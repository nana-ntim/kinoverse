<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

// Get filters
$filters = [
    'user' => $_GET['user'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'engagement' => $_GET['engagement'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest'
];

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    // Base query
    $query = "
        SELECT 
            p.*,
            u.username,
            u.profile_image_url,
            COUNT(DISTINCT l.user_id) as like_count,
            COUNT(DISTINCT c.comment_id) as comment_count,
            COUNT(DISTINCT b.user_id) as bookmark_count
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        LEFT JOIN kinoverse_likes l ON p.post_id = l.post_id
        LEFT JOIN kinoverse_comments c ON p.post_id = c.post_id
        LEFT JOIN kinoverse_bookmarks b ON p.post_id = b.post_id
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if (!empty($filters['user'])) {
        $query .= " AND u.username LIKE ?";
        $params[] = "%{$filters['user']}%";
    }

    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(p.created_at) >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(p.created_at) <= ?";
        $params[] = $filters['date_to'];
    }

    // Group and having clause for engagement filter
    $query .= " GROUP BY p.post_id";

    if (!empty($filters['engagement'])) {
        switch ($filters['engagement']) {
            case 'high':
                $query .= " HAVING (like_count + comment_count) >= 50";
                break;
            case 'medium':
                $query .= " HAVING (like_count + comment_count) BETWEEN 10 AND 49";
                break;
            case 'low':
                $query .= " HAVING (like_count + comment_count) < 10";
                break;
        }
    }

    // Apply sorting
    switch ($filters['sort']) {
        case 'oldest':
            $query .= " ORDER BY p.created_at ASC";
            break;
        case 'most_liked':
            $query .= " ORDER BY like_count DESC";
            break;
        case 'most_commented':
            $query .= " ORDER BY comment_count DESC";
            break;
        case 'most_saved':
            $query .= " ORDER BY bookmark_count DESC";
            break;
        default:
            $query .= " ORDER BY p.created_at DESC";
    }

    // Add pagination
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Get total count for pagination
    $countQuery = str_replace("SELECT p.*, u.username", "SELECT COUNT(DISTINCT p.post_id)", 
                             substr($query, 0, strpos($query, "LIMIT")));
    $stmt = $pdo->prepare($countQuery);
    array_pop($params); // Remove offset
    array_pop($params); // Remove limit
    $stmt->execute($params);
    $totalPosts = $stmt->fetchColumn();
    $totalPages = ceil($totalPosts / $limit);

    // Get quick stats
    $stats = [
        'total_posts' => $pdo->query("SELECT COUNT(*) FROM kinoverse_posts")->fetchColumn(),
        'posts_today' => $pdo->query("SELECT COUNT(*) FROM kinoverse_posts WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'total_comments' => $pdo->query("SELECT COUNT(*) FROM kinoverse_comments")->fetchColumn(),
        'total_likes' => $pdo->query("SELECT COUNT(*) FROM kinoverse_likes")->fetchColumn()
    ];

} catch (PDOException $e) {
    error_log("Error in posts management: " . $e->getMessage());
    $posts = [];
    $totalPages = 0;
    $stats = ['total_posts' => 0, 'posts_today' => 0, 'total_comments' => 0, 'total_likes' => 0];
}

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
    <title>Posts Management | Kinoverse Admin</title>
    
    <link rel="stylesheet" href="../../styles/components/navbar.css">
    <link rel="stylesheet" href="../../styles/admin/posts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/components/admin_navbar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <div class="page-header">
            <h1>Posts Management</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon posts">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo formatNumber($stats['total_posts']); ?></span>
                    <span class="stat-label">Total Posts</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo formatNumber($stats['posts_today']); ?></span>
                    <span class="stat-label">Posts Today</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon comments">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo formatNumber($stats['total_comments']); ?></span>
                    <span class="stat-label">Total Comments</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon likes">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo formatNumber($stats['total_likes']); ?></span>
                    <span class="stat-label">Total Likes</span>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="filter-group">
                    <input 
                        type="text" 
                        name="user" 
                        placeholder="Filter by username"
                        value="<?php echo htmlspecialchars($filters['user']); ?>"
                    >
                </div>

                <div class="filter-group date-group">
                    <input 
                        type="date" 
                        name="date_from" 
                        placeholder="From date"
                        value="<?php echo $filters['date_from']; ?>"
                    >
                    <input 
                        type="date" 
                        name="date_to" 
                        placeholder="To date"
                        value="<?php echo $filters['date_to']; ?>"
                    >
                </div>

                <div class="filter-group">
                    <select name="engagement">
                        <option value="">Engagement Level</option>
                        <option value="high" <?php echo $filters['engagement'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filters['engagement'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filters['engagement'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="sort">
                        <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="most_liked" <?php echo $filters['sort'] === 'most_liked' ? 'selected' : ''; ?>>Most Liked</option>
                        <option value="most_commented" <?php echo $filters['sort'] === 'most_commented' ? 'selected' : ''; ?>>Most Commented</option>
                        <option value="most_saved" <?php echo $filters['sort'] === 'most_saved' ? 'selected' : ''; ?>>Most Saved</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i>
                    Apply Filters
                </button>

                <?php if (!empty($_GET)): ?>
                    <a href="posts.php" class="clear-btn">
                        <i class="fas fa-times"></i>
                        Clear Filters
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Posts Grid -->
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-image">
                        <img 
                            src="../../<?php echo htmlspecialchars($post['image_url']); ?>" 
                            alt="Post"
                            onerror="this.src='../../assets/default-post.jpg'"
                        >
                    </div>
                    <div class="post-content">
                        <div class="post-header">
                            <img 
                                src="../../<?php echo htmlspecialchars($post['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                alt="Profile"
                                class="user-avatar"
                                onerror="this.src='../../assets/default-avatar.jpg'"
                            >
                            <div class="post-info">
                                <div class="username"><?php echo htmlspecialchars($post['username']); ?></div>
                                <div class="post-date"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></div>
                            </div>
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
                            <span>
                                <i class="fas fa-bookmark"></i>
                                <?php echo formatNumber($post['bookmark_count']); ?>
                            </span>
                        </div>

                        <div class="post-actions">
                            <button 
                                class="action-btn view" 
                                onclick="viewPost(<?php echo $post['post_id']; ?>)"
                                title="View Details"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            <button 
                                class="action-btn delete" 
                                onclick="deletePost(<?php echo $post['post_id']; ?>)"
                                title="Delete Post"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No posts found</p>
                <?php if (!empty($_GET)): ?>
                    <a href="posts.php" class="clear-btn">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $start = max(1, min($page - 2, $totalPages - 4));
                $end = min($totalPages, max(5, $page + 2));
                
                if ($start > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-btn">1</a>
                    <?php if ($start > 2): ?>
                        <span class="page-dots">...</span>
                    <?php endif;
                endif;

                for ($i = $start; $i <= $end; $i++): ?>
                    <a 
                        href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                        class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"
                    >
                        <?php echo $i; ?>
                    </a>
                <?php endfor;

                if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="page-btn">
                        <?php echo $totalPages; ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<script src="../../js/admin/posts.js"></script>
</body>
</html>