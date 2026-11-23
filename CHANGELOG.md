# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-11-23

Requires `comfino/php-api-client` `^4.1` and `comfino/php-sdk` `^3.5`, moves the demo container to PHP 8.2 (PHP 8.1
stays the minimum) and rebrands the guide to ComfinoPay. See **Upgrade notes**.

### Added

- **`public/order-cancel.php` tells a refused cancellation apart.** It catches `Comfino\Api\Exception\Conflict` (HTTP
  409: the order can no longer be cancelled) and leaves the stored order status unchanged instead of marking it
  cancelled.
- **Optional `promoCode` / `directRedirect` arguments documented** for `OrderFactory::createOrder()`
  (`PromoCode::normalize()`, `PromoCode::REQUEST_FIELD`), with a commented example in `snippets/create-order.php`.
- **`ext-mbstring` declared** in `composer.json` (required by `comfino/php-sdk` 3.3+).

### Changed

- **Rebranded to ComfinoPay:** updated naming, copyright, and branding assets (logo) across the README, docs,
  guide/demo prose, `LICENSE`, release scripts and the mirror workflow. Code identifiers (`Comfino\...` namespace,
  `comfino/*` package names, `Comfino-*`/`CR-Signature` wire header names, `COMFINO_*` env vars, and
  `comfino.pl`/`craty.pl` domains) are unchanged: this is a marketing-copy rebrand, not an API break.
- **Dependencies:** `comfino/php-api-client` `^3.0` → `^4.1`, `comfino/php-sdk` `^3.0` → `^3.5`. The demo's client,
  order and webhook factory calls are unchanged.
- **Demo container runs PHP 8.2** (`php:8.2-fpm-alpine`, currently 8.2.36) instead of 8.1. The minimum supported PHP
  version is unchanged: `composer.json` still requires `php >=8.1`, and the code was checked with `php -l` on 8.1.
  Rebuild the image with `docker compose build --pull` to pick up the latest 8.2 patch release.
- **Docs refreshed for the new library behavior:** error map (`TooManyRequests` no longer extends
  `RequestValidationError`; `HttpErrorDetailsInterface`), cancel semantics (404 idempotent, 409 refused), HTTPS-only
  application URL, generic webhook rejection responses and `getLastVerificationOutcome()`, 16-character minimum webhook
  key, `CANCELLED_BY_SHOP` in the default forbidden statuses, reverse-proxy client IP and `IpWhitelist::forComfino()`,
  clamped timeouts and the optional resilience arguments of `ApiClientFactory::createClient()`, UUIDv7
  `Comfino-Track-Id`, `authTokenProvider` token renewal, per-tenant cache scope and breaker partitioning.

### Upgrade notes

- Run `./bin/composer update` (inside the container) to pick up the new library versions.
- Code that copies these recipes and catches `RequestValidationError` should also handle
  `Comfino\Api\Exception\TooManyRequests` (HTTP 429), usually next to `ServiceUnavailable`.
- Hosts that implement `OrderInterface` themselves must add `getPromoCode(): ?string` and
  `getDirectRedirect(): ?bool`; this demo uses the SDK's `Order` and needs no change.
- A cancellation answered with HTTP 409 is a refusal, not a success; only a 404 is an idempotent failure.

## [1.2.0] - 2026-08-24

### Changed

- Require `comfino/php-api-client` `^3.0` and `comfino/php-sdk` `^3.0`. Both are backward-compatible for
  this demo's usage (no breaking-change surface — `getOrder()`, `AbstractClient::getRequest()`/
  `sendRequest()`, `Psr18ErrorDetector`, `ConfigurationManager` — is used here); the client/webhook
  factory calls are unchanged.
- `public/webhook.php` scopes `StatusManager::getInstance()` to the current tenant (new optional
  `$scope` argument in SDK 3.0), demonstrating the fix for the former process-global singleton hazard
  in a long-lived multi-tenant worker. See [docs/multi-tenant.md](docs/multi-tenant.md).
- Documented the new tenant-aware webhook interfaces (`WebhookTenantResolverInterface`,
  `TenantAwareRateLimiterInterface`/`TenantAwareReplayProtectionInterface`) in
  [docs/multi-tenant.md](docs/multi-tenant.md) and [docs/webhooks.md](docs/webhooks.md) as SaaS options
  beyond this core demo's scope.

## [1.1.0] - 2026-08-07

### Removed

- `order-status.php` recipe and all references to `Client::getOrder()`, which is being removed in the
  next `comfino/php-api-client` release. Order status should be reconciled via the status webhook
  instead.

## [1.0.0] - 2026-07-26

### Added

- Initial public release: getting-started, API client, offers, order-and-cart, order-lifecycle,
  paywall-token, webhooks, request-signing, multi-tenant, and troubleshooting docs.
- Runnable Docker demo (PHP 8.1-fpm-alpine + nginx) covering the core payment flow: offers, order
  create/status/cancel, paywall auth token, and a webhook receiver.
- Copy-paste snippets: `api-client.php`, `create-order.php`, `paywall-token.php`, `verify-webhook.php`.
- Multi-tenant support with per-shop credential resolution (`config/shops.php`), demoed via two
  sample tenants (`shop-a`, `shop-b`).

[Unreleased]: https://github.com/comfino/backend-integration-demo/compare/1.3.0...HEAD
[1.3.0]: https://github.com/comfino/backend-integration-demo/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/comfino/backend-integration-demo/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/comfino/backend-integration-demo/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/comfino/backend-integration-demo/releases/tag/1.0.0
