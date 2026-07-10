<?php
require_once '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - AARC-360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #003f47;
            --secondary-color: #a3cc38;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: #eef1f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 920px;
            min-height: 560px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 24px 60px -20px rgba(0, 40, 46, 0.28);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* -------- Brand panel -------- */
        .login-brand {
            position: relative;
            background: linear-gradient(155deg, #003f47 0%, #06555f 55%, #0b6b73 100%);
            color: #fff;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .login-brand::before {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(163, 204, 56, 0.22) 0%, rgba(163, 204, 56, 0) 70%);
            top: -120px;
            right: -120px;
        }
        .login-brand::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.08);
            bottom: -100px;
            left: -80px;
        }
        .login-brand-logo {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            width: fit-content;
        }
        .login-brand-logo img { width: 150px; display: block; }

        .login-brand-copy { position: relative; z-index: 1; }
        .login-brand-copy h2 {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 12px;
        }
        .login-brand-copy p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.72);
            line-height: 1.6;
            max-width: 320px;
        }

        .login-brand-tags {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .login-brand-tag {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #d8ecc0;
            background: rgba(163, 204, 56, 0.16);
            border: 1px solid rgba(163, 204, 56, 0.3);
            border-radius: 999px;
            padding: 5px 12px;
        }

        /* -------- Form panel -------- */
        .login-form-panel {
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-heading h1 {
            font-size: 24px;
            font-weight: 700;
            color: #14201f;
            margin-bottom: 6px;
        }
        .login-heading p {
            font-size: 13.5px;
            color: #6b7876;
            margin-bottom: 28px;
        }

        .login-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #a12626;
            background: #fdecec;
            border: 1px solid #f6c8c8;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 20px;
        }

        .login-field { margin-bottom: 18px; }
        .login-field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #29332f;
            margin-bottom: 6px;
        }
        .login-input-wrap { position: relative; }
        .login-input-wrap i.field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b9895;
            font-size: 15px;
        }
        .login-input-wrap input {
            width: 100%;
            border: 1.5px solid #dde3e1;
            border-radius: 10px;
            padding: 11px 14px 11px 40px;
            font-size: 14px;
            color: #1a1a1a;
            background: #fbfcfc;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .login-input-wrap input::placeholder { color: #a3aeab; }
        .login-input-wrap input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 63, 71, 0.1);
        }
        .login-toggle-visibility {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8b9895;
            cursor: pointer;
            font-size: 15px;
            padding: 4px;
            line-height: 1;
        }
        .login-toggle-visibility:hover { color: var(--primary-color); }

        .login-submit {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14.5px;
            font-weight: 600;
            color: #fff;
            background: var(--primary-color);
            margin-top: 6px;
            transition: background .15s ease, transform .1s ease;
        }
        .login-submit:hover { background: #05545e; }
        .login-submit:active { transform: translateY(1px); }
        .login-submit:disabled { opacity: .85; cursor: not-allowed; }

        .login-footer-note {
            text-align: center;
            font-size: 12.5px;
            color: #8b9895;
            margin-top: 22px;
        }

        @media (max-width: 820px) {
            .login-shell { grid-template-columns: 1fr; max-width: 420px; }
            .login-brand { display: none; }
            .login-form-panel { padding: 40px 32px; }
        }

        /* -------- Full-page sign-in loading overlay -------- */
        .signin-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(155deg, #003f47 0%, #06555f 55%, #0b6b73 100%);
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }
        .signin-overlay.show { opacity: 1; pointer-events: all; }
        .signin-overlay-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 22px;
            transform: translateY(10px);
            opacity: 0;
            transition: transform .35s ease .1s, opacity .35s ease .1s;
        }
        .signin-overlay.show .signin-overlay-card { transform: translateY(0); opacity: 1; }
        .signin-overlay-logo {
            background: #fff;
            border-radius: 14px;
            padding: 16px 22px;
            box-shadow: 0 20px 50px -18px rgba(0, 0, 0, 0.5);
        }
        .signin-overlay-logo img { width: 150px; display: block; }
        .signin-overlay-spinner {
            width: 34px; height: 34px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.22);
            border-top-color: var(--secondary-color);
            animation: signin-spin .7s linear infinite;
        }
        @keyframes signin-spin { to { transform: rotate(360deg); } }
        .signin-overlay-text {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            letter-spacing: .01em;
        }
        .signin-overlay-sub {
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.65);
            margin-top: -12px;
        }
        .signin-overlay-track {
            width: 200px;
            height: 4px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            overflow: hidden;
        }
        .signin-overlay-fill {
            height: 100%;
            width: 30%;
            border-radius: 999px;
            background: var(--secondary-color);
            animation: signin-progress 1.1s ease-in-out infinite;
        }
        @keyframes signin-progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(330%); }
        }
    </style>
</head>
<body>

<div class="login-shell">
    <div class="login-brand">
        <div class="login-brand-logo">
            <img src="../assets/images/aarc-360-logo-1.webp" alt="AARC-360">
        </div>
        <div class="login-brand-copy">
            <h2>Plan schedules with confidence, all in one place.</h2>
            <p>Manage engagements, track time off, and keep every team member's week in view.</p>
        </div>
        <div class="login-brand-tags">
            <span class="login-brand-tag">Assurance</span>
            <span class="login-brand-tag">Advisory</span>
            <span class="login-brand-tag">Risk</span>
            <span class="login-brand-tag">Compliance</span>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="login-heading">
            <h1>Welcome back</h1>
            <p>Sign in to access your engagement schedules</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="login-alert"><i class="bi bi-exclamation-circle-fill"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="login-field">
                <label for="email">Email</label>
                <div class="login-input-wrap">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="text" id="email" name="email" placeholder="Enter your email address" required>
                </div>
            </div>
            <div class="login-field">
                <label for="password">Password</label>
                <div class="login-input-wrap">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required style="padding-right: 40px;">
                    <button type="button" class="login-toggle-visibility" id="togglePassword" aria-label="Show password">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="login-submit" id="loginSubmitBtn">
                <span id="loginSubmitLabel">Sign In</span>
            </button>
        </form>

        <p class="login-footer-note">Contact your administrator for account setup.</p>
    </div>
</div>

<div class="signin-overlay" id="signinOverlay">
    <div class="signin-overlay-card">
        <div class="signin-overlay-logo">
            <img src="../assets/images/aarc-360-logo-1.webp" alt="AARC-360">
        </div>
        <div class="signin-overlay-spinner"></div>
        <div class="signin-overlay-text">Signing you in&hellip;</div>
        <div class="signin-overlay-sub">Loading your schedule and workspace</div>
        <div class="signin-overlay-track"><div class="signin-overlay-fill"></div></div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    document.getElementById('loginForm').addEventListener('submit', function (e) {
        const form = this;
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        if (!email || !password) return;
        if (form.dataset.submitting === '1') { e.preventDefault(); return; }

        // Defer the real submission until after the overlay has painted -
        // a plain form POST can start navigating in the same tick this
        // handler runs, before the browser ever renders the overlay.
        e.preventDefault();
        form.dataset.submitting = '1';

        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        document.getElementById('loginSubmitLabel').innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing in...';
        document.getElementById('signinOverlay').classList.add('show');

        requestAnimationFrame(() => requestAnimationFrame(() => form.submit()));
    });
</script>
</body>
</html>
