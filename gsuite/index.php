<?php
$page_title = 'GSuite Auto Create - Premiumisme';
$current_page = 'gsuite';

// Minimal backend to persist unique letter tokens across requests
if (isset($_GET['action']) && $_GET['action'] === 'reserve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid payload']);
            exit;
        }

        $dbPath = __DIR__ . '/gsuite_unique.db';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS used_letter_tokens (token TEXT PRIMARY KEY)");

        $resultUsernames = [];
        foreach ($data['items'] as $item) {
            $original = (string)($item['username'] ?? '');
            if ($original === '') {
                $resultUsernames[] = '';
                continue;
            }

            // Extract letters and digits using simple regex: letters first then digits optional
            if (preg_match('/^([a-zA-Z]+)(\d*)$/', $original, $m)) {
                $letters = strtolower($m[1]);
                $digits = $m[2] ?? '';
            } else {
                // Fallback: keep only letters for token, preserve any trailing digits
                $letters = strtolower(preg_replace('/[^a-zA-Z]/', '', $original));
                $digitsMatch = [];
                preg_match('/(\d+)$/', $original, $digitsMatch);
                $digits = $digitsMatch[1] ?? '';
            }

            $len = max(1, strlen($letters));

            // Try to reserve current token, otherwise generate a new unique token with same length
            $token = $letters;
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO used_letter_tokens(token) VALUES (?)');
            $attempts = 0;
            while (true) {
                $attempts++;
                $stmt->execute([$token]);
                if ($stmt->rowCount() === 1) {
                    // Reserved successfully
                    break;
                }
                // Collision: generate a new token with same length
                $token = '';
                for ($i = 0; $i < $len; $i++) {
                    $token .= chr(ord('a') + random_int(0, 25));
                }
                if ($attempts > 1000) { // safety
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Too many collisions']);
                    exit;
                }
            }

            $resultUsernames[] = $token . $digits;
        }

        echo json_encode(['success' => true, 'usernames' => $resultUsernames]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <div class="mb-4">
                <h2 class="text-2xl font-bold text-white">GSuite Creator</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label for="firstName" class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">First Name</label>
                    <input 
                        type="text" 
                        id="firstName" 
                        placeholder="Contoh: Apps"
                        class="form-input"
                    >
                </div>

                <!-- Last Name -->
                <div>
                    <label for="lastName" class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">Last Name</label>
                    <input 
                        type="text" 
                        id="lastName" 
                        placeholder="Contoh: Premiumisme"
                        class="form-input"
                    >
                </div>

                <!-- Username Pattern -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">Pola Username</label>
                    <div class="tab-button-group">
                        <button id="tab-alphabet" class="tab-button active" onclick="switchUsernameTab('alphabet')">
                            <i class="fas fa-font mr-2"></i>Alfabet Saja
                        </button>
                        <button id="tab-sequential" class="tab-button" onclick="switchUsernameTab('sequential')">
                            <i class="fas fa-sort-numeric-up mr-2"></i>Alfanumerik
                        </button>
                    </div>
                    <div id="alphabet-tab" class="username-tab">
                        <input 
                            type="number" 
                            id="alphabetLength" 
                            placeholder="Jumlah karakter (contoh: 5)"
                            min="1" 
                            max="20"
                            class="form-input"
                        >
                    </div>

                    <div id="sequential-tab" class="username-tab hidden">
                        <div class="tab-button-group mb-3">
                            <button id="tab-manual" class="tab-button active" onclick="switchPrefixTab('manual')">
                                <i class="fas fa-edit mr-2"></i>Input Manual
                            </button>
                            <button id="tab-random" class="tab-button" onclick="switchPrefixTab('random')">
                                <i class="fas fa-dice mr-2"></i>Random 5 Huruf
                            </button>
                        </div>
                        <div id="manual-prefix-tab" class="prefix-tab">
                            <input 
                                type="text" 
                                id="usernamePrefix" 
                                placeholder="Prefix (contoh: premiumisme)"
                                class="form-input"
                            >
                        </div>
                        <div id="random-prefix-tab" class="prefix-tab hidden">
                            <div class="bg-[var(--dark-peri)] border border-[var(--glass-border)] rounded-lg p-3">
                                <p class="text-sm text-[var(--text-light)] opacity-70 mb-2">Prefix akan dibuat random 5 huruf</p>
                                <button type="button" onclick="generateRandomPrefix()" class="btn btn-secondary w-full">
                                    <i class="fas fa-sync-alt mr-2"></i>Generate Random Prefix
                                </button>
                                <div id="randomPrefixDisplay" class="mt-3 p-2 bg-[var(--accent-glass-bg)] border border-[var(--glass-border)] rounded text-center hidden">
                                    <span class="text-[var(--accent)] font-bold" id="randomPrefixText"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Domain -->
                <div>
                    <label for="domain" class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">Domain</label>
                    <input 
                        type="text" 
                        id="domain" 
                        placeholder="Contoh: premiumisme.co"
                        class="form-input"
                    >
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">Jumlah Email</label>
                    <input 
                        type="number" 
                        id="quantity" 
                        placeholder="Contoh: 100"
                        min="1" 
                        max="10000"
                        class="form-input"
                    >
                </div>

                <!-- Password -->
                <div class="md:col-span-2">
                    <label for="password" class="block text-sm font-medium text-[var(--text-light)] opacity-80 mb-2">Password</label>
                    <input 
                        type="text" 
                        id="password" 
                        placeholder="Contoh: masuk123"
                        class="form-input"
                    >
                </div>
            </div>

            <button id="generateButton" class="w-full mt-6 btn btn-primary" onclick="generateData()" disabled>
                <i class="fas fa-magic"></i>
                Generate Data
            </button>
        </div>
    </div>

    <div id="result-section" class="hidden fade-in">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h3 class="text-xl font-bold text-white">Data yang Dibuat</h3>
                <div class="flex gap-2">
                    <button class="btn btn-secondary" onclick="downloadCSV()" id="downloadButton" disabled>
                        <i class="fas fa-download"></i> Download CSV
                    </button>
                    <button class="btn btn-secondary" onclick="copyData()" id="copyButton" disabled>
                        <i class="fas fa-copy"></i> Copy
                    </button>
                    <button class="btn btn-secondary" onclick="backToInput()">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                </div>
            </div>

            <div id="dataList" class="space-y-3 max-h-96 overflow-y-auto">
            </div>

            <div id="noData" class="text-center py-12 hidden">
                <i class="fas fa-users text-6xl text-[var(--accent)] opacity-50 mb-4"></i>
                <h3 class="text-xl font-bold text-white mb-2">Tidak ada data yang dibuat!</h3>
                <p class="text-[var(--text-light)] opacity-70">Pastikan semua field telah diisi dengan benar.</p>
            </div>
        </div>
    </div>
</div>

<style>
.username-tab, .prefix-tab {
    transition: all 0.3s ease;
}

.data-item {
    background-color: var(--accent-glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.data-item:hover {
    background-color: var(--dark-peri);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.ripple {
    position: absolute;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
</style>

<script>
let generatedData = [];

document.addEventListener('DOMContentLoaded', () => {
    const inputs = ['firstName', 'lastName', 'alphabetLength', 'usernamePrefix', 'domain', 'quantity', 'password'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', validateForm);
            el.addEventListener('change', validateForm);
        }
    });

    validateForm();

    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height) * 1.6;
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - (size / 2)) + 'px';
            ripple.style.top = (e.clientY - rect.top - (size / 2)) + 'px';

            const existingRipple = this.querySelector('.ripple');
            if (existingRipple) existingRipple.remove();
            this.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
        });
    });
});

