<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('csrf-token')
    <title>@yield('title', 'Invest')</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    @yield('header-css')
    @yield('header-js')
</head>
<body class="antialiased">
    <div class="actions">
        <div class="actions-left">
            @yield('actions-left')
        </div>
        <div class="actions-right">
            @yield('actions-right')
        </div>
    </div>

    <main>
        @yield('user-body-content')
    </main>

    @yield('user-script')
</body>
</html>
