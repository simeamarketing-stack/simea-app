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

/**
 * Read a value back out of a same-request "old input" array when a form
 * redisplays after a validation failure (never persisted across requests —
 * that would leak stale values into unrelated forms).
 */
function old(array $old, string $key, $default = '')
{
    return $old[$key] ?? $default;
}

/** Format a nullable value for display: real "chưa xác nhận" instead of a bare 0 or blank. */
function displayValue($value, string $suffix = ''): string
{
    if ($value === null || $value === '') {
        return '<span class="not-confirmed">chưa xác nhận</span>';
    }
    return e((string) $value) . $suffix;
}

/**
 * Số lượng cho người đọc: đơn vị đếm được ra số nguyên, đơn vị liên tục giữ
 * tối đa 2 chữ số thập phân — kiểu Việt (1.030 / 16.050,5) thay vì 1030.0000.
 */
function formatQty($value, ?string $unitType = null): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $num = (float) $value;
    if ($unitType === 'count') {
        return number_format(round($num), 0, ',', '.');
    }
    $formatted = number_format(round($num, 2), 2, ',', '.');
    return rtrim(rtrim($formatted, '0'), ',');
}

/** Tiền Việt: 360000 -> "360.000 đ". */
function formatMoney($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format(round((float) $value), 0, ',', '.') . ' đ';
}

/** Số giờ cho người đọc: 1.5 -> "1,5 giờ"; 8 -> "8 giờ". */
function formatHours($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    return formatQty($value) . ' giờ';
}

/** @param array<string,string> $errors */
function fieldError(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    return '<div class="field-error">' . e($errors[$field]) . '</div>';
}
