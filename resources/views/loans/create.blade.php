@extends('layouts.app')

@section('title', 'Buat Peminjaman')
@section('page-title', 'Buat Peminjaman')

@section('content')
    @php
        $oldRows =
            old(
                'items',
                [
                    [
                        'item_id' => '',
                        'quantity' => 1,
                    ],
                ]
            );

        $toolAssetMap =
            $assets
                ->groupBy('item_id')
                ->map(
                    fn ($rows) =>
                        $rows
                            ->sortBy('asset_number')
                            ->values()
                            ->map(
                                fn ($asset) => [
                                    'id' => $asset->id,
                                    'number' => $asset->asset_number,
                                    'serial' => $asset->serial_number,
                                    'location' =>
                                        $asset
                                            ->storageLocation
                                            ?->name
                                        ?? 'Tanpa lokasi',
                                ]
                            )
                            ->values()
                )
                ->all();
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Buat Pengajuan Peminjaman Terjadwal</h1>
            <p class="mt-1 text-sm text-slate-500">Alat memakai nomor inventaris/QR. Bahan memakai jumlah dan tidak dikembalikan.</p>
        </div>
        <x-button href="{{ route('loans.index') }}" variant="secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </x-button>
    </div>

    @if ($errors->any())
        <div id="loan-validation-errors" class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="text-sm text-red-800">
                <p class="font-semibold">Pengajuan belum tersimpan. Periksa data berikut:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (isset($workshopToolmen) && $workshopToolmen->isEmpty())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <p class="text-sm text-amber-800">Jurusan <strong>{{ $selectedWorkshop->code }}</strong> belum mempunyai akun Toolman aktif. Pengajuan siswa/guru belum dapat dikirim sampai Toolman jurusan dibuat atau diaktifkan.</p>
        </div>
    @elseif (isset($workshopToolmen) && $workshopToolmen->isNotEmpty())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500 text-white"><i class="bi bi-info-lg"></i></span>
            <p class="text-sm text-blue-800">Pengajuan akan masuk ke antrean Toolman <strong>{{ $selectedWorkshop->code }}</strong>: {{ $workshopToolmen->pluck('name')->implode(', ') }}.</p>
        </div>
    @endif

    <div class="alert alert-info">
        <div>
            Pengajuan belum mengubah stok.
            Pengajuan masuk ke Toolman jurusan yang dipilih.
            Stok baru berkurang saat jadwal tiba dan Toolman melakukan serah terima.
        </div>

        <div class="mt-2 fw-semibold">
            <i class="bi bi-clock-history me-1"></i>
            @if ($borrowerRole === 'siswa')
                <span class="text-danger">Batas pengembalian: HARI INI pukul 15.00 WIB (hari yang sama).</span>
            @elseif ($borrowerRole === 'guru')
                <span>Jatuh tempo otomatis: +3 hari dari waktu peminjaman.</span>
            @else
                <span>{{ $dueRuleText }}</span>
            @endif
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">
                Barang pada jurusan
                {{ $selectedWorkshop->code }}
                belum dapat ditampilkan.
            </div>

            <div>
                Unit alat tersedia:
                {{ $inventorySummary['available_asset_units'] }}.
                Jenis bahan tersedia:
                {{ $inventorySummary['available_material_items'] }}.
                Movement jurusan:
                {{ $inventorySummary['movement_rows'] }}.
            </div>

            @if ($canSelectWorkshop)
                <div class="mt-1">
                    Pilih jurusan lain yang mempunyai stok.
                </div>
            @else
                <div class="mt-1">
                    Pastikan Barang Masuk menyimpan workshop_id jurusan ini
                    dan unit alat berstatus available.
                </div>
            @endif
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('loans.store') }}"
        id="loan-form"
    >
        @csrf

        <section class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="h6 fw-bold mb-0">
                    Informasi Peminjaman
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 md:grid-cols-2">

                {{-- Jurusan --}}
                <div>
                    <label for="workshop_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Jurusan</label>
                    @if ($canSelectWorkshop)
                        <select id="workshop_id" name="workshop_id" required
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}" @selected((int) $selectedWorkshopId === (int) $workshop->id)>
                                    {{ $workshop->code }} — {{ $workshop->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($borrowerRole === 'guru')
                            <p class="mt-1.5 text-xs text-slate-500">Guru dapat memilih seluruh jurusan. Pengajuan hanya masuk ke Toolman jurusan ini.</p>
                        @endif
                    @else
                        <input type="hidden" id="workshop_id" name="workshop_id" value="{{ $selectedWorkshopId }}">
                        @error('workshop_id')
                            <p class="mb-1 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>
                        @enderror
                        <input type="text" class="w-full rounded-xl border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500"
                            value="{{ $selectedWorkshop->code }} — {{ $selectedWorkshop->name }}" disabled>
                        <p class="mt-1.5 text-xs text-slate-500">Siswa hanya dapat meminjam dari jurusannya.</p>
                    @endif
                </div>

                {{-- Peminjam --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Peminjam</label>
                    @if ($isBorrowerOnly)
                        <input type="hidden" name="borrower_id" value="{{ auth()->id() }}">
                        <input type="text" class="w-full rounded-xl border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-500"
                            value="{{ auth()->user()->name }} — {{ ucfirst($borrowerRole) }}" disabled>
                    @else
                        <select id="borrower_id" name="borrower_id" required
                            class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($borrowers as $borrower)
                                <option value="{{ $borrower->id }}" @selected((string) old('borrower_id', auth()->id()) === (string) $borrower->id)>
                                    {{ $borrower->name }} — {{ $borrower->role }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Jatuh Tempo --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Jatuh Tempo</label>
                    @if ($borrowerRole === 'siswa')
                        <div class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-semibold text-red-700">
                            <i class="bi bi-calendar-x shrink-0"></i>
                            <span>{{ $previewDueAt->translatedFormat('d F Y') }}, pukul 15.00 WIB</span>
                        </div>
                        <p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-triangle me-1"></i>Siswa wajib mengembalikan barang pada hari yang sama.</p>
                    @elseif ($borrowerRole === 'guru')
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-600">
                            <i class="bi bi-calendar shrink-0"></i>
                            <span>{{ $previewDueAt->translatedFormat('d F Y, H:i') }} WIB</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500"><i class="bi bi-info-circle me-1"></i>Jatuh tempo otomatis 3 hari dari waktu peminjaman.</p>
                    @elseif ($canSetCustomDue ?? false)
                        <p class="mb-2 text-xs text-slate-500"><i class="bi bi-info-circle me-1"></i>Kosongkan untuk default otomatis (3 hari, pukul 15.00 WIB).</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="due_date" class="mb-1 block text-xs font-semibold text-slate-600">Tanggal</label>
                                <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}"
                                    min="{{ now(config('app.timezone', 'Asia/Jakarta'))->toDateString() }}"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('due_date') border-red-400 @enderror">
                                @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="due_time" class="mb-1 block text-xs font-semibold text-slate-600">Jam</label>
                                <input id="due_time" type="time" name="due_time" value="{{ old('due_time', '15:00') }}"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('due_time') border-red-400 @enderror">
                                @error('due_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm text-slate-600">
                            <i class="bi bi-clock shrink-0"></i>
                            <span>{{ $previewDueAt->translatedFormat('d F Y') }}, pukul 15.00 WIB</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">{{ $dueRuleText }}</p>
                    @endif
                </div>

                {{-- Keperluan --}}
                <div>
                    <label for="purpose" class="mb-1.5 block text-sm font-semibold text-slate-700">Keperluan <span class="text-red-500">*</span></label>
                    <input id="purpose" type="text" name="purpose" value="{{ old('purpose') }}" required
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('purpose') border-red-400 @enderror">
                    @error('purpose')<p class="mt-1.5 text-xs text-red-600"><i class="bi bi-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                {{-- Catatan (full width) --}}
                <div class="md:col-span-2">
                    <label for="notes" class="mb-1.5 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea id="notes" name="notes" rows="2"
                        class="w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1">
                            Detail Barang
                        </h2>

                        <div class="small text-secondary">
                            Pilih barang dan isi jumlah.
                            Nomor unit alat dipilih otomatis dari urutan terkecil yang tersedia.
                        </div>
                    </div>

                    <button
                        type="button"
                        id="add-row"
                        class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        @disabled($items->isEmpty())
                    >
                        <i class="bi bi-plus-circle mr-1"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <div
                id="loan-rows"
                class="p-5 sm:p-6"
            >
                @foreach ($oldRows as $index => $oldRow)
                    @include(
                        'loans._row',
                        [
                            'index' => $index,
                            'oldRow' => $oldRow,
                            'items' => $items,
                            'assets' => $assets,
                        ]
                    )
                @endforeach
            </div>

            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-slate-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:justify-end sm:px-6">
                <x-button href="{{ route('loans.index') }}" variant="secondary" class="w-full sm:w-auto">
                    Batal
                </x-button>

                <button
                    type="submit"
                    id="submit-loan"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
                    @disabled(
                        $items->isEmpty()
                        || (
                            isset($workshopToolmen)
                            && $workshopToolmen->isEmpty()
                        )
                    )
                >
                    <span class="submit-label">
                        Kirim Pengajuan ke Toolman
                    </span>

                    <span class="submit-loading d-none">
                        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        Mengirim pengajuan...
                    </span>
                </button>
            </div>
        </section>
    </form>

    <template id="loan-row-template">
        @include(
            'loans._row',
            [
                'index' => '__INDEX__',
                'oldRow' => [
                    'item_id' => '',
                    'quantity' => 1,
                ],
                'items' => $items,
                'assets' => $assets,
            ]
        )
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const toolAssets =
                    @json($toolAssetMap);

                const rows =
                    document.getElementById(
                        'loan-rows'
                    );

                const template =
                    document.getElementById(
                        'loan-row-template'
                    );

                const addButton =
                    document.getElementById(
                        'add-row'
                    );

                let nextIndex =
                    rows.querySelectorAll(
                        '.loan-row'
                    ).length;

                const escapeHtml =
                    function (value) {
                        return String(value)
                            .replaceAll('&', '&amp;')
                            .replaceAll('<', '&lt;')
                            .replaceAll('>', '&gt;')
                            .replaceAll('"', '&quot;')
                            .replaceAll("'", '&#039;');
                    };

                function bindRow(row) {
                    const item =
                        row.querySelector(
                            '.item-select'
                        );

                    const quantity =
                        row.querySelector(
                            '.quantity-input'
                        );

                    const quantityLabel =
                        row.querySelector(
                            '.quantity-label'
                        );

                    const quantityHelp =
                        row.querySelector(
                            '.quantity-help'
                        );

                    const preview =
                        row.querySelector(
                            '.auto-unit-preview'
                        );

                    function refresh() {
                        const option =
                            item
                                .selectedOptions[0];

                        const itemId =
                            item.value;

                        const type =
                            option?.dataset.type
                            ?? '';

                        const decimal =
                            option?.dataset.decimal
                            === '1';

                        const stock =
                            Number(
                                option?.dataset.stock
                                ?? 0
                            );

                        let requested =
                            Number(
                                quantity.value
                                || 0
                            );

                        quantity.setCustomValidity('');

                        if (! itemId) {
                            quantityLabel.textContent =
                                'Jumlah';

                            quantity.step = '1';
                            quantity.min = '1';
                            quantity.removeAttribute(
                                'max'
                            );

                            quantityHelp.textContent =
                                'Pilih barang terlebih dahulu.';

                            preview.innerHTML =
                                '<span class="text-secondary">Pilih barang dan masukkan jumlah.</span>';

                            return;
                        }

                        if (type === 'material') {
                            quantityLabel.textContent =
                                'Jumlah Bahan';

                            quantity.step =
                                decimal
                                    ? '0.001'
                                    : '1';

                            quantity.min =
                                decimal
                                    ? '0.001'
                                    : '1';

                            quantity.max =
                                String(stock);

                            quantityHelp.textContent =
                                `Stok bahan tersedia: ${stock}. Bahan tidak dikembalikan.`;

                            if (
                                requested <= 0
                                || requested > stock
                            ) {
                                quantity.setCustomValidity(
                                    `Jumlah bahan maksimal ${stock}.`
                                );
                            }

                            preview.innerHTML =
                                '<span class="text-secondary">Bahan habis pakai tidak mempunyai nomor unit/QR.</span>';

                            return;
                        }

                        const available =
                            toolAssets[itemId]
                            ?? [];

                        quantityLabel.textContent =
                            'Jumlah Alat';

                        quantity.step = '1';
                        quantity.min = '1';
                        quantity.max =
                            String(
                                available.length
                            );

                        requested =
                            Math.floor(requested);

                        quantityHelp.textContent =
                            `Tersedia ${available.length} unit. Sistem memilih nomor terkecil terlebih dahulu.`;

                        if (
                            requested < 1
                            || requested
                                > available.length
                        ) {
                            quantity.setCustomValidity(
                                `Jumlah alat maksimal ${available.length} unit.`
                            );
                        }

                        const selected =
                            available.slice(
                                0,
                                Math.max(
                                    0,
                                    requested
                                )
                            );

                        if (selected.length === 0) {
                            preview.innerHTML =
                                '<span class="text-secondary">Masukkan jumlah alat minimal 1.</span>';

                            return;
                        }

                        preview.innerHTML =
                            '<ol class="mb-0 ps-4">'
                            + selected.map(
                                function (asset) {
                                    const serial =
                                        asset.serial
                                            ? ` — SN ${escapeHtml(asset.serial)}`
                                            : '';

                                    return (
                                        '<li class="mb-1">'
                                        + '<span class="font-monospace fw-semibold">'
                                        + escapeHtml(asset.number)
                                        + '</span>'
                                        + serial
                                        + ' — '
                                        + escapeHtml(asset.location)
                                        + '</li>'
                                    );
                                }
                            ).join('')
                            + '</ol>'
                            + '<div class="small text-secondary mt-2">'
                            + 'Nomor final diverifikasi kembali oleh server saat pengajuan disimpan.'
                            + '</div>';
                    }

                    item.addEventListener(
                        'change',
                        refresh
                    );

                    quantity.addEventListener(
                        'input',
                        refresh
                    );

                    row.querySelector(
                        '.remove-row'
                    )?.addEventListener(
                        'click',
                        function () {
                            if (
                                rows.querySelectorAll(
                                    '.loan-row'
                                ).length
                                <= 1
                            ) {
                                item.value = '';
                                quantity.value = '1';
                                refresh();
                                return;
                            }

                            row.remove();
                        }
                    );

                    refresh();
                }

                rows.querySelectorAll(
                    '.loan-row'
                ).forEach(bindRow);

                addButton?.addEventListener(
                    'click',
                    function () {
                        rows.insertAdjacentHTML(
                            'beforeend',
                            template
                                .innerHTML
                                .replaceAll(
                                    '__INDEX__',
                                    String(
                                        nextIndex++
                                    )
                                )
                        );

                        bindRow(
                            rows.lastElementChild
                        );
                    }
                );

                @if ($canSelectWorkshop)
                    document.getElementById(
                        'workshop_id'
                    )?.addEventListener(
                        'change',
                        function (event) {
                            const url =
                                new URL(
                                    window.location.href
                                );

                            url.searchParams.set(
                                'workshop_id',
                                event.target.value
                            );

                            window.location.href =
                                url.toString();
                        }
                    );
                @endif

                const loanForm =
                    document.getElementById(
                        'loan-form'
                    );

                const submitButton =
                    document.getElementById(
                        'submit-loan'
                    );

                loanForm?.addEventListener(
                    'submit',
                    function (event) {
                        if (
                            ! loanForm
                                .checkValidity()
                        ) {
                            submitButton
                                ?.removeAttribute(
                                    'disabled'
                                );

                            return;
                        }

                        submitButton
                            ?.setAttribute(
                                'disabled',
                                'disabled'
                            );

                        submitButton
                            ?.querySelector(
                                '.submit-label'
                            )
                            ?.classList
                            .add('d-none');

                        submitButton
                            ?.querySelector(
                                '.submit-loading'
                            )
                            ?.classList
                            .remove('d-none');
                    }
                );

                document.addEventListener(
                    'invalid',
                    function () {
                        submitButton
                            ?.removeAttribute(
                                'disabled'
                            );

                        submitButton
                            ?.querySelector(
                                '.submit-label'
                            )
                            ?.classList
                            .remove('d-none');

                        submitButton
                            ?.querySelector(
                                '.submit-loading'
                            )
                            ?.classList
                            .add('d-none');
                    },
                    true
                );

                const validationBox =
                    document.getElementById(
                        'loan-validation-errors'
                    );

                validationBox
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });

                @if ($isBorrowerOnly)
                    const role =
                        @json($borrowerRole);

                    const dateInput =
                        document.getElementById(
                            'request_date'
                        );

                    const timeInput =
                        document.getElementById(
                            'loan_time'
                        );

                    const dueDisplay =
                        document.getElementById(
                            'due_at_display'
                        );

                    const dueMessage =
                        document.getElementById(
                            'due-rule-message'
                        );

                    const pad =
                        function (number) {
                            return String(number)
                                .padStart(2, '0');
                        };

                    const updateDue =
                        function () {
                            if (
                                ! dateInput.value
                                || ! timeInput.value
                            ) {
                                return;
                            }

                            const start =
                                new Date(
                                    `${dateInput.value}T${timeInput.value}:00`
                                );

                            if (
                                Number.isNaN(
                                    start.getTime()
                                )
                            ) {
                                return;
                            }

                            const due =
                                new Date(
                                    start.getTime()
                                );

                            if (role === 'siswa' || role === 'guru') {
                                due.setHours(15, 0, 0, 0);

                                dueMessage
                                    .classList
                                    .remove('text-danger');

                                dueMessage.textContent =
                                    'Batas pengembalian maksimal pukul 15.00 pada tanggal yang sama.';
                            } else {
                                due.setDate(
                                    due.getDate() + 1
                                );

                                dueMessage.textContent =
                                    'Jatuh tempo ditentukan petugas.';
                            }

                            dueDisplay.value =
                                `${pad(due.getDate())}-${pad(due.getMonth() + 1)}-${due.getFullYear()} ${pad(due.getHours())}:${pad(due.getMinutes())}`;
                        };

                    dateInput.addEventListener(
                        'change',
                        updateDue
                    );

                    timeInput.addEventListener(
                        'change',
                        updateDue
                    );

                    updateDue();
                @endif
            }
        );
    </script>
@endpush
