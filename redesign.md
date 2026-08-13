# SIMBA — AI Agent UI/UX Revision Master Plan

> Dokumen ini dibuat khusus sebagai **instruksi kerja untuk AI coding agent** dalam merevisi UI/UX project SIMBA.
>
> Fokus utama:
> - Tampilan lebih modern, menarik, dan berwarna.
> - Navigation lebih mudah dipahami.
> - Table lebih rapi dan nyaman dibaca.
> - CRUD memiliki feedback yang jelas.
> - Mobile experience harus benar-benar friendly.
> - Tidak merusak business logic, route, authorization, role, atau database.
>
> Dokumen ini dapat diberikan langsung ke AI agent sebagai task specification.

---

# 0. GOLDEN RULES UNTUK AI AGENT

## WAJIB

- Pertahankan business logic yang sudah berjalan.
- Pertahankan route yang sudah ada.
- Pertahankan nama controller/action jika tidak perlu diubah.
- Pertahankan authorization / middleware / role.
- Pertahankan validasi backend.
- Jangan mengubah database schema hanya untuk kebutuhan UI.
- Jangan menghapus permission check.
- Jangan membuka menu/action yang tidak boleh diakses role tertentu.
- Pastikan setiap perubahan tetap responsive.
- Gunakan komponen reusable jika elemen dipakai lebih dari satu halaman.

## JANGAN

- Jangan redesign backend.
- Jangan rename route tanpa kebutuhan.
- Jangan membuat duplicate logic.
- Jangan hardcode permission hanya di frontend.
- Jangan menampilkan error Laravel mentah kepada user.
- Jangan menggunakan terlalu banyak warna random.
- Jangan membuat layout desktop bagus tetapi mobile rusak.
- Jangan membuat tombol aksi terlalu kecil di mobile.
- Jangan membuat modal yang memenuhi seluruh layar kecuali memang diperlukan.

---

# 1. TUJUAN VISUAL

SIMBA harus memiliki karakter:

```text
Modern
Clean
Friendly
Colorful
Professional
School-friendly
Easy to scan
Responsive
```

UI tidak boleh terasa:

```text
Flat
Monoton
Terlalu putih
Terlalu padat
Tabel seperti spreadsheet mentah
Navigasi membingungkan
```

---

# 2. STYLE DIRECTION

Gunakan pendekatan:

```text
Soft Color Dashboard
+
Modern Admin Panel
+
Rounded Cards
+
Clear Visual Hierarchy
+
Subtle Shadows
+
Readable Tables
+
Mobile-first Interaction
```

Rekomendasi:

- Border radius: `rounded-xl` dan `rounded-2xl`.
- Shadow: subtle, tidak terlalu kuat.
- Border: `border-slate-200`.
- Background utama: slate sangat terang.
- Card menggunakan putih.
- Accent menggunakan biru/indigo.
- Status menggunakan emerald/amber/red.
- Gunakan gradient hanya pada bagian tertentu seperti hero/card statistik, bukan seluruh halaman.

---

# 3. DESIGN TOKENS

AI agent sebaiknya menggunakan token yang konsisten.

## 3.1 Color Palette

### Primary

```text
Primary 50   : blue-50
Primary 100  : blue-100
Primary 500  : blue-500
Primary 600  : blue-600
Primary 700  : blue-700
```

### Secondary / Accent

```text
Indigo 500
Violet 500
Cyan 500
```

### Success

```text
emerald-50
emerald-100
emerald-500
emerald-600
emerald-700
```

### Warning

```text
amber-50
amber-100
amber-500
amber-600
amber-700
```

### Danger

```text
red-50
red-100
red-500
red-600
red-700
```

### Neutral

```text
slate-50
slate-100
slate-200
slate-400
slate-500
slate-600
slate-700
slate-800
slate-900
```

---

# 4. BACKGROUND APPLICATION

Body:

```html
bg-slate-50
```

Content area boleh menggunakan subtle gradient:

```html
bg-gradient-to-br from-slate-50 via-white to-blue-50/40
```

Jangan gunakan gradient pada semua card.

---

# 5. TYPOGRAPHY

Gunakan hierarchy berikut:

```text
Page Title
text-2xl md:text-3xl font-bold text-slate-900

Section Title
text-lg font-semibold text-slate-900

Card Value
text-2xl lg:text-3xl font-bold text-slate-900

Body
text-sm md:text-base text-slate-600

Helper
text-sm text-slate-500

Table Header
text-xs font-semibold uppercase tracking-wide text-slate-500
```

Jika project memungkinkan, gunakan font modern:

```text
Inter
Plus Jakarta Sans
Manrope
```

Jika tidak ingin menambah dependency, pertahankan default font Tailwind.

---

# 6. APP SHELL

Target layout desktop:

```text
┌──────────────────────────────────────────────────────────────┐
│ Sidebar          │ Topbar                                   │
│                  ├───────────────────────────────────────────┤
│ Dashboard        │ Breadcrumb                               │
│ Inventaris       │                                           │
│ Stok             │ Page Header + Action                     │
│ Laporan          │                                           │
│ User             │ KPI / Content                            │
│                  │                                           │
│                  │ Table / Form                             │
└──────────────────────────────────────────────────────────────┘
```

Target mobile:

```text
┌─────────────────────────────┐
│ ☰  SIMBA            🔔 👤   │
├─────────────────────────────┤
│ Page Title                  │
│ Description                 │
│                             │
│ [ Primary Action ]          │
│                             │
│ Content Cards               │
│                             │
│ Filter / Search             │
│                             │
│ Responsive Data             │
└─────────────────────────────┘
```

