<?php
session_start();

/* =========================
   CONEXIUNE BAZA DE DATE
========================= */
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Conexiune esuata: " . $e->getMessage());
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

if (empty($cart)) {
    $_SESSION['cart_empty_message'] = "Nu ai ales niciun produs.";
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
    die("User inexistent");
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

/* --- variabile folosite in HTML / JS --- */
$firstAddress = $addresses[0] ?? null;
$shippingCost = 25.00; // cost implicit curier

/* =========================
   PROCESARE COMANDA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipping_method = trim(
        $_POST['shipping_method']
        ?? $_SESSION['selected_shipping_method']
        ?? 'courier'
    );

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

            $easybox_id = (int)($_POST['easybox_id'] ?? $_SESSION['selected_easybox_id'] ?? 0);
            if ($easybox_id <= 0) {
                throw new Exception("EasyBox nespecificat");
            }

            $stmt = $pdo->prepare("
                SELECT id, name, address, city, county, active
                FROM easyboxes
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$easybox_id]);
            $box = $stmt->fetch();

            if (!$box || (int)$box['active'] !== 1) {
                throw new Exception("EasyBox invalid sau inactiv");
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
            if ($firstAddress) {
                $shipping_name    = $firstAddress['name'];
                $shipping_phone   = $firstAddress['phone'];
                $shipping_address = "{$firstAddress['county']}, {$firstAddress['city']}, {$firstAddress['address_line']}";
            } else {
                $shipping_name    = $user['name'] ?? '';
                $shipping_phone   = $user['phone'] ?? '';
                $shipping_address = "Valcea, Bungetani, Sat Milesti";
            }
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
            $product_id = (int)$item['id'];
            $qty        = max(1, (int)$item['qty']);
            $price      = (float)$item['price'];

            $stmtItem->execute([$order_id, $product_id, $qty, $price]);

            $stmtStock->execute([$qty, $product_id, $qty]);
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Stoc insuficient pentru produsul $product_id");
            }
        }

        $pdo->commit();

        /* =========================
           RESET COS
        ========================== */
        unset(
            $_SESSION['cart'],
            $_SESSION['cart_total'],
            $_SESSION['checkout_cart'],
            $_SESSION['checkout_total']
        );

        /* =========================
           REDIRECT
        ========================== */
        if ($payment_method === 'online') {
            header("Location: payment_gateway.php?order_id=$order_id");
        } else {
            header("Location: ConfirmareComanda.php?order=$order_id");
        }
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("ORDER ERROR: " . $e->getMessage());
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



/* Card-urile de livrare */
.card-option {
    flex: 1; /* ocupa spațiu egal în container */
    border: 1px solid #ccc;
    padding: 20px;
    border-radius: 6px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
    background: #fff;
}

/* Card activ */
.card-option.active {
    border-color: #4caf50;
    background: #e8f5e9;
}

/* Hover */
.card-option:hover {
    border-color: #4caf50;
    background: #f1fdf4;
}

/* Container card-uri livrare */
.delivery-options {
    display: flex;
    justify-content: space-between; /* stânga-dreapta */
    gap: 20px; /* spațiu între card-uri */
    margin-top: 10px;
}

.card-option.disabled {
    pointer-events: none;
    opacity: 0.6; /* opțional, să arate vizual că e inactiv */
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
            <input type="hidden" name="shipping_method" id="shipping_method" value="courier">
            <!-- optional, deja existent -->
            <input type="hidden" name="pickup_location" id="pickup_location">


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
            </form>


            <!-- hidden pentru JS -->
            <input type="hidden" id="order-subtotal-hidden" value="<?= $totalProducts ?>">

        <!-- Shipping -->
        <div class="section">
            <h3>Metoda livrare</h3>
            <div class="delivery-options">
                <div class="card-option <?= ($addresses['shipping']['type'] ?? 'courier') === 'courier' ? 'active' : '' ?>" data-shipping="courier">
                    <h4>Livrare prin curier</h4>
                </div>
            </div>
            <input type="hidden" name="shipping_method" id="shipping_method" value="<?= htmlspecialchars($addresses['shipping']['type'] ?? 'courier') ?>">
        </div>

        <div id="shipping-details">
        </div>

        <!-- Templates pentru JS -->
        <div id="courier-template" style="display:none">
            <p>Livrare prin curier la:</p>
            <strong><?= htmlspecialchars($firstAddress['name'] ?? $user['name']) ?>, <?= htmlspecialchars($firstAddress['phone'] ?? $user['phone']) ?></strong>
            <p><?= htmlspecialchars(($firstAddress['county'] ?? '') . ($firstAddress['county'] ? ', ' : '') . ($firstAddress['city'] ?? '') . ($firstAddress['city'] ? ', ' : '') . ($firstAddress['address_line'] ?? '')) ?></p>
            <p>Cost livrare: <?= number_format($shippingCost, 2) ?> Lei</p>
            <button type="button" class="change-address-btn" onclick="document.getElementById('address-modal').style.display='flex'">Alege alta adresa</button>
        </div>
        <div id="pickup-template" style="display:none">
          <div class="pickup-summary">
            <p class="pickup-title">Ridicare personala din:</p>
            <p class="pickup-subtitle">Punct de ridicare selectat</p>
            <p class="pickup-address pickup-address-view">
              Nu exista puncte de ridicare disponibile
            </p>
            <p class="pickup-co2">95% mai putin CO2</p>
            <button type="button" id="change-pickup-btn" class="btn-use change-pickup-btn">
              Alege alt punct
            </button>

          </div>
        </div>



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
                    <div class="address-item selectable" 
                         data-name="<?= htmlspecialchars($addr['name']) ?>" 
                         data-phone="<?= htmlspecialchars($addr['phone']) ?>" 
                         data-address="<?= htmlspecialchars($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) ?>">
                        <span class="radio-dot"></span>
                        <div class="address-text">
                            <strong><?= htmlspecialchars($addr['name']) ?> - <?= htmlspecialchars($addr['phone']) ?></strong>
                            <p><?= htmlspecialchars($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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

            <input type="hidden" name="payment_method" id="payment_method" value="online">


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
                <div class="address-item selectable" 
                     data-name="<?= htmlspecialchars($addr['name']) ?>" 
                     data-phone="<?= htmlspecialchars($addr['phone']) ?>" 
                     data-address="<?= htmlspecialchars($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) ?>">
                    <span class="radio-dot"></span>
                    <div class="address-text">
                        <strong><?= htmlspecialchars($addr['name']) ?> - <?= htmlspecialchars($addr['phone']) ?></strong>
                        <p><?= htmlspecialchars($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) ?></p>
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
// Modal adrese si selectie courier
// =========================
function attachAddressListeners() {
    document.querySelectorAll('.address-item.selectable').forEach(item => {
        if (item._listener) return;
        const listener = () => {
            document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // extragem datele si setam shipping
            const name = item.dataset.name;
            const phone = item.dataset.phone;
            const address = item.dataset.address;

            // cand se selecteaza din modalul de adrese pentru livrare,
            // de obicei e folosit atat pentru livrare cat si pentru billing,
            // dar daca user selecteaza explicit din modalul de billing vom marca billingOverridden
            const parentModal = item.closest('.modal-content')?.parentElement;
            if (parentModal && parentModal.id === 'billing-modal') {
                billingOverridden = true; // utilizator a ales manual billing
                // actualizam doar billing
                updateBillingDetails(name, phone, address);
            } else {
                // selectie normala pentru livrare -> setam shipping si sincronizam billing (daca nu e overridden)
                setShippingDetails(name, phone, address, parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00'));
            }

            const addressModal = document.getElementById('address-modal');
            if (addressModal) addressModal.style.display = 'none';
            const billingModal = document.getElementById('billing-modal');
            if (billingModal) billingModal.style.display = 'none';
        };
        item.addEventListener('click', listener);
        item._listener = listener;
    });
}
attachAddressListeners();

// buton "Alege alta adresa" din sectiunea change-address-btn
const changeAddressBtn = document.getElementById('change-address-btn');
if (changeAddressBtn) {
    changeAddressBtn.addEventListener('click', () => {
        const firstAddress = document.querySelector('.address-item.selectable');
        if (firstAddress) {
            const name = firstAddress.dataset.name;
            const phone = firstAddress.dataset.phone;
            const address = firstAddress.dataset.address;
            setShippingDetails(name, phone, address, parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00'));
        }
        const modal = document.getElementById('address-modal');
        if (modal) modal.style.display = 'flex';
    });
}

// buton folosest adresa de livrare pentru billing
const useShippingForBillingBtn = document.getElementById('use-shipping-for-billing');
if (useShippingForBillingBtn) {
    useShippingForBillingBtn.addEventListener('click', () => {
        billingOverridden = false; // permitem suprascrierea
        if (lastSelectedCourierAddress) {
            updateBillingDetails(lastSelectedCourierAddress.name, lastSelectedCourierAddress.phone, lastSelectedCourierAddress.address);
        } else {
            // fallback: folosim primul element din lista
            const first = document.querySelector('.address-item.selectable');
            if (first) {
                setShippingDetails(first.dataset.name, first.dataset.phone, first.dataset.address);
            }
        }
    });
}
// =========================
// Selectare metoda livrare (card-uri) - pastreaza doar courier si all
// =========================
document.querySelectorAll('.card-option').forEach(card => {
    card.addEventListener('click', () => {
        if (card.classList.contains('disabled')) return;

        document.querySelectorAll('.card-option').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        const shipping = card.dataset.shipping || 'courier';
        const shipInput = document.getElementById('shipping_method');
        if (shipInput) shipInput.value = (shipping === 'pickup' ? 'courier' : shipping);

        // ascundem sectiunea pickup daca exista
        const pickupSection = document.getElementById('pickup-section');
        if (pickupSection) pickupSection.style.display = 'none';

        // afisam detalii curier
        updateShippingDetails();

        // actualizeaza metodele de plata vizibile
        document.querySelectorAll('.payment-option').forEach(pay => {
            const shippingAttr = pay.dataset.shipping; // all, courier
            if (!shippingAttr || shippingAttr === 'all' || shippingAttr === 'courier') {
                pay.style.display = 'block';
            } else {
                pay.style.display = 'none';
            }
        });

        // seteaza metoda de plata default vizibila
        const defaultPay = Array.from(document.querySelectorAll('.payment-option')).find(e => e.style.display === 'block' || getComputedStyle(e).display !== 'none');
        if (defaultPay) {
            document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('active'));
            defaultPay.classList.add('active');
            const pm = document.getElementById('payment_method');
            if (pm) pm.value = defaultPay.dataset.pay;
            // actualizeaza info plata
            renderInfo(pm.value);
            updateVisualTotal(pm.value);
        }
    });
});

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

    function renderInfo(key){
        if(!info) return;
        if(key === 'online'){
            info.innerHTML = `
                <strong>Card online</strong>
                <p>Tranzactii securizate. Simplu si rapid. In maxima siguranta.</p>
                <p>Salveaza cardul in cont pentru un checkout mai rapid data viitoare.</p>
            `;
            // ascundem rândul cu cost procesare
            if(paymentFeeRow) paymentFeeRow.style.display = 'none';
        } else if(key === 'delivery'){
            info.innerHTML = `
                <strong>Ramburs Courier</strong>
                <p>Platesti cand ajunge courierul.</p>
            `;
            // afișăm rândul cu cost procesare
            if(paymentFeeRow) paymentFeeRow.style.display = 'block';
        } else {
            info.innerHTML = '';
            if(paymentFeeRow) paymentFeeRow.style.display = 'none';
        }
    }
 // calculeaza si actualizeaza totalul in UI (ia in calcul shipping + fee plata)
    function updateVisualTotal(paymentKey){
        const totalProduse = Number(<?= json_encode((float)$total) ?>);
        const shippingCost = parseFloat(document.getElementById('shipping-cost-hidden')?.value || '25.00');
        const paymentFee = paymentKey === 'delivery' ? 10.00 : 0.00;
        const grand = (totalProduse + shippingCost + paymentFee).toFixed(2);

        // actualizam rândurile vizuale
        if(deliveryCostElem) deliveryCostElem.textContent = shippingCost.toFixed(2);
        if(paymentFeeDisplay) paymentFeeDisplay.textContent = paymentFee.toFixed(2);
        if(totalPayElem) totalPayElem.textContent = grand + ' Lei';
        // ascund/aratam randul (in caz in care renderInfo n-a fost apelat)
        if(paymentKey === 'delivery') {
            if(paymentFeeRow) paymentFeeRow.style.display = 'block';
        } else {
            if(paymentFeeRow) paymentFeeRow.style.display = 'none';
        }

        // actualizeaza finalize form hidden
        if(finalizePaymentInput) finalizePaymentInput.value = paymentKey;
    }

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            if(opt.classList.contains('disabled')) return;
            options.forEach(o => o.classList.remove('active'));
            opt.classList.add('active');
            const key = opt.dataset.pay;
            if(input) input.value = key;

            // actualizeaza UI info si total
            renderInfo(key);
            updateVisualTotal(key);

            // actualizeaza hidden field pentru formularul finalize
            const finalizePM = document.getElementById('final_payment_method');
            if(finalizePM) finalizePM.value = key;

            // daca ai si un buton finalize, poti de asemenea schimba atributul disabled
            const finalizeBtn = document.getElementById('finalize-order-btn');
            if(finalizeBtn) {
                // exemplu: nu facem nimic special aici, dar poti activa/dezactiva
                // finalizeBtn.disabled = false;
            }
        });
    });

    const initial = input ? input.value || 'online' : 'online';
    renderInfo(initial);
    updateVisualTotal(initial);

    const shippingInput = document.getElementById('shipping_method');
    if(shippingInput){
        shippingInput.addEventListener('change', () => {
            updateVisualTotal(document.getElementById('payment_method').value);
        });
    }
})();
// =========================
// Total comanda si cantitati
// =========================
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

    const shipping = parseFloat(document.getElementById('shipping-cost-hidden')?.value) || 25.00;

    const orderSubtotalDisplay = document.getElementById('order-subtotal-display');
    if(orderSubtotalDisplay) orderSubtotalDisplay.textContent = subtotal.toFixed(2);

    const orderSubtotalHidden = document.getElementById('order-subtotal-hidden');
    if(orderSubtotalHidden) orderSubtotalHidden.value = subtotal.toFixed(2);

    const totalEl = document.getElementById('order-total');
    if(totalEl) totalEl.textContent = (subtotal + shipping).toFixed(2) + ' Lei';

    const shippingDisplay = document.getElementById('shipping-cost-display');
    if(shippingDisplay) shippingDisplay.textContent = shipping.toFixed(2) + ' Lei';
}

