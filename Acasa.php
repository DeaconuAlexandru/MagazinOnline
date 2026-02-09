<?php
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
        'header'=>['Covor persan elegant','Peste 20 de ani de experiență','Covoare de Calitate Excepțională','Descoperă colecția noastră de covoare tradiționale și moderne, fabricate cu măiestrie și pasiune pentru detalii.'],
        'buttons'=>['Vezi Colecția','Solicită Ofertă'],
        'services_title'=>'Ce Oferim',
        'services_desc'=>'Servicii Complete pentru Covoare. De la vânzare la curățare și restaurare, oferim o gamă completă de servicii pentru a păstra frumusețea covoarelor tale.',
        'services'=>[
            ['title'=>'Curățare Profesională','desc'=>'Curățare în profunzime pentru toate tipurile de covoare, cu tehnologii moderne și ecologice.'],
            ['title'=>'Reparații & Restaurare','desc'=>'Restaurăm covoarele deteriorate, păstrând autenticitatea și valoarea lor.'],
            ['title'=>'Livrare Gratuită','desc'=>'Livrare gratuită pentru comenzi și transport servicii curățare.'],
            ['title'=>'Garanție Extinsă','desc'=>'Toate covoarele vin cu garanție extinsă și suport post-vânzare dedicat.']
        ],
        'collection_title'=>'Colecția Noastră',
        'collection_sub'=>'Covoare de Excepție',
        'collection'=>[
            ['name'=>'Covor Persan Royal','type'=>'Tradițional','price'=>'350 Lei','img'=>'Image4.png'],
            ['name'=>'Covor Modern Abstract','type'=>'Contemporan','price'=>'350 Lei','img'=>'Image15.png'],
            ['name'=>'Covor Oriental Clasic','type'=>'Tradițional','price'=>'350 Lei','img'=>'Image12.png']
        ],
        'about_title'=>'Despre Noi',
        'about_desc'=>'Pasiune pentru Meșteșugul Covoarelor. De peste două decenii, Sherghei Covoare aduce în casele românilor cele mai frumoase și durabile covoare. Suntem dedicați excelenței și satisfacției clienților noștri.',
        'about_features'=>['Experiență de peste 20 de ani','Materiale de cea mai înaltă calitate','Echipă de meșteri specializați','Garanție pentru toate produsele','Consultanță personalizată gratuită','Prețuri competitive'],
        'about_quote'=>'„Fiecare covor spune o poveste. Misiunea noastră este să aducem acele povești în casa ta.” — Sherghei, Fondator —',
        'contact_title'=>'Contactează-ne',
        'contact_desc'=>'Trimite-ne un mesaj sau vizitează-ne în showroom.',
        'contact_info'=>['Adresă'=>'Str. Covoarelor Nr. 12, București','Telefon'=>'+40 123 456 789','Email'=>'contact@sherghei-covoare.ro','Program'=>'Luni - Sâmbătă: 9:00 - 18:00'],
        'map_embed'=>'<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2849.123456!2d26.100000!3d44.400000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40b1234567890abc%3A0xabcdef1234567890!2sStrada%20Covoarelor%2012%2C%20București!5e0!3m2!1sro!2sro!4v1699999999999!5m2!1sro!2sro" allowfullscreen="" loading="lazy"></iframe>'
    ]
];

