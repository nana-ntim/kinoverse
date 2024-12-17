<?php
// includes/actions/toggle_bookmark.php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

// Check if user is logged in
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

    // Check if already bookmarked
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM kinoverse_bookmarks 
        WHERE user_id = ? AND post_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $postId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Remove bookmark
        $stmt = $pdo->prepare("
            DELETE FROM kinoverse_bookmarks 
            WHERE user_id = ? AND post_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $postId]);
        $bookmarked = false;
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("
            INSERT INTO kinoverse_bookmarks (user_id, post_id, created_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id'], $postId]);
        $bookmarked = true;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'bookmarked' => $bookmarked
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error toggling bookmark: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update bookmark']);
}