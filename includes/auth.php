<?php
require_once __DIR__ . '/../config.php';

/**
 * Check if user is logged in
 */
function is_logged_in() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['admin_logged_in']) && 
           $_SESSION['admin_logged_in'] === true && 
           isset($_SESSION['admin_id']) && 
           !empty($_SESSION['admin_id']);
}

/**
 * Require admin authentication
 */
function require_admin() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!is_logged_in()) {
        // Redirect to login page, adjusting path for admin subdirectory
        $login_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin.php' : 'admin.php';
        header('Location: ' . $login_url);
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout_user();
        $login_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin.php?timeout=1' : 'admin.php?timeout=1';
        header('Location: ' . $login_url);
        exit;
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate'] > 300)) {
        session_regenerate_id(true);
        $_SESSION['last_regenerate'] = time();
    }
    
    $_SESSION['last_activity'] = time();
    
    // Additional security checks
    check_user_agent();
    check_ip_consistency();
}

/**
 * Check user agent consistency
 */
function check_user_agent() {
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            logout_user();
            header('Location: admin.php?error=security');
            exit;
        }
    } else {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
}

/**
 * Check IP consistency (optional - can be disabled for dynamic IPs)
 */
function check_ip_consistency() {
    if (isset($_SESSION['ip_address'])) {
        // Allow for some flexibility with dynamic IPs by checking subnet
        $current_ip = $_SERVER['REMOTE_ADDR'];
        $session_ip = $_SESSION['ip_address'];
        
        // Only enforce for non-local IPs
        if ($current_ip !== '127.0.0.1' && $session_ip !== '127.0.0.1') {
            $current_subnet = substr($current_ip, 0, strrpos($current_ip, '.'));
            $session_subnet = substr($session_ip, 0, strrpos($session_ip, '.'));
            
            if ($current_subnet !== $session_subnet) {
                error_log("IP address changed from {$session_ip} to {$current_ip} for user " . $_SESSION['admin_id']);
                // Uncomment below to enforce strict IP checking
                // logout_user();
                // header('Location: admin.php?error=ip_change');
                // exit;
            }
        }
    } else {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Login user
 */
function login_user($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM admins WHERE email = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['email'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regenerate'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW(), last_ip = ? WHERE id = ?");
        $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logout_user() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for messages
    session_start();
    session_regenerate_id(true);
}

/**
 * Get current admin user
 */
function get_current_admin() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'name' => $_SESSION['admin_name'],
        'role' => $_SESSION['admin_role']
    ];
}

// Authentication system now uses only database admin users
// Default admin credentials are already in the database via SQL import
?>
