<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStockIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'admin',
            'toolman'
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_number' =>
                $this->filled('reference_number')
                    ? strtoupper(
                        trim(
                            (string) $this->input(
                                'reference_number'
                            )
                        )
                    )
                    : null,

            'destination' =>
                $this->filled('destination')
                    ? trim(
                        (string) $this->input(
                            'destination'
                        )
                    )
                    : null,

            'purpose' =>
                $this->filled('purpose')
                    ? trim(
                        (string) $this->input(
                            'purpose'
                        )
                    )
                    : null,

            'description' =>
                $this->filled('description')
                    ? trim(
                        (string) $this->input(
                            'description'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'integer',
                'exists:items,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'destination' => [
                'required',
                'string',
                'max:150',
            ],

            'purpose' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ): void {
            $item = Item::query()
                ->with('unit')
                ->find(
                    $this->input('item_id')
                );

            if ($item === null) {
                return;
            }

            if (! $item->isMaterial()) {
                $validator->errors()->add(
                    'item_id',
                    'Barang keluar melalui modul ini hanya berlaku untuk bahan.'
                );
            }

            if (! $item->is_active) {
                $validator->errors()->add(
                    'item_id',
                    'Bahan yang dipilih sudah tidak aktif.'
                );
            }

            $quantity = (float) $this->input(
                'quantity',
                0
            );

            $currentStock = (float) $item->stock;

            if ($quantity > $currentStock) {
                $validator->errors()->add(
                    'quantity',
                    'Jumlah barang keluar melebihi stok yang tersedia.'
                );
            }

            if (
                $item->unit !== null
                && ! $item->unit->allows_decimal
                && abs(
                    $quantity - round($quantity)
                ) > 0.000001
            ) {
                $validator->errors()->add(
                    'quantity',
                    'Satuan bahan ini tidak mengizinkan jumlah desimal.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'item_id.required' =>
                'Bahan wajib dipilih.',

            'item_id.exists' =>
                'Bahan yang dipilih tidak ditemukan.',

            'quantity.required' =>
                'Jumlah barang keluar wajib diisi.',

            'quantity.numeric' =>
                'Jumlah barang keluar harus berupa angka.',

            'quantity.gt' =>
                'Jumlah barang keluar harus lebih dari nol.',

            'transaction_date.required' =>
                'Tanggal transaksi wajib diisi.',

            'transaction_date.date' =>
                'Tanggal transaksi tidak valid.',

            'destination.required' =>
                'Tujuan atau penerima bahan wajib diisi.',

            'destination.max' =>
                'Tujuan maksimal 150 karakter.',

            'purpose.required' =>
                'Keperluan penggunaan bahan wajib diisi.',

            'purpose.max' =>
                'Keperluan maksimal 255 karakter.',

            'reference_number.max' =>
                'Nomor referensi maksimal 100 karakter.',
        ];
    }
}