<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'admin',
            'toolman'
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',

                File::types([
                    'xlsx',
                    'xls',
                ])->max('10mb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' =>
                'Berkas Excel wajib dipilih.',

            'file.mimes' =>
                'Berkas harus berformat XLSX atau XLS.',

            'file.max' =>
                'Ukuran berkas maksimal 10 MB.',
        ];
    }
}