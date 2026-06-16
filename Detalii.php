<?php
declare(strict_types=1);

session_start();

$allowedEmails = ['office@magazinpsy.ro'];
$currentEmail = strtolower(trim((string)(
    $_SESSION['user_email'] ?? $_SESSION['email'] ?? $_SESSION['gmail'] ?? ''
)));
$isStockEditor = in_array($currentEmail, $allowedEmails, true);

ini_set('display_errors', '1');
error_reporting(E_ALL);


try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}
require_once __DIR__ . '/social_tracker.php';
recordSocialVisit($pdo);
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function formatCm($v): ?string
{
    if ($v === null || $v === '') {
        return null;
    }

    $v = floatval(str_replace(',', '.', (string)$v));
    $s = rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',');

    return $s . ' cm';
}

function parseDimensionsString(?string $s): ?array
{
    if ($s === null || trim($s) === '') {
        return null;
    }

    $s = str_replace(['×', 'X', '*'], 'x', trim($s));
    $s = preg_replace('/\s*x\s*/i', 'x', $s);
    $s = str_replace(',', '.', $s);

    if (preg_match('/^([\d\.]+)x([\d\.]+)$/i', $s, $m)) {
        return [(float)$m[1], (float)$m[2]];
    }

    return null;
}

function formatPrice($price): string
{
    return number_format((float)$price, 2, ',', '') . ' Lei';
}

function truncateText(string $text, int $maxLen): string
{
    if (function_exists('mb_strlen') && mb_strlen($text) <= $maxLen) {
        return $text;
    }

    if (strlen($text) <= $maxLen) {
        return $text;
    }

    return (function_exists('mb_substr')
        ? mb_substr($text, 0, $maxLen - 3)
        : substr($text, 0, $maxLen - 3)
    ) . '...';
}

function resolveImage(?string $imgFromDb): string
{
    $imgFromDb = trim((string)($imgFromDb ?? ''));

    if ($imgFromDb === '') {
        return 'default.png';
    }

    if (is_file($imgFromDb)) {
        return basename($imgFromDb);
    }

    $c1 = __DIR__ . '/' . ltrim($imgFromDb, '/');
    if (is_file($c1)) {
        return basename($c1);
    }

    $c2 = __DIR__ . '/' . basename($imgFromDb);
    if (is_file($c2)) {
        return basename($c2);
    }

    return 'default.png';
}

$itemId = filter_input(INPUT_GET, 'item', FILTER_VALIDATE_INT);

if (!$itemId) {
    die("<h2 style='text-align:center;margin-top:40px;'>Produsul nu a fost găsit.</h2>");
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $itemId]);
$item = $stmt->fetch();

if (!$item) {
    die("<h2 style='text-align:center;margin-top:40px;'>Produsul nu a fost găsit.</h2>");
}

$itemName = trim((string)($item['name'] ?? ''));
$itemCategory = trim((string)($item['category'] ?? ''));

$isIngerasiCategory = (strcasecmp($itemCategory, 'Ingerasi') === 0);
$isIngeras1 = (strcasecmp($itemName, 'Ingeras 1') === 0);

$imageForOutput = resolveImage($item['img'] ?? null);
$cacheBuster = '';
$fsPath = __DIR__ . '/' . $imageForOutput;

if (is_file($fsPath)) {
    $cacheBuster = '?v=' . filemtime($fsPath);
}

$imgBasename = strtolower(basename((string)($item['img'] ?? '')));
$imgNum = null;

if (preg_match('/(\d+)(?=\.[a-z]+$)/i', $imgBasename, $m)) {
    $imgNum = (int)$m[1];
}

$materialRaw = $item['material'] ?? $item['componente'] ?? $item['components'] ?? null;

