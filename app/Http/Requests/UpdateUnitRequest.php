<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
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
        /** @var Unit|null $unit */
        $unit = $this->route('unit');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9\-]+$/',

                Rule::unique('units', 'code')
                    ->ignore($unit?->id),
            ],

            'name' => [
                'required',
                'string',
                'max:50',

                Rule::unique('units', 'name')
                    ->ignore($unit?->id),
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