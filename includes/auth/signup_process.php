<?php
ini_set('display_errors', 1);
// Start session
session_start();

// At the very top, after session_start()
error_log("=== Starting Signup Process ===");
error_log("POST data received: " . print_r($_POST, true));

// Include necessary files 
require_once "../../config/database.php";
require_once "../utils/validation.php";

/**
 * Redirects back to signup with error message
 * Preserves form data for user convenience
 */
function redirectToSignup($error) {
    $_SESSION['error'] = $error;
    $_SESSION['form_data'] = $_POST;
    header("Location: ../../public/signup.php");
    exit();
}

// =============================================
// Request Method Validation
// =============================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../public/signup.php");
    exit();
}

// =============================================
// Data Collection & Initial Sanitization
// =============================================
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

// =============================================
// Field Validation
// =============================================

// Validate First Name
if ($error = validateName($firstName, "First name")) {
    redirectToSignup($error);
}

// Validate Last Name
if ($error = validateName($lastName, "Last name")) {
    redirectToSignup($error);
}

// Validate Username
if ($error = validateUsername($username)) {
    redirectToSignup($error);
}

// Validate Email
if ($error = validateEmail($email)) {
    redirectToSignup($error);
}

// Validate Password
if ($error = validatePassword($password)) {
    redirectToSignup($error);
}

// Confirm Password Match
if ($password !== $confirmPassword) {
    redirectToSignup("Passwords do not match");
}

// =============================================
// Database Operations
// =============================================
try {
    // Test database connection explicitly
    error_log("Testing database connection...");
    $pdo->query("SELECT 1");
    error_log("Database connection successful");

    // Add password hashing here
    error_log("Hashing password...");
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    if ($hashedPassword === false) {
        error_log("Password hashing failed");
        throw new Exception("Error processing password");
    }
    error_log("Password hashed successfully");

    // Before email check
    error_log("Checking for existing email: " . $email);
    $stmt = $pdo->prepare("SELECT email FROM kinoverse_users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        error_log("Email already exists: " . $email);
        redirectToSignup("This email is already registered");
    }
    error_log("Email check passed");
    
    // Before username check
    error_log("Checking for existing username: " . $username);
    $stmt = $pdo->prepare("SELECT username FROM kinoverse_users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        error_log("Username already exists: " . $username);
        redirectToSignup("This username is already taken");
    }
    error_log("Username check passed");
    
    // Before insert
    error_log("Attempting to insert new user");
    $stmt = $pdo->prepare("
        INSERT INTO kinoverse_users (
            username, 
            email, 
            password, 
            bio,
            created_at
        ) VALUES (
            ?, ?, ?, ?, 
            CURRENT_TIMESTAMP
        )
    ");
    
    $defaultBio = "Hi, I'm " . htmlspecialchars($firstName) . "! I'm new to Kinoverse.";
    
    // Log the values being inserted
    error_log("Inserting values: " . print_r([
        'username' => $username,
        'email' => $email,
        'bio' => $defaultBio
    ], true));
    
    $stmt->execute([
        $username,
        $email,
        $hashedPassword,
        $defaultBio
    ]);
    
    error_log("User successfully inserted with ID: " . $pdo->lastInsertId());

    // =============================================
    // Setup User Session
    // =============================================
    
    // Generate new session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    // Clear signup form data
    unset($_SESSION['form_data']);
    
    // =============================================
    // Success - Redirect to Feed
    // =============================================
    $_SESSION['success'] = "Welcome to Kinoverse!";
    header("Location: ../../pages/feed.php");
    exit();
    
} catch (PDOException $e) {
    error_log("=== Detailed Error Information ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0] ?? 'N/A');
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    // Development-specific detailed error
    redirectToSignup("Database Error (" . $e->getCode() . "): " . $e->getMessage());
}
?>