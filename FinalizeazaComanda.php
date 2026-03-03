<?php
// FinalizeazaComanda.php - forțare alegere transport înainte de redirecționare la Stripe
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php'; // Stripe PHP SDK

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
    die("Conexiune esuata: " . $e->getMessage());
}
/* VERIFICARI DE BAZA */
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

/* CITIM DATE DIN REQUEST */
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$shipping_method = trim($_POST['shipping_method'] ?? $_GET['shipping_method'] ?? 'courier');
$payment_method  = $_POST['payment_method'] ?? $_GET['payment_method'] ?? 'delivery';
$shipping_cost   = floatval(str_replace(',', '.', ($_POST['shipping_cost'] ?? '25.00')));
$order_subtotal  = floatval(str_replace(',', '.', ($_POST['order_subtotal'] ?? (string)$total)));
$payment_fee     = ($payment_method === 'delivery') ? 10.00 : 0.00;
$order_total     = round($order_subtotal + $shipping_cost + $payment_fee, 2);

/* PRELUARE DATE USER SI ADRESE */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die("User inexistent");

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type = 'shipping' ORDER BY id ASC");
$stmt->execute([$user_id]);
$all_addresses = $stmt->fetchAll();

$address_id = (int)($_POST['shipping_address_id'] ?? $_GET['shipping_address_id'] ?? ($all_addresses[0]['id'] ?? 0));

$addr = null;
foreach ($all_addresses as $a) {
    if ($a['id'] == $address_id) { $addr = $a; break; }
}
if (!$addr && !empty($all_addresses)) { $addr = $all_addresses[0]; $address_id = $addr['id']; }

$shipping_name    = $addr['name'] ?? $user['name'] ?? '';
$shipping_phone   = $addr['phone'] ?? $user['phone'] ?? '';
$shipping_address = $addr ? ($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) : 'Adresa necunoscuta';

$DEFAULT_SHIPPING_COST = 25.00;

/* ========== HANDLING PLATA LA LIVRARE ========== */
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
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmtItem->execute([$order_id, (int)$item['id'], (int)$item['qty'], (float)$item['price']]);
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

/* ========== HANDLING PLATA ONLINE ========== */
/* Regula noua: daca se cere sesiune Stripe, cerem obligatoriu campul shipping_choice_made = '1' */
if ($is_post && ($payment_method === 'online' || ($_POST['payment_method'] ?? '') === 'online')) {
    // verificare alegere transport
    $shipping_choice_made = $_POST['shipping_choice_made'] ?? '0';
    if ($shipping_choice_made !== '1' && !isset($_POST['pay_shipping'])) {
        // utilizator incearca bypass, redirectionam inapoi cu mesaj
        header("Location: FinalizeazaComanda.php?error=select_shipping");
        exit;
    }

    \Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

    $pay_shipping = false;
    if (isset($_POST['pay_shipping'])) {
        $pay_shipping = ((string)$_POST['pay_shipping'] === '1');
    } else {
        $pay_shipping = !empty($_POST['payShipping']) || !empty($_POST['pay_ship']);
    }

    $db_shipping_cost = $pay_shipping ? $shipping_cost : 0.00;
    $db_order_total = round($order_subtotal + $db_shipping_cost + 0.00, 2);

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
            0.00
        ]);
        $order_id = $pdo->lastInsertId();
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmtItem->execute([$order_id, (int)$item['id'], (int)$item['qty'], (float)$item['price']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Eroare creare comanda initiala: " . $e->getMessage());
    }

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);

    $candidates = ['stripe_success.php', 'stripe_succes.php'];
    $successFile = 'stripe_success.php';
    foreach ($candidates as $c) {
        if (file_exists(__DIR__ . '/' . $c)) { $successFile = $c; break; }
    }

    $success_url = $baseUrl . '/' . $successFile . '?session_id={CHECKOUT_SESSION_ID}';
    $cancel_url  = $baseUrl . '/FinalizeazaComanda.php';

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

