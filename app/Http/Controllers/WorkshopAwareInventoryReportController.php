<?php

namespace App\Http\Controllers;

use App\Services\InventoryPlacementReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkshopAwareInventoryReportController extends Controller
{
    public function index(
        Request $request
    ): View {
        return $this->render(
            $request
        );
    }

    public function inventory(
        Request $request
    ): View {
        return $this->render(
            $request
        );
    }

    private function render(
        Request $request
    ): View {
        $service = app(
            InventoryPlacementReportService::class
        );

        $summary =
            $service->summary($request);

        $selectedWorkshopId =
            $service->effectiveWorkshopId(
                $request
            );

        return view(
            'reports.index',
            [
                'items' =>
                    $service->paginate(
                        $request,
                        25
                    ),

                'inventory' => null,
                'inventoryItems' => null,

                'workshops' =>
                    $service->visibleWorkshops(
                        $request
                    ),

                'categories' =>
                    $service->categories(),

                'selectedWorkshopId' =>
                    $selectedWorkshopId,

                'isWorkshopRestricted' =>
                    $service->isWorkshopRestricted(
                        $request
                    ),

                'accessWarning' =>
                    $service->accessWarning(
                        $request
                    ),

                'summary' => $summary,

                'totalInventoryValue' =>
                    $summary['total_value'],

                'toolCount' =>
                    $summary['tools'],

                'materialCount' =>
                    $summary['materials'],

                'lowStockCount' =>
                    $summary['low_stock'],
            ]
        );
    }
}
