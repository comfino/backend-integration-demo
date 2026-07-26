# Getting started

This guide takes you from nothing to a working **server-side** Comfino integration: fetching offers,
creating an order, handling status webhooks, and minting the paywall token your frontend needs.

It is built on two public libraries (installed from Packagist):

- **`comfino/php-api-client`** ([GitHub](https://github.com/comfino/php-api-client)) — the transport
  layer: the REST `Client`, request signing, webhook signature verification, and the paywall/log token
  generators.
- **`comfino/php-sdk`** ([GitHub](https://github.com/comfino/php-sdk)) — backend services on top of it:
  factories (`ApiClientFactory`, `OrderFactory`, `WebhookManagerFactory`), the `WebhookManager`, and
  concrete order/cart DTOs.

> **Units:** every amount is an integer in the **smallest currency unit** (grosze for PLN): `199900` =
> 1 999,00 PLN. Never use floats for money.

## Prerequisites

1. A Comfino merchant account with an **API key** (and a **widget key** for the paywall token).
2. PHP 8.1+ with `ext-curl`, `ext-json`, `ext-sodium`, `ext-zlib`. A PSR-18 HTTP client + PSR-17 factories
   — this guide uses `sunrise/http-client-curl` + `sunrise/http-message`.

## Install

```bash
composer require comfino/php-api-client comfino/php-sdk sunrise/http-client-curl sunrise/http-message
```

## The backend flow

```
1. Offers      getFinancialProducts()      → show "from X PLN/mo", validate availability
2. Token       generateAuthKey()           → hand to the frontend paywall (shopper picks an offer in the paywall; the choice comes back to your backend)
3. Order       OrderFactory + createOrder() → redirect shopper to applicationUrl
4. Webhook     WebhookManager              → Comfino notifies you of status changes
5. Status      getOrder() / cancelOrder()  → reconcile / cancel
```

## Run the demo

```bash
cp .env.dist .env      # add your sandbox API/widget key
./bin/run-demo         # http://localhost:8080 (override the port via docker-compose.override.yml)
```

Each page under `public/` runs one step of the flow and shows its annotated source.

## See also

- [api-client.md](api-client.md) — building the client, errors, retry.
- [offers.md](offers.md) · [order-and-cart.md](order-and-cart.md) · [order-lifecycle.md](order-lifecycle.md)
- [paywall-token.md](paywall-token.md) · [webhooks.md](webhooks.md) · [request-signing.md](request-signing.md)
- [multi-tenant.md](multi-tenant.md) — the SaaS pattern used throughout.
- [troubleshooting.md](troubleshooting.md)
- Library sources: [`comfino/php-api-client`](https://github.com/comfino/php-api-client) ·
  [`comfino/php-sdk`](https://github.com/comfino/php-sdk)
