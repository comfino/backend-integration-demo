# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/comfino/backend-integration-demo/compare/1.1.0...HEAD
[1.1.0]: https://github.com/comfino/backend-integration-demo/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/comfino/backend-integration-demo/releases/tag/1.0.0
