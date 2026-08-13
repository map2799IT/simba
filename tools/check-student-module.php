<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [
    'Model Student' => class_exists(\App\Models\Student::class),
    'StudentController' => class_exists(\App\Http\Controllers\StudentController::class),
    'RegistrationController' => class_exists(\App\Http\Controllers\Auth\StudentRegistrationController::class),
    'StudentAccessService' => class_exists(\App\Services\StudentAccessService::class),
    'StudentSpreadsheetService' => class_exists(\App\Services\StudentSpreadsheetService::class),
    'Tabel students' => Schema::hasTable('students'),
    'Kolom students.nisn' => Schema::hasColumn('students', 'nisn'),
    'Kolom students.workshop_id' => Schema::hasColumn('students', 'workshop_id'),
    'Kolom students.user_id' => Schema::hasColumn('students', 'user_id'),
    'View students.index' => View::exists('students.index'),
    'View student register' => View::exists('auth.student-register'),
    'Route students.index' => Route::has('students.index'),
    'Route students.import.store' => Route::has('students.import.store'),
    'Route students.export' => Route::has('students.export'),
    'Route reset password' => Route::has('students.reset-password.update'),
    'Route student register' => Route::has('student-register.store'),
];

$failed = false;

echo "SIMBA STUDENT MODULE CHECK\n";
echo "==========================\n\n";

foreach ($checks as $label => $passed) {
    echo str_pad($label, 38).': '.($passed ? 'OK' : 'GAGAL').PHP_EOL;
    $failed = $failed || ! $passed;
}

if (Schema::hasTable('students')) {
    echo "\nDATA\n----\n";
    echo 'Total siswa       : '.\Illuminate\Support\Facades\DB::table('students')->count().PHP_EOL;
    echo 'Sudah registrasi : '.\Illuminate\Support\Facades\DB::table('students')->whereNotNull('user_id')->count().PHP_EOL;
    echo 'Belum registrasi : '.\Illuminate\Support\Facades\DB::table('students')->whereNull('user_id')->count().PHP_EOL;
}

echo "\n".($failed ? 'PEMERIKSAAN GAGAL.' : 'MODUL SISWA SIAP DIGUNAKAN.').PHP_EOL;
exit($failed ? 1 : 0);
