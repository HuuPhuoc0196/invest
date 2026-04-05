<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @yield('csrf-token')
    <title>@yield('title', 'Invest')</title>

    @if (View::hasSection('seo'))
        @yield('seo')
    @elseif (request()->routeIs('home'))
        @include('partials.seo-public', [
            'pageTitle' => trim(View::yieldContent('title')) ?: 'Danh sách mã cổ phiếu — ' . config('app.name'),
            'description' => 'Xem bảng giá và danh mục mã cổ phiếu, mức rủi ro và bộ lọc dữ liệu — nền tảng quản lý đầu tư cá nhân.',
        ])
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => route('home'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @elseif (auth()->check())
        <meta name="robots" content="noindex, follow">
    @endif

    @include('partials.favicon')

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    @yield('header-css')
    @vite('resources/css/theme-invest-app.css')
    @vite('resources/css/footer.css')
    @vite('resources/css/theme-drawer-shared.css')
    @yield('header-js')
</head>
<body @class([
    'antialiased',
    'theme-invest-app',
    'layout-user',
    'has-mobile-back' => !request()->routeIs('home') && !request()->is('trang-chu', 'home', 'user'),
])>
    <!-- User Info -->
    @yield('user-info')

    <div class="mobile-topbar">
        <div class="mobile-topbar-brand">
            <a href="{{ route('home') }}" class="mobile-topbar-logo" aria-label="Trang chủ">
                <img src="{{ route('site.logo') }}?v={{ file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0 }}" alt="Logo" width="36" height="36" decoding="async">
            </a>
            <div class="mobile-topbar-title">Quản lý đầu tư cá nhân</div>
        </div>
        <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(true)" aria-label="Mở menu">☰</button>
    </div>
    <div class="mobile-menu-overlay" onclick="toggleMobileMenu(false)"></div>
    <div class="actions">
        <a href="{{ route('home') }}" class="site-brand site-brand--desktop" aria-label="Trang chủ — Quản lý đầu tư cá nhân">
            <img src="{{ route('site.logo') }}?v={{ file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0 }}" alt="Logo" class="site-brand__img" width="44" height="44" decoding="async">
            <span class="site-brand__text">Quản lý đầu tư cá nhân</span>
        </a>
        <div class="actions-left mobile-menu-drawer" id="mobileMenuDrawer" role="dialog" aria-modal="true" aria-label="Menu điều hướng">
            <div class="mobile-menu-header">
                <div class="mobile-menu-header-brand">
                    <a href="{{ route('home') }}" class="mobile-menu-header-logo" aria-label="Trang chủ">
                        <img src="{{ route('site.logo') }}?v={{ file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0 }}" alt="Logo" width="36" height="36" decoding="async">
                    </a>
                    <span class="mobile-menu-title">Quản lý đầu tư cá nhân</span>
                </div>
                <button type="button" class="mobile-menu-close" onclick="toggleMobileMenu(false)" aria-label="Đóng menu">&times;</button>
            </div>
            @yield('actions-left')
        </div>
        <div class="actions-right" id="actionsRightContent">
            @yield('actions-right')
        </div>
    </div>

    <main>
        <div id="mobileActionsRightSlot" class="mobile-actions-right-slot"></div>
        @yield('user-body-content')
    </main>

    @include('partials.footer-invest', ['area' => auth()->check() ? 'user' : 'guest'])

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
                // If page has no actions-right content, don't inject an empty wrapper into main.
                if (!hasActions) {
                    if (right.parentElement !== actions) {
                        actions.appendChild(right);
                    }
                    return;
                }

                var tableContainer = document.querySelector('main .table-container');
                if (!tableContainer || !tableContainer.parentElement) {
                    // Until table is ready, keep right in its original place to avoid flicker.
                    if (attempt < 15) {
                        setTimeout(function () {
                            relocateActionsRightByViewport(attempt + 1);
                        }, 50);
                        return;
                    }
                    // Fallback: if table never appears, place it in slot.
                    if (right.parentElement !== mobileSlot) {
                        mobileSlot.appendChild(right);
                    }
                    return;
                }

                // Move only if not already in correct position.
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

        function mobileGoBack() {
            if (document.referrer) {
                window.history.back();
                return;
            }
            window.location.href = "{{ route('home') }}";
        }
    </script>
    @yield('user-script')
</body>
</html>
