<?php
declare(strict_types=1);

// Sesiune securizata
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '', // setează dacă e nevoie
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// optional: incarcare .env in dev
// require __DIR__ . '/vendor/autoload.php';
// if (class_exists(\Dotenv\Dotenv::class)) { \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); }

// CONFIG DIN VARIABILE DE MEDIU
$GOOGLE_CLIENT_ID     = getenv('GOOGLE_CLIENT_ID') ?: '1054381847952-l0fasrivsvgk7a69n9g4qj8u7a34bfms.apps.googleusercontent.com';
$GOOGLE_CLIENT_SECRET = getenv('GOOGLE_CLIENT_SECRET') ?: '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';
$GOOGLE_REDIRECT_URI = getenv('GOOGLE_REDIRECT_URI') ?: 'https://magazinpsy.ro/Login.php?action=google_callback';

// FACEBOOK CONFIG (folosește getenv() în producție)
$FACEBOOK_APP_ID     = getenv('FACEBOOK_APP_ID')     ?: 'a583f7397577c9664e4ac0c8675ccd18';
$FACEBOOK_APP_SECRET = getenv('FACEBOOK_APP_SECRET') ?: '858d476b823ef1e1b24707fa835e1a3e';
$FACEBOOK_REDIRECT_URI = getenv('FACEBOOK_REDIRECT_URI') ?: 'https://magazinpsy.ro/Acasa.php?action=facebook_callback';
// DB config
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'magazi15_ShergeiCovoare';
$dbUser = getenv('DB_USER') ?: 'magazi15_Alex';
$dbPass = getenv('DB_PASS') ?: 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

// DEBUG only in dev
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// PDO
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Eroare internă.');
}

// helper-uri
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf_token'];
}
function check_csrf(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}
function safe_redirect(string $uri): void {
    header('Location: ' . $uri);
    exit;
}

// RATE LIMIT simplu pe sesiune
if (!isset($_SESSION['auth_fail'])) $_SESSION['auth_fail'] = ['count' => 0, 'last' => 0];
$fail = &$_SESSION['auth_fail'];
$now = time();
$maxFails = 6;
$lockWindow = 300;
if ($fail['count'] >= $maxFails && ($now - $fail['last']) < $lockWindow) {
    $remaining = $lockWindow - ($now - $fail['last']);
    http_response_code(429);
    exit("Prea multe încercări. Încearcă peste {$remaining} secunde.");
}

// ===== Google OAuth flow (GET actions) =====
if (isset($_GET['action']) && $_GET['action'] === 'google') {
    $state = bin2hex(random_bytes(12));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'response_type' => 'code',
        'client_id'     => $GOOGLE_CLIENT_ID,
        'redirect_uri'  => $GOOGLE_REDIRECT_URI,
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'offline',
        'prompt'        => 'select_account'
    ]);
    safe_redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
}

if (isset($_GET['action']) && $_GET['action'] === 'google_callback') {
    if (empty($_GET['code']) || empty($_GET['state']) || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], (string)$_GET['state'])) {
        exit('State invalid sau code lipsa.');
    }
    $code = (string)$_GET['code'];

    // exchange code for tokens
    $tokenEndpoint = 'https://oauth2.googleapis.com/token';
    $post = [
        'code'          => $code,
        'client_id'     => $GOOGLE_CLIENT_ID,
        'client_secret' => $GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => $GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];
    $ch = curl_init($tokenEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $res = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($res === false) exit('Eroare la comunicarea cu Google: ' . $curlErr);
    $data = json_decode($res, true);
    if (empty($data['access_token'])) exit('Nu am primit access_token de la Google.');

    $accessToken = $data['access_token'];

    // get userinfo
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    $userJson = curl_exec($ch);
    curl_close($ch);
    $guser = json_decode($userJson, true);

    $email = $guser['email'] ?? '';
    $googleId = $guser['sub'] ?? '';
    $name = $guser['name'] ?? $email;
    if (!$email || !$googleId) exit('Date incomplete de la Google.');

    $col = 'google_id';
    $hasGoogleId = column_exists($pdo, 'users', $col);

    // cauta user dupa google_id daca exista coloana
    $user = false;
    if ($hasGoogleId) {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();
    }

    if (!$user) {
        // daca exista user cu acelasi email, update google_id
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $exist = $stmt->fetch();
        if ($exist) {
            $userId = (int)$exist['id'];
            $username = $exist['username'] ?? $email;
            if ($hasGoogleId) {
                $upd = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $upd->execute([$googleId, $userId]);
            }
        } else {
            if ($hasGoogleId) {
                $ins = $pdo->prepare("INSERT INTO users (email, username, google_id, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$email, $name, $googleId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO users (email, username, created_at) VALUES (?, ?, NOW())");
                $ins->execute([$email, $name]);
            }
            $userId = (int)$pdo->lastInsertId();
            $username = $name;
        }
    } else {
        $userId = (int)$user['id'];
        $username = $user['username'] ?? $email;
    }

    // login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_username'] = $username;

    safe_redirect('Acasa.php');
}
// ==== Facebook OAuth (GET actions) ====
if (isset($_GET['action']) && $_GET['action'] === 'facebook') {
    $state = bin2hex(random_bytes(12));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'client_id'    => $FACEBOOK_APP_ID,
        'redirect_uri' => $FACEBOOK_REDIRECT_URI,
        'state'        => $state,
        'scope'        => 'email,public_profile',
        'response_type'=> 'code',
    ]);
    safe_redirect('https://www.facebook.com/v16.0/dialog/oauth?' . $params);
}

