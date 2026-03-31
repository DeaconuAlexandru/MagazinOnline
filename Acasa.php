<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
session_start();

// Limbă
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) $lang = $_GET['lang'];

// Mesaj flash din GET
$flash = '';
if(isset($_GET['success'])) $flash = ($_GET['success']==1) ? "Mesaj trimis cu succes!" : "Te rog completează câmpurile obligatorii!";

// Text site
$txt = [
    'ro'=>[
        'title'=>'Sherghei Covoare',
        'nav'=>['Acasă','Servicii','Colecție','Despre Noi','Contact','Cosul Meu','Contul Meu'],
        'header'=>['Covor PsyGeometry elegant','Primul an de experiență','Covoare de Calitate Excepțională','Descoperă colecția noastră de covoare tradiționale și moderne, fabricate cu măiestrie și pasiune pentru detalii.'],
        'buttons'=>['Vezi Colecția','Solicită Ofertă'],
        'services_title'=>'Ce Oferim',
        'services_desc'=>'Servicii pentru Covoare. De la vânzare și consultanta, oferim o gamă completă de servicii pentru a fi satisfăcut de produsele noastre.',
        'services'=>[
            [
                'title'=>'Punem la dispoziția ta o gamă unică de produse create cu atenție și semnificație:',
                'items'=>[
                    '✨ Îngerii Păcii',
                    '✨ Mandale și Mandale Plasmatizate ale Pacii',
                    '✨ Covoarele Păcii',
                    '✨ Tablourile Păcii (format rotund și dreptunghiular)'
                ]
            ],
            [
                'title'=>'De ce să alegi Magazin PSY?',
                'items'=>[
                    '✔ Experiență de 30 de ani în producția tuturor produselor din portofoliu',
                    '✔ Materiale de cea mai înaltă calitate',
                    '✔ Produse originale, realizate cu filosofie si tehnologie proprie',
                    '✔ Garanție pentru toate produsele',
                    '✔ Consultanță personalizată gratuită',
                    '✔ Prețuri competitive, corecte'
                ]
            ]
        ],
        'collection_title'=>'Colecția Noastră',
        'collection_sub'=>'Covoare de Excepție',
        'collection'=>[
         ['name'=>'Covoare dreptunghiulare ale păcii','type'=>'Covoare dreptunghiulare ale PsyGeometry','price'=>'','img'=>'Image39.png','link'=>'Colectie.php#PsyGeometry'],
        ['name'=>'Îngeraș ai păcii','type'=>'Îngeraș al divinității','price'=>'','img'=>'Image61.png','link'=>'Colectie.php#Ingerasi'],
        ['name'=>'Mandale ale păcii','type'=>'Mandale ale divinității','price'=>'','img'=>'Image71.png','link'=>'Colectie.php#Mandala'],
        ['name'=>'Tablouri ale păcii','type'=>'Tablouri sublime','price'=>'','img'=>'Image123.png','link'=>'Colectie.php#Tablouri'],
        ['name'=>'Covor PsyGeometry Rotund','type'=>'Covor PsyGeometry rotund divin','price'=>'','img'=>'Image143.png','link'=>'Colectie.php#PsyGeometryRotunde']
        ],
        'about_title'=>'Despre Noi – Magazin PSY',
        'about_desc'=>'Suntem Ambasadori ai Păcii mondiale.Puterea Obiectelor de Putere ale Păcii,concepute cu ajutorul științei Geometriei Sacre a lui Serghei Danisin,este marită prin tehnologia amplificatoarelor PSY ale motoarelor psihoplasmatice concepute și fabricate de Adrian Pop!. Principiile de conviețuire sunt atât in Română cât și în Engleză.',
        'about_features'=>['Experiență vastă în productia a tuturor produselor al magazinului PSY','Materiale de cea mai înaltă calitate','Echipă formată din cei mai buni oameni.','Garanție pentru toate produsele','Consultanță personalizată gratuită','Prețuri competitive'],
        'about_quote'=>'„Fiecare Obiect de Putere spune o poveste despre necesitatea de a fi Pace la nivel Mondial.Misiunea noastră este să aducem aceste povești în casa ta.” — Sherghei Danisin, Nicoleta Lilin și Adrian Pop
            Fondatori! —',
        'contact_title'=>'Contactează-ne',
        'contact_desc'=>'Trimite-ne un mesaj sau vizitează-ne în showroom.',
        'contact_info'=>['Adresă'=>'Craiova,Dolj','Telefon'=>'0764.049.235','Email'=>'office@magazinpsy.ro','Program'=>'Luni - Sâmbătă: 9:00 - 18:00'],
        'map_embed'=>'<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2849.123456!2d26.100000!3d44.400000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1234567890abc%3A0xabcdef1234567890!2sStrada%20Covoarelor%2012%2C%20București!5e0!3m2!1sro!2sro!4v1699999999999!5m2!1sro!2sro" allowfullscreen="" loading="lazy"></iframe>'
    ]
];
// Credentiale DB (schimba daca hostul nu e localhost)
$dbHost = 'localhost';
$dbName = 'magazi15_ShergeiCovoare';
$dbUser = 'magazi15_Alex';
$dbPass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';


