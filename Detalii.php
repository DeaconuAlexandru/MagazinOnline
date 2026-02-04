<?php
session_start();

/* =========================
   CONEXIUNE BAZA DE DATE
   ========================= */
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}

/* =========================
   PRELUARE PRODUS
   ========================= */
if (!isset($_GET['item']) || !is_numeric($_GET['item'])) {
    die("<h2 style='text-align:center;margin-top:40px;'>Produsul nu a fost gasit.</h2>");
}

$itemId = (int) $_GET['item'];

// Folosim coloana 'id', nu 'idPrincipal'
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $itemId]);
$item = $stmt->fetch();

if (!$item) {
    die("<h2 style='text-align:center;margin-top:40px;'>Produsul nu a fost gasit.</h2>");
}

/* =========================
   ADAUGARE IN COS
   ========================= */
if (isset($_POST['add_to_cart'])) {
    $qty = max(1, (int) $_POST['qty']);

    $cartItem = [
        'id'    => $item['id'],
        'name'  => $item['name'],
        'price' => $item['price'],
        'qty'   => $qty,
        'img'   => $item['img'] ?: 'default.png' // fallback imagine
    ];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$item['id']])) {
        $_SESSION['cart'][$item['id']]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$item['id']] = $cartItem;
    }

    header("Location: CosulMeu.php");
    exit;
}

/* =========================
   FORMATARE PRET
   ========================= */
function formatPrice($price): string {
    return number_format((float)$price, 2, ',', '') . ' Lei';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title><?= $item['name'] ?> - Detalii</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body{
    margin:0;
    font-family:'Roboto',sans-serif;
    background:#fdf6f0;
    color:#222;
}
/* Header */
header {
    background: #8C92AC;
    color: #fff;
    padding: 15px;
    position: sticky; /* sau fixed dacă vrei să rămână mereu vizibil */
    top: 0;
    width: 100%;
    z-index: 1000; /* asigură că stă deasupra altor elemente */
}

header h1 {
    margin: 0;
    font-size: 28px;
    display: inline-block;
}

header nav {
    display: inline-block;
    margin-left: 20px;
}

header nav a {
    margin: 0 10px;
    text-decoration: none;
    color: #fff;
    padding: 8px 15px;
    border-radius: 8px;
}

header nav a.active {
    background: #8b4315;
}
.header-contact .btn{
    background:#fff;
    color:#b5651d;
    padding:12px 25px;
    border-radius:8px;
    text-decoration:none;
    font-weight:500;
    position:relative;
    transition:0.3s;
}
.header-contact .btn:hover{
    background:#8b4315;
    color:#fff;
    transform:translateY(-2px);
}

/* Footer General */
footer {
  background: #8C92AC; /* portocaliu când nu e dark mode */
  color: #fff; /* tot textul alb */
  padding: 50px 20px;
  font-family: 'Roboto', sans-serif;
  transition: background 0.3s, color 0.3s;
}

/* Footer Dark Mode */
footer.dark-mode {
  background: #222; /* întunecat când e dark mode */
}

/* Container flex */
footer .footer-container {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  justify-content: flex-start;
  align-items: flex-start;
}

/* Coloane footer */
footer .footer-col {
  flex: 1;
  min-width: 200px;
}

/* Logo + descriere */
footer .footer-logo img {
  height: 50px;
  width: auto;
  display: block;
  margin-bottom: 15px;
}

/* Social icons */
footer .footer-social {
  display: flex;
  gap: 10px;
  margin-top: 5px;
}

footer .footer-social a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  color: #fff; /* alb */
  transition: color 0.3s;
}

footer .footer-social a:hover {
  color: #fff; /* hover alb */
}

/* Linkuri */
footer ul {
  list-style: none;
  padding: 0;
  margin: 10px 0 20px 0;
  line-height: 1.8;
}

footer ul li a {
  color: #fff; /* alb */
  text-decoration: none;
  transition: color 0.3s;
}

