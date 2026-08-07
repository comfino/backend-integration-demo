# Troubleshooting

## Composer install fails / private repository 401

If your host has a global Composer config pointing at a private repository, `composer install` on the host
can fail with a `401`. Run Composer **inside the container** instead — `./bin/composer install` — which
uses the container's clean config. `./bin/run-demo` already does this on first start.

## `401 AuthorizationError` on API calls

- Wrong or empty API key for the active environment. Check `SANDBOX_MODE` and the matching key
  (`SANDBOX_API_KEY` vs `API_KEY`) in `.env`.
- A sandbox key used against the production host (or vice-versa). The host follows `SANDBOX_MODE` unless
  `COMFINO_API_HOST_CUSTOM` is set.

## `400` cart/hash errors on `createOrder`

The cart/customer objects were changed after construction, or contain non-scalar values. Build them
complete via `OrderFactory` and don't mutate them — see [request-signing.md](request-signing.md).

## Webhook always returns 401

That's expected for an **unsigned** request — verification runs first. A genuine Comfino notification
carries a valid `CR-Signature`. To test locally you must reproduce the signature:
`hash('sha3-256', $apiKey . $rawBody)`. Also confirm the `WebhookManager` was built with the same key
Comfino signs with (the tenant's key), and that the body isn't being altered by proxies.

## Empty offer list

`getFinancialProducts()` returned no products for that amount (e.g. below a creditor's minimum). Try a
larger `loanAmount`, and confirm the account has active products in the Comfino panel.

## Pages show a 500 / "Composer dependencies are not installed"

`vendor/` is missing. Run `./bin/composer install` (or re-run `./bin/run-demo`).

## `ext-sodium` missing

Token generation and signing need `ext-sodium`. The demo's PHP image includes it; if you run outside
Docker, install/enable the extension.

## See also

- [getting-started.md](getting-started.md) · [api-client.md](api-client.md) · [webhooks.md](webhooks.md)
