# Building the order & cart

Comfino needs the full cart, customer, and loan parameters to create an order. The recommended path is the
SDK's `OrderFactory`, which assembles the object graph from flat parameters and stays stable across SDK
versions.

Demo page: `public/order-create.php`.

> All money is integer **grosze**. `loanType` / `loanTerm` come from the offer the shopper selected in the
> paywall (see [offers.md](offers.md) and the frontend guide).

## With `OrderFactory` (recommended)

```php
use Comfino\Backend\Factory\OrderFactory;
use Comfino\Enum\LoanType;
use Comfino\Shop\Order\Cart\{CartItem, Product};
use Comfino\Shop\Order\Customer;
use Comfino\Shop\Order\Customer\Address;

$cartItems = [
    new CartItem(
        new Product(
            name: 'Laptop XYZ',
            price: 211622, // Gross unit price, grosze
            id: 'SKU-001',
            category: 'Electronics',
            ean: null,
            photoUrl: null,
            categoryIds: [2, 11],
            netPrice: 172050,
            taxRate: 23, // %
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
    ip: $_SERVER['REMOTE_ADDR'],
    taxId: null, // NIP for B2B products
    isRegular: true,
    isLogged: true,
    address: new Address('Testowa', '12', null, '02-673', 'Warszawa', 'PL')
);

$order = (new OrderFactory())->createOrder(
    orderId: 'ORDER-123',
    orderTotal: 425089, // Items + delivery, grosze
    deliveryCost: 1845,
    loanTerm: 12,
    loanType: LoanType::INSTALLMENTS_ZERO_PERCENT,
    cartItems: $cartItems,
    customer: $customer,
    returnUrl: 'https://my-shop.example/order/confirm',
    notificationUrl: 'https://my-shop.example/comfino/webhook',
    // optional: allowedProductTypes, deliveryNetCost, deliveryCostTaxRate, deliveryCostTaxValue, category
    deliveryNetCost: 1500,
    deliveryCostTaxRate: 23,
    deliveryCostTaxValue: 345
);
```

Pass `$order` to `Client::createOrder()` — see [order-lifecycle.md](order-lifecycle.md).

## Notes

- `Product` string fields are HTML-stripped by the DTO getters; pass clean data.
- Keep cart/customer values byte-stable — they are hashed for request signing
  ([request-signing.md](request-signing.md)); don't mutate the objects after creation.
- The `Order`/`Cart`/`Customer` classes can also be built manually, but `OrderFactory` is preferred.

## See also

- [order-lifecycle.md](order-lifecycle.md) · [request-signing.md](request-signing.md) · [offers.md](offers.md)
