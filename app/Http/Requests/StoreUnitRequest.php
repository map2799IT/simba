<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',
                'unique:units,code',
            ],

            'name' => [
                'required',
                'string',
                'max:50',
                'unique:units,name',
            ],

            'allows_decimal' => [
                'required',
                'boolean',
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
                'Kode satuan wajib diisi.',

            'code.regex' =>
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',

            'code.unique' =>
                'Kode satuan sudah digunakan.',

            'name.required' =>
                'Nama satuan wajib diisi.',

            'name.unique' =>
                'Nama satuan sudah digunakan.',
        ];
    }
}