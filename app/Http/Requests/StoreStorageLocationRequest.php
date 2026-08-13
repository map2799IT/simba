<?php

namespace App\Http\Requests;

use App\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStorageLocationRequest extends FormRequest
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

            'parent_id' => $this->filled('parent_id')
                ? $this->input('parent_id')
                : null,
        ]);
    }

    public function rules(): array
    {
        $workshopId = (int) $this->input(
            'workshop_id'
        );

        return [
            'workshop_id' => [
                'required',
                'integer',
                'exists:workshops,id',
            ],

            'parent_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'storage_locations',
                    'id'
                )->where(function ($query) use ($workshopId) {
                    $query->where(
                        'workshop_id',
                        $workshopId
                    );
                }),
            ],

            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9\-]+$/',

                Rule::unique(
                    'storage_locations',
                    'code'
                )->where(function ($query) use ($workshopId) {
                    $query->where(
                        'workshop_id',
                        $workshopId
                    );
                }),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'type' => [
                'required',

                Rule::in(
                    array_keys(
                        StorageLocation::typeOptions()
                    )
                ),
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

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ): void {
            $type = (string) $this->input('type');
            $parentId = $this->input('parent_id');

            if ($type === 'room' && $parentId) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi bertipe Ruang tidak boleh memiliki induk.'
                );

                return;
            }

            if ($type !== 'room' && ! $parentId) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi ini harus memiliki lokasi induk.'
                );

                return;
            }

            if (! $parentId) {
                return;
            }

            $parent = StorageLocation::query()
                ->find($parentId);

            if (! $parent) {
                return;
            }

            if (
                $parent->workshop_id !==
                (int) $this->input('workshop_id')
            ) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi induk harus berada pada bengkel yang sama.'
                );
            }

            $allowedParentTypes =
                StorageLocation::allowedParentTypes(
                    $type
                );

            if (
                ! in_array(
                    $parent->type,
                    $allowedParentTypes,
                    true
                )
            ) {
                $allowedLabels = collect(
                    $allowedParentTypes
                )
                    ->map(
                        fn (string $allowedType) =>
                            StorageLocation::TYPES[
                                $allowedType
                            ] ?? $allowedType
                    )
                    ->implode(' atau ');

                $validator->errors()->add(
                    'parent_id',
                    "Induk lokasi harus bertipe {$allowedLabels}."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'workshop_id.required' =>
                'Bengkel wajib dipilih.',

            'workshop_id.exists' =>
                'Bengkel yang dipilih tidak valid.',

            'code.required' =>
                'Kode lokasi wajib diisi.',

            'code.regex' =>
                'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',

            'code.unique' =>
                'Kode lokasi sudah digunakan pada bengkel tersebut.',

            'name.required' =>
                'Nama lokasi wajib diisi.',

            'type.required' =>
                'Jenis lokasi wajib dipilih.',

            'type.in' =>
                'Jenis lokasi tidak valid.',

            'parent_id.exists' =>
                'Lokasi induk tidak valid.',
        ];
    }
}