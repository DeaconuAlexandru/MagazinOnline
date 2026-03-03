<?php

session_start();

// Conexiune DB
$host = 'localhost';
$db   = 'magazi15_ShergeiCovoare';
$user = 'magazi15_Alex';
$pass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Conexiune esuata");
}

/* =========================
   VERIFICARE LOGIN
========================= */
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'ContinuaComanda.php';
    header("Location: Login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   VERIFICARE COS
========================= */
$cart  = $_SESSION['checkout_cart'] ?? [];
$total = $_SESSION['checkout_total'] ?? 0.0;

if (!$cart) {
    header("Location: CosulMeu.php");
    exit;
}

/* =========================
   PRELUARE USER
========================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User invalid");
}

/* =========================
   PRELUARE ADRESE LIVRARE
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM user_addresses
    WHERE user_id = ? AND type = 'shipping'
");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

/* =========================
   PROCESARE COMANDA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipping_method = trim($_POST['shipping_method'] ?? 'courier');
    $payment_method  = trim($_POST['payment_method'] ?? 'online');

    $easybox_id     = null;
    $easybox_city   = null;
    $easybox_county = null;

    try {
        $pdo->beginTransaction();

        /* =========================
           LIVRARE EASYBOX
        ========================== */
        if ($shipping_method === 'easybox') {

            $easybox_id = (int)($_POST['easybox_id'] ?? 0);
            if ($easybox_id <= 0) {
                throw new Exception("EasyBox lipsă");
            }

            $stmt = $pdo->prepare("
                SELECT name, address, city, county, active
                FROM easyboxes
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$easybox_id]);
            $box = $stmt->fetch();

            if (!$box || (int)$box['active'] !== 1) {
                throw new Exception("EasyBox invalid");
            }

            $easybox_city   = $box['city'];
            $easybox_county = $box['county'];

            $shipping_name    = 'Ridicare EasyBox - ' . $box['name'];
            $shipping_phone   = $user['phone'] ?? '';
            $shipping_address = "{$box['county']}, {$box['city']}, {$box['address']}";

        } else {

            /* =========================
               LIVRARE CURIER
            ========================== */
            $address_id = (int)($_POST['shipping_address_id'] ?? 0);
            if ($address_id <= 0) {
                throw new Exception("Adresă lipsă");
            }

            $stmt = $pdo->prepare("
                SELECT name, phone, county, city, address_line
                FROM user_addresses
                WHERE id = ? AND user_id = ? AND type = 'shipping'
                LIMIT 1
            ");
            $stmt->execute([$address_id, $user_id]);
            $addr = $stmt->fetch();

            if (!$addr) {
                throw new Exception("Adresă invalidă");
            }

            $shipping_name    = $addr['name'];
            $shipping_phone   = $addr['phone'];
            $shipping_address = "{$addr['county']}, {$addr['city']}, {$addr['address_line']}";
        }

        /* =========================
           COSTURI & STATUS
        ========================== */
        $shipping_cost = ($shipping_method === 'easybox') ? 12.99 : 25.00;
        $payment_fee   = ($payment_method === 'delivery') ? 10.00 : 0.00;

        $order_total = round(
            (float)$total + $shipping_cost + $payment_fee,
            2
        );

        $status = ($payment_method === 'online')
            ? 'pending_payment'
            : 'processing';

        /* =========================
           INSERARE COMANDA
        ========================== */
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (
                user_id, total, shipping_method,
                easybox_id, easybox_city, easybox_county,
                payment_method,
                shipping_name, shipping_phone, shipping_address,
                status, shipping_cost, payment_fee, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $user_id,
            $order_total,
            $shipping_method,
            $easybox_id,
            $easybox_city,
            $easybox_county,
            $payment_method,
            $shipping_name,
            $shipping_phone,
            $shipping_address,
            $status,
            $shipping_cost,
            $payment_fee
        ]);

        $order_id = $pdo->lastInsertId();

        /* =========================
           ORDER ITEMS + STOC
        ========================== */
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items
            (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        $stmtStock = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND stock >= ?
        ");

        foreach ($cart as $item) {
            $pid = (int)$item['id'];
            $qty = max(1, (int)$item['qty']);
            $prc = (float)$item['price'];

            $stmtItem->execute([$order_id, $pid, $qty, $prc]);
            $stmtStock->execute([$qty, $pid, $qty]);

            if (!$stmtStock->rowCount()) {
                throw new Exception("Stoc insuficient");
            }
        }

        $pdo->commit();

        unset(
            $_SESSION['cart'],
            $_SESSION['cart_total'],
            $_SESSION['checkout_cart'],
            $_SESSION['checkout_total']
        );

        if ($payment_method === 'online') {
            header("Location: FinalizeazaComanda.php?order_id=$order_id");
        } else {
            header("Location: FinalizeazaComanda.php?order=$order_id");
        }
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['order_error'] = $e->getMessage();
        header("Location: ContinuaComanda.php?error=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Continuă comanda</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 20px;
}

/* Container responsive */
.container {
    width: 100%;
    max-width: 900px;  /* limitează lățimea pe desktop normal */
    margin: 0 auto;
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    box-sizing: border-box;
}

/* Map pickup responsive */
#pickup-map {
    width: 100%;
    height: 400px;
    max-height: 60vh;
    border: 1px solid #ccc;
}

/* Media queries pentru desktop mare */
@media (min-width: 1200px) {
    .container {
        max-width: 1200px; /* container mai mare pe ecrane late */
        padding: 30px;
    }

    #pickup-map {
        height: 500px; /* harta mai mare pe desktop mare */
    }
}

/* Media queries pentru laptop/tablet */
@media (max-width: 1200px) and (min-width: 769px) {
    .container {
        max-width: 95%;
        padding: 20px;
    }

    #pickup-map {
        height: 400px;
    }
}

/* Media queries pentru mobil */
@media (max-width: 768px) {
    .container {
        padding: 10px;
        margin: 0 10px;
    }

    #pickup-map {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 8px;
        margin: 0 5px;
    }

    #pickup-map {
        height: 250px;
    }
}



.checkout-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
}

.checkout-step {
    text-align: center;
    flex: 1;
    position: relative;
}

.checkout-step::after {
    content: "";
    position: absolute;
    top: 50%;
    right: 0;
    width: 100%;
    height: 4px;
    background: #ddd;
    z-index: -1;
    transform: translateY(-50%);
}

.checkout-step:last-child::after {
    display: none;
}

.step-number {
    display: inline-block;
    width: 32px;
    height: 32px;
    line-height: 32px;
    border-radius: 50%;
    background: #ddd;
    color: #fff;
    font-weight: bold;
    margin-bottom: 5px;
}

.checkout-step.active .step-number {
    background: #4caf50;
}

.step-title {
    display: block;
    font-size: 14px;
    font-weight: 600;
}



/* Container card-uri livrare */
.delivery-options {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: stretch;
  margin-top: 10px;
}

/* Card-urile de livrare */
.card-option {
  box-sizing: border-box;
  flex: 1 1 220px;
  min-width: 200px;
  max-width: 400px;
  border: 1px solid #ccc;
  padding: 18px 16px;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease, background-color 160ms ease;
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  user-select: none;
}

/* Card activ */
.card-option.active {
  border-color: #4caf50;
  background: #e8f5e9;
  box-shadow: 0 6px 18px rgba(76,175,80,0.08);
  transform: translateY(-3px);
}

/* Hover și focus */
.card-option:hover,
.card-option:focus {
  border-color: #4caf50;
  background: #f1fdf4;
  outline: none;
  transform: translateY(-2px);
}

/* Card inactiv */
.card-option.disabled {
  pointer-events: none;
  opacity: 0.6;
}

