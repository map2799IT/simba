<?php

namespace App\Providers;

use App\Models\DamageReport;
use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemStockMovement;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\StorageLocation;
use App\Models\Workshop;
use App\Services\LoanJurusanRoutingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class JurusanAccessServiceProvider
    extends ServiceProvider
{
    private const SINGLE_WORKSHOP_ROLES = [
        'kepala_bengkel',
        'toolman',
        'siswa',
    ];

    public function boot(
        LoanJurusanRoutingService $routing
    ): void {
        $this->registerWorkshopScopes();
        $this->registerLoanScopes();
        $this->registerLoanRoutingEvents(
            $routing
        );
    }

    private function registerWorkshopScopes(): void
    {
        $scope = static function (
            Builder $builder
        ): void {
            $user = Auth::user();

            if (
                $user === null
                || ! in_array(
                    (string) $user->role,
                    self::SINGLE_WORKSHOP_ROLES,
                    true
                )
            ) {
                return;
            }

            $workshopId =
                $user->getAttribute(
                    'workshop_id'
                );

            if (
                $workshopId === null
                || $workshopId === ''
            ) {
                $builder->whereRaw(
                    '1 = 0'
                );

                return;
            }

            $table =
                $builder
                    ->getModel()
                    ->getTable();

            $builder->where(
                "{$table}.workshop_id",
                (int) $workshopId
            );
        };

        if (class_exists(Item::class)) {
            Item::addGlobalScope(
                'jurusan_visibility',
                $scope
            );
        }

        if (
            class_exists(
                ItemAsset::class
            )
        ) {
            ItemAsset::addGlobalScope(
                'jurusan_visibility',
                $scope
            );
        }

        if (
            class_exists(
                StorageLocation::class
            )
        ) {
            StorageLocation::addGlobalScope(
                'jurusan_visibility',
                $scope
            );
        }

        if (
            class_exists(
                Workshop::class
            )
        ) {
            Workshop::addGlobalScope(
                'jurusan_visibility',
                static function (
                    Builder $builder
                ): void {
                    $user = Auth::user();

                    if (
                        $user === null
                        || ! in_array(
                            (string) $user->role,
                            self::SINGLE_WORKSHOP_ROLES,
                            true
                        )
                    ) {
                        return;
                    }

                    $workshopId =
                        $user->getAttribute(
                            'workshop_id'
                        );

                    if (
                        $workshopId === null
                        || $workshopId === ''
                    ) {
                        $builder->whereRaw(
                            '1 = 0'
                        );

                        return;
                    }

                    $builder->whereKey(
                        (int) $workshopId
                    );
                }
            );
        }

        if (
            class_exists(
                ItemStockMovement::class
            )
        ) {
            ItemStockMovement::addGlobalScope(
                'jurusan_visibility',
                static function (
                    Builder $builder
                ): void {
                    $user = Auth::user();

                    if (
                        $user === null
                        || ! in_array(
                            (string) $user->role,
                            self::SINGLE_WORKSHOP_ROLES,
                            true
                        )
                    ) {
                        return;
                    }

                    $workshopId =
                        $user->getAttribute(
                            'workshop_id'
                        );

                    if (
                        $workshopId === null
                        || $workshopId === ''
                    ) {
                        $builder->whereRaw(
                            '1 = 0'
                        );

                        return;
                    }

                    $table =
                        $builder
                            ->getModel()
                            ->getTable();

                    $builder->whereExists(
                        function (
                            $query
                        ) use (
                            $table,
                            $workshopId
                        ): void {
                            $query
                                ->selectRaw('1')
                                ->from('items')
                                ->whereColumn(
                                    'items.id',
                                    "{$table}.item_id"
                                )
                                ->where(
                                    'items.workshop_id',
                                    (int) $workshopId
                                );
                        }
                    );
                }
            );
        }

        if (
            class_exists(
                DamageReport::class
            )
        ) {
            DamageReport::addGlobalScope(
                'jurusan_visibility',
                static function (
                    Builder $builder
                ): void {
                    $user = Auth::user();

                    if (
                        $user === null
                        || ! in_array(
                            (string) $user->role,
                            self::SINGLE_WORKSHOP_ROLES,
                            true
                        )
                    ) {
                        return;
                    }

                    $workshopId =
                        $user->getAttribute(
                            'workshop_id'
                        );

                    if (
                        $workshopId === null
                        || $workshopId === ''
                    ) {
                        $builder->whereRaw(
                            '1 = 0'
                        );

                        return;
                    }

                    $table =
                        $builder
                            ->getModel()
                            ->getTable();

                    $builder->whereExists(
                        function (
                            $query
                        ) use (
                            $table,
                            $workshopId
                        ): void {
                            $query
                                ->selectRaw('1')
                                ->from('items')
                                ->whereColumn(
                                    'items.id',
                                    "{$table}.item_id"
                                )
                                ->where(
                                    'items.workshop_id',
                                    (int) $workshopId
                                );
                        }
                    );
                }
            );
        }
    }

    private function registerLoanScopes(): void
    {
        if (
            ! class_exists(Loan::class)
        ) {
            return;
        }

        Loan::addGlobalScope(
            'jurusan_loan_visibility',
            static function (
                Builder $builder
            ): void {
                $user = Auth::user();

                if ($user === null) {
                    return;
                }

                $role =
                    (string) $user->role;

                if ($role === 'admin') {
                    return;
                }

                $table =
                    $builder
                        ->getModel()
                        ->getTable();

                if (
                    in_array(
                        $role,
                        [
                            'kepala_bengkel',
                            'toolman',
                        ],
                        true
                    )
                ) {
                    $workshopId =
                        $user->getAttribute(
                            'workshop_id'
                        );

                    if (
                        $workshopId === null
                        || $workshopId === ''
                        || ! Schema::hasColumn(
                            'loans',
                            'workshop_id'
                        )
                    ) {
                        $builder->whereRaw(
                            '1 = 0'
                        );

                        return;
                    }

                    $builder->where(
                        "{$table}.workshop_id",
                        (int) $workshopId
                    );

                    return;
                }

                /*
                 * Guru dan siswa hanya melihat pengajuan miliknya.
                 * Guru tetap dapat memilih alat dari seluruh jurusan.
                 */
                $builder->where(
                    "{$table}.borrower_id",
                    $user->id
                );
            }
        );

        if (
            class_exists(
                LoanItem::class
            )
        ) {
            LoanItem::addGlobalScope(
                'jurusan_loan_visibility',
                static function (
                    Builder $builder
                ): void {
                    $user = Auth::user();

                    if (
                        $user === null
                        || (string) $user->role
                            === 'admin'
                    ) {
                        return;
                    }

                    $table =
                        $builder
                            ->getModel()
                            ->getTable();

                    $builder->whereExists(
                        function (
                            $query
                        ) use (
                            $user,
                            $table
                        ): void {
                            $query
                                ->selectRaw('1')
                                ->from('loans')
                                ->whereColumn(
                                    'loans.id',
                                    "{$table}.loan_id"
                                );

                            if (
                                in_array(
                                    (string)
                                    $user->role,
                                    [
                                        'kepala_bengkel',
                                        'toolman',
                                    ],
                                    true
                                )
                            ) {
                                $query->where(
                                    'loans.workshop_id',
                                    (int)
                                    $user->workshop_id
                                );

                                return;
                            }

                            $query->where(
                                'loans.borrower_id',
                                $user->id
                            );
                        }
                    );
                }
            );
        }
    }

    private function registerLoanRoutingEvents(
        LoanJurusanRoutingService
            $routing
    ): void {
        if (
            class_exists(Loan::class)
        ) {
            Loan::creating(
                static function (
                    Loan $loan
                ) use ($routing): void {
                    if (
                        ! Schema::hasColumn(
                            'loans',
                            'workshop_id'
                        )
                    ) {
                        return;
                    }

                    $request = request();

                    $workshopId =
                        $request->input(
                            'workshop_id'
                        );

                    if (
                        $workshopId === null
                        || $workshopId === ''
                    ) {
                        return;
                    }

                    $loan->setAttribute(
                        'workshop_id',
                        (int) $workshopId
                    );

                    if (
                        Schema::hasColumn(
                            'loans',
                            'assigned_toolman_id'
                        )
                    ) {
                        $toolmanId =
                            $request->input(
                                'assigned_toolman_id'
                            )
                            ?? $routing
                                ->activeToolmanId(
                                    (int) $workshopId
                                );

                        $loan->setAttribute(
                            'assigned_toolman_id',
                            $toolmanId
                        );
                    }
                }
            );
        }

        if (
            ! class_exists(
                LoanItem::class
            )
        ) {
            return;
        }

        LoanItem::creating(
            static function (
                LoanItem $loanItem
            ) use ($routing): void {
                $loan = Loan::query()
                    ->withoutGlobalScopes()
                    ->find(
                        $loanItem
                            ->getAttribute(
                                'loan_id'
                            )
                    );

                if ($loan === null) {
                    return;
                }

                $workshopId = null;

                $itemAssetId =
                    $loanItem->getAttribute(
                        'item_asset_id'
                    );

                if (
                    $itemAssetId !== null
                    && class_exists(
                        ItemAsset::class
                    )
                ) {
                    $workshopId =
                        ItemAsset::query()
                            ->withoutGlobalScopes()
                            ->whereKey(
                                $itemAssetId
                            )
                            ->value(
                                'workshop_id'
                            );
                }

                if ($workshopId === null) {
                    $itemId =
                        $loanItem
                            ->getAttribute(
                                'item_id'
                            );

                    if ($itemId !== null) {
                        $workshopId =
                            Item::query()
                                ->withoutGlobalScopes()
                                ->whereKey(
                                    $itemId
                                )
                                ->value(
                                    'workshop_id'
                                );
                    }
                }

                if ($workshopId !== null) {
                    $routing
                        ->routeLoanFromItem(
                            $loan,
                            (int) $workshopId
                        );
                }
            }
        );
    }
}
