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
<body @class(['antialiased', 'has-mobile-back' => !request()->routeIs('home') && !request()->is('home', 'user')])>
    <!-- User Info -->
    @yield('user-info')

    <div class="mobile-topbar">
        <div class="mobile-topbar-title">Quản lý đầu tư cá nhân</div>
        <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(true)" aria-label="Mở menu">☰</button>
    </div>
    <div class="mobile-menu-overlay" onclick="toggleMobileMenu(false)"></div>
    <div class="actions">
        <div class="actions-left mobile-menu-drawer" id="mobileMenuDrawer" role="dialog" aria-modal="true" aria-label="Menu điều hướng">
            <div class="mobile-menu-header">
                <span class="mobile-menu-title">Quản lý đầu tư cá nhân</span>
                <button type="button" class="mobile-menu-close" onclick="toggleMobileMenu(false)" aria-label="Đóng menu">&times;</button>
            </div>
            @yield('actions-left')
        </div>
        <div class="actions-right" id="actionsRightContent">
            @yield('actions-right')
        </div>
    </div>

    <main>
        @if (!request()->routeIs('home') && !request()->is('home', 'user'))
            <button
                type="button"
                class="mobile-menu-toggle mobile-back-button"
                onclick="mobileGoBack()"
                aria-label="Quay lại trang trước"
            >
                ←
            </button>
        @endif
        <div id="mobileActionsRightSlot" class="mobile-actions-right-slot"></div>
        @yield('user-body-content')
    </main>

    <footer class="footer">
        <div>
            <p>&copy; {{ date('Y') }} Invest manager. All rights reserved.</p>
            <p>👉 Mọi thắc mắc hoặc liên hệ vui lòng gửi về email: lehuuphuoc0196@gmail.com</p>
        </div>
    </footer>

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
            window.location.href = "{{ url('/home') }}";
        }
    </script>
    @yield('user-script')
</body>
</html>
