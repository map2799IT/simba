<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BorrowerAwareDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = (string) $user?->role;

        if ($role === 'wakil_sarpras') {
            return $this->wakaSarprasDashboard();
        }

        if (! in_array($role, ['guru', 'siswa'], true)) {
            return app(DashboardController::class)
                ->index($request);
        }

        $workshop = null;

        if ($user?->workshop_id !== null) {
            $workshop = Workshop::query()
                ->withoutGlobalScopes()
                ->find($user->workshop_id);
        }

        $empty = collect();

        if (! Schema::hasTable('loans')) {
            return view('dashboard.borrower-only', [
                'role' => $role,
                'workshop' => $workshop,
                'canCreateLoan' =>
                    $role === 'guru'
                    || $user?->workshop_id !== null,
                'totalLoans' => 0,
                'pendingLoans' => 0,
                'approvedLoans' => 0,
                'borrowedLoans' => 0,
                'overdueLoans' => 0,
                'recentLoans' => $empty,
            ]);
        }

        $base = Loan::query()
            ->withoutGlobalScopes()
            ->where('borrower_id', $user->id);

        $totalLoans = (clone $base)->count();

        $pendingLoans = (clone $base)
            ->whereIn('status', [
                'pending',
                'requested',
                'submitted',
            ])
            ->count();

        $approvedLoans = (clone $base)
            ->where('status', 'approved')
            ->count();

        $borrowedLoans = (clone $base)
            ->whereIn('status', [
                'borrowed',
                'partially_returned',
                'active',
                'checked_out',
            ])
            ->count();

        $overdueLoans = (clone $base)
            ->whereIn('status', [
                'borrowed',
                'partially_returned',
                'active',
                'checked_out',
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $recentLoans = (clone $base)
            ->with('workshop')
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('dashboard.borrower-only', [
            'role' => $role,
            'workshop' => $workshop,
            'canCreateLoan' =>
                $role === 'guru'
                || $user->workshop_id !== null,
            'totalLoans' => $totalLoans,
            'pendingLoans' => $pendingLoans,
            'approvedLoans' => $approvedLoans,
            'borrowedLoans' => $borrowedLoans,
            'overdueLoans' => $overdueLoans,
            'recentLoans' => $recentLoans,
        ]);
    }

    private function wakaSarprasDashboard(): View
    {
        $stats = [
            'workshops' =>
                $this->countWhereActive(
                    'workshops'
                ),

            'locations' =>
                $this->countWhereActive(
                    'storage_locations'
                ),

            'master_items' =>
                $this->countWhereActive(
                    'items'
                ),

            'tool_units' =>
                Schema::hasTable('item_assets')
                    ? DB::table('item_assets')
                        ->where('is_active', true)
                        ->count()
                    : 0,

            'available_tools' =>
                Schema::hasTable('item_assets')
                    ? DB::table('item_assets')
                        ->where('is_active', true)
                        ->where('status', 'available')
                        ->count()
                    : 0,

            'borrowed_tools' =>
                Schema::hasTable('item_assets')
                    ? DB::table('item_assets')
                        ->where('is_active', true)
                        ->where('status', 'borrowed')
                        ->count()
                    : 0,

            'active_loans' =>
                Schema::hasTable('loans')
                    ? DB::table('loans')
                        ->whereIn('status', [
                            'pending',
                            'approved',
                            'borrowed',
                            'partially_returned',
                            'active',
                            'checked_out',
                        ])
                        ->count()
                    : 0,

            'overdue_loans' =>
                Schema::hasTable('loans')
                    && Schema::hasColumn(
                        'loans',
                        'due_at'
                    )
                    ? DB::table('loans')
                        ->whereIn('status', [
                            'borrowed',
                            'partially_returned',
                            'active',
                            'checked_out',
                        ])
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())
                        ->count()
                    : 0,

            'open_damages' =>
                $this->openDamageCount(),
        ];

        return view(
            'dashboard.waka-sarpras',
            [
                'stats' => $stats,
                'workshopSummaries' =>
                    $this->workshopSummaries(),

                'recentLoans' =>
                    $this->recentLoans(),

                'recentMovements' =>
                    $this->recentMovements(),
            ]
        );
    }

    private function countWhereActive(
        string $table
    ): int {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if (
            Schema::hasColumn(
                $table,
                'is_active'
            )
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        return $query->count();
    }

    private function openDamageCount(): int
    {
        if (! Schema::hasTable('damage_reports')) {
            return 0;
        }

        $query =
            DB::table(
                'damage_reports'
            );

        if (
            Schema::hasColumn(
                'damage_reports',
                'status'
            )
        ) {
            $query->whereNotIn(
                'status',
                [
                    'closed',
                    'completed',
                    'resolved',
                    'rejected',
                    'cancelled',
                ]
            );
        }

        return $query->count();
    }

    private function workshopSummaries(): Collection
    {
        if (! Schema::hasTable('workshops')) {
            return collect();
        }

        $workshops =
            DB::table('workshops')
                ->when(
                    Schema::hasColumn(
                        'workshops',
                        'is_active'
                    ),
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                )
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ]);

        return $workshops->map(
            function (object $workshop): object {
                $workshop->locations =
                    Schema::hasTable(
                        'storage_locations'
                    )
                        ? DB::table(
                            'storage_locations'
                        )
                            ->where(
                                'workshop_id',
                                $workshop->id
                            )
                            ->when(
                                Schema::hasColumn(
                                    'storage_locations',
                                    'is_active'
                                ),
                                fn ($query) =>
                                    $query->where(
                                        'is_active',
                                        true
                                    )
                            )
                            ->count()
                        : 0;

                $workshop->available_assets =
                    Schema::hasTable(
                        'item_assets'
                    )
                        ? DB::table(
                            'item_assets'
                        )
                            ->where(
                                'workshop_id',
                                $workshop->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'status',
                                'available'
                            )
                            ->count()
                        : 0;

                $workshop->active_loans =
                    Schema::hasTable('loans')
                        && Schema::hasColumn(
                            'loans',
                            'workshop_id'
                        )
                        ? DB::table('loans')
                            ->where(
                                'workshop_id',
                                $workshop->id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'pending',
                                    'approved',
                                    'borrowed',
                                    'partially_returned',
                                    'active',
                                    'checked_out',
                                ]
                            )
                            ->count()
                        : 0;

                return $workshop;
            }
        );
    }

    private function recentLoans(): Collection
    {
        if (
            ! Schema::hasTable('loans')
            || ! Schema::hasTable('users')
        ) {
            return collect();
        }

        $query =
            DB::table('loans as l')
                ->leftJoin(
                    'users as u',
                    'u.id',
                    '=',
                    'l.borrower_id'
                );

        if (
            Schema::hasTable(
                'workshops'
            )
            && Schema::hasColumn(
                'loans',
                'workshop_id'
            )
        ) {
            $query->leftJoin(
                'workshops as w',
                'w.id',
                '=',
                'l.workshop_id'
            );
        }

        return $query
            ->select([
                'l.id',
                'l.code',
                'l.status',
                'l.request_date',
                'l.due_at',
                'u.name as borrower_name',
                DB::raw(
                    Schema::hasTable(
                        'workshops'
                    )
                    && Schema::hasColumn(
                        'loans',
                        'workshop_id'
                    )
                        ? 'w.code as workshop_code'
                        : 'NULL as workshop_code'
                ),
            ])
            ->orderByDesc('l.id')
            ->limit(8)
            ->get();
    }

    private function recentMovements(): Collection
    {
        if (
            ! Schema::hasTable(
                'item_stock_movements'
            )
        ) {
            return collect();
        }

        $query =
            DB::table(
                'item_stock_movements as m'
            );

        if (Schema::hasTable('items')) {
            $query->leftJoin(
                'items as i',
                'i.id',
                '=',
                'm.item_id'
            );
        }

        if (
            Schema::hasTable('workshops')
            && Schema::hasColumn(
                'item_stock_movements',
                'workshop_id'
            )
        ) {
            $query->leftJoin(
                'workshops as w',
                'w.id',
                '=',
                'm.workshop_id'
            );
        }

        return $query
            ->select([
                'm.id',
                'm.type',
                'm.quantity',
                'm.transaction_date',
                DB::raw(
                    Schema::hasTable('items')
                        ? 'i.name as item_name'
                        : 'NULL as item_name'
                ),
                DB::raw(
                    Schema::hasTable('workshops')
                    && Schema::hasColumn(
                        'item_stock_movements',
                        'workshop_id'
                    )
                        ? 'w.code as workshop_code'
                        : 'NULL as workshop_code'
                ),
            ])
            ->orderByDesc('m.id')
            ->limit(8)
            ->get();
    }
}