footer ul li a:hover {
  color: #fff; /* hover alb */
}

/* Text simplu în footer */
footer p, footer strong, footer blockquote, footer span, footer li {
  color: #fff !important; /* forțare alb pentru tot textul */
}

/* Copyright */
footer .footer-bottom {
  margin-top: 30px;
  border-top: 1px solid rgba(255, 255, 255, 0.3);
  padding-top: 15px;
  font-size: 14px;
  text-align: left;
}

footer .footer-bottom a {
  color: #fff; /* alb */
  text-decoration: none;
  margin: 0 8px;
}

footer .footer-bottom a:hover {
  color: #fff; /* hover alb */
}

/* Responsiv */
@media screen and (max-width: 768px) {
  footer .footer-container {
    flex-direction: column;
    gap: 25px;
  }
}
nav a{
    margin:0 10px;
    text-decoration:none;
    color:#fff;
    padding:8px 15px;
    border-radius:8px;
}
nav a.active{
    background:#8b4315;
    color:#fff;
}
.page-wrapper{
    max-width:1200px;
    margin:50px auto;
    padding:20px;
    display:flex;
    flex-wrap:wrap;
    gap:40px;
    justify-content:center;
    align-items:flex-start;
}
.product-image{
    flex:1;
    min-width:350px;
}
.product-image img{
    width:100%;
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.2);
}
.product-info{
    flex:1.2;
    min-width:350px;
    background:#fff;
    padding:30px;
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.15);
}
.product-info h1{
    margin:0;
    font-size:32px;
    font-family:'Montserrat',sans-serif;
}
.product-info .type{
    margin-top:5px;
    font-weight:600;
    color:#b5651d;
}
.price-box{
    margin:20px 0;
}
.price-new{
    font-size:28px;
    font-weight:700;
    color:#b5651d;
}
.price-old{
    margin-left:10px;
    color:#777;
    text-decoration:line-through;
}
.product-info p{
    line-height:1.6;
    margin:10px 0;
}

/* CASUTE INFORMATII PRODUS */
.info-cards{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    margin-top:25px;
}
.info-card{
    background:#f9f3ee;
    padding:15px 20px;
    border-radius:12px;
    border:1px solid rgba(0,0,0,0.1);
    transition:0.3s;
}
.info-card h4{
    margin:0 0 5px 0;
    color:#b5651d;
    font-size:16px;
}
.info-card p{
    margin:0;
    font-weight:500;
}

/* BENEFICII SIMPLE */
.benefits-simple{
    margin-top:25px;
}
.benefits-simple ul{
    list-style:none;
    padding:0;
}
.benefits-simple li{
    margin:8px 0;
    font-weight:500;
}

/* CASUTE COMANDA / OFERTA */
.action-cards{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    margin-top:25px;
}
.action-card{
    background:#b5651d;
    color:#fff;
    text-align:center;
    padding:18px;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
    transition:0.3s;
    text-decoration:none;
}
.action-card:hover{
    background:#8b4315;
    transform:translateY(-3px);
}

/* Buton inapoi */
.back-btn{
    display:inline-block;
    margin-top:35px;
    padding:14px 28px;
    background:#b5651d;
    color:#fff;
    text-decoration:none;
    font-weight:600;
    font-size:16px;
    border-radius:10px;
    letter-spacing:0.5px;
    transition:0.3s;
}
.back-btn:hover{
    background:#8b4315;
    transform:translateY(-2px);
}
@media screen and (max-width:768px){
    .page-wrapper{
        flex-direction:column;
    }
    .info-cards, .action-cards{
        grid-template-columns:1fr;
    }
}
/* Toggle */
.dark-toggle {
  cursor: pointer;
  padding: 10px;
  border-radius: 8px;
  background: #fff;
  color: #b5651d;
  font-weight: 600;
  transition: 0.3s;
}
.dark-toggle:hover {
  background: #b5651d;
  color: #fff;
}