if (isset($_GET['action']) && $_GET['action'] === 'facebook_callback') {
    if (empty($_GET['code']) || empty($_GET['state']) || empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], (string)$_GET['state'])) {
        exit('State invalid sau code lipsa.');
    }
    $code = (string)$_GET['code'];

    // Exchange code for access_token
    $tokenEndpoint = 'https://graph.facebook.com/v16.0/oauth/access_token';
    $qs = http_build_query([
        'client_id'     => $FACEBOOK_APP_ID,
        'redirect_uri'  => $FACEBOOK_REDIRECT_URI,
        'client_secret' => $FACEBOOK_APP_SECRET,
        'code'          => $code,
    ]);
    $ch = curl_init($tokenEndpoint . '?' . $qs);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($res === false) exit('Eroare la comunicarea cu Facebook: ' . $curlErr);
    $data = json_decode($res, true);
    if (empty($data['access_token'])) exit('Nu am primit access_token de la Facebook.');

    $accessToken = $data['access_token'];

    // Get user info
    $ch = curl_init('https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userJson = curl_exec($ch);
    curl_close($ch);
    $fuser = json_decode($userJson, true);

    $email = $fuser['email'] ?? '';
    $facebookId = $fuser['id'] ?? '';
    $name = $fuser['name'] ?? $email;

    // email poate lipsi la unele conturi FB — gestionează cazul
    if (!$facebookId) exit('Date incomplete de la Facebook.');

    // Asigură-te că ai coloana facebook_id în tabela users (vezi secțiunea SQL mai jos)
    $col = 'facebook_id';
    $hasFacebookId = column_exists($pdo, 'users', $col);

    $user = false;
    if ($hasFacebookId) {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE facebook_id = ? LIMIT 1");
        $stmt->execute([$facebookId]);
        $user = $stmt->fetch();
    }

    if (!$user) {
        // dacă exista user cu același email, leagă providerul
        if ($email) {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $exist = $stmt->fetch();
        } else {
            $exist = false;
        }

        if ($exist) {
            $userId = (int)$exist['id'];
            $username = $exist['username'] ?? $email;
            if ($hasFacebookId) {
                $upd = $pdo->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
                $upd->execute([$facebookId, $userId]);
            }
        } else {
            // creare cont nou (daca nu exista coloana, trimiti NULL/nu atribui)
            if ($hasFacebookId) {
                $ins = $pdo->prepare("INSERT INTO users (email, username, facebook_id, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$email, $name, $facebookId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO users (email, username, created_at) VALUES (?, ?, NOW())");
                $ins->execute([$email, $name]);
            }
            $userId = (int)$pdo->lastInsertId();
            $username = $name;
        }
    } else {
        $userId = (int)$user['id'];
        $username = $user['username'] ?? $email;
    }

    // login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_username'] = $username;

    safe_redirect('Acasa.php');
}
// ===== LOGOUT via GET? =====
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    safe_redirect('Acasa.php');
}

