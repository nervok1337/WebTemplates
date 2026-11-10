<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Главная') — {{ config('app.name') }}</title>
</head>
<body>
    @include('partials.header')

    <main>
        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
