<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'Email not found.';
        } else {
            $user_id       = '';
            $username = '';
            $hashed   = '';
            $role     = '';
            $stmt->bind_result($user_id, $username, $hashed, $role);
            $stmt->fetch();

            if (!password_verify($password, $hashed)) {
                $error = 'Wrong password.';
            } else {
                $_SESSION['user_id']  = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $role;

                // Redirect based on role
                if ($role === 'admin') {
                    header("Location: ../../admin/dashboard.php");
                } elseif ($role === 'staff') {
                    header("Location: ../../admin/dashboard.php");
                } else {
                    header("Location: ../../index.php");
                }
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2D7A3A;
            --primary-green-mid: #3E9B4E;
            --primary-orange: #FF6B00;
            --primary-orange-light: #FF8C38;
            --primary-orange-pale: #FFF3E8;
            --green-light: #EAF4EB;
            --dark-text: #1A1A1A;
            --mid-text: #4A4A4A;
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
            font-family: 'Nunito', sans-serif;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: white;
        }
        .logo-text .tagline {
            font-size: 11px;
            font-weight: 600;
            opacity: 0.85;
            letter-spacing: 0.3px;
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
            margin-bottom: 28px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .input-group-custom .input-left-icon {
            position: absolute;
            left: 16px;
            color: #bbb;
            font-size: 1.1rem;
            z-index: 2;
            pointer-events: none;
            transition: color 0.2s;
        }
        .input-group-custom:focus-within .input-left-icon { color: var(--primary-orange); }
        .input-group-custom input {
            width: 100%;
            padding: 13px 46px 13px 44px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s;
            background: #fafafa;
            color: var(--dark-text);
            box-sizing: border-box;
        }
        .input-group-custom input:focus {
            outline: none;
            border-color: var(--primary-orange);
            background: white;
            box-shadow: 0 0 0 4px rgba(255,107,0,0.08);
        }
        .eye-toggle {

            position: absolute;
            right: 0;
            top: 0; bottom: 0;
            width: 44px;
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            font-size: 1rem;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
            border-radius: 0 14px 14px 0;
        }
        .eye-toggle:hover { color: var(--primary-orange); }
        .forgot-link {
            text-align: right;
            margin-top: -6px;
            margin-bottom: 20px;
        }
        .forgot-link a {
            color: var(--primary-green);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }
        .forgot-link a:hover { color: var(--primary-orange); }
        .btn-login {
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
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            background: var(--primary-orange-light);
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(255,107,0,0.3);
        }
        .btn-login:active { transform: scale(0.99); }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        .register-link a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 800;
        }
        .register-link a:hover { text-decoration: underline; }
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
        .alert-custom {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        @media (max-width: 575px) {
            .auth-card { border-radius: 28px; }
            .auth-body { padding: 1.5rem; }
            .back-home { top: 16px; left: 16px; font-size: 12px; padding: 6px 14px; }
        }
    </style>
</head>
<body>

<a href="../../index.php" class="back-home">
    <i class="bi bi-arrow-left"></i>Back
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
        <h2>Welcome Back!</h2>
        <p class="subtitle">Enter your email and password to log in.</p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-envelope"></i>
                <input type="email" name="email" id="loginEmail" placeholder="Email Address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-lock"></i>
                <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                <button type="button" class="eye-toggle" id="eyeToggle">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>

            <div class="forgot-link">
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Log In
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register now</a>
        </div>
    </div>
</div>

<script>
    const eyeToggle = document.getElementById('eyeToggle');
    const eyeIcon   = document.getElementById('eyeIcon');
    const pwdInput  = document.getElementById('loginPassword');

    eyeToggle.addEventListener('click', () => {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.className = 'bi bi-eye-slash';
        } else {
            pwdInput.type = 'password';
            eyeIcon.className = 'bi bi-eye';
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>