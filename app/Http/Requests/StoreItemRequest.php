<?php

namespace App\Http\Requests;

use App\Models\ItemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreItemRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('items', 'name')->where(
                    fn ($query) => $query
                        ->where(
                            'item_category_id',
                            $this->input('item_category_id')
                        )
                        ->where(
                            'unit_id',
                            $this->input('unit_id')
                        )
                ),
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $category = ItemCategory::query()
                ->find($this->input('item_category_id'));

            if ($category === null) {
                return;
            }

            if (! in_array(
                $category->applies_to,
                ['tool', 'material'],
                true
            )) {
                $validator->errors()->add(
                    'item_category_id',
                    'Kategori master harus khusus Alat atau khusus Bahan, tidak boleh berlaku untuk keduanya.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama master barang wajib diisi.',
            'name.unique' =>
                'Master dengan nama, kategori, dan satuan yang sama sudah tersedia.',
            'item_category_id.required' => 'Kategori wajib dipilih.',
            'item_category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'unit_id.required' => 'Satuan wajib dipilih.',
            'unit_id.exists' => 'Satuan yang dipilih tidak valid.',
        ];
    }
}
