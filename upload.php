<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) json_response(['ok'=>false,'error'=>'Image manquante.'], 422);
$f=$_FILES['image'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) json_response(['ok'=>false,'error'=>'Échec du téléversement.'], 400);
if (($f['size'] ?? 0) > 8*1024*1024) json_response(['ok'=>false,'error'=>'Image trop volumineuse (8 Mo max).'], 413);
$info=@getimagesize($f['tmp_name']);
if (!$info) json_response(['ok'=>false,'error'=>'Le fichier n\'est pas une image valide.'], 415);
$mime=$info['mime'] ?? '';
$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime] ?? null;
if (!$ext) json_response(['ok'=>false,'error'=>'Format image non pris en charge.'], 415);
$name=safe_filename(pathinfo((string)$f['name'], PATHINFO_FILENAME));
$filename=$name.'-'.bin2hex(random_bytes(5)).'.'.$ext;
$dest=UPLOAD_DIR.'/'.$filename;
if (!move_uploaded_file($f['tmp_name'],$dest)) json_response(['ok'=>false,'error'=>'Impossible d\'enregistrer l\'image.'], 500);
$relative=$filename;
json_response(['ok'=>true,'url'=>$relative]);
