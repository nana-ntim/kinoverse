<?php
// includes/actions/get_feed_posts.php

header('Content-Type: application/json');
require_once "../../config/database.php";
require_once "../../config/config_session.inc.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $perPage;

    // Get posts from followed users and user's own posts
    $query = "
        SELECT 
            p.*,
            u.username,
            u.profile_image_url,
            (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM kinoverse_comments WHERE post_id = p.post_id) as comment_count,
            (SELECT COUNT(*) FROM kinoverse_follows WHERE following_id = p.user_id) as follower_count,
            EXISTS(
                SELECT 1 FROM kinoverse_likes 
                WHERE post_id = p.post_id AND user_id = ?
            ) as is_liked,
            EXISTS(
                SELECT 1 FROM kinoverse_bookmarks 
                WHERE post_id = p.post_id AND user_id = ?
            ) as is_bookmarked
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        WHERE p.user_id IN (
            SELECT following_id 
            FROM kinoverse_follows 
            WHERE follower_id = ?
        )
        OR p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $_SESSION['user_id'], // for is_liked
        $_SESSION['user_id'], // for is_bookmarked
        $_SESSION['user_id'], // for follows
        $_SESSION['user_id'], // for user's own posts
        $perPage,
        $offset
    ]);

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no posts from follows, get popular posts
    if (empty($posts)) {
        $query = "
            SELECT 
                p.*,
                u.username,
                u.profile_image_url,
                (SELECT COUNT(*) FROM kinoverse_likes WHERE post_id = p.post_id) as like_count,
                (SELECT COUNT(*) FROM kinoverse_comments WHERE post_id = p.post_id) as comment_count,
                (SELECT COUNT(*) FROM kinoverse_follows WHERE following_id = p.user_id) as follower_count,
                EXISTS(
                    SELECT 1 FROM kinoverse_likes 
                    WHERE post_id = p.post_id AND user_id = ?
                ) as is_liked,
                EXISTS(
                    SELECT 1 FROM kinoverse_bookmarks 
                    WHERE post_id = p.post_id AND user_id = ?
                ) as is_bookmarked
            FROM kinoverse_posts p
            JOIN kinoverse_users u ON p.user_id = u.user_id
            LEFT JOIN kinoverse_likes l ON p.post_id = l.post_id
            GROUP BY p.post_id, u.username, u.profile_image_url
            ORDER BY COUNT(l.post_id) DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $perPage,
            $offset
        ]);

        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Transform boolean strings to actual booleans
    foreach ($posts as &$post) {
        $post['is_liked'] = (bool)$post['is_liked'];
        $post['is_bookmarked'] = (bool)$post['is_bookmarked'];
    }

    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'hasMore' => count($posts) === $perPage
    ]);

} catch (PDOException $e) {
    error_log("Error in get_feed_posts: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load posts'
    ]);
}