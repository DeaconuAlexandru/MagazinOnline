<?php
session_start();


try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset={$charset}",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit("Eroare DB: " . $e->getMessage());
}

require_once __DIR__ . '/social_tracker.php';

/* tracking doar la vizualizare, nu la POST/AJAX */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('recordSocialVisit')) {
    recordSocialVisit($pdo, 'CosulMeu.php');
}

$lang = 'ro';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ro', 'ru'], true)) {
    $lang = $_GET['lang'];
}

// Coșul propriu-zis
$cart = $_SESSION['cart'] ?? [];

// Funcție formatare preț
function formatPrice($price): string {
    return number_format((float)$price, 2, ',', '') . ' Lei';
}

function calculateTotal(array $cart): float {
    $total = 0;
    foreach ($cart as $it) {
        $price = (float) str_replace(',', '.', (string)($it['price'] ?? 0));
        $qty   = max(1, (int)($it['qty'] ?? 1));
        $total += $price * $qty;
    }
    return $total;
}

// =======================
// AJAX: ștergere fără refresh
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'remove') {
    $productKey = (string) ($_POST['product_key'] ?? '');

    if ($productKey !== '' && isset($_SESSION['cart'][$productKey])) {
        unset($_SESSION['cart'][$productKey]);
        $cart = $_SESSION['cart'];
    }

    $total = calculateTotal($cart);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'total_formatted' => formatPrice($total),
        'total_raw' => $total,
        'empty' => empty($cart)
    ]);
    exit;
}

// =======================
// Acțiuni non-AJAX
// =======================

// Update cantitate
if (isset($_POST['update_qty']) && isset($_POST['product_key'])) {
    $productKey = (string) $_POST['product_key'];
    $qty = max(1, (int) ($_POST['qty'] ?? 1));

    if (isset($cart[$productKey])) {
        $cart[$productKey]['qty'] = $qty;
        $_SESSION['cart'] = $cart;
    }

    header('Location: CosulMeu.php');
    exit;
}

// Ștergere produs (fallback GET)
if (isset($_GET['remove'])) {
    $productKey = (string) $_GET['remove'];

    if (isset($cart[$productKey])) {
        unset($cart[$productKey]);
        $_SESSION['cart'] = $cart;
    }

    header('Location: CosulMeu.php');
    exit;
}

// Continuă comanda
if (isset($_POST['continue'])) {
    $total = calculateTotal($cart);
    $_SESSION['checkout_cart'] = $cart;
    $_SESSION['checkout_total'] = $total;
    header('Location: ContinuaComanda.php');
    exit;
}

// Total curent
$total = calculateTotal($cart);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cosul Meu - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*,
*::before,
*::after {
  box-sizing: border-box;
}

html,
body {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

body{
  margin:0;
  font-family:'Roboto',sans-serif;
  color:#111;
  background:#fdf6f0;
  scroll-behavior:smooth;
  transition:background 0.3s,color 0.3s;
  padding-left: max(0px, env(safe-area-inset-left));
  padding-right: max(0px, env(safe-area-inset-right));
}

.dark-mode{
  background:#111;
  color:#fdf6f0;
}

header{
  position:sticky;
  top:0;
  z-index:10;
  background:#8C92AC;
  color:#fff;
  box-shadow:0 2px 8px rgba(0,0,0,0.3);
  padding:15px 30px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:15px;
  flex-wrap:wrap;
  width:100%;
}

header.dark-mode{
  background:#222;
}

header .logo{
  display:flex;
  align-items:center;
  gap:15px;
  min-width:0;
}

header .logo img{
  height:50px;
  width:auto;
  display:block;
}

#mainNav{
  flex:1;
  display:flex;
  justify-content:center;
  gap:15px;
  flex-wrap:wrap;
  align-items:center;
  transition:max-height 0.3s ease;
}

#mainNav a{
  text-decoration:none;
  padding:10px 18px;
  background:#fff;
  color:#b5651d;
  border-radius:8px;
  transition:.3s;
  font-weight:500;
  white-space:nowrap;
}

#mainNav a.active,
#mainNav a:hover{
  background:#8b4315;
  color:#fff;
  transform:translateY(-2px);
}

.menu-toggle{
  display:none;
  flex-direction:column;
  cursor:pointer;
  gap:5px;
  flex-shrink:0;
}

.menu-toggle div{
  width:30px;
  height:3px;
  background:#fff;
  border-radius:2px;
  transition:0.3s;
}

.container{
  max-width:1200px;
  margin:auto;
  padding:20px;
}

.page-title{
  font-family:'Montserrat',sans-serif;
  font-size:28px;
  margin:10px 0 20px;
}

.cart-wrapper{
  width:100%;
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
}

