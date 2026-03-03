<?php
session_start();
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) $lang = $_GET['lang'];

// Coșul propriu-zis
$cart = $_SESSION['cart'] ?? [];

// Functie formatare pret
function formatPrice($price): string {
    return number_format((float)$price, 2, ',', '') . ' Lei';
}

// =======================
// AJAX: stergere fara refresh
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'remove') {
    $productKey = (string) ($_POST['product_key'] ?? '');
    if ($productKey !== '' && isset($_SESSION['cart'][$productKey])) {
        unset($_SESSION['cart'][$productKey]);
        // actualizam $cart local
        $cart = $_SESSION['cart'];
    }
    // recalculam total
    $total = 0;
    foreach ($cart as $it) {
        $price = (float) str_replace(',', '.', $it['price']);
        $total += $price * $it['qty'];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'total_formatted' => formatPrice($total),
        'total_raw' => $total
    ]);
    exit;
}

// =======================
// 1) Procesam actiuni non-AJAX (update_qty, remove via GET, continue) INAINTE de calcul total
// =======================

// Update cantitate (formular per rand cu method=post, non-AJAX)
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

// Stergere produs (fallback GET)
if (isset($_GET['remove'])) {
    $productKey = (string) $_GET['remove'];
    if (isset($cart[$productKey])) {
        unset($cart[$productKey]);
        $_SESSION['cart'] = $cart;
    }
    header('Location: CosulMeu.php');
    exit;
}

// Continua comanda
if (isset($_POST['continue'])) {
    // recalc total inainte de pasare
    $total = 0;
    foreach ($cart as $it) {
        $price = (float) str_replace(',', '.', $it['price']);
        $total += $price * $it['qty'];
    }
    $_SESSION['checkout_cart'] = $cart;
    $_SESSION['checkout_total'] = $total;
    header('Location: ContinuaComanda.php');
    exit;
}

