<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
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
            'rejection_reason' => trim(
                (string) $this->input(
                    'rejection_reason'
                )
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' =>
                'Alasan penolakan wajib diisi.',

            'rejection_reason.max' =>
                'Alasan penolakan maksimal 3000 karakter.',
        ];
    }
}