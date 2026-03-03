<?php
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
    die("Conexiune baza de date esuata: " . $e->getMessage());
}

// AJAX: Ștergere mesaj
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id=?");
    $stmt->execute([$id]);
    exit('deleted');
}

// AJAX: Actualizare status
if(isset($_POST['update_status'])){
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    exit('updated');
}

// AJAX: Actualizare mesaj
if(isset($_POST['update_message'])){
    $id = (int)$_POST['id'];
    $message = $_POST['message'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET message=? WHERE id=?");
    $stmt->execute([$message, $id]);
    exit('updated');
}

// Preluare mesaje
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mesaje Contact - Admin</title>
<style>
body {margin:0; font-family:Arial,sans-serif; background:#fdf6f0; color:#111;}
header {background:#b5651d; color:#fff; padding:15px 20px; text-align:center;}
header h1 {margin:0; font-size:28px;}
.container {padding:20px; max-width:1200px; margin:auto;}
table {width:100%; border-collapse:collapse; margin-top:20px;}
th, td {padding:12px; border:1px solid #ccc; text-align:left; vertical-align:top;}
th {background:#b5651d; color:#fff;}
tr:nth-child(even) {background:#f9f4ef;}
tr:hover {background:#e5dcd1;}
select, textarea {padding:5px; border-radius:4px;}
button {padding:5px 10px; background:#b5651d; color:#fff; border:none; border-radius:4px; cursor:pointer;}
button:hover {background:#8b4315;}
textarea {width:100%; height:60px;}
.status {padding:5px 10px; border-radius:4px; color:#fff; text-align:center;}
.status.curs {background:#f0ad4e;}
.status.courier {background:#5bc0de;}
.status.livrat {background:#5cb85c;}
a.btn {display:inline-block; padding:8px 15px; background:#b5651d; color:#fff; border-radius:6px; text-decoration:none; margin:2px;}
a.btn:hover {background:#8b4315;}
@media screen and (max-width:768px) { table, th, td {font-size:14px;} textarea {height:50px;} }
</style>
</head>
<body>

<header>
<h1>Mesaje Contact - Admin</h1>
</header>

<div class="container">
<table>
<tr>
<th>ID</th>
<th>Nume</th>
<th>Telefon</th>
<th>Email</th>
<th>Mesaj</th>
<th>Data</th>
<th>Status</th>
<th>Acțiuni</th>
</tr>
<?php foreach($messages as $m){ 
    $class = '';
    if($m['status']=='În curs de livrare') $class='curs';
    elseif($m['status']=='Primit de Courier') $class='courier';
    elseif($m['status']=='Livrat') $class='livrat';
?>
<tr id="row-<?= $m['id'] ?>">
<td><?= $m['id'] ?></td>
<td><?= htmlspecialchars($m['name']) ?></td>
<td><?= htmlspecialchars($m['phone']) ?></td>
<td><?= htmlspecialchars($m['email']) ?></td>

<td>
<textarea onchange="updateMessage(<?= $m['id'] ?>, this.value)"><?= htmlspecialchars($m['message']) ?></textarea>
</td>

<td><?= $m['created_at'] ?></td>

<td class="status <?= $class ?>">
<select onchange="updateStatus(<?= $m['id'] ?>, this.value)">
<option value="În curs de livrare" <?= ($m['status']=='În curs de livrare')?'selected':'' ?>>În curs de livrare</option>
<option value="Primit de Courier" <?= ($m['status']=='Primit de Courier')?'selected':'' ?>>Primit de Courier</option>
<option value="Livrat" <?= ($m['status']=='Livrat')?'selected':'' ?>>Livrat</option>
</select>
</td>

<td>
<a href="#" onclick="deleteMessage(<?= $m['id'] ?>)" class="btn">Șterge</a>
</td>
</tr>
<?php } ?>
</table>
</div>

<script>
function updateStatus(id, status){
    fetch('admin_messages.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'update_status=1&id='+id+'&status='+encodeURIComponent(status)
    }).then(r=>r.text()).then(r=>{
        const td = document.querySelector('#row-'+id+' .status');
        td.className='status';
        if(status=='În curs de livrare') td.classList.add('curs');
        else if(status=='Primit de Courier') td.classList.add('courier');
        else if(status=='Livrat') td.classList.add('livrat');
    });
}

function updateMessage(id, message){
    fetch('admin_messages.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'update_message=1&id='+id+'&message='+encodeURIComponent(message)
    });
}

function deleteMessage(id){
    if(confirm('Sigur vrei să ștergi acest mesaj?')){
        fetch('admin_messages.php?delete='+id)
        .then(r=>r.text())
        .then(r=>{
            document.getElementById('row-'+id).remove();
        });
    }
}
</script>

</body>
</html>

