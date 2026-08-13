<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'admin',
            'kepala_bengkel',
            'toolman',
            'guru',
            'siswa'
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'purpose' => trim(
                (string) $this->input('purpose')
            ),

            'notes' => $this->filled('notes')
                ? trim(
                    (string) $this->input('notes')
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'items' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'items.*' => [
                'required',
                'integer',
                'distinct',
                'exists:items,id',
            ],

            'due_at' => [
                'required',
                'date',
                'after:now',
            ],

            'purpose' => [
                'required',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' =>
                'Pilih minimal satu alat.',

            'items.min' =>
                'Pilih minimal satu alat.',

            'items.max' =>
                'Sekali peminjaman maksimal 20 alat.',

            'items.*.distinct' =>
                'Terdapat alat yang dipilih lebih dari satu kali.',

            'items.*.exists' =>
                'Salah satu alat tidak ditemukan.',

            'due_at.required' =>
                'Batas waktu pengembalian wajib diisi.',

            'due_at.date' =>
                'Batas waktu pengembalian tidak valid.',

            'due_at.after' =>
                'Batas waktu pengembalian harus setelah waktu sekarang.',

            'purpose.required' =>
                'Keperluan peminjaman wajib diisi.',

            'purpose.max' =>
                'Keperluan maksimal 255 karakter.',
        ];
    }
}