---

# 7. SIDEBAR — PRIORITY HIGH

## Desktop

Sidebar harus:

- Fixed.
- Lebar sekitar `w-64`.
- Background putih atau slate gelap yang konsisten.
- Logo area jelas.
- Menu dikelompokkan.
- Active state kuat.
- Icon + label.
- Footer user/profile optional.

Contoh visual:

```text
SIMBA
Sistem Inventaris

MAIN
● Dashboard

INVENTARIS
□ Data Barang
□ Kategori
□ Lokasi

STOK
□ Stok Barang
□ Barang Masuk
□ Barang Keluar

LAPORAN
□ Laporan Inventaris
□ Laporan Stok

SISTEM
□ Pengguna
□ Profil
```

---

# 8. SIDEBAR ACTIVE STATE

Gunakan:

```html
bg-blue-600
text-white
shadow-sm
```

atau soft style:

```html
bg-blue-50
text-blue-700
ring-1 ring-blue-100
```

Pastikan icon mengikuti state.

Jangan hanya mengubah warna teks.

---

# 9. SIDEBAR GROUP LABEL

Contoh:

```html
px-3 pt-5 pb-2
text-[11px]
font-bold
uppercase
tracking-[0.14em]
text-slate-400
```

---

# 10. MOBILE SIDEBAR

Pada mobile:

- Sidebar menjadi off-canvas drawer.
- Tombol hamburger di topbar.
- Drawer maksimal sekitar `w-[85vw]`.
- Backdrop semi transparan.
- Tombol close mudah disentuh.
- Setelah klik menu, drawer otomatis menutup.
- Body tidak boleh scroll di belakang drawer.

Target:

```text
tap area >= 44px
```

---

# 11. TOPBAR

Topbar desktop:

```text
Search optional
Notification
User Name
Role
Avatar
```

Mobile:

```text
Hamburger
Logo / Page Name
Notification
Avatar
```

Gunakan:

```html
sticky top-0 z-30
border-b border-slate-200
bg-white/90 backdrop-blur
```

---

# 12. USER PROFILE AREA

Gunakan avatar fallback:

```text
BS
```

untuk:

```text
Budi Santoso
```

Tampilkan role:

```text
Budi Santoso
Waka Sarpras
```

Role gunakan text kecil / badge soft.

---

# 13. PAGE HEADER

Semua halaman harus menggunakan komponen yang konsisten.

Desktop:

```text
Data Barang
Kelola data seluruh barang inventaris sekolah.

                                + Tambah Barang
```

Mobile:

```text
Data Barang
Kelola data seluruh barang inventaris sekolah.

[ + Tambah Barang ]
```

Mobile action harus full width atau cukup besar.

---

# 14. PAGE HEADER COMPONENT TODO

Buat:

```text
resources/views/components/page-header.blade.php
```

Properties:

```text
title
description
breadcrumb
action slot
```

Acceptance:

- Responsive.
- Action pindah ke bawah pada mobile.
- Tidak overflow.
- Jarak antar elemen konsisten.

---

# 15. BREADCRUMB

Gunakan pada:

- Create.
- Edit.
- Detail.
- Laporan.
- Settings.

Contoh:

```text
Dashboard / Barang / Edit
```

Mobile:

- Font lebih kecil.
- Boleh truncate item panjang.
- Tidak menyebabkan horizontal scroll.

---

# 16. DASHBOARD VISUAL REVISION

Dashboard harus lebih berwarna tetapi tetap clean.

Gunakan KPI cards:

```text
┌────────────────────┐
│ 📦 Total Barang    │
│ 1.248              │
│ +12 bulan ini      │
└────────────────────┘

┌────────────────────┐
│ 🗂 Total Kategori  │
│ 18                 │
└────────────────────┘

┌────────────────────┐
│ ⚠ Stok Menipis    │
│ 12                 │
└────────────────────┘

┌────────────────────┐
│ ⛔ Stok Habis      │
│ 4                  │
└────────────────────┘
```

---

# 17. KPI CARD COLOR

Gunakan warna berbeda namun tidak berlebihan.

Contoh:

```text
Total Barang
bg-gradient-to-br from-blue-500 to-indigo-600

Kategori
bg-gradient-to-br from-cyan-500 to-blue-500

Stok Menipis
bg-gradient-to-br from-amber-400 to-orange-500

Stok Habis
bg-gradient-to-br from-rose-500 to-red-600
```

Atau gunakan white card + icon colored background jika ingin lebih subtle.

---

# 18. MOBILE DASHBOARD

Grid:

```text
Mobile
grid-cols-1

>= 480px
grid-cols-2

Desktop
grid-cols-4
```

Jika KPI sederhana:

```text
mobile boleh 2 kolom
```

Pastikan value tidak terlalu kecil.

---

# 19. DASHBOARD ROLE AWARE

## Admin

Tampilkan:

- Total barang.
- Total kategori.
- Total user.
- Activity.
- Stok alert.

## Petugas

Tampilkan:

- Stok tersedia.
- Barang masuk hari ini.
- Barang keluar hari ini.
- Stok menipis.

## Waka Sarpras

Tampilkan:

- Total inventaris.
- Kondisi stok.
- Stok menipis.
- Laporan terbaru.

Jangan menampilkan action transaksi jika tidak memiliki permission.

---

# 20. TABLE — PRIORITY VERY HIGH

Target table tidak boleh terlihat seperti HTML table default.

Table container:

