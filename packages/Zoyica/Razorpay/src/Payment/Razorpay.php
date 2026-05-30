<?php

namespace Zoyica\Razorpay\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Storage;

class Razorpay extends Payment
{
    /**
     * Payment method code.
     */
    protected $code = 'razorpay';

    /**
     * Razorpay API base URL.
     */
    protected string $apiBase = 'https://api.razorpay.com/v1';

    /**
     * Return redirect URL to Razorpay checkout page.
     */
    public function getRedirectUrl(): string
    {
        return route('razorpay.redirect');
    }

    /**
     * Return Razorpay Key ID from config.
     */
    public function getKeyId(): string
    {
        return $this->getConfigData('key_id') ?? '';
    }

    /**
     * Return Razorpay Key Secret from config.
     */
    public function getKeySecret(): string
    {
        return $this->getConfigData('key_secret') ?? '';
    }

    /**
     * Create a Razorpay order and return the order response.
     *
     * @param  int  $amountInPaise  Amount in smallest currency unit (paise for INR)
     * @param  string  $currency  ISO 4217 currency code
     * @param  string  $receipt  Unique receipt reference (e.g. cart ID)
     *
     * @throws \InvalidArgumentException When payment data is invalid.
     * @throws \RuntimeException When the Razorpay API call fails.
     */
    public function createOrder(int $amountInPaise, string $currency, string $receipt): array
    {
        $this->validatePaymentData([
            'amount'   => $amountInPaise,
            'currency' => $currency,
            'receipt'  => $receipt,
        ]);

        $this->logPaymentActivity('order.creating', [
            'amount'   => $amountInPaise,
            'currency' => $currency,
            'receipt'  => $receipt,
        ]);

        try {
            $response = Http::withBasicAuth($this->getKeyId(), $this->getKeySecret())
                ->timeout(30)
                ->post("{$this->apiBase}/orders", [
                    'amount'   => $amountInPaise,
                    'currency' => $currency,
                    'receipt'  => $receipt,
                ]);

            if ($response->failed()) {
                $errorDescription = $response->json('error.description') ?? 'Razorpay API error.';

                $this->logPaymentActivity('order.failed', [
                    'receipt'          => $receipt,
                    'http_status'      => $response->status(),
                    'error_code'       => $response->json('error.code'),
                    'error_description'=> $errorDescription,
                ]);

                throw new \RuntimeException($errorDescription);
            }

            $order = $response->json();

            $this->logPaymentActivity('order.created', [
                'razorpay_order_id' => $order['id'] ?? null,
                'receipt'           => $receipt,
                'amount'            => $amountInPaise,
                'currency'          => $currency,
            ]);

            return $order;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->handlePaymentError($e, ['receipt' => $receipt]);
        }
    }

    /**
     * Verify Razorpay payment signature using HMAC-SHA256.
     *
     * @param  string  $razorpayOrderId
     * @param  string  $razorpayPaymentId
     * @param  string  $razorpaySignature
     */
    public function verifySignature(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
    {
        $payload = $razorpayOrderId . '|' . $razorpayPaymentId;

        $expectedSignature = hash_hmac('sha256', $payload, $this->getKeySecret());

        $isValid = hash_equals($expectedSignature, $razorpaySignature);

        $this->logPaymentActivity($isValid ? 'signature.verified' : 'signature.mismatch', [
            'razorpay_order_id'   => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
        ]);

        return $isValid;
    }

    /**
     * Validate payment data before sending to Razorpay.
     *
     * @param  array  $data
     *
     * @throws \InvalidArgumentException When validation fails.
     */
    protected function validatePaymentData(array $data): void
    {
        $validator = validator($data, [
            'amount'   => 'required|integer|min:100',
            'currency' => 'required|string|size:3',
            'receipt'  => 'required|string|max:40',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->first();

            Log::warning("Razorpay [{$this->code}] payment validation failed", [
                'errors' => $validator->errors()->toArray(),
            ]);

            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * Handle a payment exception — log the error and re-throw as RuntimeException.
     *
     * @param  \Exception  $e
     * @param  array  $context  Extra context to log (must not contain secrets).
     *
     * @throws \RuntimeException
     */
    protected function handlePaymentError(\Exception $e, array $context = []): never
    {
        Log::error("Razorpay [{$this->code}] payment error: {$e->getMessage()}", array_merge(
            ['trace' => $e->getTraceAsString()],
            $context
        ));

        throw new \RuntimeException(
            'Payment processing failed. Please try again or contact support.',
            0,
            $e
        );
    }

    /**
     * Log payment activities for debugging and audit. Sensitive keys are never logged.
     *
     * @param  string  $action  Dot-notated action label e.g. "order.created".
     * @param  array  $data  Contextual data (must not include secrets).
     */
    protected function logPaymentActivity(string $action, array $data = []): void
    {
        // Strip any accidentally passed sensitive fields.
        $sanitized = array_diff_key($data, array_flip([
            'key_id',
            'key_secret',
            'api_key',
            'secret_key',
            'card_number',
            'cvv',
            'razorpay_signature',
        ]));

        Log::info("Razorpay [{$this->code}] {$action}", $sanitized);
    }

    /**
     * Get payment method image.
     *
     * @return array
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : 'https://razorpay.com/favicon.png';
    }    
}
