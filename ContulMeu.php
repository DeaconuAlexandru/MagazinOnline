<?php
declare(strict_types=1);

session_start();


try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset={$charset}",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit("Eroare DB: " . $e->getMessage());
}

/* ================= HELPERE ================= */
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sourceLabel(string $source): string
{
    switch ($source) {
        case 'youtube':
            return 'YouTube';
        case 'instagram':
            return 'Instagram';
        case 'facebook':
            return 'Facebook';
        case 'tiktok':
            return 'TikTok';
        case 'direct':
            return 'Direct';
        default:
            return 'Other';
    }
}

function platformLabel(string $platform): string
{
    return sourceLabel($platform);
}

function buildTrackUrl(?int $linkId): string
{
    if (!$linkId) {
        return '#';
    }
    return 'ContulMeu.php?track=1&link_id=' . (int)$linkId;
}

function upsertDailyClick(PDO $pdo, int $linkId, bool $uniqueFlag): void
{
    $stmt = $pdo->prepare("
        SELECT id, clicks_total, clicks_unique
        FROM social_daily_stats
        WHERE link_id = ? AND stat_date = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$linkId]);
    $row = $stmt->fetch();

    if ($row) {
        $stmt = $pdo->prepare("
            UPDATE social_daily_stats
            SET clicks_total = clicks_total + 1,
                clicks_unique = clicks_unique + ?
            WHERE id = ?
        ");
        $stmt->execute([$uniqueFlag ? 1 : 0, (int)$row['id']]);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO social_daily_stats (link_id, stat_date, clicks_total, clicks_unique)
        VALUES (?, CURDATE(), 1, ?)
    ");
    $stmt->execute([$linkId, $uniqueFlag ? 1 : 0]);
}

/* ================= LOGARE VIZITE (guest / logged_in) ================= */
require_once __DIR__ . '/social_tracker.php';
recordSocialVisit($pdo);

/* ================= LOGOUT ================= */
if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header("Location: Login.php");
    exit;
}

/* ================= VERIFICARE LOGIN ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit;
}

/* ================= DATE UTILIZATOR LOGAT ================= */
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: Login.php");
    exit;
}

$username = trim((string)($user['username'] ?? ''));
$email = trim((string)($user['email'] ?? ''));
$displayName = $username !== '' ? $username : 'Contul meu';

/* ================= ACCES MONITORIZARE DOAR PENTRU 2 EMAILURI ================= */
$allowedEmails = [
    'deaconunicolaealexandru@gmail.com',
    'Ownvision211@gmail.com',
];
$canSeeMonitor = in_array($email, $allowedEmails, true);

/* ================= LINKURI SOCIALE DEFAULT / SEED ================= */
$defaultSocialLinks = [
    'youtube' => [
        'name' => 'YouTube',
        'target_url' => 'https://www.youtube.com/@IngerasiiPacii/shorts',
    ],
    'instagram' => [
        'name' => 'Instagram',
        'target_url' => 'https://www.instagram.com/magazin_psy/',
    ],
    'facebook' => [
        'name' => 'Facebook',
        'target_url' => 'https://www.facebook.com/people/MagazinPsy/61590363542827/',
    ],
    'tiktok' => [
        'name' => 'TikTok',
        'target_url' => 'https://www.tiktok.com/@lucrubinefacut',
    ],
];

try {
    $stmt = $pdo->query("SELECT id, name, platform, target_url, is_active FROM social_links");
    $existingLinks = $stmt->fetchAll();
} catch (Throwable $e) {
    $existingLinks = [];
}

$linksByPlatform = [];
foreach ($existingLinks as $link) {
    $platform = strtolower((string)($link['platform'] ?? ''));
    if ($platform !== '' && !isset($linksByPlatform[$platform])) {
        $linksByPlatform[$platform] = $link;
    }
}

/* Seed lipsă */
foreach ($defaultSocialLinks as $platform => $data) {
    if (!isset($linksByPlatform[$platform])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO social_links (name, platform, target_url, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([
                $data['name'],
                $platform,
                $data['target_url']
            ]);

            $newId = (int)$pdo->lastInsertId();
            $linksByPlatform[$platform] = [
                'id' => $newId,
                'name' => $data['name'],
                'platform' => $platform,
                'target_url' => $data['target_url'],
                'is_active' => 1,
            ];
        } catch (Throwable $e) {
            // silent fail
        }
    }
}

