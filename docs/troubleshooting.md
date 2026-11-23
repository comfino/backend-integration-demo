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
- A custom host over plain `http://` (a local mock, a Docker service name) is refused unless
  `COMFINO_DEV_ENV=TRUE` is set in the environment (exact value). Add it to `.env` for local work only —
  never in production. The real API always requires HTTPS.

## `429 TooManyRequests`

ComfinoPay is rate-limiting your key. `$e->getRetryAfter()` returns the seconds to wait (or `null`). Since
`comfino/php-api-client` 4.0 `TooManyRequests` is **not** a `RequestValidationError`, so a `catch` meant for refused
request data does not catch it — handle it explicitly, next to `ServiceUnavailable`.

## `409 Conflict` on cancel

The order can no longer be cancelled (the loan is still live). Treat it as a refusal, not as a completed
cancellation — see [order-lifecycle.md](order-lifecycle.md).

## `ResponseValidationError` on `createOrder`

The returned application URL must be an absolute `https` URL; anything else is rejected. A local mock that answers
with plain `http` works only with `COMFINO_DEV_ENV=TRUE`.

## `400` cart/hash errors on `createOrder`

The cart/customer objects were changed after construction, or contain non-scalar values. Build them
complete via `OrderFactory` and don't mutate them — see [request-signing.md](request-signing.md).

## Webhook always returns 401

That's expected for an **unsigned** request — verification runs first. A genuine ComfinoPay notification
carries a valid `CR-Signature`. To test locally you must reproduce the signature:
`hash('sha3-256', $apiKey . $rawBody)`. Also confirm the `WebhookManager` was built with the same key
ComfinoPay signs with (the tenant's key), that the key is at least 16 characters (shorter keys are dropped), and
that the body isn't being altered by proxies.

The response is deliberately generic (`{"error":"Unauthorized."}`) and does not say which check failed; read
`$webhookManager->getLastVerificationOutcome()` in your own code to see the reason. A `403` means an IP
allow-list mismatch — behind a proxy check the trusted-proxy setup ([webhooks.md](webhooks.md)).

## Empty offer list

`getFinancialProducts()` returned no products for that amount (e.g. below a creditor's minimum). Try a
larger `loanAmount`, and confirm the account has active products in the ComfinoPay panel.

## Pages show a 500 / "Composer dependencies are not installed"

`vendor/` is missing. Run `./bin/composer install` (or re-run `./bin/run-demo`).

## `ext-mbstring` missing

`comfino/php-sdk` 3.3+ requires `ext-mbstring`. The demo's PHP image includes it; enable it if you run outside
Docker.

## `ext-sodium` missing

Token generation and signing need `ext-sodium`. The demo's PHP image includes it; if you run outside
Docker, install/enable the extension.

## See also

- [getting-started.md](getting-started.md) · [api-client.md](api-client.md) · [webhooks.md](webhooks.md)
