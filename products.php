<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

function product_store(): array { return read_json_file(PRODUCTS_FILE, []); }
function save_products(array $products): void { write_json_file(PRODUCTS_FILE, array_values($products)); }
function normalize_product(array $p): array {
    $p['price'] = (float)($p['price'] ?? 0);
    $p['stock'] = (int)($p['stock'] ?? 0);
    $p['sizes'] = is_array($p['sizes'] ?? null) ? array_values($p['sizes']) : [];
    $p['details'] = is_array($p['details'] ?? null) ? array_values($p['details']) : [];
    $p['is_published'] = ($p['is_published'] ?? true) !== false;
    $p['is_new'] = ($p['is_new'] ?? false) === true;
    $p['is_featured'] = ($p['is_featured'] ?? false) === true;
    $p['is_popular'] = ($p['is_popular'] ?? false) === true;
    $p['image'] = (string)($p['image'] ?? $p['image_url'] ?? '');
    $p['image_url'] = (string)($p['image_url'] ?? $p['image']);
    $p['category'] = (string)($p['category'] ?? $p['cat'] ?? 'Hauts');
    $p['cat'] = (string)($p['cat'] ?? $p['category']);
    return $p;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $products = array_map('normalize_product', product_store());
    json_response(['ok'=>true,'products'=>$products]);
}
require_auth();

try {
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $products = product_store();
        $body = normalize_product($body);
        $body['id'] = $body['id'] ?? ('p_' . bin2hex(random_bytes(8)));
        $body['created_at'] = $body['created_at'] ?? date(DATE_ATOM);
        $body['updated_at'] = date(DATE_ATOM);
        $products[] = $body;
        save_products($products);
        json_response(['ok'=>true,'product'=>$body], 201);
    }
    if ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (string)($body['id'] ?? '');
        if ($id === '') json_response(['ok'=>false,'error'=>'ID produit manquant.'], 422);
        $products = product_store(); $found = false; $saved = null;
        foreach ($products as $i=>$p) {
            if ((string)($p['id'] ?? '') === $id) {
                $body = normalize_product(array_merge($p, $body));
                $body['id']=$id; $body['updated_at']=date(DATE_ATOM);
                $products[$i]=$body; $saved=$body; $found=true; break;
            }
        }
        if (!$found) json_response(['ok'=>false,'error'=>'Produit introuvable.'], 404);
        save_products($products);
        json_response(['ok'=>true,'product'=>$saved]);
    }
    if ($method === 'DELETE') {
        $id = (string)($_GET['id'] ?? '');
        if ($id === '') json_response(['ok'=>false,'error'=>'ID produit manquant.'], 422);
        $products = product_store(); $new=[]; $found=false;
        foreach ($products as $p) {
            if ((string)($p['id'] ?? '') === $id) { $found=true; continue; }
            $new[]=$p;
        }
        if (!$found) json_response(['ok'=>false,'error'=>'Produit introuvable.'], 404);
        save_products($new);
        json_response(['ok'=>true]);
    }
    json_response(['ok'=>false,'error'=>'Méthode non autorisée.'], 405);
} catch (Throwable $e) {
    json_response(['ok'=>false,'error'=>$e->getMessage()], 500);
}