// Mesaje pentru view
$registerFeedback = '';
$loginFeedback = '';
$socialFeedback = '';

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
// ===== SOCIAL LOGIN generic (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['social_login'])) {
    $csrf = $_POST['csrf'] ?? null;
    if (!check_csrf($csrf)) {
        $socialFeedback = 'Cerere invalidă.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $provider = $_POST['provider'] ?? '';
        $provider_id = trim($_POST['provider_id'] ?? '');

        if ($email === '' || $provider === '' || $provider_id === '') {
            $socialFeedback = 'Completează toate câmpurile.';
        } else {
            $map = ['google' => 'google_id', 'facebook' => 'facebook_id', 'apple' => 'apple_id'];
            if (!isset($map[$provider])) {
                $socialFeedback = 'Provider invalid.';
            } else {
                $col = $map[$provider];
                if (!column_exists($pdo, 'users', $col)) {
                    $socialFeedback = "Coloana {$col} nu există în baza de date.";
                } else {
                    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE {$col} = ? LIMIT 1");
                    $stmt->execute([$provider_id]);
                    $u = $stmt->fetch();

                    if (!$u) {
                        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
                        $stmt->execute([$email]);
                        $exist = $stmt->fetch();
                        if ($exist) {
                            $user_id = (int)$exist['id'];
                            $username = $exist['username'] ?? $email;
                            $upd = $pdo->prepare("UPDATE users SET {$col} = ? WHERE id = ?");
                            $upd->execute([$provider_id, $user_id]);
                        } else {
                            $ins = $pdo->prepare("INSERT INTO users (email, username, {$col}, created_at) VALUES (?, ?, ?, NOW())");
                            $username = $email;
                            $ins->execute([$email, $username, $provider_id]);
                            $user_id = (int)$pdo->lastInsertId();
                        }
                    } else {
                        $user_id = (int)$u['id'];
                        $username = $u['username'] ?? $email;
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_username'] = $username;
                    safe_redirect('Acasa.php');
                }
            }
        }
    }
}

// CSRF pentru view
$csrfForView = csrf_token();

// AJAX support
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'registerFeedback' => $registerFeedback,
        'loginFeedback'    => $loginFeedback,
        'socialFeedback'   => $socialFeedback,
        'csrf'             => $csrfForView,
    ]);
    exit;
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

<header id="header" class="site-header" role="banner">
  <div class="header-inner" style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div class="brand" style="display:flex;align-items:center;gap:15px;">
      <a href="Acasa.php" aria-label="Mergi la pagina principala">
        <img src="Image3.png" alt="Sherghei Covoare" style="height:50px;display:block;">
      </a>
    </div>

    <nav id="mainNav" class="main-nav" aria-label="Meniu principal" style="display:flex;gap:12px;align-items:center;">
      <a href="Acasa.php" class="nav-link">Acasă</a>
      <a href="Colectie.php" class="nav-link">Colecție</a>
      <a href="DespreNoi.php" class="nav-link">Despre Noi</a>
      <a href="Contact.php" class="nav-link">Contact</a>
      <a href="CosulMeu.php" class="nav-link">Coșul Meu</a>

      <?php if (!empty($_SESSION['user_id'])): ?>
        <div class="account-dropdown" style="position:relative;">
          <button class="nav-link" aria-haspopup="true" aria-expanded="false" onclick="document.getElementById('accountMenu').classList.toggle('open')">
            <?php echo htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']); ?> ▾
          </button>
          <div id="accountMenu" class="dropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);background:#fff;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,0.12);min-width:160px;">
            <a href="ContulMeu.php" class="dropdown-item" style="display:block;padding:10px 14px;color:#333;text-decoration:none;">Profil</a>
            <a href="ContulMeu.php?logout=1" class="dropdown-item" style="display:block;padding:10px 14px;color:#333;text-decoration:none;">Deconectare</a>
          </div>
          <script>
            (function(){ const m = document.getElementById('accountMenu'); if(!m) return;
            document.addEventListener('click', e => {
              if (!m.classList.contains('open')) return;
              if (m.contains(e.target) || e.target.closest('[onclick*="accountMenu"]')) return;
              m.classList.remove('open'); m.style.display = 'none';
            });
            const btn = m.previousElementSibling;
            btn && btn.addEventListener('click', () => {
              const open = m.classList.toggle('open');
              m.style.display = open ? 'block' : 'none';
            });
            })();
          </script>
        </div>
      <?php else: ?>
        <a href="Login.php" class="nav-link">Contul Meu</a>
      <?php endif; ?>
    </nav>

    <button class="menu-toggle" aria-label="Deschide meniul mobil" onclick="document.getElementById('mainNav').classList.toggle('mobile-open')" style="background:none;border:0;color:inherit;font-size:20px;padding:8px 10px;">
      ☰
    </button>
  </div>
</header>

<main class="container" role="main" style="max-width:1100px;margin:28px auto;padding:18px;">

