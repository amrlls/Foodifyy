<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $terms    = isset($_POST['terms']);

    // Validation
    if (!$username || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms & Conditions.';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            // Insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2  = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $username, $email, $phone, $hashed);

            if ($stmt2->execute()) {
                $success = 'Account created successfully! Redirecting...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Foodify</title>
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
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
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
            margin-bottom: 24px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 14px;
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
        .input-group-custom:focus-within i.input-icon { color: var(--primary-orange); }
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
            line-height: 1;
            display: flex;
            align-items: center;
        }
        .eye-toggle:hover { color: var(--primary-orange); }
        .strength-bar-wrap {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            margin-bottom: 2px;
        }
        .strength-bar-wrap .bar {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            transition: background 0.3s;
        }
        .strength-label {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--muted);
        }
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 14px 0 20px;
        }
        .terms-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--primary-orange);
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .terms-row label {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            line-height: 1.5;
        }
        .terms-row label a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 700;
        }
        .terms-row label a:hover { color: var(--primary-orange); }
        .btn-register {
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
        .btn-register:hover {
            background: var(--primary-orange-light);
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(255,107,0,0.3);
        }
        .btn-register:active { transform: scale(0.99); }
        .btn-register:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        .login-link a {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 800;
        }
        .login-link a:hover { text-decoration: underline; }
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
        <h2>Create Account</h2>
        <p class="subtitle">Sign up now and start cooking with ease!</p>
        

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

        <form method="POST" action="" id="registerForm" novalidate>

            <!-- Username -->
            <div class="input-group-custom">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="username" id="regUsername" placeholder="Username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>

            <!-- Email -->
            <div class="input-group-custom">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" name="email" id="regEmail" placeholder="Email Address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <!-- Phone -->
            <div class="input-group-custom">
                <i class="bi bi-telephone input-icon"></i>
                <input type="tel" name="phone" id="regPhone" placeholder="Phone Number"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <!-- Password -->
            <div class="input-group-custom">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" id="regPassword" placeholder="Password" required>
                <button type="button" class="eye-toggle" id="eyeToggle1">
                    <i class="bi bi-eye" id="eyeIcon1"></i>
                </button>
            </div>

            <!-- Strength Bar -->
            <div class="strength-bar-wrap" id="strengthBars">
                <div class="bar" id="bar1"></div>
                <div class="bar" id="bar2"></div>
                <div class="bar" id="bar3"></div>
                <div class="bar" id="bar4"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Enter your password</div>

            <!-- Terms -->
            <div class="terms-row">
                <input type="checkbox" name="terms" id="agreeTerms" required>
                <label for="agreeTerms">
                    I agree to the <a href="#">Terms &amp; Conditions</a> and
                    <a href="#">Privacy Policy</a> Foodify
                </label>
            </div>

            <button type="submit" class="btn-register" id="registerBtn">
                <i class="bi bi-person-plus"></i> Register Now
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in now</a>
        </div>
    </div>
</div>

<script>
    // Eye toggle
    function setupEye(btnId, iconId, inputId) {
        document.getElementById(btnId).addEventListener('click', () => {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.className = 'bi bi-eye-slash';
            } else {
                inp.type = 'password';
                ico.className = 'bi bi-eye';
            }
        });
    }
    setupEye('eyeToggle1', 'eyeIcon1', 'regPassword');

    // Password strength
    const pwdInput = document.getElementById('regPassword');
    const bars = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4')
    ];
    const label    = document.getElementById('strengthLabel');
    const colors   = ['#EF4444','#F59E0B','#3B82F6','#22C55E'];
    const labels   = ['Very Weak','Fair','Strong','Very Strong'];

    pwdInput.addEventListener('input', () => {
        const v = pwdInput.value;
        let score = 0;
        if (v.length >= 8)          score++;
        if (/[A-Z]/.test(v))        score++;
        if (/[0-9]/.test(v))        score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        bars.forEach((b, i) => {
            b.style.background = i < score ? colors[score - 1] : 'var(--border)';
        });
        label.style.color   = score > 0 ? colors[score - 1] : 'var(--muted)';
        label.textContent   = v.length === 0 ? 'Enter your password' : (labels[score - 1] || 'Very Weak');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>