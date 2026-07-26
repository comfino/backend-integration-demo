<?php

declare(strict_types=1);

namespace App\Store;

/**
 * Minimal per-tenant order store (JSON files under var/orders/<shop>.json).
 *
 * Stands in for a real shop database. It records which tenant owns an order and its last known status, so the
 * status/cancel recipes and the webhook receiver can resolve a tenant from an order id. A SaaS platform would
 * use its own multi-tenant orders table instead.
 */
final class FileOrderStore
{
    public function __construct(private readonly string $baseDir)
    {
    }

    /**
     * Record (or update) an order for a tenant.
     *
     * @throws \JsonException
     */
    public function put(string $shopId, string $orderId, string $status): void
    {
        $orders = $this->allForShop($shopId);
        $orders[$orderId] = ['status' => $status, 'updatedAt' => date('c')];

        $this->write($shopId, $orders);
    }

    /**
     * @throws \JsonException
     */
    public function setStatus(string $shopId, string $orderId, string $status): void
    {
        // Same persistence as put(); kept as a separate name to read clearly at call sites.
        $this->put($shopId, $orderId, $status);
    }

    /**
     * @return array{status: string, updatedAt: string}|null
     *
     * @throws \JsonException
     */
    public function find(string $shopId, string $orderId): ?array
    {
        return $this->allForShop($shopId)[$orderId] ?? null;
    }

    /**
     * Locate which tenant owns an order id (webhooks arrive without a shop hint).
     *
     * @throws \JsonException
     */
    public function findShopForOrder(string $orderId): ?string
    {
        foreach (glob($this->baseDir . '/*.json') ?: [] as $file) {
            $orders = $this->decode($file);

            if (isset($orders[$orderId])) {
                return basename($file, '.json');
            }
        }

        return null;
    }

    /**
     * @return array<string, array{status: string, updatedAt: string}>
     *
     * @throws \JsonException
     */
    public function allForShop(string $shopId): array
    {
        return $this->decode($this->fileFor($shopId));
    }

    private function fileFor(string $shopId): string
    {
        return $this->baseDir . '/' . preg_replace('/[^a-z0-9_-]/i', '_', $shopId) . '.json';
    }

    /**
     * @return array<string, array{status: string, updatedAt: string}>
     *
     * @throws \JsonException
     */
    private function decode(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @throws \JsonException
     */
    private function write(string $shopId, array $orders): void
    {
        if (!is_dir($this->baseDir) && !mkdir($this->baseDir, 0775, true) && !is_dir($this->baseDir)) {
            throw new \RuntimeException("Cannot create order store directory: {$this->baseDir}");
        }

        file_put_contents(
            $this->fileFor($shopId),
            json_encode($orders, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
