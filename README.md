# 🛠️ Premiumisme Tools Collection

Koleksi tools yang berguna untuk berbagai keperluan digital.

## 🎯 Tools yang Tersedia

### 🔗 **Shortlink Service** (Multi-Domain)
- **Interface**: `premiumisme.co/tools/shortlink/`
- **Domain**: `shortisme.com/XXXXXX`
- **Stats**: `shortisme.com/XXXXXX/stats`

### 📧 **Generator Email**
- Generate email addresses dengan berbagai pola
- Custom domain support
- Bulk generation

### 💰 **Refund Calculator**
- Kalkulator refund untuk berbagai platform
- Support multiple currencies
- Detailed breakdown

### ✂️ **Email Splitter**
- Split email list menjadi chunks
- Custom delimiter support
- Export options

### 🗑️ **Remove Duplicate**
- Remove duplicate entries from lists
- Support various formats
- Clean and organize data

## 🚀 Quick Setup

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/premiumisme-tools.git
cd premiumisme-tools
```

### 2. Setup Tools
```bash
# Copy to web server
sudo cp -r * /var/www/html/tools/
sudo chown -R www-data:www-data /var/www/html/tools
sudo chmod -R 755 /var/www/html/tools
```

### 3. Setup Shortlink Domain
```bash
# Setup shortisme.com
cd shortisme.com
sudo chmod +x setup.sh
sudo ./setup.sh
```

## 📁 Project Structure

```
tools/
├── shortlink/                    # Shortlink creator interface
│   ├── index.php                # Form pembuat shortlink
│   └── config.php               # Domain configuration
│
├── shortisme.com/               # Shortlink domain files
│   ├── index.php                # Landing page
│   ├── redirect.php             # Handle redirects
│   ├── api.php                  # API endpoint
│   ├── stats.php                # Statistics page
│   ├── nginx.conf               # Nginx configuration
│   ├── setup.sh                 # Setup script
│   └── README.md                # Documentation
│
├── generator-email/             # Email generator tool
├── refund-calculator/           # Refund calculator tool
├── split-mail/                  # Email splitter tool
├── remove-duplicate/            # Duplicate remover tool
├── assets/                      # CSS, JS, images
├── includes/                    # Shared PHP includes
└── SETUP-COMPLETE.md            # Complete setup guide
```

## 🌐 Domain Configuration

### Premiumisme.co
- **Purpose**: Tools interface
- **Path**: `/var/www/html/tools/`
- **URL**: `https://premiumisme.co/tools/`

### Shortisme.com
- **Purpose**: Shortlink redirects
- **Path**: `/var/www/shortisme.com/`
- **URL**: `https://shortisme.com/`

## 🔧 Features

- ✅ **Responsive Design** - Works on all devices
- ✅ **Cross-Domain API** - Seamless communication
- ✅ **Real-time Statistics** - Live click tracking
- ✅ **Security Headers** - XSS protection, CORS
- ✅ **Mobile Navigation** - Touch-friendly interface
- ✅ **Auto-cleanup** - Automatic maintenance

## 📋 Requirements

- **Web Server**: Nginx/Apache
- **PHP**: 7.4+ (8.1 recommended)
- **SSL**: Let's Encrypt (recommended)
- **Domains**: premiumisme.co, shortisme.com

## 🔒 Security

- CORS headers for cross-domain requests
- Input validation and sanitization
- Prevention of direct file access
- Security headers (XSS, CSRF protection)

## 📞 Support

- **Documentation**: `SETUP-COMPLETE.md`
- **Shortlink Setup**: `shortisme.com/README.md`
- **Issues**: GitHub Issues

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**Made with ❤️ by Premiumisme Team**
