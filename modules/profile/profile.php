<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$status = ""; 

// 1. Retrieve all User Information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = $user['username'] ?? 'Guest';

// 2. Logic: Update Profile (Fixed Address Saving)
if (isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $address  = $_POST['address']; 
    $profile_img = $user['profile_image']; 

    // Handle Profile Picture Upload
    // Handle Profile Picture Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "../../assets/images/profiles/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $userId . "_" . time() . "." . $file_ext;

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
            
            // ─── LOGIK PADAM GAMBAR LAMA (TAMBAHAN) ───
            // Cek kalau user ada gambar lama dalam DB dan fail tu wujud kat server
            if (!empty($user['profile_image'])) {
                $old_image_path = $target_dir . $user['profile_image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path); // Padam fail lama
                }
            }
            // ─────────────────────────────────────────

            $profile_img = $new_filename;
        }
    }

    // UPDATE DATABASE (Strictly include address)
    $update = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
    $update->bind_param("sssssi", $fullname, $email, $phone, $address, $profile_img, $userId);
    
    if ($update->execute()) {
        $_SESSION['username'] = $fullname;
        $status = "profile_success";
        header("Refresh:0; url=profile.php?status=profile_success");
        exit();
    }
}

// 3. Logic: Update Password with Current Password Check
if (isset($_POST['update_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd     = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    // Verification against DB password
    if (password_verify($current_pwd, $user['password'])) {
        if ($new_pwd === $confirm_pwd) {
            if (strlen($new_pwd) >= 6) {
                $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
                $upd_pwd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $upd_pwd->bind_param("si", $hashed_pwd, $userId);
                if ($upd_pwd->execute()) {
                    $status = "password_success";
                }
            } else {
                $status = "password_short";
            }
        } else {
            $status = "password_mismatch";
        }
    } else {
        // Status for incorrect old password
        $status = "current_pwd_wrong";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --green: #2E7D32; --green-light: #E8F5E9;
            --orange: #FF8F00; --dark: #1A1A1A;
            --muted: #777; --border: #EEEEEE;
            --sidebar-w: 270px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: #F7F9F7; color: var(--dark); }

        /* ── SIDEBAR (LOCKED DESIGN) ── */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
            background: white; box-shadow: 2px 0 16px rgba(0,0,0,0.06);
            padding: 1.8rem 1rem; overflow-y: auto; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .sidebar-logo {
            display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px;
            margin-bottom: 2rem; padding: 0 8px;
        }
        .sidebar-logo img { width: 75px; height: 75px; object-fit: contain; border-radius: 14px; }
        .sidebar-logo .logo-text h2 { font-weight: 900; font-size: 1.6rem; color: var(--green); line-height: 1; margin-bottom: 4px; }
        .sidebar-logo .logo-text span { font-size: 0.7rem; color: var(--muted); font-weight: 600; }

        .sidebar-nav { list-style: none; padding: 0; flex: 1; }
        .sidebar-nav li { margin-bottom: 4px; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px;
            color: var(--dark); text-decoration: none; border-radius: 12px;
            font-weight: 600; font-size: 0.88rem; transition: all 0.18s;
        }
        .sidebar-nav a i { font-size: 1.15rem; width: 22px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--orange); color: white; }

        .sidebar-bottom { border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem; }
        .user-row {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            background: var(--green-light); border-radius: 12px; margin-bottom: 10px;
            cursor: pointer; transition: all 0.2s ease-out;
        }
        .user-row:hover { background-color: #DDEEE6 !important; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .user-row i { font-size: 1.6rem; color: var(--green); }
        .user-avatar-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; transition: 0.2s; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* ── MAIN CONTENT ── */
        .main-content { margin-left: var(--sidebar-w); padding: 2rem; min-height: 100vh; }
        .profile-card { background: white; border-radius: 24px; padding: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 2rem; border: 1.5px solid var(--border); }
        .profile-display-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--green-light); margin-bottom: 1rem; }
        .form-label { font-weight: 700; color: var(--muted); font-size: 0.85rem; }
        .form-control { border-radius: 12px; padding: 12px; border: 1.5px solid var(--border); }
        .btn-primary-foodify { background: var(--orange); color: white; border: none; border-radius: 50px; padding: 12px 30px; font-weight: 800; transition: 0.2s; cursor: pointer; }
        .btn-primary-foodify:hover { background: #e07f00; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../../assets/images/logo.png" alt="Foodify">
        <div class="logo-text">
            <h2>Foodify</h2>
            <span>Recipes + Groceries</span>
        </div>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="../../index.php"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="../recipe/recipes.php"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="../shop/index.php"><i class="bi bi-bag-fill"></i> Shop</a></li>
        <li><a href="../recipe/cookbook.php"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
        <li><a href="../order/index.php"><i class="bi bi-truck"></i> My Orders</a></li>
    </ul>

    <div class="sidebar-bottom">
        <a href="profile.php" class="text-decoration-none" style="color: inherit;">
            <div class="user-row" style="background-color: #DDEEE6; border: 1.5px solid var(--green);">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="../../assets/images/profiles/<?= htmlspecialchars($user['profile_image']) ?>" class="user-avatar-img">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
                <div>
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role'] ?? 'Customer') ?></div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn-side btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="mb-4 text-center text-lg-start">
        <h1 style="font-family:'Playfair Display', serif; font-weight:800; font-size: 2.2rem;">My Profile</h1>
        <p class="text-muted">Manage your account settings and preferences</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="profile-card text-center text-lg-start">
                <h5 class="fw-bold mb-4"><i class="bi bi-person-gear me-2 text-success"></i>Account Information</h5>
                
                <div class="mb-4 text-center">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="../../assets/images/profiles/<?= htmlspecialchars($user['profile_image']) ?>" class="profile-display-img">
                    <?php else: ?>
                        <div class="mb-3"><i class="bi bi-person-circle" style="font-size: 80px; color: var(--green);"></i></div>
                    <?php endif; ?>
                </div>

                <!-- Added ID to form for SweetAlert targeting -->
                <form id="profileForm" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Update Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="bi bi-phone me-1"></i><label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Default Delivery Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    <!-- Changed to type="button" to trigger SweetAlert -->
                    <button type="button" onclick="confirmUpdate()" class="btn-primary-foodify w-100">Update Profile</button>
                    <!-- Hidden submit button to be triggered by JS -->
                    <input type="hidden" name="update_profile" value="1">
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="profile-card">
                <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-danger"></i>Change Password</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_password" class="btn-primary-foodify w-100" style="background: var(--dark);">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Confirmation before updating profile
    function confirmUpdate() {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to save these changes to your profile?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FF8F00',
            cancelButtonColor: '#777',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('profileForm').submit();
            }
        });
    }

    // Logic for Status Alerts
    <?php if($status == "profile_success"): ?>
        Swal.fire({ icon: 'success', title: 'Success!', text: 'Your profile has been updated.', confirmButtonColor: '#FF8F00' });
    <?php elseif($status == "password_success"): ?>
        Swal.fire({ icon: 'success', title: 'Success!', text: 'Password has been changed.', confirmButtonColor: '#FF8F00' });
    <?php elseif($status == "current_pwd_wrong"): ?>
        Swal.fire({ icon: 'error', title: 'Oops!', text: 'Incorrect current password.', confirmButtonColor: '#1A1A1A' });
    <?php elseif($status == "password_mismatch"): ?>
        Swal.fire({ icon: 'warning', title: 'Mismatch!', text: 'New passwords do not match.', confirmButtonColor: '#FF8F00' });
    <?php elseif($status == "password_short"): ?>
        Swal.fire({ icon: 'warning', title: 'Too Short!', text: 'Password must be at least 6 characters.', confirmButtonColor: '#FF8F00' });
    <?php endif; ?>
</script>

</body>
</html>