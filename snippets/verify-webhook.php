<?php

/**
 * Verify an inbound Comfino webhook WITHOUT the full SDK WebhookManager.
 *
 * Comfino signs each webhook with a `CR-Signature` header = SHA3-256(apiKey + rawBody). WebhookSignatureVerifier does
 * the timing-safe comparison for you. Use this lightweight path if you only need a single status endpoint; use
 * WebhookManager (see docs/webhooks.md) for multiple endpoints, replay protection, and rate limiting.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Comfino\Auth\WebhookSignatureVerifier;

$apiKey = getenv('SANDBOX_API_KEY') ?: 'YOUR_SANDBOX_API_KEY';

$rawBody = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_CR_SIGNATURE'] ?? '';

if (!(new WebhookSignatureVerifier())->verify($signature, $apiKey, $rawBody)) {
    http_response_code(401);

    exit('Invalid signature.');
}

try {
    $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(400);

    exit('Bad JSON.');
}

if (!is_array($payload) || !isset($payload['status'], $payload['externalId'])) {
    http_response_code(400);

    exit('Bad payload.');
}

// TODO: update your order here, e.g. MyShop::updateOrderStatus($payload['externalId'], $payload['status']);

http_response_code(200);
