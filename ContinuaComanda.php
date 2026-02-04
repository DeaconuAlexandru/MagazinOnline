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

if (!$user) die("User inexistent");

// Preluare adrese shipping
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

    try {
        $pdo->beginTransaction();

        /* =========================
           Calcul total final + taxe livrare
        ========================== */
        $shipping_cost = ($shipping_method === 'easybox') ? 12.99 : 25.00;
        $payment_fee   = ($payment_method === 'delivery') ? 10.00 : 0.00;
        $order_total   = $total + $shipping_cost + $payment_fee;
        $status        = ($payment_method === 'online') ? 'pending_payment' : 'processing';

        /* =========================
           Date livrare
        ========================== */
        if ($shipping_method === 'easybox') {
            $easybox_id = (int)($_POST['easybox_id'] ?? 0);
            if ($easybox_id <= 0) throw new Exception("EasyBox invalid");

            $stmt = $pdo->prepare("SELECT county, city, address FROM easyboxes WHERE id = ? AND active = 1");
            $stmt->execute([$easybox_id]);
            $box = $stmt->fetch();
            if (!$box) throw new Exception("EasyBox inexistent");

            $shipping_name    = 'Ridicare EasyBox';
            $shipping_phone   = $user['phone'];
            $shipping_address = "{$box['county']}, {$box['city']}, {$box['address']}";
        } else {
            $address = $addresses[0] ?? null;
            if ($address) {
                $shipping_name    = $address['name'];
                $shipping_phone   = $address['phone'];
                $shipping_address = "{$address['county']}, {$address['city']}, {$address['address_line']}";
            } else {
                $shipping_name    = $user['name'];
                $shipping_phone   = $user['phone'];
                $county           = 'Valcea';
                $city             = 'Bungetani';
                $address_line     = 'Sat Milesti';
                $shipping_address = "$county, $city, $address_line";
            }
            $easybox_id = null;
        }

        /* =========================
           Inserare comanda
        ========================== */
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (user_id, total, shipping_method, easybox_id, payment_method,
             shipping_name, shipping_phone, shipping_address, status, shipping_cost, payment_fee, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $order_total,
            $shipping_method,
            $easybox_id,
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
           Inserare items + actualizare stoc
        ========================== */
        $stmtItem  = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($cart as $item) {
            $product_id = (int)$item['id'];
            $qty        = max(1, (int)$item['qty']);
            $price      = (float)$item['price']; // folosim prețul din sesiune

            $stmtItem->execute([$order_id, $product_id, $qty, $price]);
            $stmtStock->execute([$qty, $product_id, $qty]);

            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Nu s-a putut actualiza stocul pentru produs $product_id");
            }
        }

        $pdo->commit();

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
            <input type="hidden" id="shipping-cost-hidden" value="<?= $shippingCost ?? 25.00 ?>">


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

        <div id="shipping-details"></div>

        <!-- Templates pentru JS -->
        <div id="courier-template" style="display:none">
            <p>Livrare prin curier la:</p>
            <strong><?= htmlspecialchars($addresses['shipping']['name'] ?? $user['name']) ?>, <?= htmlspecialchars($addresses['shipping']['phone'] ?? $user['phone']) ?></strong>
            <p><?= htmlspecialchars($addresses['shipping']['address'] ?? '') ?></p>
            <p>Cost livrare: <?= number_format(25, 2) ?> Lei</p>
            <button type="button" class="change-address-btn" onclick="document.getElementById('address-modal').style.display='flex'">Alege alta adresa</button>
        </div>

        <div id="pickup-template" style="display:none">
            <p>Ridicare personală la:</p>
            <strong><?= htmlspecialchars($easyboxes[0]['name'] ?? 'EasyBox Panvil Elegant') ?></strong>
            <p><?= htmlspecialchars($easyboxes[0]['address'] ?? 'Str. Bunavestire, Nr. 1') ?></p>
            <p>Cost livrare: <?= number_format(12.99, 2) ?> Lei</p>
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
                <div class="payment-option <?= $selected_payment === 'online' ? 'active' : '' ?>"
                     data-pay="online" id="pay-online" role="button" tabindex="0" aria-pressed="<?= $selected_payment === 'online' ? 'true' : 'false' ?>">
                    <h4>Card online</h4>
                    <p>Plătești imediat, fără costuri suplimentare.</p>
                    <p>Salvează cardul în contul tău Shergei Covoare și bucură-te de beneficii.</p>
                    <div class="payment-badge" data-for="online">Detalii</div>
                </div>

                <div class="payment-option <?= $selected_payment === 'delivery' ? 'active' : '' ?>"
                     data-pay="delivery" id="pay-delivery" role="button" tabindex="0" aria-pressed="<?= $selected_payment === 'delivery' ? 'true' : 'false' ?>">
                    <h4>Ramburs Courier</h4>
                    <p>Poi plăti doar cu cardul în momentul ridicării produselor.</p>
                    <p>10,00 Lei reprezintă costul pentru procesarea plății la livrare. Plata online cu cardul este gratuită.</p>
                </div>
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

<!-- Mini-modal pentru Adauga Adresa Noua -->
<div id="new-address-modal" class="address-modal">
    <div class="modal-content">
        <span id="close-new-address" class="close-modal">&times;</span>
        <h3>Adauga adresa noua</h3>

        <form id="addressForm">
            <label>Persoana de contact</label>
            <input type="text" name="contact_name" placeholder="Nume" required>

            <label>Numar de telefon</label>
            <input type="text" name="contact_phone" placeholder="Telefon" required>

            <label>Judet</label>
            <input type="text" name="county" placeholder="Judet" required>

            <label>Localitate</label>
            <input type="text" name="city" placeholder="Oras" required>

            <label>Adresa</label>
            <input type="text" name="address_line" placeholder="Strada" required>

            <button type="submit">Adaugă adresă</button>
        </form>
    </div>
</div>

<!-- Ridicare personala cu harta -->
<div id="pickup-section" class="section" style="display:none;">
    <h3>Ridicare personală</h3>
    <div id="pickup-map" style="width:100%; height:400px; border:1px solid #ccc;"></div>
    <p id="pickup-message"></p>
    <input type="hidden" name="pickup_location" id="pickup_location" value="<?= htmlspecialchars($pickup_location ?? '') ?>">
	<div class="easybox-logo">
    <img src="easybox.png" alt="EasyBox">
    <span>easybox</span>
</div>
</div>
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

// Click pe card EasyBox
document.querySelector('.card-option[data-shipping="pickup"]')
    .addEventListener('click', () => {
        getUserLocation(showEasyBoxPickup);
    });
// -------------------------
// Event listener card-uri
document.querySelectorAll('.card-option').forEach(card => {
    card.addEventListener('click', () => {
        if(card.classList.contains('disabled')) return;

        document.querySelectorAll('.card-option').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        document.getElementById('shipping_method').value = card.dataset.shipping;

        if(card.dataset.shipping === 'pickup'){
            getUserLocation(showPickupOptions);
        } else {
            updateShippingDetails('courier');
            const pickupSection = document.getElementById('pickup-section');
            if(pickupSection) pickupSection.style.display = 'none';
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

// -------------------------
// -------------------------
// -------------------------
// Modale adresa
const addNewBtn = document.getElementById('add-new-address');
if(addNewBtn) addNewBtn.addEventListener('click', () => {
    document.getElementById('new-address-modal').style.display = 'flex';
});

const closeNewBtn = document.getElementById('close-new-address');
if(closeNewBtn) closeNewBtn.addEventListener('click', () => {
    document.getElementById('new-address-modal').style.display = 'none';
});

const closeModalBtn = document.getElementById('close-modal');
if(closeModalBtn) closeModalBtn.addEventListener('click', () => {
    document.getElementById('address-modal').style.display = 'none';
});

// -------------------------
// Load pagina si verificare EasyBox
window.addEventListener('DOMContentLoaded', () => {
    const pickupCard = document.querySelector('.card-option[data-shipping="pickup"]');
    if(!pickupCard) return;

    pickupCard.classList.add('disabled');
    pickupCard.title = "Se verifică disponibilitatea EasyBox-urilor";

    getUserLocation(pos => {
        if(!pos) return;
        const nearby = easyBoxes.filter(box => getDistanceKm(pos.lat, pos.lng, box.lat, box.lng) <= 50);
        if(nearby.length > 0){
            pickupCard.classList.remove('disabled');
            pickupCard.title = '';
            pickupCard.style.cursor = 'pointer';
        }
    });
});

// -------------------------
// Selectare adrese existente
document.querySelectorAll('.address-item.selectable').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.address-item.selectable')
            .forEach(i => i.classList.remove('active'));
        item.classList.add('active');

        const name = item.dataset.name;
        const phone = item.dataset.phone;
        const address = item.dataset.address;

        lastSelectedCourierAddress = { name, phone, address }; // salvează

        document.getElementById('shipping-details').innerHTML = `
            <p>Livrare la:</p>
            <strong>${name} - ${phone}</strong>
            <p>${address}</p>
            <p>Cost livrare: 25,00 Lei</p>
        `;

        document.getElementById('address-modal').style.display = 'none';
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
        document.addEventListener('DOMContentLoaded', updateOrderTotal);
// Adaugare adresa AJAX
// ==========================
const addressForm = document.getElementById('addressForm');
if(addressForm){
    addressForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);

        fetch('add_address_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(!data.success){ 
                alert(data.error || 'Eroare server'); 
                return; 
            }

            const a = data.address;

            // Creeaza div pentru noua adresa
            const div = document.createElement('div');
            div.className = 'address-item selectable active';
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

            // Dezactiveaza toate celelalte adrese
            document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));

            // Click pe noua adresa
            div.addEventListener('click', () => {
                document.querySelectorAll('.address-item.selectable').forEach(i => i.classList.remove('active'));
                div.classList.add('active');
                setShippingDetails(a.name, a.phone, `${a.county}, ${a.city}, ${a.address_line}`);
                document.getElementById('address-modal').style.display = 'none';
            });

            // Adauga noua adresa in lista
            const addressList = document.querySelector('.address-list');
            addressList.appendChild(div);

            // Selecteaza automat noua adresa
            div.click();

            // Inchide modal si reseteaza form
            document.getElementById('new-address-modal').style.display = 'none';
            this.reset();
        })
        .catch(err => { 
            console.error(err); 
            alert('Eroare server'); 
        });
    });
}

// ==========================
// Selectare metoda livrare (card-uri)
// ==========================
document.querySelectorAll('.card-option').forEach(card => {
    card.addEventListener('click', () => {
        if(card.classList.contains('disabled')) return;

        document.querySelectorAll('.card-option').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        document.getElementById('shipping_method').value = card.dataset.shipping;

        const pickupSection = document.getElementById('pickup-section');

        if(card.dataset.shipping === 'pickup'){
            if(pickupSection) pickupSection.style.display = 'block';
            getUserLocation(showEasyBoxPickup);
        } else {
            if(pickupSection) pickupSection.style.display = 'none';
            // Foloseste template-ul curier pentru detalii
            const courierTemplate = document.getElementById('courier-template').innerHTML;
            document.getElementById('shipping-details').innerHTML = courierTemplate;
        }
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
                <strong>${name} - ${phone}</strong><br>
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
</script>
</body>
</html>
