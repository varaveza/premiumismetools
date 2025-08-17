<?php
$page_title = 'Premiumisme Tools';
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
                        <input type="number" id="hargaProduk" placeholder="0" class="form-input" min="0" step="0.01">
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
                <button onclick="hitungRefund()" class="btn btn-primary w-full rounded-full py-3">
                    <i class="fas fa-calculator"></i> Hitung Refund
                </button>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div id="results-section" class="fade-in hidden">
        <div class="content-section">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <h2>Hasil Perhitungan Refund</h2>
                <div class="flex gap-2">
                    <button onclick="downloadPDF()" class="btn btn-accent">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                    <button onclick="resetCalculator()" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Hitung Ulang
                    </button>
                </div>
            </div>
            <div id="refundResult" class="space-y-4">
                <!-- Result content will be generated here -->
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
    try {
        // Ambil nilai input
        const namaProduk = document.getElementById('namaProduk').value.trim();
        const username = document.getElementById('username').value.trim();
        const hargaProduk = parseFloat(document.getElementById('hargaProduk').value);
        const tanggalPembelian = document.getElementById('tanggalPembelian').value;
        const tanggalKendala = document.getElementById('tanggalKendala').value;
        const masaAktif = parseInt(document.getElementById('masaAktif').value);

        // Validasi input
        if (!namaProduk || !username || !hargaProduk || !tanggalPembelian || !tanggalKendala || !masaAktif) {
            // Gunakan custom alert/modal jika ada, jika tidak, gunakan alert bawaan
            if (typeof showToast === 'function') {
                showToast('Mohon lengkapi semua data!', 'error');
            } else {
                alert('Mohon lengkapi semua data!');
            }
            return;
        }

        if (hargaProduk <= 0 || masaAktif <= 0) {
            alert('Harga produk dan masa aktif harus lebih dari 0!');
            return;
        }

        // Hitung hari yang digunakan
        const pembelian = new Date(tanggalPembelian);
        const kendala = new Date(tanggalKendala);
        
        if (kendala < pembelian) {
            alert('Tanggal kendala tidak boleh lebih awal dari tanggal pembelian!');
            return;
        }

        const MS_PER_DAY = 1000 * 60 * 60 * 24;
        const rawDaysUsed = Math.floor((kendala - pembelian) / MS_PER_DAY) + 1;
        const hariDigunakan = Math.max(0, Math.min(masaAktif, rawDaysUsed));
        
        // Hitung proporsi penggunaan dan refund
        const usageProportion = Math.min(1, Math.max(0, hariDigunakan / masaAktif));
        const biayaPenggunaan = parseFloat((usageProportion * hargaProduk).toFixed(2));
        const refundAmount = parseFloat(Math.max(0, hargaProduk - biayaPenggunaan).toFixed(2));

        // Data untuk display
        const refundData = {
            id: Date.now(),
            namaProduk: namaProduk,
            username: username,
            hargaProduk: hargaProduk,
            tanggalPembelian: tanggalPembelian,
            tanggalKendala: tanggalKendala,
            masaAktif: masaAktif,
            hariDigunakan: hariDigunakan,
            biayaPenggunaan: biayaPenggunaan,
            refundAmount: refundAmount,
            timestamp: new Date().toLocaleString('id-ID')
        };

        // Simpan data ke global variable untuk fallback PDF
        window.lastRefundData = refundData;
        
        // Tampilkan hasil
        displayRefundResult(refundData);
        document.getElementById('main-section').classList.add('hidden');
        document.getElementById('results-section').classList.remove('hidden');
        
        if (typeof showToast === 'function') {
            showToast('Refund berhasil dihitung!');
        }
        
    } catch (error) {
        console.error('Error dalam perhitungan refund:', error);
        alert('Terjadi kesalahan dalam perhitungan. Silakan coba lagi.');
    }
}

