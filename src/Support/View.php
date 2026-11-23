<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Tiny HTML view helper — a shared page layout, result panels, and a source viewer. Kept dependency-free so the recipe
 * pages stay readable and focused on the SDK calls.
 */
final class View
{
    /** Render the shared page header (doctype, styles, title, tenant switch, nav). */
    public static function header(string $title, ?Tenant $activeTenant, TenantRegistry $registry, string $self = ''): void
    {
        echo "<!DOCTYPE html>\n<html lang=\"en\"><head><meta charset=\"UTF-8\">";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES) . ' — ComfinoPay backend demo</title>';
        echo '<link rel="stylesheet" href="/assets/styles.css"></head><body>';
        echo '<p class="brand"><img src="/assets/comfinopay_logo.svg" alt="ComfinoPay" height="28"></p>';
        echo '<p class="back"><a href="/index.php' . ($activeTenant ? '?shop=' . htmlspecialchars($activeTenant->id, ENT_QUOTES) : '') . '">← All recipes</a>';

        if ($self !== '') {
            echo ' · <a href="/source.php?file=' . htmlspecialchars($self, ENT_QUOTES) .
                ($activeTenant ? '&shop=' . htmlspecialchars($activeTenant->id, ENT_QUOTES) : '') . '">View source</a>';
        }

        echo '</p>';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES) . '</h1>';

        if ($activeTenant !== null) {
            echo '<p class="tenant">Active tenant: <strong>' . htmlspecialchars($activeTenant->name, ENT_QUOTES) .
                '</strong> <code>' . htmlspecialchars($activeTenant->id, ENT_QUOTES) . '</code> — switch: ';

            $links = [];

            foreach ($registry->all() as $tenant) {
                $path = ($self !== '' ? '/' . $self : '/index.php');
                $links[] = '<a href="' . $path . '?shop=' . htmlspecialchars($tenant->id, ENT_QUOTES) . '">' .
                    htmlspecialchars($tenant->id, ENT_QUOTES) . '</a>';
            }

            echo implode(' | ', $links) . '</p>';

            if (!$activeTenant->hasCredentials()) {
                self::warn('No API key is configured for this tenant. Add one to <code>.env</code> to run live calls.');
            }
        }
    }

    public static function footer(): void
    {
        echo '</body></html>';
    }

    public static function note(string $html): void
    {
        echo '<div class="note">' . $html . '</div>';
    }

    public static function warn(string $html): void
    {
        echo '<div class="note warn">' . $html . '</div>';
    }

    /** Pretty-print a successful result payload. */
    public static function success(string $heading, string $bodyHtml): void
    {
        echo '<div class="panel ok"><h3>' . htmlspecialchars($heading, ENT_QUOTES) . '</h3>' . $bodyHtml . '</div>';
    }

    /** Render a caught exception (message and HTTP status when available). */
    public static function error(\Throwable $e): void
    {
        $status = method_exists($e, 'getStatusCode') ? (string) $e->getStatusCode() : '';

        echo '<div class="panel err"><h3>' . htmlspecialchars((new \ReflectionClass($e))->getShortName(), ENT_QUOTES) .
            ($status !== '' ? " (HTTP $status)" : '') . '</h3>';
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</pre></div>';
    }

    /** @param array<string, mixed> $data */
    public static function keyValue(array $data): string
    {
        $rows = '';

        foreach ($data as $key => $value) {
            $val = is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES);
            $rows .= '<tr><th>' . htmlspecialchars((string) $key, ENT_QUOTES) . '</th><td>' .
                htmlspecialchars((string) $val, ENT_QUOTES) . '</td></tr>';
        }

        return '<table class="kv">' . $rows . '</table>';
    }

    /** Render a PHP source file with the demo's own frame stripped, syntax-highlighted. */
    public static function source(string $absPath): void
    {
        if (!is_file($absPath)) {
            echo '<div class="panel err">Source file not found.</div>';

            return;
        }

        $code = (string) file_get_contents($absPath);

        echo '<div class="panel"><pre class="source">' . htmlspecialchars($code, ENT_QUOTES) . '</pre></div>';
    }
}
