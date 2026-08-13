@extends('layouts.app')

@section('title', 'Detail Audit Log')
@section('page-title', 'Detail Audit Log')

@section('content')
    <section class="content-card">
        <div
            class="content-card-header
                d-flex justify-content-between align-items-center"
        >
            <h1 class="h5 fw-bold mb-0">
                Detail Audit Log
            </h1>

            <a
                href="{{ url()->previous() }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>
        </div>

        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <tbody>
                        @foreach ((array) $log as $key => $value)
                            <tr>
                                <th style="width: 240px;">
                                    {{ ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $key
                                        )
                                    ) }}
                                </th>

                                <td>
                                    @if (
                                        is_array($value)
                                        || is_object($value)
                                    )
                                        <pre class="mb-0">{{ json_encode(
                                            $value,
                                            JSON_PRETTY_PRINT
                                            | JSON_UNESCAPED_UNICODE
                                        ) }}</pre>
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
