<?php
@session_start();
// Simple PHP frontend: upload images, run Python generator via CLI, show gallery

// Base directories
$baseDir = __DIR__;
$photoDir = $baseDir . DIRECTORY_SEPARATOR . 'fotosiswa';
$outputDir = $baseDir . DIRECTORY_SEPARATOR . 'output';
$tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'gemini_creator_tmp';
$pythonScript = $baseDir . DIRECTORY_SEPARATOR . 'generate.py';
$maxUploadSize = 10 * 1024 * 1024; // 10 MB

// SQLite for daily rate limiting
$DB_PATH = $baseDir . DIRECTORY_SEPARATOR . 'gemini_creator.db';
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
	error_log('Gemini DB error: ' . $e->getMessage());
}

// Limits
$MAX_PER_USER_DAILY = 2;    // per user (IP+UA) 2 per hari
$MAX_GLOBAL_DAILY = 30;     // global 30 per hari

// Common request context
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$uaHash = hash('sha256', $ua);
$today = date('Y-m-d');

if (!is_dir($photoDir)) {
	@mkdir($photoDir, 0777, true);
}
if (!is_dir($outputDir)) {
	@mkdir($outputDir, 0777, true);
}
if (!is_dir($tmpDir)) {
	@mkdir($tmpDir, 0777, true);
}

$messages = [];
$errors = [];
$generatedImages = isset($_SESSION['last_generated_images']) && is_array($_SESSION['last_generated_images']) ? $_SESSION['last_generated_images'] : [];

// Load flash messages on GET (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['gemini_messages']) && is_array($_SESSION['gemini_messages'])) {
        $messages = $_SESSION['gemini_messages'];
    }
    if (isset($_SESSION['gemini_errors']) && is_array($_SESSION['gemini_errors'])) {
        $errors = $_SESSION['gemini_errors'];
    }
    if (isset($_SESSION['last_generated_images']) && is_array($_SESSION['last_generated_images'])) {
        $generatedImages = $_SESSION['last_generated_images'];
    }
    unset($_SESSION['gemini_messages'], $_SESSION['gemini_errors']);
}

