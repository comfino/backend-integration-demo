# Multi-tenant (SaaS) integrations

A single custom shop is the one-tenant case; a SaaS e-commerce platform serves many shops, each with its
own ComfinoPay account. This demo is multi-tenant **by construction** — everything is keyed by a shop/tenant
id — so the same code serves both audiences.

## The three things you key by tenant

1. **Credentials** — each shop has its own API key (and widget key). Never share one key across shops.
2. **Order storage / status** — an inbound webhook must resolve *which* shop an order belongs to.
3. **Paywall tokens** — mint them with the specific shop's keys.

## How the demo does it

- `config/shops.php` is the tenant registry: `shopId → { name, apiKey, widgetKey }`. Each entry resolves
  from a tenant-specific env var (`SHOP_A_API_KEY`, …) and falls back to the shared `SANDBOX_API_KEY` so
  the demo runs on one sandbox account. **A real SaaS loads this from its database**, one row per shop.
- `src/Support/TenantRegistry.php` resolves a tenant; recipe pages read `?shop=` (default: the first
  tenant). `src/Support/ClientFactory.php` builds an isolated `Client` per tenant from that tenant's key.
- `src/Store/FileOrderStore.php` stores orders per tenant (`var/orders/<shop>.json`) and can locate the
  owning tenant for a given order id — used by the webhook.
- `src/Webhook/OrderStatusAdapter.php` resolves the tenant from the order id, then updates that tenant's
  order.
- `public/webhook.php` scopes `StatusManager::getInstance($adapter, $tenant->id)` to the current shop.
  As of `comfino/php-sdk` 3.0, `StatusManager` (and the other former process-global singletons —
  `ErrorLogger`, `DebugLogger`, `SettingsManager`, `ProductTypeFilterManager`) key their instance by an
  optional `$scope` string; without one, the *first* tenant to call `getInstance()` in a long-lived
  worker process owns it for every tenant after it.

## Webhook routing options

ComfinoPay calls the `notificationUrl` you set on the order. Two patterns:

- **Per-tenant URL** (used here): set `notificationUrl` to `…/webhook.php?shop=<id>` and build the
  `WebhookManager` with that tenant's key. Clean isolation.
- **Single URL, many keys**: expose one endpoint and pass **every** tenant key to `apiKeys`
  (`$registry->allApiKeys()`); the manager verifies against each and you resolve the tenant from the
  payload's `externalId`. Simpler routing, verifies against all keys.
- **Single URL, resolved tenant** (`comfino/php-sdk` 3.0+): pass a `WebhookTenantResolverInterface`
  instead of an array to `WebhookManagerFactory::createWebhookManager()`'s `apiKeys` argument. It reads
  the tenant from the request and returns *that* tenant's key alone, so the signature is verified
  against one merchant's secret instead of every merchant's — out of scope for this core demo, but the
  right choice for a SaaS platform with more than a couple of tenants.

## Checklist for a real SaaS

- [ ] Store credentials per shop (encrypted at rest); load the tenant on each request.
- [ ] Build a fresh `Client` per tenant; don't cache one client across shops. A long-lived worker can instead share
  one `SharedClient` (`comfino/php-api-client` 3.x) and pass each tenant's `ApiContext` per call — it holds no
  credentials, so one transport serves every tenant.
- [ ] Ensure order ids are unique across tenants, or namespace them per shop.
- [ ] Resolve the tenant in the webhook before touching order state.
- [ ] If you use the SDK's cache, write through `CacheManager::forScope($tenant)` and pass the same tenant to
  `new CacheInvalidate($name, $url, $pool, $tenant)` (`comfino/php-sdk` 3.5: tags are stored per scope, so an
  invalidation reaches only that tenant). Clear the pool once after upgrading from an earlier version.
- [ ] When you use the resilience options of `ApiClientFactory::createClient()` (circuit breaker, rate limiter),
  pass a per-tenant `tenantKey` so one shop's failures do not open the breaker for the others.
- [ ] Mint paywall tokens with the correct shop's keys.

## See also

- [webhooks.md](webhooks.md) · [paywall-token.md](paywall-token.md) · [api-client.md](api-client.md)
