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
$total = $_SESSION['checkout_total'] ?? 0;

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
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type = 'shipping'");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

/* =========================
   PROCESARE COMANDA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipping_method = $_POST['shipping_method'] ?? 'courier';
    $payment_method  = $_POST['payment_method'] ?? 'online';
    $easybox_id      = null;
    $easybox_city    = null;
    $easybox_county  = null;

    try {
        $pdo->beginTransaction();

        /* =========================
           ADAUGARE PUNCT EASYBOX (DACA ESTE NECESAR)
        ========================== */
        if ($shipping_method === 'easybox') {
            $new_easybox_name    = trim($_POST['new_easybox_name'] ?? '');
            $new_easybox_address = trim($_POST['new_easybox_address'] ?? '');
            $new_easybox_city    = trim($_POST['new_easybox_city'] ?? '');
            $new_easybox_county  = trim($_POST['new_easybox_county'] ?? '');
            $new_easybox_lat     = floatval($_POST['new_easybox_lat'] ?? 0);
            $new_easybox_lng     = floatval($_POST['new_easybox_lng'] ?? 0);

            if ($new_easybox_name && $new_easybox_address && $new_easybox_city && $new_easybox_county) {
                $stmt = $pdo->prepare("
                    INSERT INTO easyboxes (name, address, city, county, lat, lng, active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$new_easybox_name, $new_easybox_address, $new_easybox_city, $new_easybox_county, $new_easybox_lat, $new_easybox_lng]);
                $easybox_id      = $pdo->lastInsertId();
                $easybox_city    = $new_easybox_city;
                $easybox_county  = $new_easybox_county;
            } else {
                // preluare punct existent
                $easybox_id = (int)($_POST['easybox_id'] ?? 0);
                if ($easybox_id <= 0) throw new Exception("EasyBox invalid");

                $stmt = $pdo->prepare("SELECT county, city, address FROM easyboxes WHERE id = ? AND active = 1");
                $stmt->execute([$easybox_id]);
                $box = $stmt->fetch();
                if (!$box) throw new Exception("EasyBox inexistent");

                $easybox_city   = $box['city'];
                $easybox_county = $box['county'];
            }
        }

        /* =========================
           DATE LIVRARE
        ========================== */
        if ($shipping_method === 'easybox') {
            $shipping_name    = 'Ridicare EasyBox';
            $shipping_phone   = $user['phone'];
            $shipping_address = $box['address'] ?? $new_easybox_address ?? '';
        } else {
            $address = $addresses[0] ?? null;
            if ($address) {
                $shipping_name    = $address['name'];
                $shipping_phone   = $address['phone'];
                $shipping_address = "{$address['county']}, {$address['city']}, {$address['address_line']}";
            } else {
                $shipping_name    = $user['name'];
                $shipping_phone   = $user['phone'];
                $shipping_address = "Valcea, Bungetani, Sat Milesti";
            }
        }

        /* =========================
           COSTURI
        ========================== */
        $shipping_cost = ($shipping_method === 'easybox') ? 12.99 : 25.00;
        $payment_fee   = ($payment_method === 'delivery') ? 10.00 : 0.00;
        $order_total   = $total + $shipping_cost + $payment_fee;
        $status        = ($payment_method === 'online') ? 'pending_payment' : 'processing';

        /* =========================
           INSERARE COMANDA
        ========================== */
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (user_id, total, shipping_method, easybox_id, easybox_city, easybox_county, payment_method,
            shipping_name, shipping_phone, shipping_address, status, shipping_cost, payment_fee, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id, $order_total, $shipping_method, $easybox_id, $easybox_city, $easybox_county,
            $payment_method, $shipping_name, $shipping_phone, $shipping_address, $status,
            $shipping_cost, $payment_fee
        ]);
        $order_id = $pdo->lastInsertId();

        /* =========================
           ITEMS + ACTUALIZARE STOC
        ========================== */
        $stmtItem  = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

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
        unset($_SESSION['cart'], $_SESSION['cart_total'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);

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
/* Mini-modal Adaugă Adresa Nouă */
#new-address-modal {
    display: none; /* ascuns implicit */
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

#new-address-modal .modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    width: 90%;
    max-width: 500px;
    animation: slide-down 0.3s ease-out;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

#new-address-modal .close-modal {
    float: right;
    font-size: 24px;
    cursor: pointer;
}

#new-address-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 4px;
}

#new-address-form input {
    width: 100%;
    padding: 6px 8px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

#new-address-form button {
    padding: 10px 16px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

#new-address-form button:hover {
    background: #0056b3;
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
            <input type="hidden" name="easybox_id" id="easybox_id">
            <input type="hidden" name="shipping_method" id="shipping_method" value="courier">
            <input type="hidden" name="easybox_city" id="easybox_city">
            <input type="hidden" name="easybox_county" id="easybox_county">

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
                <div class="card-option <?= ($addresses['shipping']['type'] ?? '') === 'pickup' ? 'active' : '' ?>" data-shipping="pickup">
                    <h4>Ridicare personală</h4>
                </div>
            </div>
            <input type="hidden" name="shipping_method" id="shipping_method" value="<?= htmlspecialchars($addresses['shipping']['type'] ?? 'courier') ?>">
        </div>

        <div id="shipping-details">
		</div>

        <!-- Templates pentru JS -->
        <div id="courier-template" style="display:none">
            <p>Livrare prin curier la:</p>
            <strong><?= htmlspecialchars($addresses['shipping']['name'] ?? $user['name']) ?>, <?= htmlspecialchars($addresses['shipping']['phone'] ?? $user['phone']) ?></strong>
            <p><?= htmlspecialchars($addresses['shipping']['address'] ?? '') ?></p>
            <p>Cost livrare: <?= number_format(25, 2) ?> Lei</p>
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


        <!-- Modal Ridicare personală -->
        <div id="pickup-modal" class="address-modal" style="display:none;">
          <div class="modal-content">
            <h2>Selectează punctul de ridicare</h2>

            <div style="display:flex; gap:20px;">
              <!-- Stânga: formular + filtre + listă -->
              <div style="flex:1;">
                <h4>Adaugă EasyBox nou</h4>
                <form id="addEasyBoxForm">
                  <label>Nume punct:</label>
                  <input type="text" id="newBoxName" placeholder="Ex: EasyBox Gara de Nord" required>

                  <label>Adresă:</label>
                  <input type="text" id="newBoxAddress" placeholder="Strada / Nr" required>

                  <label>Oraș:</label>
                  <input type="text" id="newBoxCity" placeholder="Oraș" required>

                  <label>Județ:</label>
                  <input type="text" id="newBoxCounty" placeholder="Județ" required>

                  <button type="button" id="add-pickup-btn">Adaugă EasyBox</button>
                </form>

                <h4>Filtre</h4>
                <input type="text" id="filter-city" placeholder="Caută oraș...">
                <input type="text" id="filter-county" placeholder="Caută județ...">

                <h4>EasyBox-uri existente</h4>
                <div id="pickup-list" style="max-height:400px; overflow-y:auto;">
                  <?php foreach($easyboxes as $box): ?>
                    <div class="easybox-item"
                         data-id="<?= (int)$box['id'] ?>"
                         data-name="<?= htmlspecialchars($box['name']) ?>"
                         data-address="<?= htmlspecialchars($box['county'] . ', ' . $box['city'] . ', ' . $box['address']) ?>"
                         data-city="<?= htmlspecialchars($box['city']) ?>"
                         data-county="<?= htmlspecialchars($box['county']) ?>"
                         data-lat="<?= $box['lat'] ?>"
                         data-lng="<?= $box['lng'] ?>"
                         style="margin-bottom:10px; padding:5px; border:1px solid #ccc; cursor:pointer;">
                      <strong><?= htmlspecialchars($box['name']) ?></strong><br>
                      <span><?= htmlspecialchars($box['address']) ?></span><br>
                      <a href="https://www.google.com/maps/search/?api=1&query=<?= $box['lat'] ?>,<?= $box['lng'] ?>" target="_blank">Deschide în Google Maps</a>
                      <p>Program: Luni-Duminica Non stop</p>
                    </div>
                  <?php endforeach; ?>
                </div>

                <button id="back-to-list" class="pu-btn" style="margin-top:10px; padding:8px 12px; border-radius:6px;">Închide</button>
              </div>

              <!-- Dreapta: hartă din modal (ID UNIC diferit de pagina principala) -->
              <div style="flex:2;">
                <div id="pickup-map-modal" style="width:100%; height:400px;"></div>
              </div>
            </div>

            <button id="close-pickup-modal" class="close-modal">&times; Închide</button>
          </div>
        </div>

        <!-- Date facturare -->
        <div class="section">
            <div class="section-header">
                <h3>Date facturare</h3>
                <button type="button" id="edit-billing-btn">Modifică</button>
            </div>

            <!-- Afișare adresă curentă -->
            <div id="billing-details">
                <?php 
                    $billing = $addresses[0] ?? ['name' => $user['name'], 'phone' => $user['phone'], 'county' => '', 'city' => '', 'address_line' => ''];
                ?>
                <p>
                    <strong><?= htmlspecialchars($billing['name']) ?> - <?= htmlspecialchars($billing['phone']) ?></strong><br>
                    <?= htmlspecialchars($billing['county'] . ', ' . $billing['city'] . ', ' . $billing['address_line']) ?>
                </p>
            </div>

            <!-- Modal adrese facturare -->
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
                    <p>Plătești la livrare prin curier. Cost procesare: 10 Lei.</p>
                </div>

                <div class="payment-option" 
                     data-pay="pickup" 
                     data-shipping="pickup"
                     id="pay-pickup-card">
                    <h4>Card bancar la punctul de ridicare</h4>
                    <p>Plătești cu cardul la punctul de ridicare. Cost procesare: 10 Lei.</p>
                </div>
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
                <strong>Total de plata: <span id="order-total"><?= number_format($totalProducts + $shippingCost, 2) ?></span> Lei</strong>
            </div>

            <!-- hidden pentru JS -->
            <input type="hidden" id="order-subtotal-hidden" value="<?= $totalProducts ?>">
            <input type="hidden" id="shipping-cost-hidden" value="<?= $shippingCost ?>">
    <!-- Alege alta adresa -->
    <div style="margin-top:10px;">
        <button type="button" id="change-address-btn" class="change-address-btn">Alege alta adresa</button>
    </div>
    </form>
