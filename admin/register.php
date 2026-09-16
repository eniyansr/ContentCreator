<?php
/**
 * CreatorAI - Admin Registration Page
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
    <title>Admin Registration – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/auth.css'); ?>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container" style="max-width: 520px;">
            <div class="auth-card admin-auth">
                <div class="auth-header">
                    <a href="<?php echo base_url('public/'); ?>" class="auth-logo">
                        <div class="auth-logo-icon" style="background: linear-gradient(135deg, #FD79A8, #E84393);">⚙</div>
                        CreatorAI <span style="font-size: var(--font-size-sm); color: var(--accent-5); font-weight: 500; margin-left: 4px;">Admin</span>
                    </a>
                    <h1 class="auth-title">Admin Registration</h1>
                    <p class="auth-subtitle">Create an admin account with a valid registration key</p>
                </div>

                <div id="authAlert" class="auth-alert d-none"></div>

                <form id="adminRegisterForm" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Admin Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Admin Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your admin email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registration_key">Admin Registration Key <span class="required">*</span></label>
                        <input type="password" id="registration_key" name="registration_key" class="form-input" placeholder="Enter the secret registration key" required>
                        <span class="form-hint">Contact the system administrator for the registration key.</span>
                    </div>

                    <button type="submit" class="btn btn-lg" id="registerBtn" style="width:100%; background: linear-gradient(135deg, #FD79A8, #E84393); color: white;">
                        Create Admin Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an admin account? <a href="<?php echo base_url('admin/login.php'); ?>">Admin Login</a></p>
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

        document.getElementById('adminRegisterForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('registerBtn');
            const alert = document.getElementById('authAlert');
            Utils.btnLoading(btn, true);
            alert.classList.add('d-none');

            try {
                const result = await API.post('api/auth/admin_register.php', {
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                    confirm_password: document.getElementById('confirm_password').value,
                    registration_key: document.getElementById('registration_key').value,
                    csrf_token: document.querySelector('[name="csrf_token"]').value,
                });
                if (result.success) {
                    alert.className = 'auth-alert success';
                    alert.textContent = '✓ ' + result.message;
                    alert.classList.remove('d-none');
                    setTimeout(() => { window.location.href = '<?php echo base_url("admin/login.php"); ?>'; }, 1500);
                }
            } catch (error) {
                alert.className = 'auth-alert error';
                alert.textContent = '✕ ' + (error.message || 'Registration failed.');
                alert.classList.remove('d-none');
                Utils.btnLoading(btn, false);
            }
        });
    </script>
</body>
</html>
