# Sistem Informasi Puskesmas

---
# Kelompok 4
Anggota:
* Talitha Dalilah Difa	2315061012
* Radhitya Agrayasa Rhalin	2315061002
* Bagas Pangestu	2315061010
* Anggriani Luthfiyah Ratu	2315061058

---

Pendaftaran Pasien, Antrian, dan Rekam Medis

Sistem Informasi Puskesmas adalah aplikasi berbasis web yang digunakan untuk mendukung layanan Puskesmas dalam proses pendaftaran pasien, pengelolaan antrian, pemeriksaan, pembuatan rekam medis, serta pengaturan data tenaga kesehatan dan jadwal praktik. Sistem ini dirancang dengan pendekatan Role-Based Access Control (RBAC) sehingga hak akses antara Admin, Dokter, dan Pasien dibedakan dengan jelas.

---
# Use Case Diagram
![Use Case Sistem Informasi Puskesmas](https://github.com/user-attachments/assets/c74dc301-6dae-4a90-a745-40cff8edac06)


### Penjelasan Use Case Berdasarkan Aktor

---

## 1. Pasien

Pasien dapat mengakses berbagai layanan klinik secara digital.

**Use Case Pasien:**

* Registrasi
* Login / Logout
* Melihat Jadwal Dokter
* Mengambil Antrian (berdasarkan poli dan jadwal)
* Melihat Rekam Medis
* Mengelola Profil
* Melihat Pengumuman

---

## 2. Dokter

Dokter berfokus pada pengelolaan pemeriksaan dan rekam medis.

**Use Case Dokter:**

* Login / Logout
* Melihat Daftar Pasien
* Mengubah Status Antrian
* Melakukan Pemeriksaan

  * *Extend:* Membuat Rujukan
* Mengelola Data Dokter (edit profil)

---

## 3. Admin

Admin menangani pengelolaan data dan konfigurasi sistem.

**Use Case Admin:**

* Login / Logout
* Mengelola Data Dokter
* Mengelola Data Pasien
* Mengelola Data Poli
* Mengelola Jadwal
* Mengelola Pengumuman

---

## Kebutuhan Sistem

* PHP 7.4+ atau PHP 8.x
* MySQL / MariaDB
* Apache (XAMPP / LAMPP / Laragon / WAMP)
* Browser modern (Chrome, Firefox, Edge)

---

# Fitur Sistem

## Fitur Pasien

* Registrasi dan Login
* Pengambilan Antrian
* Melihat Jadwal Dokter
* Melihat Rekam Medis
* Mengubah Profil
* Melihat Pengumuman Klinik

## Fitur Dokter

* Melihat Daftar Pasien
* Mengubah Status Antrian
* Mengisi Rekam Medis
* Membuat Rujukan
* Mengubah Profil Dokter

## Fitur Admin

* Manajemen Data Dokter
* Manajemen Data Pasien
* Manajemen Data Poli
* Manajemen Jadwal Praktik
* Manajemen Pengumuman

# Cara Penggunaan Sistem

## 1. Pasien

1. Registrasi akun
2. Login ke sistem
3. Melihat jadwal dokter dan poli
4. Mengambil antrian
5. Melihat status rekam medis
6. Mengakses pengumuman klinik

## 2. Dokter

1. Login menggunakan akun dokter
2. Melihat daftar pasien pada hari tersebut
3. Memperbarui status antrian
4. Mengisi rekam medis saat pemeriksaan
5. Membuat rujukan bila diperlukan
6. Mengubah informasi profil dokter

## 3. Admin

1. Login sebagai admin
2. Mengelola data dokter, pasien, poli, jadwal, dan pengumuman
3. Melihat daftar pengguna sistem
4. Mengatur konfigurasi layanan klinik

---

## Struktur Folder

Berikut adalah struktur direktori utama dari Sistem Informasi Puskesmas:

```
public/
│
├── admin/
│   ├── modals/
│   │   ├── modal_dokter.php
│   │   ├── modal_pengumuman.php
│   │   └── modal_poli.php
│   │
│   ├── cetak_rujukan_pdf.php
│   ├── dashboard.php
│   ├── data_dokter.php
│   ├── data_pasien.php
│   ├── data_poli.php
│   ├── jadwal_praktik.php
│   ├── pengumuman.php
│   ├── profil.php
│   ├── rujukan.php
│   └── sidebar.php
│
├── dokter/
│   ├── cetak_rujukan.php
│   ├── daftar_pasien.php
│   ├── dashboard.php
│   ├── detail_rekam_medis.php
│   ├── header.php
│   ├── pemeriksaan.php
│   ├── profil_dokter.php
│   ├── proses_pemeriksaan.php
│   ├── rekam_medis.php
│   └── sidebar.php
│
├── pasien/
│   ├── forgot_password.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   └── reset_password.php
│
├── img/
│   └── (asset icon dan gambar)
│
└── partials/
    ├── footer.php
    ├── header.php
    └── logout_modal.php

src/
│
├── config/
│   ├── database.php
│   └── mail.php
│
└── helpers/
    ├── auth.php
    └── icon_helper.php
```

# Instalasi Sistem

## 1. Clone Repository

```bash
git clone <repository-url>
cd sistem-puskesmas
```

## 2. Konfigurasi Database

1. Buat database baru di MySQL/MariaDB (misal nama: `puskesmas_db`).
2. Import file SQL yang ada pada folder project (biasanya bernama `database.sql` atau sejenisnya).
3. Sesuaikan koneksi database pada file konfigurasi:

Contoh PHP Native:

```
config/database.php
```

Contoh CodeIgniter:

```
app/Config/Database.php
```

Contoh Node.js:

```
config/db.js
```

Atur sesuai kebutuhan:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=puskesmas_db
```

## 3. Menjalankan Server

### Jika PHP Native / CI / tanpa framework:

Jalankan melalui XAMPP / Laragon:

1. Pindahkan folder project ke:

```
htdocs/   (untuk XAMPP)
www/      (untuk WAMP)
```

2. Start Apache dan MySQL
3. Akses aplikasi melalui browser:

```
http://localhost/sistem-puskesmas
```

### Jika berbasis Node.js:

Jalankan:

```bash
npm install
npm start
```

Akses aplikasi:

```
http://localhost:3000
```

---

# Cara Menjalankan Aplikasi

## 1. Persiapan

* Pastikan web server aktif (Apache/Nginx)
* Pastikan database sudah terhubung
* Pastikan semua file konfigurasi sudah benar

## 2. Jalankan Sistem

Akses melalui browser:

```
http://localhost/sistem-puskesmas
```

## 3. Login Pengguna

Jika terdapat akun default (bergantung pada database.sql):

**Admin**

* Username: admin
* Password: admin123 *(contoh)*

**Dokter**

* Username: dokter1
* Password: dokter123 *(contoh)*

**Pasien**

* Registrasi langsung melalui halaman pendaftaran