if ($materialRaw === null || trim((string)$materialRaw) === '') {
    $materialText = 'Mocheta printata prin sublimare, spate cauciuc antiderapant';

    if ($imgNum !== null) {
        if ($imgNum >= 61 && $imgNum <= 67) {
            $materialText = 'Ingerașii sunt făcuți din ipsos';
        } elseif ($imgNum >= 71 && $imgNum <= 80) {
            $materialText = 'Mandala alcătuită din mocheta și spate antiderapant';
        } elseif ($imgNum >= 81 && $imgNum <= 126) {
            $materialText = 'Tablourile sunt realizate pe foaie A3, echivalent a două coli A4';
        } elseif ($imgNum >= 131 && $imgNum <= 148) {
            $materialText = 'Mocheta rotundă, imprimată, spate cauciuc antiderapant';
        } elseif ($imgNum >= 151 && $imgNum <= 160) {
            $materialText = 'Ingerașii, modelați din ipsos cu grijă și finețe, se unesc cu mandala, alcătuită din mocheta și spate antiderapant, într-o creație armonioasă ce îmbină delicatețea cu stabilitatea, spiritul cu forma și frumusețea cu funcționalitatea.';
        }
    }
} else {
    $materialText = truncateText(trim((string)$materialRaw), 300);
}

$dimensionsText = 'Nu specificat';
$isRound = false;
$isCombined = (strcasecmp($itemCategory, 'Ingeras si Mandala') === 0);

if ($isCombined) {
    $length = $item['lungime_cm'] ?? $item['length'] ?? null;
    $width  = $item['latime_cm'] ?? $item['width'] ?? null;
    $diameter = $item['diameter_cm'] ?? null;

    $parts = [];

    if ($length !== null && $length !== '' && $width !== null && $width !== '') {
        $parts[] = formatCm($length) . ' lungime';
        $parts[] = formatCm($width) . ' lățime';
    }

    if ($diameter !== null && (float)$diameter > 0) {
        $parts[] = 'Diametru ' . formatCm($diameter);
    } else {
        $parts[] = 'Diametru ' . formatCm(6);
    }

    $dimensionsText = implode(', ', $parts);
} else {
    if (!empty($itemCategory) && strcasecmp($itemCategory, 'Mandala') === 0) {
        $isRound = true;
    } elseif (!empty($item['name']) && stripos($item['name'], 'covor rotund') !== false) {
        $isRound = true;
    } elseif ($imgNum !== null && $imgNum >= 131 && $imgNum <= 143) {
        $isRound = true;
    }

    if ($isRound && !empty($item['diameter_cm']) && (float)$item['diameter_cm'] > 0) {
        $dimensionsText = 'Diametru ' . formatCm($item['diameter_cm']);
    } else {
        $length = $item['lungime_cm'] ?? $item['length'] ?? null;
        $width = $item['latime_cm'] ?? $item['width'] ?? null;

        if ($length === null || $width === null) {
            $parsed = parseDimensionsString($item['dimensiuni'] ?? $item['size'] ?? null);

            if ($parsed) {
                $length = $length ?? $parsed[0];
                $width = $width ?? $parsed[1];
            }
        }

        if ($length !== null && $length !== '' && $width !== null && $width !== '') {
            $dimensionsText = formatCm($length) . ' lungime, ' . formatCm($width) . ' lățime';
        } elseif ($length !== null && $length !== '') {
            $dimensionsText = formatCm($length) . ' lungime';
        } elseif ($width !== null && $width !== '') {
            $dimensionsText = formatCm($width) . ' lățime';
        }
    }
}

