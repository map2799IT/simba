<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserErrorLog;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErrorLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'unresolved');
        $userId = $request->input('user_id');

        $query = UserErrorLog::query()
            ->with('user')
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('message', 'like', "%{$search}%")
                        ->orWhere('exception_class', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when($status === 'unresolved', fn (Builder $q) => $q->where('is_resolved', false))
            ->when($status === 'resolved', fn (Builder $q) => $q->where('is_resolved', true))
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->orderByDesc('created_at');

        $stats = [
            'total' => UserErrorLog::count(),
            'unresolved' => UserErrorLog::where('is_resolved', false)->count(),
            'resolved' => UserErrorLog::where('is_resolved', true)->count(),
            'users_affected' => UserErrorLog::whereNotNull('user_id')
                ->where('is_resolved', false)
                ->distinct('user_id')
                ->count('user_id'),
        ];

        $topErrors = UserErrorLog::query()
            ->select('exception_class', DB::raw('COUNT(*) as count'))
            ->where('is_resolved', false)
            ->whereNotNull('exception_class')
            ->groupBy('exception_class')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $affectedUsers = DB::table('user_error_logs')
            ->join('users', 'users.id', '=', 'user_error_logs.user_id')
            ->select('users.id', 'users.name', 'users.role', DB::raw('COUNT(*) as error_count'))
            ->where('user_error_logs.is_resolved', false)
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('error_count')
            ->limit(10)
            ->get();

        return view('admin.error-logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'stats' => $stats,
            'topErrors' => $topErrors,
            'affectedUsers' => $affectedUsers,
            'search' => $search,
            'status' => $status,
            'userId' => $userId,
        ]);
    }

    public function show(UserErrorLog $errorLog): View
    {
        $errorLog->load('user');

        return view('admin.error-logs.show', [
            'log' => $errorLog,
        ]);
    }

    public function resolve(Request $request, UserErrorLog $errorLog): RedirectResponse
    {
        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $errorLog->update([
            'is_resolved' => true,
            'resolution_note' => $data['resolution_note'] ?? null,
        ]);

        return back()->with('success', 'Error telah ditandai sebagai terselesaikan.');
    }

    public function unresolve(UserErrorLog $errorLog): RedirectResponse
    {
        $errorLog->update([
            'is_resolved' => false,
            'resolution_note' => null,
        ]);

        return back()->with('success', 'Error dikembalikan ke status belum terselesaikan.');
    }

    public function destroy(UserErrorLog $errorLog): RedirectResponse
    {
        $errorLog->delete();

        return redirect()->route('admin.error-logs.index')
            ->with('success', 'Log error berhasil dihapus.');
    }

    public function clearResolved(): RedirectResponse
    {
        $count = UserErrorLog::where('is_resolved', true)->count();
        UserErrorLog::where('is_resolved', true)->delete();

        return redirect()->route('admin.error-logs.index')
            ->with('success', "{$count} log error yang sudah terselesaikan berhasil dihapus.");
    }

    /**
     * Query audit dengan filter sama dengan halaman web (paritas web = PDF = Excel).
     */
    private function auditQuery(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status', 'unresolved');
        $userId = $request->input('user_id');

        return UserErrorLog::query()
            ->with('user')
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('message', 'like', "%{$search}%")
                        ->orWhere('exception_class', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when($status === 'unresolved', fn (Builder $q) => $q->where('is_resolved', false))
            ->when($status === 'resolved', fn (Builder $q) => $q->where('is_resolved', true))
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId));
    }

    /**
     * TODO 12: Export PDF Audit Sistem (filter sama dengan halaman).
     */
    public function exportPdf(Request $request)
    {
        $logs = $this->auditQuery($request)->orderByDesc('created_at')->get();

        $pdf = Pdf::loadView('admin.error-logs.pdf', [
            'logs' => $logs,
            'filters' => $request->query(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-sistem-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * TODO 12: Export Excel Audit Sistem (filter sama dengan halaman).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $logs = $this->auditQuery($request)->orderByDesc('created_at')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Waktu', 'User', 'Role', 'HTTP Status', 'Method', 'URL', 'Route', 'IP', 'Pesan', 'Browser',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $sheet->fromArray([
                $log->created_at?->format('d-m-Y H:i:s'),
                $log->user?->name ?? 'Guest',
                $log->user?->role ?? '-',
                $log->http_status,
                $log->method,
                $log->url,
                $log->route_name,
                $log->ip_address,
                mb_substr((string) $log->message, 0, 200),
                mb_substr((string) $log->user_agent, 0, 100),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'audit-sistem-' . now()->format('Ymd-His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