```html
rounded-2xl
border border-slate-200
bg-white
shadow-sm
overflow-hidden
```

---

# 21. TABLE TOOLBAR

Sebelum tabel tambahkan toolbar:

```text
┌──────────────────────────────────────────────────────┐
│ 🔎 Cari barang...     [Kategori ▼] [Status ▼]       │
│                                      [Reset] [Filter]│
└──────────────────────────────────────────────────────┘
```

Desktop: horizontal.

Mobile: stacked.

---

# 22. SEARCH INPUT

Gunakan icon search.

```text
[ 🔎 Cari kode atau nama barang... ]
```

Style:

```html
rounded-xl
border-slate-300
bg-white
focus:border-blue-500
focus:ring-blue-500
```

Mobile:

```html
w-full
```

---

# 23. TABLE HEADER

Gunakan:

```html
bg-slate-50/80
text-xs
uppercase
font-semibold
tracking-wide
```

Header tidak perlu terlalu gelap.

---

# 24. TABLE ROW

Gunakan:

```html
hover:bg-blue-50/40
transition-colors
```

Row height cukup nyaman.

Target:

```text
py-3.5 atau py-4
```

---

# 25. TABLE DATA HIERARCHY

Untuk nama barang:

```text
Laptop Lenovo ThinkPad
BRG-000128
```

Jadikan nama barang utama, kode sebagai secondary text.

Ini lebih mudah dibaca daripada semua data memiliki bobot yang sama.

---

# 26. TABLE BADGE

Status:

```text
Aman
Menipis
Habis
Aktif
Nonaktif
```

Contoh:

```html
inline-flex
items-center
rounded-full
px-2.5
py-1
text-xs
font-semibold
```

---

# 27. STOCK BADGE

```text
Aman
bg-emerald-50 text-emerald-700 ring-emerald-200

Menipis
bg-amber-50 text-amber-700 ring-amber-200

Habis
bg-red-50 text-red-700 ring-red-200
```

Tambahkan dot kecil jika perlu.

---

# 28. TABLE ACTIONS

Desktop:

```text
[Detail] [Edit] [⋮]
```

Jika action banyak:

```text
⋮
- Detail
- Edit
- Riwayat
- Cetak Label
---------
- Hapus
```

Hapus harus dipisahkan dari action normal.

---

# 29. MOBILE TABLE STRATEGY

JANGAN hanya shrink table desktop.

Gunakan salah satu:

## Option A — Horizontal Table

Cocok jika user harus membandingkan kolom.

```html
overflow-x-auto
min-w-[800px]
```

## Option B — Mobile Card

Lebih friendly untuk inventory.

Contoh:

```text
┌────────────────────────────┐
│ Laptop Lenovo ThinkPad     │
│ BRG-000128                 │
│                            │
│ Kategori     Elektronik    │
│ Lokasi       Lab Komputer  │
│ Stok         18            │
│ Status       ● Aman        │
│                            │
│ [Detail]        [⋮]        │
└────────────────────────────┘
```

Recommended:

```text
Desktop -> table
Mobile  -> card list
```

---

# 30. MOBILE DATA CARD

Gunakan:

```html
rounded-2xl
border
bg-white
p-4
shadow-sm
```

Actions:

```text
button height >= 40px
```

Jangan gunakan icon kecil 20px sebagai satu-satunya target klik.

---

# 31. EMPTY STATE

Empty state harus visual.

Contoh:

```text
        📦

Belum Ada Barang

Data barang belum tersedia.
Tambahkan barang pertama untuk mulai
mengelola inventaris.

[ + Tambah Barang ]
```

Jika hasil filter kosong:

```text
Tidak ada hasil

Tidak ditemukan barang untuk
"laptop".

[ Reset Filter ]
```

---

# 32. FORM CRUD — PRIORITY HIGH

Form harus terasa ringan.

Gunakan layout:

```text
Desktop max-w-3xl / max-w-4xl
Mobile full width
```

Card:

```html
rounded-2xl
border border-slate-200
bg-white
p-4 sm:p-6
shadow-sm
```

---

# 33. FORM SECTION

Jika field banyak, kelompokkan:

```text
Informasi Dasar
- Kode
- Nama
- Kategori

Informasi Inventaris
- Lokasi
- Kondisi
- Stok

Informasi Tambahan
- Deskripsi
- Catatan
```

Jangan tampilkan 15 input tanpa grouping.

---

# 34. FORM GRID

Desktop:

```text
grid-cols-2
```

Mobile:

```text
grid-cols-1
```

Field panjang seperti description:

```text
col-span-full
```

---

# 35. INPUT STYLE

Standard input:

```html
w-full
rounded-xl
border-slate-300
bg-white
px-3.5
py-2.5
text-sm
shadow-sm
focus:border-blue-500
focus:ring-blue-500
```

Error:

```html
border-red-400
focus:border-red-500
focus:ring-red-500
```

---

# 36. LABEL STYLE

```html
text-sm
font-semibold
text-slate-700
```

Required:

```text
Nama Barang *
```

`*` gunakan merah.

---

# 37. HELPER TEXT

Contoh:

```text
Kode barang dibuat otomatis oleh sistem.
```

Style:

```html
mt-1 text-xs text-slate-500
```

---

# 38. VALIDATION

Field error:

```text
Nama Barang *
[.........................]
Nama barang wajib diisi.
```

Gunakan icon warning optional.

Jangan hanya menampilkan seluruh error di top.

---

# 39. VALIDATION SUMMARY

Jika ada error:

