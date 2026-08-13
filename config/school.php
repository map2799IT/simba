<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identitas Sekolah untuk Kop Surat Laporan
    |--------------------------------------------------------------------------
    */

    'province'    => 'PEMERINTAH PROVINSI SUMATERA SELATAN',
    'institution' => 'SMK NEGERI 4 PALEMBANG',
    'address'     => 'Jl. Sersan Sani No. 1019 Kemuning, Kota Palembang, Sumatera Selatan',
    'phone'       => '(0711) 810364',
    'postal_code' => '30127',
    'email'       => 'smkn4palembang@gmail.com',
    'website'     => 'www.smkn4palembang.sch.id',

    /*
    |--------------------------------------------------------------------------
    | Penandatangan Laporan
    |--------------------------------------------------------------------------
    */

    'signatories' => [
        'principal' => [
            'name' => 'Drs. H. Ahmad Fauzi, M.Pd.',
            'nip'  => '19680512 199503 1 002',
        ],
        'waka_sarpras' => [
            'name' => 'Hendra Gunawan, S.T.',
            'nip'  => '19750820 200212 1 004',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kepala Bengkel per Jurusan (diambil dari DB Users dengan role kepala_bengkel)
    | Jika tidak ada di DB, fallback ke daftar ini.
    |--------------------------------------------------------------------------
    */

    'workshop_heads' => [
        // 'TKJ' => ['name' => 'Andi Saputra, S.Kom.', 'nip' => '19820315 200901 1 003'],
    ],
];
