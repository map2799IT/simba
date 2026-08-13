# SIMBA — Instruksi Perbaikan Terarah + Audit Seluruh Route

> **Tujuan utama:** memperbaiki masalah yang tercatat pada dokumen **Update perbaikan** tanpa mengubah business logic lain yang sudah berjalan, lalu melakukan audit menyeluruh terhadap route, controller, view, middleware, permission/role, export, dan alur fitur agar tidak ada route rusak, 404 yang tidak semestinya, atau 500 yang tersembunyi.

---

## COMMAND SIAP TEMPEL KE AI AGENT

```text
Kerjakan perbaikan project SIMBA berdasarkan file TODO ini secara bertahap dan konservatif.

WAJIB:
1. Baca dan pahami struktur project, route, controller, model, service, request validation, Blade/view, middleware, policy/gate, export, PDF, migration, dan test yang SUDAH ADA sebelum mengubah kode.
2. Jangan mengubah business logic di luar scope TODO.
3. Jangan mengganti nama route, controller, model, tabel, kolom, permission, role, status enum, payload QR, atau kontrak API yang sudah dipakai kecuali benar-benar diperlukan untuk memperbaiki bug yang disebutkan.
4. Jangan melakukan refactor besar hanya demi merapikan kode.
5. Jangan membuat migration destruktif, menghapus data, reset database, migrate:fresh, db:wipe, atau mengubah struktur database tanpa kebutuhan yang terbukti.
6. Utamakan memakai relasi, field, service, helper, export class, dan komponen UI existing.
7. Setiap perubahan harus mempertahankan kompatibilitas terhadap data lama.
8. Untuk setiap error 500, temukan root cause dari log/exception. Jangan menutup error dengan try/catch kosong atau mengubahnya menjadi data palsu.
9. Setelah tiap kelompok perubahan, jalankan test/smoke test terkait sebelum lanjut.
10. Setelah seluruh perbaikan, audit SELURUH route project dan buat ringkasan PASS/FAIL beserta route atau fitur yang masih bermasalah.
11. Jangan berhenti hanya karena halaman sudah tampil. Verifikasi CRUD, filter, pencarian, detail, edit, permission, file upload, QR, PDF, Excel, pagination, dan empty state yang berkaitan.
12. Jika menemukan masalah lain di luar scope yang berisiko menyebabkan regression, catat sebagai TEMUAN TERPISAH dan jangan langsung mengubah logic tersebut tanpa alasan yang jelas.

Mulai dengan membuat baseline kondisi project, kemudian kerjakan TODO dari nomor 1 sampai 12, lalu lakukan FULL ROUTE AUDIT dan regression test.

Baca seluruh isi file ini sebelum melakukan edit.
```

---

# A. ATURAN KERJA / GUARDRAILS

## A.1. Prinsip non-regression

Perubahan harus **minimal, terarah, dan kompatibel**.

Dilarang:

- mengganti flow bisnis yang sudah berjalan;
- mengganti rule peminjaman, pengembalian, stok, mutasi, kerusakan, atau approval di luar masalah yang sedang diperbaiki;
- mengubah role/permission yang sudah benar;
- menghapus middleware untuk membuat route “bisa dibuka”;
- mengubah route menjadi public hanya untuk menghilangkan 403;
- menonaktifkan CSRF;
- menonaktifkan validation;
- mengganti relasi database hanya untuk mempermudah query;
- mengubah ID primary key atau foreign key;
- menghapus data lama yang dianggap “tidak cocok”;
- hardcode nama role/user/status;
- hardcode nama barang dari master jika sebenarnya tersedia nama barang saat transaksi barang masuk;
- membuat export PDF/Excel menggunakan dataset berbeda dengan halaman laporan tanpa alasan yang jelas;
- menangkap seluruh exception lalu mengembalikan sukses;
- membuat fallback yang menyembunyikan bug.

Jika perubahan struktur database memang benar-benar tidak bisa dihindari:

1. buktikan terlebih dahulu kenapa struktur existing tidak cukup;
2. buat migration **additive dan backward-compatible**;
3. jangan drop/rename field lama tanpa strategi migrasi;
4. jangan membuat data lama rusak;
5. dokumentasikan pengaruhnya.

---

# B. BASELINE SEBELUM EDIT

Sebelum menyentuh kode, AI agent harus mengumpulkan baseline.

Jalankan yang relevan dengan environment project:

```bash
git status
git branch --show-current
php -v
php artisan about
php artisan migrate:status
php artisan route:list
php artisan route:list -v
```

Jika opsi JSON tersedia:

```bash
php artisan route:list --json > storage/app/route-baseline.json
```

Jika tidak tersedia:

```bash
php artisan route:list > storage/app/route-baseline.txt
```

Kemudian cek:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

> Jangan memakai `migrate:fresh`, `db:wipe`, atau reset data.

Jika project memiliki test:

```bash
php artisan test
```

Jika ada frontend build:

```bash
npm run build
```

Catat error baseline yang memang sudah ada **sebelum** perubahan agar tidak salah menganggapnya regression baru.

---

# C. PEMETAAN SEBELUM IMPLEMENTASI

Sebelum memperbaiki poin 1–12, telusuri dan catat file actual yang terlibat:

