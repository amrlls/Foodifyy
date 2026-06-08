<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';

$step    = 1;
$error   = '';
$success = '';
$email   = '';

//Load from URL token
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        $error = 'Invalid or expired reset link. Please request a new one.';
        $step  = 1;
    } elseif (strtotime($result['expires_at']) < time()) {
        $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();
        $error = 'This reset link has expired. Please request a new one.';
        $step  = 1;
    } else {
        $step  = 2;
        $email = $result['email'];
    }
}

//Check email & send reset link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 1) {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $error = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'Email not found.';
        } else {
            // Generate secure token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Delete old token for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            // Insert new token
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expiresAt);
            $ins->execute();

            // Build reset link — tukar domain kalau dah live
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/modules/auth/forgot_password.php?token=" . $token;

            if (sendResetEmail($email, $resetLink)) {
                $success = 'sent';
            } else {
                $error = 'Failed to send email. Please try again.';
            }
        }
    }
}

// Reset password 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] == 2) {
    $token    = $_POST['token']    ?? '';
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? AND email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        $error = 'Invalid reset link.';
        $step  = 1;
    } elseif (strtotime($result['expires_at']) < time()) {
        $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();
        $error = 'Reset link expired. Please request a new one.';
        $step  = 1;
    } elseif (!$password || !$confirm) {
        $error = 'Please fill in all fields.';
        $step  = 2;
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $step  = 2;
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
        $step  = 2;
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $upd    = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hashed, $email);

        if ($upd->execute()) {
            $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $del->bind_param("s", $token);
            $del->execute();
            $success = 'done';
            header("refresh:2;url=login.php");
        } else {
            $error = 'Failed to update password. Please try again.';
            $step  = 2;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --accent: #FF6B6B;
            --bg-gradient: radial-gradient(at 0% 0%, rgba(255,107,107,0.15) 0px, transparent 50%),
                           radial-gradient(at 100% 0%, rgba(255,142,83,0.15) 0px, transparent 50%),
                           radial-gradient(at 100% 100%, rgba(255,107,107,0.1) 0px, transparent 50%),
                           radial-gradient(at 0% 100%, rgba(255,142,83,0.15) 0px, transparent 50%);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fdfdfd;
            background-image: var(--bg-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px; margin: 0;
        }
        .auth-card {
            width: 100%; max-width: 450px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(15px);
            border-radius: 32px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.07);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeIn 0.6s ease-out;
            padding: 3.5rem 2.5rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .brand-name {
            font-family: 'Playfair Display', serif; font-weight: 900;
            font-size: 2.8rem;
            background: var(--primary-grad);
            -webkit-background-clip: text; background-clip: text;  
      

        -webkit-text-fill-color: transparent;
        color: transparent;    
            text-align: center; margin-bottom: 0.2rem; letter-spacing: -1px;
        }
        h2 { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.8px; color: #1a1a1a; }
        .subtitle { color: #8e8e93; font-size: 0.95rem; margin-bottom: 2rem; }

        .input-group-custom { position: relative; margin-bottom: 1.2rem; }
        .input-left-icon {
            position: absolute; left: 20px; top: 50%;
            transform: translateY(-50%); color: #A0A0A0; z-index: 5;
        }
        .input-group-custom input {
            width: 100%; padding: 16px 20px 16px 52px;
            background: #F8F9FA; border: 2px solid #F1F3F5;
            border-radius: 18px; font-weight: 600;
            transition: all 0.3s ease; color: #2d3436;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .input-group-custom input:focus {
            outline: none; background: white; border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(255,107,107,0.1);
        }
        .eye-toggle {
            position: absolute; right: 15px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; color: #adb5bd;
            padding: 8px; cursor: pointer; z-index: 5;
        }
        .btn-submit {
            background: var(--primary-grad); color: white; border: none;
            width: 100%; padding: 18px; border-radius: 18px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 1rem; transition: all 0.4s;
            box-shadow: 0 12px 24px rgba(255,107,107,0.25);
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 18px 30px rgba(255,107,107,0.35); }
        .back-home {
            position: fixed; top: 40px; left: 40px;
            color: #1A1C1E; text-decoration: none; font-weight: 700;
            display: flex; align-items: center; gap: 10px; transition: 0.3s; z-index: 100;
        }
        .back-home:hover { color: var(--accent); transform: translateX(-5px); }
        .alert-custom {
            border-radius: 16px; padding: 16px; font-size: 0.9rem;
            font-weight: 600; margin-bottom: 1.8rem;
            display: flex; align-items: center; gap: 12px;
        }
        .alert-error   { background: #fff5f5; color: #e03131; border: 1px solid rgba(224,49,49,0.1); }
        .alert-success { background: #f0fff4; color: #2f855a; border: 1px solid rgba(47,133,90,0.1); }
        .step-indicator { display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem; }
        .step-dot { width: 30px; height: 6px; border-radius: 10px; background: #eee; transition: 0.3s; }
        .step-dot.active { background: var(--accent); width: 50px; }

    
        .sent-box { text-align: center; padding: 1rem 0 0.5rem; }
        .sent-icon {
            width: 90px; height: 90px; border-radius: 50%;
            background: #FFF0EB;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .done-box { text-align: center; padding: 1rem 0 0.5rem; }
        .done-icon {
            width: 90px; height: 90px; border-radius: 50%;
            background: #FFF0EB;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }

        @media (max-width: 576px) {
            .auth-card { padding: 2.5rem 1.8rem; }
            .back-home { top: 20px; left: 20px; }
        }
    </style>
</head>
<body>

<a href="login.php" class="back-home">
    <i class="bi bi-arrow-left-circle-fill fs-4"></i> <span>Back to Login</span>
</a>

<div class="auth-card">
    <div class="brand-name">foodify.</div>

    <div class="step-indicator">
        <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
    </div>

    <?php if ($success === 'sent'): ?>
    <!-- Email sent  -->
        <div class="sent-box">
            <div class="sent-icon">
                <i class="bi bi-envelope-check-fill" style="font-size:2.5rem; color:#FF6B6B;"></i>
            </div>
            <h2 class="mb-2">Check Your Email!</h2>
            <p class="text-muted">Reset link sent to<br><strong><?= htmlspecialchars($email) ?></strong></p>
            <p class="text-muted" style="font-size:0.85rem; margin-top:1rem;">
                Link expires in <b>15 minutes</b>.<br>Check your spam folder if not found.
            </p>
        </div>

    <?php elseif ($success === 'done'): ?>
    <!-- Password updated -->
        <div class="done-box">
            <div class="done-icon">
                <i class="bi bi-check-lg" style="font-size:2.5rem; color:#FF6B6B;"></i>
            </div>
            <h2 class="mb-2">Password Updated!</h2>
            <p class="text-muted">Your password has been reset successfully.<br>Redirecting to login...</p>
        </div>

    <?php elseif ($step == 1): ?>
    <!-- Enter Email ── -->
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your email and we'll send you a reset link.</p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="forgotForm">
            <input type="hidden" name="step" value="1">
            <div class="input-group-custom">
                <i class="input-left-icon bi bi-envelope-fill"></i>
                <input type="email" name="email" placeholder="Email Address"
                       value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn1">
                <span>Send Reset Link</span> <i class="bi bi-send-fill"></i>
            </button>
        </form>

    <?php elseif ($step == 2): ?>
    <!-- New Password  -->
        <h2>Reset Password</h2>
        <p class="subtitle">Set a new password for<br><strong><?= htmlspecialchars($email) ?></strong></p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="resetForm">
            <input type="hidden" name="step"  value="2">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token'] ?? '') ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-shield-lock-fill"></i>
                <input type="password" name="password" id="newPassword"
                       placeholder="New Password (min. 8 characters)" required>
                <button type="button" class="eye-toggle" onclick="toggleEye('newPassword','eye1')">
                    <i class="bi bi-eye-fill" id="eye1"></i>
                </button>
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-shield-check"></i>
                <input type="password" name="confirm" id="confirmPassword"
                       placeholder="Confirm New Password" required>
                <button type="button" class="eye-toggle" onclick="toggleEye('confirmPassword','eye2')">
                    <i class="bi bi-eye-fill" id="eye2"></i>
                </button>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn2">
                <span>Update Password</span> <i class="bi bi-check-lg"></i>
            </button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4">
        <span class="text-muted small">Remember it?</span>
        <a href="login.php" class="text-dark text-decoration-none small fw-bold ms-1">Log in here</a>
    </div>
</div>

<script>
    function toggleEye(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.className = 'bi bi-eye-slash-fill';
        } else {
            inp.type = 'password';
            ico.className = 'bi bi-eye-fill';
        }
    }

    [['forgotForm','submitBtn1'], ['resetForm','submitBtn2']].forEach(([formId, btnId]) => {
        const f = document.getElementById(formId);
        if (f) {
            f.addEventListener('submit', () => {
                const btn = document.getElementById(btnId);
                btn.style.opacity = '0.7';
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
            });
        }
    });
</script>
</body>
</html>