<?php
session_start();

/* ================= LOGOUT (PRIMUL) ================= */
if (isset($_GET['logout'])) {
    session_unset();        // sterge toate variabilele de sesiune
    session_destroy();      // distruge sesiunea
    header("Location: Login.php");
    exit;
}

/* ================= VERIFICARE LOGIN ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit;
}

/* ================= CONEXIUNE BD ================= */
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$dbUser = 'if0_40665152';
$dbPass = '7u72iuIGVg';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Eroare conexiune DB: " . $e->getMessage());
}

/* ================= DATE UTILIZATOR LOGAT ================= */
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

/* Protectie XSS */
$username = htmlspecialchars($user['username'] ?? '');
$email    = htmlspecialchars($user['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Contul Meu</title>
<style>
/* ================= GENERAL ================= */
body {
  margin: 0;
  font-family: 'Roboto', sans-serif;
  background: #fff;
  color: #333;
}

/* ================= NAV ================= */
nav {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin: 20px 0;
  flex-wrap: wrap;
  position: relative;
  transition: max-height 0.5s;
}

nav a {
  text-decoration: none;
  padding: 10px 18px;
  background: #fff;
  color: #b5651d;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
}

nav a.active {
  background: #8b4315;
  color: #fff;
}

nav a:hover {
  background: #b5651d;
  color: #fff;
  transform: translateY(-2px);
}

/* ================= HERO ================= */
.hero {
  position: relative;
  text-align: center;
  padding: 120px 20px;
  background: url('hero-bg.jpg') center/cover no-repeat;
  color: #fff;
  overflow: hidden;
  transition: transform 0.3s;
}

.hero::before {
  content: "";
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.4);
}

.hero h2, .hero p, .hero .btn {
  position: relative;
  animation: fadeUp 1s ease forwards;
}

.hero .btn {
  margin: 10px;
  padding: 14px 28px;
  background: #b5651d;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  display: inline-block;
  font-weight: 500;
  transition: 0.3s;
}

.hero .btn:hover {
  transform: translateY(-3px) scale(1.05);
}

/* ================= STATS ================= */
.stats {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin: 50px 0;
  text-align: center;
  flex-wrap: wrap;
}

.stats div {
  background: #fff;
  padding: 25px;
  border-radius: 16px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  flex: 1 1 150px;
  margin: 10px;
  transition: transform 0.3s, box-shadow 0.3s;
}

.stats div:hover {
  transform: translateY(-8px) scale(1.03);
  box-shadow: 0 12px 28px rgba(0,0,0,0.25);
}

/* ================= CARDS ================= */
.card, .account-card {
  background: #fff;
  border-radius: 16px;
  padding: 25px;
  margin: 20px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  transition: transform 0.3s, box-shadow 0.3s, background 0.3s, opacity 0.5s;
  opacity: 0;
  transform: translateY(20px);
}

.card.visible, .account-card.visible {
  opacity: 1;
  transform: translateY(0);
}

.card:hover, .account-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 45px rgba(0,0,0,0.22);
}

.card:hover h3, .card:hover p, .account-card:hover h3, .account-card:hover p {
  color: #b5651d;
}

/* ================= HEADER ================= */
header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: #8C92AC;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  padding: 15px 30px;
}

header > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

/* Logo */
header .logo {
  display: flex;
  align-items: center;
  gap: 15px;
}

header .logo img {
  height: 50px;
}

/* Nav */
header nav {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  flex: 1;
  overflow: visible;
}

header nav a {
  text-decoration: none;
  padding: 10px 18px;
  background: #fff;
  color: #b5651d;
  border-radius: 8px;
  font-weight: 500;
  transition: 0.3s;
}

header nav a:hover, header nav a.active {
  background: #8b4315;
  color: #fff;
}

/* Dropdown */
header nav .dropdown { position: relative; }
header nav .dropdown-content {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #fff;
  min-width: 180px;
  border-radius: 10px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.3);
  display: none;
  flex-direction: column;
  z-index: 2000;
}

