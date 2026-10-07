<?php
// LOGANATOR — contrôle de maintenance côté serveur.
// Les visiteurs publics voient uniquement la page de maintenance.
// Une session Premium connectée peut continuer à administrer la boutique.
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// API fallback through index.php: certains hébergeurs renvoient 405 sur les POST vers /api/*.php.
// Toutes les actions AJAX peuvent donc passer par ce fichier unique.
if (isset($_GET['api'])) {
    $api = (string)$_GET['api'];
    $parts = parse_url($api);
    $apiFile = basename((string)($parts['path'] ?? ''));
    if (!in_array($apiFile, ['auth.php','products.php','upload.php','settings.php'], true)) {
        json_response(['ok'=>false,'error'=>'API inconnue.'], 404);
    }
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $apiQuery);
        foreach ($apiQuery as $k=>$v) $_GET[$k] = $v;
    }
    require __DIR__ . '/' . $apiFile;
    exit;
}

$settingsFile = __DIR__ . '/settings.json';
$settings = [
    'name' => 'LOGANATOR',
    'maintenance' => false,
];
if (is_file($settingsFile)) {
    $raw = @file_get_contents($settingsFile);
    $saved = json_decode($raw ?: '', true);
    if (is_array($saved)) $settings = array_merge($settings, $saved);
}

