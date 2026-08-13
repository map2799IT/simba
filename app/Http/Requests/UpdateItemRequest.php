<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'toolman') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Item|null $item */
            $item = $this->route('item');

            $category = ItemCategory::query()
                ->find($this->input('item_category_id'));

            if ($item === null || $category === null) {
                return;
            }

            if ($category->applies_to !== $item->type) {
                $validator->errors()->add(
                    'item_category_id',
                    'Kategori harus tetap sesuai dengan jenis master '.
                    ($item->type === 'tool' ? 'Alat.' : 'Bahan.')
                );
            }

            $tupleChanged =
                mb_strtolower(trim((string) $item->name))
                    !== mb_strtolower(trim((string) $this->input('name')))
                || (int) $item->item_category_id
                    !== (int) $this->input('item_category_id')
                || (int) $item->unit_id
                    !== (int) $this->input('unit_id');

            if (! $tupleChanged) {
                return;
            }

            $duplicate = Item::query()
                ->withoutGlobalScopes()
                ->where('name', $this->input('name'))
                ->where(
                    'item_category_id',
                    $this->input('item_category_id')
                )
                ->where('unit_id', $this->input('unit_id'))
                ->whereKeyNot($item->getKey())
                ->exists();

            if ($duplicate) {
                $validator->errors()->add(
                    'name',
                    'Master dengan nama, kategori, dan satuan yang sama sudah tersedia.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama master barang wajib diisi.',
            'item_category_id.required' => 'Kategori wajib dipilih.',
            'item_category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'unit_id.required' => 'Satuan wajib dipilih.',
            'unit_id.exists' => 'Satuan yang dipilih tidak valid.',
        ];
    }
}
