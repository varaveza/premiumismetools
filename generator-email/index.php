<?php
$page_title = 'Email Generator - Buat Email';
$current_page = 'generator';
include '../includes/header.php';
?>

<!-- Konten Utama -->
<div>
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
                    <input type="number" id="numEmails" value="10" min="1" class="form-input">
                </div>
                <div class="bg-[var(--darker-peri)] p-4 rounded-xl border border-[var(--glass-border)]">
                    <label class="block text-sm font-medium mb-3">Tipe Nama</label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" id="randomChars" name="nameType" value="randomChars" checked>
                            <label for="randomChars" class="cursor-pointer">Karakter Acak</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="randomNumeric" name="nameType" value="randomNumeric">
                            <label for="randomNumeric" class="cursor-pointer">Numerik Acak</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="randomAlphabet" name="nameType" value="randomAlphabet">
                            <label for="randomAlphabet" class="cursor-pointer">Alfabet Acak</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="randomNameNumeric" name="nameType" value="randomNameNumeric">
                            <label for="randomNameNumeric" class="cursor-pointer">Nama + Numerik Acak</label>
                        </div>
                    </div>
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
        <div class="content-section h-full flex flex-col">
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
        const nameType = document.querySelector('input[name="nameType"]:checked').value;
        const usernameLength = Math.max(1, Math.min(32, parseInt(document.getElementById('usernameLength').value) || 8));
        
        if (!domain || numEmails <= 0) {
            showToast('Mohon masukkan domain dan jumlah email yang valid.', 'error');
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

    document.addEventListener('DOMContentLoaded', function() {
        const radioButtons = document.querySelectorAll('input[name="nameType"]');
        const lengthContainer = document.getElementById('lengthInputContainer');
        
        function toggleLengthInput() {
            const selectedType = document.querySelector('input[name="nameType"]:checked').value;
            lengthContainer.style.display = selectedType === 'randomNameNumeric' ? 'none' : 'block';
        }
        
        radioButtons.forEach(radio => radio.addEventListener('change', toggleLengthInput));
        toggleLengthInput();
    });
</script>

<?php include '../includes/footer.php'; ?> 