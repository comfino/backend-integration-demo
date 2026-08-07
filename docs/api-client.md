# The API client

The Comfino REST client is `Comfino\Api\Client` (from
[`comfino/php-api-client`](https://github.com/comfino/php-api-client)). Build it with the SDK's
`ApiClientFactory` (from [`comfino/php-sdk`](https://github.com/comfino/php-sdk)), which wires
exponential-backoff retry and sensible timeouts for you.

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
use Comfino\Api\Exception\{AuthorizationError, RequestValidationError, ServiceUnavailable};

try {
    $offers = $client->getFinancialProducts($criteria);
} catch (AuthorizationError $e) { // 401 — bad API key
    // ...
} catch (RequestValidationError $e) { // 400 — bad request payload
    // ...
} catch (ServiceUnavailable $e) { // 5xx — Comfino side / retries exhausted
    // ...
}
```

Status map: 400 `RequestValidationError`, 401 `AuthorizationError`, 403 `Forbidden`, 404 `NotFound`,
405 `MethodNotAllowed`, 409 `Conflict`, 5xx `ServiceUnavailable`, timeout `ConnectionTimeout`.

## Retry

`ApiClientFactory` configures retry automatically (defaults: connection timeout 1s, transfer timeout 3s,
3 attempts). To tune it, pass `connectionTimeout` / `transferTimeout` / `maxRetries` to `createClient()`,
or build `Client` directly with a `RetryExecutor` (see the
[`comfino/php-api-client`](https://github.com/comfino/php-api-client) README).

## See also

- [offers.md](offers.md) · [order-lifecycle.md](order-lifecycle.md) · [request-signing.md](request-signing.md)
- [multi-tenant.md](multi-tenant.md) — one client per tenant.
