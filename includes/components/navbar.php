<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Handle profile image path (for main site pages)
$profile_image = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) 
    ? $_SESSION['profile_image'] 
    : 'assets/default-avatar.jpg';

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
?>

<nav class="navbar nav-reset">
    <div class="nav-container">
        <!-- Left section -->
        <div class="nav-left">
            <a href="feed.php" class="nav-logo">Kinoverse</a>

            <div class="nav-links">
                <a href="feed.php" class="nav-link <?php echo ($current_page === 'feed.php') ? 'active' : ''; ?>">
                    Shots
                </a>
                <a href="curations.php" class="nav-link <?php echo ($current_page === 'curations.php') ? 'active' : ''; ?>">
                    Curations
                </a>
                <?php if ($is_admin): ?>
                <a href="admin/dashboard.php" class="nav-link admin-link">
                    <i class="fas fa-shield-alt"></i>
                    Admin
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search bar -->
        <div class="nav-search-container">
            <i class="fas fa-search nav-search-icon"></i>
            <input 
                type="text" 
                class="nav-search-input" 
                placeholder="Looking for something?..." 
                aria-label="Search"
            >
        </div>

        <!-- Right section -->
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Action icons -->
                <div class="nav-icons">
                    <?php if ($is_admin): ?>
                    <a href="admin/dashboard.php" class="nav-icon admin-icon" title="Admin Dashboard">
                        <i class="fas fa-shield-alt"></i>
                    </a>
                    <?php endif; ?>
                    <i class="fas fa-bolt nav-icon"></i>
                    <i class="far fa-bookmark nav-icon"></i>
                </div>
                
                <!-- Profile dropdown -->
                <div class="nav-profile-container" id="navProfileDropdown">
                    <img 
                        src="<?php echo $profile_image; ?>" 
                        alt="Profile" 
                        class="nav-profile-img"
                        onerror="this.src='../assets/default-avatar.jpg'"
                    >
                    <i class="fas fa-chevron-down nav-arrow"></i>
                    
                    <div class="nav-dropdown">
                        <div class="nav-dropdown-header">
                            <img 
                                src="<?php echo $profile_image; ?>" 
                                alt="Profile" 
                                class="nav-dropdown-profile-img"
                                onerror="this.src='../assets/default-avatar.jpg'"
                            >
                            <div class="nav-dropdown-info">
                                <div class="nav-dropdown-name">
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                    <?php if ($is_admin): ?>
                                    <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                </div>
                                <div class="nav-dropdown-email">
                                    <?php echo htmlspecialchars($_SESSION['email']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="nav-dropdown-items">
                            <a href="profile.php?username=<?php echo urlencode($_SESSION['username']); ?>" class="nav-dropdown-item">
                                <i class="fas fa-user"></i>
                                View Profile
                            </a>
                            <?php if ($is_admin): ?>
                            <a href="admin/dashboard.php" class="nav-dropdown-item admin-item">
                                <i class="fas fa-shield-alt"></i>
                                Admin Dashboard
                            </a>
                            <a href="admin/users.php" class="nav-dropdown-item admin-item">
                                <i class="fas fa-users-cog"></i>
                                Manage Users
                            </a>
                            <a href="admin/posts.php" class="nav-dropdown-item admin-item">
                                <i class="fas fa-images"></i>
                                Manage Posts
                            </a>
                            <?php endif; ?>
                            <a href="settings.php" class="nav-dropdown-item">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <div class="nav-dropdown-divider"></div>
                            <a href="../includes/auth/logout.php" class="nav-dropdown-item logout">
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
            <?php else: ?>
                <a href="login.php" class="nav-link">Log In</a>
                <a href="signup.php" class="nav-link">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="nav-mobile-menu" id="navMobileMenu">
    <!-- Mobile Profile Section -->
    <div class="nav-mobile-profile-section">
        <img 
            src="<?php echo $profile_image; ?>" 
            alt="Profile" 
            class="nav-mobile-profile-img"
            onerror="this.src='../assets/default-avatar.jpg'"
        >
        <div class="nav-mobile-profile-info">
            <div class="nav-mobile-profile-name">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($is_admin): ?>
                <span class="admin-badge">Admin</span>
                <?php endif; ?>
            </div>
            <div class="nav-mobile-profile-email">
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Items -->
    <div class="nav-mobile-items">
        <a href="feed.php" class="nav-mobile-item <?php echo ($current_page === 'feed.php') ? 'active' : ''; ?>">
            <i class="fas fa-camera"></i>
            Shots
        </a>
        <a href="curations.php" class="nav-mobile-item <?php echo ($current_page === 'curations.php') ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i>
            Curations
        </a>
        
        <?php if ($is_admin): ?>
        <div class="nav-mobile-divider"></div>
        <div class="nav-mobile-section-title">Admin</div>
        <a href="admin/dashboard.php" class="nav-mobile-item admin-item">
            <i class="fas fa-shield-alt"></i>
            Dashboard
        </a>
        <a href="admin/users.php" class="nav-mobile-item admin-item">
            <i class="fas fa-users-cog"></i>
            Manage Users
        </a>
        <a href="admin/posts.php" class="nav-mobile-item admin-item">
            <i class="fas fa-images"></i>
            Manage Posts
        </a>
        <?php endif; ?>

        <div class="nav-mobile-divider"></div>
        
        <a href="profile.php?username=<?php echo urlencode($_SESSION['username']); ?>" class="nav-mobile-item">
            <i class="fas fa-user"></i>
            View Profile
        </a>
        <a href="settings.php" class="nav-mobile-item">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="../includes/auth/logout.php" class="nav-mobile-item" style="color: #EF4444;">
            <i class="fas fa-sign-out-alt" style="color: #EF4444;"></i>
            Log Out
        </a>
    </div>
</div>

<!-- Initialize JavaScript functionality -->
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
});
</script>
<?php endif; ?>