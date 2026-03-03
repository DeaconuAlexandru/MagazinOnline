<?php
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) {
    $lang = $_GET['lang'];
}

$txt = [
    'ro'=>[
        'page_title'=>'Contactează-ne',
        'subtitle'=>'Suntem aici să te ajutăm. Trimite-ne un mesaj sau vizitează-ne în showroom.',
        'form'=>[
            'title'=>'Trimite un Mesaj',
            'name'=>'Nume Complet',
            'phone'=>'Telefon',
            'email'=>'Email',
            'message'=>'Mesaj',
            'btn'=>'Trimite Mesajul'
        ],
        'info'=>[
            'address_label'=>'Adresă',
            'address'=>'Craiova,Dolj',
            'phone_label'=>'Telefon',
            'phone'=>'0764.049.235',
            'email_label'=>'Email',
            'email'=>'office@magazinpsy.ro',
            'program_label'=>'Program',
            'program'=>'Luni - Sâmbătă: 9:00 - 18:00'
        ],
        'map_src'=>'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2849.123456!2d26.100000!3d44.400000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1234567890abc%3A0xabcdef1234567890!2sStrada%20Covoarelor%2012%2C%20București!5e0!3m2!1sro!2sro!4v1699999999999!5m2!1sro!2sro'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= $txt[$lang]['title'] ?></title>
<style>
*{box-sizing:border-box;}
body{font-family:Roboto,sans-serif;margin:0;padding:0;background:#fdf6f0;color:#111;}
.contact-container{max-width:1200px;margin:40px auto;display:flex;gap:50px;flex-wrap:wrap;padding:0 20px;}
.contact-left,.contact-right{flex:1;min-width:300px;}
.contact-left h1{font-size:36px;margin-bottom:10px;}
.contact-left p{font-size:16px;color:#444;margin-bottom:20px;}
.contact-left h3{margin-bottom:15px;}
.contact-form{display:flex;flex-direction:column;gap:12px;}
.contact-form label{font-weight:600;}
.contact-form input,.contact-form textarea{padding:12px;border-radius:8px;border:1px solid #ccc;font-size:16px;width:100%;transition:0.3s;}
.contact-form input:hover,.contact-form textarea:hover{border-color:#b5651d;box-shadow:0 0 8px rgba(181,101,29,0.4);}
.contact-form textarea{resize:none;height:150px;}
.contact-form button{padding:14px;background:#b5651d;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:0.3s;}
.contact-form button:hover{background:#8b4315;transform:scale(1.05);}
.contact-right{display:flex;flex-direction:column;gap:30px;}
.info-block{display:flex;flex-direction:column;gap:15px;}
.info-item{padding:12px 15px;border-radius:8px;background:#fff;box-shadow:0 4px 10px rgba(0,0,0,0.1);transition:0.3s;}
.info-item:hover{background:#fdf0e6;transform:scale(1.02);}
.info-item strong{display:block;margin-bottom:5px;color:#b5651d;}
.map-wrapper{margin-top:40px;width:100%;height:400px;}
.map-wrapper iframe{width:100%;height:100%;border:0;border-radius:12px;}
@media(max-width:900px){.contact-container{flex-direction:column;}}
@media(max-width:500px){.contact-container{margin:20px 10px;gap:20px;}}
/* Lazy load */
img{display:block;width:100%;height:auto;border-radius:12px;transition:transform 0.3s,box-shadow 0.3s;}
/* About Section */
.about-wrapper{
  display:flex;
  gap:40px;
  align-items:center;
  justify-content:center;
  max-width:1100px;
  margin:auto;
  flex-wrap:wrap;
}

.about-image-box{
  position:relative;
  width:380px;
}

.about-image-box img{
  width:100%;
  border-radius:16px;
  box-shadow:0 8px 20px rgba(0,0,0,0.25);
}

.about-badge{
  position:absolute;
  bottom:15px;
  right:15px;
  background:#b5651d;
  color:#fff;
  padding:12px 18px;
  border-radius:12px;
  text-align:center;
  box-shadow:0 4px 12px rgba(0,0,0,0.25);
}

.about-badge strong{
  font-size:26px;
}

.about-badge span{
  font-size:14px;
  display:block;
  margin-top:-3px;
}

.about-text{
  flex:1;
  min-width:300px;
  text-align:left;
}

.about-text ul{list-style:none;padding:0;margin:15px 0;}
.about-text ul li{margin:6px 0;}

/* Contact */
.contact form{max-width:500px;margin:auto;text-align:left;}
.contact form input,.contact form textarea{width:100%;padding:12px;margin:6px 0;border-radius:8px;border:1px solid #ccc;font-size:16px;}
.contact form button{padding:14px 28px;background:#b5651d;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:.3s;font-weight:500;}
.contact form button:hover{background:#8b4315;transform:translateY(-2px);}

/* Flash */
.flash-message{max-width:500px;margin:auto;margin-bottom:15px;padding:10px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:6px;text-align:center;}

/* Harta */
.map-container iframe{width:100%;height:400px;border:0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.2);transition:transform 0.3s,box-shadow 0.3s;}
.map-container:hover iframe{transform:scale(1.02);box-shadow:0 12px 30px rgba(0,0,0,0.3);}

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
/* HEADER GENERAL */
header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #8C92AC;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  padding: 15px 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
}

/* Logo stânga */
header .logo {
  display: flex;
  align-items: center;
  gap: 15px;
}

header .logo img {
  height: 50px;
}

/* NAV MENIU CENTRAT */
header nav {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  transition: max-height 0.5s ease;
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

header nav a:hover,
header nav a.active {
  background: #8b4315;
  color: #fff;
  transform: translateY(-2px);
}

/* ZONA DREAPTA */
.header-contact {
  display: flex;
  gap: 15px;
  align-items: center;
}

.header-contact .phone {
  font-weight: 600;
}

.header-contact .btn {
  background: #fff;
  color: #b5651d;
  padding: 12px 25px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  position: relative;
  transition: 0.3s;
}

.header-contact .btn:hover {
  background: #8b4315;
  color: #fff;
  transform: translateY(-2px);
}

/* ZONA DREAPTA */
.header-contact {display:flex;gap:15px;align-items:center;}
.header-contact .phone {font-weight:600;}
.header-contact .btn {background:#fff;color:#b5651d;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:500;transition:0.3s;}
.header-contact .btn:hover {background:#8b4315;color:#fff;}
.dark-toggle {cursor:pointer;font-size:20px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#fff;color:#b5651d;font-weight:600;transition:all 0.3s;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.dark-toggle:hover {background:#8b4315;color:#fff;transform:scale(1.1);}
body.dark-mode .dark-toggle {background:#333;color:#fff;box-shadow:0 4px 12px rgba(255,255,255,0.2);}
body.dark-mode .dark-toggle:hover {background:#b5651d;color:#fff;}

/* HAMBURGER MOBIL */
.menu-toggle{display:none;flex-direction:column;cursor:pointer;}
.menu-toggle div{width:30px;height:3px;background:#fff;margin:5px 0;transition:0.3s;}
nav.mobile-open{max-height:500px;}
@media screen and (max-width:768px){nav{flex-direction:column;max-height:0;}.menu-toggle{display:flex;}header{flex-direction:column;align-items:flex-start;padding:10px 20px;}.header-contact{margin-top:10px;}}

/* Hero text și carduri fade-in */
.hero h2,.hero p,.hero .btn{opacity:0;transform:translateY(20px);transition:transform 0.5s,opacity 0.5s;}
.hero h2.visible,.hero p.visible,.hero .btn.visible{opacity:1;transform:translateY(0);}
.card{opacity:0;transform:translateY(20px) rotateX(1deg);transition:transform 0.3s,box-shadow 0.3s,background 0.3s,opacity 0.5s;}
.card.visible{opacity:1;transform:translateY(0) rotateX(0);}
.services .card:hover,.collection-item:hover{transform:translateY(-10px) rotateX(3deg);box-shadow:0 12px 24px rgba(0,0,0,0.3);background:#fdf0e6;}
.services .card:hover h3,p,.collection-item:hover h3,p strong{color:#b5651d;transition:color 0.3s;}
.hero{position:relative;text-align:center;padding:120px 20px;background:url('hero-bg.jpg') center/cover no-repeat;color:#fff;overflow:hidden;transition:transform 0.3s;}
.hero::before{content:"";position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);}

/* Starea dark */
body.dark-mode {
  background: #111;
  color: #eee;
}

body.dark-mode header,
body.dark-mode footer {
  background: #222;
  color: #fff;
}

body.dark-mode nav a {
  background: #333;
  color: #fff;
}

body.dark-mode nav a:hover,
body.dark-mode nav a.active {
  background: #b5651d;
  color: #fff;
}

body.dark-mode .contact-form input,
body.dark-mode .contact-form textarea {
  background: #333;
  color: #eee;
  border: 1px solid #555;
}

body.dark-mode .info-item {
  background: #222;
  color: #eee;
  border: 1px solid #444;
}

body.dark-mode .dark-toggle {
  background: #333;
  color: #fff;
}

/* HAMBURGER MOBIL */
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
  transition: 0.3s;
}

/* NAV DESCHIS PE MOBIL */
nav.mobile-open {
  max-height: 500px;
}

/* RESPONSIVE */
@media screen and (max-width: 768px) {
  header {
    flex-direction: column;
    align-items: flex-start;
    padding: 10px 20px;
  }

  header nav {
    flex-direction: column;
    max-height: 0;
    overflow: hidden;
    width: 100%;
    margin: 10px 0;
  }

  .menu-toggle {
    display: flex;
  }

  .header-contact {
    margin-top: 10px;
  }
}


/* Hamburger meniu mobil */
.menu-toggle{display:none;flex-direction:column;cursor:pointer;}
.menu-toggle div{width:30px;height:3px;background:#fff;margin:5px 0;transition:0.3s;}
nav.mobile-open{max-height:500px;}
@media screen and (max-width:768px){
  nav{flex-direction:column;max-height:0;}
  .menu-toggle{display:flex;}
}
/* Hero text și butoane fade-in */
.hero h2, .hero p, .hero .btn {
  opacity: 0;
  transform: translateY(20px);
  transition: transform 0.5s, opacity 0.5s;
}
.hero h2.visible, .hero p.visible, .hero .btn.visible {
  opacity: 1;
  transform: translateY(0);
}

/* Carduri fade-in la load (staggered) */
.card {
  opacity: 0;
  transform: translateY(20px) rotateX(1deg);
  transition: transform 0.3s, box-shadow 0.3s, background 0.3s, opacity 0.5s;
}
.card.visible {
  opacity: 1;
  transform: translateY(0) rotateX(0);
}

/* Card hover effect existent păstrat */
.services .card:hover, .collection-item:hover {
  transform: translateY(-10px) rotateX(3deg);
  box-shadow: 0 12px 24px rgba(0,0,0,0.3);
  background:#fdf0e6;
}
.services .card:hover h3,p, .collection-item:hover h3,p strong {
  color:#b5651d;
  transition:color 0.3s;
}

/* Hero parallax & general */
.hero{position:relative;text-align:center;padding:120px 20px;background:url('hero-bg.jpg') center/cover no-repeat;color:#fff;overflow:hidden;transition:transform 0.3s;}
.hero::before{content:"";position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);}

</style>
</head>
<body>
<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">

    <!-- Logo stânga -->
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <!-- Meniu centrat -->
    <nav id="mainNav" style="display:flex;gap:15px;flex-wrap:wrap;justify-content:center;flex:1;">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="#Contact.php"class="active">Contact</a>
	<a href="CosulMeu.php">Cosul Meu</a>
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
<div class="contact-container">
    <div class="contact-left">
        <h1><?= $txt[$lang]['page_title'] ?></h1>
        <p><?= $txt[$lang]['subtitle'] ?></p>
        <h3><?= $txt[$lang]['form']['title'] ?></h3>
        <form action="send_message.php" method="POST" class="contact-form">
            <label><?= $txt[$lang]['form']['name'] ?> *</label>
            <input type="text" name="name" placeholder="Numele tău" required>
            <label><?= $txt[$lang]['form']['phone'] ?></label>
            <input type="text" name="phone" placeholder="+40 XXX XXX XXX">
            <label><?= $txt[$lang]['form']['email'] ?> *</label>
            <input type="email" name="email" placeholder="email@exemplu.ro" required>
            <label><?= $txt[$lang]['form']['message'] ?> *</label>
            <textarea name="message" placeholder="Scrie mesajul tău aici..." required></textarea>
            <button type="submit"><?= $txt[$lang]['form']['btn'] ?></button>
        </form>
    </div>
    <div class="contact-right">
        <div class="info-block">
            <div class="info-item">
                <strong><?= $txt[$lang]['info']['address_label'] ?></strong>
                <p><?= $txt[$lang]['info']['address'] ?></p>
            </div>
            <div class="info-item">
                <strong><?= $txt[$lang]['info']['phone_label'] ?></strong>
                <p><?= $txt[$lang]['info']['phone'] ?></p>
            </div>
        </div>
        <div class="info-block">
            <div class="info-item">
                <strong><?= $txt[$lang]['info']['email_label'] ?></strong>
                <p><?= $txt[$lang]['info']['email'] ?></p>
            </div>
            <div class="info-item">
                <strong><?= $txt[$lang]['info']['program_label'] ?></strong>
                <p><?= $txt[$lang]['info']['program'] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="map-wrapper">
    <iframe src="<?= $txt[$lang]['map_src'] ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</div>
<footer id="footer">
  <div class="footer-container">

    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image3.png" alt="Sherghei Covoare">
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
function toggleDarkMode() {
  const body = document.body;
  body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', body.classList.contains('dark-mode') ? 'on' : 'off');
}

document.addEventListener('DOMContentLoaded', function() {
  if (localStorage.getItem('darkMode') === 'on') {
    document.body.classList.add('dark-mode');
  }
});
</script>

</body>
</html>
