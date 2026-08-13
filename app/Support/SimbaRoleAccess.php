<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class SimbaRoleAccess
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_WAKA_SARPRAS = 'wakil_sarpras';
    public const ROLE_HEAD = 'kepala_bengkel';
    public const ROLE_TOOLMAN = 'toolman';
    public const ROLE_TEACHER = 'guru';
    public const ROLE_STUDENT = 'siswa';

    public const GLOBAL_REPORT_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_WAKA_SARPRAS,
    ];

    public function canRoute(
        ?User $user,
        ?string $routeName,
        string $method = 'GET',
        ?string $path = null
    ): bool {
        if ($user === null) {
            return false;
        }

        $role = (string) $user->role;
        $routeName = (string) $routeName;
        $method = strtoupper($method);
        $path = trim((string) $path, '/');

        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        if ($role === self::ROLE_WAKA_SARPRAS) {
            return $this->canWakaSarprasRoute(
                $routeName,
                $method,
                $path
            );
        }

        if (
            in_array(
                $role,
                [
                    self::ROLE_TEACHER,
                    self::ROLE_STUDENT,
                ],
                true
            )
        ) {
            return $this->canBorrowerRoute(
                $routeName,
                $method,
                $path
            );
        }

        if ($routeName === '') {
            return true;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'locations.index',
                    'locations.show',
                    'locations.inventory.*',
                    'storage-locations.index',
                    'storage-locations.show',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        if (
            $this->matches(
                $routeName,
                [
                    'admin.*',
                    'audit-logs.*',
                    'workshops.*',
                    'locations.*',
                    'storage-locations.*',
                    'item-categories.*',
                    'categories.*',
                    'units.*',
                ]
            )
        ) {
            return false;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'home',
                    'dashboard',
                    'profile.*',
                    'logout',
                    'password.*',
                    'verification.*',
                    'passkeys.*',
                ]
            )
        ) {
            return true;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'items.index',
                    'items.show',
                    'items.history',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        if ($this->matches($routeName, ['items.*'])) {
            return $role === self::ROLE_TOOLMAN;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'item-assets.index',
                    'item-assets.show',
                    'item-assets.label',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        if ($this->matches($routeName, ['item-assets.*'])) {
            return $role === self::ROLE_TOOLMAN;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'stock-receipts.index',
                    'stock-receipts.show',
                    'stock-issues.index',
                    'stock-issues.show',
                    'stock-movements.*',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                    self::ROLE_WAKA_SARPRAS,
                ],
                true
            );
        }

        if (
            $this->matches(
                $routeName,
                [
                    'stock-issues.approve',
                    'stock-issues.reject',
                    'stock-issues.pending',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_ADMIN,
                    self::ROLE_HEAD,
                    self::ROLE_WAKA_SARPRAS,
                ],
                true
            );
        }

        if (
            $this->matches(
                $routeName,
                [
                    'stock-issues.cancel',
                ]
            )
        ) {
            return true;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'stock-receipts.*',
                    'stock-issues.*',
                ]
            )
        ) {
            return $role === self::ROLE_TOOLMAN;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'loans.index',
                    'loans.show',
                    'loans.create',
                    'loans.store',
                ]
            )
        ) {
            return true;
        }

        if ($this->matches($routeName, ['loans.*'])) {
            return $role === self::ROLE_TOOLMAN;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'damage-reports.index',
                    'damage-reports.show',
                    'damage-reports.create',
                    'damage-reports.store',
                ]
            )
        ) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        if ($this->matches($routeName, ['damage-reports.*'])) {
            return $role === self::ROLE_TOOLMAN;
        }

        if ($this->matches($routeName, ['reports.*'])) {
            return in_array(
                $role,
                [
                    self::ROLE_HEAD,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        if ($this->matches($routeName, ['students.*', 'students.reset-password.*'])) {
            return in_array(
                $role,
                [
                    self::ROLE_ADMIN,
                    self::ROLE_TOOLMAN,
                ],
                true
            );
        }

        return false;
    }

    public function blockedPathPatterns(?User $user): array
    {
        if (
            $user === null
            || (string) $user->role === self::ROLE_ADMIN
        ) {
            return [];
        }

        $role = (string) $user->role;

        if ($role === self::ROLE_WAKA_SARPRAS) {
            return [
                '/admin*',
                '/audit-logs*',
                '/workshops*',
                '/items*',
                '/item-assets*',
                '/stock-receipts*',
                '/stock-issues/create',
                '/stock-issues/*/edit',
                '/stock-issues/*/cancel',
                '/stock-movements*',
                '/loans*',
                '/loan-returns*',
                '/damage-reports*',
                '/students*',
                '/item-categories*',
                '/categories*',
                '/units*',
                '/locations/create',
                '/locations/*/edit',
                '/locations/*/toggle*',
                '/locations/*/destroy',
                '/storage-locations/create',
                '/storage-locations/*/edit',
                '/storage-locations/*/toggle*',
                '/storage-locations/*/destroy',
            ];
        }

        $commonMaster = [
            '/admin*',
            '/workshops*',
            '/locations*',
            '/storage-locations*',
            '/item-categories*',
            '/categories*',
            '/units*',
            '/audit-logs*',
        ];

        if (
            in_array(
                $role,
                [
                    self::ROLE_TEACHER,
                    self::ROLE_STUDENT,
                ],
                true
            )
        ) {
            return array_merge(
                $commonMaster,
                [
                    '/items*',
                    '/item-assets*',
                    '/stock-receipts*',
                    '/stock-issues*',
                    '/stock-movements*',
                    '/loan-returns*',
                    '/loans/*/approve',
                    '/loans/*/reject',
                    '/loans/*/checkout',
                    '/loans/*/complete',
                    '/loans/*/return*',
                    '/loans/*/items/*/return',
                    '/damage-reports*',
                    '/reports*',
                ]
            );
        }

        if ($role === self::ROLE_HEAD) {
            return array_merge(
                $commonMaster,
                [
                    '/items/create',
                    '/items/bulk*',
                    '/items/*/edit',
                    '/items/*/toggle-status',
                    '/item-assets/create',
                    '/item-assets/*/edit',
                    '/stock-receipts/create',
                    '/stock-issues/create',
                    '/loan-returns*',
                    '/loans/*/return*',
                    '/loans/*/approve',
                    '/loans/*/reject',
                    '/loans/*/checkout',
                    '/loans/*/complete',
                    '/damage-reports/*/edit',
                    '/damage-reports/*/verify',
                    '/damage-reports/*/start*',
                    '/damage-reports/*/complete*',
                    '/damage-reports/*/close',
                ]
            );
        }

        if ($role === self::ROLE_TOOLMAN) {
            return $commonMaster;
        }

        return $commonMaster;
    }

    public function roleLabel(?User $user): string
    {
        return match ((string) $user?->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_WAKA_SARPRAS =>
                'Wakil Sarana dan Prasarana',
            self::ROLE_HEAD => 'Kepala Bengkel',
            self::ROLE_TOOLMAN => 'Toolman',
            self::ROLE_TEACHER => 'Guru',
            self::ROLE_STUDENT => 'Siswa',
            default => 'Pengguna',
        };
    }

    public function denialMessage(?User $user): string
    {
        return match ((string) $user?->role) {
            self::ROLE_WAKA_SARPRAS =>
                'Waka Sarpras hanya memiliki akses baca ke laporan dan inventaris lokasi.',

            self::ROLE_TEACHER,
            self::ROLE_STUDENT =>
                'Role Anda hanya memiliki akses ke fitur Peminjaman.',

            default =>
                'Anda tidak memiliki hak akses ke fitur tersebut.',
        };
    }

    public function isGlobalReportRole(?User $user): bool
    {
        return in_array(
            (string) $user?->role,
            self::GLOBAL_REPORT_ROLES,
            true
        );
    }

    private function canWakaSarprasRoute(
        string $routeName,
        string $method,
        string $path
    ): bool {
        if (
            ! in_array(
                $method,
                ['GET', 'HEAD'],
                true
            )
            && ! (
                $routeName === 'logout'
                && $method === 'POST'
            )
        ) {
            return false;
        }

        if ($routeName === '') {
            if (
                $this->matches(
                    $path,
                    [
                        '',
                        'dashboard',
                        'profile',
                        'profile/*',
                        'logout',
                        'reports',
                        'reports/*',
                        'locations/inventory-menu',
                        'livewire/*',
                        'sanctum/csrf-cookie',
                    ]
                )
            ) {
                return true;
            }

            return preg_match(
                '#^locations/\d+/inventory/(summary|complete)(/(print|pdf))?$#',
                $path
            ) === 1;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'home',
                    'dashboard',
                    'profile.*',
                    'logout',
                    'password.*',
                    'verification.*',
                    'passkeys.*',
                ]
            )
        ) {
            return true;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'reports.*',
                    'locations.inventory.*',
                ]
            )
        ) {
            return in_array(
                $method,
                ['GET', 'HEAD'],
                true
            );
        }

        return false;
    }

    private function canBorrowerRoute(
        string $routeName,
        string $method,
        string $path
    ): bool {
        if ($routeName === '') {
            if (
                $this->matches(
                    $path,
                    [
                        '',
                        'dashboard',
                        'profile',
                        'profile/*',
                        'logout',
                        'loans',
                        'loans/create',
                        'livewire/*',
                        'sanctum/csrf-cookie',
                    ]
                )
            ) {
                return true;
            }

            if (
                preg_match('/^loans\/\d+$/', $path) === 1
                && in_array($method, ['GET', 'HEAD'], true)
            ) {
                return true;
            }

            if (
                preg_match('/^loans\/\d+\/cancel$/', $path) === 1
                && in_array($method, ['POST', 'DELETE'], true)
            ) {
                return true;
            }

            return false;
        }

        if (
            $this->matches(
                $routeName,
                [
                    'home',
                    'dashboard',
                    'profile.*',
                    'logout',
                    'password.*',
                    'verification.*',
                    'passkeys.*',
                ]
            )
        ) {
            return true;
        }

        return match ($routeName) {
            'loans.index',
            'loans.show',
            'loans.create' =>
                in_array(
                    $method,
                    ['GET', 'HEAD'],
                    true
                ),

            'loans.store' =>
                $method === 'POST',

            'loans.cancel' =>
                in_array(
                    $method,
                    ['POST', 'DELETE'],
                    true
                ),

            default => false,
        };
    }

    private function matches(
        string $value,
        array $patterns
    ): bool {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