function switchUsernameTab(type) {
    const alphabetTab = document.getElementById('alphabet-tab');
    const sequentialTab = document.getElementById('sequential-tab');
    const alphabetButton = document.getElementById('tab-alphabet');
    const sequentialButton = document.getElementById('tab-sequential');

    // Hide all tabs
    alphabetTab.classList.add('hidden');
    sequentialTab.classList.add('hidden');
    
    // Remove active from all buttons
    alphabetButton.classList.remove('active');
    sequentialButton.classList.remove('active');

    if (type === 'alphabet') {
        alphabetTab.classList.remove('hidden');
        alphabetButton.classList.add('active');
    } else if (type === 'sequential') {
        sequentialTab.classList.remove('hidden');
        sequentialButton.classList.add('active');
    }
    validateForm();
}

function switchPrefixTab(type) {
    const manualTab = document.getElementById('manual-prefix-tab');
    const randomTab = document.getElementById('random-prefix-tab');
    const manualButton = document.getElementById('tab-manual');
    const randomButton = document.getElementById('tab-random');

    // Hide all tabs
    manualTab.classList.add('hidden');
    randomTab.classList.add('hidden');
    
    // Remove active from all buttons
    manualButton.classList.remove('active');
    randomButton.classList.remove('active');

    if (type === 'manual') {
        manualTab.classList.remove('hidden');
        manualButton.classList.add('active');
    } else if (type === 'random') {
        randomTab.classList.remove('hidden');
        randomButton.classList.add('active');
    }
    validateForm();
}

