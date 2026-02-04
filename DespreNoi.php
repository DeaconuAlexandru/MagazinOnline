<?php
// Setare limbă
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) {
    $lang = $_GET['lang'];
}

// Titluri și texte dinamice (opțional, dacă vrei să le traduci)
$pageTitle = "Despre Noi - Sherghei Covoare";
$headerTitle = "Sherghei Covoare";
$sectionTitle = "Despre Noi";
$mainTitle = "Pasiune pentru Meșteșug";
$experienceYears = "20+ Ani de Experiență";

// Beneficii
$benefits = [
    "Experiență de peste 20 de ani",
    "Materiale de cea mai înaltă calitate",
    "Echipă de meșteri specializați",
    "Garanție pentru toate produsele",
    "Consultanță personalizată gratuită",
    "Prețuri competitive"
];

// Misiune și Viziune
$mission = "Să oferim covoare de cea mai înaltă calitate, care aduc frumusețe și confort în fiecare casă, menținând în același timp tradițiile și măiestria artizanală.";
$vision = "Să devenim cel mai de încredere furnizor de covoare din România, recunoscut pentru excelență, integritate și pasiunea pentru arta covoarelor.";
?>
<?php
$lang = 'ro';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Despre Noi - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Roboto',sans-serif;
    background:#fdf6f0;
    color:#222;
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

nav a.active{background:#8b4315;}

.dark-toggle{
    background:#fff;
    padding:10px;
    border-radius:8px;
    cursor:pointer;
    color:#b5651d;
}

/* Layout principal */
.page-wrapper{
    max-width:1200px;
    margin:50px auto;
    padding:20px;
    display:flex;
    gap:40px;
    align-items:stretch;
}

/* Imagine stânga */
.image-left{
    flex:1;
    min-width:350px;
    border-radius:16px;
    overflow:hidden;
    position:relative;
}
.image-left img{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,0.2);
}

/* Text dreapta-jos peste imagine */
.image-badge{
    position:absolute;
    right:15px;
    bottom:15px;
    background:#b5651d;
    color:#fff;
    padding:10px 18px;
    font-size:16px;
    border-radius:10px;
    font-weight:600;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}

/* Conținut dreapta */
.content-right{
    flex:1.2;
    min-width:350px;
    display:flex;
    flex-direction:column;
    gap:20px;
}

.content-right h1{
    font-size:32px;
    font-family:'Montserrat',sans-serif;
}
.content-right h3{
    color:#b5651d;
    margin:0;
}

/* Citat */
.quote-box{
    background:#f9f3ee;
    padding:25px;
    border-radius:16px;
    border:1px solid rgba(0,0,0,0.1);
    text-align:center;
    font-size:18px;
    font-style:italic;
    margin-top:20px;
}
.quote-author{
    margin-top:10px;
    font-style:normal;
    font-weight:600;
}

/* Misiune + Viziune */
.mission-vision{
    display:flex;
    gap:20px;
    margin-top:10px;
}
.mission-vision .card{
    flex:1;
    background:#f9f3ee;
    padding:20px;
    border-radius:12px;
    border:1px solid rgba(0,0,0,0.1);
    transition:0.3s;
}
.mission-vision .card h4{
    color:#b5651d;
    margin-top:0;
}
.mission-vision .card:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,0.2);
}

@media(max-width:768px){
    .page-wrapper{flex-direction:column;}
    .mission-vision{flex-direction:column;}
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
.dark-mode{
    background:#111;
    color:#fdf6f0;
}
.dark-mode header{
    background:#222;
    color:#fdf6f0;
}
.dark-mode footer{
    background:#222;
    color:#fdf6f0;
}
.dark-toggle{
    cursor:pointer;
    padding:10px;
    border-radius:8px;
    background:#fff;
    color:#b5651d;
    font-weight:600;
    transition:0.3s;
}
.dark-toggle:hover{
    background:#b5651d;
    color:#fff;
}
/* Text vizibil în Dark Mode */
.dark-mode .content-right p,
.dark-mode .content-right h1,
.dark-mode .content-right h3,
.dark-mode .quote-box,
.dark-mode .quote-box .quote-author,
.dark-mode .mission-vision .card,
.dark-mode .mission-vision .card h4,
.dark-mode .mission-vision .card p {
    color: #fdf6f0 !important;
}

/* Fundaluri carduri și citat în Dark Mode */
.dark-mode .quote-box,
.dark-mode .mission-vision .card {
    background: #333 !important;
    border-color: #555 !important;
}
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
      <a href="DespreNoi.php"class="active">Despre Noi</a>
      <a href="Contact.php">Contact</a>
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
<div class="page-wrapper">

    <div class="image-left">
        <img src="Image14.png" alt="Povestea Sherghei Covoare">

        <!-- Textul cerut în colțul dreapta-jos -->
        <div class="image-badge">
            De peste 20 de ani în slujba eleganței și calității.
        </div>
    </div>

    <div class="content-right">
        <h3>Despre Noi</h3>
        <h1>Pasiune pentru Meșteșug</h1>

        <p>De peste două decenii, Sherghei Covoare aduce în casele românilor cele mai frumoase și durabile covoare.</p>
        <p>Am început ca o mică afacere de familie și am crescut datorită dedicării noastre pentru calitate și satisfacția clienților.</p>

        <!-- Videoclip YouTube responsive -->
        <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; margin:20px 0;">
            <iframe src="https://www.youtube.com/embed/xUU1PcMcxgE" 
                title="YouTube video" frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
                style="position:absolute; top:0; left:0; width:100%; height:100%;">
            </iframe>
        </div>

        <div class="quote-box">
            „Fiecare covor spune o poveste. Misiunea noastră este să aducem acele povești în casa ta.”
            <div class="quote-author">— Sherghei<br>Fondator —</div>
        </div>

        <div class="mission-vision">
            <div class="card">
                <h4>Misiunea Noastră</h4>
                <p>Să oferim covoare de cea mai înaltă calitate.</p>
            </div>

            <div class="card">
                <h4>Viziunea Noastră</h4>
                <p>Să devenim cel mai de încredere furnizor de covoare din România.</p>
            </div>
        </div>

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
function toggleDarkMode(){
    document.body.classList.toggle('dark-mode');
    document.querySelector('header').classList.toggle('dark-mode');
    document.querySelector('footer').classList.toggle('dark-mode');
}
</script>

</body>
</html>