```text
⚠ Data belum lengkap

Periksa kembali field yang ditandai merah.
```

Style warning/error soft.

---

# 40. STICKY FORM ACTION MOBILE

Untuk form panjang di mobile, gunakan sticky footer:

```text
┌─────────────────────────────┐
│ [ Batal ] [ Simpan Barang ] │
└─────────────────────────────┘
```

Style:

```html
sticky bottom-0
border-t
bg-white/95
backdrop-blur
```

Pastikan tidak menutup input terakhir.

---

# 41. BUTTON SYSTEM

## Primary

```html
bg-blue-600
hover:bg-blue-700
text-white
rounded-xl
```

## Secondary

```html
border
bg-white
text-slate-700
hover:bg-slate-50
```

## Danger

```html
bg-red-600
hover:bg-red-700
text-white
```

## Soft

```html
bg-blue-50
text-blue-700
hover:bg-blue-100
```

---

# 42. BUTTON SIZE MOBILE

Target minimum:

```text
height 44px
```

Action penting:

```html
w-full sm:w-auto
```

---

# 43. LOADING BUTTON

Setelah submit:

```text
Simpan Barang
↓
Menyimpan...
```

Disable selama submit.

Gunakan Alpine.js jika tersedia.

---

# 44. CRUD FEEDBACK — PRIORITY VERY HIGH

Semua action harus memberi feedback.

## Create

```text
✓ Barang berhasil ditambahkan
```

## Update

```text
✓ Data barang berhasil diperbarui
```

## Delete

```text
✓ Barang berhasil dihapus
```

## Reset Password

```text
✓ Password pengguna berhasil direset
```

## Stock In

```text
✓ Barang masuk berhasil dicatat
```

## Stock Out

```text
✓ Barang keluar berhasil dicatat
```

---

# 45. TOAST DESIGN

Gunakan toast kanan atas desktop.

Mobile:

```text
top
left/right 16px
full-ish width
```

Contoh:

```text
┌──────────────────────────────┐
│ ✓  Berhasil              ×  │
│ Barang berhasil ditambahkan. │
└──────────────────────────────┘
```

---

# 46. TOAST VISUAL

Success:

```text
icon bg emerald
border emerald
```

Error:

```text
icon bg red
border red
```

Warning:

```text
icon bg amber
border amber
```

Info:

```text
icon bg blue
border blue
```

Durasi:

```text
success 3500ms
info    4000ms
warning 5000ms
error   5500ms
```

---

# 47. TOAST COMPONENT TODO

Buat:

```text
resources/views/components/flash-toast.blade.php
```

Harus mendukung:

```text
success
error
warning
info
```

Harus:

- Bisa close manual.
- Auto close.
- Animasi masuk/keluar.
- Mobile friendly.
- Tidak menutupi navbar.

---

# 48. DELETE CONFIRMATION

Gunakan modal custom jika Alpine sudah tersedia.

Contoh:

```text
Hapus Barang?

Laptop Lenovo ThinkPad akan dihapus
dari sistem.

Tindakan ini tidak dapat dibatalkan.

[ Batal ] [ Ya, Hapus ]
```

---

# 49. DELETE MODAL MOBILE

Mobile:

- Modal hampir full width.
- Padding cukup.
- Button stacked jika ruang sempit.

```text
[ Ya, Hapus ]
[ Batal ]
```

Primary destructive action jelas tetapi tidak terlalu mudah terpencet.

---

# 50. RESET PASSWORD UX

Modal:

```text
Reset Password?

Nama
Budi Santoso

Role
Waka Sarpras

Password pengguna akan direset.

[ Batal ] [ Reset Password ]
```

Jika temporary password dihasilkan:

```text
Password sementara

A7D9-KP21

[ Salin Password ]
```

---

# 51. MODAL SYSTEM

Buat reusable:

```text
<x-confirm-modal />
```

Props:

```text
title
description
confirmLabel
cancelLabel
variant
```

Variant:

```text
danger
warning
primary
```

---

# 52. DETAIL PAGE

Jangan gunakan disabled input.

Gunakan definition-style card:

```text
Informasi Barang

Kode Barang
BRG-000128

Nama Barang
Laptop Lenovo ThinkPad

Kategori
Elektronik

Lokasi
Lab Komputer

Stok
18 Unit

Status
Aman
```

Desktop bisa 2 columns.

Mobile 1 column.

---

# 53. DETAIL HEADER

Desktop:

```text
Laptop Lenovo ThinkPad

BRG-000128

[ Edit ] [ Cetak Label ] [ ⋮ ]
```

Mobile:

```text
Laptop Lenovo ThinkPad
BRG-000128

[ Edit Barang ]
[ Aksi Lainnya ▼ ]
```

---

# 54. FILTER MOBILE

Jangan tampilkan 5 select berjajar.

Gunakan:

```text
[ 🔎 Cari barang... ]

[ Filter ]
```

Klik filter membuka:

```text
Bottom Sheet / Drawer

Kategori
[ Semua ▼ ]

Status
[ Semua ▼ ]

Lokasi
[ Semua ▼ ]

[ Reset ] [ Terapkan ]
```

Jika implementasi bottom sheet terlalu berat, stack filter vertikal.

---

# 55. FILTER ACTIVE CHIPS

Setelah filter aktif:

```text
Elektronik ×
Stok Menipis ×
Lab Komputer ×
```

User bisa menghapus satu filter tanpa reset semua.

Optional jika tidak terlalu kompleks.

---

# 56. PAGINATION

Desktop:

