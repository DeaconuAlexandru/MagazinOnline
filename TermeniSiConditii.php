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
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Termeni si Conditii</title>
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
        line-height:1.7;
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
    <h1>Termeni si Conditii</h1>
    <p>Ultima actualizare: <?php echo date('d.m.Y'); ?></p>
</div>

<main>
    <div class="card">
        <h2>1. Informații generale</h2>
        <p>
            Magazinul este operat de <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>,
            cu sediul în <?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>,
            e-mail <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>,
            telefon <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>.
        </p>

        <h2>2. Produse</h2>
        <p>
            Produsele afișate pe site sunt prezentate cu titlu informativ.
            Pozele pot avea mici diferențe față de produsul final.
        </p>

        <h2>3. Comenzi și plată</h2>
        <p>
            Comanda se confirmă după completarea datelor și validarea plății sau a metodei de plată acceptate.
        </p>

        <h2>4. Livrare</h2>
        <p>
            Livrarea se face în 2 până la 3 zile lucrătoare, iar costul de transport este afișat înainte de finalizarea comenzii.
        </p>

        <h2>5. Retragere și retur</h2>
        <p>
            Clientul are dreptul să se retragă din contract în 14 zile, conform regulilor aplicabile vânzărilor la distanță.
            Produsele returnate trebuie să fie în starea inițială, fără urme de folosire care afectează valoarea acestora.
        </p>

        <h2>6. Garanție</h2>
        <p>
            Produsele beneficiază de garanția legală de conformitate prevăzută de lege.
        </p>

        <div class="contact-box">
            <h2 style="margin-top:0;">Date de contact</h2>
            <p><strong>Adresa:</strong> <?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Telefon:</strong> <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="back-note">
            Înapoi la <a href="Acasa.php">Acasă</a>
        </div>
    </div>
</main>

</body>
</html>
