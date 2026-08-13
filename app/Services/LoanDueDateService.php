<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;

/**
 * Satu sumber kebenaran untuk kalkulasi jatuh tempo peminjaman SIMBA.
 *
 * RULE:
 * - actor = admin/toolman  → boleh custom due_at
 * - borrower = siswa       → hari yang sama pukul 15:00 (jika actor bukan admin/toolman)
 * - borrower = guru        → +3 hari dari borrowed_at (jam sama)
 * - default (lainnya)      → +3 hari pukul 15:00
 *
 * Kelas ini sengaja TIDAK menggunakan config() atau facade Laravel
 * agar dapat diuji sebagai pure unit test tanpa app container.
 */
class LoanDueDateService
{
    public const SISWA_CUTOFF_HOUR   = 15;
    public const SISWA_CUTOFF_MINUTE = 0;
    public const DEFAULT_TZ          = 'Asia/Jakarta';

    public function canSetCustomDueDate(User $actor): bool
    {
        return in_array((string) $actor->role, ['admin', 'toolman'], true);
    }

    /**
     * Hitung due_at berdasarkan actor + borrower.
     *
     * @param User        $actor      User yang login / membuat transaksi
     * @param User        $borrower   User yang meminjam barang
     * @param Carbon      $borrowedAt Waktu transaksi dibuat (server time)
     * @param string|null $dueDateStr Custom date Y-m-d (hanya digunakan jika actor boleh)
     * @param string|null $dueTimeStr Custom time H:i  (hanya digunakan jika actor boleh)
     * @throws \InvalidArgumentException jika custom due_at tidak valid
     */
    public function calculate(
        User $actor,
        User $borrower,
        Carbon $borrowedAt,
        ?string $dueDateStr = null,
        ?string $dueTimeStr = null
    ): Carbon {
        if ($this->canSetCustomDueDate($actor)) {
            if (filled($dueDateStr) && filled($dueTimeStr)) {
                $custom = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    trim($dueDateStr).' '.trim($dueTimeStr),
                    self::DEFAULT_TZ
                );

                if ($custom->lte($borrowedAt)) {
                    throw new \InvalidArgumentException(
                        'due_date: Jatuh tempo harus setelah waktu peminjaman ('
                        .$borrowedAt->format('d-m-Y H:i').').'
                    );
                }

                return $custom;
            }

            // Admin/Toolman tanpa custom → default +3 hari 15:00
            return $borrowedAt->copy()->addDays(3)->setTime(15, 0, 0);
        }

        // Self-service: rule dari role BORROWER
        return match ((string) $borrower->role) {
            'siswa' => $borrowedAt->copy()->setTime(15, 0, 0),
            'guru'  => $borrowedAt->copy()->addDays(3),
            default => $borrowedAt->copy()->addDays(3)->setTime(15, 0, 0),
        };
    }

    /**
     * Validasi apakah siswa masih boleh meminjam.
     * Siswa wajib mengembalikan hari yang sama pukul 15:00.
     *
     * @throws \InvalidArgumentException jika sudah >= 15:00
     */
    public function validateSiswaBorrowTime(Carbon $now): void
    {
        if (
            $now->hour > self::SISWA_CUTOFF_HOUR
            || ($now->hour === self::SISWA_CUTOFF_HOUR && $now->minute >= self::SISWA_CUTOFF_MINUTE)
        ) {
            throw new \InvalidArgumentException(
                'loan_time: Peminjaman siswa hanya dapat dilakukan sebelum pukul 15.00 '
                .'karena barang wajib dikembalikan pada hari yang sama.'
            );
        }
    }

    /**
     * Wrapper yang melempar ValidationException di dalam Laravel,
     * digunakan oleh controller agar error bisa ditampilkan per-field.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \InvalidArgumentException (di luar konteks Laravel)
     */
    public function validateSiswaBorrowTimeForController(Carbon $now): void
    {
        try {
            $this->validateSiswaBorrowTime($now);
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'loan_time' => 'Peminjaman siswa hanya dapat dilakukan sebelum pukul 15.00 '
                    .'karena barang wajib dikembalikan pada hari yang sama.',
            ]);
        }
    }

    /**
     * Wrapper untuk controller agar custom due_at error tampil sebagai ValidationException.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function calculateForController(
        User $actor,
        User $borrower,
        Carbon $borrowedAt,
        ?string $dueDateStr = null,
        ?string $dueTimeStr = null
    ): Carbon {
        try {
            return $this->calculate($actor, $borrower, $borrowedAt, $dueDateStr, $dueTimeStr);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            // Ekstrak field dan pesan dari format "field: pesan"
            if (str_contains($msg, ': ')) {
                [$field, $detail] = explode(': ', $msg, 2);
            } else {
                $field = 'due_date';
                $detail = $msg;
            }
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => $detail,
            ]);
        }
    }
}
