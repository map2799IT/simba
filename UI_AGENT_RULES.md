# SIMBA — UI_AGENT_RULES.md

> Aturan visual utama untuk AI coding agent.
> Baca file ini sebelum mengubah halaman SIMBA.
> Fokus: UI modern, berwarna, compact, responsive, dan tidak merusak logic.

---

## 1. NON-NEGOTIABLE

Agent WAJIB mempertahankan:

- business logic,
- route,
- database schema,
- middleware,
- role/permission,
- backend validation,
- `@csrf`,
- `@method(...)`,
- `old(...)`,
- `$errors`,
- authorization checks.

Agent DILARANG:

- rename route hanya demi UI,
- membuka action unauthorized,
- memindahkan logic bisnis ke Blade,
- membuat migration hanya untuk desain,
- menghapus validation,
- rewrite backend tanpa kebutuhan.

---

## 2. VISUAL TARGET

SIMBA harus terasa:

- modern,
- clean,
- friendly,
- colorful tetapi tidak ramai,
- compact tetapi tidak sesak,
- mudah dipindai,
- nyaman desktop dan mobile.

Bukan:

- Laravel CRUD mentah,
- halaman putih kosong,
- semua tombol electric-blue,
- tabel HTML default,
- heading sidebar sangat besar,
- desktop yang hanya diperkecil pada mobile.

---

## 3. DESIGN SYSTEM

Base:

```text
Page background  : slate-50
Surface          : white
Border           : slate-200
Text primary     : slate-900
Text secondary   : slate-600
Muted            : slate-500
Primary          : blue-600
Success          : emerald-600
Warning          : amber-500
Danger           : red-600
```

Radius:

```text
Input/button : rounded-xl
Card/table   : rounded-2xl
Badge        : rounded-full
```

Shadow:

```text
Card/table : shadow-sm
Modal/toast : shadow-lg
```

Jangan gunakan gradient pada semua card.

---

## 4. TYPOGRAPHY

Page title:

```text
text-2xl md:text-3xl
font-bold
tracking-tight
text-slate-900
```

Section title:

```text
text-lg font-semibold
```

Body:

```text
text-sm md:text-base text-slate-600
```

Helper:

```text
text-xs md:text-sm text-slate-500
```

Table header:

```text
text-xs font-semibold uppercase tracking-wide text-slate-500
```

---

## 5. APP SHELL

Desktop:

```text
Sidebar ≈ 248px
Topbar ≈ 64px
Main content
```

Main content:

```text
max-w-[1440px]
mx-auto
p-4 lg:p-8
```

Pada mobile:

```text
padding = 16px
```

DILARANG ada horizontal page scroll pada width 375px.

---

## 6. TOPBAR

Topbar:

```text
sticky
64px
white
border-bottom
subtle backdrop blur
```

Isi:

Desktop:

```text
optional global search
notification
profile
```

Mobile:

```text
hamburger
logo / SIMBA
notification
avatar
```

### PENTING

**JANGAN tampilkan page title di topbar.**

Page title hanya muncul pada content header.

Hindari kondisi:

```text
Topbar: Data Alat dan Bahan
Content: Data Alat dan Bahan
```

---

## 7. SIDEBAR

Desktop width:

```text
≈ 248px
```

Background:

```text
slate-900 / slate-950
```

Section labels harus kecil:

```text
INVENTARIS
DATA JURUSAN
DATA SEKOLAH
```

Style:

```text
11px
uppercase
font-semibold
tracking-wide
text-slate-400
```

DILARANG membuat section label seperti heading besar:

```text
I N V E N T A R I S
```

Menu:

```text
text-sm
min-height 44px
rounded-xl
icon 18–20px
```

Active menu:

```text
soft blue accent
```

Jangan membuat electric-blue besar jika tidak perlu.

Nama menu jangan terpotong tanpa alasan.

Perbaiki dahulu:

- padding,
- flex layout,
- available width,
- font size.

---

## 8. MOBILE SIDEBAR

