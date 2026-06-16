<?php
declare(strict_types=1);

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
recordSocialVisit($pdo);
$lang = 'ro';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ro', 'ru'], true)) {
    $lang = $_GET['lang'];
}
$produsPrecompletat = '';
if (!empty($_GET['produs'])) {
    $produsPrecompletat = trim((string)$_GET['produs']);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$txt = [
    'ro' => [
        'page_title' => 'Contactează-ne',
        'subtitle' => 'Suntem aici să te ajutăm. Trimite-ne un mesaj sau vizitează-ne în showroom.',
        'form' => [
            'title' => 'Trimite un Mesaj',
            'name' => 'Nume Complet',
            'phone' => 'Telefon',
            'email' => 'Email',
            'subject' => 'Subiect / Produs',
            'message' => 'Mesaj',
            'btn' => 'Trimite Mesajul'
        ],
        'info' => [
            'address_label' => 'Adresă',
            'address' => 'Craiova, Dolj',
            'phone_label' => 'Telefon',
            'phone' => '0753 508 461',
            'email_label' => 'Email',
            'email' => 'office@magazinpsy.ro',
            'program_label' => 'Program',
            'program' => 'Luni - Sâmbătă: 9:00 - 18:00'
        ],
        'map_src' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2849.123456!2d26.100000!3d44.400000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1234567890abc%3A0xabcdef1234567890!2sStrada%20Covoarelor%2012%2C%20București!5e0!3m2!1sro!2sro!4v1699999999999!5m2!1sro!2sro'
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($txt[$lang]['page_title']) ?> - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*,
*::before,
*::after {
  box-sizing: border-box;
}

html, body {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

body {
  margin: 0;
  font-family: 'Roboto', sans-serif;
  background: #fdf6f0;
  color: #111;
  padding-left: max(0px, env(safe-area-inset-left));
  padding-right: max(0px, env(safe-area-inset-right));
}

img {
  max-width: 100%;
  display: block;
}

a {
  color: inherit;
}

body.dark-mode {
  background: #111;
  color: #fdf6f0;
}

/* Header */
header {
  position: sticky;
  top: 0;
  z-index: 50;
  background: #8C92AC;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  padding: 15px 30px;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  width: 100%;
}

header.dark-mode {
  background: #222;
}

header .logo {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

header .logo img {
  height: 50px;
  width: auto;
}

#mainNav {
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

#mainNav a {
  text-decoration: none;
  padding: 10px 16px;
  background: #fff;
  color: #b5651d;
  border-radius: 8px;
  font-weight: 600;
  transition: 0.25s;
  white-space: nowrap;
}

#mainNav a:hover,
#mainNav a.active {
  background: #8b4315;
  color: #fff;
  transform: translateY(-2px);
}

.menu-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  cursor: pointer;
  margin-left: auto;
}

.menu-toggle div {
  width: 28px;
  height: 3px;
  background: #fff;
  border-radius: 2px;
}

/* Layout contact */
.page-shell {
  width: 100%;
  max-width: 1200px;
  margin: 32px auto 40px;
  padding: 0 20px;
}

.contact-hero {
  margin-bottom: 22px;
}

.contact-hero h1 {
  font-family: 'Montserrat', sans-serif;
  font-size: 36px;
  margin: 0 0 10px;
}

.contact-hero p {
  margin: 0;
  font-size: 16px;
  color: #444;
  line-height: 1.6;
}

.contact-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.85fr);
  gap: 28px;
  align-items: start;
}

.card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 10px 28px rgba(0,0,0,0.08);
  border: 1px solid rgba(181, 101, 29, 0.08);
}

.contact-left {
  padding: 26px;
}

.contact-left h3 {
  margin: 0 0 16px;
  font-size: 22px;
  font-family: 'Montserrat', sans-serif;
}

