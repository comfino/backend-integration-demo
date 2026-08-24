# Webhooks

Comfino notifies your backend of order status changes by POSTing to the `notificationUrl` you set on the
order. Every request is signed with a `CR-Signature` header — `SHA3-256(apiKey + rawBody)` — which you
**must verify** before acting on it.

There are two ways to handle webhooks; pick per how much you need.

## Option A — `WebhookManager` (recommended)

The SDK's `WebhookManager` (from [`comfino/php-sdk`](https://github.com/comfino/php-sdk)) verifies the
signature, enforces a 1 MB body limit, optionally does replay
protection / rate limiting / IP allow-listing, and dispatches to typed endpoints. Demo page:
`public/webhook.php`.

```php
use Comfino\Api\Serializer\Json;
use Comfino\Backend\Factory\WebhookManagerFactory;
use Comfino\Backend\Webhook\Endpoint\StatusNotification;
use Comfino\Shop\Order\StatusManager;
use Sunrise\Http\Message\{ResponseFactory, ServerRequestFactory, StreamFactory, UriFactory};

$statusManager = StatusManager::getInstance($yourStatusAdapter); // Implements StatusAdapterInterface

$manager = (new WebhookManagerFactory())->createWebhookManager(
    platformName: 'MyShop',
    platformVersion: '1.0.0',
    pluginVersion: '1.0.0',
    apiKeys: [$apiKey], // One or many (SaaS: try every tenant key)
    serverRequestFactory: new ServerRequestFactory(),
    streamFactory: new StreamFactory(),
    uriFactory: new UriFactory(),
    responseFactory: new ResponseFactory(),
    serializer: new Json()
);

$manager->registerEndpoint(new StatusNotification(
    name: 'status',
    endpointUrl: '/comfino/webhook',
    statusManager: $statusManager,
    forbiddenStatuses: StatusManager::DEFAULT_FORBIDDEN_STATUSES,
    ignoredStatuses: StatusManager::DEFAULT_IGNORED_STATUSES
));

$response = $manager->processRequest(endpointName: 'status'); // PSR-7 response; 401 if unsigned
// Emit $response (status code, headers, body).
```

Your status adapter is where your order state changes:

```php
use Comfino\Shop\Order\StatusAdapterInterface;

final class OrderStatusAdapter implements StatusAdapterInterface
{
    public function setStatus(string $orderId, string $status): void
    {
        // Update your order; trigger fulfillment / emails on PAID, etc.
    }
}
```

`processRequest()` builds the PSR-7 server request from PHP globals when you don't pass one, and returns a
PSR-7 response (401 on a bad/missing signature, 201/200/204 on success). The demo emits it with a tiny
`emitResponse()` helper.

Beyond `StatusNotification`, the SDK also offers `CacheInvalidate` and `Configuration` endpoints — out of
scope for this core demo.

## Option B — `WebhookSignatureVerifier` (lightweight)

If you only need one status endpoint and no extra infrastructure, verify the signature yourself with
`Comfino\Auth\WebhookSignatureVerifier` (from
[`comfino/php-api-client`](https://github.com/comfino/php-api-client)). Snippet:
`snippets/verify-webhook.php`.

```php
use Comfino\Auth\WebhookSignatureVerifier;

$rawBody = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_CR_SIGNATURE'] ?? '';

if (!(new WebhookSignatureVerifier())->verify($signature, $apiKey, $rawBody)) {
    http_response_code(401);
    exit;
}
// Parse $rawBody, update your order, return 200.
```

`verify()` uses a timing-safe comparison (`hash_equals`). Always verify against the **raw** body, not a
re-encoded one.

## Best practices

- Verify **before** parsing or acting on the payload.
- Acknowledge quickly (2xx) and offload slow work (emails, fulfillment) to a queue.
- Add **replay protection** (store processed signatures with a TTL) for high-value flows —
  `WebhookManagerFactory` accepts an optional `ReplayProtectionInterface` and `RateLimiterInterface`
  (or, as of `comfino/php-sdk` 3.0, their tenant-aware counterparts — see
  [multi-tenant.md](multi-tenant.md) for the SaaS case).
- Never log the API key or the signature.

## See also

- [order-lifecycle.md](order-lifecycle.md) · [request-signing.md](request-signing.md) · [multi-tenant.md](multi-tenant.md)
