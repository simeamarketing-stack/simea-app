<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

function url(string $path = '/'): string
{
    return rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Trim, collapse internal whitespace, and force uppercase — used for every
 * business code (SKU, material, customer) so "abc " and "ABC" are treated
 * as the same code before uniqueness checks and inserts.
 */
function normalizeCode(string $value): string
{
    return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value) ?? ''), 'UTF-8');
}

/**
 * Countable units (hộp, tem, seal, thùng) always round UP; continuous units
 * (gram, kg) keep exact decimals. Centralized here so no page reimplements
 * its own rounding rule.
 */
function roundQuantity(float $value, string $unitType): float
{
    if ($unitType === 'count') {
        return ceil(round($value, 6));
    }
    return round($value, 4);
}

function redirectTo(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function oldInput(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function stashOldInput(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clearOldInput(): void
{
    unset($_SESSION['_old']);
}

/** Format a nullable value for display: real "chưa xác nhận" instead of a bare 0 or blank. */
function displayValue($value, string $suffix = ''): string
{
    if ($value === null || $value === '') {
        return '<span class="not-confirmed">chưa xác nhận</span>';
    }
    return e((string) $value) . $suffix;
}