<!-- Modal adrese existente -->
<div id="address-modal" class="address-modal">
    <div class="modal-content" id="address-modal-content">
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
    <button type="button" id="change-pickup-btn" class="btn-use change-pickup-btn">Adaugă adresa nouă</button>

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


<!-- Ridicare personala cu harta (pagina principala) -->
<div id="pickup-section" class="section" style="display:none;">
    <h3>Ridicare personală</h3>

    <!-- Harta din pagina principala (ID UNIC) -->
    <div id="pickup-map" style="width:100%; height:400px; border:1px solid #ccc;"></div>

    <p id="pickup-message"></p>
    <input type="hidden" name="pickup_location" id="pickup_location" value="<?= htmlspecialchars($pickup_location ?? '') ?>">

    <div class="easybox-logo">
        <img src="easybox.png" alt="EasyBox">
        <span>easybox</span>
    </div>
</div>


        <?php foreach($easyboxes as $box): 
            $city = htmlspecialchars($box['city'] ?? '');
            $county = htmlspecialchars($box['county'] ?? '');
        ?>
          <div class="easybox-item"
               data-lat="<?= $box['lat'] ?>"
               data-lng="<?= $box['lng'] ?>"
               data-name="<?= htmlspecialchars($box['name']) ?>"
               data-address="<?= htmlspecialchars($box['address']) ?>"
               data-city="<?= $city ?>"
               data-county="<?= $county ?>"
               style="margin-bottom:10px; padding:5px; border:1px solid #ccc; cursor:pointer;">
            <strong><?= htmlspecialchars($box['name']) ?></strong><br>
            <span><?= htmlspecialchars($box['address']) ?></span><br>
            <a href="https://www.google.com/maps/search/?api=1&query=<?= $box['lat'] ?>,<?= $box['lng'] ?>" target="_blank">Deschide in Google Maps</a>
            <p>Program: Luni-Duminica Non stop</p>
          </div>
        <?php endforeach; ?>


<script>
// -------------------------
// Array EasyBox-uri (poți prelua din PHP prin JSON)
const easyBoxes = [
    { name: "EasyBox Craiova 1", address: "Str. A, Craiova", lat: 44.317, lng: 23.798 },
    { name: "EasyBox Craiova 2", address: "Str. B, Craiova", lat: 44.326, lng: 23.786 },
    { name: "EasyBox Bucuresti 1", address: "Bd. Magheru, Bucuresti", lat: 44.439, lng: 26.096 }
];

