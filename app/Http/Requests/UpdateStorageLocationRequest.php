<?php

namespace App\Http\Requests;

use App\Models\StorageLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStorageLocationRequest extends FormRequest
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
        /** @var StorageLocation|null $location */
        $location = $this->route(
            'storageLocation'
        );

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
                )
                    ->where(function (
                        $query
                    ) use ($workshopId) {
                        $query->where(
                            'workshop_id',
                            $workshopId
                        );
                    })
                    ->ignore($location?->id),
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
            /** @var StorageLocation|null $location */
            $location = $this->route(
                'storageLocation'
            );

            if (! $location) {
                return;
            }

            $type = (string) $this->input('type');
            $parentId = $this->input('parent_id');

            if ($type === 'room' && $parentId) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi bertipe Ruang tidak boleh memiliki induk.'
                );
            }

            if ($type !== 'room' && ! $parentId) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi ini harus memiliki lokasi induk.'
                );
            }

            if (
                $parentId &&
                (int) $parentId === $location->id
            ) {
                $validator->errors()->add(
                    'parent_id',
                    'Lokasi tidak dapat menjadi induk bagi dirinya sendiri.'
                );

                return;
            }

            if ($parentId) {
                $parent = StorageLocation::query()
                    ->find($parentId);

                if ($parent) {
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
                        $validator->errors()->add(
                            'parent_id',
                            'Jenis lokasi induk tidak sesuai.'
                        );
                    }

                    $currentParent = $parent;

                    while ($currentParent !== null) {
                        if (
                            $currentParent->id ===
                            $location->id
                        ) {
                            $validator->errors()->add(
                                'parent_id',
                                'Lokasi turunan tidak dapat dijadikan induk karena akan membentuk siklus.'
                            );

                            break;
                        }

                        $currentParent =
                            $currentParent->parent;
                    }
                }
            }

            if (
                $location->children()->exists() &&
                $location->workshop_id !==
                    (int) $this->input('workshop_id')
            ) {
                $validator->errors()->add(
                    'workshop_id',
                    'Bengkel tidak dapat diubah karena lokasi ini masih mempunyai lokasi turunan.'
                );
            }

            $newType = (string) $this->input('type');

            foreach ($location->children as $child) {
                $allowedParentTypes =
                    StorageLocation::allowedParentTypes(
                        $child->type
                    );

                if (
                    ! in_array(
                        $newType,
                        $allowedParentTypes,
                        true
                    )
                ) {
                    $validator->errors()->add(
                        'type',
                        'Jenis lokasi tidak dapat diubah karena tidak sesuai dengan lokasi turunannya.'
                    );

                    break;
                }
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