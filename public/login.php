<?php
/**
 * CreatorAI - Creator Login Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . base_url($_SESSION['profile_completed'] ? 'creator/dashboard.php' : 'onboarding/'));
    exit;
}

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – CreatorAI</title>
    <meta name="description" content="Log in to your CreatorAI account to access your personalized AI content creation tools.">
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <a href="<?php echo base_url('public/'); ?>" class="auth-logo">
                        <div class="auth-logo-icon">✦</div>
                        CreatorAI
                    </a>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to your creator account</p>
                </div>

                <div id="authAlert" class="auth-alert d-none"></div>

                <form id="loginForm" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email or Username <span class="required">*</span></label>
                        <input type="text" id="email" name="email" class="form-input" placeholder="Enter your email or username" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">👁️</button>
                        </div>
                    </div>

                    <div class="auth-options">
                        <label class="form-check">
                            <input type="checkbox" name="remember" value="1">
                            <span class="form-check-label">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="<?php echo base_url('public/register.php'); ?>">Create Account</a></p>
                    <p style="margin-top: var(--space-3);"><a href="<?php echo base_url('admin/login.php'); ?>" style="color: var(--text-muted); font-size: var(--font-size-xs);">Admin Login →</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo asset_url('js/api.js'); ?>"></script>
    <script src="<?php echo asset_url('js/utils.js'); ?>"></script>
    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                btn.textContent = '🙈';
            } else {
                field.type = 'password';
                btn.textContent = '👁️';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            const alert = document.getElementById('authAlert');
            
            Utils.btnLoading(btn, true);
            alert.classList.add('d-none');

            const formData = {
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                csrf_token: document.querySelector('[name="csrf_token"]').value,
            };

            try {
                const result = await API.post('api/auth/login.php', formData);
                
                if (result.success) {
                    alert.className = 'auth-alert success';
                    alert.textContent = '✓ ' + result.message;
                    alert.classList.remove('d-none');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 500);
                }
            } catch (error) {
                alert.className = 'auth-alert error';
                alert.textContent = '✕ ' + (error.message || 'Login failed. Please try again.');
                alert.classList.remove('d-none');
                Utils.btnLoading(btn, false);
            }
        });
    </script>
</body>
</html>
