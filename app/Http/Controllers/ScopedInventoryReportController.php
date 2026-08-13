<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScopedInventoryReportController extends Controller
{
    /**
     * Halaman utama laporan inventaris.
     */
    public function index(
        Request $request
    ): View {
        return app(
            WorkshopAwareInventoryReportController::class
        )->index($request);
    }

    /**
     * Alias halaman laporan inventaris.
     */
    public function inventory(
        Request $request
    ): View {
        return app(
            WorkshopAwareInventoryReportController::class
        )->inventory($request);
    }

    /**
     * Export laporan inventaris ke PDF.
     */
    public function pdf(
        Request $request
    ): mixed {
        return app(
            WorkshopAwareInventoryReportExportController::class
        )->pdf($request);
    }

    /**
     * Export laporan inventaris ke Excel/CSV.
     */
    public function excel(
        Request $request
    ): StreamedResponse {
        return app(
            WorkshopAwareInventoryReportExportController::class
        )->excel($request);
    }
}
