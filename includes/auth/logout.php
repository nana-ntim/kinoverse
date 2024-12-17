<?php
/**
 * Logout Processor
 * Handles user logout and session cleanup
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),    // Cookie name
        '',               // Cookie value (empty)
        time() - 3600,    // Expiration (in the past)
        '/',             // Path
        '',              // Domain
        true,            // Secure
        true             // HTTP only
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../../public/login.php");
exit();
?>