try {
$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Pentru debugging: permite sa vezi eroarea. In productie logheaza si afiseaza un mesaj generic.
    exit("Eroare DB: " . $e->getMessage());
}

// Trimitere feedback cu Post/Redirect/Get
$success = '';
$error = '';
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['submit_feedback'])){
    if(!isset($_SESSION['user_id'])){
        $error = "Trebuie să fii logat pentru a trimite feedback.";
    } else {
        $message = trim($_POST['message'] ?? '');
        if($message === ''){
            $error = "Completează mesajul.";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO feedback (user_id, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $message]);
                $feedback_id = $pdo->lastInsertId();

                // Upload imagine
                if(!empty($_FILES['image']['name'])){
                    $uploadDir = __DIR__ . '/uploads/';
                    if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileTmp = $_FILES['image']['tmp_name'];
                    $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $newName = 'feedback_'.time().rand(100,999).'.'.$fileExt;
                    $target = $uploadDir.$newName;

                    if(is_uploaded_file($fileTmp) && move_uploaded_file($fileTmp, $target)){
                        $stmt2 = $pdo->prepare("INSERT INTO feedback_images (feedback_id, image_path) VALUES (?, ?)");
                        $stmt2->execute([$feedback_id, 'uploads/'.$newName]);
                    }
                }

                $pdo->commit();

                // Post/Redirect/Get: salvăm mesajul în sesiune și redirect
                $_SESSION['success_feedback'] = "Feedback-ul tău a fost trimis!";
                header("Location: ".$_SERVER['REQUEST_URI']);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Eroare la trimiterea feedback-ului: ".$e->getMessage();
            }
        }
    }
}

// Afișare mesaj succes după redirect
if(isset($_SESSION['success_feedback'])){
    $success = $_SESSION['success_feedback'];
    unset($_SESSION['success_feedback']);
}

