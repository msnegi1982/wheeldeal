<?php

return [
    'length' => 6,
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 3),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN', 30),

    'sms_driver' => env('OTP_SMS_DRIVER', 'log'),
    'whatsapp_driver' => env('OTP_WHATSAPP_DRIVER', 'log'),

    'rate_limits' => [
        'phone_per_hour' => (int) env('OTP_PHONE_LIMIT', 5),
        'ip_per_hour' => (int) env('OTP_IP_LIMIT', 15),
    ],

    'drivers' => [
        'log' => [
            'class' => App\Services\Otp\Drivers\LogOtpDriver::class,
        ],
        'msg91' => [
            'class' => App\Services\Otp\Drivers\Msg91OtpDriver::class,
            'auth_key' => env('MSG91_AUTH_KEY'),
            'sender_id' => env('MSG91_SENDER_ID', 'WHEELD'),
            'template_id' => env('MSG91_TEMPLATE_ID'),
            'base_url' => env('MSG91_BASE_URL', 'https://control.msg91.com/api/v5'),
        ],
        'twilio' => [
            'class' => App\Services\Otp\Drivers\TwilioOtpDriver::class,
            'sid' => env('TWILIO_ACCOUNT_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'whatsapp' => [
            'class' => App\Services\Otp\Drivers\WhatsAppCloudOtpDriver::class,
            'token' => env('WHATSAPP_CLOUD_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'template_name' => env('WHATSAPP_TEMPLATE_NAME', 'otp_verification'),
            'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com/v19.0'),
        ],
    ],
];
