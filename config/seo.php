<?php

return [
    'site_name' => env('APP_NAME', 'WheelDeal'),
    'default_title' => env('SEO_DEFAULT_TITLE', 'WheelDeal — Buy & Sell Used Cars in India'),
    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Browse verified used SUVs, sedans, hatchbacks and EVs by brand, budget, fuel and city. Compare specs, view 360° interiors and connect with trusted sellers.'
    ),
    'default_image' => env('SEO_DEFAULT_IMAGE', '/images/og-default.jpg'),
    'twitter_handle' => env('SEO_TWITTER_HANDLE', '@wheeldeal'),
    'robots' => 'index,follow',
    'robots_disallow' => [
        '/admin',
        '/api',
        '/verify-otp',
    ],
    'sitemap_path' => public_path('sitemap.xml'),
    'sitemap_cache_minutes' => 60,
];
