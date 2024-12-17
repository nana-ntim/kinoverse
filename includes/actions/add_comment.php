<?php
/**
 * Add Comment Handler
 * Handles adding new comments to posts
 */

require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

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

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get post ID and comment content
$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');

// Validate input
if (!$postId || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

if (strlen($content) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment is too long']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO kinoverse_comments (user_id, post_id, content, created_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$_SESSION['user_id'], $postId, $content]);
    $commentId = $pdo->lastInsertId();

    // Get comment data with user info
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_image_url
        FROM kinoverse_comments c
        JOIN kinoverse_users u ON c.user_id = u.user_id
        WHERE c.comment_id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    // Generate comment HTML
    $commentHtml = '
    <div class="comment-item">
        <img src="../' . (!empty($comment['profile_image_url']) ? 
            htmlspecialchars($comment['profile_image_url']) : 
            'assets/default-avatar.jpg') . '"
            class="comment-avatar"
            alt="' . htmlspecialchars($comment['username']) . '\'s profile picture"
            onerror="this.src=\'../assets/default-avatar.jpg\'">
        
        <div class="comment-content">
            <div class="comment-header">
                <a href="../pages/profile.php?username=' . urlencode($comment['username']) . '" 
                   class="comment-username">
                    ' . htmlspecialchars($comment['username']) . '
                </a>
                <span class="comment-timestamp">' . getTimeAgo($comment['created_at']) . '</span>
            </div>
            <p class="comment-text">' . nl2br(htmlspecialchars($comment['content'])) . '</p>
        </div>
    </div>';

    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'commentHtml' => $commentHtml,
        'commentId' => $commentId
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error adding comment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    exit();
}