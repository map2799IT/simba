<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.simba-favicon')
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>@yield('title', 'Login') | SIMBA</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
    <link
    rel="stylesheet"
    href="{{ asset(
        'css/simba-brand.css'
    ) }}"
>
</head>

<body>
    <x-flash-toast />

    @yield('content')
</body>
</html>
