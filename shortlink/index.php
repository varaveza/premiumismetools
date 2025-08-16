<?php
$page_title = 'Premiumisme Tools';
$current_page = 'shortlink';
include '../includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper flex items-center justify-center">
    <!-- Input Section -->
    <div id="main-section" class="fade-in w-full">
        <div class="content-section">
            <h2>Buat Shortlink</h2>
            <div class="space-y-4">
                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-600">
                    <button id="single-tab" class="tab-btn active" onclick="switchTab('single')">
                        <i class="fas fa-link"></i> Single URL
                    </button>
                    <button id="bulk-tab" class="tab-btn" onclick="switchTab('bulk')">
                        <i class="fas fa-list"></i> Bulk URLs
                    </button>
                </div>

                <!-- Single URL Tab -->
                <div id="single-content" class="tab-content">
                    <div>
                        <label for="originalUrl" class="block text-sm font-medium opacity-80 mb-2">URL Asli:</label>
                        <input type="url" id="originalUrl" placeholder="https://example.com/very-long-url-here..." class="form-input">
                    </div>

                    <button id="createButton" class="w-full btn btn-primary" onclick="createShortlink()" disabled>
                        <i class="fas fa-link"></i> Buat Shortlink
                    </button>
                </div>

                <!-- Bulk URLs Tab -->
                <div id="bulk-content" class="tab-content hidden">
                    <div class="space-y-4">
                        <!-- Input Method Selection -->
                        <div class="flex gap-2">
                            <button id="text-tab" class="bulk-tab-btn active" onclick="switchBulkMethod('text')">
                                <i class="fas fa-keyboard"></i> Text Input
                            </button>
                            <button id="file-tab" class="bulk-tab-btn" onclick="switchBulkMethod('file')">
                                <i class="fas fa-file-upload"></i> Upload File
                            </button>
                        </div>

                        <!-- Text Input Method -->
                        <div id="text-method" class="bulk-method">
                            <label for="bulkUrls" class="block text-sm font-medium opacity-80 mb-2">URLs (satu per baris):</label>
                            <textarea id="bulkUrls" rows="8" placeholder="https://example1.com&#10;https://example2.com&#10;https://example3.com" class="form-input font-mono text-sm"></textarea>
                            <div class="text-xs opacity-60 mt-1">Masukkan satu URL per baris. Maksimal 50 URL sekaligus.</div>
                        </div>

                        <!-- File Upload Method -->
                        <div id="file-method" class="bulk-method hidden">
                            <label for="csvFile" class="block text-sm font-medium opacity-80 mb-2">Upload File CSV:</label>
                            <input type="file" id="csvFile" accept=".csv,.txt" class="form-input">
                            <div class="text-xs opacity-60 mt-1">Format: satu URL per baris. Maksimal 50 URL.</div>
                        </div>

                        <button id="createBulkButton" class="w-full btn btn-primary" onclick="createBulkShortlinks()" disabled>
                            <i class="fas fa-list"></i> Buat Bulk Shortlinks
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Section -->
    <div id="result-section" class="hidden fade-in w-full">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Shortlink Berhasil Dibuat!</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button onclick="viewStats()" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Lihat Stats
                    </button>
                    <button id="createNewButton" class="btn btn-secondary" onclick="createNewLink()">
                        <i class="fas fa-plus"></i> Buat Baru
                    </button>
                </div>
            </div>
            
            <div class="result-card">
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
                    <button onclick="viewStats()" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> Lihat Stats
                    </button>
                    <button onclick="deleteShortlink()" class="btn btn-secondary">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Result Section -->
    <div id="bulk-result-section" class="hidden fade-in w-full">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Bulk Shortlinks Berhasil Dibuat!</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button onclick="copyAllLinks()" class="btn btn-accent">
                        <i class="fas fa-copy"></i> Copy All Links
                    </button>
                    <button onclick="downloadBulkResults()" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download CSV
                    </button>
                    <button id="createNewBulkButton" class="btn btn-secondary" onclick="createNewBulkLink()">
                        <i class="fas fa-plus"></i> Buat Baru
                    </button>
                </div>
            </div>
            
            <div class="result-card">
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium opacity-80">Hasil Bulk Creation:</label>
                        <span class="text-sm opacity-70" id="bulkSummary"></span>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-gray-600">
                                <tr>
                                    <th class="text-left py-2 px-2">No</th>
                                    <th class="text-left py-2 px-2">URL Asli</th>
                                    <th class="text-left py-2 px-2">Shortlink</th>
                                    <th class="text-left py-2 px-2">Status</th>
                                    <th class="text-left py-2 px-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="bulkResultsTable">
                                <!-- Results will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
        color: rgba(156, 163, 175, 0.8);
    }
    .tab-btn:hover {
        border-bottom-color: rgba(156, 163, 175, 0.4);
        color: rgba(156, 163, 175, 1);
    }
    .tab-btn.active {
        border-bottom-color: #3b82f6;
        color: #60a5fa;
    }
    .tab-content {
        transition: all 0.3s ease;
    }
    .bulk-tab-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid rgba(75, 85, 99, 0.6);
        border-radius: 0.25rem;
        transition: all 0.3s ease;
        color: rgba(156, 163, 175, 0.8);
    }
    .bulk-tab-btn:hover {
        background-color: rgba(55, 65, 81, 0.7);
        color: rgba(156, 163, 175, 1);
    }
    .bulk-tab-btn.active {
        background-color: #2563eb;
        border-color: #3b82f6;
        color: white;
    }
    .bulk-method {
        transition: all 0.3s ease;
    }
    .result-table th {
        font-weight: 500;
        color: rgba(209, 213, 219, 0.8);
    }
    .result-table td {
        padding: 0.5rem;
        border-bottom: 1px solid rgba(55, 65, 81, 0.7);
    }
    .status-success {
        color: #4ade80;
    }
    .status-error {
        color: #f87171;
    }
