<?php

/**
 * Mint the short-lived paywall auth token the Comfino Web Frontend SDK needs. Generate it on your backend and hand it
 * to the frontend — never expose the API key.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Comfino\Auth\PaywallAuthKeyGenerator;

$widgetKey = getenv('SANDBOX_WIDGET_KEY') ?: 'YOUR_WIDGET_KEY';
$apiKey = getenv('SANDBOX_API_KEY') ?: 'YOUR_SANDBOX_API_KEY';

// 76-byte token: timestamp + HMAC-SHA3-256 + widget key, sodium-base64, valid ~15 minutes
try {
    $authToken = (new PaywallAuthKeyGenerator())->generateAuthKey($widgetKey, $apiKey);
} catch (SodiumException $e) {
    $authToken = '[ERROR]';
}

// Hand $authToken to the frontend as `authToken` for bootstrapPaywall() / the paywall config bridge.
echo $authToken, "\n";