/* Link-uri din card să nu distrugă layoutul */
.card-option a {
  color: inherit;
  text-decoration: none;
}

/* Responsive: pe ecrane mici stivuim cardurile */
@media (max-width: 640px) {
  .delivery-options { flex-direction: column; }
  .card-option { width: 100%; min-width: 0; max-width: none; }
}


#shipping-details,
#payment-info {
    margin-top: 10px;
    padding: 10px;
    background: #fafafa;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header button {
    padding: 6px 10px;
    cursor: pointer;
}

.summary {
    margin-top: 25px;
    padding: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.submit-order {
    margin-top: 15px;
    padding: 10px 20px;
    background: #4caf50;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 4px;
}
.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 16px;
    background: #eee;
    color: #333;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: background 0.2s, color 0.2s;
}

.back-btn:hover {
    background: #4caf50;
    color: #fff;
}
/* Buton Alege alta adresa */
.change-address-btn {
    padding: 8px 14px;
    background: #fff;
    color: #007bff;
    border: 1px solid #007bff;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}
.change-address-btn:hover {
    background: #007bff;
    color: #fff;
}

/* Modal */
.address-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    width: 90%;
    max-width: 500px;
    animation: slide-down 0.3s ease-out;
}
@keyframes slide-down {
    from { transform: translateY(-50px); opacity:0; }
    to { transform: translateY(0); opacity:1; }
}
.close-modal {
    float: right;
    font-size: 24px;
    cursor: pointer;
}
.address-item {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.address-item:hover {
    background: #f0f8ff;
    border-color: #007bff;
}
.add-new-address {
    margin-top: 10px;
    padding: 8px 14px;
    background: #fff;
    color: #007bff;
    border: 1px solid #007bff;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}
.add-new-address:hover {
    background: #007bff;
    color: #fff;
}
/* Modernizare modal adaugare adresa */
#new-address-modal .modal-content{
  width: 100%;
  max-width: 460px;
  padding: 20px 22px;
  border-radius: 12px;
  box-shadow: 0 18px 48px rgba(12,30,60,0.18);
  transform: translateY(-6px);
  transition: transform .18s ease, box-shadow .18s ease;
  background: linear-gradient(180deg,#ffffff,#fbfdff);
  border: 1px solid rgba(20,60,120,0.06);
  display: flex;
  flex-direction: column;
  gap: 10px;
}
#new-address-modal .modal-content:focus { outline: none; transform: translateY(0); }

/* Inputs */
#new-address-modal input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #e0e6ef;
  box-sizing: border-box;
  font-size: 14px;
  transition: box-shadow .12s ease, border-color .12s ease;
}
#new-address-modal input:focus {
  border-color: #4b7cf5;
  box-shadow: 0 6px 18px rgba(75,124,245,0.12);
  outline: none;
}

/* Butoane form */
#new-address-modal .form-actions{
  display:flex;
  gap:10px;
  justify-content:flex-end;
  margin-top:6px;
}
.btn-primary {
  background: linear-gradient(180deg,#2b64ff,#1f4fe6);
  color:#fff;
  padding:10px 14px;
  border-radius:10px;
  border:0;
  cursor:pointer;
  font-weight:700;
  transition: transform .08s ease, box-shadow .12s ease, opacity .12s ease;
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(35,65,170,0.18);
}
.btn-secondary {
  background: transparent;
  color:#2741c6;
  border:1px solid rgba(39,65,198,0.12);
  padding:10px 12px;
  border-radius:10px;
  cursor:pointer;
  font-weight:700;
}
.btn-secondary:hover {
  background: rgba(39,65,198,0.06);
}

/* Close icon modern */
#new-address-modal .close-x {
  font-size:20px;
  color:#51608a;
  background:transparent;
  border:0;
  cursor:pointer;
}

/* Pickup Section responsive */
#pickup-section {
    display: none;
    margin-top: 20px;
}

#pickup-map {
    width: 100%;
    height: 400px;
    max-height: 80vh; /* pentru ecrane mici */
    border: 1px solid #ccc;
}

/* Media queries pentru ecrane mai mici */
@media (max-width: 768px) {
    #pickup-map {
        height: 300px;
    }
}

@media (max-width: 480px) {
    #pickup-map {
        height: 250px;
    }
    #pickup-message {
        font-size: 14px;
    }
}
/* container lista */
.address-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* card adresa */
.address-item.selectable {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 2px solid #ddd;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s ease;
    background: #fff;
}

/* hover */
.address-item.selectable:hover {
    border-color: #007bff;
    background: #f5f9ff;
}

/* selectat */
.address-item.selectable.active {
    border-color: #007bff;
    background: #eef5ff;
}

/* bulina (radio custom) */
.radio-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #007bff;
    flex-shrink: 0;
    position: relative;
}

/* punctul din interior */
.address-item.active .radio-dot::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 8px;
    height: 8px;
    background: #007bff;
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

/* text */
.address-text strong {
    font-size: 15px;
}

.address-text p {
    font-size: 14px;
    margin: 4px 0 0;
    color: #555;
}

/* mobil */
@media (max-width: 480px) {
    .address-item.selectable {
        padding: 12px;
        gap: 10px;
    }
}

/* EasyBox logo in card */
.easybox-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
}

.easybox-logo img {
    height: 28px;
}

.easybox-logo span {
    font-size: 16px;
    font-weight: 700;
    color: #2ecc71;
}
#pickup-section .easybox-logo {
    margin-top: 12px;
    justify-content: flex-start;
}

/* Card EasyBox diferentiat */
.card-option[data-shipping="pickup"] {
    border-color: #2ecc71;
}

.card-option[data-shipping="pickup"].active {
    background: #e9f9f0;
    border-color: #2ecc71;
}
.payment-option {
    border: 1px solid #ccc;
    padding: 18px 20px;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 12px;
    transition: all 0.18s ease;
    background: #fff;
    box-shadow: 0 1px 0 rgba(0,0,0,0.02);
}

.payment-option h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
}

.payment-option p { margin: 0 0 8px 0; color:#444; font-size:14px; }

.payment-option.active {
    background: #e8f1ff; /* albastru pal */
    border-color: #4b7cf5; /* albastru intens marginal */
    box-shadow: 0 6px 18px rgba(75,124,245,0.12);
}

.payment-badge {
    display:inline-block;
    font-size:12px;
    padding:6px 8px;
    border-radius:6px;
    background:#f1f5ff;
    color:#2741c6;
    font-weight:600;
    margin-top:6px;
}
#payment-info {
    margin-top:12px;
    padding:12px;
    background:#fafcff;
    border:1px solid #e6edff;
    border-radius:8px;
    color:#123;
}
/* Modal mini Detalii Card */
.detail-modal {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.45);
    z-index: 12000;
}
.detail-modal.open { display: flex; }

.detail-modal .dialog {
    width: 100%;
    max-width: 520px;
    background: #fff;
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
    box-sizing: border-box;
}

.detail-modal .dialog h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.detail-modal .features {
    margin-top: 8px;
}

.detail-modal .feature {
    margin-bottom: 12px;
}