<?php if (empty($_SESSION['user_id'])): ?>

  <section class="card register" aria-labelledby="register-title" style="background:#fff;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 8px 24px rgba(0,0,0,0.06);">
    <h2 id="register-title" style="color:#b5651d;margin:6px 0 14px;font-family:Montserrat, sans-serif;">Înregistrează-te</h2>

    <form method="post" autocomplete="off" novalidate style="max-width:460px;display:flex;flex-direction:column;gap:12px;">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfForView ?? ($_SESSION['csrf_token'] ?? '')); ?>">
      <label for="reg-email" class="sr-only">Email</label>
      <input id="reg-email" class="input" type="email" name="email" required placeholder="Adresa de email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="padding:12px;border:1px solid #ddd;border-radius:10px;">
      <label for="reg-username" class="sr-only">Username</label>
      <input id="reg-username" class="input" type="text" name="username" required placeholder="Alege un username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" style="padding:12px;border:1px solid #ddd;border-radius:10px;">
      <label for="reg-password" class="sr-only">Parolă</label>
      <input id="reg-password" class="input" type="password" name="password" required placeholder="Alege o parolă" style="padding:12px;border:1px solid #ddd;border-radius:10px;">
      <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" name="register" style="padding:12px 16px;background:linear-gradient(135deg,#b5651d,#8b4315);color:#fff;border:0;border-radius:10px;font-weight:600;cursor:pointer;">Creează cont</button>
        <a href="Acasa.php" style="padding:12px 16px;background:#f3f3f3;color:#111;border-radius:10px;text-decoration:none;">Înapoi</a>
      </div>
    </form>

    <?php if (!empty($registerFeedback)): ?>
      <p class="feedback" role="alert" style="color:#c0392b;margin-top:12px;"><?php echo htmlspecialchars($registerFeedback); ?></p>
    <?php endif; ?>
  </section>

  <section class="card login" aria-labelledby="login-title" style="background:#fff;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 8px 24px rgba(0,0,0,0.06);">
    <h2 id="login-title" style="color:#b5651d;margin:6px 0 14px;font-family:Montserrat, sans-serif;">Autentificare</h2>

    <form method="post" autocomplete="off" novalidate style="max-width:460px;display:flex;flex-direction:column;gap:12px;">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrfForView ?? ($_SESSION['csrf_token'] ?? '')); ?>">
      <label for="login-email" class="sr-only">Email</label>
      <input id="login-email" class="input" type="email" name="email" required placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="padding:12px;border:1px solid #ddd;border-radius:10px;">
      <label for="login-password" class="sr-only">Parolă</label>
      <input id="login-password" class="input" type="password" name="password" required placeholder="Parolă" style="padding:12px;border:1px solid #ddd;border-radius:10px;">
      <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" name="login" style="padding:12px 16px;background:linear-gradient(135deg,#b5651d,#8b4315);color:#fff;border:0;border-radius:10px;font-weight:600;cursor:pointer;">Login</button>
        <a href="#" style="padding:12px 16px;background:#f3f3f3;color:#111;border-radius:10px;text-decoration:none;">Recuperează parola</a>
      </div>
    </form>

    <?php if (!empty($loginFeedback)): ?>
      <p class="feedback" role="alert" style="color:#c0392b;margin-top:12px;"><?php echo htmlspecialchars($loginFeedback); ?></p>
    <?php endif; ?>
  </section>

  <section class="card social" aria-labelledby="social-title" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.06);">
    <h2 id="social-title" style="color:#b5651d;margin:6px 0 14px;font-family:Montserrat, sans-serif;">Autentificare rapidă</h2>

    <div class="social-login" style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="Login.php?action=google" class="social-btn google" style="padding:12px 16px;border-radius:10px;background:#db4437;color:#fff;text-decoration:none;font-weight:700;">Conectează cu Google</a>
      <a href="#" class="social-btn facebook" style="padding:12px 16px;border-radius:10px;background:#1877f2;color:#fff;text-decoration:none;font-weight:700;">Conectează cu Facebook</a>
      <a href="#" class="social-btn apple" style="padding:12px 16px;border-radius:10px;background:#000;color:#fff;text-decoration:none;font-weight:700;">Conectează cu Apple</a>
    </div>

    <?php if (!empty($socialFeedback)): ?>
      <p class="feedback" role="alert" style="color:#c0392b;margin-top:12px;"><?php echo htmlspecialchars($socialFeedback); ?></p>
    <?php endif; ?>
  </section>

<?php else: ?>

  <section class="card account" aria-labelledby="welcome-title" style="background:#fff;border-radius:12px;padding:28px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.06);max-width:820px;margin:40px auto;">
    <h2 id="welcome-title" style="color:#b5651d;margin:6px 0 12px;font-family:Montserrat, sans-serif;">Bine ai revenit</h2>
    <p style="font-weight:600;margin:10px 0;"><?php echo htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email']); ?></p>
    <div style="margin-top:12px;display:flex;gap:10px;justify-content:center;">
      <a class="btn-primary" href="Acasa.php" style="padding:12px 16px;background:linear-gradient(135deg,#b5651d,#8b4315);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">Pagina principală</a>
      <a class="btn-primary" href="Login.php?logout=1" style="padding:12px 16px;background:#f3f3f3;color:#111;border-radius:10px;text-decoration:none;font-weight:600;">Deconectare</a>
    </div>
  </section>

<?php endif; ?>

</main>

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

</body>
</html>

