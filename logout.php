<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity if user is logged in
if (isset($_SESSION['admin']) && $_SESSION['admin']) {
    $current_admin = get_current_admin();
    if ($current_admin) {
        log_activity("Admin logged out", $current_admin['id']);
    }
}

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index?logged_out=1");
exit();
?>
