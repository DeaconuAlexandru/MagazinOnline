<?php
session_start();
$lang = 'ro';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en','ro','ru'])) $lang = $_GET['lang'];

// =========================
// Conexiune DB
// =========================
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

// Lista tuturor imaginilor disponibile în folder
$allImages = array_merge(glob('*.png'), glob('*.jpeg'), glob('*.jpg'));

// Mapare tipuri și câmpuri suplimentare pentru produsele din DB
foreach($collection as &$c){
    // Tip pentru filtrare
    if(strpos($c['name'], 'Persan') !== false){
        $c['type'] = 'Persan';
    } elseif(strpos($c['name'], 'Modern') !== false || strpos($c['name'], 'Artistic') !== false){
        $c['type'] = 'Modern';
    } else {
        $c['type'] = 'Tradițional';
    }

    // Descriere
    $c['desc'] = $c['description'];

    // Pret nou și vechi
    $c['price_new'] = number_format($c['price'], 2, ',', '') . ' Lei';
    $c['price_old'] = '-';

    // Imagine produs
    if(!empty($c['img']) && file_exists($c['img'])){
        $c['img'] = $c['img'];
    } elseif(!empty($allImages)){
        // fallback random din toate imaginile din folder
        $c['img'] = $allImages[array_rand($allImages)];
    } else {
        $c['img'] = 'default.png';
    }
}
unset($c);

// =========================
// Adaugă imaginile care nu apar în DB
// =========================
$usedImages = array_column($collection, 'img');
$collectionFinal = $collection; // începem cu produsele DB

foreach($allImages as $img){
    if(!in_array($img, $usedImages)){
        $collectionFinal[] = [
            'id' => 0,
            'name' => pathinfo($img, PATHINFO_FILENAME),
            'desc' => 'Covoar disponibil',
            'type' => 'Tradițional',
            'price_new' => 'La cerere',
            'price_old' => '-',
            'img' => $img
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
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
header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #8C92AC;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  padding: 15px 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
}

/* Logo stânga */
header .logo {
  display: flex;
  align-items: center;
  gap: 15px;
}

header .logo img {
  height: 50px;
}

/* NAV MENIU CENTRAT */
header nav {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  transition: max-height 0.5s ease;
}

header nav a {
  text-decoration: none;
  padding: 10px 18px;
  background: #fff;
  color: #b5651d;
  border-radius: 8px;
  font-weight: 500;
  transition: 0.3s;
}

header nav a:hover,
header nav a.active {
  background: #8b4315;
  color: #fff;
  transform: translateY(-2px);
}

/* ZONA DREAPTA */
.header-contact {
  display: flex;
  gap: 15px;
  align-items: center;
}

.header-contact .phone {
  font-weight: 600;
}

.header-contact .btn {
  background: #fff;
  color: #b5651d;
  padding: 12px 25px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  position: relative;
  transition: 0.3s;
}

.header-contact .btn:hover {
  background: #8b4315;
  color: #fff;
  transform: translateY(-2px);
}
/* Hamburger */
.menu-toggle {
  display: none;
  flex-direction: column;
  cursor: pointer;
  gap: 5px;
}
.menu-toggle div {
  width: 30px;
  height: 3px;
  background: #fff;
  transition: 0.3s;
}

/* Nav general */
header nav {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
  transition: max-height 0.3s ease;
}

/* Mobil */
@media screen and (max-width:768px){
  .menu-toggle {
    display: flex;
  }
  #mainNav {
    flex-direction: column;
    max-height: 0;
    overflow: hidden;
    width: 100%;
    gap: 10px;
  }
  #mainNav.mobile-open {
    max-height: 500px; /* ajustezi după câte linkuri ai */
  }
}
</style>
</head>
<body>
<header id="header">
  <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;">

    <!-- Logo stânga -->
    <div style="display:flex;align-items:center;gap:15px;">
      <img src="Image3.png" alt="Logo" style="height:50px;">
    </div>

    <!-- Meniu centrat -->
    <nav id="mainNav">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php"class="active">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
	<a href="CosulMeu.php">Cosul Meu</a>
	<a href="ContulMeu.php">Contul Meu</a>
    </nav>

        <!-- Hamburger mobil -->
        <div class="menu-toggle" onclick="toggleMenu()">
          <div></div>
          <div></div>
          <div></div>
        </div>


  </div>
</header>

<h2 style="text-align:center;margin-top:30px;">Colecția Noastră</h2>
<p style="text-align:center;">Explorează selecția noastră de covoare premium din categorii diverse</p>

<div style="text-align:center;margin:20px 0;">
    <button class="filter-btn active" data-filter="Toate">Toate</button>
    <button class="filter-btn" data-filter="Persan">Persane</button>
    <button class="filter-btn" data-filter="Modern">Moderne</button>
    <button class="filter-btn" data-filter="Tradițional">Tradiționale</button>
</div>

<div class="collection">
<?php foreach($collectionFinal as $c): ?>
  <div class="collection-item" data-type="<?= htmlspecialchars($c['type']) ?>">
    <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy">
    <div class="overlay">
      <p style="font-weight:600; font-size:14px; color:#ffd700;"><?= htmlspecialchars($c['type']) ?></p>
      <h3 style="margin:5px 0; font-size:18px;"><?= htmlspecialchars($c['name']) ?></h3>
      <p style="font-size:14px; line-height:1.4; max-height:60px; overflow:hidden;"><?= htmlspecialchars($c['desc']) ?></p>
      <p class="price" style="margin-top:8px;"><strong><?= htmlspecialchars($c['price_new']) ?></strong></p>
      <?php if($c['id'] != 0): ?>
      <a href="Detalii.php?item=<?= urlencode($c['id']) ?>" class="btn-detail">
        Vezi Detalii
      </a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
// =============================
// Filtrare covoare
// =============================
const filterButtons = document.querySelectorAll('.filter-btn');
const items = document.querySelectorAll('.collection-item');

filterButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    filterButtons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const filter = btn.dataset.filter;
    items.forEach(item => {
      item.style.display = (filter === 'Toate' || item.dataset.type === filter) ? 'block' : 'none';
    });
  });
});

// =============================
// Hamburger mobil
// =============================
function toggleMenu() {
  const nav = document.getElementById("mainNav");
  nav.classList.toggle("mobile-open");

  if (window.innerWidth <= 768) {
    // Arată doar linkurile selectate
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

// =============================
// Scroll highlight secțiune activă
// =============================
const sections = document.querySelectorAll('.hero, .services, .collection, .about, .contact');
const navLinks = document.querySelectorAll('nav a');

window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(section => {
    const sectionTop = section.offsetTop - 150;
    if (window.pageYOffset >= sectionTop) current = section.getAttribute('id');
  });
  navLinks.forEach(a => {
    a.classList.remove('active');
    if (a.getAttribute('href') === '#' + current) a.classList.add('active');
  });
});

// =============================
// Parallax hero
// =============================
const hero = document.querySelector('.hero');
if(hero){
  window.addEventListener('scroll', () => {
    hero.style.backgroundPositionY = -(window.scrollY * 0.3) + 'px';
  });
}
</script>
</body>
</html>
