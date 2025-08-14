# 🛠️ Tools Collection - Premiumisme

Koleksi tools PHP yang berguna untuk berbagai keperluan. Semua tools menggunakan design system yang konsisten dan aman.

## 📦 Tools yang Tersedia

### 🔗 **Shortlink Generator**
- **File**: `shortlink/shortlink.php`
- **Fitur**: Buat link pendek yang bisa di-share
- **Format**: `domain.com/randomstring`
- **Stats**: `domain.com/randomstring/stats`
- **Setup**: Lihat `shortlink/README.md`

### 📧 **Email Generator**
- **File**: `generator-email/emailcreate.php`
- **Fitur**: Generate email acak dengan berbagai format
- **Opsi**: Random chars, numeric, alphabet, nama+numeric
- **Export**: Copy atau download hasil

### 💰 **Refund Calculator**
- **File**: `refund-calculator/refund-calculator.php`
- **Fitur**: Hitung refund berdasarkan masa penggunaan
- **Input**: Harga, tanggal pembelian, tanggal kendala
- **Output**: Jumlah refund yang tepat

### 🧹 **Remove Duplicate Emails**
- **File**: `remove-duplicate/remove-duplicate.php`
- **Fitur**: Hapus email duplikat dari list
- **Opsi**: Case sensitive, trim whitespace
- **Export**: Copy atau download hasil

### 📨 **Email Splitter**
- **File**: `split-mail/email-splitter.php`
- **Fitur**: Bagi list email menjadi beberapa grup
- **Upload**: Support file .txt
- **Export**: Download per grup atau semua (.zip)

## 🚀 Quick Start

### **Requirements:**
- PHP 7.4+
- Web Server (Apache/Nginx)
- mod_rewrite (untuk Apache)

### **Installation:**
```bash
# Clone repository
git clone https://github.com/varaveza/tools.git

# Upload ke web server
# Atau gunakan XAMPP/WAMP untuk local development
```

### **Setup Web Server:**

#### **Apache (.htaccess)**
- File `.htaccess` sudah disediakan di folder `shortlink/`
- Pastikan `mod_rewrite` aktif

#### **Nginx**
- Copy konfigurasi dari `shortlink/nginx.conf`
- Sesuaikan path dan domain

## 🛡️ Keamanan

### **Fitur Keamanan:**
- ✅ **XSS Protection** - Input sanitization
- ✅ **File Upload Security** - Validasi file type & size
- ✅ **URL Validation** - Cek protocol berbahaya
- ✅ **Security Headers** - X-Frame-Options, XSS-Protection
- ✅ **No SQL Injection** - Tidak menggunakan database

### **Validasi Input:**
- Email format validation
- URL protocol checking
- File type restriction (.txt only)
- Size limit (5MB max)

## 🎨 Design System

### **CSS Framework:**
- **Tailwind CSS** - Utility-first CSS
- **Custom Variables** - Konsisten color scheme
- **Glass Morphism** - Modern UI design
- **Responsive** - Mobile-friendly

### **Color Palette:**
```css
--very-peri: #5B5C9A
--dark-peri: #3E3F6A
--darker-peri: #2A2B4F
--accent: #7A6EB7
--light-peri: #898AC8
```

## 📁 Struktur Project

```
tools/
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
├── generator-email/
│   └── emailcreate.php        # Email generator
├── includes/
│   ├── header.php             # Common header
│   └── footer.php             # Common footer
├── refund-calculator/
│   └── refund-calculator.php  # Refund calculator
├── remove-duplicate/
│   └── remove-duplicate.php   # Duplicate remover
├── shortlink/
│   ├── .htaccess              # Apache config
│   ├── nginx.conf             # Nginx config
│   ├── shortlink.php          # Main app
│   ├── redirect.php           # Redirect handler
│   ├── api.php               # API handler
│   ├── stats.php             # Stats page
│   └── README.md             # Setup guide
├── split-mail/
│   └── email-splitter.php     # Email splitter
├── logo.svg                   # Brand logo
└── README.md                  # This file
```

## 🔧 Development

### **Local Development:**
```bash
# XAMPP
# Copy folder ke htdocs/
# Akses: http://localhost/tools/

# WAMP
# Copy folder ke www/
# Akses: http://localhost/tools/
```

### **Production Deployment:**
```bash
# Upload ke VPS
git clone https://github.com/varaveza/tools.git

# Setup web server
# Konfigurasi domain
# Set permissions
chmod 755 tools/
chmod 644 tools/*.php
```

## 📊 Features Overview

| Tool | Status | Security | Responsive | Export |
|------|--------|----------|------------|---------|
| Shortlink | ✅ Complete | ✅ Secure | ✅ Yes | ✅ Copy/Share |
| Email Generator | ✅ Complete | ✅ Secure | ✅ Yes | ✅ Copy/Download |
| Refund Calculator | ✅ Complete | ✅ Secure | ✅ Yes | ✅ Copy |
| Remove Duplicate | ✅ Complete | ✅ Secure | ✅ Yes | ✅ Copy/Download |
| Email Splitter | ✅ Complete | ✅ Secure | ✅ Yes | ✅ Download |

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📝 License

Made with ❤️ by Premiumisme

## 🔗 Links

- **Repository**: https://github.com/varaveza/tools
- **Issues**: https://github.com/varaveza/tools/issues
- **Wiki**: Setup guides di masing-masing folder

---

**Note**: Semua tools menggunakan client-side processing untuk keamanan maksimal. Tidak ada database yang diperlukan.
