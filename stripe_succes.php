<?php
// stripe_succes.php
session_start();
require 'vendor/autoload.php';

/* CONFIG BAZA DE DATE */
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Conexiune esuata: " . htmlspecialchars($e->getMessage());
    exit;
}

/* ---------- AJAX endpoint: update_shipping ---------- */
/* Se apeleaza prin POST cu:
     action = update_shipping
     order_id = <id>
     ship = 1 sau 0
   Returneaza JSON { success: true|false, total: "123.45", shipping_cost: "25.00" }
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_shipping') {
    header('Content-Type: application/json; charset=utf-8');

    $order_id_post = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $ship_flag = isset($_POST['ship']) && ((string)$_POST['ship'] === '1');

    if ($order_id_post <= 0) {
        echo json_encode(['success' => false, 'error' => 'order_id invalid']);
        exit;
    }

    // recompute subtotal from order_items
    try {
        $stmtSub = $pdo->prepare("SELECT COALESCE(SUM(price * quantity),0) AS subtotal FROM order_items WHERE order_id = ?");
        $stmtSub->execute([$order_id_post]);
        $row = $stmtSub->fetch();
        $subtotal_calc = (float)($row['subtotal'] ?? 0.0);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Eroare DB la subtotal: ' . $e->getMessage()]);
        exit;
    }

    $DEFAULT_SHIPPING_COST = 25.00;
    $payment_fee = 0.00;

    $new_shipping_cost = $ship_flag ? $DEFAULT_SHIPPING_COST : 0.00;
    $new_total = round($subtotal_calc + $new_shipping_cost + $payment_fee, 2);

    try {
        $stmtUpd = $pdo->prepare("
            UPDATE orders
            SET shipping_cost = ?, total = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmtUpd->execute([$new_shipping_cost, $new_total, $order_id_post]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Eroare update DB: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'total' => number_format($new_total, 2, '.', ''),
        'shipping_cost' => number_format($new_shipping_cost, 2, '.', ''),
    ]);
    exit;
}

/* ---------------------------------------------------------------- */
/* Aici incepe fluxul normal: preluare sesiune Stripe si actualizare comanda la paid */
/* ---------------------------------------------------------------- */

/* VERIFICARE PARAMETRU session_id (GET) */
$session_id = $_GET['session_id'] ?? '';
if (!$session_id) {
    http_response_code(400);
    echo "Session Stripe lipsa";
    exit;
}

/* STRIPE - cheia de test */
\Stripe\Stripe::setApiKey('sk_test_51SysxhGmT36GYuCZvBH39trJmqiPKh2dVitHcfbv7RE4Sjb24Kc0XQ1ZVCpVkImnhgbNBVQG8QqUq473g9D2kSio00wiSzSbRv');

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);
} catch (\Exception $e) {
    http_response_code(500);
    echo "Eroare Stripe: " . htmlspecialchars($e->getMessage());
    exit;
}

if (($session->payment_status ?? '') !== 'paid') {
    http_response_code(400);
    echo "Plata nu este finalizata";
    exit;
}

/* CITIRE METADATA */
$meta = $session->metadata ?? new stdClass();

$user_id_meta      = (int)($meta->user_id ?? 0);
$order_id_meta     = (int)($meta->order_id ?? 0);
$order_subtotal_md = isset($meta->order_subtotal) ? (float)$meta->order_subtotal : null;
$shipping_cost_md  = isset($meta->shipping_cost) ? (float)$meta->shipping_cost : null;
$pay_shipping_md   = isset($meta->pay_shipping) ? ((int)$meta->pay_shipping === 1) : null;

/* VALORI DEFAULT */
$DEFAULT_SHIPPING_COST = 25.00;
$payment_fee = 0.00;

/* PRELUARE SUME */
$order_subtotal = $order_subtotal_md ?? 0.00;
$shipping_cost = ($shipping_cost_md !== null) ? $shipping_cost_md : $DEFAULT_SHIPPING_COST;
$paid_total = (float)($session->amount_total ?? 0) / 100.0;

/* DECIZIE DACA A FOST PLATIT TRANSPORTUL */
if ($pay_shipping_md !== null) {
    $shipping_paid = $pay_shipping_md;
} else {
    // daca suma platita e mai mare decat subtotal atunci consideram transport platit
    $shipping_paid = ($paid_total > $order_subtotal + 0.001);
}

/* IMPORTANT: pentru actualizarea DB vrem shipping_cost = 0 daca transportul nu a fost platit */
$shipping_cost_for_db = $shipping_paid ? $shipping_cost : 0.00;

/* TOTAL FINAL (folosim valoarea pentru DB) */
$order_total = round($order_subtotal + $shipping_cost_for_db + $payment_fee, 2);