table{
  width:100%;
  border-collapse:collapse;
  margin-bottom:20px;
  background:#fff;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 10px 24px rgba(0,0,0,0.08);
}

th, td{
  padding:14px 12px;
  border:1px solid #ece3dc;
  text-align:center;
  vertical-align:middle;
}

th{
  background:#8b4315;
  color:#fff;
  font-weight:700;
}

.cart-img{
  width:90px;
  height:90px;
  object-fit:cover;
  border-radius:10px;
  display:block;
  margin:auto;
  box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.qty-form{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  flex-wrap:wrap;
}

input.qty{
  width:64px;
  text-align:center;
  padding:8px;
  border:1px solid #ccc;
  border-radius:8px;
  font-size:15px;
}

button{
  padding:9px 15px;
  border-radius:8px;
  border:none;
  background:#b5651d;
  color:#fff;
  cursor:pointer;
  transition:0.3s;
  font-weight:600;
}

button:hover{
  background:#8b4315;
  transform:translateY(-1px);
}

.delete-ajax{
  display:inline-block;
  padding:9px 14px;
  border-radius:8px;
  background:#d9534f;
  color:#fff;
  text-decoration:none;
  font-weight:600;
  transition:0.3s;
}

.delete-ajax:hover{
  background:#c9302c;
}

.summary{
  display:flex;
  justify-content:flex-end;
  margin-top:10px;
}

.summary-box{
  background:#fff;
  padding:16px 18px;
  border-radius:14px;
  box-shadow:0 8px 20px rgba(0,0,0,0.08);
  min-width:280px;
}

.total{
  font-weight:700;
  font-size:18px;
  text-align:right;
  margin:0 0 12px;
}

.checkout-btn{
  width:100%;
}

.empty-cart{
  background:#fff;
  padding:18px;
  border-radius:14px;
  box-shadow:0 8px 20px rgba(0,0,0,0.08);
}

footer{
  background:#8C92AC;
  color:#fff;
  padding:50px 20px;
  font-family:'Roboto',sans-serif;
  transition:background 0.3s, color 0.3s;
  width:100%;
}

footer.dark-mode{
  background:#222;
}

footer .footer-container{
  display:flex;
  flex-wrap:wrap;
  gap:40px;
  justify-content:flex-start;
  align-items:flex-start;
  max-width:1200px;
  margin:0 auto;
}

footer .footer-col{
  flex:1;
  min-width:200px;
}

footer .footer-logo img{
  height:50px;
  width:auto;
  display:block;
  margin-bottom:15px;
}

footer .footer-social{
  display:flex;
  gap:10px;
  margin-top:5px;
}

footer .footer-social a{
  display:flex;
  align-items:center;
  justify-content:center;
  width:30px;
  height:30px;
  color:#fff;
  transition:color 0.3s;
}

footer ul{
  list-style:none;
  padding:0;
  margin:10px 0 20px 0;
  line-height:1.8;
}

footer ul li a{
  color:#fff;
  text-decoration:none;
}

footer .footer-bottom{
  margin-top:30px;
  border-top:1px solid rgba(255,255,255,0.3);
  padding-top:15px;
  font-size:14px;
}

footer .footer-bottom a{
  color:#fff;
  text-decoration:none;
  margin:0 8px;
}

.toast{
  position:fixed;
  right:20px;
  bottom:20px;
  background:#222;
  color:#fff;
  padding:10px 14px;
  border-radius:8px;
  box-shadow:0 6px 18px rgba(0,0,0,0.35);
  display:none;
  z-index:9999;
}

body.dark-mode .summary-box,
body.dark-mode .empty-cart,
body.dark-mode table,
body.dark-mode .container p,
body.dark-mode .summary-box p{
  background:#1e1e1e;
  color:#fdf6f0;
}

body.dark-mode th{
  background:#7b3d14;
}

body.dark-mode td{
  border-color:#333;
}

body.dark-mode input.qty{
  background:#111;
  color:#fdf6f0;
  border-color:#444;
}

body.dark-mode .delete-ajax{
  background:#c94b47;
}

body.dark-mode .delete-ajax:hover{
  background:#b63a36;
}

@media screen and (max-width: 768px){
  header{
    padding:12px 16px;
  }

  .menu-toggle{
    display:flex;
    margin-left:auto;
  }

  #mainNav{
    display:none;
    width:100%;
    flex-direction:column;
    align-items:flex-start;
    gap:10px;
    padding-top:10px;
  }

  #mainNav.mobile-open{
    display:flex;
  }

  #mainNav a{
    width:100%;
    text-align:left;
    padding:11px 14px;
  }

  .page-title{
    font-size:24px;
    margin-bottom:16px;
  }

  .container{
    padding:12px;
  }

  .cart-wrapper{
    overflow:visible;
  }

  table,
  thead,
  tbody,
  th,
  tr,
  td{
    display:block;
    width:100%;
  }

  thead{
    display:none;
  }

  table{
    background:transparent;
    box-shadow:none;
    border-radius:0;
  }

  tr{
    margin-bottom:14px;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
    padding:0;
  }

  td{
    border:none;
    border-bottom:1px solid #f0f0f0;
    text-align:left;
    padding:12px 14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
  }

  td:last-child{
    border-bottom:none;
  }

  td::before{
    content:attr(data-label);
    font-weight:700;
    color:#8b4315;
    flex:0 0 38%;
  }

  td[data-label="Imagine"]{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
  }

  td[data-label="Imagine"]::before{
    content:"Imagine";
    width:100%;
    margin-bottom:8px;
    flex:unset;
    text-align:left;
  }

  td[data-label="Imagine"] img{
    width:100%;
    max-width:140px;
    height:auto;
    margin:0;
  }

  td[data-label="Cantitate"]{
    align-items:flex-start;
  }

  td[data-label="Cantitate"]::before{
    margin-top:8px;
  }

  .qty-form{
    width:100%;
    justify-content:flex-end;
  }

  .summary{
    justify-content:stretch;
  }

  .summary-box{
    width:100%;
    min-width:0;
  }

  .total{
    text-align:left;
  }

  footer .footer-container{
    flex-direction:column;
    gap:25px;
  }
}