$priceVal = $item['price'] ?? null;
$stockVal = (int)($item['stock'] ?? 0);
$isOutOfStock = ($stockVal <= 0);
$isCerere = $isOutOfStock || ($priceVal === null || $priceVal === '' || (float)$priceVal <= 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (!$isStockEditor) {
        die('Acces interzis.');
    }

    $newStock = (isset($_POST['stock_value']) && $_POST['stock_value'] === '1') ? 1 : 0;

    $pdo->prepare("UPDATE products SET stock = :stock WHERE id = :id")->execute([
        'stock' => $newStock,
        'id'    => $itemId,
    ]);

    header("Location: " . $_SERVER['PHP_SELF'] . "?item=" . $itemId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if ($isOutOfStock) {
        $_SESSION['flash_message'] = 'Acest produs este disponibil la cerere';
        header('Location: Contact.php?produs=' . urlencode((string)($item['name'] ?? '')));
        exit;
    }

    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
    if (!$qty || $qty < 1) {
        $qty = 1;
    }

    $availableStock = (int)$item['stock'];
    if ($qty > $availableStock) {
        $qty = $availableStock;
    }

    $cartItem = [
        'id'          => $item['id'],
        'name'        => $item['name'] ?? '',
        'price'       => $item['price'] ?? 0,
        'qty'         => $qty,
        'img'         => !empty($item['img']) ? $item['img'] : 'default.png',
        'category'    => $item['category'] ?? '',
        'description' => $item['description'] ?? '',
        'material'    => $item['material'] ?? $item['componente'] ?? $item['components'] ?? '',
        'dimensiuni'  => $item['dimensiuni'] ?? $item['size'] ?? '',
        'stock'       => (int)($item['stock'] ?? 0),
    ];

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $productKey = (string)$item['id'];

    if (isset($_SESSION['cart'][$productKey])) {
        $_SESSION['cart'][$productKey]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$productKey] = $cartItem;
    }

    header('Location: CosulMeu.php');
    exit;
}

$contactOfertaUrl = 'Contact.php?produs=' . urlencode((string)($item['name'] ?? ''));
$pageTitle = trim((string)($item['name'] ?? 'Produs')) . ' - Detalii';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*,
*::before,
*::after {
    box-sizing: border-box;
}

html, body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: #fdf6f0;
    color: #222;
    padding-left: max(0px, env(safe-area-inset-left));
    padding-right: max(0px, env(safe-area-inset-right));
}

img {
    max-width: 100%;
    display: block;
}

a {
    color: inherit;
}

body.dark-mode {
    background: #111;
    color: #fdf6f0;
}

header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #8C92AC;
    color: #fff;
    padding: 14px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

header .logo {
    display: flex;
    align-items: center;
}

header .logo img {
    height: 50px;
    width: auto;
}

#mainNav {
    flex: 1 1 auto;
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

#mainNav a {
    text-decoration: none;
    padding: 10px 16px;
    background: #fff;
    color: #b5651d;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.25s;
    white-space: nowrap;
}

#mainNav a:hover,
#mainNav a.active {
    background: #8b4315;
    color: #fff;
    transform: translateY(-2px);
}

.menu-toggle {
    display: none;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    margin-left: auto;
}

.menu-toggle div {
    width: 28px;
    height: 3px;
    background: #fff;
    border-radius: 2px;
}

.page-wrapper {
    max-width: 1200px;
    margin: 32px auto 40px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(320px, 1.15fr);
    gap: 28px;
    align-items: start;
}

.product-image {
    position: sticky;
    top: 92px;
    align-self: start;
}

.product-image-box {
    background: #fff;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.10);
    border: 1px solid rgba(181, 101, 29, 0.08);
}

.product-image-box img {
    width: 100%;
    border-radius: 14px;
    object-fit: cover;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.16);
}

.product-info {
    background: #fff;
    padding: 28px;
    border-radius: 18px;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.10);
    border: 1px solid rgba(181, 101, 29, 0.08);
}

.product-info h1 {
    margin: 0;
    font-size: clamp(24px, 3vw, 34px);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.2;
}

.price-box {
    margin: 16px 0 10px;
}

.price-new {
    font-size: clamp(24px, 4vw, 30px);
    font-weight: 800;
    color: #b5651d;
}

.price-cerere {
    font-size: 18px;
    font-weight: 800;
    color: #8b4315;
    background: #fdf0e6;
    padding: 10px 14px;
    border-radius: 12px;
    display: inline-block;
}

.short-desc {
    margin: 12px 0 0;
    line-height: 1.7;
    color: #333;
}

.section-title {
    margin: 24px 0 12px;
    font-family: 'Montserrat', sans-serif;
    font-size: 18px;
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.info-card {
    background: #f9f3ee;
    padding: 15px 16px;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.06);
}

.info-card.full {
    grid-column: 1 / -1;
}

