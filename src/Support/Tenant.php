<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A single shop (tenant) and its ComfinoPay credentials.
 */
final class Tenant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $apiKey,
        public readonly string $widgetKey
    ) {
    }

    public function hasCredentials(): bool
    {
        return $this->apiKey !== '';
    }
}