header nav .dropdown-content a {
  background: transparent;
  color: #b5651d;
  padding: 12px 16px;
}

header nav .dropdown-content a:hover {
  background: #f5e6d8;
  color: #8b4315;
}

header nav .dropdown:hover .dropdown-content {
  display: flex;
}

header nav .dropdown-content.open { display: flex; }

/* ================= HEADER RIGHT ================= */
.header-contact {
  display: flex;
  align-items: center;
  gap: 15px;
  position: relative;
  z-index: 2000;
}

/* Dark Mode Toggle */
.dark-toggle {
  cursor: pointer;
  background: #fff;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  font-size: 20px;
  color: #b5651d;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 6000;
  transition: transform 0.2s;
}

.dark-toggle:hover { transform: scale(1.1); }

/* ================= HAMBURGER ================= */
.menu-toggle {
  display: none;
  flex-direction: column;
  cursor: pointer;
}

.menu-toggle div {
  width: 30px;
  height: 3px;
  background: #fff;
  margin: 5px 0;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {
  header nav { display: none; flex-direction: column; width: 100%; margin-top: 10px; gap: 10px; }
  header nav.mobile-open { display: flex; }
  .menu-toggle { display: flex; }
  .header-contact { width: 100%; justify-content: center; margin-top: 10px; }
  .dark-toggle { position: relative; top: auto; right: auto; }
}

/* ================= DARK MODE ================= */
body.dark-mode {
  background: #111;
  color: #eee;
}

body.dark-mode header { background: #222; }
body.dark-mode header nav a { background: #333; color: #eee; }
body.dark-mode header nav a:hover,
body.dark-mode header nav a.active { background: #555; color: #fff; }
body.dark-mode .account-card { background: #1e1e1e; }
body.dark-mode .account-card p { color: #ddd; }
body.dark-mode footer { background: #222; }

/* ================= FOOTER ================= */
footer {
  background: #8C92AC;
  color: #fff;
  padding: 50px 20px;
  transition: background 0.3s, color 0.3s;
}

footer.dark-mode { background: #222; }

footer .footer-container {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  justify-content: flex-start;
  align-items: flex-start;
}

footer .footer-col { flex: 1; min-width: 200px; }
footer .footer-logo img { height: 50px; margin-bottom: 15px; }
footer .footer-social { display: flex; gap: 10px; margin-top: 5px; }
footer .footer-social a { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; color: #fff; transition: color 0.3s; }
footer .footer-social a:hover { color: #fff; }
footer ul { list-style: none; padding: 0; margin: 10px 0 20px 0; line-height: 1.8; }
footer ul li a { color: #fff; text-decoration: none; transition: color 0.3s; }
footer ul li a:hover { color: #fff; }
footer p, footer strong, footer blockquote, footer span, footer li { color: #fff !important; }
footer .footer-bottom { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 15px; font-size: 14px; text-align: left; }
footer .footer-bottom a { color: #fff; text-decoration: none; margin: 0 8px; }
footer .footer-bottom a:hover { color: #fff; }

@media screen and (max-width: 768px) {
  footer .footer-container { flex-direction: column; gap: 25px; }
}
/* ================= DN Badge ================= */
/* Badge DN */
.dn-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: #ffcc00;
    color: #fff;
    font-weight: 700;
    border-radius: 50%;
    font-size: 20px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
}
.dn-badge:hover {
    background: linear-gradient(135deg, #ffd633, #ffb900);
    transform: scale(1.4) rotate(-10deg);
    box-shadow: 0 12px 20px rgba(0,0,0,0.5);
}

/* Cont container */
.myaccount {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
    font-family: 'Roboto', sans-serif;
}

/* Header cont */
.account-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 30px;
    gap: 15px;
}
.account-info p {
    margin: 2px 0;
    font-weight: 500;
}
.account-info .fullname { font-weight: 700; font-size: 18px; }
.account-header .btn-primary {
    background: #b5651d;
    color: #fff;
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}
.account-header .btn-primary:hover {
    background: #8b4315;
}

/* Carduri */
.account-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}
.card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 35px rgba(0,0,0,0.2);
}
.card h3 { margin-top:0; margin-bottom:10px; }
.card p { margin:5px 0; }

/* Butoane secundare */
.btn-secondary {
    display: inline-block;
    background: #b5651d;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    margin-top: 5px;
    transition: 0.3s;
}
.btn-secondary:hover {
    background: #8b4315;
    transform: scale(1.05);
}

  .account-footer a {
      display: inline-block;
      background: #b5651d;
      color: #fff;
      padding: 12px 28px;
      border-radius: 8px;
      font-weight: 500;
      text-decoration: none;
      transition: 0.3s, transform 0.2s;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  .account-footer a:hover {
      background: #8b4315;
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  }
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

    <!-- Logo stanga -->
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <!-- Navigatie -->
    <nav id="mainNav" style="display:flex;align-items:center;gap:15px;">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php">Cosul Meu</a>


      <?php if(isset($_SESSION['user_id'])): 
        $username = htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']); ?>
        <div class="dropdown">
          <a href="ContulMeu.php" class="active"><?= $username ?> ▾</a>
          <div class="dropdown-content">
            <a href="ContulMeu.php">Profil</a>
            <a href="#">Setări</a>
            <a href="ContulMeu.php?logout=1">Deconectare</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php">Contul Meu</a>
      <?php endif; ?>
    </nav>

        <!-- Hamburger mobil -->
        <div class="menu-toggle" onclick="toggleMenu()">
          <div></div>
          <div></div>
          <div></div>
        </div>

  </div>
</header>
<div class="container myaccount">

    <!-- Header cont -->
    <div class="account-header">
        <a href="#" class="dn-badge">DN</a>
        <div class="account-info">
            <p class="alias">Alias: adauga alias</p>
            <p class="fullname">Deaconu Nicolae Alexandru</p>
            <p class="email"><?= $email ?></p>
            <p class="phone">0771342916</p>
            <p class="loyalty">Mulțumim că ești clientul nostru de 6 ani</p>
        </div>
        <a href="#" class="btn-primary">Administrează datele tale</a>
    </div>

    <!-- Carduri principale -->
    <div class="account-cards">
        <div class="card">
            <h3>Covor Genius</h3>
            <p>Abonamentul tău la livrare gratuită și oferte exclusive</p>
            <a class="btn-secondary" href="#">Abonează-te acum</a>
        </div>

        <div class="card">
            <h3>Activitatea mea</h3>
            <p>22 de comenzi plasate</p>
            <a class="btn-secondary" href="#">Vezi istoricul de comenzi</a>
            <p>86 produse favorite</p>
            <a class="btn-secondary" href="#">Vezi produse favorite</a>
            <p>0 review-uri adăugate</p>
            <a class="btn-secondary" href="#">Vezi review-uri</a>
        </div>

        <div class="card">
            <h3>Punct de ridicare favorit</h3>
            <a class="btn-secondary" href="#">Alege punct de ridicare preferat</a>
        </div>

        <div class="card">
            <h3>Adresele mele</h3>
            <p>4 adrese salvate</p>
            <p>Adresa preferată</p>
            <p>Deaconu Nicolae Alexandru</p>
            <p>0771342916</p>
            <p>247155, Mileşti (Făureşti), Vâlcea</p>
            <a class="btn-secondary" href="#">Administrează adresele de livrare</a>
        </div>

        <div class="card">
            <h3>Firmele mele</h3>
            <p>Cumperi pentru firma ta? Nicio problemă! Adaugă aici datele care vor apărea pe factura comenzii tale</p>
            <a class="btn-secondary" href="#">Adaugă companie</a>
        </div>

        <div class="card">
            <h3>Cardurile mele</h3>
            <p>Adaugă un card pentru a plăti comenzile viitoare fără să reintroduci toate datele de plată.</p>
            <a class="btn-secondary" href="#">Administrează carduri</a>
        </div>

        <div class="card">
            <h3>Newsletter</h3>
            <p>Te abonezi la newsletterul Sherghei iar noi avem grijă să îți trimitem oferte și reduceri cu timp și cantități limitate</p>
            <p>* Prin abonarea la newsletter confirm că am peste 18 ani.</p>
            <a class="btn-secondary" href="#">Mă abonez</a>
        </div>
    </div>

    <!-- Butoane generale -->
    <div class="account-footer">
        <a class="btn-primary" href="Acasa.php">Pagina principală</a>
        <a class="btn-primary" href="ContulMeu.php?logout=1">Deconectare</a>
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
// Toggle dropdown DOAR pe mobile
// =============================
document.querySelectorAll('.dropdown > a').forEach(link => {
  link.addEventListener('click', e => {
    if (window.innerWidth > 768) return; // desktop: hover din CSS

    e.preventDefault(); // nu navigam catre ContulMeu.php

    const menu = link.nextElementSibling;
    if (!menu) return;

    // Inchide alte dropdown-uri
    document.querySelectorAll('.dropdown-content.open')
      .forEach(m => m !== menu && m.classList.remove('open'));

    // Toggle current
    menu.classList.toggle('open');
  });
});

// Inchide dropdown daca dai click in afara (mobile)
document.addEventListener('click', e => {
  document.querySelectorAll('.dropdown-content.open').forEach(menu => {
    if (!menu.parentElement.contains(e.target)) {
      menu.classList.remove('open');
    }
  });
});

// =============================
// Animatie carduri la intrare
// =============================
const cards = document.querySelectorAll('.card');
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if(e.isIntersecting){
      e.target.style.opacity = 1;
      e.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.15 });

cards.forEach(c => {
  c.style.opacity = 0;
  c.style.transform = 'translateY(25px)';
  observer.observe(c);
});

// =============================
// Dark Mode
// =============================
function toggleDarkMode(){
  document.body.classList.toggle('dark-mode');
  const footer = document.getElementById('footer');
  if(footer) footer.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? '1' : '0');
}

document.addEventListener('DOMContentLoaded', () => {
  if(localStorage.getItem('darkMode')==='1'){
    document.body.classList.add('dark-mode');
    const footer=document.getElementById('footer');
    if(footer) footer.classList.add('dark-mode');
  }
});

// =============================
// Hamburger mobil
// =============================
function toggleMenu() {
  const nav = document.getElementById('mainNav');
  nav.classList.toggle('mobile-open');

  if(window.innerWidth <= 768){
    // Linkuri permise pe mobil
    const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
    const allLinks = Array.from(nav.querySelectorAll('a'));
    allLinks.forEach(a => {
      if(allowedLinks.includes(a.getAttribute('href'))){
        a.style.display = "block";
      } else {
        a.style.display = "none";
      }
    });
  }
}

// Reafișare linkuri la resize
window.addEventListener('resize', () => {
  const nav = document.getElementById('mainNav');
  const allLinks = Array.from(nav.querySelectorAll('a'));
  if(window.innerWidth > 768){
    allLinks.forEach(a => a.style.display = "inline-block");
  } else {
    if(!nav.classList.contains('mobile-open')){
      allLinks.forEach(a => a.style.display = "none");
    } else {
      const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
      allLinks.forEach(a => a.style.display = allowedLinks.includes(a.getAttribute('href')) ? 'block' : 'none');
    }
  }
});

window.toggleMenu = toggleMenu;
window.toggleDarkMode = toggleDarkMode;
</script>


</div>
</body>
</html>
