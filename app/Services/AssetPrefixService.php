<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetPrefixService
{
    private const MAX_LENGTH = 18;

    /**
     * Alias yang membuat kode lebih ringkas dan mudah dibaca.
     */
    private const ALIASES = [
        'ROUTERBOARD' => 'ROUTER',
        'ROUTER BOARD' => 'ROUTER',
        'ROUTER' => 'ROUTER',
        'TANG CRIMPING' => 'CRIMP',
        'CRIMPING TOOL' => 'CRIMP',
        'CRIMPING' => 'CRIMP',
        'MONITOR' => 'MONITOR',
        'CPU' => 'CPU',
        'CENTRAL PROCESSING UNIT' => 'CPU',
        'PRINTER' => 'PRINTER',
        'KEYBOARD' => 'KEYBOARD',
        'MOUSE' => 'MOUSE',
        'LAPTOP' => 'LAPTOP',
        'KOMPUTER' => 'KOMPUTER',
        'ACCESS POINT' => 'AP',
        'SWITCH' => 'SWITCH',
        'HUB' => 'HUB',
        'PROYEKTOR' => 'PROYEKTOR',
        'PROJECTOR' => 'PROYEKTOR',
        'MULTIMETER' => 'MULTIMETER',
        'BOR' => 'BOR',
        'GERINDA' => 'GERINDA',
    ];

    public function prefixFor(
        Item $item,
        bool $persist = true
    ): string {
        $stored =
            $this->sanitize(
                (string)
                $item->getAttribute(
                    'asset_prefix'
                )
            );

        if ($stored !== '') {
            return $stored;
        }

        $base =
            $this->baseFromItem(
                $item
            );

        $prefix =
            $this->uniquePrefix(
                $item,
                $base
            );

        if ($persist) {
            DB::table('items')
                ->where(
                    'id',
                    $item->id
                )
                ->update([
                    'asset_prefix' =>
                        $prefix,

                    'updated_at' =>
                        now(),
                ]);

            $item->setAttribute(
                'asset_prefix',
                $prefix
            );
        }

        return $prefix;
    }

    public function baseFromItem(
        Item $item
    ): string {
        $name =
            strtoupper(
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        Str::ascii(
                            (string)
                            $item->name
                        )
                    )
                    ?: ''
                )
            );

        if (
            isset(
                self::ALIASES[$name]
            )
        ) {
            return self::ALIASES[
                $name
            ];
        }

        foreach (
            self::ALIASES
            as $needle => $alias
        ) {
            if (
                str_contains(
                    $name,
                    $needle
                )
            ) {
                return $alias;
            }
        }

        $words =
            preg_split(
                '/[^A-Z0-9]+/',
                $name,
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: [];

        $ignored = [
            'ALAT',
            'MESIN',
            'UNIT',
            'PERALATAN',
            'ELEKTRONIK',
            'LISTRIK',
            'DIGITAL',
        ];

        $words =
            array_values(
                array_filter(
                    $words,
                    static fn (
                        string $word
                    ): bool =>
                        ! in_array(
                            $word,
                            $ignored,
                            true
                        )
                )
            );

        $candidate =
            implode(
                '',
                array_slice(
                    $words,
                    0,
                    2
                )
            );

        $candidate =
            $this->sanitize(
                $candidate
            );

        if ($candidate === '') {
            $candidate =
                $this->sanitize(
                    (string)
                    $item->code
                );
        }

        if ($candidate === '') {
            $candidate =
                'ITEM'.
                (int) $item->id;
        }

        return substr(
            $candidate,
            0,
            self::MAX_LENGTH
        );
    }

    public function sanitize(
        string $value
    ): string {
        $value =
            strtoupper(
                Str::ascii(
                    trim($value)
                )
            );

        $value =
            preg_replace(
                '/[^A-Z0-9]+/',
                '',
                $value
            ) ?: '';

        return substr(
            $value,
            0,
            self::MAX_LENGTH
        );
    }

    private function uniquePrefix(
        Item $item,
        string $base
    ): string {
        $candidate =
            $base !== ''
                ? $base
                : 'ITEM'.
                    (int) $item->id;

        $sequence = 1;

        while (
            DB::table('items')
                ->where(
                    'type',
                    'tool'
                )
                ->where(
                    'id',
                    '!=',
                    $item->id
                )
                ->where(
                    'asset_prefix',
                    $candidate
                )
                ->exists()
        ) {
            $suffix =
                (string) $sequence;

            $candidate =
                substr(
                    $base,
                    0,
                    self::MAX_LENGTH
                    - strlen($suffix)
                ).
                $suffix;

            $sequence++;

            if ($sequence > 9999) {
                throw ValidationException::
                    withMessages([
                        'asset_prefix' =>
                            'Tidak dapat membuat prefix unik untuk barang '.
                            $item->name.
                            '.',
                    ]);
            }
        }

        return $candidate;
    }
}
