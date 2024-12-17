<?php
/**
 * Login Process Handler
 * Handles user authentication with complete validation
 */

session_start();

require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

// Get and validate input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "All fields are required";
    $_SESSION['form_data'] = $_POST;
    header("Location: ../../public/login.php");
    exit();
}

try {
    // Get user data
    $stmt = $pdo->prepare("
        SELECT user_id, username, email, password, 
               created_at, profile_image_url, bio
        FROM kinoverse_users 
        WHERE email = ?
    ");
    
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify user and password
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Invalid email or password";
        $_SESSION['form_data'] = $_POST;
        header("Location: ../../public/login.php");
        exit();
    }

    // Success - Set session data
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image_url'];
    $_SESSION['created_at'] = $user['created_at'];
    $_SESSION['bio'] = $user['bio'];
    
    // Clear any error states
    unset($_SESSION['error']);
    unset($_SESSION['form_data']);

    // Update last login
    $stmt = $pdo->prepare("
        UPDATE kinoverse_users 
        SET last_login = CURRENT_TIMESTAMP 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['user_id']]);

    // Redirect to feed
    header("Location: ../../pages/feed.php");
    exit();

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['form_data'] = $_POST;
    header("Location: ../../public/login.php");
    exit();
}