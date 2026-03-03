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
// Conexiune DB corectata
$dbHost = 'localhost';
$dbName = 'magazi15_ShergeiCovoare';
$dbUser = 'magazi15_Alex';
$dbPass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset={$charset}",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // Pentru debugging: permite sa vezi eroarea. In productie logheaza si afiseaza un mesaj generic.
    exit("Eroare DB: " . $e->getMessage());
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
<meta name="viewport" content="width=device-width, initial-scale=1">
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
    width: 28px;
    height: 3px;
    background: #fff;
    border-radius: 3px;
    transition: transform 0.22s ease, opacity 0.18s ease;
  }

/* ================= MOBILE ================= */
@media (max-width: 768px) {
  header nav { display: none; flex-direction: column; width: 100%; margin-top: 10px; gap: 10px; }
  header nav.mobile-open { display: flex; }
  .menu-toggle { display: flex; }
  .header-contact { width: 100%; justify-content: center; margin-top: 10px; }
  .dark-toggle { position: relative; top: auto; right: auto; }
}
 /* când meniul e deschis prin JS (clasa mobile-open) */
  header nav.mobile-open {
    display: flex;
    max-height: 1200px; /* suficient pentru câteva linkuri */
    padding-bottom: 12px;
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

@media screen and (max-width:768px){
  #mainNav { display: none; flex-direction: column; width: 100%; margin-top: 10px; gap: 8px; }
  #mainNav.mobile-open { display: flex; }
  .menu-toggle { display: flex; }
}
</style>
</head>
<body>
<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <!-- Navigatie -->
    <nav id="mainNav" aria-label="Meniu principal">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php">Cosul Meu</a>

      <?php if(isset($_SESSION['user_id'])):
        $displayName = htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']); ?>
        <div class="dropdown">
          <a href="ContulMeu.php" class="active"><?= $displayName ?> ▾</a>
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
    <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul mobil" role="button" tabindex="0">
      <div></div>
      <div></div>
      <div></div>
    </div>

  </div>
</header>
<div class="container myaccount">
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
      <p>Primul an in care aducem covoare traditionale si moderne in casa ta.</p>

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
(function(){
  // init dark mode dacă vrei să păstrezi funcționalitatea existentă
  if (localStorage.getItem('darkMode') === '1') {
    document.body.classList.add('dark-mode');
    const footer = document.getElementById('footer');
    if (footer) footer.classList.add('dark-mode');
  }

  const ALLOWED = new Set(['acasa.php','colectie.php','cosulmeu.php','contulmeu.php']);

  function norm(href){
    if(!href) return '';
    href = href.split('?')[0].split('#')[0].trim();
    // elimină orice cale, rămâne doar numele fișierului
    const parts = href.split('/');
    const file = parts[parts.length-1] || '';
    return decodeURIComponent(file).toLowerCase();
  }

  function updateNavVisibility(){
    const nav = document.getElementById('mainNav');
    if(!nav) return;
    const isMobile = window.innerWidth <= 768;
    const open = nav.classList.contains('mobile-open');

    // parcurgem doar copiii imediati, ca in markup
    Array.from(nav.children).forEach(child => {
      // link direct
      if(child.tagName === 'A'){
        const href = child.getAttribute('href') || '';
        const name = norm(href);
        if(!isMobile){
          child.style.display = '';
        } else {
          // mobil: dacă meniul închis ascundem tot
          if(!open) child.style.display = 'none';
          else child.style.display = ALLOWED.has(name) ? 'block' : 'none';
        }
        return;
      }

      // dropdown
      if(child.classList && child.classList.contains('dropdown')){
        const parentA = child.querySelector(':scope > a');
        const parentHref = parentA ? (parentA.getAttribute('href')||'') : '';
        const parentName = norm(parentHref);

        if(!isMobile){
          child.style.display = '';
          if(parentA) parentA.style.display = '';
          const dc = child.querySelector('.dropdown-content');
          if(dc) dc.style.display = '';
        } else {
          if(!open){
            child.style.display = 'none';
            if(parentA) parentA.style.display = 'none';
            const dc = child.querySelector('.dropdown-content');
            if(dc) dc.style.display = 'none';
          } else {
            // afișează părinte doar dacă permis, dar ascunde conținutul dropdown
            if(ALLOWED.has(parentName)){
              child.style.display = '';
              if(parentA) parentA.style.display = 'block';
            } else {
              child.style.display = 'none';
              if(parentA) parentA.style.display = 'none';
            }
            const dc = child.querySelector('.dropdown-content');
            if(dc) dc.style.display = 'none';
          }
        }
        return;
      }

      // alte elemente
      if(!isMobile) child.style.display = '';
      else child.style.display = nav.classList.contains('mobile-open') ? '' : 'none';
    });
  }

  // toggle meniu mobil, apelată din onclick HTML
  window.toggleMenu = function(){
    const nav = document.getElementById('mainNav');
    if(!nav) return;
    nav.classList.toggle('mobile-open');
    updateNavVisibility();
  };

  // închidere la click în afara meniului pe mobil
  document.addEventListener('click', (e) => {
    const nav = document.getElementById('mainNav');
    const toggle = document.querySelector('.menu-toggle');
    if(!nav) return;
    if(window.innerWidth > 768) return;
    if(nav.classList.contains('mobile-open') && !nav.contains(e.target) && !(toggle && toggle.contains(e.target))){
      nav.classList.remove('mobile-open');
      updateNavVisibility();
    }
  });

  // resize / orientation
  let rt;
  window.addEventListener('resize', () => {
    clearTimeout(rt);
    rt = setTimeout(updateNavVisibility, 70);
  });
  window.addEventListener('orientationchange', () => setTimeout(updateNavVisibility, 120));

  // run la load
  document.addEventListener('DOMContentLoaded', () => {
    // forțează ascunderea linkurilor pe mobil până se deschide meniul
    updateNavVisibility();
  });

})();
</script>
</div>
</body>
</html>
