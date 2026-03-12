<?php
// stripe_succes.php - actualizat: update status=paid + trimitere emailuri doar dupa confirmarea Stripe
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php';

// --- Email / SMTP setari (foloseste aceleasi valori ca in FinalizeazaComanda.php) ---
$smtpHost  = 'mail.magazinpsy.ro';
$smtpUser  = 'office@magazinpsy.ro';
$smtpPass  = 'pd3AOZRX4L7Af^Ii';
$smtpPort  = 465;

$fromEmail = 'office@magazinpsy.ro';
$fromName  = 'Magazin Psy';
$STORE_EMAIL = 'office@magazinpsy.ro';

// --- functii email (PHPMailer daca e disponibila, fallback la mail()) ---
function mailFallback($to, $subject, $htmlBody, $bcc = []) {
    global $fromEmail, $fromName;
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . $fromName . " <" . $fromEmail . ">\r\n";
    if (!empty($bcc)) $headers .= "Bcc: " . implode(',', $bcc) . "\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
}

function sendMailViaPHPMailer($to, $subject, $htmlBody, $bcc = []) {
    global $smtpHost, $smtpUser, $smtpPass, $smtpPort, $fromEmail, $fromName;

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('PHPMailer nu e instalat. Folosesc mail() ca fallback.');
        return mailFallback($to, $subject, $htmlBody, $bcc);
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        // minimal SMTP debug -> log
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug [$level]: $str"); };

        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$smtpPort;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        foreach ($bcc as $b) {
            if (filter_var($b, FILTER_VALIDATE_EMAIL)) $mail->addBCC($b);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('PHPMailer exception: ' . $e->getMessage());
        return mailFallback($to, $subject, $htmlBody, $bcc);
    } catch (Exception $e) {
        error_log('Generic mail exception: ' . $e->getMessage());
        return mailFallback($to, $subject, $htmlBody, $bcc);
    }
}

