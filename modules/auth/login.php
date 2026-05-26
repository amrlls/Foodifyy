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
            $user_id   = '';
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
                    header("Location: ../admin/dashboard.php");
                } elseif ($role === 'staff') {
                    header("Location: ../staff/dashboard.php");
                } else {
                    header("Location: ../index.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --accent: #FF6B6B;
            --bg-gradient: radial-gradient(at 0% 0%, rgba(255, 107, 107, 0.15) 0px, transparent 50%), 
                           radial-gradient(at 100% 0%, rgba(255, 142, 83, 0.15) 0px, transparent 50%), 
                           radial-gradient(at 100% 100%, rgba(255, 107, 107, 0.1) 0px, transparent 50%), 
                           radial-gradient(at 0% 100%, rgba(255, 142, 83, 0.15) 0px, transparent 50%);
            --soft-bg: #fdfdfd;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-bg);
            background-image: var(--bg-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            overflow-x: hidden;
        }

        /* Decorative background shapes */
        .bg-shape {
            position: absolute;
            z-index: -1;
            filter: blur(80px);
            border-radius: 50%;
            opacity: 0.6;
        }

        .auth-card {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.07);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            padding: 3.5rem 2.5rem 1.5rem;
            text-align: center;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.8rem;
            background: var(--primary-grad);
            
            -webkit-background-clip: text; background-clip: text;  
      

        -webkit-text-fill-color: transparent;
        color: transparent;    
            
            margin-bottom: 0.2rem;
            letter-spacing: -1px;
        }

        .ls-wide { letter-spacing: 2px; font-size: 0.7rem !important; }

        .auth-body { padding: 0 2.5rem 3.5rem; }

        h2 { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.8px; color: #1a1a1a; }
        .subtitle { color: #8e8e93; font-size: 0.95rem; margin-bottom: 2rem; }

        /* ── INPUTS ── */
        .input-group-custom {
            position: relative;
            margin-bottom: 1.2rem;
        }

        .input-left-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #A0A0A0;
            transition: 0.3s;
            z-index: 5;
        }

        .input-group-custom input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            background: #F8F9FA;
            border: 2px solid #F1F3F5;
            border-radius: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #2d3436;
        }

        .input-group-custom input:focus {
            outline: none;
            background: white;
            border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.1);
        }

        .input-group-custom input::placeholder { color: #adb5bd; font-weight: 400; }

        .input-group-custom:focus-within .input-left-icon { color: var(--accent); }

        .eye-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #adb5bd;
            padding: 8px;
            cursor: pointer;
            z-index: 5;
            transition: 0.2s;
        }

        .eye-toggle:hover { color: var(--accent); }

        /* ── BUTTONS & LINKS ── */
        .btn-login {
            background: var(--primary-grad);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1rem;
            transition: all 0.4s;
            box-shadow: 0 12px 24px rgba(255, 107, 107, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 30px rgba(255, 107, 107, 0.35);
            filter: brightness(1.05);
        }

        .btn-login:active { transform: translateY(-1px); }

        .forgot-link { text-align: right; margin-bottom: 1.5rem; }
        .forgot-link a { color: var(--accent); text-decoration: none; font-size: 0.88rem; font-weight: 700; transition: 0.2s; }
        .forgot-link a:hover { opacity: 0.8; }

        .register-link { text-align: center; margin-top: 2.2rem; font-size: 0.95rem; color: #636E72; }
        .register-link a { 
            color: #2D3436; 
            font-weight: 800; 
            text-decoration: none; 
            position: relative;
            padding-bottom: 2px;
        }
        .register-link a::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 2px;
            background: var(--primary-grad);
            transform: scaleX(0.3);
            transform-origin: left;
            transition: 0.3s;
        }
        .register-link a:hover::after { transform: scaleX(1); }

        .back-home {
            position: fixed;
            top: 40px; left: 40px;
            color: #1A1C1E;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            z-index: 100;
        }
        .back-home:hover { color: var(--accent); transform: translateX(-5px); }

        .alert-error {
            background: #fff5f5;
            color: #e03131;
            border-radius: 16px;
            padding: 16px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(224, 49, 49, 0.1);
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 576px) {
            .back-home { top: 20px; left: 20px; font-size: 0.9rem; }
            .auth-card { border-radius: 24px; }
            .auth-header { padding-top: 3rem; }
            .auth-body { padding: 0 1.8rem 3rem; }
        }
    </style>
</head>
<body>

<!-- Floating shapes for aesthetic depth -->
<div class="bg-shape" style="width: 300px; height: 300px; background: #FF6B6B; top: -100px; left: -100px;"></div>
<div class="bg-shape" style="width: 250px; height: 250px; background: #FF8E53; bottom: -50px; right: -50px;"></div>

<a href="../../index.php" class="back-home">
    <i class="bi bi-arrow-left-circle-fill fs-4"></i> <span>Back to Home</span>
</a>

<div class="auth-card">
    <div class="auth-header">
        <div class="brand-name">foodify.</div>
        <p class="text-muted small fw-bold text-uppercase ls-wide">Premium Kitchen Access</p>
    </div>

    <div class="auth-body">
        <h2>Welcome Back</h2>
        <p class="subtitle">Please enter your details to continue.</p>

        <?php if ($error): ?>
            <div class="alert-error" id="errorAlert">
                <i class="bi bi-exclamation-circle-fill fs-5"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="input-group-custom">
                <i class="input-left-icon bi bi-envelope-fill"></i>
                <input type="email" name="email" id="loginEmail" placeholder="Email Address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-shield-lock-fill"></i>
                <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                <button type="button" class="eye-toggle" id="eyeToggle">
                    <i class="bi bi-eye-fill" id="eyeIcon"></i>
                </button>
            </div>

            <div class="forgot-link">
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <span>Log In</span> <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="register-link">
            New here? <a href="register.php">Create an account</a>
        </div>
    </div>
</div>

<script>
    const eyeToggle = document.getElementById('eyeToggle');
    const eyeIcon   = document.getElementById('eyeIcon');
    const pwdInput  = document.getElementById('loginPassword');
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    // Password Toggle Logic
    eyeToggle.addEventListener('click', () => {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
            pwdInput.type = 'password';
            eyeIcon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
    });

    // Loading State UI Enhancement
    loginForm.addEventListener('submit', () => {
        submitBtn.style.opacity = '0.7';
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in...';
    });

    // Auto hide error after 3 seconds
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        setTimeout(function() {
            errorAlert.style.transition = 'all 0.5s ease';
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-10px)';
            setTimeout(function() { errorAlert.remove(); }, 500);
        }, 3000);
    }
</script>

</body>
</html>