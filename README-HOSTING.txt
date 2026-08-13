SIMBA - TOOLMAN STOCK OUT & RECEIPT EDIT FULL FIX
================================================

MASALAH 1: BARANG KELUAR TOOLMAN KOSONG
---------------------------------------
Master Barang sekarang merupakan katalog umum sehingga:

items.workshop_id = NULL

Form Barang Keluar lama masih bergantung pada global scope/master stock.
Akibatnya dropdown Toolman kosong walaupun unit dan bahan TKJ tersedia.

PERBAIKAN:
- alat dibaca dari item_assets.workshop_id;
- hanya unit aktif dan status tersedia yang muncul;
- bahan dihitung dari item_stock_movements.workshop_id;
- Toolman hanya melihat stok jurusannya;
- Admin dapat memilih jurusan;
- transaksi Barang Keluar menyimpan workshop_id;
- Toolman tidak dapat mengeluarkan barang jurusan lain.


MASALAH 2: EDIT BARANG MASUK 404
-------------------------------
URL seperti:

/stock-receipts/23/edit

mengalami 404 karena implicit model binding menjalankan global scope
sebelum StockReceiptWorkflowController memeriksa hak akses.

PERBAIKAN:
- parameter stockReceipt dibinding tanpa global scope;
- hanya movement bertipe incoming yang dapat dibinding;
- controller tetap memeriksa role dan kesamaan workshop;
- Toolman jurusan lain tetap ditolak.


INSTALASI
---------
1. Upload ZIP ke:

/home/myst6282/simba

2. Extract dan pilih:

Replace All / Overwrite

3. Jalankan:

cd /home/myst6282/simba

php tools/install-toolman-stock-out-and-receipt-binding-fix.php

php artisan optimize:clear

php tools/check-toolman-stock-out-and-receipt-edit.php


TARGET ROUTE
------------
stock-issues.index  : OK WorkshopStockIssueController@index
stock-issues.create : OK WorkshopStockIssueController@create
stock-issues.store  : OK WorkshopStockIssueController@store

Pada bagian Toolman TKJ seharusnya tampil jumlah barang dan unit,
bukan barang=0 dan unit=0.

Target akhir:

TOOLMAN STOCK OUT & RECEIPT EDIT SUDAH VALID.


PENGUJIAN TOOLMAN
-----------------
1. Login Toolman TKJ.
2. Buka Barang Keluar.
3. Klik Tambah Barang Keluar.
4. Dropdown harus berisi stok TKJ.
5. Routerboard dan Tang Crimping menampilkan unit TKJ.
6. Bahan menampilkan stok bahan TKJ.
7. Simpan satu transaksi.
8. Pastikan movement menyimpan workshop TKJ.

9. Buka Barang Masuk.
10. Klik Edit transaksi yang dibuat Toolman TKJ.
11. Halaman edit harus terbuka, tidak 404.
12. Simpan perubahan sebagai permintaan approval.


KEAMANAN
--------
- Toolman tidak dapat memilih workshop lain.
- Asset alat diverifikasi harus berada di workshop yang sama.
- Stok bahan diverifikasi berdasarkan ledger workshop.
- Jumlah melebihi stok jurusan ditolak.
- Barang Keluar alat tetap bersifat permanen.
- Tidak ada migration.
- Tidak menghapus data.
- Tidak membutuhkan npm atau Composer.