/* BODY + TEXT */
body.dark-mode {
  background: #111;
  color: #fdf6f0;
}
body.dark-mode p,
body.dark-mode h1,
body.dark-mode h2,
body.dark-mode h3,
body.dark-mode h4,
body.dark-mode li {
  color: #fdf6f0;
}

/* HEADER */
body.dark-mode header {
  background: #222;
  color: #fdf6f0;
}
body.dark-mode nav a {
  background: #333;
  color: #fdf6f0;
}
body.dark-mode nav a:hover,
body.dark-mode nav a.active {
  background: #8b4315;
  color: #fff;
}

/* CARDURI / BOXURI / PANOURI */
body.dark-mode .product-info,
body.dark-mode .info-card,
body.dark-mode .action-card,
body.dark-mode .benefits-simple li {
  background: #222;
  color: #fdf6f0;
  box-shadow: 0 0 15px rgba(255,255,255,0.05);
}

/* PREȚ */
body.dark-mode .price-new {
  color: #e09c50;
}
body.dark-mode .price-old {
  color: #aaa;
}

/* BUTOANE */
body.dark-mode .action-card,
body.dark-mode .back-btn {
  background: #8b4315;
  color: #fff;
}
body.dark-mode .action-card:hover,
body.dark-mode .back-btn:hover {
  background: #b5651d;
}

/* FOOTER */
body.dark-mode footer {
  background: #222;
  color: #fdf6f0;
}
body.dark-mode footer a,
body.dark-mode footer p,
body.dark-mode footer li,
body.dark-mode footer strong,
body.dark-mode footer span {
  color: #fdf6f0 !important;
}

/* Harta (dacă apare) */
body.dark-mode iframe {
  filter: invert(90%) contrast(90%);
}

</style>
</head>
<body>

<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <div class="menu-toggle" onclick="toggleMenu()">
      <div></div>
      <div></div>
      <div></div>
    </div>
  </div>
</header>
<div class="page-wrapper">

    <div class="product-image">
        <img src="images/<?= htmlspecialchars($item['img']) ?>"
             alt="<?= htmlspecialchars($item['name']) ?>">
    </div>

    <div class="product-info">
        <h1><?= htmlspecialchars($item['name']) ?></h1>

        <div class="price-box">
            <span class="price-new"><?= formatPrice($item['price']) ?></span>
        </div>

        <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>

        <!-- INFORMATII PRODUS (DOAR CE EXISTA IN DB) -->
        <div class="info-cards">
            <div class="info-card">
                <h4>Disponibilitate</h4>
                <p><?= ($item['stock'] > 0 ? 'In stoc' : 'Stoc epuizat') ?></p>
            </div>
        </div>

        <!-- BENEFICII -->
        <div class="benefits-simple">
            <ul>
                <li>Livrare gratuita</li>
                <li>Garantie 2 ani</li>
                <li>Retur in 30 zile</li>
            </ul>
        </div>

        <!-- ACTIUNI -->
        <div class="action-cards">
            <a href="tel:+40123456789" class="action-card">Comanda telefonic</a>
            <a href="#" class="action-card">Solicita oferta</a>
        </div>

        <!-- ADAUGARE IN COS -->
        <form method="post" style="margin-top:20px; display:flex; gap:10px; align-items:center;">
            <input type="number"
                   name="qty"
                   value="1"
                   min="1"
                   style="width:60px; padding:8px; border-radius:6px; border:1px solid #ccc;">

            <button type="submit"
                    name="add_to_cart"
                    class="action-card"
                    style="flex:1;">
                Adauga in cos
            </button>
        </form>

        <a href="Colectie.php" class="back-btn">Inapoi la colectie</a>
    </div>

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
function toggleDarkMode(){
    document.body.classList.toggle('dark-mode');
    document.querySelector('header').classList.toggle('dark-mode');
    document.querySelector('footer').classList.toggle('dark-mode');
}
</script>
</body>
</html>
