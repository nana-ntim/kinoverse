<?php
/**
 * Update Password Action Handler
 * Handles password change requests
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";
require_once "../utils/validation.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

try {
    // Get form data
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Verify passwords match
    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match.');
    }

    // Validate new password
    if ($error = validatePassword($newPassword)) {
        throw new Exception($error);
    }

    // Get user's current password hash
    $stmt = $pdo->prepare("SELECT password FROM kinoverse_users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        throw new Exception('Current password is incorrect.');
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $pdo->prepare("
        UPDATE kinoverse_users 
        SET password = ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

    $_SESSION['success'] = 'Password updated successfully!';
    header("Location: ../../pages/settings.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../../pages/settings.php");
    exit();
}