.produs-banner {
  background: linear-gradient(135deg, #b5651d, #8b4315);
  color: #fff;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 18px;
  display: flex;
  gap: 10px;
  align-items: flex-start;
  line-height: 1.5;
}

.produs-banner strong {
  font-size: 16px;
}

.contact-form {
  display: grid;
  gap: 12px;
}

.contact-form label {
  font-weight: 700;
  font-size: 14px;
  color: #333;
}

.contact-form input,
.contact-form textarea {
  width: 100%;
  padding: 13px 14px;
  border-radius: 10px;
  border: 1px solid #d8d8d8;
  font-size: 16px;
  font-family: 'Roboto', sans-serif;
  background: #fff;
  color: #111;
  outline: none;
  transition: 0.25s;
}

.contact-form input:focus,
.contact-form textarea:focus {
  border-color: #b5651d;
  box-shadow: 0 0 0 4px rgba(181, 101, 29, 0.12);
}

.contact-form textarea {
  resize: vertical;
  min-height: 160px;
}

.contact-form button {
  width: 100%;
  padding: 14px 18px;
  background: #b5651d;
  color: #fff;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 16px;
  transition: 0.25s;
}

.contact-form button:hover {
  background: #8b4315;
  transform: translateY(-1px);
}

.contact-right {
  display: grid;
  gap: 18px;
}

.info-block {
  padding: 18px;
}

.info-block h3 {
  margin: 0 0 14px;
  font-family: 'Montserrat', sans-serif;
  font-size: 20px;
}

.info-item {
  padding: 14px 15px;
  border-radius: 12px;
  background: #f9f9f9;
  border: 1px solid #ececec;
  margin-bottom: 12px;
  transition: 0.25s;
}

.info-item:hover {
  background: #fdf0e6;
  transform: translateY(-1px);
}

.info-item strong {
  display: block;
  color: #b5651d;
  margin-bottom: 6px;
}

.info-item p {
  margin: 0;
  line-height: 1.6;
}

.map-wrapper {
  max-width: 1200px;
  margin: 0 auto 40px;
  padding: 0 20px;
}

.map-card {
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 10px 28px rgba(0,0,0,0.08);
  border: 1px solid rgba(181, 101, 29, 0.08);
}

.map-card iframe {
  width: 100%;
  height: 420px;
  border: 0;
  display: block;
}

/* Footer */
footer {
  background: #8C92AC;
  color: #fff;
  padding: 50px 20px;
  transition: background 0.3s, color 0.3s;
}

footer.dark-mode {
  background: #222;
}

footer .footer-container {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  max-width: 1200px;
  margin: 0 auto;
  align-items: flex-start;
}

footer .footer-col {
  flex: 1;
  min-width: 200px;
}

footer .footer-logo img {
  height: 50px;
  width: auto;
  margin-bottom: 15px;
}

footer ul {
  list-style: none;
  padding: 0;
  margin: 10px 0 20px 0;
  line-height: 1.8;
}

footer ul li a {
  color: #fff;
  text-decoration: none;
}

footer p,
footer strong,
footer span,
footer li,
footer blockquote {
  color: #fff !important;
}

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
  color: #fff;
}

footer .footer-bottom {
  max-width: 1200px;
  margin: 30px auto 0;
  border-top: 1px solid rgba(255,255,255,0.3);
  padding-top: 15px;
  font-size: 14px;
}

footer .footer-bottom a {
  color: #fff;
  text-decoration: none;
  margin: 0 8px;
}

/* Dark mode specific */
body.dark-mode .card,
body.dark-mode .info-item {
  background: #1e1e1e;
  color: #fdf6f0;
  border-color: #333;
}

body.dark-mode .contact-hero p {
  color: #cfcfcf;
}

body.dark-mode .contact-form input,
body.dark-mode .contact-form textarea {
  background: #111;
  color: #fdf6f0;
  border-color: #444;
}

body.dark-mode .contact-form label {
  color: #e8e8e8;
}

/* Responsive */
@media (max-width: 900px) {
  .contact-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  header {
    padding: 12px 16px;
  }

  .menu-toggle {
    display: flex;
  }

  #mainNav {
    display: none;
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    margin-top: 10px;
  }

  #mainNav.mobile-open {
    display: flex;
  }

  #mainNav a {
    width: 100%;
    text-align: left;
    padding: 12px 14px;
  }

  .page-shell {
    margin-top: 24px;
    padding: 0 12px;
  }

  .contact-hero h1 {
    font-size: 28px;
  }

  .contact-left,
  .info-block {
    padding: 18px;
  }

  .map-wrapper {
    padding: 0 12px;
  }

  .map-card iframe {
    height: 320px;
  }

  footer {
    padding: 40px 16px;
  }

  footer .footer-container {
    flex-direction: column;
    gap: 25px;
  }
}

