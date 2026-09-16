<?php
/**
 * CreatorAI - Admin Login Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/auth.css'); ?>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card admin-auth">
                <div class="auth-header">
                    <a href="<?php echo base_url('public/'); ?>" class="auth-logo">
                        <div class="auth-logo-icon" style="background: linear-gradient(135deg, #FD79A8, #E84393);">⚙</div>
                        CreatorAI <span style="font-size: var(--font-size-sm); color: var(--accent-5); font-weight: 500; margin-left: 4px;">Admin</span>
                    </a>
                    <h1 class="auth-title">Admin Login</h1>
                    <p class="auth-subtitle">Access the admin dashboard</p>
                </div>

                <div id="authAlert" class="auth-alert d-none"></div>

                <form id="adminLoginForm" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Admin Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="admin@creatorai.local" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter admin password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">👁️</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg" id="loginBtn" style="width:100%; background: linear-gradient(135deg, #FD79A8, #E84393); color: white;">
                        Admin Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Need an admin account? <a href="<?php echo base_url('admin/register.php'); ?>">Register</a></p>
                    <p style="margin-top: var(--space-3);"><a href="<?php echo base_url('public/login.php'); ?>" style="color: var(--text-muted); font-size: var(--font-size-xs);">← Creator Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo asset_url('js/api.js'); ?>"></script>
    <script src="<?php echo asset_url('js/utils.js'); ?>"></script>
    <script>
        function togglePassword(id, btn) {
            const f = document.getElementById(id);
            f.type = f.type === 'password' ? 'text' : 'password';
            btn.textContent = f.type === 'password' ? '👁️' : '🙈';
        }

        document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            const alert = document.getElementById('authAlert');
            Utils.btnLoading(btn, true);
            alert.classList.add('d-none');

            try {
                const result = await API.post('api/auth/admin_login.php', {
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                    csrf_token: document.querySelector('[name="csrf_token"]').value,
                });
                if (result.success) {
                    alert.className = 'auth-alert success';
                    alert.textContent = '✓ ' + result.message;
                    alert.classList.remove('d-none');
                    setTimeout(() => { window.location.href = result.redirect; }, 500);
                }
            } catch (error) {
                alert.className = 'auth-alert error';
                alert.textContent = '✕ ' + (error.message || 'Login failed.');
                alert.classList.remove('d-none');
                Utils.btnLoading(btn, false);
            }
        });
    </script>
</body>
</html>