```text
Menampilkan 1-10 dari 124

< Previous  1 2 3 ... 13  Next >
```

Mobile:

```text
1-10 dari 124

[ Sebelumnya ] [ Berikutnya ]
```

Jangan tampilkan terlalu banyak nomor halaman di mobile.

---

# 57. RESPONSIVE BREAKPOINT STRATEGY

Gunakan pendekatan:

```text
default  = mobile
sm       = phone landscape / small tablet
md       = tablet
lg       = desktop
xl       = large desktop
```

Jangan design desktop dahulu lalu sekadar mengecilkan.

---

# 58. MOBILE GLOBAL RULES

Untuk width <= 640px:

- Main padding `p-4`.
- Card padding `p-4`.
- Button utama full width jika sendiri.
- Action toolbar stack.
- Table berubah card jika memungkinkan.
- Sidebar drawer.
- Modal hampir full width.
- Font page title `text-2xl`.
- Jangan ada horizontal scroll global.
- Touch target >= 44px.
- Dropdown tidak keluar viewport.

---

# 59. TABLE MOBILE RULES

Pada mobile:

```text
No hidden critical information.
No tiny action buttons.
No 8-column cramped table.
```

Prioritas field:

```text
Nama Barang
Kode
Stok
Status
Lokasi
```

Field sekunder bisa dipindah detail page.

---

# 60. ACCESSIBILITY

AI agent wajib mempertahankan:

- `label` untuk input.
- `aria-label` untuk icon-only button.
- Visible focus state.
- Kontras cukup.
- Keyboard navigation.
- Modal close via button.
- Jangan mengandalkan warna saja.

---

# 61. ICON SYSTEM

Pilih satu:

```text
Heroicons
atau
Lucide
```

Jangan campur icon packs.

Ukuran:

```text
Sidebar 20px
Button  18px
Card    20-24px
KPI     24px
```

---

# 62. ANIMATION

Gunakan subtle:

```text
transition-colors
duration-150
```

Modal:

```text
fade + scale ringan
```

Sidebar mobile:

```text
slide
```

Jangan gunakan animasi berat.

---

# 63. HOVER

Desktop:

- Card boleh sedikit naik.
- Button ganti warna.
- Row ganti background.
- Icon action punya hover.

Jangan bergantung pada hover untuk info penting karena mobile tidak punya hover.

---

# 64. ROLE-AWARE UI

AI agent harus audit setiap tombol:

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

Jika user tidak berhak:

```text
action jangan dirender.
```

Backend permission tetap wajib.

---

# 65. WAKA SARPRAS UX

Waka Sarpras diarahkan ke pengalaman:

```text
View
Review
Report
Monitor
```

Bukan:

```text
Transaction
Administrative modification
```

Jika hak akses project memang demikian.

Sidebar Waka:

```text
Dashboard
Data Inventaris
Stok
Laporan
Profil
```

Jangan tampilkan management user jika tidak boleh.

---

# 66. ERROR PAGE

Buat custom:

```text
403
404
500
```

Dengan visual friendly.

Contoh 403:

```text
🔒

Akses Ditolak

Anda tidak memiliki izin untuk
membuka halaman ini.

[ Kembali ke Dashboard ]
```

---

# 67. ERROR TECHNICAL HANDLING

Production:

Jangan tampilkan:

```text
SQLSTATE
Undefined variable
Stack trace
RouteNotFoundException
```

Tampilkan:

```text
Terjadi kendala saat memproses data.
Silakan coba kembali.
```

Tetap log error menggunakan Laravel logger/report.

---

# 68. ACTIVITY UI

Human readable:

```text
Budi Santoso
memperbarui Laptop Lenovo ThinkPad

8 Agustus 2026, 08:15
```

Gunakan timeline/list.

---

# 69. DATE DISPLAY

Gunakan:

```text
8 Agustus 2026
8 Agustus 2026, 08:15
```

Bukan ISO timestamp mentah.

---

# 70. NUMBER DISPLAY

Stok:

```text
1.250 unit
```

Currency jika ada:

```text
Rp 2.500.000
```

---

# 71. REUSABLE COMPONENTS TARGET

AI agent direkomendasikan membuat:

```text
resources/views/components/
├── app-logo.blade.php
├── badge.blade.php
├── breadcrumb.blade.php
├── button.blade.php
├── card.blade.php
├── confirm-modal.blade.php
├── empty-state.blade.php
├── flash-toast.blade.php
├── page-header.blade.php
├── sidebar-link.blade.php
├── stat-card.blade.php
├── table/
│   ├── container.blade.php
│   └── empty.blade.php
└── form/
    ├── input.blade.php
    ├── select.blade.php
    └── textarea.blade.php
```

Jangan membuat komponen hanya untuk 3 baris HTML yang hanya digunakan sekali.

---

# 72. FILE REVISION PRIORITY

AI agent sebaiknya audit file dalam urutan:

```text
1. layouts/app.blade.php
2. layouts/sidebar.blade.php
3. layouts/topbar.blade.php
4. dashboard.blade.php
5. items/index.blade.php
6. items/create.blade.php
7. items/edit.blade.php
8. items/show.blade.php
9. users/index.blade.php
10. users/create/edit
11. categories/*
12. stock/*
13. reports/*
14. error pages
```

Sesuaikan path actual project.

---

# 73. PHASE 1 — GLOBAL UI FOUNDATION

## TODO

