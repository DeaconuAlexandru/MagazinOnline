<?php
// FinalizeazaComanda.php - forțare alegere transport înainte de redirecționare la Stripe

session_start();
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (stripos($errstr, 'mbstring') !== false) {
        $log = date('Y-m-d H:i:s') . " | $errstr in $errfile:$errline\n";
        $log .= "Backtrace:\n" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true) . "\n\n";
        @file_put_contents(__DIR__ . '/mb_warn.log', $log, FILE_APPEND);
    }
    return false; // lasă handlerul normal să continue
});
require 'vendor/autoload.php'; // Stripe SDK + PHPMailer (dacă e instalat via composer)

// --- SETĂRI SMTP (folosește variabile de mediu sau introduce direct pentru teste) ---
$smtpHost  = 'mail.magazinpsy.ro';
$smtpUser  = 'office@magazinpsy.ro';
$smtpPass  = 'pd3AOZRX4L7Af^Ii';
$smtpPort  = 465;

$fromEmail = 'office@magazinpsy.ro';
$fromName  = 'Magazin Psy';
$STORE_EMAIL = 'office@magazinpsy.ro'; 

// ------------------- FUNCȚII EMAIL -------------------
function sendMailViaPHPMailer($to, $subject, $htmlBody, $bcc = []) {
    global $smtpHost, $smtpUser, $smtpPass, $smtpPort, $fromEmail, $fromName;

    // verifică dacă PHPMailer există
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('PHPMailer nu e instalat. Folosesc mail() ca fallback.');
        return mailFallback($to, $subject, $htmlBody, $bcc);
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Debug SMTP
        $mail->SMTPDebug = 2; // 0=off, 2=full
        $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug [$level]: $str"); };

        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // port 465
        $mail->Port       = (int)$smtpPort;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        foreach ($bcc as $b) {
            if (filter_var($b, FILTER_VALIDATE_EMAIL)) $mail->addBCC($b);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        if (!$mail->send()) {
            error_log('PHPMailer send failed: ' . $mail->ErrorInfo);
            return mailFallback($to, $subject, $htmlBody, $bcc);
        }

        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('PHPMailer exception: ' . $e->getMessage());
        return mailFallback($to, $subject, $htmlBody, $bcc);
    } catch (Exception $e) {
        error_log('Generic mail exception: ' . $e->getMessage());
        return mailFallback($to, $subject, $htmlBody, $bcc);
    }
}

function mailFallback($to, $subject, $htmlBody, $bcc = []) {
    global $fromEmail, $fromName;
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . $fromName . " <" . $fromEmail . ">\r\n";
    if (!empty($bcc)) $headers .= "Bcc: " . implode(',', $bcc) . "\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
}

