# Shortlink Setup Guide

## 🚀 Setup untuk Apache (.htaccess)

1. **Upload semua file** ke folder shortlink di web server
2. **Pastikan mod_rewrite aktif** di Apache
3. **File .htaccess sudah otomatis** dikonfigurasi
4. **Shortlink akan bekerja** dengan format: `domain.com/randomstring`

## 🚀 Setup untuk Nginx

1. **Upload semua file** ke folder shortlink di web server
2. **Copy isi nginx.conf** ke konfigurasi server block Anda
3. **Update path** sesuai struktur folder Anda:
   ```nginx
   root /path/to/your/shortlink;
   server_name yourdomain.com;
   ```
4. **Restart Nginx**:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

## 📁 Struktur File

```
shortlink/
├── .htaccess          # Apache config
├── nginx.conf         # Nginx config
├── shortlink.php      # Main application
├── redirect.php       # Redirect handler
├── api.php           # API handler
├── stats.php         # Stats page (NEW!)
├── shortlinks.json   # Database (auto-created)
└── README.md         # This file
```

## 🔧 Cara Kerja

1. **Buat shortlink**: `domain.com/shortlink/shortlink.php`
2. **Hasil**: `domain.com/ks89sdd`
3. **Klik link**: Otomatis redirect ke URL asli
4. **Lihat stats**: `domain.com/ks89sdd/stats`
5. **Tracking**: Click count tersimpan di `shortlinks.json`

## 📊 Fitur Stats (Baru!)

### **Akses Stats:**
```
domain.com/ks89sdd/stats
```

### **Yang Ditampilkan:**
- ✅ **Total Klik** dengan format angka
- ✅ **Informasi Link** (shortlink, URL asli, tanggal dibuat)
- ✅ **Statistik Klik** (klik/hari, hari aktif)
- ✅ **Indikator Performa** (status performa, status link, hari online)
- ✅ **Auto-refresh** setiap 30 detik
- ✅ **Share Stats** ke social media
- ✅ **Copy Link** dengan satu klik

### **Contoh Tampilan:**
```
📊 Statistik Shortlink

🔗 domain.com/ks89sdd
👆 1,234 klik
📈 Lihat detail: domain.com/ks89sdd/stats
```

## 🛡️ Keamanan

- ✅ Validasi URL berbahaya
- ✅ Block akses langsung ke JSON file
- ✅ Security headers
- ✅ Sanitasi input
- ✅ XSS protection

## 🎯 Contoh Penggunaan

```
Input: https://google.com
Output: domain.com/ks89sdd
Share: domain.com/ks89sdd
Stats: domain.com/ks89sdd/stats
Result: Redirect ke https://google.com
```

## ⚠️ Troubleshooting

### Apache tidak bekerja:
- Pastikan `mod_rewrite` aktif
- Cek error log Apache

### Nginx tidak bekerja:
- Cek syntax: `nginx -t`
- Restart Nginx
- Cek error log Nginx

### File tidak bisa diakses:
- Set permission: `chmod 755` untuk folder
- Set permission: `chmod 644` untuk file

### Stats tidak muncul:
- Pastikan file `stats.php` ada
- Cek permission file JSON
- Pastikan URL rewrite bekerja
