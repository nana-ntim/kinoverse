<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

// Check if user is admin
try {
    $stmt = $pdo->prepare("
        SELECT is_admin 
        FROM kinoverse_users 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $isAdmin = $stmt->fetchColumn();

    if (!$isAdmin) {
        header("Location: ../../pages/feed.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Admin auth error: " . $e->getMessage());
    header("Location: ../../pages/feed.php");
    exit();
}