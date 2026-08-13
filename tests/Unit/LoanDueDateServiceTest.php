<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\LoanDueDateService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\ValidationException;

class LoanDueDateServiceTest extends TestCase
{
    private LoanDueDateService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new LoanDueDateService();
    }

    private function makeUser(string $role): User
    {
        $u = new User();
        $u->role = $role;
        return $u;
    }

    // ── ACCEPTANCE TESTS ──────────────────────────────────────────────────────

    // TEST 1: Siswa pinjam 09:00 → due 11 Agu 15:00 (HARI YANG SAMA)
    public function test1_siswa_due_hari_yang_sama_pukul_15(): void
    {
        $actor   = $this->makeUser('siswa');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $this->assertEquals('2026-08-11 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 2: Siswa TIDAK mendapat +3 hari
    public function test2_siswa_tidak_plus_3_hari(): void
    {
        $actor   = $this->makeUser('siswa');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $this->assertNotEquals('2026-08-14', $due->format('Y-m-d'));
    }

    // TEST 3: Siswa manipulasi due_at → diabaikan, hari yang sama 15:00
    public function test3_siswa_manipulasi_custom_due_diabaikan(): void
    {
        $actor   = $this->makeUser('siswa');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        // Siswa mengirim due_date & due_time palsu — harus diabaikan
        $due = $this->svc->calculate($actor, $borrower, $borrowed, '2026-08-20', '23:59');

        $this->assertEquals('2026-08-11 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 4: Siswa setelah 15:00 harus ditolak
    public function test4_siswa_setelah_15_00_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $now = Carbon::create(2026, 8, 11, 15, 1, 0, 'Asia/Jakarta');
        $this->svc->validateSiswaBorrowTime($now);
    }

    // TEST 4b: Siswa tepat 15:00 harus ditolak
    public function test4b_siswa_tepat_15_00_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $now = Carbon::create(2026, 8, 11, 15, 0, 0, 'Asia/Jakarta');
        $this->svc->validateSiswaBorrowTime($now);
    }

    // TEST 4c: Siswa sebelum 15:00 diizinkan
    public function test4c_siswa_sebelum_15_00_diizinkan(): void
    {
        $now = Carbon::create(2026, 8, 11, 14, 59, 0, 'Asia/Jakarta');
        // Should not throw
        $this->svc->validateSiswaBorrowTime($now);
        $this->assertTrue(true);
    }

    // TEST 5: Guru pinjam 09:00 → due 14 Agu 09:00 (+3 hari, jam sama)
    public function test5_guru_plus_3_hari(): void
    {
        $actor   = $this->makeUser('guru');
        $borrower = $this->makeUser('guru');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $this->assertEquals('2026-08-14 09:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 5b: Guru TIDAK mendapat hari yang sama 15:00
    public function test5b_guru_bukan_hari_yang_sama(): void
    {
        $actor   = $this->makeUser('guru');
        $borrower = $this->makeUser('guru');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $this->assertNotEquals('2026-08-11', $due->format('Y-m-d'));
    }

    // TEST 6: Toolman membuat untuk Guru → custom due_at digunakan
    public function test6_toolman_custom_due_untuk_guru(): void
    {
        $actor   = $this->makeUser('toolman');
        $borrower = $this->makeUser('guru');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed, '2026-08-20', '12:00');

        $this->assertEquals('2026-08-20 12:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 6b: Toolman membuat untuk Siswa → custom due_at digunakan (bukan aturan siswa)
    public function test6b_toolman_custom_due_untuk_siswa(): void
    {
        $actor   = $this->makeUser('toolman');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed, '2026-08-15', '17:00');

        $this->assertEquals('2026-08-15 17:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST 7: Toolman memilih borrower — canSetCustomDueDate returns true
    public function test7_toolman_dapat_set_custom_due(): void
    {
        $actor = $this->makeUser('toolman');
        $this->assertTrue($this->svc->canSetCustomDueDate($actor));
    }

    // TEST 8: Admin lintas jurusan — canSetCustomDueDate returns true
    public function test8_admin_dapat_set_custom_due(): void
    {
        $actor = $this->makeUser('admin');
        $this->assertTrue($this->svc->canSetCustomDueDate($actor));
    }

    // TEST 8b: Siswa/Guru tidak dapat set custom due
    public function test8b_siswa_guru_tidak_dapat_custom_due(): void
    {
        $this->assertFalse($this->svc->canSetCustomDueDate($this->makeUser('siswa')));
        $this->assertFalse($this->svc->canSetCustomDueDate($this->makeUser('guru')));
    }

    // TEST 9: Custom due sebelum/sama dengan borrowed_at → InvalidArgumentException
    public function test9_custom_due_invalid_sebelum_borrowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $actor   = $this->makeUser('toolman');
        $borrower = $this->makeUser('guru');
        $borrowed = Carbon::create(2026, 8, 11, 10, 0, 0, 'Asia/Jakarta');

        $this->svc->calculate($actor, $borrower, $borrowed, '2026-08-10', '08:00');
    }

    // TEST 10: Status overdue — now > due_at
    public function test10_overdue_now_after_due(): void
    {
        $actor   = $this->makeUser('siswa');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $returnedAt = Carbon::create(2026, 8, 11, 15, 1, 0, 'Asia/Jakarta');
        $this->assertTrue($returnedAt->gt($due), 'Kembali 15:01 seharusnya terlambat');
    }

    // TEST 10b: Tidak overdue — now <= due_at
    public function test10b_not_overdue_before_due(): void
    {
        $actor   = $this->makeUser('siswa');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');
        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $returnedAt = Carbon::create(2026, 8, 11, 14, 59, 0, 'Asia/Jakarta');
        $this->assertTrue($returnedAt->lt($due), 'Kembali 14:59 seharusnya tepat waktu');
    }

    // TEST: Toolman tanpa custom → default +3 hari 15:00
    public function test_toolman_tanpa_custom_default_3_hari_15(): void
    {
        $actor   = $this->makeUser('toolman');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed);

        $this->assertEquals('2026-08-14 15:00:00', $due->format('Y-m-d H:i:s'));
    }

    // TEST: Admin membuat untuk siswa → custom due_at diterima
    public function test_admin_custom_due_untuk_siswa(): void
    {
        $actor   = $this->makeUser('admin');
        $borrower = $this->makeUser('siswa');
        $borrowed = Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta');

        $due = $this->svc->calculate($actor, $borrower, $borrowed, '2026-08-18', '10:00');

        $this->assertEquals('2026-08-18 10:00:00', $due->format('Y-m-d H:i:s'));
    }
}