// Conexiune DB
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
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
        SELECT f.id, f.message, f.created_at, u.name,
               GROUP_CONCAT(fi.image_path SEPARATOR '|') AS images
        FROM feedback f
        JOIN users u ON f.user_id = u.id
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
header{position:sticky;top:0;z-index:10;background:#b5651d;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;transition:background 0.3s;}
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

/* Hero */
.hero{position:relative;text-align:center;padding:120px 20px;background:url('hero-bg.jpg') center/cover no-repeat;color:#fff;overflow:hidden;transition:transform 0.3s;}
.hero::before{content:"";position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);}
.hero h2{position:relative;font-size:42px;font-family:'Montserrat',sans-serif;margin:0 0 15px;animation:fadeUp 1s ease forwards;}
.hero p{position:relative;font-size:20px;max-width:700px;margin:0 auto 20px;animation:fadeUp 1.2s ease forwards;}
.hero .btn{position:relative;margin:10px;padding:14px 28px;background:#b5651d;color:#fff;border-radius:8px;text-decoration:none;display:inline-block;transition:0.3s;font-weight:500;animation:fadeUp 1.4s ease forwards;}
.hero .btn::after{content:"";position:absolute;top:0;left:0;width:100%;height:100%;border-radius:8px;box-shadow:0 0 0 rgba(255,165,0,0);transition:0.3s;}
.hero .btn:hover{transform:translateY(-3px) scale(1.05);}
.hero .btn:hover::after{box-shadow:0 0 25px rgba(255,165,0,0.8);}

/* Stats */
.stats{display:flex;justify-content:center;gap:30px;margin:50px 0;text-align:center;flex-wrap:wrap;}
.stats div{background:#fff;padding:25px;border-radius:16px;box-shadow:0 8px 16px rgba(0,0,0,0.15);flex:1 1 150px;margin:10px;transition:transform 0.3s,box-shadow 0.3s;}
.stats div:hover{transform:translateY(-8px) scale(1.03);box-shadow:0 12px 28px rgba(0,0,0,0.25);}

/* Carduri */
.services,.collection,.about,.contact{padding:60px 20px;text-align:center;transition:background 0.3s,color 0.3s;}
.card{background:#fff;padding:25px;margin:20px;border-radius:16px;display:inline-block;width:260px;vertical-align:top;box-shadow:0 8px 16px rgba(0,0,0,0.15);transition:transform 0.3s,box-shadow 0.3s,background 0.3s,opacity 0.5s;opacity:0;transform:translateY(20px) rotateX(1deg);}
.card.visible{opacity:1;transform:translateY(0) rotateX(0);}
.services .card:hover,.collection-item:hover{transform:translateY(-10px) rotateX(3deg);box-shadow:0 12px 24px rgba(0,0,0,0.3);background:#fdf0e6;}
.services .card:hover h3,p,.collection-item:hover h3,p strong{color:#b5651d;transition:color 0.3s;}

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
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    font-family: Arial, sans-serif;
}

.feedback-section h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.feedback-section form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.feedback-section textarea {
    padding: 10px;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

.feedback-section input[type="file"] {
    padding: 5px 0;
}

.feedback-section button {
    width: 150px;
    padding: 10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.feedback-section button:hover {
    background: #0056b3;
}

.feedback-list {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.feedback-card {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}

.feedback-card p {
    margin: 5px 0;
}

.feedback-card small {
    color: #666;
}

.feedback-images {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.feedback-images img {
    max-width: 120px;
    max-height: 120px;
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
<div class="feedback-section">
    <h2>Lasă-ne un feedback!</h2>

    <!-- Mesaje succes/eroare -->
    <?php if(!empty($success)): ?>
        <p style="color:green"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Formular feedback -->
    <?php if(isset($_SESSION['user_id'])): ?>
        <form method="post" enctype="multipart/form-data">
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
                    <p><strong><?= htmlspecialchars($fb['name']) ?></strong>
                       <small><?= date("d.m.Y H:i", strtotime($fb['created_at'])) ?></small></p>
                    <p><?= nl2br(htmlspecialchars($fb['message'])) ?></p>

                    <?php if(!empty($fb['images'])): 
                        $imgs = explode('|', $fb['images']); ?>
                        <div class="feedback-images">
                            <?php foreach($imgs as $img): ?>
                                <?php if(file_exists($img)): ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Feedback Image">
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

<!-- Hero -->
<div class="hero" id="hero" style="background-image: url('images/hero-bg.jpg');">
  <h2><?= $txt[$lang]['header'][0] ?></h2>
  <p><?= $txt[$lang]['header'][3] ?></p>
  <?php foreach($txt[$lang]['buttons'] as $btn){ ?>
    <a href="#collection" class="btn"><?= $btn ?></a>
  <?php } ?>
</div>

<div class="stats">
<?php foreach($txt[$lang]['stats'] as $num=>$desc){ ?>
  <div><h3><?= $num ?></h3><p><?= $desc ?></p></div>
<?php } ?>
</div>

<div class="services" id="services">
  <h2><?= $txt[$lang]['services_title'] ?></h2>
  <p><?= $txt[$lang]['services_desc'] ?></p>
  <?php foreach($txt[$lang]['services'] as $s){ ?>
    <div class="card"><h3><?= $s['title'] ?></h3><p><?= $s['desc'] ?></p></div>
  <?php } ?>
</div>

<!-- Colecție -->
<div class="collection" id="collection">
  <h2><?= $txt[$lang]['collection_title'] ?></h2>
  <p><?= $txt[$lang]['collection_sub'] ?></p>
  <?php foreach($txt[$lang]['collection'] as $c){ ?>
    <div class="card collection-item">
      <img data-src="images/<?= $c['img'] ?>" alt="<?= $c['name'] ?>" class="lazy" loading="lazy">
      <h3><?= $c['name'] ?></h3>
      <p><?= $c['type'] ?></p>
      <p><strong><?= $c['price'] ?></strong></p>
    </div>
  <?php } ?>
</div>

	<!-- About Section -->
<div class="about" id="about">
  <div class="about-wrapper">
    
    <div class="about-image-box">
        <img src="Image4.png" alt="About Image">
        <div class="about-badge">
            <strong>20+</strong>
            <span>Ani de Experiență</span>
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

<div class="contact" id="contact">
  <h2><?= $txt[$lang]['contact_title'] ?></h2>
  <p><?= $txt[$lang]['contact_desc'] ?></p>
  <?php if($flash) echo "<div class='flash-message'>$flash</div>"; ?>
  <form method="post" action="send_message.php">
    <label>Nume</label>
    <input type="text" name="name" placeholder="Numele tău" required>
    <label>Telefon</label>
    <input type="text" name="phone" placeholder="+40 xxx xxx xxx">
    <label>Email</label>
    <input type="email" name="email" placeholder="email@exemplu.ro" required>
    <label>Mesaj</label>
    <textarea name="message" placeholder="Cum te putem ajuta?" required></textarea>
    <button type="submit">Trimite Mesajul</button>
  </form>

  <h3>Informații Contact</h3>
  <p><strong>Adresă:</strong> <?= $txt[$lang]['contact_info']['Adresă'] ?></p>
  <p><strong>Telefon:</strong> <?= $txt[$lang]['contact_info']['Telefon'] ?></p>
  <p><strong>Email:</strong> <?= $txt[$lang]['contact_info']['Email'] ?></p>
  <p><strong>Program:</strong> <?= $txt[$lang]['contact_info']['Program'] ?></p>

  <div class="map-container"><?= $txt[$lang]['map_embed'] ?></div>
</div>
	<?php include 'Feedback.php'; ?>
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
document.addEventListener("DOMContentLoaded", function(){

  // Lazy load imagini
  const lazyImages = document.querySelectorAll('img.lazy');
  lazyImages.forEach(img => {
    img.src = img.dataset.src;
    img.classList.remove('lazy');
  });

  // Fade-in carduri
  const cards = document.querySelectorAll('.card');
  cards.forEach((card, i) => {
    setTimeout(() => card.classList.add('visible'), i * 150);
  });

  // Hero text și butoane animate
  const heroElements = document.querySelectorAll('.hero h2, .hero p, .hero .btn');
  heroElements.forEach((el, i) => {
    setTimeout(() => el.classList.add('visible'), i * 200);
  });

  // Dark / Light mode
  function toggleDarkMode(){
    document.body.classList.toggle('dark-mode');
    document.querySelector('header').classList.toggle('dark-mode');
    document.querySelector('footer').classList.toggle('dark-mode');
  }
  window.toggleDarkMode = toggleDarkMode;

  // Hamburger mobil
  function toggleMenu(){
    const nav = document.getElementById("mainNav");
    nav.classList.toggle("mobile-open");
  }
  window.toggleMenu = toggleMenu;

  // Header links
  const nav = document.getElementById("mainNav");
  const links = Array.from(nav.querySelectorAll('a'));

  function adjustMenu(){
    if(window.innerWidth <= 768){
      nav.style.flexDirection = "column";
      nav.style.alignItems = "flex-start";
      nav.style.width = "100%";
      nav.style.gap = "10px";

      // Linkuri vizibile pe mobil
      const mobileLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
      links.forEach(link => {
        if(mobileLinks.includes(link.getAttribute('href'))){
          link.style.display = "block";
        } else {
          link.style.display = "none";
        }
      });

      // Reordonare corectă
      mobileLinks.forEach(file => {
        const link = links.find(a => a.getAttribute('href') === file);
        if(link) nav.appendChild(link);
      });

    } else {
      nav.style.flexDirection = "row";
      nav.style.alignItems = "center";
      nav.style.gap = "15px";
      // Pe desktop afișăm toate linkurile
      links.forEach(link => link.style.display = "inline-block");
    }
  }

  adjustMenu();
  window.addEventListener('resize', adjustMenu);

  // Scroll highlight secțiune activă
  const sections = document.querySelectorAll('.hero, .services, .collection, .about, .contact');
  const navLinks = document.querySelectorAll('nav a');
  window.addEventListener('scroll', function(){
    let current = '';
    sections.forEach(section => {
      const sectionTop = section.offsetTop - 150;
      if(window.pageYOffset >= sectionTop){ current = section.getAttribute('id'); }
    });
    navLinks.forEach(a => {
      a.classList.remove('active');
      if(a.getAttribute('href') === '#' + current) a.classList.add('active');
    });
  });

  // Parallax hero
  const hero = document.querySelector('.hero');
  window.addEventListener('scroll', function(){
    hero.style.backgroundPositionY = -(window.scrollY * 0.3) + 'px';
  });

});
</script>


</body>
</html>
