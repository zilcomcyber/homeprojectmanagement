<?php
// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Database configuration
$host = 'localhost';
$dbname = 'project_manager';
$username = 'root';
$password = '';

// App configuration
define('APP_NAME', 'Project Tracker');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Security settings
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application constants
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', '/homeprojectmanagement/'); 