<?php
// Surfshark Creator (webbase, on-demand). Desain dan CSS mengikuti layout global (header/footer)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SQLite rate limit: per IP+UA 10/hari, global 250/hari
$DB_PATH = __DIR__ . '/surfshark_creator.db';
$pdo = null;
try {
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    error_log('Surfshark DB error: ' . $e->getMessage());
}

$MAX_PER_IP_BROWSER = 10;
$MAX_GLOBAL_DAILY   = 250;

function call_register_api($email, $password, $domain) {
    $url = 'http://127.0.0.1:7070/register';
    $payload = array();
    if (!empty($email)) { $payload['email'] = $email; }
    if (!empty($password)) { $payload['password'] = $password; }
    if (!empty($domain)) { $payload['domain'] = $domain; }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return array('success' => false, 'error' => 'cURL error: ' . $error, 'http' => $status);
    }

    $data = json_decode($response, true);
    if ($data === null) {
        return array('success' => false, 'error' => 'Invalid JSON response (HTTP ' . $status . ')', 'http' => $status);
    }
    // Tambahkan kode HTTP agar caller bisa bedakan limit/invalid
    if (is_array($data) && !isset($data['http'])) {
        $data['http'] = $status;
    }
    return $data;
}

$resultMessage = '';
$resultsBatch = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;
    if ($jumlah < 1) { $jumlah = 1; }
    if ($jumlah > 10) { $jumlah = 10; }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $uaHash = hash('sha256', $ua);
    $today = date('Y-m-d');
    $error = null;

    // Rate limit checks
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
            $stmt->execute([$ip, $uaHash, $today]);
            $ipUaCount = (int)($stmt->fetchColumn() ?: 0);
            if ($ipUaCount >= $MAX_PER_IP_BROWSER) {
                $error = 'Anda sudah mencapai batas 10 pembuatan untuk IP/Browser ini hari ini. Coba lagi besok.';
            }
            if (!$error) {
                $stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ?");
                $stmt->execute([$today]);
                $dailyCount = (int)($stmt->fetchColumn() ?: 0);
                if ($dailyCount >= $MAX_GLOBAL_DAILY) {
                    $error = 'Kuota harian sudah habis (250 akun/hari). Coba lagi besok.';
                }
            }
        } catch (Exception $e) {
            error_log('Surfshark rate-limit check error: ' . $e->getMessage());
        }
    }

    if (!$error) {
        for ($i = 0; $i < $jumlah; $i++) {
            $email = '';
            $domain = '';
            if ($identifier !== '') {
                if (strpos($identifier, '@') !== false && preg_match('/^[^@]+@[^@]+\.[^@]+$/', $identifier)) {
                    $email = $identifier;
                } else {
                    $domain = ($identifier[0] === '@') ? $identifier : ('@' . $identifier);
                }
            }

            $apiResult = call_register_api($email, $password, $domain);
            if (isset($apiResult['success']) && $apiResult['success'] === true) {
                $resultsBatch[] = array('ok' => true, 'email' => $apiResult['email'], 'password' => $apiResult['password']);
                if ($pdo instanceof PDO) {
                    try {
                        $pdo->beginTransaction();
                        $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))")
                            ->execute([$ip, $uaHash]);
                        $pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, ?) 
                                       ON CONFLICT(date) DO UPDATE SET count = count + ?")
                            ->execute([$today, 1, 1]);
                        $pdo->commit();
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                        error_log('Surfshark rate-limit write error: ' . $e->getMessage());
                    }
                }
            } else {
                $err = isset($apiResult['error']) ? $apiResult['error'] : 'Unknown error';
                $httpCode = isset($apiResult['http']) ? intval($apiResult['http']) : 0;
                // Deteksi limit dari backend (400/429) dan hentikan iterasi berikutnya
                if ($httpCode === 429 || stripos($err, 'limit') !== false) {
                    $resultsBatch[] = array('ok' => false, 'error' => 'Limit harian tercapai. Coba lagi besok.');
                    break;
                } elseif ($httpCode === 400 && stripos($err, 'required') === false) {
                    // Banyak API mengembalikan 400 saat limit/validation
                    $resultsBatch[] = array('ok' => false, 'error' => $err);
                    break;
                } else {
                    $resultsBatch[] = array('ok' => false, 'error' => $err);
                }
            }
            if ($email !== '') { break; }
        }
        $okCount = 0; $failCount = 0;
        foreach ($resultsBatch as $r) { $r['ok'] ? $okCount++ : $failCount++; }
        $resultMessage = 'Selesai: ' . $okCount . ' sukses, ' . $failCount . ' gagal';
    } else {
        $resultMessage = 'Failed: ' . $error;
    }

    // PRG pattern: simpan hasil ke session lalu redirect 303 agar refresh tidak mengulang submit
    $_SESSION['surf_result_message'] = $resultMessage;
    $_SESSION['surf_result_batch'] = $resultsBatch;
    header('Location: ' . basename(__FILE__), true, 303);
    exit;
}

// Removed saved results display; only show current session results
// Load result from session (GET setelah redirect)
if (isset($_SESSION['surf_result_message']) || isset($_SESSION['surf_result_batch'])) {
    $resultMessage = $_SESSION['surf_result_message'] ?? '';
    $resultsBatch = $_SESSION['surf_result_batch'] ?? array();
    unset($_SESSION['surf_result_message'], $_SESSION['surf_result_batch']);
}
?>
<?php
$page_title = 'Premiumisme Tools';
$current_page = 'surfshark';
$base_prefix = '../';
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-section">
        <h2>Surfshark Creator</h2>
        <form method="post" class="grid grid-cols-1 gap-4">
            <div class="row">
                <label for="identifier">Domain</label>
                <input class="form-input" type="text" id="identifier" name="identifier" placeholder="motionisme.com" />
            </div>
            <div class="row">
                <label for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" placeholder="Premium@123" />
            </div>
            <div class="row">
                <label for="jumlah">Jumlah</label>
                <input class="form-input" type="number" id="jumlah" name="jumlah" min="1" max="10" value="1" />
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">Register</button>
        </form>
        <?php if (!empty($resultMessage) || !empty($resultsBatch)): ?>
            <div class="result-card mt-6 <?php echo (!empty($resultMessage) && strpos($resultMessage, 'Selesai:') !== 0) ? 'bg-red-500/10 border-red-500/20' : ''; ?>">
                <div class="flex items-center justify-between mb-3">
                    <div class="font-bold">Result</div>
                </div>
                <?php if (!empty($resultMessage)): ?>
                    <div class="text-sm mb-2"><?php echo htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if (!empty($resultsBatch)): ?>
                    <ul class="text-sm">
                        <?php foreach ($resultsBatch as $r): ?>
                            <?php if ($r['ok']): ?>
                                <li class="mono">Registered: <?php echo htmlspecialchars($r['email'] . ' | ' . $r['password']); ?></li>
                            <?php else: ?>
                                <li class="mono text-red-400">Failed: <?php echo htmlspecialchars($r['error']); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