</style>

<script>
    let currentShortlink = null;
    let links = JSON.parse(localStorage.getItem('shortlinks') || '[]');
    let bulkResults = [];

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('originalUrl').addEventListener('input', validateForm);
        document.getElementById('bulkUrls').addEventListener('input', validateBulkForm);
        document.getElementById('csvFile').addEventListener('change', handleFileUpload);
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

    // Tab switching functions
    function switchTab(tab) {
        // Update tab buttons
        document.getElementById('single-tab').classList.toggle('active', tab === 'single');
        document.getElementById('bulk-tab').classList.toggle('active', tab === 'bulk');
        
        // Update content
        document.getElementById('single-content').classList.toggle('hidden', tab !== 'single');
        document.getElementById('bulk-content').classList.toggle('hidden', tab !== 'bulk');
        
        // Reset forms
        if (tab === 'single') {
            document.getElementById('originalUrl').value = '';
            validateForm();
        } else {
            document.getElementById('bulkUrls').value = '';
            document.getElementById('csvFile').value = '';
            validateBulkForm();
        }
    }

    function switchBulkMethod(method) {
        // Update method buttons
        document.getElementById('text-tab').classList.toggle('active', method === 'text');
        document.getElementById('file-tab').classList.toggle('active', method === 'file');
        
        // Update method content
        document.getElementById('text-method').classList.toggle('hidden', method !== 'text');
        document.getElementById('file-method').classList.toggle('hidden', method !== 'file');
        
        // Reset inputs
        if (method === 'text') {
            document.getElementById('bulkUrls').value = '';
        } else {
            document.getElementById('csvFile').value = '';
        }
        validateBulkForm();
    }

    function validateBulkForm() {
        const bulkUrls = document.getElementById('bulkUrls').value.trim();
        const csvFile = document.getElementById('csvFile').files[0];
        const createBulkButton = document.getElementById('createBulkButton');
        
        const hasTextInput = bulkUrls.length > 0;
        const hasFileInput = csvFile && csvFile.size > 0;
        
        createBulkButton.disabled = !(hasTextInput || hasFileInput);
    }

    function handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const content = e.target.result;
            document.getElementById('bulkUrls').value = content;
            validateBulkForm();
        };
        reader.readAsText(file);
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
            shortUrl: `https://shortisme.com/${slug}`,
            clicks: 0,
            createdAt: new Date().toISOString()
        };

        // Save to server (JSON file) via API
        saveToServer(shortlink);
        
        // Also save to localStorage for local management
        links.push(shortlink);
        localStorage.setItem('shortlinks', JSON.stringify(links));
        
        currentShortlink = shortlink;
        showResult(shortlink);
        showToast('Shortlink berhasil dibuat!');
    }

    function createBulkShortlinks() {
        const bulkUrls = document.getElementById('bulkUrls').value.trim();
        if (!bulkUrls) {
            showToast('Harap masukkan URL yang valid', 'error');
            return;
        }

        // Parse URLs from textarea
        const urlList = bulkUrls.split('\n')
            .map(url => url.trim())
            .filter(url => url.length > 0)
            .slice(0, 50); // Limit to 50 URLs

        if (urlList.length === 0) {
            showToast('Tidak ada URL yang valid', 'error');
            return;
        }

        if (urlList.length > 50) {
            showToast('Maksimal 50 URL sekaligus', 'error');
            return;
        }

        // Validate all URLs
        const validUrls = [];
        const invalidUrls = [];
        
        urlList.forEach(url => {
            if (isValidUrl(url)) {
                validUrls.push(url);
            } else {
                invalidUrls.push(url);
            }
        });

        if (validUrls.length === 0) {
            showToast('Tidak ada URL yang valid', 'error');
            return;
        }

        // Show progress
        showToast(`Memproses ${validUrls.length} URL...`, 'info');
        
        // Process URLs
        processBulkUrls(validUrls, invalidUrls);
    }

    async function processBulkUrls(validUrls, invalidUrls) {
        bulkResults = [];
        let successCount = 0;
        let errorCount = 0;

        // Process each URL
        for (let i = 0; i < validUrls.length; i++) {
            const url = validUrls[i];
            
            try {
                const slug = generateSlug();
                const shortlink = {
                    id: Date.now().toString() + '_' + i,
                    originalUrl: url,
                    slug: slug,
                    shortUrl: `https://shortisme.com/${slug}`,
                    clicks: 0,
                    createdAt: new Date().toISOString()
                };

                // Save to server
                await saveToServerAsync(shortlink);
                
                // Save to localStorage
                links.push(shortlink);
                
                bulkResults.push({
                    ...shortlink,
                    status: 'success',
                    message: 'Berhasil dibuat'
                });
                successCount++;
                
                // Update progress
                if (i % 5 === 0 || i === validUrls.length - 1) {
                    showToast(`Progress: ${i + 1}/${validUrls.length} URL diproses`, 'info');
                }
                
                // Small delay to avoid overwhelming the server
                await new Promise(resolve => setTimeout(resolve, 100));
                
            } catch (error) {
                bulkResults.push({
                    originalUrl: url,
                    status: 'error',
                    message: 'Gagal dibuat: ' + error.message
                });
                errorCount++;
            }
        }

        // Add invalid URLs to results
        invalidUrls.forEach(url => {
            bulkResults.push({
                originalUrl: url,
                status: 'error',
                message: 'URL tidak valid'
            });
            errorCount++;
        });

        // Save to localStorage
        localStorage.setItem('shortlinks', JSON.stringify(links));
        
        // Show results
        showBulkResults(successCount, errorCount);
        showToast(`Bulk creation selesai! ${successCount} berhasil, ${errorCount} gagal`);
    }

    async function saveToServerAsync(shortlink) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('data', JSON.stringify(shortlink));

            fetch('https://shortisme.com/api-optimized.php', {
                method: 'POST',
                body: formData,
                mode: 'cors',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(new Error(data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                reject(error);
            });
        });
    }

    function saveToServer(shortlink) {
        // Create form data to send to server
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('data', JSON.stringify(shortlink));

        // Use optimized API endpoint
        fetch('https://shortisme.com/api-optimized.php', {
            method: 'POST',
            body: formData,
            mode: 'cors',
            headers: {
                'Accept': 'application/json'
            }
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

    function viewStats() {
        if (currentShortlink) {
            // Redirect ke halaman stats dengan slug
            window.open(`https://shortisme.com/${currentShortlink.slug}/stats`, '_blank');
            showToast('Membuka halaman statistik...');
        }
    }

    function createNewLink() {
        document.getElementById('originalUrl').value = '';
        backToInput();
    }

    function showBulkResults(successCount, errorCount) {
        const tableBody = document.getElementById('bulkResultsTable');
        const summary = document.getElementById('bulkSummary');
        
        // Update summary
        summary.textContent = `${successCount} berhasil, ${errorCount} gagal`;
        
        // Clear table
        tableBody.innerHTML = '';
        
        // Populate table
        bulkResults.forEach((result, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-800';
            
            const statusClass = result.status === 'success' ? 'status-success' : 'status-error';
            const shortUrl = result.shortUrl || '-';
            
            row.innerHTML = `
                <td class="py-2 px-2">${index + 1}</td>
                <td class="py-2 px-2">
                    <div class="max-w-xs truncate" title="${result.originalUrl}">
                        ${result.originalUrl}
                    </div>
                </td>
                <td class="py-2 px-2">
                    ${result.status === 'success' ? 
                        `<div class="flex items-center gap-2">
                            <span class="max-w-xs truncate" title="${shortUrl}">${shortUrl}</span>
                            <button onclick="copyUrl('${shortUrl}')" class="text-xs btn btn-secondary px-2 py-1">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>` : 
                        '-'
                    }
                </td>
                <td class="py-2 px-2">
                    <span class="${statusClass}">${result.message}</span>
                </td>
                <td class="py-2 px-2">
                    ${result.status === 'success' ? 
                        `<button onclick="testBulkUrl('${result.originalUrl}')" class="text-xs btn btn-primary px-2 py-1">
                            <i class="fas fa-external-link-alt"></i> Test
                        </button>` : 
                        '-'
                    }
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Show bulk result section
        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('bulk-result-section').classList.remove('hidden');
    }

    function copyUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('URL berhasil disalin!');
        });
    }

    function testBulkUrl(url) {
        window.open(url, '_blank');
        showToast('Link dibuka di tab baru');
    }

    function copyAllLinks() {
        if (bulkResults.length === 0) {
            showToast('Tidak ada link untuk disalin', 'error');
            return;
        }

        // Filter hanya link yang berhasil dibuat
        const successfulLinks = bulkResults.filter(result => result.status === 'success');
        
        if (successfulLinks.length === 0) {
            showToast('Tidak ada link yang berhasil dibuat', 'error');
            return;
        }

        // Buat teks dengan semua shortlink
        let allLinksText = '';
        successfulLinks.forEach((result, index) => {
            allLinksText += `${result.shortUrl}\n`;
        });

        // Salin ke clipboard
        navigator.clipboard.writeText(allLinksText.trim()).then(() => {
            showToast(`${successfulLinks.length} shortlink berhasil disalin!`);
        }).catch(() => {
            // Fallback untuk browser yang tidak support clipboard API
            const textArea = document.createElement('textarea');
            textArea.value = allLinksText.trim();
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast(`${successfulLinks.length} shortlink berhasil disalin!`);
        });
    }

    function downloadBulkResults() {
        if (bulkResults.length === 0) {
            showToast('Tidak ada data untuk diunduh', 'error');
            return;
        }

        // Create CSV content
        let csvContent = 'No,Original URL,Short URL,Status,Message\n';
        
        bulkResults.forEach((result, index) => {
            const originalUrl = result.originalUrl || '';
            const shortUrl = result.shortUrl || '';
            const status = result.status || '';
            const message = result.message || '';
            
            // Escape CSV values
            const escapedOriginalUrl = `"${originalUrl.replace(/"/g, '""')}"`;
            const escapedShortUrl = `"${shortUrl.replace(/"/g, '""')}"`;
            const escapedMessage = `"${message.replace(/"/g, '""')}"`;
            
            csvContent += `${index + 1},${escapedOriginalUrl},${escapedShortUrl},${status},${escapedMessage}\n`;
        });

        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `bulk-shortlinks-${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('File CSV berhasil diunduh!');
    }

    function createNewBulkLink() {
        document.getElementById('bulkUrls').value = '';
        document.getElementById('csvFile').value = '';
        backToInput();
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('bulk-result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
        currentShortlink = null;
        bulkResults = [];
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
