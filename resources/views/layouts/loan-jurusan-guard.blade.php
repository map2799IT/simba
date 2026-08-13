@auth
    @if (
        request()->routeIs(
            'loans.create'
        )
    )
        @php
            $loanUser = auth()->user();

            $loanWorkshops =
                \App\Models\Workshop::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('code')
                    ->get([
                        'id',
                        'code',
                        'name',
                    ]);

            $loanItemWorkshopMap =
                \App\Models\Item::query()
                    ->where(
                        'type',
                        'tool'
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->pluck(
                        'workshop_id',
                        'id'
                    );

            $singleWorkshop =
                in_array(
                    (string)
                    $loanUser->role,
                    [
                        'kepala_bengkel',
                        'toolman',
                        'siswa',
                    ],
                    true
                );

            $assignedWorkshopId =
                $loanUser->workshop_id;
        @endphp

        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {
                    const form = Array
                        .from(
                            document.querySelectorAll(
                                'form'
                            )
                        )
                        .find(
                            function (candidate) {
                                const action =
                                    candidate.getAttribute(
                                        'action'
                                    )
                                    || '';

                                const method =
                                    (
                                        candidate.getAttribute(
                                            'method'
                                        )
                                        || 'GET'
                                    ).toUpperCase();

                                return method === 'POST'
                                    && /\/loans\/?$/.test(
                                        new URL(
                                            action,
                                            window.location.origin
                                        ).pathname
                                    );
                            }
                        );

                    if (! form) {
                        return;
                    }

                    const workshops =
                        @json($loanWorkshops);

                    const itemWorkshopMap =
                        @json($loanItemWorkshopMap);

                    const singleWorkshop =
                        @json($singleWorkshop);

                    const assignedWorkshopId =
                        @json($assignedWorkshopId);

                    let field =
                        form.querySelector(
                            '[name="workshop_id"]'
                        );

                    if (! field) {
                        const wrapper =
                            document.createElement(
                                'div'
                            );

                        wrapper.className =
                            'alert alert-info mb-3';

                        const label =
                            document.createElement(
                                'label'
                            );

                        label.className =
                            'form-label fw-semibold';

                        label.textContent =
                            'Jurusan Tujuan Peminjaman';

                        field =
                            document.createElement(
                                singleWorkshop
                                    ? 'input'
                                    : 'select'
                            );

                        field.name =
                            'workshop_id';

                        if (singleWorkshop) {
                            field.type =
                                'hidden';

                            field.value =
                                assignedWorkshopId
                                || '';

                            const text =
                                document.createElement(
                                    'div'
                                );

                            const selected =
                                workshops.find(
                                    function (workshop) {
                                        return String(
                                            workshop.id
                                        ) === String(
                                            assignedWorkshopId
                                        );
                                    }
                                );

                            text.textContent =
                                selected
                                    ? selected.code
                                        + ' — '
                                        + selected.name
                                    : 'Akun belum memiliki jurusan.';

                            wrapper.appendChild(
                                label
                            );

                            wrapper.appendChild(
                                text
                            );

                            wrapper.appendChild(
                                field
                            );
                        } else {
                            field.className =
                                'form-select';

                            field.required =
                                true;

                            field.innerHTML =
                                '<option value="">Pilih jurusan</option>';

                            workshops.forEach(
                                function (workshop) {
                                    const option =
                                        document.createElement(
                                            'option'
                                        );

                                    option.value =
                                        workshop.id;

                                    option.textContent =
                                        workshop.code
                                        + ' — '
                                        + workshop.name;

                                    field.appendChild(
                                        option
                                    );
                                }
                            );

                            wrapper.appendChild(
                                label
                            );

                            wrapper.appendChild(
                                field
                            );

                            const help =
                                document.createElement(
                                    'div'
                                );

                            help.className =
                                'form-text';

                            help.textContent =
                                'Guru dapat memilih seluruh jurusan. Satu pengajuan hanya boleh untuk satu jurusan.';

                            wrapper.appendChild(
                                help
                            );
                        }

                        form.prepend(wrapper);
                    }

                    const filterItemOptions =
                        function () {
                            const workshopId =
                                String(
                                    field.value
                                    || ''
                                );

                            form
                                .querySelectorAll(
                                    'select[name*="item"]'
                                )
                                .forEach(
                                    function (select) {
                                        Array
                                            .from(
                                                select.options
                                            )
                                            .forEach(
                                                function (
                                                    option
                                                ) {
                                                    if (
                                                        ! option.value
                                                    ) {
                                                        return;
                                                    }

                                                    const itemWorkshopId =
                                                        itemWorkshopMap[
                                                            option.value
                                                        ];

                                                    if (
                                                        ! itemWorkshopId
                                                    ) {
                                                        return;
                                                    }

                                                    const visible =
                                                        workshopId === ''
                                                        || String(
                                                            itemWorkshopId
                                                        ) === workshopId;

                                                    option.hidden =
                                                        ! visible;

                                                    if (
                                                        ! visible
                                                        && option.selected
                                                    ) {
                                                        option.selected =
                                                            false;
                                                    }
                                                }
                                            );
                                    }
                                );
                        };

                    field.addEventListener(
                        'change',
                        filterItemOptions
                    );

                    form.addEventListener(
                        'submit',
                        function (event) {
                            if (! field.value) {
                                event.preventDefault();

                                alert(
                                    'Pilih jurusan tujuan peminjaman.'
                                );

                                field.focus();
                            }
                        }
                    );

                    filterItemOptions();
                }
            );
        </script>
    @endif
@endauth
