# 🔒 Struktur Folder Aman - Shortisme.com

## 📁 Struktur Folder Baru

```
shortisme.com/
├── config/                    # 🔒 DI LUAR PUBLIC_HTML (AMAN)
│   ├── config.php            # Database configuration
│   └── .htaccess             # Protect config folder
├── public_html/              # 🌐 WEB ACCESSIBLE
│   ├── index.php             # Landing page
│   ├── api-optimized.php     # API endpoint
│   ├── redirect-optimized.php # Redirect handler
│   ├── stats.php             # Statistics page
│   ├── setup-database.php    # Database setup
│   ├── nginx.conf            # Nginx configuration
│   ├── setup.sh              # Setup script
│   ├── database.sql          # Database schema
│   └── README.md             # Documentation
└── logs/                     # 🔒 DI LUAR PUBLIC_HTML (AMAN)
    ├── db_error.log          # Database error logs
    ├── access.log            # Access logs
    └── .htaccess             # Protect logs folder
```

## 🚀 Cara Setup

### 1. Jalankan Script Migrasi
```bash
cd /var/www/shortisme.com
chmod +x migrate-structure.sh
sudo ./migrate-structure.sh
```

### 2. Update Database Configuration
Edit file `/var/www/shortisme.com/config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'premiumisme_db'); // Database yang sama dengan premiumisme.co
define('DB_USER', 'premiumisme_user');
define('DB_PASS', 'your_secure_password'); // Ganti dengan password yang aman
```

### 3. Setup Database
```bash
# Akses via browser
https://shortisme.com/setup-database.php?setup=1

# Atau via command line
cd /var/www/shortisme.com/public_html
php setup-database.php
```

### 4. Update Nginx Configuration
```bash
# Copy nginx config
sudo cp /var/www/shortisme.com/public_html/nginx.conf /etc/nginx/sites-available/shortisme.com

# Test dan reload
sudo nginx -t
sudo systemctl reload nginx
```

## 🔒 Keamanan

### ✅ Fitur Keamanan yang Ditambahkan:

1. **Config di Luar Public HTML**
   - File konfigurasi database tidak bisa diakses dari web
   - Dilindungi dengan .htaccess

2. **Rate Limiting**
   - API: 1000 request per jam
   - Redirect: 100 request per menit
   - Stats: 50 request per menit

3. **Input Validation**
   - URL validation dengan filter berbahaya
   - Slug validation (6 karakter alfanumerik)
   - Input sanitization

4. **CORS Protection**
   - Hanya domain yang diizinkan
   - Validasi origin

5. **Error Handling**
   - Error tidak ditampilkan ke user
   - Log error ke file terpisah

6. **Prepared Statements**
   - Semua query menggunakan prepared statements
   - Mencegah SQL injection

### 🔧 File Keamanan:

- `config/.htaccess` - Melindungi folder config
- `logs/.htaccess` - Melindungi folder logs
- `config/config.php` - Fungsi keamanan tambahan

## 📊 Database

### Struktur Tabel:
```sql
-- Tabel utama shortlinks
CREATE TABLE shortlinks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(10) UNIQUE NOT NULL,
    original_url TEXT NOT NULL,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_created_at (created_at),
    INDEX idx_clicks (clicks)
);

-- Tabel analytics untuk tracking detail
CREATE TABLE link_analytics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    shortlink_id BIGINT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer TEXT,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shortlink_id) REFERENCES shortlinks(id) ON DELETE CASCADE,
    INDEX idx_shortlink_id (shortlink_id),
    INDEX idx_clicked_at (clicked_at)
);
```

## 🌐 Integration

### API Endpoints:
- `POST /api-optimized.php` - Create shortlink
- `GET /api-optimized.php?action=get&slug=XXX` - Get shortlink
- `GET /api-optimized.php?action=stats` - Get statistics

### Redirect:
- `GET /{slug}` - Redirect ke URL asli

### Stats:
- `GET /{slug}/stats` - Halaman statistik

## 🔧 Troubleshooting

### 1. Database Connection Error
```bash
# Cek log error
tail -f /var/www/shortisme.com/logs/db_error.log

# Test koneksi
php -r "require_once '/var/www/shortisme.com/config/config.php'; var_dump(getDBConnection());"
```

### 2. Permission Error
```bash
# Set permissions
sudo chown -R www-data:www-data /var/www/shortisme.com
sudo chmod -R 755 /var/www/shortisme.com
sudo chmod -R 600 /var/www/shortisme.com/config
sudo chmod -R 600 /var/www/shortisme.com/logs
```

### 3. Nginx Error
```bash
# Test nginx config
sudo nginx -t

# Check nginx error log
sudo tail -f /var/log/nginx/error.log
```

## 📞 Support

Jika ada masalah, cek:
1. File log di `/var/www/shortisme.com/logs/`
2. Nginx error log: `/var/log/nginx/error.log`
3. PHP error log: `/var/log/php/error.log`

---

**🔒 Keamanan adalah prioritas utama!**