// Helper: list image files in output directory
$listOutputImages = function($dir) {
	$images = [];
	if (is_dir($dir)) {
		$items = scandir($dir);
		foreach ($items as $it) {
			if ($it === '.' || $it === '..') continue;
			$lower = strtolower($it);
			if (preg_match('/\.(png|jpg|jpeg|gif|bmp|tiff)$/i', $lower)) {
				$images[] = $it;
			}
		}
	}
	sort($images);
	return $images;
};

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
	$beforeOutput = $listOutputImages($outputDir);
	$limitRequest = isset($_POST['limit']) ? max(1, min(2, intval($_POST['limit']))) : 2;
	if (!isset($_FILES['photos'])) {
		// Tidak ada upload -> fallback generate dari fotosiswa
		goto GENERATE_FROM_FOTOSISWA_FALLBACK;
	} else {
		$allowed = ['jpg','jpeg','png','gif','bmp','tiff'];
		$files = $_FILES['photos'];
		// Jika semua nama file kosong, anggap tidak ada upload -> fallback
		$allEmpty = true;
		for ($i = 0; $i < count($files['name']); $i++) {
			if (trim((string)$files['name'][$i]) !== '') { $allEmpty = false; break; }
		}
		if ($allEmpty) { goto GENERATE_FROM_FOTOSISWA_FALLBACK; }
		// Pre-validate incoming count for rate-limit
		$incoming = 0;
		for ($i = 0; $i < count($files['name']); $i++) {
			$name = $files['name'][$i];
			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			if (in_array($ext, $allowed, true)) { $incoming++; }
		}
		try {
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
			$stmt->execute([$ip, $uaHash, $today]);
			$ipUaCount = (int)($stmt->fetchColumn() ?: 0);
			$stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ?");
			$stmt->execute([$today]);
			$dailyCount = (int)($stmt->fetchColumn() ?: 0);
			if ($ipUaCount >= $MAX_PER_USER_DAILY) {
				$errors[] = 'Batas harian per pengguna tercapai (2/hari). Coba lagi besok.';
			} elseif ($ipUaCount + $incoming > $MAX_PER_USER_DAILY) {
				$remain = max(0, $MAX_PER_USER_DAILY - $ipUaCount);
				$errors[] = 'Melebihi batas per pengguna. Sisa kuota hari ini: ' . $remain . '.';
			}
			if (empty($errors)) {
				if ($dailyCount >= $MAX_GLOBAL_DAILY) {
					$errors[] = 'Kuota harian global habis (30/hari). Coba lagi besok.';
				} elseif ($dailyCount + $incoming > $MAX_GLOBAL_DAILY) {
					$remainG = max(0, $MAX_GLOBAL_DAILY - $dailyCount);
					$errors[] = 'Melebihi kuota global. Sisa kuota hari ini: ' . $remainG . '.';
				}
			}
		} catch (Exception $e) {
			error_log('Gemini rate-limit check error: ' . $e->getMessage());
		}
		
		$generatedCount = 0;
		for ($i = 0; $i < count($files['name']) && empty($errors); $i++) {
			$name = $files['name'][$i];
			$tmp = $files['tmp_name'][$i];
			$size = isset($files['size'][$i]) ? (int)$files['size'][$i] : 0;
			$err = $files['error'][$i];
			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			if ($err !== UPLOAD_ERR_OK) {
				$errors[] = "Failed to upload $name (err $err).";
				continue;
			}
			if ($size <= 0 || $size > $maxUploadSize) {
				$errors[] = "$name size not allowed (max 10 MB).";
				continue;
			}
			if (!in_array($ext, $allowed, true)) {
				$errors[] = "$name is not an image.";
				continue;
			}
			// Pindahkan ke folder tmp/ sementara dengan nama unik
			$unique = uniqid('up_', true) . '.' . $ext;
			$targetTmp = $tmpDir . DIRECTORY_SEPARATOR . $unique;
			if (!move_uploaded_file($tmp, $targetTmp)) {
				$errors[] = "Failed to move $name to temp.";
				continue;
			}
			// Validasi konten file benar-benar image (MIME + struktur gambar)
			$mimeOk = false;
			$finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
			if ($finfo) {
				$mime = @finfo_file($finfo, $targetTmp);
				@finfo_close($finfo);
				if (is_string($mime) && strpos($mime, 'image/') === 0) {
					$mimeOk = true;
				}
			}
			$imgInfo = @getimagesize($targetTmp);
			if (!$mimeOk || $imgInfo === false) {
				@unlink($targetTmp);
				$errors[] = "$name is not a valid image file.";
				continue;
			}
			// Jalankan generator untuk file ini
			if (file_exists($pythonScript)) {
				$scriptArg = escapeshellarg($pythonScript);
				$fileArg = escapeshellarg($targetTmp);
				$cmds = [
					"python3 $scriptArg --photo $fileArg",
					"python $scriptArg --photo $fileArg",
					"py $scriptArg --photo $fileArg",
				];
				$ran = false;
				$output = [];
				$ret = 0;
				foreach ($cmds as $cmd) {
					$output = [];
					$ret = 0;
					exec($cmd . ' 2>&1', $output, $ret);
					if ($ret === 0) { $ran = true; break; }
				}
				if ($ran) {
					$generatedCount++;
				} else {
					$errors[] = 'Failed to generate for ' . htmlspecialchars($name) . ': ' . htmlspecialchars(implode("\n", $output));
				}
			} else {
				$errors[] = 'generate.py not found.';
			}
			// Hapus file sementara apapun hasilnya
			@unlink($targetTmp);
		}
		// Update counters if any successes and no hard blocking errors occurred mid-way
		if ($generatedCount > 0) {
			try {
				$pdo->beginTransaction();
				$stmtIns = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
				for ($j = 0; $j < $generatedCount; $j++) { $stmtIns->execute([$ip, $uaHash]); }
				$pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, ?) 
					ON CONFLICT(date) DO UPDATE SET count = count + ?")
					->execute([$today, $generatedCount, $generatedCount]);
				$pdo->commit();
			} catch (Exception $e) {
				if ($pdo->inTransaction()) { $pdo->rollBack(); }
				error_log('Gemini rate-limit write error: ' . $e->getMessage());
			}
		}
		if ($generatedCount > 0) {
			$messages[] = "Generated $generatedCount card(s) from uploaded file(s).";
		}
	}
	$afterOutput = $listOutputImages($outputDir);
	$generatedImages = array_values(array_diff($afterOutput, $beforeOutput));
	$_SESSION['last_generated_images'] = $generatedImages;
	// PRG redirect so refresh won't resubmit
	$_SESSION['gemini_messages'] = $messages;
	$_SESSION['gemini_errors'] = $errors;
	header('Location: ' . basename(__FILE__), true, 303);
	exit;

	// Fallback label for goto above
	GENERATE_FROM_FOTOSISWA_FALLBACK:
	// Proses generate dari fotosiswa dengan jumlah sesuai limitRequest dan kuota
	$generatedCount = 0;
	$allPhotos = [];
	foreach (scandir($photoDir) as $ph) {
		if ($ph === '.' || $ph === '..') continue;
		if (!preg_match('/\.(png|jpg|jpeg|gif|bmp|tiff)$/i', $ph)) continue;
		$full = $photoDir . DIRECTORY_SEPARATOR . $ph;
		if (!is_file($full)) continue;
		$allPhotos[] = $ph;
	}
	if (count($allPhotos) === 0) {
		$errors[] = 'Tidak ada foto di folder fotosiswa/.';
	} else {
		try {
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
			$stmt->execute([$ip, $uaHash, $today]);
			$ipUaCount = (int)($stmt->fetchColumn() ?: 0);
			$stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ?");
			$stmt->execute([$today]);
			$dailyCount = (int)($stmt->fetchColumn() ?: 0);
			$userRemain = max(0, $MAX_PER_USER_DAILY - $ipUaCount);
			$globalRemain = max(0, $MAX_GLOBAL_DAILY - $dailyCount);
			$quota = min($userRemain, $globalRemain, count($allPhotos), $limitRequest);
			if ($quota <= 0) {
				if ($userRemain <= 0) { $errors[] = 'Batas harian per pengguna tercapai (2/hari). Coba lagi besok.'; }
				if ($globalRemain <= 0) { $errors[] = 'Kuota harian global habis (30/hari). Coba lagi besok.'; }
			} else {
				shuffle($allPhotos);
				$selected = array_slice($allPhotos, 0, $quota);
				foreach ($selected as $ph) {
					$scriptArg = escapeshellarg($pythonScript);
					$full = $photoDir . DIRECTORY_SEPARATOR . $ph;
					$fileArg = escapeshellarg($full);
					$cmds = [
						"python3 $scriptArg --photo $fileArg",
						"python $scriptArg --photo $fileArg",
						"py $scriptArg --photo $fileArg",
					];
					$ran = false; $output = []; $ret = 0;
					foreach ($cmds as $cmd) { $output = []; $ret = 0; exec($cmd . ' 2>&1', $output, $ret); if ($ret === 0) { $ran = true; break; } }
					if ($ran) { $generatedCount++; }
					else { $errors[] = 'Failed to generate for ' . htmlspecialchars($ph) . ': ' . htmlspecialchars(implode("\n", $output)); }
				}
				if ($generatedCount > 0) {
					try {
						$pdo->beginTransaction();
						$stmtIns = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
						for ($j = 0; $j < $generatedCount; $j++) { $stmtIns->execute([$ip, $uaHash]); }
						$pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, ?) ON CONFLICT(date) DO UPDATE SET count = count + ?")
							->execute([$today, $generatedCount, $generatedCount]);
						$pdo->commit();
					} catch (Exception $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } error_log('Gemini rate-limit write error: ' . $e->getMessage()); }
					$messages[] = "Generated $generatedCount card(s) dari folder fotosiswa/.";
				}
			}
		} catch (Exception $e) { error_log('Gemini fallback generate error: ' . $e->getMessage()); }
	}
	$afterOutput = $listOutputImages($outputDir);
	$generatedImages = array_values(array_diff($afterOutput, $beforeOutput));
	$_SESSION['last_generated_images'] = $generatedImages;
	$_SESSION['gemini_messages'] = $messages;
	$_SESSION['gemini_errors'] = $errors;
	header('Location: ' . basename(__FILE__), true, 303);
	exit;
}

