<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const SETTINGS_FILE = __DIR__ . '/settings.json';

$defaults = [
    'name' => 'LOGANATOR',
    'currency' => 'EUR (€)',
    'freeShip' => 80,
    'maintenance' => false,
];

function read_settings(): array {
    global $defaults;
    $s = read_json_file(SETTINGS_FILE, []);
    return array_merge($defaults, $s);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    json_response(['ok'=>true, 'settings'=>read_settings()]);
}

require_auth();
if ($method !== 'POST') json_response(['ok'=>false,'error'=>'Méthode non autorisée.'],405);

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$current = read_settings();
$next = [
    'name' => trim((string)($body['name'] ?? $current['name'])),
    'currency' => trim((string)($body['currency'] ?? $current['currency'])),
    'freeShip' => max(0, (float)($body['freeShip'] ?? $current['freeShip'])),
    'maintenance' => filter_var($body['maintenance'] ?? $current['maintenance'], FILTER_VALIDATE_BOOLEAN),
    'updated_at' => date(DATE_ATOM),
];
write_json_file(SETTINGS_FILE, $next);
json_response(['ok'=>true,'settings'=>$next]);
