#!/bin/bash

# Shortlink VPS Setup Script
# This script sets up the shortlink system on your VPS

echo "🚀 Setting up Shortlink System on VPS..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root (use sudo)"
    exit 1
fi

# Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
print_status "Installing required packages..."
apt install -y nginx php8.1-fpm php8.1-json php8.1-mbstring php8.1-curl php8.1-xml php8.1-zip unzip curl

# Create web directory if it doesn't exist
print_status "Setting up web directory..."
mkdir -p /var/www/html/tools
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Copy shortlink files to web directory
print_status "Copying shortlink files..."
cp -r . /var/www/html/tools/shortlink/
chown -R www-data:www-data /var/www/html/tools/shortlink
chmod -R 755 /var/www/html/tools/shortlink

# Create shortlinks.json file with proper permissions
print_status "Creating database file..."
touch /var/www/html/tools/shortlink/shortlinks.json
chown www-data:www-data /var/www/html/tools/shortlink/shortlinks.json
chmod 644 /var/www/html/tools/shortlink/shortlinks.json

# Configure nginx
print_status "Configuring nginx..."
cp nginx.conf /etc/nginx/sites-available/shortisme.com

# Enable the site
ln -sf /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-enabled/

# Remove default nginx site
rm -f /etc/nginx/sites-enabled/default

# Test nginx configuration
print_status "Testing nginx configuration..."
nginx -t

if [ $? -eq 0 ]; then
    print_status "Nginx configuration is valid"
else
    print_error "Nginx configuration has errors. Please check the configuration."
    exit 1
fi

# Restart services
print_status "Restarting services..."
systemctl restart nginx
systemctl restart php8.1-fpm

# Enable services to start on boot
systemctl enable nginx
systemctl enable php8.1-fpm

# Configure firewall (if ufw is available)
if command -v ufw &> /dev/null; then
    print_status "Configuring firewall..."
    ufw allow 'Nginx Full'
    ufw allow ssh
    ufw --force enable
fi

# Set up SSL with Let's Encrypt (optional)
print_status "Setting up SSL certificate..."
if command -v certbot &> /dev/null; then
    certbot --nginx -d shortisme.com -d www.shortisme.com --non-interactive --agree-tos --email admin@shortisme.com
else
    print_warning "Certbot not found. Installing Let's Encrypt certbot..."
    apt install -y certbot python3-certbot-nginx
    certbot --nginx -d shortisme.com -d www.shortisme.com --non-interactive --agree-tos --email admin@shortisme.com
fi

# Create a simple test script
print_status "Creating test script..."
cat > /var/www/html/tools/shortlink/test.php << 'EOF'
<?php
echo "Shortlink system is working!";
echo "<br>PHP version: " . phpversion();
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Document root: " . $_SERVER['DOCUMENT_ROOT'];
?>
EOF

# Final status check
print_status "Performing final checks..."

# Check if nginx is running
if systemctl is-active --quiet nginx; then
    print_status "✅ Nginx is running"
else
    print_error "❌ Nginx is not running"
fi

# Check if PHP-FPM is running
if systemctl is-active --quiet php8.1-fpm; then
    print_status "✅ PHP-FPM is running"
else
    print_error "❌ PHP-FPM is not running"
fi

# Check file permissions
if [ -r "/var/www/html/tools/shortlink/shortlinks.json" ]; then
    print_status "✅ Database file is accessible"
else
    print_error "❌ Database file is not accessible"
fi

echo ""
echo "🎉 Setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Visit https://shortisme.com to test the shortlink system"
echo "2. Create your first shortlink"
echo "3. Test the redirect functionality"
echo ""
echo "🔧 Useful commands:"
echo "- Check nginx status: systemctl status nginx"
echo "- Check PHP-FPM status: systemctl status php8.1-fpm"
echo "- View nginx logs: tail -f /var/log/nginx/error.log"
echo "- View nginx access logs: tail -f /var/log/nginx/access.log"
echo ""
echo "📁 Files location:"
echo "- Web files: /var/www/html/tools/shortlink/"
echo "- Nginx config: /etc/nginx/sites-available/shortisme.com"
echo "- Database: /var/www/html/tools/shortlink/shortlinks.json"
echo ""
print_status "Setup script completed successfully!"
