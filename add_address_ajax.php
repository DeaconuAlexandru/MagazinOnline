<?php
// add_address_ajax.php - versiune completă și robustă
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// =======================================
// Check login
// =======================================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nu esti logat']);
    exit;
}

// =======================================
// Conexiune baza de date
// =======================================
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("DB CONNECT ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Eroare conexiune baza de date']);
    exit;
}

try {
    // =======================================
    // SELECT EASYBOX (salveaza selectie din harta/modal)
    // request: POST action=select_easybox, easybox_id=NN
    // =======================================
    if (isset($_POST['action']) && $_POST['action'] === 'select_easybox') {
        $easybox_id = (int)($_POST['easybox_id'] ?? 0);
        if ($easybox_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID invalid']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name, county, city, address, lat, lng, active FROM easyboxes WHERE id = ? LIMIT 1");
        $stmt->execute([$easybox_id]);
        $box = $stmt->fetch();

        if (!$box) {
            echo json_encode(['success' => false, 'error' => 'Punctul nu exista']);
            exit;
        }

        if ((int)$box['active'] !== 1) {
            echo json_encode(['success' => false, 'error' => 'Punctul este inactiv']);
            exit;
        }

        // salveaza selectie in sesiune, pentru a fi folosita la finalizarea comenzii
        $_SESSION['selected_easybox_id'] = (int)$box['id'];
        // seteaza metoda de livrare
        $_SESSION['selected_shipping_method'] = 'easybox';

        echo json_encode(['success' => true, 'box' => $box]);
        exit;
    }

    // =======================================
    // Procesare adaugare EasyBox (adauga in tabela)
    // request: POST type=easybox, name,address,city,county
    // =======================================
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
            echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
            exit;
        }

        // verificare duplicate
        $stmt = $pdo->prepare("SELECT id FROM easyboxes WHERE name = ? AND address = ? AND city = ? AND county = ?");
        $stmt->execute([$name, $address, $city, $county]);
        $existing = $stmt->fetch();

        if ($existing) {
            $easybox_id = $existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO easyboxes (name, address, city, county, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $address, $city, $county]);
            $easybox_id = $pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'box' => [
            'id' => (int)$easybox_id,
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'county' => $county
        ]]);
        exit;
    }

    // =======================================
    // Courier / Shipping (user addresses)
    // request: POST contact_name, contact_phone, county, city, address_line
    // =======================================
    $user_id = (int)$_SESSION['user_id'];
    $name    = trim($_POST['contact_name'] ?? $_POST['name'] ?? '');
    $phone   = trim($_POST['contact_phone'] ?? $_POST['phone'] ?? '');
    $county  = trim($_POST['county'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $line    = trim($_POST['address_line'] ?? $_POST['address'] ?? '');

    $errors = [];
    if ($name === '')   $errors[] = 'Nume lipsa';
    if ($phone === '')  $errors[] = 'Telefon lipsa';
    if ($county === '') $errors[] = 'Judet lipsa';
    if ($city === '')   $errors[] = 'Oras lipsa';
    if ($line === '')   $errors[] = 'Adresa lipsa';
    if (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) $errors[] = 'Numar de telefon invalid';

    if ($errors) {
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    // cream campul 'address' complet
    $full_address = "$county, $city, $line";

    // INSERT / UPDATE
    $sql = "
        INSERT INTO user_addresses (user_id, type, name, phone, county, city, address_line, address)
        VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            phone = VALUES(phone),
            county = VALUES(county),
            city = VALUES(city),
            address_line = VALUES(address_line),
            address = VALUES(address)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $name, $phone, $county, $city, $line, $full_address]);

    // obtinem ID-ul adresei
    $id = $pdo->lastInsertId();
    if (empty($id)) {
        $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND type = 'shipping' LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        $address_id = $row ? $row['id'] : null;
    } else {
        $address_id = $id;
    }

    echo json_encode(['success' => true, 'address' => [
        'id' => $address_id,
        'name' => $name,
        'phone' => $phone,
        'county' => $county,
        'city' => $city,
        'address_line' => $line,
        'address' => $full_address
    ]]);
    exit;

} catch (Exception $ex) {
    error_log("add_address_ajax ERROR: " . $ex->getMessage());
    echo json_encode(['success' => false, 'error' => 'Eroare server: ' . $ex->getMessage()]);
    exit;
}