.detail-modal .feature strong { display:block; margin-bottom:6px; font-size:15px; }
.detail-modal .feature p { margin:0; color:#444; font-size:14px; line-height:1.35; }

.detail-modal .actions {
    margin-top: 14px;
    display:flex;
    gap:12px;
    justify-content:space-between;
    align-items:center;
}

.btn {
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-weight:600;
    border: none;
}

.btn-primary {
    background:#2948f0;
    color:#fff;
}

.btn-secondary {
    background:#f1f3f7;
    color:#222;
}

/* Close icon */
.detail-modal .close-x {
    float: right;
    background:transparent;
    border:0;
    font-size:20px;
    line-height:1;
    cursor:pointer;
    color:#666;
}

/* Responsive */
@media (max-width:480px) {
    .detail-modal .dialog { padding:14px; }
    .actions { flex-direction: column-reverse; gap:8px; }
}

/* --------------------------------------
   Stiluri pentru modalul EasyBox / butoane
   -------------------------------------- */
.pickup-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.pickup-modal-content {
  width: 90%;
  height: 80vh;
  background: #fff;
  display: flex;
  border-radius: 12px;
  overflow: hidden;
}

.pickup-left {
  width: 35%;
  padding: 12px;
  overflow-y: auto;
  border-right: 1px solid #ddd;
}

.pickup-right {
  width: 65%;
  position: relative;
}

#pickup-modal-map {
  width: 100%;
  height: 100%;
}
/* Stil modern pentru butonul "Modifică" din Date facturare */
#edit-billing-btn {
  background: transparent;
  color: #1E66FF;                     /* albastru vibrant */
  border: 1px solid rgba(30,102,255,0.12);
  padding: 8px 12px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .14s ease, background .14s ease, color .12s ease;
  box-shadow: 0 6px 18px rgba(30,102,255,0.06);
  -webkit-font-smoothing:antialiased;
  backface-visibility: hidden;
}

/* Hover */
#edit-billing-btn:hover {
  background: linear-gradient(180deg, rgba(30,102,255,0.06), rgba(30,102,255,0.02));
  color: #08387A;                      /* nuanță mai închisă la hover */
  transform: translateY(-2px);
  box-shadow: 0 14px 36px rgba(30,102,255,0.12);
}

/* Focus accesibilitate */
#edit-billing-btn:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(30,102,255,0.12);
}
/* Stil modern pentru butonul Finalizează comanda */
#finalize-order-btn {
  background: transparent;
  color: #1aa84b;                       /* text verde vibrant */
  border: 2px solid rgba(26,168,75,0.12);
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 15px;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(26,168,75,0.06);
  transition: transform .12s ease, box-shadow .14s ease, background .14s ease, color .12s ease;
  -webkit-font-smoothing: antialiased;
  backface-visibility: hidden;
}

/* Hover: fundal verde, text alb, micro-elevatie */
#finalize-order-btn:hover {
  background: linear-gradient(180deg, #13b34a, #0fa03b);
  color: #ffffff;
  transform: translateY(-3px);
  box-shadow: 0 18px 48px rgba(15,160,59,0.16);
}

/* Focus accesibilitate */
#finalize-order-btn:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(26,168,75,0.12);
}

/* Disabled state (dacă vei seta atributul disabled din JS/PHP) */
#finalize-order-btn[disabled] {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

</style>
</head>

<body>
<div class="container">
    <a href="Acasa.php" class="back-btn">Du-ma inapoi</a>

    <div class="checkout-steps">
        <div class="checkout-step">
            <span class="step-number">1</span>
            <span class="step-title">Coșul meu</span>
        </div>
        <div class="checkout-step active">
            <span class="step-number">2</span>
            <span class="step-title">Detalii comanda</span>
        </div>
        <div class="checkout-step">
            <span class="step-number">3</span>
            <span class="step-title">Comanda plasata</span>
        </div>
    </div>

            <form id="checkoutForm" method="post" action="ContinuaComanda.php">
             <!-- HIDDEN INPUTS – SHIPPING / EASYBOX -->
            <!-- optional, deja existent -->
            <input type="hidden" name="pickup_location" id="pickup_location">
			<input type="hidden" name="shipping_method" id="shipping_method" value="courier">
			<input type="hidden" name="shipping_address_id" id="shipping_address_id" value="<?= (int)($firstAddress['id'] ?? 0) ?>">



                <!-- Lista produse -->
                <div class="section">
                    <h3>Produsele din coș</h3>
                    <table>
                        <tr>
                            <th>Imagine</th>
                            <th>Produs</th>
                            <th>Pret</th>
                            <th>Cantitate</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php
                        $totalProducts = 0;
                        foreach($cart as $item):
                            $price = (float)$item['price'];
                            $subtotal = $price * $item['qty'];
                            $totalProducts += $subtotal;
                        ?>
                        <tr class="cart-item" data-price="<?= $price ?>">
                            <td><img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="100"></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= number_format($price, 2) ?> Lei</td>
                            <td><?= (int)$item['qty'] ?></td> <!-- afișăm cantitatea, fără input -->
                            <td class="row-subtotal"><?= number_format($subtotal, 2) ?> Lei</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <p class="total">Total produse: <span id="order-subtotal-display"><?= number_format($totalProducts, 2) ?></span> Lei</p>
                </div>


            <!-- hidden pentru JS -->
            <input type="hidden" id="order-subtotal-hidden" value="<?= $totalProducts ?>">
			<input type="hidden" name="shipping_method" id="shipping_method" value="<?= htmlspecialchars($addresses['shipping']['type'] ?? 'courier') ?>">


            <!-- Shipping -->
            <div class="section">
                <h3>Metoda livrare</h3>

                <div class="delivery-options">
                    <div class="card-option <?= ($addresses['shipping']['type'] ?? 'courier') === 'courier' ? 'active' : '' ?>" data-shipping="courier">
                        <h4>Livrare prin curier</h4>
                    </div>

                    <div class="card-option <?= ($addresses['shipping']['type'] ?? '') === 'pickup' ? 'active' : '' ?>" data-shipping="pickup">
                        <h4>Ridicare personală</h4>
                    </div>
                </div>

            </div>

            <div id="shipping-details"></div>

        <!-- Templates pentru JS -->
        <div id="courier-template" style="display:none">
            <p>Livrare prin curier la:</p>
            <strong><?= htmlspecialchars($firstAddress['name'] ?? $user['name']) ?>, <?= htmlspecialchars($firstAddress['phone'] ?? $user['phone']) ?></strong>
            <p><?= htmlspecialchars(($firstAddress['county'] ?? '') . ($firstAddress['county'] ? ', ' : '') . ($firstAddress['city'] ?? '') . ($firstAddress['city'] ? ', ' : '') . ($firstAddress['address_line'] ?? '')) ?></p>
            <p>Cost livrare: <?= number_format($shippingCost, 2) ?> Lei</p>
            <button type="button" class="change-address-btn" onclick="document.getElementById('address-modal').style.display='flex'">Alege alta adresa</button>
        </div>
		</form>

<!-- Date facturare -->
<div class="section">
    <div class="section-header">
        <h3>Date facturare</h3>
        <div>
            <button type="button" id="edit-billing-btn">Modifică</button>
        </div>
    </div>

    <!-- Afișare adresă curentă (va fi actualizata din JS) -->
    <div id="billing-details">
        <?php
            $billing = $firstAddress ?? ['name' => $user['name'], 'phone' => $user['phone'], 'county' => '', 'city' => '', 'address_line' => ''];
        ?>
        <p>
            <strong id="billing-name"><?= htmlspecialchars($billing['name']) ?> - <span id="billing-phone"><?= htmlspecialchars($billing['phone']) ?></span></strong><br>
            <span id="billing-address"><?= htmlspecialchars($billing['county'] . ($billing['county'] ? ', ' : '') . $billing['city'] . ($billing['city'] ? ', ' : '') . $billing['address_line']) ?></span>
        </p>
    </div>

