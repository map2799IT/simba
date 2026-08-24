# Panduan Update ke Hosting

## Perubahan Terbaru (24 Agustus 2026)

### Commit `cd0c3fa` — sudah di GitHub
- Export laporan diubah dari CSV → **XLSX** (angka tidak salah baca Excel)
- Fix double-encoding URL export (filter tahun tidak hilang saat klik tombol Excel/PDF)
- Hapus trailing zeros di semua tampilan jumlah (`,00` → integer)
- Tambah tahun/periode di judul PDF dan nama file

### Uncommitted — belum di GitHub
- Fix kolom **Jurusan** kosong di Laporan Barang Masuk/Keluar
  - Fallback ke `item_assets.workshop` jika `items.workshop_id` NULL
  - Berlaku di tampilan web, XLSX export, dan PDF export

---

## Langkah Update Hosting

### 1. Commit & Push perubahan terbaru

Jalankan di terminal lokal (`C:\xampp\htdocs\simba`):

```bash
git add app/Http/Controllers/WorkshopAwareInventoryReportController.php
git add app/Http/Controllers/WorkshopAwareInventoryReportExportController.php
git add resources/views/reports/index.blade.php
git commit -m "Fix: fallback workshop code from item_assets when items.workshop_id is NULL"
git push origin main
```

---

### 2. Login ke hosting via SSH

```bash
ssh username@ip_server
cd /path/to/simba
```

Jika hosting pakai cPanel, buka **Terminal** di cPanel.

---

### 3. Pull perubahan dari GitHub

```bash
git pull origin main
```

---

### 4. Install/update dependencies (jika ada perubahan composer.json)

```bash
composer install --no-dev --optimize-autoloader
```

> Lewati jika tidak ada perubahan `composer.json`.

---

### 5. Build assets (jika ada perubahan JS/CSS)

```bash
npm ci
npm run build
```

> Lewati jika tidak ada perubahan file di `resources/`.

---

### 6. Jalankan migration (jika ada migration baru)

```bash
php artisan migrate --force
```

> Perubahan kali ini **tidak ada migration baru** — langkah ini opsional.

---

### 7. Clear cache

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

---

### 8. Fix data `items.workshop_id` (jalankan sekali)

Item lama di database punya `workshop_id = NULL` sehingga kolom Jurusan
di Barang Masuk kosong. Jalankan SQL ini di phpMyAdmin hosting:

```sql
UPDATE items i
INNER JOIN (
    SELECT item_id, MIN(workshop_id) AS workshop_id
    FROM item_assets
    WHERE workshop_id IS NOT NULL
    GROUP BY item_id
) ia ON ia.item_id = i.id
SET i.workshop_id = ia.workshop_id
WHERE i.workshop_id IS NULL;
```

Verifikasi:
```sql
SELECT COUNT(*) FROM items WHERE workshop_id IS NULL;
-- Harusnya 0 atau hanya item yang memang tidak punya jurusan
```

---

### 9. Verifikasi

1. Buka halaman **Laporan → Laporan Inventaris**
2. Klik tab **Barang Masuk** → pastikan kolom **Jurusan** terisi
3. Klik tombol **Excel** → file `.xlsx` terunduh (bukan `.csv`)
4. Buka file Excel → nilai Harga Satuan dan Nilai Inventaris tampil sebagai angka benar
5. Coba filter **Tahun = 2026** → klik Excel/PDF → file nama mengandung `2026`

---

## Rollback

Jika terjadi masalah:

```bash
git log --oneline -5        # lihat commit sebelumnya
git revert HEAD             # batalkan commit terakhir
php artisan optimize:clear
```

---

## Catatan

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/WorkshopAwareInventoryReportController.php` | Eager load `item.itemAssets.workshop` |
| `app/Http/Controllers/WorkshopAwareInventoryReportExportController.php` | Helper `workshopCode()`, fix XLSX export |
| `resources/views/reports/index.blade.php` | Helper `$workshopCode()` dengan fallback |
| `app/Http/Controllers/InventoryReportExportController.php` | CSV → XLSX |
| `app/Http/Controllers/ItemImportController.php` | Fix parsing angka ribuan Indonesia |
| `resources/views/components/button.blade.php` | Fix double HTML-encoding URL |
| 9 file views lainnya | Strip trailing zeros, tahun di judul PDF |
