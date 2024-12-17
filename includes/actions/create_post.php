<?php
/**
 * Create Post Handler
 * Processes new post creation including image upload
 */

require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

/**
 * Handles image upload and processing
 * @param array $file The uploaded file array
 * @return array Array with 'path' or 'error' key
 */
function handleImageUpload($file) {
    // Validate file presence
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'No file uploaded'];
    }

    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['error' => 'Image must be less than 10MB'];
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed'];
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'post_' . uniqid() . '_' . time() . '.' . $extension;
    
    // Set up paths
    $uploadsPath = dirname(dirname(dirname(__FILE__))) . '/uploads/posts/original';
    $uploadPath = $uploadsPath . '/' . $filename;
    $relativePath = 'uploads/posts/original/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['error' => 'Failed to save image'];
    }

    // TODO: Generate thumbnail (implementation depends on your image processing library)
    
    return ['path' => $relativePath];
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Handle image upload
    $uploadResult = handleImageUpload($_FILES['post_image']);
    if (isset($uploadResult['error'])) {
        throw new Exception($uploadResult['error']);
    }

    // Validate and sanitize input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cameraDetails = trim($_POST['camera_details'] ?? '');
    $lensDetails = trim($_POST['lens_details'] ?? '');
    $lightingSetup = trim($_POST['lighting_setup'] ?? '');

    if (empty($title)) {
        throw new Exception('Title is required');
    }

    // Insert post into database
    $stmt = $pdo->prepare("
        INSERT INTO kinoverse_posts (
            user_id,
            image_url,
            title,
            description,
            camera_details,
            lens_details,
            lighting_setup
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $uploadResult['path'],
        $title,
        $description,
        $cameraDetails,
        $lensDetails,
        $lightingSetup
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Return error response
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}