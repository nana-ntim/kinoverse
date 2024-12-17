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
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Prevent self-modification
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot modify your own account']);
    exit();
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'ban':
            $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
            $duration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING);
            
            if (!$reason || !$duration) {
                throw new Exception('Missing ban details');
            }

            // Calculate ban end date
            if ($duration === 'permanent') {
                $banUntil = '9999-12-31 23:59:59'; // Far future date for permanent ban
            } else {
                $banUntil = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
            }

            // Update user's ban status
            $stmt = $pdo->prepare("
                UPDATE kinoverse_users 
                SET banned_until = ?, ban_reason = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$banUntil, $reason, $userId]);

            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_admin_logs 
                (admin_id, action_type, target_user_id, details) 
                VALUES (?, 'ban', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
                json_encode([
                    'reason' => $reason,
                    'duration' => $duration,
                    'ban_until' => $banUntil
                ])
            ]);
            break;
            
        case 'unban':
            // Remove ban status
            $stmt = $pdo->prepare("
                UPDATE kinoverse_users 
                SET banned_until = NULL, ban_reason = NULL
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_admin_logs 
                (admin_id, action_type, target_user_id) 
                VALUES (?, 'unban', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $userId]);
            break;
            
        case 'promote':
            // Check if already admin
            $stmt = $pdo->prepare("SELECT is_admin FROM kinoverse_users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $isAdmin = $stmt->fetchColumn();

            if ($isAdmin) {
                throw new Exception('User is already an admin');
            }

            // Promote to admin
            $stmt = $pdo->prepare("
                UPDATE kinoverse_users 
                SET is_admin = TRUE
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_admin_logs 
                (admin_id, action_type, target_user_id) 
                VALUES (?, 'promote', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $userId]);
            break;
            
        case 'delete':
            // First, log the deletion
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_admin_logs 
                (admin_id, action_type, target_user_id) 
                VALUES (?, 'delete', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $userId]);

            // Delete user's content first
            $tables = [
                'kinoverse_posts',
                'kinoverse_comments',
                'kinoverse_likes',
                'kinoverse_bookmarks'
            ];

            foreach ($tables as $table) {
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = ?");
                $stmt->execute([$userId]);
            }

            // Delete social connections
            $stmt = $pdo->prepare("
                DELETE FROM kinoverse_follows 
                WHERE follower_id = ? OR following_id = ?
            ");
            $stmt->execute([$userId, $userId]);

            // Finally, delete the user
            $stmt = $pdo->prepare("DELETE FROM kinoverse_users WHERE user_id = ?");
            $stmt->execute([$userId]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Admin action error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}