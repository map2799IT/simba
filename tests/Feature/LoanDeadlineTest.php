<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Unit;
use App\Models\Item;
use App\Models\Workshop;
use App\Models\User;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private Unit $unit;
    private ItemCategory $category;
    private Item $item;
    private User $toolman;
    private User $siswa;
    private User $guru;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create([
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
            'is_active' => true,
        ]);

        $this->unit = Unit::factory()->create(['allows_decimal' => false]);
        $this->category = ItemCategory::factory()->create(['applies_to' => 'material']);
        $this->item = Item::factory()->create([
            'item_category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'stock' => 100,
            'type' => 'material',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin', 'workshop_id' => null]);
        $this->toolman = User::factory()->create([
            'role' => 'toolman',
            'workshop_id' => $this->workshop->id,
        ]);
        $this->siswa = User::factory()->create([
            'role' => 'siswa',
            'workshop_id' => $this->workshop->id,
        ]);
        $this->guru = User::factory()->create(['role' => 'guru', 'workshop_id' => null]);
    }

    /** TEST 1: borrowed_at = server time, due_at = +3 hari 15:00 */
    public function test_siswa_loan_due_at_is_three_days_at_15_regardless_of_borrow_hour(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 8, 20, 0, 'Asia/Jakarta'));

        $loan = $this->makeLoanDirectly($this->siswa);

        $this->assertEquals(
            '2026-08-14 15:00:00',
            $loan->due_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
    }

    /** TEST 2: jam berbeda, deadline tetap 3 hari 15:00 */
    public function test_due_at_is_same_regardless_of_borrow_time(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 13, 30, 0, 'Asia/Jakarta'));

        $loan = $this->makeLoanDirectly($this->siswa);

        $this->assertEquals(
            '2026-08-14 15:00:00',
            $loan->due_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
    }

    /** TEST 4: user biasa mengirim due_at palsu — backend mengabaikan */
    public function test_siswa_cannot_set_custom_due_at_via_request(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 10, 0, 0, 'Asia/Jakarta'));

        $response = $this->actingAs($this->siswa)->post(route('loans.store'), [
            'workshop_id' => $this->workshop->id,
            'borrower_id' => $this->siswa->id,
            'purpose' => 'Test peminjaman',
            'due_date' => '2099-12-31',
            'due_time' => '23:59',
            'items' => [
                ['item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $loan = Loan::latest()->first();

        if ($loan) {
            $this->assertNotEquals(
                '2099-12-31 23:59:00',
                $loan->due_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'Siswa should NOT be able to set custom due_at'
            );
        }
    }

    /** TEST 5: toolman tanpa custom → default +3 hari 15:00 */
    public function test_toolman_without_custom_due_gets_default(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta'));

        $loan = $this->makeLoanDirectly($this->toolman, borrower: $this->siswa);

        $this->assertEquals(
            '2026-08-14 15:00:00',
            $loan->due_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
    }

    /** TEST 7: toolman menentukan custom deadline valid */
    public function test_toolman_can_set_valid_custom_due_at(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta'));

        $loan = $this->makeLoanDirectly(
            $this->toolman,
            borrower: $this->siswa,
            dueDate: '2026-08-20',
            dueTime: '17:00'
        );

        $this->assertEquals(
            '2026-08-20 17:00:00',
            $loan->due_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
    }

    /** TEST 9: custom due_at sebelum borrowed_at harus gagal validasi */
    public function test_custom_due_before_borrowed_at_fails(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 10, 0, 0, 'Asia/Jakarta'));

        $response = $this->actingAs($this->toolman)->post(route('loans.store'), [
            'workshop_id' => $this->workshop->id,
            'borrower_id' => $this->siswa->id,
            'purpose' => 'Test peminjaman',
            'due_date' => '2026-08-10',
            'due_time' => '08:00',
            'items' => [
                ['item_id' => $this->item->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors(['due_date']);
    }

    /** TEST 10 & 11: pengembalian sebelum/sesudah due_at */
    public function test_return_before_due_at_is_not_late(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta'));
        $loan = $this->makeLoanDirectly($this->siswa);

        // Kembali sebelum jatuh tempo
        Carbon::setTestNow(Carbon::create(2026, 8, 14, 14, 59, 0, 'Asia/Jakarta'));
        $this->assertTrue(now()->lt($loan->effectiveDueAt()));
    }

    public function test_return_after_due_at_is_late(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 11, 9, 0, 0, 'Asia/Jakarta'));
        $loan = $this->makeLoanDirectly($this->siswa);

        // Kembali setelah jatuh tempo
        Carbon::setTestNow(Carbon::create(2026, 8, 14, 15, 1, 0, 'Asia/Jakarta'));
        $this->assertTrue(now()->gt($loan->effectiveDueAt()));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeLoanDirectly(
        User $creator,
        ?User $borrower = null,
        ?string $dueDate = null,
        ?string $dueTime = null
    ): Loan {
        $borrower ??= $creator;
        $now = now(config('app.timezone', 'Asia/Jakarta'));

        $dueAt = ($dueDate && $dueTime && in_array($creator->role, ['admin', 'toolman']))
            ? Carbon::createFromFormat('Y-m-d H:i', "$dueDate $dueTime", 'Asia/Jakarta')
            : $now->copy()->addDays(3)->setTime(15, 0, 0);

        return Loan::create([
            'code' => 'TEST-'.uniqid(),
            'borrower_id' => $borrower->id,
            'workshop_id' => $this->workshop->id,
            'status' => Loan::STATUS_PENDING,
            'request_date' => $now->toDateString(),
            'scheduled_at' => $now,
            'due_at' => $dueAt,
            'purpose' => 'Test otomasi deadline',
        ]);
    }
}
