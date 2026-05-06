<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$step    = 1;
$error   = '';
$success = '';
$email   = '';

// Step 2 — Verify email and show reset form
if (isset($_GET['step']) && $_GET['step'] == 2 && isset($_GET['email'])) {
    $step  = 2;
    $email = base64_decode($_GET['email']);
}

// Handle Step 1 — Check email exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 1) {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $error = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'Email not found.';
        } else {
            // Go to step 2
            $encoded = base64_encode($email);
            header("Location: forgot_password.php?step=2&email=$encoded");
            exit();
        }
    }
}

// Handle Step 2 — Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 2) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$password || !$confirm) {
        $step  = 2;
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 8) {
        $step  = 2;
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $step  = 2;
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {
            $success = 'Password updated! Redirecting to login...';
            header("refresh:2;url=login.php");
        } else {
            $step  = 2;
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2D7A3A;
            --primary-orange: #FF6B00;
            --primary-orange-light: #FF8C38;
            --dark-text: #1A1A1A;
            --muted: #888;
            --border: #E8E8E8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
                font-family: 'Nunito', sans-serif;
                background:
                    radial-gradient(ellipse at 20% 50%, rgba(120, 198, 121, 0.25) 0%, transparent 60%),
                    radial-gradient(ellipse at 80% 20%, rgba(255, 183, 77, 0.2) 0%, transparent 55%),
                    radial-gradient(ellipse at 60% 80%, rgba(100, 181, 246, 0.15) 0%, transparent 50%),
                    linear-gradient(135deg, #f0faf0 0%, #fffde7 50%, #e3f2fd 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
        .auth-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 36px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .auth-card:hover { transform: translateY(-4px); }
        .auth-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, #3E9B4E 60%, #9FA825 100%);
            padding: 2.2rem 2rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .auth-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .auth-header::after {
            content: '';
            position: absolute;
            bottom: -30px; left: -30px;
            width: 110px; height: 110px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }
        .logo-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 14px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}
        .logo-text { line-height: 1; text-align: left; }
        .logo-text .brand {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: white;
        }
        .logo-text .tagline {
            font-size: 11px;
            font-weight: 600;
            opacity: 0.85;
        }
        .auth-body { padding: 2rem 2rem 2.5rem; }
        .auth-body h2 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 4px;
        }
        .auth-body .subtitle {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 16px;
        }
        .input-group-custom i.input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #bbb;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.2s;
        }
        .input-group-custom:focus-within i.input-icon { color: var(--primary-orange); }
        .input-group-custom input {
            width: 100%;
            padding: 13px 44px 13px 44px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s;
            background: #fafafa;
            color: var(--dark-text);
        }
        .input-group-custom input:focus {
            outline: none;
            border-color: var(--primary-orange);
            background: white;
            box-shadow: 0 0 0 4px rgba(255,107,0,0.08);
        }
        .eye-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            font-size: 1rem;
            z-index: 3;
            padding: 4px 6px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }
        .eye-toggle:hover { color: var(--primary-orange); }
        .btn-submit {
            background: var(--primary-orange);
            border: none;
            padding: 14px;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 15px;
            color: white;
            width: 100%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: var(--primary-orange-light);
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(255,107,0,0.3);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        .back-link a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 800;
        }
        .back-link a:hover { text-decoration: underline; }
        .alert-custom {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        .alert-success {
            background: #DCFCE7;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--border);
            transition: background 0.3s;
        }
        .step-dot.active { background: var(--primary-orange); }
        .step-line {
            width: 40px;
            height: 2px;
            background: var(--border);
        }
        .back-home {
            position: fixed;
            top: 24px; left: 24px;
            background: white;
            padding: 8px 18px;
            border-radius: 40px;
            text-decoration: none;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            font-size: 13px;
            color: var(--primary-green);
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            transition: all 0.2s;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .back-home:hover {
            background: var(--primary-green);
            color: white;
            transform: translateX(-3px);
        }
        @media (max-width: 575px) {
            .auth-card { border-radius: 28px; }
            .auth-body { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<a href="login.php" class="back-home">
    <i class="bi bi-arrow-left"></i> Back to Login
</a>

<div class="auth-card">
    <div class="auth-header">
        <div class="logo-wrap">
    <img src="../../assets/images/logo.png" alt="Foodify Logo" class="logo-image">
    <div class="logo-text">
        <div class="brand">Foodify</div>
        <div class="tagline">Recipes + Groceries E-Commerce</div>
    </div>
    </div>
</div>

    <div class="auth-body">

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>"></div>
            <div class="step-line"></div>
            <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
        </div>

        <?php if ($step == 1): ?>

            <h2>Forgot Password?</h2>
            <p class="subtitle">Enter your registered email to reset your password.</p>

            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="step" value="1">
                <div class="input-group-custom">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Email Address"
                           value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-search"></i> Find Account
                </button>
            </form>

        <?php elseif ($step == 2): ?>

            <h2>Reset Password</h2>
            <p class="subtitle">Create a new password for <strong><?= htmlspecialchars($email) ?></strong></p>

            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="input-group-custom">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" id="newPassword" placeholder="New Password" required>
                    <button type="button" class="eye-toggle" onclick="toggleEye('newPassword', 'eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>

                <div class="input-group-custom">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" name="confirm" id="confirmPassword" placeholder="Confirm Password" required>
                    <button type="button" class="eye-toggle" onclick="toggleEye('confirmPassword', 'eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-circle"></i> Reset Password
                </button>
            </form>

        <?php endif; ?>

        <div class="back-link">
            Remember your password? <a href="login.php">Log in</a>
        </div>
    </div>
</div>

<script>
    function toggleEye(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            ico.className = 'bi bi-eye';
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>