// =======================
// 2) Calculam totalul pe baza starii curente a cosului (dupa update/remove)
// =======================
$total = 0;
foreach ($cart as $it) {
    $price = (float) str_replace(',', '.', $it['price']);
    $total += $price * $it['qty'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cosul Meu - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
/* (stilurile tale raman neschimbate) */
body{margin:0;font-family:'Roboto',sans-serif;color:#111;background:#fdf6f0;scroll-behavior:smooth;}
.dark-mode{background:#111;color:#fdf6f0;}
header{position:sticky;top:0;z-index:10;background:#8C92AC;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
header h1{font-family:'Montserrat',sans-serif;font-weight:700;font-size:30px;margin:0;}
.header-contact{display:flex;gap:15px;align-items:center;}
.header-contact .phone{font-weight:600;}
.header-contact .btn{background:#fff;color:#b5651d;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:500;position:relative;transition:0.3s;}
.header-contact .btn:hover{background:#8b4315;color:#fff;transform:translateY(-2px);}
.dark-toggle{cursor:pointer;padding:10px;border-radius:8px;background:#fff;color:#b5651d;font-weight:600;transition:0.3s;}
.dark-toggle:hover{background:#b5651d;color:#fff;}
nav{display:flex;justify-content:center;gap:15px;margin:20px 0;flex-wrap:wrap;}
nav a{text-decoration:none;padding:10px 18px;background:#fff;color:#b5651d;border-radius:8px;transition:.3s;font-weight:500;}
nav a.active{background:#8b4315;color:#fff;}
nav a:hover{background:#b5651d;color:#fff;transform:translateY(-2px);}
.container{max-width:1200px;margin:auto;padding:20px;}
table{width:100%;border-collapse:collapse;margin-bottom:20px;}
th, td{padding:10px;border:1px solid #ccc;text-align:center;}
th{background:#8b4315;color:#fff;}
input.qty{width:50px;text-align:center;}
button, select{padding:8px 15px;border-radius:6px;border:none;background:#b5651d;color:#fff;cursor:pointer;}
button:hover, select:hover{background:#8b4315;}
.total{font-weight:bold;font-size:18px;text-align:right;}
footer{background:#8C92AC;color:#fff;padding:50px 20px;font-family:'Roboto',sans-serif;}
footer .footer-container{display:flex;flex-wrap:wrap;gap:40px;justify-content:flex-start;align-items:flex-start;}
footer .footer-col{flex:1;min-width:200px;}
footer .footer-logo img{height:50px;width:auto;display:block;margin-bottom:15px;}
footer .footer-social{display:flex;gap:10px;margin-top:5px;}
footer .footer-social a{display:flex;align-items:center;justify-content:center;width:30px;height:30px;color:#fff;transition:color 0.3s;}
footer .footer-social a:hover{color:#fff;}
footer ul{list-style:none;padding:0;margin:10px 0 20px 0;line-height:1.8;}
footer ul li a{color:#fff;text-decoration:none;transition:color 0.3s;}
footer ul li a:hover{color:#fff;}
footer .footer-bottom{margin-top:30px;border-top:1px solid rgba(255,255,255,0.3);padding-top:15px;font-size:14px;text-align:left;}
footer .footer-bottom a{color:#fff;text-decoration:none;margin:0 8px;}
@media screen and (max-width:768px){footer .footer-container{flex-direction:column;gap:25px;}}
/* Hamburger */
.menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; }
.menu-toggle div { width: 30px; height: 3px; background: #fff; transition: 0.3s; }
/* Nav general */
header nav { flex: 1; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; transition: max-height 0.3s ease; }
/* Mobil */
@media screen and (max-width:768px){
  .menu-toggle { display: flex; }
  #mainNav { flex-direction: column; max-height: 0; overflow: hidden; width: 100%; gap: 10px; }
  #mainNav.mobile-open { max-height: 500px; }
}

/* stiluri pentru mesaj mic de confirmare (optional) */
.toast {
  position: fixed;
  right: 20px;
  bottom: 20px;
  background: #222;
  color: #fff;
  padding: 10px 14px;
  border-radius: 8px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.35);
  display: none;
  z-index: 9999;
}
</style>
</head>
<body>
<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <nav id="mainNav" style="display:flex;gap:15px;flex-wrap:wrap;justify-content:center;flex:1;">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php" class="active">Cosul Meu</a>
	  <a href="ContulMeu.php">Contul Meu</a>
    </nav>

    <div class="menu-toggle" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </div>
</header>

<div class="container">
<?php if(empty($cart)): ?>
    <p>Coșul tău este gol.</p>
<?php else: ?>
<table id="cartTable">
<tr>
<th>Imagine</th>
<th>Produs</th>
<th>Preț</th>
<th>Cantitate</th>
<th>Subtotal</th>
<th>Acțiune</th>
</tr>

<?php foreach($cart as $productKey => $item):
    $price = (float) str_replace(',', '.', $item['price']);
    $subtotal = $price * $item['qty'];
?>
<tr data-key="<?= htmlspecialchars($productKey) ?>">
<td><img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="100"></td>
<td><?= htmlspecialchars($item['name']) ?></td>
<td><?= formatPrice($price) ?></td>
<td>
    <!-- formular separat pentru fiecare rand (update non-AJAX) -->
    <form method="post" style="display:inline-block;">
        <input type="number" name="qty" value="<?= (int)$item['qty'] ?>" min="1" class="qty">
        <input type="hidden" name="product_key" value="<?= htmlspecialchars($productKey) ?>">
        <button type="submit" name="update_qty">Update</button>
    </form>
</td>
<td class="subtotal"><?= formatPrice($subtotal) ?></td>
<td>
    <!-- link AJAX pentru stergere (fara refresh) -->
    <a href="CosulMeu.php?remove=<?= urlencode($productKey) ?>" class="delete-ajax" data-key="<?= htmlspecialchars($productKey) ?>">Șterge</a>
</td>
</tr>
<?php endforeach; ?>

</table>

<form method="post" style="text-align:right;">
    <p class="total">Total produse: <span id="totalAmount"><?= formatPrice($total) ?></span></p>
    <button type="submit" name="continue">Continuă Comanda</button>
</form>
<?php endif; ?>
</div>

<!-- toast optional -->
<div id="toast" class="toast" aria-hidden="true"></div>

<footer id="footer">
  <div class="footer-container">
    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image3.png" alt="Sherghei Covoare">
      </div>
      <p>Primul an prin care aducem covoare traditionale si moderne in casa ta.</p>
      <div class="footer-social">
        <a href="https://www.facebook.com/maestro.fortunato/" target="_blank" title="Facebook">FB</a>
        <a href="https://www.instagram.com/hariharago?igsh=MXd5dHd5ZzY2ZnBpaw==" target="_blank" title="Instagram">IG</a>
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
        <li><a href="#">Vanzare covoare</a></li>
        <li><a href="#">Curatare profesionala</a></li>
        <li><a href="#">Restaurare covoare</a></li>
        <li><a href="#">Consultanta gratuita</a></li>
        <li><a href="#">Livrare la domiciliu</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <strong>Contact</strong>
      <p>Adresa, Craiova,Dolj</p>
      <p>Telefon, 0764.049.235</p>
      <p>Email, office@magazinpsy.ro</p>
      <p>Program, Luni Sambata 9 18</p>
    </div>

  </div>

  <div class="footer-bottom">
    <p>© 2024 Sherghei Covoare. Toate drepturile rezervate.</p>
    <a href="#">Termeni si conditii</a> |
    <a href="#">Politica de confidentialitate</a>
  </div>
</footer>

<script>
// Hamburger mobil
function toggleMenu() {
  const nav = document.getElementById("mainNav");
  nav.classList.toggle("mobile-open");
  if (window.innerWidth <= 768) {
    const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
    const allLinks = Array.from(nav.querySelectorAll('a'));
    allLinks.forEach(a => {
      if (allowedLinks.includes(a.getAttribute('href'))) a.style.display = "block";
      else a.style.display = "none";
    });
  }
}
window.toggleMenu = toggleMenu;

// Dark mode
function toggleDarkMode(){
  document.body.classList.toggle('dark-mode');
  document.querySelector('header').classList.toggle('dark-mode');
  document.querySelector('footer').classList.toggle('dark-mode');
}
window.toggleDarkMode = toggleDarkMode;

// =============================
// AJAX: stergere produs fara refresh
// =============================
document.addEventListener('click', function(e){
  var el = e.target;
  if (!el) return;
  // gasim elementul cu clasa delete-ajax (sau copilul)
  var a = el.closest && el.closest('.delete-ajax');
  if (!a) return;
  e.preventDefault();

  var key = a.getAttribute('data-key');
  if (!key) return;

  // creare formData
  var fd = new FormData();
  fd.append('ajax_action', 'remove');
  fd.append('product_key', key);

  fetch(location.href, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  }).then(function(resp){
    if (!resp.ok) throw new Error('network');
    return resp.json();
  }).then(function(json){
    if (json && json.success) {
      // eliminam randul din tabel
      var row = document.querySelector('tr[data-key="'+CSS.escape(key)+'"]');
      if (row) row.parentNode.removeChild(row);

      // actualizam totalul afisat
      var t1 = document.getElementById('totalAmount');
      var t2 = document.getElementById('totalAmount2');
      if (t1) t1.textContent = json.total_formatted;
      if (t2) t2.textContent = json.total_formatted;

      // daca nu mai sunt randuri, afisam text gol
      var anyRow = document.querySelector('#cartTable tr[data-key]');
      if (!anyRow) {
        document.querySelector('.container').innerHTML = '<p>Coșul tău este gol.</p>';
      }

      // optional: arata un mesaj scurt (fara console)
      var toast = document.getElementById('toast');
      if (toast) {
        toast.textContent = 'Produs sters din cos';
        toast.style.display = 'block';
        setTimeout(function(){ toast.style.display = 'none'; }, 1500);
      }
    } else {
      // eroare — poti afisa un mesaj minimal
      alert('A aparut o eroare. Incearca din nou.');
    }
  }).catch(function(){
    alert('A aparut o eroare de retea. Verifica conexiunea.');
  });

});
</script>
</body>
</html>