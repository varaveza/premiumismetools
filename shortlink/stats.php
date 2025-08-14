<?php
$page_title = 'Shortlink Stats - Statistik Link';
$current_page = 'shortlink';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Premiumisme'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="bg-animation">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <main class="container-main">
        <!-- Header -->
        <header class="header-section">
            <!-- Mobile Hamburger Menu -->
            <div class="mobile-nav-toggle">
                <button id="hamburger-btn" class="hamburger-btn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- Logo Section -->
            <div class="logo-section">
                <img src="../logo.svg" alt="Premiumisme Logo" class="logo">
            </div>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="../generator-email/" class="nav-link">Generator Email</a>
                <a href="../refund-calculator/" class="nav-link">Refund Calculator</a>
                <a href="../split-mail/" class="nav-link">Email Splitter</a>
                <a href="../remove-duplicate/" class="nav-link">Remove Duplicate</a>
                <a href="../shortlink/" class="nav-link active">Shortlink</a>
            </nav>

            <!-- Mobile Navigation Overlay -->
            <div id="mobile-nav" class="mobile-nav">
                <div class="mobile-nav-content">
                    <a href="../generator-email/" class="mobile-nav-link">Generator Email</a>
                    <a href="../refund-calculator/" class="mobile-nav-link">Refund Calculator</a>
                    <a href="../split-mail/" class="mobile-nav-link">Email Splitter</a>
                    <a href="../remove-duplicate/" class="mobile-nav-link">Remove Duplicate</a>
                    <a href="../shortlink/" class="mobile-nav-link active">Shortlink</a>
                </div>
            </div>
        </header>

<?php
// Get slug from URL path
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));
$slug = '';

// Extract slug from URL like /OKXtEr/stats.php
if (count($pathParts) >= 2 && $pathParts[1] === 'stats.php') {
    $slug = $pathParts[0];
} else {
    // Fallback to GET parameter
    $slug = $_GET['slug'] ?? '';
}

// Load data from external URL
$externalUrl = 'https://shortisme.com/shortlink/shortlinks.json';
$links = [];

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; ShortlinkStats/1.0)'
        ]
    ]);
    
    $jsonData = file_get_contents($externalUrl, false, $context);
    if ($jsonData !== false) {
        $links = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $links = [];
        }
    }
} catch (Exception $e) {
    $links = [];
}

// Find the link by slug
$foundLink = null;
foreach ($links as $link) {
    if ($link['slug'] === $slug) {
        $foundLink = $link;
        break;
    }
}

// Calculate time since creation
$createdTime = $foundLink ? new DateTime($foundLink['createdAt']) : null;
$now = new DateTime();
$timeDiff = $createdTime ? $now->diff($createdTime) : null;

// Format time difference
function formatTimeDiff($diff) {
    if ($diff->y > 0) return $diff->y . ' tahun yang lalu';
    if ($diff->m > 0) return $diff->m . ' bulan yang lalu';
    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}
?>

