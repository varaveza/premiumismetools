<?php
$page_title = 'Premiumisme Tools';
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
                <button id="tab-rar" class="tab-button" onclick="switchTab('rar')">RAR Download</button>
            </div>

            <div id="manual-tab" class="tab-content">
                <textarea id="emailInput" placeholder="Masukkan email:password atau email saja, satu per baris" class="w-full h-40 p-3 form-input resize-none"></textarea>
                <div class="flex justify-between items-center mt-2">
                    <span id="emailCount" class="text-sm opacity-70">0 baris</span>
                    <button onclick="clearEmails()" class="btn btn-secondary text-sm">
                        <i class="fas fa-trash"></i> Bersihkan
                    </button>
                </div>
            </div>

            <div id="file-tab" class="tab-content hidden">
                <div class="upload-area text-center cursor-pointer" onclick="triggerFileInput('emailFile')" ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                    <div class="mb-4"><i class="fas fa-upload fa-2x text-[var(--light-peri)]"></i></div>
                    <p class="opacity-80 mb-2">Klik atau jatuhkan file ke sini</p>
                    <p class="text-sm opacity-60">Format: .txt, satu baris per data</p>
                    <input type="file" id="emailFile" class="hidden" accept=".txt" onchange="handleFileSelect(event)">
                </div>
                <div id="emailFileInfo" class="mt-3 text-sm opacity-70 hidden"></div>
            </div>

            <div id="rar-tab" class="tab-content hidden">
                <div class="bg-[var(--dark-bg)] p-4 rounded-lg border border-[var(--border-color)]">
                    <h4 class="text-white font-bold mb-3">Konfigurasi RAR Download</h4>
                    <div class="space-y-3">
                        <div>
                            <label for="rarPrefix" class="block text-sm font-medium opacity-80 mb-1">Prefix nama file:</label>
                            <input type="text" id="rarPrefix" value="fileisme" class="form-input w-full" placeholder="fileisme">
                        </div>
                        <div>
                            <label for="rarExtension" class="block text-sm font-medium opacity-80 mb-1">Ekstensi file:</label>
                            <select id="rarExtension" class="form-input w-full">
                                <option value=".rar">.rar</option>
                                <option value=".zip">.zip</option>
                                <option value=".7z">.7z</option>
                            </select>
                        </div>
                        <div>
                            <label for="rarStartNumber" class="block text-sm font-medium opacity-80 mb-1">Nomor awal:</label>
                            <input type="number" id="rarStartNumber" value="1" min="1" class="form-input w-full">
                        </div>
                        <div class="text-sm opacity-70">
                            <p>Format output: <span id="rarFormatPreview" class="font-mono text-[var(--light-peri)]">splitisme.rar</span></p>
                            <p class="text-xs opacity-60 mt-1">Satu file RAR berisi multiple file fileisme-{nomor}.txt di dalamnya</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label for="splitSize" class="block text-sm font-medium opacity-80 mb-2">Bagi per (X) baris:</label>
                <input type="number" id="splitSize" value="10" min="1" class="form-input w-full">
            </div>

            <button id="splitButton" class="w-full mt-2 btn btn-primary py-3" onclick="splitEmails()" disabled>
                <i class="fas fa-columns"></i> Bagi Data
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
                    <button id="downloadRarButton" class="btn btn-accent" onclick="downloadAllAsRar()" disabled>
                        <i class="fas fa-file-archive"></i> Download RAR
                    </button>
                </div>
            </div>
            <div id="splitResults" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
