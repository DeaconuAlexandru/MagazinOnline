<?php
session_start();

// Afisare erori pentru debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexiune DB
$host = 'localhost';
$db   = 'magazi15_ShergeiCovoare';
$user = 'magazi15_Alex';
$pass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Conexiune DB esuata: '.$e->getMessage());
}

// Initializare feedback
$registerFeedback = '';
$loginFeedback = '';
$socialFeedback = '';

// LOGOUT
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: Acasa.php");
    exit;
}

// REGISTER
if (isset($_POST['register'])) {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $username === '' || $password === '') {
        $registerFeedback = 'Completeaza toate campurile.';
    } else {
        // Verificam daca exista deja un user cu acest email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $registerFeedback = 'Deja te-ai inregistrat pe acest email, te rog sa te loghezi';
        } else {
            // Optional: verificam unicitatea username-ului
            $stmt2 = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt2->execute([$username]);
            if ($stmt2->fetch()) {
                $registerFeedback = 'Username-ul este folosit deja. Alege alt username.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, username, password, created_at) VALUES (?, ?, ?, NOW())");
                try {
                    $stmt->execute([$email, $username, $hash]);
                    // Nu face auto-login. Afiseaza instructiunea sa se logheze.
                    $registerFeedback = 'Cont creat. Te rog sa te loghezi';
                } catch (PDOException $e) {
                    $registerFeedback = 'Eroare la creare cont. Incearca din nou.';
                }
            }
        }
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginFeedback = 'Completeaza toate campurile.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_username'] = $user['username'];

            header("Location: Acasa.php");
            exit;
        } else {
            $loginFeedback = 'Email sau parola incorecta.';
        }
    }
}

