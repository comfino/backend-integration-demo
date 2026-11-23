<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Multi-tenant registry: resolves a shop id to its ComfinoPay credentials.
 *
 * Backed by config/shops.php. A custom single-shop integration would have exactly one tenant; a SaaS platform loads
 * this from its own database keyed by shop/tenant id.
 */
final class TenantRegistry
{
    /** @var array<string, Tenant> */
    private array $tenants = [];

    private string $defaultId;

    public function __construct(string $configFile)
    {
        /** @var array<string, array{name: string, apiKey: string, widgetKey: string}> $shops */
        $shops = require $configFile;

        foreach ($shops as $id => $shop) {
            $this->tenants[$id] = new Tenant($id, $shop['name'], $shop['apiKey'], $shop['widgetKey']);
        }

        $this->defaultId = array_key_first($this->tenants) ?? '';
    }

    /** Resolve a tenant by id, falling back to the first configured tenant. */
    public function get(?string $id): Tenant
    {
        if ($id !== null && isset($this->tenants[$id])) {
            return $this->tenants[$id];
        }

        return $this->tenants[$this->defaultId];
    }

    /** @return array<string, Tenant> */
    public function all(): array
    {
        return $this->tenants;
    }

    /** Every tenant's API key — a SaaS webhook receiver verifies against all of them. */
    public function allApiKeys(): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (Tenant $tenant): string => $tenant->apiKey, $this->tenants)
        )));
    }
}