- [ ] Audit layout utama.
- [ ] Ubah body background menjadi soft slate.
- [ ] Rapikan max width main content.
- [ ] Buat spacing global konsisten.
- [ ] Buat topbar sticky.
- [ ] Rapikan sidebar desktop.
- [ ] Buat mobile sidebar drawer.
- [ ] Implement active menu.
- [ ] Tampilkan role user dengan friendly.
- [ ] Tambah responsive container.
- [ ] Pastikan tidak ada global horizontal overflow.

## Acceptance Criteria

- Sidebar usable desktop dan mobile.
- Topbar tidak overlap content.
- Content memiliki spacing konsisten.
- Semua halaman mengikuti app shell yang sama.

---

# 74. PHASE 2 — DESIGN COMPONENTS

## TODO

- [ ] Buat `<x-page-header>`.
- [ ] Buat `<x-flash-toast>`.
- [ ] Buat `<x-badge>`.
- [ ] Buat `<x-empty-state>`.
- [ ] Buat `<x-confirm-modal>`.
- [ ] Buat `<x-stat-card>`.
- [ ] Buat sidebar link reusable jika berguna.
- [ ] Buat button style konsisten.

## Acceptance Criteria

- Tidak ada duplikasi besar untuk toast.
- Badge status konsisten.
- Delete modal dapat digunakan ulang.
- Header halaman konsisten.

---

# 75. PHASE 3 — DASHBOARD

## TODO

- [ ] Upgrade KPI cards.
- [ ] Tambahkan icon.
- [ ] Tambahkan warna status.
- [ ] Buat responsive grid.
- [ ] Tambahkan section stok perlu perhatian.
- [ ] Tambahkan activity recent jika data tersedia.
- [ ] Sesuaikan card berdasarkan role.

## Acceptance Criteria

- Dashboard tidak terlihat kosong.
- Mobile tetap mudah dibaca.
- Tidak ada card yang overflow.
- Waka hanya melihat data sesuai permission.

---

# 76. PHASE 4 — INDEX / TABLE

## TODO

- [ ] Bungkus table dalam modern card.
- [ ] Tambah toolbar.
- [ ] Rapikan search.
- [ ] Rapikan filter.
- [ ] Tambah status badge.
- [ ] Rapikan action.
- [ ] Tambah row hover.
- [ ] Tambah empty state.
- [ ] Rapikan pagination.
- [ ] Buat mobile card view jika memungkinkan.

## Acceptance Criteria

- Table nyaman dibaca.
- Tidak crowded.
- Action mudah ditemukan.
- Mobile tidak memaksa user zoom.
- Tidak ada action unauthorized.

---

# 77. PHASE 5 — CREATE / EDIT

## TODO

- [ ] Form dalam card.
- [ ] Group field.
- [ ] Responsive grid.
- [ ] Required indicator.
- [ ] Helper text.
- [ ] Error inline.
- [ ] Validation summary.
- [ ] Loading submit.
- [ ] Sticky mobile action jika form panjang.
- [ ] Tombol batal jelas.

## Acceptance Criteria

- Form nyaman di 375px.
- Tidak ada input overflow.
- Error terlihat dekat field.
- Submit tidak bisa double click.

---

# 78. PHASE 6 — CRUD NOTIFICATIONS

## TODO

- [ ] Standardisasi success session.
- [ ] Standardisasi error session.
- [ ] Tambah toast success.
- [ ] Tambah toast error.
- [ ] Tambah toast warning/info.
- [ ] Tambah animasi.
- [ ] Tambah auto close.
- [ ] Tambah close manual.
- [ ] Test di mobile.

## Acceptance Criteria

Setelah:

```text
Create
Update
Delete
Reset Password
Stock In
Stock Out
```

user selalu mendapat feedback.

---

# 79. PHASE 7 — CONFIRMATIONS

## TODO

- [ ] Delete confirmation.
- [ ] Reset password confirmation.
- [ ] Stock destructive confirmation jika perlu.
- [ ] Nonaktifkan user confirmation jika ada.
- [ ] Gunakan modal reusable.

## Acceptance Criteria

- Tidak ada destructive action sekali klik.
- Modal tidak keluar viewport mobile.
- Action label spesifik.

---

# 80. PHASE 8 — DETAIL PAGES

## TODO

- [ ] Hapus tampilan seperti disabled form.
- [ ] Gunakan info card.
- [ ] Buat key-value layout.
- [ ] Tambah badge.
- [ ] Tambah action header.
- [ ] Responsive mobile.

---

# 81. PHASE 9 — RESPONSIVE AUDIT

Test:

```text
375 x 667
390 x 844
430 x 932
768 x 1024
1024 x 768
1366 x 768
1440 x 900
```

## TODO

- [ ] Sidebar mobile.
- [ ] Topbar.
- [ ] Dashboard cards.
- [ ] Table.
- [ ] Mobile cards.
- [ ] Forms.
- [ ] Select.
- [ ] Dropdown.
- [ ] Modal.
- [ ] Toast.
- [ ] Pagination.
- [ ] Long text.
- [ ] Empty state.

---

# 82. MOBILE UX ACCEPTANCE

Pada width 375px:

- [ ] Tidak ada horizontal page scroll.
- [ ] Semua primary button mudah disentuh.
- [ ] Drawer sidebar berfungsi.
- [ ] Toast tidak keluar layar.
- [ ] Modal muat.
- [ ] Input full width.
- [ ] Label tidak terpotong.
- [ ] Action tidak terlalu kecil.
- [ ] Table tidak menjadi sangat sempit.
- [ ] Pagination usable.
- [ ] Header action tidak overlap title.

---