// --- functii email specifice comenzi (copiate si adaptate) ---
function sendOrderEmail(PDO $pdo, $order_id, $to = null) {
    global $STORE_EMAIL;
    $to = $to ?? $STORE_EMAIL;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $clientEmail = null;
    if (!empty($order['user_id'])) {
        $s = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        $s->execute([(int)$order['user_id']]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $clientEmail = $u['email'];
    }

    $html = '<html><body>';
    $html .= '<h2>Comanda #' . htmlspecialchars($order_id) . '</h2>';
    $html .= '<p><strong>Client ID:</strong> ' . htmlspecialchars($order['user_id'] ?? '') . '</p>';
    $html .= '<p><strong>Adresa livrare:</strong> ' . nl2br(htmlspecialchars($order['shipping_address'] ?? '')) . '</p>';
    $html .= '<p><strong>Telefon:</strong> ' . htmlspecialchars($order['shipping_phone'] ?? '') . '</p>';
    $html .= '<p><strong>Metoda livrare:</strong> ' . htmlspecialchars($order['shipping_method'] ?? '') . ' | <strong>Metoda plată:</strong> ' . htmlspecialchars($order['payment_method'] ?? '') . '</p>';

    $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';
    $html .= '<thead><tr><th align="left">Produs</th><th align="center">Cant.</th><th align="right">Pret/u</th><th align="right">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
        $price = number_format((float)($it['price'] ?? 0), 2, '.', '');
        $lineTotal = number_format($qty * (float)($it['price'] ?? 0), 2, '.', '');
        $productName = htmlspecialchars($it['product_name'] ?? $it['product_id']);
        $html .= '<tr>';
        $html .= '<td>' . $productName . '</td>';
        $html .= '<td align="center">' . $qty . '</td>';
        $html .= '<td align="right">' . $price . ' RON</td>';
        $html .= '<td align="right">' . $lineTotal . ' RON</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<p><strong>Cost transport:</strong> ' . number_format((float)($order['shipping_cost'] ?? 0), 2, '.', '') . ' RON</p>';
    $html .= '<p><strong>Total:</strong> ' . number_format((float)$order['total'], 2, '.', '') . ' RON</p>';
    $html .= '<p><em>Data comenzii: ' . htmlspecialchars($order['created_at'] ?? '') . '</em></p>';
    $html .= '</body></html>';

    $subject = 'Nouă comandă #' . $order_id;
    $bcc = [];
    if ($clientEmail) $bcc[] = $clientEmail;

    return sendMailViaPHPMailer($to, $subject, $html, $bcc);
}

function sendClientConfirmationEmail(PDO $pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT o.*, u.email as user_email, u.name as user_name FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || empty($order['user_email']) || !filter_var($order['user_email'], FILTER_VALIDATE_EMAIL)) return false;

    $clientEmail = $order['user_email'];
    $clientName = $order['user_name'] ?? 'Client';

    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '<html><body>';
    $html .= '<h2>Confirmare comandă #' . htmlspecialchars($order_id) . '</h2>';
    $html .= '<p>Bună ' . htmlspecialchars($clientName) . ',</p>';
    $html .= '<p>Mulțumim! Am înregistrat comanda ta. Mai jos găsești detaliile:</p>';
    $html .= '<ul>';
    $html .= '<li><strong>Adresa livrare:</strong> ' . nl2br(htmlspecialchars($order['shipping_address'] ?? '')) . '</li>';
    $html .= '<li><strong>Telefon:</strong> ' . htmlspecialchars($order['shipping_phone'] ?? '') . '</li>';
    $html .= '<li><strong>Metoda plată:</strong> ' . htmlspecialchars($order['payment_method'] ?? '') . '</li>';
    $html .= '</ul>';

    $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';
    $html .= '<thead><tr><th align="left">Produs</th><th align="center">Cant.</th><th align="right">Pret/u</th><th align="right">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
        $price = number_format((float)($it['price'] ?? 0), 2, '.', '');
        $lineTotal = number_format($qty * (float)($it['price'] ?? 0), 2, '.', '');
        $productName = htmlspecialchars($it['product_name'] ?? $it['product_id']);
        $html .= '<tr>';
        $html .= '<td>' . $productName . '</td>';
        $html .= '<td align="center">' . $qty . '</td>';
        $html .= '<td align="right">' . $price . ' RON</td>';
        $html .= '<td align="right">' . $lineTotal . ' RON</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p><strong>Total:</strong> ' . number_format((float)$order['total'], 2, '.', '') . ' RON</p>';
    $html .= '<p>Te vom anunța când comanda este expediată.</p>';
    $html .= '<p>Cu stimă,<br/>' . htmlspecialchars($GLOBALS['fromName'] ?? $fromName) . '</p>';
    $html .= '</body></html>';

    $subject = 'Confirmare comandă #' . $order_id;
    return sendMailViaPHPMailer($clientEmail, $subject, $html);
}

// --- DB connection ---
$host = 'localhost';
$db   = 'magazi15_ShergeiCovoare';
$user = 'magazi15_Alex';
$pass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Conexiune esuata: " . htmlspecialchars($e->getMessage());
    exit;
}

/* helper: verifica existenta coloanei in tabela orders */
function column_exists(PDO $pdo, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'orders'
          AND column_name = ?
    ");
    $stmt->execute([$column]);
    $row = $stmt->fetch();
    return (int)($row['cnt'] ?? 0) > 0;
}

/* Verificare parametru session_id */
$session_id = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';
if ($session_id === '') {
    http_response_code(400);
    echo "Session Stripe lipsa";
    exit;
}

/* Stripe API key */
\Stripe\Stripe::setApiKey('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');

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id, ['expand' => ['payment_intent']]);
} catch (\Exception $e) {
    http_response_code(500);
    echo "Eroare Stripe: " . htmlspecialchars($e->getMessage());
    exit;
}

