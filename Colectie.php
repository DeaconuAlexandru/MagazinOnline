<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$lang = 'ro';

function safe_strtolower($s): string {
    if ($s === null) return '';
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

function safe_stripos($haystack, $needle) {
    if ($haystack === null) return false;
    return function_exists('mb_stripos') ? mb_stripos($haystack, $needle) : stripos($haystack, $needle);
}

function remove_accents(string $s): string {
    return strtr($s, [
        'Ă'=>'A','ă'=>'a','Â'=>'A','â'=>'a','Î'=>'I','î'=>'i',
        'Ș'=>'S','ș'=>'s','Ş'=>'S','ş'=>'s','Ț'=>'T','ț'=>'t','Ţ'=>'T','ţ'=>'t'
    ]);
}

function normalize_key(string $s): string {
    $s = remove_accents($s);
    $s = safe_strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    return trim((string)$s, '-');
}

function category_label_from_key(string $key): string {
    $key = normalize_key($key);

    if ($key === 'ingerasi-si-mandale-ale-pacii' || $key === 'ingerasi-si-mandala-ale-pacii' || $key === 'ingerasi-si-mandale') {
        return 'Ingerași și mandale ale păcii';
    }
    if ($key === 'ingerasi') {
        return 'Îngerași ai păcii';
    }
    if ($key === 'mandala') {
        return 'Mandale ale păcii';
    }
    if ($key === 'psygeometry-rotunde') {
        return 'Mochete rotunde ale păcii';
    }
    if ($key === 'psygeometry') {
        return 'Mochete dreptunghiulare ale păcii';
    }
    if ($key === 'tablouri') {
        return 'Tablouri ale păcii';
    }
    if ($key === 'rotunde') {
        return 'Covoare rotunde ale păcii';
    }
    if ($key === 'covoare') {
        return 'Covoare dreptunghiulare ale păcii';
    }

    return 'Mochete dreptunghiulare ale păcii';
}

function detectType(array $row): string {
    $category = trim((string)($row['category'] ?? ''));
    $name = trim((string)($row['name'] ?? ''));
    $img = trim((string)($row['img'] ?? ''));

    $blob = safe_strtolower(remove_accents($category . ' ' . $name . ' ' . basename($img)));

    if (
        safe_stripos($blob, 'ingerasi si mandale') !== false ||
        safe_stripos($blob, 'ingerasi si mandala') !== false ||
        safe_stripos($blob, 'pachetul pacii') !== false
    ) {
        return 'ingerasi-si-mandale-ale-pacii';
    }

    if (safe_stripos($blob, 'ingerasi') !== false || safe_stripos($blob, 'ingeras') !== false || safe_stripos($blob, 'angel') !== false) {
        return 'ingerasi';
    }

    if (safe_stripos($blob, 'mandala') !== false) {
        return 'mandala';
    }

    if (safe_stripos($blob, 'tablouri') !== false || safe_stripos($blob, 'tablou') !== false || safe_stripos($blob, 'painting') !== false) {
        return 'tablouri';
    }

    if (safe_stripos($blob, 'rotund') !== false || safe_stripos($blob, 'round') !== false) {
        return 'psygeometry-rotunde';
    }

    if (safe_stripos($blob, 'psy') !== false || safe_stripos($blob, 'geometry') !== false) {
        return 'psygeometry';
    }

    if (safe_stripos($blob, 'covor') !== false || safe_stripos($blob, 'covoare') !== false || safe_stripos($blob, 'rug') !== false || safe_stripos($blob, 'carpet') !== false) {
        return 'covoare';
    }

    return 'psygeometry';
}

function resolve_image(string $imgFromDb): string {
    $imgFromDb = trim($imgFromDb);
    if ($imgFromDb === '') {
        return 'default.png';
    }

    $candidate1 = __DIR__ . '/' . ltrim($imgFromDb, '/');
    $candidate2 = __DIR__ . '/' . basename($imgFromDb);

    if (is_file($candidate1)) return basename($candidate1);
    if (is_file($candidate2)) return basename($candidate2);

    return 'default.png';
}

$activeFilter = 'psygeometry';

if (isset($_GET['cat']) && $_GET['cat'] !== '') {
    $incoming = normalize_key((string)$_GET['cat']);

    if (
        $incoming === 'ingerasi-si-mandale-ale-pacii' ||
        $incoming === 'ingerasi-si-mandala-ale-pacii' ||
        $incoming === 'ingerasi-si-mandale'
    ) {
        $activeFilter = 'ingerasi-si-mandale-ale-pacii';
    } else {
        $activeFilter = $incoming;
    }
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ro', 'ru'], true)) {
    $lang = $_GET['lang'];
}


try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}
require_once __DIR__ . '/social_tracker.php';
recordSocialVisit($pdo);
$stmt = $pdo->query("SELECT * FROM products ORDER BY sort_ordine ASC, id ASC");
$collection = $stmt->fetchAll();