// Preluare feedback-uri
try {
    $stmt = $pdo->query("
        SELECT f.id, f.message, f.created_at,
               COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), u.email, 'Utilizator') AS author_name,
               GROUP_CONCAT(fi.image_path SEPARATOR '|') AS images
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.id
        LEFT JOIN feedback_images fi ON fi.feedback_id = f.id
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ");
    $feedbacks = $stmt->fetchAll();
} catch (Exception $e) {
    $feedbacks = [];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $txt[$lang]['title'] ?></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Roboto',sans-serif;color:#111;background:#fdf6f0;scroll-behavior:smooth;transition:background 0.3s,color 0.3s;}
.dark-mode{background:#111;color:#fdf6f0;}
header{position:sticky;top:0;z-index:10;background:#8C92AC;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;transition:background 0.3s;}
header.dark-mode{background:#222;}
header h1{font-family:'Montserrat',sans-serif;font-weight:700;font-size:30px;margin:0;transition:color 0.3s;}
.header-contact{display:flex;gap:15px;align-items:center;}
.header-contact .phone{font-weight:600;}
.header-contact .btn{background:#fff;color:#b5651d;padding:12px 25px;border-radius:8px;text-decoration:none;transition:0.3s;font-weight:500;position:relative;}
.header-contact .btn::after{content:"";position:absolute;top:0;left:0;width:100%;height:100%;border-radius:8px;box-shadow:0 0 15px rgba(255,165,0,0);transition:0.3s;}
.header-contact .btn:hover{background:#8b4315;color:#fff;transform:translateY(-2px);}
.header-contact .btn:hover::after{box-shadow:0 0 15px rgba(255,165,0,0.7);}
.dark-toggle{cursor:pointer;padding:10px;border-radius:8px;background:#fff;color:#b5651d;font-weight:600;transition:0.3s;}
.dark-toggle:hover{background:#b5651d;color:#fff;}

/* Nav */
nav{display:flex;justify-content:center;gap:15px;margin:20px 0;flex-wrap:wrap;position:relative;transition:max-height 0.5s;}
nav a{text-decoration:none;padding:10px 18px;background:#fff;color:#b5651d;border-radius:8px;transition:.3s;font-weight:500;}
nav a.active{background:#8b4315;color:#fff;}
nav a:hover{background:#b5651d;color:#fff;transform:translateY(-2px);}


/* HERO - scalabil după conținut */
.hero{
  position: relative;
  text-align: center;
  padding: clamp(40px, 10vh, 120px) 20px; /* se scalează cu viewport și conținut */
  background-image: url('images/hero-bg.jpg');
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
  color: #fff;
  overflow: visible; /* permite extinderea pe verticală */
  box-sizing: border-box;
  z-index: 0;
}

.hero::before{
  content: "";
  position: absolute;
  inset: 0;
  background:
    linear-gradient(135deg, #25206F, #4B3FBF, #7A6BFF),
    url("images/Image122.png");
  background-size: cover, cover;
  background-position: center center, center center;
  background-repeat: no-repeat, no-repeat;
  background-blend-mode: overlay;
  pointer-events: none;
  z-index: 1;
  opacity: 0.95;
}

/* asigură textul deasupra overlay-ului */
.hero > *{
  position: relative;
  z-index: 2;
}

.hero h2{
  font-size: 42px;
  font-family: 'Montserrat', sans-serif;
  margin: 0 0 15px;
  animation: fadeUp 1s ease forwards;
}

.hero p{
  font-size: 20px;
  max-width: 700px;
  margin: 0 auto 20px;
  animation: fadeUp 1.2s ease forwards;
  color: #fff;
}

.hero .btn{
  position: relative;
  margin: 10px;
  padding: 14px 28px;
  background: #b5651d;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  display: inline-block;
  transition: transform 0.3s, box-shadow 0.3s;
  font-weight: 500;
  animation: fadeUp 1.4s ease forwards;
}

.hero .btn::after{
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  box-shadow: 0 0 0 rgba(255,165,0,0);
  transition: box-shadow 0.3s;
  pointer-events: none;
}

.hero .btn:hover{
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}
/* Stats */
.stats{display:flex;justify-content:center;gap:30px;margin:50px 0;text-align:center;flex-wrap:wrap;}
.stats div{background:#fff;padding:25px;border-radius:16px;box-shadow:0 8px 16px rgba(0,0,0,0.15);flex:1 1 150px;margin:10px;transition:transform 0.3s,box-shadow 0.3s;}
.stats div:hover{transform:translateY(-8px) scale(1.03);box-shadow:0 12px 28px rgba(0,0,0,0.25);}

/* Services layout egal si stilizat */
.services{
  padding: 60px 20px;
  text-align: center;
  transition: background 0.3s, color 0.3s;
}

.services .service-grid{
  max-width: 1100px;
  margin: 30px auto 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  align-items: stretch;
}

/* Carduri egale */
.services .card{
  width: 100%;
  margin: 0;
  padding: 28px 24px;
  border-radius: 20px;
  background: linear-gradient(180deg, #ffffff 0%, #faf7f2 100%);
  box-shadow: 0 12px 30px rgba(0,0,0,0.12);
  border: 1px solid rgba(181, 101, 29, 0.08);
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  opacity: 0;
  transform: translateY(18px);
  transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
  min-height: 100%;
}

.services .card.visible{
  opacity: 1;
  transform: translateY(0);
}

.services .card h3{
  margin-top: 0;
  margin-bottom: 14px;
  font-size: 20px;
  line-height: 1.3;
  color: #1f1f1f;
}

.services .card .service-list{
  margin-top: 0;
  flex: 1;
}

.services .card:hover{
  transform: translateY(-8px);
  box-shadow: 0 16px 34px rgba(0,0,0,0.18);
  background: linear-gradient(180deg, #fff8f1 0%, #fff2e6 100%);
}

.service-list{
  list-style: none;
  padding: 0;
  margin: 12px 0 0 0;
  text-align: left;
}

.service-list li{
  margin-bottom: 10px;
  line-height: 1.6;
}
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

/* Carduri dark mode */
body.dark-mode .card {
  background-color: #222;
  color: #fdf6f0;
  box-shadow: 0 8px 16px rgba(255,255,255,0.05);
}

/* Butoane dark mode */
body.dark-mode .btn {
  background-color: #b5651d;
  color: #fff;
}

body.dark-mode .btn:hover {
  background-color: #8b4315;
  color: #fff;
}

/* Linkuri nav dark mode */
body.dark-mode nav a {
  background-color: #333;
  color: #fdf6f0;
}

body.dark-mode nav a:hover,
body.dark-mode nav a.active {
  background-color: #8b4315;
  color: #fff;
}

/* Harta și iframe */
body.dark-mode .map-container iframe {
  filter: invert(90%) contrast(90%);
}
/* Stats dark mode */
body.dark-mode .stats div {
  background-color: #222;
  color: #fdf6f0;
  box-shadow: 0 8px 16px rgba(255,255,255,0.05);
}

/* About badge dark mode */
body.dark-mode .about-badge {
  background-color: #b5651d; /* sau alt portocaliu mai deschis */
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

/* Ascunde nav pe mobil, afișează hamburger */
header nav {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

header .icon {
  display: none;
  font-size: 26px;
  cursor: pointer;
  color: #fff;
}

/* Mobile styles */
@media screen and (max-width: 768px){
  #mainNav{
    display: none;
  }
  #mainNav.mobile-open{
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 10px;
    padding: 10px 0;
  }
    /* Hamburger meniu mobil */
    .menu-toggle {
      display: none; /* ascuns implicit */
      flex-direction: column;
      cursor: pointer;
      gap: 5px;
    }
    .menu-toggle div {
      width: 30px;
      height: 3px;
      background: #fff;
      margin: 5px 0;
      transition: 0.3s;
}
}

    @media screen and (max-width:768px){
      .menu-toggle {
        display: flex;
      }
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
.feedback-section {
    max-width: 480px;
    margin: 24px auto;
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    font-family: Arial, sans-serif;
    font-size: 14px;
}


.feedback-section h2 {
    text-align: center;
    margin-bottom: 12px;
    color: #333;
    font-size: 18px;
}

.feedback-section form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}


.feedback-section textarea {
    padding: 8px;
    font-size: 13px;
    resize: vertical;
    min-height: 60px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

.feedback-section input[type="file"] {
    padding: 4px 0;
    font-size: 13px;
}
.feedback-section button {
    width: 120px;
    padding: 8px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    align-self: flex-start;
    font-size: 14px;
}

.feedback-section button:hover {
    background: #0056b3;
}

.feedback-list {
    margin-top: 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.feedback-card {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
}

.feedback-card p {
    margin: 4px 0;
}

.feedback-card small {
    color: #666;
    font-size: 12px;
}

.feedback-images {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.feedback-images img {
    max-width: 80px;
    max-height: 80px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.feedback-section { max-width:800px; margin:40px auto; padding:20px; border:1px solid #ddd; border-radius:8px; background:#f9f9f9; }
.feedback-section h2 { text-align:center; margin-bottom:20px; color:#333; }
.feedback-section form { display:flex; flex-direction:column; gap:10px; }
.feedback-section textarea { padding:10px; font-size:14px; resize:vertical; min-height:80px; border-radius:5px; border:1px solid #ccc; }
.feedback-section button { width:150px; padding:10px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer; }
.feedback-section button:hover { background:#0056b3; }
.feedback-list { margin-top:30px; display:flex; flex-direction:column; gap:20px; }
.feedback-card { padding:15px; border:1px solid #ddd; border-radius:8px; background:#fff; }
.feedback-card p { margin:5px 0; }
.feedback-card small { color:#666; }
.feedback-images { margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; }
.feedback-images img { max-width:120px; max-height:120px; object-fit:cover; border-radius:5px; border:1px solid #ccc; }

/* forțează hover pe linkul curent (Acasa) */
nav a.force-hover,
nav a.force-hover:hover,
nav a.force-hover:focus {
  background: #8b4315 !important;
  color: #fff !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important;
}
.service-list{
    list-style: none;
    padding: 0;
    margin: 12px 0 0 0;
    text-align: left;
}

.service-list li{
    margin-bottom: 8px;
    line-height: 1.6;
}
.collection{
  padding: 60px 20px;
  text-align: center;
}

.collection-grid{
  max-width: 1000px;
  margin: 30px auto 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 240px));
  justify-content: center;
  gap: 24px;
}

.collection .collection-item{
  width: 100%;
  margin: 0;
  padding: 16px;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 12px 28px rgba(0,0,0,0.12);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  opacity: 0;
  transform: translateY(16px);
}

.collection .collection-item.visible{
  opacity: 1;
  transform: translateY(0);
}

.collection .collection-item img{
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 14px;
  display: block;
  margin-bottom: 12px;
}

.collection .collection-item h3{
  margin: 10px 0 6px;
  font-size: 18px;
}

.collection .collection-item p{
  margin: 4px 0;
  font-size: 14px;
}

.collection .collection-item:hover{
  transform: translateY(-6px);
  box-shadow: 0 16px 32px rgba(0,0,0,0.18);
}

@media screen and (max-width: 768px){
  .collection-grid{
    grid-template-columns: 1fr;
    max-width: 320px;
  }

  .collection .collection-item img{
    height: 160px;
  }
}
.collection-link{
  text-decoration: none;
  color: inherit;
  display: block;
}

.collection-link:hover{
  text-decoration: none;
  color: inherit;
}
</style>
</head>
<body>

<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">

    <!-- Logo stânga -->
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image41.png" alt="Logo" style="height:50px;">
    </div>

    <!-- Meniu centrat -->
    <nav id="mainNav">
      <a href="Acasa.php"class="active">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
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
<!-- Hero -->
<div class="hero" id="hero">
  <h2><?= htmlspecialchars($txt[$lang]['header'][0] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= htmlspecialchars($txt[$lang]['header'][3] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
<?php
// elimină diacritice românești
function remove_accents(string $s): string {
    $map = [
        'Ă'=>'A','ă'=>'a','Â'=>'A','â'=>'a','Î'=>'I','î'=>'i',
        'Ș'=>'S','ș'=>'s','Ş'=>'S','ş'=>'s','Ț'=>'T','ț'=>'t','Ţ'=>'T','ţ'=>'t'
    ];
    return strtr($s, $map);
}
// lowercase sigur
function safe_strtolower(string $s): string {
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

foreach (($txt[$lang]['buttons'] ?? []) as $btn) {
    $label = trim((string)$btn);
    $norm = safe_strtolower(remove_accents($label));

    $isColectie = ($norm === 'vezi colectia');
    // detectează butonul "Solicită Ofertă" robust, indiferent de diacritice
    $isSolicitaOferta = (strpos($norm, 'solicita') !== false && strpos($norm, 'oferta') !== false)
                     || (strpos($norm, 'solicita') !== false && strpos($norm, 'ofertă') !== false);

    // link: dacă vrei navigare între pagini folosește 'Acasa.php#contact'
    // dacă vrei sări doar pe aceeași pagină folosește '#contact'
    if ($isColectie) {
        $href = 'Colectie.php';
    } elseif ($isSolicitaOferta) {
        $href = 'Contact.php';
    } else {
        $href = '#collection';
    }

    echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="btn">' . htmlspecialchars($btn, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
</div>
<!-- Colecție -->
<div class="collection" id="collection">
  <h2><?= $txt[$lang]['collection_title'] ?></h2>
  <p><?= $txt[$lang]['collection_sub'] ?></p>

  <div class="collection-grid">
    <?php foreach($txt[$lang]['collection'] as $c){ ?>
      <a href="<?= htmlspecialchars($c['link'], ENT_QUOTES, 'UTF-8') ?>" class="collection-link">
        <div class="card collection-item">
          <img data-src="images/<?= $c['img'] ?>" alt="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>" class="lazy" loading="lazy">
          <h3><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($c['type'], ENT_QUOTES, 'UTF-8') ?></p>
          <p><strong><?= htmlspecialchars($c['price'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>
      </a>
    <?php } ?>
  </div>
</div>
<div class="stats">
<?php foreach($txt[$lang]['stats'] as $num=>$desc){ ?>
  <div><h3><?= $num ?></h3><p><?= $desc ?></p></div>
<?php } ?>
</div>

<div class="services" id="services">
  <h2><?= $txt[$lang]['services_title'] ?></h2>
  <p><?= $txt[$lang]['services_desc'] ?></p>

  <div class="service-grid">
    <?php foreach($txt[$lang]['services'] as $s){ ?>
      <div class="card">
          <h3><?= htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') ?></h3>
          <ul class="service-list">
              <?php foreach($s['items'] as $item){ ?>
                  <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
              <?php } ?>
          </ul>
      </div>
    <?php } ?>
  </div>
</div>

	<!-- About Section -->
<div class="about" id="about">
  <div class="about-wrapper">
    
    <div class="about-image-box">
        <img src="Image4.png" alt="About Image">
        <div class="about-badge">
            <strong>Suntem Ambasadori ai Pacii Mondiale</strong>
            <span>Principiile de convietuire este in Romania si in engleza</span>
        </div>
    </div>

    <div class="about-text">
      <h2><?= $txt[$lang]['about_title'] ?></h2>
      <p><?= $txt[$lang]['about_desc'] ?></p>

      <ul>
        <?php foreach($txt[$lang]['about_features'] as $f){ ?>
          <li><?= $f ?></li>
        <?php } ?>
      </ul>

      <blockquote><?= $txt[$lang]['about_quote'] ?></blockquote>
    </div>
  </div>
</div>

<div class="feedback-section">
    <h2>Lasă-ne un feedback!</h2>

    <?php if(!empty($success)): ?>
        <p style="color:green"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Formular feedback -->
    <?php if(isset($_SESSION['user_id'])): ?>
        <form method="post" enctype="multipart/form-data" accept-charset="UTF-8">
            <textarea name="message" placeholder="Scrie mesajul tău..." required></textarea>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="submit_feedback">Trimite Feedback</button>
        </form>
    <?php else: ?>
        <p>Trebuie să fii logat pentru a lăsa feedback. <a href="Login.php">Autentifică-te</a></p>
    <?php endif; ?>

    <!-- Lista feedback-urilor -->
    <div class="feedback-list">
        <?php if(!empty($feedbacks)): ?>
            <?php foreach($feedbacks as $fb): ?>
                <div class="feedback-card">
                    <p>
                        <strong>
                            <?= htmlspecialchars($fb['author_name'] ?? ($fb['name'] ?? 'Utilizator'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </strong>
                        <small><?= date("d.m.Y H:i", strtotime($fb['created_at'] ?? '')) ?></small>
                    </p>

                    <p>
                        <?= nl2br(htmlspecialchars($fb['message'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                    </p>

                    <?php if(!empty($fb['images'])):
                        $imgs = explode('|', $fb['images']); ?>
                        <div class="feedback-images">
                            <?php foreach($imgs as $img): ?>
                                <?php if(!empty($img) && file_exists(__DIR__ . '/' . $img)): ?>
                                    <img src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Feedback Image">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nu există încă feedback-uri.</p>
        <?php endif; ?>
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
  'use strict';

  // Helperuri
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const noop = () => {};
  const debounce = (fn, wait = 120) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  };
  const throttle = (fn, limit = 100) => {
    let last = 0;
    return (...args) => {
      const now = Date.now();
      if (now - last >= limit) { last = now; fn(...args); }
    };
  };

  /* ===============================
     Configuri si constante
     =============================== */
  const ADMIN_MARKER = 'mesaje contact - admin';
  const ADMIN_TOKENS = ['id','nume','telefon','email','mesaj','data','status','actiuni'];
  const HEADER_BG = '#8C92AC';
  const HERO_SELECTOR = '.hero';
  const LAZY_SELECTOR = 'img.lazy';
  const CARD_SELECTOR = '.card';
  const HERO_ANIM_SEL = '.hero h2, .hero p, .hero .btn';

  /* ===============================
     Detectare si eliminare elemente admin
     =============================== */
  function stripDiacritics(s = '') {
    try { return s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
    catch(e){ return s; }
  }

  function txtNorm(el) {
    if(!el) return '';
    const t = (el.textContent || el.innerText || '').replace(/\s+/g,' ').trim().toLowerCase();
    return stripDiacritics(t);
  }

  function looksLikeAdmin(el) {
    if(!el) return false;
    const t = txtNorm(el);
    if(!t) return false;
    if(t.includes(stripDiacritics(ADMIN_MARKER))) return true;
    const hasAll = ADMIN_TOKENS.every(tok => t.includes(tok));
    if(hasAll) return true;
    const cls = stripDiacritics(((el.className || '') + ' ' + (el.id || '')).toLowerCase());
    if(/(^|\W)(admin|mesaje|adminmessages|admin-panel|adminpanel)(\W|$)/.test(cls)) return true;
    return false;
  }

  function removeContainer(el) {
    if(!el) return;
    const container = el.closest('tfoot') || el.closest('thead') || el.closest('table') || el.closest('section') || el.closest('article') || el.closest('div') || el;
    if(container && container.parentNode) container.parentNode.removeChild(container);
  }

  function removeAdminElements(root = document) {
    try {
      // check common header elements first
      const candidates = $$( 'thead, tfoot, table, tr, section, article, div, aside, caption, h1, h2, h3, th', root );
      for(const el of candidates) {
        if(looksLikeAdmin(el)) removeContainer(el);
      }

      // fallback scan for body children (limited depth)
      const bodyChildren = Array.from(document.body.children);
      for(const node of bodyChildren) {
        if(looksLikeAdmin(node)) removeContainer(node);
      }
    } catch(e) { /* silent */ }
  }

  /* ===============================
     Force header background
     =============================== */
  function enforceHeaderColor() {
    try {
      const id = 'sherghei-header-color';
      if(!document.getElementById(id)){
        const s = document.createElement('style');
        s.id = id;
        s.textContent = `header, header#header { background-color: ${HEADER_BG} !important; } header.dark-mode { background-color: ${HEADER_BG} !important; }`;
        document.head.appendChild(s);
      }
      const h = document.querySelector('header');
      if(h){
        h.style.setProperty('background-color', HEADER_BG, 'important');
      }
    } catch(e) {}
  }

  /* ===============================
     Lazy load imagini (IntersectionObserver)
     =============================== */
  function initLazyLoad() {
    const imgs = $$(LAZY_SELECTOR);
    if(imgs.length === 0) return;
    if('IntersectionObserver' in window){
      const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if(entry.isIntersecting){
            const img = entry.target;
            const src = img.dataset && img.dataset.src ? img.dataset.src : img.getAttribute('data-src');
            if(src) img.src = src;
            img.classList.remove('lazy');
            io.unobserve(img);
          }
        });
      }, { rootMargin: '200px 0px' });
      imgs.forEach(i => io.observe(i));
    } else {
      // fallback
      imgs.forEach(img => {
        const src = img.dataset && img.dataset.src ? img.dataset.src : img.getAttribute('data-src');
        if(src) img.src = src;
        img.classList.remove('lazy');
      });
    }
  }

  /* ===============================
     Card si hero animatii la intrare
     =============================== */
  function animateOnLoad() {
    const cards = $$(CARD_SELECTOR);
    cards.forEach((card, i) => setTimeout(() => card.classList.add('visible'), i * 120));

    const heroEls = $$(HERO_ANIM_SEL);
    heroEls.forEach((el, i) => setTimeout(() => el.classList.add('visible'), i * 180));
  }

  /* ===============================
     Dark mode toggle cu localStorage
     =============================== */
  function initDarkMode() {
    const apply = on => {
      document.body.classList.toggle('dark-mode', on);
      const header = document.querySelector('header');
      const footer = document.querySelector('footer');
      header && header.classList.toggle('dark-mode', on);
      footer && footer.classList.toggle('dark-mode', on);
      enforceHeaderColor();
    };
    const stored = localStorage.getItem('darkMode') === '1';
    apply(stored);
    window.toggleDarkMode = function(){
      const now = !document.body.classList.contains('dark-mode');
      try{ localStorage.setItem('darkMode', now ? '1' : '0'); } catch(e){}
      apply(now);
    };
  }

  /* ===============================
     Responsive menu logic
     =============================== */
  function initMenu() {
    const nav = document.getElementById('mainNav');
    if(!nav) return;
    const adjust = () => {
      const links = Array.from(nav.querySelectorAll('a'));
      if(window.innerWidth <= 768){
        nav.style.flexDirection = 'column';
        nav.style.alignItems = 'flex-start';
        nav.style.width = '100%';
        nav.style.gap = '10px';
        const mobileLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
        links.forEach(link => {
          const href = link.getAttribute('href') || '';
          if(mobileLinks.includes(href)) link.style.display = 'block'; else link.style.display = 'none';
        });
        // ensure order
        mobileLinks.forEach(file => {
          const link = links.find(a => a.getAttribute('href') === file);
          if(link) nav.appendChild(link);
        });
      } else {
        nav.style.flexDirection = 'row';
        nav.style.alignItems = 'center';
        nav.style.gap = '15px';
        links.forEach(link => link.style.display = 'inline-block');
      }
    };

    window.toggleMenu = () => {
      nav.classList.toggle('mobile-open');
      adjust();
    };

    adjust();
    window.addEventListener('resize', debounce(adjust, 100));
  }

  /* ===============================
     Scroll spy pentru highlight meniu
     =============================== */
  function initScrollSpy() {
    const sections = $$('.hero, .services, .collection, .about, .contact').filter(Boolean);
    const navLinks = Array.from(document.querySelectorAll('nav a'));
    if(sections.length === 0 || navLinks.length === 0) return;

    const sectionMap = sections.map(s => ({
      id: s.id || '',
      top: () => s.getBoundingClientRect().top + window.pageYOffset
    }));

    const onScroll = throttle(() => {
      let currentId = '';
      const offset = 150;
      const y = window.pageYOffset;
      for(const s of sectionMap){
        if(y >= s.top() - offset) currentId = s.id;
      }
      navLinks.forEach(a => {
        a.classList.toggle('active', (a.getAttribute('href') === '#' + currentId) && currentId);
      });
    }, 120);

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ===============================
     Smooth parallax for hero background
     =============================== */
  function initParallax() {
    const hero = document.querySelector(HERO_SELECTOR);
    if(!hero) return;
    const onScroll = throttle(() => {
      try {
        hero.style.backgroundPositionY = -(window.scrollY * 0.3) + 'px';
      } catch(e){}
    }, 20);
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ===============================
     Observe DOM mutatii si curata
     =============================== */
  function initMutationObserver() {
    try {
      const moCb = debounce(mutations => {
        removeAdminElements(document);
        enforceHeaderColor();
      }, 150);

      const mo = new MutationObserver(moCb);
      mo.observe(document.body, { childList: true, subtree: true });
      // expune pentru debugging daca e nevoie
      window.__sherghei_admin_observer = mo;
    } catch(e){}
  }

  /* ===============================
     Init general la DOMContentLoaded
     =============================== */
  document.addEventListener('DOMContentLoaded', function(){
    // curata initial
    removeAdminElements(document);
    enforceHeaderColor();

    // init module
    initLazyLoad();
    animateOnLoad();
    initDarkMode();
    initMenu();
    initScrollSpy();
    initParallax();
    initMutationObserver();
  });

})();
(function(){
  'use strict';
  try {
    // obține numele fișierului curent
    const path = window.location.pathname.split('/').pop().toLowerCase();
    const currentFiles = ['acasa.php', '','index.php','home.php']; // adaptează dacă ai alte nume
    if(currentFiles.includes(path)) {
      // caută linkul care duce la Acasa.php sau link cu text "Acasă"
      let link = document.querySelector('nav a[href="Acasa.php"]') 
              || Array.from(document.querySelectorAll('nav a')).find(a => (a.textContent||'').trim().toLowerCase() === 'acasă' || (a.textContent||'').trim().toLowerCase() === 'acasa');
      if(link){
        link.classList.add('active');
        link.classList.add('force-hover');
        link.setAttribute('aria-current','page');
      }
    }
  } catch(e) { console.error(e); }
})();
document.addEventListener('DOMContentLoaded', function(){
  try {
    const map = document.querySelector('.map-container');
    const feedbacks = document.querySelectorAll('.feedback-section');
    if(!map || feedbacks.length === 0) return;

    // mutăm prima secțiune feedback imediat după map
    const first = feedbacks[0];
    map.parentNode.insertBefore(first, map.nextSibling);

    // eliminăm duplicatele rămase (dacă există)
    for(let i = 1; i < feedbacks.length; i++){
      feedbacks[i].remove();
    }

    // ușoare ajustări vizuale
    first.style.marginTop = '24px';
    first.style.marginBottom = '30px';
    first.style.maxWidth = '800px';
    first.style.width = '100%';
    first.style.boxSizing = 'border-box';
  } catch(e){
    // silent fail
    console.error(e);
  }
});
</script>

</body>
</html>