/* ================= TRACK CLICK (REDIRECT) ================= */
if (isset($_GET['track'], $_GET['link_id'])) {
    $linkId = (int)$_GET['link_id'];

    if ($linkId > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, target_url, is_active
                FROM social_links
                WHERE id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$linkId]);
            $link = $stmt->fetch();

            if ($link && !empty($link['target_url'])) {
                $ipHash = hashIp(getClientIp());
                $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
                $referrer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 2000);
                $countryCode = substr(getCountryCode(), 0, 10);
                $sessionKey = substr(getSessionKey(), 0, 128);

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM social_clicks
                    WHERE link_id = ?
                      AND session_key = ?
                      AND DATE(clicked_at) = CURDATE()
                    LIMIT 1
                ");
                $stmt->execute([$linkId, $sessionKey]);
                $alreadyClickedToday = (bool)$stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    INSERT INTO social_clicks
                        (link_id, clicked_at, ip_hash, user_agent, referrer, country_code, session_key)
                    VALUES
                        (?, NOW(), ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $linkId,
                    $ipHash,
                    $userAgent,
                    $referrer,
                    $countryCode,
                    $sessionKey
                ]);

                upsertDailyClick($pdo, $linkId, !$alreadyClickedToday);

                header('Location: ' . $link['target_url'], true, 302);
                exit;
            }
        } catch (Throwable $e) {
            // silent fail
        }
    }

    header("Location: Acasa.php", true, 302);
    exit;
}

/* ================= TRAFIC SOCIAL PE SITE ================= */
$monitorStats = [];
$monitorDaily = [];
$clickStatsByLink = [];
$clickDaily = [];

