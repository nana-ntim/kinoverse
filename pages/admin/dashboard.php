<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

// Fetch stats
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM kinoverse_users");
    $totalUsers = $stmt->fetchColumn();

    // Total posts
    $stmt = $pdo->query("SELECT COUNT(*) FROM kinoverse_posts");
    $totalPosts = $stmt->fetchColumn();

    // Posts today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM kinoverse_posts 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $postsToday = $stmt->fetchColumn();

    // Active users today
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) 
        FROM (
            SELECT user_id FROM kinoverse_posts WHERE DATE(created_at) = CURDATE()
            UNION
            SELECT user_id FROM kinoverse_comments WHERE DATE(created_at) = CURDATE()
            UNION
            SELECT user_id FROM kinoverse_likes WHERE DATE(created_at) = CURDATE()
        ) as active_users
    ");
    $stmt->execute();
    $activeUsers = $stmt->fetchColumn();

    // Recent signups
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(p.post_id) as post_count,
               COUNT(DISTINCT f.follower_id) as follower_count
        FROM kinoverse_users u
        LEFT JOIN kinoverse_posts p ON u.user_id = p.user_id
        LEFT JOIN kinoverse_follows f ON u.user_id = f.following_id
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();

    // Trending posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_image_url,
               COUNT(DISTINCT l.user_id) as like_count,
               COUNT(DISTINCT c.comment_id) as comment_count
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        LEFT JOIN kinoverse_likes l ON p.post_id = l.post_id
        LEFT JOIN kinoverse_comments c ON p.post_id = c.post_id
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY p.post_id
        ORDER BY (COUNT(DISTINCT l.user_id) + COUNT(DISTINCT c.comment_id)) DESC
        LIMIT 5
    ");
    $stmt->execute();
    $trendingPosts = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching admin stats: " . $e->getMessage());
}

// Function to format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Kinoverse</title>
    
    <link rel="stylesheet" href="../../styles/components/navbar.css">
    <link rel="stylesheet" href="../../styles/admin/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/components/admin_navbar.php'; ?>

    <main class="admin-main">
        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-buttons">
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    Manage Users
                </a>
                <a href="posts.php" class="action-btn">
                    <i class="fas fa-images"></i>
                    Manage Posts
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-flag"></i>
                    View Reports
                </a>
                <a href="#" class="action-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Export Report
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value"><?php echo formatNumber($totalUsers); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon posts">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Posts</span>
                    <span class="stat-value"><?php echo formatNumber($totalPosts); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Posts Today</span>
                    <span class="stat-value"><?php echo formatNumber($postsToday); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Active Users Today</span>
                    <span class="stat-value"><?php echo formatNumber($activeUsers); ?></span>
                </div>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-grid">
            <!-- Recent Users -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Sign-ups</h2>
                    <a href="users.php" class="view-all">View All</a>
                </div>
                <div class="user-list">
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="user-item">
                            <img 
                                src="../../<?php echo htmlspecialchars($user['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                alt="Profile" 
                                class="user-avatar"
                                onerror="this.src='../../assets/default-avatar.jpg'"
                            >
                            <div class="user-info">
                                <div class="user-primary">
                                    <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="joined-date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <div class="user-stats">
                                    <span>
                                        <i class="fas fa-camera"></i>
                                        <?php echo formatNumber($user['post_count']); ?> posts
                                    </span>
                                    <span>
                                        <i class="fas fa-users"></i>
                                        <?php echo formatNumber($user['follower_count']); ?> followers
                                    </span>
                                </div>
                            </div>
                            <div class="user-actions">
                                <button class="icon-btn" onclick="viewUser(<?php echo $user['user_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Trending Posts -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Trending Posts</h2>
                    <a href="posts.php" class="view-all">View All</a>
                </div>
                <div class="post-list">
                    <?php foreach ($trendingPosts as $post): ?>
                        <div class="post-item">
                            <div class="post-thumbnail">
                                <img 
                                    src="../../<?php echo htmlspecialchars($post['image_url']); ?>" 
                                    alt="Post thumbnail"
                                    onerror="this.src='../../assets/default-post.jpg'"
                                >
                            </div>
                            <div class="post-info">
                                <div class="post-header">
                                    <img 
                                        src="../../<?php echo htmlspecialchars($post['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                        alt="Profile" 
                                        class="post-user-avatar"
                                        onerror="this.src='../../assets/default-avatar.jpg'"
                                    >
                                    <span class="post-username"><?php echo htmlspecialchars($post['username']); ?></span>
                                </div>
                                <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                <div class="post-stats">
                                    <span>
                                        <i class="fas fa-heart"></i>
                                        <?php echo formatNumber($post['like_count']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-comment"></i>
                                        <?php echo formatNumber($post['comment_count']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="post-actions">
                                <button class="icon-btn" onclick="viewPost(<?php echo $post['post_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    function exportReport() {
        // Get current date for filename
        const date = new Date().toISOString().split('T')[0];
        
        // Redirect to export script
        window.location.href = '../../includes/actions/export_posts.php?type=dashboard&date=' + date;
    }

    function viewUser(userId) {
        window.location.href = `view_user.php?id=${userId}`;
    }

    function viewPost(postId) {
        window.location.href = `view_post.php?id=${postId}`;
    }
    </script>
</body>
</html>