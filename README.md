# 📘 Panduan Deploy Aplikasi Absensi Guru ke Rumahweb (cPanel)

> **Stack:** Laravel + Google Apps Script (API) + Google Sheets (Database)

---

## 📋 Daftar Isi

1. [Persiapan Google Apps Script](#1-persiapan-google-apps-script)
2. [Konfigurasi Google Spreadsheet](#2-konfigurasi-google-spreadsheet)
3. [Persiapan Kode Laravel](#3-persiapan-kode-laravel)
4. [Upload ke Rumahweb cPanel](#4-upload-ke-rumahweb-cpanel)
5. [Konfigurasi di Server](#5-konfigurasi-di-server)
6. [Pengujian Aplikasi](#6-pengujian-aplikasi)
7. [Panduan Penggunaan Harian](#7-panduan-penggunaan-harian)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Persiapan Google Apps Script

### Buat Spreadsheet Baru
1. Buka [Google Sheets](https://sheets.google.com) → buat spreadsheet baru
2. Beri nama: **"Database Absensi Guru"**
3. Catat **Spreadsheet ID** dari URL:
   ```
   https://docs.google.com/spreadsheets/d/[SPREADSHEET_ID]/edit
   ```

### Buka Apps Script Editor
1. Di Spreadsheet: klik **Extensions → Apps Script**
2. Hapus kode `myFunction()` yang ada
3. Salin seluruh isi `Code.gs` dari folder proyek ini
4. Klik ikon 💾 **Save** (Ctrl+S)

### Deploy sebagai Web App
1. Klik **Deploy → New Deployment**
2. Pilih tipe: **Web App**
3. Isi konfigurasi:
   - **Description:** Aplikasi Absensi v1.0
   - **Execute as:** `Me`
   - **Who has access:** `Anyone` _(penting: agar Laravel bisa mengakses)_
4. Klik **Deploy**
5. **Salin URL Web App** yang muncul, contoh:
   ```
   https://script.google.com/macros/s/AKfycbxXXXXXXXXXXXXXXXXX/exec
   ```

> ⚠️ **Catatan:** Setiap kali Anda mengubah kode `Code.gs`, wajib buat **New Deployment** baru atau **Edit** deployment yang ada agar perubahan aktif.

---

## 2. Konfigurasi Google Spreadsheet

Sheet berikut akan **dibuat otomatis** saat pertama kali aplikasi dijalankan oleh Apps Script:

| Nama Sheet    | Kolom                                                                         |
|---------------|-------------------------------------------------------------------------------|
| `Data_Siswa`  | ID, Kelas, NIS, Nama Siswa                                                    |
| `Absensi`     | ID_Absen, Tanggal, Semester, Kelas, Jam_Mulai, Jam_Selesai, Materi, Catatan, Detail_Kehadiran |
| `Nilai`       | ID_Nilai, NIS, Kelas, Semester, Tugas_1, Tugas_2, Tugas_3, PTS, PAS, Praktik |

---

## 3. Persiapan Kode Laravel

### Install Dependencies
```bash
# Di komputer lokal, masuk ke direktori proyek
cd "Proyrk Aplikasi Absen"

# Install semua package PHP
composer install --no-dev --optimize-autoloader
```

### Generate App Key
```bash
cp .env.example .env
php artisan key:generate
```

### Edit File `.env`
```ini
APP_NAME="Aplikasi Absensi Guru"
APP_ENV=production
APP_KEY=base64:...  (hasil generate di atas)
APP_DEBUG=false
APP_URL=https://domainanda.com

# ← TERPENTING: URL Apps Script Anda
GAS_API_URL=https://script.google.com/macros/s/XXXXX/exec
```

### Optimasi untuk Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Upload ke Rumahweb cPanel

### Opsi A — Rekomendasi: Arahkan Document Root ke `/public`

Cara paling bersih:
1. Login ke **cPanel → Domains**
2. Klik **Manage** pada domain Anda
3. Ubah **Document Root** ke: `/home/<username>/absensi/public`
4. Upload **seluruh folder proyek** ke `/home/<username>/absensi/`

Struktur di server:
```
/home/<username>/
└── absensi/           ← Upload seluruh folder proyek ke sini
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── public/         ← Document Root domain diarahkan ke sini
    │   ├── index.php
    │   └── .htaccess
    ├── routes/
    ├── resources/
    ├── vendor/
    └── .env
```

### Opsi B — Tanpa mengubah Document Root

Jika Document Root tetap di `public_html`:
1. Upload folder proyek ke `/home/<username>/absensi/`
2. Upload `index.php` (root) ke `/home/<username>/public_html/index.php`
3. Upload `.htaccess` (root) ke `/home/<username>/public_html/.htaccess`
4. Sesuaikan path di `index.php` jika perlu

### Upload via File Manager cPanel
1. cPanel → **File Manager** → masuk ke `/home/<username>/`
2. Klik **Upload** → pilih **Zip Archive**
3. Zip dulu folder `absensi/` di komputer lokal
4. Upload dan **Extract** di server

---

## 5. Konfigurasi di Server

### Set Permission
Di cPanel File Manager, klik kanan folder:
- `storage/` → Permissions → **755** (recursive)
- `bootstrap/cache/` → Permissions → **755**

Atau via SSH (jika tersedia):
```bash
chmod -R 755 storage bootstrap/cache
```

### Verifikasi `.env` di Server
Pastikan file `.env` sudah benar (tidak ter-upload tanpa API URL):
- Di File Manager: buka file `.env` → pastikan `GAS_API_URL` sudah terisi

---

## 6. Pengujian Aplikasi

### Test Koneksi ke Apps Script
Buka di browser:
```
https://domainanda.com
```
Jika muncul dashboard → koneksi berhasil ✅

Jika muncul error koneksi, cek:
1. `GAS_API_URL` di `.env` sudah benar?
2. Deployment Apps Script aktif?
3. Apps Script di-deploy dengan akses **Anyone**?

### Urutan Pengujian Fitur
1. **Data Siswa** → Tambah siswa manual
2. **Data Siswa** → Impor CSV (download template di bawah)
3. **Absensi** → Pilih kelas, isi absensi, simpan
4. **Penilaian** → Input nilai Tugas 1
5. **Penilaian → Lihat Leger** → Cek nilai & predikat otomatis

---

## 7. Panduan Penggunaan Harian

### Format File CSV untuk Import Siswa
```csv
kelas,nis,nama
X-A,2024001,Ahmad Ridwan Saputra
X-A,2024002,Budi Santoso
X-B,2024003,Citra Dewi Rahayu
XI-IPA,2024101,Dina Nur Fadilah
```

### Skala Predikat Nilai
| Nilai   | Predikat |
|---------|----------|
| ≥ 90    | A        |
| 80–89   | B        |
| 70–79   | C        |
| 60–69   | D        |
| < 60    | E        |

### Mencetak Daftar Hadir
1. Buka halaman **Absensi**
2. Pilih kelas & tanggal
3. Klik tombol 🖨️ **Cetak**

### Mencetak Leger Nilai
1. Buka **Penilaian → Lihat Leger**
2. Pilih kelas & semester
3. Klik tombol 🖨️ **Cetak Leger**

---

## 8. Troubleshooting

### ❌ "Koneksi ke database gagal"
- Cek `GAS_API_URL` di `.env`
- Pastikan deployment Apps Script aktif dan accessnya **Anyone**
- Cek log di `storage/logs/laravel.log`

### ❌ "Class not found" / Error 500
- Pastikan `composer install` dijalankan
- Cek permission folder `storage/` dan `bootstrap/cache/`
- Pastikan `php artisan config:cache` dijalankan

### ❌ Redirect Loop / Halaman Tidak Muat
- Cek `.htaccess` di root dan di `public/`
- Cek PHP version di cPanel (minimal PHP 8.1)

### ❌ Data tidak tersimpan di Sheets
- Pastikan akun Google yang menjalankan Apps Script memiliki akses **Edit** ke Spreadsheet
- Buka Script Editor → **Run → getInitialData** secara manual untuk tes

### ❌ CSS/JS tidak muncul (tampilan rusak)
- Cek `APP_URL` di `.env` sesuai dengan domain Anda (dengan/tanpa www)
- Jalankan: `php artisan view:clear && php artisan view:cache`

---

*Dibuat dengan Google Apps Script + Laravel + Google Sheets*
*Dihosting di Rumahweb (cPanel Shared Hosting)*
