<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDamageReportRequest extends FormRequest
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
            'diagnosis' => trim(
                (string) $this->input(
                    'diagnosis'
                )
            ),

            'action_taken' => trim(
                (string) $this->input(
                    'action_taken'
                )
            ),

            'vendor' => $this->filled('vendor')
                ? trim(
                    (string) $this->input(
                        'vendor'
                    )
                )
                : null,

            'resolution_notes' =>
                $this->filled('resolution_notes')
                    ? trim(
                        (string) $this->input(
                            'resolution_notes'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'resolution' => [
                'required',
                Rule::in([
                    'repaired',
                    'unrepairable',
                ]),
            ],

            'condition_after' => [
                'nullable',
                'required_if:resolution,repaired',
                Rule::in(
                    array_keys(
                        Item::conditionOptions()
                    )
                ),
            ],

            'diagnosis' => [
                'required',
                'string',
                'max:5000',
            ],

            'action_taken' => [
                'required',
                'string',
                'max:5000',
            ],

            'vendor' => [
                'nullable',
                'string',
                'max:150',
            ],

            'repair_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'resolution_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required' =>
                'Hasil penanganan wajib dipilih.',

            'resolution.in' =>
                'Hasil penanganan tidak valid.',

            'condition_after.required_if' =>
                'Kondisi setelah perbaikan wajib dipilih.',

            'condition_after.in' =>
                'Kondisi setelah perbaikan tidak valid.',

            'diagnosis.required' =>
                'Diagnosis kerusakan wajib diisi.',

            'diagnosis.max' =>
                'Diagnosis maksimal 5000 karakter.',

            'action_taken.required' =>
                'Tindakan perbaikan wajib diisi.',

            'action_taken.max' =>
                'Tindakan maksimal 5000 karakter.',

            'vendor.max' =>
                'Nama vendor maksimal 150 karakter.',

            'repair_cost.numeric' =>
                'Biaya perbaikan harus berupa angka.',

            'repair_cost.min' =>
                'Biaya perbaikan tidak boleh negatif.',

            'resolution_notes.max' =>
                'Catatan penyelesaian maksimal 3000 karakter.',
        ];
    }
}