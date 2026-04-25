@php
    $sessionUser = session('auth_user') ?? session('user') ?? [];
@endphp

<header class="top-header">
    <div class="header-left">
        <button class="sidebar-toggle-mobile" id="sidebarToggleMobile" aria-label="Toggle sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <span class="header-title">@yield('page_title', 'SFMS')</span>
    </div>
    
    <div class="header-right">
        <button class="theme-toggle-button" id="themeToggleButton" type="button" data-theme-toggle aria-label="Switch theme" title="Switch theme">
            <svg class="theme-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div class="header-notifications">
            <button class="notify-btn" id="notification-bell" aria-label="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notify-badge" id="notification-count">0</span>
            </button>
        </div>

        <div class="header-user">
            <div class="header-avatar">
                {{ strtoupper(substr((string)($sessionUser['email'] ?? 'U'), 0, 1)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ $sessionUser['full_name'] ?? $sessionUser['email'] ?? '' }}</div>
                <div class="user-role">{{ ucfirst(str_replace('_', ' ', (string)($sessionUser['role'] ?? ''))) }}</div>
            </div>
        </div>
    </div>
</header>
