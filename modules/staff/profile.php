<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user['role'] !== 'staff') {
    header('Location: ../../index.php');
    exit();
}

$staff_username = $user['username'];
$profile_img = $user['profile_image'];
$greeting = "Staff Profile - View and update your information.";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_address = trim($_POST['address'] ?? '');
    $profile_image = $user['profile_image'];

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $new_image_url = uploadToCloudinary($_FILES['profile_pic']['tmp_name'], 'foodify/profiles');
        $conn->ping();

        if ($new_image_url) {
            $profile_image = $new_image_url;
        } else {
            $target_dir = "../../assets/images/profiles/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
            $new_filename = "staff_" . $userId . "_" . time() . "." . $file_ext;
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
                if (!empty($user['profile_image']) && !str_starts_with($user['profile_image'], 'http')) {
                    $old_path = $target_dir . $user['profile_image'];
                    if (file_exists($old_path)) unlink($old_path);
                }
                $profile_image = $new_filename;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE users SET username = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $new_username, $new_phone, $new_address, $profile_image, $userId);
    if ($stmt->execute()) {
        $success = 'Profile updated successfully!';
        $_SESSION['username'] = $new_username;
        $user['username'] = $new_username;
        $user['profile_image'] = $profile_image;
        $profile_img = $profile_image;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $userId);
                if ($stmt->execute()) {
                    $success = 'Password updated successfully!';
                }
            } else {
                $error = 'New password must be at least 8 characters.';
            }
        } else {
            $error = 'New password and confirmation do not match.';
        }
    } else {
        $error = 'Current password is incorrect.';
    }
}

$profileSrc = getImageSrc($profile_img, '../../assets/images/profiles/');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - Foodify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8F9FA; }
        .profile-card { background: white; border-radius: 28px; padding: 2rem; max-width: 700px; margin: 0 auto; }
        .profile-img-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-update { background: #1A1C1E; color: white; border: none; padding: 12px 28px; border-radius: 16px; }
        .btn-update:hover { background: #FF8E53; }
        .form-control { border-radius: 12px; padding: 12px; }
        .form-control:focus { border-color: #FF8E53; box-shadow: 0 0 0 3px rgba(255,142,83,0.1); }
    </style>
</head>
<body>
    <?php include 'includes/staff_nav.php'; ?>
    
    <div class="main-content">
        <div class="profile-card">
            <h2 class="fw-bold mb-4">Staff Profile</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-4">
                    <?php if ($profileSrc && $profileSrc !== 'placeholder'): ?>
                        <img src="<?= htmlspecialchars($profileSrc) ?>" class="profile-img-preview" id="imgPreview">
                    <?php else: ?>
                        <div style="width:120px; height:120px; background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="bi bi-person-fill" style="font-size: 60px; color: white;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <small class="text-muted">Upload JPG, PNG or GIF (Max 5MB)</small>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-dark">Staff Member</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn-update w-100 mb-4">Save Profile Changes</button>
            </form>
            
            <hr class="my-4">
            <h5 class="fw-bold mb-3">Change Password</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control">
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <button type="submit" name="update_password" class="btn-update w-100">Update Password</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var imgPreview = document.getElementById('imgPreview');
                    if (imgPreview) {
                        imgPreview.src = e.target.result;
                    } else {
                        var container = document.querySelector('.text-center.mb-4');
                        var newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.className = 'profile-img-preview';
                        newImg.id = 'imgPreview';
                        newImg.style.width = '120px';
                        newImg.style.height = '120px';
                        newImg.style.borderRadius = '50%';
                        newImg.style.objectFit = 'cover';
                        newImg.style.margin = '0 auto';
                        newImg.style.border = '4px solid white';
                        newImg.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                        
                        var existing = container.querySelector('.profile-img-preview, div[style*="width:120px"]');
                        if (existing) existing.remove();
                        container.insertBefore(newImg, container.firstChild);
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>