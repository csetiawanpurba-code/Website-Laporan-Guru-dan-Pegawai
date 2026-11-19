# Sistem Laporan Guru dan Pegawai Sekolah

Aplikasi web untuk mengelola laporan kegiatan guru dan pegawai sekolah.

## 🎯 Fitur

### Semua Role
- **Login** - Masuk ke sistem dengan akun
- **Dashboard** - Halaman utama dengan ringkasan data dan grafik
- **Profil** - Ubah data profil dan password
- **Logout** - Keluar dari sistem

### Admin
- **Kelola Data** - Mengelola data guru, pegawai, dan kepala sekolah (CRUD)
- **Verifikasi Laporan** - Menyetujui/menolak laporan
- **Rekap Laporan** - Ringkasan laporan berdasarkan periode
- **Monitoring** - Memantau kinerja dan laporan

### Kepala Sekolah
- **Monitoring Laporan** - Memonitor hasil laporan dan kinerja melalui tampilan laporan dan grafik
- Melihat statistik dan tren laporan
- Filter berdasarkan periode dan role

### Guru & Pegawai
- **Input Laporan** - Membuat laporan kegiatan
- **Riwayat Laporan** - Melihat daftar laporan yang sudah dibuat

### Tambahan
- **Export & Cetak** - Export ke CSV dan cetak laporan
- **Responsive Design** - Tampilan menyesuaikan ukuran layar

## 👥 Role Pengguna

| Role | Akses |
|------|-------|
| ADMIN | Semua fitur |
| KEPALA_SEKOLAH | Dashboard, Monitoring, Profil |
| GURU | Dashboard, Input Laporan, Riwayat, Profil |
| PEGAWAI | Dashboard, Input Laporan, Riwayat, Profil |

## 📋 Persyaratan Sistem

- XAMPP (Apache, MySQL, PHP 7.4+)
- Browser modern (Chrome, Firefox, Edge, Safari)

## 🚀 Cara Instalasi

### 1. Install XAMPP

Download dan install XAMPP dari: https://www.apachefriends.org/download.html

### 2. Copy Folder Aplikasi

Extract file ZIP dan copy folder `xampp-sistem-laporan` ke folder `htdocs`:

**Windows:**
```
C:\xampp\htdocs\sistem-laporan\
```

**Linux/Mac:**
```
/opt/lampp/htdocs/sistem-laporan/
```

### 3. Start XAMPP

1. Buka **XAMPP Control Panel**
2. Klik **Start** pada **Apache**
3. Klik **Start** pada **MySQL**

### 4. Import Database

1. Buka browser, akses: `http://localhost/phpmyadmin`
2. Klik tab **"Import"**
3. Klik **"Choose File"** dan pilih file `database.sql`
4. Klik tombol **"Go"** di bagian bawah

### 5. Fix Password (PENTING!)

Setelah import database, akses file fix-password.php untuk memperbaiki password:

```
http://localhost/sistem-laporan/fix-password.php
```

Klik tombol **"Login Sekarang"** setelah proses selesai.

**HAPUS file fix-password.php setelah selesai!**

### 6. Akses Aplikasi

Buka browser dan akses:
```
http://localhost/sistem-laporan/
```

## 🔐 Akun Login

Setelah menjalankan fix-password.php, gunakan akun berikut:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@sekolah.com | admin123 |
| Kepala Sekolah | kepala@sekolah.com | kepala123 |
| Guru | guru@sekolah.com | guru123 |
| Pegawai | pegawai@sekolah.com | pegawai123 |

## 📁 Struktur Folder

```
sistem-laporan/
├── assets/
│   ├── css/
│   │   └── style.css          # File CSS utama
│   └── js/
│       └── app.js             # File JavaScript
├── config/
│   └── database.php           # Konfigurasi database
├── includes/
│   ├── header.php             # Header template
│   └── footer.php             # Footer template
├── database.sql               # File SQL untuk import
├── fix-password.php           # Script perbaikan password
├── index.php                  # Redirect ke login/dashboard
├── login.php                  # Halaman login
├── logout.php                 # Proses logout
├── dashboard.php              # Halaman dashboard
├── kelola-data.php            # Kelola data pengguna (Admin)
├── input-laporan.php          # Input laporan (Guru/Pegawai)
├── riwayat-laporan.php        # Riwayat laporan
├── verifikasi.php             # Verifikasi laporan (Admin)
├── monitoring.php             # Monitoring laporan (Admin/Kepala Sekolah)
├── rekap.php                  # Rekap laporan (Admin)
├── profil.php                 # Halaman profil
└── README.md                  # Dokumentasi
```

## 🛠️ Troubleshooting

### Halaman Blank
- Pastikan Apache sudah running (hijau di XAMPP Control Panel)
- Periksa error log di `C:\xampp\apache\logs\error.log`

### Database Error
- Pastikan MySQL sudah running
- Pastikan database `sistem_laporan_sekolah` sudah di-import
- Periksa konfigurasi di `config/database.php`

### Tidak Bisa Login
1. Pastikan sudah menjalankan `fix-password.php`
2. Gunakan password yang benar:
   - Admin: `admin123`
   - Kepala Sekolah: `kepala123`
   - Guru: `guru123`
   - Pegawai: `pegawai123`
3. Jika masih gagal, jalankan ulang `fix-password.php`

### CSS Tidak Muncul
- Periksa konfigurasi `BASE_URL` di `config/database.php`
- Default: `define('BASE_URL', '/sistem-laporan');`

### Menu Tidak Sesuai Role
- Menu ditampilkan sesuai dengan role masing-masing
- Kepala Sekolah hanya melihat menu Dashboard, Monitoring, dan Profil
- Guru/Pegawai hanya melihat menu Dashboard, Input Laporan, Riwayat, dan Profil

## 📝 Catatan

1. **Keamanan**: Hapus file `fix-password.php` setelah digunakan
2. **Backup**: Backup database secara berkala
3. **Password**: Ganti password default setelah login pertama

## 📞 Dukungan

Jika mengalami masalah, pastikan:
1. XAMPP sudah terinstall dengan benar
2. Apache dan MySQL sudah running
3. Database sudah di-import
4. File `fix-password.php` sudah dijalankan
5. Menggunakan browser modern

---

© 2024 Sistem Laporan Guru dan Pegawai
