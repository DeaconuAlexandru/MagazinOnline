<?php
// add_address_ajax.php - suport separat guest_addresses + user_addresses
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

function json_and_exit($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// Conexiune DB
$host = 'localhost';
$db   = 'magazi15_ShergeiCovoare';
$user = 'magazi15_Alex';
$pass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("DELETE FROM guest_addresses WHERE expires_at IS NOT NULL AND expires_at < NOW()");
} catch (PDOException $e) {
    error_log("DB CONNECT ERROR: " . $e->getMessage());
    json_and_exit(['success' => false, 'error' => 'Eroare conexiune baza de date']);
}

// owner info: user_id (nullable) and session id for guest
$sessionId = session_id();
$ownerUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

try {
    // SELECT EASYBOX
    if (isset($_POST['action']) && $_POST['action'] === 'select_easybox') {
        $easybox_id = (int)($_POST['easybox_id'] ?? 0);
        if ($easybox_id <= 0) {
            json_and_exit(['success' => false, 'error' => 'ID invalid']);
        }

        $stmt = $pdo->prepare("SELECT id, name, county, city, address, lat, lng, active FROM easyboxes WHERE id = ? LIMIT 1");
        $stmt->execute([$easybox_id]);
        $box = $stmt->fetch();

        if (!$box) {
            json_and_exit(['success' => false, 'error' => 'Punctul nu exista']);
        }

        if ((int)$box['active'] !== 1) {
            json_and_exit(['success' => false, 'error' => 'Punctul este inactiv']);
        }

        $_SESSION['selected_easybox_id'] = (int)$box['id'];
        $_SESSION['selected_shipping_method'] = 'easybox';

        json_and_exit(['success' => true, 'box' => $box]);
    }

    // Procesare add easybox
    $type = $_POST['type'] ?? 'shipping';

    if ($type === 'easybox') {
        $name    = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $county  = trim($_POST['county'] ?? '');

        $errors = [];
        if ($name === '')    $errors[] = 'Nume punct lipsa';
        if ($address === '') $errors[] = 'Adresa lipsa';
        if ($city === '')    $errors[] = 'Oras lipsa';
        if ($county === '')  $errors[] = 'Judet lipsa';
        if ($errors) {
            json_and_exit(['success' => false, 'error' => implode(', ', $errors)]);
        }

        $stmt = $pdo->prepare("SELECT id FROM easyboxes WHERE name = ? AND address = ? AND city = ? AND county = ? LIMIT 1");
        $stmt->execute([$name, $address, $city, $county]);
        $existing = $stmt->fetch();

        if ($existing) {
            $easybox_id = (int)$existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO easyboxes (name, address, city, county, active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$name, $address, $city, $county]);
            $easybox_id = (int)$pdo->lastInsertId();
        }

        json_and_exit(['success' => true, 'box' => [
            'id' => $easybox_id,
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'county' => $county
        ]]);
    }

    // Courier / Shipping
    $name    = trim((string)($_POST['contact_name'] ?? $_POST['name'] ?? ''));
    $phone   = trim((string)($_POST['contact_phone'] ?? $_POST['phone'] ?? ''));
    $county  = trim((string)($_POST['county'] ?? ''));
    $city    = trim((string)($_POST['city'] ?? ''));
    $line    = trim((string)($_POST['address_line'] ?? $_POST['address'] ?? ''));
    $addressId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $saveFlag = isset($_POST['save']) ? (bool)$_POST['save'] : true;

    $errors = [];
    if ($name === '')   $errors[] = 'Nume lipsa';
    if ($phone === '')  $errors[] = 'Telefon lipsa';
    if ($county === '') $errors[] = 'Judet lipsa';
    if ($city === '')   $errors[] = 'Oras lipsa';
    if ($line === '')   $errors[] = 'Adresa lipsa';
    if (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) $errors[] = 'Numar de telefon invalid';

    if ($errors) {
        json_and_exit(['success' => false, 'error' => implode(', ', $errors)]);
    }

    $full_address = "{$county}, {$city}, {$line}";

    // UPDATE existing address
    if ($addressId) {
        if ($ownerUserId !== null) {
            // update user_addresses
            $stmt = $pdo->prepare("SELECT id, user_id FROM user_addresses WHERE id = ? LIMIT 1");
            $stmt->execute([$addressId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                json_and_exit(['success' => false, 'error' => 'Adresa nu exista']);
            }

            if ((int)$existing['user_id'] !== $ownerUserId) {
                json_and_exit(['success' => false, 'error' => 'Nu ai permisiunea sa modifici aceasta adresa']);
            }

            $upd = $pdo->prepare("
                UPDATE user_addresses SET
                    name = ?, phone = ?, county = ?, city = ?, address_line = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$name, $phone, $county, $city, $line, $full_address, $addressId]);

            json_and_exit(['success' => true, 'type' => 'user', 'address' => [
                'id' => $addressId,
                'name' => $name,
                'phone' => $phone,
                'county' => $county,
                'city' => $city,
                'address_line' => $line,
                'address' => $full_address
            ]]);
        } else {
            // guest update. Use guest_addresses table
            $stmt = $pdo->prepare("SELECT id, phone FROM guest_addresses WHERE id = ? LIMIT 1");
            $stmt->execute([$addressId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                json_and_exit(['success' => false, 'error' => 'Adresa guest nu exista']);
            }

            // require phone match for basic ownership check
            if ($existing['phone'] !== $phone) {
                json_and_exit(['success' => false, 'error' => 'Datele nu coincid cu adresa guest']);
            }

            $email = trim($_POST['email'] ?? $_POST['guest_email'] ?? '');

            $updGuest = $pdo->prepare("
                UPDATE guest_addresses SET
                    name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $updGuest->execute([$name, $email, $phone, $full_address, $city, $county, $addressId]);

            json_and_exit(['success' => true, 'type' => 'guest', 'guest_address_id' => $addressId, 'address' => [
                'id' => $addressId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'county' => $county,
                'city' => $city,
                'address_line' => $line,
                'address' => $full_address
            ]]);
        }
    }

    // save=false -> return only
    if (!$saveFlag) {
        json_and_exit(['success' => true, 'address' => [
            'id' => null,
            'name' => $name,
            'phone' => $phone,
            'county' => $county,
            'city' => $city,
            'address_line' => $line,
            'address' => $full_address
        ]]);
    }

    // INSERT new address
    if ($ownerUserId !== null) {
        // user logged in: insert in user_addresses only
        $ins = $pdo->prepare("
            INSERT INTO user_addresses
            (user_id, session_id, type, name, phone, county, city, address_line, address, created_at)
            VALUES (?, ?, 'shipping', ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$ownerUserId, $sessionId, $name, $phone, $county, $city, $line, $full_address]);
        $newId = (int)$pdo->lastInsertId();

        json_and_exit(['success' => true, 'type' => 'user', 'address' => [
            'id' => $newId,
            'name' => $name,
            'phone' => $phone,
            'county' => $county,
            'city' => $city,
            'address_line' => $line,
            'address' => $full_address
        ]]);
    } else {
        // guest: insert in guest_addresses only
        $email = trim($_POST['email'] ?? $_POST['guest_email'] ?? '');

        $insGuest = $pdo->prepare("
            INSERT INTO guest_addresses
            (guest_id, name, email, phone, address, city, country, created_at, updated_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ");
        $insGuest->execute([0, $name, $email, $phone, $full_address, $city, $county]);
        $guest_address_id = (int)$pdo->lastInsertId();
        $_SESSION['guest_address_id'] = $guest_address_id;
        $_SESSION['guest_name'] = $name;
        $_SESSION['guest_phone'] = $phone;
        $_SESSION['guest_email'] = $email;
        json_and_exit(['success' => true, 'type' => 'guest', 'guest_address_id' => $guest_address_id, 'address' => [
            'id' => $guest_address_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'county' => $county,
            'city' => $city,
            'address_line' => $line,
            'address' => $full_address
        ]]);
    }

} catch (Exception $ex) {
    error_log("add_address_ajax ERROR: " . $ex->getMessage());
    json_and_exit(['success' => false, 'error' => 'Eroare server: ' . $ex->getMessage()]);
}