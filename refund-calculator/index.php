<?php
$page_title = 'Premiumisme Tools - Refund Calculator';
$current_page = 'refund';
include '../includes/header.php';
?>

<!-- Content Wrapper untuk standarisasi layout -->
<div class="content-wrapper">
    <!-- Input Section -->
    <div id="main-section" class="fade-in">
        <div class="content-section">
            <h2>Kalkulator Refund</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label for="namaProduk" class="block text-sm font-medium opacity-80 mb-2">Nama Produk</label>
                        <input type="text" id="namaProduk" placeholder="Contoh: Capcut Pro" class="form-input">
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium opacity-80 mb-2">Username Customer</label>
                        <input type="text" id="username" placeholder="Contoh: premiumisme_bot" class="form-input">
                    </div>
                    <div>
                        <label for="hargaProduk" class="block text-sm font-medium opacity-80 mb-2">Harga Produk (Rp)</label>
                        <input type="number" id="hargaProduk" placeholder="0" class="form-input" min="0" step="1000">
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="tanggalPembelian" class="block text-sm font-medium opacity-80 mb-2">Tanggal Pembelian</label>
                        <input type="date" id="tanggalPembelian" class="form-input">
                    </div>
                    <div>
                        <label for="tanggalKendala" class="block text-sm font-medium opacity-80 mb-2">Tanggal Kendala</label>
                        <input type="date" id="tanggalKendala" class="form-input">
                    </div>
                    <div>
                        <label for="masaAktif" class="block text-sm font-medium opacity-80 mb-2">Total Masa Aktif (Hari)</label>
                        <input type="number" id="masaAktif" placeholder="30" class="form-input" min="1" step="1">
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <label for="jumlahClaim" class="block text-sm font-medium opacity-80 mb-2">Jumlah Claim Garansi</label>
                <input type="number" id="jumlahClaim" placeholder="0" class="form-input w-full" min="0" step="1" value="0">
            </div>
            <div class="mt-6">
                <button onclick="hitungRefund()" class="btn btn-primary w-full rounded-full py-3">
                    <i class="fas fa-calculator"></i> Hitung Refund
                </button>
            </div>
        </div>

    <!-- Results Section -->
    <div id="results-section" class="fade-in hidden">
        <div class="content-section">
            <h2>Hasil Perhitungan Refund</h2>
            <div class="result-card">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="opacity-70">Nama Produk:</span>
                        <span id="resultNamaProduk" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Username:</span>
                        <span id="resultUsername" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Harga Produk:</span>
                        <span id="resultHargaProduk" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Tanggal Pembelian:</span>
                        <span id="resultTanggalPembelian" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Tanggal Kendala:</span>
                        <span id="resultTanggalKendala" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Masa Aktif:</span>
                        <span id="resultMasaAktif" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Hari Digunakan:</span>
                        <span id="resultHariDigunakan" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Sisa Hari:</span>
                        <span id="resultSisaHari" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Jumlah Claim Garansi:</span>
                        <span id="resultJumlahClaim" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Biaya Service:</span>
                        <span id="resultBiayaService" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Proporsi Penggunaan:</span>
                        <span id="resultProporsi" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Biaya Penggunaan:</span>
                        <span id="resultBiayaPenggunaan" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Status:</span>
                        <span id="resultStatus" class="font-bold"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="opacity-70">Jumlah Refund:</span>
                        <span id="resultRefund" class="font-bold"></span>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-4">
                <button onclick="resetCalculator()" class="btn btn-secondary flex-1">Hitung Ulang</button>
                <button onclick="copyResults()" class="btn btn-primary flex-1">Salin Hasil</button>
            </div>
        </div>
    </div>
</div>

<script>
// Set default tanggal kendala ke hari ini
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggalKendala').value = today;
});

