<?php
declare(strict_types=1);

session_name('loganator_premium');
session_start();

const DATA_DIR = __DIR__;
const PRODUCTS_FILE = __DIR__ . '/products.json';
const USERS_FILE = __DIR__ . '/users.json';
const UPLOAD_DIR = __DIR__;

if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0755, true);
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

function json_response($data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function read_json_file(string $file, array $default = []): array {
    if (!is_file($file)) return $default;
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}
function write_json_file(string $file, array $data): void {
    $tmp = $file . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException('Impossible d\'écrire les données. Vérifie les permissions du dossier data/.');
    }
}
function current_user(): ?array {
    return isset($_SESSION['premium_user']) && is_array($_SESSION['premium_user']) ? $_SESSION['premium_user'] : null;
}
function require_auth(): array {
    $user = current_user();
    if (!$user) json_response(['ok'=>false,'error'=>'Connexion Premium requise.'], 401);
    return $user;
}
function safe_filename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name) ?: 'image';
    return trim($name, '.-') ?: 'image';
}
