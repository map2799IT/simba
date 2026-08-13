<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnLoanItemRequest extends FormRequest
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
            'return_notes' =>
                $this->filled('return_notes')
                    ? trim(
                        (string) $this->input(
                            'return_notes'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'condition_in' => [
                'required',
                Rule::in(
                    array_keys(
                        Item::conditionOptions()
                    )
                ),
            ],

            'return_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'condition_in.required' =>
                'Kondisi alat saat kembali wajib dipilih.',

            'condition_in.in' =>
                'Kondisi alat saat kembali tidak valid.',

            'return_notes.max' =>
                'Catatan pengembalian maksimal 3000 karakter.',
        ];
    }
}