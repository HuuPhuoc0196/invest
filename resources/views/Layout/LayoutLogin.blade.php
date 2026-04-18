@php $logoVer = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0; @endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('csrf-token')
    <title>@yield('title', config('app.name', 'Invest'))</title>

    @yield('seo')

    @include('partials.favicon')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    @yield('header-css')
    @vite('resources/css/app.css')
    @vite('resources/css/theme-invest-app.css')
    @vite('resources/css/footer.css')
    @vite('resources/css/theme-drawer-shared.css')
    @yield('header-js')
</head>
<body class="antialiased theme-invest-app layout-user">
    <div class="mobile-topbar">
        <div class="mobile-topbar-brand">
            <a href="{{ route('home') }}" class="mobile-topbar-logo" aria-label="Trang chủ">
                <img src="{{ route('site.logo') }}?v={{ $logoVer }}" alt="Logo" width="36" height="36" decoding="async">
            </a>
            <div class="mobile-topbar-title">Quản lý đầu tư cá nhân</div>
        </div>
        <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(true)" aria-label="Mở menu">☰</button>
    </div>
    <div class="mobile-menu-overlay" onclick="toggleMobileMenu(false)"></div>
    <div class="actions">
        <a href="{{ route('home') }}" class="site-brand site-brand--desktop" aria-label="Trang chủ — Quản lý đầu tư cá nhân">
            <img src="{{ route('site.logo') }}?v={{ $logoVer }}" alt="Logo" class="site-brand__img" width="44" height="44" decoding="async">
            <span class="site-brand__text">Quản lý đầu tư cá nhân</span>
        </a>
        <div class="actions-left mobile-menu-drawer" id="mobileMenuDrawer" role="dialog" aria-modal="true" aria-label="Menu điều hướng">
            <div class="mobile-menu-header">
                <div class="mobile-menu-header-brand">
                    <a href="{{ route('home') }}" class="mobile-menu-header-logo" aria-label="Trang chủ">
                        <img src="{{ route('site.logo') }}?v={{ $logoVer }}" alt="Logo" width="36" height="36" decoding="async">
                    </a>
                    <span class="mobile-menu-title">Quản lý đầu tư cá nhân</span>
                </div>
                <button type="button" class="mobile-menu-close" onclick="toggleMobileMenu(false)" aria-label="Đóng menu">&times;</button>
            </div>
            @include('partials.guest-nav-actions')
        </div>
        <div class="actions-right" id="actionsRightContent">
            @yield('actions-right')
        </div>
    </div>

    <main class="login-layout-main">
        <div id="mobileActionsRightSlot" class="mobile-actions-right-slot"></div>
        <div class="login-container">
            @yield('body-content')
        </div>
    </main>

    @include('partials.footer-invest', ['area' => 'guest'])

    <script>
        function toggleMobileMenu(open) {
            if (open) {
                document.body.classList.add('mobile-menu-open');
            } else {
                document.body.classList.remove('mobile-menu-open');
            }
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                toggleMobileMenu(false);
            }
        });

        function relocateActionsRightByViewport(attempt) {
            attempt = attempt || 0;
            var actions = document.querySelector('.actions');
            var right = document.getElementById('actionsRightContent');
            var mobileSlot = document.getElementById('mobileActionsRightSlot');
            if (!actions || !right || !mobileSlot) return;

            var hasActions = right.innerHTML && right.innerHTML.trim() !== '';

            if (window.innerWidth <= 768) {
                if (!hasActions) {
                    if (right.parentElement !== actions) {
                        actions.appendChild(right);
                    }
                    return;
                }

                var tableContainer = document.querySelector('main .table-container');
                if (!tableContainer || !tableContainer.parentElement) {
                    if (attempt < 15) {
                        setTimeout(function () {
                            relocateActionsRightByViewport(attempt + 1);
                        }, 50);
                        return;
                    }
                    if (right.parentElement !== mobileSlot) {
                        mobileSlot.appendChild(right);
                    }
                    return;
                }

                if (right.parentElement !== tableContainer.parentElement || right.nextElementSibling !== tableContainer) {
                    tableContainer.parentElement.insertBefore(right, tableContainer);
                }
            } else {
                if (right.parentElement !== actions) {
                    actions.appendChild(right);
                }
                document.body.classList.remove('mobile-menu-open');
            }
        }

        var relocateTimer = null;
        function scheduleRelocate() {
            if (relocateTimer) clearTimeout(relocateTimer);
            relocateTimer = setTimeout(function () { relocateActionsRightByViewport(0); }, 120);
        }

        window.addEventListener('resize', scheduleRelocate);
        document.addEventListener('DOMContentLoaded', scheduleRelocate);
    </script>
    @yield('page-modals')
    @yield('login-script')
</body>
</html>