function displayRefundResult(data) {
    const resultDiv = document.getElementById('refundResult');
    // Mengubah tampilan hasil agar sesuai dengan tema gelap
    resultDiv.innerHTML = `
        <div class="result-card">
            <div class="result-header mb-4 pb-2 border-b border-gray-600">
                <h3 class="text-xl font-bold text-white">Kalkulasi Refund Produk</h3>
                <p class="text-sm opacity-70">No. Refund #${data.id.toString().slice(-6)}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-4">
                <div class="results-column">
                    <h4 class="section-title">Informasi Produk</h4>
                    <div class="info-item"><span class="info-label">Nama Produk:</span> <span class="info-value">${data.namaProduk.toUpperCase()}</span></div>
                    <div class="info-item"><span class="info-label">Customer:</span> <span class="info-value">${data.username.toUpperCase()}</span></div>
                    <div class="info-item"><span class="info-label">Harga Produk:</span> <span class="info-value">Rp ${data.hargaProduk.toLocaleString('id-ID')}</span></div>
                </div>

                <div class="results-column">
                    <h4 class="section-title">Informasi Waktu</h4>
                    <div class="info-item"><span class="info-label">Tanggal Order:</span> <span class="info-value">${formatDate(data.tanggalPembelian)}</span></div>
                    <div class="info-item"><span class="info-label">Tanggal Kendala:</span> <span class="info-value">${formatDate(data.tanggalKendala)}</span></div>
                    <div class="info-item"><span class="info-label">Masa Aktif:</span> <span class="info-value">${data.masaAktif} hari</span></div>
                </div>
            </div>

            <div class="calculation-breakdown">
                <h4 class="section-title">Kalkulasi Penggunaan</h4>
                <div class="info-item"><span class="info-label">Hari Digunakan:</span> <span class="info-value">${data.hariDigunakan} hari</span></div>
                <div class="info-item"><span class="info-label">Biaya Penggunaan:</span> <span class="info-value">Rp ${data.biayaPenggunaan.toLocaleString('id-ID', {maximumFractionDigits: 2})}</span></div>
            </div>

            <div class="final-result">
                <div class="final-result-label">TOTAL REFUND</div>
                <div class="final-result-amount">Rp ${data.refundAmount.toLocaleString('id-ID', {maximumFractionDigits: 2})}</div>
            </div>
        </div>
    `;
}


function resetCalculator() {
    try {
        document.getElementById('namaProduk').value = '';
        document.getElementById('username').value = '';
        document.getElementById('hargaProduk').value = '';
        document.getElementById('tanggalPembelian').value = '';
        document.getElementById('masaAktif').value = '';
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggalKendala').value = today;
        
        document.getElementById('main-section').classList.remove('hidden');
        document.getElementById('results-section').classList.add('hidden');
    } catch (error) {
        console.error('Error dalam reset calculator:', error);
        alert('Terjadi kesalahan saat mereset kalkulator.');
    }
}

function downloadPDF() {
    try {
        // Check if html2pdf library is loaded
        if (typeof window.html2pdf === 'undefined') {
            console.error('html2pdf library not found, trying to load it...');
            
            // Try to load html2pdf dynamically
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
            script.onload = function() {
                console.log('html2pdf loaded successfully');
                setTimeout(() => downloadPDF(), 100);
            };
            script.onerror = function() {
                alert('Gagal memuat library PDF. Silakan refresh halaman dan coba lagi.');
            };
            document.head.appendChild(script);
            return;
        }

        // Check if we have refund data
        if (!window.lastRefundData) {
            alert('Tidak ada data untuk diunduh. Silakan hitung refund terlebih dahulu.');
            return;
        }
        
        // Show loading indicator
        if (typeof showToast === 'function') {
            showToast('Membuat PDF...', 'info');
        }
        
        generatePDFWithData(window.lastRefundData);
        
    } catch (error) {
        console.error('Error dalam download PDF:', error);
        alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi. Error: ' + error.message);
    }
}