@media screen and (max-width: 420px){
  header{
    padding:12px 12px;
  }

  .container{
    padding:10px;
  }

  td{
    padding:11px 12px;
  }

  td::before{
    flex:0 0 42%;
  }

  .delete-ajax{
    width:100%;
    text-align:center;
  }

  .summary-box{
    padding:14px;
  }
}
</style>
</head>
<body>

<header id="header">
  <div class="logo">
    <a href="Acasa.php" style="display:inline-flex;align-items:center;text-decoration:none;">
      <img src="Image41.png" alt="Logo">
    </a>
  </div>

  <nav id="mainNav">
    <a href="Acasa.php">Acasă</a>
    <a href="Colectie.php">Colecție</a>
    <a href="DespreNoi.php">Despre Noi</a>
    <a href="Contact.php">Contact</a>
    <a href="CosulMeu.php" class="active">Cosul Meu</a>
    <a href="ContulMeu.php">Contul Meu</a>
  </nav>

  <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul">
    <div></div><div></div><div></div>
  </div>
</header>

<div class="container">
  <h1 class="page-title">Coșul Meu</h1>

  <?php if (empty($cart)): ?>
    <div class="empty-cart">
      <p>Coșul tău este gol.</p>
    </div>
  <?php else: ?>
    <div class="cart-wrapper">
      <table id="cartTable">
        <thead>
          <tr>
            <th>Imagine</th>
            <th>Produs</th>
            <th>Preț</th>
            <th>Cantitate</th>
            <th>Subtotal</th>
            <th>Acțiune</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $productKey => $item):
            $price = (float) str_replace(',', '.', (string)($item['price'] ?? 0));
            $qty = max(1, (int)($item['qty'] ?? 1));
            $subtotal = $price * $qty;
            $img = (string)($item['img'] ?? '');
            $name = (string)($item['name'] ?? '');
        ?>
          <tr data-key="<?= htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8') ?>">
            <td data-label="Imagine">
              <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="cart-img">
            </td>
            <td data-label="Produs">
              <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>

              <?php if (!empty($item['category'])): ?>
                <div style="font-size:13px;color:#666;margin-top:4px;">
                  Categorie: <?= htmlspecialchars((string)$item['category'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($item['material'])): ?>
                <div style="font-size:13px;color:#666;margin-top:4px;">
                  Material: <?= htmlspecialchars((string)$item['material'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($item['dimensiuni'])): ?>
                <div style="font-size:13px;color:#666;margin-top:4px;">
                  Dimensiuni: <?= htmlspecialchars((string)$item['dimensiuni'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>
            </td>
            <td data-label="Preț"><?= formatPrice($price) ?></td>
            <td data-label="Cantitate">
              <form method="post" class="qty-form">
                <input type="number" name="qty" value="<?= $qty ?>" min="1" class="qty">
                <input type="hidden" name="product_key" value="<?= htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="update_qty">Update</button>
              </form>
            </td>
            <td data-label="Subtotal" class="subtotal"><?= formatPrice($subtotal) ?></td>
            <td data-label="Acțiune">
              <a href="CosulMeu.php?remove=<?= urlencode($productKey) ?>" class="delete-ajax" data-key="<?= htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8') ?>">Șterge</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="summary" id="summaryBox">
      <div class="summary-box">
        <p class="total">Total produse: <span id="totalAmount"><?= formatPrice($total) ?></span></p>
        <form method="post" style="margin:0;">
          <button type="submit" name="continue" class="checkout-btn">Continuă Comanda</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<div id="toast" class="toast" aria-hidden="true"></div>

<footer id="footer">
  <div class="footer-container">
    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image41.png" alt="Sherghei Covoare">
      </div>
      <p>Primul an prin care aducem covoare traditionale si moderne in casa ta.</p>
      <div class="footer-social">
        <a href="https://www.facebook.com/maestro.fortunato/" target="_blank" title="Facebook">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v8h4v-8h3l1-4h-4V6a1 1 0 0 1 1-1h3z"/>
          </svg>
        </a>
        <a href="https://www.instagram.com/hariharago?igsh=MXd5dHd5ZzY2ZnBpaw==" target="_blank" title="Instagram">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
            <path d="M16 11.37a4 4 0 1 1-7.94 1.26 4 4 0 0 1 7.94-1.26z"/>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
          </svg>
        </a>
      </div>
    </div>

    <div class="footer-col">
      <strong>Navigare</strong>
      <ul>
        <li><a href="Acasa.php">Acasa</a></li>
        <li><a href="Colectie.php">Colectie</a></li>
        <li><a href="DespreNoi.php">Despre Noi</a></li>
        <li><a href="Contact.php">Contact</a></li>
        <li><a href="CosulMeu.php">Cosul Meu</a></li>
        <li><a href="ContulMeu.php">Contul Meu</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <strong>Servicii</strong>
      <ul>
        <li><a href="#">Vanzare produse psygeometry</a></li>
        <li><a href="#">Consultanta gratuita</a></li>
        <li><a href="#">Livrare la domiciliu</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <strong>Contact</strong>
      <p>Adresa, Craiova,Dolj</p>
      <p>Telefon, 0753 508 461</p>
      <p>Email, office@magazinpsy.ro</p>
      <p>Program, Luni Sambata 9 18</p>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© 2024 Sherghei Covoare. Toate drepturile rezervate.</p>
    <a href="TermeniSiConditii.php">Termeni si conditii</a> |
    <a href="PoliticaDeConfidentialitate.php">Politica de confidentialitate</a>
  </div>
</footer>

<script>
(function () {
  'use strict';

  function escapeSelector(value) {
    if (window.CSS && typeof CSS.escape === 'function') {
      return CSS.escape(value);
    }
    return String(value).replace(/["\\]/g, '\\$&');
  }

  function toggleMenu() {
    const nav = document.getElementById('mainNav');
    if (!nav) return;
    nav.classList.toggle('mobile-open');
  }
  window.toggleMenu = toggleMenu;

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    if (header) header.classList.toggle('dark-mode');
    if (footer) footer.classList.toggle('dark-mode');
  }
  window.toggleDarkMode = toggleDarkMode;

  document.addEventListener('click', function (e) {
    const a = e.target && e.target.closest ? e.target.closest('.delete-ajax') : null;
    if (!a) return;

    e.preventDefault();

    const key = a.getAttribute('data-key');
    if (!key) return;

    const fd = new FormData();
    fd.append('ajax_action', 'remove');
    fd.append('product_key', key);

    fetch(location.href, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
    .then(function (resp) {
      if (!resp.ok) throw new Error('network');
      return resp.json();
    })
    .then(function (json) {
      if (!json || !json.success) {
        alert('A apărut o eroare. Încearcă din nou.');
        return;
      }

      const row = document.querySelector('tr[data-key="' + escapeSelector(key) + '"]');
      if (row) row.remove();

      const totalAmount = document.getElementById('totalAmount');
      if (totalAmount) totalAmount.textContent = json.total_formatted;

      const anyRow = document.querySelector('#cartTable tbody tr[data-key]');
      if (!anyRow || json.empty) {
        const container = document.querySelector('.container');
        if (container) {
          container.innerHTML = '<h1 class="page-title">Coșul Meu</h1><div class="empty-cart"><p>Coșul tău este gol.</p></div>';
        }
      }

      const toast = document.getElementById('toast');
      if (toast) {
        toast.textContent = 'Produs șters din coș';
        toast.style.display = 'block';
        setTimeout(function () {
          toast.style.display = 'none';
        }, 1500);
      }
    })
    .catch(function () {
      alert('A apărut o eroare de rețea. Verifică conexiunea.');
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
    const nav = document.getElementById('mainNav');
    if (nav && window.innerWidth > 768) {
      nav.classList.remove('mobile-open');
    }
  });

})();
</script>
</body>
</html>