// (Dihilangkan) Auto-generate via scan fotosiswa/ saat upload untuk mencegah penghapusan foto lain

// Generate using existing photos in fotosiswa/ when user chooses to (default source)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
	$beforeOutput = $listOutputImages($outputDir);
	if (file_exists($pythonScript)) {
		$generatedCount = 0;
		$hasPhotos = false;
		// Kumpulkan kandidat valid
		$allPhotos = [];
		foreach (scandir($photoDir) as $ph) {
			if ($ph === '.' || $ph === '..') continue;
			if (!preg_match('/\.(png|jpg|jpeg|gif|bmp|tiff)$/i', $ph)) continue;
			$full = $photoDir . DIRECTORY_SEPARATOR . $ph;
			if (!is_file($full)) continue;
			$allPhotos[] = $ph;
		}
		if (count($allPhotos) > 0) { $hasPhotos = true; }
		// Ambil kuota tersisa dan batasi jumlah yang akan diproses
		$selected = [];
		$limitRequest = isset($_POST['limit']) ? max(1, min(2, intval($_POST['limit']))) : 2;
		try {
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_submissions WHERE ip = ? AND ua_hash = ? AND date(submitted_at) = ?");
			$stmt->execute([$ip, $uaHash, $today]);
			$ipUaCount = (int)($stmt->fetchColumn() ?: 0);
			$stmt = $pdo->prepare("SELECT count FROM daily_submissions WHERE date = ?");
			$stmt->execute([$today]);
			$dailyCount = (int)($stmt->fetchColumn() ?: 0);
			$userRemain = max(0, $MAX_PER_USER_DAILY - $ipUaCount);
			$globalRemain = max(0, $MAX_GLOBAL_DAILY - $dailyCount);
			$quota = min($userRemain, $globalRemain, count($allPhotos), $limitRequest);
			if ($quota <= 0) {
				if ($userRemain <= 0) {
					$errors[] = 'Batas harian per pengguna tercapai (2/hari). Coba lagi besok.';
				}
				if ($globalRemain <= 0) {
					$errors[] = 'Kuota harian global habis (30/hari). Coba lagi besok.';
				}
			} else {
				// Acak dan ambil sesuai kuota
				shuffle($allPhotos);
				$selected = array_slice($allPhotos, 0, $quota);
			}
		} catch (Exception $e) {
			error_log('Gemini rate-limit check error: ' . $e->getMessage());
		}
		foreach ($selected as $ph) {
			$scriptArg = escapeshellarg($pythonScript);
			$full = $photoDir . DIRECTORY_SEPARATOR . $ph;
			$fileArg = escapeshellarg($full);
			$cmds = [
				"python3 $scriptArg --photo $fileArg",
				"python $scriptArg --photo $fileArg",
				"py $scriptArg --photo $fileArg",
			];
			$ran = false;
			$output = [];
			$ret = 0;
			foreach ($cmds as $cmd) {
				$output = [];
				$ret = 0;
				exec($cmd . ' 2>&1', $output, $ret);
				if ($ret === 0) { $ran = true; break; }
			}
			if ($ran) {
				$generatedCount++;
			} else {
				$errors[] = 'Failed to generate for ' . htmlspecialchars($ph) . ': ' . htmlspecialchars(implode("\n", $output));
			}
		}
		if (!$hasPhotos) {
			$errors[] = 'Tidak ada foto di folder fotosiswa/.';
		}
		// Update counters
		if ($generatedCount > 0) {
			try {
				$pdo->beginTransaction();
				$stmtIns = $pdo->prepare("INSERT INTO ip_submissions (ip, ua_hash, submitted_at) VALUES (?, ?, datetime('now'))");
				for ($j = 0; $j < $generatedCount; $j++) { $stmtIns->execute([$ip, $uaHash]); }
				$pdo->prepare("INSERT INTO daily_submissions (date, count) VALUES (?, ?) 
					ON CONFLICT(date) DO UPDATE SET count = count + ?")
					->execute([$today, $generatedCount, $generatedCount]);
				$pdo->commit();
			} catch (Exception $e) {
				if ($pdo->inTransaction()) { $pdo->rollBack(); }
				error_log('Gemini rate-limit write error: ' . $e->getMessage());
			}
		}
		if ($generatedCount > 0) {
			$messages[] = "Generated $generatedCount card(s) dari folder fotosiswa/.";
		}
	} else {
		$errors[] = 'generate.py not found.';
	}
	$afterOutput = $listOutputImages($outputDir);
	$generatedImages = array_values(array_diff($afterOutput, $beforeOutput));
	$_SESSION['last_generated_images'] = $generatedImages;
	// PRG redirect so refresh won't resubmit
	$_SESSION['gemini_messages'] = $messages;
	$_SESSION['gemini_errors'] = $errors;
	header('Location: ' . basename(__FILE__), true, 303);
	exit;
}