<!-- Modal adrese facturare (ramane) -->
<div id="billing-modal" class="address-modal">
    <div class="modal-content">
        <span id="close-billing-modal" class="close-modal">&times;</span>
        <h3>Alege adresa de facturare</h3>

        <div class="address-list">
            <?php foreach($addresses as $addr): ?>
                <?php $fullAddress = trim($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']); ?>
                <div class="address-item selectable" 
                     data-id="<?= (int)$addr['id'] ?>"
                     data-name="<?= htmlspecialchars($addr['name'], ENT_QUOTES) ?>"
                     data-phone="<?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?>"
                     data-address="<?= htmlspecialchars($fullAddress, ENT_QUOTES) ?>">
                    <span class="radio-dot"></span>
                    <div class="address-text">
                        <strong><?= htmlspecialchars($addr['name'], ENT_QUOTES) ?> - <?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?></strong>
                        <p><?= htmlspecialchars($fullAddress, ENT_QUOTES) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

        <!-- Metoda plata (bloc complet: optiuni, info, modal, hidden fields, script) -->
        <div class="section">
            <h3>Metoda plata</h3>
            <?php
                $payment_methods = [
                    'online' => [
                        'title' => 'Card online',
                        'desc' => 'Plătești imediat, fără costuri suplimentare.'
                    ],
                    'delivery' => [
                        'title' => 'Card bancar la punctul de livrare',
                        'desc' => 'Plătești cu cardul la ridicare. Cost procesare 10,00 Lei.'
                    ]
                ];

                // setează default dacă nu există
                $selected_payment = $_POST['payment_method'] ?? 'online';
            ?>
        <div class="payment-grid">
            <div class="payment-option" 
                 data-pay="online" 
                 data-shipping="all"
                 id="pay-online">
                <h4>Card online</h4>
                <p>Plătești imediat, fără costuri suplimentare.</p>
            </div>

            <div class="payment-option" 
                 data-pay="delivery" 
                 data-shipping="courier"
                 id="pay-delivery">
                <h4>Ramburs Courier</h4>
                <p>Plătești la livrare prin curier.</p>
            </div>

            <!-- nou: plata la ridicare (vizibila doar pentru pickup) -->
            <div class="payment-option" 
                 data-pay="pickup_card" 
                 data-shipping="pickup"
                 id="pay-pickup-card">
                <h4>Card bancar la punctul de livrare</h4>
                <p>Poti plati doar cu cardul in momentul ridicarii produselor.</p>
                <p>10,00 Lei reprezintă costul pentru procesarea plății la livrare. Plata online cu cardul este gratuită.</p>
            </div>

            <input type="hidden" name="payment_method" id="payment_method" value="<?= htmlspecialchars($selected_payment) ?>">
            <input type="hidden" name="save_card" id="save_card" value="0">
            <div id="payment-info" aria-live="polite"></div>
        </div>


        <!-- Modal Detalii Salveaza Card -->
        <div id="save-card-modal" class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="save-card-title" style="display:none;">
          <div class="dialog" role="document">
            <button class="close-x" id="save-card-close" aria-label="Inchide">&times;</button>
            <h3 id="save-card-title">Adaugă cardul la contul tău Shergei Covoare</h3>

            <div class="features">
              <div class="feature">
                <strong>Tranzacții securizate</strong>
                <p>Tranzacțiile sunt securizate prin intermediul unui cod numeric criptat asociat cardului tău de debit/credit.</p>
              </div>

              <div class="feature">
                <strong>Simplu și rapid</strong>
                <p>Salvează cardul și beneficiază de cea mai rapidă experiență de plată, printr-un singur click.</p>
              </div>

              <div class="feature">
                <strong>În maximă siguranță</strong>
                <p>Shergei Covoare nu stochează datele cardului. Prioritatea noastră este siguranța tranzacțiilor, folosim sistemul 3D Secure.</p>
              </div>
            </div>

            <div class="actions" style="margin-top:14px; display:flex; gap:12px; justify-content:space-between;">
              <button type="button" id="save-card-yes" class="btn btn-primary">Salveaza Cardul</button>
              <button type="button" id="save-card-no" class="btn btn-secondary">Nu multumesc!</button>
            </div>

            <p style="margin-top:12px; color:#666; font-size:13px;">
              Prin salvarea cardului accepti termenii si conditiile pentru utilizarea acestui serviciu.
            </p>
          </div>
        </div>

        <!-- Sumare comanda -->
        <div class="summary">
            <p>Cost produse: <span id="order-subtotal-display"><?= number_format($totalProducts, 2) ?></span> Lei</p>
            <p>Cost livrare: <span id="shipping-cost-display"><?= number_format($shippingCost, 2) ?></span> Lei</p>

            <!-- rând pentru cost procesare plată (inițial ascuns) -->
            <p id="payment-fee-row" style="display:none">Cost procesare plată: <span id="payment-fee-display">0.00</span> Lei</p>

            <strong>Total de plata: <span id="order-total"><?= number_format($totalProducts + $shippingCost, 2) ?></span> Lei</strong>
        </div>

        <!-- hidden pentru JS -->
        <input type="hidden" id="order-subtotal-hidden" value="<?= $totalProducts ?>">
        <input type="hidden" id="shipping-cost-hidden" value="<?= number_format($shippingCost, 2) ?>">

    <!-- Buton FINALIZEAZA: formular separat, va trimite la FinalizeazaComanda.php -->
    <div style="margin-top:12px;">
        <form id="finalizeForm" method="post" action="FinalizeazaComanda.php" style="display:inline;">
            <input type="hidden" name="shipping_method" id="final_shipping_method" value="<?= htmlspecialchars($addresses['shipping']['type'] ?? 'courier') ?>">
			<input type="hidden" name="shipping_address_id" id="final_shipping_address_id" value="<?= (int)($firstAddress['id'] ?? 0) ?>">
            <input type="hidden" name="payment_method" id="final_payment_method" value="<?= htmlspecialchars($selected_payment) ?>">
            <input type="hidden" name="shipping_cost" id="final_shipping_cost" value="<?= number_format($shippingCost, 2) ?>">
            <input type="hidden" name="order_subtotal" id="final_order_subtotal" value="<?= $totalProducts ?>">
            <button type="submit" id="finalize-order-btn">Finalizează comanda</button>
        </form>
    </div>

<!-- Modal adrese existente -->
<div id="address-modal" class="address-modal">
    <div class="modal-content" id="address-modal_content">
        <span id="close-modal" class="close-modal">&times;</span>
        <h3>Alege adresa de livrare</h3>
        <p>Selecteaza o adresa din lista de mai jos sau adauga una noua.</p>

        <div class="address-list">
        <?php foreach($addresses as $addr): ?>
            <?php $fullAddress = trim($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']); ?>
            <div class="address-item selectable"
                 data-id="<?= (int)$addr['id'] ?>"
                 data-name="<?= htmlspecialchars($addr['name'], ENT_QUOTES) ?>"
                 data-phone="<?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?>"
                 data-address="<?= htmlspecialchars($fullAddress, ENT_QUOTES) ?>">
                <span class="radio-dot"></span>
                <div class="address-text">
                    <strong><?= htmlspecialchars($addr['name'], ENT_QUOTES) ?> - <?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?></strong>
                    <p><?= htmlspecialchars($fullAddress, ENT_QUOTES) ?></p>
                </div>
            </div>
        <?php endforeach; ?>

            <button id="add-new-address" class="add-new-address">+ Adaugă Adresă Nouă</button>
        </div>
    </div>
</div>

    <!-- Buton deschidere modal -->


    <!-- Modal Adaugă adresa nouă -->
    <div id="new-address-modal" class="address-modal" style="display:none;">
        <div class="modal-content">
            <span id="close-new-address" class="close-x">&times;</span>

            <form id="addressForm">
                <input type="hidden" name="type" value="courier">

                <label>Persoana de contact</label>
                <input type="text" name="contact_name" required>

                <label>Numar de telefon</label>
                <input type="text" name="contact_phone" required>

                <label>Judet</label>
                <input type="text" name="county" required>

                <label>Localitate</label>
                <input type="text" name="city" required>

                <label>Adresa</label>
                <input type="text" name="address_line" required>

                <button type="submit">Adaugă adresa nouă</button>
            </form>
        </div>
    </div>
<script>
// Script curatat, fara EasyBox si pickup

// Stocare ultima adresă selectată courier
let lastSelectedCourierAddress = null;
let billingOverridden = false; // daca true, nu suprascriem billing automat

// Actualizeaza detaliile de livrare pentru curier
function updateShippingDetails() {
    const detailsDiv = document.getElementById('shipping-details');
    if (!detailsDiv) return;

    if (lastSelectedCourierAddress) {
        detailsDiv.innerHTML = `
            <p>Livrare prin curier la:</p>
            <strong>${escapeHtml(lastSelectedCourierAddress.name)} - ${escapeHtml(lastSelectedCourierAddress.phone)}</strong>
            <p>${escapeHtml(lastSelectedCourierAddress.address)}</p>
            <p>Cost livrare: ${parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00').toFixed(2)} Lei</p>
            <button type="button" class="change-address-btn" onclick="document.getElementById('address-modal').style.display='flex'">Alege alta adresa</button>
        `;
    } else {
        const tpl = document.getElementById('courier-template');
        if (tpl) detailsDiv.innerHTML = tpl.innerHTML;
    }

    // daca billing nu a fost modificat manual, sincronizam
    if (!billingOverridden && lastSelectedCourierAddress) {
        updateBillingDetails(lastSelectedCourierAddress.name, lastSelectedCourierAddress.phone, lastSelectedCourierAddress.address);
    }
}

// actualizeaza vizual datele de facturare
function updateBillingDetails(name, phone, address) {
    const nameEl = document.getElementById('billing-name');
    const phoneEl = document.getElementById('billing-phone');
    const addrEl = document.getElementById('billing-address');

    if (nameEl) nameEl.textContent = name + ' - ';
    if (phoneEl) phoneEl.textContent = phone;
    if (addrEl) addrEl.textContent = address;
}
// Seteaza detalii livrare (folosit la selectarea unei adrese)
function setShippingDetails(name, phone, address, cost = 25.00) {
    lastSelectedCourierAddress = { name, phone, address };
    const shippingInfo = document.getElementById('shipping-details');
    if (shippingInfo) {
        shippingInfo.innerHTML = `
            <p>Livrare la:</p>
            <strong>${escapeHtml(name)}${phone ? ' - ' + escapeHtml(phone) : ''}</strong>
            <p>${escapeHtml(address)}</p>
            <p>Cost livrare: ${parseFloat(cost).toFixed(2)} Lei</p>
        `;
    }

    // actualizeaza costul in hidden si totalul
    const shippingHidden = document.getElementById('shipping-cost-hidden');
    if (shippingHidden) shippingHidden.value = parseFloat(cost).toFixed(2);

    // daca billing nu a fost modificat manual, sincronizam
    if (!billingOverridden) {
        updateBillingDetails(name, phone, address);
    }

    updateOrderTotal();
}

// helper escape html
function escapeHtml(s){
    return String(s || '').replace(/[&<>"'`=\/]/g, function(ch){
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#47;',
            '`': '&#96;',
            '=': '&#61;'
        }[ch];
    });
}

// =========================
// Persistenta selectie adresa + marcaj in toate copiile
// =========================

// salveaza id-ul selectat in hidden-uri si in localStorage
function syncHiddenAddressId(id) {
    const a = String(id || '0');
    const elMain = document.getElementById('shipping_address_id');
    const elFinal = document.getElementById('final_shipping_address_id');
    if (elMain) elMain.value = a;
    if (elFinal) elFinal.value = a;
    try { localStorage.setItem('selected_address_id', a); } catch (e) { /* ignore */ }
}

// returneaza toate elementele address-item pentru un id
function getAddressElementsById(id) {
    if (!id) return [];
    return Array.from(document.querySelectorAll('.address-item.selectable[data-id="' + id + '"]'));
}

// marcheaza toate elementele cu acelasi id si dezactiveaza restul
function markActiveById(id) {
    const all = Array.from(document.querySelectorAll('.address-item.selectable'));
    all.forEach(i => i.classList.remove('active'));
    if (!id) return null;
    const matches = getAddressElementsById(id);
    if (matches.length) {
        matches.forEach(m => m.classList.add('active'));
        return matches[0];
    }
    return null;
}

// inchide modalele de adrese
function closeAddressModals() {
    ['address-modal', 'billing-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    });
}

// =========================
// Modal adrese si selectie courier (actualizat)
// =========================
function attachAddressListeners() {
    document.querySelectorAll('.address-item.selectable').forEach(item => {
        if (item._listener) return;

        const listener = () => {
            const id = (item.dataset.id || '').toString();
            const name = item.dataset.name || '';
            const phone = item.dataset.phone || '';
            const address = item.dataset.address || '';

            // marcheaza toate copiile aceleiasi adrese
            const firstMatch = markActiveById(id) || (function(){
                // fallback: daca nu exista id, cautam dupa name+phone+address si marcam toate copiile
                const found = Array.from(document.querySelectorAll('.address-item.selectable'))
                    .filter(it => (it.dataset.name||'') === name && (it.dataset.phone||'') === phone && (it.dataset.address||'') === address);
                if (found.length) {
                    found.forEach(f => f.classList.add('active'));
                    return found[0];
                }
                return null;
            })();

            // sincronizam hidden + localStorage
            syncHiddenAddressId(id);

            // pastram referinta in JS
            lastSelectedCourierAddress = { id: id, name: name, phone: phone, address: address };

            const parentModal = item.closest('.modal-content')?.parentElement;
            if (parentModal?.id === 'billing-modal') {
                billingOverridden = true;
                if (typeof updateBillingDetails === 'function') updateBillingDetails(name, phone, address);
            } else {
                if (typeof setShippingDetails === 'function') {
                    setShippingDetails(
                        name,
                        phone,
                        address,
                        parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00')
                    );
                }
            }

            closeAddressModals();

            if (typeof updateOrderTotal === 'function') updateOrderTotal();
            if (typeof updateVisualTotal === 'function') updateVisualTotal(document.getElementById('payment_method')?.value || 'online');
        };

        item.addEventListener('click', listener);
        item._listener = listener;
    });
}
attachAddressListeners();

// =========================
// initializare la incarcare - restaureaza selectie din hidden sau localStorage
// =========================
window.addEventListener('DOMContentLoaded', () => {
    // prioritate: hidden shipping_address_id, apoi localStorage, apoi prima adresa din lista
    const hiddenEl = document.getElementById('shipping_address_id');
    const hiddenVal = hiddenEl ? (hiddenEl.value || '') : '';
    let savedLS = '';
    try { savedLS = localStorage.getItem('selected_address_id') || ''; } catch (e) { savedLS = ''; }
    const chosen = hiddenVal && hiddenVal !== '0' ? hiddenVal : (savedLS && savedLS !== '0' ? savedLS : null);

    let activeItem = null;
    if (chosen) {
        activeItem = document.querySelector('.address-item.selectable[data-id="' + chosen + '"]');
    }
    if (!activeItem) activeItem = document.querySelector('.address-item.selectable');

    if (activeItem) {
        const id = activeItem.dataset.id || '';
        // marcheaza toate copiile imediat
        const first = markActiveById(id) || activeItem;

        const name = first.dataset.name || '';
        const phone = first.dataset.phone || '';
        const address = first.dataset.address || '';

        lastSelectedCourierAddress = { id: id, name: name, phone: phone, address: address };

        // sincronizam hidden si localStorage
        syncHiddenAddressId(id);

        // populam UI si billing daca nu e suprascris
        if (typeof setShippingDetails === 'function') {
            setShippingDetails(name, phone, address, parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00'));
        }
        if (!billingOverridden && typeof updateBillingDetails === 'function') {
            updateBillingDetails(name, phone, address);
        }

        if (typeof updateOrderTotal === 'function') updateOrderTotal();
        if (typeof updateVisualTotal === 'function') updateVisualTotal(document.getElementById('payment_method')?.value || 'online');
    }

    // reasiguram listener-ele pentru elemente noi
    attachAddressListeners();
});

// =========================
// Buton „Alege altă adresă” - deschide modal si marcheaza item curent
// =========================
document.getElementById('change-address-btn')?.addEventListener('click', () => {
    const curId = document.getElementById('shipping_address_id')?.value || '';
    if (curId) {
        markActiveById(curId);
    }
    const modal = document.getElementById('address-modal');
    if (modal) modal.style.display = 'flex';
});

// =========================
// Edit billing - deschide si marcheaza dupa id sau dupa date vizibile
// =========================
document.getElementById('edit-billing-btn')?.addEventListener('click', () => {
    const curId = document.getElementById('shipping_address_id')?.value || '';
    if (curId) {
        if (markActiveById(curId)) {
            const m = document.getElementById('billing-modal');
            if (m) m.style.display = 'flex';
            return;
        }
    }

    const billingName = (document.getElementById('billing-name')?.textContent || '').split(' - ')[0]?.trim() || '';
    const billingPhone = document.getElementById('billing-phone')?.textContent?.trim() || '';
    const found = Array.from(document.querySelectorAll('#billing-modal .address-item.selectable'))
        .find(it => (it.dataset.name || '') === billingName && (it.dataset.phone || '') === billingPhone);
    if (found) found.classList.add('active');
    const modal = document.getElementById('billing-modal');
    if (modal) modal.style.display = 'flex';
});
// =========================
// Selectare metoda livrare (card-uri) - pastreaza doar courier si all
// =========================
// -----------------------------
// Selectare metoda livrare (card-uri) - corect, suporta pickup
// -----------------------------
(function setupShippingCardOptions(){
    const cardOptions = Array.from(document.querySelectorAll('.card-option'));
    const paymentOptions = Array.from(document.querySelectorAll('.payment-option'));
    const shipInput = document.getElementById('shipping_method');
    const shippingDetails = document.getElementById('shipping-details');
    const courierTpl = document.getElementById('courier-template');
    const pickupTpl = document.getElementById('pickup-template');
    const shippingCostHidden = document.getElementById('shipping-cost-hidden');

    const pickupAvailable = !!pickupTpl; // daca exista template pickup in DOM

    // init: daca pickup nu exista, ascundem doar card-urile/payment legate de pickup
    cardOptions.forEach(c => {
        if (c.dataset.shipping === 'pickup' && !pickupAvailable) {
            c.style.display = 'none';
        } else {
            c.style.display = '';
        }
    });

    // functie utilitara pentru a afisa detaliile potrivite
    function renderShippingDetailsFor(method) {
        if (!shippingDetails) return;
        if (method === 'pickup' && pickupTpl) {
            shippingDetails.innerHTML = pickupTpl.innerHTML;
            // setam shipping cost 0 pentru pickup
            if (shippingCostHidden) shippingCostHidden.value = (0).toFixed(2);
        } else {
            // folosește courier template daca exista, altfel incearca sa foloseasca valorile existente
            if (courierTpl) {
                shippingDetails.innerHTML = courierTpl.innerHTML;
            } else {
                // fallback textual
                shippingDetails.innerHTML = '<p>Livrare prin curier</p>';
            }
            if (shippingCostHidden) shippingCostHidden.value = (25.00).toFixed(2);
        }
    }

    // functie care actualizeaza vizibilitatea optiunilor de plata in functie de metoda curenta
    function refreshPaymentOptionsFor(method) {
        paymentOptions.forEach(pay => {
            const shippingAttr = pay.dataset.shipping; // all, courier, pickup, undefined
            if (!shippingAttr || shippingAttr === 'all' || shippingAttr === method) {
                pay.style.display = '';
            } else {
                pay.style.display = 'none';
            }
        });

        // gasim prima optiune vizibila si o setam activa
        const firstVisible = paymentOptions.find(p => getComputedStyle(p).display !== 'none');
        if (firstVisible) {
            document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('active'));
            firstVisible.classList.add('active');
            const pm = document.getElementById('payment_method');
            if (pm) pm.value = firstVisible.dataset.pay;
            // actualizeaza info si total
            if (typeof renderInfo === 'function') renderInfo(pm.value);
            if (typeof updateVisualTotal === 'function') updateVisualTotal(pm.value);
        }
    }

    // click handler pentru fiecare card-option
    cardOptions.forEach(card => {
        if (card._bound) return;
        card._bound = true;
        card.addEventListener('click', () => {
            // nu permite click daca card este ascuns/disabled
            if (getComputedStyle(card).display === 'none' || card.classList.contains('disabled')) return;

            // activam vizual
            cardOptions.forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            // shipping real: luam exact data-shipping (nu facem remap)
            const shipping = card.dataset.shipping || 'courier';
            if (shipInput) shipInput.value = shipping;

            // render shipping details (pickup/courier)
            renderShippingDetailsFor(shipping);

            // actualizam costuri si optiuni plata
            refreshPaymentOptionsFor(shipping);

            // actualizeaza totals
            if (typeof updateOrderTotal === 'function') updateOrderTotal();
            if (typeof updateVisualTotal === 'function') updateVisualTotal(document.getElementById('payment_method')?.value || 'online');
        });
    });

    // initializare: daca exista un shipping setat server-side, simulam click pe card corespunzator
    const initialShipping = (document.getElementById('shipping_method')?.value) || 'courier';
    // gasim card-ul corespunzator
    const initialCard = cardOptions.find(c => (c.dataset.shipping || 'courier') === initialShipping) || cardOptions.find(c => (c.dataset.shipping || 'courier') === 'courier');
    if (initialCard) {
        initialCard.classList.add('active');
        renderShippingDetailsFor(initialShipping);
        refreshPaymentOptionsFor(initialShipping);
    } else {
        // fallback: render courier
        renderShippingDetailsFor('courier');
        refreshPaymentOptionsFor('courier');
    }

})();

// =========================
// Payment handlers (online, delivery)
// =========================
(function(){
    const options = document.querySelectorAll('.payment-option');
    const input = document.getElementById('payment_method');
    const info = document.getElementById('payment-info');
    const summary = document.querySelector('.summary');
    const deliveryCostElem = document.getElementById('shipping-cost-display');
    const totalPayElem = document.getElementById('order-total');
    const paymentFeeRow = document.getElementById('payment-fee-row');
    const paymentFeeDisplay = document.getElementById('payment-fee-display');
    const finalizePaymentInput = document.getElementById('final_payment_method');
    const finalizeShippingInput = document.getElementById('final_shipping_method');
    const finalizeShippingCostInput = document.getElementById('final_shipping_cost');
    const finalizeOrderSubtotalInput = document.getElementById('final_order_subtotal');

    // fallback: asigura existencea hidden shipping_method daca cineva l-a sters din HTML
    (function ensureShippingHidden(){
        if (!document.getElementById('shipping_method')) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'shipping_method';
            inp.id = 'shipping_method';
            inp.value = 'courier';
            document.getElementById('checkoutForm')?.appendChild(inp);
        }
    })();

    function renderInfo(key){
        if(!info) return;
        if(key === 'online'){
            info.innerHTML = `
                <strong>Card online</strong>
                <p>Tranzactii securizate. Simplu si rapid. In maxima siguranta.</p>
                <p>Salveaza cardul in cont pentru un checkout mai rapid data viitoare.</p>
            `;
            if(paymentFeeRow) paymentFeeRow.style.display = 'none';
        } else if(key === 'delivery'){
            info.innerHTML = `
                <strong>Ramburs Courier</strong>
                <p>Platesti cand ajunge courierul.</p>
            `;
            if(paymentFeeRow) paymentFeeRow.style.display = 'block';
        } else {
            info.innerHTML = '';
            if(paymentFeeRow) paymentFeeRow.style.display = 'none';
        }
    }

    // citeste subtotalul din DOM (prioritizeaza hidden, fallback la display)
    function getSubtotalFromDom(){
        const hidden = document.getElementById('order-subtotal-hidden');
        if(hidden && hidden.value) return parseFloat(hidden.value) || 0;
        const disp = document.getElementById('order-subtotal-display');
        if(disp) {
            // curata textul si transforma virgula in punct daca exista
            const raw = String(disp.textContent || '').replace(/[^\d\.,-]/g, '').replace(',', '.');
            return parseFloat(raw) || 0;
        }
        return 0;
    }

    // calculeaza si actualizeaza totalul in UI (ia in calcul shipping + fee plata)
    function updateVisualTotal(paymentKey){
        const totalProduse = getSubtotalFromDom();

        const shippingMethod = document.getElementById('shipping_method')?.value || 'courier';

        let shippingCost = 25.00;
        if(shippingMethod === 'pickup'){
            shippingCost = 0.00;
        }

        let paymentFee = 0.00;

        // ramburs courier
        if(paymentKey === 'delivery' && shippingMethod === 'courier'){
            paymentFee = 10.00;
        }

        // card la punctul de livrare (pickup)
        if(paymentKey === 'pickup_card' && shippingMethod === 'pickup'){
            paymentFee = 10.00;
        }

        const grand = (totalProduse + shippingCost + paymentFee).toFixed(2);

        if(deliveryCostElem) deliveryCostElem.textContent = shippingCost.toFixed(2);
        if(paymentFeeDisplay) paymentFeeDisplay.textContent = paymentFee.toFixed(2);
        if(totalPayElem) totalPayElem.textContent = grand + ' Lei';

        if(paymentFeeRow){
            paymentFeeRow.style.display = paymentFee > 0 ? 'block' : 'none';
        }

        if(finalizePaymentInput) finalizePaymentInput.value = paymentKey;
        if(finalizeShippingInput) finalizeShippingInput.value = shippingMethod;
        if(finalizeShippingCostInput) finalizeShippingCostInput.value = shippingCost.toFixed(2);
        if(finalizeOrderSubtotalInput) finalizeOrderSubtotalInput.value = totalProduse.toFixed(2);
    }

    // =========================
    // Total comanda si cantitati
    function updateOrderTotal() {
        let subtotal = 0;
        document.querySelectorAll('tr.cart-item').forEach(row => {
            const price = parseFloat(row.dataset.price) || 0;

            const qtyInput = row.querySelector('input.qty-input');
            let qty = 1;
            if(qtyInput) qty = parseInt(qtyInput.value) || 1;
            else {
                const qtyCell = row.querySelector('td:nth-child(4)');
                qty = qtyCell ? parseInt(qtyCell.textContent) || 1 : 1;
            }

            const rowSubtotal = price * qty;
            const rowSubEl = row.querySelector('.row-subtotal');
            if(rowSubEl) rowSubEl.textContent = rowSubtotal.toFixed(2) + ' Lei';
            subtotal += rowSubtotal;
        });

        const shipping = parseFloat(document.getElementById('shipping-cost-hidden')?.value) || 0;

        // salvez subtotalul in hidden pentru referinta in updateVisualTotal
        const orderSubtotalHidden = document.getElementById('order-subtotal-hidden');
        if(orderSubtotalHidden) orderSubtotalHidden.value = subtotal.toFixed(2);

        // actualizez afisarea subtotal produse
        const orderSubtotalDisplay = document.getElementById('order-subtotal-display');
        if(orderSubtotalDisplay) orderSubtotalDisplay.textContent = subtotal.toFixed(2);

        // calculez taxa in functie de metoda de plata selectata
        const paymentKey = document.getElementById('payment_method')?.value || 'online';
        const paymentFee = (paymentKey === 'delivery' || paymentKey === 'pickup_card') ? 10.00 : 0.00;

        // actualizez rândul cu shipping si fee
        const shippingDisplay = document.getElementById('shipping-cost-display');
        if(shippingDisplay) shippingDisplay.textContent = shipping.toFixed(2) + ' Lei';

        if(paymentFeeDisplay) paymentFeeDisplay.textContent = paymentFee.toFixed(2);
        if(paymentFeeRow) {
            paymentFeeRow.style.display = paymentFee > 0 ? 'block' : 'none';
        }

        // total final include si taxa de procesare
        const totalEl = document.getElementById('order-total');
        if(totalEl) totalEl.textContent = (subtotal + shipping + paymentFee).toFixed(2) + ' Lei';

        // sincronizez final form fields
        if(finalizeOrderSubtotalInput) finalizeOrderSubtotalInput.value = subtotal.toFixed(2);
        if(finalizeShippingCostInput) finalizeShippingCostInput.value = shipping.toFixed(2);
    }

    // ataseaza click pe optiunile de plata (daca nu e facut in alta parte)
    options.forEach(opt => {
        if(opt._bound) return;
        opt._bound = true;
        opt.addEventListener('click', () => {
            document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('active'));
            opt.classList.add('active');
            const pm = document.getElementById('payment_method');
            if(pm) pm.value = opt.dataset.pay;
            renderInfo(pm?.value || 'online');
            updateVisualTotal(pm?.value || 'online');
        });
    });

    // initializare vizuala
    const initialPayment = document.getElementById('payment_method')?.value || 'online';
    renderInfo(initialPayment);
    updateOrderTotal();
    updateVisualTotal(initialPayment);

})(); // end IIFE

