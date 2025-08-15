#!/bin/bash

echo "=== Setup Shortisme.com ==="
echo "Shortlink Service Setup"
echo ""

# Create directory
echo "Creating directory..."
sudo mkdir -p /var/www/shortisme.com

# Copy files
echo "Copying files..."
sudo cp -r . /var/www/shortisme.com/
sudo chown -R www-data:www-data /var/www/shortisme.com
sudo chmod -R 755 /var/www/shortisme.com

# Setup nginx configuration
echo "Setting up nginx configuration..."

# Backup existing config
sudo cp /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-available/shortisme.com.backup 2>/dev/null || true

# Create nginx config
sudo tee /etc/nginx/sites-available/shortisme.com > /dev/null << 'EOF'
server {
    listen 80;
    server_name shortisme.com www.shortisme.com;
    root /var/www/shortisme.com;
    index index.php index.html;

    # Handle shortlink redirects (format: domain.com/randomstring)
    location ~ ^/([a-zA-Z0-9]{6})$ {
        try_files $uri $uri/ /redirect.php?slug=$1;
    }

    # Handle stats pages (format: domain.com/randomstring/stats)
    location ~ ^/([a-zA-Z0-9]{6})/stats$ {
        try_files $uri $uri/ /stats.php?slug=$1;
    }

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Prevent direct access to JSON file
    location ~ /shortlinks\.json$ {
        deny all;
        return 404;
    }

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";

    # Handle static files
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
echo "Enabling nginx site..."
sudo ln -sf /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-enabled/

# Test nginx configuration
echo "Testing nginx configuration..."
sudo nginx -t

if [ $? -eq 0 ]; then
    echo "Nginx configuration is valid!"
    echo "Reloading nginx..."
    sudo systemctl reload nginx
    echo ""
    echo "=== Setup Complete ==="
    echo "Shortlink Domain: https://shortisme.com"
    echo ""
    echo "Make sure to:"
    echo "1. Point shortisme.com DNS to this server"
    echo "2. Configure SSL certificate (optional)"
    echo ""
    echo "Test the setup:"
    echo "curl https://shortisme.com/test-setup.php"
else
    echo "Nginx configuration test failed!"
    exit 1
fi
