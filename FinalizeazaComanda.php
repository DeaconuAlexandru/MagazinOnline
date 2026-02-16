<?php

// FinalizeazaComanda.php (actualizat pentru adrese multiple + creare comanda pending pentru Stripe)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php'; // Stripe PHP SDK

/* =========================
   CONFIG BAZA DE DATE
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
   VERIFICARI DE BAZA
========================= */
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'ContinuaComanda.php';
    header("Location: Login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cart    = $_SESSION['checkout_cart'] ?? [];
$total   = floatval($_SESSION['checkout_total'] ?? 0.0);

if (empty($cart)) {
    $_SESSION['cart_empty_message'] = "Nu ai ales niciun produs.";
    header("Location: ContinuaComanda.php");
    exit;
}

/* =========================
   Citim date din POST/GET
========================= */
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$shipping_method = trim($_POST['shipping_method'] ?? $_GET['shipping_method'] ?? 'courier');
$payment_method  = $_POST['payment_method'] ?? $_GET['payment_method'] ?? 'delivery';
$shipping_cost   = floatval(str_replace(',', '.', ($_POST['shipping_cost'] ?? '25.00')));
$order_subtotal  = floatval(str_replace(',', '.', ($_POST['order_subtotal'] ?? (string)$total)));
$payment_fee     = ($payment_method === 'delivery') ? 10.00 : 0.00;
$order_total     = round($order_subtotal + $shipping_cost + $payment_fee, 2);

/* =========================
   Preluare date user si adrese multiple
========================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die("User inexistent");

// Preluare toate adresele de shipping
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type = 'shipping' ORDER BY id ASC");
$stmt->execute([$user_id]);
$all_addresses = $stmt->fetchAll();

// Determinare adresa selectata
$address_id = (int)($_POST['shipping_address_id'] ?? $_GET['shipping_address_id'] ?? ($all_addresses[0]['id'] ?? 0));

// Gasim adresa selectata
$addr = null;
foreach ($all_addresses as $a) {
    if ($a['id'] == $address_id) {
        $addr = $a;
        break;
    }
}

// fallback: prima adresa daca nu exista selectata
if (!$addr && !empty($all_addresses)) {
    $addr = $all_addresses[0];
    $address_id = $addr['id'];
}

$shipping_name    = $addr['name'] ?? $user['name'] ?? '';
$shipping_phone   = $addr['phone'] ?? $user['phone'] ?? '';
$shipping_address = $addr ? ($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) : 'Adresa necunoscuta';

/* =========================
   POST = plata la livrare (curier)
========================= */
if ($is_post && $payment_method === 'delivery') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO orders
            (user_id, total, shipping_method, payment_method, shipping_name, shipping_phone, shipping_address, status, shipping_cost, payment_fee, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'processing', ?, ?, NOW())
        ");

        $stmt->execute([
            $user_id,
            $order_total,
            $shipping_method,
            $payment_method,
            $shipping_name,
            $shipping_phone,
            $shipping_address,
            $shipping_cost,
            $payment_fee
        ]);

        $order_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($cart as $item) {
            $stmtItem->execute([
                $order_id,
                (int)$item['id'],
                (int)$item['qty'],
                (float)$item['price']
            ]);
        }

        $pdo->commit();
        unset($_SESSION['checkout_cart'], $_SESSION['checkout_total'], $_SESSION['cart']);

        echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><title>Comanda</title>
              <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
              <div class="container py-5"><div class="card p-4 text-center"><h4>Comanda procesata cu succes</h4>
              <p>Curierul va prelua coletul in cel mai scurt timp.</p><a href="Acasa.php" class="btn btn-success">Pagina principala</a></div></div></body></html>';
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Eroare comanda: " . $e->getMessage());
    }
}

