# ✅ Deployment Checklist

## 🚀 Pre-Deployment

### Repository Setup
- [ ] Create new GitHub repository
- [ ] Push code to repository
- [ ] Set up branch protection (optional)
- [ ] Configure .gitignore

### Domain Configuration
- [ ] Point premiumisme.co to server IP
- [ ] Point shortisme.com to server IP
- [ ] Wait for DNS propagation (24-48 hours)

## 🖥️ Server Setup

### System Requirements
- [ ] Ubuntu 20.04+ / CentOS 8+
- [ ] Nginx installed
- [ ] PHP 8.1+ installed
- [ ] SSL certificates ready

### File Permissions
- [ ] Create web directories
- [ ] Set proper ownership (www-data)
- [ ] Set proper permissions (755 for dirs, 644 for files)

## 📁 File Deployment

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/premiumisme-tools.git
cd premiumisme-tools
```

### 2. Deploy Tools Interface
```bash
# Copy to premiumisme.co/tools
sudo cp -r * /var/www/html/tools/
sudo chown -R www-data:www-data /var/www/html/tools
sudo chmod -R 755 /var/www/html/tools
```

### 3. Deploy Shortlink Domain
```bash
# Copy shortisme.com files
sudo cp -r shortisme.com/* /var/www/shortisme.com/
sudo chown -R www-data:www-data /var/www/shortisme.com
sudo chmod -R 755 /var/www/shortisme.com
```

## ⚙️ Nginx Configuration

### 1. Premiumisme.co Config
```bash
sudo tee /etc/nginx/sites-available/premiumisme.co > /dev/null << 'EOF'
server {
    listen 80;
    server_name premiumisme.co www.premiumisme.co;
    root /var/www/html;
    index index.php index.html;

    location /tools {
        try_files $uri $uri/ /tools/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}
EOF
```

### 2. Shortisme.com Config
```bash
sudo tee /etc/nginx/sites-available/shortisme.com > /dev/null << 'EOF'
server {
    listen 80;
    server_name shortisme.com www.shortisme.com;
    root /var/www/shortisme.com;
    index index.php index.html;

    location ~ ^/([a-zA-Z0-9]{6})$ {
        try_files $uri $uri/ /redirect.php?slug=$1;
    }

    location ~ ^/([a-zA-Z0-9]{6})/stats$ {
        try_files $uri $uri/ /stats.php?slug=$1;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /shortlinks\.json$ {
        deny all;
        return 404;
    }

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF
```

### 3. Enable Sites
```bash
sudo ln -sf /etc/nginx/sites-available/premiumisme.co /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/shortisme.com /etc/nginx/sites-enabled/
```

## 🔒 SSL Setup

### Install Certbot
```bash
sudo apt install certbot python3-certbot-nginx
```

### Get SSL Certificates
```bash
sudo certbot --nginx -d premiumisme.co -d www.premiumisme.co
sudo certbot --nginx -d shortisme.com -d www.shortisme.com
```

## 🧪 Testing

### 1. Test Nginx Configuration
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 2. Test URLs
- [ ] `https://premiumisme.co/tools/` - Main tools page
- [ ] `https://premiumisme.co/tools/shortlink/` - Shortlink creator
- [ ] `https://shortisme.com/` - Landing page
- [ ] `https://shortisme.com/test-setup.php` - Setup test

### 3. Test Functionality
- [ ] Create shortlink from premiumisme.co
- [ ] Access shortlink at shortisme.com/XXXXXX
- [ ] View statistics at shortisme.com/XXXXXX/stats
- [ ] Test mobile navigation
- [ ] Test responsive design

## 🔧 Post-Deployment

### 1. Security
- [ ] Check file permissions
- [ ] Verify SSL certificates
- [ ] Test CORS headers
- [ ] Validate input sanitization

### 2. Performance
- [ ] Enable gzip compression
- [ ] Set up caching headers
- [ ] Optimize images
- [ ] Monitor error logs

### 3. Monitoring
- [ ] Set up log rotation
- [ ] Configure monitoring (optional)
- [ ] Set up backups
- [ ] Monitor disk space

## 📋 Final Checklist

### ✅ Technical
- [ ] All URLs accessible
- [ ] SSL certificates working
- [ ] Cross-domain API working
- [ ] Database file writable
- [ ] Error logs clean

### ✅ User Experience
- [ ] Design consistent across domains
- [ ] Mobile navigation working
- [ ] Responsive design working
- [ ] Loading times acceptable
- [ ] No console errors

### ✅ Security
- [ ] No sensitive data exposed
- [ ] Input validation working
- [ ] CORS headers correct
- [ ] File access restricted
- [ ] Security headers set

## 🆘 Troubleshooting

### Common Issues
1. **CORS Error**: Check api.php headers
2. **404 on Shortlink**: Verify nginx config
3. **Permission Denied**: Check file ownership
4. **SSL Error**: Verify certificate installation

### Logs to Check
```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/php8.1-fpm.log
```

---

**Deployment Status**: ✅ Ready for Production