Pada `< lg`:

- menjadi drawer/off-canvas,
- width sekitar 85vw,
- max-width sekitar 320px,
- backdrop,
- close button,
- klik backdrop menutup,
- klik navigation menutup,
- lock body scroll saat drawer terbuka.

Touch target minimum:

```text
44px
```

---

## 9. PAGE HEADER

Struktur:

```text
Title
Description
Primary Action
```

Desktop:

```text
Title/deskripsi kiri
Action kanan
```

Mobile:

```text
Title
Description
Action di bawah
```

Primary action mobile boleh full-width.

Contoh:

```text
Data Alat & Bahan
Kelola master alat dan bahan inventaris sekolah.

[ + Tambah Data ]
```

Hindari label ambigu seperti:

```text
Tambah Master
Submit
Process
```

---

## 10. SPACING

Gunakan spacing konsisten:

```text
Header → section : 24px
Section → section: 20–24px
Card padding     : 16–24px
Form gap         : 16px
Table cell       : 14–16px
```

DILARANG membuat blank vertical area sangat besar.

Jika data tidak ada:

```text
render empty state
```

bukan area putih kosong.

---

## 11. SUMMARY CARDS

Gunakan hanya bila datanya berguna.

Contoh:

```text
Total Data
Kategori
Alat
Bahan
Stok Menipis
```

Card:

```text
white
border slate-200
rounded-2xl
shadow-sm
```

Icon container boleh berwarna soft:

```text
blue-50
emerald-50
amber-50
red-50
```

Jangan membuat semua card gradient.

---

## 12. SEARCH & FILTER

Container:

```text
white
border slate-200
rounded-2xl
p-4
shadow-sm
```

Desktop:

```text
Search flex-1
Jenis
Kategori
Filter
Reset
```

Semua control memiliki tinggi sama:

```text
≈ 44px
```

Search placeholder:

```text
Cari nama, kode, atau kategori...
```

Mobile:

```text
Search full width
Filter button
```

Advanced filter boleh stack/drawer.

Jangan memaksa banyak select berjajar pada mobile.

---

## 13. TABLE DESKTOP

Container:

```text
white
border slate-200
rounded-2xl
shadow-sm
overflow-hidden
```

Header:

```text
bg-slate-50
```

Row:

```text
hover:bg-blue-50/40
```

Nama data gunakan hierarchy:

```text
Laptop Lenovo ThinkPad
BRG-00128
```

Nama lebih tebal.

Kode lebih kecil dan muted.

---

## 14. STATUS BADGES

Safe:

```text
emerald
```

Warning:

```text
amber
```

Danger:

```text
red
```

Neutral:

```text
slate
```

Badge harus memiliki teks.

Jangan hanya mengandalkan warna.

---

## 15. TABLE ACTIONS

Jika action sedikit:

```text
Detail
Edit
```

Jika action banyak:

```text
Detail
⋮
```

Dropdown:

```text
Edit
Riwayat
Cetak
---------
Hapus
```

`Hapus` harus dipisahkan secara visual.

Render action hanya jika user punya permission.

---

## 16. EMPTY STATE

DILARANG meninggalkan table/content blank.

Jika belum ada data:

```text
[icon]

Belum Ada Data

Data belum tersedia.
Tambahkan data pertama untuk memulai.

[ + Tambah Data ]
```

Jika pencarian kosong:

```text
Tidak ada hasil untuk "..."

[ Reset Filter ]
```

---

## 17. MOBILE DATA

Default strategi SIMBA:

```text
Desktop = table
Mobile  = card list
```

JANGAN mengecilkan tabel 6–8 kolom ke width 375px.

Mobile card:

```text
Nama                 Badge
Kode

Kategori             ...
Satuan               ...
Stok                 ...

[ Detail ]       [ ⋮ ]
```

Card:

```text
rounded-2xl
border
white
p-4
shadow-sm
```

Action minimum sekitar 44px.

---

## 18. FORMS

Container:

