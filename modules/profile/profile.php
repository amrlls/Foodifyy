<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$status = $_GET['status'] ?? "";

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = $user['username'] ?? 'Guest';
$nav_role = $user['role'] ?? 'Customer';

if (isset($_POST['update_profile'])) {
    $fullname    = $_POST['fullname'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $address     = $_POST['address'];
    $profile_img = $user['profile_image'];

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $new_image_url = uploadToCloudinary($_FILES['profile_pic']['tmp_name'], 'foodify/profiles');
        $conn->ping();

        if ($new_image_url) {
            $profile_img = $new_image_url;
        } else {
            $target_dir = "../../assets/images/profiles/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
            $new_filename = "user_" . $userId . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
                if (!empty($user['profile_image']) && !str_starts_with($user['profile_image'], 'http')) {
                    $old_path = $target_dir . $user['profile_image'];
                    if (file_exists($old_path)) unlink($old_path);
                }
                $profile_img = $new_filename;
            }
        }
    }

    $update = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
    $update->bind_param("sssssi", $fullname, $email, $phone, $address, $profile_img, $userId);
    if ($update->execute()) {
        $_SESSION['username'] = $fullname;
        header("Refresh:0; url=profile.php?status=profile_success");
        exit();
    }
}

if (isset($_POST['update_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd     = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    if (password_verify($current_pwd, $user['password'])) {
        if ($new_pwd === $confirm_pwd) {
            if (strlen($new_pwd) >= 8) {
                $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
                $upd_pwd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upd_pwd->bind_param("si", $hashed_pwd, $userId);
                if ($upd_pwd->execute()) {
                    header("Location: profile.php?status=password_success");
                    exit();
                }
            } else {
                header("Location: profile.php?status=password_short");
                exit();
            }
        } else {
            header("Location: profile.php?status=password_mismatch");
            exit();
        }
    } else {
        header("Location: profile.php?status=current_pwd_wrong");
        exit();
    }
}

$profileSrc = getImageSrc($user['profile_image'], '../../assets/images/profiles/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            --sidebar-dark: #1A1C1E;
            --accent: #FF8E53;
            --soft-bg: #F8F9FA;
            --sidebar-w: 280px;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--soft-bg); color: #2D3436; overflow-x: hidden; }

        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-dark); color: white;
            padding: 2.5rem 1.5rem; z-index: 1000;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif; font-weight: 900;
            letter-spacing: -1px;
            background: var(--primary-grad); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem; padding-left: 1rem;
        }
        .sidebar-greet-box { padding-left: 1rem; margin-bottom: 3rem; }
        .sidebar-greet-box p { color: #949494; font-size: 0.8rem; margin: 0; font-weight: 400; }
        .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 15px; padding: 14px 18px;
            color: #949494; text-decoration: none; border-radius: 16px;
            font-weight: 500; transition: all 0.3s ease;
        }
        .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar-nav a.active { background: var(--primary-grad); color: white; box-shadow: 0 10px 20px rgba(255,107,107,0.25); }
        .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            padding: 15px; border-radius: 20px; transition: all 0.2s ease; cursor: pointer;
        }
        .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }
        .user-card:active { transform: scale(0.95); background: rgba(255,255,255,0.1); }
        .user-card img { transition: transform 0.3s ease; }
        .user-card:hover img { transform: rotate(5deg); }

        .main-content { margin-left: var(--sidebar-w); padding: 3rem; }
        .profile-card {
            background: white; border-radius: 32px; padding: 2.5rem;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); height: 100%;
        }
        .top-bar { margin-bottom: 2rem; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; font-weight: 900; color: #1A1C1E; line-height: 1; margin: 0; }
        .top-bar p { color: #7f8c8d; font-size: 1.1rem; margin-top: 0.8rem; }

        .profile-img-preview-wrapper { position: relative; width: 140px; height: 140px; margin: 0 auto 2.5rem; }
        .profile-img-preview { width: 100%; height: 100%; border-radius: 40px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }

        .form-label { font-weight: 800; color: #1A1C1E; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.8rem; }
        .form-control {
            border-radius: 18px; padding: 14px 20px;
            background: #F8F9FA; border: 1px solid #EDEDED;
            font-weight: 500; transition: 0.3s;
        }
        .form-control:focus { background: white; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(255,142,83,0.1); }

        /* Eye toggle button */
        .pwd-toggle {
            position: absolute; top: 50%; right: 14px;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #bdc3c7; font-size: 1rem; padding: 4px;
            transition: color 0.2s; z-index: 5;
        }
        .pwd-toggle:hover { color: var(--accent); }

        .btn-update-main {
            background: var(--primary-grad); color: white; border: none;
            border-radius: 20px; padding: 18px; font-weight: 800; width: 100%;
            transition: 0.3s; box-shadow: 0 10px 20px rgba(255,107,107,0.25); margin-top: 1rem;
        }
        .btn-update-main:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(255,107,107,0.35); color: white; }
        .btn-password-alt {
            background: var(--sidebar-dark); color: white; border-radius: 20px;
            padding: 18px; font-weight: 700; width: 100%; border: none; transition: 0.3s;
        }
        .btn-password-alt:hover { background: #000; color: white; }
        .info-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: #FFF0EB; color: #FF6B6B;
            padding: 6px 16px; border-radius: 50px;
            font-weight: 700; font-size: 0.75rem; margin-bottom: 1.5rem;
        }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px); z-index: 10000;
            display: none; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .confirm-box {
            background: white; padding: 3rem; border-radius: 40px;
            width: 90%; max-width: 450px; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popupFade 0.25s ease;
        }
        @keyframes popupFade { from { opacity:0; transform:scale(0.9) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .modal-icon-circle { width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; }
        .modal-icon-circle.orange { background:#FFF0EB; }
        .modal-icon-circle.red    { background:#FFEAEA; }
        .btn-modal-primary {
            background: linear-gradient(135deg,#FF6B6B 0%,#FF8E53 100%);
            color:white; border:none; border-radius:50px; padding:14px 0;
            font-weight:800; width:100%; font-size:0.95rem; transition:0.3s; cursor:pointer;
        }
        .btn-modal-primary:hover { opacity:0.9; transform:translateY(-2px); }
        .btn-modal-cancel {
            background:#F1F3F5; color:#2D3436; border:none; border-radius:50px;
            padding:14px 0; font-weight:700; width:100%; font-size:0.95rem; transition:0.3s; cursor:pointer;
        }
        .btn-modal-cancel:hover { background:#E2E5E9; }
        /* ── TOPBAR MOBILE ── */
        .topbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: var(--sidebar-dark);
            padding: 1rem 1.5rem;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-logo {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 1.5rem;
            letter-spacing: -1px;
            background: var(--primary-grad);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hamburger {
            background: none;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
            padding: 4px;
        }

        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }

        .sidebar-overlay.active {
            display: block;
        }
        /* ── MOBILE ── */
        @media (max-width: 768px) {

            .topbar {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 5rem 1rem 1rem;
            }

            .top-bar h1 {
                font-size: 2rem;
            }

            .top-bar p {
                font-size: 0.95rem;
            }

            .profile-card {
                padding: 1.5rem;
                border-radius: 24px;
            }

            .profile-img-preview-wrapper {
                width: 110px;
                height: 110px;
                margin-bottom: 1.5rem;
            }

            .btn-update-main,
            .btn-password-alt {
                padding: 14px;
                font-size: 0.9rem;
            }

            .confirm-box {
                padding: 2rem 1.5rem;
                border-radius: 28px;
            }
        }
    </style>
</head>
<body>
<!-- TOPBAR MOBILE -->
<div class="topbar">
    <span class="topbar-logo">foodify.</span>

    <button class="hamburger" onclick="toggleSidebar()">
        <i class="bi bi-list" id="hamburgerIcon"></i>
    </button>
</div>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<div class="sidebar">
    <div class="sidebar-logo"><h2>foodify.</h2></div>
    <div class="sidebar-greet-box"><p>Manage your account</p></div>
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-book"></i> Recipes</a></li>
        <li><a href="../shop/items.php"><i class="bi bi-bag-heart"></i> Market</a></li>
        <li><a href="../recipe/cookbook.php"><i class="bi bi-journal-text"></i> My Cookbook</a></li>
        <li><a href="../order/my_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3" style="border:1px solid var(--accent);">
                <?php if ($profileSrc): ?>
                    <img src="<?= htmlspecialchars($profileSrc) ?>" style="width:42px;height:42px;border-radius:12px;object-fit:cover;">
                <?php else: ?>
                    <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px;height:42px;background:var(--primary-grad);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <div class="text-white fw-bold small text-truncate" style="max-width:130px;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size:0.65rem;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?= htmlspecialchars($nav_role ?? 'Customer') ?></div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2 border-opacity-25" style="font-size:0.85rem">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1>Account Settings</h1>
        <p>Manage your digital kitchen profile and security.</p>
    </div>

    <div class="row g-5">

        <!-- PROFILE FORM -->
        <div class="col-lg-7">
            <div class="profile-card">
                <div class="info-pill"><i class="bi bi-person-circle"></i> Profile Information</div>
                <form id="profileForm" method="POST" enctype="multipart/form-data">
                    <div class="profile-img-preview-wrapper">
                        <?php if ($profileSrc): ?>
                            <img src="<?= htmlspecialchars($profileSrc) ?>" class="profile-img-preview" id="imgPreview">
                        <?php else: ?>
                            <div class="profile-img-preview d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-person text-muted" style="font-size:3rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Update Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Display Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+60...">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Delivery Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Enter your full address for delivery..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    <button type="button" onclick="confirmUpdate()" class="btn-update-main">
                        Save Profile Changes <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <input type="hidden" name="update_profile" value="1">
                </form>
            </div>
        </div>

        <!-- PASSWORD FORM -->
        <div class="col-lg-5">
            <div class="profile-card">
                <div class="info-pill" style="background:#E8F0FF;color:#357BFF;">
                    <i class="bi bi-shield-lock"></i> Security & Privacy
                </div>
                <form method="POST" id="pwdForm">
                    <div class="mb-4">
                        <label class="form-label">Current Password</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="current_password" class="form-control pe-5" placeholder="" required>
                            <button type="button" onclick="togglePwd('current_password', this)" class="pwd-toggle">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <hr class="my-4 opacity-50">
                    <div class="mb-4">
                        <label class="form-label">New Password</label>
                        <div class="position-relative">
                            <input type="password" name="new_password" id="new_password" class="form-control pe-5" placeholder="Min. 8 characters" required>
                            <button type="button" onclick="togglePwd('new_password', this)" class="pwd-toggle">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" placeholder="" required>
                            <button type="button" onclick="togglePwd('confirm_password', this)" class="pwd-toggle">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-password-alt" onclick="confirmPasswordUpdate()">
                        Update Password
                    </button>
                    <input type="hidden" name="update_password" value="1">
                </form>
            </div>
        </div>

    </div>
</div>

<!-- MODALS -->
<div id="profileConfirmOverlay" class="modal-overlay">
    <div class="confirm-box">
        <div class="mb-3"><i class="bi bi-person-check-fill" style="font-size:4rem;color:#FF8E53;"></i></div>
        <h3 class="fw-bold mb-2">Save Profile Changes?</h3>
        <p class="text-muted">Your profile information will be updated instantly.</p>
        <div class="d-flex gap-3 mt-4">
            <button class="btn-modal-cancel" onclick="closeModal('profileConfirmOverlay')">Cancel</button>
            <button class="btn-modal-primary" onclick="submitProfileForm()">Yes, Save</button>
        </div>
    </div>
</div>

<div id="profileSuccessOverlay" class="modal-overlay <?= (isset($_GET['status']) && $_GET['status'] == 'profile_success') ? 'active' : '' ?>">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle orange"><i class="bi bi-check-lg" style="font-size:2.5rem;color:#FF6B6B;"></i></div></div>
        <h3 class="fw-bold mb-2">Profile Updated!</h3>
        <p class="text-muted">Your profile has been updated perfectly.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeProfileSuccess()">Ok</button></div>
    </div>
</div>

<div id="pwdConfirmOverlay" class="modal-overlay">
    <div class="confirm-box">
        <div class="mb-3"><i class="bi bi-shield-lock-fill" style="font-size:4rem;color:#FF8E53;"></i></div>
        <h3 class="fw-bold mb-2">Update Password?</h3>
        <p class="text-muted">You will need to use your new password the next time you log in.</p>
        <div class="d-flex gap-3 mt-4">
            <button class="btn-modal-cancel" onclick="closeModal('pwdConfirmOverlay')">Cancel</button>
            <button class="btn-modal-primary" onclick="submitPwdForm()">Yes, Update</button>
        </div>
    </div>
</div>

<div id="pwdSuccessOverlay" class="modal-overlay <?= ($status == 'password_success') ? 'active' : '' ?>">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle orange"><i class="bi bi-check-lg" style="font-size:2.5rem;color:#FF6B6B;"></i></div></div>
        <h3 class="fw-bold mb-2">Security Updated!</h3>
        <p class="text-muted">Your new password has been saved successfully.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('pwdSuccessOverlay')">Ok</button></div>
    </div>
</div>

<div id="errIncompleteOverlay" class="modal-overlay">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle red"><i class="bi bi-exclamation-lg" style="font-size:2.5rem;color:#FF4D4D;"></i></div></div>
        <h3 class="fw-bold mb-2">Incomplete</h3>
        <p class="text-muted">Please fill in all password fields before continuing.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('errIncompleteOverlay')">Got It</button></div>
    </div>
</div>

<div id="errShortOverlay" class="modal-overlay">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle red"><i class="bi bi-key-fill" style="font-size:2.5rem;color:#FF4D4D;"></i></div></div>
        <h3 class="fw-bold mb-2">Too Short</h3>
        <p class="text-muted">New password must be at least 8 characters long.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('errShortOverlay')">Got It</button></div>
    </div>
</div>

<div id="errMismatchOverlay" class="modal-overlay <?= ($status == 'password_mismatch') ? 'active' : '' ?>">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle red"><i class="bi bi-shield-x" style="font-size:2.5rem;color:#FF4D4D;"></i></div></div>
        <h3 class="fw-bold mb-2">Passwords Don't Match</h3>
        <p class="text-muted">Your new password and confirmation password are not the same.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('errMismatchOverlay')">Try Again</button></div>
    </div>
</div>

<div id="errShortServerOverlay" class="modal-overlay <?= ($status == 'password_short') ? 'active' : '' ?>">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle red"><i class="bi bi-key-fill" style="font-size:2.5rem;color:#FF4D4D;"></i></div></div>
        <h3 class="fw-bold mb-2">Too Short</h3>
        <p class="text-muted">Password must be at least 8 characters long.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('errShortServerOverlay')">Got It</button></div>
    </div>
</div>

<div id="errWrongPwdOverlay" class="modal-overlay <?= ($status == 'current_pwd_wrong') ? 'active' : '' ?>">
    <div class="confirm-box">
        <div class="mb-3"><div class="modal-icon-circle red"><i class="bi bi-lock-fill" style="font-size:2.5rem;color:#FF4D4D;"></i></div></div>
        <h3 class="fw-bold mb-2">Wrong Password</h3>
        <p class="text-muted">Your current password is incorrect. Please try again.</p>
        <div class="mt-4"><button class="btn-modal-primary" onclick="closeModal('errWrongPwdOverlay')">Try Again</button></div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('imgPreview').src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}

function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function confirmUpdate()     { openModal('profileConfirmOverlay'); }
function submitProfileForm() { document.getElementById('profileForm').submit(); }
function closeProfileSuccess() {
    closeModal('profileSuccessOverlay');
    const url = new URL(window.location);
    url.searchParams.delete('status');
    window.history.replaceState({}, document.title, url.pathname);
}

function confirmPasswordUpdate() {
    const current = document.getElementById('current_password').value.trim();
    const newPwd  = document.getElementById('new_password').value.trim();
    const confirm = document.getElementById('confirm_password').value.trim();
    if (!current || !newPwd || !confirm) { openModal('errIncompleteOverlay'); return; }
    if (newPwd.length < 8) { openModal('errShortOverlay'); return; }
    if (newPwd !== confirm) { openModal('errMismatchOverlay'); return; }
    openModal('pwdConfirmOverlay');
}

function submitPwdForm() {
    closeModal('pwdConfirmOverlay');
    document.getElementById('pwdForm').submit();
}

function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const icon = document.getElementById('hamburgerIcon');

    const isOpen = sidebar.classList.toggle('open');

    overlay.classList.toggle('active', isOpen);

    icon.className = isOpen
        ? 'bi bi-x-lg'
        : 'bi bi-list';
}

document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');

        if (sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });
});
</script>
</body>
</html>