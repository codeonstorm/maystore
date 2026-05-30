<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('razorpay::app.checkout.payment-description') }}</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
        }
        .loading {
            text-align: center;
            color: #555;
        }
        .loading p {
            margin-top: 1rem;
            font-size: 1rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top-color: #528ff0;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <p>@lang('razorpay::app.checkout.pay-now')…</p>
    </div>

    {{-- Hidden form to submit payment details to our success endpoint --}}
    <form id="razorpay-success-form" method="POST" action="{{ route('razorpay.success') }}">
        @csrf
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature"  id="razorpay_signature">
    </form>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        var options = {
            key:         @json($keyId),
            amount:      @json($razorpayOrder['amount']),
            currency:    @json($razorpayOrder['currency']),
            name:        @json(core()->getCurrentChannel()->name),
            description: @json(__('razorpay::app.checkout.payment-description')),
            order_id:    @json($razorpayOrder['id']),

            prefill: {
                name:    @json(trim(($cart->billing_address?->first_name ?? '') . ' ' . ($cart->billing_address?->last_name ?? ''))),
                email:   @json($cart->billing_address?->email ?? ''),
                contact: @json($cart->billing_address?->phone ?? '')
            },

            theme: {
                color: "#528ff0"
            },

            handler: function (response) {
                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
                document.getElementById('razorpay_signature').value  = response.razorpay_signature;
                document.getElementById('razorpay-success-form').submit();
            },

            modal: {
                ondismiss: function () {
                    window.location.href = "{{ route('razorpay.cancel') }}";
                }
            }
        };

        var rzp = new Razorpay(options);

        rzp.on('payment.failed', function (response) {
            window.location.href = "{{ route('razorpay.cancel') }}"
                + '?error=' + encodeURIComponent(response.error.description);
        });

        rzp.open();
    </script>
</body>
</html>
