<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total users count
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM kinoverse_users");
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
} catch (PDOException $e) {
    error_log("Error getting user count: " . $e->getMessage());
    $totalUsers = 0;
    $totalPages = 1;
}

// Get users with stats
try {
    $query = "
        SELECT 
            u.*,
            COUNT(DISTINCT p.post_id) as post_count,
            COUNT(DISTINCT f1.follower_id) as follower_count,
            COUNT(DISTINCT f2.following_id) as following_count,
            (
                SELECT MAX(created_at)
                FROM kinoverse_posts
                WHERE user_id = u.user_id
            ) as last_post_date
        FROM kinoverse_users u
        LEFT JOIN kinoverse_posts p ON u.user_id = p.user_id
        LEFT JOIN kinoverse_follows f1 ON u.user_id = f1.following_id
        LEFT JOIN kinoverse_follows f2 ON u.user_id = f2.follower_id
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

function getTimeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    
    $diff = time() - strtotime($timestamp);
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    } else {
        return date('M j, Y', strtotime($timestamp));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Kinoverse Admin</title>
    
    <link rel="stylesheet" href="../../styles/components/navbar.css">
    <link rel="stylesheet" href="../../styles/admin/users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/components/admin_navbar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <div class="page-header">
            <h1>User Management</h1>
            <div class="header-actions">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="userSearch" placeholder="Search users...">
                </div>
                <button class="header-btn" onclick="exportUsers()">
                    <i class="fas fa-download"></i>
                    Export Users
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($totalUsers); ?></span>
                <span class="stat-label">Total Users</span>
            </div>
            <div class="stat-item new">
                <span class="stat-value">+<?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kinoverse_users WHERE DATE(created_at) = CURDATE()");
                    $stmt->execute();
                    echo $stmt->fetchColumn();
                ?></span>
                <span class="stat-label">New Today</span>
            </div>
            <div class="stat-item active">
                <span class="stat-value"><?php 
                    $stmt = $pdo->prepare("
                        SELECT COUNT(DISTINCT user_id) FROM (
                            SELECT user_id FROM kinoverse_posts WHERE DATE(created_at) = CURDATE()
                            UNION
                            SELECT user_id FROM kinoverse_likes WHERE DATE(created_at) = CURDATE()
                            UNION
                            SELECT user_id FROM kinoverse_comments WHERE DATE(created_at) = CURDATE()
                        ) as active_users
                    ");
                    $stmt->execute();
                    echo $stmt->fetchColumn();
                ?></span>
                <span class="stat-label">Active Today</span>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Posts</th>
                        <th>Followers</th>
                        <th>Following</th>
                        <th>Last Active</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <img 
                                        src="../../<?php echo htmlspecialchars($user['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                        alt="Profile"
                                        onerror="this.src='../../assets/default-avatar.jpg'"
                                    >
                                    <div class="user-info">
                                        <div class="username">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge admin">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="cell-content"><?php echo number_format($user['post_count']); ?></div></td>
                            <td><div class="cell-content"><?php echo number_format($user['follower_count']); ?></div></td>
                            <td><div class="cell-content"><?php echo number_format($user['following_count']); ?></div></td>
                            <td>
                                <div class="cell-content last-active">
                                    <?php echo getTimeAgo($user['last_post_date']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="cell-content">
                                    <?php if ($user['banned_until']): ?>
                                        <span class="badge banned">Banned</span>
                                    <?php else: ?>
                                        <span class="badge active">Active</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                        <button 
                                            class="action-btn view" 
                                            onclick="viewUser(<?php echo $user['user_id']; ?>)" 
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (!$user['banned_until']): ?>
                                            <button 
                                                class="action-btn warning" 
                                                onclick="banUser(<?php echo $user['user_id']; ?>)"
                                                title="Ban User"
                                            >
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="action-btn success" 
                                                onclick="unbanUser(<?php echo $user['user_id']; ?>)"
                                                title="Unban User"
                                            >
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!$user['is_admin']): ?>
                                            <button 
                                                class="action-btn promote" 
                                                onclick="promoteUser(<?php echo $user['user_id']; ?>)"
                                                title="Make Admin"
                                            >
                                                <i class="fas fa-user-shield"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button 
                                            class="action-btn danger" 
                                            onclick="deleteUser(<?php echo $user['user_id']; ?>)"
                                            title="Delete User"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Action Modals -->
    <div class="modal" id="banModal">
        <div class="modal-content">
            <h2>Ban User</h2>
            <p>Are you sure you want to ban this user?</p>
            <form id="banForm">
                <div class="form-group">
                    <label for="banReason">Reason for ban:</label>
                    <textarea id="banReason" required></textarea>
                </div>
                <div class="form-group">
                    <label for="banDuration">Ban duration:</label>
                    <select id="banDuration" required>
                        <option value="1">1 day</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('banModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Ban User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/users.js"></script>
</body>
</html>