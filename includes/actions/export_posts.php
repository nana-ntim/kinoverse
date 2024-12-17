<?php
require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";
require_once "../../includes/auth/admin_auth.php";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=posts_report.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for proper Excel UTF-8 handling
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'Post ID',
    'Title',
    'Username',
    'Created Date',
    'Likes',
    'Comments',
    'Bookmarks',
    'Description'
]);

try {
    // Build query with filters
    $query = "
        SELECT 
            p.post_id,
            p.title,
            p.description,
            p.created_at,
            u.username,
            COUNT(DISTINCT l.user_id) as like_count,
            COUNT(DISTINCT c.comment_id) as comment_count,
            COUNT(DISTINCT b.user_id) as bookmark_count
        FROM kinoverse_posts p
        JOIN kinoverse_users u ON p.user_id = u.user_id
        LEFT JOIN kinoverse_likes l ON p.post_id = l.post_id
        LEFT JOIN kinoverse_comments c ON p.post_id = c.post_id
        LEFT JOIN kinoverse_bookmarks b ON p.post_id = b.post_id
        WHERE 1=1
    ";

    $params = [];

    // Apply filters
    if (!empty($_GET['user'])) {
        $query .= " AND u.username LIKE ?";
        $params[] = "%{$_GET['user']}%";
    }

    if (!empty($_GET['date_from'])) {
        $query .= " AND DATE(p.created_at) >= ?";
        $params[] = $_GET['date_from'];
    }

    if (!empty($_GET['date_to'])) {
        $query .= " AND DATE(p.created_at) <= ?";
        $params[] = $_GET['date_to'];
    }

    // Group by
    $query .= " GROUP BY p.post_id";

    // Having clause for engagement filter
    if (!empty($_GET['engagement'])) {
        switch ($_GET['engagement']) {
            case 'high':
                $query .= " HAVING (like_count + comment_count) >= 50";
                break;
            case 'medium':
                $query .= " HAVING (like_count + comment_count) BETWEEN 10 AND 49";
                break;
            case 'low':
                $query .= " HAVING (like_count + comment_count) < 10";
                break;
        }
    }

    // Apply sorting
    switch ($_GET['sort'] ?? 'newest') {
        case 'oldest':
            $query .= " ORDER BY p.created_at ASC";
            break;
        case 'most_liked':
            $query .= " ORDER BY like_count DESC";
            break;
        case 'most_commented':
            $query .= " ORDER BY comment_count DESC";
            break;
        case 'most_saved':
            $query .= " ORDER BY bookmark_count DESC";
            break;
        default:
            $query .= " ORDER BY p.created_at DESC";
    }

    // Log export action
    $stmt = $pdo->prepare("
        INSERT INTO kinoverse_admin_logs 
        (admin_id, action_type, details) 
        VALUES (?, 'export_posts', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        json_encode([
            'filters' => $_GET,
            'timestamp' => date('Y-m-d H:i:s')
        ])
    ]);

    // Execute main query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    // Write data rows
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['post_id'],
            $row['title'],
            $row['username'],
            $row['created_at'],
            $row['like_count'],
            $row['comment_count'],
            $row['bookmark_count'],
            $row['description']
        ]);
    }

} catch (PDOException $e) {
    error_log("Error exporting posts: " . $e->getMessage());
    // Write error row
    fputcsv($output, ['Error generating report. Please try again.']);
}

fclose($output);