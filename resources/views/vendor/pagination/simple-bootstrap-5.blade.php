@if ($paginator->hasPages())
    <div
        class="d-flex flex-column flex-sm-row
            justify-content-between align-items-sm-center
            gap-3 px-3 py-3"
        role="navigation"
        aria-label="Navigasi halaman"
    >
        <div class="small text-secondary">
            Halaman
            <span class="fw-semibold text-dark">
                {{ number_format((int) $paginator->currentPage(), 0, ',', '.') }}
            </span>
        </div>

        <nav aria-label="Halaman data">
            <ul class="pagination pagination-sm mb-0">
                @if ($paginator->onFirstPage())
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                    >
                        <span class="page-link">
                            &lsaquo;
                            Sebelumnya
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                        >
                            &lsaquo;
                            Sebelumnya
                        </a>
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                        >
                            Berikutnya
                            &rsaquo;
                        </a>
                    </li>
                @else
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                    >
                        <span class="page-link">
                            Berikutnya
                            &rsaquo;
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
