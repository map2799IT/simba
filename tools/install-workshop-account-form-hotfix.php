<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$formFile =
    $root.
    '/resources/views/workshops/_form.blade.php';

if (! is_file($formFile)) {
    fwrite(
        STDERR,
        "GAGAL: resources/views/workshops/_form.blade.php tidak ditemukan.\n"
    );

    exit(1);
}

$contents =
    file_get_contents($formFile);

if (! is_string($contents)) {
    fwrite(
        STDERR,
        "GAGAL: file form jurusan tidak dapat dibaca.\n"
    );

    exit(1);
}

$markerStart =
    '{{-- SIMBA WORKSHOP ACCOUNT OPTIONS START --}}';

$markerEnd =
    '{{-- SIMBA WORKSHOP ACCOUNT OPTIONS END --}}';

if (
    str_contains(
        $contents,
        $markerStart
    )
) {
    echo "Fallback headUsers/toolmanUsers sudah terpasang.\n";
    exit(0);
}

$backup =
    $formFile.
    '.before-account-options.'.
    date('YmdHis').
    '.bak';

if (! copy(
    $formFile,
    $backup
)) {
    fwrite(
        STDERR,
        "GAGAL: backup form jurusan tidak dapat dibuat.\n"
    );

    exit(1);
}

/*
|--------------------------------------------------------------------------
| Fallback data pilihan akun
|--------------------------------------------------------------------------
|
| Beberapa versi WorkshopController tidak mengirim $headUsers dan
| $toolmanUsers pada method create(). View lama kemudian menghasilkan:
|
| Undefined variable $headUsers
|
| Blok ini hanya menjalankan query jika variabel belum dikirim controller.
|
*/
$patch = <<<'BLADE'
{{-- SIMBA WORKSHOP ACCOUNT OPTIONS START --}}
@php
    $headUsers =
        isset($headUsers)
            ? collect($headUsers)
            : \App\Models\User::query()
                ->withoutGlobalScopes()
                ->where(
                    'role',
                    'kepala_bengkel'
                )
                ->orderBy('name')
                ->orderBy('username')
                ->get();

    $toolmanUsers =
        isset($toolmanUsers)
            ? collect($toolmanUsers)
            : \App\Models\User::query()
                ->withoutGlobalScopes()
                ->where(
                    'role',
                    'toolman'
                )
                ->orderBy('name')
                ->orderBy('username')
                ->get();
@endphp
{{-- SIMBA WORKSHOP ACCOUNT OPTIONS END --}}

BLADE;

$updated =
    $patch.
    $contents;

if (
    file_put_contents(
        $formFile,
        $updated
    ) === false
) {
    copy(
        $backup,
        $formFile
    );

    fwrite(
        STDERR,
        "GAGAL: form jurusan tidak dapat ditulis.\n"
    );

    exit(1);
}

echo "WORKSHOP ACCOUNT FORM HOTFIX BERHASIL DIPASANG.\n";
echo "Backup: {$backup}\n";
