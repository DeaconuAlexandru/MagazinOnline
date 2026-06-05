<?php
declare(strict_types=1);

session_start();

/* CONEXIUNE BD */
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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit("Eroare DB: " . $e->getMessage());
}
require_once __DIR__ . '/social_tracker.php';
recordSocialVisit($pdo);
// Setare limbă
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) {
    $lang = $_GET['lang'];
}

// Titluri și texte dinamice
$pageTitle = "Despre Noi - Sherghei Covoare";
$headerTitle = "Sherghei Covoare";
$sectionTitle = "Despre Noi";
$mainTitle = "Suntem Ambasadori ai Pacii Mondiale";
$experienceYears = "Experiență vastă";

// Beneficii
$benefits = [
    "Experiență vastă",
    "Materiale de cea mai înaltă calitate",
    "Echipă de oameni profesioniști",
    "Garanție pentru toate produsele",
    "Consultanță personalizată gratuită",
    "Prețuri competitive"
];

// Misiune și Viziune
$mission = "Să oferim covoare de cea mai înaltă calitate, care aduc frumusețe și confort în fiecare casă, menținând în același timp tradițiile și măiestria artizanală.";
$vision = "Să devenim cel mai de încredere furnizor de covoare din România, recunoscut pentru excelență, integritate și pasiunea pentru arta covoarelor.";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --bg: #f7f4ef;
    --surface: rgba(255,255,255,0.80);
    --surface-strong: #ffffff;
    --text: #1f2430;
    --muted: #616b7c;
    --accent: #2563eb;
    --accent-2: #b5651d;
    --header-link: #8B4513;
    --header-link-dark: #f6d7c3;
    --border: rgba(31,36,48,0.08);
    --shadow: 0 18px 50px rgba(31,36,48,0.10);
    --shadow-soft: 0 10px 28px rgba(31,36,48,0.08);
    --radius: 24px;
}

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
    font-family:'Inter',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(181,101,29,0.10), transparent 36%),
        radial-gradient(circle at top right, rgba(139,67,21,0.08), transparent 30%),
        var(--bg);
    color:var(--text);
    transition: background .35s ease, color .35s ease;
}

body.dark-mode{
    background:
        radial-gradient(circle at top left, rgba(181,101,29,0.10), transparent 36%),
        radial-gradient(circle at top right, rgba(139,67,21,0.08), transparent 30%),
        #111318;
    color:#f5f7fb;
}

a{
    color:inherit;
    text-decoration:none;
}

img{
    display:block;
    max-width:100%;
}

header{
    position:sticky;
    top:0;
    z-index:20;
    backdrop-filter: blur(14px);
    background: rgba(140,146,172,0.92);
    border-bottom:1px solid rgba(255,255,255,0.15);
    box-shadow:0 8px 30px rgba(0,0,0,0.10);
    transition: background .3s ease, box-shadow .3s ease;
}

body.dark-mode header{
    background: rgba(28,31,41,0.92);
    border-bottom:1px solid rgba(255,255,255,0.08);
}

.header-inner{
    max-width:1200px;
    margin:0 auto;
    padding:14px 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
}

.logo-wrap{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:180px;
}

.logo-wrap img{
    height:48px;
    width:auto;
    filter: drop-shadow(0 8px 16px rgba(0,0,0,0.12));
    transition: transform .25s ease, filter .25s ease;
}

.logo-wrap img:hover{
    transform: scale(1.04);
    filter: drop-shadow(0 12px 20px rgba(0,0,0,0.18));
}

.nav-wrap{
    display:flex;
    align-items:center;
    justify-content:center;
    flex:1;
}

nav{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:center;
}

nav a{
    padding:10px 16px;
    border-radius:999px;
    background:rgba(255,255,255,0.92);
    color:var(--header-link);
    font-weight:600;
    font-size:14px;
    transition:transform .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease, opacity .2s ease;
    box-shadow:0 8px 20px rgba(31,36,48,0.06);
}

body.dark-mode nav a{
    background:rgba(255,255,255,0.06);
    color:var(--header-link-dark);
    border:1px solid rgba(255,255,255,0.06);
}

nav a:hover,
nav a.active,
nav a.force-hover{
    background:var(--accent-2);
    color:#fff;
    transform:translateY(-2px);
    box-shadow:0 12px 24px rgba(181,101,29,0.25);
}

