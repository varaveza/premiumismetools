cd <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Premiumisme Tools'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../logo.svg">
    <link rel="icon" type="image/png" href="../logo.svg">
    <link rel="shortcut icon" href="../logo.svg">
    
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
                <a href="../generator-email/" class="nav-link <?php echo $current_page === 'generator' ? 'active' : ''; ?>">Generator Email</a>
                <a href="../refund-calculator/" class="nav-link <?php echo $current_page === 'refund' ? 'active' : ''; ?>">Refund Calculator</a>
                <a href="../split-mail/" class="nav-link <?php echo $current_page === 'splitter' ? 'active' : ''; ?>">Email Splitter</a>
                <a href="../remove-duplicate/" class="nav-link <?php echo $current_page === 'duplicate' ? 'active' : ''; ?>">Remove Duplicate</a>
                <a href="../shortlink/" class="nav-link <?php echo $current_page === 'shortlink' ? 'active' : ''; ?>">Shortlink</a>
            </nav>

            <!-- Mobile Navigation Overlay -->
            <div id="mobile-nav" class="mobile-nav">
                <div class="mobile-nav-content">
                    <a href="../generator-email/" class="mobile-nav-link <?php echo $current_page === 'generator' ? 'active' : ''; ?>">Generator Email</a>
                    <a href="../refund-calculator/" class="mobile-nav-link <?php echo $current_page === 'refund' ? 'active' : ''; ?>">Refund Calculator</a>
                    <a href="../split-mail/" class="mobile-nav-link <?php echo $current_page === 'splitter' ? 'active' : ''; ?>">Email Splitter</a>
                    <a href="../remove-duplicate/" class="mobile-nav-link <?php echo $current_page === 'duplicate' ? 'active' : ''; ?>">Remove Duplicate</a>
                    <a href="../shortlink/" class="mobile-nav-link <?php echo $current_page === 'shortlink' ? 'active' : ''; ?>">Shortlink</a>
                </div>
            </div>
        </header>
