<?php

declare(strict_types=1);

return [
    'google' => [
        'places_api_key' => env('MFW_GOOGLE_PLACES_KEY', env('MFW_INPUTABLE_GOOGLE_PLACES_KEY', env('MFW_INPUT_GOOGLE_PLACES_KEY', ''))),
    ],
    'countries_resolver' => null,
];
