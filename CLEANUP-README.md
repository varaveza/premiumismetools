# 🧹 File Cleanup System

## 📋 Overview

Sistem auto-cleanup untuk menghapus file upload yang sudah selesai dan file lama lebih dari 30 hari.

## 🔧 File yang Dibuat

### **1. cleanup-all.php** ⭐ **MAIN FILE**
- **All-in-One** cleanup script
- Menggabungkan semua fungsi cleanup
- Hapus file upload, JSON, log, backup, temp
- Statistik detail per jenis file
- Bisa dijalankan via web browser atau CLI

### **2. auto-cleanup.sh**
- Script bash untuk auto-cleanup
- Bisa dijalankan via cron job
- Memanggil `cleanup-all.php`

## 📁 Direktori yang Di-Cleanup

```
tools/
├── uploads/              # File upload umum
├── temp/                 # File temporary
├── tmp/                  # File temporary
├── split-mail/uploads/   # Upload split-mail
├── remove-duplicate/uploads/  # Upload remove-duplicate
├── generator-email/uploads/   # Upload generator-email
├── refund-calculator/uploads/ # Upload refund-calculator
└── shortlink/            # Database JSON shortlink
```

## ⏰ Auto-Cleanup Rules

### **File yang Dihapus:**
- **File upload** lebih dari 30 hari
- **Log file** lebih dari 10MB
- **Backup file** lebih dari 7 hari
- **Temp file** lebih dari 1 hari
- **Direktori kosong** setelah cleanup

### **File Extension yang Di-Cleanup:**
- `.txt` - Text files
- `.csv` - CSV files
- `.xlsx`, `.xls` - Excel files
- `.zip`, `.rar`, `.7z` - Archive files
- `.json` - JSON files (termasuk database shortlink)
- `.log` - Log files
- `.backup`, `.bak` - Backup files
- `.tmp` - Temporary files

## 🚀 Cara Penggunaan

### **Manual Cleanup:**
```bash
# Via web browser (RECOMMENDED)
http://localhost/tools/cleanup-all.php

# Via command line
php cleanup-all.php
```

### **Auto Cleanup (Cron Job):**
```bash
# Edit crontab
crontab -e

# Tambahkan baris ini (jalan setiap hari jam 2 pagi)
0 2 * * * /path/to/tools/auto-cleanup.sh

# Atau jalankan manual
chmod +x auto-cleanup.sh
./auto-cleanup.sh
```

## 📊 Monitoring

### **Log File:**
- `cleanup-all.log` - Log semua aktivitas cleanup
- Format: `[2024-01-15 02:00:00] - All-in-One cleanup started`

### **Check Log:**
```bash
# Lihat log terbaru
tail -f cleanup-all.log

# Cari file yang dihapus
grep "Deleted" cleanup-all.log
```

## ⚠️ Important Notes

### **Keamanan:**
- Script hanya menghapus file dengan extension tertentu
- Tidak menghapus file PHP atau file sistem
- Backup log sebelum cleanup

### **Performance:**
- Cleanup berjalan di background
- Tidak mempengaruhi performa website
- Minimal disk I/O

### **Recovery:**
- File yang dihapus TIDAK bisa dikembalikan
- Pastikan backup penting sebelum cleanup
- Check log untuk detail file yang dihapus

## 🔄 Integration

### **Dengan Tools yang Ada:**
- **Split Mail**: Client-side processing (tidak ada upload)
- **Remove Duplicate**: Client-side processing (tidak ada upload)
- **Generator Email**: Tidak ada upload file
- **Refund Calculator**: Tidak ada upload file

### **Untuk Tool Baru:**
Jika ada tool baru dengan upload file, tambahkan direktori ke `$directories` di `cleanup-all.php`:

```php
$directories = [
    // ... existing dirs
    'new-tool' => __DIR__ . '/new-tool/uploads/',
];
```

## 📈 Statistics

### **Cleanup Report:**
- Jumlah file yang dihapus
- Total ukuran yang dibebaskan
- Waktu eksekusi
- Error log (jika ada)

---

**🎉 Sistem cleanup siap untuk menjaga disk space tetap bersih!**