// Trimite email magazin + BCC client (detaliile comenzii)
function sendOrderEmail(PDO $pdo, $order_id, $to = null) {
    global $STORE_EMAIL;
    $to = $to ?? $STORE_EMAIL;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $clientEmail = null;
    if (!empty($order['user_id'])) {
        $s = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        $s->execute([(int)$order['user_id']]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $clientEmail = $u['email'];
    }

    $html = '<html><body>';
    $html .= '<h2>Comanda #' . htmlspecialchars($order_id) . '</h2>';
    $html .= '<p><strong>Client ID:</strong> ' . htmlspecialchars($order['user_id']) . '</p>';
    $html .= '<p><strong>Adresa livrare:</strong> ' . nl2br(htmlspecialchars($order['shipping_address'])) . '</p>';
    $html .= '<p><strong>Telefon:</strong> ' . htmlspecialchars($order['shipping_phone']) . '</p>';
    $html .= '<p><strong>Metoda livrare:</strong> ' . htmlspecialchars($order['shipping_method']) . ' | <strong>Metoda plată:</strong> ' . htmlspecialchars($order['payment_method']) . '</p>';

    $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';
    $html .= '<thead><tr><th align="left">Produs</th><th align="center">Cant.</th><th align="right">Pret/u</th><th align="right">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
        $price = number_format((float)($it['price'] ?? 0), 2, '.', '');
        $lineTotal = number_format($qty * (float)($it['price'] ?? 0), 2, '.', '');
        $productName = htmlspecialchars($it['product_name'] ?? $it['product_id']);
        $html .= '<tr>';
        $html .= '<td>' . $productName . '</td>';
        $html .= '<td align="center">' . $qty . '</td>';
        $html .= '<td align="right">' . $price . ' RON</td>';
        $html .= '<td align="right">' . $lineTotal . ' RON</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<p><strong>Cost transport:</strong> ' . number_format((float)($order['shipping_cost'] ?? 0), 2, '.', '') . ' RON</p>';
    $html .= '<p><strong>Total:</strong> ' . number_format((float)$order['total'], 2, '.', '') . ' RON</p>';
    $html .= '<p><em>Data comenzii: ' . htmlspecialchars($order['created_at']) . '</em></p>';
    $html .= '</body></html>';

    $subject = 'Nouă comandă #' . $order_id;
    $bcc = [];
    if ($clientEmail) $bcc[] = $clientEmail;

    if (sendMailViaPHPMailer($to, $subject, $html, $bcc)) return true;
    return mailFallback($to, $subject, $html, $bcc);
}

// Confirmare comanda catre client
function sendClientConfirmationEmail(PDO $pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT o.*, u.email as user_email, u.name as user_name FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || empty($order['user_email']) || !filter_var($order['user_email'], FILTER_VALIDATE_EMAIL)) return false;

    $clientEmail = $order['user_email'];
    $clientName = $order['user_name'] ?? 'Client';

    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '<html><body>';
    $html .= '<h2>Confirmare comandă #' . htmlspecialchars($order_id) . '</h2>';
    $html .= '<p>Bună ' . htmlspecialchars($clientName) . ',</p>';
    $html .= '<p>Mulțumim! Am înregistrat comanda ta. Mai jos găsești detaliile:</p>';
    $html .= '<ul>';
    $html .= '<li><strong>Adresa livrare:</strong> ' . nl2br(htmlspecialchars($order['shipping_address'])) . '</li>';
    $html .= '<li><strong>Telefon:</strong> ' . htmlspecialchars($order['shipping_phone']) . '</li>';
    $html .= '<li><strong>Metoda plată:</strong> ' . htmlspecialchars($order['payment_method']) . '</li>';
    $html .= '</ul>';

    $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';
    $html .= '<thead><tr><th align="left">Produs</th><th align="center">Cant.</th><th align="right">Pret/u</th><th align="right">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
        $price = number_format((float)($it['price'] ?? 0), 2, '.', '');
        $lineTotal = number_format($qty * (float)($it['price'] ?? 0), 2, '.', '');
        $productName = htmlspecialchars($it['product_name'] ?? $it['product_id']);
        $html .= '<tr>';
        $html .= '<td>' . $productName . '</td>';
        $html .= '<td align="center">' . $qty . '</td>';
        $html .= '<td align="right">' . $price . ' RON</td>';
        $html .= '<td align="right">' . $lineTotal . ' RON</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p><strong>Total:</strong> ' . number_format((float)$order['total'], 2, '.', '') . ' RON</p>';
    $html .= '<p>Te vom anunța când comanda este expediată.</p>';
    $html .= '<p>Cu stimă,<br/>' . htmlspecialchars($GLOBALS['fromName'] ?? 'Magazin') . '</p>';
    $html .= '</body></html>';

    $subject = 'Confirmare comandă #' . $order_id;
    if (sendMailViaPHPMailer($clientEmail, $subject, $html)) return true;
    return mailFallback($clientEmail, $subject, $html);
}

