<?php
session_start();

// Verificare login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nu esti logat']);
    exit;
}

// Conexiune baza de date
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Eroare conexiune baza de date']);
    exit;
}

// Determinare tip adresa
$type = $_POST['type'] ?? 'shipping';

if($type === 'easybox') {
    // --------------------------
    // EasyBox
    // --------------------------
    $name    = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $county  = trim($_POST['county'] ?? '');
    $lat     = floatval($_POST['lat'] ?? 0);
    $lng     = floatval($_POST['lng'] ?? 0);

    // Validare minimă
    $errors = [];
    if($name === '') $errors[] = 'Nume punct lipsă';
    if($address === '') $errors[] = 'Adresă lipsă';
    if($city === '') $errors[] = 'Oraș lipsă';
    if($county === '') $errors[] = 'Județ lipsă';

    if(!empty($errors)){
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    // Verificare duplicate
    $stmt = $pdo->prepare("
        SELECT id FROM easyboxes 
        WHERE name = ? AND address = ? AND city = ? AND county = ? AND active = 1
    ");
    $stmt->execute([$name, $address, $city, $county]);

    if($stmt->fetch()){
        echo json_encode(['success' => false, 'error' => 'Acest punct EasyBox există deja!']);
        exit;
    }

    // Inserare EasyBox
    $stmt = $pdo->prepare("
        INSERT INTO easyboxes (name, address, city, county, lat, lng, active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$name, $address, $city, $county, $lat, $lng]);
    $easybox_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'box' => [
            'id' => $easybox_id,
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'county' => $county,
            'lat' => $lat,
            'lng' => $lng
        ]
    ]);
    exit;

} else {
    // --------------------------
    // Adresa normală
    // --------------------------
    $user_id = (int)$_SESSION['user_id'];
    $name    = trim($_POST['contact_name'] ?? '');
    $phone   = trim($_POST['contact_phone'] ?? '');
    $county  = trim($_POST['county'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $line    = trim($_POST['address_line'] ?? '');

    // Validare date
    $errors = [];
    if ($name === '') $errors[] = 'Nume lipsă';
    if ($phone === '') $errors[] = 'Telefon lipsă';
    if ($county === '') $errors[] = 'Județ lipsă';
    if ($city === '') $errors[] = 'Oraș lipsă';
    if ($line === '') $errors[] = 'Stradă lipsă';
    if (!preg_match('/^0[0-9]{9}$/', $phone)) $errors[] = 'Număr de telefon invalid';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    // Verificare duplicate
    $stmt = $pdo->prepare("
        SELECT id FROM user_addresses 
        WHERE user_id = ? AND type = 'shipping' AND name = ? AND phone = ? AND address_line = ? AND county = ? AND city = ?
    ");
    $stmt->execute([$user_id, $name, $phone, $line, $county, $city]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Această adresă există deja!']);
        exit;
    }

    // Adăugare adresă
    $stmt = $pdo->prepare("
        INSERT INTO user_addresses (user_id, type, name, phone, county, city, address_line) 
        VALUES (?, 'shipping', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $name, $phone, $county, $city, $line]);

    $address_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'address' => [
            'id' => $address_id,
            'name' => $name,
            'phone' => $phone,
            'county' => $county,
            'city' => $city,
            'address_line' => $line
        ]
    ]);
    exit;
}
