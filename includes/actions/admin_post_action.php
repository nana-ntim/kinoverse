<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';
$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'delete':
            // First log the action
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_admin_logs 
                (admin_id, action_type, details) 
                VALUES (?, 'delete_post', ?)
            ");
            
            // Get post info for logging
            $postStmt = $pdo->prepare("
                SELECT p.*, u.username 
                FROM kinoverse_posts p
                JOIN kinoverse_users u ON p.user_id = u.user_id
                WHERE p.post_id = ?
            ");
            $postStmt->execute([$postId]);
            $postInfo = $postStmt->fetch();
            
            $stmt->execute([
                $_SESSION['user_id'],
                json_encode([
                    'post_id' => $postId,
                    'title' => $postInfo['title'],
                    'username' => $postInfo['username']
                ])
            ]);

            // Delete related data first
            $tables = [
                'kinoverse_likes',
                'kinoverse_comments',
                'kinoverse_bookmarks'
            ];

            foreach ($tables as $table) {
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE post_id = ?");
                $stmt->execute([$postId]);
            }

            // Finally delete the post
            $stmt = $pdo->prepare("DELETE FROM kinoverse_posts WHERE post_id = ?");
            $stmt->execute([$postId]);

            break;
            
        default:
            throw new Exception('Invalid action');
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Admin post action error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}