<?php

/**
 * Recipe 3 — query an order's status.
 *
 * GET /orders/{orderId} via Client::getOrder(). Use it to reconcile state on the return page, or from a cron job. The
 * authoritative live status still arrives via the webhook.
 */

declare(strict_types=1);

use App\Store\FileOrderStore;
use App\Support\ClientFactory;
use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 * @var ClientFactory $clientFactory
 * @var FileOrderStore $orderStore
 */
['tenant' => $tenant, 'registry' => $registry, 'clientFactory' => $clientFactory, 'orderStore' => $orderStore] = require __DIR__ . '/bootstrap.php';

View::header('Order status', $tenant, $registry, 'order-status.php');

$orderId = (string) ($_GET['orderId'] ?? '');

try {
    // Offer a picker of orders previously created for this tenant.
    $known = $orderStore->allForShop($tenant->id);
} catch (JsonException $e) {
    View::error($e);
    View::footer();

    exit;
}

echo '<div class="panel"><form method="get"><input type="hidden" name="shop" value="' . htmlspecialchars($tenant->id, ENT_QUOTES) . '">';
echo '<label>Order id: <input type="text" name="orderId" value="' . htmlspecialchars($orderId, ENT_QUOTES) . '" size="40"></label> ';
echo '<button type="submit">Look up</button></form>';

if ($known !== []) {
    echo '<p class="lead">Known orders for this tenant: ';
    echo implode(' | ', array_map(
        static fn (string $id): string => '<a href="?shop=' . urlencode($tenant->id) . '&orderId=' . urlencode($id) . '">' . htmlspecialchars($id, ENT_QUOTES) . '</a>',
        array_keys($known)
    ));
    echo '</p>';
} else {
    echo '<p class="lead">No orders yet — <a href="order-create.php?shop=' . urlencode($tenant->id) . '">create one first</a>.</p>';
}

echo '</div>';

if ($orderId !== '') {
    try {
        $order = $clientFactory->forTenant($tenant)->getOrder($orderId);

        View::success('Order ' . htmlspecialchars($orderId, ENT_QUOTES), View::keyValue([
            'status' => $order->status,
            'createdAt' => $order->createdAt?->format('c') ?? '—',
            'amount' => number_format($order->loanParameters->amount / 100, 2) . ' PLN',
            'loanTerm' => $order->loanParameters->term . ' months',
            'applicationUrl' => $order->applicationUrl,
        ]));

        // Keep the local store in sync with the remote status.
        $orderStore->setStatus($tenant->id, $orderId, $order->status);
    } catch (Throwable $e) {
        View::error($e);
    }
}

View::footer();