function hitungRefund() {
    const namaProduk = document.getElementById('namaProduk').value.trim();
    const username = document.getElementById('username').value.trim();
    const hargaProduk = parseFloat(document.getElementById('hargaProduk').value);
    const tanggalPembelian = document.getElementById('tanggalPembelian').value;
    const tanggalKendala = document.getElementById('tanggalKendala').value;
    const masaAktif = parseInt(document.getElementById('masaAktif').value);
    const jumlahClaim = parseInt(document.getElementById('jumlahClaim').value) || 0;

    // Validasi input
    if (!namaProduk || !username || !hargaProduk || !tanggalPembelian || !tanggalKendala || !masaAktif) {
        showToast('Mohon lengkapi semua data!', 'error');
        return;
    }

    if (hargaProduk <= 0 || masaAktif <= 0) {
        showToast('Harga produk dan masa aktif harus lebih dari 0!', 'error');
        return;
    }

    // Hitung hari yang digunakan
    const pembelian = new Date(tanggalPembelian);
    const kendala = new Date(tanggalKendala);
    const selisihWaktu = kendala.getTime() - pembelian.getTime();
    const hariDigunakan = Math.ceil(selisihWaktu / (1000 * 3600 * 24));

    if (hariDigunakan < 0) {
        showToast('Tanggal kendala tidak boleh sebelum tanggal pembelian!', 'error');
        return;
    }

    // Hitung sisa hari
    const sisaHari = Math.max(0, masaAktif - hariDigunakan);

    // Tentukan biaya service berdasarkan jumlah claim garansi
    let biayaService;
    if (hariDigunakan < 7) {
        biayaService = 0.8; // Pemakaian kurang dari 1 minggu
    } else if (jumlahClaim === 0) {
        biayaService = 0.7; // Belum pernah claim garansi tapi sudah lebih dari seminggu
    } else if (jumlahClaim >= 1 && jumlahClaim <= 2) {
        biayaService = 0.6; // Sudah pernah claim garansi 1-2x
    } else if (jumlahClaim === 3) {
        biayaService = 0.5; // Sudah pernah claim garansi 3x
    } else {
        biayaService = 0.4; // Sudah pernah claim garansi > 3x
    }

    // Hitung refund menggunakan rumus: (harga × sisa hari : durasi) × biaya service
    const proporsiSisa = sisaHari / masaAktif;
    const refundSebelumService = hargaProduk * proporsiSisa;
    const refund = refundSebelumService * biayaService;

    // Tentukan status
    let status, statusColor;
    if (refund > 0) {
        status = 'Berhak Refund';
        statusColor = 'var(--success-color)';
    } else if (refund < 0) {
        status = 'Kurang Bayar';
        statusColor = 'var(--error-color)';
    } else {
        status = 'Sudah Pas';
        statusColor = 'var(--accent)';
    }

    // Format currency
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };

    // Update hasil
    document.getElementById('resultNamaProduk').textContent = namaProduk;
    document.getElementById('resultUsername').textContent = username;
    document.getElementById('resultHargaProduk').textContent = formatCurrency(hargaProduk);
    document.getElementById('resultTanggalPembelian').textContent = new Date(tanggalPembelian).toLocaleDateString('id-ID');
    document.getElementById('resultTanggalKendala').textContent = new Date(tanggalKendala).toLocaleDateString('id-ID');
    document.getElementById('resultMasaAktif').textContent = masaAktif + ' hari';
    document.getElementById('resultHariDigunakan').textContent = hariDigunakan + ' hari';
    document.getElementById('resultSisaHari').textContent = sisaHari + ' hari';
    document.getElementById('resultJumlahClaim').textContent = jumlahClaim + ' kali';
    document.getElementById('resultBiayaService').textContent = (biayaService * 100) + '%';
    document.getElementById('resultProporsi').textContent = (proporsiSisa * 100).toFixed(1) + '%';
    document.getElementById('resultBiayaPenggunaan').textContent = formatCurrency(hargaProduk - refundSebelumService);
    document.getElementById('resultStatus').textContent = status;
    document.getElementById('resultStatus').style.color = statusColor;
    document.getElementById('resultRefund').textContent = formatCurrency(refund);
    document.getElementById('resultRefund').style.color = statusColor;

    // Tampilkan hasil
    document.getElementById('main-section').classList.add('hidden');
    document.getElementById('results-section').classList.remove('hidden');
    
    showToast('Perhitungan refund berhasil!');
}

function resetCalculator() {
    document.getElementById('namaProduk').value = '';
    document.getElementById('username').value = '';
    document.getElementById('hargaProduk').value = '';
    document.getElementById('tanggalPembelian').value = '';
    document.getElementById('masaAktif').value = '';
    document.getElementById('jumlahClaim').value = '0';
    
    // Reset tanggal kendala ke hari ini
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggalKendala').value = today;
    
    document.getElementById('main-section').classList.remove('hidden');
    document.getElementById('results-section').classList.add('hidden');
}

function copyResults() {
    const results = `Hasil Perhitungan Refund:
Nama Produk: ${document.getElementById('resultNamaProduk').textContent}
Username: ${document.getElementById('resultUsername').textContent}
Harga Produk: ${document.getElementById('resultHargaProduk').textContent}
Tanggal Pembelian: ${document.getElementById('resultTanggalPembelian').textContent}
Tanggal Kendala: ${document.getElementById('resultTanggalKendala').textContent}
Masa Aktif: ${document.getElementById('resultMasaAktif').textContent}
Hari Digunakan: ${document.getElementById('resultHariDigunakan').textContent}
Sisa Hari: ${document.getElementById('resultSisaHari').textContent}
Jumlah Claim Garansi: ${document.getElementById('resultJumlahClaim').textContent}
Biaya Service: ${document.getElementById('resultBiayaService').textContent}
Proporsi Penggunaan: ${document.getElementById('resultProporsi').textContent}
Biaya Penggunaan: ${document.getElementById('resultBiayaPenggunaan').textContent}
Status: ${document.getElementById('resultStatus').textContent}
Jumlah Refund: ${document.getElementById('resultRefund').textContent}`;

    navigator.clipboard.writeText(results).then(() => {
        showToast('Hasil perhitungan berhasil disalin!');
    }).catch(() => {
        showToast('Gagal menyalin hasil!', 'error');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
