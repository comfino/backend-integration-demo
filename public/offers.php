<?php

/**
 * Recipe 1 — fetch installment offers for a cart amount.
 *
 * GET /financial-products via Client::getFinancialProducts(LoanQueryCriteria).
 *
 * A shop calls this to show "from X PLN/month" style offers, or to validate that ComfinoPay has any offer for the current
 * cart before displaying the payment method.
 */

declare(strict_types=1);

use App\Support\ClientFactory;
use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;
use Comfino\Api\Dto\Payment\LoanQueryCriteria;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 * @var ClientFactory $clientFactory
 * @var array $demoCart
 */
['tenant' => $tenant, 'registry' => $registry, 'clientFactory' => $clientFactory, 'demoCart' => $demoCart] = require __DIR__ . '/bootstrap.php';

View::header('Fetch offers', $tenant, $registry, 'offers.php');

$loanAmount = $demoCart['loanAmount']; // Grosze

View::note(sprintf(
    'Querying offers for a cart of <strong>%s PLN</strong> (%d grosze).',
    number_format($loanAmount / 100, 2),
    $loanAmount
));

try {
    $client = $clientFactory->forTenant($tenant);

    // Only loanAmount is required; loanTerm / loanType / productTypes narrow the result.
    $criteria = new LoanQueryCriteria(loanAmount: $loanAmount);

    $response = $client->getFinancialProducts($criteria);
    $products = $response->financialProducts;

    if ($products === []) {
        View::note('No offers returned for this amount.');
    } else {
        $rows = '<table class="kv"><tr><th>Type</th><th>Creditor</th><th>Instalment</th><th>To pay</th><th>Term</th><th>RRSO</th></tr>';

        foreach ($products as $product) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s PLN</td><td>%s PLN</td><td>%d mo</td><td>%.2f%%</td></tr>',
                htmlspecialchars($product->type->getValue(), ENT_QUOTES),
                htmlspecialchars($product->creditorName, ENT_QUOTES),
                number_format($product->instalmentAmount / 100, 2),
                number_format($product->toPay / 100, 2),
                $product->loanTerm,
                $product->rrso
            );
        }

        $rows .= '</table>';

        View::success(count($products) . ' offer(s) returned', $rows);
    }
} catch (Throwable $e) {
    View::error($e);
}

View::footer();