# 83. DESKTOP UX ACCEPTANCE

Pada width 1366px:

- [ ] Sidebar tidak terlalu besar.
- [ ] Main content tidak menempel sidebar.
- [ ] Table menggunakan area dengan baik.
- [ ] Form tidak terlalu lebar.
- [ ] Dashboard cards balance.
- [ ] Header/action sejajar.
- [ ] Whitespace cukup.

---

# 84. COLOR REVIEW CHECKLIST

- [ ] Primary action biru.
- [ ] Success hijau.
- [ ] Warning kuning/orange.
- [ ] Danger merah.
- [ ] Neutral slate.
- [ ] Maksimal 1-2 accent utama per screen.
- [ ] Tidak semua card menggunakan gradient.
- [ ] Text memiliki contrast cukup.
- [ ] Status tidak hanya dibedakan warna.

---

# 85. NAVIGATION REVIEW CHECKLIST

- [ ] Group menu jelas.
- [ ] Active menu jelas.
- [ ] Icon konsisten.
- [ ] Role tidak melihat menu terlarang.
- [ ] Mobile drawer.
- [ ] Menu label tidak ambigu.
- [ ] Sidebar tidak terlalu penuh.
- [ ] Profile mudah ditemukan.

---

# 86. TABLE REVIEW CHECKLIST

- [ ] Search mudah ditemukan.
- [ ] Filter mudah digunakan.
- [ ] Header jelas.
- [ ] Hover row.
- [ ] Badge status.
- [ ] Action tidak crowded.
- [ ] Empty state.
- [ ] Pagination.
- [ ] Mobile strategy.
- [ ] Permission respected.

---

# 87. FORM REVIEW CHECKLIST

- [ ] Label.
- [ ] Required.
- [ ] Placeholder helpful.
- [ ] Helper text.
- [ ] Validation error.
- [ ] Correct old input.
- [ ] Responsive layout.
- [ ] Submit loading.
- [ ] Cancel action.
- [ ] Success feedback.

---

# 88. CRUD REVIEW CHECKLIST

Create:

- [ ] Submit.
- [ ] Success toast.
- [ ] Error feedback.
- [ ] Redirect clear.

Read:

- [ ] Friendly detail.
- [ ] Badge.
- [ ] Action by permission.

Update:

- [ ] Pre-filled data.
- [ ] Inline errors.
- [ ] Success toast.

Delete:

- [ ] Confirmation.
- [ ] Success toast.
- [ ] Error handling.

---

# 89. AI AGENT IMPLEMENTATION RULE

Setiap kali merevisi file, agent harus:

```text
1. Baca file terkait.
2. Identifikasi business logic.
3. Jangan ubah logic jika tidak perlu.
4. Pisahkan UI changes dari logic changes.
5. Pastikan permission tetap.
6. Implement UI.
7. Cek responsive class.
8. Cek Blade syntax.
9. Cek route reference.
10. Cek session notification.
```

---

# 90. AI AGENT TEST RULE

Setelah satu modul selesai:

```text
php artisan route:list
php artisan view:clear
php artisan cache:clear
```

Jika project punya automated checker/tools, jalankan juga.

Jangan menganggap UI benar hanya karena tidak ada syntax error.

---

# 91. BLADE SAFETY

Pastikan:

- `@csrf` tidak hilang.
- `@method('PUT')` tidak hilang.
- `@method('DELETE')` tidak hilang.
- `old()` tetap digunakan.
- `$errors` tetap digunakan.
- `route()` tetap valid.
- Permission directive tidak hilang.

---

# 92. CONTROLLER SAFETY

Jika mengubah notification:

Tidak boleh mengubah hasil business logic.

Contoh aman:

```php
return redirect()
    ->route('items.index')
    ->with('success', 'Barang berhasil ditambahkan.');
```

---

# 93. STANDARD CRUD MESSAGES

## Barang

```text
Barang berhasil ditambahkan.
Data barang berhasil diperbarui.
Barang berhasil dihapus.
```

## User

```text
Pengguna berhasil ditambahkan.
Data pengguna berhasil diperbarui.
Pengguna berhasil dihapus.
Password pengguna berhasil direset.
```

## Kategori

```text
Kategori berhasil ditambahkan.
Kategori berhasil diperbarui.
Kategori berhasil dihapus.
```

## Stock

```text
Barang masuk berhasil dicatat.
Barang keluar berhasil dicatat.
Penyesuaian stok berhasil disimpan.
```

---

# 94. EXAMPLE MODERN CARD

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">
                Total Barang
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format($totalItems) }}
            </p>
        </div>

        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
            {{-- icon --}}
        </div>
    </div>
</div>
```

---

# 95. EXAMPLE PRIMARY BUTTON

```blade
<a
    href="{{ route('items.create') }}"
    class="
        inline-flex min-h-11 items-center justify-center gap-2
        rounded-xl bg-blue-600 px-4 py-2.5
        text-sm font-semibold text-white
        shadow-sm transition
        hover:bg-blue-700
        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
    "
>
    Tambah Barang
</a>
```

---

# 96. EXAMPLE TABLE WRAPPER

```blade
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            ...
        </table>
    </div>
</div>
```

---

# 97. EXAMPLE MOBILE CARD

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate font-semibold text-slate-900">
                {{ $item->name }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                {{ $item->code }}
            </p>
        </div>

        <x-badge :status="$item->stock_status" />
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-slate-500">Kategori</dt>
            <dd class="mt-1 font-medium text-slate-800">
                {{ $item->category->name ?? '-' }}
            </dd>
        </div>

        <div>
            <dt class="text-slate-500">Stok</dt>
            <dd class="mt-1 font-medium text-slate-800">
                {{ $item->stock }}
            </dd>
        </div>
    </dl>
</div>
```