- `routes/web.php`
- file route lain jika ada;
- controller terkait inventory/barang masuk/unit/QR/laporan/audit/jurusan/bengkel;
- model terkait;
- form request;
- service/helper;
- export Excel;
- PDF view;
- Blade view;
- middleware;
- policy/gate/permission;
- route model binding;
- storage/file upload;
- QR generation service;
- test terkait.

Cari dengan:

```bash
php artisan route:list
grep -R "inventaris" routes app resources -n
grep -R "barang masuk" routes app resources -n
grep -R "qr" routes app resources -n
grep -R "kerusakan" routes app resources -n
grep -R "laporan" routes app resources -n
grep -R "audit" routes app resources -n
grep -R "jurusan" routes app resources -n
grep -R "bengkel" routes app resources -n
```

Di Windows, gunakan pencarian yang setara (`Get-ChildItem` + `Select-String`) bila `grep` tidak tersedia.

---

# D. TODO PERBAIKAN

## 1. Ringkasan Alat/Bahan + Lokasi Penyimpanan

### Masalah

Pada menu **Ringkasan Alat/Bahan**, nama yang ditampilkan masih mengacu pada nama/kode dari master barang. Yang dibutuhkan adalah nama alat/barang yang berasal dari data **Barang Masuk**, karena nama pada master hanya menjadi acuan.

Selain itu, **Lokasi Penyimpanan** harus tetap muncul pada role lain yang memang memiliki akses; saat ini di Toolman tidak terlihat.

### Target

- Label/nama yang tampil pada ringkasan mengambil nama barang pada detail/transaksi Barang Masuk.
- Jangan mengubah master barang.
- Jangan mengubah identity utama unit/barang.
- Relasi master tetap dipakai untuk referensi bila memang dibutuhkan.
- Lokasi penyimpanan muncul bagi role yang secara existing memang diizinkan melihat data tersebut.
- Jangan membuka akses lokasi kepada role yang tidak memiliki permission.

### Implementasi aman

1. Temukan field actual pada detail Barang Masuk yang menyimpan nama barang saat transaksi.
2. Gunakan field tersebut untuk **display**.
3. Jika data lama tidak memiliki field tersebut, gunakan fallback yang aman ke master **hanya untuk data lama**, bukan menjadikan master kembali sebagai sumber utama.
4. Jangan memakai kode barang sebagai bagian label jika user hanya membutuhkan nama.
5. Audit condition Blade/controller yang menyebabkan Lokasi Penyimpanan hilang untuk Toolman/role lain.
6. Betulkan condition permission, bukan menghapus middleware/authorization.

### Acceptance test

- Barang dengan master sama tetapi nama input Barang Masuk berbeda harus tampil sesuai nama di Barang Masuk.
- Lokasi tampil untuk role yang berhak.
- Lokasi tetap tersembunyi untuk role yang tidak berhak.
- Filter/pagination tetap bekerja.
- Tidak ada N+1 query yang parah setelah perubahan.

---

## 2. Unit Alat & QR + Cetak QR Massal

### Masalah

Pencarian/tampilan pada menu:

- **Unit Alat & QR**
- **Cetak QR Massal**

masih terlalu bergantung pada nama barang dari master.

### Target

Gunakan **nama barang yang berasal dari Barang Masuk** sebagai label utama untuk:

- tampilan daftar;
- pencarian;
- filter;
- pemilihan item yang akan dicetak;
- preview QR massal.

Master tetap menjadi referensi/acuan.

### Penting

**Jangan mengubah isi/identity QR hanya karena nama display berubah.**

QR tetap harus mewakili identifier unit yang memang digunakan sistem saat ini.

### Acceptance test

- Cari menggunakan nama dari Barang Masuk → unit ditemukan.
- Jika nama master berbeda → hasil tetap menggunakan nama Barang Masuk.
- Cetak QR satuan dan massal tetap menghasilkan QR yang mengarah pada unit yang benar.
- QR tidak tertukar antar unit.
- Tidak ada perubahan route QR existing.

---

## 3. Edit Barang Masuk: Tahun Perolehan Mengubah Kode + QR

### Masalah

Saat Barang Masuk diedit dan **tahun perolehan berubah**, kode barang/unit yang diturunkan dari tahun tersebut belum ikut berubah secara konsisten. QR juga harus mengikuti kode baru bila QR memang merepresentasikan kode tersebut.

### Target

Jika dan hanya jika tahun perolehan berubah:

1. hitung ulang kode berdasarkan generator/service existing;
2. pastikan kode baru unik;
3. update data yang memang menyimpan kode turunan tersebut;
4. regenerate QR yang bergantung pada kode;
5. relasi berbasis ID tetap utuh;
6. data histori tidak hilang.

### Jangan lakukan

- jangan mengganti primary key;
- jangan mengganti foreign key;
- jangan generate ulang kode jika tahun tidak berubah;
- jangan mengubah format kode;
- jangan membuat algoritma kode baru kalau service existing sudah ada;
- jangan memutus relasi transaksi lama.

### Wajib audit dependency kode

Cari seluruh pemakaian kolom kode:

```bash
grep -R "item_code" app resources database routes -n
grep -R "kode_barang" app resources database routes -n
grep -R "asset_code" app resources database routes -n
```

