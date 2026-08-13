<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk business rule deadline peminjaman SIMBA.
 * Tidak butuh database — menguji logika Carbon langsung.
 */
class LoanDeadlineTest extends TestCase
{
    private function defaultDue(Carbon $borrowedAt): Carbon
    {
        return $borrowedAt->copy()->addDays(3)->setTime(15, 0, 0);
    }

    private function customDue(
        string $role,
        Carbon $borrowedAt,
        ?string $dueDate,
        ?string $dueTime
    ): Carbon {
        $canCustomize = in_array($role, ['admin', 'toolman'], true);

        if ($canCustomize && $dueDate && $dueTime) {
            $custom = Carbon::createFromFormat(
                'Y-m-d H:i',
                "$dueDate $dueTime",
                'Asia/Jakarta'
            );

            if ($custom->lte($borrowedAt)) {
                throw new \InvalidArgumentException('Jatuh tempo harus setelah waktu peminjaman.');
            }

            return $custom;
        }

        return $this->defaultDue($borrowedAt);
    }

    // TEST 1: Siswa pinjam 08:00 → due 3 hari 15:00
    public function test1_siswa_pinjam_pagi_due_3_hari_15_00(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 8, 20, 0, 'Asia/Jakarta');
        $due = $this->defaultDue($borrowed);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 2: Jam berbeda, deadline tetap 3 hari 15:00
    public function test2_jam_berbeda_deadline_tetap_3_hari_15_00(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 13, 30, 0, 'Asia/Jakarta');
        $due = $this->defaultDue($borrowed);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 3: Pinjam sore pukul 16:10, deadline tetap 3 hari 15:00
    public function test3_pinjam_sore_deadline_tetap_3_hari_15_00(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 16, 10, 0, 'Asia/Jakarta');
        $due = $this->defaultDue($borrowed);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 4: Siswa tidak bisa custom due (role 'siswa' diabaikan)
    public function test4_siswa_tidak_bisa_custom_due(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 10, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('siswa', $borrowed, '2099-12-31', '23:59');

        // Harus default, bukan 2099
        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 5: Toolman tanpa custom → default +3 hari 15:00
    public function test5_toolman_tanpa_custom_due_default(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('toolman', $borrowed, null, null);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 6: Admin tanpa custom → default +3 hari 15:00
    public function test6_admin_tanpa_custom_due_default(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('admin', $borrowed, null, null);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 7: Toolman custom deadline valid
    public function test7_toolman_custom_due_valid(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('toolman', $borrowed, '2026-08-20', '17:00');

        $this->assertEquals('2026-08-20 17:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 8: Admin custom deadline valid
    public function test8_admin_custom_due_valid(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('admin', $borrowed, '2026-08-25', '10:00');

        $this->assertEquals('2026-08-25 10:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 9: Custom due sebelum borrowed_at → exception
    public function test9_custom_due_sebelum_borrowed_at_gagal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $borrowed = Carbon::create(2026, 8, 11, 10, 0, 0, 'Asia/Jakarta');
        $this->customDue('toolman', $borrowed, '2026-08-10', '08:00');
    }

    // TEST 10: Pengembalian sebelum due_at → tidak terlambat
    public function test10_pengembalian_sebelum_due_tidak_terlambat(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->defaultDue($borrowed);

        $returnedAt = Carbon::create(2026, 8, 14, 14, 59, 0, 'Asia/Jakarta');

        $this->assertTrue($returnedAt->lt($due), 'Pengembalian sebelum due_at seharusnya tidak terlambat');
    }

    // TEST 11: Pengembalian setelah due_at → terlambat
    public function test11_pengembalian_setelah_due_terlambat(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->defaultDue($borrowed);

        $returnedAt = Carbon::create(2026, 8, 14, 15, 1, 0, 'Asia/Jakarta');

        $this->assertTrue($returnedAt->gt($due), 'Pengembalian setelah due_at seharusnya terlambat');
    }

    // TEST: Guru tidak bisa custom due
    public function test_guru_tidak_bisa_custom_due(): void
    {
        $borrowed = Carbon::create(2026, 8, 11, 8, 0, 0, 'Asia/Jakarta');
        $due = $this->customDue('guru', $borrowed, '2099-12-31', '23:59');

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }
}
