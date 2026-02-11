<?php
/**
 * Payment Gateway Configuration
 * 
 * This file will store API credentials for real payment providers
 * When you obtain the actual APIs, update the credentials here
 */

return [
    // Airtel Money API Configuration
    'airtel_money' => [
        'enabled' => false, // Set to true when you have real API credentials
        'mode' => 'sandbox', // 'sandbox' or 'production'
        'api_key' => '', // Your Airtel Money API Key
        'api_secret' => '', // Your Airtel Money API Secret
        'merchant_id' => '', // Your Merchant ID
        'base_url' => [
            'sandbox' => 'https://openapi.airtel.africa/merchant/v1',
            'production' => 'https://openapi.airtel.africa/merchant/v1'
        ],
        'currency' => 'MWK',
        'country' => 'MW'
    ],

    // TNM Mpamba API Configuration
    'tnm_mpamba' => [
        'enabled' => false, // Set to true when you have real API credentials
        'mode' => 'sandbox',
        'api_key' => '', // Your TNM Mpamba API Key
        'api_secret' => '', // Your TNM Mpamba API Secret
        'merchant_code' => '', // Your Merchant Code
        'base_url' => [
            'sandbox' => 'https://sandbox-api.tnm.co.mw/v1',
            'production' => 'https://api.tnm.co.mw/v1'
        ],
        'currency' => 'MWK'
    ],

    // Paychannel API Configuration
    'paychannel' => [
        'enabled' => false, // Set to true when you have real API credentials
        'mode' => 'sandbox',
        'api_key' => '', // Your Paychannel API Key
        'merchant_id' => '', // Your Merchant ID
        'base_url' => [
            'sandbox' => 'https://sandbox.paychannel.mw/api',
            'production' => 'https://api.paychannel.mw/api'
        ],
        'currency' => 'MWK'
    ],

    // PayChanger API Configuration
    'paychanger' => [
        'enabled' => true,
        'mode' => 'test', // 'test' or 'live'
        'public_key' => 'pub-test-Mn7AJSBw3lGkLmPjJynTUrDo8Pb8yvNx',
        'secret_key' => 'sec-test-k19GPZqK1zEHIQq129UNEf7itCkPNwRP',
        'base_url' => [
            'test' => 'https://api.paychanger.com/v1', // Assuming standard URL, adjust if known
            'live' => 'https://api.paychanger.com/v1'
        ],
        'currency' => 'MWK'
    ],

    // Virtual Account Settings (for demo/testing)
    'virtual_mode' => [
        'enabled' => true, // When all above are enabled, set this to false
        'auto_approve' => true, // Auto-approve withdrawals in virtual mode
        'simulate_delay' => 2 // Seconds to simulate API delay
    ]
];
?>
