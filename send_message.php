<?php
// send_message.php - handler contact (PHPMailer robust + fallback mail)
// Paste exact acest fisier in proiect (suprascrie vechiul send_message.php)

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
session_start();

// redirect tinta: referer daca exista, altfel Acasa.php
$redirect = (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Acasa.php';
function redirect_with($ok, $msg = null, $redirect) {
    if ($msg) $_SESSION['contact_flash'] = $msg;
    header('Location: ' . $redirect . (strpos($redirect, '?') === false ? '?' : '&') . 'success=' . ($ok ? '1' : '0'));
    exit;
}

// preluare input
$name    = trim((string)($_POST['name']  ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// validari minime
if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with(false, 'Completeaza corect numele, emailul si mesajul.', $redirect);
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = null;
try {
    $pdo = new PDO($dsn, $user, $pass, $pdoOptions);
} catch (Throwable $e) {
    error_log('send_message.php - DB connect error: ' . $e->getMessage());
    // nu blocheaza trimiterea; continuam doar fara salvare
}

/* ========== Optional: inserare in DB (daca exista conexiune) ========== */
$saved = false;
if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, phone, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $phone, $email, $message]);
        $saved = true;
    } catch (Throwable $e) {
        error_log('send_message.php - DB insert error: ' . $e->getMessage());
    }
}

/* ========== TRIMITERE EMAIL ========== */
// Setari SMTP
$smtpHost  = 'mail.magazinpsy.ro';
$smtpUser  = 'office@magazinpsy.ro';
$smtpPass  = 'pd3AOZRX4L7Af^Ii';
$smtpPort  = 465; // 465 pentru SMTPS, 587 pentru STARTTLS
$fromEmail = 'office@magazinpsy.ro';
$fromName  = 'Magazin Psy';
$toEmail   = 'office@magazinpsy.ro';
$subject   = 'Mesaj contact site - ' . ($name ?: 'Vizitator');

$html  = "<h3>Mesaj contact de pe site</h3>";
$html .= "<p><strong>Nume:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>";
$html .= "<p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>";
$html .= "<p><strong>Telefon:</strong> " . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</p>";
$html .= "<p><strong>Mesaj:</strong><br>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>";

/* fallback mail() */
$mailFallback = function($to, $subj, $body, $replyTo = null, $fEmail=null, $fName=null){
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    if ($fName || $fEmail) $headers .= "From: " . ($fName ?? '') . " <" . ($fEmail ?? '') . ">\r\n";
    if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
    return @mail($to, $subj, $body, $headers);
};

$sentMail = false;
$autoload = __DIR__ . '/vendor/autoload.php';
$enable_debug = (isset($_GET['debug']) && $_GET['debug'] === '1'); // foloseste ?debug=1 pentru teste

if (file_exists($autoload)) {
    try {
        require_once $autoload;
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                // Debug - scrie in error_log daca activezi debug
                $mail->SMTPDebug = $enable_debug ? 2 : 0;
                $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug [$level]: $str"); };

                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                // Recomandari: foloseste SMTPS (465) sau STARTTLS (587) dupa configuratia hostului
                if ($smtpPort == 465) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Port = (int)$smtpPort;

                // unele hosturi necesita aceste optiuni (self-signed certs)
                $mail->SMTPAutoTLS = false;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                // IMPORTANT: setFrom sa fie acelasi domeniu/cont ca SMTP user
                $mail->setFrom($smtpUser, $fromName);
                $mail->addAddress($toEmail);
                $mail->addReplyTo($email, $name);

                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $html;
                $mail->AltBody = strip_tags($html);

                $sentMail = (bool)$mail->send();
                if (!$sentMail) {
                    error_log('send_message.php - PHPMailer->send returned false (no exception).');
                }
            } catch (Throwable $e) {
                error_log('send_message.php - PHPMailer send failed: ' . $e->getMessage());
                // fallback la mail()
                $sentMail = $mailFallback($toEmail, $subject, $html, $email, $fromEmail, $fromName);
            }
        } else {
            error_log('send_message.php - PHPMailer class not found in autoload.');
            $sentMail = $mailFallback($toEmail, $subject, $html, $email, $fromEmail, $fromName);
        }
    } catch (Throwable $e) {
        error_log('send_message.php - Error loading autoload: ' . $e->getMessage());
        $sentMail = $mailFallback($toEmail, $subject, $html, $email, $fromEmail, $fromName);
    }
} else {
    error_log('send_message.php - vendor/autoload.php not found -> using mail() fallback.');
    $sentMail = $mailFallback($toEmail, $subject, $html, $email, $fromEmail, $fromName);
}

/* ========== Final redirect ========== */
if ($sentMail) {
    redirect_with(true, 'Mesaj trimis cu succes.', $redirect);
} else {
    if (!$saved) {
        redirect_with(false, 'Eroare la trimiterea si salvarea mesajului. Contacteaza direct office@magazinpsy.ro.', $redirect);
    } else {
        redirect_with(false, 'Eroare la trimiterea mesajului. Mesajul a fost salvat; contacteaza direct office@magazinpsy.ro pentru confirmare.', $redirect);
    }
}
