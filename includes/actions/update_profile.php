<?php
/**
 * Update Profile Handler
 * Handles profile updates including image uploads
 */

require_once "../../config/config_session.inc.php";
require_once "../../config/database.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

/**
 * Validates and handles image upload
 * @param array $file The uploaded file array
 * @param string $type Type of image (profile or banner)
 * @param string|null $oldImage Path to existing image
 * @return array Array with 'path' or 'error' key
 */
function handleImageUpload($file, $type = 'profile', $oldImage = null) {
    // If no file uploaded, return null path
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null];
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

    // Set up paths based on image type
    $baseDir = dirname(dirname(dirname(__FILE__)));
    $uploadDir = $type === 'profile' ? '/uploads/profiles' : '/uploads/profiles/banners';
    $uploadsPath = $baseDir . $uploadDir;

    // Create directory if it doesn't exist
    if (!file_exists($uploadsPath)) {
        if (!mkdir($uploadsPath, 0777, true)) {
            return ['error' => 'Failed to create upload directory'];
        }
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $type . '_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = $uploadsPath . '/' . $filename;
    $relativePath = 'uploads/profiles' . ($type === 'banner' ? '/banners' : '') . '/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['error' => 'Failed to save image'];
    }

    // Delete old image if it exists
    if ($oldImage && $oldImage !== 'assets/default-avatar.jpg' && file_exists($baseDir . '/' . $oldImage)) {
        unlink($baseDir . '/' . $oldImage);
    }

    return ['path' => $relativePath];
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Handle profile image upload if present
    $newProfileImagePath = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handleImageUpload($_FILES['profile_image'], 'profile', $_SESSION['profile_image'] ?? null);
        if (isset($uploadResult['error'])) {
            throw new Exception($uploadResult['error']);
        }
        $newProfileImagePath = $uploadResult['path'];
    }

    // Handle banner image upload if present
    $newBannerImagePath = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handleImageUpload($_FILES['banner_image'], 'banner', $_SESSION['bioImage'] ?? null);
        if (isset($uploadResult['error'])) {
            throw new Exception($uploadResult['error']);
        }
        $newBannerImagePath = $uploadResult['path'];
    }

    // Validate and sanitize input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Basic validation
    if (empty($username) || empty($email)) {
        throw new Exception('Username and email are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if username or email is already taken (excluding current user)
    $stmt = $pdo->prepare("
        SELECT user_id FROM kinoverse_users 
        WHERE (username = ? OR email = ?) 
        AND user_id != ?
    ");
    $stmt->execute([$username, $email, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        throw new Exception('Username or email is already taken');
    }

    // Build update query
    $updateFields = [];
    $params = [];

    // Add basic fields
    $updateFields[] = "username = ?";
    $params[] = $username;

    $updateFields[] = "email = ?";
    $params[] = $email;

    $updateFields[] = "bio = ?";
    $params[] = $bio;

    // Add profile image if uploaded
    if ($newProfileImagePath) {
        $updateFields[] = "profile_image_url = ?";
        $params[] = $newProfileImagePath;
    }

    // Add banner image if uploaded
    if ($newBannerImagePath) {
        $updateFields[] = "bioImage = ?";
        $params[] = $newBannerImagePath;
    }

    // Add user_id to params
    $params[] = $_SESSION['user_id'];

    // Update database
    $sql = "UPDATE kinoverse_users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt->execute($params)) {
        throw new Exception('Failed to update profile');
    }

    // Update session data
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['bio'] = $bio;
    if ($newProfileImagePath) {
        $_SESSION['profile_image'] = $newProfileImagePath;
    }
    if ($newBannerImagePath) {
        $_SESSION['bioImage'] = $newBannerImagePath;
    }

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Profile updated successfully!';

} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = $e->getMessage();
} finally {
    header("Location: ../../pages/settings.php");
    exit();
}