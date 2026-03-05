<?php
// DEBUG: arată erori pentru a prinde rapid problemele
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) $lang = $_GET['lang'];

// Conexiune DB
$host = 'localhost';
$db   = 'magazi15_ShergeiCovoare';
$user = 'magazi15_Alex';
$pass = 'lFG;;pevW4DJ?zKD';
$charset = 'utf8mb4';

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

// =========================
// Preluare produse din DB
// =========================
$stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
$collection = $stmt->fetchAll();

// Lista tuturor imaginilor disponibile în folder (calea relativă)
$allImages = array_merge(
    glob('*.png') ?: [],
    glob('*.PNG') ?: [],
    glob('*.jpg') ?: [],
    glob('*.JPG') ?: [],
    glob('*.jpeg') ?: [],
    glob('*.JPEG') ?: []
);

// Funcții fallback pentru funcții multibyte (dacă nu există mbstring)
function safe_strtolower($s) {
    if ($s === null) return '';
    if (function_exists('mb_strtolower')) return mb_strtolower($s, 'UTF-8');
    return strtolower($s);
}
function safe_stripos($haystack, $needle) {
    if ($haystack === null) return false;
    if (function_exists('mb_stripos')) return mb_stripos($haystack, $needle);
    return stripos($haystack, $needle);
}

function detectType(array $row) {

$name = isset($row['name']) ? (string)$row['name'] : '';
$img  = isset($row['img']) ? (string)$row['img'] : '';

// folosim doar numele fisierului
$basename = strtolower(basename($img));

// ==============================
// extragem numarul din imagine
// ==============================
$imgNum = null;

if (preg_match('/(\d+)(?=\.[a-z]+$)/i', $basename, $match)) {
    $imgNum = (int)$match[1];
}

// =====================================
// 1. Mapping pe baza numerelor imaginii
// =====================================
if ($imgNum !== null) {

        // Tablouri doar PNG
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        
        if (($imgNum == 65 || $imgNum == 66) && $ext === 'png') {
            return 'Tablouri';
        }

    // Mandala
    if ($imgNum == 63 || $imgNum == 64) {
        return 'Mandala';
    }

    // Ingerasi
    if ($imgNum == 61 || $imgNum == 62) {
        return 'Ingerasi';
    }

    // PsyGeometry
    if ($imgNum >= 1 && $imgNum <= 60) {
        return 'PsyGeometry';
    }
}

// =====================================
// 2. Mapping pe baza cuvintelor
// =====================================
$keywords = [

    'Tablouri' => [
        'tablou','tablouri','tableau','painting','picture','image'
    ],

    'Ingerasi' => [
        'inger','ingeras','angel','cherub'
    ],

    'Mandala' => [
        'mandala','mandal'
    ],

    'PsyGeometry' => [
        'psy','geometry','psyg'
    ]
];

$lname = safe_strtolower($name . ' ' . $basename);

foreach ($keywords as $type => $words) {

    foreach ($words as $w) {

        if (safe_stripos($lname, $w) !== false) {
            return $type;
        }

        if (preg_match('/'.$w.'/iu', $lname)) {
            return $type;
        }
    }
}

// =====================================
// fallback final
// =====================================
return 'Traditional';


}


// Mapare tipuri și câmpuri suplimentare pentru produsele din DB
foreach($collection as &$c){

    // descriere
    $c['desc'] = $c['description'] ?? '';

    // preturi
    $c['price_new'] = isset($c['price'])
        ? number_format($c['price'],2,',','').' Lei'
        : 'La cerere';

    $c['price_old'] = '-';

    $imgFromDb = trim((string)($c['img'] ?? ''));
    $resolved = '';

    if ($imgFromDb !== '') {

        if (is_file($imgFromDb)) {
            $resolved = basename($imgFromDb);
        } else {

            $candidate = __DIR__ . '/' . ltrim($imgFromDb,'/');

            if (is_file($candidate)) {
                $resolved = basename($candidate);
            } else {

                $candidate2 = __DIR__ . '/' . basename($imgFromDb);

                if (is_file($candidate2)) {
                    $resolved = basename($candidate2);
                }
            }
        }
    }

    if ($resolved !== '') {
        $c['img'] = $resolved;
    } else {
        $c['img'] = 'default.png';
    }

    // IMPORTANT
    $c['type'] = detectType($c);
}