// -------------------------
// Distanta km
function getDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}
// -------------------------
// Geolocatie utilizator
function getUserLocation(cb) {
    if (!navigator.geolocation) {
        cb(null);
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => cb({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => cb(null)
    );
}
// Stocare ultima adresă selectată
let lastSelectedCourierAddress = null;

function updateShippingDetails(type) {
    const detailsDiv = document.getElementById('shipping-details');
    if(type === 'courier'){
        if(lastSelectedCourierAddress){
            // folosește ultima adresă selectată
            detailsDiv.innerHTML = `
                <p>Livrare prin curier la:</p>
                <strong>${lastSelectedCourierAddress.name} - ${lastSelectedCourierAddress.phone}</strong>
                <p>${lastSelectedCourierAddress.address}</p>
                <p>Cost livrare: 25,00 Lei</p>
                <button type="button" class="change-address-btn" onclick="document.getElementById('address-modal').style.display='flex'">Alege alta adresa</button>
            `;
        } else {
            // folosește template default
            detailsDiv.innerHTML = document.getElementById('courier-template').innerHTML;
        }
    } else if(type === 'pickup'){
        detailsDiv.innerHTML = document.getElementById('pickup-template').innerHTML;
    }
}

// -------------------------
// Updatează starea card-urilor (pickup/courier)
function updateCardStates(nearbyEasyBoxes){
    const pickupCard = document.querySelector('.card-option[data-shipping="pickup"]');
    const courierCard = document.querySelector('.card-option[data-shipping="courier"]');

    if(nearbyEasyBoxes.length === 0){
        pickupCard.classList.add('disabled');
        pickupCard.title = "Coșul tău conține produse care nu pot fi ridicate personal. Te rugăm să alegi livrare prin curier.";
        pickupCard.style.cursor = 'not-allowed';
        pickupCard.classList.remove('active');

        courierCard.classList.add('active');
        document.getElementById('shipping_method').value = 'courier';
        updateShippingDetails('courier');
    } else {
        pickupCard.classList.remove('disabled');
        pickupCard.title = '';
        pickupCard.style.cursor = 'pointer';
    }
}

// -------------------------
// Afisare EasyBox pe harta
// Afisare EasyBox pe harta
function showEasyBoxPickup(userPos) {
    const section = document.getElementById('pickup-section');
    const mapDiv = document.getElementById('pickup-map');
    const message = document.getElementById('pickup-message');

    section.style.display = 'block';

    if (!userPos) {
        message.textContent = "EasyBox indisponibil in zona ta.";
        mapDiv.style.display = 'none';
        return;
    }

    const nearby = easyBoxes.filter(b =>
        getDistanceKm(userPos.lat, userPos.lng, b.lat, b.lng) <= 50
    );

    if (nearby.length === 0) {
        message.textContent = "EasyBox indisponibil in zona ta.";
        mapDiv.style.display = 'none';
        return;
    }

    message.textContent = "Alege un EasyBox din apropiere.";

    const map = new google.maps.Map(mapDiv, {
        center: userPos,
        zoom: 12
    });

    new google.maps.Marker({
        position: userPos,
        map: map,
        title: "Locatia ta",
        icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
    });

    nearby.forEach(box => {
        const marker = new google.maps.Marker({
            position: { lat: box.lat, lng: box.lng },
            map: map,
            title: box.name
        });

        marker.addListener('click', () => {
            document.getElementById('shipping-details').innerHTML = `
                <p>Ridicare personala la:</p>
                <strong>${box.name}</strong>
                <p>${box.address}</p>
                <p>Cost livrare: 12,99 Lei</p>
            `;
            document.getElementById('shipping_method').value = 'pickup';
            document.getElementById('pickup_location').value = box.name + ' | ' + box.address;
        });
    });
}

// -------------------------
// Event listener card-uri
// -------------------------
// Click pe card-uri shipping
document.querySelectorAll('.card-option').forEach(card => {
    card.addEventListener('click', () => {
        if(card.classList.contains('disabled')) return;

        // Dezactivează toate card-urile și activează pe cel curent
        document.querySelectorAll('.card-option').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        // Setează metoda de livrare
        const shippingType = card.dataset.shipping;
        document.getElementById('shipping_method').value = shippingType;

        const pickupSection = document.getElementById('pickup-section');

        if(shippingType === 'pickup'){
            // Arată secțiunea pickup
            if(pickupSection) pickupSection.style.display = 'block';
            // Obține locația utilizatorului și afișează EasyBox-urile
            getUserLocation(showEasyBoxPickup);
        } else {
            // Ascunde secțiunea pickup și afișează detaliile curier
            if(pickupSection) pickupSection.style.display = 'none';
            updateShippingDetails('courier');
        }

        // === Actualizează metodele de plată în funcție de shipping ===
        document.querySelectorAll('.payment-option').forEach(pay => {
            const shipping = pay.dataset.shipping; // all, courier, pickup
            if(shipping === 'all' || shipping === shippingType) {
                pay.style.display = 'block';
            } else {
                pay.style.display = 'none';
            }
        });

        // Setează default metoda de plată vizibilă
        const defaultPay = document.querySelector('.payment-option[style*="block"]');
        if(defaultPay) {
            document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('active'));
            defaultPay.classList.add('active');
            document.getElementById('payment_method').value = defaultPay.dataset.pay;
        }
    });

    // Tooltip pentru card disabled
    card.addEventListener('mouseenter', () => {
        if(card.classList.contains('disabled')){
            const tooltip = document.createElement('div');
            tooltip.id = 'disabled-tooltip';
            tooltip.textContent = card.title;
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '6px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.top = (card.getBoundingClientRect().top - 40 + window.scrollY) + 'px';
            tooltip.style.left = (card.getBoundingClientRect().left) + 'px';
            tooltip.style.zIndex = 10000;
            document.body.appendChild(tooltip);
        }
    });
    card.addEventListener('mouseleave', () => {
        const tooltip = document.getElementById('disabled-tooltip');
        if(tooltip) tooltip.remove();
    });
});

// === Click pe metoda de plată pentru selectare ===
document.querySelectorAll('.payment-option').forEach(pay => {
    pay.addEventListener('click', () => {
        document.querySelectorAll('.payment-option').forEach(p => p.classList.remove('active'));
        pay.classList.add('active');
        document.getElementById('payment_method').value = pay.dataset.pay;
    });
});

// -------------------------
// La încărcarea paginii: verifică dacă pickup-ul e disponibil
window.addEventListener('DOMContentLoaded', () => {
    const pickupCard = document.querySelector('.card-option[data-shipping="pickup"]');
    if(!pickupCard) return;

    getUserLocation(pos => {
        if(!pos) return;

        // Găsește EasyBox-urile în raza de 50km
        const nearby = easyBoxes.filter(box => getDistanceKm(pos.lat, pos.lng, box.lat, box.lng) <= 100);

        if(nearby.length > 0){
            pickupCard.classList.remove('disabled');
            pickupCard.title = '';
            pickupCard.style.cursor = 'pointer';
        } else {
            pickupCard.classList.add('disabled');
            pickupCard.title = "Coșul tău conține produse care nu pot fi ridicate personal.";
            pickupCard.style.cursor = 'not-allowed';
        }
    });
});
document.addEventListener('DOMContentLoaded', () => {
    let lastSelectedCourierAddress = null;

    // =========================
    // Funcție pentru actualizare shipping
    // =========================
    function setShippingDetails(name, phone, address, cost) {
        lastSelectedCourierAddress = { name, phone, address };
        const shippingInfo = document.getElementById('shipping-details');
        if (shippingInfo) {
            shippingInfo.innerHTML = `
                <p>Livrare la:</p>
                <strong>${name}${phone ? ' - ' + phone : ''}</strong>
                <p>${address}</p>
                <p>Cost livrare: ${cost.toFixed(2)} Lei</p>
            `;
        }
    }

    // =========================
    // SELECTARE COURIER - MODAL 1
    // =========================
    const addressModal = document.getElementById('address-modal');
    const newAddressModal = document.getElementById('new-address-modal');
    const addressForm = document.getElementById('addressForm');

    // --- click pe buton schimbare adresă
    const changeAddressBtn = document.getElementById('change-address-btn');
    if (changeAddressBtn) {
        changeAddressBtn.addEventListener('click', () => {
            if (addressModal) addressModal.style.display = 'flex';
        });
    }

    // --- selectare adresă existentă în modal 1
    function attachAddressListeners() {
        document.querySelectorAll('.address-item.selectable').forEach(item => {
            item.removeEventListener('click', item._listener); // previne dublarea
            const listener = () => {
                document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                setShippingDetails(item.dataset.name, item.dataset.phone, item.dataset.address, 25.00);
                if (addressModal) addressModal.style.display = 'none';
            };
            item.addEventListener('click', listener);
            item._listener = listener; // stocăm referința pentru removeEventListener
        });
    }
    attachAddressListeners();

          if (addressForm) {
            addressForm.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(addressForm);

                fetch('add_address_ajax.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return alert(data.error || 'Eroare server');

                        const a = data.address;
                        const div = document.createElement('div');
                        div.className = 'address-item selectable';
                        div.dataset.name = a.name;
                        div.dataset.phone = a.phone;
                        div.dataset.address = `${a.county}, ${a.city}, ${a.address_line}`;
                        div.innerHTML = `
                            <span class="radio-dot"></span>
                            <div class="address-text">
                                <strong>${a.name} - ${a.phone}</strong>
                                <p>${a.county}, ${a.city}, ${a.address_line}</p>
                            </div>
                        `;

                        // atașăm exact în modalul corect
                        const list = newAddressModal.closest('#address-modal')?.querySelector('.address-list');
                        if(list) list.appendChild(div);

                        // atașează listener noului item
                        attachAddressListeners();

                        newAddressModal.style.display = 'none';
                        addressForm.reset();
                    })
                    .catch(err => console.error(err));
            });
        }


    // =========================
    // EASYBOX - modal separat
    // =========================
    const pickupModal = document.getElementById('pickup-modal');
    const pickupList = document.getElementById('pickup-list');
    const addPickupBtn = document.getElementById('add-pickup-btn');
    const pickupSection = document.getElementById('pickup-section');

    function addBoxToList(box) {
        const li = document.createElement('div');
        li.classList.add('easybox-item');
        li.dataset.id = box.id;
        li.dataset.name = box.name;
        li.dataset.address = box.address;
        li.dataset.city = box.city;
        li.dataset.county = box.county;
        li.innerHTML = `<strong>${box.name}</strong> <span>${box.address}, ${box.city}, ${box.county}</span>`;
        pickupList.appendChild(li);

        li.addEventListener('click', () => {
            // Setează input-urile hidden din form
            document.getElementById('shipping_method').value = 'pickup';
            document.getElementById('easybox_id').value = box.id;
            document.getElementById('easybox_city').value = box.city;
            document.getElementById('easybox_county').value = box.county;

            // Actualizează secțiunea Pickup
            setShippingDetails(box.name, 'Ridicare EasyBox', `${box.address}, ${box.city}, ${box.county}`, 12.99);

            if (pickupSection) pickupSection.style.display = 'block';
            if (pickupModal) pickupModal.style.display = 'none';
        });
    }

// ---------------------------
// Courier / adresa utilizatorului
if (addressForm) {
    addressForm.addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(addressForm);
        fetch('add_address_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(!data.success) return alert(data.error || 'Eroare server');

                const a = data.address;
                const div = document.createElement('div');
                div.className = 'address-item selectable';
                div.dataset.name = a.name;
                div.dataset.phone = a.phone;
                div.dataset.address = `${a.county}, ${a.city}, ${a.address_line}`;
                div.innerHTML = `
                    <span class="radio-dot"></span>
                    <div class="address-text">
                        <strong>${a.name} - ${a.phone}</strong>
                        <p>${a.county}, ${a.city}, ${a.address_line}</p>
                    </div>
                `;
                // adaugă direct în modalul Courier
                const list = document.querySelector('#address-modal .address-list');
                if(list) list.appendChild(div);
                attachAddressListeners();
                newAddressModal.style.display = 'none';
                addressForm.reset();
            })
            .catch(err => console.error(err));
    });
}

