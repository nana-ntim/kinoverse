<?php
/**
 * Settings Page
 * Handles user account settings and profile management
 */

require_once "../config/config_session.inc.php";
require_once "../config/database.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Kinoverse</title>
    
    <link rel="stylesheet" href="../styles/components/navbar.css">
    <link rel="stylesheet" href="../styles/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/components/navbar.php'; ?>

    <main class="settings-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message-container success-container">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message-container error-container">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Account Settings -->
        <div class="account-settings">
            <h1 class="settings-title">Account Settings</h1>

            <div class="profile-info">
                <div class="profile-image">
                    <img 
                        src="<?php echo !empty($_SESSION['profile_image']) ? 
                            '../' . htmlspecialchars($_SESSION['profile_image']) : 
                            '../assets/default-avatar.jpg'; ?>" 
                        alt="Profile Picture"
                        onerror="this.src='../assets/default-avatar.jpg'"
                    >
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
                    <?php if (!empty($_SESSION['bio'])): ?>
                        <div class="user-bio"><?php echo nl2br(htmlspecialchars($_SESSION['bio'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="action-buttons">
                <button class="settings-btn edit-profile" onclick="openEditModal()">
                    Edit Profile
                </button>
                <button class="settings-btn change-password" onclick="openPasswordModal()">
                    Change Password
                </button>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div class="settings-modal" id="editProfileModal">
            <div class="modal-content">
                <h2 class="modal-title">Edit Profile</h2>
                <form action="../includes/actions/update_profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Profile Image Upload -->
                    <div class="form-group">
                        <label for="profile_image">Profile Picture</label>
                        <div class="image-upload-preview">
                            <img id="profilePreview" 
                                 src="<?php echo !empty($_SESSION['profile_image']) ? '../' . $_SESSION['profile_image'] : '../assets/bg.jpg'; ?>" 
                                 alt="Profile Preview"
                                 class="current-image">
                            <div class="file-input-container">
                                <label class="file-input-button" for="profile_image">
                                    Choose Image
                                </label>
                                <input type="file" 
                                       id="profile_image" 
                                       name="profile_image" 
                                       accept="image/*"
                                       onchange="previewImage(this, 'profilePreview')">
                            </div>
                        </div>
                        <small class="input-help">Maximum size: 10MB. Recommended: Square image</small>
                    </div>

                    <!-- Banner Image Upload -->
                    <div class="form-group">
                        <label for="banner_image">Profile Banner</label>
                        <div class="image-upload-preview banner-preview">
                            <img id="bannerPreview" 
                                 src="<?php echo !empty($_SESSION['bioImage']) ? '../' . $_SESSION['bioImage'] : '../assets/bg.jpg'; ?>" 
                                 alt="Banner Preview"
                                 class="current-image">
                            <div class="file-input-container">
                                <label class="file-input-button" for="banner_image">
                                    Choose Banner
                                </label>
                                <input type="file" 
                                       id="banner_image" 
                                       name="banner_image" 
                                       accept="image/*"
                                       onchange="previewImage(this, 'bannerPreview')">
                            </div>
                        </div>
                        <small class="input-help">Maximum size: 10MB. Recommended: 1920×300 pixels</small>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($_SESSION['username']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($_SESSION['email']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            placeholder="Tell us about yourself"
                        ><?php echo htmlspecialchars($_SESSION['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-btn cancel-btn" onclick="closeModal('editProfileModal')">
                            Cancel
                        </button>
                        <button type="submit" class="modal-btn save-btn">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Modal -->
        <div class="settings-modal" id="passwordModal">
            <div class="modal-content">
                <h2 class="modal-title">Change Password</h2>
                <form action="../includes/actions/update_password.php" method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                        >
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-btn cancel-btn" onclick="closeModal('passwordModal')">
                            Cancel
                        </button>
                        <button type="submit" class="modal-btn save-btn">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Account Section -->
        <div class="delete-section">
            <h2 class="delete-title">Delete Account</h2>
            <p class="delete-description">
                Once you delete your account, there is no going back. Please be certain.
            </p>
            <button class="delete-btn" onclick="confirmDelete()">
                Delete Account
            </button>
        </div>

        <a href="../includes/auth/logout.php" class="logout-link">Log out</a>
    </main>

    <script>
    // File input handling with preview
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal functions
    function openEditModal() {
        document.getElementById('editProfileModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function openPasswordModal() {
        document.getElementById('passwordModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        document.body.style.overflow = '';
    }

    // Close modal when clicking outside
    document.querySelectorAll('.settings-modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal when pressing escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.settings-modal').forEach(modal => {
                if (modal.classList.contains('active')) {
                    closeModal(modal.id);
                }
            });
        }
    });

    // Delete account confirmation
    function confirmDelete() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            window.location.href = '../includes/actions/delete_account.php';
        }
    }

    // Auto-hide messages after 5 seconds
    const messages = document.querySelectorAll('.message-container');
    if (messages.length > 0) {
        setTimeout(() => {
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 5000);
    }
    </script>
</body>
</html>