// Confirmare livrare + solicitare review (apelabil din admin la status=delivered)
function sendDeliveryAndReviewEmail(PDO $pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT o.*, u.email as user_email, u.name as user_name FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order || empty($order['user_email']) || !filter_var($order['user_email'], FILTER_VALIDATE_EMAIL)) return false;

    $clientEmail = $order['user_email'];
    $clientName = $order['user_name'] ?? 'Client';

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $reviewLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/leave_review.php?order=' . urlencode($order_id);

    $html = '<html><body>';
    $html .= '<h2>Comanda #' . htmlspecialchars($order_id) . ' - Livrată</h2>';
    $html .= '<p>Bună ' . htmlspecialchars($clientName) . ',</p>';
    $html .= '<p>Comanda ta a fost livrată. Sperăm că ești mulțumit(ă) de produse.</p>';
    $html .= '<p>Te rugăm lasă-ne un review: <a href="' . htmlspecialchars($reviewLink) . '">Lasă un review</a></p>';
    $html .= '<p>Mulțumim!<br/>' . htmlspecialchars($GLOBALS['fromName'] ?? 'Magazin') . '</p>';
    $html .= '</body></html>';

    $subject = 'Comanda #' . $order_id . ' — confirmare livrare & review';
    if (sendMailViaPHPMailer($clientEmail, $subject, $html)) return true;
    return mailFallback($clientEmail, $subject, $html);
}

// ------------------- ADMIN ACCOUNT ENSURE -------------------
// Creează/actualizează cont admin pentru $adminEmail dacă tabela users permite
function ensureAdminAccount(PDO $pdo, $adminEmail = 'biz.craiova@gmail.com') {
    try {
        // verificăm coloane tabela users
        $colsStmt = $pdo->query("SHOW COLUMNS FROM users");
        $colsArr = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_column($colsArr, 'Field');

        if (!in_array('email', $cols)) {
            error_log('ensureAdminAccount: tabela users nu are coloana email - nu pot crea admin.');
            return false;
        }

        $hasRole = in_array('role', $cols);
        $hasPassword = in_array('password', $cols) || in_array('passwd', $cols);
        $hasName = in_array('name', $cols);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($hasRole) {
                $upd = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                $upd->execute([$user['id']]);
            }
            return true;
        }

        $passwordPlain = bin2hex(random_bytes(6)); // 12 chars hex
        $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $fields = ['email'];
        $values = [$adminEmail];
        $placeholders = ['?'];

        if ($hasName) { $fields[] = 'name'; $values[] = 'Biz Admin'; $placeholders[] = '?'; }
        if ($hasPassword) { $fields[] = 'password'; $values[] = $passwordHash; $placeholders[] = '?'; }
        if ($hasRole) { $fields[] = 'role'; $values[] = 'admin'; $placeholders[] = '?'; }
        if (in_array('created_at', $cols)) { $fields[] = 'created_at'; $values[] = date('Y-m-d H:i:s'); $placeholders[] = '?'; }

        $sql = "INSERT INTO users (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $ins = $pdo->prepare($sql);
        $ins->execute($values);

        // trimitem parola initiala catre admin
        $subject = 'Acces admin creat';
        $body = '<p>Am creat un cont admin pentru <strong>' . htmlspecialchars($adminEmail) . '</strong>.</p>';
        $body .= '<p>Parolă temporară: <strong>' . htmlspecialchars($passwordPlain) . '</strong></p>';
        $body .= '<p>Vă rugăm să schimbați parola la prima autentificare.</p>';
        if (!sendMailViaPHPMailer($adminEmail, $subject, $body)) {
            mailFallback($adminEmail, $subject, $body);
        }

        return true;
    } catch (Exception $e) {
        error_log('ensureAdminAccount error: ' . $e->getMessage());
        return false;
    }
}

// ------------------- DB CONNECTION -------------------
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

// Asigurăm cont admin (opțional)
ensureAdminAccount($pdo, $STORE_EMAIL);

// ------------------- LOGICA EXISTENTĂ -------------------
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    $user = [
        'name' => $_SESSION['guest_name'] ?? 'Guest',
        'phone' => $_SESSION['guest_phone'] ?? '',
        'email' => $_SESSION['guest_email'] ?? ''
    ];
}

$cart    = $_SESSION['checkout_cart'] ?? [];
$total   = floatval($_SESSION['checkout_total'] ?? 0.0);