if ($canSeeMonitor) {
    try {
        $stmt = $pdo->query("
            SELECT
                source,
                COUNT(*) AS total_visits,
                COUNT(DISTINCT ip_hash) AS unique_visits,
                MAX(visited_at) AS last_visit
            FROM social_visits
            GROUP BY source
            ORDER BY FIELD(source, 'youtube', 'instagram', 'facebook', 'tiktok', 'direct', 'other')
        ");
        $monitorStats = $stmt->fetchAll();
    } catch (Throwable $e) {
        $monitorStats = [];
    }

    try {
        $stmt = $pdo->query("
            SELECT
                visit_date AS day,
                COUNT(*) AS total_visits,
                COUNT(DISTINCT ip_hash) AS unique_visits
            FROM social_visits
            GROUP BY visit_date
            ORDER BY day DESC
            LIMIT 30
        ");
        $monitorDaily = $stmt->fetchAll();
    } catch (Throwable $e) {
        $monitorDaily = [];
    }

    try {
        $stmt = $pdo->query("
            SELECT
                l.id,
                l.name,
                l.platform,
                l.target_url,
                l.is_active,
                COALESCE(COUNT(c.id), 0) AS total_clicks,
                COALESCE(COUNT(DISTINCT c.session_key), 0) AS unique_clicks,
                MAX(c.clicked_at) AS last_click
            FROM social_links l
            LEFT JOIN social_clicks c ON c.link_id = l.id
            GROUP BY l.id, l.name, l.platform, l.target_url, l.is_active
            ORDER BY FIELD(l.platform, 'youtube', 'instagram', 'facebook', 'tiktok'), l.id
        ");
        $clickStatsByLink = $stmt->fetchAll();
    } catch (Throwable $e) {
        $clickStatsByLink = [];
    }

    try {
        $stmt = $pdo->query("
            SELECT
                stat_date AS day,
                SUM(clicks_total) AS total_clicks,
                SUM(clicks_unique) AS unique_clicks
            FROM social_daily_stats
            GROUP BY stat_date
            ORDER BY day DESC
            LIMIT 30
        ");
        $clickDaily = $stmt->fetchAll();
    } catch (Throwable $e) {
        $clickDaily = [];
    }
}

$statsBySource = [];
foreach ($monitorStats as $row) {
    $statsBySource[(string)$row['source']] = $row;
}

$trafficLabels = ['YouTube', 'Instagram', 'Facebook', 'TikTok', 'Direct'];
$trafficTotals = [
    (int)($statsBySource['youtube']['total_visits'] ?? 0),
    (int)($statsBySource['instagram']['total_visits'] ?? 0),
    (int)($statsBySource['facebook']['total_visits'] ?? 0),
    (int)($statsBySource['tiktok']['total_visits'] ?? 0),
    (int)($statsBySource['direct']['total_visits'] ?? 0),
];
$trafficUnique = [
    (int)($statsBySource['youtube']['unique_visits'] ?? 0),
    (int)($statsBySource['instagram']['unique_visits'] ?? 0),
    (int)($statsBySource['facebook']['unique_visits'] ?? 0),
    (int)($statsBySource['tiktok']['unique_visits'] ?? 0),
    (int)($statsBySource['direct']['unique_visits'] ?? 0),
];

$trackedLinks = [];
foreach (['youtube', 'instagram', 'facebook', 'tiktok'] as $platform) {
    if (isset($linksByPlatform[$platform])) {
        $trackedLinks[$platform] = $linksByPlatform[$platform];
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contul Meu</title>
<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background: #fff;
  color: #333;
}

header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: #8C92AC;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  padding: 15px 30px;
}

header > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

header nav {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  flex: 1;
  flex-wrap: wrap;
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
}

header nav .dropdown {
  position: relative;
}

header nav .dropdown-content {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #fff;
  min-width: 180px;
  border-radius: 10px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.3);
  display: none;
  flex-direction: column;
  z-index: 2000;
}

header nav .dropdown-content a {
  background: transparent;
  color: #b5651d;
  padding: 12px 16px;
}

header nav .dropdown-content a:hover {
  background: #f5e6d8;
  color: #8b4315;
}

header nav .dropdown:hover .dropdown-content {
  display: flex;
}

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
  border-radius: 3px;
}

.myaccount {
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}

.account-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  margin-bottom: 30px;
  gap: 15px;
}

.account-info p {
  margin: 2px 0;
  font-weight: 500;
}

.account-info .fullname {
  font-weight: 700;
  font-size: 18px;
}

.account-footer {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.account-footer a,
.btn-primary {
  display: inline-block;
  background: #b5651d;
  color: #fff;
  padding: 12px 28px;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  transition: 0.3s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  border: none;
  cursor: pointer;
}

.account-footer a:hover,
.btn-primary:hover {
  background: #8b4315;
  transform: translateY(-2px) scale(1.03);
}

.section-title {
  margin: 35px 0 15px;
  font-size: 22px;
}

.account-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.card {
  background: #fff;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.card h3 {
  margin-top: 0;
  margin-bottom: 10px;
}

.card p {
  margin: 5px 0;
}

.monitor-section {
  margin-top: 30px;
}

.monitor-section h2 {
  margin-bottom: 15px;
}

.monitor-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.monitor-table th,
.monitor-table td {
  padding: 12px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

.monitor-table th {
  background: #8C92AC;
  color: #fff;
}

footer {
  background: #8C92AC;
  color: #fff;
  padding: 50px 20px;
  margin-top: 40px;
}

footer .footer-container {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  justify-content: flex-start;
  align-items: flex-start;
}

footer .footer-col {
  flex: 1;
  min-width: 200px;
}

footer .footer-logo img {
  height: 50px;
  margin-bottom: 15px;
}

footer .footer-social {
  display: flex;
  gap: 10px;
  margin-top: 5px;
}

footer .footer-social a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  color: #fff;
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
footer blockquote,
footer span,
footer li {
  color: #fff !important;
}

footer .footer-bottom {
  margin-top: 30px;
  border-top: 1px solid rgba(255,255,255,0.3);
  padding-top: 15px;
  font-size: 14px;
  text-align: left;
}

footer .footer-bottom a {
  color: #fff;
  text-decoration: none;
  margin: 0 8px;
}

body.dark-mode {
  background: #111;
  color: #eee;
}

body.dark-mode header {
  background: #222;
}

body.dark-mode header nav a {
  background: #333;
  color: #eee;
}

body.dark-mode header nav a:hover,
body.dark-mode header nav a.active {
  background: #555;
  color: #fff;
}

body.dark-mode .card {
  background: #1e1e1e;
  color: #eee;
}

body.dark-mode .monitor-table {
  background: #1e1e1e;
  color: #eee;
}

body.dark-mode .monitor-table th {
  background: #333;
}

body.dark-mode footer {
  background: #222;
}

.chart-card {
  max-width: 760px;
  margin: 20px auto;
  padding: 16px;
}

.chart-wrap {
  position: relative;
  width: 100%;
  height: 220px;
}

@media (max-width: 768px) {
  header nav {
    display: none;
    flex-direction: column;
    width: 100%;
    margin-top: 10px;
    gap: 10px;
  }

  header nav.mobile-open {
    display: flex;
  }

  .menu-toggle {
    display: flex;
  }

  .account-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  footer .footer-container {
    flex-direction: column;
    gap: 25px;
  }

  .chart-card {
    max-width: 100%;
    margin: 16px 0;
    padding: 14px;
  }

  .chart-wrap {
    height: 180px;
  }

  .section-title {
    font-size: 18px;
    margin: 24px 0 12px;
  }

  .card p {
    font-size: 14px;
    line-height: 1.4;
  }
}

@media (max-width: 480px) {
  .chart-wrap {
    height: 160px;
  }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header id="header">
  <div>
    <a href="Acasa.php" style="display:inline-flex;align-items:center;text-decoration:none;">
      <img src="Image41.png" alt="Logo" style="height:50px;">
    </a>

    <nav id="mainNav" aria-label="Meniu principal">
      <a href="Acasa.php">Acasă</a>
      <a href="Colectie.php">Colecție</a>
      <a href="DespreNoi.php">Despre Noi</a>
      <a href="Contact.php">Contact</a>
      <a href="CosulMeu.php">Cosul Meu</a>

      <div class="dropdown">
        <a href="ContulMeu.php" class="active"><?= h($displayName) ?> ▾</a>
        <div class="dropdown-content">
          <a href="ContulMeu.php">Profil</a>
          <a href="ContulMeu.php?logout=1">Deconectare</a>
        </div>
      </div>
    </nav>

    <div class="menu-toggle" onclick="toggleMenu()" aria-label="Deschide meniul mobil" role="button" tabindex="0">
      <div></div>
      <div></div>
      <div></div>
    </div>
  </div>
</header>

<div class="myaccount">
  <div class="account-header">
    <div class="account-info">
      <p class="fullname"><?= h($displayName) ?></p>
      <p>Utilizator logat</p>
    </div>
    <div class="account-footer">
      <a class="btn-primary" href="Acasa.php">Pagina principală</a>
      <a class="btn-primary" href="ContulMeu.php?logout=1">Deconectare</a>
    </div>
  </div>

  <h2 class="section-title">Trafic din social</h2>

  <div class="account-cards">
    <?php foreach (['youtube', 'instagram', 'facebook', 'tiktok', 'direct'] as $src): ?>
      <?php $row = $statsBySource[$src] ?? null; ?>
      <div class="card">
        <h3><?= h(sourceLabel($src)) ?></h3>
        <p>Total intrări: <?= (int)($row['total_visits'] ?? 0) ?></p>
        <p>Intrări unice: <?= (int)($row['unique_visits'] ?? 0) ?></p>
        <p>Ultima intrare: <?= h((string)($row['last_visit'] ?? '-')) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($canSeeMonitor): ?>
    <div class="monitor-section">
      <div class="card chart-card">
        <h3>Trafic pe platforme</h3>
        <div class="chart-wrap">
          <canvas id="socialTrafficChart"></canvas>
        </div>
      </div>

      <div class="card" style="margin-top:20px;">
        <h3>Evoluție pe zile</h3>
        <table class="monitor-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Intrări total</th>
              <th>Intrări unice</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($monitorDaily)): ?>
              <?php foreach ($monitorDaily as $row): ?>
                <tr>
                  <td><?= h((string)$row['day']) ?></td>
                  <td><?= (int)($row['total_visits'] ?? 0) ?></td>
                  <td><?= (int)($row['unique_visits'] ?? 0) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="3">Nu există date încă.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="card" style="margin-top:20px;">
        <h3>Clickuri pe linkurile sociale</h3>
        <table class="monitor-table">
          <thead>
            <tr>
              <th>Platformă</th>
              <th>Nume</th>
              <th>Total clickuri</th>
              <th>Clickuri unice</th>
              <th>Ultimul click</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($clickStatsByLink)): ?>
              <?php foreach ($clickStatsByLink as $row): ?>
                <tr>
                  <td><?= h((string)$row['platform']) ?></td>
                  <td><?= h((string)$row['name']) ?></td>
                  <td><?= (int)($row['total_clicks'] ?? 0) ?></td>
                  <td><?= (int)($row['unique_clicks'] ?? 0) ?></td>
                  <td><?= h((string)$row['last_click'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5">Nu există clickuri înregistrate încă.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="card" style="margin-top:20px;">
        <h3>Evoluție clickuri pe zile</h3>
        <table class="monitor-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Clickuri total</th>
              <th>Clickuri unice</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($clickDaily)): ?>
              <?php foreach ($clickDaily as $row): ?>
                <tr>
                  <td><?= h((string)$row['day']) ?></td>
                  <td><?= (int)($row['total_clicks'] ?? 0) ?></td>
                  <td><?= (int)($row['unique_clicks'] ?? 0) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="3">Nu există date încă.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<footer id="footer">
  <div class="footer-container">
    <div class="footer-col">
      <div class="footer-logo">
        <img src="Image41.png" alt="Sherghei Covoare">
      </div>
      <p>Primul an in care aducem covoare traditionale si moderne in casa ta.</p>

      <div class="footer-social">
        <?php if (isset($trackedLinks['facebook'])): ?>
          <a href="<?= h(buildTrackUrl((int)$trackedLinks['facebook']['id'])) ?>" target="_blank" rel="noopener" title="Facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v8h4v-8h3l1-4h-4V6a1 1 0 0 1 1-1h3z"/>
            </svg>
          </a>
        <?php endif; ?>

        <?php if (isset($trackedLinks['instagram'])): ?>
          <a href="<?= h(buildTrackUrl((int)$trackedLinks['instagram']['id'])) ?>" target="_blank" rel="noopener" title="Instagram">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
              <path d="M16 11.37a4 4 0 1 1-7.94 1.26 4 4 0 0 1 7.94-1.26z"/>
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
            </svg>
          </a>
        <?php endif; ?>

        <?php if (isset($trackedLinks['youtube'])): ?>
          <a href="<?= h(buildTrackUrl((int)$trackedLinks['youtube']['id'])) ?>" target="_blank" rel="noopener" title="YouTube">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.97C18.88 4 12 4 12 4s-6.88 0-8.59.45A2.78 2.78 0 0 0 1.46 6.42 29.5 29.5 0 0 0 1 12a29.5 29.5 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.97C5.12 20 12 20 12 20s6.88 0 8.59-.45a2.78 2.78 0 0 0 1.95-1.97A29.5 29.5 0 0 0 23 12a29.5 29.5 0 0 0-.46-5.58z"/>
              <polygon points="10,15 15,12 10,9"/>
            </svg>
          </a>
        <?php endif; ?>

        <?php if (isset($trackedLinks['tiktok'])): ?>
          <a href="<?= h(buildTrackUrl((int)$trackedLinks['tiktok']['id'])) ?>" target="_blank" rel="noopener" title="TikTok">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 8.5a4.5 4.5 0 0 1-4.5-4.5H9v11.5a3.5 3.5 0 1 1-3-3.46V9.9a6.5 6.5 0 1 0 7 6.4V8.5h3z"/>
            </svg>
          </a>
        <?php endif; ?>
      </div>
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
      <strong>Servicii</strong>
      <ul>
        <li><a href="#">Vanzare produse psygeometry</a></li>
        <li><a href="#">Consultanta gratuita</a></li>
        <li><a href="#">Livrare la domiciliu</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <strong>Contact</strong>
      <p>Adresa, Craiova,Dolj</p>
      <p>Telefon, 0753 508 461</p>
      <p>Email, office@magazinpsy.ro</p>
      <p>Program, Luni Sambata 9 18</p>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© 2024 Sherghei Covoare. Toate drepturile rezervate.</p>
    <a href="TermeniSiConditii.php">Termeni si conditii</a> |
    <a href="PoliticaDeConfidentialitate.php">Politica de confidentialitate</a>
  </div>
</footer>

<script>
(function(){
  if (localStorage.getItem('darkMode') === '1') {
    document.body.classList.add('dark-mode');
    const footer = document.getElementById('footer');
    if (footer) footer.classList.add('dark-mode');
  }

  const ALLOWED = new Set(['acasa.php','colectie.php','cosulmeu.php','contulmeu.php']);

  function norm(href){
    if(!href) return '';
    href = href.split('?')[0].split('#')[0].trim();
    const parts = href.split('/');
    const file = parts[parts.length - 1] || '';
    return decodeURIComponent(file).toLowerCase();
  }

  function updateNavVisibility(){
    const nav = document.getElementById('mainNav');
    if(!nav) return;
    const isMobile = window.innerWidth <= 768;
    const open = nav.classList.contains('mobile-open');

    Array.from(nav.children).forEach(child => {
      if(child.tagName === 'A'){
        const href = child.getAttribute('href') || '';
        const name = norm(href);
        if(!isMobile){
          child.style.display = '';
        } else {
          child.style.display = open && ALLOWED.has(name) ? 'block' : 'none';
        }
        return;
      }

      if(child.classList && child.classList.contains('dropdown')){
        const parentA = child.querySelector(':scope > a');
        const parentHref = parentA ? (parentA.getAttribute('href') || '') : '';
        const parentName = norm(parentHref);

        if(!isMobile){
          child.style.display = '';
          if(parentA) parentA.style.display = '';
          const dc = child.querySelector('.dropdown-content');
          if(dc) dc.style.display = '';
        } else {
          if(!open){
            child.style.display = 'none';
            if(parentA) parentA.style.display = 'none';
            const dc = child.querySelector('.dropdown-content');
            if(dc) dc.style.display = 'none';
          } else {
            if(ALLOWED.has(parentName)){
              child.style.display = '';
              if(parentA) parentA.style.display = 'block';
            } else {
              child.style.display = 'none';
              if(parentA) parentA.style.display = 'none';
            }
            const dc = child.querySelector('.dropdown-content');
            if(dc) dc.style.display = 'none';
          }
        }
        return;
      }
    });
  }

  window.toggleMenu = function(){
    const nav = document.getElementById('mainNav');
    if(!nav) return;
    nav.classList.toggle('mobile-open');
    updateNavVisibility();
  };

  document.addEventListener('click', (e) => {
    const nav = document.getElementById('mainNav');
    const toggle = document.querySelector('.menu-toggle');
    if(!nav) return;
    if(window.innerWidth > 768) return;
    if(nav.classList.contains('mobile-open') && !nav.contains(e.target) && !(toggle && toggle.contains(e.target))){
      nav.classList.remove('mobile-open');
      updateNavVisibility();
    }
  });

  let rt;
  window.addEventListener('resize', () => {
    clearTimeout(rt);
    rt = setTimeout(updateNavVisibility, 70);
  });

  window.addEventListener('orientationchange', () => setTimeout(updateNavVisibility, 120));

  document.addEventListener('DOMContentLoaded', () => {
    updateNavVisibility();
  });
})();

document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('socialTrafficChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($trafficLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [
          {
            label: 'Intrări totale',
            data: <?= json_encode($trafficTotals, JSON_UNESCAPED_UNICODE) ?>,
            borderWidth: 1
          },
          {
            label: 'Intrări unice',
            data: <?= json_encode($trafficUnique, JSON_UNESCAPED_UNICODE) ?>,
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            top: 8,
            right: 8,
            bottom: 0,
            left: 0
          }
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              boxWidth: 12,
              boxHeight: 12,
              font: {
                size: 11
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              font: {
                size: 11
              }
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              font: {
                size: 11
              }
            }
          }
        }
      }
    });
  }
});
</script>

</body>
</html>
