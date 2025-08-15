<?php
$page_title = 'Premiumisme Tools - Generator Email';
$current_page = 'generator';
include '../includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper">
    <!-- Input Section -->
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <h2>Pengaturan Generator</h2>
            <div class="space-y-4">
                <div>
                    <label for="domain" class="block text-sm font-medium opacity-80 mb-2">Domain Email</label>
                    <input type="text" id="domain" value="example.com" placeholder="contoh: example.com" class="form-input">
                </div>
                <div>
                    <label for="numEmails" class="block text-sm font-medium opacity-80 mb-2">Jumlah Email</label>
                    <input type="number" id="numEmails" value="10" min="1" max="5000" class="form-input">
                </div>
                <div class="result-card">
                    <label for="nameType" class="block text-sm font-medium mb-3">Tipe Nama</label>
                    <select id="nameType" class="form-input" onchange="toggleLengthInput()">
                        <option value="randomChars">Karakter Acak</option>
                        <option value="randomNumeric">Numerik Acak</option>
                        <option value="randomAlphabet">Alfabet Acak</option>
                        <option value="randomNameNumeric">Nama + Numerik Acak</option>
                    </select>
                </div>
                <div id="lengthInputContainer">
                    <label for="usernameLength" class="block text-sm font-medium opacity-80 mb-2">Panjang Karakter</label>
                    <input type="number" id="usernameLength" value="8" min="1" max="32" class="form-input">
                </div>
                <button onclick="generateEmails()" class="w-full mt-2 btn btn-primary">
                    <i class="fas fa-cogs"></i> Generate Email
                </button>
            </div>
        </div>
    </div>

    <!-- Result Section -->
    <div id="result-section" class="hidden fade-in">
        <div class="content-section">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Hasil Email (<span id="resultCount">0</span>)</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary text-sm" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button id="copyGeneratedButton" class="btn btn-secondary text-sm" onclick="copyGeneratedEmails()" disabled>
                        <i class="fas fa-copy"></i> Salin
                    </button>
                    <button id="downloadGeneratedButton" class="btn btn-secondary text-sm" onclick="downloadGeneratedEmails()" disabled>
                        <i class="fas fa-download"></i> Unduh
                    </button>
                </div>
            </div>
            <textarea id="resultOutput" class="w-full flex-grow p-3 form-input resize-none" readonly placeholder="Hasil email akan muncul di sini..."></textarea>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[var(--darker-peri)] border border-[var(--glass-border)] rounded-xl p-6 max-w-md mx-4">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-[var(--error-color)] rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-white">Batas Maksimal</h3>
        </div>
        <p class="text-[var(--text-light)] mb-6">
            Jumlah email yang dapat dibuat maksimal <strong>5000</strong> email per generate untuk menjaga performa sistem.
        </p>
        <div class="flex justify-end">
            <button onclick="closeErrorModal()" class="btn btn-primary px-6">
                <i class="fas fa-check"></i> Mengerti
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Faker/3.1.0/faker.min.js"></script>
<script>
    let generatedEmails = [];

    function generateRandomString(length) {
        const characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return result;
    }

    function generateRandomNumeric(length = 8) {
        let result = '';
        for (let i = 0; i < length; i++) {
            result += Math.floor(Math.random() * 10);
        }
        return result;
    }

    function generateRandomAlphabet(length = 8) {
        const letters = 'abcdefghijklmnopqrstuvwxyz';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        return result;
    }

    function generateRandomNameNumeric() {
        const firstName = faker.name.firstName().toLowerCase().replace(/[^a-z0-9]/g, '');
        const randomNumbers = Math.floor(100 + Math.random() * 900);
        return firstName + randomNumbers;
    }

    function copyToClipboard(text, successMessage) {
        navigator.clipboard.writeText(text).then(() => showToast(successMessage));
    }

    function generateEmails() {
        const domain = document.getElementById('domain').value.trim();
        const numEmails = parseInt(document.getElementById('numEmails').value);
        const nameType = document.getElementById('nameType').value;
        const usernameLength = Math.max(1, Math.min(32, parseInt(document.getElementById('usernameLength').value) || 8));
        
        if (!domain || numEmails <= 0) {
            showToast('Mohon masukkan domain dan jumlah email yang valid.', 'error');
            return;
        }
        
        if (numEmails > 5000) {
            showErrorModal();
            return;
        }
        
        const generatedEmailsSet = new Set();
        const maxAttempts = numEmails * 3;
        let attempts = 0;
        
        while (generatedEmailsSet.size < numEmails && attempts < maxAttempts) {
            let username = '';
            switch (nameType) {
                case 'randomChars': username = generateRandomString(usernameLength); break;
                case 'randomNumeric': username = generateRandomNumeric(usernameLength); break;
                case 'randomAlphabet': username = generateRandomAlphabet(usernameLength); break;
                case 'randomNameNumeric': username = generateRandomNameNumeric(); break;
            }
            generatedEmailsSet.add(`${username}@${domain}`);
            attempts++;
        }

        generatedEmails = Array.from(generatedEmailsSet).sort();
        
        displayGeneratedResults();
        showToast(`${generatedEmails.length} email berhasil dibuat!`);

        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('result-section').classList.remove('hidden');
    }

    function displayGeneratedResults() {
        const resultOutput = document.getElementById('resultOutput');
        const resultCount = document.getElementById('resultCount');
        const downloadBtn = document.getElementById('downloadGeneratedButton');
        const copyBtn = document.getElementById('copyGeneratedButton');
        
        resultOutput.value = generatedEmails.join('\n');
        resultCount.textContent = generatedEmails.length;

        const hasResults = generatedEmails.length > 0;
        downloadBtn.disabled = !hasResults;
        copyBtn.disabled = !hasResults;
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
        generatedEmails = [];
        displayGeneratedResults();
    }

    function copyGeneratedEmails() {
        if (generatedEmails.length === 0) return;
        copyToClipboard(generatedEmails.join('\n'), `${generatedEmails.length} email berhasil disalin!`);
    }

    function downloadGeneratedEmails() {
        if (generatedEmails.length === 0) return;
        const emailsText = generatedEmails.join('\n');
        const blob = new Blob([emailsText], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `generated-emails.txt`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast(`File berhasil diunduh!`);
    }

    function showErrorModal() {
        document.getElementById('errorModal').classList.remove('hidden');
    }

    function closeErrorModal() {
        document.getElementById('errorModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const lengthContainer = document.getElementById('lengthInputContainer');
        
        function toggleLengthInput() {
            const selectedType = document.getElementById('nameType').value;
            lengthContainer.style.display = selectedType === 'randomNameNumeric' ? 'none' : 'block';
        }
        
        // Initialize on page load
        toggleLengthInput();
        
        // Close modal when clicking outside
        document.getElementById('errorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeErrorModal();
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?> 