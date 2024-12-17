<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId) {
    header("Location: users.php");
    exit();
}

try {
    // Get user details with stats
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT p.post_id) as post_count,
            COUNT(DISTINCT f1.follower_id) as follower_count,
            COUNT(DISTINCT f2.following_id) as following_count,
            COUNT(DISTINCT l.post_id) as like_count,
            COUNT(DISTINCT c.comment_id) as comment_count,
            (
                SELECT MAX(created_at)
                FROM kinoverse_posts
                WHERE user_id = u.user_id
            ) as last_post_date,
            (
                SELECT MAX(created_at)
                FROM kinoverse_likes
                WHERE user_id = u.user_id
            ) as last_like_date,
            (
                SELECT MAX(created_at)
                FROM kinoverse_comments
                WHERE user_id = u.user_id
            ) as last_comment_date
        FROM kinoverse_users u
        LEFT JOIN kinoverse_posts p ON u.user_id = p.user_id
        LEFT JOIN kinoverse_follows f1 ON u.user_id = f1.following_id
        LEFT JOIN kinoverse_follows f2 ON u.user_id = f2.follower_id
        LEFT JOIN kinoverse_likes l ON u.user_id = l.user_id
        LEFT JOIN kinoverse_comments c ON u.user_id = c.user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: users.php");
        exit();
    }

    // Get recent posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(DISTINCT l.user_id) as like_count,
               COUNT(DISTINCT c.comment_id) as comment_count
        FROM kinoverse_posts p
        LEFT JOIN kinoverse_likes l ON p.post_id = l.post_id
        LEFT JOIN kinoverse_comments c ON p.post_id = c.post_id
        WHERE p.user_id = ?
        GROUP BY p.post_id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentPosts = $stmt->fetchAll();

    // Get admin action history
    $stmt = $pdo->prepare("
        SELECT l.*, admin.username as admin_name
        FROM kinoverse_admin_logs l
        JOIN kinoverse_users admin ON l.admin_id = admin.user_id
        WHERE l.target_user_id = ?
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $adminLogs = $stmt->fetchAll();

    // Get most active followers
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT l.post_id) as interaction_count
        FROM kinoverse_users u
        JOIN kinoverse_follows f ON f.follower_id = u.user_id
        LEFT JOIN kinoverse_likes l ON l.user_id = u.user_id
        LEFT JOIN kinoverse_posts p ON l.post_id = p.user_id AND p.user_id = ?
        WHERE f.following_id = ?
        GROUP BY u.user_id
        ORDER BY interaction_count DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $userId]);
    $activeFollowers = $stmt->fetchAll();

    function getTimeAgo($date) {
        if (!$date) return 'Never';
        
        $diff = time() - strtotime($date);
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        } else {
            return date('M j, Y', strtotime($date));
        }
    }

} catch (PDOException $e) {
    error_log("Error in view_user: " . $e->getMessage());
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User | Kinoverse Admin</title>
    
    <link rel="stylesheet" href="../../styles/components/navbar.css">
    <link rel="stylesheet" href="../../styles/admin/view_user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/components/admin_navbar.php'; ?>

    <main class="admin-main">
        <!-- Back button and actions -->
        <div class="page-header">
            <a href="users.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Users
            </a>
            <?php if ($userId !== $_SESSION['user_id']): ?>
                <div class="action-buttons">
                    <?php if (!$user['banned_until']): ?>
                        <button class="action-btn warning" onclick="banUser(<?php echo $userId; ?>)">
                            <i class="fas fa-ban"></i>
                            Ban User
                        </button>
                    <?php else: ?>
                        <button class="action-btn success" onclick="unbanUser(<?php echo $userId; ?>)">
                            <i class="fas fa-user-check"></i>
                            Unban User
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!$user['is_admin']): ?>
                        <button class="action-btn primary" onclick="promoteUser(<?php echo $userId; ?>)">
                            <i class="fas fa-user-shield"></i>
                            Make Admin
                        </button>
                    <?php endif; ?>
                    
                    <button class="action-btn danger" onclick="deleteUser(<?php echo $userId; ?>)">
                        <i class="fas fa-trash"></i>
                        Delete User
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- User Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <img 
                    src="../../<?php echo htmlspecialchars($user['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                    alt="Profile"
                    class="profile-image"
                    onerror="this.src='../../assets/default-avatar.jpg'"
                >
                <div class="profile-info">
                    <div class="profile-name">
                        <?php echo htmlspecialchars($user['username']); ?>
                        <?php if ($user['is_admin']): ?>
                            <span class="badge admin">Admin</span>
                        <?php endif; ?>
                        <?php if ($user['banned_until']): ?>
                            <span class="badge banned">Banned</span>
                        <?php else: ?>
                            <span class="badge active">Active</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="profile-join-date">
                        Joined <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>

            <?php if ($user['banned_until']): ?>
                <div class="ban-info">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="ban-details">
                        <div class="ban-status">
                            Banned until: <?php echo $user['banned_until'] === 'permanent' ? 'Permanent' : date('F j, Y', strtotime($user['banned_until'])); ?>
                        </div>
                        <div class="ban-reason">
                            Reason: <?php echo htmlspecialchars($user['ban_reason']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon posts">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($user['post_count']); ?></span>
                    <span class="stat-label">Posts</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon followers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($user['follower_count']); ?></span>
                    <span class="stat-label">Followers</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon following">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($user['following_count']); ?></span>
                    <span class="stat-label">Following</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon engagement">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($user['like_count']); ?></span>
                    <span class="stat-label">Likes Given</span>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Activity</h2>
                </div>
                <div class="activity-timeline">
                    <?php if ($user['last_post_date']): ?>
                        <div class="timeline-item">
                            <i class="fas fa-camera"></i>
                            <div class="timeline-content">
                                <div class="timeline-title">Posted new content</div>
                                <div class="timeline-time"><?php echo getTimeAgo($user['last_post_date']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['last_comment_date']): ?>
                        <div class="timeline-item">
                            <i class="fas fa-comment"></i>
                            <div class="timeline-content">
                                <div class="timeline-title">Left a comment</div>
                                <div class="timeline-time"><?php echo getTimeAgo($user['last_comment_date']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['last_like_date']): ?>
                        <div class="timeline-item">
                            <i class="fas fa-heart"></i>
                            <div class="timeline-content">
                                <div class="timeline-title">Liked a post</div>
                                <div class="timeline-time"><?php echo getTimeAgo($user['last_like_date']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Posts</h2>
                </div>
                <div class="posts-grid">
                    <?php foreach ($recentPosts as $post): ?>
                        <div class="post-card">
                            <img 
                                src="../../<?php echo htmlspecialchars($post['image_url']); ?>" 
                                alt="Post"
                                class="post-image"
                                onerror="this.src='../../assets/default-post.jpg'"
                            >
                            <div class="post-overlay">
                                <div class="post-stats">
                                    <span><i class="fas fa-heart"></i> <?php echo number_format($post['like_count']); ?></span>
                                    <span><i class="fas fa-comment"></i> <?php echo number_format($post['comment_count']); ?></span>
                                </div>
                                <div class="post-date"><?php echo getTimeAgo($post['created_at']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Admin History -->
            <?php if (!empty($adminLogs)): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>Admin Action History</h2>
                    </div>
                    <div class="admin-logs">
                        <?php foreach ($adminLogs as $log): ?>
                            <div class="log-item">
                                <div class="log-icon">
                                    <?php 
                                    switch ($log['action_type']) {
                                        case 'ban':
                                            echo '<i class="fas fa-ban"></i>';
                                            break;
                                        case 'unban':
                                            echo '<i class="fas fa-user-check"></i>';
                                            break;
                                        case 'promote':
                                            echo '<i class="fas fa-user-shield"></i>';
                                            break;
                                        default:
                                            echo '<i class="fas fa-cog"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="log-content">
                                    <div class="log-details">
                                        <span>by <?php echo htmlspecialchars($log['admin_name']); ?></span>
                                        <?php if ($log['details']): ?>
                                            <?php 
                                            $details = json_decode($log['details'], true);
                                            if ($details && isset($details['reason'])):
                                            ?>
                                                <span class="log-reason">
                                                    Reason: <?php echo htmlspecialchars($details['reason']); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="log-time">
                                        <?php echo getTimeAgo($log['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active Followers -->
            <?php if (!empty($activeFollowers)): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>Most Active Followers</h2>
                    </div>
                    <div class="followers-list">
                        <?php foreach ($activeFollowers as $follower): ?>
                            <div class="follower-item">
                                <img 
                                    src="../../<?php echo htmlspecialchars($follower['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                    alt="Profile"
                                    class="follower-avatar"
                                    onerror="this.src='../../assets/default-avatar.jpg'"
                                >
                                <div class="follower-info">
                                    <div class="follower-name">
                                        <?php echo htmlspecialchars($follower['username']); ?>
                                    </div>
                                    <div class="follower-stats">
                                        <?php echo number_format($follower['interaction_count']); ?> interactions
                                    </div>
                                </div>
                                <button 
                                    class="view-btn"
                                    onclick="viewUser(<?php echo $follower['user_id']; ?>)"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Ban Modal -->
    <div class="modal" id="banModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ban User</h2>
                <button class="close-btn" onclick="closeModal('banModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="banForm">
                <div class="form-group">
                    <label for="banReason">Reason for ban:</label>
                    <textarea 
                        id="banReason" 
                        name="reason" 
                        required 
                        placeholder="Enter the reason for banning this user..."
                    ></textarea>
                </div>
                <div class="form-group">
                    <label for="banDuration">Ban duration:</label>
                    <select id="banDuration" name="duration" required>
                        <option value="1">1 day</option>
                        <option value="3">3 days</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('banModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Ban User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/users.js"></script>
</body>
</html>