<?php

return [
    'brand' => [
        'name' => env('APP_NAME', 'WheelDeal'),
        'tagline' => 'Find the right four-wheeler, without the runaround.',
        'phone' => env('CONTACT_PHONE', '+91 98765 43210'),
        'whatsapp' => env('CONTACT_WHATSAPP', '919876543210'),
        'email' => env('CONTACT_EMAIL', 'hello@wheeldeal.test'),
        'address' => env('CONTACT_ADDRESS', '2nd Floor, 12 MG Road, Bengaluru, Karnataka 560001'),
        'hours' => 'Mon–Sat, 9:30am–7:00pm',
    ],

    'pagination' => [
        'listings' => 12,
        'related' => 4,
        'brands' => 24,
    ],

    'cache' => [
        'ttl' => (int) env('CATALOG_CACHE_TTL', 600),
        'home_ttl' => (int) env('HOME_CACHE_TTL', 300),
        'prefix' => 'wheeldeal',
    ],

    'uploads' => [
        'max_gallery_images' => 12,
        'max_request_photos' => 6,
        'max_image_kb' => 5120,
        'min_360_frames' => 24,
        'max_360_frames' => 36,
    ],

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK'),
        'instagram' => env('SOCIAL_INSTAGRAM'),
        'youtube' => env('SOCIAL_YOUTUBE'),
        'x' => env('SOCIAL_X'),
    ],
];