```text
max-w-4xl
white
border
rounded-2xl
p-4 sm:p-6
shadow-sm
```

Jika field banyak, kelompokkan:

```text
Informasi Dasar
Informasi Inventaris
Informasi Tambahan
```

Grid:

```text
Mobile  : 1 column
Desktop : 2 columns
```

Field panjang:

```text
full width
```

---

## 19. INPUTS

Standard:

```text
height ≈ 44px
rounded-xl
border slate-300
focus blue
```

Label:

```text
text-sm font-semibold text-slate-700
```

Required:

```text
*
```

merah.

Validation error harus tepat di bawah field.

Jangan hanya menampilkan error summary di atas.

---

## 20. FORM ACTIONS

Primary:

```text
Simpan
Perbarui
Tambah
```

Secondary:

```text
Batal
Kembali
```

Submit harus memiliki loading state jika memungkinkan:

```text
Simpan
→
Menyimpan...
```

Disable saat submit untuk mencegah double submit.

---

## 21. CRUD TOAST

Semua CRUD wajib memberi feedback.

Contoh:

```text
Barang berhasil ditambahkan.
Data barang berhasil diperbarui.
Barang berhasil dihapus.
Password pengguna berhasil direset.
Barang masuk berhasil dicatat.
Barang keluar berhasil dicatat.
```

Toast:

Desktop:

```text
top-right
```

Mobile:

```text
top
margin kiri/kanan 16px
```

Support:

```text
success
error
warning
info
```

Harus:

- auto close,
- close manual,
- tidak keluar viewport.

---

## 22. DESTRUCTIVE CONFIRMATION

Delete tidak boleh satu klik.

Modal:

```text
Hapus Data?

[Nama data] akan dihapus dari sistem.
Tindakan ini tidak dapat dibatalkan.

[ Batal ] [ Ya, Hapus ]
```

Mobile modal:

```text
width = viewport - 32px
```

Button boleh stack.

Reset password juga wajib confirmation.

---

## 23. DETAIL PAGE

DILARANG menggunakan disabled form untuk detail.

Gunakan info card:

```text
Informasi Barang

Kode Barang      BRG-00128
Nama             Laptop Lenovo
Kategori         Elektronik
Lokasi           Lab Komputer
Stok             18 Unit
Status           Aman
```

Desktop:

```text
2 columns
```

Mobile:

```text
1 column
```

---

## 24. PAGINATION

Desktop:

```text
Menampilkan 1–10 dari 124
< 1 2 3 ... >
```

Mobile:

```text
1–10 dari 124

[ Sebelumnya ] [ Berikutnya ]
```

Jangan menampilkan banyak nomor kecil pada mobile.

---

## 25. COLOR USAGE

Blue hanya untuk:

- primary action,
- active accent,
- link penting.

Emerald untuk:

- success,
- stok aman.

Amber untuk:

- warning,
- stok menipis.

Red untuk:

- error,
- delete,
- stok habis.

Jangan memenuhi seluruh halaman dengan blue.

---

## 26. ROLE-AWARE UI

Sebelum render:

```text
Create
Edit
Delete
Reset Password
Stock In
Stock Out
Export
Print
```

cek permission.

Jika user tidak berhak:

```text
jangan render action.
```

Backend authorization tetap wajib.

---

## 27. WAKA SARPRAS

Jika permission Waka hanya view/report, arahkan UI ke:

```text
Dashboard
Inventaris
Stok
Laporan
Profil
```

Jangan tampilkan transaksi yang backend tidak izinkan.

---

## 28. ACCESSIBILITY

Wajib:

- setiap input punya label,
- icon-only button punya `aria-label`,
- focus state terlihat,
- contrast cukup,
- modal punya tombol close,
- status tidak hanya dibedakan warna.

---

## 29. DATA ALAT & BAHAN — SPECIFIC RULE

Halaman wajib mengikuti urutan:

```text
Page Header
↓
Optional Summary Cards
↓
Search / Filter
↓
Table desktop / Card mobile
↓
Pagination
```

