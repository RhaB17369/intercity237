<?php
/**
 * Ticket Service — Pure business logic helpers (no DB, fully testable)
 */

function build_qr_data(
    string $ref,
    string $name,
    string $from,
    string $to,
    string $dep,
    string $token
): string {
    return json_encode([
        'ref'   => $ref,
        'name'  => $name,
        'from'  => $from,
        'to'    => $to,
        'dep'   => $dep,
        'token' => $token,
    ], JSON_UNESCAPED_UNICODE);
}

function parse_qr_data(string $json): ?array
{
    $data = json_decode($json, true);
    if (!is_array($data)) return null;
    $required = ['ref', 'name', 'from', 'to', 'dep', 'token'];
    foreach ($required as $key) {
        if (empty($data[$key])) return null;
    }
    return $data;
}

function is_ticket_scannable(string $booking_status, ?string $scanned_at): bool
{
    return $booking_status === 'confirmed' && $scanned_at === null;
}

function get_scan_error_message(string $booking_status, ?string $scanned_at): ?string
{
    if ($booking_status !== 'confirmed') {
        return 'Réservation non confirmée.';
    }
    if ($scanned_at !== null) {
        return 'Ce ticket a déjà été scanné le ' . date('d/m/Y à H:i', strtotime($scanned_at));
    }
    return null;
}

function format_scan_timestamp(?string $scanned_at): string
{
    if ($scanned_at === null) return 'Non scanné';
    $ts = strtotime($scanned_at);
    return $ts !== false ? date('d/m/Y à H:i', $ts) : 'Date invalide';
}

function generate_qr_token(): string
{
    return bin2hex(random_bytes(32));
}
