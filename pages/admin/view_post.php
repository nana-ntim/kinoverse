<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$postId) {
    header("Location: posts.php");
    exit();
}

try {
    // Get post details with user info and stats
    $stmt = $pdo->prepare("
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
        WHERE p.post_id = ?
        GROUP BY p.post_id
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        header("Location: posts.php");
        exit();
    }

    // Get comments
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.profile_image_url,
            COUNT(DISTINCT l.user_id) as like_count
        FROM kinoverse_comments c
        JOIN kinoverse_users u ON c.user_id = u.user_id
        LEFT JOIN kinoverse_comment_likes l ON c.comment_id = l.comment_id
        WHERE c.post_id = ?
        GROUP BY c.comment_id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();

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
    error_log("Error in view_post: " . $e->getMessage());
    header("Location: posts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post | Kinoverse Admin</title>
    
    <link rel="stylesheet" href="../../styles/components/navbar.css">
    <link rel="stylesheet" href="../../styles/admin/view_post.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/components/admin_navbar.php'; ?>

    <main class="admin-main">
        <!-- Back button and actions -->
        <div class="page-header">
            <a href="posts.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Posts
            </a>
            <div class="action-buttons">
                <button class="action-btn danger" onclick="deletePost(<?php echo $postId; ?>)">
                    <i class="fas fa-trash"></i>
                    Delete Post
                </button>
            </div>
        </div>

        <!-- Post Content -->
        <div class="post-container">
            <div class="post-content">
                <!-- Post Image -->
                <div class="post-image-container">
                    <img 
                        src="../../<?php echo htmlspecialchars($post['image_url']); ?>" 
                        alt="Post"
                        class="post-image"
                        onerror="this.src='../../assets/default-post.jpg'"
                    >
                </div>

                <!-- Post Info -->
                <div class="post-info">
                    <!-- Author Info -->
                    <div class="author-info">
                        <img 
                            src="../../<?php echo htmlspecialchars($post['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                            alt="Profile"
                            class="author-avatar"
                            onerror="this.src='../../assets/default-avatar.jpg'"
                        >
                        <div class="author-details">
                            <a href="view_user.php?id=<?php echo $post['user_id']; ?>" class="author-name">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                            <div class="post-date"><?php echo getTimeAgo($post['created_at']); ?></div>
                        </div>
                    </div>

                    <!-- Post Title and Description -->
                    <div class="post-text">
                        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                        <p class="post-description"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                    </div>

                    <!-- Post Stats -->
                    <div class="post-stats">
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span><?php echo number_format($post['like_count']); ?> likes</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comment"></i>
                            <span><?php echo number_format($post['comment_count']); ?> comments</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-bookmark"></i>
                            <span><?php echo number_format($post['bookmark_count']); ?> saves</span>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <?php if (!empty($comments)): ?>
                        <div class="comments-section">
                            <h2>Recent Comments</h2>
                            <div class="comments-list">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <img 
                                            src="../../<?php echo htmlspecialchars($comment['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                                            alt="Profile"
                                            class="comment-avatar"
                                            onerror="this.src='../../assets/default-avatar.jpg'"
                                        >
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <a href="view_user.php?id=<?php echo $comment['user_id']; ?>" class="comment-author">
                                                    <?php echo htmlspecialchars($comment['username']); ?>
                                                </a>
                                                <span class="comment-date"><?php echo getTimeAgo($comment['created_at']); ?></span>
                                            </div>
                                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            <div class="comment-stats">
                                                <span><i class="fas fa-heart"></i> <?php echo number_format($comment['like_count']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
    function deletePost(postId) {
        if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            // Add your delete post logic here
            window.location.href = `../../includes/actions/delete_post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 