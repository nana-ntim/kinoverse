<?php
/**
 * Profile Helper Functions
 * Utility functions for user profile management
 */

/**
 * Get user's complete profile data
 * @param PDO $pdo Database connection
 * @param string $username Username to fetch
 * @return array|false User data or false if not found
 */
function getUserProfile($pdo, $username) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                kinoverse_users.*,
                (SELECT COUNT(*) FROM kinoverse_posts WHERE user_id = kinoverse_users.user_id) as post_count,
                (SELECT COUNT(*) FROM kinoverse_follows WHERE following_id = kinoverse_users.user_id) as follower_count,
                (SELECT COUNT(*) FROM kinoverse_follows WHERE follower_id = kinoverse_users.user_id) as following_count
            FROM kinoverse_users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's posts with pagination
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Posts per page
 * @param int $offset Pagination offset
 * @return array Array of posts
 */
function getUserPosts($pdo, $userId, $limit = 12, $offset = 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT post_id, image_url, title, description, created_at,
                   (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = kinoverse_posts.post_id) as like_count
            FROM kinoverse_posts 
            WHERE user_id = ? 
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching user posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user is following another user
 * @param PDO $pdo Database connection
 * @param int $followerId Follower's user ID
 * @param int $followingId Following user's ID
 * @return bool True if following, false otherwise
 */
function isFollowing($pdo, $followerId, $followingId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM kinoverse_follows 
            WHERE follower_id = ? AND following_id = ?
        ");
        $stmt->execute([$followerId, $followingId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking follow status: " . $e->getMessage());
        return false;
    }
}

/**
 * Toggle follow status between users
 * @param PDO $pdo Database connection
 * @param int $followerId Follower's user ID
 * @param int $followingId Following user's ID
 * @return array Status array with success/error message
 */
function toggleFollow($pdo, $followerId, $followingId) {
    try {
        $pdo->beginTransaction();

        // Check if already following
        $following = isFollowing($pdo, $followerId, $followingId);
        
        if ($following) {
            // Unfollow
            $stmt = $pdo->prepare("
                DELETE FROM kinoverse_follows 
                WHERE follower_id = ? AND following_id = ?
            ");
            $stmt->execute([$followerId, $followingId]);
            $message = "Unfollowed successfully";
        } else {
            // Follow
            $stmt = $pdo->prepare("
                INSERT INTO kinoverse_follows (follower_id, following_id, created_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$followerId, $followingId]);
            $message = "Followed successfully";
        }

        $pdo->commit();
        return ['success' => true, 'message' => $message];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error toggling follow status: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update follow status'];
    }
}

/**
 * Get user's latest activity (posts and likes)
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $limit Number of items to fetch
 * @return array Array of activity items
 */
function getUserActivity($pdo, $userId, $limit = 10) {
    try {
        // Get recent posts
        $stmt = $pdo->prepare("
            (SELECT 
                'post' as type,
                posts.post_id as id,
                posts.title,
                posts.created_at,
                kinoverse_posts.image_url
            FROM kinoverse_posts 
            WHERE user_id = ?)
            UNION
            (SELECT 
                'like' as type,
                posts.post_id as id,
                posts.title,
                likes.created_at,
                posts.image_url
            FROM kinoverse_likes 
            JOIN kinoverse_posts ON kinoverse_likes.post_id = kinoverse_posts.post_id
            WHERE kinoverse_likes.user_id = ?)
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching user activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Format user stats for display
 * @param int $count Number to format
 * @return string Formatted number (e.g., 1.2K, 1.1M)
 */
function formatCount($count) {
    if ($count >= 1000000) {
        return round($count / 1000000, 1) . 'M';
    }
    if ($count >= 1000) {
        return round($count / 1000, 1) . 'K';
    }
    return $count;
}

/**
 * Get suggested users to follow
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param int $limit Number of suggestions
 * @return array Array of suggested users
 */
function getSuggestedUsers($pdo, $userId, $limit = 3) {
    try {
        // Get users with similar post types/tags who aren't already followed
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.username, u.profile_image_url, u.bio,
                   COUNT(DISTINCT p.post_id) as post_count
            FROM kinoverse_users u
            LEFT JOIN kinoverse_posts p ON u.user_id = p.user_id
            WHERE u.user_id != ? 
            AND u.user_id NOT IN (
                SELECT following_id 
                FROM kinoverse_follows 
                WHERE follower_id = ?
            )
            GROUP BY u.user_id
            ORDER BY post_count DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching suggested users: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate and sanitize profile data
 * @param array $data Profile data to validate
 * @return array Validated and sanitized data
 */
function validateProfileData($data) {
    $errors = [];
    $sanitized = [];

    // Username validation
    if (empty($data['username'])) {
        $errors['username'] = "Username is required";
    } else {
        $sanitized['username'] = filter_var(trim($data['username']), FILTER_SANITIZE_STRING);
        if (strlen($sanitized['username']) < 3 || strlen($sanitized['username']) > 30) {
            $errors['username'] = "Username must be between 3 and 30 characters";
        }
    }

    // Bio validation
    if (isset($data['bio'])) {
        $sanitized['bio'] = filter_var(trim($data['bio']), FILTER_SANITIZE_STRING);
        if (strlen($sanitized['bio']) > 500) {
            $errors['bio'] = "Bio must not exceed 500 characters";
        }
    }

    return [
        'errors' => $errors,
        'sanitized' => $sanitized
    ];
}

/**
 * Get common followers between two users
 * @param PDO $pdo Database connection
 * @param int $user1Id First user's ID
 * @param int $user2Id Second user's ID
 * @param int $limit Number of common followers to return
 * @return array Array of common followers
 */
function getCommonFollowers($pdo, $user1Id, $user2Id, $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.profile_image_url
            FROM kinoverse_users u
            INNER JOIN kinoverse_follows f1 ON u.user_id = f1.follower_id
            INNER JOIN kinoverse_follows f2 ON u.user_id = f2.follower_id
            WHERE f1.following_id = ?
            AND f2.following_id = ?
            LIMIT ?
        ");
        $stmt->execute([$user1Id, $user2Id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching common followers: " . $e->getMessage());
        return [];
    }
}