@media (max-width: 420px) {
  .page-shell {
    padding: 0 10px;
  }

  .contact-hero h1 {
    font-size: 24px;
  }

  .contact-form input,
  .contact-form textarea,
  .contact-form button {
    font-size: 15px;
  }

  .map-card iframe {
    height: 280px;
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
    <a href="Contact.php" class="active">Contact</a>
    <a href="CosulMeu.php">Cosul Meu</a>
    <a href="ContulMeu.php">Contul Meu</a>
  </nav>

  <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul">
    <div></div>
    <div></div>
    <div></div>
  </div>
</header>

<div class="page-shell">
  <section class="contact-hero">
    <h1><?= e($txt[$lang]['page_title']) ?></h1>
    <p><?= e($txt[$lang]['subtitle']) ?></p>
  </section>

  <section class="contact-layout">
    <div class="card contact-left">
      <h3><?= e($txt[$lang]['form']['title']) ?></h3>

      <?php if ($produsPrecompletat !== ''): ?>
        <div class="produs-banner">
          <span style="font-size:22px;line-height:1;">📦</span>
          <div>
            Soliciți ofertă pentru: <strong><?= e($produsPrecompletat) ?></strong>
          </div>
        </div>
      <?php endif; ?>

      <form action="send_message.php" method="POST" class="contact-form">
        <div>
          <label for="name"><?= e($txt[$lang]['form']['name']) ?> *</label>
          <input type="text" id="name" name="name" placeholder="Numele tău" required>
        </div>

        <div>
          <label for="phone"><?= e($txt[$lang]['form']['phone']) ?></label>
          <input type="text" id="phone" name="phone" placeholder="+40 XXX XXX XXX">
        </div>

        <div>
          <label for="email"><?= e($txt[$lang]['form']['email']) ?> *</label>
          <input type="email" id="email" name="email" placeholder="email@exemplu.ro" required>
        </div>

        <div>
          <label for="subjectInput"><?= e($txt[$lang]['form']['subject']) ?></label>
          <input
            type="text"
            name="subject"
            id="subjectInput"
            value="<?= e($produsPrecompletat) ?>"
            placeholder="Despre ce produs este vorba?"
          >
        </div>

        <div>
          <label for="messageInput"><?= e($txt[$lang]['form']['message']) ?> *</label>
          <textarea
            name="message"
            id="messageInput"
            placeholder="Scrie mesajul tău aici..."
            required
          ><?php
            if ($produsPrecompletat !== '') {
                echo e('Bună ziua, sunt interesat(ă) de produsul: ' . $produsPrecompletat . '. Vă rog să îmi trimiteți o ofertă de preț.');
            }
          ?></textarea>
        </div>

        <?php if ($produsPrecompletat !== ''): ?>
          <input type="hidden" name="produs_ref" value="<?= e($produsPrecompletat) ?>">
        <?php endif; ?>

        <button type="submit"><?= e($txt[$lang]['form']['btn']) ?></button>
      </form>
    </div>

    <aside class="contact-right">
      <div class="card info-block">
        <h3>Informații</h3>

        <div class="info-item">
          <strong><?= e($txt[$lang]['info']['address_label']) ?></strong>
          <p><?= e($txt[$lang]['info']['address']) ?></p>
        </div>

        <div class="info-item">
          <strong><?= e($txt[$lang]['info']['phone_label']) ?></strong>
          <p><?= e($txt[$lang]['info']['phone']) ?></p>
        </div>

        <div class="info-item">
          <strong><?= e($txt[$lang]['info']['email_label']) ?></strong>
          <p><?= e($txt[$lang]['info']['email']) ?></p>
        </div>

        <div class="info-item">
          <strong><?= e($txt[$lang]['info']['program_label']) ?></strong>
          <p><?= e($txt[$lang]['info']['program']) ?></p>
        </div>
      </div>
    </aside>
  </section>
</div>

<div class="map-wrapper">
  <div class="map-card">
    <iframe
      src="<?= e($txt[$lang]['map_src']) ?>"
      allowfullscreen
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>
</div>

<footer id="footer">
  <div class="footer-container">
    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image41.png" alt="Sherghei Covoare">
      </div>
      <p>Primul an prin care aducem covoare traditionale si moderne in casa ta.</p>

      <div class="footer-social">
        <a href="https://www.facebook.com/maestro.fortunato/" target="_blank" rel="noopener noreferrer" title="Facebook">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v8h4v-8h3l1-4h-4V6a1 1 0 0 1 1-1h3z"/>
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
      <strong>Contact</strong>
      <p>Adresa, Craiova, Dolj</p>
      <p>Telefon, 0753 508 461</p>
      <p>Email, office@magazinpsy.ro</p>
      <p>Program, Luni - Sambata 9 - 18</p>
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

  function toggleMenu() {
    const nav = document.getElementById('mainNav');
    if (!nav) return;
    nav.classList.toggle('mobile-open');
  }
  window.toggleMenu = toggleMenu;

  document.addEventListener('click', function (e) {
    const nav = document.getElementById('mainNav');
    const toggle = e.target.closest('.menu-toggle');
    const link = e.target.closest('#mainNav a');

    if (link && nav && nav.classList.contains('mobile-open') && window.innerWidth <= 768) {
      nav.classList.remove('mobile-open');
    }

    if (!toggle && nav && !nav.contains(e.target)) {
      // optional: nimic
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
      const nav = document.getElementById('mainNav');
      if (nav) nav.classList.remove('mobile-open');
    }
  });
})();
</script>
</body>
</html>
