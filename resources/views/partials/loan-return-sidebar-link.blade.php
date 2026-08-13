@php
    $user = auth()->user();

    $canAccessLoans = $user?->hasRole(
        'admin',
        'kepala_bengkel',
        'toolman',
        'guru',
        'siswa'
    ) ?? false;

    $canAccessReturns = $user?->hasRole(
        'admin',
        'kepala_bengkel',
        'toolman'
    ) ?? false;
@endphp

@if (
    $canAccessLoans
    && \Illuminate\Support\Facades\Route::has(
        'loans.index'
    )
)
    <a
        href="{{ route('loans.index') }}"
        class="sidebar-link {{
            request()->routeIs(
                'loans.index',
                'loans.create',
                'loans.show'
            )
                ? 'active'
                : ''
        }}"
    >
        <span class="sidebar-icon">
            <i class="bi bi-arrow-left-right"></i>
        </span>

        <span class="sidebar-text">
            Peminjaman
        </span>
    </a>
@endif

@if (
    $canAccessReturns
    && \Illuminate\Support\Facades\Route::has(
        'loans.returns.index'
    )
)
    <a
        href="{{ route('loans.returns.index') }}"
        class="sidebar-link {{
            request()->routeIs(
                'loans.returns.*',
                'loans.return-form'
            )
                ? 'active'
                : ''
        }}"
    >
        <span class="sidebar-icon">
            <i class="bi bi-arrow-return-left"></i>
        </span>

        <span class="sidebar-text">
            Pengembalian
        </span>
    </a>
@endif
