<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$routeFile = $root.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
$loginFile = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'auth'.DIRECTORY_SEPARATOR.'login.blade.php';

if (! is_file($routeFile)) {
    fwrite(STDERR, "routes/web.php tidak ditemukan.\n");
    exit(1);
}

$routes = file_get_contents($routeFile);

if ($routes === false) {
    fwrite(STDERR, "Gagal membaca routes/web.php.\n");
    exit(1);
}

$markerStart = '/* SIMBA_STUDENT_MODULE_START */';
$markerEnd = '/* SIMBA_STUDENT_MODULE_END */';

if (! str_contains($routes, $markerStart)) {
    $backup = $routeFile.'.before-student-module.'.date('YmdHis').'.bak';

    if (! copy($routeFile, $backup)) {
        fwrite(STDERR, "Gagal membuat backup routes/web.php.\n");
        exit(1);
    }

    $block = <<<'ROUTES'

/* SIMBA_STUDENT_MODULE_START */
Route::middleware('guest')->group(function (): void {
    Route::get(
        '/student-register',
        [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'create']
    )->name('student-register.create');

    Route::post(
        '/student-register',
        [\App\Http\Controllers\Auth\StudentRegistrationController::class, 'store']
    )->name('student-register.store');
});

Route::middleware('auth')
    ->prefix('students')
    ->name('students.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\StudentController::class, 'index'])
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\StudentController::class, 'create'])
            ->name('create');
        Route::post('/', [\App\Http\Controllers\StudentController::class, 'store'])
            ->name('store');

        Route::get('/import', [\App\Http\Controllers\StudentController::class, 'importCreate'])
            ->name('import.create');
        Route::post('/import', [\App\Http\Controllers\StudentController::class, 'importStore'])
            ->name('import.store');
        Route::get('/template', [\App\Http\Controllers\StudentController::class, 'template'])
            ->name('template');
        Route::get('/export', [\App\Http\Controllers\StudentController::class, 'export'])
            ->name('export');

        Route::get('/{student}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPasswordEdit'])
            ->whereNumber('student')
            ->name('reset-password.edit');
        Route::put('/{student}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPasswordUpdate'])
            ->whereNumber('student')
            ->name('reset-password.update');

        Route::get('/{student}/edit', [\App\Http\Controllers\StudentController::class, 'edit'])
            ->whereNumber('student')
            ->name('edit');
        Route::put('/{student}', [\App\Http\Controllers\StudentController::class, 'update'])
            ->whereNumber('student')
            ->name('update');
        Route::delete('/{student}', [\App\Http\Controllers\StudentController::class, 'destroy'])
            ->whereNumber('student')
            ->name('destroy');
    });
/* SIMBA_STUDENT_MODULE_END */
ROUTES;

    $routes = rtrim($routes)."\n".$block."\n";

    if (file_put_contents($routeFile, $routes) === false) {
        copy($backup, $routeFile);
        fwrite(STDERR, "Gagal menulis routes/web.php. File lama dipulihkan.\n");
        exit(1);
    }

    echo "[PASANG] Route modul siswa\n";
    echo "[BACKUP] {$backup}\n";
} else {
    echo "[OK] Route modul siswa sudah terpasang.\n";
}

if (is_file($loginFile)) {
    $login = file_get_contents($loginFile);
    $loginMarker = 'SIMBA_STUDENT_REGISTER_LINK';

    if (is_string($login) && ! str_contains($login, $loginMarker)) {
        $backup = $loginFile.'.before-student-register-link.'.date('YmdHis').'.bak';
        copy($loginFile, $backup);

        $link = <<<'BLADE'

            {{-- SIMBA_STUDENT_REGISTER_LINK --}}
            @if (\Illuminate\Support\Facades\Route::has('student-register.create'))
                <div class="text-center mt-3">
                    <span class="text-secondary">Siswa belum memiliki akun?</span>
                    <a href="{{ route('student-register.create') }}" class="fw-semibold text-decoration-none">
                        Registrasi dengan NISN
                    </a>
                </div>
            @endif
BLADE;

        if (preg_match('/<\/form>/i', $login) === 1) {
            $login = preg_replace('/<\/form>/i', $link."\n        </form>", $login, 1);
        } else {
            $login .= $link;
        }

        file_put_contents($loginFile, $login);
        echo "[PASANG] Link Registrasi Siswa pada halaman login\n";
        echo "[BACKUP] {$backup}\n";
    } else {
        echo "[OK] Link registrasi pada login sudah tersedia atau login view tidak dapat dibaca.\n";
    }
} else {
    echo "[LEWATI] resources/views/auth/login.blade.php tidak ditemukan.\n";
}

echo "\nInstalasi source selesai. Jalankan migration, dump-autoload, dan optimize:clear.\n";