Gunakan nama actual di project.

Jika ada field denormalized yang memang wajib ikut berubah, update di dalam **database transaction**.

### File QR

Urutan aman:

1. validasi;
2. generate kode baru;
3. cek uniqueness;
4. simpan database dalam transaction;
5. generate QR baru;
6. pastikan file baru berhasil;
7. baru bersihkan QR lama jika memang tidak lagi dipakai.

Jangan menghapus file QR lama sebelum file baru berhasil dibuat.

### Acceptance test

- Tahun tetap → kode dan QR tetap.
- Tahun berubah → kode berubah sesuai aturan existing.
- QR membuka unit yang benar.
- Peminjaman/kerusakan/perbaikan/history unit lama tetap terhubung.
- Tidak ada duplicate code.

---

## 4. Data Barang Masuk Ditampilkan Per Dokumen

### Masalah

Flow input sudah dimulai dari data dokumen, tahun perolehan, dan metadata lain, kemudian diikuti daftar barang. Tetapi halaman daftar Barang Masuk masih terlalu item-oriented.

### Target UX

Halaman utama **Barang Masuk** ditampilkan **per dokumen/transaksi penerimaan**, bukan satu row per unit/barang.

Contoh konsep:

```text
No Dokumen | Tanggal/Tahun | Sumber/Supplier* | Jumlah Jenis | Total Unit | Aksi
```

`*` Hanya tampilkan field yang memang tersedia pada schema existing.

Aksi:

- Detail
- Edit
- Hapus jika memang sebelumnya tersedia dan role berhak

Di **Detail Barang Masuk**, tampilkan barang-barang yang masuk dalam dokumen tersebut.

### Prinsip implementasi

1. Periksa apakah project sudah memiliki model/header dokumen Barang Masuk.
2. Jika sudah ada, gunakan relasi header → detail.
3. Jika belum ada tetapi data memiliki `nomor_dokumen` yang konsisten, prioritaskan grouping/query/view terlebih dahulu.
4. Jangan langsung melakukan redesign database.
5. Edit harus tetap mempertahankan logic existing dan validasi existing.

### Acceptance test

- Satu dokumen dengan 10 barang → satu row pada halaman utama.
- Klik detail → 10 barang terlihat.
- Edit dokumen → metadata dan detail tetap sinkron.
- Pagination menghitung dokumen, bukan item, jika halaman memang sudah berubah ke document-level.
- Search no dokumen bekerja.
- Tidak muncul duplicate dokumen.

---

## 5. Lapor Kerusakan: Tambah Gambar Bukti

### Masalah

Form **Laporkan Kerusakan Alat** belum memiliki bukti gambar.

### Target

Tambahkan upload gambar **opsional** sebagai bukti tambahan dan tampilkan pada **Kelola Kerusakan**.

### Wajib

- gunakan storage convention existing;
- validation file image;
- jangan simpan base64 ke database;
- gunakan nama file unik;
- lindungi dari path traversal;
- gambar lama tidak hilang ketika edit tanpa upload gambar baru;
- jika record dihapus, file orphan ditangani sesuai pola existing project.

Jika project belum punya rule upload standar, minimal gunakan konsep:

```php
'image',
'mimes:jpg,jpeg,png,webp'
```

Batas ukuran harus mengikuti konvensi project; jangan menetapkan batas berbeda tanpa alasan.

### Kelola Kerusakan

Tambahkan:

- thumbnail/indikator bukti;
- tombol lihat bukti;
- preview yang responsif;
- fallback “Tidak ada bukti gambar”.

Jangan memaksa gambar untuk laporan lama.

### Acceptance test

- Submit tanpa gambar tetap berhasil.
- Submit dengan gambar valid berhasil.
- File non-image ditolak.
- Edit tanpa gambar baru mempertahankan file lama.
- Bukti dapat dilihat oleh role yang memang berhak mengelola kerusakan.

---

## 6. Laporan Inventaris Tidak Menampilkan Data

### Masalah

Halaman **Laporan Inventaris** kosong/tidak menampilkan data meski database memiliki data.

### Cara memperbaiki

Trace secara berurutan:

1. route;
2. middleware/permission;
3. controller method;
4. query builder/Eloquent;
5. default filter;
6. relationship;
7. scope model;
8. pagination;
9. data yang dikirim ke view;
10. key/variable Blade;
11. empty state.

### Wajib

- jangan hardcode data;
- jangan hapus filter seluruhnya hanya agar data muncul;
- cari filter default yang terlalu ketat;
- cek mismatch nama parameter;
- cek `where` terhadap field/status yang berbeda;
- cek `whereHas`;
- cek soft delete;
- cek join yang membuat data hilang;
- cek akses role;
- cek eager loading.

### Acceptance test

- Tanpa filter → data inventaris existing muncul.
- Filter → hasil benar.
- Reset filter → kembali ke seluruh data yang berhak dilihat.
- Pagination tidak menghilangkan filter.
- Empty state hanya tampil jika dataset memang kosong.

---

## 7. Laporan Stok: Tabel Tidak Rapi + PDF 500 + Paritas Excel

### Masalah