function generateRandomAlphabet(length) {
    const chars = 'abcdefghijklmnopqrstuvwxyz';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function generateRandomPrefix() {
    const randomPrefix = generateRandomAlphabet(5);
    document.getElementById('randomPrefixText').textContent = randomPrefix;
    document.getElementById('randomPrefixDisplay').classList.remove('hidden');
    showToast('Random prefix berhasil dibuat!');
}

function generateUsername(index, usernameType) {
    if (usernameType === 'alphabet') {
        const length = parseInt(document.getElementById('alphabetLength').value) || 5;
        return generateRandomAlphabet(length);
    } else if (usernameType === 'sequential') {
        let prefix = 'user';
        
        // Check if manual tab is active
        if (!document.getElementById('manual-prefix-tab').classList.contains('hidden')) {
            prefix = document.getElementById('usernamePrefix').value.trim() || 'user';
        } else if (!document.getElementById('random-prefix-tab').classList.contains('hidden')) {
            // Use random prefix if available, otherwise generate new one
            const randomPrefixText = document.getElementById('randomPrefixText').textContent;
            prefix = randomPrefixText || generateRandomAlphabet(5);
        }
        
        return `${prefix}${index}`;
    }
}

function validateForm() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const domain = document.getElementById('domain').value.trim();
    const quantity = parseInt(document.getElementById('quantity').value);
    const password = document.getElementById('password').value.trim();
    
    let isValid = firstName && lastName && domain && quantity > 0 && password;
    
    // Check username pattern validation
    const alphabetTab = document.getElementById('alphabet-tab');
    const sequentialTab = document.getElementById('sequential-tab');
    
    if (!alphabetTab.classList.contains('hidden')) {
        const alphabetLength = parseInt(document.getElementById('alphabetLength').value);
        isValid = isValid && alphabetLength > 0 && alphabetLength <= 20;
    } else if (!sequentialTab.classList.contains('hidden')) {
        // Check if manual tab is active
        if (!document.getElementById('manual-prefix-tab').classList.contains('hidden')) {
            const usernamePrefix = document.getElementById('usernamePrefix').value.trim();
            isValid = isValid && usernamePrefix;
        } else if (!document.getElementById('random-prefix-tab').classList.contains('hidden')) {
            // For random prefix, always valid since it will be generated
            isValid = isValid && true;
        }
    }

    const generateButton = document.getElementById('generateButton');
    generateButton.disabled = !isValid;
}

function generateData() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const domain = document.getElementById('domain').value.trim();
    const quantity = parseInt(document.getElementById('quantity').value);
    const password = document.getElementById('password').value.trim();
    
    // Determine username type
    const alphabetTab = document.getElementById('alphabet-tab');
    let usernameType = 'alphabet';
    if (!document.getElementById('sequential-tab').classList.contains('hidden')) {
        usernameType = 'sequential';
    }
    
    generatedData = [];
    
    for (let i = 1; i <= quantity; i++) {
        const username = generateUsername(i, usernameType);
        const email = `${username}@${domain}`;
        
        generatedData.push({
            firstName: firstName,
            lastName: lastName,
            email: email,
            password: password
        });
    }

    // Reserve unique letter tokens server-side so future runs avoid duplicates
    const payload = { items: generatedData.map(d => ({ username: d.email.split('@')[0] })) };
    fetch('?action=reserve', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(r => r.json())
        .then(res => {
            if (res && res.success && Array.isArray(res.usernames)) {
                const domainFinal = document.getElementById('domain').value.trim();
                generatedData = generatedData.map((d, idx) => ({ ...d, email: `${res.usernames[idx]}@${domainFinal}` }));
            }
            displayResults();
            document.getElementById('main-section').classList.add('hidden');
            document.getElementById('result-section').classList.remove('hidden');
            showToast(`${generatedData.length} data berhasil dibuat!`);
        })
        .catch(() => {
            displayResults();
            document.getElementById('main-section').classList.add('hidden');
            document.getElementById('result-section').classList.remove('hidden');
            showToast(`${generatedData.length} data berhasil dibuat!`);
        });
}

