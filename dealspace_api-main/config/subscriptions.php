<?php

return [
    'plans' => [
        'basic' => [
            'name' => 'Basic Plan',
            'price_id' => env('STRIPE_BASIC_PRICE_ID'),
            'price' => 9.99,
            'features' => [
                'Up to 5 deals per month',
                'Up to 100 contacts',
                'Basic reporting',
                'Email support',
            ],
        ],
        'pro' => [
            'name' => 'Pro Plan',
            'price_id' => env('STRIPE_PRO_PRICE_ID'),
            'price' => 29.99,
            'features' => [
                'Unlimited deals',
                'Unlimited contacts',
                'Advanced reporting & analytics',
                'Priority email & chat support',
                'Custom fields',
                'API access',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise Plan',
            'price_id' => env('STRIPE_ENTERPRISE_PRICE_ID'),
            'price' => 99.99,
            'features' => [
                'Everything in Pro',
                'Advanced integrations',
                'SLA guarantee',
                'Custom training',
                '24/7 phone support',
            ],
        ],
    ],
];