<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>foodify.</h2>
    </div>
    <div class="sidebar-greet-box">
        <p><?= $greeting ?? 'Staff Panel' ?></p>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a></li>
        <li><a href="inventory.php" class="<?= $current_page == 'inventory.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Inventory
        </a></li>
        <li><a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Orders
        </a></li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="profile.php" class="text-decoration-none d-block">
            <div class="user-card d-flex align-items-center gap-3 mb-3">
                <?php 
                    $profileSrc = getImageSrc($profile_img ?? '', '../../assets/images/profiles/');
                    if ($profileSrc && $profileSrc !== 'placeholder'): ?>
                        <img src="<?= htmlspecialchars($profileSrc) ?>" style="width:42px; height:42px; border-radius:12px; object-fit:cover;">
                <?php else: ?>
                    <div class="text-white rounded-3 p-2 d-flex justify-content-center align-items-center" style="width:42px; height:42px; background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="text-white fw-bold small"><?= htmlspecialchars($staff_username ?? 'Staff') ?></div>
                    <div style="font-size: 0.65rem; color: #FF8E53;">STAFF</div>
                </div>
            </div>
        </a>
        <a href="../auth/logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2">Logout</a>
    </div>
</div>

<style>
    .sidebar {
        position: fixed; left: 0; top: 0; width: 280px; height: 100vh;
        background: #1A1C1E; color: white;
        padding: 2.5rem 1.5rem; z-index: 1000;
        display: flex; flex-direction: column;
    }
    .sidebar-logo h2 { 
        font-family: 'Playfair Display', serif; font-weight: 900; 
        background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
        background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .sidebar-greet-box { margin-bottom: 3rem; }
    .sidebar-greet-box p { color: #949494; font-size: 0.8rem; }
    .sidebar-nav { list-style: none; padding: 0; flex-grow: 1; }
    .sidebar-nav li { margin-bottom: 0.5rem; }
    .sidebar-nav a {
        display: flex; align-items: center; gap: 15px; padding: 12px 18px;
        color: #949494; text-decoration: none; border-radius: 16px;
        transition: all 0.3s ease;
    }
    .sidebar-nav a:hover { color: white; background: rgba(255,255,255,0.05); }
    .sidebar-nav a.active { 
        background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
        color: white;
    }
    .sidebar-footer { padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); }
    .user-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        padding: 12px;
        border-radius: 20px;
        transition: all 0.2s ease;
    }
    .user-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }
    .main-content { margin-left: 280px; padding: 2rem 3rem; }
</style>