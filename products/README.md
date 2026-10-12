# Inventory Management System

Aplikasi berbasis web untuk mengelola persediaan barang menggunakan PHP Native dan MySQL.

## Deskripsi Project

Inventory Management System dibuat untuk membantu pengelolaan data barang, kategori, supplier, serta transaksi stok masuk dan stok keluar. Aplikasi menyediakan login pengguna dengan pembagian hak akses berdasarkan role.

## Teknologi yang Digunakan

- PHP Native
- MySQL
- HTML
- CSS
- XAMPP
- Git dan GitHub

## Fitur Aplikasi

- Login dan logout pengguna
- Hak akses Admin dan Staff
- Dashboard ringkasan persediaan
- Pengelolaan kategori
- Pengelolaan produk
- Pengelolaan supplier
- Transaksi stok masuk dan stok keluar
- Riwayat pergerakan stok
- Pencarian, filter, sorting, dan pagination produk
- Validasi data dan pembatasan akses

## Persyaratan

- XAMPP dengan Apache dan MySQL
- PHP yang kompatibel dengan project
- Browser web
- phpMyAdmin untuk mengelola database

## Cara Menjalankan Aplikasi

1. Salin folder project ke direktori `htdocs` XAMPP.
2. Jalankan Apache dan MySQL melalui XAMPP.
3. Buat database bernama `inventory-app-vutri` melalui phpMyAdmin.
4. Impor file SQL database jika file tersebut sudah tersedia di project.
5. Sesuaikan konfigurasi database pada `config/database.php`.
6. Buka aplikasi melalui browser:

   `http://localhost/inventory-app-vutri/`

7. Login menggunakan akun yang telah dibuat pada database.

## Keamanan

Aplikasi menggunakan prepared statement untuk query database, validasi input, pengelolaan session, pembatasan akses berdasarkan role, serta escaping output HTML pada bagian yang telah menerapkannya.

## Pengembang

Vutri Rahayu

## Repository

[GitHub - Inventory Management System](https://github.com/vutrirahayu23102006-create/inventory-app-vutri)
