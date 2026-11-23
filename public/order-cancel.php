<?php

/**
 * Recipe 4 — cancel an order.
 *
 * PUT /orders/{orderId}/cancel via Client::cancelOrder(). Returns void; a thrown HttpErrorException means it failed.
 *
 * Two failures are worth telling apart (comfino/php-api-client 4.1):
 *   - 404 NotFound: the order is unknown to ComfinoPay. cancelOrder() declares it an idempotent failure
 *     (isIdempotentFailure()), so there is nothing left to cancel and a retry is safe to treat as done.
 *   - 409 Conflict: the order is past the point where it can be cancelled and the loan is still live. This is NOT a
 *     completed cancellation — keep the order as it is in the shop.
 *
 * Cancellation is a good candidate for the SDK's durable OutboundRequestQueue in production (retry on transient failure)
 * — out of scope for this core demo.
 */

declare(strict_types=1);

use App\Store\FileOrderStore;
use App\Support\ClientFactory;
use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;
use Comfino\Api\Exception\Conflict;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 * @var ClientFactory $clientFactory
 * @var FileOrderStore $orderStore
 */
['tenant' => $tenant, 'registry' => $registry, 'clientFactory' => $clientFactory, 'orderStore' => $orderStore] = require __DIR__ . '/bootstrap.php';

View::header('Cancel order', $tenant, $registry, 'order-cancel.php');

$orderId = (string) ($_GET['orderId'] ?? '');

try {
    $known = $orderStore->allForShop($tenant->id);
} catch (JsonException $e) {
    View::error($e);
    View::footer();

    exit;
}

echo '<div class="panel"><form method="post"><input type="hidden" name="shop" value="' . htmlspecialchars($tenant->id, ENT_QUOTES) . '">';
echo '<label>Order id: <input type="text" name="orderId" value="' . htmlspecialchars($orderId, ENT_QUOTES) . '" size="40"></label> ';
echo '<button type="submit">Cancel order</button></form>';

if ($known !== []) {
    echo '<p class="lead">Known orders: ' . implode(' | ', array_map(
        static fn (string $id): string => '<code>' . htmlspecialchars($id, ENT_QUOTES) . '</code>',
        array_keys($known)
    )) . '</p>';
}

echo '</div>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (string) ($_POST['orderId'] ?? '');

    if ($orderId === '') {
        View::warn('Enter an order id to cancel.');
    } else {
        try {
            $clientFactory->forTenant($tenant)->cancelOrder($orderId);
            $orderStore->setStatus($tenant->id, $orderId, 'CANCELLED_BY_SHOP');

            View::success('Cancelled', '<p>Order <code>' . htmlspecialchars($orderId, ENT_QUOTES) . '</code> was canceled.</p>');
        } catch (Conflict) {
            // The order can no longer be canceled — do not mark it canceled locally.
            View::warn('Order ' . htmlspecialchars($orderId, ENT_QUOTES) . ' can no longer be cancelled (HTTP 409); its status is unchanged.');
        } catch (Throwable $e) {
            View::error($e);
        }
    }
}

View::footer();
