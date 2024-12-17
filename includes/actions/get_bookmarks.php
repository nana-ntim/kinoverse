
// includes/actions/get_bookmarks.php
<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_image_url,
               (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM kinoverse_comments WHERE post_id = p.post_id) as comment_count,
               EXISTS(
                   SELECT 1 FROM kinoverse_likes 
                   WHERE post_id = p.post_id AND user_id = ?
               ) as is_liked
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        JOIN kinoverse_bookmarks b ON p.post_id = b.post_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $perPage, $offset]);
    $posts = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);

} catch (PDOException $e) {
    error_log("Error fetching bookmarks: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load bookmarks'
    ]);
}
?>