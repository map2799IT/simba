<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password akun role per jurusan
    |--------------------------------------------------------------------------
    */
    'default_password' =>
        env(
            'SIMBA_SEED_PASSWORD',
            'Password123!'
        ),

    /*
    |--------------------------------------------------------------------------
    | Daftar jurusan bawaan SIMBA
    |--------------------------------------------------------------------------
    |
    | Seeder dan reset database akan membuat akun Kepala Bengkel,
    | Toolman, dan Siswa demo berdasarkan daftar ini.
    |
    | Kode jurusan sengaja mengikuti permintaan sekolah:
    | TKJ, RPL, DPIB, TAV, TTIL, TP, TSM, dan TKR.
    |
    */
    'workshops' => [
        'tkj' => [
            'code' => 'TKJ',
            'name' =>
                'Teknik Komputer dan Jaringan',

            'description' =>
                'Jurusan komputer, jaringan, server, dan infrastruktur teknologi informasi.',

            'is_active' => true,
        ],

        'rpl' => [
            'code' => 'RPL',
            'name' =>
                'Rekayasa Perangkat Lunak',

            'description' =>
                'Jurusan pengembangan aplikasi, pemrograman, basis data, dan rekayasa perangkat lunak.',

            'is_active' => true,
        ],

        'dpib' => [
            'code' => 'DPIB',
            'name' =>
                'Desain Pemodelan dan Informasi Bangunan',

            'description' =>
                'Jurusan perencanaan, gambar bangunan, pemodelan, dan informasi konstruksi.',

            'is_active' => true,
        ],

        'tav' => [
            'code' => 'TAV',
            'name' =>
                'Teknik Audio Video',

            'description' =>
                'Jurusan elektronika, sistem audio, video, dan perangkat elektronik.',

            'is_active' => true,
        ],

        'ttil' => [
            'code' => 'TTIL',
            'name' =>
                'Teknik Tenaga Instalasi Listrik',

            'description' =>
                'Jurusan instalasi, pemeliharaan, pengendalian, dan tenaga listrik.',

            'is_active' => true,
        ],

        'tp' => [
            'code' => 'TP',
            'name' =>
                'Teknik Pemesinan',

            'description' =>
                'Jurusan pemesinan, bubut, frais, CNC, pengukuran, dan produksi manufaktur.',

            'is_active' => true,
        ],

        'tsm' => [
            'code' => 'TSM',
            'name' =>
                'Teknik Sepeda Motor',

            'description' =>
                'Jurusan perawatan, diagnosis, dan perbaikan sepeda motor.',

            'is_active' => true,
        ],

        'tkr' => [
            'code' => 'TKR',
            'name' =>
                'Teknik Kendaraan Ringan',

            'description' =>
                'Jurusan perawatan, diagnosis, dan perbaikan kendaraan ringan.',

            'is_active' => true,
        ],
    ],
];
