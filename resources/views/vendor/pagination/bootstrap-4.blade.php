@if ($paginator->hasPages())
    <div
        class="d-flex flex-column flex-md-row
            justify-content-between align-items-md-center
            gap-3 px-3 py-3"
        role="navigation"
        aria-label="Navigasi halaman"
    >
        <div class="small text-secondary">
            Menampilkan
            <span class="fw-semibold text-dark">
                {{ number_format((int) $paginator->firstItem(), 0, ',', '.') }}
            </span>
            sampai
            <span class="fw-semibold text-dark">
                {{ number_format((int) $paginator->lastItem(), 0, ',', '.') }}
            </span>
            dari
            <span class="fw-semibold text-dark">
                {{ number_format((int) $paginator->total(), 0, ',', '.') }}
            </span>
            data
        </div>

        <nav aria-label="Halaman data">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                {{-- Tombol halaman sebelumnya --}}
                @if ($paginator->onFirstPage())
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
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
                            aria-label="Halaman sebelumnya"
                        >
                            &lsaquo;
                            Sebelumnya
                        </a>
                    </li>
                @endif

                {{-- Nomor halaman --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li
                            class="page-item disabled"
                            aria-disabled="true"
                        >
                            <span class="page-link">
                                {{ $element }}
                            </span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <li
                                    class="page-item active"
                                    aria-current="page"
                                >
                                    <span class="page-link">
                                        {{ $page }}
                                    </span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a
                                        class="page-link"
                                        href="{{ $url }}"
                                        aria-label="Buka halaman {{ $page }}"
                                    >
                                        {{ $page }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Tombol halaman berikutnya --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            aria-label="Halaman berikutnya"
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
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            Berikutnya
                            &rsaquo;
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
