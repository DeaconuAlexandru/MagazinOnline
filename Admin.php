<?php
declare(strict_types=1);
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['post_login_redirect'] = 'Admin.php';
    header('Location: Login.php?redirect=Admin.php');
    exit;
}

$ADMIN_ALLOWED_EMAILS = [
    'deaconunicolaealexandru@gmail.com',
    'Ownvision211@gmail.com',
];

function admin_email_allowed(?string $email, array $allowedEmails): bool {
    $email = strtolower(trim((string)$email));
    if ($email === '') {
        return false;
    }

    foreach ($allowedEmails as $allowed) {
        if ($email === strtolower(trim((string)$allowed))) {
            return true;
        }
    }

    return false;
}

$currentAdminEmail = (string)($_SESSION['user_email'] ?? '');
if (!admin_email_allowed($currentAdminEmail, $ADMIN_ALLOWED_EMAILS)) {
    header('Location: Acasa.php');
    exit;
}


try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Eroare DB: " . $e->getMessage());
}

$categories = [
    'PsyGeometry'         => 'Mochete dreptunghiulare ale pacii',
    'Ingerasi'            => 'Ingerasi ai pacii',
    'Mandala'             => 'Mandale ale pacii',
    'Tablouri'            => 'Tablouri ale pacii',
    'PsyGeometry Rotunde' => 'Mochete rotunde ale pacii',
    'Rotunde'             => 'Covoare rotunde ale pacii',
    'Covoare'             => 'Covoare dreptunghiulare ale pacii',
];

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_order') {
    header('Content-Type: application/json');
    $order = $_POST['order'] ?? [];
    if (!is_array($order)) { echo json_encode(['ok' => false]); exit; }
    $stmt = $pdo->prepare("UPDATE products SET sort_ordine = :ord WHERE id = :id");
    foreach ($order as $sortPos => $productId) {
        $stmt->execute(['ord' => (int)$sortPos + 1, 'id' => (int)$productId]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) { $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]); echo json_encode(['ok' => true]); }
    else echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_product') {
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $preview     = trim($_POST['preview'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $material    = trim($_POST['material'] ?? '');
    $lungime     = trim($_POST['lungime_cm'] ?? '');
    $latime      = trim($_POST['latime_cm'] ?? '');
    $diameter    = trim($_POST['diameter_cm'] ?? '');
    $sort        = (int)($_POST['sort_ordine'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 1);
    $priceRaw    = trim($_POST['price'] ?? '');
    $priceVal    = null;
    if ($priceRaw !== '' && strtolower($priceRaw) !== 'la cerere') {
        $priceVal = (float)str_replace(',', '.', $priceRaw);
    }
    $imgName = '';
    if (!empty($_FILES['img']['name'])) {
        $ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
            $newName = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['img']['tmp_name'], __DIR__ . '/' . $newName)) $imgName = $newName;
        } else { $flash = 'Format imagine invalid.'; $flashType = 'error'; }
    }
    if ($imgName === '' && !empty($_POST['img_manual'])) $imgName = trim($_POST['img_manual']);
    if ($flash === '' && $name !== '' && $category !== '') {
        $pdo->prepare("INSERT INTO products (name, category, preview, description, material, price, lungime_cm, latime_cm, diameter_cm, img, sort_ordine, stock) VALUES (:name,:category,:preview,:description,:material,:price,:lungime,:latime,:diameter,:img,:sort,:stock)")
            ->execute([
                'name'=>$name,'category'=>$category,'preview'=>$preview,'description'=>$description,
                'material'=>$material,'price'=>$priceVal,
                'lungime'=>$lungime!==''?(float)str_replace(',','.',$lungime):null,
                'latime' =>$latime !==''?(float)str_replace(',','.',$latime) :null,
                'diameter'=>$diameter!==''?(float)str_replace(',','.',$diameter):null,
                'img'=>$imgName,'sort'=>$sort,'stock'=>$stock,
            ]);
        $flash = 'Produsul "' . htmlspecialchars($name) . '" a fost adaugat!';
    } elseif ($flash === '') { $flash = 'Completeaza Numele si Categoria!'; $flashType = 'error'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_product') {
    $id          = (int)($_POST['edit_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $preview     = trim($_POST['preview'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $material    = trim($_POST['material'] ?? '');
    $lungime     = trim($_POST['lungime_cm'] ?? '');
    $latime      = trim($_POST['latime_cm'] ?? '');
    $diameter    = trim($_POST['diameter_cm'] ?? '');
    $sort        = (int)($_POST['sort_ordine'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 1);
    $priceRaw    = trim($_POST['price'] ?? '');
    $priceVal    = null;
    if ($priceRaw !== '' && strtolower($priceRaw) !== 'la cerere') $priceVal = (float)str_replace(',','.',$priceRaw);
    $imgName = trim($_POST['img_current'] ?? '');
    if (!empty($_FILES['img']['name'])) {
        $ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
            $newName = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['img']['tmp_name'], __DIR__ . '/' . $newName)) $imgName = $newName;
        }
    }
    if ($id > 0 && $name !== '' && $category !== '') {
        $pdo->prepare("UPDATE products SET name=:name,category=:category,preview=:preview,description=:description,material=:material,price=:price,lungime_cm=:lungime,latime_cm=:latime,diameter_cm=:diameter,img=:img,sort_ordine=:sort,stock=:stock WHERE id=:id")
            ->execute([
                'name'=>$name,'category'=>$category,'preview'=>$preview,'description'=>$description,
                'material'=>$material,'price'=>$priceVal,
                'lungime'=>$lungime!==''?(float)str_replace(',','.',$lungime):null,
                'latime' =>$latime !==''?(float)str_replace(',','.',$latime) :null,
                'diameter'=>$diameter!==''?(float)str_replace(',','.',$diameter):null,
                'img'=>$imgName,'sort'=>$sort,'stock'=>$stock,'id'=>$id
            ]);
        $flash = 'Produsul a fost actualizat!';
    }
}

