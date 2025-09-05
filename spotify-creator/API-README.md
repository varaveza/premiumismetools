# Spotify Creator API Integration

File ini adalah versi API dari `index.php` yang menggunakan API endpoint untuk membuat akun Spotify alih-alih memanggil Python CLI secara langsung.

## File yang Dibuat

- `api-index.php` - Interface PHP yang sama dengan `index.php` tetapi menggunakan API call
- `config.php` - Diupdate dengan konfigurasi API endpoint

## File yang Dihapus (karena pindah ke API)

- `py/` folder - Seluruh folder Python CLI sudah dihapus karena tidak digunakan lagi
- `py/cli_create.py` - Python CLI script
- `py/main.py` - Main Python script
- `py/cleanup_cookies.js` - JavaScript cleanup script
- `py/pm2.js` - PM2 configuration
- `py/cookies/` - Cookie files
- `py/verification_html/` - Verification HTML files
- `py/public/` - Public HTML files

**Catatan**: File di folder `api/` tetap dipertahankan karena digunakan untuk API server.

## Cara Penggunaan

### 1. Setup API Server

Pastikan API server sudah berjalan di VPS lain. API server bisa dijalankan dengan:

```bash
cd spotify-creator/spo/api
python api_app.py
```

API server akan berjalan di port 5112 (default).

### 2. Konfigurasi API Endpoint

Edit file `config.php` untuk mengatur API endpoint:

```php
'API_ENDPOINT' => 'http://your-vps-ip:5112/api/create',  // Ganti dengan IP VPS Anda
'API_KEY' => 'your-api-key-here',  // Jika menggunakan API key authentication
```

### 3. Akses File

Akses `api-index.php` melalui browser:

```
http://your-domain/spotify-creator/api-index.php
```

## Perbedaan dengan index.php

| Aspek | index.php | api-index.php |
|-------|-----------|---------------|
| **Eksekusi** | Python CLI langsung | API call via cURL |
| **Server** | Local Python | Remote API server |
| **Timeout** | Default PHP | 5 menit (300 detik) |
| **Error Handling** | Python output | API response |
| **Debug Info** | Python debug | API debug |

## Konfigurasi API

### API Endpoint
- **URL**: `http://your-vps-ip:5112/api/create`
- **Method**: POST
- **Content-Type**: application/json

### Request Body
```json
{
    "domain": "motionisme.com",
    "password": "Premium@123",
    "trial_link": "https://www.spotify.com/student/...verificationId=..." // optional
}
```

### Response
```json
{
    "success": true,
    "email": "username@domain.com",
    "status": "STUDENT" // or "REGULAR"
}
```

## Rate Limiting

File `api-index.php` memiliki rate limiting yang membatasi:
- **10 users per hari** berdasarkan IP address
- Rate limiting aktif secara default (`DISABLE_RATE_LIMIT = false`)
- Data tersimpan di SQLite database (`spo_creator.db`)

### Cara Kerja Rate Limiting:
1. Setiap request dicek jumlah submission per IP address
2. Jika sudah submit 10 kali hari ini, akan ditolak
3. Pesan error: "Anda sudah membuat 10 akun hari ini. Coba lagi besok."
4. Reset otomatis setiap hari (00:00)

### Disable Rate Limiting (untuk testing):
```php
'DISABLE_RATE_LIMIT' => true,  // Di config.php
```

## Keuntungan Menggunakan API

1. **Scalability**: API server bisa di-deploy di VPS terpisah
2. **Resource Management**: Python process berjalan di server terpisah
3. **Load Balancing**: Bisa menggunakan multiple API servers
4. **Monitoring**: Lebih mudah monitor API server
5. **Security**: API server bisa di-secure dengan IP whitelist
6. **Rate Limiting**: 1 user per hari untuk mencegah abuse

## Troubleshooting

### API Connection Failed
- Pastikan API server berjalan
- Check firewall settings
- Verify API endpoint URL

### Timeout Issues
- API call timeout di-set 5 menit
- Jika masih timeout, bisa di-extend di `api-index.php`

### Authentication Issues
- Jika menggunakan API key, pastikan `API_KEY` di config.php sudah benar
- Check API server logs untuk error details

## Security Notes

- API server mendukung IP whitelist
- Bisa menggunakan API key authentication
- Rate limiting masih berlaku di PHP side
- Debug info di-hidden untuk security

## Migration dari index.php

Untuk migrasi dari `index.php` ke `api-index.php`:

1. Setup API server di VPS
2. Update `config.php` dengan API endpoint
3. Ganti link dari `index.php` ke `api-index.php`
4. Test functionality

Interface dan tampilan tetap sama, hanya backend yang berubah dari Python CLI ke API call.
