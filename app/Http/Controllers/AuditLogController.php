<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->input('search')
        );

        $logs = AuditLog::query()
            ->with('user')
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
                                    'auditable_label',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'route_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'url',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'ip_address',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'user',
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
                $request->filled('event'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'event',
                        $request->input('event')
                    )
            )
            ->when(
                $request->filled(
                    'auditable_type'
                ),
                fn (Builder $query): Builder =>
                    $query->where(
                        'auditable_type',
                        $request->input(
                            'auditable_type'
                        )
                    )
            )
            ->when(
                $request->filled('user_id'),
                fn (Builder $query): Builder =>
                    $query->where(
                        'user_id',
                        $request->input('user_id')
                    )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $request->input('date_from')
                    )
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $request->input('date_to')
                    )
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,

            'events' =>
                AuditLog::eventOptions(),

            'models' =>
                AuditLog::modelOptions(),

            'users' => User::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'username',
                    'email',
                ]),
        ]);
    }

    public function show(
        AuditLog $auditLog
    ): View {
        $auditLog->load('user');

        return view('audit-logs.show', [
            'log' => $auditLog,
        ]);
    }
}