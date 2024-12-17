<?php
/**
 * Load Post Modal
 * Handles loading the post modal dynamically
 */

require_once "../config/config_session.inc.php";
require_once "../config/database.php";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Get post ID
$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$postId) {
    die('Invalid post ID');
}

try {
    // Fetch post data
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.username, u.profile_image_url,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
               EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as is_liked,
               EXISTS(SELECT 1 FROM kinoverse_follows WHERE follower_id = ? AND following_id = u.user_id) as is_following
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        WHERE p.post_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $postId]);
    $post = $stmt->fetch();

    if (!$post) {
        die('Post not found');
    }

    // Fetch comments
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_image_url
        FROM kinoverse_comments c
        JOIN kinoverse_users u ON c.user_id = u.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();

    // Include modal template
    require_once "../includes/components/post_modal.php";

} catch (PDOException $e) {
    error_log("Error loading post: " . $e->getMessage());
    die('Failed to load post');
}