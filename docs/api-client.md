# The API client

The ComfinoPay REST client is `Comfino\Api\Client` (from
[`comfino/php-api-client`](https://github.com/comfino/php-api-client)). Build it with the SDK's
`ApiClientFactory` (from [`comfino/php-sdk`](https://github.com/comfino/php-sdk)), which wires
exponential-backoff retry and sensible timeouts for you. This guide is written for `comfino/php-api-client` 4.1 and `comfino/php-sdk` 3.5.

## Building the client

```php
use Comfino\Backend\Factory\ApiClientFactory;
use Sunrise\Http\Client\Curl\Client as CurlClient;
use Sunrise\Http\Message\{RequestFactory, ResponseFactory, StreamFactory};

$client = (new ApiClientFactory())->createClient(
    httpClient: new CurlClient(new ResponseFactory()), // PSR-18
    requestFactory: new RequestFactory(),              // PSR-17
    streamFactory: new StreamFactory(),                // PSR-17
    apiKey: $apiKey,
    userAgent: 'MyShop/1.0.0',
    apiBaseUrl: 'https://api-ecommerce.craty.pl',      // sandbox; null → environment default
    apiLanguage: 'pl'                                  // ISO 639-1
);
```

A plain `http://` `apiBaseUrl` (local mock, Docker service name) is refused unless `COMFINO_DEV_ENV=TRUE` is set;
the real API always requires HTTPS.

The library is transport-agnostic — it ships no HTTP client. `sunrise/http-client-curl` (PSR-18) and
`sunrise/http-message` (PSR-7/17) are used here; `nyholm/psr7` + any PSR-18 client work too.

Base URLs: production `https://api-ecommerce.comfino.pl`, sandbox `https://api-ecommerce.craty.pl`.

## Operations used by this demo

| Method                                       | HTTP | Purpose                                                   |
|----------------------------------------------|------|-----------------------------------------------------------|
| `getFinancialProducts(LoanQueryCriteria $c)` | GET  | List installment offers for an amount.                    |
| `createOrder(OrderInterface $o)`             | POST | Create an order → `->applicationUrl` (signing automatic). |
| `cancelOrder(string $id)`                    | PUT  | Cancel an order (returns void).                           |

Other operations exist (`isShopAccountActive`, `getWidgetKey`, `getProductTypes`, …) — see the
[`comfino/php-api-client`](https://github.com/comfino/php-api-client) README.

## Error handling

Every API error implements `Comfino\Api\HttpErrorExceptionInterface` and exposes `getStatusCode()`:

```php
use Comfino\Api\Exception\{AuthorizationError, RequestValidationError, ServiceUnavailable, TooManyRequests};

try {
    $offers = $client->getFinancialProducts($criteria);
} catch (AuthorizationError $e) { // 401 — bad API key
    // ...
} catch (TooManyRequests $e) { // 429 — rate limited
    // $e->getRetryAfter(): seconds to wait, or null
} catch (RequestValidationError $e) { // 400 — bad request payload
    // $e->getFieldErrors(): field name => list of messages — build customer-facing text from this, not the message
} catch (ServiceUnavailable $e) { // 5xx — ComfinoPay side / retries exhausted
    // ...
}
```

Status map: 400 `RequestValidationError`, 401 `AuthorizationError`, 403 `Forbidden`, 404 `NotFound`,
405 `MethodNotAllowed`, 409 `Conflict`, 429 `TooManyRequests`, 5xx `ServiceUnavailable`, timeout `ConnectionTimeout`.

Notes (`comfino/php-api-client` 4.x):

- `TooManyRequests` **no longer extends** `RequestValidationError` — a 429 is transient, not a refusal of your data.
  A `catch (RequestValidationError)` does not catch it any more; handle it next to `ServiceUnavailable`.
- Errors are classified by HTTP status before the body is decoded, so a proxy's HTML error page is a retryable
  `ServiceUnavailable`, not a validation failure. `HttpErrorDetailsInterface` adds `hasNonNegotiatedBody()` and
  `isIdempotentFailure()` (e.g. a 404 on `cancelOrder()`).
- Request and response bodies carried by exceptions are redacted (customer data is masked), so logging a caught
  exception does not leak personal data. Still never log the API key.
- The `Comfino-Track-Id` correlation header is a UUIDv7 (32 lowercase hex characters); you may pin your own with
  `setTrackId()`.

## Retry

`ApiClientFactory` configures retry automatically (defaults: connection timeout 1s, transfer timeout 3s,
3 attempts). To tune it, pass `connectionTimeout` / `transferTimeout` / `maxRetries` to `createClient()` — values are
clamped (connection ≤ 30 s, transfer ≤ 60 s, attempts 1–5) — or build `Client` directly with a `RetryExecutor` (see the
[`comfino/php-api-client`](https://github.com/comfino/php-api-client) README).

For a busy or multi-tenant host, `createClient()` also accepts a `circuitBreaker`, an outbound `rateLimiter`, a
`requestObserver` and a `tenantKey` (partitions breaker/limiter state per tenant). They are optional and out of scope
for this core demo.

## See also

- [offers.md](offers.md) · [order-lifecycle.md](order-lifecycle.md) · [request-signing.md](request-signing.md)
- [multi-tenant.md](multi-tenant.md) — one client per tenant.
