<?php
$page_title = 'Email Splitter - Pembagi Email';
$current_page = 'splitter';
include '../includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper">
    <!-- Input Section -->
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <div class="tab-button-group">
                <button id="tab-manual" class="tab-button active" onclick="switchTab('manual')">Input Manual</button>
                <button id="tab-file" class="tab-button" onclick="switchTab('file')">Upload File</button>
            </div>

            <div id="manual-tab" class="tab-content">
                <textarea id="emailInput" placeholder="Masukkan email satu per baris" class="w-full h-40 p-3 form-input resize-none"></textarea>
                <div class="flex justify-between items-center mt-2">
                    <span id="emailCount" class="text-sm opacity-70">0 email</span>
                    <button onclick="clearEmails()" class="btn btn-secondary text-sm">
                        <i class="fas fa-trash"></i> Bersihkan
                    </button>
                </div>
            </div>

            <div id="file-tab" class="tab-content hidden">
                <div class="upload-area text-center cursor-pointer" onclick="triggerFileInput('emailFile')" ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                    <div class="mb-4"><i class="fas fa-upload fa-2x text-[var(--light-peri)]"></i></div>
                    <p class="opacity-80 mb-2">Klik atau jatuhkan file ke sini</p>
                    <p class="text-sm opacity-60">Format: .txt, satu email per baris</p>
                    <input type="file" id="emailFile" class="hidden" accept=".txt" onchange="handleFileSelect(event)">
                </div>
                <div id="emailFileInfo" class="mt-3 text-sm opacity-70 hidden"></div>
            </div>

            <div class="mt-6">
                <label for="splitSize" class="block text-sm font-medium opacity-80 mb-2">Bagi per (X) email:</label>
                <input type="number" id="splitSize" value="10" min="1" class="form-input w-full">
            </div>

            <button id="splitButton" class="w-full mt-2 btn btn-primary py-3" onclick="splitEmails()" disabled>
                <i class="fas fa-columns"></i> Bagi Email
            </button>
        </div>
    </div>

    <!-- Result Section -->
    <div id="result-section" class="hidden fade-in">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <h3 class="text-xl font-bold text-white">Hasil Pembagian</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button id="downloadAllButton" class="btn btn-primary" onclick="downloadAllResults()" disabled>
                        <i class="fas fa-file-archive"></i> Download Semua (.zip)
                    </button>
                </div>
            </div>
            <div id="splitResults" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