// Fungsi untuk generate PDF menggunakan data langsung (lebih reliable)
function generatePDFWithData(data) {
    const opt = {
        margin:       0.5,
        filename:     `refund-premiumisme-${Date.now()}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    const currentDate = new Date().toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'long' });
    
    // Menggunakan data langsung dari variabel (lebih reliable)
    const namaProduk = data.namaProduk.toUpperCase();
    const customer = data.username.toUpperCase();
    const hargaProduk = 'Rp ' + data.hargaProduk.toLocaleString('id-ID');
    const tanggalOrder = formatDate(data.tanggalPembelian);
    const tanggalKendala = formatDate(data.tanggalKendala);
    const masaAktif = data.masaAktif + ' hari';
    const hariDigunakan = data.hariDigunakan + ' hari';
    const biayaPenggunaan = 'Rp ' + data.biayaPenggunaan.toLocaleString('id-ID', {maximumFractionDigits: 2});
    const totalRefund = 'Rp ' + data.refundAmount.toLocaleString('id-ID', {maximumFractionDigits: 2});
    const refundNumber = 'No. Refund #' + data.id.toString().slice(-6);
    
    console.log('Using direct data for PDF:', {
        namaProduk,
        customer,
        hargaProduk,
        tanggalOrder,
        tanggalKendala,
        masaAktif,
        hariDigunakan,
        biayaPenggunaan,
        totalRefund,
        refundNumber
    });

    // Membuat konten HTML untuk PDF menggunakan template yang sama persis dengan file refund.html
    const htmlContentForPdf = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Dokumen Refund - Premiumisme Tools</title>
            <!-- Menggunakan inline CSS untuk kompatibilitas PDF terbaik -->
            <style>
                body { font-family: 'Inter', Arial, sans-serif; background: #fff; color: #333; margin: 0; padding: 20px; }
                .container { max-width: 800px; margin: 0 auto; background: #f8f9fa; border-radius: 12px; overflow: hidden; border: 1px solid #e0e0e0; }
                .header { background: linear-gradient(135deg, #7A6EB7 0%, #5B5C9A 100%); color: white; padding: 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
                .header p { margin: 5px 0 0 0; opacity: 0.9; }
                .content { padding: 20px; }
                .result-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
                .result-header { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
                .product-name { margin: 0 0 5px 0; color: #5B5C9A; font-size: 18px; font-weight: bold; }
                .username-text { margin: 0; color: #666; font-size: 14px; }
                .results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
                .results-column { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; }
                .section-title { margin: 0 0 15px 0; color: #7A6EB7; font-size: 16px; font-weight: bold; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px; }
                .info-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
                .info-label { color: #666; font-weight: 500; }
                .info-value { font-weight: bold; color: #333; text-align: right; }
                .calculation-breakdown { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px; }
                .final-result { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
                .final-result-label { font-size: 14px; opacity: 0.9; margin-bottom: 5px; }
                .final-result-amount { font-size: 28px; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>DOKUMEN REFUND</h1>
                    <p>Premiumisme Tools</p>
                </div>
                <div class="content">
                    <div class="result-card">
                        <div class="result-header">
                            <h3 class="product-name">Kalkulasi Refund Produk</h3>
                            <p class="username-text">${refundNumber}</p>
                        </div>
                        <div class="results-grid">
                            <div class="results-column">
                                <h4 class="section-title">Informasi Produk</h4>
                                <div class="info-item"><span class="info-label">Nama Produk</span><span class="info-value">${namaProduk}</span></div>
                                <div class="info-item"><span class="info-label">Customer</span><span class="info-value">${customer}</span></div>
                                <div class="info-item"><span class="info-label">Harga Produk</span><span class="info-value">${hargaProduk}</span></div>
                            </div>
                            <div class="results-column">
                                <h4 class="section-title">Informasi Waktu</h4>
                                <div class="info-item"><span class="info-label">Tanggal Order</span><span class="info-value">${tanggalOrder}</span></div>
                                <div class="info-item"><span class="info-label">Tanggal Kendala</span><span class="info-value">${tanggalKendala}</span></div>
                                <div class="info-item"><span class="info-label">Masa Aktif</span><span class="info-value">${masaAktif}</span></div>
                            </div>
                        </div>
                        <div class="calculation-breakdown">
                            <h4 class="section-title">Kalkulasi Penggunaan</h4>
                            <div class="info-item"><span class="info-label">Hari Digunakan</span><span class="info-value">${hariDigunakan}</span></div>
                            <div class="info-item"><span class="info-label">Biaya Penggunaan</span><span class="info-value">${biayaPenggunaan}</span></div>
                        </div>
                        <div class="final-result">
                            <div class="final-result-label">TOTAL REFUND</div>
                            <div class="final-result-amount">${totalRefund}</div>
                        </div>
                    </div>
                    <div class="footer">
                        <p>Dokumen ini dibuat secara otomatis oleh Premiumisme Tools</p>
                        <p>Tanggal: ${currentDate}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;

    // Generate PDF dari konten HTML yang sudah dibuat
    html2pdf().set(opt).from(htmlContentForPdf).save().then(() => {
        if (typeof showToast === 'function') {
            showToast('PDF berhasil diunduh!');
        } else {
            alert('PDF berhasil diunduh!');
        }
    }).catch(error => {
        console.error('Error saat membuat PDF dengan html2pdf:', error);
        
        // Fallback: try alternative method
        try {
            console.log('Trying alternative PDF generation method...');
            
            // Create a temporary element to render the content
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlContentForPdf;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            document.body.appendChild(tempDiv);
            
            // Try to generate PDF from the temporary element
            html2pdf().set({
                ...opt,
                html2canvas: { 
                    scale: 1.5, 
                    useCORS: true, 
                    letterRendering: true,
                    allowTaint: true,
                    foreignObjectRendering: true
                }
            }).from(tempDiv).save().then(() => {
                if (typeof showToast === 'function') {
                    showToast('PDF berhasil diunduh!');
                } else {
                    alert('PDF berhasil diunduh!');
                }
            }).catch(fallbackError => {
                console.error('Fallback PDF generation also failed:', fallbackError);
                alert('Gagal membuat PDF. Silakan coba refresh halaman dan coba lagi.');
            }).finally(() => {
                document.body.removeChild(tempDiv);
            });
            
        } catch (fallbackError) {
            console.error('Fallback method failed:', fallbackError);
            alert('Gagal membuat PDF. Silakan coba refresh halaman dan coba lagi.');
        }
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>

<?php include '../includes/footer.php'; ?>