unset($c);

// =========================
// Adaugă imaginile care nu apar în DB
// =========================
$usedImages = array_column($collection, 'img');
$collectionFinal = $collection; // începem cu produsele DB

foreach($allImages as $img){

    $basename = strtolower(basename($img));
    $imgNum = null;

    if (preg_match('/(\d+)(?=\.[a-z]+$)/i', $basename, $match)) {
        $imgNum = (int)$match[1];
    }

    // nu generam produse fake pentru imaginile controlate din DB
    if ($imgNum >= 61 && $imgNum <= 66) {
        continue;
    }

    if(!in_array($img, $usedImages)){

        $fakeName = pathinfo($img, PATHINFO_FILENAME);

        $row = [
            'id' => 0,
            'name' => $fakeName,
            'description' => 'Covoar disponibil',
            'img' => $img,
            'price' => null
        ];

        $type = detectType($row);

        $collectionFinal[] = [
            'id' => 0,
            'name' => $fakeName,
            'desc' => 'Covoar disponibil',
            'type' => $type,
            'price_new' => 'La cerere',
            'price_old' => '-',
            'img' => $img
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sherghei Covoare - Colecție</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Roboto',sans-serif;color:#111;background:#fdf6f0;scroll-behavior:smooth;}
.dark-mode{background:#111;color:#fdf6f0;}
header{position:sticky;top:0;z-index:10;background:#8C92AC;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
header h1{font-family:'Montserrat',sans-serif;font-weight:700;font-size:30px;margin:0;}
nav{display:flex;justify-content:center;gap:15px;margin:20px 0;flex-wrap:wrap;}
nav a{text-decoration:none;padding:10px 18px;background:#fff;color:#b5651d;border-radius:8px;transition:.3s;font-weight:500;}
nav a.active{background:#8b4315;color:#fff;}
nav a:hover{background:#b5651d;color:#fff;transform:translateY(-2px);}
/* Colecție covoare */
.collection{display:flex;flex-wrap:wrap;justify-content:center;gap:20px;padding:50px 20px;}
.collection-item{position:relative;width:300px;border-radius:16px;overflow:hidden;cursor:pointer;transition:transform 0.3s;}
.collection-item img{width:100%;height:250px;object-fit:cover;display:block;transition:transform 0.3s;}
.collection-item:hover img{transform:scale(1.05);}
.overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.65);color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:15px;opacity:0;transition:0.3s;}
.collection-item:hover .overlay{opacity:1;}
.overlay h3, .overlay p, .overlay .price{margin:5px 0;}
.btn-detail{margin-top:10px;padding:10px 20px;background:#fff;color:#b5651d;text-decoration:none;border-radius:8px;display:flex;align-items:center;gap:5px;}
.btn-detail svg{width:16px;height:16px;}

/* Butoane filtrare */
.filter-btn{margin:0 5px 10px 5px;padding:8px 16px;background:#fff;color:#b5651d;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:0.3s;}
.filter-btn:hover{background:#b5651d;color:#fff;}
.filter-btn.active{background:#8b4315;color:#fff;}
/* HEADER GENERAL */
header{
  position:sticky;
  top:0;
  z-index:10;
  background:#8C92AC;
  color:#fff;
  box-shadow:0 2px 8px rgba(0,0,0,0.3);
  padding:15px 30px;
  display:flex;
  align-items:center;
  justify-content:flex-start;
  position:relative;
  flex-wrap:wrap;
}

/* Meniu centrat pe desktop */
#mainNav{
  position:absolute;
  left:50%;
  transform:translateX(-50%);
  display:flex;
  gap:15px;
}

/* Hamburger în dreapta */
.menu-toggle{
  display:none;
  flex-direction:column;
  gap:5px;
  cursor:pointer;
  margin-left:auto;
}

.menu-toggle div{
  width:25px;
  height:3px;
  background:#fff;
}

/* Mobil */
@media screen and (max-width:768px){

  #mainNav{
    position:static;
    transform:none;
    flex-direction:column;
    max-height:0;
    overflow:hidden;
    width:100%;
    gap:10px;
  }

  #mainNav.mobile-open{
    max-height:500px;
  }

  .menu-toggle{
    display:flex;
  }
}
</style>
</head>
<body>
<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <nav id="mainNav">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php" class="active">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php">Cosul Meu</a>
      <a href="ContulMeu.php">Contul Meu</a>
    </nav>

    <div class="menu-toggle" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </div>
</header>

<h2 style="text-align:center;margin-top:30px;">Colecția PsyGeometry Tehnology</h2>
<p style="text-align:center;">Explorează selecția noastră din acest magazin PSY minunat.</p>

    <div style="text-align:center;margin:20px 0;">
        <button class="filter-btn active" data-filter="PsyGeometry">Covoare ale păcii</button>
        <button class="filter-btn" data-filter="Ingerasi">Îngerași ai păcii</button>
        <button class="filter-btn" data-filter="Mandala">Mandale ale păcii</button>
        <button class="filter-btn" data-filter="Tablouri">Tablouri ale păcii</button>
    </div>

<div class="collection">
<?php foreach($collectionFinal as $c): 
    // imagine si cache buster
    $img = $c['img'] ?? 'default.png';
    $cacheBuster = '';
    if (!empty($img) && is_file($img)) {
        $cacheBuster = '?v=' . filemtime($img);
    }
    $imgEsc = htmlspecialchars($img . $cacheBuster);
?>
  <div class="collection-item" data-type="<?= htmlspecialchars($c['type']) ?>">
    <img src="<?= $imgEsc ?>" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy">
    <div class="overlay">
      <p style="font-weight:600; font-size:14px; color:#ffd700;"><?= htmlspecialchars($c['type']) ?></p>
      <h3 style="margin:5px 0; font-size:18px;"><?= htmlspecialchars($c['name']) ?></h3>
      <p style="font-size:14px; line-height:1.4; max-height:60px; overflow:hidden;"><?= htmlspecialchars($c['desc']) ?></p>
      <p class="price" style="margin-top:8px;"><strong><?= htmlspecialchars($c['price_new']) ?></strong></p>
      <?php if(!empty($c['id']) && $c['id'] != 0): ?>
      <a href="Detalii.php?item=<?= urlencode($c['id']) ?>" class="btn-detail">Vezi Detalii</a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
// Filtrare covoare, fără buton "Toate"
const filterButtons = document.querySelectorAll('.filter-btn');
const items = document.querySelectorAll('.collection-item');

function applyFilter(btn) {
  filterButtons.forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const filter = btn.dataset.filter;
  items.forEach(item => {
    item.style.display = (item.dataset.type === filter) ? 'block' : 'none';
  });
}

filterButtons.forEach(btn => {
  btn.addEventListener('click', () => applyFilter(btn));
});

// aplicăm filtrul implicit la încărcare, pe butonul marcat active
document.addEventListener('DOMContentLoaded', () => {
  const activeBtn = document.querySelector('.filter-btn.active') || document.querySelector('.filter-btn');
  if (activeBtn) applyFilter(activeBtn);
});

// Hamburger mobil
function toggleMenu() {
  const nav = document.getElementById("mainNav");
  nav.classList.toggle("mobile-open");

  if (window.innerWidth <= 768) {
    const allowedLinks = ['Acasa.php','Colectie.php','CosulMeu.php','ContulMeu.php'];
    const allLinks = Array.from(nav.querySelectorAll('a'));
    allLinks.forEach(a => {
      if (allowedLinks.includes(a.getAttribute('href'))) {
        a.style.display = "block";
      } else {
        a.style.display = "none";
      }
    });
  }
}
window.toggleMenu = toggleMenu;
</script>
</body>
</html>