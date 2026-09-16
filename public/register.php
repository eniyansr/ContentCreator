<?php
/**
 * CreatorAI - Creator Registration Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . base_url('creator/dashboard.php'));
    exit;
}

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – CreatorAI</title>
    <meta name="description" content="Create your free CreatorAI account and start generating AI-powered content.">
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container" style="max-width: 520px;">
            <div class="auth-card">
                <div class="auth-header">
                    <a href="<?php echo base_url('public/'); ?>" class="auth-logo">
                        <div class="auth-logo-icon">✦</div>
                        CreatorAI
                    </a>
                    <h1 class="auth-title">Create Your Account</h1>
                    <p class="auth-subtitle">Start your personalized AI content creation journey</p>
                </div>

                <div id="authAlert" class="auth-alert d-none"></div>

                <form id="registerForm" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Choose a username" required>
                        <span class="form-hint">3-30 characters. Letters, numbers, and underscores only.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">👁️</button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="str1"></div>
                            <div class="password-strength-bar" id="str2"></div>
                            <div class="password-strength-bar" id="str3"></div>
                            <div class="password-strength-bar" id="str4"></div>
                        </div>
                        <span class="password-strength-text form-hint" id="passwordStrengthText">Minimum 8 characters with uppercase, lowercase, number, and special character.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="country">Country <span class="required">*</span></label>
                        <select id="country" name="country" class="form-select" required>
                            <option value="">Select your country</option>
                            <option value="India">India</option>
                            <option value="United States">United States</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="Brazil">Brazil</option>
                            <option value="Japan">Japan</option>
                            <option value="South Korea">South Korea</option>
                            <option value="Indonesia">Indonesia</option>
                            <option value="Nigeria">Nigeria</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Mexico">Mexico</option>
                            <option value="Philippines">Philippines</option>
                            <option value="Vietnam">Vietnam</option>
                            <option value="Turkey">Turkey</option>
                            <option value="UAE">UAE</option>
                            <option value="Saudi Arabia">Saudi Arabia</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="preferred_language">Preferred Language</label>
                        <select id="preferred_language" name="preferred_language" class="form-select">
                            <option value="English">English</option>
                            <option value="Hindi">Hindi</option>
                            <option value="Spanish">Spanish</option>
                            <option value="French">French</option>
                            <option value="German">German</option>
                            <option value="Portuguese">Portuguese</option>
                            <option value="Arabic">Arabic</option>
                            <option value="Japanese">Japanese</option>
                            <option value="Korean">Korean</option>
                            <option value="Mandarin">Mandarin</option>
                            <option value="Indonesian">Indonesian</option>
                            <option value="Turkish">Turkish</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span class="form-check-label">I agree to the Terms of Service and Privacy Policy <span class="required">*</span></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="registerBtn">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="<?php echo base_url('public/login.php'); ?>">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo asset_url('js/api.js'); ?>"></script>
    <script src="<?php echo asset_url('js/utils.js'); ?>"></script>
    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
            btn.textContent = field.type === 'password' ? '👁️' : '🙈';
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const pwd = this.value;
            let strength = 0;
            if (pwd.length >= 8) strength++;
            if (/[A-Z]/.test(pwd)) strength++;
            if (/[0-9]/.test(pwd)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) strength++;

            const levels = ['weak', 'fair', 'good', 'strong'];
            const texts = ['Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['var(--error)', 'var(--warning)', 'var(--accent-4)', 'var(--success)'];

            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('str' + i);
                bar.className = 'password-strength-bar';
                if (i <= strength) {
                    bar.classList.add(levels[strength - 1]);
                }
            }

            const text = document.getElementById('passwordStrengthText');
            if (pwd.length > 0) {
                text.textContent = 'Password strength: ' + (texts[strength - 1] || 'Very Weak');
                text.style.color = colors[strength - 1] || 'var(--error)';
            } else {
                text.textContent = 'Minimum 8 characters with uppercase, lowercase, number, and special character.';
                text.style.color = '';
            }
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('registerBtn');
            const alert = document.getElementById('authAlert');

            // Client-side validation
            if (!document.getElementById('terms').checked) {
                alert.className = 'auth-alert error';
                alert.textContent = '✕ Please accept the Terms of Service.';
                alert.classList.remove('d-none');
                return;
            }

            Utils.btnLoading(btn, true);
            alert.classList.add('d-none');

            const formData = {
                full_name: document.getElementById('full_name').value.trim(),
                username: document.getElementById('username').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                confirm_password: document.getElementById('confirm_password').value,
                country: document.getElementById('country').value,
                preferred_language: document.getElementById('preferred_language').value,
                csrf_token: document.querySelector('[name="csrf_token"]').value,
            };

            try {
                const result = await API.post('api/auth/register.php', formData);
                
                if (result.success) {
                    alert.className = 'auth-alert success';
                    alert.textContent = '✓ ' + result.message;
                    alert.classList.remove('d-none');
                    
                    setTimeout(() => {
                        window.location.href = '<?php echo base_url("public/login.php"); ?>?registered=1';
                    }, 1500);
                }
            } catch (error) {
                alert.className = 'auth-alert error';
                alert.textContent = '✕ ' + (error.message || 'Registration failed. Please try again.');
                alert.classList.remove('d-none');
                Utils.btnLoading(btn, false);
            }
        });
    </script>
</body>
</html>
