# Paywall auth token

The Comfino Web Frontend SDK (checkout paywall) needs a short-lived **auth token** that only your backend
can produce — it is an HMAC-SHA3-256 keyed with your **secret API key**, so it must never be generated in
the browser. This is the one piece of backend work the frontend integration depends on.

Demo page: `public/paywall-token.php`. Snippet: `snippets/paywall-token.php`.

## Generate

```php
use Comfino\Auth\PaywallAuthKeyGenerator;

$authToken = (new PaywallAuthKeyGenerator())->generateAuthKey($widgetKey, $apiKey);
```

The token is a 76-byte value (8-byte timestamp + 32-byte HMAC + 36-byte widget key), sodium-base64
encoded, and valid for about **15 minutes**. Mint a fresh one per checkout page render.

## Hand-off to the frontend

Expose the token to your frontend and pass it as `authToken`:

- **SDK API:** `bootstrapPaywall({ authToken, loanAmount, environment, platform: 'generic' })` /
  `sdk.createPaywall({ authToken, loanAmount, ... })`.
- **Config bridge:** put it in the `#comfino-paywall-config` JSON block.

`loanAmount` is the cart total in **grosze**. See the Web Frontend SDK guide for the frontend side.

## Security

- Never send the **API key** to the browser — only the derived `authToken`.
- Tokens expire; if the checkout page can live longer than ~15 minutes, refresh the token (e.g. a small
  backend endpoint the page calls before mounting the paywall).

## See also

- [request-signing.md](request-signing.md) — the same crypto family used for order signing.
- [multi-tenant.md](multi-tenant.md) — mint per-tenant tokens with each tenant's keys.
