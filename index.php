<?php
$page_title = 'Premiumisme Tools';
$current_page = 'home';
include 'includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper flex items-center justify-center" style="min-height: 100vh;">
    <!-- Input Section -->
    <div id="main-section" class="fade-in w-full">
        <div class="content-section">
            <h2>Premiumisme Tools</h2>
            <p class="text-center opacity-80 mb-8">Kumpulan tools untuk membantu pekerjaan Anda</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="generator-email/" class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Generator Email</h3>
                    <p>Buat email acak dengan berbagai domain</p>
                </a>
                
                <a href="refund-calculator/" class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Refund Calculator</h3>
                    <p>Hitung refund berdasarkan durasi dan claim</p>
                </a>
                
                <a href="split-mail/" class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-columns"></i>
                    </div>
                    <h3>Email Splitter</h3>
                    <p>Bagi email menjadi beberapa grup</p>
                </a>
                
                <a href="remove-duplicate/" class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Remove Duplicate</h3>
                    <p>Hapus email duplikat dari list</p>
                </a>
                
                <a href="shortlink/" class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Shortlink</h3>
                    <p>Buat link pendek untuk URL panjang</p>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.tool-card {
    @apply bg-[var(--accent-glass-bg)] border border-[var(--glass-border)] rounded-xl p-6 text-center transition-all duration-300 hover:transform hover:scale-105 hover:shadow-lg;
}

.tool-card:hover {
    @apply border-[var(--accent-border)] shadow-[0_8px_32px_rgba(122,110,183,0.3)];
}

.tool-icon {
    @apply w-16 h-16 bg-[var(--accent)] rounded-full flex items-center justify-center mx-auto mb-4;
}

.tool-icon i {
    @apply text-2xl text-white;
}

.tool-card h3 {
    @apply text-xl font-bold text-white mb-2;
}

.tool-card p {
    @apply text-[var(--text-light)] opacity-80;
}
</style>

<?php include 'includes/footer.php'; ?>
