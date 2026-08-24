<?php

namespace App\Http\Controllers;

use App\Models\ItemStockMovement;
use App\Services\InventoryPlacementReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WorkshopAwareInventoryReportController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function inventory(Request $request): View
    {
        return $this->render($request);
    }

    private function render(Request $request): View
    {
        $service = app(InventoryPlacementReportService::class);

        $summary             = $service->summary($request);
        $selectedWorkshopId  = $service->effectiveWorkshopId($request);
        $workshops           = $service->visibleWorkshops($request);
        $categories          = $service->categories();

        $tab = in_array($request->input('tab'), ['barang_masuk', 'barang_keluar'], true)
            ? $request->input('tab')
            : 'inventaris';

        $movementRows    = null;
        $movementSummary = null;

        if ($tab === 'barang_masuk') {
            $movementRows    = $this->movementQuery($request, ItemStockMovement::TYPE_INCOMING)
                ->paginate(25)->withQueryString();
            $movementSummary = $this->movementSummary($request, ItemStockMovement::TYPE_INCOMING);
        } elseif ($tab === 'barang_keluar') {
            $movementRows    = $this->movementQuery($request, ItemStockMovement::TYPE_OUTGOING)
                ->paginate(25)->withQueryString();
            $movementSummary = $this->movementSummary($request, ItemStockMovement::TYPE_OUTGOING);
        }

        return view('reports.index', [
            'tab'                 => $tab,
            'items'               => $tab === 'inventaris'
                ? $service->paginate($request, 25)
                : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
            'inventory'           => null,
            'inventoryItems'      => null,
            'workshops'           => $workshops,
            'categories'          => $categories,
            'selectedWorkshopId'  => $selectedWorkshopId,
            'isWorkshopRestricted' => $service->isWorkshopRestricted($request),
            'accessWarning'       => $service->accessWarning($request),
            'summary'             => $summary,
            'totalInventoryValue' => $summary['total_value'],
            'toolCount'           => $summary['tools'],
            'materialCount'       => $summary['materials'],
            'lowStockCount'       => $summary['low_stock'],
            'movementRows'        => $movementRows,
            'movementSummary'     => $movementSummary,
        ]);
    }

    private function movementQuery(Request $request, string $type): Builder
    {
        $search = trim((string) $request->input('search'));

        return ItemStockMovement::query()
            ->with(['item.category', 'item.unit', 'item.workshop', 'item.itemAssets.workshop', 'storageLocation', 'user'])
            ->where('type', $type)
            ->when(
                $search !== '',
                function (Builder $q) use ($search): void {
                    $q->where(function (Builder $sub) use ($search): void {
                        $sub->where('receipt_code', 'like', "%{$search}%")
                            ->orWhere('reference_number', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('fund_source', 'like', "%{$search}%")
                            ->orWhereHas('item', function (Builder $iq) use ($search): void {
                                $iq->where('code', 'like', "%{$search}%")
                                   ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('item_category_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('item_category_id', $request->input('item_category_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn (Builder $q): Builder => $q->whereYear('transaction_date', $request->integer('year'))
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    private function movementSummary(Request $request, string $type): array
    {
        $base = ItemStockMovement::query()
            ->where('type', $type)
            ->when(
                $request->filled('workshop_id'),
                fn (Builder $q): Builder => $q->whereHas(
                    'item',
                    fn (Builder $iq): Builder => $iq->where('workshop_id', $request->input('workshop_id'))
                )
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q): Builder => $q->whereDate('transaction_date', '<=', $request->input('date_to'))
            )
            ->when(
                $request->filled('year'),
                fn (Builder $q): Builder => $q->whereYear('transaction_date', $request->integer('year'))
            );

        $result = [
            'total_transactions' => (clone $base)->count(),
            'total_quantity'     => (float) (clone $base)->sum('quantity'),
            'unique_items'       => (clone $base)->distinct('item_id')->count('item_id'),
        ];

        if ($type === ItemStockMovement::TYPE_INCOMING) {
            $result['total_value'] = (float) (clone $base)
                ->selectRaw('SUM(quantity * COALESCE(unit_price, 0)) as v')
                ->value('v');
        }

        return $result;
    }
}
