<?php

declare(strict_types=1);

$installer = __DIR__.
    DIRECTORY_SEPARATOR.
    'install-jurusan-role-loan-routing.php';

if (! is_file($installer)) {
    fwrite(
        STDERR,
        "Installer jurusan tidak ditemukan.\n"
    );

    exit(1);
}

require $installer;
