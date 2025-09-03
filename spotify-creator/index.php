<?php
require __DIR__ . '/config.php';
// Use session for PRG (Post/Redirect/Get) to avoid form resubmission on refresh
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
// Load flashed result if redirected
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['result'])) {
    $result = $_SESSION['result'];
    unset($_SESSION['result']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = getClientIp();
    $ua = getUserAgent();
    $uaHash = hash('sha256', $ua);
    $today = todayDate();

    // Global daily cap first (skip if disabled via config)
    if (!$cfg['DISABLE_RATE_LIMIT'] && getDailyCount($pdo, $today) >= 100) {
        http_response_code(429);
        $result = ['success' => false, 'error' => 'Daily global limit reached (100)'];
    } elseif (!$cfg['DISABLE_RATE_LIMIT'] && hasReachedLimit($pdo, $ip, $uaHash, $today)) {
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
    // Attach display password and redirect (PRG)
    if (is_array($result)) {
        $result['display_password'] = $password;
        $_SESSION['result'] = $result;
    }
    header('Location: ' . basename(__FILE__), true, 303);
    exit;
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
              <label class="mb-2 text-sm opacity-80">Domain</label>
              <input class="form-input" name="domain" placeholder="motionisme.com">
            </div>
            <div>
              <label class="mb-2 text-sm opacity-80">Password</label>
              <input class="form-input" name="password" type="password" placeholder="Premium@123">
            </div>
            <div>
              <label class="mb-2 text-sm opacity-80">Trial link</label>
              <input class="form-input" name="trial_link" placeholder="https://www.spotify.com/student/...verificationId=...">
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">Buat Akun</button>
          </form>
          <!-- Processing Modal -->
          <div id="processingModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 14px; padding: 24px 24px 22px; max-width: 440px; width: calc(100% - 40px); text-align: center;">
              <div style="display:flex; align-items:center; justify-content:center; margin-bottom: 10px; gap: 14px;">
                <div style="width: 34px; height: 34px; position: relative;">
                  <div style="position:absolute; inset:0; border-radius:50%; border:3px solid rgba(255,255,255,.15);"></div>
                  <div class="spinner-arc" style="position:absolute; inset:0; border-radius:50%; border:3px solid transparent; border-top-color: var(--accent); animation: spin 0.9s linear infinite;"></div>
                </div>
                <div style="text-align:left;">
                  <div style="font-weight:700; color: var(--text-light);">Memproses pembuatan akun</div>
                  <div style="opacity:.85; color: var(--text-light); font-size: 13px;">Waktu berlalu: <span id="elapsedSeconds">0.0</span> detik</div>
                </div>
              </div>
              <div class="dots" style="display:flex; align-items:center; justify-content:center; gap:6px; margin-top:4px;">
                <span class="dot" style="width:6px; height:6px; border-radius:50%; background: var(--accent); opacity:.85; animation: bounce 1.2s infinite ease-in-out;"></span>
                <span class="dot" style="width:6px; height:6px; border-radius:50%; background: var(--accent); opacity:.65; animation: bounce 1.2s .15s infinite ease-in-out;"></span>
                <span class="dot" style="width:6px; height:6px; border-radius:50%; background: var(--accent); opacity:.45; animation: bounce 1.2s .3s infinite ease-in-out;"></span>
              </div>
            </div>
          </div>
          <?php if ($result): ?>
            <div class="result-card mt-6">
              <?php if (!empty($result['success'])): ?>
                <h3 class="text-lg mb-2">Akun Berhasil Dibuat</h3>
                <div class="space-y-2 text-sm">
                  <div><strong>Email:</strong> <?php echo htmlspecialchars($result['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                  <div><strong>Password:</strong> <?php echo htmlspecialchars($result['display_password'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                  <div><strong>Status:</strong> <?php echo htmlspecialchars($result['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <script>
                  // Mark done and stop timer
                  (function(){
                    var modal = document.getElementById('processingModal');
                    if (modal) modal.style.display = 'none';
                    if (window.__elapsedTimer) { clearInterval(window.__elapsedTimer); window.__elapsedTimer = null; }
                  })();
                </script>
              <?php else: ?>
                <h3 class="text-lg mb-2">Gagal</h3>
                <p class="text-sm">Gagal, silakan coba lagi.</p>
                <script>
                  // Stop timer on failure too
                  (function(){
                    var modal = document.getElementById('processingModal');
                    if (modal) modal.style.display = 'none';
                    if (window.__elapsedTimer) { clearInterval(window.__elapsedTimer); window.__elapsedTimer = null; }
                  })();
                </script>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function() {
      var modal = document.getElementById('processingModal');
      if (modal) {
        modal.style.display = 'flex';
        // Start elapsed timer
        var start = Date.now();
        var target = document.getElementById('elapsedSeconds');
        if (target) {
          window.__elapsedTimer = setInterval(function() {
            var elapsed = (Date.now() - start) / 1000;
            target.textContent = elapsed.toFixed(1);
          }, 100);
        }
      }
    }, { once: true });
  }
});
</script>

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


