# Fetching offers

Use `Client::getFinancialProducts()` to list the installment offers Comfino has for a given cart amount.
A shop uses this to render "from X PLN/month" messaging or to decide whether to show Comfino at all.

Demo page: `public/offers.php`.

## Query

```php
use Comfino\Api\Dto\Payment\LoanQueryCriteria;

$criteria = new LoanQueryCriteria(
    loanAmount: 199900, // Grosze — the only required field
    // loanTerm: 12,
    // loanType: LoanType::INSTALLMENTS_ZERO_PERCENT,
    // productTypes: ['INSTALLMENTS_ZERO_PERCENT', 'PAY_LATER'],
);

$response = $client->getFinancialProducts($criteria);
$products = $response->financialProducts; // FinancialProduct[]
```

## Reading a `FinancialProduct`

Each offer exposes (money in grosze):

```php
foreach ($products as $p) {
    $p->type->getValue();   // e.g. "INSTALLMENTS_ZERO_PERCENT" (LoanType enum)
    $p->creditorName;       // Lender name
    $p->instalmentAmount;   // Monthly instalment, grosze
    $p->toPay;              // Total to repay, grosze
    $p->loanTerm;           // Months
    $p->rrso;               // APR (%)
}
```

An empty array means Comfino has no offer for that amount (e.g., below a creditor's minimum) — hide the
payment method in that case.

## See also

- [order-and-cart.md](order-and-cart.md) — turning a chosen offer into an order.
- [api-client.md](api-client.md) — errors and retry.
