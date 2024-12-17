<?php
require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

// Get post ID from URL
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);

if (!$post_id) {
    echo "Invalid post ID";
    exit;
}

try {
    // Get post data with user info
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.username,
            u.profile_image_url,
            (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM kinoverse_comments WHERE post_id = p.post_id) as comment_count,
            EXISTS(
                SELECT 1 FROM kinoverse_likes 
                WHERE post_id = p.post_id AND user_id = ?
            ) as is_liked
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        WHERE p.post_id = ?
    ");

    $stmt->execute([$_SESSION['user_id'], $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "Post not found";
        exit;
    }

    // Get comments for the post
    $commentStmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.profile_image_url
        FROM kinoverse_comments c
        JOIN kinoverse_users u ON c.user_id = u.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $commentStmt->execute([$post_id]);
    $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function for time formatting
    function getTimeAgo($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return "just now";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . "m ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . "h ago";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . "d ago";
        } else {
            return date('M j, Y', $time);
        }
    }
?>

<div class="post-header">
    <button class="post-back-btn">
        <i class="fas fa-arrow-left"></i>
    </button>
    <div class="post-user-info">
        <img 
            src="../<?php echo htmlspecialchars($post['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
            alt="Profile picture"
            class="post-avatar"
            onerror="this.src='../assets/default-avatar.jpg'"
        >
        <a href="../pages/profile.php?username=<?php echo urlencode($post['username']); ?>" 
           class="post-username">
            <?php echo htmlspecialchars($post['username']); ?>
        </a>
    </div>
</div>

<div class="post-content">
    <div class="post-image-container">
        <img 
            src="../<?php echo htmlspecialchars($post['image_url']); ?>" 
            alt="<?php echo htmlspecialchars($post['title']); ?>"
            class="post-image"
            onerror="this.src='../assets/default-post.jpg'"
        >
    </div>

    <div class="post-details">
        <?php if (!empty($post['title'])): ?>
            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
        <?php endif; ?>

        <?php if (!empty($post['description'])): ?>
            <p class="post-description"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
        <?php endif; ?>
    </div>

    <div class="post-actions">
        <div class="action-buttons">
            <button class="action-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>" data-action="like">
                <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
            </button>
            <button class="action-btn" onclick="document.getElementById('commentInput').focus()">
                <i class="far fa-comment"></i>
            </button>
            <button class="action-btn" onclick="openCollectionModal(<?php echo $post['post_id']; ?>)">
                <i class="far fa-bookmark"></i>
            </button>
        </div>
        <div class="action-stats">
            <?php echo number_format($post['like_count']); ?> likes
        </div>
    </div>

    <div class="comments-section">
        <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <img 
                    src="../<?php echo htmlspecialchars($comment['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>" 
                    alt="<?php echo htmlspecialchars($comment['username']); ?>'s profile picture"
                    class="comment-avatar"
                    onerror="this.src='../assets/default-avatar.jpg'"
                >
                <div class="comment-content">
                    <div class="comment-header">
                        <a href="../pages/profile.php?username=<?php echo urlencode($comment['username']); ?>" 
                           class="comment-username">
                            <?php echo htmlspecialchars($comment['username']); ?>
                        </a>
                        <span class="comment-time"><?php echo getTimeAgo($comment['created_at']); ?></span>
                    </div>
                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<form class="comment-form" id="commentForm">
    <textarea 
        class="comment-input" 
        id="commentInput"
        placeholder="Add a comment..."
        maxlength="1000"
        rows="1"
    ></textarea>
    <button type="submit" class="comment-submit" disabled>Post</button>
</form>

<div class="collection-modal" id="collectionModal">
    <div class="collection-modal-content">
        <div class="modal-header">
            <h3>Save to Collection</h3>
            <button class="close-btn" onclick="closeCollectionModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="collection-list" id="collectionList">
            <!-- Collections will be loaded here -->
        </div>
        <button class="create-collection-btn" onclick="showCreateCollection()">
            <i class="fas fa-plus"></i>
            Create New Collection
        </button>
    </div>
</div>

<?php
} catch (Exception $e) {
    error_log("Error loading post: " . $e->getMessage());
    echo "Error loading post";
}
?>