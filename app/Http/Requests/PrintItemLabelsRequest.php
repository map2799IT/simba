<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintItemLabelsRequest extends FormRequest
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
            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*' => [
                'required',
                'integer',
                'distinct',
                'exists:items,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' =>
                'Pilih minimal satu barang untuk dicetak.',

            'items.min' =>
                'Pilih minimal satu barang untuk dicetak.',

            'items.max' =>
                'Sekali cetak maksimal 100 label.',

            'items.*.exists' =>
                'Salah satu barang yang dipilih tidak ditemukan.',
        ];
    }
}