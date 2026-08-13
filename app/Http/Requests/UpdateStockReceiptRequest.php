<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ItemStockMovement;
use App\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (
            $user === null
            || ! in_array(
                (string) $user->role,
                ['admin', 'toolman', 'kepala_bengkel'],
                true
            )
        ) {
            return false;
        }

        $movement = $this->route('stockReceipt');

        if (! $movement instanceof ItemStockMovement) {
            $movement = ItemStockMovement::query()
                ->withoutGlobalScopes()
                ->find($movement);
        }

        if ($movement === null) {
            return false;
        }

        if ((string) $user->role === 'admin') {
            return true;
        }

        return $user->workshop_id !== null
            && (int) $user->workshop_id === (int) $movement->workshop_id;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn (string $key): ?string =>
            $this->filled($key)
                ? trim((string) $this->input($key))
                : null;

        $this->merge([
            'document_number' => $this->filled('document_number')
                ? strtoupper(trim((string) $this->input('document_number')))
                : null,
            'source' => $clean('source'),
            'fund_source' => $clean('fund_source'),
            'brand' => $clean('brand'),
            'model' => $clean('model'),
            'specification' => $clean('specification'),
            'notes' => $clean('notes'),
            'change_reason' => $clean('change_reason'),
        ]);
    }

    public function rules(): array
    {
        return [
            'workshop_id' => ['required', 'integer', 'exists:workshops,id'],
            'storage_location_id' => [
                'required',
                'integer',
                'exists:storage_locations,id',
            ],
            'receipt_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:150'],
            'fund_source' => ['nullable', 'string', 'max:150'],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'specification' => ['nullable', 'string', 'max:3000'],
            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
            'condition' => [
                'required',
                Rule::in(array_keys(Item::conditionOptions())),
            ],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
            'notes' => ['nullable', 'string', 'max:3000'],
            'change_reason' => [
                Rule::requiredIf(
                    fn (): bool =>
                        (string) $this->user()?->role === 'toolman'
                ),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $movement = $this->route('stockReceipt');

            if (! $movement instanceof ItemStockMovement) {
                $movement = ItemStockMovement::query()
                    ->withoutGlobalScopes()
                    ->find($movement);
            }

            if ($movement === null) {
                return;
            }

            if ($movement->type !== ItemStockMovement::TYPE_INCOMING) {
                $validator->errors()->add(
                    'quantity',
                    'Data ini bukan transaksi Barang Masuk.'
                );
                return;
            }

            $user = $this->user();
            $workshopId = (int) $this->input('workshop_id');

            if (
                $user !== null
                && (string) $user->role !== 'admin'
                && (int) $user->workshop_id !== $workshopId
            ) {
                $validator->errors()->add(
                    'workshop_id',
                    'Toolman dan Kepala Bengkel hanya dapat mengubah Barang Masuk jurusannya.'
                );
            }

            $location = StorageLocation::query()
                ->withoutGlobalScopes()
                ->find($this->input('storage_location_id'));

            if (
                $location !== null
                && (int) $location->workshop_id !== $workshopId
            ) {
                $validator->errors()->add(
                    'storage_location_id',
                    'Lokasi harus berada pada jurusan tujuan.'
                );
            }

            if ($location !== null && ! $location->is_active) {
                $validator->errors()->add(
                    'storage_location_id',
                    'Lokasi penyimpanan sudah tidak aktif.'
                );
            }

            $item = Item::query()
                ->withoutGlobalScopes()
                ->with('unit')
                ->find($movement->item_id);

            if ($item === null) {
                return;
            }

            $quantity = (float) $this->input('quantity', 0);

            if (
                $item->isTool()
                && abs($quantity - round($quantity)) > 0.000001
            ) {
                $validator->errors()->add(
                    'quantity',
                    'Jumlah alat harus berupa bilangan bulat.'
                );
            }

            if (
                $item->unit !== null
                && ! $item->unit->allows_decimal
                && abs($quantity - round($quantity)) > 0.000001
            ) {
                $validator->errors()->add(
                    'quantity',
                    'Satuan barang ini tidak mengizinkan jumlah desimal.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'workshop_id.required' => 'Jurusan wajib dipilih.',
            'storage_location_id.required' =>
                'Lokasi penyimpanan wajib dipilih.',
            'receipt_date.required' => 'Tanggal perolehan wajib diisi.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.gt' => 'Jumlah harus lebih dari nol.',
            'change_reason.required' =>
                'Toolman wajib menjelaskan alasan perubahan.',
            'photo.max' => 'Ukuran foto maksimal 3 MB.',
        ];
    }
}
