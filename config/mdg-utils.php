<?php

declare(strict_types=1);

// Publish with: php artisan vendor:publish --tag=mdg-utils

return [
    // How @ariary, AriaryAmount and AsAriary write and read amounts.
    'ariary' => [
        // Thousands separator: "space" (Ar 1 500, the Malagasy default), "dot" (Ar 1.500)
        // or "comma" (Ar 1,500, English style; amounts are then read in that style only).
        'grouping' => env('MDG_UTILS_ARIARY_GROUPING', 'space'),

        // "before" (Ar 1 500) or "after" (1 500 Ar).
        'symbol_position' => env('MDG_UTILS_ARIARY_SYMBOL_POSITION', 'before'),

        // Decimal separator: "comma" (Ar 1 500,50, the Malagasy default) or "dot".
        // It must differ from the grouping.
        'decimal' => env('MDG_UTILS_ARIARY_DECIMAL', 'comma'),
    ],
];
