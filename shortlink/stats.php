<?php
$page_title = 'Shortlink Stats - Statistik Link';
$current_page = 'shortlink';
include '../includes/header.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';
$dbFile = 'shortlinks.json';

// Load database
if (file_exists($dbFile)) {
    $links = json_decode(file_get_contents($dbFile), true);
} else {
    $links = [];
}

// Find the link
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
                        <h2 class="text-2xl font-bold text-white mb-2">Statistik Shortlink</h2>
                        <p class="opacity-70">Analisis performa link Anda</p>
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
                    <button onclick="window.location.href='shortlink.php'" class="btn btn-secondary flex-1">
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
                    <h2 class="text-2xl font-bold text-white mb-2">Shortlink Tidak Ditemukan</h2>
                    <p class="opacity-70 mb-6">Link yang Anda cari tidak ada atau sudah dihapus.</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="window.location.href='shortlink.php'" class="btn btn-primary">
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

// Auto-refresh stats every 30 seconds
setInterval(() => {
    // Only refresh if user is on the page
    if (!document.hidden) {
        location.reload();
    }
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>
