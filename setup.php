<?php
require __DIR__ . '/api/bootstrap.php';
$users = read_json_file(USERS_FILE);
if ($users) { header('Location: index.html'); exit; }
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email=strtolower(trim((string)($_POST['email']??'')));
  $password=(string)($_POST['password']??'');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $msg='Adresse e-mail invalide.';
  elseif(strlen($password)<8) $msg='Le mot de passe doit contenir au moins 8 caractères.';
  else {
    write_json_file(USERS_FILE, [['email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>'premium','created_at'=>date(DATE_ATOM)]]);
    header('Location: index.html?setup=ok'); exit;
  }
}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOGANATOR — Configuration Premium</title><style>body{font-family:Arial,sans-serif;background:#0b0b10;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}.box{width:min(92%,480px);background:#15151d;border:1px solid #333;border-radius:18px;padding:28px;box-sizing:border-box}input,button{width:100%;padding:13px;border-radius:10px;border:1px solid #444;box-sizing:border-box;margin-top:8px}input{background:#09090d;color:#fff}button{background:#fff;color:#000;font-weight:700;cursor:pointer}.err{color:#ff8d8d;margin:10px 0}.muted{color:#aaa;line-height:1.5}</style></head><body><div class="box"><h1>👑 LOGANATOR Premium</h1><p class="muted">Configuration unique. Aucun Supabase, aucune base SQL. Ce compte servira à gérer publiquement les produits.</p><?php if($msg):?><div class="err"><?=htmlspecialchars($msg)?></div><?php endif;?><form method="post"><label>E-mail Premium<input type="email" name="email" required></label><label>Mot de passe Premium<input type="password" name="password" minlength="8" required></label><button>Créer le compte Premium</button></form></div></body></html>
