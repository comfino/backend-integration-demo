<?php

/**
 * Recipe 2 — build and create an order.
 *
 * Assembles the order graph with OrderFactory and submits it with Client::createOrder().
 *
 * The API client attaches the request-signing headers (Comfino-Cart-Hash / Comfino-Customer-Hash / Comfino-Order-Signature)
 * automatically. On success ComfinoPay returns an application URL to redirect the shopper to the ComfinoPay application from.
 *
 * The loan type/term normally comes from the offer the shopper picked in the frontend paywall; here we fetch offers first
 * and use the first one so the demo is self-contained.
 */

declare(strict_types=1);

use App\Store\FileOrderStore;
use App\Support\ClientFactory;
use App\Support\Env;
use App\Support\Tenant;
use App\Support\TenantRegistry;
use App\Support\View;
use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Backend\Factory\OrderFactory;
use Comfino\Enum\LoanType;
use Comfino\Shop\Order\Cart\CartItem;
use Comfino\Shop\Order\Cart\Product;
use Comfino\Shop\Order\Customer;
use Comfino\Shop\Order\Customer\Address;

/**
 * @var Tenant $tenant
 * @var TenantRegistry $registry
 * @var ClientFactory $clientFactory
 * @var FileOrderStore $orderStore
 * @var array $demoCart
 * @var string $baseUrl
 */
[
    'tenant' => $tenant,
    'registry' => $registry,
    'clientFactory' => $clientFactory,
    'orderStore' => $orderStore,
    'demoCart' => $demoCart,
    'baseUrl' => $baseUrl,
] = require __DIR__ . '/bootstrap.php';

View::header('Create order', $tenant, $registry, 'order-create.php');

$cart = $demoCart;
$loanAmount = $cart['loanAmount'];
$vatValue = $cart['productUnitPrice'] - $cart['productUnitNetPrice'];
$deliveryVatValue = $cart['deliveryCost'] - $cart['deliveryNetCost'];

try {
    $client = $clientFactory->forTenant($tenant);

    // Pick a loan type/term from the available offers (would be the shopper's paywall choice).
    $offers = $client->getFinancialProducts(new LoanQueryCriteria(loanAmount: $loanAmount))->financialProducts;

    $loanType = $offers !== [] ? $offers[0]->type : LoanType::INSTALLMENTS_ZERO_PERCENT;
    $loanTerm = $offers !== [] ? $offers[0]->loanTerm : 12;

    $orderId = sprintf('DEMO_%s_%d', strtoupper(str_replace('-', '', $tenant->id)), time());

    $cartItems = [
        new CartItem(
            new Product(
                name: $cart['productName'],
                price: $cart['productUnitPrice'], // Gross, grosze
                id: 'DEMO-SKU-1',
                category: 'Electronics',
                ean: null,
                photoUrl: null,
                categoryIds: [2, 11, 35],
                netPrice: $cart['productUnitNetPrice'],
                taxRate: $cart['vatRate'],
                taxValue: $vatValue
            ),
            $cart['quantity']
        ),
    ];

    $customer = new Customer(
        firstName: 'Jan',
        lastName: 'Testowy',
        email: Env::get('TEST_EMAIL', 'jan.testowy@example.com'),
        phoneNumber: Env::get('TEST_PHONE_NUMBER', '+48123456789'),
        ip: $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        taxId: null,
        isRegular: true,
        isLogged: true,
        address: new Address(
            street: 'Testowa',
            buildingNumber: '12',
            apartmentNumber: null,
            postalCode: '02-673',
            city: 'Warszawa',
            countryCode: 'PL'
        )
    );

    $order = (new OrderFactory())->createOrder(
        orderId: $orderId,
        orderTotal: $loanAmount,
        deliveryCost: $cart['deliveryCost'],
        loanTerm: $loanTerm,
        loanType: $loanType,
        cartItems: $cartItems,
        customer: $customer,
        returnUrl: $baseUrl . '/index.php?shop=' . urlencode($tenant->id),
        notificationUrl: $baseUrl . '/webhook.php?shop=' . urlencode($tenant->id),
        allowedProductTypes: null,
        deliveryNetCost: $cart['deliveryNetCost'],
        deliveryCostTaxRate: $cart['vatRate'],
        deliveryCostTaxValue: $deliveryVatValue
    );

    $applicationUrl = $client->createOrder($order)->applicationUrl;

    // Record the order so the status/cancel recipes and the webhook can resolve this tenant.
    $orderStore->put($tenant->id, $orderId, 'CREATED');

    View::success(
        'Order created',
        View::keyValue([
            'orderId' => $orderId,
            'loanType' => $loanType->getValue(),
            'loanTerm' => $loanTerm . ' months',
            'orderTotal' => number_format($loanAmount / 100, 2) . ' PLN',
            'applicationUrl' => $applicationUrl,
        ]) . '<p><a class="btn" href="' . htmlspecialchars($applicationUrl, ENT_QUOTES) . '" target="_blank" rel="noopener">Open ComfinoPay application →</a></p>' .
        '<p class="lead">Next: <a href="order-cancel.php?shop=' . urlencode($tenant->id) . '&orderId=' . urlencode($orderId) . '">cancel it</a>, or wait for the status webhook.</p>');
} catch (Throwable $e) {
    View::error($e);
}

View::footer();