function displayResults() {
    const dataList = document.getElementById('dataList');
    const noData = document.getElementById('noData');
    
    if (generatedData.length > 0) {
        dataList.innerHTML = '';
        noData.classList.add('hidden');
        
        generatedData.forEach((data, index) => {
            const dataItem = document.createElement('div');
            dataItem.className = 'data-item';
            dataItem.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-[var(--accent)] bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <span class="text-[var(--accent)] font-bold text-sm">${index + 1}</span>
                        </div>
                        <div class="grid grid-cols-4 gap-4 text-sm flex-1">
                            <div><span class="opacity-70">First Name:</span> <span class="font-semibold">${data.firstName}</span></div>
                            <div><span class="opacity-70">Last Name:</span> <span class="font-semibold">${data.lastName}</span></div>
                            <div><span class="opacity-70">Email Address:</span> <span class="font-semibold">${data.email}</span></div>
                            <div><span class="opacity-70">Password:</span> <span class="font-semibold">${data.password}</span></div>
                        </div>
                    </div>
                    <button onclick="copySingleData(${index})" class="text-[var(--accent)] hover:text-[var(--light-peri)] text-sm ml-4" title="Copy Email & Password">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            `;
            dataList.appendChild(dataItem);
        });
        
        document.getElementById('downloadButton').disabled = false;
        document.getElementById('copyButton').disabled = false;
    } else {
        dataList.innerHTML = '';
        noData.classList.remove('hidden');
        document.getElementById('downloadButton').disabled = true;
        document.getElementById('copyButton').disabled = true;
    }
}

function copySingleData(index) {
    const data = generatedData[index];
    const dataText = `${data.email}|${data.password}`;
    navigator.clipboard.writeText(dataText).then(() => {
        showToast(`Email & Password ${index + 1} berhasil dicopy`);
    });
}

function copyData() {
    const emailPasswordData = generatedData.map(data => `${data.email}|${data.password}`).join('\n');
    navigator.clipboard.writeText(emailPasswordData).then(() => {
        showToast(`${generatedData.length} Email & Password berhasil dicopy ke clipboard`);
    });
}

function generateCSV() {
    const headers = ['First Name [Required]', 'Last Name [Required]', 'Email Address [Required]', 'Password [Required]', 'Password Hash Function [UPLOAD ONLY]', 'Org Unit Path [Required]', 'New Primary Email [UPLOAD ONLY]', 'Recovery Email', 'Home Secondary Email', 'Work Secondary Email', 'Recovery Phone [MUST BE IN THE E.164 FORMAT]', 'Work Phone', 'Home Phone', 'Mobile Phone', 'Work Address', 'Home Address', 'Employee ID', 'Employee Type', 'Employee Title', 'Manager Email', 'Department', 'Cost Center', 'Building ID', 'Floor Name', 'Floor Section', 'Change Password at Next Sign-In', 'New Status [UPLOAD ONLY]', 'Advanced Protection Program enrollment'];
    const csvRows = [headers.join(',')];
    
    generatedData.forEach(data => {
        const row = [
            data.firstName,
            data.lastName,
            data.email,
            data.password,
            '', // Password Hash Function [UPLOAD ONLY]
            '/', // Org Unit Path [Required] - selalu "/"
            '', // New Primary Email [UPLOAD ONLY]
            '', // Recovery Email
            '', // Home Secondary Email
            '', // Work Secondary Email
            '', // Recovery Phone [MUST BE IN THE E.164 FORMAT]
            '', // Work Phone
            '', // Home Phone
            '', // Mobile Phone
            '', // Work Address
            '', // Home Address
            '', // Employee ID
            '', // Employee Type
            '', // Employee Title
            '', // Manager Email
            '', // Department
            '', // Cost Center
            '', // Building ID
            '', // Floor Name
            '', // Floor Section
            'FALSE', // Change Password at Next Sign-In - selalu "FALSE"
            '', // New Status [UPLOAD ONLY]
            '' // Advanced Protection Program enrollment
        ];
        csvRows.push(row.join(','));
    });
    
    return csvRows.join('\n');
}

function downloadCSV() {
    if (generatedData.length === 0) {
        showToast('Tidak ada data yang dibuat untuk didownload', 'error');
        return;
    }
    
    const csvContent = generateCSV();
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `auto-created-data-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    showToast(`File CSV dengan ${generatedData.length} data berhasil didownload`);
}

function backToInput() {
    document.getElementById('result-section').classList.add('hidden');
    document.getElementById('main-section').classList.remove('hidden');
    clearForm();
    generatedData = [];
}

function clearForm() {
    document.getElementById('firstName').value = '';
    document.getElementById('lastName').value = '';
    document.getElementById('alphabetLength').value = '';
    document.getElementById('usernamePrefix').value = '';
    document.getElementById('domain').value = '';
    document.getElementById('quantity').value = '';
    document.getElementById('password').value = '';
    
    // Clear random prefix display
    document.getElementById('randomPrefixDisplay').classList.add('hidden');
    document.getElementById('randomPrefixText').textContent = '';
    
    validateForm();
}
</script>

<?php include '../includes/footer.php'; ?>
