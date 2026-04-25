<?php
/**
 * Login Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

require_once __DIR__ . '/../../backend/config/settings.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: ' . public_url('/frontend/pages/dashboard.php'));
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/styles.css?v=20260415-1')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/color-scheme.css?v=20260415-1')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/login.css?v=20260415-1')); ?>">
</head>
<body>

<main class="login-container">
    <header class="page-auth-header">
        <img src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/logo.png')); ?>" alt="School Logo" class="page-auth-logo">
        <div class="page-auth-text">
            <span class="page-auth-title">School Facility Maintenance</span>
            <span class="page-auth-subtitle">Philippine College of Science &amp; Technology</span>
        </div>
    </header>
    <div class="card">
        <section class="form-panel">
            <div class="form-panel-inner">
                <div id="alert-container"></div>

                <form id="login-form" autocomplete="off">
                    <h2 class="auth-title">Sign In</h2>
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

                    <div class="login-options-row">
                        <label class="remember-me-control" for="remember_me">
                            <input type="checkbox" id="remember_me" name="remember_me">
                            <span>Remember me</span>
                        </label>
                        <button type="button" class="forgot-password-link" id="show-forgot-password-btn">Forgot password?</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="login-btn">Sign In</button>
                </form>

                <form id="forgot-password-form" class="form-hidden" autocomplete="off">
                    <h2 class="auth-title">Forgot Password</h2>
                    <p class="auth-subtitle">Request a reset code and set a new password</p>

                    <div class="form-group">
                        <label for="forgot_email">Email</label>
                        <input
                            type="email"
                            id="forgot_email"
                            name="forgot_email"
                            placeholder="Enter your email"
                            autocomplete="off"
                            readonly
                            value=""
                            required>
                    </div>

                    <button type="button" class="btn btn-secondary btn-block" id="send-reset-code-btn">Send Reset Code</button>
                    <button type="button" class="btn btn-secondary btn-block form-hidden" id="resend-reset-code-btn">Resend Code</button>

                    <div class="form-group">
                        <label for="forgot_code">Reset Code</label>
                        <input
                            type="text"
                            id="forgot_code"
                            name="forgot_code"
                            placeholder="Enter 6-digit code"
                            maxlength="6"
                            autocomplete="off"
                            readonly
                            value=""
                            required>
                    </div>

                    <div class="form-group">
                        <label for="forgot_password">New Password</label>
                        <div class="password-field-wrapper">
                            <input
                                type="password"
                                id="forgot_password"
                                name="forgot_password"
                                placeholder="Minimum 8 characters"
                                autocomplete="new-password"
                                readonly
                                value=""
                                required>
                            <button type="button" id="toggle-forgot-password" class="password-toggle-btn" aria-label="Show password" title="Show password">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="forgot_confirm_password">Confirm New Password</label>
                        <div class="password-field-wrapper">
                            <input
                                type="password"
                                id="forgot_confirm_password"
                                name="forgot_confirm_password"
                                placeholder="Re-enter new password"
                                autocomplete="new-password"
                                readonly
                                value=""
                                required>
                            <button type="button" id="toggle-forgot-confirm-password" class="password-toggle-btn" aria-label="Show password" title="Show password">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="forgot-reset-btn">Reset Password</button>
                    <button type="button" class="btn btn-secondary btn-block" id="forgot-back-login-btn">Back to Sign In</button>
                </form>

                <form id="register-form" class="form-hidden" autocomplete="off">
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Register and wait for Super Admin approval</p>

                    <div class="form-group" id="register-name-group">
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
                        <div class="field-error-text">Name must contain only letters, spaces, and hyphens (no numbers)</div>
                    </div>

                    <div class="form-group" id="register-email-group">
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
                        <div class="field-error-text">Please enter a valid email address</div>
                    </div>

                    <div class="form-group" id="register-password-group">
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
                        <div class="password-strength-meter" id="register-password-meter">
                            <div class="password-strength-bar" id="register-password-bar"></div>
                        </div>
                        <div class="password-strength-text" id="register-password-text"></div>
                        <div class="field-error-text">Password must be at least 8 characters with uppercase, lowercase, and numbers</div>
                    </div>

                    <div class="form-group" id="register-confirm-password-group">
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
                        <div class="field-error-text">Passwords do not match</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="register-btn">Sign Up</button>
                    <button type="button" class="btn btn-secondary btn-block" id="show-login-btn">Back to Sign In</button>

                </form>
            </div>
        </section>

        <aside class="pitch-panel">
            <div class="pitch-content">
                <div class="form-brand">
                    <img src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/logo.png')); ?>" alt="School Logo">
                    <div class="form-brand-text">
                        <div class="form-brand-name">Philippine College of Science &amp; Technology</div>
                        <div class="form-brand-subtitle">Institutional Facility Management System</div>
                    </div>
                </div>
                <div class="pitch-image-wrap">
                    <img
                        id="auth-pitch-image"
                        src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/3.jpg')); ?>"
                        alt="PhilCST Campus"
                        loading="lazy">
                </div>
            </div>
        </aside>

        </div>
    </div>
</main>

<script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/utils.js')); ?>"></script>
<script src="<?php echo htmlspecialchars(public_url('/frontend/assets/js/api.js')); ?>"></script>

<script>
// Disable zoom and scroll jumping on all devices
document.documentElement.style.zoom = '';
document.body.style.zoom = '';

// Prevent pinch zoom on mobile
document.addEventListener('wheel', (event) => {
    if (event.ctrlKey) {
        event.preventDefault();
    }
}, { passive: false });

// Prevent keyboard zoom shortcuts
document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey)) {
        return;
    }

    const blockedKeys = ['+', '=', '-', '_', '0'];
    if (blockedKeys.includes(event.key)) {
        event.preventDefault();
    }
});

// Prevent browser autofill from populating login fields
document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const rememberMeInput = document.getElementById('remember_me');
    const registerInputIds = [
        'register_full_name',
        'register_email',
        'register_password',
        'register_confirm_password',
        'forgot_email',
        'forgot_code',
        'forgot_password',
        'forgot_confirm_password'
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

    if (emailInput && rememberMeInput) {
        const rememberedEmail = localStorage.getItem('sfms_remembered_email');
        if (rememberedEmail) {
            emailInput.value = rememberedEmail;
            rememberMeInput.checked = true;
        }

        rememberMeInput.addEventListener('change', () => {
            if (rememberMeInput.checked && emailInput.value.trim()) {
                localStorage.setItem('sfms_remembered_email', emailInput.value.trim());
            } else if (!rememberMeInput.checked) {
                localStorage.removeItem('sfms_remembered_email');
            }
        });

        emailInput.addEventListener('input', () => {
            if (rememberMeInput.checked) {
                localStorage.setItem('sfms_remembered_email', emailInput.value.trim());
            }
        });
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
    baseURL: '<?php echo htmlspecialchars(public_url('/api')); ?>',
    async login(email, password) {
        const response = await fetch(`${this.baseURL}/auth/login`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Server returned an invalid response. Please check API path/config.');
        }

        if (!data.success) {
            const error = new Error(data.message || 'Login failed');
            error.status = response.status;
            error.data = data.data || {};
            throw error;
        }
        return data;
    },
    async register(payload) {
        const response = await fetch(`${this.baseURL}/auth/register`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Server returned an invalid response. Please try again.');
        }
        if (!data.success) throw new Error(data.message);
        return data;
    },
    async forgotPasswordRequest(email) {
        const response = await fetch(`${this.baseURL}/auth/forgot_password_request`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Server returned an invalid response. Please verify API routes and try again.');
        }
        if (!data.success) {
            const error = new Error(data.message || 'Failed to request reset code');
            error.status = response.status;
            error.data = data.data || {};
            throw error;
        }
        return data;
    },
    async forgotPasswordReset(email, resetCode, password) {
        const response = await fetch(`${this.baseURL}/auth/forgot_password_reset`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, reset_code: resetCode, password })
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Server returned an invalid response. Please verify API routes and try again.');
        }
        if (!data.success) throw new Error(data.message || 'Failed to reset password');
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
    const response = await fetch('<?php echo htmlspecialchars(public_url('/frontend/pages/set-session.php')); ?>', {
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
const forgotPasswordForm = document.getElementById('forgot-password-form');
const showRegisterBtn = document.getElementById('show-register-btn');
const showLoginBtn = document.getElementById('show-login-btn');
const showForgotPasswordBtn = document.getElementById('show-forgot-password-btn');
const forgotBackLoginBtn = document.getElementById('forgot-back-login-btn');
const sendResetCodeBtn = document.getElementById('send-reset-code-btn');
const resendResetCodeBtn = document.getElementById('resend-reset-code-btn');
const forgotResetBtn = document.getElementById('forgot-reset-btn');
const showRegisterBtnPanel = document.getElementById('show-register-btn-panel');
const showLoginBtnPanel = document.getElementById('show-login-btn-panel');
const alertContainer = document.getElementById('alert-container');
const authCard = document.querySelector('.login-container .card');
const loginPasswordInput = document.getElementById('password');
const toggleLoginPasswordBtn = document.getElementById('toggle-login-password');
const forgotPasswordInput = document.getElementById('forgot_password');
const toggleForgotPasswordBtn = document.getElementById('toggle-forgot-password');
const forgotConfirmPasswordInput = document.getElementById('forgot_confirm_password');
const toggleForgotConfirmPasswordBtn = document.getElementById('toggle-forgot-confirm-password');
const loginEmailInput = document.getElementById('email');
const loginBtn = document.getElementById('login-btn');
const rememberMeInput = document.getElementById('remember_me');

const LOGIN_LOCK_STORAGE_KEY_LEGACY = 'sfms_login_lock_until_ms';
const LOGIN_LOCK_DURATION_KEY_LEGACY = 'sfms_login_lock_duration_seconds';
const LOGIN_LOCK_STORAGE_KEY_PREFIX = 'sfms_login_lock_until_ms:';
const LOGIN_LOCK_DURATION_KEY_PREFIX = 'sfms_login_lock_duration_seconds:';
const FORGOT_RESET_COOLDOWN_KEY = 'sfms_forgot_reset_cooldown_until_ms';
const LOGIN_DEFAULT_BUTTON_TEXT = loginBtn ? loginBtn.innerHTML : 'Sign In';
let loginLockInterval = null;
let forgotResetCooldownInterval = null;

function normalizeLoginLockEmail(email) {
    return String(email || '').trim().toLowerCase();
}

function getLoginLockStorageKey(email) {
    return LOGIN_LOCK_STORAGE_KEY_PREFIX + normalizeLoginLockEmail(email);
}

function getLoginLockDurationKey(email) {
    return LOGIN_LOCK_DURATION_KEY_PREFIX + normalizeLoginLockEmail(email);
}

function formatCountdown(totalSeconds) {
    const safeSeconds = Math.max(0, Math.floor(totalSeconds));
    const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, '0');
    const seconds = String(safeSeconds % 60).padStart(2, '0');
    return `${minutes}:${seconds}`;
}

function setLoginControlsDisabled(disabled) {
    if (loginEmailInput) {
        loginEmailInput.disabled = disabled;
        loginEmailInput.readOnly = disabled;
    }

    if (loginPasswordInput) {
        loginPasswordInput.disabled = disabled;
        loginPasswordInput.readOnly = disabled;
    }

    if (rememberMeInput) {
        rememberMeInput.disabled = disabled;
    }

    if (toggleLoginPasswordBtn) {
        toggleLoginPasswordBtn.disabled = disabled;
    }

    if (loginBtn) {
        loginBtn.disabled = disabled;
    }
}

function clearLoginLockState(email = '') {
    const normalizedEmail = normalizeLoginLockEmail(email);
    if (normalizedEmail) {
        localStorage.removeItem(getLoginLockStorageKey(normalizedEmail));
        localStorage.removeItem(getLoginLockDurationKey(normalizedEmail));
    }

    // Cleanup legacy keys from older builds where lockout was global.
    localStorage.removeItem(LOGIN_LOCK_STORAGE_KEY_LEGACY);
    localStorage.removeItem(LOGIN_LOCK_DURATION_KEY_LEGACY);

    if (loginLockInterval) {
        window.clearInterval(loginLockInterval);
        loginLockInterval = null;
    }

    setLoginControlsDisabled(false);

    if (loginBtn) {
        loginBtn.innerHTML = LOGIN_DEFAULT_BUTTON_TEXT;
    }
}

function renderLoginLockAlert(countdown, progressPercent) {
    alertContainer.innerHTML = `
        <div class="alert alert-danger login-lock-alert" role="status" aria-live="polite">
            <div class="login-lock-header">
                <span class="login-lock-title">Login temporarily locked</span>
                <span class="login-lock-timer">${countdown}</span>
            </div>
            <div class="login-lock-message">Too many login attempts. Please wait before trying again.</div>
            <div class="login-lock-progress" aria-hidden="true">
                <span class="login-lock-progress-fill" style="width: ${progressPercent}%;"></span>
            </div>
        </div>
    `;
}

function startLoginLockTimer(retryAfterSeconds, durationSeconds = null, email = '') {
    const normalizedEmail = normalizeLoginLockEmail(email);
    const parsedRetry = Math.max(1, Number(retryAfterSeconds) || 300);
    const parsedDuration = Math.max(1, Number(durationSeconds) || parsedRetry);
    const lockUntilMs = Date.now() + (parsedRetry * 1000);
    if (normalizedEmail) {
        localStorage.setItem(getLoginLockStorageKey(normalizedEmail), String(lockUntilMs));
        localStorage.setItem(getLoginLockDurationKey(normalizedEmail), String(parsedDuration));
    }

    if (loginLockInterval) {
        window.clearInterval(loginLockInterval);
        loginLockInterval = null;
    }

    const tick = () => {
        const remainingSeconds = Math.ceil((lockUntilMs - Date.now()) / 1000);

        if (remainingSeconds <= 0) {
            clearLoginLockState(normalizedEmail);
            alertContainer.innerHTML = '<div class="alert alert-success">Login lock has ended. You can try again now.</div>';
            return;
        }

        setLoginControlsDisabled(true);
        const countdown = formatCountdown(remainingSeconds);
        const progressPercent = Math.max(0, Math.min(100, (remainingSeconds / parsedDuration) * 100));
        if (loginBtn) {
            loginBtn.innerHTML = `Locked (${countdown})`;
        }

        renderLoginLockAlert(countdown, progressPercent);
    };

    tick();
    loginLockInterval = window.setInterval(tick, 1000);
}

function restoreLoginLockTimer() {
    const email = normalizeLoginLockEmail(loginEmailInput ? loginEmailInput.value : '');

    // Remove legacy global lock keys from older builds.
    localStorage.removeItem(LOGIN_LOCK_STORAGE_KEY_LEGACY);
    localStorage.removeItem(LOGIN_LOCK_DURATION_KEY_LEGACY);

    if (!email) {
        return;
    }

    const lockUntilMs = Number(localStorage.getItem(getLoginLockStorageKey(email)) || 0);
    if (!lockUntilMs) {
        return;
    }

    const remainingSeconds = Math.ceil((lockUntilMs - Date.now()) / 1000);
    const durationSeconds = Number(localStorage.getItem(getLoginLockDurationKey(email)) || remainingSeconds);
    if (remainingSeconds <= 0) {
        clearLoginLockState(email);
        return;
    }

    startLoginLockTimer(remainingSeconds, durationSeconds, email);
}

function clearForgotPasswordCooldown() {
    localStorage.removeItem(FORGOT_RESET_COOLDOWN_KEY);

    if (forgotResetCooldownInterval) {
        window.clearInterval(forgotResetCooldownInterval);
        forgotResetCooldownInterval = null;
    }

    if (sendResetCodeBtn) {
        sendResetCodeBtn.disabled = false;
        sendResetCodeBtn.innerHTML = 'Send Reset Code';
        sendResetCodeBtn.classList.remove('form-hidden');
    }

    if (resendResetCodeBtn) {
        resendResetCodeBtn.disabled = false;
        resendResetCodeBtn.innerHTML = 'Resend Code';
        resendResetCodeBtn.classList.add('form-hidden');
    }
}

function startForgotPasswordCooldown(retryAfterSeconds) {
    const parsedRetry = Math.max(1, Number(retryAfterSeconds) || 60);
    const cooldownUntilMs = Date.now() + (parsedRetry * 1000);
    localStorage.setItem(FORGOT_RESET_COOLDOWN_KEY, String(cooldownUntilMs));

    if (forgotResetCooldownInterval) {
        window.clearInterval(forgotResetCooldownInterval);
        forgotResetCooldownInterval = null;
    }

    const tick = () => {
        const remainingSeconds = Math.ceil((cooldownUntilMs - Date.now()) / 1000);

        if (remainingSeconds <= 0) {
            clearForgotPasswordCooldown();
            return;
        }

        // Determine which button is visible and update only that one
        const sendBtnVisible = sendResetCodeBtn && !sendResetCodeBtn.classList.contains('form-hidden');
        const resendBtnVisible = resendResetCodeBtn && !resendResetCodeBtn.classList.contains('form-hidden');

        if (sendBtnVisible) {
            sendResetCodeBtn.disabled = true;
            sendResetCodeBtn.innerHTML = `Wait ${formatCountdown(remainingSeconds)} to resend`;
        }

        if (resendBtnVisible) {
            resendResetCodeBtn.disabled = true;
            resendResetCodeBtn.innerHTML = `Wait ${formatCountdown(remainingSeconds)} to resend`;
        }
    };

    tick();
    forgotResetCooldownInterval = window.setInterval(tick, 1000);
}

function restoreForgotPasswordCooldown() {
    const cooldownUntilMs = Number(localStorage.getItem(FORGOT_RESET_COOLDOWN_KEY) || 0);
    if (!cooldownUntilMs) {
        if (resendResetCodeBtn) {
            resendResetCodeBtn.classList.add('form-hidden');
        }
        return;
    }

    const remainingSeconds = Math.ceil((cooldownUntilMs - Date.now()) / 1000);
    if (remainingSeconds <= 0) {
        clearForgotPasswordCooldown();
        return;
    }

    if (resendResetCodeBtn) {
        resendResetCodeBtn.classList.remove('form-hidden');
    }

    startForgotPasswordCooldown(remainingSeconds);
}

function updatePasswordToggleIcon(toggleButton, passwordInput) {
    if (!toggleButton || !passwordInput) return;

    const isVisible = passwordInput.type === 'text';
    toggleButton.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    toggleButton.setAttribute('title', isVisible ? 'Hide password' : 'Show password');

    toggleButton.innerHTML = isVisible
        ? '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.6 10.7C10.2 11.1 10 11.5 10 12C10 13.1 10.9 14 12 14C12.5 14 12.9 13.8 13.3 13.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 5.2C10.6 5.1 11.3 5 12 5C16.5 5 20.2 7.9 22 12C21.3 13.6 20.2 15 18.8 16.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 6.3C4.6 7.5 3.2 9.4 2 12C3.8 16.1 7.5 19 12 19C13.7 19 15.3 18.6 16.6 17.9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
}

if (toggleLoginPasswordBtn && loginPasswordInput) {
    toggleLoginPasswordBtn.addEventListener('click', () => {
        loginPasswordInput.type = loginPasswordInput.type === 'password' ? 'text' : 'password';
        updatePasswordToggleIcon(toggleLoginPasswordBtn, loginPasswordInput);
    });

    updatePasswordToggleIcon(toggleLoginPasswordBtn, loginPasswordInput);
}

if (toggleForgotPasswordBtn && forgotPasswordInput) {
    toggleForgotPasswordBtn.addEventListener('click', () => {
        forgotPasswordInput.type = forgotPasswordInput.type === 'password' ? 'text' : 'password';
        updatePasswordToggleIcon(toggleForgotPasswordBtn, forgotPasswordInput);
    });

    updatePasswordToggleIcon(toggleForgotPasswordBtn, forgotPasswordInput);
}

if (toggleForgotConfirmPasswordBtn && forgotConfirmPasswordInput) {
    toggleForgotConfirmPasswordBtn.addEventListener('click', () => {
        forgotConfirmPasswordInput.type = forgotConfirmPasswordInput.type === 'password' ? 'text' : 'password';
        updatePasswordToggleIcon(toggleForgotConfirmPasswordBtn, forgotConfirmPasswordInput);
    });

    updatePasswordToggleIcon(toggleForgotConfirmPasswordBtn, forgotConfirmPasswordInput);
}

function showLoginForm() {
    registerForm.classList.add('form-hidden');
    forgotPasswordForm.classList.add('form-hidden');
    loginForm.classList.remove('form-hidden');
    alertContainer.innerHTML = '';
    if (authCard) {
        authCard.classList.remove('register-mode');
    }
}

function showRegisterForm() {
    loginForm.classList.add('form-hidden');
    forgotPasswordForm.classList.add('form-hidden');
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

function showForgotPasswordForm() {
    loginForm.classList.add('form-hidden');
    registerForm.classList.add('form-hidden');
    forgotPasswordForm.classList.remove('form-hidden');
    alertContainer.innerHTML = '';

    forgotPasswordForm.reset();

    ['forgot_email', 'forgot_code', 'forgot_password', 'forgot_confirm_password'].forEach((id) => {
        const input = document.getElementById(id);
        if (!input) return;

        input.value = '';
        input.readOnly = true;
        input.addEventListener('focus', () => { input.readOnly = false; }, { once: true });
    });
}

if (showRegisterBtn) {
    showRegisterBtn.addEventListener('click', showRegisterForm);
}
if (showForgotPasswordBtn) {
    showForgotPasswordBtn.addEventListener('click', showForgotPasswordForm);
}
showLoginBtn.addEventListener('click', showLoginForm);
if (forgotBackLoginBtn) {
    forgotBackLoginBtn.addEventListener('click', showLoginForm);
}
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
    const normalizedEmail = normalizeLoginLockEmail(email);
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('remember_me')?.checked;
    const lockUntilMs = Number(localStorage.getItem(getLoginLockStorageKey(normalizedEmail)) || 0);
    if (lockUntilMs > Date.now()) {
        const remainingSeconds = Math.ceil((lockUntilMs - Date.now()) / 1000);
        const durationSeconds = Number(localStorage.getItem(getLoginLockDurationKey(normalizedEmail)) || remainingSeconds);
        startLoginLockTimer(remainingSeconds, durationSeconds, normalizedEmail);
        return;
    }
    
    alertContainer.innerHTML = '';
    
    if (!email || !password) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Email and password are required</div>';
        return;
    }
    
    const originalText = loginBtn ? loginBtn.innerHTML : LOGIN_DEFAULT_BUTTON_TEXT;
    loginBtn.innerHTML = 'Logging in...';
    loginBtn.disabled = true;
    
    try {
        const response = await window.API.login(email, password);
        
        if (response.success) {
            clearLoginLockState(normalizedEmail);

            if (rememberMe) {
                localStorage.setItem('sfms_remembered_email', email);
            } else {
                localStorage.removeItem('sfms_remembered_email');
            }

            window.Session.set('user', response.data.user);
            await syncPhpSessionUser(response.data.user);
            alertContainer.innerHTML = '<div class="alert alert-success">Login successful! Redirecting...</div>';
            setTimeout(() => {
                const role = response.data.user.role;
                if (role === 'super_admin') {
                    window.location.href = '<?php echo htmlspecialchars(public_url('/frontend/pages/dashboard.php')); ?>';
                } else if (role === 'maintenance_admin') {
                    window.location.href = '<?php echo htmlspecialchars(public_url('/frontend/pages/maintenance-dashboard.php')); ?>';
                } else if (role === 'maintenance_staff') {
                    window.location.href = '<?php echo htmlspecialchars(public_url('/frontend/pages/staff-dashboard.php')); ?>';
                } else {
                    window.location.href = '<?php echo htmlspecialchars(public_url('/frontend/pages/dashboard.php')); ?>';
                }
            }, 500);
        } else {
            throw new Error(response.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);

        if (Number(error?.status) === 429) {
            const retryAfterSeconds = Number(error?.data?.retry_after_seconds || 300);
            startLoginLockTimer(retryAfterSeconds, retryAfterSeconds, normalizedEmail);
            return;
        }

        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        if (loginBtn) {
            loginBtn.innerHTML = originalText;
            loginBtn.disabled = false;
        }
    }
});

restoreLoginLockTimer();

// Validation helper functions
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function getPasswordStrength(password) {
    if (!password) return null;
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    if (strength <= 2) return 'weak';
    if (strength <= 4) return 'fair';
    return 'strong';
}

function updateFieldValidation(fieldId, isValid) {
    const groupMap = {
        register_full_name: 'register-name-group',
        register_email: 'register-email-group',
        register_password: 'register-password-group',
        register_confirm_password: 'register-confirm-password-group'
    };
    const group = document.getElementById(groupMap[fieldId] || (fieldId + '-group'));
    if (!group) return;

    if (isValid === true) {
        group.classList.remove('form-field-error');
        group.classList.add('form-field-valid');
    } else if (isValid === false) {
        group.classList.remove('form-field-valid');
        group.classList.add('form-field-error');
    } else {
        group.classList.remove('form-field-error', 'form-field-valid');
    }
}

function isValidFullName(name) {
    // Same rule as backend: letters (with accents), spaces, apostrophes, hyphens, periods
    return /^(?=.*\p{L})[\p{L} .'-]+$/u.test(name);
}

function updatePasswordStrength() {
    const password = document.getElementById('register_password').value;
    const meter = document.getElementById('register-password-meter');
    const bar = document.getElementById('register-password-bar');
    const text = document.getElementById('register-password-text');

    if (!password) {
        meter.classList.remove('active');
        bar.className = 'password-strength-bar';
        text.textContent = '';
        updateFieldValidation('register_password', null);
        return;
    }

    const strength = getPasswordStrength(password);
    meter.classList.add('active');
    bar.className = 'password-strength-bar ' + (strength || '');
    text.className = 'password-strength-text ' + (strength || '');
    text.textContent = strength ? 'Password Strength: ' + strength.charAt(0).toUpperCase() + strength.slice(1) : '';

    const isValid = password.length >= 8 && strength !== 'weak';
    updateFieldValidation('register_password', isValid);
}

function validateRegisterField(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    let isValid = null;

    if (fieldId === 'register_full_name') {
        if (field.value && field.value.trim().length > 0) {
            const name = field.value.trim();
            isValid = name.length >= 2 && isValidFullName(name);
        }
    } else if (fieldId === 'register_email') {
        if (field.value) {
            isValid = isValidEmail(field.value.trim());
        }
    } else if (fieldId === 'register_confirm_password') {
        const password = document.getElementById('register_password').value;
        if (field.value) {
            isValid = field.value === password && password.length >= 8;
        }
    }

    updateFieldValidation(fieldId, isValid);
}

// Add real-time validation listeners to register form
document.getElementById('register_full_name').addEventListener('blur', () => validateRegisterField('register_full_name'));
document.getElementById('register_full_name').addEventListener('change', () => validateRegisterField('register_full_name'));

document.getElementById('register_email').addEventListener('blur', () => validateRegisterField('register_email'));
document.getElementById('register_email').addEventListener('change', () => validateRegisterField('register_email'));

document.getElementById('register_password').addEventListener('input', updatePasswordStrength);
document.getElementById('register_password').addEventListener('blur', () => validateRegisterField('register_password'));

document.getElementById('register_confirm_password').addEventListener('input', () => validateRegisterField('register_confirm_password'));
document.getElementById('register_confirm_password').addEventListener('blur', () => validateRegisterField('register_confirm_password'));

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fullName = document.getElementById('register_full_name').value.trim();
    const email = document.getElementById('register_email').value.trim();
    const password = document.getElementById('register_password').value;
    const confirmPassword = document.getElementById('register_confirm_password').value;
    const registerBtn = document.getElementById('register-btn');

    alertContainer.innerHTML = '';

    // Validate all fields
    let hasErrors = false;
    const errorMessages = [];

    if (!fullName || fullName.length < 2 || !isValidFullName(fullName)) {
        updateFieldValidation('register_full_name', false);
        hasErrors = true;
        errorMessages.push('Name must contain only letters, spaces, apostrophes, and hyphens (no numbers).');
    } else {
        updateFieldValidation('register_full_name', true);
    }

    if (!isValidEmail(email)) {
        updateFieldValidation('register_email', false);
        hasErrors = true;
        errorMessages.push('Please enter a valid email address.');
    } else {
        updateFieldValidation('register_email', true);
    }

    const strength = getPasswordStrength(password);
    if (password.length < 8 || strength === 'weak') {
        updateFieldValidation('register_password', false);
        hasErrors = true;
        errorMessages.push('Password must be at least 8 characters and not weak.');
    } else {
        updateFieldValidation('register_password', true);
    }

    if (password !== confirmPassword) {
        updateFieldValidation('register_confirm_password', false);
        hasErrors = true;
        errorMessages.push('Passwords do not match.');
    } else {
        updateFieldValidation('register_confirm_password', true);
    }

    if (hasErrors) {
        alertContainer.innerHTML = `<div class="alert alert-danger">${errorMessages[0] || 'Please fix the errors above'}</div>`;
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
        document.getElementById('register-name-group').classList.remove('form-field-valid', 'form-field-error');
        document.getElementById('register-email-group').classList.remove('form-field-valid', 'form-field-error');
        document.getElementById('register-password-group').classList.remove('form-field-valid', 'form-field-error');
        document.getElementById('register-confirm-password-group').classList.remove('form-field-valid', 'form-field-error');
        showLoginForm();
    } catch (error) {
        console.error('Register error:', error);
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        registerBtn.innerHTML = originalText;
        registerBtn.disabled = false;
    }
});

if (sendResetCodeBtn) {
    sendResetCodeBtn.addEventListener('click', async () => {
        const forgotEmailInput = document.getElementById('forgot_email');
        const email = forgotEmailInput ? forgotEmailInput.value.trim() : '';

        if (!isValidEmail(email)) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Please enter a valid email address.</div>';
            return;
        }

        const originalText = sendResetCodeBtn.innerHTML;
        sendResetCodeBtn.disabled = true;
        sendResetCodeBtn.innerHTML = 'Sending code...';

        try {
            const response = await window.API.forgotPasswordRequest(email);
            const successMessage = response?.message || 'If your email is registered, a reset code has been sent to your email.';
            alertContainer.innerHTML = `<div class="alert alert-success">${successMessage} Please check your inbox and spam folder.</div>`;
            if (sendResetCodeBtn) {
                sendResetCodeBtn.classList.add('form-hidden');
            }
            if (resendResetCodeBtn) {
                resendResetCodeBtn.classList.remove('form-hidden');
            }
            startForgotPasswordCooldown(60);
        } catch (error) {
            if (Number(error?.status) === 429) {
                const retryAfterSeconds = Number(error?.data?.retry_after_seconds || 60);
                startForgotPasswordCooldown(retryAfterSeconds);
                alertContainer.innerHTML = '<div class="alert alert-danger">Please wait before requesting another reset code.</div>';
                return;
            }

            alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            if (!localStorage.getItem(FORGOT_RESET_COOLDOWN_KEY)) {
                sendResetCodeBtn.disabled = false;
                sendResetCodeBtn.innerHTML = originalText;
            }
        }
    });
}

if (resendResetCodeBtn) {
    resendResetCodeBtn.addEventListener('click', async () => {
        const forgotEmailInput = document.getElementById('forgot_email');
        const email = forgotEmailInput ? forgotEmailInput.value.trim() : '';

        if (!isValidEmail(email)) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Please enter a valid email address.</div>';
            return;
        }

        if (resendResetCodeBtn.disabled || sendResetCodeBtn.disabled) {
            return;
        }

        const originalText = resendResetCodeBtn.innerHTML;
        resendResetCodeBtn.disabled = true;
        resendResetCodeBtn.innerHTML = 'Sending code...';

        try {
            const response = await window.API.forgotPasswordRequest(email);
            const successMessage = response?.message || 'A new reset code has been sent to your email.';
            alertContainer.innerHTML = `<div class="alert alert-success">${successMessage} Please check your inbox and spam folder.</div>`;
            startForgotPasswordCooldown(60);
        } catch (error) {
            if (Number(error?.status) === 429) {
                const retryAfterSeconds = Number(error?.data?.retry_after_seconds || 60);
                startForgotPasswordCooldown(retryAfterSeconds);
                alertContainer.innerHTML = '<div class="alert alert-danger">Please wait before requesting another reset code.</div>';
                return;
            }

            alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            if (!localStorage.getItem(FORGOT_RESET_COOLDOWN_KEY)) {
                resendResetCodeBtn.disabled = false;
                resendResetCodeBtn.innerHTML = originalText;
            }
        }
    });
}
restoreForgotPasswordCooldown();

if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('forgot_email').value.trim();
        const resetCode = document.getElementById('forgot_code').value.trim();
        const password = document.getElementById('forgot_password').value;
        const confirmPassword = document.getElementById('forgot_confirm_password').value;

        if (!isValidEmail(email)) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Please enter a valid email address.</div>';
            return;
        }

        if (!/^\d{6}$/.test(resetCode)) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Reset code must be a 6-digit number.</div>';
            return;
        }

        if (password.length < 8) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Password must be at least 8 characters.</div>';
            return;
        }

        if (password !== confirmPassword) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
            return;
        }

        const originalText = forgotResetBtn ? forgotResetBtn.innerHTML : 'Reset Password';
        if (forgotResetBtn) {
            forgotResetBtn.disabled = true;
            forgotResetBtn.innerHTML = 'Resetting password...';
        }

        try {
            await window.API.forgotPasswordReset(email, resetCode, password);
            alertContainer.innerHTML = '<div class="alert alert-success">Password reset successful. You can now sign in.</div>';
            showLoginForm();
        } catch (error) {
            alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        } finally {
            if (forgotResetBtn) {
                forgotResetBtn.disabled = false;
                forgotResetBtn.innerHTML = originalText;
            }
        }
    });
}
</script>

</body>
</html>

