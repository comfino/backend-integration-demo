<?php

/**
 * Shared bootstrap for every recipe page.
 *
 *  - Loads the Composer autoloader (comfino/php-sdk + php-api-client + sunrise/http).
 *  - Builds the multi-tenant registry and resolves the active tenant from ?shop=.
 *  - Exposes shared services: $registry, $tenant, $clientFactory, $orderStore.
 *
 * Recipe pages `require` this, then make exactly one SDK call and render the result. Verbose errors are on for demo
 * clarity — do NOT do that in production.
 */

declare(strict_types=1);

use App\Store\FileOrderStore;
use App\Support\ClientFactory;
use App\Support\TenantRegistry;

ini_set('display_errors', '1');
error_reporting(E_ALL);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);

    die("Composer dependencies are not installed. Run `./bin/run-demo` or `composer install`.");
}

require_once $autoload;

$registry = new TenantRegistry(dirname(__DIR__) . '/config/shops.php');
$tenant = $registry->get($_GET['shop'] ?? null);

$clientFactory = new ClientFactory();
$orderStore = new FileOrderStore(dirname(__DIR__) . '/var/orders');

/**
 * Fixed demo cart shared by the order/offers recipes. All money in grosze (1 PLN = 100).
 */
$demoCart = [
    'productName' => 'Demo product',
    'productUnitNetPrice' => 172050, // 1720.50 PLN
    'vatRate' => 23, // %
    'quantity' => 2,
    'deliveryNetCost' => 1499, // 14.99 PLN
];
$demoCart['productUnitPrice'] = (int) round($demoCart['productUnitNetPrice'] * (1 + $demoCart['vatRate'] / 100));
$demoCart['deliveryCost'] = (int) round($demoCart['deliveryNetCost'] * (1 + $demoCart['vatRate'] / 100));
$demoCart['loanAmount'] = $demoCart['productUnitPrice'] * $demoCart['quantity'] + $demoCart['deliveryCost'];

/** Current request base URL (for return/notification callbacks). */
$baseUrl = (static function (): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8084');
})();

return compact('registry', 'tenant', 'clientFactory', 'orderStore', 'demoCart', 'baseUrl');