- tabel halaman laporan terlalu sedikit kolom;
- alignment/spacing tidak rapi;
- PDF menghasilkan 500;
- Excel memiliki informasi jauh lebih lengkap dibanding halaman.

### Target

Gunakan **satu definisi dataset laporan** sebanyak mungkin untuk:

- halaman web;
- PDF;
- Excel.

Minimal, field yang penting dan sudah tersedia pada Excel harus dipertimbangkan agar tampil di web juga.

Jangan menciptakan field baru yang tidak tersedia.

### UI

Jika kolom banyak:

- gunakan wrapper horizontal scroll;
- header jelas;
- cell alignment konsisten;
- angka rata kanan bila komponen existing menerapkan itu;
- status menggunakan badge existing;
- tanggal menggunakan formatter existing;
- jangan mengecilkan font sampai tidak terbaca;
- jangan memotong informasi penting.

### PDF 500

Wajib cek root cause:

```bash
tail -n 200 storage/logs/laravel.log
```

atau log actual environment.

Periksa khusus:

- variable tidak dikirim ke PDF view;
- relationship null;
- pemanggilan helper yang tidak tersedia saat rendering;
- asset path;
- gambar/logo;
- font;
- method collection;
- property model salah;
- view PDF tidak ditemukan;
- memory/time;
- library PDF configuration;
- route/controller salah;
- data besar tanpa chunking/pagination export.

### Acceptance test

- Halaman stok menampilkan dataset yang masuk akal dan setara dengan export.
- PDF HTTP 200 dan file valid.
- Excel file valid.
- Filter web/PDF/Excel menghasilkan dataset yang sama.
- Tidak ada 500 di log.

---

## 8. Laporan Peminjaman & Pengembalian: PDF 500 + Paritas Excel

### Masalah

- PDF error 500;
- Excel memiliki kolom lebih lengkap;
- tabel halaman perlu disesuaikan dengan data laporan yang sebenarnya.

### Target

- Samakan sumber query/dataset web, PDF, dan Excel.
- Tambahkan kolom web yang memang berguna dan sudah tersedia pada export.
- Pertahankan business rule peminjaman/pengembalian existing.
- Jangan mengubah rule jatuh tempo, status, approval, atau waktu transaksi dari task ini.

### Wajib cek

- relasi peminjam;
- role/jenis peminjam;
- detail barang/unit;
- status;
- tanggal transaksi;
- tanggal kembali bila tersedia;
- overdue/due date bila memang sudah ada;
- filter;
- null relationship;
- export mapping.

### PDF

Perbaiki root cause dari exception aktual, bukan membuat PDF versi kosong.

### Acceptance test

- Data halaman sesuai Excel.
- PDF berhasil.
- Excel berhasil.
- Detail unit/barang tidak hilang.
- Filter menghasilkan data konsisten di semua format.
- Tidak ada perubahan business rule peminjaman.

---

## 9. Laporan Kerusakan & Perbaikan: PDF 500

### Target

Perbaiki PDF tanpa mengubah workflow kerusakan/perbaikan.

Audit:

- route export;
- controller;
- query;
- eager loading;
- status;
- relasi unit/barang;
- relasi pelapor;
- relasi perbaikan;
- optional image;
- PDF view;
- null-safe rendering;
- format tanggal;
- asset path.

Jika bukti gambar dari TODO nomor 5 ikut dicantumkan di PDF, lakukan hanya jika layout PDF existing memang mendukung dan tidak membuat PDF gagal.

### Acceptance test

- Halaman web tetap normal.
- PDF HTTP 200.
- File dapat dibuka.
- Data sesuai filter.
- Data lama tanpa gambar tidak error.
- Record yang belum memiliki data perbaikan tidak menyebabkan null exception.

---

## 10. Laporan Mutasi Stok: PDF 500

### Target

Perbaiki PDF mutasi stok.

Pastikan dataset memuat informasi existing seperti:

- item/barang;
- tipe mutasi;
- qty;
- stok sebelum/sesudah jika memang tersedia;
- referensi;
- user;
- timestamp;
- keterangan;

hanya jika field tersebut memang ada pada sistem.

### Audit

- route;
- controller;
- query;
- relation;
- export class;
- PDF view;
- null handling;
- filter tanggal;
- filter tipe;
- filter barang;
- order;
- pagination vs export.

### Acceptance test

- Web tetap benar.
- PDF valid.
- Excel jika tersedia tetap valid.
- Total mutasi sesuai query.
- Filter konsisten.

---

## 11. Menu Bengkel / Jurusan Error 500

### Masalah

Menu **Bengkel/Jurusan** menghasilkan 500.

### Langkah wajib

1. reproduksi error;
2. catat URL + HTTP method;
3. baca stack trace/log;
4. cocokkan route dengan controller action;
5. cek route model binding;
6. cek query;
7. cek view;
8. cek variable;
9. cek permission;
10. cek relasi jurusan/bengkel;
11. cek data null;
12. cek sidebar link memakai route name yang benar.

### Jangan

- menghapus route;
- mengubah 500 menjadi redirect tanpa memperbaiki penyebab;
- menghapus authorization;
- membuat dummy data agar halaman tampil.

### Acceptance test

Minimal cek:

- index;
- create jika ada;
- store jika ada;
- show jika ada;
- edit;
- update;
- delete jika ada;
- search/filter;
- pagination;
- permission;
- empty state.

---

## 12. Audit Sistem: Download PDF + Excel

### Target

Tambahkan export:

- **PDF**
- **Excel**

pada menu **Audit Sistem**.

Export harus menggunakan filter yang sama dengan halaman audit bila filter tersedia.

### Kolom

Jangan membuat schema audit baru. Gunakan field existing.

Kolom yang ditampilkan harus mengikuti data audit actual, misalnya bila tersedia:

- waktu;
- user;
- role;
- action/event;
- method;
- URL/route;
- status code;
- IP;
- message/error;
- context.

Hanya gunakan field yang memang ada.

### Keamanan

Audit dapat mengandung data sensitif.

- Jangan mengekspor password/token/session/cookie.
- Jangan mengekspor full request payload yang mengandung secret.
- Sanitasi context sesuai helper existing.
- Export hanya bisa diakses role yang memang memiliki permission audit.

Jika Audit Sistem existing sudah mencatat error request seperti 404/500, pastikan export juga mampu menampilkan data tersebut tanpa error.

### Acceptance test

- PDF valid.
- Excel valid.
- Filter web = filter export.
- Permission benar.
- Data sensitif tidak bocor.
- Dataset besar tetap dapat diekspor dengan mekanisme yang sesuai library existing.

---

# E. STANDAR PARITAS LAPORAN WEB / PDF / EXCEL

Untuk laporan:

1. Inventaris
2. Stok
3. Peminjaman & Pengembalian
4. Kerusakan & Perbaikan
5. Mutasi Stok
6. Audit Sistem

usahakan pola:

```text
Report Query/Service
      |
      +--> Web
      +--> PDF
      +--> Excel
```

Tidak wajib melakukan refactor besar.

Jika sekarang project sudah memiliki query terpisah, minimal pastikan:

- filter sama;
- sorting sama;
- permission sama;
- total record sama;
- mapping status sama;
- formatter tanggal konsisten;
- tidak ada field penting hilang hanya di salah satu output.

Bila refactor shared query berisiko besar, jangan dipaksakan. Perbaiki inkonsistensinya secara lokal.

---

# F. FULL ROUTE AUDIT — WAJIB SETELAH TODO 1–12

Ini bukan hanya menjalankan `route:list`. Setiap route harus diperiksa terhadap action dan aksesnya.

## F.1. Export daftar route final

```bash
php artisan route:list > storage/app/route-final.txt
```

Jika JSON tersedia:

```bash
php artisan route:list --json > storage/app/route-final.json
```

Bandingkan dengan baseline.

Perubahan route hanya boleh ada jika memang diperlukan oleh TODO, misalnya route export Audit Sistem atau upload/action baru yang legitimate.

Jika route existing hilang tanpa sengaja → regression.

---

## F.2. Validasi setiap route

Untuk tiap route, cek:

- HTTP method benar;
- URI benar;
- route name unik;
- controller class ada;
- controller method ada;
- invokable controller valid;
- middleware terpasang;
- auth terpasang jika dibutuhkan;
- permission/role benar;
- model binding resolve;
- parameter route sesuai signature controller;
- link/form Blade memakai route name benar;
- form method cocok (`POST/PUT/PATCH/DELETE`);
- CSRF tersedia;
- redirect target ada;
- view yang dirender ada;
- route export menghasilkan response file, bukan view error.

---

## F.3. Cari duplicate route name

Gunakan route list dan/atau script untuk mendeteksi:

- route name duplikat;
- URI + method duplikat;
- route shadowing;
- route dinamis menangkap URI statis;
- route parameter optional yang berbahaya.

Contoh masalah yang wajib dicari:

```text
/items/{item}
```

jangan sampai menelan route statis seperti:

```text
/items/import
/items/export
/items/qr
```

jika urutan/constraint route salah.

Gunakan `whereNumber`, `whereUuid`, atau constraint existing jika sesuai tipe ID actual.

---

## F.4. Audit seluruh pemanggilan `route()`

Cari:

```bash
grep -R "route(" resources app -n
```

Pastikan semua nama route yang dipakai benar-benar terdaftar.

Cari juga:

```bash
grep -R "redirect()->route" app -n
grep -R "to_route(" app -n
grep -R "Route::" routes -n
```

Temuan:

- route name typo;
- route lama setelah rename;
- action sidebar mengarah ke route salah;
- form delete memakai route edit;
- link detail parameter salah;
- export URL salah.

---

## F.5. Audit controller ↔ view

Cari seluruh:

```php
return view(...)
```

Pastikan file Blade-nya ada.

Perhatikan nested view seperti:

```text
reports.stock.index
reports.stock.pdf
reports.borrowing.index
reports.borrowing.pdf
```

Cek juga:

- variable name cocok;
- collection tidak dianggap model tunggal;
- null relation;
- typo property;
- pagination object;
- `with()`/`compact()`.

---

## F.6. Audit route model binding

Untuk route seperti:

```text
/items/{item}
/borrowings/{borrowing}
/damages/{damage}
/departments/{department}
```

pastikan:

