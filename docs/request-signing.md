# Request signing

Order creation is cryptographically signed so ComfinoPay can verify the cart/customer data was not tampered
with and that the request came from an authenticated shop. **You don't write this code** — the API client
does it automatically inside `createOrder()`. This page explains what happens so you can debug signature
errors.

## The three headers

When you call `Client::createOrder($order)`, the client computes and attaches:

| Header | Value |
|---|---|
| `Comfino-Cart-Hash` | `SHA3-256(json(cart))` |
| `Comfino-Customer-Hash` | `SHA3-256(json(customer))` |
| `Comfino-Order-Signature` | `SHA3-256_HMAC(cartHash . customerHash, apiKey)` |

(The client also sends a `Comfino-Track-Id` correlation header — a UUIDv7 as 32 lowercase hex characters since
`comfino/php-api-client` 4.1.)

The server recomputes the hashes from the received body and the signature from your API key; a mismatch
means the data or key is wrong.

## What this means for you

- **Don't mutate** the `Cart`/`Customer` objects after building them — build them complete via
  `OrderFactory` / their constructors ([order-and-cart.md](order-and-cart.md)). Added/changed fields
  change the hash.
- **Use the right API key** for the environment (sandbox key with the sandbox host, live key with
  production). A wrong key yields a `401`/signature error, not a hash error.
- JSON serialization is kept byte-consistent by the library (`JSON_UNESCAPED_SLASHES |
  JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK`); you don't need to replicate it.

## Troubleshooting

| Symptom                   | Likely cause                                                           |
|---------------------------|------------------------------------------------------------------------|
| `400` Cart hash mismatch. | Cart/customer object mutated after creation, or non-scalar values.     |
| `401` Invalid signature.  | Wrong API key, or sandbox key against production host (or vice-versa). |

The reverse direction (ComfinoPay signing webhooks it sends **to** you) uses `CR-Signature` — see
[webhooks.md](webhooks.md).

## See also

- [order-and-cart.md](order-and-cart.md) · [order-lifecycle.md](order-lifecycle.md) · [webhooks.md](webhooks.md)
