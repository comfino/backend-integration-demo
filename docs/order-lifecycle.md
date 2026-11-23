# Order lifecycle: create, cancel

Once you have an `Order` ([order-and-cart.md](order-and-cart.md)), the client drives its lifecycle.

Demo pages: `public/order-create.php`, `order-cancel.php`.

## Create → redirect

```php
$response = $client->createOrder($order);
$applicationUrl = $response->applicationUrl;

header('Location: ' . $applicationUrl); // Send the shopper to the ComfinoPay application.
```

The client only accepts an absolute `https` application URL (anything else fails with `ResponseValidationError`);
plain `http` is allowed solely with `COMFINO_DEV_ENV=TRUE` for local mocks.

The client attaches the request-signing headers automatically ([request-signing.md](request-signing.md)).
Persist your `orderId` now so you can reconcile later; the authoritative payment result arrives via the
webhook ([webhooks.md](webhooks.md)), not on the return URL. `OrderStatus` values include `CREATED`,
`WAITING_FOR_PAYMENT`, `ACCEPTED`, `PAID`, `REJECTED`, `RESIGN`, `CANCELLED_BY_SHOP`, `CANCELLED`.

## Cancel

```php
use Comfino\Api\Exception\Conflict;
use Comfino\Api\HttpErrorDetailsInterface;

try {
    $client->cancelOrder('ORDER-123'); // Returns void.
} catch (Conflict $e) { // 409 — the order is past the point of cancellation; the loan is still live
    // Do NOT mark the order canceled in your shop.
} catch (\Comfino\Api\HttpErrorExceptionInterface $e) {
    /* 404 for an unknown order: $e instanceof HttpErrorDetailsInterface && $e->isIdempotentFailure() — nothing to
       cancel, safe to treat as done. Anything else is a real failure — inspect $e->getStatusCode(). */
}
```

Only a 404 counts as an idempotent cancellation failure; a 409 is **not** a completed cancellation
(`comfino/php-api-client` 4.1). The demo page `order-cancel.php` shows both branches.

> In production, cancellation is a good fit for the SDK's durable **OutboundRequestQueue** (retry on
> transient failure). That's beyond this core demo — see the
[`comfino/php-sdk`](https://github.com/comfino/php-sdk) docs.

## See also

- [order-and-cart.md](order-and-cart.md) · [webhooks.md](webhooks.md) · [api-client.md](api-client.md)
