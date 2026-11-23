# Paywall auth token

The ComfinoPay Web Frontend SDK (checkout paywall) needs a short-lived **auth token** that only your backend
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
- Tokens expire; if the checkout page can live longer than ~15 minutes, refresh the token. Expose a small
  backend endpoint that mints a token for the current tenant and pass it to the frontend as
  `authTokenProvider` — the paywall controller calls it when the token is older than ~12 minutes and applies the
  result (see the *SaaS token renewal* section of the Web Frontend SDK `custom-platform` guide). Never mint tokens
  from an endpoint that accepts an arbitrary tenant id from an unauthenticated caller.
- With `comfino/php-sdk`, `PaywallConfigBuilder::buildConfig()` assembles the whole paywall config (token,
  `flags`, `flagAttributes`, `loggingToken`, `trackingConsent`) for hosts that use the SDK's settings layer; this
  demo covers the token only.

## See also

- [request-signing.md](request-signing.md) — the same crypto family used for order signing.
- [multi-tenant.md](multi-tenant.md) — mint per-tenant tokens with each tenant's keys.