// =========================
// Fix modal "Adaugă adresă nouă" și integrare în lista de adrese
// Pune acest bloc la sfârșitul scriptului
// =========================
// Fix modal "Adaugă adresă nouă" și integrare în lista de adrese
(function(){
    // aliasuri sigure (nu suprascriem $ daca exista jQuery)
    const qs = sel => document.querySelector(sel);
    const qsa = sel => Array.from(document.querySelectorAll(sel));

    const addNewAddrBtn = qs('#add-new-address'); // buton din address-modal
    const newAddrModal = qs('#new-address-modal');
    const closeNewAddr = qs('#close-new-address');
    const addressForm = qs('#addressForm');
    const addressListInModal = qs('#address-modal .address-list');
    const addressModal = qs('#address-modal');
    const billingModal = qs('#billing-modal');

    // helper escape
    function escapeHtml(s){
        return String(s).replace(/[&<>"'`=\/]/g, function(ch){
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#47;',
                '`': '&#96;',
                '=': '&#61;'
            }[ch];
        });
    }

    // --- util: atasam un click handler in siguranta (no-dup) ---
    function safeAddListener(el, type, fn){
        if(!el) return;
        el.__bound = el.__bound || {};
        if(el.__bound[type]) return;
        el.addEventListener(type, fn);
        el.__bound[type] = true;
    }

    // deschide modalul de adaugare adresa
    if (addNewAddrBtn) {
        safeAddListener(addNewAddrBtn, 'click', (e) => {
            e.preventDefault();
            if(addressModal) addressModal.style.display = 'none';
            if(newAddrModal) newAddrModal.style.display = 'flex';
            const first = newAddrModal?.querySelector('input');
            if(first) first.focus();
        });
    }

    // inchide modalul de adaugare adresa
    if (closeNewAddr) {
        safeAddListener(closeNewAddr, 'click', () => {
            if (newAddrModal) newAddrModal.style.display = 'none';
        });
    }

    // click outside new-address modal -> close
    if (newAddrModal) {
        safeAddListener(newAddrModal, 'click', (ev) => {
            if (ev.target === newAddrModal) newAddrModal.style.display = 'none';
        });
    }

    // click outside address-modal -> close
    if (addressModal) {
        safeAddListener(addressModal, 'click', (ev) => {
            if (ev.target === addressModal) addressModal.style.display = 'none';
        });
    }

    // click outside billing-modal -> close
    if (billingModal) {
        safeAddListener(billingModal, 'click', (ev) => {
            if (ev.target === billingModal) billingModal.style.display = 'none';
        });
    }

    // close buttons for generic modals (#close-modal, #close-billing-modal etc.)
    const closeModalBtn = qs('#close-modal');
    if (closeModalBtn) safeAddListener(closeModalBtn, 'click', () => {
        if(addressModal) addressModal.style.display = 'none';
    });
    const closeBillingModalBtn = qs('#close-billing-modal');
    if (closeBillingModalBtn) safeAddListener(closeBillingModalBtn, 'click', () => {
        if(billingModal) billingModal.style.display = 'none';
    });

    // buton "Alege alta adresa" din template poate fi inline onclick (fallback),
    // dar atasam si un handler generic pentru toate .change-address-btn
    qsa('.change-address-btn').forEach(btn => {
        safeAddListener(btn, 'click', (e) => {
            e.preventDefault();
            if(addressModal) addressModal.style.display = 'flex';
        });
    });

    // buton "Alege alt punct" (pickup)
    qsa('.change-pickup-btn, #choose-easybox-link').forEach(btn => {
        safeAddListener(btn, 'click', (e) => {
            // lasam comportamentul default (link) dar si deschidem pagina intr-un tab nou daca e cazul
            // daca vrei sa deschizi modal intern, aici poti implementa
        });
    });

    // buton edit billing
    const editBillingBtn = qs('#edit-billing-btn');
    if(editBillingBtn) safeAddListener(editBillingBtn, 'click', () => {
        if(billingModal) billingModal.style.display = 'flex';
    });

    // ----------------------------
    // Selectare/adaugare adrese (atat pentru elementele existente cat si pentru cele noi)
    // ----------------------------
    function handleAddressItemClick(item, isFromBillingModal){
        return function(){
            // dezactiveaza toate si activeaza acesta
            qsa('.address-item.selectable').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            const name = item.dataset.name;
            const phone = item.dataset.phone;
            const address = item.dataset.address;

            if (isFromBillingModal) {
                // daca a venit din modalul de billing, marcam billingOverridden in locul potrivit
                if (typeof window !== 'undefined') window.billingOverridden = true;
                if (typeof updateBillingDetails === 'function') updateBillingDetails(name, phone, address);
            } else {
                // selectie normala pentru livrare
                try {
                    setShippingDetails(name, phone, address, parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00'));
                } catch(e){
                    console.error('setShippingDetails nu exista', e);
                }
            }

            // inchidem modalul corespunzator
            if (isFromBillingModal) {
                if (billingModal) billingModal.style.display = 'none';
            } else {
                if (addressModal) addressModal.style.display = 'none';
            }

            // actualizam total/visual
            if (typeof updateOrderTotal === 'function') updateOrderTotal();
            if (typeof updateVisualTotal === 'function') updateVisualTotal(document.getElementById('payment_method')?.value || 'online');
        };
    }

    // atasam handler pentru elementele existente .address-item.selectable
    qsa('.address-item.selectable').forEach(item => {
        if(item.__addrBound) return;
        item.__addrBound = true;
        // daca elementul este in billing-modal, semnalam
        const parentModal = item.closest('.modal-content')?.parentElement;
        const isBilling = parentModal && parentModal.id === 'billing-modal';
        item.addEventListener('click', handleAddressItemClick(item, isBilling));
    });

    // functie helper pentru append (pastreaza compatibilitate)
    function appendAddressToModal(name, phone, addressText){
        if(!addressListInModal) return;

        const normalized = s => String(s || '').trim().toLowerCase();

        const existing = Array.from(addressListInModal.querySelectorAll('.address-item.selectable'))
            .find(item =>
                normalized(item.dataset.name) === normalized(name) &&
                normalized(item.dataset.phone) === normalized(phone) &&
                normalized(item.dataset.address) === normalized(addressText)
            );

        if (existing) {
            qsa('.address-item.selectable').forEach(i => i.classList.remove('active'));
            existing.classList.add('active');
            try {
                setShippingDetails(existing.dataset.name, existing.dataset.phone, existing.dataset.address, 25.00);
            } catch(e){}
            if(addressModal) addressModal.style.display = 'none';
            return;
        }

        const div = document.createElement('div');
        div.className = 'address-item selectable';
        div.dataset.name = name;
        div.dataset.phone = phone;
        div.dataset.address = addressText;
        div.innerHTML = `
            <span class="radio-dot"></span>
            <div class="address-text">
                <strong>${escapeHtml(name)} - ${escapeHtml(phone)}</strong>
                <p>${escapeHtml(addressText)}</p>
            </div>
        `;

        addressListInModal.insertBefore(div, addressListInModal.firstChild);

        qsa('.address-item.selectable').forEach(i => i.classList.remove('active'));
        div.classList.add('active');

        try { setShippingDetails(name, phone, addressText, 25.00); } catch(e){}

        // attach click handler
        div.addEventListener('click', handleAddressItemClick(div, false));

        if(addressModal) addressModal.style.display = 'none';
        if(newAddrModal) newAddrModal.style.display = 'none';
    }

    // daca exista formular adresa, trimitem folosind appendAddressToModal (pastrat din codul tau)
    if (addressForm && !addressForm.dataset.bound) {
        addressForm.dataset.bound = '1';
        addressForm.addEventListener('submit', async function(e){
            e.preventDefault();

            const name = (this.querySelector('input[name="contact_name"]')?.value || '').trim();
            const phone = (this.querySelector('input[name="contact_phone"]')?.value || '').trim();
            const county = (this.querySelector('input[name="county"]')?.value || '').trim();
            const city = (this.querySelector('input[name="city"]')?.value || '').trim();
            const address_line = (this.querySelector('input[name="address_line"]')?.value || '').trim();

            if(!name || !phone || !county || !city || !address_line){
                alert('Completeaza toate campurile!');
                return;
            }

            const formData = new FormData(this);

            try {
                const resp = await fetch('add_address_ajax.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await resp.json().catch(()=>({ success: false }));

                if (data && data.success && data.address) {
                    const a = data.address;
                    appendAddressToModal(a.name, a.phone, `${a.county}, ${a.city}, ${a.address_line}`);
                } else if (data && data.success === false) {
                    alert(data.error || 'Eroare la server');
                } else {
                    appendAddressToModal(name, phone, `${county}, ${city}, ${address_line}`);
                }

            } catch (err) {
                console.error(err);
                appendAddressToModal(name, phone, `${county}, ${city}, ${address_line}`);
            } finally {
                if(newAddrModal) newAddrModal.style.display = 'none';
                this.reset();
            }
        });
    }

    // expose append function global daca alte scripturi folosesc apel direct
    window.appendAddressToModal = appendAddressToModal;

})();
</script>

</body>
</html>