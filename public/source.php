<?php

/**
 * Annotated source viewer — shows the PHP source of a recipe page so readers can see the exact SDK calls. Only
 * whitelisted files in this directory are viewable.
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

$allowed = [
    'offers.php', 'order-create.php', 'order-status.php', 'order-cancel.php',
    'paywall-token.php', 'webhook.php', 'index.php', 'bootstrap.php',
];

$file = (string) ($_GET['file'] ?? '');

View::header('Source: ' . ($file !== '' ? $file : '(none)'), $tenant, $registry);

if (!in_array($file, $allowed, true)) {
    View::warn('Unknown or non-viewable file. Choose a recipe from the home page.');
} else {
    View::source(__DIR__ . '/' . $file);
}

View::footer();