/* Verificam status plata */
$payment_status = $session->payment_status ?? '';
if ($payment_status !== 'paid') {
    http_response_code(400);
    echo "Plata nu este finalizata";
    exit;
}

/* Citire metadata din sesiune Stripe */
$meta = [];
if (!empty($session->metadata) && (is_object($session->metadata) || is_array($session->metadata))) {
    foreach ($session->metadata as $k => $v) $meta[$k] = $v;
}

$user_id_meta      = isset($meta['user_id']) ? (int)$meta['user_id'] : 0;
$order_id_meta     = isset($meta['order_id']) ? (int)$meta['order_id'] : 0;
$order_subtotal_md = isset($meta['order_subtotal']) ? (float)$meta['order_subtotal'] : null;
$shipping_cost_md  = isset($meta['shipping_cost']) ? (float)$meta['shipping_cost'] : null;
$pay_shipping_md   = isset($meta['pay_shipping']) ? ((int)$meta['pay_shipping'] === 1) : null;

/* VALORI DEFAULT */
$DEFAULT_SHIPPING_COST = 25.00;
$payment_fee = 0.00;

/* PRELUARE SUME reale din sesiune Stripe */
$paid_total = 0.00;
if (isset($session->amount_total)) {
    $paid_total = (float)$session->amount_total / 100.0;
}

/* Determinari initiale */
$order_subtotal = $order_subtotal_md ?? 0.00;
$shipping_cost = ($shipping_cost_md !== null) ? $shipping_cost_md : $DEFAULT_SHIPPING_COST;

if ($pay_shipping_md !== null) {
    $shipping_paid = $pay_shipping_md;
} else {
    $shipping_paid = ($paid_total > $order_subtotal + 0.001);
}

/* shipping pentru DB */
$shipping_cost_for_db = $shipping_paid ? $shipping_cost : 0.00;

/* calc total final din Stripe (sursa de adevar) */
$order_total = round($paid_total, 2);

/* FUNCTII de cautare fallback comanda */
function find_best_pending_for_user(PDO $pdo, int $user_id, float $paid_total) {
    $stmt = $pdo->prepare("SELECT id, total FROM orders WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    if (!$orders) return 0;
    $bestId = 0;
    $bestDiff = PHP_FLOAT_MAX;
    foreach ($orders as $o) {
        $diff = abs((float)$o['total'] - $paid_total);
        if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $bestId = (int)$o['id'];
        }
    }
    if ($bestDiff <= 1.5) return $bestId;
    return (int)$orders[0]['id'];
}

function find_pending_by_total(PDO $pdo, float $paid_total) {
    $stmt = $pdo->prepare("SELECT id, total FROM orders WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR) ORDER BY created_at DESC LIMIT 200");
    $stmt->execute();
    $orders = $stmt->fetchAll();
    if (!$orders) return 0;
    $bestId = 0;
    $bestDiff = PHP_FLOAT_MAX;
    foreach ($orders as $o) {
        $diff = abs((float)$o['total'] - $paid_total);
        if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $bestId = (int)$o['id'];
        }
    }
    if ($bestDiff <= 1.5) return $bestId;
    return 0;
}

/* Determinare order_id cu fallback */
$order_id = $order_id_meta;

if ($order_id <= 0 && $user_id_meta > 0) {
    $order_id = find_best_pending_for_user($pdo, $user_id_meta, $paid_total);
}

if ($order_id <= 0 && $paid_total > 0.0) {
    $order_id = find_pending_by_total($pdo, $paid_total);
}

if ($order_id <= 0) {
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) $order_id = (int)$row['id'];
}

