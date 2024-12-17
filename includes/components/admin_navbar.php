<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Handle profile image path (for admin pages - different directory structure)
$profile_image = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) 
    ? '../../' . $_SESSION['profile_image'] 
    : '../../assets/default-avatar.jpg';

// Check if user is admin
$is_admin = isset($_SESSION['user_id']) ? false : false;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT is_admin FROM kinoverse_users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $is_admin = (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error checking admin status: " . $e->getMessage());
    }
}

// Redirect non-admins
if (!$is_admin) {
    header("Location: ../../pages/feed.php");
    exit();
}
?>

<nav class="navbar nav-reset">
    <div class="nav-container">
        <!-- Left section -->
        <div class="nav-left">
            <a href="../../pages/feed.php" class="nav-logo">Kinoverse</a>

            <div class="nav-links">
                <a href="../../pages/feed.php" class="nav-link">
                    Shots
                </a>
                <a href="dashboard.php" class="nav-link admin-link active">
                    <i class="fas fa-shield-alt"></i>
                    Admin
                </a>
            </div>
        </div>

        <!-- Search bar -->
        <div class="nav-search-container">
            <i class="fas fa-search nav-search-icon"></i>
            <input 
                type="text" 
                class="nav-search-input" 
                placeholder="Search users, posts..." 
                aria-label="Search"
            >
        </div>

        <!-- Right section -->
        <div class="nav-right">
            <div class="nav-icons">
                <a href="dashboard.php" class="nav-icon admin-icon" title="Admin Dashboard">
                    <i class="fas fa-shield-alt"></i>
                </a>
                <a href="../../pages/feed.php" class="nav-icon" title="Return to Site">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            
            <!-- Profile dropdown -->
            <div class="nav-profile-container" id="navProfileDropdown">
                <img 
                    src="<?php echo $profile_image; ?>" 
                    alt="Profile" 
                    class="nav-profile-img"
                    onerror="this.src='../../assets/default-avatar.jpg'"
                >
                <i class="fas fa-chevron-down nav-arrow"></i>
                
                <div class="nav-dropdown">
                    <div class="nav-dropdown-header">
                        <img 
                            src="<?php echo $profile_image; ?>" 
                            alt="Profile" 
                            class="nav-dropdown-profile-img"
                            onerror="this.src='../../assets/default-avatar.jpg'"
                        >
                        <div class="nav-dropdown-info">
                            <div class="nav-dropdown-name">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                                <span class="admin-badge">Admin</span>
                            </div>
                            <div class="nav-dropdown-email">
                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="nav-dropdown-items">
                        <a href="../../pages/profile.php?username=<?php echo urlencode($_SESSION['username']); ?>" class="nav-dropdown-item">
                            <i class="fas fa-user"></i>
                            View Profile
                        </a>
                        <a href="dashboard.php" class="nav-dropdown-item admin-item">
                            <i class="fas fa-shield-alt"></i>
                            Admin Dashboard
                        </a>
                        <a href="users.php" class="nav-dropdown-item admin-item">
                            <i class="fas fa-users-cog"></i>
                            Manage Users
                        </a>
                        <a href="posts.php" class="nav-dropdown-item admin-item">
                            <i class="fas fa-images"></i>
                            Manage Posts
                        </a>
                        <a href="../../pages/settings.php" class="nav-dropdown-item">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <div class="nav-dropdown-divider"></div>
                        <a href="../../includes/auth/logout.php" class="nav-dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            Log Out
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <button class="nav-menu-btn" id="navMenuBtn" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="nav-mobile-menu" id="navMobileMenu">
    <!-- Mobile Profile Section -->
    <div class="nav-mobile-profile-section">
        <img 
            src="<?php echo $profile_image; ?>" 
            alt="Profile" 
            class="nav-mobile-profile-img"
            onerror="this.src='../../assets/default-avatar.jpg'"
        >
        <div class="nav-mobile-profile-info">
            <div class="nav-mobile-profile-name">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
                <span class="admin-badge">Admin</span>
            </div>
            <div class="nav-mobile-profile-email">
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Items -->
    <div class="nav-mobile-items">
        <!-- Admin Section -->
        <div class="nav-mobile-section-title">Admin</div>
        <a href="dashboard.php" class="nav-mobile-item admin-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-shield-alt"></i>
            Dashboard
        </a>
        <a href="users.php" class="nav-mobile-item admin-item <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            Manage Users
        </a>
        <a href="posts.php" class="nav-mobile-item admin-item <?php echo ($current_page === 'posts.php') ? 'active' : ''; ?>">
            <i class="fas fa-images"></i>
            Manage Posts
        </a>

        <div class="nav-mobile-divider"></div>

        <!-- Main Site Links -->
        <div class="nav-mobile-section-title">Main Site</div>
        <a href="../../pages/feed.php" class="nav-mobile-item">
            <i class="fas fa-camera"></i>
            Shots
        </a>
        <a href="../../pages/curations.php" class="nav-mobile-item">
            <i class="fas fa-layer-group"></i>
            Curations
        </a>

        <div class="nav-mobile-divider"></div>
        
        <!-- Profile Links -->
        <a href="../../pages/profile.php?username=<?php echo urlencode($_SESSION['username']); ?>" class="nav-mobile-item">
            <i class="fas fa-user"></i>
            View Profile
        </a>
        <a href="../../pages/settings.php" class="nav-mobile-item">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="../../includes/auth/logout.php" class="nav-mobile-item" style="color: #EF4444;">
            <i class="fas fa-sign-out-alt" style="color: #EF4444;"></i>
            Log Out
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Handle screen size changes
    const mediaQuery = window.matchMedia('(min-width: 769px)');
    
    function handleScreenChange(e) {
        const mobileMenu = document.getElementById('navMobileMenu');
        const menuBtn = document.getElementById('navMenuBtn');
        
        if (e.matches && mobileMenu && menuBtn) {
            // Reset mobile menu when returning to desktop
            mobileMenu.classList.remove('active');
            const icon = menuBtn.querySelector('i');
            if (icon) {
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        }
    }
    
    // Add the listener for screen size changes
    mediaQuery.addListener(handleScreenChange);

    // Profile dropdown functionality
    const profileDropdown = document.getElementById('navProfileDropdown');
    if (profileDropdown) {
        // Toggle dropdown on click
        profileDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
    }

    // Mobile menu functionality
    const menuBtn = document.getElementById('navMenuBtn');
    const mobileMenu = document.getElementById('navMobileMenu');
    
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            // Update icon
            const icon = menuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                mobileMenu.classList.remove('active');
                const icon = menuBtn.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        });
    }
});</script>