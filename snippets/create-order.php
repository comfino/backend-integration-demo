<?php

/**
 * Build an order with OrderFactory and submit it. The client attaches the request-signing headers automatically; on
 * success you get an application URL to redirect the shopper to.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Comfino\Backend\Factory\ApiClientFactory;
use Comfino\Backend\Factory\OrderFactory;
use Comfino\Enum\LoanType;
use Comfino\Shop\Order\Cart\CartItem;
use Comfino\Shop\Order\Cart\Product;
use Comfino\Shop\Order\Customer;
use Comfino\Shop\Order\Customer\Address;
use Psr\Http\Client\ClientExceptionInterface;
use Sunrise\Http\Client\Curl\Client as CurlClient;
use Sunrise\Http\Message\RequestFactory;
use Sunrise\Http\Message\ResponseFactory;
use Sunrise\Http\Message\StreamFactory;

$apiKey = getenv('SANDBOX_API_KEY') ?: 'YOUR_SANDBOX_API_KEY';

$client = (new ApiClientFactory())->createClient(
    httpClient: new CurlClient(new ResponseFactory()),
    requestFactory: new RequestFactory(),
    streamFactory: new StreamFactory(),
    apiKey: $apiKey,
    apiBaseUrl: 'https://api-ecommerce.craty.pl'
);

// All money in grosze (1 PLN = 100).
$cartItems = [
    new CartItem(
        new Product(
            name: 'Laptop XYZ',
            price: 211622, // Gross unit price
            id: 'SKU-001',
            category: 'Electronics',
            ean: null,
            photoUrl: null,
            categoryIds: [2, 11],
            netPrice: 172050,
            taxRate: 23,
            taxValue: 39572
        ),
        quantity: 2
    ),
];

$customer = new Customer(
    firstName: 'Jan',
    lastName: 'Kowalski',
    email: 'jan.kowalski@example.com',
    phoneNumber: '+48123456789',
    ip: '127.0.0.1',
    taxId: null,
    isRegular: true,
    isLogged: true,
    address: new Address('Testowa', '12', null, '02-673', 'Warszawa', 'PL')
);

// The loanType / loanTerm come from the offer the shopper picked in the paywall.
$order = (new OrderFactory())->createOrder(
    orderId: 'ORDER-' . time(),
    orderTotal: 425089, // Items + delivery, grosze
    deliveryCost: 1845,
    loanTerm: 12,
    loanType: LoanType::INSTALLMENTS_ZERO_PERCENT,
    cartItems: $cartItems,
    customer: $customer,
    returnUrl: 'https://my-shop.example/order/confirm',
    notificationUrl: 'https://my-shop.example/comfino/webhook'
);

try {
    $applicationUrl = $client->createOrder($order)->applicationUrl;
} catch (ClientExceptionInterface $e) {
    echo $e->getMessage(), "\n";

    $applicationUrl = '[ERROR]';
}

// Redirect the shopper: header('Location: ' . $applicationUrl);
echo $applicationUrl, "\n";
