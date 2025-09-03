<?php
require __DIR__ . '/config.php';

// --- Helpers ---
function getClientIp(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function getUserAgent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

function todayDate(): string {
    return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d');
}

function initializeDb(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ip_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        ua_hash TEXT NOT NULL,
        submitted_at DATETIME NOT NULL
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ip_date ON ip_submissions(ip, ua_hash, submitted_at)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL UNIQUE,
        count INTEGER DEFAULT 0
    )");
}

function hasReachedLimit(PDO $pdo, string $ip, string $uaHash, string $date): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ? LIMIT 1");
    $stmt->execute([$ip, $uaHash, $date]);
    return (bool) $stmt->fetchColumn();
}

function recordSubmission(PDO $pdo, string $ip, string $uaHash, string $date): void {
    $stmt = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
    $stmt->execute([$ip, $uaHash]);
    $pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, 1)
                   ON CONFLICT(date) DO UPDATE SET count = count + 1")
        ->execute([$date]);
}

function getDailyCount(PDO $pdo, string $date): int {
    $stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ? LIMIT 1");
    $stmt->execute([$date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['count'] : 0;
}

function callFlask(array $payload, array $cfg): array {
    $ch = curl_init($cfg['FLASK_API']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $cfg['FLASK_BACKEND_API_KEY']
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 180,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['success' => false, 'error' => $err];
    $data = json_decode($resp, true);
    if (!is_array($data)) return ['success' => false, 'error' => 'Invalid backend response'];
    return $data;
}

// --- Bootstrap ---
$cfg = load_config();
$pdo = new PDO('sqlite:' . $cfg['SQLITE_PATH']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
initializeDb($pdo);

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = getClientIp();
    $ua = getUserAgent();
    $uaHash = hash('sha256', $ua);
    $today = todayDate();

    // Global daily cap first
    if (getDailyCount($pdo, $today) >= 100) {
        http_response_code(429);
        $result = ['success' => false, 'error' => 'Daily global limit reached (100)'];
    } elseif (hasReachedLimit($pdo, $ip, $uaHash, $today)) {
        http_response_code(429);
        $result = ['success' => false, 'error' => 'Daily limit reached for this IP/UA'];
    } else {
        // Prefer values from form, fallback to server .env
        $domain = trim($_POST['domain'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($domain === '') $domain = $cfg['SPOTIFY_DOMAIN'];
        if ($password === '') $password = $cfg['SPOTIFY_PASSWORD'];

        if ($domain === '' || $password === '') {
            $result = ['success' => false, 'error' => 'Missing domain or password'];
        } else {
            $payload = [
                'domain' => $domain,
                'password' => $password,
                'trial_link' => trim($_POST['trial_link'] ?? '')
            ];
            $result = callFlask($payload, $cfg);
            if (!empty($result['success'])) {
                recordSubmission($pdo, $ip, $uaHash, $today);
            }
        }
    }
}
?>
<?php
$page_title = 'Spotify Creator';
$current_page = 'spo';
$base_prefix = '../';
include '../includes/header.php';
?>

        <div class="content-wrapper">
        <div class="content-section">
          <h2>Spotify Creator</h2>
          <form method="post" class="grid grid-cols-1 gap-4">
            <div>
              <label class="mb-2 text-sm opacity-80">Domain (opsional)</label>
              <input class="form-input" name="domain" placeholder="kosongkan untuk pakai .env">
            </div>
            <div>
              <label class="mb-2 text-sm opacity-80">Password (opsional)</label>
              <input class="form-input" name="password" type="password" placeholder="kosongkan untuk pakai .env">
            </div>
            <div>
              <label class="mb-2 text-sm opacity-80">Trial link (opsional)</label>
              <input class="form-input" name="trial_link" placeholder="https://www.spotify.com/student/...verificationId=...">
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">Buat Akun</button>
          </form>
          <?php if ($result): ?>
            <div class="result-card mt-6">
              <h3 class="text-lg mb-2">Result</h3>
              <pre class="result-output" style="white-space: pre-wrap;"><?php echo htmlspecialchars(json_encode([
                'success' => $result['success'] ?? false,
                'email' => $result['email'] ?? null,
                'status' => $result['status'] ?? null,
                'error' => $result['error'] ?? null,
              ], JSON_PRETTY_PRINT)); ?></pre>
            </div>
          <?php endif; ?>
        </div>
      </div>

<?php include '../includes/footer.php'; ?>

<?php if ($result && (!($result['success'] ?? false)) && (($result['error'] ?? '') === 'Daily global limit reached (100)')): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showToast === 'function') {
        showToast('Sudah 100 nyet, besok lagi.', 'error');
    } else {
        alert('Sudah 100 nyet, besok lagi.');
    }
});
</script>
<?php endif; ?>


