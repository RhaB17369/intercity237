<?php
/**
 * Intercity237 — Business logic helpers (pure functions, fully testable)
 */

/**
 * Formats a monetary amount in FCFA.
 */
function format_money(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Converts a duration in minutes to human-readable format.
 */
function format_duration(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) return "{$m}min";
    if ($m === 0) return "{$h}h";
    return "{$h}h {$m}min";
}

/**
 * Validates a Cameroonian phone number (starts with 6, 9 digits).
 */
function validate_phone_cm(string $phone): bool
{
    $digits = preg_replace('/\D/', '', $phone);
    // Accept 9 digits starting with 6, or 12 digits (237 prefix + 9)
    if (strlen($digits) === 12 && str_starts_with($digits, '237')) {
        $digits = substr($digits, 3);
    }
    return strlen($digits) === 9 && $digits[0] === '6';
}

/**
 * Validates password strength (≥8 chars, upper, lower, digit).
 */
function validate_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Generates a unique booking reference (e.g. ICY-2026-A3B9C1).
 */
function generate_booking_ref(): string
{
    $rand = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    return 'ICY-' . date('Y') . '-' . $rand;
}

/**
 * Calculates arrival datetime given departure + duration in minutes.
 */
function calculate_arrival(string $departure_at, int $duration_min): string
{
    $ts = strtotime($departure_at) + ($duration_min * 60);
    return date('Y-m-d H:i:s', $ts);
}

/**
 * Returns the number of available seats.
 */
function seats_available(int $total, int $booked): int
{
    return max(0, $total - $booked);
}

/**
 * Returns true if a schedule is bookable (scheduled/boarding, seats left).
 */
function is_bookable(string $status, int $seats_available): bool
{
    return in_array($status, ['scheduled', 'boarding']) && $seats_available > 0;
}