.info-card h4 {
    margin: 0 0 6px;
    color: #b5651d;
    font-size: 16px;
}

.info-card p {
    margin: 0;
    font-weight: 500;
    line-height: 1.6;
}

.action-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.action-card {
    background: #b5651d;
    color: #fff;
    text-align: center;
    padding: 16px 14px;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.25s;
    text-decoration: none;
    display: block;
    border: none;
}

.action-card:hover {
    background: #8b4315;
    transform: translateY(-2px);
}

.action-card.secondary {
    background: #1d4ed8;
}

.action-card.secondary:hover {
    background: #1e40af;
}

.btn-solicita-oferta {
    display: block;
    margin-top: 16px;
    padding: 16px;
    background: linear-gradient(135deg, #b5651d, #8b4315);
    color: #fff;
    text-align: center;
    border-radius: 14px;
    font-weight: 800;
    font-size: 16px;
    text-decoration: none;
    transition: 0.25s;
    box-shadow: 0 4px 15px rgba(181, 101, 29, 0.35);
}

.btn-solicita-oferta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(181, 101, 29, 0.45);
}

.buy-row {
    display: flex;
    gap: 10px;
    align-items: stretch;
    margin-top: 18px;
    flex-wrap: wrap;
}

.qty-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f7f7f7;
    border: 1px solid #ddd;
    padding: 10px 12px;
    border-radius: 12px;
}

.qty-wrap label {
    font-weight: 700;
    white-space: nowrap;
}

.qty-wrap input {
    width: 72px;
    padding: 10px 10px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 16px;
    text-align: center;
}

.back-btn {
    display: inline-block;
    margin-top: 22px;
    padding: 12px 18px;
    background: #f3e7dc;
    color: #8b4315;
    text-decoration: none;
    font-weight: 700;
    border-radius: 12px;
    transition: 0.25s;
    border: 1px solid rgba(181, 101, 29, 0.12);
}

.back-btn:hover {
    background: #ead6c1;
    transform: translateY(-1px);
}

.stock-editor {
    margin-top: 12px;
    padding: 12px;
    border-radius: 12px;
    background: #f9f9f9;
    border: 1px dashed #d7d7d7;
}

.stock-editor form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.stock-editor select {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ccc;
    background: #fff;
}

.stock-editor button {
    padding: 10px 14px;
    border-radius: 10px;
    border: none;
    background: #b5651d;
    color: #fff;
    font-weight: 700;
    cursor: pointer;
}

.stock-editor button:hover {
    background: #8b4315;
}

footer {
    background: #8C92AC;
    color: #fff;
    padding: 50px 20px;
    transition: background 0.3s, color 0.3s;
}

footer.dark-mode {
    background: #222;
}

footer .footer-container {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    align-items: flex-start;
}

footer .footer-col {
    flex: 1;
    min-width: 200px;
}

footer .footer-logo img {
    height: 50px;
    width: auto;
    margin-bottom: 15px;
}

footer ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 20px 0;
    line-height: 1.8;
}

footer ul li a {
    color: #fff;
    text-decoration: none;
}

footer p,
footer strong,
footer span,
footer li,
footer blockquote {
    color: #fff !important;
}

footer .footer-bottom {
    max-width: 1200px;
    margin: 30px auto 0;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    padding-top: 15px;
    font-size: 14px;
}

footer .footer-bottom a {
    color: #fff;
    text-decoration: none;
    margin: 0 8px;
}

body.dark-mode {
    background: #111;
    color: #fdf6f0;
}

body.dark-mode .product-image-box,
body.dark-mode .product-info,
body.dark-mode .info-card,
body.dark-mode .qty-wrap,
body.dark-mode .stock-editor {
    background: #1d1d1d;
    color: #fdf6f0;
    border-color: #333;
}

body.dark-mode .short-desc,
body.dark-mode .info-card p {
    color: #e4e4e4;
}

body.dark-mode .info-card h4 {
    color: #ffcb9a;
}

body.dark-mode .back-btn {
    background: #2a2a2a;
    color: #ffcb9a;
}