if (!empty($settings['maintenance']) && !current_user()) {
    http_response_code(503);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Retry-After: 3600');
    $title = htmlspecialchars((string)($settings['name'] ?? 'LOGANATOR'), ENT_QUOTES, 'UTF-8');
    ?>
<!doctype html>
<html lang="fr">
<head>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?> — Maintenance</title>
<style>
html,body{margin:0;min-height:100%;background:#050507;color:#fff;font-family:Arial,Helvetica,sans-serif}
body{min-height:100vh;display:grid;place-items:center;text-align:center}
.maintenance{width:min(760px,calc(100% - 40px));padding:40px 20px}
.maintenance h1{font-size:clamp(32px,7vw,64px);margin:0 0 18px;font-weight:900;letter-spacing:-2px}
.maintenance p{margin:0;color:#b8b8c5;font-size:clamp(17px,3vw,22px);line-height:1.5}
.dot{width:10px;height:10px;border-radius:50%;background:#fff;display:inline-block;margin-right:10px;vertical-align:middle;box-shadow:0 0 18px #fff}
</style>
</head>
<body>
<main class="maintenance" aria-live="polite">
<h1>Le site est en cours de maintenance</h1>
<p><span class="dot"></span>Nous revenons bientôt.</p>
</main>
</body>
</html>
<?php
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>LOGANATOR V26 — Compte & Premium</title>
<style>
:root{--bg:#07070a;--card:#111118;--text:#fff;--muted:#aaa9b5;--accent:#8b5cf6;--line:#292936}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.top{padding:9px;text-align:center;background:#fff;color:#000;font-weight:800;font-size:13px}
nav{position:sticky;top:0;z-index:20;display:flex;justify-content:space-between;align-items:center;padding:16px 5%;background:#09090def;border-bottom:1px solid var(--line);backdrop-filter:blur(12px)}
.logo{font-weight:1000;letter-spacing:3px;font-size:24px}.links{display:flex;gap:24px}.links a{color:#ddd;text-decoration:none}
.btn{border:1px solid var(--line);background:#12121a;color:#fff;border-radius:12px;padding:10px 14px;cursor:pointer}
.hero{padding:90px 7%;min-height:520px;display:flex;align-items:center;background:radial-gradient(circle at 80% 30%,#8b5cf655,transparent 34%),radial-gradient(circle at 15% 70%,#c084fc22,transparent 32%)}
.kicker{color:#c4b5fd;font-weight:900;letter-spacing:2px}h1{font-size:clamp(50px,9vw,110px);line-height:.9;margin:15px 0}.hero p{max-width:650px;color:var(--muted);font-size:18px}
.primary{background:linear-gradient(135deg,var(--accent),#c084fc);color:white;border:0;padding:14px 20px;border-radius:12px;font-weight:900;cursor:pointer}
.section{padding:55px 5%}h2{font-size:38px;margin:0 0 24px}.drop{padding:28px;border:1px solid var(--line);border-radius:20px;background:linear-gradient(120deg,#18131f,#0d0d12);margin-bottom:35px}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}.card{background:var(--card);border:1px solid var(--line);border-radius:18px;overflow:hidden;cursor:pointer}
.photo{height:330px;background:#17171f;overflow:hidden}.photo img{width:100%;height:100%;object-fit:cover;display:block;transition:.25s}.card:hover .photo img{transform:scale(1.03)}
.info{padding:16px}.tag{font-size:11px;color:#c4b5fd;font-weight:900}.row{display:flex;justify-content:space-between;gap:10px;align-items:center}.price{font-size:19px;font-weight:900}.muted{color:var(--muted);font-size:14px}
.buy{width:100%;margin-top:12px;border:0;border-radius:10px;padding:12px;background:#fff;color:#000;font-weight:900;cursor:pointer}
.gallery{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}.gallery img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;border:1px solid var(--line)}
footer{padding:35px 5%;border-top:1px solid var(--line);text-align:center;color:var(--muted)}
.modal{position:fixed;inset:0;background:#000b;display:none;align-items:center;justify-content:center;padding:20px;z-index:100}
.modal.open{display:flex}.panel{width:min(1000px,100%);max-height:92vh;overflow:auto;background:#111118;border:1px solid var(--line);border-radius:20px;position:relative}
.close{position:absolute;right:15px;top:12px;width:40px;height:40px;border-radius:50%;border:1px solid var(--line);background:#0b0b10;color:#fff;font-size:22px;cursor:pointer;z-index:2}
.detail{display:grid;grid-template-columns:1.1fr .9fr}.detail-img{background:#09090d;min-height:520px}.detail-img img{width:100%;height:100%;min-height:520px;object-fit:contain}.detail-info{padding:35px}.detail-info h2{font-size:42px;margin:8px 0}.detail-price{font-size:30px;font-weight:900;margin:18px 0}.detail-list{color:#ddd;line-height:1.8;padding-left:20px}
.pay-options{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:15px 0}.pay-option{padding:14px;border:1px solid var(--line);border-radius:12px;background:#0b0b10;color:#fff;cursor:pointer;text-align:left}.pay-option.selected{border-color:#a78bfa;background:#211936}
.checkout-total{font-size:22px;font-weight:900;margin:18px 0}.note{font-size:13px;color:var(--muted);line-height:1.5}
.delivery-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:20px 0}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:13px;font-weight:800;color:#ddd}.field input,.field select{width:100%;padding:13px;border:1px solid var(--line);border-radius:10px;background:#09090d;color:#fff;font:inherit}.field input:focus,.field select:focus{outline:2px solid #8b5cf6;border-color:#8b5cf6}.delivery-note{padding:12px;border:1px solid var(--line);border-radius:12px;background:#0b0b10;color:var(--muted);font-size:13px;line-height:1.5}.form-error{color:#fca5a5;font-size:13px;display:none;margin-top:8px}.form-error.show{display:block}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,1fr)}.links{display:none}.detail{grid-template-columns:1fr}.detail-img,.detail-img img{min-height:350px}.detail-info{padding:25px}}
@media(max-width:550px){.grid{grid-template-columns:1fr}.gallery{grid-template-columns:repeat(2,1fr)}.hero{padding:65px 6%}.detail-info h2{font-size:32px}.pay-options{grid-template-columns:1fr}}
</style>

<style>
.admin-modal{position:fixed;inset:0;background:#000c;display:none;align-items:center;justify-content:center;padding:20px;z-index:300}.admin-modal.open{display:flex}.admin-box{width:min(1100px,100%);max-height:92vh;overflow:auto;background:#111118;border:1px solid #292936;border-radius:20px;padding:28px;position:relative}.admin-close{position:absolute;right:14px;top:12px;border:1px solid #292936;background:#09090d;color:#fff;border-radius:50%;width:38px;height:38px;font-size:22px;cursor:pointer}.admin-grid{display:grid;gap:12px;margin-top:18px}.admin-card{display:grid;grid-template-columns:90px 1fr auto;gap:14px;align-items:center;padding:12px;border:1px solid #292936;border-radius:14px;background:#0b0b10}.admin-card img{width:90px;height:90px;object-fit:cover;border-radius:10px}.admin-actions{display:flex;gap:8px;flex-wrap:wrap}.admin-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px}.admin-form .full{grid-column:1/-1}.admin-form label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:800}.admin-form input,.admin-form textarea{padding:12px;border:1px solid #292936;border-radius:10px;background:#09090d;color:#fff;font:inherit}.admin-form textarea{min-height:100px}.premium{display:inline-block;padding:6px 10px;border-radius:999px;background:#2a1646;color:#d8b4fe;font-weight:900;font-size:12px}.admin-msg{margin-top:12px;padding:11px;border-radius:10px;background:#0b0b10;color:#aaa9b5}.admin-msg.ok{color:#86efac}.admin-msg.err{color:#fda4af}@media(max-width:700px){.admin-card{grid-template-columns:65px 1fr}.admin-card img{width:65px;height:65px}.admin-actions{grid-column:1/-1}.admin-form{grid-template-columns:1fr}.admin-form .full{grid-column:auto}}
</style>
<style>
.v15-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:0 0 22px}
.v15-search{flex:1 1 260px;min-width:180px;padding:13px 15px;border:1px solid var(--line);border-radius:12px;background:#0b0b10;color:#fff;font:inherit}
.v15-filters{display:flex;gap:8px;flex-wrap:wrap}.v15-filter{border:1px solid var(--line);background:#111118;color:#ddd;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:800}.v15-filter.active{border-color:#a78bfa;background:#211936;color:#fff}
.v15-empty{padding:30px;border:1px dashed var(--line);border-radius:16px;color:var(--muted);text-align:center;grid-column:1/-1}
.v15-cart-list{display:grid;gap:10px;margin:15px 0}.v15-cart-row{display:grid;grid-template-columns:70px 1fr auto;gap:12px;align-items:center;padding:12px;border:1px solid var(--line);border-radius:14px;background:#0b0b10}.v15-cart-row img{width:70px;height:70px;object-fit:cover;border-radius:10px}
.v15-qty{display:flex;align-items:center;gap:7px;margin-top:7px}.v15-qty button{width:34px;height:34px;border:1px solid var(--line);border-radius:9px;background:#15151d;color:#fff;cursor:pointer;font-weight:900}.v15-remove{border:0;background:none;color:#fca5a5;cursor:pointer;font-weight:800}
.v15-cart-total{display:flex;justify-content:space-between;padding:15px 0;border-top:1px solid var(--line);font-size:20px;font-weight:900}.v15-shipping{padding:11px 13px;border:1px solid var(--line);border-radius:11px;background:#101017;color:#c4b5fd;font-size:13px}
.v15-size{margin:15px 0}.v15-size label{display:block;font-weight:800;font-size:13px;margin-bottom:8px}.v15-sizes{display:flex;gap:8px;flex-wrap:wrap}.v15-size-btn{min-width:46px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#0b0b10;color:#fff;cursor:pointer;font-weight:900}.v15-size-btn.selected{border-color:#a78bfa;background:#211936}
.v15-fav{position:absolute;right:12px;top:12px;width:40px;height:40px;border-radius:50%;border:1px solid var(--line);background:#09090dcc;color:#fff;cursor:pointer;font-size:19px;z-index:3}.photo{position:relative}
.v15-stat{padding:15px;border:1px solid var(--line);border-radius:14px;background:#0b0b10}.v15-stat b{font-size:24px;display:block;margin-top:4px}
@media(max-width:700px){.v15-cart-row{grid-template-columns:60px 1fr}.v15-cart-row>.v15-line-total{grid-column:2}.v15-cart-row img{width:60px;height:60px}}
</style>
<style>
.v16-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.v16-tab{padding:10px 14px;border:1px solid var(--line);background:#0b0b10;color:#ddd;border-radius:999px;cursor:pointer;font-weight:900}.v16-tab.active{background:#211936;border-color:#a78bfa;color:#fff}.v16-section{display:none}.v16-section.active{display:block}.v16-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0}.v16-kpi{padding:16px;border:1px solid var(--line);border-radius:16px;background:#0b0b10}.v16-kpi b{display:block;font-size:28px;margin-top:5px}.v16-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}.v16-box{border:1px solid var(--line);border-radius:16px;background:#0b0b10;padding:16px;margin:14px 0}.v16-box h3{margin-top:0}.v16-list{display:grid;gap:8px}.v16-line{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:11px;border:1px solid var(--line);border-radius:12px}.v16-switch{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #20202a}.v16-switch:last-child{border-bottom:0}.v16-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}.v16-form .full{grid-column:1/-1}.v16-form input,.v16-form select,.v16-form textarea{width:100%;padding:11px;border:1px solid var(--line);border-radius:10px;background:#09090d;color:#fff;font:inherit}.v16-form textarea{min-height:90px}.v16-badge{padding:5px 8px;border-radius:999px;background:#211936;color:#d8b4fe;font-size:11px;font-weight:900}.v16-danger{color:#fca5a5}.v16-good{color:#86efac}.v16-muted{color:var(--muted);font-size:13px}.v16-table{width:100%;border-collapse:collapse}.v16-table th,.v16-table td{text-align:left;padding:10px;border-bottom:1px solid var(--line);font-size:13px}.v16-scroll{overflow:auto}.v16-preview{border:1px dashed #8b5cf6;border-radius:14px;padding:18px;background:#100d18}.v16-check{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}.v16-check label{padding:10px;border:1px solid var(--line);border-radius:10px}.v16-help{font-size:12px;color:var(--muted);line-height:1.5}.v16-alert{padding:12px;border-radius:12px;background:#16121d;border:1px solid #30243e;margin:10px 0}.v16-publish{position:sticky;bottom:0;padding:12px 0;background:#111118;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end}
@media(max-width:850px){.v16-kpis{grid-template-columns:repeat(2,1fr)}.v16-two,.v16-form{grid-template-columns:1fr}.v16-form .full{grid-column:auto}}
@media(max-width:520px){.v16-kpis{grid-template-columns:1fr}.v16-check{grid-template-columns:1fr}}
</style>

<style id="v17-glass-ui">
:root{--bg:#07070b;--card:rgba(255,255,255,.075);--text:#fff;--muted:#b8b8c5;--accent:#9b7cff;--line:rgba(255,255,255,.14)}
body{background:radial-gradient(circle at 10% 10%,rgba(155,124,255,.12),transparent 28%),radial-gradient(circle at 90% 25%,rgba(255,255,255,.06),transparent 25%),#07070b}nav,.card,.drop,.panel,.admin-box,.v15-stat,.v16-box,.v15-cart-row,.delivery-note,.v16-preview,.v16-alert{background:rgba(255,255,255,.065)!important;backdrop-filter:blur(24px) saturate(140%);-webkit-backdrop-filter:blur(24px) saturate(140%);border-color:rgba(255,255,255,.14)!important;box-shadow:0 18px 55px rgba(0,0,0,.22),inset 0 1px 0 rgba(255,255,255,.07)}
nav{background:rgba(10,10,15,.68)!important}.hero{background:radial-gradient(circle at 80% 30%,rgba(155,124,255,.28),transparent 34%),radial-gradient(circle at 15% 70%,rgba(255,255,255,.07),transparent 32%)}
.btn,.v15-filter,.v16-tab,.pay-option,.v15-size-btn,.close,.admin-close{background:rgba(255,255,255,.07)!important;border-color:rgba(255,255,255,.16)!important;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.primary{background:linear-gradient(135deg,#8f70ff,#c7b9ff)!important;box-shadow:0 10px 30px rgba(143,112,255,.28)}
.card{position:relative;transform-style:preserve-3d;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.card:hover{box-shadow:0 28px 70px rgba(0,0,0,.36),inset 0 1px 0 rgba(255,255,255,.12);border-color:rgba(255,255,255,.28)!important}.photo{isolation:isolate}.photo:after{content:"";position:absolute;inset:-30%;pointer-events:none;opacity:0;background:radial-gradient(circle at var(--mx,50%) var(--my,50%),rgba(255,255,255,.38),rgba(255,255,255,.08) 10%,transparent 28%);mix-blend-mode:screen;transition:opacity .2s}.card:hover .photo:after{opacity:1}.photo img{transition:transform .45s cubic-bezier(.2,.8,.2,1),filter .35s}.card:hover .photo img{transform:scale(1.08);filter:saturate(1.08) brightness(1.04)}
.buy{background:rgba(255,255,255,.9)!important;box-shadow:0 8px 24px rgba(255,255,255,.08)}
.field input,.field select,.admin-form input,.admin-form textarea,.v15-search,.v16-form input,.v16-form select,.v16-form textarea{background:rgba(0,0,0,.22)!important;border-color:rgba(255,255,255,.14)!important}
@media(max-width:700px){.card{transform:none!important}.photo:after{display:none}}
</style>
<script>
(function(){function init(){document.querySelectorAll('.card').forEach(function(card){var photo=card.querySelector('.photo');if(!photo||card.dataset.v17glass)return;card.dataset.v17glass='1';card.addEventListener('pointermove',function(e){if(e.pointerType==='touch')return;var r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width,y=(e.clientY-r.top)/r.height;card.style.transform='perspective(900px) rotateX('+((.5-y)*5).toFixed(2)+'deg) rotateY('+((x-.5)*5).toFixed(2)+'deg) translateY(-5px)';photo.style.setProperty('--mx',(x*100).toFixed(1)+'%');photo.style.setProperty('--my',(y*100).toFixed(1)+'%')});card.addEventListener('pointerleave',function(){card.style.transform='';photo.style.setProperty('--mx','50%');photo.style.setProperty('--my','50%')})})}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();window.v17InitGlass=init})();
</script>


<style id="v22-1-fixes">
.v22-store-panel{margin-top:16px;padding:16px;border:1px solid rgba(255,255,255,.14);border-radius:16px;background:rgba(255,255,255,.055);backdrop-filter:blur(18px)}
.v22-store-title{font-weight:900;margin-bottom:10px}.v22-store-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.v22-store-card{border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:14px;background:rgba(0,0,0,.18)}.v22-store-card b{display:block;margin-bottom:5px}.v22-store-card button{width:100%;margin-top:10px}
@media(max-width:600px){.v22-store-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="top">🚚 LIVRAISON OFFERTE À PARTIR DE 80€</div>
<nav><div class="logo">LOGANATOR</div><div class="links"><a href="#shop">SHOP</a><a href="#drop">DROP</a><a href="#vetements">VÊTEMENTS</a></div><div style="display:flex;gap:8px;align-items:center"><button class="btn" id="accountBtn" type="button" onclick="return window.__LOGANATOR_OPEN_ACCOUNT ? window.__LOGANATOR_OPEN_ACCOUNT() : false;">👤 COMPTE</button><button class="btn" onclick="showCart()">🛒 <span id="count">0</span></button></div></nav>

<section class="hero"><div><div class="kicker">LOGANATOR SHOP</div><h1>PORTE TON<br>LOGANATOR.</h1><p>La boutique de la chaîne, avec les vraies photos de tes créations.</p><button class="primary" onclick="document.querySelector('#shop').scrollIntoView({behavior:'smooth'})">VOIR LA COLLECTION ↓</button></div></section>

<section class="section" id="drop"><div class="drop"><div class="kicker">DROP #01</div><h2>LOGANATOR COLLECTION</h2><p class="muted">Hoodie, doudoune, baggy et chaussures.</p></div></section>

<section class="section" id="shop"><h2>La collection</h2>
<div class="v15-toolbar"><input id="v15Search" class="v15-search" type="search" placeholder="🔎 Rechercher un produit...">
<div class="v15-filters" id="v15Filters">
<button class="v15-filter active" data-filter="Tous">TOUS</button><button class="v15-filter" data-filter="Hoodie">HOODIE</button><button class="v15-filter" data-filter="Doudoune">DOUDOUNE</button><button class="v15-filter" data-filter="Baggy">BAGGY</button><button class="v15-filter" data-filter="Chaussures">CHAUSSURES</button>
</div><span id="v15ResultCount" class="muted" style="white-space:nowrap">0 produit</span></div><div class="grid" id="grid"></div></section>

<section class="section" id="vetements"><h2>Tes vêtements 👕</h2><p class="muted">Clique sur un vêtement pour voir la grande image et tous ses détails.</p><div class="gallery" id="gallery"></div></section>

<footer>© 2026 LOGANATOR — Boutique officielle</footer>

<div class="modal" id="detailModal" onclick="if(event.target===this)closeModal('detailModal')">
  <div class="panel">
    <button class="close" onclick="closeModal('detailModal')">×</button>
    <div class="detail">
      <div class="detail-img"><img id="detailImage" src="" alt=""></div>
      <div class="detail-info">
        <span class="tag">LOGANATOR</span>
        <h2 id="detailName"></h2>
        <div class="detail-price" id="detailPrice"></div>
        <p id="detailCat" class="muted"></p>
        <ul id="detailList" class="detail-list"></ul>
        <div class="v15-size"><label>TAILLE</label><div class="v15-sizes" id="v15Sizes"></div></div>
        <button class="primary" style="width:100%;margin-top:12px" id="detailAdd">AJOUTER AU PANIER 🛒</button>
        <button class="buy" style="margin-top:10px" id="detailBuy">ACHETER / CHOISIR LE PAIEMENT</button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="checkoutModal" onclick="if(event.target===this)closeModal('checkoutModal')">
  <div class="panel">
    <button class="close" onclick="closeModal('checkoutModal')">×</button>
    <div class="detail-info">
      <span class="tag">COMMANDE LOGANATOR</span>
      <h2>Mode de paiement</h2>
      <p class="muted">Choisis un mode de paiement pour la démonstration du checkout.</p>
      <div id="checkoutItems" class="v15-cart-list"></div>
      <div id="v15Shipping" class="v15-shipping"></div>
      <div class="v15-cart-total"><span>TOTAL</span><span id="checkoutTotal"></span></div>
      <h3>📦 Où doit-on livrer ?</h3>
      <div class="delivery-form">
        <div class="field"><label for="firstName">Prénom</label><input id="firstName" autocomplete="given-name" placeholder="Logan"></div>
        <div class="field"><label for="lastName">Nom</label><input id="lastName" autocomplete="family-name" placeholder="Nom"></div>
        <div class="field full"><label for="address">Adresse</label><input id="address" autocomplete="street-address" placeholder="12 rue Exemple"></div>
        <div class="field"><label for="postcode">Code postal</label><input id="postcode" inputmode="numeric" autocomplete="postal-code" placeholder="75000"></div>
        <div class="field"><label for="city">Ville</label><input id="city" autocomplete="address-level2" placeholder="Paris"></div>
        <div class="field full"><label for="country">Pays</label><select id="country"><option>France</option><option>Belgique</option><option>Suisse</option><option>Luxembourg</option><option>Autre</option></select></div>
      </div>
      <div class="delivery-note">🚚 Les informations de livraison sont utilisées uniquement pour préparer la commande dans cette démonstration.</div>
      <div id="formError" class="form-error">Remplis les champs obligatoires avant de continuer.</div>
      <div class="pay-options">
        <button class="pay-option selected" data-pay="Carte bancaire">💳 Carte bancaire</button>
        <button class="pay-option" data-pay="PayPal">🅿️ PayPal</button>
      </div>
      <button class="primary" style="width:100%" onclick="finishCheckout()">CONTINUER</button>
      <p class="note">⚠️ Le paiement réel n'est pas connecté dans cette version. Pour encaisser réellement des commandes, il faudra connecter un prestataire de paiement et les informations légales de la boutique.</p>
    </div>
  </div>
</div>

<script>
const products=[
{id:1,name:"Hoodie LOGANATOR",price:49.9,cat:"Hauts",image:"photo-5.jpg",details:["Hoodie LOGANATOR","Design officiel LOGANATOR","Photo produit incluse","Tailles à définir"]},
{id:2,name:"Doudoune LOGANATOR",price:89.9,cat:"Doudounes",image:"photo-9.png",details:["Doudoune LOGANATOR","Coupe épaisse et large","Design blanc LOGANATOR","Tailles à définir"]},
{id:3,name:"Baggy LOGANATOR",price:59.9,cat:"Bas",image:"photo-13.png",details:["Baggy LOGANATOR","Coupe large / streetwear","Design LOGANATOR","Tailles à définir"]},
{id:4,name:"Chaussures LOGANATOR",price:79.9,cat:"Chaussures",image:"photo-6.png",details:["Chaussures LOGANATOR","Design personnalisé LOGANATOR","Photo produit incluse","Tailles à définir"]},
{id:"pack-gaming-neon-bleu",name:"Pack Gaming LOGANATOR Néon Bleu",price:100,cat:"Gaming",category:"Gaming",stock:200,image:"photo-15.png",image_url:"photo-15.png",description:"Pack gaming complet LOGANATOR avec clavier mécanique AZERTY, souris haute précision, tapis de souris XXL et télécommande RGB.",details:["Clavier mécanique AZERTY","Souris haute précision jusqu'à 16 000 DPI","Tapis de souris XXL ultra fluide","Télécommande RGB incluse","RGB personnalisable"],sizes:[],is_published:true,is_new:true,is_featured:true}];

let cart=JSON.parse(localStorage.getItem('loganatorCart')||'[]'),
    favorites=JSON.parse(localStorage.getItem('loganatorFavorites')||'[]');
let selectedProduct=null,selectedPayment="Carte bancaire",selectedFilter="Tous",selectedSize="";

function save(){localStorage.setItem('loganatorCart',JSON.stringify(cart));update()}
function update(){document.getElementById('count').textContent=cart.reduce((s,x)=>s+x.qty,0)}
function fav(id){return favorites.map(String).includes(String(id))}
function toggleFav(id){id=String(id);favorites=fav(id)?favorites.filter(x=>String(x)!==id):[...favorites,id];localStorage.setItem('loganatorFavorites',JSON.stringify(favorites));render()}
function sizes(p){return p.cat==="Chaussures"?["36","37","38","39","40","41","42","43","44","45"]:["XS","S","M","L","XL","XXL"]}

function add(id,size){
 const p=products.find(x=>String(x.id)===String(id));
 if(!p)return;
 size=size||selectedSize||sizes(p)[0];
 let x=cart.find(a=>String(a.id)===String(id)&&a.size===size);
 if(x)x.qty++;else cart.push({id:p.id,qty:1,size});
 save();alert("Ajouté au panier 🛒")
}
function changeQty(id,size,d){
 let x=cart.find(a=>String(a.id)===String(id)&&a.size===size);
 if(!x)return;
 x.qty+=d;
 if(x.qty<1)cart=cart.filter(a=>a!==x);
 save();renderCheckout()
}
function removeCart(id,size){cart=cart.filter(a=>!(String(a.id)===String(id)&&a.size===size));save();renderCheckout()}

function openProduct(id){
 selectedProduct=products.find(p=>String(p.id)===String(id));
 if(!selectedProduct)return;
 selectedSize="";
 const price=Number(selectedProduct.price)||0;
 document.getElementById('detailImage').src=selectedProduct.image;
 document.getElementById('detailImage').alt=selectedProduct.name;
 document.getElementById('detailName').textContent=selectedProduct.name;
 document.getElementById('detailPrice').textContent=price.toFixed(2)+"€";
 document.getElementById('detailCat').textContent=selectedProduct.cat;
 document.getElementById('detailList').innerHTML=(selectedProduct.details||[]).map(x=>`<li>${x}</li>`).join('');
 document.getElementById('v15Sizes').innerHTML=sizes(selectedProduct).map(x=>`<button class="v15-size-btn" type="button" data-size="${x}">${x}</button>`).join('');
 document.querySelectorAll('#v15Sizes button').forEach(b=>b.onclick=()=>{
   selectedSize=b.dataset.size;
   document.querySelectorAll('#v15Sizes button').forEach(x=>x.classList.toggle('selected',x===b))
 });
 document.getElementById('detailAdd').onclick=()=>add(selectedProduct.id);
 document.getElementById('detailBuy').onclick=()=>{
   add(selectedProduct.id);closeModal('detailModal');openCheckout()
 };
 document.getElementById('detailModal').classList.add('open')
}

function closeModal(id){const e=document.getElementById(id);if(e)e.classList.remove('open')}

function renderCheckout(){
 let total=0;
 document.getElementById('checkoutItems').innerHTML=cart.length?cart.map(x=>{
   const p=products.find(p=>String(p.id)===String(x.id));
   if(!p)return '';
   const price=Number(p.price)||0,line=price*x.qty;
   total+=line;
   return `<div class="v15-cart-row"><img src="${p.image}" alt="${p.name}"><div><b>${p.name}</b><div class="muted">${price.toFixed(2)}€ · Taille ${x.size}</div><div class="v15-qty"><button onclick='changeQty(${JSON.stringify(p.id)},${JSON.stringify(x.size)},-1)'>−</button><b>${x.qty}</b><button onclick='changeQty(${JSON.stringify(p.id)},${JSON.stringify(x.size)},1)'>+</button><button class="v15-remove" onclick='removeCart(${JSON.stringify(p.id)},${JSON.stringify(x.size)})'>Supprimer</button></div></div><div class="v15-line-total"><b>${line.toFixed(2)}€</b></div></div>`
 }).join(''):'<div class="v15-empty">Ton panier est vide 😴</div>';
 document.getElementById('checkoutTotal').textContent=total.toFixed(2)+"€";
 document.getElementById('v15Shipping').textContent=total>=80?"🚚 Livraison offerte !":"🚚 Livraison offerte à partir de 80€ — encore "+(80-total).toFixed(2)+"€"
}

function openCheckout(){if(!cart.length)return alert("Ton panier est vide 😴");renderCheckout();document.getElementById('checkoutModal').classList.add('open')}
function showCart(){openCheckout()}
function finishCheckout(){
 const ids=["firstName","lastName","address","postcode","city"],
       missing=ids.some(id=>!document.getElementById(id).value.trim());
 document.getElementById('formError').classList.toggle('show',missing);
 if(missing)return;
 alert("Mode sélectionné : "+selectedPayment+"\n\nLe paiement réel n'est pas encore connecté.")
}

/* V15 — recherche + catégories.
   Tout passe par cette seule fonction render().
   Elle cherche dans nom, catégorie et description, sans dépendre de la casse.
*/
function normalizeCategory(value){
 const c=String(value||'').trim().toLowerCase();
 if(['hoodie','haut','hauts','top','sweat'].includes(c))return 'Hoodie';
 if(['doudoune','doudounes','puffer'].includes(c))return 'Doudoune';
 if(['baggy','bas','bottom','pantalon'].includes(c))return 'Baggy';
 if(['chaussure','chaussures','shoe','shoes'].includes(c))return 'Chaussures';
 return String(value||'').trim();
}

function productSearchText(p){
 return [
   p.name||'',
   p.cat||'',
   p.description||'',
   ...(Array.isArray(p.details)?p.details:[])
 ].join(' ').toLowerCase();
}

function render(){
 const input=document.getElementById('v15Search');
 const q=(input?.value||'').trim().toLowerCase();

 const list=products.filter(p=>{
   const category=normalizeCategory(p.cat);
   const matchesCategory=selectedFilter==="Tous"||category===selectedFilter;
   const matchesSearch=!q||productSearchText(p).includes(q);
   return matchesCategory&&matchesSearch;
 });

 const count=document.getElementById('v15ResultCount');
 if(count)count.textContent=`${list.length} produit${list.length>1?'s':''} trouvé${list.length>1?'s':''}`;

 document.getElementById('grid').innerHTML=list.length?list.map(p=>{
   const price=Number(p.price)||0;
   return `<article class="card" onclick='openProduct(${JSON.stringify(p.id)})'>
     <div class="photo">
       <img src="${p.image}" alt="${p.name}" loading="lazy">
       <button class="v15-fav" onclick='event.stopPropagation();toggleFav(${JSON.stringify(p.id)})'>${fav(p.id)?"♥":"♡"}</button>
     </div>
     <div class="info">
       <span class="tag">LOGANATOR</span>
       <h3>${p.name}</h3>
       <div class="row"><span class="price">${price.toFixed(2)}€</span><span class="muted">${normalizeCategory(p.cat)}</span></div>
       <button class="buy" onclick='event.stopPropagation();add(${JSON.stringify(p.id)})'>AJOUTER AU PANIER</button>
     </div>
   </article>`
 }).join(''):'<div class="v15-empty">Aucun produit ne correspond à ta recherche ou à cette catégorie.</div>';

 document.getElementById('gallery').innerHTML=products.map(p=>`<img src="${p.image}" alt="${p.name}" loading="lazy" onclick='openProduct(${JSON.stringify(p.id)})' style="cursor:pointer">`).join('');
}

document.getElementById('v15Search').addEventListener('input',render);
document.querySelectorAll('#v15Filters button').forEach(b=>{
 b.addEventListener('click',()=>{
   selectedFilter=b.dataset.filter;
   document.querySelectorAll('#v15Filters button').forEach(x=>x.classList.toggle('active',x===b));
   render()
 })
});
document.querySelectorAll('.pay-option').forEach(b=>b.addEventListener('click',()=>{
 selectedPayment=b.dataset.pay;
 document.querySelectorAll('.pay-option').forEach(x=>x.classList.toggle('selected',x===b))
}));

render();
update();
</script>

<div class="modal" id="accountModal" onclick="if(event.target===this)closeModal('accountModal')">
<div class="panel"><button class="close" onclick="closeModal('accountModal')">×</button>
<div class="detail-info"><span class="tag">LOGANATOR</span><h2 id="authTitle">Connexion / création de compte</h2><div id="accountArea">
<div class="field"><label>Adresse e-mail</label><input id="v12Email" type="email" autocomplete="email" placeholder="ton@email.com"></div><br>
<div class="field"><label>Mot de passe</label><input id="v12Password" type="password" autocomplete="current-password" placeholder="••••••••"></div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:15px"><button class="primary" type="button" id="v26Login" onclick="window.v26Login&&window.v26Login()">SE CONNECTER</button><button class="btn" type="button" id="v26Signup" onclick="window.v26Signup&&window.v26Signup()">CRÉER UN COMPTE</button><button class="btn" type="button" id="v26Reset" onclick="window.v26Reset&&window.v26Reset()">MOT DE PASSE OUBLIÉ</button></div>
<div id="v12Msg" class="notice">Tout se fait depuis LOGANATOR. Le contrôle Premium fonctionne sans service externe.</div>
</div></div></div></div>
<div class="admin-modal" id="premiumModal" onclick="if(event.target===this)closePremium()">
<div class="admin-box"><button class="admin-close" onclick="closePremium()">×</button>
<span class="premium">👑 COMPTE PREMIUM</span><h2>Centre de contrôle LOGANATOR</h2>
<p class="muted">Gestion produits, site, promotions, commandes, membres et statistiques.</p>
<div id="premiumMsg" class="admin-msg">Chargement…</div><div class="v22-store-panel" id="v22StorePanel"><div class="v22-store-title">🏪 TES DEUX BOUTIQUES</div><div class="muted">Le même compte PREMIUM peut accéder aux deux boutiques.</div><div class="v22-store-grid"><div class="v22-store-card"><b>🔵 LOGANATOR</b><span class="muted">Boutique actuelle</span><button class="btn" type="button" onclick="v22StayLoganator()">RESTANTER SUR LOGANATOR</button></div><div class="v22-store-card"><b>🌸 LOGANATRICE</b><span class="muted">Boutique rose</span><button class="primary" type="button" onclick="v22OpenLoganatrice()">OUVRIR LOGANATRICE ↗</button></div></div></div>
<div class="v16-tabs">
<button class="v16-tab active" data-v16-tab="overview">📊 Vue d'ensemble</button><button class="v16-tab" data-v16-tab="products">🛍️ Produits</button><button class="v16-tab" data-v16-tab="site">🎨 Site & brouillon</button><button class="v16-tab" data-v16-tab="promos">🏷️ Promotions</button><button class="v16-tab" data-v16-tab="orders">📦 Commandes</button><button class="v16-tab" data-v16-tab="members">👥 Membres</button><button class="v16-tab" data-v16-tab="tools">🧠 Outils</button><button class="v16-tab" data-v16-tab="stores">🏪 Contrôler boutiques</button></div>
<section class="v16-section active" data-v16-section="overview"><div class="v16-kpis"><div class="v16-kpi">Visites<b id="v16Visits">0</b></div><div class="v16-kpi">Vues produits<b id="v16Views">0</b></div><div class="v16-kpi">Ajouts panier<b id="v16Adds">0</b></div><div class="v16-kpi">Commandes<b id="v16Orders">0</b></div></div><div class="v16-two"><div class="v16-box"><h3>🔥 Produits populaires</h3><div id="v16Popular" class="v16-list"></div></div><div class="v16-box"><h3>⚠️ Alertes</h3><div id="v16Alerts"></div></div></div><div class="v16-box"><h3>📈 Activité récente</h3><div id="v16Recent" class="v16-list"></div></div></section>
<section class="v16-section" data-v16-section="stores">
<div class="v16-box"><h3>🏪 Contrôler boutiques</h3>
<p class="v16-muted">Ton compte PREMIUM contrôle maintenant les deux boutiques depuis ce même centre. Les modifications de catalogue sont publiées directement sur l’hébergement.</p>
<div id="v26StoreCards" class="v16-two"></div>
<div id="v26StoreStatus" class="v16-good" style="margin-top:12px">Chargement des boutiques…</div>
</div>
<div class="v16-box"><h3>ℹ️ Boutique sélectionnée</h3><div id="v26StoreInfo" class="v16-muted">—</div></div>
</section> <section class="v16-section" data-v16-section="products"><div class="admin-actions"><button class="primary" onclick="newPremiumProduct()">➕ Ajouter un produit</button><button class="btn" onclick="v16RefreshProducts()">🔄 Actualiser</button><button class="btn" onclick="v16ExportCatalogCSV()">📊 Exporter CSV</button><button class="btn" onclick="v16ExportBackup()">💾 Sauvegarder la boutique</button><button class="btn" onclick="document.getElementById('v16BackupFile').click()">📥 Restaurer une sauvegarde</button><input id="v16BackupFile" type="file" accept="application/json,.json" style="display:none" onchange="v16ImportBackup(event)"></div><p class="v16-help">Les modifications des produits sont publiées directement sur l’hébergement et deviennent visibles publiquement. Les sauvegardes restent locales à ce navigateur.</p><div id="premiumList" class="admin-grid"></div></section>
<section class="v16-section" data-v16-section="site"><div class="v16-box"><h3>✏️ Contenu du site</h3><div class="v16-form"><label>Titre HERO<input id="v16HeroTitle" placeholder="PORTE TON LOGANATOR."></label><label>Sous-titre<input id="v16HeroText" placeholder="La boutique officielle…"></label><label>Bouton principal<input id="v16HeroButton" placeholder="VOIR LA COLLECTION"></label><label>Texte bannière<input id="v16BannerText" placeholder="LIVRAISON OFFERTE…"></label><label class="full">Message DROP<textarea id="v16DropText"></textarea></label></div></div><div class="v16-box"><h3>🧩 Sections</h3><div class="v16-switch"><span>Afficher les nouveautés</span><input id="v16ShowNew" type="checkbox" checked></div><div class="v16-switch"><span>Afficher les populaires</span><input id="v16ShowPopular" type="checkbox" checked></div><div class="v16-switch"><span>Afficher les promotions</span><input id="v16ShowPromos" type="checkbox" checked></div><div class="v16-switch"><span>Afficher le DROP</span><input id="v16ShowDrop" type="checkbox" checked></div></div><div class="v16-preview"><b>👁️ Prévisualisation</b><h2 id="v16PreviewTitle">PORTE TON LOGANATOR.</h2><p id="v16PreviewText" class="muted">La boutique officielle LOGANATOR.</p><button class="primary" id="v16PreviewButton">VOIR LA COLLECTION</button></div><div class="v16-publish"><button class="btn" onclick="v16SaveDraft()">💾 Enregistrer brouillon</button><button class="primary" onclick="v16PublishDraft()">🚀 Publier</button></div></section>
<section class="v16-section" data-v16-section="promos"><div class="v16-box"><h3>🏷️ Créer un code promo</h3><div class="v16-form"><label>Code<input id="v16PromoCode" placeholder="LOGAN10"></label><label>Type<select id="v16PromoType"><option value="percent">Pourcentage</option><option value="fixed">Montant fixe</option></select></label><label>Valeur<input id="v16PromoValue" type="number" min="0" step="0.01"></label><label>Minimum d'achat<input id="v16PromoMin" type="number" min="0" step="0.01"></label><label>Date de début<input id="v16PromoStart" type="datetime-local"></label><label>Date de fin<input id="v16PromoEnd" type="datetime-local"></label><label>Limite d'utilisations<input id="v16PromoMax" type="number" min="1"></label><button class="primary full" onclick="v16CreatePromo()">➕ Créer le code</button></div></div><div class="v16-box"><h3>Codes existants</h3><div id="v16PromoList" class="v16-list"></div></div></section>
<section class="v16-section" data-v16-section="orders"><div class="admin-actions"><button class="btn" onclick="v16LoadOrders()">🔄 Actualiser les commandes</button></div><div class="v16-scroll"><table class="v16-table"><thead><tr><th>Date</th><th>Client</th><th>Total</th><th>Statut</th><th>Paiement</th><th></th></tr></thead><tbody id="v16OrderRows"></tbody></table></div></section>
<section class="v16-section" data-v16-section="members"><div class="v16-box"><h3>👥 Gestion des membres PREMIUM</h3><div class="admin-actions"><input id="premiumMemberEmail" type="email" placeholder="nouveau@email.com" style="flex:1;min-width:220px;padding:12px;border:1px solid var(--line);border-radius:10px;background:#09090d;color:#fff"><button class="primary" onclick="addPremiumMember()">➕ AJOUTER</button></div><div id="premiumMembersList" class="admin-grid"></div></div></section>
<section class="v16-section" data-v16-section="tools"><div class="v16-two"><div class="v16-box"><h3>🧠 Assistant boutique</h3><div id="v16SmartTools"></div></div><div class="v16-box"><h3>🧹 Contrôle qualité</h3><div id="v16Quality"></div></div></div><div class="v16-box"><h3>🛡️ Sécurité</h3><p class="v16-help">Les écritures Premium sont protégées par une session PHP côté serveur. Aucune clé de base de données n’est exposée dans le navigateur.</p></div></section>
<div class="admin-actions" style="margin-top:18px"><button class="btn" onclick="premiumLogout()">Se déconnecter</button></div></div></div>
<div class="admin-modal" id="premiumEditor" onclick="if(event.target===this)closeEditor()"><div class="admin-box"><button class="admin-close" onclick="closeEditor()">×</button><span class="premium">👑 PREMIUM</span><h2 id="premiumEditorTitle">Modifier un produit</h2><form class="admin-form" id="premiumForm"><input type="hidden" id="premiumId"><label class="full">Nom<input id="premiumName" required></label><label>Prix (€)<input id="premiumPrice" type="number" step="0.01" min="0" required></label><label>Prix avant promo (€)<input id="premiumCompare" type="number" step="0.01" min="0"></label><label>Catégorie<select id="premiumCat"><option>Hauts</option><option>Doudounes</option><option>Bas</option><option>Chaussures</option><option>Gaming</option></select></label><label>Stock<input id="premiumStock" type="number" min="0" value="0"></label><label class="full">Tailles (séparées par des virgules)<input id="premiumSizes" placeholder="XS,S,M,L,XL"></label><label>Badge<select id="premiumBadge"><option value="">Aucun</option><option>NEW</option><option>PROMO</option><option>BEST-SELLER</option><option>ÉPUISÉ</option></select></label><label>Publication<input id="premiumPublishAt" type="datetime-local"></label><label class="full">Description<textarea id="premiumDetails"></textarea></label><label class="full">Image<input id="premiumImage" type="file" accept="image/*"></label><label><input id="premiumFeatured" type="checkbox"> ⭐ Produit à la une</label><label><input id="premiumPopular" type="checkbox"> 🔥 Produit populaire</label><label><input id="premiumNew" type="checkbox" checked> 🆕 Nouveauté</label><label><input id="premiumPublished" type="checkbox" checked> 👁️ Publié</label><button class="primary full" type="submit">💾 ENREGISTRER</button></form></div></div>
<div class="admin-modal" id="premiumMembersModal" style="display:none"></div>
<script>
// LOGANATOR V27 — fonctionnement autonome : PHP + JSON, sans Supabase.
const LOGANATOR_PREMIUM=["gauducheaulogan@gmail.com","leloganator@gmail.com","alexisprouillac@gmail.com","theteamsloganator@gmail.com"];
const LOGANATOR_API='api';
const LOGANATOR_SB_URL='https://aidaqphingkvvevgbeyk.supabase.co';
const LOGANATOR_SB_KEY='sb_publishable_g3yTfPLGBasbLU4Q3LEi0w_P6b8ROLM';
let LOGANATOR_SB=null, LOGANATOR_USER=null;
try{ if(window.supabase?.createClient) LOGANATOR_SB=window.supabase.createClient(LOGANATOR_SB_URL,LOGANATOR_SB_KEY); }catch(e){ console.warn('Supabase Auth:',e.message); }
function premiumEmail(){return (LOGANATOR_USER?.email||'').toLowerCase()}
function isLoganatorPremium(){return !!LOGANATOR_USER && (LOGANATOR_USER.role==='premium' || LOGANATOR_PREMIUM.includes(premiumEmail()))}
function showAccountMessage(m){const el=document.getElementById('accountArea');if(el)el.innerHTML=m}
async function apiFetch(path,options={}){const r=await fetch('index.php?api='+encodeURIComponent(path),{credentials:'same-origin',...options});let d={};try{d=await r.json()}catch{}if(!r.ok||d.ok===false)throw new Error(d.error||('Erreur HTTP '+r.status));return d}
window.openAccount=async function(){
  if(LOGANATOR_USER){if(isLoganatorPremium()){openPremium();return}showAccountMessage(`<p>Connecté avec <b>${esc(LOGANATOR_USER.email)}</b>.</p><button class="btn" onclick="premiumLogout()">Se déconnecter</button>`)}
  else showAccountMessage(`<div class="field"><label>Adresse e-mail</label><input id="v12Email" type="email" placeholder="ton@email.com"></div><br><div class="field"><label>Mot de passe</label><input id="v12Password" type="password" placeholder="••••••••"></div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:15px"><button class="primary" onclick="v12Login()">SE CONNECTER</button></div><div id="v12Msg" class="notice">Connexion Premium autonome. Aucun Supabase.</div><div style="margin-top:10px;font-size:.9em;opacity:.75">Première installation : ouvre <b>setup.php</b> une seule fois pour créer le compte administrateur.</div>`);
  document.getElementById('authTitle').textContent=LOGANATOR_USER?'Mon compte':'Connexion Premium';
  document.getElementById('accountModal').classList.add('open');
}
function v12msg(m){const e=document.getElementById('v12Msg');if(e)e.textContent=m}
window.v12Login=async function(){const email=document.getElementById('v12Email')?.value.trim(),password=document.getElementById('v12Password')?.value;if(!email||!password)return v12msg('Remplis les deux champs.');try{if(!LOGANATOR_SB?.auth)throw new Error('Connexion Supabase indisponible.');const {data,error}=await LOGANATOR_SB.auth.signInWithPassword({email,password});if(error)throw error;const u=data.user;LOGANATOR_USER={email:(u?.email||email).toLowerCase(),role:LOGANATOR_PREMIUM.includes((u?.email||email).toLowerCase())?'premium':'user',id:u?.id||null};closeModal('accountModal');document.getElementById('accountBtn').textContent=isLoganatorPremium()?'👑 PREMIUM':'👤 COMPTE';await v12LoadProducts();render();if(isLoganatorPremium())openPremium()}catch(e){v12msg('❌ '+(e.message||'Connexion impossible.'))}}
window.v12Signup=async function(){const email=document.getElementById('v12Email')?.value.trim(),password=document.getElementById('v12Password')?.value;if(!email||password.length<6)return v12msg('E-mail valide + mot de passe de 6 caractères minimum.');try{const {error}=await LOGANATOR_SB.auth.signUp({email,password,options:{emailRedirectTo:location.href}});v12msg(error?'❌ '+error.message:'✅ Compte créé. Vérifie ton e-mail si une confirmation est demandée.')}catch(e){v12msg('❌ '+e.message)}}
window.v12Reset=async function(){const email=document.getElementById('v12Email')?.value.trim();if(!email)return v12msg('Entre ton e-mail.');try{const {error}=await LOGANATOR_SB.auth.resetPasswordForEmail(email,{redirectTo:location.href});v12msg(error?'❌ '+error.message:'✅ Si le compte existe, un e-mail de réinitialisation a été envoyé.')}catch(e){v12msg('❌ '+e.message)}}
window.premiumLogout=async function(){try{await LOGANATOR_SB?.auth?.signOut()}catch{}LOGANATOR_USER=null;closePremium();closeModal('accountModal');document.getElementById('accountBtn').textContent='👤 COMPTE'}
function premiumMessage(m,ok=false){const e=document.getElementById('premiumMsg');if(e){e.textContent=m;e.className='admin-msg'+(ok?' ok':'')}}
window.openPremium=async function(){if(!isLoganatorPremium())return alert('Accès réservé aux membres PREMIUM 👑');await v12LoadProducts();render();renderPremiumList();document.getElementById('premiumModal').classList.add('open')}
window.closePremium=function(){document.getElementById('premiumModal').classList.remove('open')}
window.closeEditor=function(){document.getElementById('premiumEditor').classList.remove('open')}
function localFallbackImage(p){const n=(p.name||'').toLowerCase();if(n.includes('hoodie'))return'photo-5.jpg';if(n.includes('doudoune'))return'photo-9.png';if(n.includes('baggy'))return'photo-13.png';if(n.includes('chauss'))return'photo-6.png';return'photo-5.jpg'}
function dbToProduct(p){const description=(p.description||'').trim();const rawCat=p.category||p.cat||'';const cat=normalizeCategory(rawCat);return {...p,price:Number(p.price)||0,cat,category:rawCat,image:p.image_url||p.image||localFallbackImage(p),description,details:Array.isArray(p.details)&&p.details.length?p.details:(description?description.split(/\r?\n/).map(x=>x.trim()).filter(Boolean):[p.name||'Produit LOGANATOR'])}}
async function v12LoadProducts(){try{const d=await apiFetch('products.php');const remote=(d.products||[]).map(dbToProduct);if(remote.length){products.splice(0,products.length,...remote)}return remote}catch(e){console.warn('Catalogue PHP:',e.message);return products}}
window.v12LoadProducts=v12LoadProducts;
function renderPremiumList(){const box=document.getElementById('premiumList');if(!box)return;box.innerHTML=products.map(p=>`<div class="admin-card"><img src="${esc(p.image)}" alt="${esc(p.name)}"><div><b>${esc(p.name)}</b><div class="muted">${Number(p.price).toFixed(2)}€ · ${esc(p.cat)} · Stock: ${Number(p.stock??0)}</div></div><div class="admin-actions"><button class="btn" onclick='downloadPremiumImage(${JSON.stringify(p.id)})'>⬇️ Télécharger l’image</button><button class="btn" onclick='editPremiumProduct(${JSON.stringify(p.id)})'>✏️ Modifier</button><button class="btn" onclick='deletePremiumProduct(${JSON.stringify(p.id)})'>🗑️ Supprimer</button></div></div>`).join('')||'<div class="v16-muted">Aucun produit.</div>'}
window.editPremiumProduct=function(id){const p=products.find(x=>String(x.id)===String(id));if(!p)return;document.getElementById('premiumEditorTitle').textContent='Modifier le produit';document.getElementById('premiumId').value=id;document.getElementById('premiumName').value=p.name||'';document.getElementById('premiumPrice').value=p.price||0;document.getElementById('premiumCompare').value=p.compare_at_price||'';document.getElementById('premiumCat').value=normalizeCategory(p.cat)||'Hauts';document.getElementById('premiumStock').value=p.stock??0;document.getElementById('premiumSizes').value=(p.sizes||sizes(p)).join(',');document.getElementById('premiumBadge').value=p.badge||'';document.getElementById('premiumDetails').value=p.description||((p.details||[]).join('\n'));document.getElementById('premiumFeatured').checked=!!p.is_featured;document.getElementById('premiumPopular').checked=!!p.is_popular;document.getElementById('premiumNew').checked=p.is_new!==false;document.getElementById('premiumPublished').checked=p.is_published!==false;document.getElementById('premiumPublishAt').value=p.publish_at?new Date(p.publish_at).toISOString().slice(0,16):'';document.getElementById('premiumImage').value='';document.getElementById('premiumEditor').classList.add('open')}
window.newPremiumProduct=function(){document.getElementById('premiumEditorTitle').textContent='Ajouter un produit';['premiumId','premiumName','premiumCompare','premiumPublishAt','premiumDetails'].forEach(id=>document.getElementById(id).value='');document.getElementById('premiumPrice').value=0;document.getElementById('premiumCat').value='Hauts';document.getElementById('premiumStock').value=0;document.getElementById('premiumSizes').value='XS,S,M,L,XL';document.getElementById('premiumBadge').value='';document.getElementById('premiumFeatured').checked=false;document.getElementById('premiumPopular').checked=false;document.getElementById('premiumNew').checked=true;document.getElementById('premiumPublished').checked=true;document.getElementById('premiumImage').value='';document.getElementById('premiumEditor').classList.add('open')}
async function v12Upload(file){const fd=new FormData();fd.append('image',file);const d=await apiFetch('upload.php',{method:'POST',body:fd});return d.url}
window.deletePremiumProduct=async function(id){if(!isLoganatorPremium()||!confirm('Supprimer ce produit pour tout le monde ?'))return;try{await apiFetch('products.php?id='+encodeURIComponent(id),{method:'DELETE'});await v12LoadProducts();render();renderPremiumList();premiumMessage('✅ Produit supprimé publiquement pour tout le monde.',true)}catch(e){alert('Erreur : '+e.message)}}
async function v15CheckPremium(email){return !!LOGANATOR_USER&&LOGANATOR_USER.email.toLowerCase()===String(email||'').toLowerCase()}
async function openPremiumMembers(){if(!isLoganatorPremium())return;document.getElementById('premiumMembersModal').classList.add('open');await loadPremiumMembers()}
function closePremiumMembers(){document.getElementById('premiumMembersModal').classList.remove('open')}
function loadPremiumMembers(){const box=document.getElementById('premiumMembersList');if(!box)return;box.innerHTML=`<div class="admin-card"><div><b>${esc(LOGANATOR_USER?.email||'Compte Premium')}</b><div class="muted">Administrateur Premium autonome</div></div></div>`}
function addPremiumMember(){alert('La gestion des comptes se fait sur l’hébergement, sans Supabase.')}
function removePremiumMember(){alert('La gestion des comptes se fait sur l’hébergement, sans Supabase.')}
async function bootV12(){try{if(LOGANATOR_SB?.auth){const {data}=await LOGANATOR_SB.auth.getSession();const u=data?.session?.user;if(u){const email=(u.email||'').toLowerCase();LOGANATOR_USER={email,role:LOGANATOR_PREMIUM.includes(email)?'premium':'user',id:u.id||null};}}}catch(e){console.warn('Session Supabase:',e.message)}document.getElementById('accountBtn').textContent=isLoganatorPremium()?'👑 PREMIUM':'👤 COMPTE';await v12LoadProducts();render();if(LOGANATOR_SB?.auth)LOGANATOR_SB.auth.onAuthStateChange((_event,session)=>{const u=session?.user;if(u){const email=(u.email||'').toLowerCase();LOGANATOR_USER={email,role:LOGANATOR_PREMIUM.includes(email)?'premium':'user',id:u.id||null}}else LOGANATOR_USER=null;document.getElementById('accountBtn').textContent=isLoganatorPremium()?'👑 PREMIUM':'👤 COMPTE';});}
bootV12();
setInterval(async()=>{if(document.hidden)return;try{await v12LoadProducts();render()}catch(e){}},5000);
</script>\n
<script>
/* LOGANATOR V16.1 PREMIUM */
const V16_OWNER_EMAILS=['gauducheaulogan@gmail.com','leloganator@gmail.com','alexisprouillac@gmail.com','theteamsloganator@gmail.com'];
let v16Stats={};
function v16Esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
function v16Can(){return !!LOGANATOR_USER && (isLoganatorPremium() || V16_OWNER_EMAILS.includes((LOGANATOR_USER.email||'').toLowerCase()))}
function v16Track(type,productId=null,metadata={}){if(!LOGANATOR_SB)return;LOGANATOR_SB.from('analytics_events').insert({event_type:type,product_id:productId||null,session_id:sessionStorage.getItem('loganatorSession')||crypto.randomUUID(),path:location.pathname,metadata}).then(()=>{}).catch(()=>{})}
if(!sessionStorage.getItem('loganatorSession'))sessionStorage.setItem('loganatorSession',crypto.randomUUID());
v16Track('visit');
const _openProduct=window.openProduct; window.openProduct=function(id){v16Track('product_view',id);return _openProduct(id)};
const _add=window.add; window.add=function(id,size){v16Track('cart_add',id);return _add(id,size)};
function v16SetTab(name){document.querySelectorAll('[data-v16-tab]').forEach(b=>b.classList.toggle('active',b.dataset.v16Tab===name));document.querySelectorAll('[data-v16-section]').forEach(s=>s.classList.toggle('active',s.dataset.v16Section===name));if(name==='overview')v16LoadStats();if(name==='orders')v16LoadOrders();if(name==='members')loadPremiumMembers();if(name==='promos')v16LoadPromos();if(name==='tools')v16LoadTools();if(name==='site')v16LoadSettings()}
document.addEventListener('click',e=>{const b=e.target.closest('[data-v16-tab]');if(b)v16SetTab(b.dataset.v16Tab)});
function v16Notice(m,ok=true){premiumMessage(m,ok)}
async function v16SchemaCheck(){if(!LOGANATOR_SB||!v16Can())return;const tables=['product_meta','store_settings','promo_codes','orders','order_items','analytics_events'];const missing=[];for(const t of tables){const r=await LOGANATOR_SB.from(t).select('*',{count:'exact',head:true});if(r.error && /relation|table|schema cache|does not exist|Could not find/i.test(r.error.message||''))missing.push(t)}if(missing.length)v16Notice('⚠️ Tables Premium manquantes : '+missing.join(', ')+'. Exécute premium_v16_1.sql dans Supabase.',false)}
async function v16LoadStats(){if(!v16Can()||!LOGANATOR_SB)return;const {data,error}=await LOGANATOR_SB.from('analytics_events').select('event_type,product_id,metadata,created_at').order('created_at',{ascending:false}).limit(1000);if(error){v16Notice('⚠️ Analytics indisponibles : '+error.message,false);return}const a=data||[];document.getElementById('v16Visits').textContent=a.filter(x=>x.event_type==='visit').length;document.getElementById('v16Views').textContent=a.filter(x=>x.event_type==='product_view').length;document.getElementById('v16Adds').textContent=a.filter(x=>x.event_type==='cart_add').length;const orders=await LOGANATOR_SB.from('orders').select('id,total,status,created_at,email').limit(1000);document.getElementById('v16Orders').textContent=(orders.data||[]).length;const counts={};a.filter(x=>x.event_type==='product_view').forEach(x=>counts[x.product_id]=(counts[x.product_id]||0)+1);const pop=Object.entries(counts).sort((a,b)=>b[1]-a[1]).slice(0,5);document.getElementById('v16Popular').innerHTML=pop.length?pop.map(([id,n])=>{const p=products.find(x=>String(x.id)===String(id));return `<div class="v16-line"><span>${v16Esc(p?.name||'Produit')}</span><b>${n} vues</b></div>`}).join(''):'<div class="v16-muted">Pas encore assez de données.</div>';const alerts=products.filter(p=>Number(p.stock??0)<=3);document.getElementById('v16Alerts').innerHTML=alerts.length?alerts.map(p=>`<div class="v16-alert">⚠️ <b>${v16Esc(p.name)}</b> : stock faible (${Number(p.stock??0)})</div>`).join(''):'<div class="v16-good">✅ Aucun stock faible.</div>';document.getElementById('v16Recent').innerHTML=a.slice(0,8).map(x=>`<div class="v16-line"><span>${v16Esc(x.event_type)}</span><span class="v16-muted">${new Date(x.created_at).toLocaleString('fr-FR')}</span></div>`).join('')||'<div class="v16-muted">Aucune activité.</div>'}
async function v16RefreshProducts(){await v12LoadProducts();render();renderPremiumList();v16LoadStats();v16LoadTools()}
function renderPremiumList(){const box=document.getElementById('premiumList');if(!box)return;box.innerHTML=products.map(p=>{const stock=Number(p.stock??0);const badge=p.badge||((stock<=0)?'ÉPUISÉ':p.is_new?'NEW':p.is_popular?'BEST-SELLER':'');return `<div class="admin-card"><img src="${v16Esc(p.image)}" alt="${v16Esc(p.name)}"><div><b>${v16Esc(p.name)}</b><div class="muted">${Number(p.price).toFixed(2)}€ · ${v16Esc(normalizeCategory(p.cat))} · Stock: ${stock}</div><div style="margin-top:6px">${badge?`<span class="v16-badge">${v16Esc(badge)}</span>`:''} ${p.is_featured?'⭐':''}</div></div><div class="admin-actions"><button class="btn" onclick='downloadPremiumImage(${JSON.stringify(p.id)})'>⬇️ Télécharger l’image</button><button class="btn" onclick='editPremiumProduct(${JSON.stringify(p.id)})'>✏️ Modifier</button><button class="btn" onclick='deletePremiumProduct(${JSON.stringify(p.id)})'>🗑️ Supprimer</button></div></div>`}).join('')}
const _editPremiumProduct=window.editPremiumProduct;window.editPremiumProduct=function(id){const p=products.find(x=>String(x.id)===String(id));if(!p)return;document.getElementById('premiumEditorTitle').textContent='Modifier un produit';document.getElementById('premiumId').value=id;document.getElementById('premiumName').value=p.name||'';document.getElementById('premiumPrice').value=p.price||0;document.getElementById('premiumCompare').value=p.compare_at_price||'';document.getElementById('premiumCat').value=normalizeCategory(p.cat)||'Hauts';document.getElementById('premiumStock').value=p.stock??0;document.getElementById('premiumSizes').value=(p.sizes||sizes(p)).join(',');document.getElementById('premiumBadge').value=p.badge||'';document.getElementById('premiumDetails').value=p.description||((p.details||[]).join('\n'));document.getElementById('premiumFeatured').checked=!!p.is_featured;document.getElementById('premiumPopular').checked=!!p.is_popular;document.getElementById('premiumNew').checked=p.is_new!==false;document.getElementById('premiumPublished').checked=p.is_published!==false;document.getElementById('premiumPublishAt').value=p.publish_at?new Date(p.publish_at).toISOString().slice(0,16):'';document.getElementById('premiumImage').value='';document.getElementById('premiumEditor').classList.add('open')};
const _newPremiumProduct=window.newPremiumProduct;window.newPremiumProduct=function(){document.getElementById('premiumEditorTitle').textContent='Ajouter un produit';['premiumId','premiumName','premiumCompare','premiumPublishAt','premiumDetails'].forEach(id=>document.getElementById(id).value='');document.getElementById('premiumPrice').value=0;document.getElementById('premiumCat').value='Hauts';document.getElementById('premiumStock').value=0;document.getElementById('premiumSizes').value='XS,S,M,L,XL';document.getElementById('premiumBadge').value='';document.getElementById('premiumFeatured').checked=false;document.getElementById('premiumPopular').checked=false;document.getElementById('premiumNew').checked=true;document.getElementById('premiumPublished').checked=true;document.getElementById('premiumImage').value='';document.getElementById('premiumEditor').classList.add('open')};

async function v16LoadSettings(){if(!v16Can())return;const {data,error}=await LOGANATOR_SB.from('store_settings').select('*').eq('id',1).maybeSingle();if(error||!data)return;const x=data.draft||{};document.getElementById('v16HeroTitle').value=x.heroTitle||'PORTE TON LOGANATOR.';document.getElementById('v16HeroText').value=x.heroText||'La boutique de la chaîne, avec les vraies photos de tes créations.';document.getElementById('v16HeroButton').value=x.heroButton||'VOIR LA COLLECTION';document.getElementById('v16BannerText').value=x.bannerText||'🚚 LIVRAISON OFFERTE À PARTIR DE 80€';document.getElementById('v16DropText').value=x.dropText||'';['New','Popular','Promos','Drop'].forEach(k=>{const el=document.getElementById('v16Show'+k);if(el)el.checked=x['show'+k]!==false});v16PreviewSettings()}
function v16SettingsObj(){return {heroTitle:document.getElementById('v16HeroTitle').value,heroText:document.getElementById('v16HeroText').value,heroButton:document.getElementById('v16HeroButton').value,bannerText:document.getElementById('v16BannerText').value,dropText:document.getElementById('v16DropText').value,showNew:document.getElementById('v16ShowNew').checked,showPopular:document.getElementById('v16ShowPopular').checked,showPromos:document.getElementById('v16ShowPromos').checked,showDrop:document.getElementById('v16ShowDrop').checked}}
function v16PreviewSettings(){const x=v16SettingsObj();document.getElementById('v16PreviewTitle').textContent=x.heroTitle;document.getElementById('v16PreviewText').textContent=x.heroText;document.getElementById('v16PreviewButton').textContent=x.heroButton}
['v16HeroTitle','v16HeroText','v16HeroButton','v16BannerText','v16DropText','v16ShowNew','v16ShowPopular','v16ShowPromos','v16ShowDrop'].forEach(id=>document.getElementById(id)?.addEventListener('input',v16PreviewSettings));
async function v16SaveDraft(){if(!v16Can())return;const x=v16SettingsObj();const {error}=await LOGANATOR_SB.from('store_settings').upsert({id:1,draft:x,updated_at:new Date().toISOString()});v16Notice(error?'❌ '+error.message:'💾 Brouillon enregistré.',!error)}
async function v16PublishDraft(){if(!v16Can())return;const x=v16SettingsObj();const {error}=await LOGANATOR_SB.from('store_settings').upsert({id:1,draft:x,published:x,updated_at:new Date().toISOString()});v16Notice(error?'❌ '+error.message:'🚀 Modifications publiées.',!error)}
async function v16CreatePromo(){if(!v16Can())return;const d={code:document.getElementById('v16PromoCode').value.trim().toUpperCase(),type:document.getElementById('v16PromoType').value,value:Number(document.getElementById('v16PromoValue').value)||0,min_order:Number(document.getElementById('v16PromoMin').value)||0,max_uses:Number(document.getElementById('v16PromoMax').value)||null,starts_at:document.getElementById('v16PromoStart').value?new Date(document.getElementById('v16PromoStart').value).toISOString():null,ends_at:document.getElementById('v16PromoEnd').value?new Date(document.getElementById('v16PromoEnd').value).toISOString():null,active:true};if(!d.code)return alert('Entre un code.');const {error}=await LOGANATOR_SB.from('promo_codes').insert(d);if(error)return alert(error.message);await v16LoadPromos()}
async function v16LoadPromos(){if(!v16Can())return;const {data,error}=await LOGANATOR_SB.from('promo_codes').select('*').order('created_at',{ascending:false});const box=document.getElementById('v16PromoList');if(error){box.innerHTML='<div class="v16-danger">'+v16Esc(error.message)+'</div>';return}box.innerHTML=(data||[]).map(p=>`<div class="v16-line"><span><b>${v16Esc(p.code)}</b> · ${p.type==='percent'?p.value+'%':Number(p.value).toFixed(2)+'€'} ${p.active?'🟢':'🔴'}</span><button class="btn" onclick='v16TogglePromo(${JSON.stringify(p.id)},${JSON.stringify(!p.active)})'>${p.active?'Désactiver':'Activer'}</button></div>`).join('')||'<div class="v16-muted">Aucun code.</div>'}
async function v16TogglePromo(id,active){const {error}=await LOGANATOR_SB.from('promo_codes').update({active}).eq('id',id);if(error)return alert(error.message);v16LoadPromos()}
async function v16LoadOrders(){if(!v16Can())return;const {data,error}=await LOGANATOR_SB.from('orders').select('id,email,total,status,payment_status,created_at').order('created_at',{ascending:false}).limit(200);const box=document.getElementById('v16OrderRows');if(error){box.innerHTML='<tr><td colspan="6">'+v16Esc(error.message)+'</td></tr>';return}box.innerHTML=(data||[]).map(o=>`<tr><td>${new Date(o.created_at).toLocaleString('fr-FR')}</td><td>${v16Esc(o.email||'—')}</td><td>${Number(o.total).toFixed(2)}€</td><td><select onchange='v16SetOrderStatus(${JSON.stringify(o.id)},this.value)'><option value="pending" ${o.status==='pending'?'selected':''}>En attente</option><option value="preparation" ${o.status==='preparation'?'selected':''}>Préparation</option><option value="shipped" ${o.status==='shipped'?'selected':''}>Expédiée</option><option value="delivered" ${o.status==='delivered'?'selected':''}>Livrée</option><option value="cancelled" ${o.status==='cancelled'?'selected':''}>Annulée</option></select></td><td>${v16Esc(o.payment_status)}</td><td><button class="btn" onclick='v16ViewOrder(${JSON.stringify(o.id)})'>Voir</button></td></tr>`).join('')||'<tr><td colspan="6">Aucune commande.</td></tr>'}
async function v16SetOrderStatus(id,status){const {error}=await LOGANATOR_SB.from('orders').update({status}).eq('id',id);if(error)alert(error.message);else v16Notice('✅ Statut mis à jour.',true)}
async function v16ViewOrder(id){const {data,error}=await LOGANATOR_SB.from('order_items').select('*').eq('order_id',id);if(error)return alert(error.message);alert((data||[]).map(x=>`${x.product_name} × ${x.quantity} — ${x.size||'sans taille'}`).join('\n')||'Commande vide')}
async function v16LoadTools(){if(!v16Can())return;const low=products.filter(p=>Number(p.stock??0)<=3);const missing=products.filter(p=>!p.description||!p.image);document.getElementById('v16SmartTools').innerHTML=`<div class="v16-line">🔥 Produits à mettre en avant <b>${products.filter(p=>p.is_popular||p.is_featured).length}</b></div><div class="v16-line">⚠️ Stocks faibles <b>${low.length}</b></div><div class="v16-line">📝 Fiches à compléter <b>${missing.length}</b></div><div class="v16-line">🆕 Nouveautés <b>${products.filter(p=>p.is_new!==false).length}</b></div>`;document.getElementById('v16Quality').innerHTML=products.map(p=>{const issues=[];if(!p.name)issues.push('nom');if(!p.description)issues.push('description');if(!p.image)issues.push('image');if(Number(p.price)<=0)issues.push('prix');return `<div class="v16-line"><span>${v16Esc(p.name)}</span><span class="${issues.length?'v16-danger':'v16-good'}">${issues.length?'⚠️ '+issues.join(', '):'✅ OK'}</span></div>`}).join('')}
const _openPremium=window.openPremium;window.openPremium=async function(){if(!v16Can())return alert('Accès réservé aux membres PREMIUM 👑');await v12LoadProducts();render();renderPremiumList();document.getElementById('premiumModal').classList.add('open');v16LoadStats();v16LoadSettings();v16LoadPromos();v16LoadTools()};
</script>

<script>
/* LOGANATOR V16.1 ZERO SUPABASE SETUP
   Premium extras are stored locally in this browser. No SQL/table/RLS is required.
   Core catalog continues to read the existing public `products` table when available.
*/
(function(){
  const KEY='loganator_v16_zero_setup_v1';
  const load=()=>{try{return JSON.parse(localStorage.getItem(KEY)||'{}')}catch(e){return {}}};
  const save=x=>localStorage.setItem(KEY,JSON.stringify(x));
  let store=load();
  store.products=Array.isArray(store.products)?store.products:[];
  store.settings=store.settings||{};
  store.promos=Array.isArray(store.promos)?store.promos:[];
  store.orders=Array.isArray(store.orders)?store.orders:[];
  store.members=Array.isArray(store.members)?store.members:[];
  store.events=Array.isArray(store.events)?store.events:[];
  const v27Seed={id:'pack-gaming-neon-bleu',name:'Pack Gaming LOGANATOR Néon Bleu',price:100,cat:'Gaming',category:'Gaming',stock:200,image:'photo-15.png',image_url:'photo-15.png',description:'Pack gaming complet LOGANATOR avec clavier mécanique AZERTY, souris haute précision, tapis de souris XXL et télécommande RGB.',details:['Clavier mécanique AZERTY',"Souris haute précision jusqu'à 16 000 DPI",'Tapis de souris XXL ultra fluide','Télécommande RGB incluse','RGB personnalisable'],sizes:[],is_published:true,is_new:true,is_featured:true};
  if(!store.products.some(x=>String(x.id)==='pack-gaming-neon-bleu')){store.products.push(v27Seed)}
  save(store);

  const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const uid=()=> 'local-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,8);
  const localProducts=()=>store.products;
  const mergeProduct=(p)=>{const i=store.products.findIndex(x=>String(x.id)===String(p.id)); if(i>=0)store.products[i]={...store.products[i],...p}; else store.products.push(p); save(store)};
  const removeProduct=id=>{store.products=store.products.filter(x=>String(x.id)!==String(id));save(store)};
  const applyMeta=()=>{
    products.forEach(p=>{const m=store.products.find(x=>String(x.id)===String(p.id));if(m)Object.assign(p,m)});
    store.products.filter(p=>String(p.id).startsWith('local-')).forEach(p=>{if(!products.some(x=>String(x.id)===String(p.id)))products.push(p)});
  };
  const can=()=>typeof v16Can==='function' ? v16Can() : isLoganatorPremium();
  const notice=(m,ok=false)=>{if(typeof v16Notice==='function')v16Notice(m,ok);else if(typeof premiumMessage==='function')premiumMessage(m,ok)};

  // V27 PUBLIC CRUD — PHP/JSON, no Supabase.
  const oldForm=document.getElementById('premiumForm');
  if(oldForm){
    const clean=oldForm.cloneNode(true); oldForm.replaceWith(clean);
    clean.addEventListener('submit',async e=>{
      e.preventDefault();
      if(!can()) return alert('Accès Premium requis.');
      const id=document.getElementById('premiumId').value.trim();
      const existing=products.find(x=>String(x.id)===String(id))||{};
      const payload={
        id:id||undefined,
        name:document.getElementById('premiumName').value.trim(),
        price:Number(document.getElementById('premiumPrice').value)||0,
        category:document.getElementById('premiumCat').value,
        cat:document.getElementById('premiumCat').value,
        description:document.getElementById('premiumDetails').value.trim(),
        details:document.getElementById('premiumDetails').value.split(/\r?\n/).map(x=>x.trim()).filter(Boolean),
        stock:Number(document.getElementById('premiumStock').value)||0,
        sizes:document.getElementById('premiumSizes').value.split(',').map(x=>x.trim()).filter(Boolean),
        badge:document.getElementById('premiumBadge').value||null,
        is_featured:document.getElementById('premiumFeatured').checked,
        is_popular:document.getElementById('premiumPopular').checked,
        is_new:document.getElementById('premiumNew').checked,
        is_published:document.getElementById('premiumPublished').checked,
        publish_at:document.getElementById('premiumPublishAt').value?new Date(document.getElementById('premiumPublishAt').value).toISOString():null,
        compare_at_price:document.getElementById('premiumCompare').value?Number(document.getElementById('premiumCompare').value):null
      };
      const file=document.getElementById('premiumImage').files[0];
      try{
        if(file)payload.image_url=await v12Upload(file);
        else if(existing.image_url||existing.image)payload.image_url=existing.image_url||existing.image;
        payload.image=payload.image_url||existing.image_url||existing.image||'';
        const method=id?'PUT':'POST';
        const r=await apiFetch('products.php',{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        closeEditor();
        await v12LoadProducts();
        render();renderPremiumList();
        if(typeof v16LoadTools==='function')v16LoadTools();
        notice(id?'✅ Produit modifié et publié publiquement.':'✅ Produit ajouté et publié publiquement.',true);
      }catch(err){alert('Impossible de publier le produit : '+(err?.message||err))}
    });
  }

  window.downloadPremiumImage=async function(id){
    if(!(typeof v16Can==='function'?v16Can():isLoganatorPremium()))return alert('Téléchargement réservé aux membres PREMIUM 👑');
    const p=products.find(x=>String(x.id)===String(id));
    if(!p)return alert('Produit introuvable.');
    const src=p.image_url||p.image;
    if(!src)return alert('Aucune image disponible pour ce produit.');
    try{
      let blob;
      if(src.startsWith('data:')){
        const response=await fetch(src); blob=await response.blob();
      }else{
        const response=await fetch(src,{mode:'cors'});
        if(!response.ok)throw new Error('Image inaccessible');
        blob=await response.blob();
      }
      const url=URL.createObjectURL(blob),a=document.createElement('a');
      const ext=(blob.type.split('/')[1]||'png').replace('jpeg','jpg').split(';')[0];
      a.href=url;a.download=(p.name||'image-produit').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-zA-Z0-9_-]+/g,'-')+'.'+ext;
      document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);
    }catch(err){
      // Fallback for images hosted without CORS support.
      const a=document.createElement('a');a.href=src;a.download=(p.name||'image-produit')+'.png';a.target='_blank';a.rel='noopener';document.body.appendChild(a);a.click();a.remove();
      premiumMessage('Si le téléchargement ne démarre pas, ouvre l’image dans le nouvel onglet puis enregistre-la.');
    }
  };

  window.renderPremiumList=function(){const box=document.getElementById('premiumList');if(!box)return;box.innerHTML=products.map(p=>{const stock=Number(p.stock??0);const badge=p.badge||((stock<=0)?'ÉPUISÉ':p.is_new?'NEW':p.is_popular?'BEST-SELLER':'');return `<div class="admin-card"><img src="${esc(p.image)}" alt="${esc(p.name)}"><div><b>${esc(p.name)}</b><div class="muted">${Number(p.price).toFixed(2)}€ · ${esc(normalizeCategory(p.cat))} · Stock: ${stock}</div><div style="margin-top:6px">${badge?`<span class="v16-badge">${esc(badge)}</span>`:''} ${p.is_featured?'⭐':''}</div></div><div class="admin-actions"><button class="btn" onclick='downloadPremiumImage(${JSON.stringify(p.id)})'>⬇️ Télécharger l’image</button><button class="btn" onclick='v16DuplicateProduct(${JSON.stringify(p.id)})'>📑 Dupliquer</button><button class="btn" onclick='editPremiumProduct(${JSON.stringify(p.id)})'>✏️ Modifier</button><button class="btn" onclick='deletePremiumProduct(${JSON.stringify(p.id)})'>🗑️ Supprimer</button></div></div>`}).join('')||'<div class="v16-muted">Aucun produit.</div>'};

  // Premium tools: duplicate products, export CSV, full local backup/restore.
  window.v16DuplicateProduct=async function(id){if(!can())return alert('Accès Premium requis.');const p=products.find(x=>String(x.id)===String(id));if(!p)return alert('Produit introuvable.');try{const copy={name:(p.name||'Produit')+' (copie)',price:Number(p.price)||0,category:p.category||p.cat||'',cat:p.category||p.cat||'',description:p.description||((p.details||[]).join('\n')),details:p.details||[],stock:Number(p.stock)||0,sizes:p.sizes||[],badge:p.badge||null,is_featured:false,is_popular:false,is_new:p.is_new!==false,is_published:p.is_published!==false,publish_at:p.publish_at||null,compare_at_price:p.compare_at_price||null,image_url:p.image_url||p.image||'',image:p.image_url||p.image||''};await apiFetch('products.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(copy)});await v12LoadProducts();render();renderPremiumList();notice('📑 Copie publiée sur la boutique.',true)}catch(err){alert('Impossible de dupliquer : '+(err?.message||err))}};
  window.v16ExportCatalogCSV=function(){if(!can())return alert('Accès Premium requis.');const rows=[['id','nom','prix','prix_avant_promo','categorie','stock','tailles','badge','description','image','publie','nouveaute','populaire','a_la_une'],...products.map(p=>[p.id,p.name,p.price,p.compare_at_price??'',normalizeCategory(p.cat),p.stock??0,(p.sizes||sizes(p)||[]).join('|'),p.badge||'',p.description||((p.details||[]).join(' / ')),p.image_url||p.image||'',p.is_published!==false,p.is_new!==false,!!p.is_popular,!!p.is_featured])];const csv='\ufeff'+rows.map(row=>row.map(v=>'"'+String(v??'').replace(/"/g,'""')+'"').join(';')).join('\r\n');const url=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8;'}));const a=document.createElement('a');a.href=url;a.download='loganator-catalogue.csv';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);notice('📊 Catalogue CSV exporté.',true)};
  window.v16ExportBackup=function(){if(!can())return alert('Accès Premium requis.');const backup={format:'LOGANATOR-SHOP-BACKUP',version:1,created_at:new Date().toISOString(),local_store:store,products:products};const url=URL.createObjectURL(new Blob([JSON.stringify(backup,null,2)],{type:'application/json'}));const a=document.createElement('a');a.href=url;a.download='loganator-shop-backup-'+new Date().toISOString().slice(0,10)+'.json';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);notice('💾 Sauvegarde téléchargée.',true)};
  window.v16ImportBackup=function(event){if(!can())return alert('Accès Premium requis.');const file=event.target.files&&event.target.files[0];if(!file)return;const reader=new FileReader();reader.onload=()=>{try{const data=JSON.parse(reader.result);if(data.format!=='LOGANATOR-SHOP-BACKUP'||!data.local_store)throw new Error('Ce fichier ne semble pas être une sauvegarde LOGANATOR valide.');if(!confirm('Restaurer cette sauvegarde locale ? Les données locales actuelles seront remplacées.'))return;store={...data.local_store};store.products=Array.isArray(store.products)?store.products:[];store.settings=store.settings||{};store.promos=Array.isArray(store.promos)?store.promos:[];store.orders=Array.isArray(store.orders)?store.orders:[];store.members=Array.isArray(store.members)?store.members:[];store.events=Array.isArray(store.events)?store.events:[];save(store);if(Array.isArray(data.products))products.splice(0,products.length,...data.products);applyMeta();render();renderPremiumList();v16LoadSettings();v16LoadPromos();v16LoadOrders();v16LoadStats();v16LoadTools();loadPremiumMembers();notice('✅ Sauvegarde restaurée dans ce navigateur.',true)}catch(err){alert('Impossible de restaurer : '+err.message)}finally{event.target.value=''}};reader.readAsText(file)};

  // Site editor: local draft + local published copy.
  window.v16LoadSettings=function(){if(!can())return;const x=store.settings.draft||{};document.getElementById('v16HeroTitle').value=x.heroTitle||'PORTE TON LOGANATOR.';document.getElementById('v16HeroText').value=x.heroText||'La boutique de la chaîne, avec les vraies photos de tes créations.';document.getElementById('v16HeroButton').value=x.heroButton||'VOIR LA COLLECTION';document.getElementById('v16BannerText').value=x.bannerText||'🚚 LIVRAISON OFFERTE À PARTIR DE 80€';document.getElementById('v16DropText').value=x.dropText||'';['New','Popular','Promos','Drop'].forEach(k=>{const el=document.getElementById('v16Show'+k);if(el)el.checked=x['show'+k]!==false});v16PreviewSettings()};
  window.v16SaveDraft=function(){if(!can())return;store.settings.draft=v16SettingsObj();save(store);notice('💾 Brouillon enregistré dans ce navigateur.',true)};
  window.v16PublishDraft=function(){if(!can())return;store.settings.published=v16SettingsObj();store.settings.draft=store.settings.published;save(store);notice('🚀 Publication locale terminée. Aucun SQL nécessaire.',true)};

  // Promotions: local only.
  window.v16LoadPromos=function(){const box=document.getElementById('v16PromoList');if(!box)return;box.innerHTML=store.promos.map(p=>`<div class="v16-line"><span><b>${esc(p.code)}</b> · ${p.type==='percent'?p.value+'%':Number(p.value).toFixed(2)+'€'} ${p.active?'🟢':'🔴'}</span><button class="btn" onclick='v16TogglePromo(${JSON.stringify(p.id)},${JSON.stringify(!p.active)})'>${p.active?'Désactiver':'Activer'}</button></div>`).join('')||'<div class="v16-muted">Aucun code.</div>'};
  window.v16CreatePromo=function(){if(!can())return;const d={id:uid(),code:document.getElementById('v16PromoCode').value.trim().toUpperCase(),type:document.getElementById('v16PromoType').value,value:Number(document.getElementById('v16PromoValue').value)||0,min_order:Number(document.getElementById('v16PromoMin').value)||0,max_uses:Number(document.getElementById('v16PromoMax').value)||null,starts_at:document.getElementById('v16PromoStart').value||null,ends_at:document.getElementById('v16PromoEnd').value||null,active:true};if(!d.code)return alert('Entre un code.');store.promos.unshift(d);save(store);['v16PromoCode','v16PromoValue','v16PromoMin','v16PromoMax','v16PromoStart','v16PromoEnd'].forEach(id=>document.getElementById(id).value='');v16LoadPromos();notice('🏷️ Promotion créée localement.',true)};
  window.v16TogglePromo=function(id,active){const p=store.promos.find(x=>String(x.id)===String(id));if(p){p.active=active;save(store);v16LoadPromos()}};

  // Orders: local workspace, ready for future checkout integration.
  window.v16LoadOrders=function(){const box=document.getElementById('v16OrderRows');if(!box)return;box.innerHTML=store.orders.map(o=>`<tr><td>${new Date(o.created_at||Date.now()).toLocaleString('fr-FR')}</td><td>${esc(o.email||'—')}</td><td>${Number(o.total||0).toFixed(2)}€</td><td><select onchange='v16SetOrderStatus(${JSON.stringify(o.id)},this.value)'><option value="pending" ${o.status==='pending'?'selected':''}>En attente</option><option value="preparation" ${o.status==='preparation'?'selected':''}>Préparation</option><option value="shipped" ${o.status==='shipped'?'selected':''}>Expédiée</option><option value="delivered" ${o.status==='delivered'?'selected':''}>Livrée</option><option value="cancelled" ${o.status==='cancelled'?'selected':''}>Annulée</option></select></td><td>${esc(o.payment_status||'—')}</td><td><button class="btn" onclick='v16ViewOrder(${JSON.stringify(o.id)})'>Voir</button></td></tr>`).join('')||'<tr><td colspan="6">Aucune commande locale.</td></tr>'};
  window.v16SetOrderStatus=function(id,status){const o=store.orders.find(x=>String(x.id)===String(id));if(o){o.status=status;save(store);notice('✅ Statut local mis à jour.',true)}};
  window.v16ViewOrder=function(id){const o=store.orders.find(x=>String(x.id)===String(id));alert(o?((o.items||[]).map(x=>`${x.product_name} × ${x.quantity} — ${x.size||'sans taille'}`).join('\n')||'Commande vide'):'Commande introuvable')};

  // Premium members: use the four built-in Premium accounts + local additions. No DB write.
  window.loadPremiumMembers=function(){const box=document.getElementById('premiumMembersList');if(!box)return;const all=[...new Set([...LOGANATOR_PREMIUM,...store.members])];box.innerHTML=all.map(email=>`<div class="admin-card" style="grid-template-columns:1fr auto"><div><b>${esc(email)}</b><div class="muted">Membre PREMIUM ${LOGANATOR_PREMIUM.includes(email)?'principal':'local'}</div></div>${LOGANATOR_PREMIUM.includes(email)?'':'<button class="btn" onclick=\'removePremiumMember('+JSON.stringify(email)+')\'>🗑️ Retirer</button>'}</div>`).join('')};
  window.addPremiumMember=function(){const e=document.getElementById('premiumMemberEmail'),email=e.value.trim().toLowerCase();if(!email.includes('@'))return alert('E-mail invalide.');if(!store.members.includes(email))store.members.push(email);save(store);e.value='';loadPremiumMembers();notice('👑 Membre ajouté localement.',true)};
  window.removePremiumMember=function(email){if(!confirm('Retirer '+email+' de la liste locale Premium ?'))return;store.members=store.members.filter(x=>x!==email);save(store);loadPremiumMembers()};
  window.openPremiumMembers=async function(){if(!can())return;document.getElementById('premiumMembersModal').classList.add('open');loadPremiumMembers()};
  window.closePremiumMembers=function(){document.getElementById('premiumMembersModal').classList.remove('open')};

  window.v16LoadTools=function(){if(!can())return;applyMeta();const low=products.filter(p=>Number(p.stock??0)<=3);const missing=products.filter(p=>!p.description||!p.image);document.getElementById('v16SmartTools').innerHTML=`<div class="v16-line">🔥 Produits à mettre en avant <b>${products.filter(p=>p.is_popular||p.is_featured).length}</b></div><div class="v16-line">⚠️ Stocks faibles <b>${low.length}</b></div><div class="v16-line">📝 Fiches à compléter <b>${missing.length}</b></div><div class="v16-line">🆕 Nouveautés <b>${products.filter(p=>p.is_new!==false).length}</b></div><div class="v16-line">💾 Données Premium locales <b>${store.products.length}</b></div>`;document.getElementById('v16Quality').innerHTML=products.map(p=>{const issues=[];if(!p.name)issues.push('nom');if(!p.description)issues.push('description');if(!p.image)issues.push('image');if(Number(p.price)<=0)issues.push('prix');return `<div class="v16-line"><span>${esc(p.name)}</span><span class="${issues.length?'v16-danger':'v16-good'}">${issues.length?'⚠️ '+issues.join(', '):'✅ OK'}</span></div>`}).join('')};

  // Stats: calculate from local events instead of requiring analytics_events.
  window.v16LoadStats=function(){if(!can())return;const ev=store.events||[];const views=ev.filter(x=>x.type==='view').length;const fav=ev.filter(x=>x.type==='favorite').length;const cart=ev.filter(x=>x.type==='cart').length;const visits=ev.filter(x=>x.type==='visit').length;const orders=store.orders.length;const map={};ev.filter(x=>x.type==='view').forEach(x=>map[x.product_id]=(map[x.product_id]||0)+1);const popular=Object.entries(map).sort((a,b)=>b[1]-a[1])[0];const set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v};set('v16Visits',visits);set('v16Views',views);set('v16Favorites',fav);set('v16CartAdds',cart);set('v16Orders',orders);set('v16Popular',popular?products.find(p=>String(p.id)===String(popular[0]))?.name||'—':'—');};

  // Apply local metadata after Supabase catalog loading.
  const oldLoad=window.v12LoadProducts;
  window.v12LoadProducts=async function(){if(oldLoad)await oldLoad();renderPremiumList();};
  const oldOpen=window.openPremium;
  window.openPremium=async function(){if(!can())return alert('Accès réservé aux membres PREMIUM 👑');await v12LoadProducts();render();renderPremiumList();document.getElementById('premiumModal').classList.add('open');v16LoadStats();v16LoadTools();loadPremiumMembers()};

  // Small visual notice explaining the mode.
  const msg=document.getElementById('premiumMsg');
  if(msg){msg.textContent='⚡ MODE AUTONOME : les produits sont enregistrés sur l’hébergement dans un fichier JSON et deviennent publics immédiatement. Aucun Supabase, aucune base SQL.';msg.className='admin-msg ok'}
})();
</script>
<script>
/* V21 FINAL - recherche robuste */
(function(){
  const input=document.getElementById('v15Search');
  if(!input || !Array.isArray(window.products || products)) return;
  function smart(q,p){
    q=(q||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
    const name=(p.name||'').toLowerCase();
    const cat=(p.cat||'').toLowerCase();
    const text=[name,cat,p.description||'',...(p.details||[])].join(' ').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
    if(!q) return true;
    const price=Number(p.price)||0;
    const priceMatch=q.match(/(?:moins de|sous|inferieur a|moins que)\s*(\d+(?:[.,]\d+)?)/);
    if(priceMatch && price >= Number(priceMatch[1].replace(',','.'))) return false;
    if(/(?:nouveaute|nouveautes|new)/.test(q) && p.is_new===false) return false;
    if(/(?:populaire|best.?seller)/.test(q) && !p.is_popular) return false;
    const terms=q.replace(/(?:moins de|sous|inferieur a|moins que)\s*\d+(?:[.,]\d+)?/g,'').trim().split(/\s+/).filter(Boolean);
    return terms.every(t=>{
      if(['vetement','vetements'].includes(t)) return /hoodie|doudoune|baggy/.test(text);
      if(t==='hoodie') return /hoodie|haut|sweat/.test(text);
      if(t==='doudoune') return /doudoune|puffer/.test(text);
      if(t==='baggy') return /baggy|pantalon|bas/.test(text);
      if(['chaussure','chaussures','shoe','shoes'].includes(t)) return /chaussure|shoe/.test(text);
      if(t==='blanc' || t==='blanche') return /blanc|white/.test(text);
      if(t==='noir' || t==='noire') return /noir|black/.test(text);
      return text.includes(t);
    });
  }
  window.render=function(){
    const q=input.value||'';
    const list=products.filter(p=>{
      const category=normalizeCategory(p.cat);
      return (typeof selectedFilter==='undefined'||selectedFilter==='Tous'||category===selectedFilter) && smart(q,p);
    });
    const count=document.getElementById('v15ResultCount');
    if(count) count.textContent=`${list.length} produit${list.length>1?'s':''} trouvé${list.length>1?'s':''}`;
    const grid=document.getElementById('grid');
    if(!grid)return;
    grid.innerHTML=list.length?list.map(p=>{const price=Number(p.price)||0;return `<article class="card" onclick='openProduct(${JSON.stringify(p.id)})'><div class="photo"><img src="${p.image}" alt="${p.name}" loading="lazy"><button class="v15-fav" onclick='event.stopPropagation();toggleFav(${JSON.stringify(p.id)})'>${fav(p.id)?'♥':'♡'}</button></div><div class="info"><span class="tag">LOGANATOR</span><h3>${p.name}</h3><div class="row"><span class="price">${price.toFixed(2)}€</span><span class="muted">${normalizeCategory(p.cat)}</span></div><button class="buy" onclick='event.stopPropagation();add(${JSON.stringify(p.id)})'>AJOUTER AU PANIER</button></div></article>`}).join(''):'<div class="v15-empty">Aucun produit ne correspond à ta recherche ou à cette catégorie.</div>';
    const gallery=document.getElementById('gallery');
    if(gallery)gallery.innerHTML=list.map(p=>`<img src="${p.image}" alt="${p.name}" loading="lazy" onclick='openProduct(${JSON.stringify(p.id)})' style="cursor:pointer">`).join('');
  };
  input.addEventListener('input',window.render);
  document.querySelectorAll('#v15Filters button').forEach(b=>b.addEventListener('click',()=>{selectedFilter=b.dataset.filter;document.querySelectorAll('#v15Filters button').forEach(x=>x.classList.toggle('active',x===b));window.render();}));
  setTimeout(window.render,50);
})();
</script>
<script id="v22-1-js">
/* V22.1 — conserve l'interface V21 et répare uniquement l'accès PREMIUM + les boutons ajoutés. */
(function(){
  const ALLOW=['gauducheaulogan@gmail.com','leloganator@gmail.com','alexisprouillac@gmail.com','theteamsloganator@gmail.com'];
  const LOGANATRICE='https://leloganator.github.io/Loganatrice-shop/';
  window.V22_PREMIUM=false;
  async function check(){
    const email=(window.LOGANATOR_USER?.email||'').toLowerCase();
    if(!email){window.V22_PREMIUM=false;return false;}
    if(ALLOW.includes(email)){window.V22_PREMIUM=true;return true;}
    try{
      const ok=typeof window.v15CheckPremium==='function' ? await window.v15CheckPremium(email) : false;
      window.V22_PREMIUM=!!ok; return !!ok;
    }catch(e){window.V22_PREMIUM=false;return false}
  }
  window.v22IsPremium=check;
  window.v22OpenLoganatrice=function(){
    check().then(ok=>{if(!ok)return alert('Accès réservé aux membres PREMIUM 👑');window.open(LOGANATRICE,'_blank','noopener,noreferrer')});
  };
  window.v22StayLoganator=function(){
    const modal=document.getElementById('premiumModal'); if(modal) modal.scrollTo({top:0,behavior:'smooth'});
  };
  async function refresh(){
    const b=document.getElementById('accountBtn'); if(!b)return;
    const ok=await check();
    if(ok){b.textContent='👑 PREMIUM';b.dataset.premium='1';}
    else if(window.LOGANATOR_USER){b.textContent='👤 '+((window.LOGANATOR_USER.email||'compte').split('@')[0]);b.dataset.premium='0';}
    else{b.textContent='👤 COMPTE';b.dataset.premium='0';}
  }
  window.v22RefreshPremiumButton=refresh;

  const oldAccount=window.openAccount;
  window.openAccount=async function(){
    if(await check()) return window.openPremium();
    return oldAccount?oldAccount():null;
  };

  const oldOpen=window.openPremium;
  window.openPremium=async function(){
    if(!(await check())){alert('Accès réservé aux membres PREMIUM 👑');return;}
    if(typeof oldOpen==='function') await oldOpen();
    const modal=document.getElementById('premiumModal'); if(modal)modal.classList.add('open');
    const panel=document.getElementById('v22StorePanel'); if(panel)panel.style.display='block';
    if(typeof window.v16LoadStats==='function')window.v16LoadStats();
    if(typeof window.v16LoadSettings==='function')window.v16LoadSettings();
    if(typeof window.v16LoadPromos==='function')window.v16LoadPromos();
    if(typeof window.v16LoadOrders==='function')window.v16LoadOrders();
    if(typeof window.v16LoadTools==='function')window.v16LoadTools();
    if(typeof window.loadPremiumMembers==='function')window.loadPremiumMembers();
  };

  // Authentication is handled by the PHP session in api/auth.php.

  const oldLogout=window.premiumLogout;
  window.premiumLogout=async function(){if(oldLogout)await oldLogout();window.V22_PREMIUM=false;await refresh()};

  if(window.LOGANATOR_SB?.auth){window.LOGANATOR_SB.auth.onAuthStateChange(()=>setTimeout(refresh,120));}
  const tries=[200,700,1500,3000]; tries.forEach(ms=>setTimeout(refresh,ms));
})();
</script>

<style id="loganator-v25-style">
:root{--v25-blue:#1687ff;--v25-violet:#7c3cff;--v25-pink:#ff3fa4;--v25-bg:#05060b;--v25-panel:rgba(12,15,27,.82);--v25-line:rgba(255,255,255,.10)}
body.v25{background:radial-gradient(circle at 10% 10%,rgba(22,135,255,.12),transparent 28%),radial-gradient(circle at 90% 20%,rgba(255,63,164,.10),transparent 28%),#05060b}
.v25-shell{position:fixed;inset:0;background:rgba(2,3,8,.92);backdrop-filter:blur(24px);z-index:99999;display:none;color:#fff;font-family:Arial,Helvetica,sans-serif}.v25-shell.open{display:flex}
.v25-side{width:270px;padding:22px 16px;border-right:1px solid var(--v25-line);background:rgba(8,10,18,.9);overflow:auto}.v25-brand{font-weight:1000;letter-spacing:3px;font-size:22px;margin:4px 8px 26px}.v25-brand span{background:linear-gradient(90deg,var(--v25-blue),var(--v25-violet),var(--v25-pink));-webkit-background-clip:text;color:transparent}.v25-group{font-size:10px;letter-spacing:1.5px;color:#8e95aa;font-weight:900;margin:20px 9px 8px}.v25-nav{width:100%;border:1px solid transparent;background:transparent;color:#d9dce7;text-align:left;padding:11px 12px;border-radius:12px;cursor:pointer;margin:2px 0;font-weight:800}.v25-nav:hover,.v25-nav.active{background:linear-gradient(90deg,rgba(22,135,255,.18),rgba(124,60,255,.14),rgba(255,63,164,.10));border-color:rgba(255,255,255,.09);color:#fff}.v25-main{flex:1;min-width:0;display:flex;flex-direction:column}.v25-top{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--v25-line);background:rgba(7,9,16,.72)}.v25-top h2{margin:0;font-size:24px}.v25-close{border:1px solid var(--v25-line);background:#10131f;color:#fff;border-radius:12px;padding:9px 13px;cursor:pointer}.v25-content{padding:24px;overflow:auto}.v25-page{display:none}.v25-page.active{display:block}.v25-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.v25-card{background:var(--v25-panel);border:1px solid var(--v25-line);border-radius:18px;padding:17px}.v25-kpi{font-size:13px;color:#9ca4b7}.v25-kpi b{display:block;font-size:29px;color:#fff;margin-top:7px}.v25-card h3{margin:0 0 12px}.v25-actions{display:flex;flex-wrap:wrap;gap:9px}.v25-btn{border:1px solid var(--v25-line);background:#111522;color:#fff;border-radius:11px;padding:10px 13px;cursor:pointer;font-weight:800}.v25-btn.primary{background:linear-gradient(135deg,var(--v25-blue),var(--v25-violet),var(--v25-pink));border:0}.v25-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.v25-form label{display:grid;gap:6px;color:#b5bbca;font-size:13px}.v25-form input,.v25-form textarea,.v25-form select{width:100%;background:#090c15;color:#fff;border:1px solid var(--v25-line);border-radius:10px;padding:11px;outline:none}.v25-form textarea{min-height:100px;resize:vertical}.v25-full{grid-column:1/-1}.v25-list{display:grid;gap:8px}.v25-row{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px;border:1px solid var(--v25-line);border-radius:12px;background:rgba(255,255,255,.025)}.v25-muted{color:#9ca4b7;font-size:13px}.v25-pill{font-size:11px;border-radius:999px;padding:5px 9px;background:rgba(22,135,255,.14);border:1px solid rgba(22,135,255,.25)}.v25-status{margin-top:10px;color:#8fe0b1;font-size:13px}.v25-mobile-menu{display:none}
@media(max-width:900px){.v25-shell.open{display:block}.v25-side{display:none}.v25-main{height:100%}.v25-mobile-menu{display:flex;gap:8px;overflow:auto;padding:8px 12px;border-bottom:1px solid var(--v25-line)}.v25-mobile-menu .v25-nav{white-space:nowrap;width:auto}.v25-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.v25-form{grid-template-columns:1fr}.v25-content{padding:15px}}
@media(max-width:520px){.v25-grid{grid-template-columns:1fr}.v25-top{padding:14px}.v25-content{padding:12px}}
</style>
<script>
document.body.classList.add('v25');
</script>
<div class="v25-shell" id="v25Shell" aria-hidden="true">
  <aside class="v25-side">
    <div class="v25-brand">LOGANATOR <span>V25</span></div>
    <div class="v25-group">PILOTAGE</div>
    <button class="v25-nav active" data-v25="dashboard">📊 Dashboard</button>
    <button class="v25-nav" data-v25="products">🛍️ Produits</button>
    <button class="v25-nav" data-v25="media">🖼️ Media Center</button>
    <button class="v25-nav" data-v25="orders">📦 Commandes</button>
    <button class="v25-nav" data-v25="clients">👥 Clients</button>
    <div class="v25-group">BOUTIQUE</div>
    <button class="v25-nav" data-v25="home">🏠 Page d'accueil</button>
    <button class="v25-nav" data-v25="builder">🧩 Builder</button>
    <button class="v25-nav" data-v25="appearance">🎨 Apparence</button>
    <button class="v25-nav" data-v25="promos">🏷️ Promotions</button>
    <button class="v25-nav" data-v25="drops">🔥 Drops</button>
    <button class="v25-nav" data-v25="schedule">⏰ Programmation</button>
    <div class="v25-group">COMMUNAUTÉ</div>
    <button class="v25-nav" data-v25="members">👑 Membres Premium</button>
    <button class="v25-nav" data-v25="notifications">🔔 Notifications</button>
    <button class="v25-nav" data-v25="gamification">🎮 Gamification</button>
    <div class="v25-group">OUTILS</div>
    <button class="v25-nav" data-v25="analytics">📈 Statistiques</button>
    <button class="v25-nav" data-v25="preview">👁️ Aperçu</button>
    <button class="v25-nav" data-v25="backups">💾 Sauvegardes</button>
    <button class="v25-nav" data-v25="diagnostic">🩺 Diagnostic</button>
    <button class="v25-nav" data-v25="lab">🧪 LAB</button>
    <button class="v25-nav" data-v25="settings">⚙️ Paramètres</button>
  </aside>
  <main class="v25-main">
    <div class="v25-top"><div><div class="v25-muted">👑 CONTROL CENTER</div><h2 id="v25Title">Dashboard</h2></div><button class="v25-close" id="v25Close">✕ Fermer</button></div>
    <div class="v25-mobile-menu"> <button class="v25-nav active" data-v25="dashboard">📊</button><button class="v25-nav" data-v25="products">🛍️</button><button class="v25-nav" data-v25="home">🏠</button><button class="v25-nav" data-v25="appearance">🎨</button><button class="v25-nav" data-v25="analytics">📈</button><button class="v25-nav" data-v25="diagnostic">🩺</button></div>
    <div class="v25-content">
      <section class="v25-page active" data-page="dashboard"><div class="v25-grid"><div class="v25-card v25-kpi">Produits<b id="v25Products">—</b></div><div class="v25-card v25-kpi">Visites<b id="v25Visits">—</b></div><div class="v25-card v25-kpi">Favoris<b id="v25Favs">—</b></div><div class="v25-card v25-kpi">Panier<b id="v25Cart">—</b></div></div><div style="height:14px"></div><div class="v25-grid"><div class="v25-card"><h3>⚡ Actions rapides</h3><div class="v25-actions"><button class="v25-btn primary" data-jump="products">➕ Produit</button><button class="v25-btn" data-jump="home">🏠 Accueil</button><button class="v25-btn" data-jump="promos">🏷️ Promo</button><button class="v25-btn" data-jump="drops">🔥 Drop</button></div></div><div class="v25-card"><h3>🏪 Mes boutiques</h3><p class="v25-muted">Contrôle commun pour LOGANATOR et LOGANATRICE.</p><div class="v25-actions"><button class="v25-btn primary" id="v25ShopLoganator">🔵 LOGANATOR</button><button class="v25-btn" id="v25ShopLoganatrice">🩷 LOGANATRICE</button></div></div><div class="v25-card"><h3>🩺 État</h3><div id="v25QuickStatus" class="v25-status">Analyse…</div></div><div class="v25-card"><h3>🔥 Launch Center</h3><p class="v25-muted">Prépare un drop complet avant publication.</p><button class="v25-btn primary" data-jump="drops">Ouvrir</button></div></div></section>
      <section class="v25-page" data-page="products"><div class="v25-card"><h3>🛍️ Gestion catalogue</h3><div class="v25-actions"><button class="v25-btn primary" id="v25NewProduct">➕ Ajouter</button><button class="v25-btn" id="v25Refresh">🔄 Actualiser</button><button class="v25-btn" id="v25Export">📊 Export CSV</button></div><div id="v25ProductList" class="v25-list" style="margin-top:14px"></div></div></section>
      <section class="v25-page" data-page="media"><div class="v25-card"><h3>🖼️ Media Center</h3><p class="v25-muted">Bibliothèque et préparation des visuels produits.</p><div id="v25MediaList" class="v25-list"></div></div></section>
      <section class="v25-page" data-page="orders"><div class="v25-card"><h3>📦 Commandes</h3><div id="v25OrderList" class="v25-list"><div class="v25-muted">Chargement des commandes…</div></div></div></section>
      <section class="v25-page" data-page="clients"><div class="v25-card"><h3>👥 Clients</h3><p class="v25-muted">Gestion des clients selon les données et permissions disponibles.</p><div id="v25ClientList" class="v25-list"></div></div></section>
      <section class="v25-page" data-page="home"><div class="v25-card"><h3>🏠 Éditeur de page d'accueil</h3><div class="v25-form"><label>Titre HERO<input id="v25HeroTitle"></label><label>Bouton principal<input id="v25HeroButton"></label><label class="v25-full">Sous-titre<textarea id="v25HeroText"></textarea></label><label class="v25-full">Bannière<input id="v25Banner"></label><label class="v25-full">Message DROP<textarea id="v25DropMessage"></textarea></label></div><div class="v25-actions" style="margin-top:14px"><button class="v25-btn primary" id="v25SaveHome">💾 Enregistrer</button><button class="v25-btn" id="v25PreviewHome">👁️ Aperçu</button></div><div id="v25HomeStatus" class="v25-status"></div></div></section>
      <section class="v25-page" data-page="builder"><div class="v25-card"><h3>🧩 Builder de sections</h3><p class="v25-muted">Prépare l'ordre des sections visibles sur l'accueil.</p><div id="v25BuilderList" class="v25-list"></div><div class="v25-actions" style="margin-top:12px"><button class="v25-btn primary" id="v25AddSection">➕ Ajouter une section</button></div></div></section>
      <section class="v25-page" data-page="appearance"><div class="v25-card"><h3>🎨 Apparence V25</h3><div class="v25-form"><label>Thème<select id="v25Theme"><option>Dark Neon</option><option>Blue Space</option><option>Cyber Violet</option><option>Blue / Violet / Pink</option></select></label><label>Accent<select id="v25Accent"><option>Bleu</option><option>Violet</option><option>Rose</option></select></label><label>Nom de boutique<input id="v25StoreName" value="LOGANATOR"></label><label>Favicon<input id="v25Favicon" value="favicon.png"></label></div><div class="v25-actions" style="margin-top:14px"><button class="v25-btn primary" id="v25SaveAppearance">✨ Appliquer</button></div><div id="v25AppearanceStatus" class="v25-status"></div></div></section>
      <section class="v25-page" data-page="promos"><div class="v25-card"><h3>🏷️ Promotions</h3><div class="v25-form"><label>Code<input id="v25PromoCode" placeholder="LOGAN20"></label><label>Valeur<input id="v25PromoValue" type="number" min="0" placeholder="20"></label><label>Type<select id="v25PromoType"><option value="percent">Pourcentage</option><option value="fixed">Montant fixe</option></select></label><label>Actif<select id="v25PromoActive"><option value="true">Oui</option><option value="false">Non</option></select></label></div><div class="v25-actions" style="margin-top:12px"><button class="v25-btn primary" id="v25AddPromo">➕ Créer</button></div><div id="v25PromoList" class="v25-list" style="margin-top:12px"></div></div></section>
      <section class="v25-page" data-page="drops"><div class="v25-card"><h3>🔥 Drop Manager</h3><div class="v25-form"><label>Nom du Drop<input id="v25DropName" placeholder="DROP AUTOMNE"></label><label>Date<input id="v25DropDate" type="datetime-local"></label><label class="v25-full">Description<textarea id="v25DropDesc"></textarea></label></div><div class="v25-actions" style="margin-top:12px"><button class="v25-btn primary" id="v25SaveDrop">🚀 Préparer le Drop</button></div><div id="v25DropStatus" class="v25-status"></div></div></section>
      <section class="v25-page" data-page="schedule"><div class="v25-card"><h3>⏰ Programmation</h3><p class="v25-muted">Prépare des publications et rappelle-toi que les actions réellement programmées doivent rester reliées à un système d'automatisation autorisé.</p><div id="v25ScheduleList" class="v25-list"></div></div></section>
      <section class="v25-page" data-page="members"><div class="v25-card"><h3>👑 Membres Premium</h3><div class="v25-form"><label class="v25-full">E-mail<input id="v25MemberEmail" type="email" placeholder="email@exemple.com"></label></div><div class="v25-actions" style="margin-top:12px"><button class="v25-btn primary" id="v25AddMember">➕ Ajouter Premium</button></div><div id="v25MemberList" class="v25-list" style="margin-top:12px"></div></div></section>
      <section class="v25-page" data-page="notifications"><div class="v25-card"><h3>🔔 Notifications internes</h3><div class="v25-form"><label>Titre<input id="v25NotifTitle"></label><label>Icône<input id="v25NotifIcon" value="🔔"></label><label class="v25-full">Message<textarea id="v25NotifText"></textarea></label></div><button class="v25-btn primary" id="v25AddNotif" style="margin-top:12px">➕ Publier dans le site</button><div id="v25NotifList" class="v25-list" style="margin-top:12px"></div></div></section>
      <section class="v25-page" data-page="gamification"><div class="v25-card"><h3>🎮 Gamification</h3><div class="v25-form"><label>Niveau client<input id="v25Level" type="number" min="1" value="1"></label><label>Points<input id="v25Points" type="number" min="0" value="0"></label><label class="v25-full">Badges<input id="v25Badges" placeholder="Premier achat, Collectionneur"></label></div><button class="v25-btn primary" id="v25SaveGame" style="margin-top:12px">💾 Enregistrer</button></div></section>
      <section class="v25-page" data-page="analytics"><div class="v25-grid"><div class="v25-card v25-kpi">Produits<b id="v25AProducts">—</b></div><div class="v25-card v25-kpi">Visites<b id="v25AVisits">—</b></div><div class="v25-card v25-kpi">Favoris<b id="v25AFavs">—</b></div><div class="v25-card v25-kpi">Panier<b id="v25ACart">—</b></div></div><div class="v25-card" style="margin-top:14px"><h3>📈 Activité</h3><div id="v25AnalyticsText" class="v25-list"></div></div></section>
      <section class="v25-page" data-page="preview"><div class="v25-card"><h3>👁️ Aperçu</h3><p class="v25-muted">Le site public reste la référence finale. Cette zone prépare les changements avant publication.</p><div class="v25-actions"><button class="v25-btn primary" id="v25OpenPublic">🌐 Ouvrir la boutique</button><button class="v25-btn" data-jump="home">✏️ Modifier l'accueil</button></div></div></section>
      <section class="v25-page" data-page="backups"><div class="v25-card"><h3>💾 Sauvegardes</h3><div class="v25-actions"><button class="v25-btn primary" id="v25Backup">⬇️ Exporter configuration</button><button class="v25-btn" id="v25Restore">📥 Restaurer</button><input id="v25RestoreFile" type="file" accept="application/json" hidden></div><div id="v25BackupStatus" class="v25-status"></div></div></section>
      <section class="v25-page" data-page="diagnostic"><div class="v25-grid"><div class="v25-card v25-kpi">Serveur PHP<b id="dSupabase">…</b></div><div class="v25-card v25-kpi">Session<b id="dSession">…</b></div><div class="v25-card v25-kpi">Premium<b id="dPremium">…</b></div><div class="v25-card v25-kpi">Produits<b id="dProducts">…</b></div></div><div class="v25-card" style="margin-top:14px"><h3>🩺 Diagnostic complet</h3><div id="v25Diag" class="v25-list"></div><button class="v25-btn primary" id="v25RunDiag" style="margin-top:12px">🔄 Relancer l'analyse</button></div></section>
      <section class="v25-page" data-page="lab"><div class="v25-card"><h3>🧪 LOGANATOR LAB</h3><p class="v25-muted">Zone expérimentale V25 : fonctions nouvelles isolées pour éviter de casser la boutique principale.</p><div class="v25-actions"><button class="v25-btn" data-jump="diagnostic">🩺 Diagnostic</button><button class="v25-btn" data-jump="preview">👁️ Preview</button><button class="v25-btn" data-jump="builder">🧩 Builder</button></div></div></section>
      <section class="v25-page" data-page="settings"><div class="v25-card"><h3>⚙️ Paramètres</h3><div class="v25-form"><label>Nom boutique<input id="v25SettingName" value="LOGANATOR"></label><label>Devise<select id="v25Currency"><option>EUR (€)</option></select></label><label>Livraison offerte à partir de<input id="v25FreeShip" type="number" value="80"></label><label>Mode maintenance<select id="v25Maintenance"><option value="false">Désactivé</option><option value="true">Activé</option></select></label></div><button class="v25-btn primary" id="v25SaveSettings" style="margin-top:12px">💾 Enregistrer</button><div id="v25SettingsStatus" class="v25-status"></div></div></section>
    </div>
  </main>
</div>
<script>
(function(){
  const $=id=>document.getElementById(id);
  const shell=$('v25Shell');
  let v25Premium=false;
  const localKey='loganator_v25_config';
  const getCfg=()=>{try{return JSON.parse(localStorage.getItem(localKey)||'{}')}catch(e){return {}}};
  const saveCfg=x=>localStorage.setItem(localKey,JSON.stringify(x));
  function cfg(){return getCfg()}
  function setPage(name){document.querySelectorAll('.v25-page').forEach(x=>x.classList.toggle('active',x.dataset.page===name));document.querySelectorAll('.v25-nav').forEach(x=>x.classList.toggle('active',x.dataset.v25===name));const b=document.querySelector('.v25-nav[data-v25="'+name+'"]');$('v25Title').textContent=b?b.textContent.replace(/^\S+\s*/,''):name; if(name==='products')loadProducts(); if(name==='members')loadMembers(); if(name==='promos')loadPromos(); if(name==='diagnostic')runDiag(); if(name==='orders')loadOrders(); if(name==='media')loadMedia();}
  document.querySelectorAll('[data-v25]').forEach(b=>b.addEventListener('click',()=>setPage(b.dataset.v25)));
  document.querySelectorAll('[data-jump]').forEach(b=>b.addEventListener('click',()=>setPage(b.dataset.jump)));
  $('v25Close').addEventListener('click',()=>{shell.classList.remove('open');shell.setAttribute('aria-hidden','true')});
  async function premiumCheck(){ return !!(window.LOGANATOR_USER && (typeof window.isLoganatorPremium==='function' ? window.isLoganatorPremium() : true)); }
  async function openV25(){
    v25Premium=await premiumCheck();
    if(!v25Premium){alert('👑 Accès réservé aux membres PREMIUM.');return}
    shell.classList.add('open');shell.setAttribute('aria-hidden','false');setPage('dashboard');refreshDashboard();loadHome();runDiag();
  }
  window.openPremium=window.openV25;
  window.v25Open=openV25;
  async function refreshDashboard(){const ps=Array.isArray(window.products)?window.products:[];$('v25Products').textContent=ps.length;$('v25Visits').textContent=(window.store&&window.store.events)?window.store.events.filter(x=>x.type==='visit').length:'—';$('v25Favs').textContent=(window.store&&window.store.events)?window.store.events.filter(x=>x.type==='favorite').length:'—';$('v25Cart').textContent=(window.store&&window.store.events)?window.store.events.filter(x=>x.type==='cart').length:'—';$('v25AProducts').textContent=ps.length;$('v25AVisits').textContent=$('v25Visits').textContent;$('v25AFavs').textContent=$('v25Favs').textContent;$('v25ACart').textContent=$('v25Cart').textContent;$('v25QuickStatus').textContent='🟢 Control Center actif';}
  function loadHome(){const c=cfg();$('v25HeroTitle').value=c.heroTitle||'PORTE TON LOGANATOR.';$('v25HeroButton').value=c.heroButton||'VOIR LA COLLECTION';$('v25HeroText').value=c.heroText||'La boutique LOGANATOR nouvelle génération.';$('v25Banner').value=c.banner||'🚚 LIVRAISON OFFERTE À PARTIR DE 80€';$('v25DropMessage').value=c.dropMessage||'';}
  $('v25SaveHome').addEventListener('click',()=>{const c=cfg();Object.assign(c,{heroTitle:$('v25HeroTitle').value,heroButton:$('v25HeroButton').value,heroText:$('v25HeroText').value,banner:$('v25Banner').value,dropMessage:$('v25DropMessage').value});saveCfg(c);$('v25HomeStatus').textContent='✅ Brouillon V25 enregistré.'});
  $('v25PreviewHome').addEventListener('click',()=>alert('👁️ Aperçu préparé. Les changements V25 sont enregistrés comme configuration de contrôle.'));
  function loadProducts(){const ps=Array.isArray(window.products)?window.products:[];$('v25ProductList').innerHTML=ps.map(p=>`<div class="v25-row"><span><b>${esc(p.name)}</b><br><span class="v25-muted">${Number(p.price||0).toFixed(2)}€ · ${esc(p.cat||'')}</span></span><span class="v25-actions"><button class="v25-btn" onclick="editPremiumProduct(${JSON.stringify(p.id)})">✏️ Modifier</button><button class="v25-btn" onclick="newPremiumProduct()">➕</button></span></div>`).join('')||'<div class="v25-muted">Aucun produit.</div>';}
  function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}
  $('v25NewProduct').addEventListener('click',()=>window.newPremiumProduct&&window.newPremiumProduct());$('v25Refresh').addEventListener('click',async()=>{if(window.v12LoadProducts)await window.v12LoadProducts();loadProducts();refreshDashboard()});$('v25Export').addEventListener('click',()=>window.v16ExportCatalogCSV&&window.v16ExportCatalogCSV());
  function loadMedia(){const ps=Array.isArray(window.products)?window.products:[];$('v25MediaList').innerHTML=ps.map(p=>`<div class="v25-row"><span><b>${esc(p.name)}</b><br><span class="v25-muted">${esc(p.image||'Aucune image')}</span></span><span class="v25-pill">${p.image?'IMAGE':'À COMPLÉTER'}</span></div>`).join('')||'<div class="v25-muted">Aucun média.</div>';}
  async function loadOrders(){try{if(!window.LOGANATOR_SB){$('v25OrderList').innerHTML='<div class="v25-muted">Supabase indisponible.</div>';return}const {data,error}=await window.LOGANATOR_SB.from('orders').select('*').order('created_at',{ascending:false}).limit(50);if(error)throw error;$('v25OrderList').innerHTML=(data||[]).map(o=>`<div class="v25-row"><span><b>#${esc(o.id||'')}</b><br><span class="v25-muted">${esc(o.status||'reçue')}</span></span><span>${o.total!=null?Number(o.total).toFixed(2)+'€':''}</span></div>`).join('')||'<div class="v25-muted">Aucune commande.</div>'}catch(e){$('v25OrderList').innerHTML='<div class="v25-muted">Commandes indisponibles avec le schéma actuel.</div>'}}
  async function loadMembers(){try{const {data,error}=await window.LOGANATOR_SB.from('premium_members').select('*').order('created_at',{ascending:false});if(error)throw error;$('v25MemberList').innerHTML=(data||[]).map(m=>`<div class="v25-row"><span>${esc(m.email)}</span><button class="v25-btn" onclick="v25RemoveMember(${JSON.stringify(m.email)})">🗑️ Retirer</button></div>`).join('')||'<div class="v25-muted">Aucun membre.</div>'}catch(e){$('v25MemberList').innerHTML='<div class="v25-muted">Impossible de charger les membres avec les permissions actuelles.</div>'}}
  window.v25RemoveMember=async email=>{if(!confirm('Retirer '+email+' de Premium ?'))return;const {error}=await window.LOGANATOR_SB.from('premium_members').delete().eq('email',email);if(error)alert(error.message);else loadMembers()};
  $('v25AddMember').addEventListener('click',async()=>{const email=$('v25MemberEmail').value.trim().toLowerCase();if(!email.includes('@'))return alert('E-mail invalide.');const {error}=await window.LOGANATOR_SB.from('premium_members').insert({email});if(error)alert(error.message);else{$('v25MemberEmail').value='';loadMembers()}});
  function loadPromos(){const c=cfg();const arr=c.promos||[];$('v25PromoList').innerHTML=arr.map((p,i)=>`<div class="v25-row"><span><b>${esc(p.code)}</b> · ${p.value}${p.type==='percent'?'%':'€'}</span><button class="v25-btn" onclick="v25DeletePromo(${i})">🗑️</button></div>`).join('')||'<div class="v25-muted">Aucune promotion V25.</div>'}
  window.v25DeletePromo=i=>{const c=cfg();c.promos=c.promos||[];c.promos.splice(i,1);saveCfg(c);loadPromos()};$('v25AddPromo').addEventListener('click',()=>{const c=cfg();c.promos=c.promos||[];c.promos.push({code:$('v25PromoCode').value.trim().toUpperCase(),value:Number($('v25PromoValue').value)||0,type:$('v25PromoType').value,active:$('v25PromoActive').value==='true'});saveCfg(c);loadPromos();$('v25PromoCode').value='';});
  $('v25SaveDrop').addEventListener('click',()=>{const c=cfg();c.drop={name:$('v25DropName').value,date:$('v25DropDate').value,desc:$('v25DropDesc').value};saveCfg(c);$('v25DropStatus').textContent='🚀 Drop V25 préparé.'});
  $('v25AddSection').addEventListener('click',()=>{const c=cfg();c.sections=c.sections||['Hero','Nouveautés','Populaires','Premium','Promotions','Drop'];const x=prompt('Nom de la nouvelle section ?');if(!x)return;c.sections.push(x);saveCfg(c);renderSections()});
  function renderSections(){const c=cfg();const s=c.sections||['Hero','Nouveautés','Populaires','Premium','Promotions','Drop'];$('v25BuilderList').innerHTML=s.map((x,i)=>`<div class="v25-row"><span>${i+1}. ${esc(x)}</span><button class="v25-btn" onclick="v25RemoveSection(${i})">🗑️</button></div>`).join('')};window.v25RemoveSection=i=>{const c=cfg();c.sections=c.sections||[];c.sections.splice(i,1);saveCfg(c);renderSections()};renderSections();
  $('v25SaveAppearance').addEventListener('click',()=>{const c=cfg();Object.assign(c,{theme:$('v25Theme').value,accent:$('v25Accent').value,storeName:$('v25StoreName').value,favicon:$('v25Favicon').value});saveCfg(c);$('v25AppearanceStatus').textContent='✨ Apparence V25 enregistrée.'});
  $('v25OpenPublic').addEventListener('click',()=>window.open(location.href,'_blank'));
  $('v25Backup').addEventListener('click',()=>{const data={format:'LOGANATOR-V25-CONFIG',created_at:new Date().toISOString(),config:cfg()};const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(data,null,2)],{type:'application/json'}));a.download='loganator-v25-config.json';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);$('v25BackupStatus').textContent='💾 Configuration exportée.'});$('v25Restore').addEventListener('click',()=>$('v25RestoreFile').click());$('v25RestoreFile').addEventListener('change',e=>{const f=e.target.files[0];if(!f)return;const r=new FileReader();r.onload=()=>{try{const d=JSON.parse(r.result);if(d.format!=='LOGANATOR-V25-CONFIG')throw Error('Format invalide');saveCfg(d.config||{});loadHome();renderSections();$('v25BackupStatus').textContent='✅ Configuration restaurée.'}catch(err){alert(err.message)}};r.readAsText(f)});
  $('v25AddNotif').addEventListener('click',()=>{const c=cfg();c.notifications=c.notifications||[];c.notifications.unshift({title:$('v25NotifTitle').value,text:$('v25NotifText').value,icon:$('v25NotifIcon').value,date:new Date().toISOString()});saveCfg(c);$('v25NotifTitle').value='';$('v25NotifText').value='';renderNotifs()});function renderNotifs(){const a=cfg().notifications||[];$('v25NotifList').innerHTML=a.map(n=>`<div class="v25-row"><span>${esc(n.icon)} <b>${esc(n.title)}</b><br><span class="v25-muted">${esc(n.text)}</span></span></div>`).join('')||'<div class="v25-muted">Aucune notification.</div>'}renderNotifs();
  $('v25SaveGame').addEventListener('click',()=>{const c=cfg();c.game={level:Number($('v25Level').value)||1,points:Number($('v25Points').value)||0,badges:$('v25Badges').value};saveCfg(c)});
  async function loadServerSettings(){
    try{
      const d=await apiFetch('settings.php');
      if(!d.ok)throw new Error(d.error||'Impossible de charger les paramètres.');
      const s=d.settings||{};
      $('v25SettingName').value=s.name||'LOGANATOR';
      $('v25Currency').value=s.currency||'EUR (€)';
      $('v25FreeShip').value=Number(s.freeShip??80);
      $('v25Maintenance').value=s.maintenance?'true':'false';
      $('v25SettingsStatus').textContent=s.maintenance?'🔴 Maintenance ACTIVE : les visiteurs publics voient uniquement le message de maintenance.':'🟢 Site public en ligne.';
    }catch(e){$('v25SettingsStatus').textContent='⚠️ Paramètres serveur indisponibles : '+e.message}
  }
  $('v25SaveSettings').addEventListener('click',async()=>{
    const payload={name:$('v25SettingName').value.trim()||'LOGANATOR',currency:$('v25Currency').value,freeShip:Number($('v25FreeShip').value)||80,maintenance:$('v25Maintenance').value==='true'};
    const btn=$('v25SaveSettings');btn.disabled=true;
    try{
      const r=await apiFetch('settings.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),cache:'no-store'});
      const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||('Erreur HTTP '+r.status));
      const c=cfg();c.settings={...payload};saveCfg(c);
      $('v25SettingsStatus').textContent=payload.maintenance?'🔴 MAINTENANCE ACTIVÉE — le public voit maintenant uniquement « Le site est en cours de maintenance ».':'🟢 MAINTENANCE DÉSACTIVÉE — la boutique est de nouveau publique.';
    }catch(e){$('v25SettingsStatus').textContent='❌ '+e.message}
    finally{btn.disabled=false}
  });
  loadServerSettings();
  async function runDiag(){let premium=false;try{premium=await premiumCheck()}catch(e){}$('dSupabase').textContent='⚪ N/A';$('dSession').textContent=window.LOGANATOR_USER?'🟢':'🔴';$('dPremium').textContent=premium?'🟢':'🔴';$('dProducts').textContent=Array.isArray(window.products)?'🟢':'🔴';$('v25Diag').innerHTML=[['Serveur PHP',true],['Session',!!window.LOGANATOR_USER],['Premium',premium],['Produits',Array.isArray(window.products)],['Mode maintenance','Serveur'],['Control Center',true]].map(x=>`<div class="v25-row"><span>${x[0]}</span><span class="v25-pill">${x[1]===true?'🟢 OK':x[1]==='Serveur'?'🟢 ACTIF':'⚪ N/A'}</span></div>`).join('')};$('v25RunDiag').addEventListener('click',runDiag);
  $('v25ShopLoganator').addEventListener('click',()=>alert('🔵 LOGANATOR sélectionné.'));$('v25ShopLoganatrice').addEventListener('click',()=>alert('🩷 LOGANATRICE sélectionnée.'));
  window.addEventListener('keydown',e=>{if(e.key==='Escape'&&shell.classList.contains('open'))shell.classList.remove('open')});
})();
</script>



<style id="loganator-v26-account-style">
#accountBtn{position:relative!important;z-index:100002!important;pointer-events:auto!important;cursor:pointer!important}
#accountModal{z-index:100001!important}
#accountModal.open{display:flex!important;visibility:visible!important;opacity:1!important}
.v26-account-note{margin-top:12px;padding:12px 14px;border:1px solid rgba(120,100,255,.25);border-radius:12px;background:linear-gradient(135deg,rgba(30,120,255,.12),rgba(125,70,255,.12),rgba(255,70,160,.08));color:#dfe4ff;font-size:13px}
.v26-premium-access{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
</style>

<script id="v26-hard-open">
window.__LOGANATOR_OPEN_ACCOUNT=function(){var m=document.getElementById("accountModal");if(m){m.classList.add("open");m.style.display="flex";m.style.visibility="visible";m.style.opacity="1";}return false;};
</script>
<script id="loganator-v26-account-system">
(function(){
  'use strict';
  const PREMIUM_ALLOW=['gauducheaulogan@gmail.com','leloganator@gmail.com','alexisprouillac@gmail.com','theteamsloganator@gmail.com'];
  function el(id){return document.getElementById(id)}
  async function currentUser(){try{const r=await apiFetch('auth.php?action=me');window.LOGANATOR_USER=r.user||null;return window.LOGANATOR_USER}catch(e){return window.LOGANATOR_USER||null}}
  async function isPremium(user){return !!(user&&user.role==='premium')}
  function showModal(){const m=el('accountModal');if(!m)return null;m.style.display='flex';m.style.visibility='visible';m.style.opacity='1';m.classList.add('open');return m}
  function hideAccount(){const m=el('accountModal');if(m){m.classList.remove('open');m.style.display='none';m.style.visibility='hidden';m.style.opacity='0'}}
  function safe(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
  function msg(t){const x=el('v12Msg');if(x)x.textContent=t}
  function renderLogged(user,premium){const title=el('authTitle'),area=el('accountArea');if(title)title.textContent='Mon compte';if(!area)return;area.innerHTML='<p>Connecté avec <b>'+safe(user.email)+'</b>.</p>'+(premium?'<div class="v26-account-note">👑 <b>Compte PREMIUM détecté</b><br>Ton espace Premium fonctionne directement sur ton hébergement.</div><div class="v26-premium-access"><button class="primary" type="button" id="v26Premium">👑 OUVRIR PREMIUM</button><button class="btn" type="button" id="v26Logout">SE DÉCONNECTER</button></div>':'<div class="v26-account-note">Compte connecté.</div><div class="v26-premium-access"><button class="btn" type="button" id="v26Logout">SE DÉCONNECTER</button></div>');const p=el('v26Premium');if(p)p.onclick=()=>window.openPremium();const lo=el('v26Logout');if(lo)lo.onclick=async()=>{await window.premiumLogout();showAccount()}}
  function renderLogin(){const title=el('authTitle'),area=el('accountArea');if(title)title.textContent='Connexion Premium';if(!area)return;area.innerHTML='<div class="field"><label>Adresse e-mail</label><input id="v12Email" type="email" autocomplete="email" placeholder="ton@email.com"></div><br><div class="field"><label>Mot de passe</label><input id="v12Password" type="password" autocomplete="current-password" placeholder="••••••••"></div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:15px"><button class="primary" type="button" id="v26Login">SE CONNECTER</button></div><div id="v12Msg" class="notice">Connexion Premium autonome — aucun Supabase.</div><div style="margin-top:10px;font-size:.9em;opacity:.75">Première installation : ouvre <b>setup.php</b> une seule fois.</div>';el('v26Login').onclick=()=>window.v12Login()}
  async function showAccount(){showModal();const user=await currentUser();if(user)renderLogged(user,await isPremium(user));else renderLogin()}
  window.v26Login=window.v12Login=async function(){const email=(el('v12Email')?.value||'').trim().toLowerCase(),password=el('v12Password')?.value||'';if(!email||!password)return msg('Remplis ton e-mail et ton mot de passe.');try{const r=await apiFetch('auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({email,password})});window.LOGANATOR_USER=r.user;const premium=await isPremium(r.user);if(premium){hideAccount();await window.openPremium()}else renderLogged(r.user,false)}catch(e){msg('❌ '+e.message)}};
  window.v26Signup=window.v12Signup=async function(){window.open('setup.php','_blank')};
  window.v26Reset=window.v12Reset=async function(){msg('Le changement de mot de passe se fait dans la configuration de l’hébergement. Aucun service externe n’est utilisé.')};
  window.openPremium=async function(){try{hideAccount();const user=await currentUser();if(!(await isPremium(user))){alert('👑 Accès réservé aux membres PREMIUM.');return false}const shell=el('v25Shell');if(shell){shell.classList.add('open');shell.setAttribute('aria-hidden','false');const dash=document.querySelector('.v25-page[data-page="dashboard"]');document.querySelectorAll('.v25-page').forEach(x=>x.classList.remove('active'));if(dash)dash.classList.add('active');await v12LoadProducts();if(typeof window.v25RefreshDashboard==='function')await window.v25RefreshDashboard();return true}const old=el('premiumModal');if(old){old.classList.add('open');old.style.display='flex';await v12LoadProducts();return true}return false}catch(e){console.error(e);alert('Le panneau Premium ne peut pas être ouvert.');return false}};
  window.openAccount=showAccount;
  function bind(){const b=el('accountBtn');if(!b)return;b.removeAttribute('onclick');b.onclick=e=>{e.preventDefault();e.stopPropagation();showAccount()}}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',bind,{once:true});else bind();
  document.addEventListener('click',e=>{const b=e.target?.closest?.('#accountBtn');if(!b)return;e.preventDefault();e.stopPropagation();showAccount()},true);
  currentUser().then(u=>{const b=el('accountBtn');if(b)b.textContent=u?'👑 PREMIUM':'👤 COMPTE'});
})();
</script>


<script id="loganator-v26-multi-store-cloud">
(function(){
'use strict';
const MS_KEY='__multiStore';
const DEFAULT_LOGANATRICE_CATALOG=[{"id": "talons", "name": "Talons Loganatrice", "price": "79,99 €", "sizes": ["35", "36", "37", "38", "39", "40", "41"], "desc": "Talons rose pailleté avec logo Loganatrice.", "cat": "Talons", "images": ["talons.png"]}, {"id": "robe-courte", "name": "Robe courte Loganatrice", "price": "59,99 €", "sizes": ["XS", "S", "M", "L", "XL"], "desc": "Robe courte entièrement rose pailleté, sans logo.", "cat": "Robes", "images": ["robe-courte.png"]}, {"id": "robe-longue", "name": "Robe longue Loganatrice", "price": "89,99 €", "sizes": ["XS", "S", "M", "L", "XL"], "desc": "Robe longue rose pailleté, coupe élégante, sans logo.", "cat": "Robes", "images": ["robe-longue.png"]}, {"id": "jupe", "name": "Jupe Loganatrice", "price": "49,99 €", "sizes": ["XS", "S", "M", "L", "XL"], "desc": "Jupe rose pailleté assortie à la collection.", "cat": "Jupes", "images": ["jupe.png"]}, {"id": "casquette", "name": "Casquette Loganatrice", "price": "24,99 €", "sizes": ["Unique"], "desc": "Casquette rose pailleté avec logo de la boutique.", "cat": "Casquettes", "images": ["casquette.png"]}, {"id": "casque-bluetooth", "name": "Casque Bluetooth Loganatrice", "price": "89,99 €", "sizes": ["Unique"], "desc": "Casque Bluetooth rose pailleté avec logo Loganatrice.", "cat": "Casques", "images": ["casque-bluetooth.png"]}, {"id": "sac", "name": "Sac Loganatrice", "price": "54,99 €", "sizes": ["Unique"], "desc": "Sac rose pailleté avec logo Loganatrice.", "cat": "Sacs", "images": ["sac.png"]}, {"id": "accessoires", "name": "Set d'accessoires Loganatrice", "price": "29,99 €", "sizes": ["Unique"], "desc": "Set d'accessoires rose pailleté.", "cat": "Accessoires", "images": ["accessoires.png"]}, {"id": "doudoune", "name": "Doudoune Loganatrice", "price": "99,99 €", "sizes": ["XS", "S", "M", "L", "XL"], "desc": "Doudoune rose pailleté avec logo Loganatrice.", "cat": "Doudounes", "images": ["doudoune.png"]}];
let activeStore='loganator';
let cloudRow=null;
let cloudDraft=null;
let loaded=false;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const deepClone=o=>JSON.parse(JSON.stringify(o));
function premiumOK(){try{return typeof v16Can==='function'&&v16Can()}catch(e){return false}}
function storeName(s){return s==='loganatrice'?'🌸 LOGANATRICE':'🔵 LOGANATOR'}
function baseLoganatorCatalog(){
  return (Array.isArray(products)?products:[]).map(x=>deepClone(x));
}
function normalizeCat(p){
  const x={...p};
  x.id=String(x.id||('local-'+Date.now()));
  x.name=x.name||'Produit';
  x.price=Number(x.price)||0;
  x.cat=x.cat||x.category||'Hauts';
  x.category=x.cat;
  x.description=x.description||((x.details||[]).join('\n'));
  x.details=Array.isArray(x.details)?x.details:(x.description?[x.description]:[]);
  x.sizes=Array.isArray(x.sizes)&&x.sizes.length?x.sizes:['Unique'];
  x.images=Array.isArray(x.images)&&x.images.length?x.images:[x.image||x.image_url||''];
  x.image=x.image||x.image_url||x.images[0]||'';
  x.image_url=x.image_url||x.image||'';
  x.stock=Number(x.stock)||0;
  return x;
}
async function loadCloud(){
  if(!window.LOGANATOR_SB) return false;
  const r=await window.LOGANATOR_SB.from('store_settings').select('*').eq('id',1).maybeSingle();
  if(r.error) throw r.error;
  cloudRow=r.data||{id:1,draft:{},published:{}};
  cloudDraft=(cloudRow.draft&&typeof cloudRow.draft==='object')?cloudRow.draft:{};
  if(!cloudDraft[MS_KEY]||typeof cloudDraft[MS_KEY]!=='object') cloudDraft[MS_KEY]={};
  const ms=cloudDraft[MS_KEY];
  if(!ms.loganator) ms.loganator={catalog:baseLoganatorCatalog(),settings:{},promos:[]};
  if(!Array.isArray(ms.loganator.catalog)||!ms.loganator.catalog.length) ms.loganator.catalog=baseLoganatorCatalog();
  if(!ms.loganatrice) ms.loganatrice={catalog:DEFAULT_LOGANATRICE_CATALOG.map(normalizeCat),settings:{},promos:[]};
  if(!Array.isArray(ms.loganatrice.catalog)||!ms.loganatrice.catalog.length) ms.loganatrice.catalog=DEFAULT_LOGANATRICE_CATALOG.map(normalizeCat);
  loaded=true;
  return true;
}
async function saveCloud(publish=true){
  if(!window.LOGANATOR_SB) throw new Error('Supabase indisponible.');
  cloudDraft=cloudDraft||{};
  cloudDraft[MS_KEY]=cloudDraft[MS_KEY]||{};
  const payload={id:1,draft:cloudDraft,updated_at:new Date().toISOString()};
  if(publish) payload.published={...(cloudRow?.published||{}),[MS_KEY]:deepClone(cloudDraft[MS_KEY])};
  const r=await window.LOGANATOR_SB.from('store_settings').upsert(payload);
  if(r.error) throw r.error;
  cloudRow={...(cloudRow||{}),...payload};
}
function getStore(){return cloudDraft?.[MS_KEY]?.[activeStore]||{}}
function setStoreData(data){cloudDraft[MS_KEY][activeStore]=data}
async function selectStore(store){
  activeStore=store;
  if(!loaded) await loadCloud();
  const data=getStore();
  const catalog=(data.catalog||[]).map(normalizeCat);
  products.splice(0,products.length,...catalog);
  try{renderPremiumList()}catch(e){}
  const cards=document.getElementById('v26StoreCards');
  if(cards) cards.innerHTML=['loganator','loganatrice'].map(s=>`<div class="v16-box" style="cursor:pointer;border:2px solid ${s===activeStore?'#a78bfa':'var(--line)'}" onclick="window.v26SelectStore('${s}')"><h3>${storeName(s)}</h3><div class="v16-muted">${s===activeStore?'✅ Boutique actuellement contrôlée':'Cliquer pour contrôler cette boutique'}</div><button class="${s===activeStore?'primary':'btn'}" type="button">${s===activeStore?'ACTIVE':'CONTROLER'}</button></div>`).join('');
  const info=document.getElementById('v26StoreInfo'); if(info) info.innerHTML=`<b>${storeName(activeStore)}</b><br>${catalog.length} produit(s) dans le catalogue cloud.<br><span class="v16-muted">Les onglets Produits, Site et Promotions agissent sur cette boutique.</span>`;
  const status=document.getElementById('v26StoreStatus'); if(status) status.textContent='Boutique active : '+storeName(activeStore);
  try{v16LoadTools()}catch(e){}
  try{if(document.querySelector('[data-v16-tab="site"]')?.classList.contains('active'))v16LoadSettings()}catch(e){}
  try{if(document.querySelector('[data-v16-tab="promos"]')?.classList.contains('active'))v16LoadPromos()}catch(e){}
}
window.v26SelectStore=selectStore;
window.v26GetActiveStore=()=>activeStore;

async function saveProductCloud(p){
  if(!loaded) await loadCloud();
  const data=getStore();
  data.catalog=Array.isArray(data.catalog)?data.catalog:[];
  const idx=data.catalog.findIndex(x=>String(x.id)===String(p.id));
  if(idx>=0)data.catalog[idx]={...data.catalog[idx],...p}; else data.catalog.push(p);
  setStoreData(data); await saveCloud(true);
  products.splice(0,products.length,...data.catalog.map(normalizeCat));
  renderPremiumList(); try{render()}catch(e){}
}
window.deletePremiumProduct=async function(id){
  if(!confirm('Supprimer ce produit de '+storeName(activeStore)+' ?'))return;
  if(!loaded) await loadCloud();
  const data=getStore(); data.catalog=(data.catalog||[]).filter(x=>String(x.id)!==String(id)); setStoreData(data);
  await saveCloud(true); products.splice(0,products.length,...data.catalog.map(normalizeCat));
  renderPremiumList(); try{render()}catch(e){}; v16Notice('✅ Produit supprimé de '+storeName(activeStore)+'.',true);
};
window.editPremiumProduct=function(id){
  const p=products.find(x=>String(x.id)===String(id)); if(!p)return;
  document.getElementById('premiumEditorTitle').textContent='Modifier un produit — '+storeName(activeStore);
  document.getElementById('premiumId').value=id; document.getElementById('premiumName').value=p.name||'';
  document.getElementById('premiumPrice').value=p.price||0; document.getElementById('premiumCompare').value=p.compare_at_price||'';
  document.getElementById('premiumCat').value=p.cat||p.category||''; document.getElementById('premiumStock').value=p.stock??0;
  document.getElementById('premiumSizes').value=(p.sizes||[]).join(','); document.getElementById('premiumBadge').value=p.badge||'';
  document.getElementById('premiumDetails').value=p.description||((p.details||[]).join('\n'));
  document.getElementById('premiumFeatured').checked=!!p.is_featured; document.getElementById('premiumPopular').checked=!!p.is_popular;
  document.getElementById('premiumNew').checked=p.is_new!==false; document.getElementById('premiumPublished').checked=p.is_published!==false;
  document.getElementById('premiumPublishAt').value=p.publish_at?new Date(p.publish_at).toISOString().slice(0,16):'';
  document.getElementById('premiumImage').value=''; document.getElementById('premiumEditor').classList.add('open');
};
window.newPremiumProduct=function(){
  document.getElementById('premiumEditorTitle').textContent='Ajouter un produit — '+storeName(activeStore);
  ['premiumId','premiumName','premiumCompare','premiumPublishAt','premiumDetails'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('premiumPrice').value=0; document.getElementById('premiumCat').value='Hauts'; document.getElementById('premiumStock').value=0;
  document.getElementById('premiumSizes').value='XS,S,M,L,XL'; document.getElementById('premiumBadge').value='';
  document.getElementById('premiumFeatured').checked=false; document.getElementById('premiumPopular').checked=false;
  document.getElementById('premiumNew').checked=true; document.getElementById('premiumPublished').checked=true; document.getElementById('premiumImage').value='';
  document.getElementById('premiumEditor').classList.add('open');
};
function bindProductForm(){
  const form=document.getElementById('premiumForm'); if(!form)return;
  const clean=form.cloneNode(true); form.replaceWith(clean);
  clean.addEventListener('submit',async e=>{
    e.preventDefault();
    try{
      if(!loaded) await loadCloud();
      const id=document.getElementById('premiumId').value.trim();
      const old=products.find(x=>String(x.id)===String(id))||{};
      const file=document.getElementById('premiumImage').files[0]; let image=old.image||old.image_url||'';
      if(file && window.v12Upload) image=await window.v12Upload(file);
      const p=normalizeCat({...old,id:id||('cloud-'+Date.now().toString(36)),name:document.getElementById('premiumName').value.trim(),
        price:Number(document.getElementById('premiumPrice').value)||0,compare_at_price:document.getElementById('premiumCompare').value?Number(document.getElementById('premiumCompare').value):null,
        cat:document.getElementById('premiumCat').value,category:document.getElementById('premiumCat').value,stock:Number(document.getElementById('premiumStock').value)||0,
        sizes:document.getElementById('premiumSizes').value.split(',').map(x=>x.trim()).filter(Boolean),badge:document.getElementById('premiumBadge').value||null,
        description:document.getElementById('premiumDetails').value.trim(),details:document.getElementById('premiumDetails').value.trim().split(/\r?\n/).filter(Boolean),
        is_featured:document.getElementById('premiumFeatured').checked,is_popular:document.getElementById('premiumPopular').checked,is_new:document.getElementById('premiumNew').checked,
        is_published:document.getElementById('premiumPublished').checked,publish_at:document.getElementById('premiumPublishAt').value?new Date(document.getElementById('premiumPublishAt').value).toISOString():null,
        image,image_url:image,images:[image]});
      await saveProductCloud(p); closeEditor(); v16Notice('✅ Produit enregistré dans le cloud pour '+storeName(activeStore)+'.',true);
    }catch(err){alert('Erreur : '+(err.message||err))}
  });
}
async function loadCloudProducts(){
  if(!loaded) await loadCloud();
  await selectStore(activeStore);
}
window.v16LoadStoreCloud=loadCloudProducts;

// Cloud site settings
window.v16LoadSettings=async function(){
  if(!loaded) await loadCloud();
  const x=getStore().settings||{};
  const defaults=activeStore==='loganatrice'?{heroTitle:'LOGANATRICE',heroText:'Le même esprit de présentation premium, en rose pailleté. 💗✨',heroButton:'Découvrir la boutique'}:{heroTitle:'PORTE TON LOGANATOR.',heroText:'La boutique de la chaîne, avec les vraies photos de tes créations.',heroButton:'VOIR LA COLLECTION',bannerText:'🚚 LIVRAISON OFFERTE À PARTIR DE 80€',dropText:''};
  document.getElementById('v16HeroTitle').value=x.heroTitle||defaults.heroTitle;
  document.getElementById('v16HeroText').value=x.heroText||defaults.heroText; document.getElementById('v16HeroButton').value=x.heroButton||defaults.heroButton;
  document.getElementById('v16BannerText').value=x.bannerText||defaults.bannerText||''; document.getElementById('v16DropText').value=x.dropText||defaults.dropText||'';
  ['New','Popular','Promos','Drop'].forEach(k=>{const el=document.getElementById('v16Show'+k);if(el)el.checked=x['show'+k]!==false}); v16PreviewSettings();
};
window.v16SaveDraft=async function(){if(!loaded)await loadCloud();const d=getStore();d.settings={...d.settings,...v16SettingsObj()};setStoreData(d);await saveCloud(false);v16Notice('💾 Brouillon cloud enregistré pour '+storeName(activeStore)+'.',true)};
window.v16PublishDraft=async function(){if(!loaded)await loadCloud();const d=getStore();d.settings={...d.settings,...v16SettingsObj()};setStoreData(d);await saveCloud(true);v16Notice('🚀 Modifications publiées sur '+storeName(activeStore)+'.',true)};

// Cloud promos
window.v16LoadPromos=async function(){
  const box=document.getElementById('v16PromoList'); if(!box)return;
  if(!loaded)await loadCloud(); const arr=getStore().promos||[];
  box.innerHTML=arr.map((p,i)=>`<div class="v16-line"><span><b>${esc(p.code)}</b> · ${p.type==='percent'?p.value+'%':Number(p.value).toFixed(2)+'€'} ${p.active?'🟢':'🔴'}</span><button class="btn" onclick="window.v26ToggleCloudPromo(${i})">${p.active?'Désactiver':'Activer'}</button></div>`).join('')||'<div class="v16-muted">Aucun code pour cette boutique.</div>';
};
window.v26ToggleCloudPromo=async function(i){if(!loaded)await loadCloud();const d=getStore();d.promos=d.promos||[];if(!d.promos[i])return;d.promos[i].active=!d.promos[i].active;setStoreData(d);await saveCloud(true);v16LoadPromos()};
window.v16CreatePromo=async function(){
  if(!loaded)await loadCloud();
  const d=getStore();d.promos=d.promos||[];
  const code=document.getElementById('v16PromoCode').value.trim().toUpperCase(); if(!code)return alert('Entre un code.');
  d.promos.push({code,type:document.getElementById('v16PromoType').value,value:Number(document.getElementById('v16PromoValue').value)||0,min_order:Number(document.getElementById('v16PromoMin').value)||0,max_uses:Number(document.getElementById('v16PromoMax').value)||null,starts_at:document.getElementById('v16PromoStart').value?new Date(document.getElementById('v16PromoStart').value).toISOString():null,ends_at:document.getElementById('v16PromoEnd').value?new Date(document.getElementById('v16PromoEnd').value).toISOString():null,active:true});
  setStoreData(d);await saveCloud(true);v16LoadPromos();
};
window.v16LoadOrders=async function(){
  const box=document.getElementById('v16OrderRows');if(!box)return;
  if(activeStore==='loganatrice'){box.innerHTML='<tr><td colspan="6">🌸 LOGANATRICE : le paiement actuel de cette version est simulé, donc aucune commande réelle n’est enregistrée.</td></tr>';return;}
  // Preserve the existing LOGANATOR order manager.
  const r=await window.LOGANATOR_SB.from('orders').select('id,email,total,status,payment_status,created_at').order('created_at',{ascending:false}).limit(200);
  if(r.error){box.innerHTML='<tr><td colspan="6">'+esc(r.error.message)+'</td></tr>';return;}
  box.innerHTML=(r.data||[]).map(o=>`<tr><td>${new Date(o.created_at).toLocaleString('fr-FR')}</td><td>${esc(o.email||'—')}</td><td>${Number(o.total).toFixed(2)}€</td><td><select onchange='window.v26SetLoganatorOrderStatus(${JSON.stringify(o.id)},this.value)'><option value="pending" ${o.status==='pending'?'selected':''}>En attente</option><option value="preparation" ${o.status==='preparation'?'selected':''}>Préparation</option><option value="shipped" ${o.status==='shipped'?'selected':''}>Expédiée</option><option value="delivered" ${o.status==='delivered'?'selected':''}>Livrée</option><option value="cancelled" ${o.status==='cancelled'?'selected':''}>Annulée</option></select></td><td>${esc(o.payment_status)}</td><td></td></tr>`).join('')||'<tr><td colspan="6">Aucune commande.</td></tr>';
};
window.v26SetLoganatorOrderStatus=async function(id,status){const r=await window.LOGANATOR_SB.from('orders').update({status}).eq('id',id);if(r.error)return alert(r.error.message);v16Notice('✅ Statut mis à jour.',true)};
window.v16LoadStats=async function(){
  if(activeStore==='loganatrice'){
    const n=products.length;
    const a=document.getElementById('v16Visits'),b=document.getElementById('v16Views'),c=document.getElementById('v16Adds'),d=document.getElementById('v16Orders');
    if(a)a.textContent='—'; if(b)b.textContent=n; if(c)c.textContent='—'; if(d)d.textContent='0';
    const pop=document.getElementById('v16Popular'); if(pop)pop.innerHTML='<div class="v16-muted">Statistiques de visites non branchées sur cette version de Loganatrice.</div>';
    const alerts=document.getElementById('v16Alerts'); if(alerts)alerts.innerHTML='<div class="v16-good">✅ Catalogue Loganatrice chargé depuis le cloud.</div>';
    const recent=document.getElementById('v16Recent'); if(recent)recent.innerHTML='<div class="v16-muted">Aucune commande réelle : le paiement Loganatrice est encore simulé.</div>';
    return;
  }
  try{
    const r=await window.LOGANATOR_SB.from('analytics_events').select('event_type,product_id,metadata,created_at').order('created_at',{ascending:false}).limit(1000);
    if(r.error)throw r.error;
    const a=r.data||[], orders=await window.LOGANATOR_SB.from('orders').select('id,total,status,created_at,email').limit(1000);
    document.getElementById('v16Visits').textContent=a.filter(x=>x.event_type==='visit').length;
    document.getElementById('v16Views').textContent=a.filter(x=>x.event_type==='product_view').length;
    document.getElementById('v16Adds').textContent=a.filter(x=>x.event_type==='cart_add').length;
    document.getElementById('v16Orders').textContent=(orders.data||[]).length;
    const counts={};a.filter(x=>x.event_type==='product_view').forEach(x=>counts[x.product_id]=(counts[x.product_id]||0)+1);
    const pop=Object.entries(counts).sort((x,y)=>y[1]-x[1]).slice(0,5);
    document.getElementById('v16Popular').innerHTML=pop.length?pop.map(([id,n])=>{const p=products.find(x=>String(x.id)===String(id));return `<div class="v16-line"><span>${v16Esc(p?.name||'Produit')}</span><b>${n} vues</b></div>`}).join(''):'<div class="v16-muted">Pas encore assez de données.</div>';
    const low=products.filter(p=>Number(p.stock??0)<=3);
    document.getElementById('v16Alerts').innerHTML=low.length?low.map(p=>`<div class="v16-alert">⚠️ <b>${v16Esc(p.name)}</b> : stock faible (${Number(p.stock??0)})</div>`).join(''):'<div class="v16-good">✅ Aucun stock faible.</div>';
  }catch(e){premiumMessage('⚠️ Statistiques indisponibles : '+(e.message||e),false)}
};
window.v16SetTab=v16SetTab;

// Add store UI behavior and cloud load
const oldOpen=window.openPremium;
window.openPremium=async function(){
  // Utilise exactement le contrôle Premium existant de la V26.4.
  // Aucune nouvelle vérification de sécurité n'est ajoutée ici.
  await oldOpen();
  const modal=document.getElementById('premiumModal');
  if(!modal || !modal.classList.contains('open')) return;
  try{await loadCloud();}catch(e){v16Notice('⚠️ Cloud boutiques indisponible : '+(e.message||e),false);return;}
  try{await selectStore(activeStore);}catch(e){}
  bindProductForm();
  try{v16LoadStats()}catch(e){}; try{v16LoadSettings()}catch(e){}; try{v16LoadPromos()}catch(e){}; try{v16LoadTools()}catch(e){}
};
const oldClose=window.closePremium;
window.closePremium=async function(){
  try{
    activeStore='loganator';
    if(loaded) await selectStore('loganator');
  }catch(e){}
  const modal=document.getElementById('premiumModal'); if(modal)modal.classList.remove('open');
};
// extend tab handler by wrapping
const origTab=window.v16SetTab;
window.v16SetTab=function(name){
  if(name==='stores'){selectStore(activeStore);return;}
  return origTab(name);
};
document.addEventListener('click',e=>{
  const b=e.target.closest('[data-v16-tab="stores"]'); if(b){e.preventDefault();selectStore(activeStore);}
});
window.addEventListener('load',()=>setTimeout(()=>{if(window.LOGANATOR_SB&&premiumOK()){loadCloud().catch(()=>{});}},1200));
})();
</script>

<script>
// Si la maintenance est activée depuis Premium pendant que cette page publique est déjà ouverte,
// on recharge rapidement : index.php renverra alors uniquement la page de maintenance.
(function(){
  setInterval(function(){
    apiFetch('settings.php')
      .then(function(d){if(d&&d.ok&&d.settings&&d.settings.maintenance) location.reload()})
      .catch(function(){/* réseau temporairement indisponible : ne pas casser la boutique */});
  },5000);
})();
</script>
</body>
</html>