/* Daca nu gasim order_id, log si redirect fara id */
if ($order_id <= 0) {
    error_log("stripe_succes.php: nu am gasit order_id pentru session {$session_id}, metadata: " . json_encode($meta));
    unset($_SESSION['cart'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
    header("Location: " . $baseUrl . "/Acasa.php?payment=success");
    exit;
}

/* Citire comanda din DB */
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    http_response_code(500);
    echo "Eroare DB: " . htmlspecialchars($e->getMessage());
    exit;
}

if (!$order) {
    error_log("stripe_succes.php: comanda inexistenta id={$order_id}");
    unset($_SESSION['cart'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
    header("Location: " . $baseUrl . "/Acasa.php?payment=success");
    exit;
}

/* Daca e deja platita, redirect cu id */
if (($order['status'] ?? '') === 'paid') {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
    header("Location: " . $baseUrl . "/Acasa.php?payment=success&order_id=" . urlencode($order_id));
    exit;
}

/* Determinare valori finale pentru DB update.
   Folosim suma platita de Stripe pentru total.
   Shipping cost deja calculat mai sus. */
$final_total = $order_total > 0 ? $order_total : round($order_subtotal + $shipping_cost_for_db + $payment_fee, 2);
$final_shipping = $shipping_cost_for_db;
$final_payment_fee = $payment_fee;

/* Pregatire update dinamic. Verificam daca coloanele stripe_* exista. */
$has_session_col = column_exists($pdo, 'stripe_session_id');
$has_pi_col = column_exists($pdo, 'stripe_payment_intent');
$has_payment_method_col = column_exists($pdo, 'payment_method');

$updateCols = [
    'status = ?',
    'total = ?',
    'shipping_cost = ?',
    'payment_fee = ?',
    'updated_at = NOW()'
];

$params = ['paid', $final_total, $final_shipping, $final_payment_fee];

if ($has_session_col) {
    $updateCols[] = 'stripe_session_id = ?';
    $params[] = $session_id;
}

$payment_intent_id = '';
if (!empty($session->payment_intent)) {
    if (is_string($session->payment_intent)) $payment_intent_id = $session->payment_intent;
    elseif (is_object($session->payment_intent) && isset($session->payment_intent->id)) $payment_intent_id = $session->payment_intent->id;
}
if ($has_pi_col) {
    $updateCols[] = 'stripe_payment_intent = ?';
    $params[] = $payment_intent_id;
}

// setam metoda de plata la 'online' daca exista coloana
if ($has_payment_method_col) {
    $updateCols[] = 'payment_method = ?';
    $params[] = 'online';
}

$params[] = $order_id;

$sql = "UPDATE orders SET " . implode(', ', $updateCols) . " WHERE id = ?";

try {
    $pdo->beginTransaction();
    $stmtUpd = $pdo->prepare($sql);
    $stmtUpd->execute($params);
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Eroare actualizare comanda: " . htmlspecialchars($e->getMessage());
    exit;
}

/* Dupa ce comanda este marcata platita -> trimitem emailuri (magazin + client) */
try {
    // trimitem email magazin (inclusiv BCC client)
    try {
        sendOrderEmail($pdo, $order_id, $STORE_EMAIL);
    } catch (Exception $e) {
        error_log("Eroare la sendOrderEmail pentru order {$order_id}: " . $e->getMessage());
    }

    // trimitem confirmare catre client
    try {
        sendClientConfirmationEmail($pdo, $order_id);
    } catch (Exception $e) {
        error_log("Eroare la sendClientConfirmationEmail pentru order {$order_id}: " . $e->getMessage());
    }
} catch (Exception $e) {
    // nu blocam redirect-ul daca email-urile esueaza
    error_log("Eroare trimitere emailuri post-plata pentru order {$order_id}: " . $e->getMessage());
}

/* Curatare sesiune server side */
unset($_SESSION['cart'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);

/* Redirect catre Acasa cu order_id */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
$acasaUrl = $baseUrl . '/Acasa.php?payment=success&order_id=' . urlencode($order_id);

header("Location: " . $acasaUrl);
exit;