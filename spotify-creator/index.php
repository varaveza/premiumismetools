<?php
require __DIR__ . '/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
$cfg = load_config();

// Initialize database
try {
    $pdo = new PDO('sqlite:' . $cfg['SQLITE_PATH']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if not exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS ip_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        ua_hash TEXT NOT NULL,
        submitted_at DATETIME NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL UNIQUE,
        count INTEGER DEFAULT 0
    )");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

$result = null;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $uaHash = hash('sha256', $ua);
    $today = date('Y-m-d');
    
    // Check rate limiting
    if (!$cfg['DISABLE_RATE_LIMIT']) {
        // Check global limit
        $stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ?");
        $stmt->execute([$today]);
        $dailyCount = $stmt->fetchColumn() ?: 0;
        
        if ($dailyCount >= 100) {
            $result = ['success' => false, 'error' => 'Daily global limit reached (100)'];
        } else {
            // Check IP limit
            $stmt = $pdo->prepare("SELECT 1 FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
            $stmt->execute([$ip, $uaHash, $today]);
            if ($stmt->fetchColumn()) {
                $result = ['success' => false, 'error' => 'Daily limit reached for this IP/UA'];
            }
        }
    }
    
    // If not rate limited, create account
    if (!$result) {
        $domain = trim($_POST['domain'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($domain)) $domain = $cfg['SPOTIFY_DOMAIN'];
        if (empty($password)) $password = $cfg['SPOTIFY_PASSWORD'];
        
        if (empty($domain) || empty($password)) {
            $result = ['success' => false, 'error' => 'Missing domain or password'];
        } else {
            // Call Python CLI
            $py = escapeshellcmd('python');
            $cli = escapeshellarg(__DIR__ . '/py/cli_create.py');
            $argDomain = escapeshellarg($domain);
            $argPassword = escapeshellarg($password);
            $argTrial = escapeshellarg(trim($_POST['trial_link'] ?? ''));
            
            $cmd = $py . ' ' . $cli . ' ' . $argDomain . ' ' . $argPassword;
            if (!empty($_POST['trial_link'])) {
                $cmd .= ' ' . $argTrial;
            }
            
            $output = shell_exec($cmd . ' 2>&1');
            $json = json_decode($output, true);
            
            if (is_array($json) && !empty($json['success'])) {
                // Record submission
                $stmt = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
                $stmt->execute([$ip, $uaHash]);
                
                $pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, 1)
                               ON CONFLICT(date) DO UPDATE SET count = count + 1")
                    ->execute([$today]);
                
                $result = $json;
                $result['display_password'] = $password;
            } else {
                $result = $json ?: ['success' => false, 'error' => 'CLI execution failed'];
            }
        }
    }
    
    // Store result in session and redirect
    if ($result) {
        $_SESSION['result'] = $result;
    }
    header('Location: ' . basename(__FILE__), true, 303);
    exit;
}

// Load result from session
if (isset($_SESSION['result'])) {
    $result = $_SESSION['result'];
    unset($_SESSION['result']);
}

// Include header
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
                <input class="form-input" name="domain" placeholder="motionisme.com" value="<?php echo htmlspecialchars($_POST['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label class="mb-2 text-sm opacity-80">Password</label>
                <input class="form-input" name="password" type="password" placeholder="Premium@123" value="<?php echo htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div>
                <label class="mb-2 text-sm opacity-80">Trial link (optional)</label>
                <input class="form-input" name="trial_link" placeholder="https://www.spotify.com/student/...verificationId=...">
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">Buat Akun</button>
        </form>
        
        <!-- Processing Modal -->
        <div id="processingModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
            <div style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 14px; padding: 24px; max-width: 440px; width: calc(100% - 40px); text-align: center;">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <div class="text-left">
                        <div class="font-bold">Memproses pembuatan akun</div>
                        <div class="text-sm opacity-75">Waktu: <span id="elapsedSeconds">0.0</span> detik</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Result Display -->
        <?php if ($result): ?>
            <?php if (!empty($result['success'])): ?>
                <!-- Success -->
                <div class="result-card mt-6 bg-green-500/10 border-green-500/20">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-green-500">Akun Berhasil Dibuat</h3>
                    </div>
                    <div class="space-y-2 text-sm text-green-400">
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($result['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><strong>Password:</strong> <?php echo htmlspecialchars($result['display_password'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div><strong>Status:</strong> <?php echo htmlspecialchars($result['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Error -->
                <div class="result-card mt-6 bg-red-500/10 border-red-500/20">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-red-500">Gagal</h3>
                    </div>
                    <div class="text-sm text-red-400">
                        <?php echo htmlspecialchars($result['error'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const modal = document.getElementById('processingModal');
    
    if (form && modal) {
        form.addEventListener('submit', function() {
            modal.style.display = 'flex';
            
            // Start timer
            const start = Date.now();
            const timerElement = document.getElementById('elapsedSeconds');
            
            const timer = setInterval(function() {
                const elapsed = (Date.now() - start) / 1000;
                if (timerElement) {
                    timerElement.textContent = elapsed.toFixed(1);
                }
            }, 100);
            
            // Store timer reference
            window.elapsedTimer = timer;
        });
    }
    
    // Hide modal if result exists
    if (document.querySelector('.result-card')) {
        if (modal) modal.style.display = 'none';
        if (window.elapsedTimer) {
            clearInterval(window.elapsedTimer);
        }
    }
});
</script>