---

# 98. BEFORE / AFTER TARGET

## Before

```text
Plain white
Thin layout
Basic HTML table
Buttons mixed colors
No CRUD feedback
Mobile requires horizontal scrolling everywhere
Sidebar difficult to scan
```

## After

```text
Soft colored background
Modern cards
Clear primary color
Status badges
Toast feedback
Responsive navigation
Mobile card data
Consistent buttons
Friendly empty states
Clear page hierarchy
```

---

# 99. AGENT TASK TEMPLATE

Gunakan task prompt ini ketika memberikan pekerjaan ke AI agent:

```text
Revise UI/UX modul [NAMA MODUL] mengikuti
SIMBA_AI_AGENT_UI_UX_REVISION_MASTER_PLAN.md.

Rules:
- Jangan mengubah business logic.
- Jangan mengubah route kecuali benar-benar dibutuhkan.
- Jangan menghapus permission checks.
- Pertahankan validasi backend.
- Fokus pada visual hierarchy, responsive, table/form UX,
  toast CRUD, empty state, dan mobile experience.
- Gunakan reusable Blade components bila relevan.
- Pastikan tampilan 375px tidak horizontal overflow.
- Setelah perubahan, cek Blade syntax dan route reference.
- Laporkan file yang diubah dan alasan singkat tiap perubahan.
```

---

# 100. AGENT TODO FORMAT PER MODUL

AI agent harus menulis progress seperti:

```text
[✓] Page header
[✓] Search bar
[✓] Filter
[✓] Table styling
[✓] Status badge
[✓] Mobile card
[✓] Pagination
[✓] Empty state
[✓] CRUD toast
[✓] Delete confirmation
[✓] Permission audit
[✓] Responsive audit
```

Jika ada yang tidak diterapkan:

```text
[ ] Mobile card
Reason: current table requires multi-column comparison,
so horizontal responsive table retained.
```

---

# 101. PRIORITY MATRIX

## P0 — Harus Dikerjakan

- Responsive sidebar.
- Friendly table.
- Friendly mobile table/card.
- CRUD toast.
- Delete confirmation.
- Form validation.
- Permission-safe actions.
- No horizontal overflow.

## P1 — Sangat Disarankan

- Colored dashboard cards.
- Page header.
- Badge.
- Empty state.
- Responsive filters.
- Loading button.

## P2 — Enhancement

- Filter chips.
- Activity timeline.
- Notification center.
- Skeleton loading.
- Bottom sheet filter.

---

# 102. DEFINITION OF DONE

UI revision dianggap selesai jika:

- [ ] Tampilan terasa modern.
- [ ] Warna konsisten.
- [ ] Navigation jelas.
- [ ] Active state jelas.
- [ ] Dashboard lebih hidup.
- [ ] Table mudah dibaca.
- [ ] Mobile table friendly.
- [ ] Search/filter mudah.
- [ ] Form mudah diisi.
- [ ] Validation friendly.
- [ ] Semua CRUD memberi feedback.
- [ ] Delete memiliki confirmation.
- [ ] Toast responsive.
- [ ] Role/permission tetap aman.
- [ ] Tidak ada horizontal overflow mobile.
- [ ] Tidak ada error teknis ditampilkan ke user.
- [ ] UI konsisten antar modul.

---

# 103. FINAL UI VISION

Target akhir SIMBA:

```text
┌───────────────────────────────┐
│ Clean Navigation              │
├───────────────────────────────┤
│ Colorful but Professional     │
├───────────────────────────────┤
│ Clear Dashboard               │
├───────────────────────────────┤
│ Friendly Tables               │
├───────────────────────────────┤
│ Easy Forms                    │
├───────────────────────────────┤
│ CRUD Feedback                 │
├───────────────────────────────┤
│ Responsive Mobile Experience  │
├───────────────────────────────┤
│ Safe Role-based Actions       │
└───────────────────────────────┘
```

SIMBA harus terasa seperti aplikasi yang mudah digunakan setiap hari oleh staf sekolah, bukan sekadar halaman CRUD Laravel.

---

# 104. RECOMMENDED FIRST SPRINT

AI agent sebaiknya mulai dari:

```text
SPRINT 1

1. App Layout
2. Sidebar
3. Topbar
4. Page Header
5. Toast
6. Button System
7. Badge
```

Kemudian:

```text
SPRINT 2

1. Items Index
2. Items Create
3. Items Edit
4. Items Detail
5. Mobile Items UI
```

Kemudian:

```text
SPRINT 3

1. Users
2. Categories
3. Stock
4. Reports
```

Terakhir:

```text
SPRINT 4

1. Dashboard role-aware
2. Error pages
3. Responsive audit
4. UI consistency audit
5. Permission UI audit
```

---

# 105. INSTRUKSI FINAL UNTUK AI AGENT

Jika dokumen ini diberikan kepada AI coding agent:

> Kerjakan revisi secara bertahap. Prioritaskan konsistensi dan usability. Jangan melakukan rewrite besar yang tidak diperlukan. Jangan mengubah business logic yang sudah benar. Setiap modul yang selesai harus diperiksa pada desktop dan mobile. Semua aksi CRUD harus memiliki feedback visual dan semua destructive action harus memiliki confirmation. Permission dan role yang sudah ada tidak boleh rusak.

