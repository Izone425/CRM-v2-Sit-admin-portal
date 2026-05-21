<?php

return [
    'webhook_secret' => env('TEAMS_LEAD_WEBHOOK_SECRET'),

    'defaults' => [
        'categories' => 'Active',
        'stage' => 'Transfer',
        'lead_status' => 'New',
    ],

    'owner_aliases' => [
        'jaja' => 'Nurul Najaa Nadiah',
        'sheena' => 'Sheena Liew',
    ],
];