// listeners qty (daca exista)
document.querySelectorAll('input.qty-input').forEach(input => {
    input.addEventListener('change', updateOrderTotal);
});

// initializare la incarcare
document.addEventListener('DOMContentLoaded', () => {
    // asigur valoare default shipping cost
    const shippingHidden = document.getElementById('shipping-cost-hidden');
    if (shippingHidden && !shippingHidden.value) shippingHidden.value = '25.00';

    // daca exista o adresa initiala din PHP, setam-o ca lastSelected si sincronizam billing
    <?php if ($firstAddress): ?>
    lastSelectedCourierAddress = {
        name: <?= json_encode($firstAddress['name']) ?>,
        phone: <?= json_encode($firstAddress['phone']) ?>,
        address: <?= json_encode(($firstAddress['county'] ?? '') . ', ' . ($firstAddress['city'] ?? '') . ', ' . ($firstAddress['address_line'] ?? '')) ?>
    };
    updateShippingDetails();
    <?php endif; ?>

    updateOrderTotal();

    // modal facturare
    const editBillingBtn = document.getElementById('edit-billing-btn');
    if(editBillingBtn) editBillingBtn.addEventListener('click', () => {
        const billingModal = document.getElementById('billing-modal');
        if(billingModal) billingModal.style.display = 'flex';
    });

    const closeBilling = document.getElementById('close-billing-modal');
    if(closeBilling) closeBilling.addEventListener('click', () => {
        const billingModal = document.getElementById('billing-modal');
        if(billingModal) billingModal.style.display = 'none';
    });

    // selectare adresa facturare (in modal) -> marcheaza billingOverridden
    document.querySelectorAll('#billing-modal .address-item.selectable').forEach(item => {
        // daca nu are listener, atasam in attachAddressListeners; aici doar marcam override
        item.addEventListener('click', () => {
            billingOverridden = true;
            // updateBillingDetails va fi apelat din attachAddressListeners
        });
    });

    // inchidem address-modal la click in exterior
    const addrModal = document.getElementById('address-modal');
    if(addrModal) addrModal.addEventListener('click', ev => {
        if (ev.target === addrModal) addrModal.style.display = 'none';
    });

    const newAddrModal = document.getElementById('new-address-modal');
    if(newAddrModal) newAddrModal.addEventListener('click', ev => {
        if (ev.target === newAddrModal) newAddrModal.style.display = 'none';
    });
});

