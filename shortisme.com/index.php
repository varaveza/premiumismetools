<?php
$page_title = 'Shortisme.com - Shortlink Service';
$current_page = 'shortlink';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Shortisme'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://premiumisme.co/tools/assets/css/style.css">
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
                <img src="https://premiumisme.co/tools/logo.svg" alt="Shortisme Logo" class="logo">
            </div>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="https://premiumisme.co/tools/generator-email/" class="nav-link">Generator Email</a>
                <a href="https://premiumisme.co/tools/refund-calculator/" class="nav-link">Refund Calculator</a>
                <a href="https://premiumisme.co/tools/split-mail/" class="nav-link">Email Splitter</a>
                <a href="https://premiumisme.co/tools/remove-duplicate/" class="nav-link">Remove Duplicate</a>
                <a href="https://premiumisme.co/tools/shortlink/" class="nav-link active">Shortlink</a>
            </nav>

            <!-- Mobile Navigation Overlay -->
            <div id="mobile-nav" class="mobile-nav">
                <div class="mobile-nav-content">
                    <a href="https://premiumisme.co/tools/generator-email/" class="mobile-nav-link">Generator Email</a>
                    <a href="https://premiumisme.co/tools/refund-calculator/" class="mobile-nav-link">Refund Calculator</a>
                    <a href="https://premiumisme.co/tools/split-mail/" class="mobile-nav-link">Email Splitter</a>
                    <a href="https://premiumisme.co/tools/remove-duplicate/" class="mobile-nav-link">Remove Duplicate</a>
                    <a href="https://premiumisme.co/tools/shortlink/" class="mobile-nav-link active">Shortlink</a>
                </div>
            </div>
        </header>

        <!-- Konten Utama -->
        <div>
            <div class="fade-in">
                <div class="content-section text-center">
                    <div class="mb-6">
                        <i class="fas fa-link text-6xl text-[var(--accent)] mb-4"></i>
                        <h2 class="text-3xl font-bold text-white mb-2">🔗 Shortisme.com</h2>
                        <p class="opacity-70 mb-2">Shortlink Service</p>
                        <p class="text-sm opacity-60 mb-6">This domain is used for shortlink redirects only.</p>
                    </div>
                    
                    <div class="bg-[var(--darker-peri)] p-6 rounded-xl border border-[var(--glass-border)] mb-6">
                        <h3 class="font-bold text-white mb-4">📋 How to use:</h3>
                        <div class="space-y-3 text-left">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-plus text-[var(--accent)]"></i>
                                <span class="opacity-70">Create shortlinks at <strong class="text-white">premiumisme.co/tools/shortlink</strong></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-external-link-alt text-[var(--accent)]"></i>
                                <span class="opacity-70">Your shortlinks will be accessible at <strong class="text-white">shortisme.com/XXXXXX</strong></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-chart-line text-[var(--accent)]"></i>
                                <span class="opacity-70">View statistics at <strong class="text-white">shortisme.com/XXXXXX/stats</strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="https://premiumisme.co/tools/shortlink/" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Shortlink
                        </a>
                        <a href="https://premiumisme.co/tools/" class="btn btn-secondary">
                            <i class="fas fa-tools"></i> All Tools
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