$filterCat = trim($_GET['cat'] ?? '');
$search    = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
if ($filterCat !== '') { $sql .= " AND category = :cat"; $params['cat'] = $filterCat; }
if ($search !== '')    { $sql .= " AND name LIKE :search"; $params['search'] = '%'.$search.'%'; }
$sql .= " ORDER BY sort_ordine = 0, sort_ordine ASC, id ASC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll();

$editProduct = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $editProduct = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Sherghei Covoare</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;font-family:'Roboto',sans-serif;background:#f0f2f5;color:#222;}
header{background:#8C92AC;color:#fff;padding:15px 30px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,0.2);flex-wrap:wrap;gap:10px;}
header h1{margin:0;font-family:'Montserrat',sans-serif;font-size:20px;}
header a{color:#fff;text-decoration:none;background:rgba(255,255,255,0.2);padding:7px 14px;border-radius:8px;font-size:13px;}
header a:hover{background:rgba(255,255,255,0.35);}
.container{max-width:1400px;margin:30px auto;padding:0 20px;}
.flash{padding:14px 20px;border-radius:10px;margin-bottom:20px;font-weight:600;}
.flash.success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
.flash.error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
.admin-grid{display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start;}
@media(max-width:900px){.admin-grid{grid-template-columns:1fr;}}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,0.08);padding:24px;}
.card h2{margin:0 0 20px;font-family:'Montserrat',sans-serif;font-size:18px;color:#8b4315;border-bottom:2px solid #f0e6da;padding-bottom:10px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-weight:600;margin-bottom:5px;font-size:14px;color:#555;}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 12px;border-radius:8px;border:1px solid #ddd;font-size:14px;font-family:'Roboto',sans-serif;transition:0.2s;}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#b5651d;box-shadow:0 0 0 3px rgba(181,101,29,0.1);outline:none;}
.form-group textarea{resize:vertical;min-height:80px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.price-toggle{display:flex;gap:15px;margin-bottom:8px;}
.price-toggle label{display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px;}
.btn-submit{width:100%;padding:12px;background:#b5651d;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:0.2s;margin-top:5px;}
.btn-submit:hover{background:#8b4315;}
.btn-submit.edit-mode{background:#1976D2;}
.btn-submit.edit-mode:hover{background:#1565C0;}
.cancel-edit{display:block;text-align:center;margin-top:8px;color:#888;text-decoration:none;font-size:14px;}
.cancel-edit:hover{color:#b5651d;}
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filters input{padding:9px 14px;border-radius:8px;border:1px solid #ddd;font-size:14px;min-width:180px;}
.filters select{padding:9px 14px;border-radius:8px;border:1px solid #ddd;font-size:14px;}
.btn-filter{padding:9px 18px;background:#8C92AC;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;}
.btn-filter:hover{background:#6b7190;}
.products-table{width:100%;border-collapse:collapse;font-size:14px;}
.products-table th{background:#8b4315;color:#fff;padding:10px 12px;text-align:left;font-weight:600;}
.products-table td{padding:10px 12px;border-bottom:1px solid #eee;vertical-align:middle;}
.products-table tr:hover td{background:#fdf6f0;}
.products-table img{width:55px;height:45px;object-fit:cover;border-radius:6px;}
.drag-row{cursor:grab;transition:background 0.15s;}
.drag-row.dragging{opacity:0.35;background:#fdf0e6;}
.drag-row.drag-over{background:#fdebd0;border-top:3px solid #b5651d;}
.drag-handle{color:#ccc;font-size:20px;cursor:grab;user-select:none;padding:0 6px;}
.drag-handle:hover{color:#b5651d;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-pret{background:#e8f5e9;color:#2e7d32;}
.badge-cerere{background:#fff3cd;color:#856404;}
.badge-stock{background:#d4edda;color:#155724;}
.badge-nostock{background:#f8d7da;color:#721c24;}
.btn-edit{padding:5px 12px;background:#1976D2;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block;}
.btn-edit:hover{background:#1565C0;}
.btn-delete{padding:5px 12px;background:#e53935;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;}
.btn-delete:hover{background:#b71c1c;}
.table-wrapper{overflow-x:auto;}
.prod-count{color:#888;font-size:13px;margin-bottom:10px;}
.save-order-bar{display:none;position:fixed;bottom:24px;right:24px;background:#b5651d;color:#fff;padding:14px 22px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.25);z-index:9999;font-weight:700;cursor:pointer;align-items:center;gap:10px;font-size:15px;}
.save-order-bar:hover{background:#8b4315;transform:translateY(-2px);}
.save-order-bar.visible{display:flex;}
</style>
</head>
<body>
<header>
  <h1>Admin Panel - Sherghei Covoare</h1>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <a href="Colectie.php">Colectie</a>
    <a href="Acasa.php">Acasa</a>
  </div>
</header>
<div class="container">
  <?php if ($flash !== ''): ?>
    <div class="flash <?= $flashType ?>"><?= $flash ?></div>
  <?php endif; ?>
  <div class="admin-grid">
    <div class="card">
      <h2><?= $editProduct ? 'Editeaza produsul' : 'Adauga produs nou' ?></h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editProduct ? 'edit_product' : 'add_product' ?>">
        <?php if ($editProduct): ?>
          <input type="hidden" name="edit_id" value="<?= (int)$editProduct['id'] ?>">
          <input type="hidden" name="img_current" value="<?= htmlspecialchars($editProduct['img'] ?? '') ?>">
        <?php endif; ?>
        <div class="form-group">
          <label>Nume produs *</label>
          <input type="text" name="name" required placeholder="ex: Mocheta Pace Interior" value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Categorie *</label>
          <select name="category" required>
            <option value="">-- Selecteaza --</option>
            <?php foreach ($categories as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= ($editProduct['category'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Pret</label>
          <div class="price-toggle">
            <label><input type="radio" name="price_type" value="suma" id="pt_suma" <?= (!$editProduct || (float)($editProduct['price'] ?? 0) > 0) ? 'checked' : '' ?>> Suma fixa (Lei)</label>
            <label><input type="radio" name="price_type" value="cerere" id="pt_cerere" <?= ($editProduct && (float)($editProduct['price'] ?? 0) <= 0) ? 'checked' : '' ?>> La cerere</label>
          </div>
          <input type="text" name="price" id="priceInput" placeholder="ex: 350" value="<?= ($editProduct && (float)($editProduct['price'] ?? 0) > 0) ? htmlspecialchars((string)$editProduct['price']) : '' ?>">
        </div>
        <div class="form-group">
          <label>Preview (text scurt pentru card)</label>
          <input type="text" name="preview" placeholder="O scurta descriere..." value="<?= htmlspecialchars($editProduct['preview'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Descriere completa</label>
          <textarea name="description" placeholder="Descriere detaliata..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Material / Componente</label>
          <input type="text" name="material" placeholder="ex: Mocheta printata, spate antiderapant" value="<?= htmlspecialchars($editProduct['material'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Lungime (cm)</label>
            <input type="text" name="lungime_cm" placeholder="160" value="<?= htmlspecialchars((string)($editProduct['lungime_cm'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label>Latime (cm)</label>
            <input type="text" name="latime_cm" placeholder="100" value="<?= htmlspecialchars((string)($editProduct['latime_cm'] ?? '')) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Diametru (cm) - doar rotunde</label>
          <input type="text" name="diameter_cm" placeholder="120" value="<?= htmlspecialchars((string)($editProduct['diameter_cm'] ?? '')) ?>">
        </div>
        <div class="form-group">
          <label>Imagine - upload fisier nou</label>
          <input type="file" name="img" accept="image/*">
          <?php if ($editProduct && !empty($editProduct['img'])): ?>
            <small style="color:#888;display:block;margin-top:4px;">Actuala: <?= htmlspecialchars($editProduct['img']) ?></small>
          <?php endif; ?>
        </div>
        <?php if (!$editProduct): ?>
        <div class="form-group">
          <label>SAU - Nume fisier existent pe server</label>
          <input type="text" name="img_manual" placeholder="ex: Image55.png">
        </div>
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>Sort ordine</label>
            <input type="number" name="sort_ordine" min="0" value="<?= (int)($editProduct['sort_ordine'] ?? 0) ?>">
          </div>
          <div class="form-group">
            <label>Stoc</label>
            <select name="stock">
              <option value="1" <?= (($editProduct['stock'] ?? 1) > 0) ? 'selected' : '' ?>>Disponibil</option>
              <option value="0" <?= (isset($editProduct['stock']) && (int)$editProduct['stock'] === 0) ? 'selected' : '' ?>>Epuizat</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn-submit <?= $editProduct ? 'edit-mode' : '' ?>">
          <?= $editProduct ? 'Salveaza modificarile' : 'Adauga produsul' ?>
        </button>
        <?php if ($editProduct): ?>
          <a href="Admin.php" class="cancel-edit">Anuleaza editarea</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="card">
      <h2>Produse</h2>
      <form method="get" class="filters">
        <input type="text" name="search" placeholder="Cauta dupa nume..." value="<?= htmlspecialchars($search) ?>">
        <select name="cat">
          <option value="">Toate categoriile</option>
          <?php foreach ($categories as $val => $label): ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $filterCat === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-filter">Filtreaza</button>
        <a href="Admin.php" style="color:#888;font-size:14px;text-decoration:none;">Reseteaza</a>
      </form>
      <p class="prod-count"><?= count($products) ?> produse - Trage randurile pentru reordonare</p>
      <div class="table-wrapper">
        <table class="products-table">
          <thead>
            <tr><th>drag</th><th>Img</th><th>Nume</th><th>Categorie</th><th>Pret</th><th>Dim.</th><th>Stoc</th><th>Sort</th><th>Actiuni</th></tr>
          </thead>
          <tbody id="sortable">
          <?php foreach ($products as $p):
            $dim = '';
            if (!empty($p['lungime_cm']) && !empty($p['latime_cm'])) $dim = $p['lungime_cm'].'x'.$p['latime_cm'].' cm';
            elseif (!empty($p['diameter_cm'])) $dim = 'diam '.$p['diameter_cm'].' cm';
          ?>
          <tr class="drag-row" data-id="<?= (int)$p['id'] ?>">
            <td><span class="drag-handle">::::</span></td>
            <td><img src="<?= htmlspecialchars($p['img'] ?? '') ?>" alt="" onerror="this.src='default.png'"></td>
            <td><strong><?= htmlspecialchars($p['name'] ?? '') ?></strong><br><small style="color:#aaa;">#<?= (int)$p['id'] ?></small></td>
            <td style="font-size:13px;"><?= htmlspecialchars($p['category'] ?? '') ?></td>
            <td>
              <?php if ((float)($p['price'] ?? 0) > 0): ?>
                <span class="badge badge-pret"><?= number_format((float)$p['price'],2,',','') ?> Lei</span>
              <?php else: ?>
                <span class="badge badge-cerere">La cerere</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#777;"><?= $dim ?: '-' ?></td>
            <td>
              <?php if ((int)($p['stock'] ?? 0) > 0): ?>
                <span class="badge badge-stock">OK</span>
              <?php else: ?>
                <span class="badge badge-nostock">Epuizat</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#aaa;"><?= (int)$p['sort_ordine'] ?></td>
            <td style="white-space:nowrap;">
              <a href="Admin.php?edit=<?= (int)$p['id'] ?><?= $filterCat ? '&cat='.urlencode($filterCat) : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="btn-edit">Edit</a>
              &nbsp;
              <button class="btn-delete" onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'] ?? '')) ?>')">Sterge</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="save-order-bar" id="saveOrderBar" onclick="saveOrder()">Salveaza ordinea noua</div>
<script>
let dragSrc = null;
function initDragDrop() {
  document.querySelectorAll('.drag-row').forEach(row => {
    row.setAttribute('draggable', 'true');
    row.addEventListener('dragstart', function(e) { dragSrc = this; this.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
    row.addEventListener('dragend', function() { this.classList.remove('dragging'); document.querySelectorAll('.drag-row').forEach(r => r.classList.remove('drag-over')); });
    row.addEventListener('dragover', function(e) { e.preventDefault(); document.querySelectorAll('.drag-row').forEach(r => r.classList.remove('drag-over')); if (this !== dragSrc) this.classList.add('drag-over'); });
    row.addEventListener('drop', function(e) {
      e.preventDefault();
      if (this === dragSrc) return;
      const tbody = document.getElementById('sortable');
      const rows = Array.from(tbody.querySelectorAll('.drag-row'));
      if (rows.indexOf(dragSrc) < rows.indexOf(this)) tbody.insertBefore(dragSrc, this.nextSibling);
      else tbody.insertBefore(dragSrc, this);
      this.classList.remove('drag-over');
      document.getElementById('saveOrderBar').classList.add('visible');
    });
  });
}
function saveOrder() {
  const order = Array.from(document.querySelectorAll('#sortable .drag-row')).map(r => r.dataset.id);
  const fd = new FormData();
  fd.append('action', 'save_order');
  order.forEach(id => fd.append('order[]', id));
  fetch(location.pathname, { method:'POST', body:fd }).then(r => r.json()).then(data => {
    if (data.ok) {
      const bar = document.getElementById('saveOrderBar');
      bar.innerHTML = 'Ordine salvata!';
      bar.style.background = '#2e7d32';
      setTimeout(() => { bar.classList.remove('visible'); bar.style.background=''; bar.innerHTML='Salveaza ordinea noua'; }, 2500);
    }
  });
}
function deleteProduct(id, name) {
  if (!confirm('Stergi "' + name + '"? Nu poate fi anulat!')) return;
  const fd = new FormData();
  fd.append('action', 'delete_product');
  fd.append('id', id);
  fetch(location.pathname, { method:'POST', body:fd }).then(r => r.json()).then(data => {
    if (data.ok) {
      const row = document.querySelector('.drag-row[data-id="' + id + '"]');
      if (row) { row.style.transition='opacity 0.3s'; row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
    }
  });
}
document.addEventListener('DOMContentLoaded', function() {
  initDragDrop();
  const ptSuma = document.getElementById('pt_suma');
  const ptCerere = document.getElementById('pt_cerere');
  const priceInp = document.getElementById('priceInput');
  function syncPrice() {
    if (!ptCerere || !priceInp) return;
    if (ptCerere.checked) { priceInp.value=''; priceInp.disabled=true; priceInp.placeholder='La cerere'; }
    else { priceInp.disabled=false; priceInp.placeholder='ex: 350'; }
  }
  if (ptSuma) ptSuma.addEventListener('change', syncPrice);
  if (ptCerere) ptCerere.addEventListener('change', syncPrice);
  syncPrice();
});
</script>
</body>
</html>
