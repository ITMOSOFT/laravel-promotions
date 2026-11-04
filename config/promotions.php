<?php

return [
    'table_prefix' => env('PROMOTIONS_TABLE_PREFIX', 'promotion_'),
    'models' => [
        'promotion' => \ItmoSoft\Promotions\Models\Promotion::class,
        'redemption' => \ItmoSoft\Promotions\Models\Redemption::class,
    ],
    'currency' => env('PROMOTIONS_CURRENCY', 'USD'),
];
