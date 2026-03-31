<?php
/**
 * Login Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - School Facility Maintenance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <style>
        :root {
            --auth-gradient: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            --auth-accent: #1d4ed8;
            --auth-accent-deep: #1e40af;
            --auth-ink: #0f172a;
            --auth-muted: #64748b;
            --auth-input: #f8fafc;
            --auth-border: #cbd5e1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, sans-serif;
            background:
                radial-gradient(circle at 8% 15%, rgba(29, 78, 216, 0.14) 0%, rgba(29, 78, 216, 0) 32%),
                radial-gradient(circle at 92% 80%, rgba(30, 64, 175, 0.12) 0%, rgba(30, 64, 175, 0) 34%),
                linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            position: relative;
            overflow: hidden;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        main.login-container {
            margin: 0;
            padding: 18px;
            background: transparent !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            inset: 0;
        }

        .page-auth-header {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 4;
            width: min(940px, calc(100vw - 36px));
            height: 58px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e293b;
            background: rgba(248, 250, 252, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.32);
            border-radius: 14px;
            padding: 0 16px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.07);
            backdrop-filter: blur(5px);
        }

        .page-auth-logo {
            width: 30px;
            height: 30px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .page-auth-title {
            font-size: 0.79rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
            color: #3f2a93;
            white-space: nowrap;
        }

        .page-auth-divider {
            width: 1px;
            height: 22px;
            background: rgba(148, 163, 184, 0.45);
            margin: 0 2px;
            flex-shrink: 0;
        }

        .page-auth-subtitle {
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: none;
            flex: 1 1 auto;
        }

        .login-container .card {
            width: min(940px, calc(100vw - 36px));
            min-height: 540px;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(148, 163, 184, 0.35);
            margin-top: 58px;
        }

        .login-container .card.register-mode {
            grid-template-columns: 0.92fr 1.08fr;
        }

        .form-panel {
            background: #ffffff;
            padding: 40px 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-panel-inner {
            width: 100%;
            max-width: 360px;
        }

        .auth-title {
            font-size: 2.15rem;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: var(--auth-ink);
            margin-bottom: 6px;
            font-weight: 800;
            text-align: center;
        }

        .auth-subtitle {
            color: var(--auth-muted);
            font-size: 0.93rem;
            text-align: center;
            margin-bottom: 28px;
        }

        #alert-container {
            margin-bottom: 14px;
        }

        .alert {
            padding: 11px 13px;
            border-radius: 10px;
            font-size: 0.88rem;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: #fff0f0;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            color: #334155;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            border: 1px solid var(--auth-border);
            border-radius: 10px;
            background: var(--auth-input);
            color: #111827;
            padding: 12px 13px;
            font-size: 0.94rem;
            font-family: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16);
            background: #ffffff;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .password-field-wrapper {
            position: relative;
        }

        .password-field-wrapper input {
            padding-right: 46px;
        }

        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle-btn:hover {
            color: #111827;
            background: rgba(17, 24, 39, 0.06);
        }

        .password-toggle-btn:focus-visible {
            outline: 2px solid #60a5fa;
            outline-offset: 1px;
        }

        .password-toggle-btn svg {
            width: 18px;
            height: 18px;
        }

        .btn {
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 12px 20px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-size: 0.76rem;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        .btn-primary {
            margin-top: 8px;
            color: #ffffff;
            background: var(--auth-gradient);
            box-shadow: 0 10px 18px rgba(30, 64, 175, 0.28);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px rgba(30, 64, 175, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.72;
            cursor: not-allowed;
        }

        .btn-secondary {
            margin-top: 10px;
            color: #1e3a8a;
            background: #ffffff;
            border: 1px solid #93c5fd;
        }

        .btn-secondary:hover:not(:disabled) {
            transform: translateY(-1px);
            background: #eff6ff;
        }

        .form-note {
            margin-top: 14px;
            color: #64748b;
            text-align: center;
            font-size: 0.78rem;
            line-height: 1.5;
        }

        .pitch-panel {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0;
            overflow: hidden;
            border-left: 1px solid rgba(148, 163, 184, 0.28);
        }

        .pitch-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            filter: contrast(1.03) saturate(1.02);
        }

        .mode-register-only {
            display: none;
        }

        .login-container .card.register-mode .form-panel {
            order: 2;
        }

        .login-container .card.register-mode .pitch-panel {
            order: 1;
        }

        .login-container .card.register-mode .mode-login-only {
            display: none;
        }

        .login-container .card.register-mode .mode-register-only {
            display: block;
        }

        .form-hidden {
            display: none;
        }

        @media (max-width: 900px) {
            .login-container .card,
            .login-container .card.register-mode {
                width: min(560px, calc(100vw - 24px));
                min-height: 0;
                grid-template-columns: 1fr;
                margin-top: 50px;
            }

            .page-auth-header {
                width: min(560px, calc(100vw - 24px));
            }

            .form-panel,
            .login-container .card.register-mode .form-panel {
                order: 2;
                padding: 34px 24px;
            }

            .pitch-panel,
            .login-container .card.register-mode .pitch-panel {
                order: 1;
                min-height: 260px;
                border-left: 0;
                border-bottom: 1px solid rgba(148, 163, 184, 0.28);
            }
        }

        @media (max-width: 460px) {
            main.login-container {
                padding: 10px;
            }

            .form-panel {
                padding: 28px 16px;
            }

            .page-auth-header {
                top: 8px;
                width: calc(100vw - 20px);
                height: 50px;
                border-radius: 12px;
                padding: 0 10px;
                gap: 8px;
            }

            .page-auth-logo {
                width: 24px;
                height: 24px;
            }

            .page-auth-title {
                font-size: 0.68rem;
                letter-spacing: 0.05em;
            }

            .page-auth-divider,
            .page-auth-subtitle {
                display: none;
            }

            .auth-title {
                font-size: 1.65rem;
            }

            .pitch-panel {
                min-height: 220px;
            }
        }
    </style>
</head>
<body>

<main class="login-container">
    <header class="page-auth-header">
        <img src="/School_Facility_Maintenance_System/frontend/assets/images/logo.png" alt="School Logo" class="page-auth-logo">
        <span class="page-auth-title">School Facility Maintenance</span>
        <span class="page-auth-divider" aria-hidden="true"></span>
        <span class="page-auth-subtitle">Philippine College of Science &amp; Technology</span>
    </header>
    <div class="card">
        <section class="form-panel">
            <div class="form-panel-inner">
                <div id="alert-container"></div>

                <form id="login-form" autocomplete="off">
                    <h1 class="auth-title">Sign In</h1>
                    <p class="auth-subtitle">Use your school account credentials to continue</p>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Email"
                            autocomplete="off"
                            readonly
                            value=""
                            required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Password"
                                autocomplete="new-password"
                                readonly
                                value=""
                                required>
                            <button type="button" id="toggle-login-password" class="password-toggle-btn" aria-label="Show password" title="Show password">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="login-btn">Sign In</button>
                    <button type="button" class="btn btn-secondary btn-block" id="show-register-btn">Create Account</button>
                </form>

                <form id="register-form" class="form-hidden" autocomplete="off">
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Register and wait for Super Admin approval</p>

                    <div class="form-group">
                        <label for="register_full_name">Name</label>
                        <input
                            type="text"
                            id="register_full_name"
                            name="register_full_name"
                            placeholder="Name"
                            autocomplete="off"
                            readonly
                            value=""
                            required>
                    </div>

                    <div class="form-group">
                        <label for="register_email">Email</label>
                        <input
                            type="email"
                            id="register_email"
                            name="register_email"
                            placeholder="Email"
                            autocomplete="off"
                            readonly
                            value=""
                            required>
                    </div>

                    <div class="form-group">
                        <label for="register_password">Password</label>
                        <input
                            type="password"
                            id="register_password"
                            name="register_password"
                            placeholder="Minimum 8 characters"
                            autocomplete="new-password"
                            readonly
                            value=""
                            required>
                    </div>

                    <div class="form-group">
                        <label for="register_confirm_password">Confirm Password</label>
                        <input
                            type="password"
                            id="register_confirm_password"
                            name="register_confirm_password"
                            placeholder="Re-enter password"
                            autocomplete="new-password"
                            readonly
                            value=""
                            required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="register-btn">Sign Up</button>
                    <button type="button" class="btn btn-secondary btn-block" id="show-login-btn">Back to Sign In</button>

                    <p class="form-note">After registration, your account will remain pending until Super Admin approval and role assignment.</p>
                </form>
            </div>
        </section>

        <aside class="pitch-panel">
            <img src="/School_Facility_Maintenance_System/frontend/assets/images/Philcst.jpg"
                 alt="Philcst"
                 class="pitch-image" />
        </aside>

        </div>
    </div>
</main>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
// Lock browser/page zoom behavior on login screen for stable form sizing.
(function lockLoginScale() {
    const applyZoomLock = () => {
        document.documentElement.style.zoom = '1';
        document.body.style.zoom = '1';
    };

    applyZoomLock();
    window.addEventListener('resize', applyZoomLock);

    window.addEventListener('wheel', (event) => {
        if (event.ctrlKey) {
            event.preventDefault();
        }
    }, { passive: false });

    window.addEventListener('keydown', (event) => {
        if (!(event.ctrlKey || event.metaKey)) {
            return;
        }

        const blockedKeys = ['+', '=', '-', '_', '0'];
        if (blockedKeys.includes(event.key)) {
            event.preventDefault();
        }
    });
})();

// Prevent browser autofill from populating login fields
document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const registerInputIds = [
        'register_full_name',
        'register_email',
        'register_password',
        'register_confirm_password'
    ];
    if (emailInput) {
        emailInput.value = '';
        emailInput.readOnly = true;
        emailInput.addEventListener('focus', () => { emailInput.readOnly = false; }, { once: true });
    }
    if (passwordInput) {
        passwordInput.value = '';
        passwordInput.readOnly = true;
        passwordInput.addEventListener('focus', () => { passwordInput.readOnly = false; }, { once: true });
    }

    registerInputIds.forEach((id) => {
        const input = document.getElementById(id);
        if (!input) return;

        input.value = '';
        input.readOnly = true;
        input.addEventListener('focus', () => { input.readOnly = false; }, { once: true });
    });

});

// Ensure API is defined globally
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/laravel_app/public/backend/api',
    async login(email, password) {
        const response = await fetch(`${this.baseURL}/auth.php?action=login`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        return data;
    },
    async register(payload) {
        const response = await fetch(`${this.baseURL}/auth.php?action=register`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        return data;
    }
};

// Ensure Session is defined globally
window.Session = window.Session || {
    get(key) { 
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : null;
    },
    set(key, value) { localStorage.setItem(key, JSON.stringify(value)); },
    clear() { localStorage.clear(); }
};

async function syncPhpSessionUser(userPayload) {
    const response = await fetch('/School_Facility_Maintenance_System/frontend/pages/set-session.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userPayload)
    });

    const data = await response.json();
    if (!data.success) {
        throw new Error('Failed to sync frontend PHP session');
    }
}

const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const showRegisterBtn = document.getElementById('show-register-btn');
const showLoginBtn = document.getElementById('show-login-btn');
const showRegisterBtnPanel = document.getElementById('show-register-btn-panel');
const showLoginBtnPanel = document.getElementById('show-login-btn-panel');
const alertContainer = document.getElementById('alert-container');
const authCard = document.querySelector('.login-container .card');
const loginPasswordInput = document.getElementById('password');
const toggleLoginPasswordBtn = document.getElementById('toggle-login-password');

function updateLoginPasswordToggleIcon() {
    if (!toggleLoginPasswordBtn || !loginPasswordInput) return;

    const isVisible = loginPasswordInput.type === 'text';
    toggleLoginPasswordBtn.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    toggleLoginPasswordBtn.setAttribute('title', isVisible ? 'Hide password' : 'Show password');

    toggleLoginPasswordBtn.innerHTML = isVisible
        ? '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.6 10.7C10.2 11.1 10 11.5 10 12C10 13.1 10.9 14 12 14C12.5 14 12.9 13.8 13.3 13.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 5.2C10.6 5.1 11.3 5 12 5C16.5 5 20.2 7.9 22 12C21.3 13.6 20.2 15 18.8 16.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 6.3C4.6 7.5 3.2 9.4 2 12C3.8 16.1 7.5 19 12 19C13.7 19 15.3 18.6 16.6 17.9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
}

if (toggleLoginPasswordBtn && loginPasswordInput) {
    toggleLoginPasswordBtn.addEventListener('click', () => {
        loginPasswordInput.type = loginPasswordInput.type === 'password' ? 'text' : 'password';
        updateLoginPasswordToggleIcon();
    });

    updateLoginPasswordToggleIcon();
}

function showLoginForm() {
    registerForm.classList.add('form-hidden');
    loginForm.classList.remove('form-hidden');
    alertContainer.innerHTML = '';
    if (authCard) {
        authCard.classList.remove('register-mode');
    }
}

function showRegisterForm() {
    loginForm.classList.add('form-hidden');
    registerForm.classList.remove('form-hidden');
    alertContainer.innerHTML = '';
    if (authCard) {
        authCard.classList.add('register-mode');
    }

    registerForm.reset();

    ['register_full_name', 'register_email', 'register_password', 'register_confirm_password'].forEach((id) => {
        const input = document.getElementById(id);
        if (!input) return;

        input.value = '';
        input.readOnly = true;
        input.addEventListener('focus', () => { input.readOnly = false; }, { once: true });
    });

}

showRegisterBtn.addEventListener('click', showRegisterForm);
showLoginBtn.addEventListener('click', showLoginForm);
if (showRegisterBtnPanel) {
    showRegisterBtnPanel.addEventListener('click', showRegisterForm);
}
if (showLoginBtnPanel) {
    showLoginBtnPanel.addEventListener('click', showLoginForm);
}
showLoginForm();

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const loginBtn = document.getElementById('login-btn');
    
    alertContainer.innerHTML = '';
    
    if (!email || !password) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Email and password are required</div>';
        return;
    }
    
    const originalText = loginBtn.innerHTML;
    loginBtn.innerHTML = 'Logging in...';
    loginBtn.disabled = true;
    
    try {
        const response = await window.API.login(email, password);
        
        if (response.success) {
            window.Session.set('user', response.data.user);
            await syncPhpSessionUser(response.data.user);
            alertContainer.innerHTML = '<div class="alert alert-success">Login successful! Redirecting...</div>';
            setTimeout(() => {
                const role = response.data.user.role;
                if (role === 'super_admin') {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/dashboard.php';
                } else if (role === 'maintenance_admin') {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/maintenance-dashboard.php';
                } else if (role === 'maintenance_staff') {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/staff-dashboard.php';
                } else {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/dashboard.php';
                }
            }, 500);
        } else {
            throw new Error(response.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        loginBtn.innerHTML = originalText;
        loginBtn.disabled = false;
    }
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fullName = document.getElementById('register_full_name').value.trim();
    const email = document.getElementById('register_email').value.trim();
    const password = document.getElementById('register_password').value;
    const confirmPassword = document.getElementById('register_confirm_password').value;
    const registerBtn = document.getElementById('register-btn');

    alertContainer.innerHTML = '';

    if (!fullName || !email || !password || !confirmPassword) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Please fill in all fields</div>';
        return;
    }

    if (password.length < 8) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Password must be at least 8 characters</div>';
        return;
    }

    if (password !== confirmPassword) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Passwords do not match</div>';
        return;
    }

    const originalText = registerBtn.innerHTML;
    registerBtn.innerHTML = 'Creating account...';
    registerBtn.disabled = true;

    try {
        await window.API.register({
            full_name: fullName,
            email,
            password
        });

        alertContainer.innerHTML = '<div class="alert alert-success">Registration submitted. Please wait for Super Admin approval.</div>';
        registerForm.reset();
        showLoginForm();
    } catch (error) {
        console.error('Register error:', error);
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        registerBtn.innerHTML = originalText;
        registerBtn.disabled = false;
    }
});
</script>

</body>
</html>
