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
        // Redirect to login page
        $login_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../login.php' : 'login.php';
        header('Location: ' . $login_url);
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout_user();
        $login_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../login.php?timeout=1' : 'login.php?timeout=1';
        header('Location: ' . $login_url);
        exit;
    }

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate'] > 300)) {
        if (!headers_sent()) {
            session_regenerate_id(true);
            $_SESSION['last_regenerate'] = time();
        }
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
            error_log("Security Alert: User agent changed for admin ID " . ($_SESSION['admin_id'] ?? 'unknown'));
            logout_user();
            $redirect_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin/?error=security' : 'admin/?error=security';
            header('Location: ' . $redirect_url);
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
                // header('Location: index.php?error=ip_change');
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
        if (headers_sent()) {
            error_log("Headers already sent, cannot regenerate session ID");
        } else {
            session_regenerate_id(true);
        }

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

/**
 * Check if user has required role
 */
function has_role($required_role) {
    if (!is_logged_in()) {
        return false;
    }

    $current_role = $_SESSION['admin_role'];

    // Role hierarchy: super_admin > admin > viewer
    $roles = ['viewer' => 1, 'admin' => 2, 'super_admin' => 3];

    if (!isset($roles[$current_role]) || !isset($roles[$required_role])) {
        return false;
    }

    return $roles[$current_role] >= $roles[$required_role];
}

/**
 * Require specific role or higher
 */
function require_role($required_role) {
    require_admin(); // First ensure they are logged in

    if (!has_role($required_role)) {
        error_log("Access denied: User ID " . ($_SESSION['admin_id'] ?? 'unknown') . " with role " . ($_SESSION['admin_role'] ?? 'unknown') . " attempted to access resource requiring " . $required_role);

        // Determine correct redirect path based on current location
        $redirect_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin/?error=insufficient_permissions' : 'admin/?error=insufficient_permissions';
        header('Location: ' . $redirect_url);
        exit;
    }

    // Additional validation - ensure role hasn't been tampered with
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $current_role = $stmt->fetchColumn();

        if (!$current_role || $current_role !== $_SESSION['admin_role']) {
            error_log("Security Alert: Role mismatch for user ID " . $_SESSION['admin_id'] . ". Session role: " . $_SESSION['admin_role'] . ", DB role: " . $current_role);
            logout_user();
            $redirect_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin/?error=security' : 'admin/?error=security';
            header('Location: ' . $redirect_url);
            exit;
        }
    } catch (Exception $e) {
        error_log("Database error during role validation: " . $e->getMessage());
        logout_user();
        $redirect_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../admin/?error=security' : 'admin/?error=security';
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Check if user can manage specific project (for admin role)
 */
function can_manage_project($project_id) {
    if (!is_logged_in()) {
        return false;
    }

    $current_admin = get_current_admin();

    // Super admin can manage all projects
    if ($current_admin['role'] === 'super_admin') {
        return true;
    }

    // Admin can only manage their own projects
    if ($current_admin['role'] === 'admin') {
        global $pdo;
        $stmt = $pdo->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        return $project && $project['created_by'] == $current_admin['id'];
    }

    // Viewers cannot manage projects
    return false;
}

// Authentication system now uses only database admin users
// Default admin credentials are already in the database via SQL import

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login");
        exit();
    }
}