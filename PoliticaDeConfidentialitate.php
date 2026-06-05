<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
session_start();

$companyName = 'Magazin PSY';
$address = 'Craiova, Dolj';
$phone = '0764.049.235';
$email = 'office@magazinpsy.ro';
$lastUpdated = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Politica de Confidentialitate</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
    body{
        margin:0;
        font-family:'Roboto',sans-serif;
        background:#fdf6f0;
        color:#111;
    }
    header{
        position:sticky;
        top:0;
        z-index:10;
        background:#8C92AC;
        box-shadow:0 2px 8px rgba(0,0,0,0.18);
        padding:14px 22px;
        display:flex;
        align-items:center;
        gap:16px;
    }
    .home-link{
        display:inline-flex;
        align-items:center;
        text-decoration:none;
    }
    .home-link img{
        height:50px;
        width:auto;
        display:block;
    }
    .title-wrap{
        padding:34px 20px 10px;
        text-align:center;
    }
    .title-wrap h1{
        font-family:'Montserrat',sans-serif;
        margin:0;
        font-size:34px;
    }
    .title-wrap p{
        margin:10px 0 0;
        color:#555;
    }
    main{
        max-width:1000px;
        margin:0 auto;
        padding:20px;
    }
    .card{
        background:#fff;
        border-radius:18px;
        box-shadow:0 10px 24px rgba(0,0,0,0.08);
        padding:28px;
        line-height:1.75;
    }
    h2{
        font-family:'Montserrat',sans-serif;
        margin-top:24px;
        margin-bottom:10px;
        font-size:22px;
        color:#8b4315;
    }
    p, li{
        font-size:16px;
    }
    ul{
        margin:10px 0 0 18px;
        padding:0;
    }
    .contact-box{
        margin-top:26px;
        padding:18px;
        background:#f8f3ee;
        border-radius:14px;
        border:1px solid #ead8c7;
    }
    .back-note{
        text-align:center;
        margin-top:18px;
        font-size:14px;
        color:#666;
    }
    .back-note a{
        color:#8b4315;
        text-decoration:none;
        font-weight:600;
    }
    .back-note a:hover{
        text-decoration:underline;
    }
    .footer-note{
        margin-top:28px;
        font-size:14px;
        color:#666;
    }
    @media (max-width: 768px){
        .title-wrap h1{font-size:28px;}
        .card{padding:20px;}
    }
</style>
</head>
<body>

<header>
    <a class="home-link" href="Acasa.php" title="Mergi la Acasa">
        <img src="Image41.png" alt="Logo">
    </a>
</header>

<div class="title-wrap">
    <h1>Politica de Confidentialitate</h1>
    <p>Ultima actualizare: <?php echo htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<main>
    <div class="card">
        <h2>1. Informatii generale</h2>
        <p>
            <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>,
            cu sediul in <?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>,
            respecta confidentialitatea datelor tale personale si le prelucreaza in mod legal, corect si transparent.
        </p>

        <h2>2. Ce date colectam</h2>
        <p>Putem colecta urmatoarele date:</p>
        <ul>
            <li>nume si prenume,</li>
            <li>adresa de email,</li>
            <li>numar de telefon,</li>
            <li>adresa de livrare si facturare,</li>
            <li>mesajele trimise prin formularele site-ului,</li>
            <li>imaginile incarcate la feedback, daca alegi sa le trimiti.</li>
        </ul>

        <h2>3. De ce folosim datele</h2>
        <p>Folosim datele tale pentru:</p>
        <ul>
            <li>procesarea comenzilor,</li>
            <li>livrare si comunicare cu tine,</li>
            <li>crearea si administrarea contului,</li>
            <li>raspuns la mesaje si cereri,</li>
            <li>gestionarea feedback-ului trimis prin site.</li>
        </ul>

        <h2>4. Temeiul prelucrarii</h2>
        <p>
            Prelucram datele tale pentru executarea unei comenzi, pentru indeplinirea obligatiilor legale,
            pentru interesele legitime ale magazinului si, cand este cazul, pe baza consimtamantului tau.
        </p>

        <h2>5. Cu cine putem partaja datele</h2>
        <p>
            Datele pot fi transmise catre furnizori de servicii necesari functionarii site-ului,
            cum ar fi servicii de gazduire, curierat, plata sau email, doar in masura in care este necesar.
        </p>

        <h2>6. Cat timp pastram datele</h2>
        <p>
            Pastram datele doar pe perioada necesara scopului pentru care au fost colectate,
            apoi le stergem sau le anonimizam, cu exceptia situatiilor in care legea cere o pastrare mai lunga.
        </p>

        <h2>7. Drepturile tale</h2>
        <p>
            Ai dreptul sa ceri accesul la datele tale, corectarea lor, stergerea lor, restrictionarea prelucrarii,
            portabilitatea datelor si opozitia fata de anumite prelucrari.
        </p>

        <h2>8. Cookie-uri</h2>
        <p>
            Site-ul poate folosi cookie-uri necesare pentru functionare si, daca este cazul, alte cookie-uri pentru analiza sau imbunatatirea experientei.
        </p>

        <h2>9. Contact</h2>
        <p>
            Pentru cereri legate de datele personale ne poti contacta folosind datele de mai jos.
        </p>

        <div class="contact-box">
            <p><strong>Adresa:</strong> <?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Telefon:</strong> <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="footer-note">
            Aceasta pagina poate fi actualizata oricand, in functie de modificarile site-ului sau ale serviciilor folosite.
        </div>

        <div class="back-note">
            Mergi inapoi la <a href="Acasa.php">Acasă</a>
        </div>
    </div>
</main>

</body>
</html>
