<?php

/**
 * Build a Comfino REST API client and fetch offers.
 *
 * Requires: comfino/php-sdk, comfino/php-api-client, sunrise/http-client-curl, sunrise/http-message.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Backend\Factory\ApiClientFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Sunrise\Http\Client\Curl\Client as CurlClient;
use Sunrise\Http\Message\RequestFactory;
use Sunrise\Http\Message\ResponseFactory;
use Sunrise\Http\Message\StreamFactory;

$apiKey = getenv('SANDBOX_API_KEY') ?: 'YOUR_SANDBOX_API_KEY';

// ApiClientFactory wires exponential-backoff retry around the PSR-18 client.
$client = (new ApiClientFactory())->createClient(
    httpClient: new CurlClient(new ResponseFactory()), // PSR-18
    requestFactory: new RequestFactory(),              // PSR-17
    streamFactory: new StreamFactory(),                // PSR-17
    apiKey: $apiKey,
    userAgent: 'MyShop/1.0.0',
    apiBaseUrl: 'https://api-ecommerce.craty.pl', // sandbox; omit/null for the default
    apiLanguage: 'pl'
);

try {
    // Fetch installment offers for a 1999.00 PLN cart (amount in grosze).
    $offers = $client->getFinancialProducts(new LoanQueryCriteria(loanAmount: 199900))->financialProducts;
} catch (ClientExceptionInterface $e) {
    echo $e->getMessage(), "\n";

    $offers = [];
}

foreach ($offers as $offer) {
    printf(
        "%s (%s): from %.2f PLN/mo over %d months\n",
        $offer->type->getValue(),
        $offer->creditorName,
        $offer->instalmentAmount / 100,
        $offer->loanTerm
    );
}
