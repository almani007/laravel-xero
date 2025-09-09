<?php
return [
    'client_id' => env('XERO_CLIENT_ID', ''),
    'client_secret' => env('XERO_CLIENT_SECRET', ''),
    'redirect_uri' => env('XERO_REDIRECT_URI', ''),
    'scopes' => ['openid','profile','email','accounting.transactions','accounting.contacts'],
];