// =========================
// Fix modal "Adaugă adresă nouă" și integrare în lista de adrese
// Pune acest bloc la sfârșitul scriptului
// =========================
// Fix modal "Adaugă adresă nouă" și integrare în lista de adrese
(function(){
    const $ = sel => document.querySelector(sel);
    const $$ = sel => Array.from(document.querySelectorAll(sel));

    const addNewAddrBtn = $('#add-new-address'); // buton din address-modal
    const newAddrModal = $('#new-address-modal');
    const closeNewAddr = $('#close-new-address');
    const addressForm = $('#addressForm');
    const addressListInModal = $('#address-modal .address-list');

    // deschide modalul de adaugare adresa
    if (addNewAddrBtn && !addNewAddrBtn.dataset.bound) {
        addNewAddrBtn.dataset.bound = '1';
        addNewAddrBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const addrModal = $('#address-modal');
            if(addrModal) addrModal.style.display = 'none';
            if(newAddrModal) newAddrModal.style.display = 'flex';
            const first = newAddrModal?.querySelector('input');
            if(first) first.focus();
        });
    }

    // inchide modalul de adaugare adresa
    if (closeNewAddr && !closeNewAddr.dataset.bound) {
        closeNewAddr.dataset.bound = '1';
        closeNewAddr.addEventListener('click', () => {
            if (newAddrModal) newAddrModal.style.display = 'none';
        });
    }

    // click outside modal inchide
    if (newAddrModal && !newAddrModal.dataset.outbound) {
        newAddrModal.dataset.outbound = '1';
        newAddrModal.addEventListener('click', (ev) => {
            if (ev.target === newAddrModal) newAddrModal.style.display = 'none';
        });
    }

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

    // helper simplu selection handler (fallback)
    function simpleSelectHandler(div){
        document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
        div.classList.add('active');
        setShippingDetails(div.dataset.name, div.dataset.phone, div.dataset.address, 25.00);
        const modal = document.getElementById('address-modal');
        if(modal) modal.style.display = 'none';
    }

    // functie helper fara duplicate
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
            // select existing and update shipping details
            document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
            existing.classList.add('active');
            try {
                setShippingDetails(existing.dataset.name, existing.dataset.phone, existing.dataset.address, 25.00);
            } catch(e){}
            const addrModal = document.getElementById('address-modal');
            if(addrModal) addrModal.style.display = 'none';
            return;
        }

        // create new node and insert at top so user vede imediat
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

        // detach any previous active and select new
        document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
        div.classList.add('active');

        try { setShippingDetails(name, phone, addressText, 25.00); } catch(e){}

        // attach click handler for the new element
        div.addEventListener('click', () => simpleSelectHandler(div));

        // close both modals: new-address and address list
        const addrModal = document.getElementById('address-modal');
        if(addrModal) addrModal.style.display = 'none';
        if(newAddrModal) newAddrModal.style.display = 'none';
    }

    // submit form adaugare adresa - folosim async/await, tratam JSON
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
                alert('Completează toate câmpurile!');
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
                    // fallback local in caz de raspuns neasteptat
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

    // Ascunde orice buton / optiune legata de pickup in payment si card-option
    $$('.payment-option').forEach(opt => {
        if(opt.dataset.pay === 'pickup' || opt.dataset.shipping === 'pickup') {
            opt.style.display = 'none';
        }
    });
    $$('.card-option').forEach(card => {
        if(card.dataset.shipping === 'pickup') {
            card.remove();
        }
    });

})(); // end IIFE
</script>

</body>
</html>