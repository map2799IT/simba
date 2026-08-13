<?php

namespace App\Http\Requests;

use App\Models\DamageReport;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDamageReportRequest extends FormRequest
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
            'description' => trim(
                (string) $this->input(
                    'description'
                )
            ),

            'notes' => $this->filled('notes')
                ? trim(
                    (string) $this->input(
                        'notes'
                    )
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'integer',
                'exists:items,id',
            ],

            'severity' => [
                'required',
                Rule::in(
                    array_keys(
                        DamageReport::severityOptions()
                    )
                ),
            ],

            'reported_at' => [
                'required',
                'date',
            ],

            'description' => [
                'required',
                'string',
                'max:5000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'evidence_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ): void {
            $item = Item::query()->find(
                $this->input('item_id')
            );

            if ($item === null) {
                return;
            }

            if (! $item->isTool()) {
                $validator->errors()->add(
                    'item_id',
                    'Laporan kerusakan hanya berlaku untuk alat.'
                );
            }

            if (! $item->is_active) {
                $validator->errors()->add(
                    'item_id',
                    'Alat yang dipilih sudah tidak aktif.'
                );
            }

            if ($item->status === 'borrowed') {
                $validator->errors()->add(
                    'item_id',
                    'Alat yang sedang dipinjam harus diproses melalui pengembalian terlebih dahulu.'
                );
            }

            $hasOpenReport = DamageReport::query()
                ->where(
                    'item_id',
                    $item->id
                )
                ->whereIn(
                    'status',
                    DamageReport::openStatuses()
                )
                ->exists();

            if ($hasOpenReport) {
                $validator->errors()->add(
                    'item_id',
                    'Alat ini masih memiliki laporan kerusakan yang belum selesai.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'item_id.required' =>
                'Alat wajib dipilih.',

            'item_id.exists' =>
                'Alat yang dipilih tidak ditemukan.',

            'severity.required' =>
                'Tingkat kerusakan wajib dipilih.',

            'severity.in' =>
                'Tingkat kerusakan tidak valid.',

            'reported_at.required' =>
                'Waktu laporan wajib diisi.',

            'reported_at.date' =>
                'Waktu laporan tidak valid.',

            'description.required' =>
                'Deskripsi kerusakan wajib diisi.',

            'description.max' =>
                'Deskripsi maksimal 5000 karakter.',

            'notes.max' =>
                'Catatan maksimal 3000 karakter.',

            'evidence_image.image' =>
                'File bukti harus berupa gambar.',

            'evidence_image.mimes' =>
                'Format bukti harus jpg, jpeg, png, atau webp.',

            'evidence_image.max' =>
                'Ukuran bukti maksimal 5 MB.',
        ];
    }
}