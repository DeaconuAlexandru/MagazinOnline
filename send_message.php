<?php
// Configurație DB pentru InfinityFree
$host = 'sql112.infinityfree.com';
$db   = 'if0_40665152_database';
$user = 'if0_40665152';
$pass = '7u72iuIGVg';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Eroare la conectare: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, phone, email, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $message]);
        header("Location: Acasa.php?success=1");
        exit;
    } else {
        header("Location: Acasa.php?success=0");
        exit;
    }
} else {
    header("Location: Acasa.php?success=0");
    exit;
}
?>
