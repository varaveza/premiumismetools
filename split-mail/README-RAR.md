# Split Mail - RAR Download Feature

## Overview
Fitur RAR Download memungkinkan Anda untuk mengunduh hasil pembagian email dalam format file dengan nama `fileisme-{nomor}` yang dapat dikonfigurasi.

## Cara Menggunakan

### 1. Input Data
- Masukkan email:password atau email saja dalam format satu baris per data
- Atau upload file .txt yang berisi data email

### 2. Konfigurasi RAR Download
Klik tab "RAR Download" untuk mengatur:
- **Prefix nama file**: Default `fileisme` (bisa diubah sesuai kebutuhan)
- **Ekstensi file**: Pilih antara `.rar`, `.zip`, atau `.7z`
- **Nomor awal**: Mulai dari nomor berapa (default: 1)

### 3. Pembagian Data
- Atur jumlah baris per grup dalam "Bagi per (X) baris"
- Klik "Bagi Data" untuk memproses

### 4. Download RAR
Setelah data dibagi, Anda akan melihat:
- **Download Semua (.zip)**: Download semua grup dalam satu file ZIP
- **Download RAR**: Download satu file RAR/ZIP yang berisi multiple file `fileisme-{nomor}` di dalamnya

## Format Output
Contoh hasil download dengan konfigurasi default:
```
splitisme.rar (satu file RAR)
├── fileisme-1 (25 email)
├── fileisme-2 (25 email)
├── fileisme-3 (25 email)
└── fileisme-4 (25 email)
```

**Catatan**: 
- Satu file RAR/ZIP yang berisi multiple file di dalamnya
- File di dalam RAR menggunakan nama `fileisme-{nomor}` tanpa ekstensi
- Nama file RAR: `splitisme.rar`

## Fitur Tambahan
- Preview format nama file secara real-time
- Dukungan berbagai ekstensi (.rar, .zip, .7z)
- Nomor awal yang dapat dikonfigurasi
- Prefix nama file yang dapat disesuaikan

## Contoh Penggunaan
1. Input 100 email
2. Bagi per 25 baris
3. Prefix: "mailist"
4. Ekstensi: .rar
5. Nomor awal: 1

Hasil: `splitisme.rar` yang berisi:
- `mailist-1` (25 email)
- `mailist-2` (25 email)
- `mailist-3` (25 email)
- `mailist-4` (25 email)
