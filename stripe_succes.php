<?php
session_start();
require 'vendor/autoload.php';

/* =========================
   CONECTARE DB
========================= */
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $user, $pass, $options);

/* =========================
   STRIPE SESSION
========================= */
$session_id = $_GET['session_id'] ?? '';
if (!$session_id) die("Session Stripe lipsa");

\Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

$session = \Stripe\Checkout\Session::retrieve($session_id);

if ($session->payment_status !== 'paid') {
    die("Plata nu este finalizata");
}

/* =========================
   DATE STRIPE CORECTE
========================= */
$user_id        = (int)$session->metadata->user_id;
$shipping_method = $session->metadata->shipping_method;
$order_subtotal  = (float)$session->metadata->order_subtotal;
$shipping_cost   = (float)$session->metadata->shipping_cost;
$payment_fee     = (float)$session->metadata->payment_fee;

/* suma REAL platita */
$paid_total = $session->amount_total / 100;

/* a fost platit transportul? */
$shipping_paid = ($paid_total > $order_subtotal);

/* daca NU a fost platit, anulam transportul */
if (!$shipping_paid) {
    $shipping_cost = 0;
}

/* total final */
$order_total = round($order_subtotal + $shipping_cost + $payment_fee, 2);

/* =========================
   DATE USER
========================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die("User invalid");

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type='shipping' LIMIT 1");
$stmt->execute([$user_id]);
$addr = $stmt->fetch();

$shipping_name = $addr['name'] ?? $user['name'];
$shipping_phone = $addr['phone'] ?? $user['phone'];
$shipping_address = $addr
    ? $addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']
    : 'Adresa necunoscuta';

$cart = $_SESSION['checkout_cart'] ?? [];
if (empty($cart)) die("Cos gol");

/* =========================
   COMANDA
========================= */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO orders
        (user_id, total, shipping_method, payment_method, shipping_name,
         shipping_phone, shipping_address, status, shipping_cost,
         payment_fee, created_at)
        VALUES (?, ?, ?, 'online', ?, ?, ?, 'paid', ?, ?, NOW())
    ");

    $stmt->execute([
        $user_id,
        $order_total,
        $shipping_method,
        $shipping_name,
        $shipping_phone,
        $shipping_address,
        $shipping_cost,
        $payment_fee
    ]);

    $order_id = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $stmtItem->execute([
            $order_id,
            (int)$item['id'],
            (int)$item['qty'],
            (float)$item['price']
        ]);
    }

    $pdo->commit();

    unset($_SESSION['cart'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);

    header("Location: FinalizareComanda.php?payment=success&order_id=$order_id");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Eroare comanda: " . $e->getMessage());
}
