<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Store\FileOrderStore;
use Comfino\Shop\Order\StatusAdapterInterface;

/**
 * Bridges Comfino order-status webhooks to the shop's own order storage.
 *
 * The SDK's StatusNotification endpoint calls setStatus($orderId, $status) after it has verified the CR-Signature and
 * filtered out forbidden/ignored statuses. Here we resolve which tenant owns the order (multi-tenant) and persist the
 * new status.
 *
 * In a real integration this is where you would update the order in your database and trigger fulfillment / customer
 * e-mails on a paid status.
 */
final class OrderStatusAdapter implements StatusAdapterInterface
{
    public function __construct(private readonly FileOrderStore $store)
    {
    }

    /**
     * @throws \JsonException
     */
    public function setStatus(string $orderId, string $status): void
    {
        $shopId = $this->store->findShopForOrder($orderId);

        if ($shopId === null) {
            /* Unknown order — a real integration would log this and return without failing, so Comfino still receives a
               success acknowledgement. */
            return;
        }

        $this->store->setStatus($shopId, $orderId, $status);
    }
}
