# Comfino Backend SDK — integration guide & runnable demo

[![Latest Version](https://img.shields.io/badge/release-1.1.0-blue.svg)](https://github.com/comfino/backend-integration-demo/releases)
[![Software License](https://img.shields.io/badge/license-BSD%203--Clause-orange.svg)](LICENSE)

Everything a developer needs to integrate **Comfino** payments on the **server side** — for a single
custom shop or a multi-tenant **SaaS e-commerce platform**. It pairs a written guide (`docs/`),
copy-paste PHP (`snippets/`), and a **runnable Docker application** (`public/` + `src/`) built on the
public Comfino backend libraries:

- [`comfino/php-api-client`](https://packagist.org/packages/comfino/php-api-client) `^2.1`
  ([GitHub](https://github.com/comfino/php-api-client)) — the transport layer:
  REST `Client`, request signing, webhook signature verification, paywall token generation.
- [`comfino/php-sdk`](https://packagist.org/packages/comfino/php-sdk) `^2.0`
  ([GitHub](https://github.com/comfino/php-sdk)) — backend services on top of it:
  `ApiClientFactory`, `OrderFactory`, `WebhookManager`, order/cart DTOs.

The frontend side (checkout paywall + product widget) lives in the separate **Web Frontend SDK** guide [web-frontend-sdk](https://github.com/comfino/web-frontend-sdk);
this repository is its backend counterpart.

> **Money is always in the smallest currency unit** (grosze for PLN) on the API/wire boundary — `199900`
> means `1 999,00 PLN`.

## What the demo covers (core payment flow)

| Recipe (page) | SDK surface | Guide |
|---|---|---|
| `offers.php` | `Client::getFinancialProducts(LoanQueryCriteria)` | [docs/offers.md](docs/offers.md) |
| `order-create.php` | `OrderFactory` → `Client::createOrder()` → `applicationUrl` | [docs/order-and-cart.md](docs/order-and-cart.md), [docs/order-lifecycle.md](docs/order-lifecycle.md) |
| `order-cancel.php` | `Client::cancelOrder()` | [docs/order-lifecycle.md](docs/order-lifecycle.md) |
| `paywall-token.php` | `Auth\PaywallAuthKeyGenerator` | [docs/paywall-token.md](docs/paywall-token.md) |
| `webhook.php` | `WebhookManager` + `StatusNotification`, `CR-Signature` verification | [docs/webhooks.md](docs/webhooks.md) |

Request signing (cart/customer/signature headers) happens automatically inside `createOrder()` — see
[docs/request-signing.md](docs/request-signing.md).

## Who it's for

- **Custom shops** — the single-tenant case. Wire one API key and follow the recipes.
- **SaaS platforms** — the demo is **multi-tenant by construction**: credentials, order storage, and the
  webhook receiver are all keyed by a shop/tenant id. See [docs/multi-tenant.md](docs/multi-tenant.md).

## Requirements

- Docker + Docker Compose (the demo runs fully in containers: PHP 8.1-FPM + Nginx).
- Comfino **sandbox** credentials (API key, and a widget key for the paywall-token recipe). Optional —
  pages render without them; live API calls need at least an API key.

## Run it

```bash
cp .env.dist .env       # then edit .env and add your sandbox API/widget key
./bin/run-demo          # builds images, starts containers, installs Composer deps in the container
```

Open <http://localhost:8080/>. Stop with `./bin/stop-demo`.

> **Changing the port:** the demo is served on host port **8080** by default. To use a different port,
> copy `docker-compose.override.yml.dist` to `docker-compose.override.yml` and edit the `ports` mapping —
> Compose merges it automatically, and the file is git-ignored so it won't affect anyone else.

Helper scripts run tools **inside** the container: `./bin/composer <cmd>`, `./bin/php <args>`. Running
Composer in the container also avoids any private-repository config on your host.

> **Security:** `.env`, `vendor/`, and `var/` are git-ignored. This project maps to a **public** GitHub
> repository — never commit real API/widget keys, emails, or phone numbers. Only `.env.dist` (placeholders)
> is tracked.

## Testing the webhook

The receiver verifies the `CR-Signature` header before doing anything, so an unsigned request is rejected:

```bash
curl -i -X POST "http://localhost:8080/webhook.php?shop=shop-a" -d '{"status":"PAID","externalId":"DEMO_ORDER_1"}'
# → HTTP/1.1 401  (signature missing/invalid)
```

Real notifications carry a valid `CR-Signature` computed by Comfino from your API key.

## Layout

```
bin/        run-demo, stop-demo, composer, php
config/     Docker (php-fpm + nginx) and the demo tenant registry (shops.php)
docs/       backend integration guide (one file per use case) + multi-tenant + troubleshooting
public/     runnable recipe pages + webhook.php + source viewer
snippets/   copy-paste PHP: api-client, create-order, paywall-token, verify-webhook
src/        App\ classes: Env, TenantRegistry, ClientFactory, View, FileOrderStore, OrderStatusAdapter
```

## License

BSD-3-Clause
