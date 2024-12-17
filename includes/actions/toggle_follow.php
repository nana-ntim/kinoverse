<?php
// includes/actions/toggle_follow.php

require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Prevent self-follow
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if already following
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM kinoverse_follows 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $userId]);
    $isFollowing = (bool)$stmt->fetchColumn();

    if ($isFollowing) {
        // Unfollow
        $stmt = $pdo->prepare("
            DELETE FROM kinoverse_follows 
            WHERE follower_id = ? AND following_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        
        // Get updated follower count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM kinoverse_follows 
            WHERE following_id = ?
        ");
        $stmt->execute([$userId]);
        $followerCount = $stmt->fetchColumn();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'following' => false,
            'followerCount' => $followerCount
        ]);
    } else {
        // Follow
        $stmt = $pdo->prepare("
            INSERT INTO kinoverse_follows (follower_id, following_id, created_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        
        // Get updated follower count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM kinoverse_follows 
            WHERE following_id = ?
        ");
        $stmt->execute([$userId]);
        $followerCount = $stmt->fetchColumn();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'following' => true,
            'followerCount' => $followerCount
        ]);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Follow Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update follow status'
    ]);
}