/* AFISARE UI, eventuala eroare */
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>Finalizeaza comanda</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { padding:30px; background:#f7f7f7; font-family:Arial,sans-serif; }
.card { border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.pay-method { margin-bottom:18px; }
.small-muted { color:#666; font-size:0.95rem; }
.big-amount { font-size:40px; font-weight:700; }
.ship-box { display:flex; align-items:center; gap:12px; padding:14px; border-radius:10px; border:1px solid #e2e6ee; background: linear-gradient(180deg, #fff, #fbfdff); box-shadow: 0 4px 20px rgba(17,24,39,0.04); margin-top:12px; }
.ship-checkbox { width:22px; height:22px; border-radius:6px; border:1px solid #cbd5e1; display:flex; align-items:center; justify-content:center; cursor:pointer; background:#fff; }
.ship-checkbox.checked { background:#0ea5a4; border-color:#0ea5a4; color:#fff; }
.ship-label { font-weight:600; }
.total-row { display:flex; justify-content:space-between; align-items:center; margin-top:18px; }
.btn-green { background:#10b981; border:none; color:#fff; }
.muted-note { font-size:0.95rem; color:#555; margin-top:12px; }
</style>
</head>
<body>

<div class="container">
  <?php if ($error === 'select_shipping'): ?>
    <div class="alert alert-warning">Te rog confirmă dacă plătești transportul sau nu înainte de a continua spre plată.</div>
  <?php endif; ?>

  <div class="row gy-3">
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
            <input class="form-check-input" type="checkbox" id="payShippingLeft">
            <label class="form-check-label" for="payShippingLeft">Doriți să plătiți transportul acum, <?= number_format($DEFAULT_SHIPPING_COST,2) ?> lei? Sau îl plătiți la primire.</label>
        </div>

        <p>Total: <strong><span id="totalLeft"><?= number_format($order_subtotal,2) ?></span> lei</strong></p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card p-4 text-center" id="stripeCard">
        <h5>Plata securizata Stripe</h5>
        <p class="text-muted">Vei fi redirectionat catre Stripe unde vei introduce detaliile cardului.</p>

        <div class="mt-3">
          <div class="small-muted">Subtotal produse</div>
          <div id="subtotalBig" class="big-amount"><?= number_format($order_subtotal, 2, '.', '') ?> <span style="font-size:18px;font-weight:600;">RON</span></div>

          <div class="ship-box" id="shipBox" role="button" tabindex="0" aria-pressed="false">
            <div id="shipCheckbox" class="ship-checkbox" title="Plătește transportul"></div>
            <div>
              <div class="ship-label">Vrei să plătești courierul?</div>
              <div class="small-muted">Cost transport: <?= number_format($DEFAULT_SHIPPING_COST, 2) ?> lei</div>
            </div>
          </div>

          <div class="total-row">
            <div class="small-muted">Total estimat</div>
            <div style="text-align:right;">
              <div id="totalRight" style="font-size:28px; font-weight:700;"><?= number_format($order_subtotal, 2, '.', '') ?> RON</div>
            </div>
          </div>

          <div class="muted-note">
            <span>Transport inclus: <strong id="shipIncludedText">Nu</strong></span>
            <div class="small-muted" style="display:block; margin-top:8px;">Bifarea acestei opțiuni modifică totalul înainte de plata online. Dacă plătești, costul va fi inclus in sesiunea Stripe.</div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <a href="Acasa.php" class="btn btn-success">Mergi la pagina principală</a>

            <form method="POST" id="stripeForm" class="w-100">
                <input type="hidden" name="payment_method" id="paymentMethodInput" value="online">
                <input type="hidden" name="pay_shipping" id="payShippingInput" value="0">
                <input type="hidden" name="shipping_cost" id="shippingCostInput" value="<?= htmlspecialchars($shipping_cost) ?>">
                <input type="hidden" name="order_subtotal" id="orderSubtotalInput" value="<?= htmlspecialchars($order_subtotal) ?>">
                <input type="hidden" name="shipping_address_id" value="<?= htmlspecialchars($address_id) ?>">
                <input type="hidden" name="shipping_choice_made" id="shippingChoiceMade" value="0">
                <button type="submit" class="btn btn-green w-100">Continua spre plata</button>
            </form>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

<script>
const subtotal = <?= json_encode($order_subtotal) ?> * 1;
const defaultShip = <?= json_encode($DEFAULT_SHIPPING_COST) ?> * 1;

const checkboxLeft = document.getElementById('payShippingLeft');
const totalLeftEl = document.getElementById('totalLeft');
const payShippingInput = document.getElementById('payShippingInput');
const shippingCostInput = document.getElementById('shippingCostInput');
const orderSubtotalInput = document.getElementById('orderSubtotalInput');
const shippingChoiceMade = document.getElementById('shippingChoiceMade');
const stripeForm = document.getElementById('stripeForm');

const stripeCard = document.getElementById('stripeCard');
const paymentMethodInput = document.getElementById('paymentMethodInput');
const radioDelivery = document.getElementById('pm_delivery');
const radioOnline = document.getElementById('pm_online');

const shipCheckbox = document.getElementById('shipCheckbox');
const shipBox = document.getElementById('shipBox');
const shipIncludedText = document.getElementById('shipIncludedText');
const totalRightEl = document.getElementById('totalRight');
const subtotalBig = document.getElementById('subtotalBig');

let shipChecked = false;

// marcăm că utilizatorul a luat o decizie despre transport când interacționează
function markShippingChoice() {
    if (shippingChoiceMade) shippingChoiceMade.value = '1';
}

// init din checkbox daca are stare
if (checkboxLeft) { shipChecked = checkboxLeft.checked; if (shipChecked) markShippingChoice(); }

function computeTotal() {
    return subtotal + (shipChecked ? defaultShip : 0);
}

function updateAllDisplays() {
    const total = computeTotal();
    if (totalLeftEl) totalLeftEl.textContent = total.toFixed(2);
    if (totalRightEl) totalRightEl.textContent = total.toFixed(2) + ' RON';
    if (subtotalBig) subtotalBig.textContent = subtotal.toFixed(2) + ' RON';
    if (shipIncludedText) shipIncludedText.textContent = shipChecked ? 'Da' : 'Nu';
    if (shipCheckbox) {
        if (shipChecked) shipCheckbox.classList.add('checked'), shipCheckbox.textContent = '✓';
        else shipCheckbox.classList.remove('checked'), shipCheckbox.textContent = '';
    }
    if (payShippingInput) payShippingInput.value = shipChecked ? '1' : '0';
    if (shippingCostInput) shippingCostInput.value = shipChecked ? defaultShip.toFixed(2) : '0.00';
    if (orderSubtotalInput) orderSubtotalInput.value = subtotal.toFixed(2);
}

function toggleShip() {
    shipChecked = !shipChecked;
    markShippingChoice();
    updateAllDisplays();
}

if (checkboxLeft) {
    checkboxLeft.addEventListener('change', function(){
        shipChecked = checkboxLeft.checked;
        markShippingChoice();
        updateAllDisplays();
    });
}

if (shipBox) {
    shipBox.addEventListener('click', function(){
        toggleShip();
        if (checkboxLeft) checkboxLeft.checked = shipChecked;
    });
    shipBox.addEventListener('keyup', function(e){ if (e.key === 'Enter' || e.key === ' ') toggleShip(); });
}

if (radioDelivery) radioDelivery.addEventListener('change', () => { updateStripeVisibility(); });
if (radioOnline) radioOnline.addEventListener('change', () => { updateStripeVisibility(); });

function updateStripeVisibility() {
    if (radioOnline && radioOnline.checked) {
        stripeCard.style.display = 'block';
        paymentMethodInput.value = 'online';
    } else {
        stripeCard.style.display = 'none';
        paymentMethodInput.value = 'delivery';
    }
}

// interceptam submit pentru a verifica alegerea transport
if (stripeForm) {
    stripeForm.addEventListener('submit', function(e){
        // setam hidden inputs
        if (payShippingInput) payShippingInput.value = shipChecked ? '1' : '0';
        if (shippingCostInput) shippingCostInput.value = shipChecked ? defaultShip.toFixed(2) : '0.00';
        if (orderSubtotalInput) orderSubtotalInput.value = subtotal.toFixed(2);
        if (shippingChoiceMade) {
            // daca nu a luat o decizie si utilizator trimite la Stripe, blocam si afisam alerta
            if (shippingChoiceMade.value !== '1') {
                e.preventDefault();
                alert('Te rog confirmă dacă plătești transportul sau nu înainte de a continua spre plată.');
                return false;
            }
        }
        // lasam formularul sa fie trimis
    });
}

updateStripeVisibility();
updateAllDisplays();
</script>

</body>
</html>