body.dark-mode nav a:hover,
body.dark-mode nav a.active,
body.dark-mode nav a.force-hover{
    background:var(--accent-2);
    color:#fff;
}

.menu-toggle{
    display:none;
    flex-direction:column;
    gap:5px;
    cursor:pointer;
    padding:10px;
    border-radius:12px;
    background:rgba(255,255,255,0.18);
    transition:transform .2s ease, background .2s ease;
}

.menu-toggle:hover{
    transform:translateY(-1px);
    background:rgba(255,255,255,0.24);
}

.menu-toggle span{
    width:26px;
    height:3px;
    background:#fff;
    border-radius:999px;
}

.page{
    max-width:1200px;
    margin:0 auto;
    padding:34px 20px 60px;
}

.hero{
    display:grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap:28px;
    align-items:stretch;
    margin-top:8px;
}

.hero-card,
.info-card,
.quote-card,
.panel,
.mini-card,
.video-card,
.stat{
    background:var(--surface);
    border:1px solid var(--border);
    box-shadow:var(--shadow-soft);
    backdrop-filter: blur(14px);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease, background .25s ease;
}

.hero-card:hover,
.info-card:hover,
.quote-card:hover,
.mini-card:hover,
.video-card:hover,
.stat:hover{
    transform:translateY(-6px);
    box-shadow:var(--shadow);
    border-color: rgba(181,101,29,0.18);
}

.hero-card{
    border-radius:32px;
    overflow:hidden;
    position:relative;
    min-height:560px;
}

.hero-image{
    position:absolute;
    inset:0;
}

.hero-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform .6s ease, filter .4s ease;
}

.hero-card:hover .hero-image img{
    transform:scale(1.05);
    filter:saturate(1.05) contrast(1.02);
}

.hero-overlay{
    position:absolute;
    inset:0;
    background:
        linear-gradient(180deg, rgba(16,18,24,0.04), rgba(16,18,24,0.62)),
        linear-gradient(135deg, rgba(181,101,29,0.18), rgba(139,67,21,0.12));
}

.hero-content{
    position:relative;
    z-index:2;
    display:flex;
    flex-direction:column;
    justify-content:flex-end;
    height:100%;
    padding:34px;
    color:#fff;
}

.kicker{
    display:inline-flex;
    width:max-content;
    padding:8px 14px;
    border-radius:999px;
    background:rgba(255,255,255,0.16);
    border:1px solid rgba(255,255,255,0.18);
    font-size:13px;
    font-weight:600;
    letter-spacing:0.02em;
    margin-bottom:16px;
    transition:transform .2s ease, background .2s ease;
}

.kicker:hover{
    transform:translateY(-1px);
    background:rgba(255,255,255,0.22);
}

.hero-content h1{
    margin:0;
    font-family:'Montserrat',sans-serif;
    font-size:clamp(30px, 4vw, 52px);
    line-height:1.05;
    max-width:10ch;
}

.hero-content p{
    margin:16px 0 0;
    max-width:56ch;
    font-size:16px;
    line-height:1.75;
    color:rgba(255,255,255,0.92);
}

.hero-badge{
    position:absolute;
    right:22px;
    bottom:22px;
    max-width:280px;
    padding:16px 18px;
    border-radius:18px;
    background:rgba(255,255,255,0.14);
    border:1px solid rgba(255,255,255,0.18);
    color:#fff;
    backdrop-filter: blur(10px);
    box-shadow:0 12px 30px rgba(0,0,0,0.18);
    transition:transform .25s ease, background .25s ease;
}

.hero-badge:hover{
    transform:translateY(-3px);
    background:rgba(255,255,255,0.18);
}

.hero-badge strong{
    display:block;
    font-size:15px;
    margin-bottom:6px;
}

.hero-badge span{
    font-size:13px;
    line-height:1.5;
    color:rgba(255,255,255,0.88);
}

