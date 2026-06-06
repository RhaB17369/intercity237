<?php
/**
 * Route Service — Pure business logic helpers (no DB, fully testable)
 */

function format_duration_route(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) return "{$m}min";
    if ($m === 0) return "{$h}h";
    return "{$h}h {$m}min";
}

function format_price_fcfa(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function is_valid_schedule_status(string $status): bool
{
    return in_array($status, ['scheduled', 'boarding', 'departed', 'cancelled', 'completed'], true);
}

function is_schedule_active(string $status): bool
{
    return in_array($status, ['scheduled', 'boarding'], true);
}

function parse_api_path(string $path): array
{
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $resource = $segments[0] ?? '';
    $id       = isset($segments[1]) && ctype_digit((string)$segments[1]) ? (int)$segments[1] : null;
    return ['resource' => $resource, 'id' => $id];
}

function calculate_arrival_time(string $departure_at, int $duration_min): string
{
    $ts = strtotime($departure_at) + ($duration_min * 60);
    return date('Y-m-d H:i:s', $ts);
}

function build_schedule_filter(array $params): array
{
    $filter = [];
    if (!empty($params['origin']))      $filter['origin']      = (int) $params['origin'];
    if (!empty($params['destination'])) $filter['destination'] = (int) $params['destination'];
    if (!empty($params['date']))        $filter['date']        = $params['date'];
    return $filter;
}
