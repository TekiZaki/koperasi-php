# 📌 Koperasi PHP

Aplikasi **Manajemen Koperasi Masjid** berbasis **PHP + MySQL** dengan fitur lengkap untuk mengelola:

- **Kas Umum** (Transaksi pemasukan & pengeluaran)
- **Simpanan Anggota** (wajib & sukarela)
- **Pinjaman (Piutang)** & cicilan
- **Infaq** (pemasukan & pengeluaran)
- **Manajemen User** (role: user, admin, superadmin)

---

## 🚀 Fitur Utama

- 🔑 **Autentikasi & Otorisasi**  
  Login dengan hashing password (`password_hash`, `password_verify`) dan role-based access control.
- 👥 **Manajemen User**  
  Tambah, edit, hapus user dengan role `user`, `admin`, `superadmin`.
- 💰 **Kas Umum**  
  Catat pemasukan & pengeluaran dengan deskripsi detail.
- 💵 **Simpanan**  
  Kelola simpanan anggota (wajib & sukarela) + detail riwayat.
- 📊 **Pinjaman**  
  Tambah pinjaman anggota, tenor, status (aktif, selesai, gagal), plus riwayat pembayaran cicilan.
- 🙏 **Infaq**  
  Catat penerimaan & penyaluran infaq dengan nama donatur opsional.
- 📱 **UI Responsif & Mobile Friendly**  
  Dibangun dengan **Bootstrap 5** + custom JS untuk pengalaman lebih baik di mobile.
- 🔒 **Keamanan**
  - Session hijacking prevention (session_regenerate_id)
  - CSRF token untuk form
  - Validasi input & sanitasi output (anti-XSS)
  - PDO prepared statements (anti-SQL Injection)

---

## 🗂️ Struktur Direktori

```

koperasi-php/
│── actions/            # Handler untuk CRUD (transactions, savings, loans, infaq)
│── assets/             # Asset statis (CSS, JS, images)
│   ├── css/
│   └── js/main.js
│── config/             # Konfigurasi (Database.php, generator hash pw)
│── includes/           # Template header, footer, sidebar
│── models/             # Model OOP untuk database (User, Loan, Saving, etc.)
│── pages/              # Halaman konten (dashboard, transaksi, dsb.)
│── ajax\_get\_\*.php      # Endpoint AJAX untuk detail pinjaman & simpanan
│── crud.php            # Halaman CRUD user
│── db.sql              # Skema database MySQL
│── index.php           # Routing utama aplikasi
│── login.php           # Halaman login
│── logout.php          # Logout & destroy session

```

---

## 🛠️ Instalasi

### 1. Clone Repo

```bash
git clone https://github.com/username/koperasi-php.git
cd koperasi-php
```

### 2. Setup Database

- Import file `db.sql` ke MySQL:

```bash
mysql -u root -p < db.sql
```

- Database default: `koperasi_php`

### 3. Konfigurasi Koneksi DB

Edit file `config/Database.php` jika username/password MySQL berbeda:

```php
private $host = "localhost";
private $db_name = "koperasi_php";
private $username = "root";
private $password = "";
```

### 4. Jalankan Aplikasi

- Letakkan folder di dalam `htdocs` (XAMPP) atau `www` (Laragon).
- Akses lewat browser:
  👉 `http://localhost/koperasi-php/login.php`

---

## 🔑 Akun Default

| Username   | Password            | Role       |
| ---------- | ------------------- | ---------- |
| superadmin | testdzaki231203test | superadmin |

> ⚠️ **WAJIB ganti password default** di produksi dengan `config/pw.php`.

---

## 📜 Role User

- **Superadmin**: Full access (semua fitur + user management)
- **Admin**: CRUD transaksi, simpanan, pinjaman, infaq
- **User**: Hanya view data tertentu

---

## 🖼️ Screenshot (opsional)

_Tambahkan screenshot halaman login, dashboard, dll._

---

## 🔒 Catatan Keamanan

- Gunakan **HTTPS** untuk deployment.
- Ganti password default segera.
- Atur permission file di server.
- Jangan gunakan akun `root` MySQL di produksi.

---

## 🤝 Kontribusi

Pull request dan issue sangat terbuka.
Ikuti best practice keamanan & coding standard PHP.

---

## 📄 Lisensi

MIT License – silakan digunakan & dimodifikasi sesuai kebutuhan.

---

## 🗂️ Alur Penggunaan

### 1. Login

- Buka `http://localhost/koperasi-php/login.php`
- Masukkan username & password

### 2. Dashboard

- Menampilkan ringkasan kas, simpanan, pinjaman, dan infaq

### 3. Kas Umum

- Klik menu **Kas Umum**
- Tambahkan pemasukan/pengeluaran
- Edit atau hapus data bila diperlukan

### 4. Simpanan Anggota

- Klik menu **Simpanan**
- Tambahkan simpanan **wajib** atau **sukarela**
- Klik nama anggota untuk melihat detail riwayat simpanan (AJAX modal)

### 5. Pinjaman (Piutang)

- Klik menu **Piutang**
- Tambah data pinjaman (nama anggota, tanggal, jumlah, tenor)
- Klik detail pinjaman untuk melihat riwayat cicilan
- Tambahkan pembayaran cicilan sampai status berubah jadi **selesai**

### 6. Infaq

- Klik menu **Infaq**
- Tambah pemasukan atau pengeluaran infaq
- Bisa menambahkan nama donatur (opsional)

### 7. Manajemen User

- Hanya bisa diakses oleh **Superadmin**
- Buka `crud.php` untuk:
  - Tambah user baru
  - Edit username, password, nama, dan role
  - Hapus user

### 8. Logout

- Klik tombol **Logout** di pojok kanan atas untuk keluar

---
