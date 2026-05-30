<?php

return [
    'admin' => [
        'system' => [
            'razorpay'         => 'Razorpay',
            'razorpay-info'    => 'Accept payments via Razorpay — Cards, UPI, Netbanking, Wallets and more.',
            'title'            => 'Title',
            'description'      => 'Description',
            'logo'             => 'Logo',
            'key-id'           => 'Key ID',
            'key-id-info'      => 'Your Razorpay Key ID from the Razorpay Dashboard.',
            'key-secret'       => 'Key Secret',
            'key-secret-info'  => 'Your Razorpay Key Secret from the Razorpay Dashboard. Keep this confidential.',
            'status'           => 'Status',
            'sort-order'       => 'Sort Order',
        ],
    ],

    'errors' => [
        'payment-cancelled'    => 'Payment was cancelled. Please try again.',
        'invalid-response'     => 'Invalid payment response received from Razorpay.',
        'signature-mismatch'   => 'Payment verification failed. Please contact support.',
        'something-went-wrong' => 'Something went wrong while placing the order. Please contact support.',
    ],

    'checkout' => [
        'pay-now'              => 'Pay Now',
        'payment-description'  => 'Powered by Razorpay',
    ],
];
