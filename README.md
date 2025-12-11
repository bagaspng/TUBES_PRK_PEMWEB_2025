# Sistem Informasi Puskesmas

---

Pendaftaran Pasien, Antrian, dan Rekam Medis

Sistem Informasi Puskesmas adalah aplikasi berbasis web yang digunakan untuk mendukung layanan Puskesmas dalam proses pendaftaran pasien, pengelolaan antrian, pemeriksaan, pembuatan rekam medis, serta pengaturan data tenaga kesehatan dan jadwal praktik. Sistem ini dirancang dengan pendekatan Role-Based Access Control (RBAC) sehingga hak akses antara Admin, Dokter, dan Pasien dibedakan dengan jelas.
---

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