# Surfshark Creator

Tool untuk membuat akun Surfshark secara otomatis dengan dukungan proxy dan rate limiting.

## Fitur

- ✅ Backend Node.js dengan API endpoints
- ✅ Frontend PHP dengan form input
- ✅ Rate limiting per IP dan global
- ✅ Dukungan proxy multiple negara
- ✅ Database SQLite untuk tracking
- ✅ PM2 configuration untuk production
- ✅ Processing modal dan copy hasil

## Instalasi

1. Install dependencies:
```bash
cd Surfshark-creator
npm install
```

2. Konfigurasi proxy di `config.json`:
```json
{
  "password": "masuk@B1",
  "domain": "@yotomail.com",
  "proxies": {
    "SG": "username__cr.sg:password@gw.dataimpulse.com:823"
  },
  "default_country": "SG"
}
```

3. Jalankan backend:
```bash
# Development
npm run dev

# Production dengan PM2
pm2 start ecosystem.config.js
```

4. Akses frontend melalui browser di `index.php`

## Konfigurasi

### Backend (app.js)
- Port: 8080 (default)
- Rate limit: 300 global, 25 per IP per hari
- Proxy support untuk multiple negara

### Frontend (index.php)
- Form input untuk password, negara, dan jumlah akun
- Rate limiting dengan database SQLite
- Processing modal saat membuat akun
- Copy hasil ke clipboard

## API Endpoints

- `GET /health` - Health check
- `GET /countries` - Daftar negara yang tersedia
- `POST /create` - Membuat akun Surfshark

## Rate Limiting

- **Per IP**: 25 akun per hari
- **Global**: 300 akun per hari
- Tracking menggunakan SQLite database

## Struktur File

```
Surfshark-creator/
├── app.js              # Backend Node.js
├── index.php           # Frontend PHP
├── config.json         # Konfigurasi proxy
├── package.json        # Dependencies
├── ecosystem.config.js # PM2 config
├── surfshark_creator.db # SQLite database
└── logs/              # Log files
```

## Penggunaan

1. Buka `index.php` di browser
2. Masukkan password akun
3. Pilih kode negara proxy (opsional)
4. Tentukan jumlah akun yang ingin dibuat
5. Klik "Buat Akun"
6. Tunggu proses selesai
7. Copy hasil yang ditampilkan

## Troubleshooting

- Pastikan backend berjalan di port 8080
- Cek log PM2: `pm2 logs surfshark-creator`
- Pastikan proxy configuration benar
- Cek rate limit jika gagal membuat akun
