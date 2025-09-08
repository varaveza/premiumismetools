<?php $base_prefix = isset($base_prefix) ? $base_prefix : '../'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Premiumisme Tools'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base_prefix; ?>favicon.png">
    <link rel="shortcut icon" href="<?php echo $base_prefix; ?>favicon.png">
    <link rel="apple-touch-icon" href="<?php echo $base_prefix; ?>favicon.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_prefix; ?>assets/css/style.css">
    <style>
        .nav-scroller { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 8px; }
        .nav-viewport { overflow-x: auto; overflow-y: hidden; position: relative; white-space: nowrap; scrollbar-width: none; -ms-overflow-style: none; }
        .nav-viewport::-webkit-scrollbar { display: none; }
        .nav-track { display: inline-flex; gap: 8px; padding: 0; white-space: nowrap; }
        .nav-track.desktop-nav { flex-wrap: nowrap; }
        .nav-arrow { width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text-light); cursor: pointer; display:flex; align-items:center; justify-content:center; }
        .nav-arrow:disabled { opacity: .4; cursor: default; }
    </style>
    
    <!-- PDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Ensure PDF libraries are loaded
        window.addEventListener('load', function() {
            if (typeof window.html2pdf === 'undefined') {
                console.warn('html2pdf not loaded, attempting to load...');
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                script.onload = function() {
                    console.log('html2pdf loaded successfully on window load');
                };
                script.onerror = function() {
                    console.error('Failed to load html2pdf on window load');
                };
                document.head.appendChild(script);
            }
        });
    </script>
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
                <img src="<?php echo $base_prefix; ?>logo.svg" alt="Premiumisme Logo" class="logo">
            </div>

            <!-- Desktop Navigation: Scrollable with arrows -->
            <div class="nav-scroller">
                <button class="nav-arrow left" id="navPrev" aria-label="Prev" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="nav-viewport" id="navViewport">
                    <nav class="desktop-nav nav-track" id="navTrack">
                        <a href="<?php echo $base_prefix; ?>generator-email/" class="nav-link <?php echo $current_page === 'generator' ? 'active' : ''; ?>">Generator Email</a>
                        <a href="<?php echo $base_prefix; ?>gsuite/" class="nav-link <?php echo $current_page === 'gsuite' ? 'active' : ''; ?>">GSuite Creator</a>
                        <a href="<?php echo $base_prefix; ?>refund-calculator/" class="nav-link <?php echo $current_page === 'refund' ? 'active' : ''; ?>">Refund Calculator</a>
                        <a href="<?php echo $base_prefix; ?>split-mail/" class="nav-link <?php echo $current_page === 'splitter' ? 'active' : ''; ?>">Email Splitter</a>
                        <a href="<?php echo $base_prefix; ?>remove-duplicate/" class="nav-link <?php echo $current_page === 'duplicate' ? 'active' : ''; ?>">Remove Duplicate</a>
                        <a href="<?php echo $base_prefix; ?>shortlink/" class="nav-link <?php echo $current_page === 'shortlink' ? 'active' : ''; ?>">Shortlink</a>
                        <a href="<?php echo $base_prefix; ?>spotify-creator/" class="nav-link <?php echo $current_page === 'spo' ? 'active' : ''; ?>">Spotify Creator</a>
                        <a href="<?php echo $base_prefix; ?>capcut-creator/" class="nav-link <?php echo $current_page === 'capcut' ? 'active' : ''; ?>">CapCut Creator</a>
                    </nav>
                </div>
                <button class="nav-arrow right" id="navNext" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Mobile Navigation Overlay -->
            <div id="mobile-nav" class="mobile-nav">
                <div class="mobile-nav-content">
                    <a href="<?php echo $base_prefix; ?>generator-email/" class="mobile-nav-link <?php echo $current_page === 'generator' ? 'active' : ''; ?>">Generator Email</a>
                    <a href="<?php echo $base_prefix; ?>gsuite/" class="mobile-nav-link <?php echo $current_page === 'gsuite' ? 'active' : ''; ?>">GSuite Creator</a>
                    <a href="<?php echo $base_prefix; ?>refund-calculator/" class="mobile-nav-link <?php echo $current_page === 'refund' ? 'active' : ''; ?>">Refund Calculator</a>
                    <a href="<?php echo $base_prefix; ?>split-mail/" class="mobile-nav-link <?php echo $current_page === 'splitter' ? 'active' : ''; ?>">Email Splitter</a>
                    <a href="<?php echo $base_prefix; ?>remove-duplicate/" class="mobile-nav-link <?php echo $current_page === 'duplicate' ? 'active' : ''; ?>">Remove Duplicate</a>
                    <a href="<?php echo $base_prefix; ?>shortlink/" class="mobile-nav-link <?php echo $current_page === 'shortlink' ? 'active' : ''; ?>">Shortlink</a>
                    <a href="<?php echo $base_prefix; ?>spotify-creator/" class="mobile-nav-link <?php echo $current_page === 'spo' ? 'active' : ''; ?>">Spotify Creator</a>
                    <a href="<?php echo $base_prefix; ?>capcut-creator/" class="mobile-nav-link <?php echo $current_page === 'capcut' ? 'active' : ''; ?>">CapCut Creator</a>
                </div>
            </div>
        </header>
