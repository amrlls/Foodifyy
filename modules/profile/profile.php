<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2E7D32;
            --green-light: #E8F5E9;
            --orange: #FF8F00;
            --dark: #1A1A1A;
            --muted: #777;
            --border: #EEEEEE;
            --sidebar-w: 270px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: #F7F9F7; color: var(--dark); }

        /* Sidebar Styles */
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
        .user-name { font-weight: 700; font-size: 0.88rem; }
        .user-role { font-size: 0.7rem; color: var(--muted); }

        .btn-side { display: block; padding: 9px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; text-align: center; text-decoration: none; margin-bottom: 6px; transition: 0.2s; }
        .btn-side-login { background: var(--orange); color: white; }
        .btn-side-logout { background: #FEE2E2; color: #DC2626; }
        .btn-side-logout:hover { background: #DC2626; color: white; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-w); padding: 2rem; min-height: 100vh; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 800; }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, var(--green) 0%, #3E9B4E 100%);
            border-radius: 24px; padding: 2rem; color: white; margin-bottom: 2rem;
            display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
        }
        .profile-avatar {
            width: 90px; height: 90px; background: rgba(255,255,255,0.2);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem; backdrop-filter: blur(4px);
        }
        .profile-title h1 { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 800; margin-bottom: 0.25rem; }
        .profile-title p { opacity: 0.9; font-size: 0.85rem; margin: 0; }

        /* Profile Sections */
        .profile-card {
            background: white; border-radius: 24px; padding: 1.5rem; margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); border: 1px solid var(--border);
        }
        .profile-card h3 {
            font-weight: 800; font-size: 1.2rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 8px;
            padding-bottom: 0.75rem; border-bottom: 2px solid var(--orange); display: inline-block;
        }
        .form-label { font-weight: 700; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.4rem; }
        .form-control, .form-select {
            border-radius: 14px; padding: 12px 16px; border: 1.5px solid var(--border);
            font-size: 0.9rem; transition: 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--orange); box-shadow: 0 0 0 3px rgba(255,143,0,0.1); outline: none;
        }
        .btn-save {
            background: var(--orange); border: none; border-radius: 50px; padding: 12px 28px;
            font-weight: 800; color: white; transition: 0.2s;
        }
        .btn-save:hover { background: #e07f00; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,143,0,0.3); }
        
        .alert-custom {
            border-radius: 16px; font-weight: 600; margin-bottom: 1.5rem;
            display: none;
        }
        .alert-custom.show { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .profile-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Foodify">
        <div class="logo-text">
            <h2>Foodify</h2>
            <span>Recipes + Groceries</span>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li><a href="index.html"><i class="bi bi-house-fill"></i> Home</a></li>
        <li><a href="recipes.html"><i class="bi bi-journal-bookmark-fill"></i> Recipes</a></li>
        <li><a href="shop.html"><i class="bi bi-bag-fill"></i> Shop</a></li>
        <li><a href="myCookbooks.html"><i class="bi bi-bookmark-heart-fill"></i> My Cookbooks</a></li>
        <li><a href="myOrders.html"><i class="bi bi-truck"></i> My Orders</a></li>
    </ul>
    <div class="sidebar-bottom">
        <a href="profile.html" class="text-decoration-none" style="color: inherit;">
            <div class="user-row" style="background: #DDEEE6;">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="user-name">Name</div>
                    <div class="user-role">Customer</div>
                </div>
            </div>
        </a>
        <a href="loginPage.html" class="btn-side btn-side-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="profile-title">
            <h1>(Name)</h1>
            <p><i class="bi bi-envelope"></i> name@foodify.com</p>
        </div>
    </div>

    <!-- Alert Message (Hidden by default, shows after form submit) -->
    <div class="alert alert-custom" id="alertMessage">
        <i class="bi bi-check-circle-fill me-2"></i>
        <span id="alertText">Profile updated successfully!</span>
    </div>

    <div class="row g-4">
        <!-- Personal Information Section -->
        <div class="col-lg-7">
            <div class="profile-card">
                <h3><i class="bi bi-person-badge" style="color: var(--orange);"></i> Personal Information</h3>
                <form id="profileForm">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" id="fullname" class="form-control" value="Name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" value="name@foodify.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" value="012-3456789" placeholder="e.g., 012-3456789">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Delivery Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="Your full address for delivery">No. 12, Jalan Universiti 1, Taman Universiti, 81310 Johor Bahru, Johor</textarea>
                    </div>
                    <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password Section -->
        <div class="col-lg-5">
            <div class="profile-card">
                <h3><i class="bi bi-shield-lock" style="color: var(--orange);"></i> Change Password</h3>
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" id="currentPassword" class="form-control" placeholder="Enter current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="newPassword" class="form-control" placeholder="Minimum 6 characters">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Re-enter new password">
                    </div>
                    <button type="submit" class="btn-save"><i class="bi bi-key"></i> Update Password</button>
                </form>
            </div>

            <!-- Account Stats -->
            <div class="profile-card">
                <h3><i class="bi bi-graph-up" style="color: var(--orange);"></i> Account Stats</h3>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Account Status</span>
                    <span class="badge" style="background: var(--green-light); color: var(--green);">Active</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Role</span>
                    <span class="fw-bold">Customer</span>
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total Orders</span>
                    <span class="fw-bold">8</span>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>