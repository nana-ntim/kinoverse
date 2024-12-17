<?php
/**
 * User Profile Page
 * Displays user profile, stats, and posts
 */

require_once "../config/config_session.inc.php";
require_once "../config/database.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get username from URL parameter
$username = $_GET['username'] ?? ($_SESSION['username'] ?? null);

if (!$username) {
    header("Location: ../public/login.php");
    exit();
}

try {
    // Fetch user data
    $stmt = $pdo->prepare("
        SELECT kinoverse_users.*, 
               (SELECT COUNT(*) FROM kinoverse_posts WHERE user_id = kinoverse_users.user_id) as post_count,
               (SELECT COUNT(*) FROM kinoverse_follows WHERE following_id = kinoverse_users.user_id) as follower_count,
               (SELECT COUNT(*) FROM kinoverse_follows WHERE follower_id = kinoverse_users.user_id) as following_count,
               EXISTS(
                   SELECT 1 FROM kinoverse_follows 
                   WHERE follower_id = ? AND following_id = kinoverse_users.user_id
               ) as is_following
        FROM kinoverse_users 
        WHERE username = ?
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 0, $username]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: 404.php");
        exit();
    }

    // Fetch user's posts with engagement stats
    $stmt = $pdo->prepare("
        SELECT kinoverse_posts.*, 
               (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = kinoverse_posts.post_id) as like_count,
               (SELECT COUNT(*) FROM kinoverse_comments WHERE post_id = kinoverse_posts.post_id) as comment_count,
               EXISTS(
                   SELECT 1 FROM kinoverse_likes 
                   WHERE post_id = kinoverse_posts.post_id AND user_id = ?
               ) as is_liked
        FROM kinoverse_posts 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $user['user_id']]);
    $posts = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Profile Error: " . $e->getMessage());
    header("Location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> | Kinoverse</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="../styles/components/navbar.css">
    <link rel="stylesheet" href="../styles/components/create_post_modal.css">
    <link rel="stylesheet" href="../styles/components/post_view.css">
    <link rel="stylesheet" href="../styles/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/components/navbar.php'; ?>

    <main class="profile-container">
        <!-- Banner Image -->
        <div class="profile-banner-wrapper">
            <div class="profile-banner">
                <?php if (!empty($user['bioImage'])): ?>
                    <img src="../<?php echo htmlspecialchars($user['bioImage']); ?>" 
                         alt="Profile Banner"
                         onerror="this.src='../assets/bg.jpg'">
                <?php else: ?>
                    <img src="../assets/bg.jpg" alt="Default Banner">
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Info Section -->
        <div class="profile-info">
            <!-- Profile Image -->
            <div class="profile-image">
                <img src="../<?php echo !empty($user['profile_image_url']) ? 
                    htmlspecialchars($user['profile_image_url']) : 
                    'assets/default-avatar.jpg'; ?>" 
                    alt="Profile Picture"
                    onerror="this.src='../assets/default-avatar.jpg'">
            </div>

            <!-- Profile Name & Bio -->
            <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
            
            <?php if (!empty($user['bio'])): ?>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            <?php endif; ?>

            <!-- Profile Stats -->
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($user['post_count']); ?></span>
                    <span class="stat-label">posts</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($user['follower_count']); ?></span>
                    <span class="stat-label">followers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($user['following_count']); ?></span>
                    <span class="stat-label">following</span>
                </div>
            </div>
        </div>

        <!-- Posts Grid -->
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div class="posts-empty">No posts yet</div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-item" data-post-id="<?php echo $post['post_id']; ?>">
                        <div class="post-link" data-post-trigger>
                            <img src="../<?php echo htmlspecialchars($post['image_url']); ?>" 
                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                loading="lazy"
                                onerror="this.src='../assets/default-post.jpg'">
                            <div class="post-overlay">
                                <div class="post-stats">
                                    <div class="stat">
                                        <i class="fas fa-heart"></i>
                                        <span><?php echo number_format($post['like_count']); ?></span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-comment"></i>
                                        <span><?php echo number_format($post['comment_count']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/components/create_post_modal.php'; ?>

    <!-- Post View Container -->
    <div class="post-view-overlay" id="postViewOverlay"></div>
    <div class="post-view-container" id="postView">
    </div>
    
    <!-- Scripts -->
    <script src="../js/post_view.js"></script>
    <script src="../js/create_post.js"></script>
</body>
</html>