<?php
// FinalizeazaComanda.php (versiune reparata)
// ATENTIE: in productie dezactiveaza display_errors si logheaza erorile in fisier.

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
   Preluare date user si adresa
========================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die("User inexistent");

$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type = 'shipping' LIMIT 1");
$stmt->execute([$user_id]);
$addr = $stmt->fetch();

$shipping_name    = $addr['name'] ?? $user['name'] ?? '';
$shipping_phone   = $addr['phone'] ?? $user['phone'] ?? '';
$shipping_address = $addr ? ($addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line']) : 'Adresa necunoscuta';

/* =========================
   DACA POST = plata la livrare -> inseram comanda in baza de date
   (tratare doar la POST pentru a evita inserari accidentale la GET)
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
        // Afisare mesaj de succes pentru plata la livrare
        echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><title>Comanda</title>
              <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
              <div class="container py-5"><div class="card p-4 text-center"><h4>Comanda procesata cu succes</h4>
              <p>Curierul va prelua coletul in cel mai scurt timp.</p><a href="Acasa.php" class="btn btn-success">Pagina principala</a></div></div></body></html>';
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        // in dev afisam eroarea, in productie logheaza si arata mesaj generic
        die("Eroare comanda: " . $e->getMessage());
    }
}

/* =========================
   DACA POST = plata online -> cream sesiunea Stripe
========================= */
if ($is_post && ($payment_method === 'online' || ($_POST['payment_method'] ?? '') === 'online')) {
    // seteaza cheia ta secreta
    \Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

    $pay_shipping = !empty($_POST['pay_shipping']);
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

    // construire success/cancel URL fara spatii (encode)
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Daca folderul/calea contine spatii, inlocuieste cu %20 OR, mai bine, redenumeste folderul sa nu contina spatii.
    $basePath = str_replace(' ', '%20', 'Firma Lui Sherghei De Covoare'); // ajusteaza daca e necesar
    $baseUrl = $proto . '://' . $host . '/' . $basePath;

    $success_url = $baseUrl . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}';
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
                'shipping_method' => $shipping_method,
                'shipping_cost' => $shipping_cost,
                'payment_fee' => $payment_fee,
                'order_subtotal' => $order_subtotal,
                'pay_shipping' => $pay_shipping ? 1 : 0,
            ],
        ]);

        header("Location: " . $session->url);
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Afiseaza eroarea Stripe (pentru depanare)
        // In productie, scrie intr-un fisier de log si afiseaza un mesaj generic
        $err = $e->getMessage();
        die("Eroare Stripe: " . htmlspecialchars($err));
    } catch (Exception $e) {
        die("Eroare: " . htmlspecialchars($e->getMessage()));
    }
}

/* =========================
   AFISARE UI (cand nu e POST de confirmare)
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
                Plateste online si transportul (<?= number_format($shipping_cost,2) ?> lei)
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
    const total = subtotal + (checkbox.checked ? shipping : 0);
    totalEl.textContent = total.toFixed(2);
    payShippingInput.value = checkbox.checked ? 1 : 0;
}

function updateStripeVisibility() {
    if (radioOnline.checked) {
        stripeCard.style.display = 'block';
        paymentMethodInput.value = 'online';
    } else {
        stripeCard.style.display = 'none';
        paymentMethodInput.value = 'delivery';
    }
}

updateTotalAndHidden();
updateStripeVisibility();

checkbox.addEventListener('change', updateTotalAndHidden);
radioDelivery.addEventListener('change', () => {
    updateStripeVisibility();
    updateTotalAndHidden();
});
radioOnline.addEventListener('change', () => {
    updateStripeVisibility();
    updateTotalAndHidden();
});
</script>

</body>
</html>