// ---------------------------
// EasyBox / Ridicare personală
  if (addPickupBtn) {
        addPickupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const name    = document.getElementById('newBoxName').value.trim();
            const address = document.getElementById('newBoxAddress').value.trim();
            const city    = document.getElementById('newBoxCity').value.trim();
            const county  = document.getElementById('newBoxCounty').value.trim();

            if (!name || !address || !city || !county) {
                alert("Completează toate câmpurile!");
                return;
            }

            const formData = new FormData();
            formData.append('type', 'easybox');
            formData.append('name', name);
            formData.append('address', address);
            formData.append('city', city);
            formData.append('county', county);

            fetch('add_address_ajax.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        addBoxToListAndMap(data.box);
                        document.getElementById('newBoxName').value = '';
                        document.getElementById('newBoxAddress').value = '';
                        document.getElementById('newBoxCity').value = '';
                        document.getElementById('newBoxCounty').value = '';
                        alert("EasyBox adăugat cu succes!");
                    } else {
                        alert(data.error || "Eroare la adăugare EasyBox");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Eroare la comunicarea cu serverul");
                });
        });
    }

    // -------------------------
    if (closePickupModal) closePickupModal.addEventListener('click', () => pickupModal.style.display = 'none');
    if (backToList) backToList.addEventListener('click', () => pickupModal.style.display = 'none');

    // =========================
    // Card shipping
    // =========================
    document.querySelectorAll('.card-option').forEach(card => {
        card.addEventListener('click', () => {
            const type = card.dataset.shipping;
            if (type === 'pickup') {
                if (pickupSection) pickupSection.style.display = 'block';
                setShippingDetails('Ridicare personală', '', 'Alege un punct de ridicare', 0.00);
            } else {
                if (pickupSection) pickupSection.style.display = 'none';
            }
        });
    });
});

// ==========================
// Funcție de afișare detalii livrare
// ==========================
// ==========================
// Funcție de afișare detalii livrare
// ==========================
function setShippingDetails(name, phone, address, cost = 25.00) {
    document.getElementById('shipping-details').innerHTML = `
        <p>Livrare la:</p>
        <strong>${name} - ${phone}</strong>
        <p>${address}</p>
        <p>Cost livrare: ${cost.toFixed(2)} Lei</p>
    `;

    // actualizeaza cost livrare in input hidden
    const shippingCostInput = document.getElementById('shipping-cost');
    if(shippingCostInput) shippingCostInput.value = cost;

    // recalcul total
    updateOrderTotal();
}
    // Recalculeaza total comanda
        function updateOrderTotal() {
            let subtotal = 0;
            document.querySelectorAll('tr.cart-item').forEach(row => {
                const price = parseFloat(row.dataset.price);
                const qty = parseInt(row.querySelector('input.qty-input').value) || 1;
                const rowSubtotal = price * qty;
                row.querySelector('.row-subtotal').textContent = rowSubtotal.toFixed(2) + ' Lei';
                subtotal += rowSubtotal;
            });

            const shipping = parseFloat(document.getElementById('shipping-cost-hidden').value) || 0;

            document.getElementById('order-subtotal-display').textContent = subtotal.toFixed(2);
            document.getElementById('order-subtotal-hidden').value = subtotal.toFixed(2);

            const totalEl = document.getElementById('order-total');
            if(totalEl) totalEl.textContent = (subtotal + shipping).toFixed(2) + ' Lei';

            const shippingDisplay = document.getElementById('shipping-cost-display');
            if(shippingDisplay) shippingDisplay.textContent = shipping.toFixed(2) + ' Lei';
        }

        // Ascultător pentru modificarea cantității
        document.querySelectorAll('input.qty-input').forEach(input => {
            input.addEventListener('change', updateOrderTotal);
        });

        // Ascultător pentru schimbarea metodei de livrare
        const shippingInput = document.getElementById('shipping_method');
        if(shippingInput){
            shippingInput.addEventListener('change', function() {
                const newCost = this.value === 'easybox' ? 12.99 : 25.00;
                const shippingHidden = document.getElementById('shipping-cost-hidden');
                if(shippingHidden) shippingHidden.value = newCost;
                updateOrderTotal();
            });
        }

        // Update inițial la încărcarea paginii
  document.addEventListener('DOMContentLoaded', () => {
    updateOrderTotal();

    const addressForm = document.getElementById('addressForm');
    if (!addressForm) return;

    addressForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('add_address_ajax.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) { 
                    alert(data.error || 'Eroare server'); 
                    return; 
                }

                const a = data.address;

                // Creeaza div pentru noua adresa
                const div = document.createElement('div');
                div.className = 'address-item selectable';
                div.dataset.name = a.name;
                div.dataset.phone = a.phone;
                div.dataset.address = `${a.county}, ${a.city}, ${a.address_line}`;

                div.innerHTML = `
                    <span class="radio-dot"></span>
                    <div class="address-text">
                        <strong>${a.name} - ${a.phone}</strong>
                        <p>${a.county}, ${a.city}, ${a.address_line}</p>
                    </div>
                `;

                // Adauga noua adresa in lista
                const addressList = document.querySelector('.address-list');
                addressList.appendChild(div);

                // Dezactiveaza toate celelalte adrese
                document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));

                // Selecteaza noua adresa după ce browserul a terminat render-ul
                setTimeout(() => {
                    div.classList.add('active');
                    setShippingDetails(a.name, a.phone, `${a.county}, ${a.city}, ${a.address_line}`);
                }, 10);

                // Click pe noua adresa pentru viitoarele selectii
                div.addEventListener('click', () => {
                    document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
                    div.classList.add('active');
                    setShippingDetails(a.name, a.phone, `${a.county}, ${a.city}, ${a.address_line}`);
                    document.getElementById('address-modal').style.display = 'none';
                });

                // Inchide modal si reseteaza form
                document.getElementById('new-address-modal').style.display = 'none';
                this.reset();
            })
            .catch(err => { 
                console.error(err); 
                alert('Eroare server'); 
            });
    });
});
// ==========================
// Selectare metoda livrare (card-uri)
// ==========================
// -------------------------
// -------------------------
// Click pe card-uri shipping
// Event listener card-uri (înlocuiește blocul vechi cu acesta)
document.querySelectorAll('.card-option').forEach(card => {
    card.addEventListener('click', () => {
        if (card.classList.contains('disabled')) return;

        // Dezactivează toate card-urile și activează pe cel curent
        document.querySelectorAll('.card-option').forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        // Setează metoda de livrare
        document.getElementById('shipping_method').value = card.dataset.shipping;

        const pickupSection = document.getElementById('pickup-section');

        if (card.dataset.shipping === 'pickup') {
            // Afișează detaliile pickup (template) și secțiunea cu harta
            updateShippingDetails('pickup');
            if (pickupSection) pickupSection.style.display = 'block';

            // Obține locația utilizatorului și afișează EasyBox-urile pe hartă
            getUserLocation(showEasyBoxPickup);
        } else {
            // Courier: ascunde secțiunea pickup și afișează detaliile curier
            if (pickupSection) pickupSection.style.display = 'none';
            updateShippingDetails('courier');
        }
    });

    // Tooltip pentru card disabled (păstrează ce ai avut)
    card.addEventListener('mouseenter', () => {
        if (card.classList.contains('disabled')) {
            const tooltip = document.createElement('div');
            tooltip.id = 'disabled-tooltip';
            tooltip.textContent = card.title;
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '6px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.top = (card.getBoundingClientRect().top - 40 + window.scrollY) + 'px';
            tooltip.style.left = (card.getBoundingClientRect().left) + 'px';
            tooltip.style.zIndex = 10000;
            document.body.appendChild(tooltip);
        }
    });
    card.addEventListener('mouseleave', () => {
        const tooltip = document.getElementById('disabled-tooltip');
        if (tooltip) tooltip.remove();
    });
});


