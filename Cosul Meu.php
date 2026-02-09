<?php
session_start();
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) $lang = $_GET['lang'];

// Preluăm produsele din colecție
$collection = $_SESSION['collection'] ?? [];

// Coșul propriu-zis
$cart = $_SESSION['cart'] ?? [];

// Funcție pentru format preț
function formatPrice($price): string {
    return number_format((float)$price, 2, ',', '') . ' Lei';
}

// Calcul total
$total = 0;
foreach($cart as $item){
    $price = (float) str_replace(',', '.', $item['price']);
    $total += $price * $item['qty'];
}

// Update cantitate
if(isset($_POST['update_qty'])){
    $img = $_POST['img'];
    $qty = max(1, (int)$_POST['qty']);
    if(isset($cart[$img])){
        $cart[$img]['qty'] = $qty;
        $_SESSION['cart'] = $cart;
    }
    header('Location: CosulMeu.php');
    exit;
}

// Ștergere produs
if(isset($_GET['remove'])){
    $img = $_GET['remove'];
    unset($cart[$img]);
    $_SESSION['cart'] = $cart;
    header('Location: CosulMeu.php');
    exit;
}

// Continua comanda
if(isset($_POST['continue'])){
    $_SESSION['checkout_cart'] = $cart;      // salvăm coșul pentru pagina ContinuaComanda
    $_SESSION['checkout_total'] = $total;    // salvăm totalul pentru pagina ContinuaComanda
    header('Location: ContinuaComanda.php');
    exit;
}

// Livrare selectată
$shipping = $_POST['shipping'] ?? 'Courier';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cosul Meu - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
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
.menu-toggle {
  display: none;
  flex-direction: column;
  cursor: pointer;
  gap: 5px;
}
.menu-toggle div {
  width: 30px;
  height: 3px;
  background: #fff;
  transition: 0.3s;
}

/* Nav general */
header nav {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  transition: max-height 0.3s ease;
}

/* Mobil */
@media screen and (max-width:768px){
  .menu-toggle {
    display: flex;
  }
  #mainNav {
    flex-direction: column;
    max-height: 0;
    overflow: hidden;
    width: 100%;
    gap: 10px;
  }
  #mainNav.mobile-open {
    max-height: 500px; /* ajustezi după câte linkuri ai */
  }
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

        <!-- Hamburger mobil -->
        <div class="menu-toggle" onclick="toggleMenu()">
          <div></div>
          <div></div>
          <div></div>
        </div>


  </div>
</header>

<div class="container">
<?php if(empty($cart)): ?>
    <p>Coșul tău este gol.</p>
<?php else: ?>
<form method="post">
<table>
<tr>
<th>Imagine</th>
<th>Produs</th>
<th>Preț</th>
<th>Cantitate</th>
<th>Subtotal</th>
<th>Acțiune</th>
</tr>

<?php foreach($cart as $name => $item): 
    // Preț corect ca float
    $price = (float) str_replace(',', '.', $item['price']);
    $subtotal = $price * $item['qty'];
?>
<tr>
<td><img src="<?= $item['img'] ?>" alt="<?= $item['name'] ?>" width="100"></td>
<td><?= $item['name'] ?></td>
<td><?= formatPrice($price) ?></td>
<td>
    <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" class="qty">
    <input type="hidden" name="img" value="<?= $name ?>">
    <button type="submit" name="update_qty">Update</button>
</td>
<td><?= formatPrice($subtotal) ?></td>
<td><a href="CosulMeu.php?remove=<?= $name ?>">Șterge</a></td>
</tr>
<?php endforeach; ?>

</table>
<p class="total">Total produse: <?= formatPrice($total) ?></p>
<p class="total">Total de plată (înclusiv livrare): <?= formatPrice($total) ?></p>

<button type="submit" name="continue">Continuă Comanda</button>
</form>
<?php endif; ?>
</div>

<footer id="footer">
  <div class="footer-container">

    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image3.png" alt="Sherghei Covoare">
      </div>
      <p>De peste 20 de ani aduci covoare traditionale si moderne in casa ta.</p>

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
        <li><a href="#">Vanzare covoare</a></li>
        <li><a href="#">Curatare profesionala</a></li>
        <li><a href="#">Restaurare covoare</a></li>
        <li><a href="#">Consultanta gratuita</a></li>
        <li><a href="#">Livrare la domiciliu</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <strong>Contact</strong>
      <p>Adresa, Str. Covoarelor Nr. 12, Bucuresti</p>
      <p>Telefon, +40 123 456 789</p>
      <p>Email, contact@sherghei-covoare.ro</p>
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
// =============================
// Hamburger mobil
// =============================
function toggleMenu() {
  const nav = document.getElementById("mainNav");
  nav.classList.toggle("mobile-open");

  if (window.innerWidth <= 768) {
    // Afișăm doar linkurile permise
    const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
    const allLinks = Array.from(nav.querySelectorAll('a'));
    allLinks.forEach(a => {
      if (allowedLinks.includes(a.getAttribute('href'))) {
        a.style.display = "block";
      } else {
        a.style.display = "none";
      }
    });
  }
}
window.toggleMenu = toggleMenu;

// =============================
// Dark mode
// =============================
function toggleDarkMode(){
  document.body.classList.toggle('dark-mode');
  document.querySelector('header').classList.toggle('dark-mode');
  document.querySelector('footer').classList.toggle('dark-mode');
}
window.toggleDarkMode = toggleDarkMode;

// =============================
// Ajustare la resize (reafișăm linkurile când trecem la desktop)
// =============================
window.addEventListener('resize', () => {
  const nav = document.getElementById("mainNav");
  const allLinks = Array.from(nav.querySelectorAll('a'));
  if (window.innerWidth > 768) {
    allLinks.forEach(a => a.style.display = "inline-block");
  } else {
    // Pe mobil, dacă meniul nu e deschis, ascundem linkurile
    if (!nav.classList.contains('mobile-open')) {
      allLinks.forEach(a => a.style.display = "none");
    } else {
      // Dacă meniul e deschis, afișăm doar linkurile permise
      const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
      allLinks.forEach(a => {
        a.style.display = allowedLinks.includes(a.getAttribute('href')) ? 'block' : 'none';
      });
    }
  }
});
</script>
</body>
</html>
