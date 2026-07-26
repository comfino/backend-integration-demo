# Multi-tenant (SaaS) integrations

A single custom shop is the one-tenant case; a SaaS e-commerce platform serves many shops, each with its
own Comfino account. This demo is multi-tenant **by construction** — everything is keyed by a shop/tenant
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

## Webhook routing options

Comfino calls the `notificationUrl` you set on the order. Two patterns:

- **Per-tenant URL** (used here): set `notificationUrl` to `…/webhook.php?shop=<id>` and build the
  `WebhookManager` with that tenant's key. Clean isolation.
- **Single URL, many keys**: expose one endpoint and pass **every** tenant key to `apiKeys`
  (`$registry->allApiKeys()`); the manager verifies against each and you resolve the tenant from the
  payload's `externalId`. Simpler routing, verifies against all keys.

## Checklist for a real SaaS

- [ ] Store credentials per shop (encrypted at rest); load the tenant on each request.
- [ ] Build a fresh `Client` per tenant; don't cache one client across shops.
- [ ] Ensure order ids are unique across tenants, or namespace them per shop.
- [ ] Resolve the tenant in the webhook before touching order state.
- [ ] Mint paywall tokens with the correct shop's keys.

## See also

- [webhooks.md](webhooks.md) · [paywall-token.md](paywall-token.md) · [api-client.md](api-client.md)