// Butonul "Alege alta adresa"
const changeAddressBtn = document.getElementById('change-address-btn');
if(changeAddressBtn){
    changeAddressBtn.addEventListener('click', () => {
        const firstAddress = document.querySelector('.address-item.selectable');
        if(firstAddress){
            const name = firstAddress.dataset.name;
            const phone = firstAddress.dataset.phone;
            const address = firstAddress.dataset.address;

            lastSelectedCourierAddress = { name, phone, address };

            document.getElementById('shipping-details').innerHTML = `
                <p>Livrare la:</p>
                <strong>${name} - ${phone}</strong>
                <p>${address}</p>
                <p>Cost livrare: 25,00 Lei</p>
            `;
        }

        // Deschide modalul
        document.getElementById('address-modal').style.display = 'flex';
    });
}
// -------------------------
// Modal facturare
document.getElementById('edit-billing-btn').addEventListener('click', () => {
    document.getElementById('billing-modal').style.display = 'flex';
});

document.getElementById('close-billing-modal').addEventListener('click', () => {
    document.getElementById('billing-modal').style.display = 'none';
});

// Selectare adresa facturare
document.querySelectorAll('#billing-modal .address-item.selectable').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('#billing-modal .address-item.selectable').forEach(i => i.classList.remove('active'));
        item.classList.add('active');

        const name = item.dataset.name;
        const phone = item.dataset.phone;
        const address = item.dataset.address;

        document.getElementById('billing-details').innerHTML = `
            <p>
                <strong>${name} - ${phone}</strong><br>F
                ${address}
            </p>
        `;

        document.getElementById('billing-modal').style.display = 'none';
    });
});
// payment handlers
(function(){
    const options = document.querySelectorAll('.payment-option');
    const input = document.getElementById('payment_method');
    const info = document.getElementById('payment-info');
    const summary = document.querySelector('.summary'); // pentru actualizare vizuală a totalului
    const totalProdElem = summary ? summary.querySelector('p:first-child') : null;
    const deliveryCostElem = summary ? summary.querySelector('p:nth-child(2)') : null;
    const totalPayElem = summary ? summary.querySelector('strong') : null;

    function renderInfo(key){
        if(!info) return;
        if(key === 'online'){
            info.innerHTML = `
                <strong>Card online</strong>
                <p>Tranzacții securizate. Simplu și rapid. În maximă siguranță.</p>
                <p>Salvează cardul în contul tău Shergei Covoare pentru un checkout mai rapid data viitoare.</p>
            `;
        } else if(key === 'delivery'){
            info.innerHTML = `
                <strong>Card la livrare</strong>
                <p>Plătești cu cardul la livrare. Cost procesare 10,00 Lei.</p>
                <p>Recomandare, dacă vrei zero costuri, alege Card online.</p>
            `;
        } else {
            info.innerHTML = '';
        }
    }

    function updateVisualTotal(paymentKey){
        // calculezi doar pentru afișare client
        // preiei valoarea total produse din sesiune injectată în HTML, de ex data-total
        const totalProduse = Number(<?= json_encode((float)$total) ?>);
        // cost livrare detectat din shipping_method input
        const shippingMethod = document.getElementById('shipping_method').value || 'courier';
        const shippingCost = shippingMethod === 'pickup' ? 12.99 : 25.00;
        const paymentFee = paymentKey === 'delivery' ? 10.00 : 0.00;
        const grand = (totalProduse + shippingCost + paymentFee).toFixed(2);

        if(deliveryCostElem){
            deliveryCostElem.textContent = 'Cost livrare: ' + shippingCost.toFixed(2) + ' Lei';
        }
        if(totalPayElem){
            totalPayElem.textContent = 'Total de plata: ' + grand + ' Lei';
        }
    }

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            if(opt.classList.contains('disabled')) return;
            options.forEach(o => o.classList.remove('active'));
            opt.classList.add('active');
            const key = opt.dataset.pay;
            input.value = key;
            renderInfo(key);
            updateVisualTotal(key);
        });
    });

    // initial render
    const initial = input.value || 'online';
    renderInfo(initial);
    updateVisualTotal(initial);

    // dacă utilizator schimbă metoda de shipping, actualizăm suma
    const shippingInput = document.getElementById('shipping_method');
    if(shippingInput){
        shippingInput.addEventListener('change', () => {
            updateVisualTotal(document.getElementById('payment_method').value);
        });
    }
})();
(function(){
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  // elemente folosite
  const pickupTemplate = $('#pickup-template');
  const pickupModal = $('#pickup-modal');
  const easyNameEl = $('#easybox-name');
  const easyAddrEl = $('#easybox-address');
  const pickupList = $('#pickup-list');
  const pickupModalMapEl = $('#pickup-modal-map');
  const shippingInput = $('#shipping_method');
  const pickupLocationInput = $('#pickup_location');

  // safety: exista elemente?
  if(!pickupTemplate || !pickupModal || !pickupList) return;

  function setEasyBox(name, address){
    if(easyNameEl) easyNameEl.textContent = name;
    if(easyAddrEl) easyAddrEl.textContent = address;
    if(shippingInput) shippingInput.value = 'pickup';
    if(pickupLocationInput) pickupLocationInput.value = name + ' | ' + address;
    pickupTemplate.style.display = 'block';
  }

  // asteapta google.maps (retry mic)
  function waitForGoogleMaps(cb, tries = 0){
    if(window.google && window.google.maps){
      return cb();
    }
    if(tries > 40){ // ~4s
      console.warn('Google Maps nu s-a incarcat.');
      return cb(new Error('maps-not-ready'));
    }
    setTimeout(()=> waitForGoogleMaps(cb, tries+1), 100);
  }
	// plaseaza undeva sus in script, inainte de openPickupModal (sau langa alte utilitare)
function populateModalListFromPage() {
  const modalList = document.getElementById('pickup-list');
  if(!modalList) return;
  // daca deja exista elemente in modal, nu mai copiem
  if(modalList.querySelectorAll('.easybox-item').length) return;
  // selectam lista "globala" (elementele de jos) - folosim only direct children care nu sunt in modal
  document.querySelectorAll('body > .easybox-item, .container > .easybox-item').forEach(el => {
    // evita copiarea unei item deja in modal (safety)
    const clone = el.cloneNode(true);
    modalList.appendChild(clone);
  });
}
function generateDynamicFilterButtons() {
    const pickupList = document.getElementById('pickup-list');
    if(!pickupList) return;

    const leftCol = pickupList.parentElement.querySelector('.left-controls');
    if(!leftCol) return;

    // Curatam eventualele butoane vechi
    leftCol.innerHTML = '';

    // Extragem orașele și județele unice din lista de EasyBox-uri
    const cities = new Set();
    const counties = new Set();

    $$('#pickup-list .easybox-item').forEach(item => {
        const city = item.dataset.city?.trim();
        const county = item.dataset.county?.trim();
        if(city) cities.add(city);
        if(county) counties.add(county);
    });

    // Cream containerul pentru filtre
    const container = document.createElement('div');
    container.className = 'dynamic-filter-buttons';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '6px';

    // Creeaza butoane pentru orase
    const cityDiv = document.createElement('div');
    cityDiv.innerHTML = '<strong>Orase:</strong>';
    cities.forEach(city => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = city;
        btn.className = 'pu-filter-btn';
        btn.addEventListener('click', () => applySimpleFilter('city', city));
        cityDiv.appendChild(btn);
    });
    container.appendChild(cityDiv);

    // Creeaza butoane pentru judete
    const countyDiv = document.createElement('div');
    countyDiv.innerHTML = '<strong>Judete:</strong>';
    counties.forEach(county => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = county;
        btn.className = 'pu-filter-btn';
        btn.addEventListener('click', () => applySimpleFilter('county', county));
        countyDiv.appendChild(btn);
    });
    container.appendChild(countyDiv);

    // Adauga buton Clear
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.textContent = 'Clear Filtru';
    clearBtn.className = 'pu-filter-btn';
    clearBtn.addEventListener('click', () => applySimpleFilter('', ''));
    container.appendChild(clearBtn);

    // Inseram containerul in leftCol
    leftCol.appendChild(container);
}

 function openPickupModal() {
    if (!pickupModal) return;

    pickupModal.style.display = 'flex';

    waitForGoogleMaps((err) => {
        if (err) {
            attachMaplessListHandlers();
            initPickupModalEnhancements();
            return;
        }

        getUserLocation(userPos => {
            let center = userPos || null;

            // fallback la primul EasyBox cu coordonate valide
            if (!center) {
                const first = document.querySelector('#pickup-list .easybox-item');
                if (first && first.dataset.lat && first.dataset.lng) {
                    const lat = parseFloat(first.dataset.lat);
                    const lng = parseFloat(first.dataset.lng);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        center = { lat, lng };
                    }
                }
            }

            // fallback general
            if (!center) {
                center = { lat: 44.439, lng: 26.096 };
            }

            // inițializare hartă
            const map = new google.maps.Map(pickupModalMapEl, {
                center: center,
                zoom: 12,
                gestureHandling: 'greedy'
            });

            const bounds = new google.maps.LatLngBounds();

            // marker locație utilizator
            if (userPos) {
                new google.maps.Marker({
                    position: userPos,
                    map: map,
                    title: 'Locatia ta',
                    icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                });
            }

            // funcție pentru click pe EasyBox
            function handleEasyBoxClick(item, pos) {
                return () => {
                    map.setCenter(pos);
                    map.setZoom(14);

                    const name = item.dataset.name || '';
                    const address = item.dataset.address || '';
                    const city = item.dataset.city || '';
                    const county = item.dataset.county || '';
                    const easyboxId = item.dataset.id || '';

                    const pickupInput = document.getElementById('pickup_location');
                    const shipInput   = document.getElementById('shipping_method');
                    const cityInput   = document.getElementById('easybox_city');
                    const countyInput = document.getElementById('easybox_county');

                    if(pickupInput) pickupInput.value = `${name} | ${address}`;
                    if(shipInput) shipInput.value = 'easybox';
                    if(cityInput) cityInput.value = city;
                    if(countyInput) countyInput.value = county;

                    const nameEl = document.getElementById('easybox-name');
                    const addrEl = document.getElementById('easybox-address');
                    if(nameEl) nameEl.textContent = name;
                    if(addrEl) addrEl.textContent = address;

                    const details = document.getElementById('shipping-details');
                    if(details){
                        details.innerHTML = `
                            <p>Ridicare personala din:</p>
                            <strong>${name}</strong>
                            <p>${address}</p>
                            <p>${city}, ${county}</p>
                            <button type="button" class="change-pickup-btn">Alege alt punct</button>
                        `;
                    }

                    try {
                        localStorage.setItem('selected_easybox', JSON.stringify({ id: easyboxId, name, address, city, county }));
                    } catch(e){}
                };
            }

            // parcurgere lista EasyBox
            $$('#pickup-list .easybox-item').forEach(item => {
                const lat = parseFloat(item.dataset.lat);
                const lng = parseFloat(item.dataset.lng);
                if(!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                const pos = { lat, lng };
                bounds.extend(pos);

                new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: item.dataset.name || ''
                });

                // atașare click o singură dată
                if(!item.dataset.bound){
                    item.addEventListener('click', handleEasyBoxClick(item, pos));
                    item.dataset.bound = '1';
                }
            });

            // ajustare zoom la toate marcajele
            if(!userPos) map.fitBounds(bounds);

            initPickupModalEnhancements();
        });
    });
}
    // ========== SALVARE SERVER ==========

  // versiune fallback: daca maps nu e disponibil, permit selectionarea din lista (fara harta)
  function attachMaplessListHandlers(){
    $$('#pickup-list .easybox-item').forEach(item => {
      if(item.dataset.boundAttached) return;
      item.dataset.boundAttached = '1';
      item.addEventListener('click', () => {
        setEasyBox(item.dataset.name, item.dataset.address);
        pickupModal.style.display = 'none';
      });
    });
    initPickupModalEnhancements();
  }

  // Bind click global pentru orice buton cu clasa change-pickup-btn (in template)
  document.addEventListener('click', e => {
    const btn = e.target.closest('.change-pickup-btn');
    if(btn) {
      e.preventDefault();
      openPickupModal();
    }
  });

  // inchidere modal: cross + buton inapoi
  const closeIcon = $('#close-pickup-modal');
  if(closeIcon) closeIcon.addEventListener('click', () => pickupModal.style.display = 'none');

  const backBtn = $('#back-to-list');
  if(backBtn) backBtn.addEventListener('click', () => pickupModal.style.display = 'none');

  // populate initial: seteaza primul easybox din lista (daca exista)
  const firstItem = $('#pickup-list .easybox-item');
  if(firstItem){
    setEasyBox(firstItem.dataset.name, firstItem.dataset.address);
  }

  // expune initPickupModalEnhancements daca e definit (codul tau anterior)
  try { if(typeof initPickupModalEnhancements === 'function') initPickupModalEnhancements(); } catch(e){}

})();
(function(){
    const payOnlineBadge = document.querySelector('#pay-online .payment-badge');
    const modal = document.getElementById('save-card-modal');
    const closeBtn = document.getElementById('save-card-close');
    const yesBtn = document.getElementById('save-card-yes');
    const noBtn = document.getElementById('save-card-no');
    const saveField = document.getElementById('save_card');
    const paymentInfo = document.getElementById('payment-info');

    // safety checks
    if(!payOnlineBadge || !modal) return;

    function openModal(){
        modal.style.display = 'flex';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden','false');
    }
    function closeModal(){
        modal.style.display = 'none';
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden','true');
    }

    // click Detalii din card online
    payOnlineBadge.addEventListener('click', function(e){
        e.stopPropagation();
        openModal();
    });

    // buton inchidere
    closeBtn.addEventListener('click', closeModal);
    noBtn.addEventListener('click', function(){
        saveField.value = '0';
        closeModal();
    });

    // Salveaza cardul - setam campul hidden si afisam confirmare vizuala
    yesBtn.addEventListener('click', function(){
        saveField.value = '1';
        closeModal();

        // afisare mesaj scurt in payment-info
        if(paymentInfo){
            paymentInfo.innerHTML = '<strong>Card salvat in mod sigur.</strong><p>Poti gestiona cardurile salvate din contul tau.</p>';
            // optional: sterge mesajul dupa 4s
            setTimeout(()=> {
                if(paymentInfo) paymentInfo.innerHTML = '';
            }, 4000);
        }

        // optional: si localStorage pentru UI persistenta
        try { localStorage.setItem('sh_save_card', '1'); } catch(e){}
    });

    // click outside dialog inchide
    modal.addEventListener('click', function(ev){
        if(ev.target === modal) closeModal();
    });

    // inchide cu ESC
    document.addEventListener('keydown', function(ev){
        if(ev.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });
})();
// delegare: prinde orice click pe element cu clasa change-address-btn
document.addEventListener('click', function(e){
    const btn = e.target.closest('.change-address-btn');
    if(!btn) return;
    e.preventDefault();
    document.getElementById('address-modal').style.display = 'flex';
    // opțional: focus pe primul input din modal
    const first = document.querySelector('#address-modal input');
    if(first) first.focus();
});
<!-- Functie Butonul Alege alt punct -->
(function(){

// helper: safe query
const $ = sel => document.querySelector(sel);
const $$ = sel => Array.from(document.querySelectorAll(sel));

// Adauga butoane filtre si placeholder in partea stanga a modalului
function ensureFilterControls() {
  const leftCol = document.querySelector('#pickup-modal > div > div:first-child');
  if(!leftCol) return;
  if(leftCol.querySelector('.left-controls')) return;

  const ctrls = document.createElement('div');
  ctrls.className = 'left-controls';
  ctrls.innerHTML = `
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
      <input id="pickup-filter-input" placeholder="Cauta oras/judet..." style="flex:1;padding:6px 8px;border:1px solid #ddd;border-radius:6px;">
      <button id="filter-city-btn" class="pu-filter-btn" type="button">Filtreaza dupa oras</button>
      <button id="filter-county-btn" class="pu-filter-btn" type="button">Filtreaza dupa judet</button>
      <button id="filter-clear-btn" class="pu-filter-btn" type="button">Clear</button>
    </div>
  `;
  const pickupListEl = leftCol.querySelector('#pickup-list');
  leftCol.insertBefore(ctrls, pickupListEl);

  // bind butoane si input (safety: nu le lega de mai multe ori)
  const input = document.getElementById('pickup-filter-input');
  const fCity = document.getElementById('filter-city-btn');
  const fCounty = document.getElementById('filter-county-btn');
  const fClear = document.getElementById('filter-clear-btn');

  if(input && !input.dataset.bound){
    input.dataset.bound = '1';
    // permite filtrare rapida la Enter
    input.addEventListener('keydown', (e) => {
      if(e.key === 'Enter'){
        const val = input.value.trim();
        // default: cauta in address (fallback)
        applySimpleFilter('city', val);
      }
    });
  }

  if(fCity && !fCity.dataset.bound) {
    fCity.dataset.bound = '1';
    fCity.addEventListener('click', () => {
      const val = input.value.trim();
      if(!val) { const ask = prompt('Introduceți orașul pentru filtrare (ex: Bucuresti):'); applySimpleFilter('city', ask ? ask.trim() : ''); }
      else applySimpleFilter('city', val);
    });
  }

  if(fCounty && !fCounty.dataset.bound) {
    fCounty.dataset.bound = '1';
    fCounty.addEventListener('click', () => {
      const val = input.value.trim();
      if(!val) { const ask = prompt('Introduceți județul pentru filtrare (ex: Ilfov):'); applySimpleFilter('county', ask ? ask.trim() : ''); }
      else applySimpleFilter('county', val);
    });
  }

  if(fClear && !fClear.dataset.bound) {
    fClear.dataset.bound = '1';
    fClear.addEventListener('click', () => {
      input.value = '';
      applySimpleFilter('', '');
    });
  }
}
	
// Atasare butoane actiuni la fiecare easybox-item (daca nu au)
function attachActionsToItems() {
    $$('#pickup-list .easybox-item').forEach(item => {
        if(item.querySelector('.item-actions')) return; // deja procesat

        const lat = item.dataset.lat;
        const lng = item.dataset.lng;
        const name = item.querySelector('strong')?.textContent || '';
        const address = item.querySelector('span')?.textContent || '';

        // ========== handler: Foloseste acum ==========
        btnUse.addEventListener('click', (ev) => {
            ev.stopPropagation();
            // seteaza input
            const pickupInput = $('#pickup_location');
            if(pickupInput) pickupInput.value = name + ' | ' + address;
            // seteaza shipping method
            const ship = $('#shipping_method');
            if(ship) ship.value = 'pickup';
            // actualizeaza shipping-details (afisare pickup)
            const details = $('#shipping-details');
            if(details) {
                details.innerHTML = `
                    <p>Ridicare personala din:</p>
                    <strong>${name}</strong>
                    <p>${address}</p>
                    <p>95% mai puțin CO₂</p>
                    <p>Produse livrate de eMAG</p>
                    <p>Începând de mâine, după 16:00</p>
                    <div style="margin-top:8px;">
                        <button type="button" class="btn-use" id="open-pickup-modal-after">Alege alt punct</button>
                    </div>
                `;
            }

            // muta numele/adresa in zona template (daca exista)
            const easyNameEl = $('#easybox-name');
            const easyAddrEl = $('#easybox-address');
            if(easyNameEl) easyNameEl.textContent = name;
            if(easyAddrEl) easyAddrEl.textContent = address;

            // inchide modalul
            const pickupModal = $('#pickup-modal');
            if(pickupModal) pickupModal.style.display = 'none';

            // daca exista buton Alege alt punct in details, legam click care redeschide modal
            setTimeout(()=> {
                const reopen = document.getElementById('open-pickup-modal-after');
                if(reopen) reopen.addEventListener('click', () => {
                    const pm = $('#pickup-modal');
                    if(pm) pm.style.display = 'flex';
                });
            }, 50);
        });

        // ========== handler: Salveaza ca favorit ==========
        btnSave.addEventListener('click', (ev) => {
            ev.stopPropagation();
            // ia favorite din localStorage
            let fav = [];
            try { fav = JSON.parse(localStorage.getItem('fav_easyboxes') || '[]'); } catch(e) { fav = []; }
            // verifica duplicate
            const exists = fav.find(f => f.name === name && f.address === address);
            if(!exists) {
                fav.push({ name, address, lat, lng, saved_at: Date.now() });
                localStorage.setItem('fav_easyboxes', JSON.stringify(fav));
                // feedback vizual
                btnSave.textContent = 'Salvat ✓';
                btnSave.style.background = '#0074d9';
                btnSave.style.color = '#fff';
                btnSave.disabled = true;
            } else {
                // deja salvat -> toggle remove
                fav = fav.filter(f => !(f.name === name && f.address === address));
                localStorage.setItem('fav_easyboxes', JSON.stringify(fav));
                btnSave.textContent = 'Salveaza ca punct favorit';
                btnSave.style.background = '';
                btnSave.style.color = '#0074d9';
                btnSave.disabled = false;
            }
        });

        // Optional: click pe item centra harta in modal (comportament suplimentar)
        item.addEventListener('click', () => {
            // simuleaza "Foloseste acum" - doar seta preview si nu inchide modal
            const easyNameEl = $('#easybox-name');
            const easyAddrEl = $('#easybox-address');
            if(easyNameEl) easyNameEl.textContent = name;
            if(easyAddrEl) easyAddrEl.textContent = address;
        });
    });
}

// Filtreaza lista dupa substring (city/judet)
// helper: normalizeaza (sterge diacritice, trimite la lowercase)
function normalizeForSearch(s){
  if(!s) return '';
  return s.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

// Filtreaza lista dupa city / county
function applySimpleFilter(type, value) {
  // daca value gol -> afiseaza tot
  if(!value) {
    $$('#pickup-list .easybox-item').forEach(it => it.style.display = '');
    return;
  }
  const q = normalizeForSearch(value);
  $$('#pickup-list .easybox-item').forEach(it => {
    const city = normalizeForSearch(it.dataset.city || '');
    const county = normalizeForSearch(it.dataset.county || '');
    const addr = normalizeForSearch(it.dataset.address || '');
    const name = normalizeForSearch(it.dataset.name || '');

    let target = '';
    if(type === 'city') target = city || addr || name;
    else if(type === 'county') target = county || addr || name;
    else target = addr + ' ' + name;

    if(target.indexOf(q) !== -1) it.style.display = '';
    else it.style.display = 'none';
  });
}
// Init: adauga controale si ataseaza actiuni la deschiderea modalului
function initPickupModalEnhancements(){
    ensureFilterControls();

    // legare butoane filtre
    const fCity = document.getElementById('filter-city-btn');
    const fCounty = document.getElementById('filter-county-btn');
    if(fCity && !fCity.dataset.bound) {
        fCity.dataset.bound = '1';
        fCity.addEventListener('click', () => {
            const val = prompt('Introduceți orașul pentru filtrare (ex: Bucuresti):');
            applySimpleFilter('city', val ? val.trim() : '');
        });
    }
    if(fCounty && !fCounty.dataset.bound) {
        fCounty.dataset.bound = '1';
        fCounty.addEventListener('click', () => {
            const val = prompt('Introduceți județul pentru filtrare (ex: Ilfov):');
            applySimpleFilter('county', val ? val.trim() : '');
        });
    }

}

// cand modalul se deschide (butonul change-pickup-btn), pregatim elementele
const changePickupBtn = document.getElementById('change-pickup-btn');
if(changePickupBtn) {
    changePickupBtn.addEventListener('click', () => {
        // asiguram ca lista si actiunile exista
        setTimeout(()=> {
            initPickupModalEnhancements();

            // initializeaza hartile din modal (daca ai initializat deja map in codul tau)
            // codul tau anterior creeaza markers cand modalul se deschide
        }, 60);
    });
}

// La incarcarea paginii, pregatesc item-urile daca modalul e deja populat
document.addEventListener('DOMContentLoaded', () => {
    // asiguram actiuni pentru item-urile statice (daca modalul e deja in DOM)
    initPickupModalEnhancements();

    // buton back-to-list deja existent -> pe click inchide modal
    const back = document.getElementById('back-to-list');
    if(back) back.addEventListener('click', () => {
        const pm = document.getElementById('pickup-modal');
        if(pm) pm.style.display = 'none';
    });

    // close icon
    const closePm = document.getElementById('close-pickup-modal');
    if(closePm) closePm.addEventListener('click', () => {
        const pm = document.getElementById('pickup-modal');
        if(pm) pm.style.display = 'none';
    });

    // daca exista favorite salvate, marcheaza butoanele
    try {
        const fav = JSON.parse(localStorage.getItem('fav_easyboxes') || '[]');
        if(fav.length) {
            $$('#pickup-list .easybox-item').forEach(item => {
                const name = item.querySelector('strong')?.textContent || '';
                const addr = item.querySelector('span')?.textContent || '';
                const exists = fav.find(f => f.name === name && f.address === addr);
                if(exists) {
                    // daca item are deja buton salvat, marcam
                    const btn = item.querySelector('.btn-save');
                    if(btn) {
                        btn.textContent = 'Salvat ✓';
                        btn.style.background = '#0074d9';
                        btn.style.color = '#fff';
                        btn.disabled = true;
                    }
                }
            });
        }
    } catch(e){ /* ignore */ }
});
})();

// =======================
// VARIABILE GLOBALE
// =======================
let pageMap = null;
let modalMap = null;

let pageMarkers = [];
let modalMarkers = [];

let pageMapInitialized = false;
let modalMapInitialized = false;

let userLocationMarker = null; // << OBLIGATORIU

// =======================
// CREARE HARTA GENERICA
// =======================
function createEasyBoxMap(mapElementId, markersArray) {
    const mapEl = document.getElementById(mapElementId);
    if (!mapEl || !window.google) return null;

    const map = new google.maps.Map(mapEl, {
        center: { lat: 45.9432, lng: 24.9668 },
        zoom: 7,
        gestureHandling: 'greedy'
    });

    document.querySelectorAll('.easybox-item').forEach(div => {
        const lat = parseFloat(div.dataset.lat);
        const lng = parseFloat(div.dataset.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const box = {
            id: div.dataset.id,
            name: div.dataset.name,
            address: div.dataset.address,
            city: div.dataset.city,
            county: div.dataset.county,
            lat,
            lng
        };

        const marker = new google.maps.Marker({
            position: { lat, lng },
            map,
            title: box.name
        });

        marker.addListener('click', () => selectEasyBox(box));

        markersArray.push(marker);
    });

    return map;
}

// =======================
// INIT HARTA PAGINA
// =======================
function initPageMap() {
    if (pageMapInitialized) return;

    pageMap = createEasyBoxMap('pickup-map', pageMarkers);
    if (!pageMap) return;

    pageMapInitialized = true;
}

// =======================
// INIT HARTA MODAL
// =======================
function initModalMap() {
    if (modalMapInitialized) return;

    modalMap = createEasyBoxMap('pickup-map-modal', modalMarkers);
    if (!modalMap) return;

    modalMapInitialized = true;

    // resize corect dupa ce modalul e vizibil
    setTimeout(() => {
        google.maps.event.trigger(modalMap, 'resize');
        if (modalMarkers[0]) {
            modalMap.setCenter(modalMarkers[0].getPosition());
            modalMap.setZoom(12);
        }
    }, 150);
}
function locateUserOnModalMap() {
    if (!modalMap || !navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userPos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            // stergem markerul vechi daca exista
            if (userLocationMarker) {
                userLocationMarker.setMap(null);
            }

            userLocationMarker = new google.maps.Marker({
                position: userPos,
                map: modalMap,
                title: 'Locatia mea',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#4285F4',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2
                }
            });

            modalMap.setCenter(userPos);
            modalMap.setZoom(14);
        },
        (err) => {
            console.warn('Nu s-a putut obtine locatia utilizatorului', err);
        },
        {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 0
        }
    );
}
// =======================
// SELECTIE EASYBOX (DIN ORICARE MAPA)
// =======================
function selectEasyBox(box) {
    document.getElementById('shipping_method').value = 'pickup';
    document.getElementById('pickup_location').value =
        `${box.name} | ${box.address}, ${box.city}, ${box.county}`;

    document.getElementById('pickup-section').style.display = 'block';

    if (pageMap) {
        pageMap.setCenter({ lat: box.lat, lng: box.lng });
        pageMap.setZoom(15);
    }

    if (modalMap) {
        modalMap.setCenter({ lat: box.lat, lng: box.lng });
        modalMap.setZoom(15);
    }

    const details = document.getElementById('shipping-details');
    if (details) {
        details.innerHTML = `
            <p>Ridicare personala din:</p>
            <strong>${box.name}</strong>
            <p>${box.address}, ${box.city}, ${box.county}</p>
            <p>Cost livrare: 0,00 Lei</p>
        `;
    }

    // inchide modalul
    const modal = document.getElementById('pickup-modal');
    if (modal) modal.style.display = 'none';

    try {
        localStorage.setItem('selected_easybox', JSON.stringify(box));
    } catch (e) {}
}

// =======================
// DESCHIDERE PICKUP (PAGINA)
// =======================
document.querySelector('[data-shipping="pickup"]')
    ?.addEventListener('click', () => {
        const section = document.getElementById('pickup-section');
        section.style.display = 'block';

        setTimeout(() => {
            initPageMap();
            google.maps.event.trigger(pageMap, 'resize');
        }, 100);
    });

// =======================
// DESCHIDERE MODAL PICKUP
// =======================
document.addEventListener('click', (e) => {
    if (!e.target.classList.contains('change-pickup-btn')) return;

    const modal = document.getElementById('pickup-modal');
    modal.style.display = 'flex';

    setTimeout(() => {
        initModalMap();
        locateUserOnModalMap(); // 👈 ADAUGAT
    }, 120);
});

// =======================
// INCHIDERE MODAL
// =======================
document.getElementById('close-pickup-modal')
    ?.addEventListener('click', () => {
        document.getElementById('pickup-modal').style.display = 'none';
    });
// === Fix deschidere/inchidere "Adauga Adresa Noua" ===
document.addEventListener('click', function(e) {
  // deschidere: butonul din modalul de adrese sau orice selector comun
  const openBtn = e.target.closest('#add-new-address, .add-new-address, #open-new-address-btn, .open-new-address-btn');
  if (openBtn) {
    e.preventDefault();

    const addressModal = document.getElementById('address-modal');
    const newModal = document.getElementById('new-address-modal');

    // inchide modalul de selectie adrese daca e deschis
    if (addressModal && addressModal.style.display !== 'none') addressModal.style.display = 'none';

    if (!newModal) {
      console.warn('new-address-modal nu exista in DOM');
      return;
    }

    newModal.style.display = 'flex';
    newModal.setAttribute('aria-hidden', 'false');

    const first = newModal.querySelector('input, textarea, select');
    if (first) first.focus();

    return;
  }

  // inchidere prin X sau butoane cu clase similare
  const closeBtn = e.target.closest('#close-new-address, .close-new-address, #close-modal, .close-modal, .close-x');
  if (closeBtn) {
    // gasim cel mai apropiat container modal
    const modal = e.target.closest('.address-modal') || document.getElementById('new-address-modal') || document.getElementById('address-modal');
    if (modal) {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
    return;
  }

  // inchidere la click pe overlay (click direct pe containerul modal)
  const overlay = e.target.closest('#new-address-modal, #address-modal, #pickup-modal, #billing-modal, #save-card-modal');
  if (overlay && overlay.classList && overlay.classList.contains('address-modal') && e.target === overlay) {
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
  }
});

// inchidere la ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    ['new-address-modal','address-modal','pickup-modal','billing-modal','save-card-modal'].forEach(id => {
      const m = document.getElementById(id);
      if (m && m.style.display && m.style.display !== 'none') {
        m.style.display = 'none';
        m.setAttribute('aria-hidden', 'true');
      }
    });
  }
});
// =======================
// INCHIDERE MODAL
// =======================
</script>

<script
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCnN5ZP5FFsaHPXxc16keDSRcTb0I0v58E&callback=initEasyBoxMap"
  async
  defer>
</script>

</body>
</html>