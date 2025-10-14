<?php
$page_title = 'Premiumisme Tools';
$current_page = 'drive';
$base_prefix = '../';
include '../includes/header.php'; 
?>

<div class="content-wrapper">
    <div class="content-section">
        <h2>GDrive Downloader</h2>

        <form id="extractor-form" class="grid grid-cols-1 gap-4">
            <div>
                <label for="urls" class="block mb-2 text-sm opacity-80">Google Drive URLs (one per line, max 50)</label>
                <textarea 
                    id="urls"
                    name="urls" 
                    class="form-input" 
                    rows="8" 
                    placeholder="Masukkan URL Google Drive di sini, satu per baris...&#10;Contoh:&#10;https://drive.google.com/file/d/1dRz7kLUZAvRAyv_W8UneRIBXX7ro6L3U/view?usp=sharing&#10;https://drive.google.com/file/d/1dlEYauL0Pu1WCjrjD0ZZ5FXRPGGZ42ie/view?usp=sharing"
                    required></textarea>
            </div>
            
            <button type="submit" id="submit-btn" class="btn btn-primary w-full mt-2">
                <span id="submit-btn-text">Submit</span>
                <svg id="loading-spinner" class="animate-spin -ml-1 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>

        <!-- Container untuk menampilkan hasil -->
        <div id="results-container" class="mt-8"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('extractor-form');
        const submitBtn = document.getElementById('submit-btn');
        const submitBtnText = document.getElementById('submit-btn-text');
        const loadingSpinner = document.getElementById('loading-spinner');
        const resultsContainer = document.getElementById('results-container');
        const urlsTextarea = document.getElementById('urls');

        // --- PERBAIKAN ---
        // Konfigurasi API URL untuk development dan production
        const isLocalhost = window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1' ||
                           window.location.hostname.includes('localhost');
        
        const API_BASE_URL = isLocalhost 
            ? 'http://localhost:1203'  // Development - direct to Node.js
            : window.location.origin;  // Production - use PHP proxy
        
        // Debug info
        console.log('Hostname:', window.location.hostname);
        console.log('API Base URL:', API_BASE_URL);
        console.log('Is Localhost:', isLocalhost);

        function extractFileId(url) {
            if (!url) return null;
            const patterns = [
                /\/file\/d\/([a-zA-Z0-9-_]+)/,
                /id=([a-zA-Z0-9-_]+)/,
                /\/d\/([a-zA-Z0-9-_]+)/
            ];
            for (const pattern of patterns) {
                const match = url.match(pattern);
                if (match && match[1]) {
                    return match[1];
                }
            }
            if (url.length > 30 && !url.includes('/') && !url.includes(':')) {
                return url;
            }
            return null;
        }

        async function fetchContent(fileId) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 20000);
            
            try {
                // Use different endpoints for development vs production
                const endpoint = isLocalhost 
                    ? `${API_BASE_URL}/api/get-drive-content?fileId=${fileId}`
                    : `${API_BASE_URL}/tools/drive-extractor/api-proxy.php?fileId=${fileId}`;
                
                console.log('Fetching from:', endpoint);
                
                const response = await fetch(endpoint, {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: `HTTP error! status: ${response.status}` }));
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }
                return await response.text();
            } catch (error) {
                clearTimeout(timeoutId);
                throw error;
            }
        }
        
        // Fungsi render hasil yang disederhanakan - hanya menampilkan konten gabungan
        function renderResults(results) {
            const successCount = results.filter(r => r.status === 'success').length;
            const errorCount = results.length - successCount;
            let combinedContent = '';
            let failedLinks = [];

            results.forEach(result => {
                if (result.status === 'success') {
                    // Remove spaces from content
                    const cleanContent = result.content.replace(/\s+/g, '');
                    combinedContent += cleanContent + '\n';
                } else {
                    failedLinks.push({
                        index: result.index,
                        url: result.url,
                        message: result.message
                    });
                }
            });

            let resultHTML = '';

            if (combinedContent.trim()) {
                resultHTML += `
                    <div class="result-card mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <div class="font-bold">Hasil (${successCount} berhasil, ${errorCount} gagal)</div>
                            <button id="copy-btn" type="button" class="btn btn-secondary">Copy</button>
                        </div>
                        <pre id="result-text" class="bg-black/30 p-3 rounded border border-white/10 text-sm overflow-x-auto whitespace-pre-wrap">${escapeHTML(combinedContent.trim())}</pre>
                    </div>
                `;

                // Event listener untuk tombol copy
                const copyBtn = document.getElementById('copy-btn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', () => {
                        const contentToCopy = document.getElementById('result-text').textContent;
                        navigator.clipboard.writeText(contentToCopy).then(() => {
                            copyBtn.textContent = 'Copied!';
                            setTimeout(() => { copyBtn.textContent = 'Copy'; }, 2000);
                        }).catch(() => {
                            // Fallback
                            const textArea = document.createElement('textarea');
                            textArea.value = contentToCopy;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                            copyBtn.textContent = 'Copied!';
                            setTimeout(() => { copyBtn.textContent = 'Copy'; }, 2000);
                        });
                    });
                }
            }

            // Show failed links information
            if (failedLinks.length > 0) {
                resultHTML += `
                    <div class="result-card mt-4 bg-red-500/10 border-red-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-500">Link yang Gagal (${failedLinks.length})</h3>
                        </div>
                        <div class="space-y-2">
                            ${failedLinks.map(link => `
                                <div class="text-sm text-red-400 p-2 bg-red-500/5 rounded border border-red-500/10">
                                    <div class="font-medium">Link #${link.index}:</div>
                                    <div class="text-xs opacity-80 break-all">${escapeHTML(link.url)}</div>
                                    <div class="text-xs mt-1">Error: ${escapeHTML(link.message)}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            if (resultHTML) {
                resultsContainer.innerHTML = resultHTML;
            } else {
                resultsContainer.innerHTML = `
                    <div class="result-card mt-6 bg-red-500/10 border-red-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-500">Gagal</h3>
                        </div>
                        <div class="text-sm text-red-400">Tidak ada file yang berhasil diekstrak. Periksa URL dan pastikan file dapat diakses.</div>
                    </div>
                `;
            }
        }
        
        function escapeHTML(str) {
            if (typeof str !== 'string') return '';
            const p = document.createElement("p");
            p.appendChild(document.createTextNode(str));
            return p.innerHTML;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const urls = urlsTextarea.value.split('\n').map(url => url.trim()).filter(Boolean);
            
            if (urls.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="result-card mt-6 bg-red-500/10 border-red-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-500">Error</h3>
                        </div>
                        <div class="text-sm text-red-400">Please provide at least one URL.</div>
                    </div>
                `;
                return;
            }

            if (urls.length > 50) {
                resultsContainer.innerHTML = `
                    <div class="result-card mt-6 bg-red-500/10 border-red-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-500">Error</h3>
                        </div>
                        <div class="text-sm text-red-400">Maximum 50 URLs allowed per request.</div>
                    </div>
                `;
                return;
            }

            submitBtn.disabled = true;
            submitBtnText.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            resultsContainer.innerHTML = '<div class="text-center text-gray-300 mt-6">Processing... please wait.</div>';

            const promises = urls.map(async (url, index) => {
                const fileId = extractFileId(url);
                const result = { index: index + 1, url: url, fileId: fileId };

                if (!fileId) {
                    return { ...result, status: 'error', message: 'Invalid Google Drive URL or File ID format' };
                }

                try {
                    const content = await fetchContent(fileId);
                    return { ...result, status: 'success', message: 'File content retrieved successfully', content };
                } catch (error) {
                    return { ...result, status: 'error', message: `Failed to fetch from backend. Error: ${error.message}. Is the backend server running?` };
                }
            });
            
            const finalResults = await Promise.all(promises);
            renderResults(finalResults);

            submitBtn.disabled = false;
            submitBtnText.classList.remove('hidden');
            loadingSpinner.classList.add('hidden');
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
