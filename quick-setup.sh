#!/bin/bash

# 🚀 Quick Setup Script - Multi-Domain Shortlink
# Setup time: ~2 minutes

echo "🚀 Starting Quick Setup..."

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Please run as root (use sudo)${NC}"
    exit 1
fi

echo -e "${YELLOW}📁 Creating directories...${NC}"

# Create directories
mkdir -p /var/www/html/tools
mkdir -p /var/www/shortisme.com

echo -e "${YELLOW}📂 Copying files...${NC}"

# Copy tools interface
cp -r * /var/www/html/tools/
rm -rf /var/www/html/tools/shortisme.com
rm -rf /var/www/html/tools/nginx-configs
rm -f /var/www/html/tools/quick-setup.sh

# Copy shortisme.com files
cp -r shortisme.com/* /var/www/shortisme.com/

echo -e "${YELLOW}🔐 Setting permissions...${NC}"

# Set permissions
chown -R www-data:www-data /var/www/html/tools
chown -R www-data:www-data /var/www/shortisme.com
chmod -R 755 /var/www/html/tools
chmod -R 755 /var/www/shortisme.com

echo -e "${YELLOW}⚙️ Setting up Nginx configs...${NC}"

# Copy nginx configs
cp nginx-configs/premiumisme.co /etc/nginx/sites-available/
cp nginx-configs/shortisme.com /etc/nginx/sites-available/

# Enable sites
ln -sf /etc/nginx/sites-available/premiumisme.co /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-enabled/

echo -e "${YELLOW}🧪 Testing Nginx configuration...${NC}"

# Test nginx config
if nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration error${NC}"
    exit 1
fi

echo -e "${GREEN}🎉 Setup completed successfully!${NC}"
echo ""
echo -e "${YELLOW}📋 Next steps:${NC}"
echo "1. Point domains to server IP:"
echo "   - premiumisme.co → $(curl -s ifconfig.me)"
echo "   - shortisme.com → $(curl -s ifconfig.me)"
echo ""
echo "2. Wait for DNS propagation (24-48 hours)"
echo ""
echo "3. Test URLs:"
echo "   - https://premiumisme.co/tools/"
echo "   - https://shortisme.com/"
echo ""
echo "4. Optional: Setup SSL with Certbot"
echo "   sudo apt install certbot python3-certbot-nginx"
echo "   sudo certbot --nginx -d premiumisme.co -d www.premiumisme.co"
echo "   sudo certbot --nginx -d shortisme.com -d www.shortisme.com"
echo ""
echo -e "${GREEN}✨ Your multi-domain shortlink system is ready!${NC}"
