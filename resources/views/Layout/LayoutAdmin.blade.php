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
    @vite('resources/css/footer.css')
    @yield('header-js')
</head>

<body class="antialiased">
     <!-- User Info -->
     @yield('user-info')

    <div class="actions">
        <div class="actions-left" style="display: flex; flex-direction: column; gap: 5px;">
            @yield('actions-left')
        </div>
        <div class="actions-right">
            @yield('actions-right')
        </div>
    </div>

    <main>
        @yield('admin-body-content')
    </main>

    <footer class="footer">
       <div>
            <p>&copy; {{ date('Y') }} Invest manager. All rights reserved.</p>
            <p>👉 Mọi thắc mắc hoặc liên hệ vui lòng gửi về email: lehuuphuoc0196@gmail.com</p>
        </div>
    </footer>

    @yield('admin-script')
</body>
</html>