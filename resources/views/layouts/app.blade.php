<!DOCTYPE html>
<html lang="en">
@php
    $sessionUser = session('auth_user') ?? session('user');
    $sessionRole = strtolower((string)($sessionUser['role'] ?? ''));
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'SFMS - School Facility Maintenance System')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            var storageKey = 'sfmsThemeMode';
            var savedMode = 'dark';

            try {
                var fromStorage = localStorage.getItem(storageKey);
                if (fromStorage === 'light' || fromStorage === 'dark') {
                    savedMode = fromStorage;
                }
            } catch (error) {
                savedMode = 'dark';
            }

            var resolved = savedMode;
            document.documentElement.setAttribute('data-theme-mode', savedMode);
            document.documentElement.setAttribute('data-theme-resolved', resolved);
            document.documentElement.style.colorScheme = resolved;
        })();
    </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/color-scheme.css') }}">
    @if (!empty($sessionUser))
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/laravel-shell.css') }}">
    @endif
    @yield('styles')
    @if (!empty($sessionUser))
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/light-mode-polish.css') }}">
    @endif
</head>
<body @if(!empty($sessionUser)) data-user-role="{{ $sessionRole }}" @endif>

@if (!empty($sessionUser))
    @include('includes.sidebar')
    @include('includes.header')
    <main class="main-content">
        @yield('content')
    </main>
    @include('includes.footer')
@else
    @yield('content')
@endif

<!-- Scripts -->
<script src="{{ asset('frontend/assets/js/api.js') }}"></script>
<script>
    // Configure API base URL for Laravel
    window.API_BASE_URL = '{{ url('/api') }}';
    window.CSRF_TOKEN = '{{ csrf_token() }}';
    
    // Add CSRF token to all fetch requests
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
        const [resource, config = {}] = args;
        const isExternal = typeof resource === 'string' && resource.startsWith('http') && !resource.startsWith(window.location.origin);
        
        if (!isExternal && config.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(config.method.toUpperCase())) {
            config.headers = config.headers || {};
            config.headers['X-CSRF-TOKEN'] = window.CSRF_TOKEN;
        }
        
        return originalFetch.apply(this, args);
    };
</script>
@if (!empty($sessionUser))
<script src="{{ asset('frontend/assets/js/sidebar.js') }}"></script>
@endif
<script>
    (function () {
        var storageKey = 'sfmsThemeMode';

        function normalizeMode(mode) {
            return mode === 'light' ? 'light' : 'dark';
        }

        function setTheme(mode, persist) {
            var safeMode = normalizeMode(mode);
            var root = document.documentElement;
            root.setAttribute('data-theme-mode', safeMode);
            root.setAttribute('data-theme-resolved', safeMode);
            root.style.colorScheme = safeMode;

            if (persist) {
                try {
                    localStorage.setItem(storageKey, safeMode);
                } catch (error) {
                    // Ignore write errors and keep current UI state.
                }
            }

            var toggleButtons = document.querySelectorAll('[data-theme-toggle]');
            var nextMode = safeMode === 'dark' ? 'light' : 'dark';
            for (var i = 0; i < toggleButtons.length; i += 1) {
                toggleButtons[i].setAttribute('aria-label', 'Switch to ' + nextMode + ' mode');
                toggleButtons[i].setAttribute('title', 'Switch to ' + nextMode + ' mode');
                toggleButtons[i].setAttribute('data-theme-current', safeMode);
            }
        }

        function getStoredMode() {
            try {
                return normalizeMode(localStorage.getItem(storageKey));
            } catch (error) {
                return 'dark';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            setTheme(getStoredMode(), false);

            var toggleButtons = document.querySelectorAll('[data-theme-toggle]');
            for (var i = 0; i < toggleButtons.length; i += 1) {
                toggleButtons[i].addEventListener('click', function () {
                    var current = document.documentElement.getAttribute('data-theme-resolved') === 'light' ? 'light' : 'dark';
                    var next = current === 'dark' ? 'light' : 'dark';
                    setTheme(next, true);
                });
            }
        });
    })();
</script>
@yield('scripts')
</body>
</html>
