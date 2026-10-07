<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'me';

if ($action === 'me') {
    json_response(['ok'=>true, 'authenticated'=>!!current_user(), 'user'=>current_user()]);
}
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    json_response(['ok'=>true]);
}
if ($action === 'login') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') json_response(['ok'=>false,'error'=>'E-mail et mot de passe requis.'], 422);
    $users = read_json_file(USERS_FILE);
    $found = null;
    foreach ($users as $u) {
        if (is_array($u) && strtolower((string)($u['email'] ?? '')) === $email) { $found = $u; break; }
    }
    if (!$found || !password_verify($password, (string)($found['password_hash'] ?? ''))) json_response(['ok'=>false,'error'=>'E-mail ou mot de passe incorrect.'], 401);
    $_SESSION['premium_user'] = ['email'=>$email, 'role'=>'premium'];
    session_regenerate_id(true);
    json_response(['ok'=>true,'user'=>$_SESSION['premium_user']]);
}
if ($action === 'setup') {
    // Only setup.php should call this action.
    if (is_file(USERS_FILE) && count(read_json_file(USERS_FILE)) > 0) json_response(['ok'=>false,'error'=>'Le compte Premium est déjà configuré.'], 409);
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) json_response(['ok'=>false,'error'=>'E-mail valide et mot de passe de 8 caractères minimum requis.'], 422);
    write_json_file(USERS_FILE, [[
        'email'=>$email,
        'password_hash'=>password_hash($password, PASSWORD_DEFAULT),
        'role'=>'premium',
        'created_at'=>date(DATE_ATOM)
    ]]);
    json_response(['ok'=>true]);
}
json_response(['ok'=>false,'error'=>'Action inconnue.'], 404);