// SOCIAL LOGIN
if (isset($_POST['social_login'])) {
    $email = trim($_POST['email'] ?? '');
    $provider = $_POST['provider'] ?? '';
    $provider_id = $_POST['provider_id'] ?? '';

    if ($email === '' || $provider === '' || $provider_id === '') {
        $socialFeedback = 'Completeaza toate campurile.';
    } else {
        $map = ['google'=>'google_id','facebook'=>'facebook_id','apple'=>'apple_id'];
        if (!isset($map[$provider])) {
            $socialFeedback = 'Provider invalid.';
        } else {
            $col = $map[$provider];

            // Verificare dacă coloana există în tabel
            $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($col, $columns)) {
                $socialFeedback = "Coloana $col nu exista in baza de date.";
            } else {
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE $col = ? LIMIT 1");
                $stmt->execute([$provider_id]);
                $user = $stmt->fetch();

                if (!$user) {
                    $stmt = $pdo->prepare("INSERT INTO users (email, $col, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$email, $provider_id]);
                    $user_id = $pdo->lastInsertId();
                    $username = $email;
                } else {
                    $user_id = $user['id'];
                    $username = $user['username'] ?? $email;
                }

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_username'] = $username;

                header("Location: Acasa.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contul Meu - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Roboto',sans-serif;color:#111;background:#fdf6f0;scroll-behavior:smooth;}
.dark-mode{background:#111;color:#fdf6f0;}
header{position:sticky;top:0;z-index:10;background:#8C92AC;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
header h1{font-family:'Montserrat',sans-serif;font-weight:700;font-size:30px;margin:0;}
.header-contact{display:flex;gap:15px;align-items:center;}
.header-contact .phone{font-weight:600;}
.dark-toggle{cursor:pointer;padding:10px;border-radius:8px;background:#fff;color:#b5651d;font-weight:600;transition:0.3s;}
.dark-toggle:hover{background:#8b4315;color:#fff;}
nav{display:flex;justify-content:center;gap:15px;margin:20px 0;flex-wrap:wrap;}
nav a{text-decoration:none;padding:10px 18px;background:#fff;color:#b5651d;border-radius:8px;transition:.3s;font-weight:500;}
nav a.active{background:#8b4315;color:#fff;}
nav a:hover{background:#8b4315;color:#fff;transform:translateY(-2px);}
.container{max-width:1200px;margin:auto;padding:20px;}
.container form{max-width:420px;margin:25px auto;padding:28px;background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.12);display:flex;flex-direction:column;gap:16px;animation:fadeIn 0.4s ease;}
.container input{padding:13px 15px;font-size:15px;border:1px solid #ccc;border-radius:10px;outline:none;transition:all 0.25s ease;background:#fafafa;}
.container input::placeholder{color:#999;}
.container input:focus{border-color:#b5651d;background:#fff;box-shadow:0 0 0 3px rgba(181,101,29,0.15);}
.container button{padding:13px;font-size:15px;font-weight:600;border:none;border-radius:10px;background:linear-gradient(135deg,#b5651d,#8b4315);color:#fff;cursor:pointer;transition:all 0.3s ease;}
.container button:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,0.2);}
.container button:active{transform:translateY(0);box-shadow:none;}
.container h2{text-align:center;margin:30px 0 15px;color:#b5651d;font-family:'Montserrat',sans-serif;font-weight:700;}
.feedback{max-width:420px;margin:5px auto 15px;font-size:14px;text-align:center;color:#d9534f;min-height:18px;}
.social-login{display:flex;flex-direction:column;gap:14px;max-width:420px;margin:20px auto 40px;}
.social-btn{display:flex;align-items:center;justify-content:center;gap:12px;padding:13px;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;transition:all 0.3s ease;}
.social-btn img{width:22px;height:auto;}
.social-btn.google{background:#fff;color:#111;border:1px solid #ccc;}
.social-btn.google:hover{background:#f3f3f3;}
.social-btn.facebook{background:#1877f2;color:#fff;}
.social-btn.facebook:hover{background:#145edc;}
.social-btn.apple{background:#000;color:#fff;}
.social-btn.apple:hover{background:#1a1a1a;}
.social-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2);}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
@media screen and (max-width:480px){.container{padding:20px 10px;}.container form{padding:22px;margin:20px 10px;}.container h2{font-size:20px;}.container input,.container button,.social-btn{font-size:14px;padding:11px;}}
footer{background:#8C92AC;color:#fff;padding:50px 20px;font-family:'Roboto',sans-serif;}
footer .footer-container{display:flex;flex-wrap:wrap;gap:40px;justify-content:flex-start;align-items:flex-start;}
footer .footer-col{flex:1;min-width:200px;}
footer .footer-logo img{height:50px;width:auto;display:block;margin-bottom:15px;}
footer .footer-social{display:flex;gap:10px;margin-top:5px;}
footer .footer-social a{display:flex;align-items:center;justify-content:center;width:30px;height:30px;color:#fff;transition:color 0.3s;}
footer .footer-social a:hover{color:#fff;}
footer ul{list-style:none;padding:0;margin:10px 0 20px 0;line-height:1.8;}
footer ul li a{color:#fff;text-decoration:none;transition:color 0.3s;}
footer ul li a:hover{color:#fff;}
footer .footer-bottom{margin-top:30px;border-top:1px solid rgba(255,255,255,0.3);padding-top:15px;font-size:14px;text-align:left;}
footer .footer-bottom a{color:#fff;text-decoration:none;margin:0 8px;}
@media screen and (max-width:768px){footer .footer-container{flex-direction:column;gap:25px;}}
/* =============================
   STRUCTURA GENERALA
============================= */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 30px 20px;
}

/* =============================
   FORMULARE LOGIN / REGISTER
============================= */
.container form {
    max-width: 420px;
    margin: 25px auto;
    padding: 28px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    display: flex;
    flex-direction: column;
    gap: 16px;
    animation: fadeIn 0.4s ease;
}

/* =============================
   INPUT-URI
============================= */
.container input {
    padding: 13px 15px;
    font-size: 15px;
    border: 1px solid #ccc;
    border-radius: 10px;
    outline: none;
    transition: all 0.25s ease;
    background: #fafafa;
}

.container input::placeholder {
    color: #999;
}

.container input:focus {
    border-color: #b5651d;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(181,101,29,0.15);
}

/* =============================
   BUTOANE STANDARD
============================= */
.container button {
    padding: 13px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #b5651d, #8b4315);
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.container button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
}

.container button:active {
    transform: translateY(0);
    box-shadow: none;
}

/* =============================
   TITLURI
============================= */
.container h2 {
    text-align: center;
    margin: 30px 0 15px;
    color: #b5651d;
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
}

/* =============================
   MESAJE FEEDBACK
============================= */
.feedback {
    max-width: 420px;
    margin: 5px auto 15px;
    font-size: 14px;
    text-align: center;
    color: #d9534f;
    min-height: 18px;
}

/* =============================
   SOCIAL LOGIN
============================= */
.social-login {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 420px;
    margin: 20px auto 40px;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 13px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
}

.social-btn img {
    width: 22px;
    height: auto;
}

/* Google */
.social-btn.google {
    background: #fff;
    color: #111;
    border: 1px solid #ccc;
}
.social-btn.google:hover {
    background: #f3f3f3;
}

/* Facebook */
.social-btn.facebook {
    background: #1877f2;
    color: #fff;
}
.social-btn.facebook:hover {
    background: #145edc;
}

/* Apple */
.social-btn.apple {
    background: #000;
    color: #fff;
}
.social-btn.apple:hover {
    background: #1a1a1a;
}

/* Hover comun */
.social-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* =============================
   ANIMATII
============================= */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* =============================
   RESPONSIVE
============================= */
@media screen and (max-width: 480px) {
    .container { padding: 20px 10px; }
    .container form { padding: 22px; margin: 20px 10px; }
    .container h2 { font-size: 20px; }
    .container input,
    .container button,
    .social-btn { font-size: 14px; padding: 11px; }
}
.social-login {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 420px;
    margin: 20px auto 40px;
}

.social-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Icon pseudo-element */
.social-btn::before {
    content: '';
    display: inline-block;
    width: 22px;
    height: 22px;
    background-size: cover;
    background-position: center;
    border-radius: 4px;
}

/* Google */
.social-btn.google {
    background: #db4437;
}
.social-btn.google::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="%23fff" d="M24 9.5c3.5 0 6.5 1.3 8.5 3.1l6.3-6.3C34.6 3 29.6 1 24 1 14.8 1 7 7.9 7 17c0 9 7.8 16 17 16 4.3 0 7.4-1.3 9.7-2.7l-4-3.6C27.3 27.5 25 28 24 28c-5.6 0-10-4.5-10-10s4.4-10 10-10z"/></svg>');
}
.social-btn.google:hover {
    background: #c33d2e;
}

/* Facebook */
.social-btn.facebook {
    background: #1877f2;
}
.social-btn.facebook::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23fff" d="M22 12a10 10 0 1 0-11 9.9V14h-3v-2h3v-1.5c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 1-2 2v1.5h3l-.5 2h-2.5v7.9A10 10 0 0 0 22 12z"/></svg>');
}
.social-btn.facebook:hover {
    background: #145edc;
}

/* Apple */
.social-btn.apple {
    background: #000;
}
.social-btn.apple::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23fff" d="M16.5 7c-.8-.9-2-1-2.9-1-1.3 0-2.4.7-3 1.7-1.3 2-1 5 1 6.4 1.2.9 2.7 1 4 1 1.3 0 2.7-.6 3.5-1.5-.1-1.1-.8-2-1.6-2.7-.7-.5-1.5-.8-2.4-.9zM15 3c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2z"/></svg>');
}
.social-btn.apple:hover {
    background: #333;
}

</style>
</head>
<body>

<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <div class="menu-toggle" onclick="toggleMenu()">
      <div></div>
      <div></div>
      <div></div>
    </div>

  </div>
</header>

<div class="container">

<?php
// Daca utilizatorul nu este autentificat afisam formularele
if (!isset($_SESSION['user_id'])) {
?>

    <h2>Inregistreaza-te</h2>
    <form method="post" autocomplete="off" novalidate>
        <input type="email" name="email" required placeholder="Introdu adresa de email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        <input type="text" name="username" required placeholder="Alege un username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        <input type="password" name="password" required placeholder="Alege o parola">
        <button type="submit" name="register">Creeaza cont</button>
    </form>

    <?php if (!empty($registerFeedback)): ?>
        <p class="feedback"><?php echo htmlspecialchars($registerFeedback); ?></p>
    <?php endif; ?>

    <h2>Autentificare</h2>
    <form method="post" autocomplete="off" novalidate>
        <input type="email" name="email" required placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        <input type="password" name="password" required placeholder="Parola">
        <button type="submit" name="login">Login</button>
    </form>

    <?php if (!empty($loginFeedback)): ?>
        <p class="feedback"><?php echo htmlspecialchars($loginFeedback); ?></p>
    <?php endif; ?>

    <h2>Autentificare rapida</h2>

    <div class="social-login">
        <a href="#" class="social-btn google">Google</a>
        <a href="#" class="social-btn facebook">Facebook</a>
        <a href="#" class="social-btn apple">Apple</a>
    </div>

<?php } else {
    // Utilizator autentificat, afisam scurt rezumat si link de logout
    $displayName = htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']);
?>
    <div style="max-width:820px;margin:40px auto;padding:28px;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.08);text-align:center;">
        <h2>Bine ai revenit</h2>
        <p>Ești autentificat ca <?php echo $displayName; ?>.</p>
        <p><a class="btn-primary" href="Acasa.php">Pagina principală</a> <a class="btn-primary" href="Login.php?logout=1">Deconectare</a></p>
    </div>
<?php } ?>

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

</body>
</html>

