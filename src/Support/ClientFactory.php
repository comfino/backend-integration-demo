<?php

declare(strict_types=1);

namespace App\Support;

use Comfino\Api\Client;
use Comfino\Backend\Factory\ApiClientFactory;
use Sunrise\Http\Client\Curl\Client as CurlClient;
use Sunrise\Http\Message\RequestFactory;
use Sunrise\Http\Message\ResponseFactory;
use Sunrise\Http\Message\StreamFactory;

/**
 * Builds a ComfinoPay REST API client for a given tenant.
 *
 * Uses the SDK's ApiClientFactory (which wires exponential-backoff retry) with a sunrise/http PSR-18 client and PSR-17
 * factories. The API key is per-tenant, so a SaaS platform gets an isolated client per shop; the host/user-agent/language
 * are shared.
 */
final class ClientFactory
{
    public function forTenant(Tenant $tenant): Client
    {
        return (new ApiClientFactory())->createClient(
            httpClient: new CurlClient(new ResponseFactory()),
            requestFactory: new RequestFactory(),
            streamFactory: new StreamFactory(),
            apiKey: $tenant->apiKey,
            userAgent: Env::get('PLATFORM_NAME', 'ComfinoBackendDemo') . '/' . Env::get('PLUGIN_VERSION', '1.0.0'),
            apiBaseUrl: Env::apiHost() ?: null,
            apiLanguage: Env::language()
        );
    }
}
