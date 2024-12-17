<?php
/**
 * Delete Account Action Handler
 * Permanently deletes user account and all associated data
 */

require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    $userId = $_SESSION['user_id'];

    // Delete user's files
    $stmt = $pdo->prepare("
        SELECT profile_image_url, bioImage 
        FROM kinoverse_users 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $files = $stmt->fetch();

    // Delete profile image
    if (!empty($files['profile_image_url'])) {
        $profilePath = "../../" . $files['profile_image_url'];
        if (file_exists($profilePath)) {
            unlink($profilePath);
        }
    }

    // Delete bio banner
    if (!empty($files['bioImage'])) {
        $bannerPath = "../../" . $files['bioImage'];
        if (file_exists($bannerPath)) {
            unlink($bannerPath);
        }
    }

    // Delete user's posts and associated images
    $stmt = $pdo->prepare("
        SELECT image_url 
        FROM kinoverse_posts 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    
    while ($post = $stmt->fetch()) {
        if (!empty($post['image_url'])) {
            $imagePath = "../../" . $post['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }

    // Delete user account (cascading deletes will handle related records)
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    // Clear session
    session_destroy();

    // Redirect to login with message
    session_start();
    $_SESSION['success'] = 'Your account has been successfully deleted.';
    header("Location: ../../public/login.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    $_SESSION['error'] = 'Failed to delete account. Please try again.';
    header("Location: ../../pages/settings.php");
    exit();
}