- parameter route sama dengan nama argumen;
- model yang dibinding benar;
- custom route key bila ada benar;
- record milik scope yang benar;
- 404 hanya terjadi untuk record tidak ditemukan/di luar scope;
- bukan 500 akibat type mismatch.

---

## F.7. Audit route berdasarkan role

Buat **role-route matrix** berdasarkan role yang memang ada pada project.

Minimal audit role project yang tersedia, termasuk bila ada:

- Admin
- Toolman
- Siswa
- Guru
- Kabeng
- Waka Sarpras

Jangan menganggap semua role harus melihat semua menu.

Untuk setiap role:

```text
Role
├── Dashboard
├── Master Data
├── Inventaris
├── Barang Masuk
├── Unit/QR
├── Peminjaman
├── Pengembalian
├── Kerusakan
├── Stok
├── Laporan
├── Audit
└── Jurusan/Bengkel
```

Tentukan berdasarkan middleware/policy existing:

- ALLOW;
- DENY 403;
- HIDDEN FROM NAVIGATION.

**403 yang memang sesuai permission bukan bug.**

Yang dianggap bug:

- menu terlihat tetapi route selalu 403 karena condition sidebar salah;
- role memiliki permission tetapi link disembunyikan;
- route bisa diakses tanpa permission padahal seharusnya tidak;
- role yang benar mendapat 500.

---

# G. ROUTE SMOKE TEST

## G.1. Buat test, jangan hanya klik manual

Jika project sudah memiliki test infrastructure, tambahkan regression test terarah.

Prefer:

```text
tests/Feature/Routes/
tests/Feature/Reports/
tests/Feature/Inventory/
```

atau pola existing project.

### Static GET routes

Smoke test route yang tidak membutuhkan parameter:

- login/authenticated sesuai kebutuhan;
- expect 200 / 302 / 403 sesuai desain;
- jangan hanya assert “bukan 500” jika status yang benar sudah diketahui.

### Parameterized routes

Gunakan factory/fixture/model existing untuk menyediakan:

- item;
- unit;
- borrowing;
- damage;
- department;
- workshop;
- receipt;
- user.

Jangan memasukkan ID palsu lalu menganggap 404 sebagai PASS untuk semua.

---

## G.2. Export smoke test

Untuk setiap route export:

```text
GET report PDF
GET report Excel
```

Assert:

- bukan 500;
- content-type sesuai;
- response memiliki file/content;
- filter parameter diterima;
- unauthorized role ditolak sesuai desain.

Jika library PDF merender stream:

- minimal response success;
- ukuran body > 0;
- header `Content-Type` sesuai library actual.

---

# H. AUDIT ERROR 404 / 500

Setelah route audit:

1. buka route valid utama;
2. buka satu URL invalid untuk memverifikasi 404 handler;
3. pastikan 404 tidak menjadi 500;
4. pastikan halaman error tidak membocorkan stack trace di non-debug environment;
5. pastikan Audit Sistem, jika memang existing didesain merekam request/error, tetap menerima record 404/500 sesuai mekanisme yang sudah ada.

Jangan sengaja membuat exception destruktif di production.

Untuk test 500, gunakan test environment/mocked exception bila memang diperlukan.

---

# I. CHECK SELURUH MENU SIDEBAR / NAVIGATION

Untuk setiap link sidebar:

- route terdaftar;
- route name benar;
- icon tidak mempengaruhi route;
- active state benar;
- permission condition sama dengan route middleware/policy;
- tidak ada menu “mati”;
- tidak ada menu yang tampil tetapi selalu 404;
- tidak ada menu yang tampil tetapi controller/view hilang;
- tidak ada menu yang tersembunyi padahal role berhak.

Fokus khusus:

- Ringkasan Alat/Bahan
- Lokasi Penyimpanan
- Barang Masuk
- Unit Alat & QR
- Cetak QR Massal
- Kerusakan
- Laporan Inventaris
- Laporan Stok
- Peminjaman & Pengembalian
- Kerusakan & Perbaikan
- Mutasi Stok
- Bengkel/Jurusan
- Audit Sistem

---

# J. CHECK CRUD END-TO-END

Untuk resource yang memiliki CRUD:

## CREATE

- form route;
- submit route;
- validation;
- success notification;
- failure validation;
- authorization;
- duplicate handling.

## READ

- index;
- detail;
- search;
- filter;
- sort jika ada;
- pagination;
- empty state.

## UPDATE

- edit route;
- form data benar;
- update route;
- method PATCH/PUT benar;
- validation;
- old file/QR handling;
- relation tidak putus.

## DELETE

- authorization;
- method DELETE;
- confirmation UI;
- foreign-key handling;
- file cleanup bila ada;
- tidak menghapus data terkait secara salah.

---

# K. CHECK STORAGE / FILE

Karena ada gambar bukti dan QR:

```bash
php artisan storage:link
```

hanya jika environment memang membutuhkan dan symlink belum ada.

Audit:

- disk config;
- public/private visibility;
- URL helper;
- file exists;
- fallback file hilang;
- delete file lama;
- permission folder;
- path production vs local.

Jangan mengubah `FILESYSTEM_DISK` secara sembarangan.

---

# L. CHECK QR

Semua perubahan QR harus diuji:

