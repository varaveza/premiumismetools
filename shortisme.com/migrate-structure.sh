#!/bin/bash

# Script untuk migrasi struktur folder shortisme.com
# Menyimpan config.php di luar public_html untuk keamanan

echo "🚀 Migrasi Struktur Folder Shortisme.com"
echo "========================================"

# Buat struktur folder baru
echo "📁 Membuat struktur folder..."
mkdir -p /var/www/shortisme.com/config
mkdir -p /var/www/shortisme.com/public_html
mkdir -p /var/www/shortisme.com/logs

# Pindahkan file web ke public_html
echo "📂 Memindahkan file web ke public_html..."
mv /var/www/shortisme.com/index.php /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/api-optimized.php /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/redirect-optimized.php /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/stats.php /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/setup-database.php /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/nginx.conf /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/README.md /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/setup.sh /var/www/shortisme.com/public_html/ 2>/dev/null || true
mv /var/www/shortisme.com/database.sql /var/www/shortisme.com/public_html/ 2>/dev/null || true

# Pindahkan config.php ke folder config (jika ada)
if [ -f "/var/www/shortisme.com/config.php" ]; then
    echo "🔧 Memindahkan config.php ke folder config..."
    mv /var/www/shortisme.com/config.php /var/www/shortisme.com/config/
fi

# Set permissions
echo "🔐 Mengatur permissions..."
chown -R www-data:www-data /var/www/shortisme.com
chmod -R 755 /var/www/shortisme.com
chmod -R 600 /var/www/shortisme.com/config
chmod -R 600 /var/www/shortisme.com/logs

# Buat file log kosong
echo "📝 Membuat file log..."
touch /var/www/shortisme.com/logs/db_error.log
touch /var/www/shortisme.com/logs/access.log
chown www-data:www-data /var/www/shortisme.com/logs/*.log
chmod 644 /var/www/shortisme.com/logs/*.log

echo ""
echo "✅ Migrasi selesai!"
echo ""
echo "📋 Struktur folder baru:"
echo "shortisme.com/"
echo "├── config/           # Konfigurasi (tidak bisa diakses web)"
echo "│   ├── config.php"
echo "│   └── .htaccess"
echo "├── public_html/      # File yang bisa diakses web"
echo "│   ├── index.php"
echo "│   ├── api-optimized.php"
echo "│   ├── redirect-optimized.php"
echo "│   ├── stats.php"
echo "│   └── ..."
echo "└── logs/            # Log files (tidak bisa diakses web)"
echo "    ├── db_error.log"
echo "    └── access.log"
echo ""
echo "🔧 Langkah selanjutnya:"
echo "1. Update config.php dengan kredensial database yang benar"
echo "2. Test koneksi database: https://shortisme.com/setup-database.php?setup=1"
echo "3. Restart nginx: sudo systemctl reload nginx"
echo ""
