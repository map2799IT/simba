SIMBA - RESET DATA DUMMY, PERTAHANKAN USER
==============================================

FUNGSI
------
Mengosongkan seluruh tabel data aplikasi selain:

- users;
- workshops;
- migrations;
- tabel induk lain yang diperlukan oleh foreign key user.

Alasan workshops dipertahankan:
users.workshop_id membutuhkan data jurusan agar akun tetap valid.


DATA YANG AKAN TERHAPUS
-----------------------
Termasuk, bila tabelnya tersedia:

- master alat dan bahan;
- unit alat dan QR;
- lokasi penyimpanan;
- barang masuk;
- barang keluar;
- pergerakan stok;
- peminjaman;
- pengembalian;
- histori dan log operasional;
- session database;
- kategori dan satuan;
- data dummy lainnya.


PENTING
-------
Proses ini permanen.

Backup database melalui phpMyAdmin sebelum menjalankan reset.

JANGAN memakai:
php artisan migrate:fresh


INSTALASI
---------
Extract ZIP langsung ke root project:

LOKAL:
C:\xampp\htdocs\simba

HOSTING:
/home/myst6282/simba


MENJALANKAN DI HOSTING
----------------------
cd /home/myst6282/simba

php tools/reset-data-keep-users.php


Untuk melanjutkan, ketik:

RESET DATA SIMBA


MODE TANPA KONFIRMASI
---------------------
Hanya gunakan setelah backup:

php tools/reset-data-keep-users.php --force


SETELAH RESET
-------------
cd /home/myst6282/simba

php artisan optimize:clear


PEMERIKSAAN USER
----------------
php artisan tinker --execute="dump(
    \App\Models\User::query()->count()
);"


CATATAN
-------
Kategori, satuan, lokasi, master barang, dan transaksi ikut dikosongkan.

Akun user dan jurusannya tetap ada.