// Gallery: only show images generated in this request
$images = $generatedImages;
?>
<?php
$page_title = 'Premiumisme Tools';
$current_page = 'gemini';
$base_prefix = '../';
include '../includes/header.php';
?>

<div class="content-wrapper">
	<div class="content-section">
		<h2>Gemini Verify</h2>

		<?php if (!empty($messages)) { ?>
			<div class="result-card mt-4 bg-emerald-500/10 border-emerald-500/20">
				<ul class="list-disc ml-5 text-emerald-400">
					<?php foreach ($messages as $m) { echo '<li>' . htmlspecialchars($m) . '</li>'; } ?>
				</ul>
			</div>
		<?php } ?>

		<?php if (!empty($errors)) { ?>
			<div class="result-card mt-4 bg-red-500/10 border-red-500/20">
				<ul class="list-disc ml-5 text-red-400">
					<?php foreach ($errors as $e) { echo '<li>' . htmlspecialchars($e) . '</li>'; } ?>
				</ul>
			</div>
		<?php } ?>

		<div class="result-card mt-4">
			<form method="post" enctype="multipart/form-data" class="grid gap-3">
				<input type="hidden" name="action" value="upload">
				<label class="mb-2 text-sm opacity-80">Upload Photos (opsional)</label>
				<input class="form-input" type="file" name="photos[]" accept="image/*" multiple>
				<div>
					<label class="mb-2 text-sm opacity-80">Jumlah Output</label>
					<select name="limit" class="form-input" style="max-width:140px;">
						<option value="1">1</option>
						<option value="2">2</option>
					</select>
				</div>
				<div class="mt-2">
					<button type="submit" class="btn btn-secondary w-full">Generate</button>
				</div>
				<p class="text-xs opacity-70">Jika tidak upload, sistem akan menggunakan gambar default.</p>
			</form>
		</div>

		<h2 class="mt-6">Result</h2>
		<?php if (!empty($images)) { ?>
			<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap: 12px;">
				<?php foreach ($images as $img) { ?>
					<a href=<?php echo '"output/' . rawurlencode($img) . '"'; ?> target="_blank">
						<img style="width:100%; height:auto; border: 1px solid #ccc; border-radius: 6px;" src=<?php echo '"output/' . htmlspecialchars($img) . '"'; ?> alt="output image">
					</a>
				<?php } ?>
			</div>
		<?php } else { ?>
			<div class="result-card mt-4"><div class="text-sm opacity-70">Belum ada hasil pada sesi ini.</div></div>
		<?php } ?>
	</div>
</div>

<?php include '../includes/footer.php'; ?>


