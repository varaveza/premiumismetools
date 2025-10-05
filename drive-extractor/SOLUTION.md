# Drive Extractor - Solusi ERR_BLOCKED_BY_CLIENT

## 🚨 Masalah
Error `net::ERR_BLOCKED_BY_CLIENT` terjadi karena:
1. API masih mencoba mengakses `localhost:1203` di production
2. Node.js API tidak berjalan di server production
3. Port 1203 tidak terbuka di firewall

## ✅ Solusi yang Diterapkan

### 1. **PHP Proxy (api-proxy.php)**
- Membuat proxy PHP yang berkomunikasi dengan Node.js API
- Frontend di production akan menggunakan proxy ini
- Proxy berjalan di same-origin (tidak ada CORS issue)

### 2. **Smart API URL Detection**
```javascript
// Otomatis detect environment
const isLocalhost = window.location.hostname === 'localhost' || 
                   window.location.hostname === '127.0.0.1' ||
                   window.location.hostname.includes('localhost');

const endpoint = isLocalhost 
    ? `${API_BASE_URL}/api/get-drive-content?fileId=${fileId}`  // Direct to Node.js
    : `${API_BASE_URL}/tools/drive-extractor/api-proxy.php?fileId=${fileId}`;  // PHP Proxy
```

### 3. **Scripts untuk Menjalankan API**
- `start-api.sh` - Menjalankan Node.js API
- `stop-api.sh` - Menghentikan Node.js API

## 🚀 Deployment Steps

### **Di Server Production:**

#### 1. Upload Files
```bash
# Upload semua file ke server
# Pastikan file-file ini ada:
# - api.js
# - api-proxy.php
# - index.php (updated)
# - package.json
# - start-api.sh
# - stop-api.sh
```

#### 2. Install Dependencies
```bash
cd /path/to/drive-extractor
npm install
```

#### 3. Start API Server
```bash
# Make scripts executable
chmod +x *.sh

# Start API server
./start-api.sh
```

#### 4. Test API
```bash
# Test direct API
curl "http://localhost:1203/api/get-drive-content?fileId=test"

# Test PHP proxy
curl "https://premiumisme.co/tools/drive-extractor/api-proxy.php?fileId=test"
```

## 🔧 Troubleshooting

### Check API Status
```bash
# Check if API is running
ps aux | grep api.js

# Check port 1203
netstat -tlnp | grep 1203

# Check logs
tail -f logs/combined.log
```

### Restart API
```bash
./stop-api.sh
./start-api.sh
```

### Debug Frontend
1. Buka browser console
2. Lihat log:
   - Hostname
   - API Base URL
   - Is Localhost
   - Fetching from: [endpoint]

## 📋 Flow Diagram

```
Development:
Frontend → localhost:1203/api/get-drive-content → Node.js API

Production:
Frontend → premiumisme.co/tools/drive-extractor/api-proxy.php → localhost:1203/api/get-drive-content → Node.js API
```

## ✅ Verification

Setelah deployment:
1. Buka https://premiumisme.co/tools/drive-extractor/
2. Buka browser console (F12)
3. Lihat log debug info
4. Masukkan Google Drive URL
5. Klik "Extract Files"
6. Seharusnya tidak ada error `ERR_BLOCKED_BY_CLIENT`

## 🔒 Security Notes
- API hanya bisa diakses dari localhost (internal)
- PHP proxy memvalidasi input
- Rate limiting tetap aktif
- Logs tersimpan untuk monitoring


