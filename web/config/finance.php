<?php

return [

    'main_treasury_account' => env('FINANCE_MAIN_ACCOUNT', '1000'),

    'accounts' => [
        'accounts_receivable' => '1100',
        'tuition_revenue' => '4000',
        'application_fee_revenue' => '4010',
        'examination_fee_revenue' => '4020',
        'graduation_fee_revenue' => '4030',
        'other_fee_revenue' => '4090',
        'cash_mpesa' => '1010',
        'cash_bank' => '1020',
    ],

    'invoice_types' => [
        'application' => 'Application fee',
        'tuition' => 'Semester charges',
        'qa_annual' => 'Quality assurance (annual)',
        'indexing_nck' => 'Indexing (NCK)',
        'graduation' => 'Graduation fees',
        'supplementary' => 'Supplementary examination',
        'other' => 'Other fees',
    ],

    'payment_methods' => [
        'mpesa' => 'M-Pesa',
        'bank_transfer' => 'Bank transfer',
        'card' => 'Credit/debit card',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'eft' => 'EFT',
        'helb' => 'HELB',
        'sponsor' => 'Sponsor',
        'work_study_credit' => 'Work-study credit',
    ],

    'mpesa' => [
        'enabled' => (bool) env('MPESA_ENABLED', false),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
        'account_reference_prefix' => env('MPESA_ACCOUNT_REFERENCE_PREFIX', 'TICH'),
    ],

    'invoice_due_days' => (int) env('FINANCE_INVOICE_DUE_DAYS', 30),

    'ar' => [
        'reminder_interval_days' => (int) env('FINANCE_AR_REMINDER_INTERVAL_DAYS', 7),
        'reminder_days_before_due' => (int) env('FINANCE_AR_REMINDER_BEFORE_DUE_DAYS', 3),
        'sms_enabled' => (bool) env('FINANCE_SMS_ENABLED', false),
        'sms_api_url' => env('FINANCE_SMS_API_URL'),
        'sms_api_token' => env('FINANCE_SMS_API_TOKEN'),
        'sms_sender' => env('FINANCE_SMS_SENDER', 'TICH'),
    ],

    'fee_defaults' => [
        'application_fee' => 1000,
        'qa_annual_fee' => 1000,
        'graduation_fee' => 4000,
    ],

];
