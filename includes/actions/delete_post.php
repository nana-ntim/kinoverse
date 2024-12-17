<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$postId) {
    echo json_encode(["success" => false, "error" => "Invalid post ID"]);
    exit();
}

try {
    // First verify that the user owns this post
    $stmt = $pdo->prepare("SELECT user_id, image_url FROM kinoverse_posts WHERE post_id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(["success" => false, "error" => "Post not found"]);
        exit();
    }

    if ($post['user_id'] !== $_SESSION['user_id']) {
        echo json_encode(["success" => false, "error" => "You don't have permission to delete this post"]);
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete related records first
    $tables = ['kinoverse_likes', 'kinoverse_comments', 'kinoverse_bookmarks'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE post_id = ?");
        $stmt->execute([$postId]);
    }

    // Delete the post
    $stmt = $pdo->prepare("DELETE FROM kinoverse_posts WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Commit transaction
    $pdo->commit();

    // Delete the image file if it exists
    if ($post['image_url'] && file_exists("../../" . $post['image_url'])) {
        unlink("../../" . $post['image_url']);
    }

    echo json_encode([
        "success" => true,
        "message" => "Post deleted successfully"
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error deleting post: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "Server error occurred while deleting the post"
    ]);
} 