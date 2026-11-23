<?php

/**
 * Recipe 5 — mint a paywall auth token.
 *
 * The ComfinoPay Web Frontend SDK (checkout paywall) needs a short-lived V3 auth token that only your backend can produce
 * (it is an HMAC-SHA3-256 over the widget key, keyed with the secret API key). Generate it here and hand it to the
 * frontend — never expose the API key to the browser.
 *
 * The token is a 76-byte value: 8-byte timestamp + 32-byte HMAC + 36-byte widget key, sodium-base64 encoded, valid ~15
 * minutes. See the frontend guide for how the SDK consumes it.
 */

declare(strict_types=1);

use App\Support\Env;
use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;
use Comfino\Auth\PaywallAuthKeyGenerator;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 */
['tenant' => $tenant, 'registry' => $registry] = require __DIR__ . '/bootstrap.php';

View::header('Paywall auth token', $tenant, $registry, 'paywall-token.php');

try {
    if ($tenant->widgetKey === '' || !$tenant->hasCredentials()) {
        View::warn('This tenant needs both an API key and a widget key configured to mint a token.');
    } else {
        $token = (new PaywallAuthKeyGenerator())->generateAuthKey($tenant->widgetKey, $tenant->apiKey);

        View::success(
            'Token minted',
            View::keyValue([
                'tenant' => $tenant->id,
                'environment' => Env::environment(),
                'authToken' => $token,
                'validFor' => '~15 minutes',
            ]) . '<p class="lead">Pass this as <code>authToken</code> to the Web Frontend SDK ' .
            '(<code>bootstrapPaywall()</code> / the paywall config bridge).</p>'
        );
    }
} catch (SodiumException $e) {
    View::error($e);
}

View::footer();
