<?php
/**
 * Toggle Like Handler
 * Handles liking and unliking posts
 */

require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get post ID
$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if already liked
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM kinoverse_likes 
        WHERE user_id = ? AND post_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $postId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Unlike
        $stmt = $pdo->prepare("
            DELETE FROM kinoverse_likes 
            WHERE user_id = ? AND post_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $postId]);
        $liked = false;
    } else {
        // Like
        $stmt = $pdo->prepare("
            INSERT INTO kinoverse_likes (user_id, post_id, created_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id'], $postId]);
        $liked = true;
    }

    // Get updated like count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM kinoverse_likes 
        WHERE post_id = ?
    ");
    $stmt->execute([$postId]);
    $likeCount = $stmt->fetchColumn();

    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likeCount' => $likeCount
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error toggling like: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update like']);
    exit();
}