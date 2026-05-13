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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Foodify</title>
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
            --border-color: #F1F3F5;
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
            padding: 40px 20px;
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
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.07);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            padding: 3rem 2.5rem 1rem;
            text-align: center;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.8rem;
            background: var(--primary-grad);
            -webkit-background-clip: text;
            background-clip: text;              /* tambah ni */

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
            border: 2px solid var(--border-color);
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
        }

        /* ── STRENGTH BAR ── */
        .strength-bar-wrap {
            display: flex;
            gap: 6px;
            margin-top: -5px;
            margin-bottom: 5px;
            padding: 0 5px;
        }
        .strength-bar-wrap .bar {
            flex: 1;
            height: 5px;
            border-radius: 10px;
            background: var(--border-color);
            transition: background 0.4s ease;
        }
        .strength-label {
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            padding-left: 5px;
            color: #A0A0A0;
        }

        /* ── TERMS & CONDITIONS ── */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 2rem;
        }
        .terms-row input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
            margin-top: 3px;
        }
        .terms-row label {
            font-size: 0.85rem;
            color: #636E72;
            line-height: 1.5;
            cursor: pointer;
        }
        .terms-row a { color: var(--accent); text-decoration: none; font-weight: 700; }

        /* ── BUTTONS & LINKS ── */
        .btn-register {
            background: var(--primary-grad);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s;
            box-shadow: 0 12px 24px rgba(255, 107, 107, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 30px rgba(255, 107, 107, 0.35);
        }

        .login-link { text-align: center; margin-top: 2.2rem; font-size: 0.95rem; color: #636E72; }
        .login-link a { color: #2D3436; font-weight: 800; text-decoration: none; border-bottom: 2px solid var(--accent); }

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

        /* ── ALERTS ── */
        .alert-custom {
            padding: 16px;
            border-radius: 16px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }
        .alert-error { background: #fff5f5; color: #e03131; border-color: rgba(224, 49, 49, 0.1); }
        .alert-success { background: #f2fcf5; color: #099268; border-color: rgba(9, 146, 104, 0.1); }

        @media (max-width: 576px) {
            .back-home { top: 20px; left: 20px; font-size: 0.9rem; }
            .auth-card { border-radius: 24px; }
            .auth-body { padding: 0 1.8rem 3rem; }
        }
    </style>
</head>
<body>

<div class="bg-shape" style="width: 300px; height: 300px; background: #FF6B6B; top: -100px; left: -100px;"></div>
<div class="bg-shape" style="width: 250px; height: 250px; background: #FF8E53; bottom: -50px; right: -50px;"></div>

<?php
$backUrl = '../../index.php';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];

    // Elak kosong / self redirect
    if (
        !empty($referer) &&
        strpos($referer, 'register.php') === false
    ) {
        $backUrl = $referer;
    }
}
?>

<a href="<?= htmlspecialchars($backUrl) ?>" class="back-home">
    <i class="bi bi-arrow-left-circle-fill fs-4"></i> Back
</a>
<div class="auth-card">
    <div class="auth-header">
        <div class="brand-name">foodify.</div>
        <p class="text-muted small fw-bold text-uppercase ls-wide">Join the Premium Kitchen</p>
    </div>

    <div class="auth-body">
        <h2>Create Account</h2>
        <p class="subtitle">Sign up now and start cooking with ease!</p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-circle-fill fs-5"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-custom alert-success">
                <i class="bi bi-check-circle-fill fs-5"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm" novalidate>
            <div class="input-group-custom">
                <i class="input-left-icon bi bi-person-fill"></i>
                <input type="text" name="username" id="regUsername" placeholder="Username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-envelope-fill"></i>
                <input type="email" name="email" id="regEmail" placeholder="Email Address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-telephone-fill"></i>
                <input type="tel" name="phone" id="regPhone" placeholder="Phone Number"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="input-group-custom">
                <i class="input-left-icon bi bi-shield-lock-fill"></i>
                <input type="password" name="password" id="regPassword" placeholder="Password" required>
                <button type="button" class="eye-toggle" id="eyeToggle1">
                    <i class="bi bi-eye-fill" id="eyeIcon1"></i>
                </button>
            </div>

            <div class="strength-bar-wrap" id="strengthBars">
                <div class="bar" id="bar1"></div>
                <div class="bar" id="bar2"></div>
                <div class="bar" id="bar3"></div>
                <div class="bar" id="bar4"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Enter your password</div>

            <div class="terms-row">
                <input type="checkbox" name="terms" id="agreeTerms" required>
                <label for="agreeTerms">
                    I agree to the <a href="#">Terms &amp; Conditions</a> and 
                    <a href="#">Privacy Policy</a> of Foodify
                </label>
            </div>

            <button type="submit" class="btn-register" id="registerBtn">
                Register Now <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in now</a>
        </div>
    </div>
</div>

<script>
    // Eye toggle logic (Setup as per original setupEye function)
    function setupEye(btnId, iconId, inputId) {
        document.getElementById(btnId).addEventListener('click', () => {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                inp.type = 'password';
                ico.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        });
    }
    setupEye('eyeToggle1', 'eyeIcon1', 'regPassword');

    // Password strength logic (Original Logic Kept)
    const pwdInput = document.getElementById('regPassword');
    const bars = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4')
    ];
    const label    = document.getElementById('strengthLabel');
    const colors   = ['#EF4444','#F59E0B','#3B82F6','#22C55E']; // Matching modern palette
    const labels   = ['Very Weak','Fair','Strong','Very Strong'];

    pwdInput.addEventListener('input', () => {
        const v = pwdInput.value;
        let score = 0;
        if (v.length >= 8)           score++;
        if (/[A-Z]/.test(v))        score++;
        if (/[0-9]/.test(v))        score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        bars.forEach((b, i) => {
            b.style.background = i < score ? colors[score - 1] : 'var(--border-color)';
        });
        label.style.color   = score > 0 ? colors[score - 1] : '#A0A0A0';
        label.textContent   = v.length === 0 ? 'Enter your password' : (labels[score - 1] || 'Very Weak');
    });

    // Loading State for button
    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('registerBtn');
        btn.style.opacity = '0.7';
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
    });
</script>

</body>
</html>