<!-- Konten Utama -->
<div>
    <?php if ($foundLink): ?>
        <!-- Stats Section -->
        <div class="fade-in">
            <div class="content-section">
                <div class="flex flex-col md:flex-row justify-between items-start mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-2">📊 Statistik Shortlink</h2>
                        <p class="opacity-70">Analisis performa link: <span class="text-[var(--accent)]"><?php echo htmlspecialchars($slug); ?></span></p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="window.location.href='<?php echo $foundLink['originalUrl']; ?>'" class="btn btn-primary text-sm">
                            <i class="fas fa-external-link-alt"></i> Kunjungi Link
                        </button>
                        <button onclick="copyShortUrl()" class="btn btn-secondary text-sm">
                            <i class="fas fa-copy"></i> Salin
                        </button>
                    </div>
                </div>

                <!-- Main Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Shortlink Info -->
                    <div class="bg-[var(--darker-peri)] p-6 rounded-xl border border-[var(--glass-border)]">
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-link text-[var(--accent)]"></i>
                            Informasi Link
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="opacity-70">Shortlink:</span>
                                <div class="short-url text-sm"><?php echo htmlspecialchars($foundLink['shortUrl']); ?></div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="opacity-70">URL Asli:</span>
                                <span class="text-sm break-all opacity-70"><?php echo htmlspecialchars($foundLink['originalUrl']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="opacity-70">Dibuat:</span>
                                <span class="text-sm opacity-70"><?php echo $createdTime ? $createdTime->format('d/m/Y H:i') : 'N/A'; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="opacity-70">Usia:</span>
                                <span class="text-sm opacity-70"><?php echo $timeDiff ? formatTimeDiff($timeDiff) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Click Statistics -->
                    <div class="bg-[var(--darker-peri)] p-6 rounded-xl border border-[var(--glass-border)]">
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-line text-[var(--accent)]"></i>
                            Statistik Klik
                        </h3>
                        <div class="space-y-4">
                            <div class="text-center">
                                <div class="text-4xl font-bold text-[var(--accent)] mb-2"><?php echo number_format($foundLink['clicks']); ?></div>
                                <div class="text-sm opacity-70">Total Klik</div>
                            </div>
                            
                            <?php if ($timeDiff && $timeDiff->days > 0): ?>
                                <div class="grid grid-cols-2 gap-4 text-center">
                                    <div>
                                        <div class="text-lg font-bold text-[var(--success-color)]">
                                            <?php echo number_format($foundLink['clicks'] / max($timeDiff->days, 1), 1); ?>
                                        </div>
                                        <div class="text-xs opacity-70">Klik/Hari</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-[var(--light-peri)]">
                                            <?php echo $timeDiff->days; ?>
                                        </div>
                                        <div class="text-xs opacity-70">Hari Aktif</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Performance Indicators -->
                <div class="bg-[var(--darker-peri)] p-6 rounded-xl border border-[var(--glass-border)] mb-6">
                    <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-tachometer-alt text-[var(--accent)]"></i>
                        Indikator Performa
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php
                        $clickCount = $foundLink['clicks'];
                        $performance = 'Baik';
                        $color = 'var(--success-color)';
                        
                        if ($clickCount == 0) {
                            $performance = 'Belum Ada Klik';
                            $color = 'var(--error-color)';
                        } elseif ($clickCount < 10) {
                            $performance = 'Sedang';
                            $color = 'var(--light-peri)';
                        } elseif ($clickCount < 50) {
                            $performance = 'Baik';
                            $color = 'var(--success-color)';
                        } else {
                            $performance = 'Sangat Baik';
                            $color = 'var(--accent)';
                        }
                        ?>
                        
                        <div class="text-center p-4 bg-[var(--darkest-peri)] rounded-lg">
                            <div class="text-2xl font-bold mb-2" style="color: <?php echo $color; ?>">
                                <?php echo $performance; ?>
                            </div>
                            <div class="text-sm opacity-70">Status Performa</div>
                        </div>
                        
                        <div class="text-center p-4 bg-[var(--darkest-peri)] rounded-lg">
                            <div class="text-2xl font-bold mb-2 text-[var(--accent)]">
                                <?php echo $clickCount > 0 ? 'Aktif' : 'Tidak Aktif'; ?>
                            </div>
                            <div class="text-sm opacity-70">Status Link</div>
                        </div>
                        
                        <div class="text-center p-4 bg-[var(--darkest-peri)] rounded-lg">
                            <div class="text-2xl font-bold mb-2 text-[var(--light-peri)]">
                                <?php echo $timeDiff ? $timeDiff->days : 0; ?>
                            </div>
                            <div class="text-sm opacity-70">Hari Online</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <button onclick="window.location.href='index.php'" class="btn btn-secondary flex-1">
                        <i class="fas fa-arrow-left"></i> Buat Link Baru
                    </button>
                    <button onclick="shareStats()" class="btn btn-primary flex-1">
                        <i class="fas fa-share"></i> Bagikan Stats
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Not Found Section -->
        <div class="fade-in">
            <div class="content-section text-center">
                <div class="mb-6">
                    <i class="fas fa-exclamation-triangle text-6xl text-[var(--error-color)] mb-4"></i>
                    <h2 class="text-2xl font-bold text-white mb-2">❌ Shortlink Tidak Ditemukan</h2>
                    <p class="opacity-70 mb-2">Link <span class="text-[var(--accent)]"><?php echo htmlspecialchars($slug); ?></span> tidak ditemukan.</p>
                    <p class="text-sm opacity-60 mb-6">Link mungkin sudah dihapus atau belum dibuat.</p>
                </div>
                
                <div class="bg-[var(--darker-peri)] p-4 rounded-xl border border-[var(--glass-border)] mb-6">
                    <h3 class="font-bold text-white mb-3">🔍 Tips:</h3>
                    <ul class="text-sm opacity-70 text-left space-y-1">
                        <li>• Pastikan URL shortlink sudah benar</li>
                        <li>• Link mungkin sudah dihapus oleh pemilik</li>
                        <li>• Coba buat link baru dengan slug yang berbeda</li>
                    </ul>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="window.location.href='index.php'" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Link Baru
                    </button>
                    <button onclick="window.history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function copyShortUrl() {
    const shortUrl = '<?php echo $foundLink ? $foundLink['shortUrl'] : ''; ?>';
    navigator.clipboard.writeText(shortUrl).then(() => {
        showToast('Shortlink berhasil disalin!');
    }).catch(() => {
        showToast('Gagal menyalin link!', 'error');
    });
}

function shareStats() {
    const statsUrl = window.location.href;
    const shortUrl = '<?php echo $foundLink ? $foundLink['shortUrl'] : ''; ?>';
    const clicks = <?php echo $foundLink ? $foundLink['clicks'] : 0; ?>;
    
    const shareText = `📊 Statistik Shortlink\n\n🔗 ${shortUrl}\n👆 ${clicks} klik\n📈 Lihat detail: ${statsUrl}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'Statistik Shortlink',
            text: shareText,
            url: statsUrl
        });
    } else {
        navigator.clipboard.writeText(shareText).then(() => {
            showToast('Statistik berhasil disalin!');
        });
    }
}

// Auto-refresh stats every 30 seconds (only if link exists)
<?php if ($foundLink): ?>
setInterval(() => {
    // Only refresh if user is on the page and link exists
    if (!document.hidden) {
        location.reload();
    }
}, 30000);
<?php endif; ?>


</script>

        </main>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast hidden"></div>

    <script>
        // Mobile navigation toggle
        document.getElementById('hamburger-btn').addEventListener('click', function() {
            document.getElementById('mobile-nav').classList.toggle('active');
            document.body.classList.toggle('nav-open');
        });

        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            const mobileNav = document.getElementById('mobile-nav');
            const hamburgerBtn = document.getElementById('hamburger-btn');
            
            if (!mobileNav.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                mobileNav.classList.remove('active');
                document.body.classList.remove('nav-open');
            }
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>
</body>
</html>
