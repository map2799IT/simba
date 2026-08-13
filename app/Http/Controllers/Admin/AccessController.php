<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function index(): View
    {
        $roles = [
            'admin' => [
                'label' => 'Administrator',
                'description' =>
                    'Akses penuh ke seluruh sistem dan seluruh jurusan.',
            ],

            'wakil_sarpras' => [
                'label' =>
                    'Wakil Sarana dan Prasarana',

                'description' =>
                    'Pengawasan global read-only: dashboard, laporan, lokasi penyimpanan, print, PDF, dan Excel.',
            ],

            'kepala_bengkel' => [
                'label' => 'Kepala Bengkel',
                'description' =>
                    'Memantau inventaris dan transaksi jurusan yang ditetapkan.',
            ],

            'toolman' => [
                'label' => 'Toolman',
                'description' =>
                    'Mengelola inventaris dan transaksi operasional jurusannya.',
            ],

            'guru' => [
                'label' => 'Guru',
                'description' =>
                    'Mengajukan dan melihat peminjaman miliknya dari seluruh jurusan.',
            ],

            'siswa' => [
                'label' => 'Siswa',
                'description' =>
                    'Mengajukan dan melihat peminjaman miliknya pada jurusan akun.',
            ],
        ];

        $all = [
            'admin',
            'wakil_sarpras',
            'kepala_bengkel',
            'toolman',
            'guru',
            'siswa',
        ];

        $permissions = [
            [
                'module' => 'Dashboard',
                'icon' => 'bi-columns-gap',
                'roles' => $all,
            ],
            [
                'module' => 'Pengguna dan Hak Akses',
                'icon' => 'bi-people-fill',
                'roles' => ['admin'],
            ],
            [
                'module' => 'Master Jurusan/Kategori/Satuan',
                'icon' => 'bi-building-gear',
                'roles' => ['admin'],
            ],
            [
                'module' => 'Lokasi Penyimpanan: CRUD',
                'icon' => 'bi-geo-alt-fill',
                'roles' => [
                    'admin',
                    'toolman',
                ],
            ],
            [
                'module' => 'Lokasi Penyimpanan: Lihat/Print/PDF',
                'icon' => 'bi-printer-fill',
                'roles' => [
                    'admin',
                    'wakil_sarpras',
                    'kepala_bengkel',
                    'toolman',
                ],
            ],
            [
                'module' => 'Master Barang dan Unit QR',
                'icon' => 'bi-tools',
                'roles' => [
                    'admin',
                    'kepala_bengkel',
                    'toolman',
                ],
            ],
            [
                'module' => 'Barang Masuk/Keluar/Pergerakan',
                'icon' => 'bi-arrow-left-right',
                'roles' => [
                    'admin',
                    'kepala_bengkel',
                    'toolman',
                ],
            ],
            [
                'module' => 'Proses Stok',
                'icon' => 'bi-box-arrow-in-down',
                'roles' => [
                    'admin',
                    'toolman',
                ],
            ],
            [
                'module' => 'Pengajuan Peminjaman',
                'icon' => 'bi-journal-plus',
                'roles' => [
                    'admin',
                    'toolman',
                    'guru',
                    'siswa',
                ],
            ],
            [
                'module' => 'Approval/Checkout/Pengembalian',
                'icon' => 'bi-arrow-return-left',
                'roles' => [
                    'admin',
                    'toolman',
                ],
            ],
            [
                'module' => 'Kerusakan Operasional',
                'icon' => 'bi-wrench-adjustable',
                'roles' => [
                    'admin',
                    'kepala_bengkel',
                    'toolman',
                ],
            ],
            [
                'module' => 'Laporan Seluruh Jurusan',
                'icon' => 'bi-bar-chart-line-fill',
                'roles' => [
                    'admin',
                    'wakil_sarpras',
                ],
            ],
            [
                'module' => 'Laporan Jurusan Sendiri',
                'icon' => 'bi-clipboard-data',
                'roles' => [
                    'kepala_bengkel',
                    'toolman',
                ],
            ],
            [
                'module' => 'Audit Aktivitas',
                'icon' => 'bi-journal-text',
                'roles' => ['admin'],
            ],
        ];

        return view(
            'admin.access.index',
            compact(
                'roles',
                'permissions'
            )
        );
    }

    public function update(
        Request $request
    ): RedirectResponse {
        return redirect()
            ->route(
                'admin.access.index'
            )
            ->with(
                'warning',
                'Hak akses SIMBA menggunakan matriks role pada source code dan tidak diubah langsung dari halaman ini.'
            );
    }
}
