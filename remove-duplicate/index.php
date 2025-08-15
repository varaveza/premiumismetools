<?php
$page_title = 'Premiumisme Tools';
$current_page = 'duplicate';
include '../includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper">
    <!-- Input Section -->
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <h2>Hapus Email Duplikat</h2>
            <div class="space-y-4">
                <div>
                    <label for="emailInput" class="block text-sm font-medium opacity-80 mb-2">Daftar Email</label>
                    <textarea id="emailInput" placeholder="Masukkan email satu per baris..." class="w-full h-40 p-3 form-input resize-none"></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <span id="emailCount" class="text-sm opacity-70">0 email</span>
                        <button onclick="clearEmails()" class="text-sm font-medium" style="color: var(--error-color);">Bersihkan</button>
                    </div>
                </div>
                
                <div class="result-card">
                    <label class="block text-sm font-medium mb-3">Opsi</label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="caseSensitive" class="mr-3">
                            <label for="caseSensitive" class="cursor-pointer">Case Sensitive (Beda huruf besar/kecil dianggap berbeda)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="trimWhitespace" class="mr-3" checked>
                            <label for="trimWhitespace" class="cursor-pointer">Hapus spasi di awal dan akhir</label>
                        </div>
                    </div>
                </div>

                <button onclick="removeDuplicates()" class="w-full mt-2 btn btn-primary">
                    <i class="fas fa-filter"></i> Hapus Duplikat
                </button>
            </div>
        </div>
    </div>

    <!-- Result Section -->
    <div id="result-section" class="hidden fade-in">
        <div class="content-section">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Hasil Pembersihan</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary text-sm" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button id="copyResultButton" class="btn btn-secondary text-sm" onclick="copyResult()" disabled>
                        <i class="fas fa-copy"></i> Salin
                    </button>
                    <button id="downloadResultButton" class="btn btn-secondary text-sm" onclick="downloadResult()" disabled>
                        <i class="fas fa-download"></i> Unduh
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="result-card">
                    <h4 class="font-bold text-white mb-3">Statistik</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="opacity-70">Email Asli:</span>
                            <span id="originalCount">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Email Setelah Dibersihkan:</span>
                            <span id="cleanedCount">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Duplikat Dihapus:</span>
                            <span id="duplicateCount" style="color: var(--error-color);">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Penghematan:</span>
                            <span id="savingsPercentage">0%</span>
                        </div>
                    </div>
                </div>
                
                <div class="result-card">
                    <h4 class="font-bold text-white mb-3">Email Bersih</h4>
                    <textarea id="resultOutput" class="w-full h-32 p-2 form-input text-sm resize-none" readonly placeholder="Email yang sudah dibersihkan akan muncul di sini..."></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let originalEmails = [];
    let cleanedEmails = [];

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('emailInput').addEventListener('input', updateEmailCount);
        updateEmailCount();
    });

    function updateEmailCount() {
        const emailInputText = document.getElementById('emailInput').value;
        originalEmails = emailInputText.split('\n').map(e => e.trim()).filter(e => e);
        
        document.getElementById('emailCount').textContent = `${originalEmails.length} email`;
    }

    function clearEmails() {
        document.getElementById('emailInput').value = '';
        updateEmailCount();
    }

    function removeDuplicates() {
        if (originalEmails.length === 0) {
            showToast('Harap masukkan minimal satu email', 'error');
            return;
        }

        const caseSensitive = document.getElementById('caseSensitive').checked;
        const trimWhitespace = document.getElementById('trimWhitespace').checked;

        // Process emails
        let processedEmails = originalEmails.map(email => {
            if (trimWhitespace) {
                email = email.trim();
            }
            return caseSensitive ? email : email.toLowerCase();
        });

        // Remove duplicates while preserving order
        const seen = new Set();
        const uniqueEmails = [];
        const originalOrder = [];

        originalEmails.forEach((email, index) => {
            const processedEmail = processedEmails[index];
            if (!seen.has(processedEmail)) {
                seen.add(processedEmail);
                uniqueEmails.push(email);
                originalOrder.push(index);
            }
        });

        cleanedEmails = uniqueEmails;

        // Calculate statistics
        const originalCount = originalEmails.length;
        const cleanedCount = cleanedEmails.length;
        const duplicateCount = originalCount - cleanedCount;
        const savingsPercentage = originalCount > 0 ? ((duplicateCount / originalCount) * 100).toFixed(1) : 0;

        // Display results
        document.getElementById('originalCount').textContent = originalCount;
        document.getElementById('cleanedCount').textContent = cleanedCount;
        document.getElementById('duplicateCount').textContent = duplicateCount;
        document.getElementById('savingsPercentage').textContent = savingsPercentage + '%';

        document.getElementById('resultOutput').value = cleanedEmails.join('\n');

        // Enable buttons
        document.getElementById('copyResultButton').disabled = cleanedEmails.length === 0;
        document.getElementById('downloadResultButton').disabled = cleanedEmails.length === 0;

        // Show result section
        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('result-section').classList.remove('hidden');

        showToast(`Berhasil menghapus ${duplicateCount} email duplikat!`);
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
    }

    function copyResult() {
        if (cleanedEmails.length === 0) return;
        navigator.clipboard.writeText(cleanedEmails.join('\n')).then(() => {
            showToast(`${cleanedEmails.length} email berhasil disalin!`);
        });
    }

    function downloadResult() {
        if (cleanedEmails.length === 0) return;
        const emailsText = cleanedEmails.join('\n');
        const blob = new Blob([emailsText], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `cleaned-emails.txt`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast(`File berhasil diunduh!`);
    }
</script>

<?php include '../includes/footer.php'; ?>
