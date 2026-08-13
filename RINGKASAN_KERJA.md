# SIMBA — Ringkasan Kerja Sesi & Rekomendasi Fitur

> Tanggal: 2026-08-13

---

# 1. Ringkasan Kerja yang Sudah Selesai

## 1.1 Perbaikan Flow Import Barang Masuk (Stock Import)

### Bug yang diperbaiki
| # | Bug | Dampak | Fix |
|---|---|---|---|
| 1 | `StoreStockReceiptRequest::authorize()` hanya `admin,toolman` | Import kepala_bengkel selalu gagal "Tidak ada data valid" | Tambah role `kepala_bengkel` |
| 2 | Label turunan template `[Turunan] X (kode) > Y` tak cocok match lokasi | Lokasi salah/misassign stok & aset secara senyap | Parse label di `resolveLocation()` + hard-fail jika non-kosong tak ketemu |
| 3 | Re-upload dokumen sama tanpa guard | Inflasi stok & aset ganda | Cegah duplikat berdasar `reference_number` + `workshop_id` |
| 4 | CSV UTF-8 BOM | Header jadi `\ufeffnomor_...`, file kosong | Strip BOM di `readRows()` |
| 5 | Desimal koma `1,5` | Qty jadi `1.0` / harga gagal `numeric` | Normalisasi `,` → `.` |
| 6 | `harga_unit` kosong (string `''`) | Satu baris gagalkan seluruh dokumen | Kosong → `null` |

### Akses role menu import
- Dibuka untuk `admin`, `toolman`, `kepala_bengkel` (route + middleware + controller + sidebar).
- `kepala_bengkel` opengimport kini berfungsi.

### Sidebar / role-menu-guard
- Sumber 403/menu hilang ternyata di `app/Support/SimbaRoleAccess.php` + `resources/views/layouts/role-menu-guard.blade.php`.
- `toolman` & `kepala_bengkel` kini bisa membuka menu **Lokasi Penyimpanan** (block lokasi dihapus).
- Aksi kelola lokasi (create/edit/toggle/destroy) tetap diblok untuk non-admin.

### Referensi & template import per jurusan
- Admin: lihat semua jurusan.
- `toolman` & `kepala_bengkel`: hanya jurusan akun (workshop, lokasi) — master barang tetap katalog global.
- Lokasi ditampilkan **hierarkis induk → turunan** di halaman Referensi & sheet REFERENSI Excel (label `[Induk]`/`[Turunan]`).

## 1.2 Sorting asc/desc + Pagination 10/25/100 (Semua Tabel Index)

### Infrastruktur baru
- `app/Traits/SortsIndex.php` — whitelist sort + per-page 10/25/100 (aman dari nilai acak).
- `resources/views/components/sortable-header.blade.php` — header bisa urut ↑/↓, pertahankan filter query.
- `resources/views/components/per-page-selector.blade.php` — dropdown baris/halaman (10/25/100).

### Tabel yang di-wire (18 controller + view)
| Kelompok | Tabel | Contoh kolom sort |
|---|---|---|
| Master | Item, Kategori, Satuan, Jurusan, Siswa | code, name, type, stock |
| Stok | Barang Masuk, Barang Keluar, Pergerakan, Workflow | receipt_code, transaction_date, quantity |
| Ops | Peminjaman, Pengembalian, Kerusakan, Unit Aset | code, reported_at, asset_number |
| Admin | Lokasi, Audit Log (2), User, Error Log | code, created_at, name, role |

### Titik perbaikan
- `->paginate($perPage)` mengganti hardcode (20/15/30, dst).
- Default `orderBy` dipertahankan; sort user override lewat `->when($sort !== null, ...)`.
- `sort` / `direction` diteruskan via `withQueryString()` dan hidden input di form filter.
- Verifikasi: `php -l` semua controller bersih, `artisan view:cache` sukses (semua blade kompilasi), config/route/view cache dibersihkan.

---

# 2. Rekomendasi Fitur Tambahan

## 2.1 Prioritas Tinggi (Dampak Bisnis)
1. **Bulk Actions** — pilih banyak data lalu hapus/ubah status/export sekaligus.
2. **Export dengan Filter Aktif** — PDF/Excel memakai search + filter + sort yang sedang aktif.
3. **Peringatan Stok Menipis** — notifikasi saat `stock <= minimum_stock`.
4. **Deteksi Duplikat** — peringatan jika kode/barcode master sudah terpakai.

## 2.2 Prioritas Sedang (Pengalaman Pengguna)
5. **Pencarian Lanjutan** — rentang tanggal, multi-seleksi, filter AND/OR.
6. **Kolom Tabel Bisa Diatur** — tampil/sembunyi kolom, simpan preferensi user.
7. **Riwayat & Undo** — lacak dan bisa memulihkan penyesuaian stok.
8. **Pratinjau Foto** — lihat foto sebelum upload.

## 2.3 Prioritas Rendah (Nice to Have)
9. **Export QR massal** ke CSV/Excel.
10. **Dashboard Widget customizable** — grafik stok, mutasi, peminjaman.
11. **Aplikasi Mobile** — native Android/iOS dengan sinkronisasi offline.
12. **Dokumentasi API** — Swagger/OpenAPI.

## 2.4 Quick Wins (Mudah & Cepat)
13. **Shortcut Keyboard** — `Ctrl+F` cari, `Esc` tutup modal.
14. **Cetak Bukti Barang Masuk** — langsung PDF tanpa buka halaman detail.
15. **Salin Kode sekali klik** — item code / receipt code.
16. **Badge waktu update** — timestamp terakhir pada kartu/tabel.

---

# 3. Catatan / Skip Sementara
- Kolom relasi (kategori/satuan/workshop) sengaja belum dibuat sortable di beberapa tabel — tambah jika dibutuhkan.
- `receipt_date` import hanya ambil baris pertama dokumen; `tanggal` belum divalidasi format — tambah jika multi-tanggal per dokumen dipakai.
- Per-page selector hanya di footer desktop untuk tabel yang punya footer mobile.