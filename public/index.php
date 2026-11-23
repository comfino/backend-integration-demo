<?php

/**
 * Landing page — lists the backend recipes and the active tenant switch.
 */

declare(strict_types=1);

use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 */
['tenant' => $tenant, 'registry' => $registry] = require __DIR__ . '/bootstrap.php';

$shop = '?shop=' . htmlspecialchars($tenant->id, ENT_QUOTES);

View::header('ComfinoPay backend integration demo', $tenant, $registry);
?>
<p class="lead">
    Server-side recipes for integrating the ComfinoPay payment gateway with a custom shop or a multi-tenant
    SaaS platform, built on the public <code>comfino/php-sdk</code> and <code>comfino/php-api-client</code>
    libraries. Each recipe runs one SDK call for the active tenant and shows the result plus its annotated
    source. Money is always in <strong>grosze</strong> (1 PLN = 100).
</p>

<h2>Payment flow</h2>
<div class="cards">
    <a class="card" href="offers.php<?= $shop ?>">
        <h3>1. Fetch offers</h3>
        <p><code>getFinancialProducts()</code> — installment offers for the demo cart amount.</p>
    </a>
    <a class="card" href="order-create.php<?= $shop ?>">
        <h3>2. Create order</h3>
        <p><code>OrderFactory</code> → <code>createOrder()</code> → redirect URL. Signing is automatic.</p>
    </a>
    <a class="card" href="order-cancel.php<?= $shop ?>">
        <h3>3. Cancel order</h3>
        <p><code>cancelOrder()</code> — cancel an order created in step 2.</p>
    </a>
    <a class="card" href="paywall-token.php<?= $shop ?>">
        <h3>4. Paywall auth token</h3>
        <p><code>PaywallAuthKeyGenerator</code> — the token your frontend hands to the Web SDK.</p>
    </a>
</div>

<h2>Webhooks</h2>
<div class="cards">
    <a class="card" href="source.php?file=webhook.php&amp;shop=<?= htmlspecialchars($tenant->id, ENT_QUOTES) ?>">
        <h3>Status webhook receiver</h3>
        <p>
           <code>WebhookManager</code> + <code>StatusNotification</code>, CR-Signature verified. Endpoint:
           <code>POST /webhook.php<?= $shop ?></code>. View the annotated source.
        </p>
    </a>
</div>

<h2>Multi-tenant</h2>
<p class="lead">
    Every recipe operates on the tenant selected above (<code><?= htmlspecialchars($tenant->id, ENT_QUOTES) ?></code>). Credentials
    are resolved per tenant in <code>config/shops.php</code>; a real SaaS loads them from its database. See
    <code>docs/multi-tenant.md</code>.
</p>
<?php
View::footer();