/* =========================
   POST = plata online (Stripe)
   Observatie: cream comanda initiala cu status = pending
   si setam shipping_cost in functie de optiunea userului
========================= */
if ($is_post && ($payment_method === 'online' || ($_POST['payment_method'] ?? '') === 'online')) {
    \Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

    // citim daca userul a bifat plata transportului
    $pay_shipping = false;
    if (isset($_POST['pay_shipping'])) {
        // daca folosesti hidden input din formular, valoarea vine '0' sau '1'
        $pay_shipping = ((string)$_POST['pay_shipping'] === '1');
    } else {
        // compatibilitate: daca checkbox era in form si era trimis direct
        $pay_shipping = !empty($_POST['payShipping']) || !empty($_POST['pay_shipping']);
    }

    // shipping cost pentru DB, doar daca user a ales sa plateasca online transportul
    $db_shipping_cost = $pay_shipping ? $shipping_cost : 0.00;

    // recalculam total pentru DB
    $db_order_total = round($order_subtotal + $db_shipping_cost + 0.00, 2); // payment_fee = 0 pentru online in implementarea ta

    $line_items = [];

    foreach ($cart as $item) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'ron',
                'product_data' => ['name' => $item['name']],
                'unit_amount' => intval($item['price'] * 100),
            ],
            'quantity' => $item['qty'],
        ];
    }

    if ($pay_shipping) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'ron',
                'product_data' => ['name' => 'Transport curier'],
                'unit_amount' => intval($shipping_cost * 100),
            ],
            'quantity' => 1,
        ];
    }

    // Creez comanda initiala cu status = pending, cu shipping cost corect
    try {
        $pdo->beginTransaction();

        $stmtCreate = $pdo->prepare("
            INSERT INTO orders
            (user_id, total, shipping_method, payment_method, shipping_name, shipping_phone, shipping_address, status, shipping_cost, payment_fee, created_at)
            VALUES (?, ?, ?, 'online', ?, ?, ?, 'pending', ?, ?, NOW())
        ");

        $stmtCreate->execute([
            $user_id,
            $db_order_total,
            $shipping_method,
            $shipping_name,
            $shipping_phone,
            $shipping_address,
            $db_shipping_cost,
            0.00 // payment_fee pentru online
        ]);

        $order_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($cart as $item) {
            $stmtItem->execute([
                $order_id,
                (int)$item['id'],
                (int)$item['qty'],
                (float)$item['price']
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Eroare creare comanda initiala: " . $e->getMessage());
    }

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // directorul curent al scriptului (ex: '' sau '/folder')
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);

    // Detectam ce fisier de success exista pe server (evita 404)
    $candidates = ['stripe_success.php', 'stripe_succes.php'];
    $successFile = 'stripe_success.php';
    foreach ($candidates as $c) {
        if (file_exists(__DIR__ . '/' . $c)) {
            $successFile = $c;
            break;
        }
    }

    // URL-urile absolute pentru Stripe
    $success_url = $baseUrl . '/' . $successFile . '?session_id={CHECKOUT_SESSION_ID}';
    $cancel_url  = $baseUrl . '/FinalizeazaComanda.php';

    // Debug: logam URL-urile trimise la Stripe (temporar)
    error_log("Stripe success_url: " . $success_url);
    error_log("Stripe cancel_url: " . $cancel_url);

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url'  => $cancel_url,
            'metadata' => [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'shipping_method' => $shipping_method,
                'shipping_cost' => $db_shipping_cost,
                'payment_fee' => 0.00,
                'order_subtotal' => $order_subtotal,
                'pay_shipping' => $pay_shipping ? 1 : 0,
                'shipping_address_id' => $address_id
            ],
        ]);

        header("Location: " . $session->url);
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        die("Eroare Stripe: " . htmlspecialchars($e->getMessage()));
    } catch (Exception $e) {
        die("Eroare: " . htmlspecialchars($e->getMessage()));
    }
}

