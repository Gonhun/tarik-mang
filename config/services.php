<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    // WhatsApp gateway (scan QR di Integrasi > WhatsApp). Dipakai oleh sendMessageWithWhatsApp().
    'whatsapp' => [
        'url' => rtrim((string) env('WHATSAPP_API_URL', ''), '/'),
        'key' => env('WHATSAPP_API_KEY'),
        'instance' => env('WHATSAPP_INSTANCE', 'ams'),
        'verify_ssl' => (bool) env('WHATSAPP_VERIFY_SSL', true),
    ],

    'loginwa' => [
        'url' => env('LOGINWA_API_URL'),
        'number' => env('LOGINWA_NUMBER'),
        'key' => env('LOGINWA_PRIVATE_KEY'),
        'verify_ssl' => (bool) env('LOGINWA_VERIFY_SSL', true),
    ],

    'fortigate' => [
        'url' => env('FORTIGATE_API_URL', 'https://192.168.4.1:8443/api/v2'),
        'token' => env('FORTIGATE_API_TOKEN'),
        // Fortigate di jaringan internal biasanya memakai sertifikat self-signed.
        'verify_ssl' => (bool) env('FORTIGATE_VERIFY_SSL', false),
    ],

    'ainya' => [
        'whatsapp_webhook_url' => env('AINYA_WHATSAPP_WEBHOOK_URL'),
        'isr_billing_url' => env('AINYA_ISR_BILLING_URL', 'https://ainya.binasaranasukses.co.id/webhook/api/billingisr'),
        'isr_invoice_url' => env('AINYA_ISR_INVOICE_URL', 'https://ainya.binasaranasukses.co.id/webhook/api/detailInvoice'),
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'secret_token' => env('N8N_SECRET_TOKEN'),
    ],
    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
