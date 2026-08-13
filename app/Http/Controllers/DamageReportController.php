<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveDamageReportRequest;
use App\Http\Requests\StoreDamageReportRequest;
use App\Models\DamageReport;
use App\Models\Item;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DamageReportController extends Controller
{
    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $canViewAll = $user->hasRole(
            'admin',
            'kepala_bengkel',
            'toolman',
            'guru'
        );

        $search = trim(
            (string) $request->input(
                'search'
            )
        );

        $reports = DamageReport::query()
            ->with([
                'item.category',
                'item.workshop',
                'item.location.parent.parent.parent',
                'reporter',
                'handler',
                'completer',
            ])
            ->when(
                ! $canViewAll,
                fn (Builder $query): Builder =>
                    $query->where(
                        'reported_by',
                        $user->id
                    )
            )
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $subquery
                        ) use ($search): void {
                            $subquery
                                ->where(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'diagnosis',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'item',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
                                        $itemQuery
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'reporter',
                                    fn (
                                        Builder $userQuery
                                    ): Builder =>
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('status'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'status',
                        $request->input(
                            'status'
                        )
                    )
            )
            ->when(
                $request->filled('severity'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'severity',
                        $request->input(
                            'severity'
                        )
                    )
            )
            ->when(
                $request->filled('workshop_id'),
                function (
                    Builder $query
                ) use ($request): void {
                    $query->whereHas(
                        'item',
                        fn (
                            Builder $itemQuery
                        ): Builder =>
                            $itemQuery->where(
                                'workshop_id',
                                $request->input(
                                    'workshop_id'
                                )
                            )
                    );
                }
            )
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'damage-reports.index',
            [
                'reports' => $reports,

                'statuses' =>
                    DamageReport::statusOptions(),

                'severities' =>
                    DamageReport::severityOptions(),

                'workshops' =>
                    Workshop::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy('code')
                        ->get(),
            ]
        );
    }

    public function create(
        Request $request
    ): View {
        $tools = Item::query()
            ->with([
                'category',
                'workshop',
                'location.parent.parent.parent',
            ])
            ->where('type', 'tool')
            ->where('is_active', true)
            ->where(
                'status',
                '!=',
                'borrowed'
            )
            ->whereDoesntHave(
                'damageReports',
                fn (Builder $query): Builder =>
                    $query->whereIn(
                        'status',
                        DamageReport::openStatuses()
                    )
            )
            ->orderBy('workshop_id')
            ->orderBy('name')
            ->orderBy('code')
            ->get();

        return view(
            'damage-reports.create',
            [
                'tools' => $tools,

                'severities' =>
                    DamageReport::severityOptions(),

                'selectedItemId' => old(
                    'item_id',
                    $request->integer('item')
                ),
            ]
        );
    }

    public function store(
        StoreDamageReportRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $report = DB::transaction(
            function () use (
                $data,
                $request
            ): DamageReport {
                $item = Item::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['item_id']
                    );

                if (! $item->isTool()) {
                    throw ValidationException::withMessages([
                        'item_id' =>
                            'Laporan kerusakan hanya berlaku untuk alat.',
                    ]);
                }

                if (! $item->is_active) {
                    throw ValidationException::withMessages([
                        'item_id' =>
                            'Alat yang dipilih sudah tidak aktif.',
                    ]);
                }

                if ($item->status === 'borrowed') {
                    throw ValidationException::withMessages([
                        'item_id' =>
                            'Alat masih berstatus dipinjam.',
                    ]);
                }

                $hasOpenReport = DamageReport::query()
                    ->where(
                        'item_id',
                        $item->id
                    )
                    ->whereIn(
                        'status',
                        DamageReport::openStatuses()
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($hasOpenReport) {
                    throw ValidationException::withMessages([
                        'item_id' =>
                            'Alat masih memiliki laporan kerusakan yang belum selesai.',
                    ]);
                }

                /*
                 * TODO 5: Simpan bukti gambar (opsional) ke storage.
                 */
                $evidencePath = null;
                if ($request->hasFile('evidence_image') && $request->file('evidence_image')->isValid()) {
                    $evidencePath = $request->file('evidence_image')->store(
                        'damage-reports',
                        'public'
                    );
                }

                $report = DamageReport::query()
                    ->create([
                        'code' => null,

                        'item_id' =>
                            $item->id,

                        'loan_item_id' =>
                            null,

                        'reported_by' =>
                            $request->user()->id,

                        'status' =>
                            DamageReport::STATUS_REPORTED,

                        'severity' =>
                            $data['severity'],

                        'reported_at' =>
                            $data['reported_at'],

                        'condition_before' =>
                            $item->condition,

                        'description' =>
                            $data['description'],

                        'notes' =>
                            $data['notes'] ?? null,

                        'evidence_image' =>
                            $evidencePath,
                    ]);

                $report->fill([
                    'code' => sprintf(
                        'RSK-%s-%06d',
                        now()->format('Ymd'),
                        $report->id
                    ),
                ])->save();

                $item->fill([
                    'condition' =>
                        $data['severity'],

                    'status' =>
                        $this->statusFromCondition(
                            $data['severity']
                        ),
                ])->save();

                return $report;
            },
            attempts: 3
        );

        return redirect()
            ->route(
                'damage-reports.show',
                $report
            )
            ->with(
                'success',
                'Laporan kerusakan berhasil dibuat.'
            );
    }

    public function show(
        Request $request,
        DamageReport $damageReport
    ): View {
        $this->ensureCanView(
            $request,
            $damageReport
        );

        $damageReport->load([
            'item.category',
            'item.unit',
            'item.workshop',
            'item.location.parent.parent.parent',
            'loanItem.loan',
            'reporter',
            'handler',
            'completer',
        ]);

        return view(
            'damage-reports.show',
            [
                'report' =>
                    $damageReport,

                'conditions' =>
                    Item::conditionOptions(),

                'canManage' =>
                    $request->user()->hasRole(
                        'admin',
                        'toolman'
                    ),
            ]
        );
    }

    public function start(
        Request $request,
        DamageReport $damageReport
    ): RedirectResponse {
        abort_unless(
            $request->user()?->hasRole('admin', 'toolman'),
            403
        );

        DB::transaction(
            function () use (
                $damageReport,
                $request
            ): void {
                $report = DamageReport::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $damageReport->id
                    );

                if (! $report->canStart()) {
                    throw ValidationException::withMessages([
                        'report' =>
                            'Laporan ini tidak dapat mulai diperbaiki.',
                    ]);
                }

                $item = Item::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $report->item_id
                    );

                $report->fill([
                    'status' =>
                        DamageReport::STATUS_IN_REPAIR,

                    'handled_by' =>
                        $request->user()->id,

                    'started_at' => now(),
                ])->save();

                $item->fill([
                    'status' => 'maintenance',
                ])->save();
            },
            attempts: 3
        );

        return back()->with(
            'success',
            'Proses perbaikan alat berhasil dimulai.'
        );
    }

    public function resolve(
        ResolveDamageReportRequest $request,
        DamageReport $damageReport
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(
            function () use (
                $damageReport,
                $request,
                $data
            ): void {
                $report = DamageReport::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $damageReport->id
                    );

                if (! $report->canResolve()) {
                    throw ValidationException::withMessages([
                        'report' =>
                            'Laporan ini sudah tidak dapat diselesaikan.',
                    ]);
                }

                $item = Item::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $report->item_id
                    );

                $isUnrepairable =
                    $data['resolution']
                    === 'unrepairable';

                $conditionAfter = $isUnrepairable
                    ? 'unfit'
                    : $data['condition_after'];

                $report->fill([
                    'status' => $isUnrepairable
                        ? DamageReport::STATUS_UNREPAIRABLE
                        : DamageReport::STATUS_REPAIRED,

                    'handled_by' =>
                        $report->handled_by
                        ?? $request->user()->id,

                    'completed_by' =>
                        $request->user()->id,

                    'started_at' =>
                        $report->started_at
                        ?? now(),

                    'completed_at' =>
                        now(),

                    'condition_after' =>
                        $conditionAfter,

                    'diagnosis' =>
                        $data['diagnosis'],

                    'action_taken' =>
                        $data['action_taken'],

                    'vendor' =>
                        $data['vendor'] ?? null,

                    'repair_cost' =>
                        $data['repair_cost'] ?? null,

                    'resolution_notes' =>
                        $data['resolution_notes']
                        ?? null,
                ])->save();

                $item->fill([
                    'condition' =>
                        $conditionAfter,

                    'status' =>
                        $this->statusFromCondition(
                            $conditionAfter
                        ),
                ])->save();
            },
            attempts: 3
        );

        return back()->with(
            'success',
            'Penyelesaian laporan kerusakan berhasil dicatat.'
        );
    }

    private function ensureCanView(
        Request $request,
        DamageReport $report
    ): void {
        $user = $request->user();

        if (
            $user->hasRole(
                'admin',
                'kepala_bengkel',
                'toolman',
                'guru'
            )
        ) {
            return;
        }

        abort_unless(
            $report->reported_by
                === $user->id,
            403
        );
    }

    private function statusFromCondition(
        string $condition
    ): string {
        return match ($condition) {
            'good' =>
                'available',

            'maintenance' =>
                'maintenance',

            'minor_damage',
            'major_damage',
            'unfit' =>
                'damaged',

            default =>
                'damaged',
        };
    }
}