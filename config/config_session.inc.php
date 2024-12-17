<?php
/**
 * Session Configuration
 * Handles session initialization and security settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    // Cookie settings
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours instead of 30 minutes
        'path' => '/',
        'domain' => '',      // Leave empty for current domain
        'secure' => true,
        'httponly' => true
    ]);
    
    session_start();
}

// Regenerate session ID periodically for security
function regenerate_session_id() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if we need to regenerate session ID
if (!isset($_SESSION['last_regeneration'])) {
    regenerate_session_id();
} else {
    $interval = 60 * 30; // 30 minutes
    if (time() - $_SESSION['last_regeneration'] >= $interval) {
        regenerate_session_id();
    }
}