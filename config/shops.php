<?php

/**
 * Demo tenant registry.
 *
 * A single ComfinoPay integration serves many shops. A custom shop is simply the one-tenant case; a SaaS platform has many.
 * Each tenant carries its own ComfinoPay credentials.
 *
 * Here we define two demo tenants. Each resolves its API/widget key from a tenant-specific env var (SHOP_A_API_KEY, ...)
 * and falls back to the shared SANDBOX_API_KEY / SANDBOX_WIDGET_KEY, so the whole demo runs with a single sandbox account.
 * In a real SaaS you would load this table from your database, one credential set per shop.
 *
 * Returns: array<string shopId, array{name: string, apiKey: string, widgetKey: string}>
 */

declare(strict_types=1);

use App\Support\Env;

$sharedApiKey = Env::apiKey();
$sharedWidgetKey = Env::widgetKey();

return [
    'shop-a' => [
        'name' => 'Shop A (electronics)',
        'apiKey' => (string) getenv('SHOP_A_API_KEY') ?: $sharedApiKey,
        'widgetKey' => (string) getenv('SHOP_A_WIDGET_KEY') ?: $sharedWidgetKey,
    ],
    'shop-b' => [
        'name' => 'Shop B (fashion)',
        'apiKey' => (string) getenv('SHOP_B_API_KEY') ?: $sharedApiKey,
        'widgetKey' => (string) getenv('SHOP_B_WIDGET_KEY') ?: $sharedWidgetKey,
    ],
];