usort($collection, function (array $a, array $b): int {
    $target = 'ingeras 1';

    $aName = safe_strtolower(trim((string)($a['name'] ?? '')));
    $bName = safe_strtolower(trim((string)($b['name'] ?? '')));

    $aIsTarget = ($aName === $target);
    $bIsTarget = ($bName === $target);

    if ($aIsTarget !== $bIsTarget) {
        return $aIsTarget ? -1 : 1;
    }

    $aSort = (int)($a['sort_ordine'] ?? 0);
    $bSort = (int)($b['sort_ordine'] ?? 0);

    if ($aSort !== $bSort) {
        return $aSort <=> $bSort;
    }

    return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
});

$collectionFinal = [];

foreach ($collection as $c) {
    $c['type_key'] = detectType($c);
    $c['type'] = category_label_from_key($c['type_key']);

    $c['desc'] = !empty($c['preview']) ? $c['preview'] : ($c['description'] ?? '');

    $stockVal = (int)($c['stock'] ?? 0);
    $priceVal = $c['price'] ?? null;

    $c['stock_val'] = $stockVal;
    $c['availability_text'] = $stockVal > 0 ? 'Disponibil in stoc' : 'La cerere';
    $c['availability_class'] = $stockVal > 0 ? 'in-stock' : 'out-stock';

    $isOutOfStock = ($stockVal <= 0);
    $showPrice = (!$isOutOfStock && $priceVal !== null && $priceVal !== '' && (float)$priceVal > 0);

    if ($isOutOfStock) {
        $c['price_new'] = '';
        $c['price_is_cerere'] = true;
        $c['hide_price'] = true;
    } elseif ($priceVal === null || $priceVal === '' || (float)$priceVal <= 0) {
        $c['price_new'] = 'La cerere';
        $c['price_is_cerere'] = true;
        $c['hide_price'] = false;
    } else {
        $c['price_new'] = number_format((float)$priceVal, 2, ',', '') . ' Lei';
        $c['price_is_cerere'] = false;
        $c['hide_price'] = false;
    }

    $c['price_old'] = '-';
    $c['show_price'] = $showPrice;
    $c['img'] = resolve_image((string)($c['img'] ?? ''));

    $collectionFinal[] = $c;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sherghei Covoare - Colectie</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Roboto',sans-serif;color:#111;background:#fdf6f0;scroll-behavior:smooth;}
.dark-mode{background:#111;color:#fdf6f0;}
header{position:sticky;top:0;z-index:10;background:#8C92AC;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;align-items:center;justify-content:flex-start;flex-wrap:wrap;}
header h1{font-family:'Montserrat',sans-serif;font-weight:700;font-size:30px;margin:0;}
#mainNav{position:absolute;left:50%;transform:translateX(-50%);display:flex;justify-content:center;gap:15px;flex-wrap:wrap;}
nav a{text-decoration:none;padding:10px 18px;background:#fff;color:#b5651d;border-radius:8px;transition:.3s;font-weight:500;}
nav a.active{background:#8b4315;color:#fff;}
nav a:hover{background:#b5651d;color:#fff;transform:translateY(-2px);}
.menu-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;margin-left:auto;}
.menu-toggle div{width:25px;height:3px;background:#fff;}
.collection{display:flex;flex-wrap:wrap;justify-content:center;gap:20px;padding:50px 20px;}
.collection-item{position:relative;width:300px;border-radius:16px;overflow:hidden;cursor:pointer;transition:transform 0.3s;}
.collection-item img{width:100%;height:250px;object-fit:cover;display:block;transition:transform 0.3s;}
.collection-item:hover img{transform:scale(1.05);}
.overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.65);color:#fff;display:flex;flex-direction:column;justify-content:space-between;align-items:center;text-align:center;padding:12px 15px;opacity:0;transition:0.3s;}
.overlay-buttons{display:flex;gap:8px;width:100%;justify-content:center;padding-bottom:40px;flex-wrap:wrap;}
.collection-item:hover .overlay{opacity:1;}
.overlay h3,.overlay p,.overlay .price{margin:5px 0;}
.btn-detail{margin-top:10px;padding:10px 20px;background:#fff;color:#b5651d;text-decoration:none;border-radius:8px;display:inline-flex;align-items:center;gap:5px;}
.btn-solicita{margin-top:6px;padding:8px 16px;background:#b5651d;color:#fff;text-decoration:none;border-radius:8px;display:inline-flex;align-items:center;font-size:13px;}
.filter-btn{margin:0 5px 10px 5px;padding:8px 16px;background:#fff;color:#b5651d;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:0.3s;}
.filter-btn:hover{background:#b5651d;color:#fff;}
.filter-btn.active{background:#8b4315;color:#fff;}
.availability{font-weight:600;font-size:14px;margin:4px 0 0 0;}
.in-stock{color:#7CFC00;}
.out-stock{color:#ff6b6b;}
@media screen and (max-width:768px){
  #mainNav{position:static;transform:none;flex-direction:column;max-height:0;overflow:hidden;width:100%;gap:10px;margin-top:10px;}
  #mainNav.mobile-open{max-height:500px;}
  .menu-toggle{display:flex;}
}
</style>
</head>
<body>

<header id="header">
    <div style="display:flex;align-items:center;gap:15px;">
      <a href="Acasa.php" style="display:inline-flex;align-items:center;text-decoration:none;">
        <img src="Image41.png" alt="Logo" style="height:50px;">
      </a>
    </div>

    <nav id="mainNav">
      <a href="Acasa.php">Acasa</a>
      <a href="Colectie.php" class="active">Colectie</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php">Cosul Meu</a>
      <a href="ContulMeu.php">Contul Meu</a>
    </nav>

    <div class="menu-toggle" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
</header>

<h2 id="Colectie" style="text-align:center;margin-top:30px;">Colecția PsyGeometry Tehnology</h2>
<p style="text-align:center;">Explorează selecția noastră din acest magazin PSY minunat.</p>

<div style="text-align:center;margin:20px 0;" id="filters">
  <button class="filter-btn <?= $activeFilter === 'psygeometry' ? 'active' : '' ?>" data-filter="psygeometry">Mochete dreptunghiulare ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'ingerasi' ? 'active' : '' ?>" data-filter="ingerasi">Îngerași ai păcii</button>
  <button class="filter-btn <?= $activeFilter === 'mandala' ? 'active' : '' ?>" data-filter="mandala">Mandale ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'ingerasi-si-mandale-ale-pacii' ? 'active' : '' ?>" data-filter="ingerasi-si-mandale-ale-pacii">Ingerași și mandale ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'tablouri' ? 'active' : '' ?>" data-filter="tablouri">Tablouri ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'psygeometry-rotunde' ? 'active' : '' ?>" data-filter="psygeometry-rotunde">Mochete rotunde ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'rotunde' ? 'active' : '' ?>" data-filter="rotunde">Covoare rotunde ale păcii</button>
  <button class="filter-btn <?= $activeFilter === 'covoare' ? 'active' : '' ?>" data-filter="covoare">Covoare dreptunghiulare ale păcii</button>
</div>

<div class="collection">
<?php foreach ($collectionFinal as $c): ?>
  <?php
    $img      = $c['img'] ?? 'default.png';
    $imgPath  = __DIR__ . '/' . $img;
    $cacheBuster = is_file($imgPath) ? '?v=' . filemtime($imgPath) : '';
    $imgEsc   = htmlspecialchars($img . $cacheBuster, ENT_QUOTES, 'UTF-8');
    $typeEsc  = htmlspecialchars((string)($c['type_key'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nameEsc  = htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $descEsc  = htmlspecialchars((string)($c['desc'] ?? ''), ENT_QUOTES, 'UTF-8');
    $priceEsc = htmlspecialchars((string)($c['price_new'] ?? 'La cerere'), ENT_QUOTES, 'UTF-8');
    $isCerere = $c['price_is_cerere'] ?? false;
    $hidePrice = $c['hide_price'] ?? false;
    $productNameLower = safe_strtolower(preg_replace('/\s+/u', ' ', trim((string)($c['name'] ?? ''))));
    $showSolicitaOferta = $isCerere || $hidePrice || preg_match('/^ingeras\s*[2-5]$/u', $productNameLower) || $productNameLower === 'mandala 1';
    $contactUrl = 'Contact.php?produs=' . urlencode((string)($c['name'] ?? ''));
    $availabilityText = (string)($c['availability_text'] ?? '');
    $availabilityClass = (string)($c['availability_class'] ?? 'out-stock');
  ?>
  <div class="collection-item"
       data-type="<?= $typeEsc ?>"
       onclick="window.location='Detalii.php?item=<?= urlencode((string)$c['id']) ?>'"
       style="cursor:pointer;<?= ($c['type_key'] ?? '') === $activeFilter ? '' : 'display:none;' ?>">
    <img src="<?= $imgEsc ?>" alt="<?= $nameEsc ?>" loading="lazy">
    <div class="overlay">
      <p style="font-weight:600;font-size:14px;color:#ffd700;margin:4px 0;"><?= htmlspecialchars((string)($c['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <h3 style="margin:5px 0;font-size:18px;"><?= $nameEsc ?></h3>
      <p style="font-size:13px;line-height:1.5;margin:8px 0 8px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;"><?= $descEsc ?></p>

      <p class="availability <?= htmlspecialchars($availabilityClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($availabilityText, ENT_QUOTES, 'UTF-8') ?>
      </p>

      <?php if (!empty($c['show_price'])): ?>
        <p style="font-weight:700;font-size:15px;color:#ffd700;margin:0 0 10px 0;"><?= $priceEsc ?></p>
      <?php endif; ?>

      <div class="overlay-buttons">
        <?php if (!empty($c['id']) && (int)$c['id'] !== 0): ?>
          <a href="Detalii.php?item=<?= urlencode((string)$c['id']) ?>" class="btn-detail" onclick="event.stopPropagation();">Vezi Detalii</a>
        <?php endif; ?>
        <?php if ($showSolicitaOferta): ?>
          <a href="<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-solicita" onclick="event.stopPropagation();">Solicita oferta</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
function toggleMenu() {
  const nav = document.getElementById("mainNav");
  nav.classList.toggle("mobile-open");
}

document.addEventListener('DOMContentLoaded', () => {
  const filterButtons = Array.from(document.querySelectorAll('.filter-btn'));
  const items = Array.from(document.querySelectorAll('.collection-item'));

  function applyFilter(filter) {
    filterButtons.forEach(b => {
      b.classList.toggle('active', b.dataset.filter === filter);
    });

    items.forEach(item => {
      item.style.display = (item.dataset.type === filter) ? '' : 'none';
    });

    const url = new URL(window.location.href);
    url.searchParams.set('cat', filter);
    window.history.replaceState(null, '', url.toString());
  }

  const initialFilter =
    new URLSearchParams(window.location.search).get('cat') ||
    document.querySelector('.filter-btn.active')?.dataset.filter ||
    'psygeometry';

  applyFilter(initialFilter);

  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => applyFilter(btn.dataset.filter));
  });
});
</script>

</body>
</html>
