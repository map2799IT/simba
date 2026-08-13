<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'toolman', 'kepala_bengkel') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('items', []))
            ->map(static function (mixed $row): array {
                $row = is_array($row) ? $row : [];

                $clean = static fn (string $key): ?string =>
                    isset($row[$key])
                    && trim((string) $row[$key]) !== ''
                        ? trim((string) $row[$key])
                        : null;

                return [
                    'item_id' => $row['item_id'] ?? null,
                    'quantity' => $row['quantity'] ?? null,
                    'storage_location_id' =>
                        $row['storage_location_id'] ?? null,
                    'brand' => $clean('brand'),
                    'model' => $clean('model'),
                    'specification' => $clean('specification'),
                    'unit_price' => $row['unit_price'] ?? null,
                    'minimum_stock' => $row['minimum_stock'] ?? null,
                    'condition' => $row['condition'] ?? 'good',
                    'notes' => $clean('notes'),
                ];
            })
            ->values()
            ->all();

        $cleanHeader = fn (string $key): ?string =>
            $this->filled($key)
                ? trim((string) $this->input($key))
                : null;

        $this->merge([
            'document_number' => $this->filled('document_number')
                ? strtoupper(trim((string) $this->input('document_number')))
                : null,
            'source' => $cleanHeader('source'),
            'fund_source' => $cleanHeader('fund_source'),
            'notes' => $cleanHeader('notes'),
            'items' => $rows,
        ]);
    }

    public function rules(): array
    {
        return [
            'workshop_id' => [
                'required',
                'integer',
                'exists:workshops,id',
            ],
            'receipt_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:150'],
            'fund_source' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],

            /*
             * Master yang sama boleh muncul beberapa kali
             * apabila merek/model/spesifikasinya berbeda.
             */
            'items.*.item_id' => [
                'required',
                'integer',
                'exists:items,id',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],
            'items.*.storage_location_id' => [
                'required',
                'integer',
                'exists:storage_locations,id',
            ],
            'items.*.brand' => [
                'nullable',
                'string',
                'max:100',
            ],
            'items.*.model' => [
                'nullable',
                'string',
                'max:100',
            ],
            'items.*.specification' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
            'items.*.minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999999.999',
            ],
            'items.*.condition' => [
                'required',
                Rule::in(array_keys(Item::conditionOptions())),
            ],
            'items.*.photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
            'items.*.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $workshopId = (int) $this->input('workshop_id');

            if (
                $user !== null
                && (string) $user->role === 'toolman'
                && (int) $user->workshop_id !== $workshopId
            ) {
                $validator->errors()->add(
                    'workshop_id',
                    'Toolman hanya dapat menerima barang untuk jurusannya.'
                );
            }

            foreach ($this->input('items', []) as $index => $row) {
                $item = Item::query()
                    ->withoutGlobalScopes()
                    ->with('unit')
                    ->find($row['item_id'] ?? null);

                if ($item === null) {
                    continue;
                }

                if (! $item->is_active) {
                    $validator->errors()->add(
                        "items.{$index}.item_id",
                        'Master barang yang dipilih sudah tidak aktif.'
                    );
                }

                $location = StorageLocation::query()
                    ->withoutGlobalScopes()
                    ->find($row['storage_location_id'] ?? null);

                if (
                    $location !== null
                    && (int) $location->workshop_id !== $workshopId
                ) {
                    $validator->errors()->add(
                        "items.{$index}.storage_location_id",
                        'Lokasi harus berada pada bengkel tujuan.'
                    );
                }

                if ($location !== null && ! $location->is_active) {
                    $validator->errors()->add(
                        "items.{$index}.storage_location_id",
                        'Lokasi penyimpanan sudah tidak aktif.'
                    );
                }

                $quantity = (float) ($row['quantity'] ?? 0);

                if (
                    $item->isTool()
                    && abs($quantity - round($quantity)) > 0.000001
                ) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        'Jumlah alat harus berupa bilangan bulat.'
                    );
                }

                if (
                    $item->unit !== null
                    && ! $item->unit->allows_decimal
                    && abs($quantity - round($quantity)) > 0.000001
                ) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        'Satuan barang ini tidak mengizinkan jumlah desimal.'
                    );
                }

                $minimumStock =
                    (float)
                    (
                        $row[
                            'minimum_stock'
                        ]
                        ?? 0
                    );

                if (
                    $item->isMaterial()
                    && $item->unit !== null
                    && ! $item->unit->allows_decimal
                    && abs(
                        $minimumStock
                        - round(
                            $minimumStock
                        )
                    ) > 0.000001
                ) {
                    $validator->errors()->add(
                        "items.{$index}.minimum_stock",
                        'Stok minimum harus berupa bilangan bulat karena satuan ini tidak mengizinkan desimal.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'workshop_id.required' => 'Bengkel tujuan wajib dipilih.',
            'receipt_date.required' => 'Tanggal perolehan wajib diisi.',
            'items.required' => 'Minimal satu detail barang wajib diisi.',
            'items.*.item_id.required' => 'Master barang wajib dipilih.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih dari nol.',
            'items.*.storage_location_id.required' =>
                'Lokasi penyimpanan wajib dipilih.',
            'items.*.brand.max' => 'Merek maksimal 100 karakter.',
            'items.*.model.max' => 'Model/Tipe maksimal 100 karakter.',
            'items.*.specification.max' =>
                'Spesifikasi maksimal 3000 karakter.',
            'items.*.photo.max' => 'Ukuran foto maksimal 3 MB.',
        ];
    }
}