body.dark-mode .qty-wrap input,
body.dark-mode .stock-editor select {
    background: #111;
    color: #fdf6f0;
    border-color: #444;
}

@media (max-width: 980px) {
    .page-wrapper {
        grid-template-columns: 1fr;
    }

    .product-image {
        position: static;
    }
}

@media (max-width: 768px) {
    header {
        padding: 12px 16px;
    }

    .menu-toggle {
        display: flex;
    }

    #mainNav {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        margin-top: 10px;
    }

    #mainNav.mobile-open {
        display: flex;
    }

    #mainNav a {
        width: 100%;
        text-align: left;
        padding: 12px 14px;
    }

    .page-wrapper {
        margin-top: 20px;
        padding: 0 12px;
        gap: 18px;
    }

    .product-info {
        padding: 18px;
    }

    .info-cards,
    .action-cards {
        grid-template-columns: 1fr;
    }

    .buy-row {
        flex-direction: column;
    }

    .qty-wrap {
        width: 100%;
        justify-content: space-between;
    }

    .qty-wrap input {
        width: 88px;
    }

    .action-card,
    .btn-solicita-oferta,
    .back-btn,
    .stock-editor button {
        width: 100%;
    }

    footer {
        padding: 40px 16px;
    }

    footer .footer-container {
        flex-direction: column;
        gap: 25px;
    }
}

@media (max-width: 420px) {
    .page-wrapper {
        padding: 0 10px;
    }

    .product-info h1 {
        font-size: 24px;
    }

    .price-new {
        font-size: 24px;
    }

    .product-image-box {
        padding: 10px;
    }

    .qty-wrap {
        gap: 8px;
    }

    .qty-wrap input {
        width: 76px;
    }

    .info-card,
    .stock-editor {
        padding: 13px;
    }

    .map-note {
        font-size: 13px;
    }
}
</style>
</head>
<body>

<header id="header">
    <div class="logo">
        <a href="Acasa.php" style="display:inline-flex;align-items:center;text-decoration:none;">
            <img src="Image41.png" alt="Logo">
        </a>
    </div>

    <nav id="mainNav">
        <a href="Acasa.php">Acasă</a>
        <a href="Colectie.php">Colecție</a>
        <a href="DespreNoi.php">Despre Noi</a>
        <a href="Contact.php">Contact</a>
        <a href="CosulMeu.php">Cosul Meu</a>
        <a href="ContulMeu.php">Contul Meu</a>
    </nav>

    <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul">
        <div></div>
        <div></div>
        <div></div>
    </div>
</header>

