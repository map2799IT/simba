<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\SortsIndex;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuditLogController extends Controller
{
    use SortsIndex;

    /**
     * Menampilkan daftar audit log.
     */
    public function index(Request $request): View
    {
        if (! Schema::hasTable('audit_logs')) {
            return view(
                'admin.audit-logs.index',
                [
                    'logs' => collect(),
                    'tableAvailable' => false,
                ]
            );
        }

        $columns = Schema::getColumnListing(
            'audit_logs'
        );

        $sortColumns = array_values(
            array_filter(
                ['created_at', 'event', 'description', 'id'],
                fn (string $c) => in_array($c, $columns, true)
            )
        );

        [$sort, $direction, $perPage] = $this->indexSortParams($sortColumns);

        $query = DB::table('audit_logs')
            ->select('audit_logs.*');

        $userColumn = $this->firstExistingColumn(
            $columns,
            [
                'user_id',
                'causer_id',
                'actor_id',
                'created_by',
            ]
        );

        if (
            $userColumn !== null
            && Schema::hasTable('users')
        ) {
            $query
                ->leftJoin(
                    'users as audit_users',
                    'audit_users.id',
                    '=',
                    "audit_logs.{$userColumn}"
                )
                ->addSelect(
                    'audit_users.name as user_name'
                );
        } else {
            $query->addSelect(
                DB::raw('NULL as user_name')
            );
        }

        $search = trim(
            (string) $request->input('search')
        );

        if ($search !== '') {
            $searchColumns = array_values(
                array_filter([
                    $this->firstExistingColumn(
                        $columns,
                        [
                            'event',
                            'action',
                            'activity',
                            'type',
                        ]
                    ),

                    $this->firstExistingColumn(
                        $columns,
                        [
                            'description',
                            'message',
                            'notes',
                            'properties',
                        ]
                    ),

                    $this->firstExistingColumn(
                        $columns,
                        [
                            'subject_type',
                            'model_type',
                            'entity_type',
                        ]
                    ),
                ])
            );

            if ($searchColumns !== []) {
                $query->where(
                    function (Builder $searchQuery) use (
                        $searchColumns,
                        $search
                    ): void {
                        foreach (
                            $searchColumns
                            as $index => $column
                        ) {
                            $method = $index === 0
                                ? 'where'
                                : 'orWhere';

                            $searchQuery->{$method}(
                                "audit_logs.{$column}",
                                'like',
                                "%{$search}%"
                            );
                        }
                    }
                );
            }
        }

        $orderColumn = $this->firstExistingColumn(
            $columns,
            [
                'created_at',
                'logged_at',
                'performed_at',
                'id',
            ]
        );

        if ($orderColumn !== null) {
            $query->orderByDesc(
                "audit_logs.{$orderColumn}"
            );
        }

        if ($sort !== null) {
            $query->orderBy(
                "audit_logs.{$sort}",
                $direction
            );
        }

        return view(
            'admin.audit-logs.index',
            [
                'logs' => $query
                    ->paginate($perPage)
                    ->withQueryString(),

                'tableAvailable' => true,

                'sort' => $sort,

                'direction' => $direction,

                'perPage' => $perPage,
            ]
        );
    }

    /**
     * Menampilkan detail audit log.
     */
    public function show(
        string|int $auditLog
    ): View {
        if (! Schema::hasTable('audit_logs')) {
            throw new NotFoundHttpException(
                'Tabel audit_logs belum tersedia.'
            );
        }

        $log = DB::table('audit_logs')
            ->where('id', $auditLog)
            ->first();

        abort_if(
            $log === null,
            404,
            'Audit log tidak ditemukan.'
        );

        return view(
            'admin.audit-logs.show',
            [
                'log' => $log,
            ]
        );
    }

    /**
     * Audit log bersifat hanya-baca.
     */
    public function create(): never
    {
        abort(404);
    }

    public function store(Request $request): never
    {
        abort(405);
    }

    public function edit(
        string|int $auditLog
    ): never {
        abort(404);
    }

    public function update(
        Request $request,
        string|int $auditLog
    ): never {
        abort(405);
    }

    public function destroy(
        string|int $auditLog
    ): never {
        abort(405);
    }

    private function firstExistingColumn(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (
                in_array(
                    $candidate,
                    $columns,
                    true
                )
            ) {
                return $candidate;
            }
        }

        return null;
    }
}
