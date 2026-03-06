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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Full-screen background with blur and darken effect */
        body {
            background-image: url('/School_Facility_Maintenance_System/frontend/assets/images/login-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            position: relative;
        }

        /* Semi-transparent overlay with blur effect */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1;
        }

        /* Login container - centered popup card */
        /* override global <main> rules (margin-left, background) which were
           causing the entire page to appear white behind the form */
        main.login-container {
            margin: 0;              /* remove the 240px sidebar offset */
            padding: 0;             /* we'll handle padding inside the card */
            background: transparent !important; /* get rid of white background */
            min-height: 100vh;      /* full viewport height for centering */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        /* Floating card with shadow and rounded corners */
        .login-container .card {
            background: transparent;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: none;
            animation: slideUp 0.5s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container .card:hover {
            transform: none;
            box-shadow: none;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card header with purple gradient theme */
        .card-header {
            background: linear-gradient(135deg, #6608be 0%, #7d1beb 100%);
            color: #ffffff;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 20px;
        }

        .card-header .login-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #ffffff;
            padding: 6px;
            object-fit: contain;
            box-shadow: 0 4px 15px rgba(102, 8, 190, 0.2);
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 600;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .card-header p {
            margin: 0;
            font-size: 0.95em;
            opacity: 0.95;
            font-weight: 300;
        }

        /* Card body styling */
        .card-body {
            padding: 40px 35px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 0px;
            margin-top: 0;
        }

        /* Alert styling */
        #alert-container {
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.95em;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        /* Form group styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ffffff;
            font-size: 0.95em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus {
            outline: none;
            border-color: #7d1beb;
            box-shadow: 0 0 0 3px rgba(125, 27, 235, 0.2);
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Button styling */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6608be 0%, #7d1beb 100%);
            color: #ffffff;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #5605a3 0%, #6d16d8 100%);
            box-shadow: 0 8px 20px rgba(102, 8, 190, 0.3);
            transform: translateY(-2px);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                padding: 15px;
            }

            .card-header {
                padding: 30px 25px;
                gap: 15px;
            }

            .card-header h2 {
                font-size: 1.2em;
            }

            .card-body {
                padding: 30px 25px;
            }

            .card-header .login-logo {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }

            .login-container .card {
                border-radius: 12px;
            }

            .card-header {
                padding: 25px 20px;
            }

            .card-header h2 {
                font-size: 1.1em;
            }

            .card-header p {
                font-size: 0.85em;
            }

            .card-body {
                padding: 25px 20px;
            }

            .card-header .login-logo {
                width: 90px;
                height: 90px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                font-size: 0.9em;
            }

            .form-group input {
                padding: 11px 12px;
                font-size: 0.9em;
            }

            .btn {
                padding: 11px 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

<main class="login-container">
    <div class="card">
        <div class="card-header">
            <img src="/School_Facility_Maintenance_System/frontend/assets/images/logo.png"
                 alt="School Logo" class="login-logo" />
            <h2>Philcst Centralize School Facility Maintenance</h2>
        </div>
        
        <div class="card-body">
            <div id="alert-container"></div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        value="admin@school.edu"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        value="Admin@123"
                        required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                    Login
                </button>
            </form>
            
        </div>
    </div>
</main>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
// Ensure API is defined globally
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
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

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const loginBtn = document.getElementById('login-btn');
    const alertContainer = document.getElementById('alert-container');
    
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
            alertContainer.innerHTML = '<div class="alert alert-success">Login successful! Redirecting...</div>';
            setTimeout(() => {
                const role = response.data.user.role;
                if (role === 'super_admin') {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/dashboard.php';
                } else if (role === 'maintenance_admin') {
                    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/maintenance-dashboard.php';
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
</script>

</body>
</html>