<div class="page-wrapper">
    <div class="product-image">
        <div class="product-image-box">
            <img src="<?= e($imageForOutput . $cacheBuster) ?>" alt="<?= e($item['name'] ?? 'Produs') ?>">
        </div>
    </div>

    <div class="product-info">
        <h1><?= e($item['name'] ?? '') ?></h1>

        <div class="price-box">
            <?php if (!$isCerere && $priceVal !== null && $priceVal !== '' && (float)$priceVal > 0): ?>
                <div id="priceDisplay" class="price-new"><?= e(formatPrice($priceVal)) ?></div>
            <?php else: ?>
                <div class="price-cerere">Preț la cerere</div>
            <?php endif; ?>
        </div>

        <p class="short-desc"><?= nl2br(e((string)($item['description'] ?? ''))) ?></p>

        <div class="section-title">Detalii produs</div>

        <div class="info-cards">
            <div class="info-card">
                <h4>Disponibilitate</h4>
                <?php if ($isOutOfStock): ?>
                    <p>Solicită ofertă</p>
                <?php else: ?>
                    <p>Stoc: <?= (int)$item['stock'] ?></p>

                    <?php if ($isStockEditor): ?>
                        <div class="stock-editor">
                            <form method="post">
                                <select name="stock_value">
                                    <option value="1" <?= ((int)($item['stock'] ?? 0) > 0) ? 'selected' : '' ?>>În stoc</option>
                                    <option value="0" <?= ((int)($item['stock'] ?? 0) <= 0) ? 'selected' : '' ?>>La cerere</option>
                                </select>
                                <button type="submit" name="update_stock">Salvează</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <h4>Dimensiuni</h4>
                <p><?= e($dimensionsText) ?></p>
            </div>

            <div class="info-card full">
                <h4>Material</h4>
                <p><?= e($materialText) ?></p>
            </div>
        </div>

        <div class="section-title">Acțiuni rapide</div>

        <div class="action-cards">
            <a href="tel:+40753508461" class="action-card secondary">📞 Comandă telefonic</a>

            <?php if (!$isCerere): ?>
                <a href="Contact.php" class="action-card">✉️ Întreabă-ne</a>
            <?php else: ?>
                <a href="<?= e($contactOfertaUrl) ?>" class="action-card">✉️ Întreabă-ne</a>
            <?php endif; ?>
        </div>

        <?php if ($isCerere): ?>
            <a href="<?= e($contactOfertaUrl) ?>" class="btn-solicita-oferta">
                📩 Solicită ofertă pentru acest produs
            </a>
        <?php elseif (isset($item['stock']) && (int)$item['stock'] > 0): ?>
            <form method="post" class="buy-row">
                <div class="qty-wrap">
                    <label for="qtyInput">Cantitate</label>
                    <input
                        type="number"
                        id="qtyInput"
                        name="qty"
                        value="1"
                        min="1"
                        max="<?= (int)$item['stock'] ?>"
                    >
                </div>

                <button type="submit" name="add_to_cart" class="action-card" style="flex:1;">
                    🛒 Adaugă în coș
                </button>
            </form>
        <?php endif; ?>

        <a href="Colectie.php" class="back-btn">← Înapoi la colecție</a>
    </div>
</div>

<footer id="footer">
    <div class="footer-container">
        <div class="footer-col">
            <div class="footer-logo">
                <img src="Image41.png" alt="Sherghei Covoare">
            </div>
            <p>Primul an prin care aducem covoare tradiționale și moderne în casa ta.</p>
        </div>

        <div class="footer-col">
            <strong>Navigare</strong>
            <ul>
                <li><a href="Acasa.php">Acasa</a></li>
                <li><a href="Colectie.php">Colectie</a></li>
                <li><a href="DespreNoi.php">Despre Noi</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="CosulMeu.php">Cosul Meu</a></li>
                <li><a href="ContulMeu.php">Contul Meu</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <strong>Contact</strong>
            <p>Adresa, Craiova, Dolj</p>
            <p>Telefon, 0753 508 461</p>
            <p>Email, office@magazinpsy.ro</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2024 Sherghei Covoare. Toate drepturile rezervate.</p>
        <a href="TermeniSiConditii.php">Termeni si conditii</a> |
        <a href="PoliticaDeConfidentialitate.php">Politica de confidentialitate</a>
    </div>
</footer>

<script>
(function () {
    'use strict';

    function toggleMenu() {
        const nav = document.getElementById('mainNav');
        if (!nav) return;
        nav.classList.toggle('mobile-open');
    }
    window.toggleMenu = toggleMenu;

    const qtyInput = document.getElementById('qtyInput');
    const priceDisplay = document.getElementById('priceDisplay');

    <?php if (!$isCerere): ?>
    const pricePerUnit = <?= (float)($item['price'] ?? 0) ?>;

    if (qtyInput && priceDisplay) {
        qtyInput.addEventListener('input', function () {
            let qty = parseInt(this.value, 10);

            if (isNaN(qty) || qty < 1) {
                qty = 1;
                this.value = 1;
            }

            const maxQty = parseInt(this.max || '1', 10);
            if (!isNaN(maxQty) && qty > maxQty) {
                qty = maxQty;
                this.value = maxQty;
            }

            priceDisplay.innerText = (pricePerUnit * qty).toFixed(2).replace('.', ',') + ' Lei';
        });
    }
    <?php endif; ?>

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            const nav = document.getElementById('mainNav');
            if (nav) nav.classList.remove('mobile-open');
        }
    });
})();
</script>
</body>
</html>
