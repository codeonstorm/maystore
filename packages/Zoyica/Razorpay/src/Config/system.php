<?php

return [
    [
        'key'    => 'sales.payment_methods.razorpay',
        'name'   => 'razorpay::app.admin.system.razorpay',
        'info'   => 'razorpay::app.admin.system.razorpay-info',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'razorpay::app.admin.system.title',
                'type'          => 'text',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'description',
                'title'         => 'razorpay::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'image',
                'title'         => 'razorpay::app.admin.system.logo',
                'type'          => 'image',
                'channel_based' => false,
                'locale_based'  => false,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
            ], [
                'name'          => 'key_id',
                'title'         => 'razorpay::app.admin.system.key-id',
                'info'          => 'razorpay::app.admin.system.key-id-info',
                'type'          => 'text',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'key_secret',
                'title'         => 'razorpay::app.admin.system.key-secret',
                'info'          => 'razorpay::app.admin.system.key-secret-info',
                'type'          => 'password',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'active',
                'title'         => 'razorpay::app.admin.system.status',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'    => 'sort',
                'title'   => 'razorpay::app.admin.system.sort-order',
                'type'    => 'select',
                'options' => [
                    ['title' => '1', 'value' => 1],
                    ['title' => '2', 'value' => 2],
                    ['title' => '3', 'value' => 3],
                    ['title' => '4', 'value' => 4],
                    ['title' => '5', 'value' => 5],
                ],
            ],
        ],
    ],
];