.side-column{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.info-card{
    border-radius:28px;
    padding:26px;
}

.info-card h3{
    margin:0 0 8px;
    font-family:'Montserrat',sans-serif;
    font-size:15px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:var(--accent);
}

.info-card h2{
    margin:0;
    font-family:'Montserrat',sans-serif;
    font-size:clamp(26px, 3vw, 38px);
    line-height:1.12;
}

.info-card .lead{
    margin:14px 0 0;
    color:var(--muted);
    line-height:1.75;
    font-size:15px;
}

.chips{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:20px;
}

.chip{
    padding:9px 13px;
    border-radius:999px;
    background:#fff;
    border:1px solid rgba(181,101,29,0.12);
    color:#3a4252;
    font-size:13px;
    font-weight:600;
    box-shadow:0 8px 18px rgba(31,36,48,0.05);
    transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.chip:hover{
    transform:translateY(-2px);
    background:#fffaf5;
    box-shadow:0 12px 22px rgba(31,36,48,0.08);
}

body.dark-mode .chip{
    background:#171a23;
    color:#eef2ff;
    border-color:rgba(255,255,255,0.07);
}

.quote-card{
    border-radius:28px;
    padding:24px;
}

.quote-card p{
    margin:0;
    font-size:16px;
    line-height:1.8;
    color:var(--text);
}

.quote-author{
    margin-top:14px;
    font-weight:700;
    color:var(--accent);
}

.section-grid{
    margin-top:22px;
    display:grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap:18px;
}

.mini-card{
    background:var(--surface-strong);
    border:1px solid var(--border);
    border-radius:22px;
    padding:20px;
}

.mini-card h4{
    margin:0 0 10px;
    font-family:'Montserrat',sans-serif;
    font-size:18px;
    color:var(--text);
}

.mini-card p{
    margin:0;
    color:var(--muted);
    line-height:1.75;
    font-size:14px;
}

.media-grid{
    margin-top:28px;
    display:grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap:18px;
}

.video-card{
    border-radius:24px;
    overflow:hidden;
    background:var(--surface-strong);
    border:1px solid var(--border);
}

.youtube-responsive{
    position:relative;
    padding-bottom:56.25%;
    height:0;
    overflow:hidden;
}

.youtube-responsive iframe{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    border:0;
    transition:transform .25s ease, filter .25s ease;
}

.video-card:hover iframe{
    transform:scale(1.01);
    filter:saturate(1.02);
}

.stats{
    margin-top:22px;
    display:grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap:16px;
}

.stat{
    padding:20px;
    border-radius:22px;
    background:var(--surface-strong);
    text-align:left;
}

.stat strong{
    display:block;
    font-family:'Montserrat',sans-serif;
    font-size:22px;
    color:var(--accent);
    margin-bottom:8px;
}

.stat span{
    color:var(--muted);
    line-height:1.6;
    font-size:14px;
}

footer{
    background:linear-gradient(135deg, #8C92AC, #7c839b);
    color:#fff;
    padding:48px 20px 28px;
    margin-top:44px;
}

body.dark-mode footer{
    background:linear-gradient(135deg, #1f222b, #181b23);
}

footer .footer-container{
    max-width:1200px;
    margin:0 auto;
    display:grid;
    grid-template-columns: 1.3fr 1fr 1fr 1fr;
    gap:24px;
}

footer .footer-col strong{
    display:block;
    font-size:16px;
    margin-bottom:12px;
}

footer .footer-logo img{
    height:52px;
    width:auto;
    margin-bottom:14px;
    transition:transform .25s ease;
}

footer .footer-logo img:hover{
    transform:scale(1.04);
}

footer p,
footer li,
footer a{
    color:#fff;
    line-height:1.8;
    font-size:14px;
}

footer ul{
    list-style:none;
    padding:0;
    margin:0;
}

footer li a{
    opacity:0.95;
    transition:opacity .2s ease, transform .2s ease, text-decoration-color .2s ease;
}

footer li a:hover{
    opacity:1;
    text-decoration:underline;
}

footer .footer-social{
    display:flex;
    gap:10px;
    margin-top:12px;
}

footer .footer-social a{
    width:36px;
    height:36px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,0.14);
    transition:transform .2s ease, background .2s ease;
}

footer .footer-social a:hover{
    transform:translateY(-2px);
    background:rgba(255,255,255,0.22);
}

footer .footer-bottom{
    max-width:1200px;
    margin:26px auto 0;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,0.22);
    display:flex;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    font-size:13px;
    opacity:0.95;
}

footer .footer-bottom a{
    transition:opacity .2s ease;
}

footer .footer-bottom a:hover{
    opacity:1;
    text-decoration:underline;
}

.menu-toggle.is-open{
    background:rgba(255,255,255,0.30);
}

@media (max-width: 980px){
    .hero{
        grid-template-columns:1fr;
    }

    .media-grid,
    .section-grid,
    .stats,
    footer .footer-container{
        grid-template-columns:1fr 1fr;
    }
}

@media (max-width: 760px){
    .header-inner{
        flex-wrap:wrap;
    }

    .nav-wrap{
        order:3;
        width:100%;
    }

    nav{
        display:none;
        width:100%;
        flex-direction:column;
        align-items:stretch;
    }

    nav.mobile-open{
        display:flex;
    }

    nav a{
        width:100%;
        text-align:center;
    }

    .menu-toggle{
        display:flex;
    }

    .hero-card{
        min-height:520px;
    }

    .hero-content{
        padding:24px;
    }

    .hero-badge{
        position:relative;
        right:auto;
        bottom:auto;
        margin-top:18px;
        max-width:none;
    }

    .media-grid,
    .section-grid,
    .stats,
    footer .footer-container{
        grid-template-columns:1fr;
    }
}

@media (prefers-reduced-motion: reduce){
    html{
        scroll-behavior:auto;
    }
    *, *::before, *::after{
        animation:none !important;
        transition:none !important;
    }
}

h1, h2, h3, h4, .kicker, .quote-author, .stat strong {
    transition: transform .25s ease, color .25s ease, text-shadow .25s ease;
}

h1:hover,
h2:hover,
h3:hover,
h4:hover,
.kicker:hover,
.quote-author:hover,
.stat strong:hover {
    transform: translateY(-2px);
    color: var(--accent);
    text-shadow: 0 8px 18px rgba(139, 67, 21, 0.16);
}

img {
    transition: transform .35s ease, box-shadow .35s ease, filter .35s ease;
}

img:hover {
    transform: scale(1.02);
    filter: saturate(1.03) contrast(1.02);
}

a, button, .chip, .mini-card, .video-card, .stat, .quote-card, .info-card {
    transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease, border-color .25s ease;
}

button:hover,
a:hover,
.chip:hover,
.mini-card:hover,
.video-card:hover,
.stat:hover,
.quote-card:hover,
.info-card:hover {
    transform: translateY(-3px);
}

.hero-content h1,
.hero-content p,
.info-card h2,
.info-card h3,
.info-card .lead,
.quote-card p,
.quote-author,
.mini-card h4,
.mini-card p,
.stat strong,
.stat span{
    transition: transform .25s ease, color .25s ease, background-color .25s ease, box-shadow .25s ease;
    border-radius: 12px;
    padding: 2px 6px;
}

.hero-content h1:hover,
.hero-content p:hover,
.info-card h2:hover,
.info-card h3:hover,
.info-card .lead:hover,
.quote-card p:hover,
.quote-author:hover,
.mini-card h4:hover,
.mini-card p:hover,
.stat strong:hover,
.stat span:hover{
    transform: translateY(-2px);
    color: var(--accent);
    background: rgba(181, 101, 29, 0.06);
    box-shadow: 0 8px 18px rgba(31, 36, 48, 0.06);
}

.hero-badge strong,
.hero-badge span,
footer p,
footer li,
footer a{
    transition: transform .25s ease, color .25s ease, background-color .25s ease;
}

.hero-badge strong:hover,
.hero-badge span:hover,
footer p:hover,
footer li:hover,
footer a:hover{
    transform: translateY(-1px);
}
</style>
</head>

<body>

<header id="header">
    <div style="display:flex;align-items:center;gap:15px;">
      <a href="Acasa.php" style="display:inline-flex;align-items:center;text-decoration:none;">
        <img src="Image41.png" alt="Logo" style="height:50px;">
      </a>
    </div>

        <div class="nav-wrap">
            <nav id="mainNav">
                <a href="Acasa.php">Acasă</a>
                <a href="Colectie.php">Colecție</a>
                <a href="DespreNoi.php" class="active">Despre Noi</a>
                <a href="Contact.php">Contact</a>
                <a href="CosulMeu.php">Cosul Meu</a>
                <a href="ContulMeu.php">Contul Meu</a>
            </nav>
        </div>

        <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</header>

<main class="page">
    <section class="hero">
        <div class="hero-card">
            <div class="hero-image">
                <img src="Image124.png" alt="Povestea Sherghei Covoare">
            </div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="kicker">Despre noi</div>
                <h1>Suntem Ambasadori ai Păcii mondiale</h1>
                <p>Puterea Obiectelor de Putere ale Păcii, concepute cu ajutorul științei Geometriei Sacre a lui Serghei Danisin, este marită prin tehnologia amplificatoarelor PSY ale motoarelor psihoplasmatice concepute și fabricate de Adrian Pop!</p>
                <div class="hero-badge">
                    <strong>Primul an în care eleganța și calitatea au prins formă prin muncă, răbdare și respect.</strong>
                </div>
            </div>
        </div>

        <div class="side-column">
            <div class="info-card">
                <h3>Despre Noi</h3>
                <h2>Suntem Ambasadori ai Păcii mondiale</h2>
                <p class="lead">Păstrăm o identitate clară. Produsele noastre urmăresc echilibrul dintre estetică, semnificație și utilitate.</p>
                <div class="chips">
                    <?php foreach($benefits as $benefit): ?>
                        <span class="chip"><?= htmlspecialchars($benefit, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="quote-card">
                <p>„Fiecare Obiect de Putere spune o poveste despre necesitatea de a fi Pace la nivel Mondial.Misiunea noastră este să aducem aceste povești în casa ta.”</p>
                <div class="quote-author">— Serghei Danișin, Nicoleta Iliin și Adrian Pop<br>Fondatori! —</div>
            </div>
        </div>
    </section>

    <section class="section-grid">
        <div class="mini-card">
            <h4>Misiunea Noastră</h4>
            <p>Transformăm intenția în formă, forma în vibrație și vibrația în stare, dând viață unor creații unice, amplificate prin tehnologie PSY și susținute de Energia Viului, Conștiința Materiei, Bioenergia și forțele subtile ale ființei umane, pentru echilibru, armonie, evoluție si Pace interioara.</p>
        </div>

        <div class="mini-card">
            <h4>Viziunea Noastră</h4>
            <p>Ne dorim să ratificăm la Sarmizegetusa Regia, Capitala Spirituală a Românilor, Tratatul de Pace între toate Popoarele Lumii până în anul 2050 !</p>
        </div>
    </section>

    <section class="media-grid">
        <div class="video-card">
            <div class="youtube-responsive">
                <iframe
                    src="https://www.youtube.com/embed/xUU1PcMcxgE"
                    title="YouTube video 1"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"></iframe>
            </div>
        </div>

        <div class="video-card">
            <div class="youtube-responsive">
                <iframe
                    src="https://www.youtube.com/embed/og-hz8YWx-Y"
                    title="YouTube video 2"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"></iframe>
            </div>
        </div>
    </section>
</main>

<footer id="footer">
    <div class="footer-container">
        <div class="footer-col">
            <div class="footer-logo">
                <img src="Image41.png" alt="Sherghei Covoare">
            </div>
            <p>Primul an prin care aducem covoare traditionale si moderne in casa ta.</p>
            <div class="footer-social">
                <a href="https://www.facebook.com/maestro.fortunato/" target="_blank" title="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v8h4v-8h3l1-4h-4V6a1 1 0 0 1 1-1h3z"/>
                    </svg>
                </a>
                <a href="https://www.instagram.com/hariharago?igsh=MXd5dHd5ZzY2ZnBpaw==" target="_blank" title="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            <p>Email,office@magazinpsy.ro</p>
            <p>Program, Luni Sambata 9 18</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2024 Sherghei Covoare. Toate drepturile rezervate.</p>
        <div>
            <a href="TermeniSiConditii.php">Termeni si conditii</a>
            <a href="PoliticaDeConfidentialitate.php">Politica de confidentialitate</a>
        </div>
    </div>
</footer>

<script>
function toggleMenu(){
    const nav = document.getElementById('mainNav');
    const toggle = document.querySelector('.menu-toggle');
    nav.classList.toggle('mobile-open');
    toggle.classList.toggle('is-open');
}

document.addEventListener('DOMContentLoaded', function () {
    const current = window.location.pathname.split('/').pop().toLowerCase();
    const links = document.querySelectorAll('nav a');

    links.forEach(link => {
        const href = (link.getAttribute('href') || '').toLowerCase();
        if (href === current || href === 'desprenoi.php' && current === 'desprenoi.php') {
            link.classList.add('active', 'force-hover');
            link.setAttribute('aria-current', 'page');
        }
    });

    const animated = document.querySelectorAll('.hero-card, .info-card, .quote-card, .mini-card, .video-card, .stat, .chip');
    animated.forEach((el, i) => {
        setTimeout(() => {
            el.style.transform = 'translateY(0)';
        }, i * 60);
    });
});

function toggleDarkMode(){
    document.body.classList.toggle('dark-mode');
}
</script>

</body>
</html>