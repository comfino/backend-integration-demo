# Order lifecycle: create, status, cancel

Once you have an `Order` ([order-and-cart.md](order-and-cart.md)), the client drives its lifecycle.

Demo pages: `public/order-create.php`, `order-status.php`, `order-cancel.php`.

## Create → redirect

```php
$response = $client->createOrder($order);
$applicationUrl = $response->applicationUrl;

header('Location: ' . $applicationUrl); // Send the shopper to the Comfino application.
```

The client attaches the request-signing headers automatically ([request-signing.md](request-signing.md)).
Persist your `orderId` now so you can reconcile later; the authoritative payment result arrives via the
webhook ([webhooks.md](webhooks.md)), not on the return URL.

## Query status

```php
$order = $client->getOrder('ORDER-123');

$order->status;                       // e.g. "ACCEPTED", "PAID", "CANCELLED"
$order->createdAt?->format('c');
$order->loanParameters->amount;       // Grosze
$order->loanParameters->term;         // Months
$order->applicationUrl;
```

`OrderStatus` values include `CREATED`, `WAITING_FOR_PAYMENT`, `ACCEPTED`, `PAID`, `REJECTED`, `RESIGN`,
`CANCELLED_BY_SHOP`, `CANCELLED`.

## Cancel

```php
try {
    $client->cancelOrder('ORDER-123'); // Returns void.
} catch (\Comfino\Api\HttpErrorExceptionInterface $e) {
    // e.g., already paid, or unknown order — inspect $e->getStatusCode()
}
```

> In production, cancellation is a good fit for the SDK's durable **OutboundRequestQueue** (retry on
> transient failure). That's beyond this core demo — see the
[`comfino/php-sdk`](https://github.com/comfino/php-sdk) docs.

## See also

- [order-and-cart.md](order-and-cart.md) · [webhooks.md](webhooks.md) · [api-client.md](api-client.md)
