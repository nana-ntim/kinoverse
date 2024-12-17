<?php
/**
 * Get Post Handler
 * Returns post data and HTML for dynamic loading
 */

require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

// Set JSON content type
header('Content-Type: application/json');

// Check if post ID is provided
if (!isset($_GET['post_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Post ID is required'
    ]);
    exit();
}

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

try {
    // Add session debugging
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Requested post_id: " . $_GET['post_id']);

    // Get post data
    $stmt = $pdo->prepare("
        SELECT kinoverse_posts.*, 
               kinoverse_users.username, 
               kinoverse_users.profile_image_url,
               (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = kinoverse_posts.post_id) as like_count,
               EXISTS(
                   SELECT 1 FROM kinoverse_likes 
                   WHERE post_id = kinoverse_posts.post_id AND user_id = ?
               ) as is_liked
        FROM kinoverse_posts 
        JOIN kinoverse_users ON kinoverse_posts.user_id = kinoverse_users.user_id
        WHERE kinoverse_posts.post_id = ?
    ");
    
    // Log the query parameters
    error_log("Executing query with user_id: " . ($_SESSION['user_id'] ?? 0) . " and post_id: " . $_GET['post_id']);
    
    $stmt->execute([$_SESSION['user_id'] ?? 0, $_GET['post_id']]);
    $post = $stmt->fetch();

    if (!$post) {
        error_log("Post not found for ID: " . $_GET['post_id']);
        throw new Exception('Post not found');
    }

    // Log successful post fetch
    error_log("Post successfully fetched: " . print_r($post, true));

    // Get comments
    $stmt = $pdo->prepare("
        SELECT kinoverse_comments.*, kinoverse_users.username, kinoverse_users.profile_image_url
        FROM kinoverse_comments 
        JOIN kinoverse_users ON kinoverse_comments.user_id = kinoverse_users.user_id
        WHERE post_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$_GET['post_id']]);
    $comments = $stmt->fetchAll();

    // Start output buffering
    ob_start();
    ?>
    <!-- Post Header -->
    <header class="post-header">
        <button type="button" class="post-back-btn" id="postBackBtn" aria-label="Close post">
            <i class="fas fa-arrow-left"></i>
        </button>
        
        <div class="post-user-info">
            <img 
                src="../<?php echo htmlspecialchars($post['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>"
                class="post-avatar"
                alt=""
                loading="lazy"
                onerror="this.src='../assets/default-avatar.jpg'"
            >
            <a href="../pages/profile.php?username=<?php echo urlencode($post['username']); ?>" 
               class="post-username">
                <?php echo htmlspecialchars($post['username']); ?>
            </a>
        </div>
    </header>
    
    <!-- Main Content -->
    <div class="post-content">
        <!-- Image -->
        <div class="post-image-container">
            <img 
                src="../<?php echo htmlspecialchars($post['image_url']); ?>"
                class="post-image"
                alt="<?php echo htmlspecialchars($post['title']); ?>"
                loading="lazy"
            >
        </div>
        
        <!-- Details -->
        <div class="post-details">
            <?php if (!empty($post['title']) || !empty($post['description'])): ?>
                <?php if (!empty($post['title'])): ?>
                    <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <?php endif; ?>
                
                <?php if (!empty($post['description'])): ?>
                    <p class="post-description"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Technical Details -->
            <?php if (!empty($post['camera_details']) || !empty($post['lens_details']) || !empty($post['lighting_setup'])): ?>
                <div class="technical-details">
                    <?php if (!empty($post['camera_details'])): ?>
                        <div class="technical-item">
                            <div class="technical-label">Camera</div>
                            <div class="technical-value"><?php echo htmlspecialchars($post['camera_details']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['lens_details'])): ?>
                        <div class="technical-item">
                            <div class="technical-label">Lens</div>
                            <div class="technical-value"><?php echo htmlspecialchars($post['lens_details']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['lighting_setup'])): ?>
                        <div class="technical-item">
                            <div class="technical-label">Lighting Setup</div>
                            <div class="technical-value"><?php echo nl2br(htmlspecialchars($post['lighting_setup'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Comments -->
        <div class="comments-section">
            <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <img 
                        src="../<?php echo htmlspecialchars($comment['profile_image_url'] ?? 'assets/default-avatar.jpg'); ?>"
                        class="comment-avatar"
                        alt=""
                        loading="lazy"
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
                        <p class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Actions Bar -->
    <div class="post-actions">
        <div class="action-buttons">
            <button 
                class="action-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>"
                data-action="like"
                aria-label="<?php echo $post['is_liked'] ? 'Unlike' : 'Like'; ?>"
            >
                <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
            </button>
            <button 
                class="action-btn"
                onclick="document.getElementById('commentInput').focus()"
                aria-label="Comment"
            >
                <i class="far fa-comment"></i>
            </button>
        </div>
        
        <div class="action-stats">
            <span id="likeCount"><?php echo number_format($post['like_count']); ?></span> likes
        </div>
    </div>

    <!-- Comment Form -->
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
    <?php
    $html = ob_get_clean();

    // Return success response with HTML
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log("Detailed error loading post: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => 'Check server logs for more information'
    ]);
}
?>