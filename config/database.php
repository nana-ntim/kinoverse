<?php
// Database credentials
// $host = 'localhost';
// $dbname = 'webtech_fall2024_asamoah_ntim';
// $dbusername = 'asamoah.ntim';
// $dbpassword = 'Frimpomaah123#';

$host = 'localhost';
$dbname = 'kinoverse';
$dbusername = 'root';
$dbpassword = '';

try {
    // Create PDO instance
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbusername,
        $dbpassword
    );
    
    // Configure PDO error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Log the error for administrators
    error_log("Database Connection Error: " . $e->getMessage());
    
    // User-friendly error message
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}
?>