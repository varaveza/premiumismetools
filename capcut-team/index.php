<?php
$page_title = 'Capcut-Teams Auto-Invite - Premiumisme';
$current_page = 'capcut-team';
$base_prefix = '../';
include '../includes/header.php'; 
?>

<div class="content-wrapper">
    <div class="content-section">

        <form id="invite-form" class="grid grid-cols-1 gap-4">
            <div>
                <label for="link" class="block mb-2 text-sm opacity-80">CapCut Workspace Invite Link</label>
                <input 
                    type="text" 
                    id="link"
                    name="link" 
                    class="form-input" 
                    placeholder="https://www.capcut.com/sv2/xxxxx/"
                    required>
            </div>

            <div>
                <label for="accounts" class="block mb-2 text-sm opacity-80">Accounts (Email|Password)</label>
                <textarea 
                    id="accounts"
                    name="accounts" 
                    class="form-input" 
                    rows="10" 
                    placeholder="email1@example.com|password1&#10;email2@example.com|password2&#10;email3@example.com|password3"
                    required></textarea>
                <p class="text-xs opacity-60 mt-1">Format: email|password (satu per baris)</p>
            </div>

            <div>
                <label for="workers" class="block mb-2 text-sm opacity-80">Threads</label>
                <input 
                    type="number" 
                    id="workers"
                    name="workers" 
                    class="form-input" 
                    value="10"
                    min="1"
                    max="50"
                    placeholder="10">
            </div>
            
            <button type="submit" id="submit-btn" class="btn btn-primary w-full mt-2">
                <span id="submit-btn-text">
                    Submit
                </span>
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
        
        const form = document.getElementById('invite-form');
        const submitBtn = document.getElementById('submit-btn');
        const submitBtnText = document.getElementById('submit-btn-text');
        const loadingSpinner = document.getElementById('loading-spinner');
        const resultsContainer = document.getElementById('results-container');

        // Check if elements exist
        if (!form) {
            console.error('Form not found!');
            return;
        }
        if (!submitBtn) {
            console.error('Submit button not found!');
            return;
        }
        
        // API URL - Development: localhost, Production: gunakan PHP proxy untuk menghindari CORS
        const isLocalhost = window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';
        
        const API_ENDPOINT = isLocalhost 
            ? 'http://localhost:8001/api/join'  // Development: direct ke Node.js
            : window.location.origin + '/tools/capcut-team/api-proxy.php';  // Production: melalui PHP proxy

        function parseAccounts(accountsText) {
            const lines = accountsText.split('\n').map(line => line.trim()).filter(Boolean);
            const accounts = [];
            
            for (const line of lines) {
                // Support format: email|password or email:password
                const separator = line.includes('|') ? '|' : ':';
                const parts = line.split(separator);
                
                if (parts.length >= 2) {
                    const email = parts[0].trim();
                    const password = parts.slice(1).join(separator).trim();
                    if (email && password) {
                        accounts.push([email, password]);
                    }
                }
            }
            
            return accounts;
        }

        function renderResults(data) {
            if (!data) {
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
                        <div class="text-sm text-red-400">Tidak ada response dari server.</div>
                    </div>
                `;
                return;
            }

            if (data.error) {
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
                        <div class="text-sm text-red-400 whitespace-pre-wrap">${escapeHTML(data.error)}</div>
                    </div>
                `;
                return;
            }

            if (data.success && data.summary && data.results) {
                const summary = data.summary;
                const results = data.results;
                
                // Filter hanya yang success untuk copy
                const successResults = results.filter(r => r.status === 'success');
                const successEmails = successResults.map(r => r.email).filter(Boolean);
                
                // Hitung ulang failed termasuk member_full
                const failedCount = results.filter(r => 
                    r.status === 'failed' || 
                    r.status === 'error' || 
                    r.status === 'login_failed' || 
                    r.status === 'member_full'
                ).length;
                
                let resultHTML = `
                    <div class="result-card mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold">Hasil Invite</h3>
                            <div class="flex items-center gap-2">
                                ${successEmails.length > 0 ? `
                                <button id="copy-success-btn" class="btn btn-secondary text-sm" title="Copy Success Emails">
                                    <i class="fas fa-copy mr-2"></i>Copy Success (${successEmails.length})
                                </button>
                                ` : ''}
                                <div class="flex gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400 border border-green-500/30">
                                        Success: ${summary.successfully_joined || 0}
                                    </span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                        Already: ${summary.already_member || 0}
                                    </span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30">
                                        Failed: ${failedCount}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4 p-4 bg-black/20 rounded-lg border border-white/10">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <div class="opacity-70">Total Accounts</div>
                                    <div class="font-bold text-lg">${summary.total_accounts || 0}</div>
                                </div>
                                <div>
                                    <div class="opacity-70">Successfully Joined</div>
                                    <div class="font-bold text-lg text-green-400">${summary.successfully_joined || 0}</div>
                                </div>
                                <div>
                                    <div class="opacity-70">Already Member</div>
                                    <div class="font-bold text-lg text-blue-400">${summary.already_member || 0}</div>
                                </div>
                                <div>
                                    <div class="opacity-70">Time Elapsed</div>
                                    <div class="font-bold text-lg">${summary.time_elapsed || 'N/A'}</div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-96 overflow-y-auto">
                `;

                results.forEach((result, index) => {
                    // member_full dianggap sebagai failed
                    const isFailed = result.status === 'member_full' || 
                                   result.status === 'failed' || 
                                   result.status === 'error' || 
                                   result.status === 'login_failed';
                    
                    const statusColors = {
                        'success': 'bg-green-500/10 border-green-500/20 text-green-400',
                        'already': 'bg-blue-500/10 border-blue-500/20 text-blue-400',
                        'member_full': 'bg-red-500/10 border-red-500/20 text-red-400', // Changed to red
                        'login_failed': 'bg-red-500/10 border-red-500/20 text-red-400',
                        'failed': 'bg-red-500/10 border-red-500/20 text-red-400',
                        'error': 'bg-red-500/10 border-red-500/20 text-red-400'
                    };

                    const statusIcons = {
                        'success': 'fa-check-circle',
                        'already': 'fa-info-circle',
                        'member_full': 'fa-times-circle', 
                        'login_failed': 'fa-times-circle',
                        'failed': 'fa-times-circle',
                        'error': 'fa-times-circle'
                    };

                    const statusClass = statusColors[result.status] || statusColors['error'];
                    const statusIcon = statusIcons[result.status] || statusIcons['error'];
                    
                    // Display status text - member_full ditampilkan sebagai "failed"
                    const displayStatus = result.status === 'member_full' ? 'failed' : result.status;

                    // Format message - jika ada link, buat bisa diklik
                    let messageHTML = '';
                    if (result.message) {
                        // Escape HTML dulu, lalu convert URL menjadi link
                        const escapedMessage = escapeHTML(result.message);
                        // Convert URL to clickable link (setelah escape untuk security)
                        messageHTML = escapedMessage.replace(/(https?:\/\/[^\s<>"']+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 underline break-all">$1</a>');
                    }
                    
                    resultHTML += `
                        <div class="p-3 rounded-lg border ${statusClass}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 flex-1">
                                    <i class="fas ${statusIcon}"></i>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold">${escapeHTML(result.email || 'N/A')}</div>
                                        <div class="text-xs opacity-80 mt-1 break-words">${messageHTML}</div>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold uppercase ml-2 flex-shrink-0">${displayStatus || 'unknown'}</span>
                            </div>
                        </div>
                    `;
                });

                resultHTML += `
                        </div>
                    </div>
                `;

                resultsContainer.innerHTML = resultHTML;
                
                // Add event listener untuk tombol copy success
                if (successEmails.length > 0) {
                    // Store successEmails di data attribute untuk akses
                    const copySuccessBtn = document.getElementById('copy-success-btn');
                    if (copySuccessBtn) {
                        const emailsToCopy = [...successEmails]; // Copy array
                        copySuccessBtn.addEventListener('click', () => {
                            const emailsText = emailsToCopy.join('\n');
                            navigator.clipboard.writeText(emailsText).then(() => {
                                const originalHTML = copySuccessBtn.innerHTML;
                                copySuccessBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
                                copySuccessBtn.disabled = true;
                                setTimeout(() => {
                                    copySuccessBtn.innerHTML = originalHTML;
                                    copySuccessBtn.disabled = false;
                                }, 2000);
                            }).catch(() => {
                                // Fallback
                                const textArea = document.createElement('textarea');
                                textArea.value = emailsText;
                                textArea.style.position = 'fixed';
                                textArea.style.opacity = '0';
                                document.body.appendChild(textArea);
                                textArea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textArea);
                                const originalHTML = copySuccessBtn.innerHTML;
                                copySuccessBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
                                copySuccessBtn.disabled = true;
                                setTimeout(() => {
                                    copySuccessBtn.innerHTML = originalHTML;
                                    copySuccessBtn.disabled = false;
                                }, 2000);
                            });
                        });
                    }
                }
            } else {
                resultsContainer.innerHTML = `
                    <div class="result-card mt-6 bg-yellow-500/10 border-yellow-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-yellow-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-yellow-500">Warning</h3>
                        </div>
                        <div class="text-sm text-yellow-400">Response format tidak dikenali.</div>
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
            
            const link = document.getElementById('link').value.trim();
            const accountsText = document.getElementById('accounts').value.trim();
            const workers = parseInt(document.getElementById('workers').value) || 10;

            if (!link) {
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
                        <div class="text-sm text-red-400">Link invite harus diisi.</div>
                    </div>
                `;
                return;
            }

            const accounts = parseAccounts(accountsText);
            if (accounts.length === 0) {
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
                        <div class="text-sm text-red-400">Minimal satu account harus diisi (format: email|password).</div>
                    </div>
                `;
                return;
            }

            submitBtn.disabled = true;
            submitBtnText.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            resultsContainer.innerHTML = '<div class="text-center text-gray-300 mt-6">Processing please wait.</div>';

            const payload = {
                link: link,
                accounts: accounts,
                workers: workers
            };

            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'POST',
                    mode: 'cors',
                    credentials: 'omit',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    let errorData;
                    try {
                        errorData = JSON.parse(errorText);
                    } catch (e) {
                        errorData = { message: errorText };
                    }
                    
                    // Jika ada message dari backend, tampilkan dengan format yang lebih baik
                    if (errorData.message) {
                        const escapedMessage = escapeHTML(errorData.message);
                        const messageHTML = escapedMessage.replace(/(https?:\/\/[^\s<>"']+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 underline break-all">$1</a>');
                        
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
                                <div class="text-sm text-red-400 break-words">${messageHTML}</div>
                            </div>
                        `;
                        return;
                    }
                    
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const data = await response.json();
                renderResults(data);
            } catch (error) {
                console.error('API Error:', error.message);
                resultsContainer.innerHTML = `
                    <div class="result-card mt-6 bg-red-500/10 border-red-500/20">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-500">Connection Error</h3>
                        </div>
                        <div class="text-sm text-red-400">
                            Gagal terhubung ke API server. Pastikan server berjalan di ${API_ENDPOINT}
                            <br><br>
                            <strong>Error:</strong> ${escapeHTML(error.message)}
                        </div>
                    </div>
                `;
            } finally {
                submitBtn.disabled = false;
                submitBtnText.classList.remove('hidden');
                loadingSpinner.classList.add('hidden');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>

