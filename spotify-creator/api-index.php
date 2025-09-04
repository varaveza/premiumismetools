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
    
    // Check rate limiting - 1 user (IP + UA) per day
    if (!$cfg['DISABLE_RATE_LIMIT']) {
        // Check if this IP + UA combination already submitted today
        $stmt = $pdo->prepare("SELECT 1 FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
        $stmt->execute([$ip, $uaHash, $today]);
        if ($stmt->fetchColumn()) {
            $result = ['success' => false, 'error' => 'Anda sudah membuat akun hari ini. Coba lagi besok.'];
        }
    }
    
    // If not rate limited, create account via API
    if (!$result) {
        $domain = trim($_POST['domain'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $trial_link = trim($_POST['trial_link'] ?? '');
        
        if (empty($domain)) $domain = $cfg['SPOTIFY_DOMAIN'];
        if (empty($password)) $password = $cfg['SPOTIFY_PASSWORD'];
        
        if (empty($domain) || empty($password)) {
            $result = ['success' => false, 'error' => 'Missing domain or password'];
        } else {
            // Call API
            $api_url = $cfg['API_ENDPOINT'] ?? 'http://localhost:5111/api/create';
            
            $post_data = [
                'domain' => $domain,
                'password' => $password
            ];
            
            if (!empty($trial_link)) {
                $post_data['trial_link'] = $trial_link;
            }
            
            // Prepare API request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            
            // Add API key if configured
            if (!empty($cfg['API_KEY'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-API-Key: ' . $cfg['API_KEY']
                ]);
            }
            
            $api_response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error) {
                $result = ['success' => false, 'error' => 'API connection failed: ' . $curl_error];
            } else {
                $json = json_decode($api_response, true);
                
                if (is_array($json) && !empty($json['success'])) {
                    // Record submission
                    $stmt = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
                    $stmt->execute([$ip, $uaHash]);
                    
                    $pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, 1)
                                   ON CONFLICT(date) DO UPDATE SET count = count + 1")
                        ->execute([$today]);
                    
                    $result = $json;
                    // Remove debug info from user-facing result for security
                    unset($result['debug']);
                    // Mark that we attempted student verification if a trial link was provided
                    $result['trial_attempted'] = !empty($trial_link);
                    $result['display_password'] = $password;
                } else {
                    $result = $json ?: ['success' => false, 'error' => 'API call failed'];
                    // Add debug info
                    $result['debug'] = [
                        'api_url' => $api_url,
                        'http_code' => $http_code,
                        'response' => $api_response,
                        'json_error' => json_last_error_msg(),
                        'post_data' => $post_data
                    ];
                    $result['trial_attempted'] = !empty($trial_link);
                }
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
$page_title = 'Spotify Creator (API)';
$current_page = 'spo';
$base_prefix = '../';
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-section">
        <h2>Spotify Creator (API)</h2>
        
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
                        <?php
                        // If debug contains discount_already_used, show a gentle notice
                        $discountUsed = false;
                        if (!empty($result['debug']) && is_array($result['debug'])) {
                            foreach ($result['debug'] as $dbg) {
                                if (is_array($dbg) && isset($dbg['result']) && $dbg['result'] === 'discount_already_used') {
                                    $discountUsed = true;
                                    break;
                                }
                                if (is_string($dbg) && strpos($dbg, 'discount_already_used') !== false) {
                                    $discountUsed = true;
                                    break;
                                }
                            }
                        }
                        if ($discountUsed): ?>
                            <div class="mt-2 text-yellow-400">
                                <strong>Catatan:</strong> Link student sudah digunakan. Akun dibuat sebagai <em>basic</em>.
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($result['trial_attempted']) && !$discountUsed && ($result['status'] ?? '') === 'REGULAR'): ?>
                            <div class="mt-2 text-yellow-400">
                                <strong>Catatan:</strong> Verifikasi student dicoba, namun tidak terkonfirmasi. Link mungkin tidak valid/expired atau cookies login tidak sesuai.
                            </div>
                        <?php endif; ?>
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
                     
                     <?php if (!empty($result['debug'])): ?>
                     <details class="mt-3">
                         <summary class="cursor-pointer text-xs text-red-300 hover:text-red-200">Debug Info</summary>
                         <pre class="mt-2 text-xs bg-red-500/20 p-2 rounded border border-red-500/30 overflow-x-auto"><?php echo htmlspecialchars(print_r($result['debug'], true), ENT_QUOTES, 'UTF-8'); ?></pre>
                     </details>
                     <?php endif; ?>
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