DILARANG:

```text
Page Header
↓
Filter
↓
Huge blank white area
```

Page header:

```text
Data Alat & Bahan
Kelola master alat dan bahan inventaris sekolah.

+ Tambah Data
```

Table desktop prioritas:

```text
Nama
Jenis
Kategori
Satuan
Aksi
```

Kode tampil sebagai secondary text di bawah nama.

Mobile card minimal:

```text
Nama + Jenis badge
Kode
Kategori
Satuan
Detail / More
```

---

## 30. CURRENT BAD RESULT — DO NOT REPEAT

Hindari hasil seperti:

- judul halaman tampil dua kali,
- sidebar section heading lebih besar daripada menu,
- menu seperti `Cetak QR Mas...` terpotong,
- filter terlalu panjang,
- tombol `Tambah Master` jauh dan terisolasi,
- area putih kosong sangat besar,
- terlalu banyak electric-blue,
- mobile hanya versi tabel desktop yang dipersempit.

---

## 31. RESPONSIVE TEST

Minimal audit:

```text
375px
390px
430px
768px
1024px
1366px
1440px
```

Pada 375px wajib:

- no global horizontal scroll,
- sidebar drawer,
- header tidak overlap,
- button mudah disentuh,
- filter usable,
- modal muat,
- toast muat,
- mobile card readable.

---

## 32. AGENT WORKFLOW

Sebelum edit:

1. baca Blade target,
2. baca layout,
3. baca sidebar/topbar,
4. cek route,
5. cek permission,
6. cek Tailwind,
7. cek Alpine jika ada.

Implement.

Setelah edit:

1. cek Blade syntax,
2. cek route reference,
3. cek permission,
4. cek empty state,
5. cek CRUD toast,
6. cek desktop,
7. cek 375px,
8. cek horizontal overflow.

---

## 33. CHECK COMMANDS

Jalankan yang relevan:

```bash
php artisan route:list
php artisan view:clear
php artisan cache:clear
```

Gunakan test/checker project jika tersedia.

---

## 34. REPORT FORMAT

Setelah revisi agent harus melaporkan:

```text
FILES CHANGED
- ...

UI CHANGES
- ...

MOBILE FIXES
- ...

PERMISSION CHECK
- ...

TESTS/CHECKS
- ...

REMAINING
- ...
```

---

## 35. STANDARD TASK PROMPT

Gunakan prompt berikut:

```text
Baca UI_AGENT_RULES.md terlebih dahulu dan jadikan
sebagai aturan visual utama.

Revisi UI/UX halaman [NAMA HALAMAN].

Jangan mengubah:
- business logic,
- route,
- database,
- middleware,
- validation,
- role/permission.

Fokus:
- visual hierarchy,
- compact layout,
- sidebar/navigation,
- search/filter,
- table desktop,
- mobile card,
- CRUD toast,
- confirmation,
- empty state,
- responsive 375px.

LANGSUNG edit file project.
Jangan hanya memberi contoh kode.

Setelah selesai:
- cek Blade,
- cek route,
- cek permission,
- cek desktop,
- cek width 375px,
- laporkan file yang diubah.
```

---

## 36. DEFINITION OF DONE

Halaman dianggap selesai jika:

- page title hanya satu,
- action utama jelas,
- navigation mudah dibaca,
- tidak ada blank area besar tanpa fungsi,
- search/filter compact,
- table desktop readable,
- mobile menggunakan layout yang layak,
- empty state tersedia,
- CRUD memberi toast,
- delete/reset punya confirmation,
- permission tetap aman,
- 375px tidak horizontal overflow,
- desain konsisten dengan halaman lain.

---

## FINAL RULE

> Jangan menilai UI selesai hanya karena sudah menggunakan Tailwind, `rounded-xl`, shadow, atau warna biru.

UI selesai hanya jika:

```text
jelas
rapi
proporsional
mudah digunakan
tidak mubazir ruang
responsive
permission-safe
```