<script>
    let allLines = [];

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('emailInput').addEventListener('input', updateLineCount);
        document.getElementById('rarPrefix').addEventListener('input', updateRarFormatPreview);
        document.getElementById('rarStartNumber').addEventListener('input', updateRarFormatPreview);
        document.getElementById('rarExtension').addEventListener('change', updateRarFormatPreview);
        updateLineCount();
        updateRarFormatPreview();
    });

    function updateRarFormatPreview() {
        const extension = document.getElementById('rarExtension').value || '.rar';
        document.getElementById('rarFormatPreview').textContent = `splitisme${extension}`;
    }

    function switchTab(type) {
        document.getElementById('manual-tab').classList.toggle('hidden', type !== 'manual');
        document.getElementById('file-tab').classList.toggle('hidden', type !== 'file');
        document.getElementById('rar-tab').classList.toggle('hidden', type !== 'rar');
        document.getElementById('tab-manual').classList.toggle('active', type === 'manual');
        document.getElementById('tab-file').classList.toggle('active', type === 'file');
        document.getElementById('tab-rar').classList.toggle('active', type === 'rar');
    }

    function updateLineCount() {
        const inputText = document.getElementById('emailInput').value;
        allLines = inputText.split('\n').map(line => line.trim()).filter(line => line);
        
        document.getElementById('emailCount').textContent = `${allLines.length} baris`;
        document.getElementById('splitButton').disabled = allLines.length === 0;
    }

    function clearEmails() {
        document.getElementById('emailInput').value = '';
        const fileInfo = document.getElementById('emailFileInfo');
        fileInfo.classList.add('hidden');
        fileInfo.textContent = '';
        document.getElementById('emailFile').value = '';
        updateLineCount();
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
            updateLineCount();
            showToast(`${allLines.length} baris berhasil dimuat.`);
        };
        reader.readAsText(file);
    }

    function splitEmails() {
        if (allLines.length === 0) {
            showToast('Harap masukkan minimal satu baris data', 'error');
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
        document.getElementById('downloadRarButton').disabled = false;

        for (let i = 0; i < allLines.length; i += splitSize) {
            const group = allLines.slice(i, i + splitSize);
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
        showToast('Data berhasil dibagi!');
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
        const prefix = document.getElementById('rarPrefix').value || 'fileisme';
        const startNumber = parseInt(document.getElementById('rarStartNumber').value) || 1;
        const fileNumber = startNumber + groupIndex;
        a.download = `${prefix}-${fileNumber}.txt`;
        a.click();
        URL.revokeObjectURL(url);
        showToast(`${prefix}-${fileNumber}.txt berhasil diunduh`);
    }

    function downloadAllResults() {
        const splitSize = parseInt(document.getElementById('splitSize').value);
        const prefix = document.getElementById('rarPrefix').value || 'fileisme';
        const startNumber = parseInt(document.getElementById('rarStartNumber').value) || 1;
        const zip = new JSZip();
        for (let i = 0; i < allLines.length; i += splitSize) {
            const group = allLines.slice(i, i + splitSize);
            const fileNumber = startNumber + Math.floor(i / splitSize);
            // Konsisten dengan format RAR: fileisme-{nomor}.txt
            const fileName = `${prefix}-${fileNumber}.txt`;
            zip.file(fileName, group.join('\n'));
        }
        zip.generateAsync({type:"blob"}).then(content => {
            const url = URL.createObjectURL(content);
            const a = document.createElement('a');
            a.href = url;
            // Nama file ZIP konsisten dengan RAR: splitisme.zip
            a.download = `splitisme.zip`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('File splitisme.zip berhasil diunduh!');
        });
    }

    function downloadAllAsRar() {
        const splitSize = parseInt(document.getElementById('splitSize').value);
        const prefix = document.getElementById('rarPrefix').value || 'fileisme';
        const startNumber = parseInt(document.getElementById('rarStartNumber').value) || 1;
        const extension = document.getElementById('rarExtension').value || '.rar';
        
        const zip = new JSZip();
        for (let i = 0; i < allLines.length; i += splitSize) {
            const group = allLines.slice(i, i + splitSize);
            const fileNumber = startNumber + Math.floor(i / splitSize);
            // Nama file di dalam RAR menggunakan format fileisme-{nomor}.txt
            const fileName = `${prefix}-${fileNumber}.txt`;
            zip.file(fileName, group.join('\n'));
        }
        zip.generateAsync({type:"blob"}).then(content => {
            const url = URL.createObjectURL(content);
            const a = document.createElement('a');
            a.href = url;
            // Nama file RAR tetap splitisme.rar
            a.download = `splitisme${extension}`;
            a.click();
            URL.revokeObjectURL(url);
            showToast(`File splitisme${extension} berhasil diunduh!`);
        });
    }

    function backToInput() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('main-section').classList.remove('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
