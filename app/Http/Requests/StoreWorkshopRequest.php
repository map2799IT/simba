<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
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
                'max:30',
                'regex:/^[A-Z0-9\-]+$/',
                'unique:workshops,code',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'department' => [
                'nullable',
                'string',
                'max:100',
            ],

            'physical_location' => [
                'nullable',
                'string',
                'max:150',
            ],

            'head_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
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
            'code.required' => 'Kode bengkel wajib diisi.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode bengkel sudah digunakan.',
            'name.required' => 'Nama bengkel wajib diisi.',
            'head_user_id.exists' => 'Kepala bengkel yang dipilih tidak valid.',
        ];
    }
}