/* GASIRE COMANDA */
if ($order_id_meta <= 0) {
    http_response_code(400);
    echo "Order id lipsa in metadata";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$order_id_meta]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    http_response_code(500);
    echo "Eroare DB: " . htmlspecialchars($e->getMessage());
    exit;
}

if (!$order) {
    http_response_code(404);
    echo "Comanda inexistenta";
    exit;
}

/* DACA E DEJA PLATITA, REDIRECT CATRE ACASA */
if (($order['status'] ?? '') === 'paid') {
    header("Location: Acasa.php?payment=success&order_id={$order_id_meta}");
    exit;
}

/* ACTUALIZARE COMANDA LA PAID */
try {
    $pdo->beginTransaction();

    $stmtUpd = $pdo->prepare("
        UPDATE orders
        SET status = 'paid',
            total = ?,
            shipping_cost = ?,
            payment_fee = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmtUpd->execute([
        $order_total,
        $shipping_cost_for_db,
        $payment_fee,
        $order_id_meta
    ]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Eroare actualizare comanda: " . htmlspecialchars($e->getMessage());
    exit;
}

/* CURATARE SESIUNE */
unset($_SESSION['cart'], $_SESSION['checkout_cart'], $_SESSION['checkout_total']);

/* CONSTRUCTIE URL ABSOLUT CATRE Acasa.php */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$baseUrl = $proto . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
$acasaUrl = $baseUrl . '/Acasa.php?payment=success&order_id=' . urlencode($order_id_meta);

/* AFISARE PAGINA DE CONFIRMARE (UI cu toggle pentru transport) */
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>Plata confirmata</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f6f7fb; padding:30px; font-family:Arial, sans-serif; }
  .confirm-card { max-width:720px; margin:0 auto; }
  .big-amount { font-size:48px; font-weight:700; cursor:pointer; user-select:none; }
  .small-muted { color:#666; }
  .ship-box {
    display:flex; align-items:center; gap:12px;
    padding:14px; border-radius:10px; border:1px solid #e2e6ee;
    background: linear-gradient(180deg, #fff, #fbfdff);
    box-shadow: 0 4px 20px rgba(17,24,39,0.04);
    margin-top:12px;
  }
  .ship-checkbox {
    width:22px; height:22px; border-radius:6px; border:1px solid #cbd5e1;
    display:flex; align-items:center; justify-content:center; cursor:pointer;
    background:#fff;
  }
  .ship-checkbox.checked { background:#0ea5a4; border-color:#0ea5a4; color:#fff; }
  .ship-label { font-weight:600; }
  .total-row { display:flex; justify-content:space-between; align-items:center; margin-top:18px; }
  .btn-green { background:#10b981; border:none; color:#fff; }
  .muted-note { font-size:0.95rem; color:#555; margin-top:12px; }
  .badge-paid { display:inline-block; padding:6px 10px; background:#e6fffa; color:#065f46; border-radius:999px; font-weight:600; margin-left:8px; }
</style>
</head>
<body>
<div class="container">
  <div class="card p-4 confirm-card">
    <h4>Plata procesata cu succes</h4>
    <p class="small-muted">Comanda <strong>#<?= htmlspecialchars($order_id_meta) ?></strong></p>

    <?php
      // date pentru afisat
      $displayShippingDefault = $DEFAULT_SHIPPING_COST; // valoare de aratat ca optiune
      $initialTotalDisplay = $order_subtotal + ($shipping_paid ? $shipping_cost : 0.00);
    ?>

    <div class="mt-3">
      <div class="small-muted">Subtotal produse</div>
      <div id="subtotal" class="big-amount"><?= number_format($order_subtotal, 2, '.', '') ?> <span style="font-size:18px;font-weight:600;">RON</span></div>

      <!-- AFISEAZA CASUTA CU OPTIUNEA DE TRANSPORT (intotdeauna vizibila; daca deja platit arata starea) -->
      <div class="ship-box" id="shipBox" role="button" tabindex="0" aria-pressed="<?= $shipping_paid ? 'true' : 'false' ?>">
        <?php if (!$shipping_paid): ?>
          <div id="shipCheckbox" class="ship-checkbox" title="Plătește transportul"></div>
          <div>
            <div class="ship-label">Vrei să plătești courierul?</div>
            <div class="small-muted">Cost transport: <?= number_format($displayShippingDefault, 2) ?> lei</div>
          </div>
        <?php else: ?>
          <div class="ship-checkbox checked">✓</div>
          <div>
            <div class="ship-label">Transport plătit prin Stripe</div>
            <div class="small-muted">Cost transport: <?= number_format($shipping_cost, 2) ?> lei</div>
          </div>
        <?php endif; ?>
      </div>

      <div class="total-row">
        <div class="small-muted">Total înregistrat</div>
        <div style="text-align:right;">
          <div id="totalAmount" style="font-size:28px; font-weight:700;"><?= number_format($initialTotalDisplay, 2, '.', '') ?> RON</div>
        </div>
      </div>

      <div class="muted-note">
        <span>Transport inclus: <strong id="shipIncludedText"><?= $shipping_paid ? 'Da' : 'Nu' ?></strong></span>
        <?php if (!$shipping_paid): ?>
          <div class="small-muted" style="display:block; margin-top:8px;">
            Atenție: bifarea acestei opțiuni modifică doar totalul afișat aici. Dacă dorești să încasezi efectiv costul de transport, pot implementa un endpoint care creează o sesiune Stripe separată pentru transport (recomandat).
          </div>
        <?php else: ?>
          <span class="badge-paid">Transport achitat</span>
        <?php endif; ?>
      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="<?= htmlspecialchars($acasaUrl) ?>" class="btn btn-success">Mergi la pagina principală</a>

        <!-- buton vizibil pentru utilizator (doar client-side: adauga/sterge transport in afisare) -->
        <?php if (!$shipping_paid): ?>
          <button id="payShipBtn" class="btn btn-outline-primary">Adaugă/Elimină transport 25.00 lei</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const subtotal = parseFloat(<?= json_encode($order_subtotal) ?>) || 0;
    const defaultShip = parseFloat(<?= json_encode($DEFAULT_SHIPPING_COST) ?>) || 25;
    const shippingPaid = <?= $shipping_paid ? 'true' : 'false' ?>;
    let shipChecked = shippingPaid ? true : false;

    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('totalAmount');
    const shipCheckbox = document.getElementById('shipCheckbox');
    const shipBox = document.getElementById('shipBox');
    const shipIncludedText = document.getElementById('shipIncludedText');
    const payShipBtn = document.getElementById('payShipBtn');

    function updateDisplayLocal(val) {
      // val: new total numeric OR undefined => compute from shipChecked
      const shipVal = shipChecked ? defaultShip : 0.00;
      const total = (typeof val === 'number') ? val : (subtotal + shipVal);
      totalEl.textContent = total.toFixed(2) + ' RON';
      if (shipCheckbox) {
        if (shipChecked) shipCheckbox.classList.add('checked'), shipCheckbox.textContent = '✓';
        else shipCheckbox.classList.remove('checked'), shipCheckbox.textContent = '';
      }
      if (shipIncludedText) shipIncludedText.textContent = shipChecked ? 'Da' : 'Nu';
      if (payShipBtn) payShipBtn.textContent = shipChecked ? 'Elimină transport' : 'Adaugă transport 25.00 lei';
    }

    async function sendUpdateToServer(orderId, shipFlag) {
      // POST to same php file
      const fd = new FormData();
      fd.append('action', 'update_shipping');
      fd.append('order_id', <?= json_encode($order_id_meta) ?>);
      fd.append('ship', shipFlag ? '1' : '0');

      try {
        const res = await fetch(window.location.pathname + window.location.search, {
          method: 'POST',
          credentials: 'same-origin',
          body: fd
        });
        const json = await res.json();
        if (json.success) {
          // update vizual cu valoarea returnata din DB
          const newTotal = parseFloat(json.total) || (subtotal + (shipFlag ? defaultShip : 0));
          updateDisplayLocal(newTotal);
        } else {
          alert('Eroare actualizare: ' + (json.error || 'unknown'));
          // revert state in caz de eroare
          shipChecked = !shipChecked;
          updateDisplayLocal();
        }
      } catch (err) {
        alert('Eroare comunicare cu serverul: ' + err.message);
        shipChecked = !shipChecked;
        updateDisplayLocal();
      }
    }

    function toggleAndPersist() {
      // togglam starea local si trimitem la server
      shipChecked = !shipChecked;
      // pentru UX, actualizam imediat vizual (optimistic)
      updateDisplayLocal();
      // trimitem la server pentru persis
      sendUpdateToServer(<?= json_encode($order_id_meta) ?>, shipChecked);
    }

    if (!shippingPaid) {
      if (subtotalEl) subtotalEl.addEventListener('click', toggleAndPersist);
      if (shipBox) shipBox.addEventListener('click', function(e){
        toggleAndPersist();
      });
      if (shipBox) shipBox.addEventListener('keyup', function(e){ if (e.key === 'Enter' || e.key === ' ') { toggleAndPersist(); }});
    }

    if (payShipBtn) {
      payShipBtn.addEventListener('click', toggleAndPersist);
    }

    // initial update: arata totalul curent din DB
    updateDisplayLocal(<?= json_encode($initialTotalDisplay) ?>);
  })();
</script>
</body>
</html>
<?php
exit;