<script>
    let allEmails = [];

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('emailInput').addEventListener('input', updateEmailCount);
        updateEmailCount();
    });

    function switchTab(type) {
        document.getElementById('manual-tab').classList.toggle('hidden', type !== 'manual');
        document.getElementById('file-tab').classList.toggle('hidden', type === 'manual');
        document.getElementById('tab-manual').classList.toggle('active', type === 'manual');
        document.getElementById('tab-file').classList.toggle('active', type !== 'manual');
    }

    const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    function updateEmailCount() {
        const emailInputText = document.getElementById('emailInput').value;
        allEmails = emailInputText.split('\n').map(e => e.trim()).filter(e => e && validateEmail(e));
        
        document.getElementById('emailCount').textContent = `${allEmails.length} email valid`;
        document.getElementById('splitButton').disabled = allEmails.length === 0;
    }

    function clearEmails() {
        document.getElementById('emailInput').value = '';
        const fileInfo = document.getElementById('emailFileInfo');
        fileInfo.classList.add('hidden');
        fileInfo.textContent = '';
        document.getElementById('emailFile').value = '';
        updateEmailCount();
    }

    const triggerFileInput = (id) => document.getElementById(id).click();
    const handleDragOver = (e) => { e.preventDefault(); e.currentTarget.classList.add('dragover'); };
    const handleDragLeave = (e) => e.currentTarget.classList.remove('dragover');

    function handleDrop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');
        if (event.dataTransfer.files.length > 0) processFile(event.dataTransfer.files[0]);
    }

    function handleFileSelect(event) {
        if (event.target.files.length > 0) processFile(event.target.files[0]);
    }

    function processFile(file) {
        // Additional security validation
        const maxSize = 5 * 1024 * 1024; // 5MB limit
        const allowedTypes = ['text/plain'];
        
        // Check file size
        if (file.size > maxSize) {
            showToast('File terlalu besar (maksimal 5MB)', 'error');
            return;
        }
        
        // Check MIME type
        if (!allowedTypes.includes(file.type)) {
            showToast('Hanya file text (.txt) yang diperbolehkan', 'error');
            return;
        }
        
        // Check file extension
        const fileName = file.name.toLowerCase();
        if (!fileName.endsWith('.txt')) {
            showToast('Hanya file .txt yang diperbolehkan', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('emailInput').value = e.target.result;
            const infoElement = document.getElementById('emailFileInfo');
            infoElement.textContent = `File: ${file.name} (${file.size} bytes)`;
            infoElement.classList.remove('hidden');
            updateEmailCount();
            showToast(`${allEmails.length} email berhasil dimuat.`);
        };
        reader.readAsText(file);
    }

    function splitEmails() {
        if (allEmails.length === 0) {
            showToast('Harap masukkan minimal satu email yang valid', 'error');
            return;
        }
        const splitSize = parseInt(document.getElementById('splitSize').value);
        if (isNaN(splitSize) || splitSize <= 0) {
            showToast('Ukuran pembagian harus angka positif', 'error');
            return;
        }

        const resultsContainer = document.getElementById('splitResults');
        resultsContainer.innerHTML = '';
        document.getElementById('downloadAllButton').disabled = false;

        for (let i = 0; i < allEmails.length; i += splitSize) {
            const group = allEmails.slice(i, i + splitSize);
            const groupIndex = Math.floor(i / splitSize) + 1;
            const groupCard = document.createElement('div');
            groupCard.className = 'result-card';
            groupCard.innerHTML = `
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-bold text-white">Grup ${groupIndex} (${group.length})</h4>
                    <div class="flex space-x-2">
                        <button class="text-[var(--light-peri)] hover:text-white text-sm font-medium" onclick="copyGroup(${groupIndex - 1})">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="text-[var(--light-peri)] hover:text-white text-sm font-medium" onclick="downloadGroup(${groupIndex - 1})">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <textarea id="group-${groupIndex}" class="w-full p-2 form-input text-sm resize-none" rows="5" readonly>${group.join('\n')}</textarea>
            `;
            resultsContainer.appendChild(groupCard);
        }

        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('result-section').classList.remove('hidden');
        showToast('Email berhasil dibagi!');
    }
    
    function getGroupText(groupIndex) {
        return document.getElementById(`group-${groupIndex + 1}`).value;
    }

    function copyGroup(groupIndex) {
        navigator.clipboard.writeText(getGroupText(groupIndex)).then(() => {
            showToast(`Grup ${groupIndex + 1} berhasil disalin`);
        });
    }

    function downloadGroup(groupIndex) {
        const text = getGroupText(groupIndex);
        const blob = new Blob([text], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `email-group-${groupIndex + 1}.txt`;
        a.click();
        URL.revokeObjectURL(url);
        showToast(`Grup ${groupIndex + 1} berhasil diunduh`);
    }

    function downloadAllResults() {
        const splitSize = parseInt(document.getElementById('splitSize').value);
        const zip = new JSZip();
        for (let i = 0; i < allEmails.length; i += splitSize) {
            const group = allEmails.slice(i, i + splitSize);
            zip.file(`group-${Math.floor(i / splitSize) + 1}.txt`, group.join('\n'));
        }
        zip.generateAsync({type:"blob"}).then(content => {
            const url = URL.createObjectURL(content);
            const a = document.createElement('a');
            a.href = url;
            a.download = `all-email-groups.zip`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('Semua grup berhasil diunduh (.zip)!');
        });
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
