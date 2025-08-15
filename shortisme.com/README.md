# Shortisme.com - Shortlink Service

Folder ini berisi semua file yang diperlukan untuk menjalankan domain `shortisme.com` sebagai layanan shortlink.

## File yang Ada

- `index.php` - Landing page dengan design yang sama dengan premiumisme.co
- `redirect.php` - Handle redirect shortlink (shortisme.com/XXXXXX)
- `api.php` - API endpoint untuk cross-domain requests
- `stats.php` - Halaman statistik shortlink (shortisme.com/XXXXXX/stats)
- `nginx.conf` - Konfigurasi nginx untuk shortisme.com
- `test-setup.php` - File untuk testing setup
- `setup.sh` - Script setup otomatis

## Design & Assets

- Menggunakan CSS dari `https://premiumisme.co/tools/assets/css/style.css`
- Logo dari `https://premiumisme.co/tools/logo.svg`
- Design yang konsisten dengan premiumisme.co

## Cara Deploy

### 1. Upload ke Server
```bash
# Upload folder shortisme.com ke root domain
scp -r shortisme.com/* user@your-server:/var/www/shortisme.com/
```

### 2. Run Setup Script (Recommended)
```bash
cd /var/www/shortisme.com
sudo chmod +x setup.sh
sudo ./setup.sh
```

### 3. Manual Setup (Alternative)
```bash
# Set permissions
sudo chown -R www-data:www-data /var/www/shortisme.com
sudo chmod -R 755 /var/www/shortisme.com

# Setup nginx
sudo cp nginx.conf /etc/nginx/sites-available/shortisme.com
sudo ln -s /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Test Setup
```bash
# Test setup
curl https://shortisme.com/test-setup.php
```

## URL Examples

- **Landing Page**: `https://shortisme.com/`
- **Shortlink**: `https://shortisme.com/AbC123`
- **Stats**: `https://shortisme.com/AbC123/stats`
- **API**: `https://shortisme.com/api.php`

## Database

File `shortlinks.json` akan dibuat otomatis saat pertama kali digunakan.

## Integration

Domain ini bekerja bersama dengan `premiumisme.co/tools` untuk membuat shortlink.

## Features

- ✅ Design konsisten dengan premiumisme.co
- ✅ Responsive design
- ✅ Cross-domain API communication
- ✅ Real-time statistics
- ✅ Mobile-friendly navigation