/* =========================
   AFISARE UI pentru selectare adresa
========================= */
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>Finalizare comanda</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { padding:30px; background:#f7f7f7; font-family:Arial,sans-serif; }
.card { border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.pay-method { margin-bottom:18px; }
.small-muted { color:#666; font-size:0.95rem; }
</style>
</head>
<body>

<div class="container">
  <div class="row gy-3">

    <!-- STANGA: alegere metoda si total -->
    <div class="col-md-6">
      <div class="card p-4">
        <h5>Metoda plata</h5>

        <div class="pay-method">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method_select" id="pm_delivery" value="delivery" <?= $payment_method === 'delivery' ? 'checked' : '' ?>>
            <label class="form-check-label" for="pm_delivery">Plata la livrare (curier)</label>
            <div class="small-muted">Plata se face la primirea coletului.</div>
          </div>

          <div class="form-check mt-2">
            <input class="form-check-input" type="radio" name="payment_method_select" id="pm_online" value="online" <?= $payment_method === 'online' ? 'checked' : '' ?>>
            <label class="form-check-label" for="pm_online">Card online (Stripe)</label>
            <div class="small-muted">Vei fi redirectionat catre Stripe pentru plata securizata.</div>
          </div>
        </div>

        <hr>

        <p>Produse: <strong><?= number_format($order_subtotal,2) ?> lei</strong></p>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="payShipping">
            <label class="form-check-label" for="payShipping">
                Doriți să plătiți transportul acum, 25.00 lei? Sau îl plătiți la primire.
            </label>
        </div>

        <p>
            Total:
            <strong><span id="total"><?= number_format($order_subtotal,2) ?></span> lei</strong>
        </p>
      </div>
    </div>

    <!-- DREAPTA: butoane / info Stripe (afiseaza doar daca e selectat online) -->
    <div class="col-md-6">
      <div class="card p-4 text-center" id="stripeCard">
        <h5>Plata securizata Stripe</h5>
        <p class="text-muted">Vei fi redirectionat catre Stripe unde vei introduce detaliile cardului (numar card, data expirare, CVC, nume).</p>

        <!-- formularul trimite payment_method=online + pay_shipping -->
        <form method="POST" id="stripeForm">
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="online">
            <input type="hidden" name="pay_shipping" id="payShippingInput" value="0">
            <input type="hidden" name="shipping_cost" value="<?= htmlspecialchars($shipping_cost) ?>">
            <input type="hidden" name="order_subtotal" value="<?= htmlspecialchars($order_subtotal) ?>">
            <button class="btn btn-success w-100">Continua spre plata</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
const subtotal = <?= json_encode($order_subtotal) ?> * 1;
const shipping = <?= json_encode($shipping_cost) ?> * 1;

const checkbox = document.getElementById('payShipping');
const totalEl = document.getElementById('total');
const payShippingInput = document.getElementById('payShippingInput');
const stripeCard = document.getElementById('stripeCard');
const paymentMethodInput = document.getElementById('paymentMethodInput');
const radioDelivery = document.getElementById('pm_delivery');
const radioOnline = document.getElementById('pm_online');

function updateTotalAndHidden() {
    const total = subtotal + (checkbox && checkbox.checked ? shipping : 0);
    totalEl.textContent = total.toFixed(2);
    if (payShippingInput) payShippingInput.value = (checkbox && checkbox.checked) ? 1 : 0;
}

function updateStripeVisibility() {
    if (radioOnline && radioOnline.checked) {
        stripeCard.style.display = 'block';
        paymentMethodInput.value = 'online';
    } else {
        stripeCard.style.display = 'none';
        paymentMethodInput.value = 'delivery';
    }
}

if (checkbox) checkbox.addEventListener('change', updateTotalAndHidden);
if (radioDelivery) radioDelivery.addEventListener('change', () => { updateStripeVisibility(); updateTotalAndHidden(); });
if (radioOnline) radioOnline.addEventListener('change', () => { updateStripeVisibility(); updateTotalAndHidden(); });

updateTotalAndHidden();
updateStripeVisibility();
</script>

</body>
</html>
