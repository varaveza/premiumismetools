<?php
$page_title = 'Shortlink - Pembuat Link Pendek';
$current_page = 'shortlink';
include '../includes/header.php';
?>

<!-- Konten Utama -->
<div>
    <!-- Input Section -->
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <h2>Buat Shortlink</h2>
            <div class="space-y-4">
                <div>
                    <label for="originalUrl" class="block text-sm font-medium opacity-80 mb-2">URL Asli:</label>
                    <input type="url" id="originalUrl" placeholder="https://example.com/very-long-url-here..." class="form-input">
                </div>
<<<<<<< HEAD

=======
>>>>>>> 540ea07a7625ca845a3df12f14df24175dd39954
                <button id="createButton" class="w-full btn btn-primary" onclick="createShortlink()" disabled>
                    <i class="fas fa-link"></i> Buat Shortlink
                </button>
            </div>
        </div>
    </div>

    <!-- Result Section -->
    <div id="result-section" class="hidden fade-in">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Shortlink Berhasil Dibuat!</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button id="createNewButton" class="btn btn-primary" onclick="createNewLink()">
                        <i class="fas fa-plus"></i> Buat Baru
                    </button>
                </div>
            </div>
            
            <div class="bg-[var(--darker-peri)] p-4 rounded-lg border border-[var(--glass-border)]">
                <div class="mb-3">
                    <label class="block text-sm font-medium opacity-80 mb-2">URL Asli:</label>
                    <div class="text-sm opacity-70 break-all" id="resultOriginalUrl"></div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium opacity-80 mb-2">Shortlink:</label>
                    <div class="flex items-center gap-2">
                        <div class="short-url flex-1" id="resultShortUrl"></div>
                        <button onclick="copyShortUrl()" class="btn btn-secondary text-sm px-3 py-2">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium opacity-80 mb-2">Klik:</label>
                    <div class="text-sm opacity-70" id="resultClicks">0</div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="testShortlink()" class="btn btn-primary flex-1">
                        <i class="fas fa-external-link-alt"></i> Test Link
                    </button>
                    <button onclick="deleteShortlink()" class="btn btn-secondary">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentShortlink = null;
    let links = JSON.parse(localStorage.getItem('shortlinks') || '[]');

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('originalUrl').addEventListener('input', validateForm);
    });

    function validateForm() {
        const originalUrl = document.getElementById('originalUrl').value.trim();
        const createButton = document.getElementById('createButton');
        createButton.disabled = !originalUrl || !isValidUrl(originalUrl);
    }

    function isValidUrl(string) {
        try {
            const url = new URL(string);
            // Check for dangerous protocols
            if (url.protocol === 'javascript:' || url.protocol === 'data:' || url.protocol === 'file:') {
                return false;
            }
            // Check for localhost or private IPs (optional security)
            if (url.hostname === 'localhost' || url.hostname.startsWith('127.') || url.hostname.startsWith('192.168.')) {
                return false;
            }
            return true;
        } catch (_) {
            return false;
        }
    }

    function generateSlug() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    function createShortlink() {
        const originalUrl = document.getElementById('originalUrl').value.trim();
        
        if (!originalUrl || !isValidUrl(originalUrl)) {
            showToast('Harap masukkan URL yang valid', 'error');
            return;
        }

        // Generate slug
        const slug = generateSlug();

        const shortlink = {
            id: Date.now().toString(),
            originalUrl: originalUrl,
            slug: slug,
            shortUrl: `${window.location.origin}/${slug}`,
            clicks: 0,
            createdAt: new Date().toISOString()
        };

        // Save to server (JSON file)
        saveToServer(shortlink);
        
        // Also save to localStorage for local management
        links.push(shortlink);
        localStorage.setItem('shortlinks', JSON.stringify(links));
        
        currentShortlink = shortlink;
        showResult(shortlink);
        showToast('Shortlink berhasil dibuat!');
    }

    function saveToServer(shortlink) {
        // Create form data to send to server
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('data', JSON.stringify(shortlink));

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Shortlink saved to server');
            } else {
                console.error('Failed to save to server:', data.error);
            }
        })
        .catch(error => {
            console.error('Error saving to server:', error);
        });
    }

    function showResult(shortlink) {
        document.getElementById('resultOriginalUrl').textContent = shortlink.originalUrl;
        document.getElementById('resultShortUrl').textContent = shortlink.shortUrl;
        document.getElementById('resultClicks').textContent = shortlink.clicks;
        
        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('result-section').classList.remove('hidden');
    }

    function copyShortUrl() {
        const shortUrl = document.getElementById('resultShortUrl').textContent;
        navigator.clipboard.writeText(shortUrl).then(() => {
            showToast('Shortlink berhasil disalin!');
        });
    }

    function testShortlink() {
        if (currentShortlink) {
            currentShortlink.clicks++;
            localStorage.setItem('shortlinks', JSON.stringify(links));
            document.getElementById('resultClicks').textContent = currentShortlink.clicks;
            window.open(currentShortlink.originalUrl, '_blank');
            showToast('Link dibuka di tab baru');
        }
    }

    function deleteShortlink() {
        if (currentShortlink) {
            links = links.filter(link => link.id !== currentShortlink.id);
            localStorage.setItem('shortlinks', JSON.stringify(links));
            backToInput();
            showToast('Shortlink berhasil dihapus');
        }
    }

    function createNewLink() {
        document.getElementById('originalUrl').value = '';
        backToInput();
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
        currentShortlink = null;
    }

    // Handle hash-based redirects
    function handleHashRedirect() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            const link = links.find(l => l.slug === hash);
            if (link) {
                link.clicks++;
                localStorage.setItem('shortlinks', JSON.stringify(links));
                window.location.href = link.originalUrl;
            }
        }
    }

    // Check for hash redirects on page load
    window.addEventListener('load', handleHashRedirect);
    window.addEventListener('hashchange', handleHashRedirect);
</script>

<?php include '../includes/footer.php'; ?>