if (empty($cart)) {
    $_SESSION['cart_empty_message'] = "Nu ai ales niciun produs.";
    header("Location: ContinuaComanda.php");
    exit;
}

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$shipping_method = trim($_POST['shipping_method'] ?? $_GET['shipping_method'] ?? 'courier');
// permit fallback la $_SESSION['selected_payment'] (setat de ContinuaComanda.php la click),
$payment_method  = $_POST['payment_method'] 
                   ?? $_GET['payment_method'] 
                   ?? $_SESSION['selected_payment'] 
                   ?? 'online';
$shipping_cost   = floatval(str_replace(',', '.', ($_POST['shipping_cost'] ?? '25.00')));
$order_subtotal  = floatval(str_replace(',', '.', ($_POST['order_subtotal'] ?? (string)$total)));
$payment_fee     = ($payment_method === 'delivery') ? 10.00 : 0.00;
$order_total     = round($order_subtotal + $shipping_cost + $payment_fee, 2);

$all_addresses = [];

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND type = 'shipping' ORDER BY id ASC");
    $stmt->execute([$user_id]);
    $all_addresses = $stmt->fetchAll();
} elseif (!empty($_SESSION['guest_address_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM guest_addresses WHERE id = ?");
    $stmt->execute([$_SESSION['guest_address_id']]);
    $addr = $stmt->fetch();
    if ($addr) {
        $all_addresses[] = [
            'id' => $addr['id'],
            'name' => $addr['name'],
            'phone' => $addr['phone'],
            'county' => $addr['country'] ?? '',
            'city' => $addr['city'],
            'address_line' => $addr['address']
        ];
    }
}
if (!$user_id && empty($_SESSION['guest_address_id'])) {
    header("Location: ContinuaComanda.php");
    exit;
}
$address_id = (int)($_POST['shipping_address_id'] ?? $_GET['shipping_address_id'] ?? ($all_addresses[0]['id'] ?? 0));

$addr = null;
foreach ($all_addresses as $a) {
    if ($a['id'] == $address_id) { $addr = $a; break; }
}
if (!$addr && !empty($all_addresses)) { $addr = $all_addresses[0]; $address_id = $addr['id']; }

$shipping_name    = $addr['name'] ?? $user['name'] ?? '';
$shipping_phone   = $addr['phone'] ?? $user['phone'] ?? '';
if ($addr) {
    if (!$user_id) {
        $shipping_address = $addr['address_line'];
    } else {
        $shipping_address = $addr['county'] . ', ' . $addr['city'] . ', ' . $addr['address_line'];
    }
} else {
    $shipping_address = 'Adresa necunoscuta';
}

$DEFAULT_SHIPPING_COST = 25.00;

/* HANDLING PLATA LA LIVRARE */
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

        // TRIMIT EMAIL (magazin + optional client)
        try { sendOrderEmail($pdo, $order_id, $STORE_EMAIL); } catch (Exception $e) { error_log('sendOrderEmail error (delivery): ' . $e->getMessage()); }
        // TRIMIT CONFIRMARE CLIENT
        try { sendClientConfirmationEmail($pdo, $order_id); } catch (Exception $e) { error_log('sendClientConfirmationEmail error (delivery): ' . $e->getMessage()); }

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

/* HANDLING PLATA ONLINE */
if ($is_post && ($payment_method === 'online' || ($_POST['payment_method'] ?? '') === 'online')) {
    $shipping_choice_made = $_POST['shipping_choice_made'] ?? '0';
    if ($shipping_choice_made !== '1' && !isset($_POST['pay_shipping'])) {
        header("Location: FinalizeazaComanda.php?error=select_shipping");
        exit;
    }

   \Stripe\Stripe::setApiKey('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');
    $pay_shipping = (isset($_POST['pay_shipping'])) ? ((string)$_POST['pay_shipping'] === '1') : (!empty($_POST['payShipping']) || !empty($_POST['pay_ship']));
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
<html lang="ro">
<head>
<meta charset="utf-8">
<title>Finalizeaza comanda</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* păstrez stilurile tale */
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
          <!-- WRAP pentru option delivery: il ascundem/afisam din JS -->
        <div class="form-check" id="deliveryOptionWrap" <?= ($payment_method === 'online') ? 'style="display:none"' : '' ?>>
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
const deliveryOptionWrap = document.getElementById('deliveryOptionWrap');

const shipCheckbox = document.getElementById('shipCheckbox');
const shipBox = document.getElementById('shipBox');
const shipIncludedText = document.getElementById('shipIncludedText');
const totalRightEl = document.getElementById('totalRight');
const subtotalBig = document.getElementById('subtotalBig');

let shipChecked = false;
// hide immediately if online already selected (avoid flicker)
if (deliveryOptionWrap && radioOnline && radioOnline.checked) {
    deliveryOptionWrap.style.display = 'none';
    if (radioDelivery) radioDelivery.checked = false;
}

// robust binding: prindem toate input-urile cu name="payment_method_select"
// (acoperă click, change și cazurile în care elementele sunt recreate)
document.querySelectorAll('input[name="payment_method_select"]').forEach(function(el){
    el.addEventListener('change', updateStripeVisibility);
    el.addEventListener('click', updateStripeVisibility);
});
// marcheaza ca utilizatorul a luat o decizie despre transport cand interacționează
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

// când se schimbă metoda de plată prin radio, actualizăm vizibilitatea
if (radioDelivery) radioDelivery.addEventListener('change', () => { updateStripeVisibility(); });
if (radioOnline) radioOnline.addEventListener('change', () => { updateStripeVisibility(); });

function updateStripeVisibility() {

    const onlineSelected = radioOnline && radioOnline.checked;

    /* CONTROL OPTIUNE PLATA LA LIVRARE */
    if (deliveryOptionWrap) {

        if (onlineSelected) {

            deliveryOptionWrap.style.display = 'none';

            // prevenim selectarea accidentala
            if (radioDelivery) {
                radioDelivery.checked = false;
                radioDelivery.disabled = true;
            }

        } else {

            deliveryOptionWrap.style.display = 'block';

            if (radioDelivery) {
                radioDelivery.disabled = false;
            }
        }
    }

    /* CONTROL CARD STRIPE */
    if (onlineSelected) {

        if (stripeCard) stripeCard.style.display = 'block';

        if (paymentMethodInput) {
            paymentMethodInput.value = 'online';
        }

        /* setare implicita transport daca utilizatorul nu a ales */
        if (shippingChoiceMade && shippingChoiceMade.value !== '1') {

            shippingChoiceMade.value = '1';
            shipChecked = false;

            if (checkboxLeft) checkboxLeft.checked = false;

            if (payShippingInput) {
                payShippingInput.value = '0';
            }

            if (shippingCostInput) {
                shippingCostInput.value = '0.00';
            }

            updateAllDisplays();
        }

    } else {

        if (stripeCard) stripeCard.style.display = 'none';

        if (paymentMethodInput) {
            paymentMethodInput.value = 'delivery';
        }

        /* utilizatorul trebuie sa confirme transportul */
        if (shippingChoiceMade) {
            shippingChoiceMade.value = '0';
        }
    }
}
// interceptam submit pentru a verifica alegerea transport
if (stripeForm) {

    stripeForm.addEventListener('submit', function(e){

        if (payShippingInput)
            payShippingInput.value = shipChecked ? '1' : '0';

        if (shippingCostInput)
            shippingCostInput.value = shipChecked ? defaultShip.toFixed(2) : '0.00';

        if (orderSubtotalInput)
            orderSubtotalInput.value = subtotal.toFixed(2);

        if (paymentMethodInput && paymentMethodInput.value === 'online') {

            if (shippingChoiceMade && shippingChoiceMade.value !== '1') {
                e.preventDefault();
                alert('Te rog confirma daca platesti transportul sau nu.');
                return false;
            }
        }
    });

}

// initializare UI
updateStripeVisibility();
updateAllDisplays();
</script>

</body>
</html>