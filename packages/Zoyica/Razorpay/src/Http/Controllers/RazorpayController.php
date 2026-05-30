<?php

namespace Zoyica\Razorpay\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Zoyica\Razorpay\Payment\Razorpay;

class RazorpayController extends Controller
{
    public function __construct(
        protected Razorpay $razorpay,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Redirect to Razorpay checkout and create a Razorpay order.
     */
    public function redirect()
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $amountInPaise = (int) round($cart->grand_total * 100);
            $currency      = $cart->cart_currency_code;
            $receipt       = 'cart_' . $cart->id;

            $razorpayOrder = $this->razorpay->createOrder($amountInPaise, $currency, $receipt);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Razorpay redirect: invalid order parameters', [
                'cart_id' => $cart->id,
                'error'   => $e->getMessage(),
            ]);

            session()->flash('error', $e->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        } catch (\Exception $e) {
            Log::error('Razorpay redirect: failed to create order', [
                'cart_id' => $cart->id,
                'error'   => $e->getMessage(),
            ]);

            session()->flash('error', trans('razorpay::app.errors.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }

        return view('razorpay::redirect', [
            'cart'          => $cart,
            'razorpayOrder' => $razorpayOrder,
            'keyId'         => $this->razorpay->getKeyId(),
        ]);
    }

    /**
     * Handle successful payment callback. Verify signature and create Bagisto order.
     */
    public function success()
    {
        // Strict request validation — reject tampered or incomplete payloads.
        $data = request()->validate([
            'razorpay_order_id'   => ['required', 'string', 'regex:/^order_[A-Za-z0-9]+$/'],
            'razorpay_payment_id' => ['required', 'string', 'regex:/^pay_[A-Za-z0-9]+$/'],
            'razorpay_signature'  => ['required', 'string', 'size:64'],
        ]);

        Log::info('Razorpay success callback received', [
            'razorpay_order_id'   => $data['razorpay_order_id'],
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            // Never log the signature or secrets.
        ]);

        if (! $this->razorpay->verifySignature(
            $data['razorpay_order_id'],
            $data['razorpay_payment_id'],
            $data['razorpay_signature']
        )) {
            Log::warning('Razorpay signature verification failed', [
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'ip'                  => request()->ip(),
            ]);

            session()->flash('error', trans('razorpay::app.errors.signature-mismatch'));

            return redirect()->route('shop.checkout.cart.index');
        }

        // Idempotency guard — prevent duplicate orders for the same payment
        $existing = $this->orderRepository->findOneWhere([
            'razorpay_payment_id' => $data['razorpay_payment_id'],
        ]);

        if ($existing) {
            Log::info('Razorpay duplicate success callback ignored', [
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'existing_order_id'   => $existing->id,
            ]);

            session()->flash('order_id', $existing->id);

            return redirect()->route('shop.checkout.onepage.success');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            Log::warning('Razorpay success: cart not found after signature verification', [
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
            ]);

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            Cart::collectTotals();

            $orderData = (new OrderResource($cart))->jsonSerialize();

            /** @var \Webkul\Sales\Models\Order $order */
            $order = $this->orderRepository->create($orderData);

            $this->orderRepository->update([
                'status'              => 'processing',
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_order_id'   => $data['razorpay_order_id'],
            ], $order->id);

            if ($order->canInvoice()) {
                $invoiceData = ['order_id' => $order->id];

                foreach ($order->items as $item) {
                    $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
                }

                $this->invoiceRepository->create($invoiceData);
            }

            Cart::deActivateCart();

            Log::info('Razorpay order placed successfully', [
                'bagisto_order_id'    => $order->id,
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
            ]);

            session()->flash('order_id', $order->id);

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed after verified payment', [
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'error'               => $e->getMessage(),
                'trace'               => $e->getTraceAsString(),
            ]);

            session()->flash('error', trans('razorpay::app.errors.something-went-wrong'));

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel()
    {
        Log::info('Razorpay payment cancelled by user', ['ip' => request()->ip()]);

        session()->flash('error', trans('razorpay::app.errors.payment-cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }
}
