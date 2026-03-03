<?php
// stripe_succes.php - actualizat, tolerant si robust
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php';

// Conexiune DB
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

/* Cheia Stripe de test */
\Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

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