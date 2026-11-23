<?php

/**
 * Webhook receiver — order status notifications from ComfinoPay.
 *
 * ComfinoPay POSTs status changes here (the notificationUrl we set on the order). Each request is signed with a
 * `CR-Signature` header (SHA3-256 over the API key and raw body). We use the SDK's WebhookManager, which:
 *   1. Verifies the signature against the tenant API key(s) BEFORE any handling (401 on fail).
 *   2. Enforces a 1 MB body limit and (optionally) replay protection / rate limiting.
 *   3. Dispatches to a registered StatusNotification endpoint, which calls our OrderStatusAdapter to persist the new
 *      status — skipping forbidden/ignored statuses.
 *
 * Multi-tenant: the tenant is taken from ?shop=; its key goes into `apiKeys`. A SaaS platform can instead pass every
 * tenant key ($registry->allApiKeys()) and let the manager match.
 *
 * The manager returns a PSR-7 response which we emit verbatim.
 */

declare(strict_types=1);

use App\Store\FileOrderStore;
use App\Support\Env;
use App\Support\Tenant;
use App\Webhook\OrderStatusAdapter;
use Comfino\Api\Serializer\Json;
use Comfino\Backend\Factory\WebhookManagerFactory;
use Comfino\Backend\Webhook\Endpoint\StatusNotification;
use Comfino\Shop\Order\StatusManager;
use Psr\Http\Message\ResponseInterface;
use Sunrise\Http\Message\ResponseFactory;
use Sunrise\Http\Message\ServerRequestFactory;
use Sunrise\Http\Message\StreamFactory;
use Sunrise\Http\Message\UriFactory;

/**
 * @var Tenant $tenant
 * @var FileOrderStore $orderStore
 */
['tenant' => $tenant, 'orderStore' => $orderStore] = require __DIR__ . '/bootstrap.php';

// The active tenant's key (SaaS: use $registry->allApiKeys() to accept any tenant)
$apiKeys = array_values(array_filter([$tenant->apiKey]));

/* $tenant->id scopes the singleton to this shop, so a long-lived PHP-FPM worker that later serves a different shop's
   webhook doesn't reuse this request's adapter for it. */
$statusManager = StatusManager::getInstance(new OrderStatusAdapter($orderStore), $tenant->id);

$webhookManager = (new WebhookManagerFactory())->createWebhookManager(
    platformName: Env::get('PLATFORM_NAME', 'CustomBackendDemo'),
    platformVersion: Env::get('PLATFORM_VERSION', '1.0.0'),
    pluginVersion: Env::get('PLUGIN_VERSION', '1.0.0'),
    apiKeys: $apiKeys,
    serverRequestFactory: new ServerRequestFactory(),
    streamFactory: new StreamFactory(),
    uriFactory: new UriFactory(),
    responseFactory: new ResponseFactory(),
    serializer: new Json()
);

$webhookManager->registerEndpoint(new StatusNotification(
    name: 'status',
    endpointUrl: '/webhook.php',
    statusManager: $statusManager,
    forbiddenStatuses: StatusManager::DEFAULT_FORBIDDEN_STATUSES,
    ignoredStatuses: StatusManager::DEFAULT_IGNORED_STATUSES
));

// Verify + dispatch. Builds the PSR-7 ServerRequest from PHP globals internally.
$response = $webhookManager->processRequest(endpointName: 'status');

emitResponse($response);

/** Emit a PSR-7 response (status line, headers, body) to the SAPI. */
function emitResponse(ResponseInterface $response): void
{
    http_response_code($response->getStatusCode());

    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header($name . ': ' . $value, false);
        }
    }

    echo (string) $response->getBody();
}
