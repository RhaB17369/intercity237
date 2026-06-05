<?php
/**
 * Intercity237 — route-service REST API
 *
 * GET  /api/health
 * GET  /api/cities
 * GET  /api/routes
 * GET  /api/schedules[?origin=&destination=&date=]
 * GET  /api/schedules/{id}
 */

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method   = $_SERVER['REQUEST_METHOD'];
$pathInfo = trim($_SERVER['PATH_INFO'] ?? $_GET['path'] ?? '/', '/');
$segments = explode('/', $pathInfo);
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;

function json_response(mixed $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function json_error(string $message, int $code = 400): void
{
    json_response(['error' => $message, 'code' => $code], $code);
}

match(true) {

    $resource === 'health' =>
        json_response(['status' => 'ok', 'service' => 'route-service', 'timestamp' => date('c')]),

    $method === 'GET' && $resource === 'cities' =>
        cities_list($pdo),

    $method === 'GET' && $resource === 'routes' =>
        routes_list($pdo),

    $method === 'GET' && $resource === 'schedules' && $id !== null =>
        schedule_detail($pdo, $id),

    $method === 'GET' && $resource === 'schedules' =>
        schedules_list($pdo),

    default =>
        json_error('Route not found', 404),
};

function cities_list(PDO $pdo): void
{
    $rows = $pdo->query("SELECT id, name, region FROM cities ORDER BY name")->fetchAll();
    json_response(['service' => 'route-service', 'count' => count($rows), 'data' => $rows]);
}

function routes_list(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT r.id, c1.name AS origin, c2.name AS destination,
               r.distance_km, r.duration_min, r.base_price
        FROM routes r
        JOIN cities c1 ON r.origin_id = c1.id
        JOIN cities c2 ON r.destination_id = c2.id
        ORDER BY c1.name, c2.name
    ")->fetchAll();
    json_response(['service' => 'route-service', 'count' => count($rows), 'data' => $rows]);
}

function schedules_list(PDO $pdo): void
{
    $origin = $_GET['origin'] ?? '';
    $dest   = $_GET['destination'] ?? '';
    $date   = $_GET['date'] ?? '';

    $where  = "s.status IN ('scheduled','boarding') AND (s.seats_total - s.seats_booked) > 0 AND s.departure_at > NOW()";
    $params = [];

    if ($origin) { $where .= " AND c1.name = :origin"; $params[':origin'] = $origin; }
    if ($dest)   { $where .= " AND c2.name = :dest";   $params[':dest']   = $dest; }
    if ($date)   { $where .= " AND DATE(s.departure_at) = :date"; $params[':date'] = $date; }

    $stmt = $pdo->prepare("
        SELECT s.id, s.departure_at, s.arrival_at,
               s.seats_total, s.seats_booked,
               (s.seats_total - s.seats_booked) AS seats_available,
               r.base_price, r.distance_km, r.duration_min,
               c1.name AS origin, c2.name AS destination,
               o.name AS operator, b.model AS bus_model, b.capacity
        FROM schedules s
        JOIN routes r    ON s.route_id = r.id
        JOIN buses b     ON s.bus_id = b.id
        JOIN operators o ON b.operator_id = o.id
        JOIN cities c1   ON r.origin_id = c1.id
        JOIN cities c2   ON r.destination_id = c2.id
        WHERE {$where}
        ORDER BY s.departure_at
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    json_response(['service' => 'route-service', 'count' => count($rows), 'data' => $rows]);
}

function schedule_detail(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("
        SELECT s.id, s.departure_at, s.arrival_at,
               s.seats_total, s.seats_booked,
               (s.seats_total - s.seats_booked) AS seats_available,
               s.status, r.base_price, r.distance_km, r.duration_min,
               c1.name AS origin, c2.name AS destination,
               o.name AS operator, b.model AS bus_model, b.capacity
        FROM schedules s
        JOIN routes r    ON s.route_id = r.id
        JOIN buses b     ON s.bus_id = b.id
        JOIN operators o ON b.operator_id = o.id
        JOIN cities c1   ON r.origin_id = c1.id
        JOIN cities c2   ON r.destination_id = c2.id
        WHERE s.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) json_error('Schedule not found', 404);
    json_response(['service' => 'route-service', 'data' => $row]);
}
