<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkItemRequest extends FormRequest
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
        $type = (string) $this->input('type');

        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'brand' => $this->filled('brand')
                ? trim((string) $this->input('brand'))
                : null,

            'model' => $this->filled('model')
                ? trim((string) $this->input('model'))
                : null,

            'quantity' => $type === 'tool'
                ? $this->input('quantity', 1)
                : 1,

            'condition' => $type === 'material'
                ? 'good'
                : $this->input('condition', 'good'),

            'is_borrowable' => $this->boolean(
                'is_borrowable'
            ),

            'is_active' => $this->boolean(
                'is_active'
            ),

            'storage_location_id' =>
                $this->filled('storage_location_id')
                    ? $this->input('storage_location_id')
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(array_keys(Item::typeOptions())),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:500',
            ],

            'item_category_id' => [
                'required',
                'integer',
                'exists:item_categories,id',
            ],

            'unit_id' => [
                'required',
                'integer',
                'exists:units,id',
            ],

            'workshop_id' => [
                'required',
                'integer',
                'exists:workshops,id',
            ],

            'storage_location_id' => [
                'nullable',
                'integer',
                'exists:storage_locations,id',
            ],

            'brand' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
             * Satu nomor seri per baris.
             */
            'serial_numbers' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'received_date' => [
                'nullable',
                'date',
            ],

            'acquisition_source' => [
                'nullable',
                'string',
                'max:100',
            ],

            'fund_source' => [
                'nullable',
                'string',
                'max:100',
            ],

            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'condition' => [
                'required',
                Rule::in(
                    array_keys(Item::conditionOptions())
                ),
            ],

            'stock' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999999.999',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999999.999',
            ],

            'is_borrowable' => [
                'required',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ): void {
            $type = (string) $this->input('type');

            $category = ItemCategory::query()
                ->find($this->input('item_category_id'));

            if (
                $category !== null
                && ! in_array(
                    $category->applies_to,
                    [$type, 'both'],
                    true
                )
            ) {
                $validator->errors()->add(
                    'item_category_id',
                    'Kategori tidak sesuai dengan jenis barang.'
                );
            }

            $locationId = $this->input(
                'storage_location_id'
            );

            if ($locationId) {
                $location = StorageLocation::query()
                    ->find($locationId);

                if (
                    $location !== null
                    && $location->workshop_id !==
                        (int) $this->input('workshop_id')
                ) {
                    $validator->errors()->add(
                        'storage_location_id',
                        'Lokasi harus berada pada bengkel yang dipilih.'
                    );
                }

                if (
                    $location !== null
                    && ! $location->is_active
                ) {
                    $validator->errors()->add(
                        'storage_location_id',
                        'Lokasi yang dipilih sudah tidak aktif.'
                    );
                }
            }

            if ($type === 'material') {
                if (
                    $this->input('stock') === null
                    || $this->input('stock') === ''
                ) {
                    $validator->errors()->add(
                        'stock',
                        'Stok awal bahan wajib diisi.'
                    );
                }

                if (
                    $this->input('minimum_stock') === null
                    || $this->input('minimum_stock') === ''
                ) {
                    $validator->errors()->add(
                        'minimum_stock',
                        'Stok minimum bahan wajib diisi.'
                    );
                }
            }

            if ($type !== 'tool') {
                return;
            }

            $serialNumbers = $this->serialNumbers();

            /*
             * Nomor seri boleh kosong seluruhnya. Ketika diisi,
             * jumlahnya harus sama dengan jumlah alat.
             */
            if (
                $serialNumbers !== []
                && count($serialNumbers) !==
                    (int) $this->input('quantity')
            ) {
                $validator->errors()->add(
                    'serial_numbers',
                    'Jumlah nomor seri harus sama dengan jumlah alat.'
                );
            }

            if (
                count($serialNumbers) !==
                count(array_unique($serialNumbers))
            ) {
                $validator->errors()->add(
                    'serial_numbers',
                    'Terdapat nomor seri yang sama pada input.'
                );
            }

            if (
                $serialNumbers !== []
                && Item::query()
                    ->whereIn(
                        'serial_number',
                        $serialNumbers
                    )
                    ->exists()
            ) {
                $validator->errors()->add(
                    'serial_numbers',
                    'Salah satu nomor seri sudah terdaftar.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required' =>
                'Jenis barang wajib dipilih.',

            'name.required' =>
                'Nama barang wajib diisi.',

            'quantity.required' =>
                'Jumlah barang wajib diisi.',

            'quantity.min' =>
                'Jumlah barang minimal satu.',

            'quantity.max' =>
                'Sekali input maksimal 500 alat.',

            'item_category_id.required' =>
                'Kategori wajib dipilih.',

            'unit_id.required' =>
                'Satuan wajib dipilih.',

            'workshop_id.required' =>
                'Bengkel wajib dipilih.',

            'storage_location_id.exists' =>
                'Lokasi penyimpanan tidak valid.',

            'unit_price.numeric' =>
                'Harga satuan harus berupa angka.',

            'stock.numeric' =>
                'Stok harus berupa angka.',

            'minimum_stock.numeric' =>
                'Stok minimum harus berupa angka.',
        ];
    }

    private function serialNumbers(): array
    {
        $value = trim(
            (string) $this->input('serial_numbers')
        );

        if ($value === '') {
            return [];
        }

        return collect(
            preg_split('/\R+/', $value) ?: []
        )
            ->map(
                fn (string $serial) =>
                    strtoupper(trim($serial))
            )
            ->filter()
            ->values()
            ->all();
    }
}