1. QR unit lama masih terbaca.
2. QR unit baru terbaca.
3. Setelah perubahan tahun, QR baru menunjuk item/unit yang sama.
4. QR massal tidak duplicate.
5. route hasil scan ada.
6. route hasil scan tidak 500.
7. unauthorized scan mengikuti policy existing.
8. nama display dari Barang Masuk tidak mengubah identity QR secara salah.

---

# M. CHECK PDF

Untuk semua PDF:

- Inventory jika ada;
- Stock;
- Borrowing/Return;
- Damage/Repair;
- Stock Mutation;
- Audit.

Wajib cek:

```bash
tail -f storage/logs/laravel.log
```

saat reproduksi.

Periksa view PDF:

- gunakan data yang dikirim controller;
- hindari asset web-only yang tidak dapat diakses engine PDF;
- gunakan path file lokal jika engine existing memerlukannya;
- null-safe;
- tabel tidak terlalu lebar;
- page break;
- header/footer;
- font existing;
- no JavaScript dependency;
- no interactive component dependency.

Jangan mengganti library PDF hanya untuk menyelesaikan satu error kecuali library existing benar-benar rusak dan ada bukti.

---

# N. CHECK EXCEL

Untuk semua Excel:

- data sama dengan query laporan;
- header benar;
- mapping benar;
- tanggal tidak berubah menjadi serial yang tidak diformat;
- nilai numeric tetap numeric jika semestinya;
- filter diterapkan;
- tidak ada N+1 ekstrem;
- large data tidak crash.

Jangan mengubah package Excel jika existing package masih berfungsi.

---

# O. CHECK UI TABLE

Untuk table dengan banyak kolom:

Gunakan pattern project existing.

Minimal:

```html
<div class="overflow-x-auto">
    <table>
        ...
    </table>
</div>
```

Sesuaikan class dengan Tailwind/Bootstrap/CSS actual project.

Kriteria:

- header dan isi sejajar;
- tidak ada kolom terpotong;
- action konsisten;
- mobile dapat horizontal scroll;
- status badge konsisten;
- empty state colspan benar;
- pagination tidak merusak layout;
- filter tidak terlalu lebar;
- panjang text memiliki wrapping/truncate yang masuk akal.

Jangan mengubah seluruh design system.

---

# P. PERFORMANCE CHECK

Setelah query display dari Barang Masuk ditambahkan:

- gunakan eager loading bila perlu;
- hindari query per row;
- jangan load seluruh tabel hanya untuk dropdown jika sudah ada pagination/search;
- PDF/Excel dataset besar harus menggunakan strategi library existing.

Di local/dev dapat gunakan query log/debugbar jika sudah tersedia, tetapi jangan menambah dependency baru hanya untuk audit ini.

---

# Q. SECURITY CHECK

Pastikan perubahan tidak menyebabkan:

- IDOR;
- privilege escalation;
- unrestricted file upload;
- XSS dari nama barang/keterangan;
- path traversal;
- export audit berisi credential;
- route export tanpa auth;
- mass assignment field sensitif;
- QR membuka data sensitif tanpa policy existing;
- CSRF hilang.

Untuk file upload:

- validasi MIME;
- nama unik;
- jangan percaya nama file client sebagai path final.

---

# R. VALIDASI FINAL

Jalankan:

```bash
php artisan optimize:clear
php artisan route:list
php artisan test
npm run build
```

Sesuaikan bila project tidak memakai npm/build frontend.

Kemudian lakukan manual smoke test minimal:

```text
[ ] Login Admin
[ ] Login Toolman
[ ] Login role operasional lain yang tersedia
[ ] Dashboard
[ ] Ringkasan
[ ] Lokasi Penyimpanan
[ ] Barang Masuk index
[ ] Barang Masuk create
[ ] Barang Masuk detail
[ ] Barang Masuk edit tanpa ubah tahun
[ ] Barang Masuk edit dengan ubah tahun
[ ] Unit Alat & QR
[ ] QR tunggal
[ ] QR massal
[ ] Scan QR
[ ] Lapor Kerusakan tanpa gambar
[ ] Lapor Kerusakan dengan gambar
[ ] Kelola Kerusakan
[ ] Laporan Inventaris
[ ] Laporan Stok Web
[ ] Laporan Stok PDF
[ ] Laporan Stok Excel
[ ] Laporan Peminjaman Web
[ ] Laporan Peminjaman PDF
[ ] Laporan Peminjaman Excel
[ ] Laporan Kerusakan/Perbaikan Web
[ ] Laporan Kerusakan/Perbaikan PDF
[ ] Laporan Mutasi Stok Web
[ ] Laporan Mutasi Stok PDF
[ ] Bengkel/Jurusan
[ ] Audit Sistem
[ ] Audit PDF
[ ] Audit Excel
[ ] 404 handler
[ ] Tidak ada 500 baru
```

---

# S. FORMAT LAPORAN AI AGENT SETELAH SELESAI

AI agent **WAJIB** memberikan laporan akhir dengan format ini:

```markdown
# HASIL PERBAIKAN SIMBA

## 1. Ringkasan Perubahan
- ...

## 2. File yang Diubah
- path/file.php
  - alasan:
  - perubahan:

## 3. Database
- Migration baru: YA/TIDAK
- Jika YA: alasan
- Data lama kompatibel: YA/TIDAK

## 4. Perbaikan per TODO
### TODO 1
- Status: PASS/FAIL
- Root cause:
- Fix:
- Verifikasi:

...
### TODO 12
- Status: PASS/FAIL
- Root cause:
- Fix:
- Verifikasi:

## 5. Full Route Audit
- Total route:
- Static route dites:
- Parameterized route dites:
- Route PASS:
- Route 302 expected:
- Route 403 expected:
- Route 404 expected:
- Route FAIL:
- Route 500:

### Route bermasalah
| Method | URI | Name | Role | Status | Masalah |
|---|---|---|---|---|---|

## 6. Export Audit
| Laporan | Web | PDF | Excel | Filter Konsisten |
|---|---|---|---|---|
| Inventaris | PASS | ... | ... | PASS |
| Stok | PASS | PASS | PASS | PASS |
| Peminjaman/Pengembalian | PASS | PASS | PASS | PASS |
| Kerusakan/Perbaikan | PASS | PASS | ... | PASS |
| Mutasi Stok | PASS | PASS | ... | PASS |
| Audit Sistem | PASS | PASS | PASS | PASS |

## 7. Role/Permission Audit
| Fitur | Admin | Toolman | Siswa | Guru | Kabeng | Waka Sarpras |
|---|---|---|---|---|---|---|
| ... | ALLOW | ALLOW | DENY | ... |

Gunakan hanya role yang benar-benar ada di database/project.

## 8. Test
- php artisan test:
- npm run build:
- manual smoke test:
- regression:

## 9. Error Log
- Error 500 tersisa:
- Warning:
- Temuan di luar scope:

## 10. Kesimpulan
- Semua TODO selesai: YA/TIDAK
- Semua route tervalidasi: YA/TIDAK
- Aman untuk deploy: YA/TIDAK
```

---

# T. DEFINITION OF DONE

Task **belum selesai** hanya karena source code sudah berubah.

Task baru boleh dinyatakan selesai jika:

- [ ] TODO 1–12 diverifikasi;
- [ ] nama barang display menggunakan sumber Barang Masuk pada area yang diminta;
- [ ] Lokasi Penyimpanan kembali terlihat sesuai permission;
- [ ] QR tetap memiliki identity yang benar;
- [ ] perubahan tahun mengubah kode/QR secara aman;
- [ ] Barang Masuk dapat dikelola per dokumen;
- [ ] bukti gambar kerusakan bekerja;
- [ ] Laporan Inventaris menampilkan data;
- [ ] PDF Laporan Stok tidak 500;
- [ ] PDF Peminjaman/Pengembalian tidak 500;
- [ ] PDF Kerusakan/Perbaikan tidak 500;
- [ ] PDF Mutasi Stok tidak 500;
- [ ] Bengkel/Jurusan tidak 500;
- [ ] Audit Sistem memiliki PDF + Excel;
- [ ] web/PDF/Excel konsisten;
- [ ] seluruh route terinventarisasi;
- [ ] semua route utama smoke-tested;
- [ ] permission antar role tetap benar;
- [ ] tidak ada route name hilang tanpa alasan;
- [ ] tidak ada 500 baru;
- [ ] test/build lulus atau kegagalan baseline terdokumentasi;
- [ ] tidak ada perubahan business logic di luar scope.

---

# U. PRIORITAS EKSEKUSI YANG DISARANKAN

Kerjakan dalam urutan ini agar regression mudah dilacak:

```text
PHASE 0  Baseline + route inventory
PHASE 1  Barang Masuk + sumber nama transaksi
PHASE 2  Unit/QR + regenerasi kode/QR
PHASE 3  Ringkasan + Lokasi Penyimpanan
PHASE 4  Kerusakan + upload bukti
PHASE 5  Laporan Inventaris
PHASE 6  Laporan Stok
PHASE 7  Laporan Peminjaman/Pengembalian
PHASE 8  Laporan Kerusakan/Perbaikan
PHASE 9  Laporan Mutasi Stok
PHASE 10 Bengkel/Jurusan
PHASE 11 Audit Sistem PDF/Excel
PHASE 12 Full Route Audit
PHASE 13 Regression Test + final report
```

Setelah setiap phase:

```bash
php artisan optimize:clear
php artisan test --filter=<test-yang-relevan>
```

Jika tidak ada test specific, lakukan smoke test route/fitur tersebut sebelum lanjut.

---

# V. INSTRUKSI TERAKHIR UNTUK AI AGENT

- Jangan menebak nama field.
- Jangan menebak nama tabel.
- Jangan menebak route name.
- Jangan menebak permission.
- Jangan menebak service QR.
- Jangan menebak library PDF/Excel.

**Temukan implementasi existing terlebih dahulu, baru patch berdasarkan struktur actual project.**

Untuk setiap bug:

```text
REPRODUCE
→ TRACE
→ FIND ROOT CAUSE
→ MINIMAL PATCH
→ TEST
→ REGRESSION CHECK
→ DOCUMENT RESULT
```

Jika satu poin memerlukan perubahan yang berpotensi mempengaruhi modul lain, hentikan refactor luas dan pilih solusi paling lokal yang tetap konsisten dengan arsitektur existing.
