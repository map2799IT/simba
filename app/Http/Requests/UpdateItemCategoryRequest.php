<?php

namespace App\Http\Requests;

use App\Models\ItemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemCategoryRequest extends FormRequest
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
            'code' => strtoupper(
                trim((string) $this->input('code'))
            ),

            'name' => trim(
                (string) $this->input('name')
            ),
        ]);
    }

    public function rules(): array
    {
        /** @var ItemCategory|null $itemCategory */
        $itemCategory = $this->route('itemCategory');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9\-]+$/',

                Rule::unique(
                    'item_categories',
                    'code'
                )->ignore($itemCategory?->id),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'applies_to' => [
                'required',
                Rule::in(
                    array_keys(
                        ItemCategory::appliesToOptions()
                    )
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' =>
                'Kode kategori wajib diisi.',

            'code.regex' =>
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',

            'code.unique' =>
                'Kode kategori sudah digunakan.',

            'name.required' =>
                'Nama kategori wajib diisi.',

            'applies_to.required' =>
                'Penggunaan kategori wajib dipilih.',

            'applies_to.in' =>
                'Jenis penggunaan kategori tidak valid.',
        ];
    }
}