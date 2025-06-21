<?php
require_once __DIR__ . '/../config.php';

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }

    // Check if token has expired (1 hour)
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

require_once __DIR__ . '/project_steps_templates.php';

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Format date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format currency
 */
function format_currency($amount) {
    return 'KES ' . number_format($amount, 2);
}

/**
 * Get project status badge class
 */
function get_status_badge_class($status) {
    $classes = [
        'planning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'ongoing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'suspended' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Get progress bar color class
 */
function get_progress_color_class($percentage) {
    if ($percentage >= 80) return 'bg-green-500';
    if ($percentage >= 60) return 'bg-blue-500';
    if ($percentage >= 40) return 'bg-yellow-500';
    if ($percentage >= 20) return 'bg-orange-500';
    return 'bg-red-500';
}

/**
 * Fetch all projects with filters (for public use - only published)
 */
function get_projects($filters = []) {
    global $pdo;
    $sql = "SELECT p.*, d.name as department_name, w.name as ward_name, 
                   sc.name as sub_county_name, c.name as county_name
            FROM projects p
            JOIN departments d ON p.department_id = d.id
            JOIN wards w ON p.ward_id = w.id
            JOIN sub_counties sc ON p.sub_county_id = sc.id
            JOIN counties c ON p.county_id = c.id
            WHERE p.visibility = 'published'";
    $params = [];
    if (!empty($filters['search'])) {
        $sql .= " AND (p.project_name LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['department'])) {
        $sql .= " AND p.department_id = ?";
        $params[] = $filters['department'];
    }
    if (!empty($filters['ward'])) {
        $sql .= " AND p.ward_id = ?";
        $params[] = $filters['ward'];
    }
    if (!empty($filters['sub_county'])) {
        $sql .= " AND p.sub_county_id = ?";
        $params[] = $filters['sub_county'];
    }
    if (!empty($filters['year'])) {
        $sql .= " AND p.project_year = ?";
        $params[] = $filters['year'];
    }
    $sql .= " ORDER BY p.created_at DESC";
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = $filters['limit'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Fetch all projects with filters (for admin use - all visibility)
 */
function get_all_projects($filters = []) {
    global $pdo;
    $sql = "SELECT p.*, d.name as department_name, w.name as ward_name, 
                   sc.name as sub_county_name, c.name as county_name
            FROM projects p
            JOIN departments d ON p.department_id = d.id
            JOIN wards w ON p.ward_id = w.id
            JOIN sub_counties sc ON p.sub_county_id = sc.id
            JOIN counties c ON p.county_id = c.id
            WHERE 1=1";
    $params = [];
    if (!empty($filters['search'])) {
        $sql .= " AND (p.project_name LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['department'])) {
        $sql .= " AND p.department_id = ?";
        $params[] = $filters['department'];
    }
    if (!empty($filters['ward'])) {
        $sql .= " AND p.ward_id = ?";
        $params[] = $filters['ward'];
    }
    if (!empty($filters['sub_county'])) {
        $sql .= " AND p.sub_county_id = ?";
        $params[] = $filters['sub_county'];
    }
    if (!empty($filters['year'])) {
        $sql .= " AND p.project_year = ?";
        $params[] = $filters['year'];
    }
    if (!empty($filters['visibility'])) {
        $sql .= " AND p.visibility = ?";
        $params[] = $filters['visibility'];
    }
    $sql .= " ORDER BY p.created_at DESC";
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = $filters['limit'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single project by ID
 */
function get_project_by_id($id) {
    global $pdo;
    $sql = "SELECT p.*, d.name as department_name, w.name as ward_name, 
                   sc.name as sub_county_name, c.name as county_name
            FROM projects p
            JOIN departments d ON p.department_id = d.id
            JOIN wards w ON p.ward_id = w.id
            JOIN sub_counties sc ON p.sub_county_id = sc.id
            JOIN counties c ON p.county_id = c.id
            WHERE p.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get departments
 */
function get_departments() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get counties
 */
function get_counties() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM counties ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get sub-counties by county
 */
function get_sub_counties($county_id = null) {
    global $pdo;
    if ($county_id) {
        $stmt = $pdo->prepare("SELECT * FROM sub_counties WHERE county_id = ? ORDER BY name");
        $stmt->execute([$county_id]);
    } else {
        $stmt = $pdo->query("SELECT sc.*, c.name as county_name FROM sub_counties sc JOIN counties c ON sc.county_id = c.id ORDER BY c.name, sc.name");
    }
    return $stmt->fetchAll();
}

/**
 * Get wards by sub-county
 */
function get_wards($sub_county_id = null) {
    global $pdo;
    if ($sub_county_id) {
        $stmt = $pdo->prepare("SELECT * FROM wards WHERE sub_county_id = ? ORDER BY name");
        $stmt->execute([$sub_county_id]);
    } else {
        $stmt = $pdo->query("SELECT w.*, sc.name as sub_county_name FROM wards w JOIN sub_counties sc ON w.sub_county_id = sc.id ORDER BY sc.name, w.name");
    }
    return $stmt->fetchAll();
}

/**
 * Get project years
 */
function get_project_years() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT project_year FROM projects ORDER BY project_year DESC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Log activity
 */
function log_activity($message, $user_id = null) {
    error_log("Activity: " . $message . " (User: " . $user_id . ")");
    return true;
}

/**
 * Get Migori projects
 */
function get_migori_projects(
    $department_filter = '',
    $status_filter = '',
    $year_filter = '',
    $search_query = '',
    $sub_county_filter = '',
    $limit = null,
    $offset = 0
) {
    global $pdo;

    $query = "SELECT p.*, d.name as department_name, sc.name as sub_county_name, 
                     w.name as ward_name, c.name as county_name,
                     COALESCE(AVG(r.rating), 0) as average_rating,
                     COUNT(DISTINCT r.id) as total_ratings
              FROM projects p 
              LEFT JOIN departments d ON p.department_id = d.id
              LEFT JOIN sub_counties sc ON p.sub_county_id = sc.id  
              LEFT JOIN wards w ON p.ward_id = w.id
              LEFT JOIN counties c ON p.county_id = c.id
              LEFT JOIN project_ratings r ON p.id = r.project_id
              WHERE p.visibility = 'published'";

    $params = [];

    if (!empty($department_filter)) {
        $query .= " AND p.department_id = ?";
        $params[] = $department_filter;
    }

    if (!empty($status_filter)) {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($year_filter)) {
        $query .= " AND p.project_year = ?";
        $params[] = $year_filter;
    }

    if (!empty($sub_county_filter)) {
        $query .= " AND p.sub_county_id = ?";
        $params[] = $sub_county_filter;
    }

    if (!empty($search_query)) {
        $query .= " AND (p.project_name LIKE ? OR sc.name LIKE ? OR p.project_year LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $query .= " GROUP BY p.id ORDER BY p.created_at DESC";

    if ($limit !== null) {
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_total_projects_count(
    $department_filter = '',
    $status_filter = '',
    $year_filter = '',
    $search_query = '',
    $sub_county_filter = ''
) {
    global $pdo;

    $query = "SELECT COUNT(DISTINCT p.id) 
              FROM projects p 
              LEFT JOIN departments d ON p.department_id = d.id
              LEFT JOIN sub_counties sc ON p.sub_county_id = sc.id  
              LEFT JOIN wards w ON p.ward_id = w.id
              LEFT JOIN counties c ON p.county_id = c.id
              WHERE p.visibility = 'published'";

    $params = [];

    if (!empty($department_filter)) {
        $query .= " AND p.department_id = ?";
        $params[] = $department_filter;
    }

    if (!empty($status_filter)) {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($year_filter)) {
        $query .= " AND p.project_year = ?";
        $params[] = $year_filter;
    }

    if (!empty($sub_county_filter)) {
        $query .= " AND p.sub_county_id = ?";
        $params[] = $sub_county_filter;
    }

    if (!empty($search_query)) {
        $query .= " AND (p.project_name LIKE ? OR sc.name LIKE ? OR p.project_year LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Get Migori sub-counties only
 */
function get_migori_sub_counties() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT sc.* FROM sub_counties sc 
                          JOIN counties c ON sc.county_id = c.id 
                          WHERE c.name = 'Migori' ORDER BY sc.name");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get project statistics for Migori County
 */
function get_project_stats() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) as planning
                          FROM projects p
                          WHERE p.visibility = 'published'");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Get status text color class
 */
function get_status_text_class($status) {
    switch ($status) {
        case 'completed':
            return 'text-green-600 dark:text-green-400';
        case 'ongoing':
            return 'text-blue-600 dark:text-blue-400';
        case 'planning':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'suspended':
            return 'text-red-600 dark:text-red-400';
        case 'cancelled':
            return 'text-gray-600 dark:text-gray-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get feedback status badge class
 */
function get_feedback_status_badge_class($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'reviewed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'responded' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Upload file helper
 */
function handle_file_upload($file, $allowed_types = ['csv', 'xlsx']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Add project comment to feedback table
 */
function add_project_comment($project_id, $comment_text, $user_name, $user_email = null, $parent_id = 0) {
    global $pdo;

    try {
        if (empty($project_id) || empty($comment_text) || empty($user_name)) {
            error_log("Comment validation failed - Project ID: $project_id, User: '$user_name', Comment length: " . strlen($comment_text));
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $subject = $parent_id > 0 ? "Reply to comment" : "Project Comment";

        $sql = "INSERT INTO feedback (project_id, citizen_name, citizen_email, subject, message, status, parent_comment_id, user_ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);

        $result = $stmt->execute([$project_id, $user_name, $user_email, $subject, $comment_text, $parent_id, $user_ip, $user_agent]);

        if ($result) {
            $comment_id = $pdo->lastInsertId();
            error_log("Comment successfully inserted with ID: $comment_id for project: $project_id by user: $user_name");
            log_activity("New comment submitted for project ID: $project_id by $user_name from IP: $user_ip");
            return ['success' => true, 'message' => 'Comment submitted successfully and is pending approval'];
        } else {
            error_log("Failed to insert comment - SQL error: " . implode(', ', $stmt->errorInfo()));
            return ['success' => false, 'message' => 'Database error: Failed to save comment'];
        }

    } catch (Exception $e) {
        error_log("Add comment error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit comment. Please try again.'];
    }
}

/**
 * Get project comments from feedback table with nested replies
 */
function get_project_comments($project_id) {
    global $pdo;

    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';

        $sql = "SELECT f.*, 
                       a.name as admin_name,
                       a.email as admin_email,
                       CASE WHEN f.user_ip = ? AND f.status = 'pending' THEN 1 ELSE 0 END as is_user_pending,
                       CASE WHEN f.subject = 'Admin Response' OR f.citizen_name = '' OR f.citizen_name IS NULL THEN 1 ELSE 0 END as is_admin_comment
                FROM feedback f
                LEFT JOIN admins a ON f.responded_by = a.id 
                WHERE f.project_id = ? 
                AND (f.status IN ('approved', 'reviewed', 'responded') OR (f.status = 'pending' AND f.user_ip = ?))
                AND (f.parent_comment_id IS NULL OR f.parent_comment_id = 0)
                ORDER BY f.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_ip, $project_id, $user_ip]);
        $main_comments = $stmt->fetchAll();

        $comments_with_responses = [];

        foreach ($main_comments as $comment) {
            $comment_array = $comment;

            // Get admin response if exists
            if (!empty($comment['admin_response']) && !empty($comment['responded_by']) && $comment['status'] === 'responded') {
                $admin_comment = [
                    'id' => 'admin_' . $comment['id'],
                    'project_id' => $comment['project_id'],
                    'citizen_name' => '',
                    'citizen_email' => '',
                    'subject' => 'Admin Response',
                    'message' => $comment['admin_response'],
                    'status' => 'approved',
                    'parent_comment_id' => $comment['id'],
                    'user_ip' => '',
                    'user_agent' => '',
                    'created_at' => $comment['updated_at'],
                    'admin_name' => $comment['admin_name'] ?? 'Admin',
                    'admin_email' => $comment['admin_email'],
                    'is_user_pending' => 0,
                    'is_admin_comment' => 1,
                    'responded_by' => $comment['responded_by']
                ];

                $comment_array['admin_response_comment'] = $admin_comment;
            }

            $comment_array['replies'] = get_comment_replies_limited($comment['id'], 3);
            $comment_array['total_replies'] = get_comment_replies_count($comment['id']);

            $comments_with_responses[] = $comment_array;
        }

        return $comments_with_responses;

    } catch (Exception $e) {
        error_log("Get project comments error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get limited replies for a comment
 */
function get_comment_replies_limited($parent_id, $limit = 3, $user_ip = null) {
    global $pdo;

    try {
        if (!$user_ip) {
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';
        }

        $sql = "SELECT f.*, 
                       a.name as admin_name,
                       a.email as admin_email,
                       CASE WHEN f.user_ip = ? AND f.status = 'pending' THEN 1 ELSE 0 END as is_user_pending,
                       CASE WHEN f.subject = 'Admin Response' OR f.citizen_name = '' OR f.citizen_name IS NULL THEN 1 ELSE 0 END as is_admin_comment
                FROM feedback f
                LEFT JOIN admins a ON f.responded_by = a.id 
                WHERE f.parent_comment_id = ? 
                AND (f.status IN ('approved', 'reviewed', 'responded') OR (f.status = 'pending' AND f.user_ip = ?))
                ORDER BY f.created_at ASC
                LIMIT ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_ip, $parent_id, $user_ip, $limit]);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Get comment replies limited error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of replies for a comment
 */
function get_comment_replies_count($parent_id) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) FROM feedback 
                WHERE parent_comment_id = ? 
                AND status IN ('approved', 'reviewed', 'responded')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parent_id]);
        return $stmt->fetchColumn();

    } catch (Exception $e) {
        error_log("Get comment replies count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get approved comments count for a project
 */
function get_approved_comments_count($project_id) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(*) FROM feedback 
                WHERE project_id = ? 
                AND status IN ('approved', 'reviewed', 'responded')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$project_id]);
        return $stmt->fetchColumn();

    } catch (Exception $e) {
        error_log("Get approved comments count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get replies for a comment from feedback table
 */
function get_comment_replies($parent_id) {
    global $pdo;

    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';

        $stmt = $pdo->prepare("SELECT f.*, 
                                     a.name as admin_name,
                                     a.email as admin_email,
                                     f.responded_by IS NOT NULL as is_admin_comment,
                                     CASE WHEN f.user_ip = ? AND f.status = 'pending' THEN 1 ELSE 0 END as is_user_pending
                              FROM feedback f 
                              LEFT JOIN admins a ON f.responded_by = a.id
                              WHERE f.parent_comment_id = ? 
                              AND (f.status IN ('approved', 'reviewed', 'responded') OR (f.status = 'pending' AND f.user_ip = ?))
                              ORDER BY f.created_at ASC");
        $stmt->execute([$user_ip, $parent_id, $user_ip]);
        $replies = $stmt->fetchAll();

        $processed_replies = [];
        foreach ($replies as $reply) {
            $processed_replies[] = [
                'id' => $reply['id'],
                'user_name' => $reply['is_admin_comment'] ? $reply['admin_name'] : $reply['citizen_name'],
                'comment_text' => $reply['message'],
                'created_at' => $reply['created_at'],
                'admin_name' => $reply['admin_name'],
                'is_admin_comment' => $reply['is_admin_comment'],
                'user_email' => $reply['citizen_email'],
                'status' => $reply['status'],
                'is_user_pending' => $reply['is_user_pending'],
                'responded_by' => $reply['responded_by']
            ];
        }

        return $processed_replies;

    } catch (Exception $e) {
        error_log("Get comment replies error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get comments for admin panel with filtering from feedback table
 */
function get_comments_for_admin($filters = []) {
    global $pdo;

    try {
        $sql = "SELECT f.*, 
                       p.project_name,
                       a.name as admin_name,
                       a.username as admin_username,
                       f.responded_by IS NOT NULL as is_admin_comment,
                       parent.citizen_name as parent_user_name
                FROM feedback f
                LEFT JOIN projects p ON f.project_id = p.id
                LEFT JOIN admins a ON f.responded_by = a.id
                LEFT JOIN feedback parent ON f.parent_comment_id = parent.id
                WHERE (f.subject LIKE '%comment%' OR f.subject LIKE '%Comment%')";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND f.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['project_id'])) {
            $sql .= " AND f.project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (f.message LIKE ? OR f.citizen_name LIKE ? OR p.project_name LIKE ?)";
            $search_param = '%' . $filters['search'] . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Get comments for admin error: " . $e->getMessage());
        return [];
    }
}

/**
 * Approve or reject comment in feedback table
 */
function moderate_comment($comment_id, $status, $admin_id = null) {
    global $pdo;

    try {
        if (!in_array($status, ['approved', 'rejected'])) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $stmt = $pdo->prepare("UPDATE feedback SET status = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $admin_id, $comment_id]);

        return ['success' => true, 'message' => 'Comment ' . $status . ' successfully'];

    } catch (Exception $e) {
        error_log("Moderate comment error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to moderate comment'];
    }
}

/**
 * Create project steps based on department type
 */
function create_project_steps($project_id, $department_name) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_steps WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $existing_steps = $stmt->fetchColumn();

        if ($existing_steps > 0) {
            return ['success' => false, 'message' => 'Project already has steps defined'];
        }

        $steps_template = get_default_project_steps($department_name);

        if (empty($steps_template)) {
            return ['success' => false, 'message' => 'No step template found for this department'];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO project_steps (project_id, step_name, step_description, step_order, status, expected_duration_days) VALUES (?, ?, ?, ?, 'pending', ?)");

        foreach ($steps_template as $index => $step) {
            $stmt->execute([
                $project_id,
                $step['name'],
                $step['description'],
                $index + 1,
                $step['duration'] ?? 30
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Project steps created successfully'];

    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Create project steps error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create project steps'];
    }
}

/**
 * Get project steps
 */
function get_project_steps($project_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM project_steps WHERE project_id = ? ORDER BY order_number");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * Calculate project progress percentage
 */
function calculate_project_progress($project_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 
                            COUNT(*) as total_steps,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_steps
                          FROM project_steps WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $result = $stmt->fetch();

    if ($result['total_steps'] == 0) return 0;
    return round(($result['completed_steps'] / $result['total_steps']) * 100);
}

/**
 * Update project step status
 */
function update_step_status($step_id, $status, $completion_date = null) {
    global $pdo;

    try {
        if ($completion_date === null && $status === 'completed') {
            $completion_date = date('Y-m-d');
        }

        $sql = "UPDATE project_steps SET status = ?, completion_date = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$status, $completion_date, $step_id]);

        if ($result) {
            $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
            $stmt->execute([$step_id]);
            $project_id = $stmt->fetchColumn();

            $progress = calculate_project_progress($project_id);
            $stmt = $pdo->prepare("UPDATE projects SET progress_percentage = ? WHERE id = ?");
            $stmt->execute([$progress, $project_id]);
        }

        return $result;
    } catch (Exception $e) {
        error_log("Update step status error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get more replies for a comment with offset
 */
function get_more_comment_replies($parent_id, $offset = 0, $limit = 5) {
    global $pdo;

    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';

        $sql = "SELECT f.*, 
                       a.name as admin_name,
                       a.email as admin_email,
                       CASE WHEN f.user_ip = ? AND f.status = 'pending' THEN 1 ELSE 0 END as is_user_pending,
                       CASE WHEN f.subject = 'Admin Response' OR f.citizen_name = '' OR f.citizen_name IS NULL THEN 1 ELSE 0 END as is_admin_comment
                FROM feedback f
                LEFT JOIN admins a ON f.responded_by = a.id 
                WHERE f.parent_comment_id = ? 
                AND (f.status IN ('approved', 'reviewed', 'responded') OR (f.status = 'pending' AND f.user_ip = ?))
                ORDER BY f.created_at ASC
                LIMIT ? OFFSET ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_ip, $parent_id, $user_ip, $limit, $offset]);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Get more comment replies error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add prepared response
 */
function add_prepared_response($response_text) {
    global $pdo;

    try {
        $sql = "INSERT INTO feedback_templates (response_text, created_at) VALUES (?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$response_text]);

        if ($result) {
            return ['success' => true, 'message' => 'Prepared response added successfully'];
        } else {
            error_log("Failed to insert prepared response - SQL error: " . implode(', ', $stmt->errorInfo()));
            return ['success' => false, 'message' => 'Database error: Failed to save prepared response'];
        }
    } catch (Exception $e) {
        error_log("Add prepared response error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add prepared response. Please try again.'];
    }
}

/**
 * Delete prepared response
 */
function delete_prepared_response($response_id) {
    global $pdo;

    try {
        $sql = "DELETE FROM feedback_templates WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$response_id]);

        if ($result) {
            return ['success' => true, 'message' => 'Prepared response deleted successfully'];
        } else {
            error_log("Failed to delete prepared response - SQL error: " . implode(', ', $stmt->errorInfo()));
            return ['success' => false, 'message' => 'Database error: Failed to delete prepared response'];
        }
    } catch (Exception $e) {
        error_log("Delete prepared response error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete prepared response. Please try again.'];
    }
}

/**
 * Update prepared response
 */
function update_prepared_response($response_id, $response_text) {
    global $pdo;

    try {
        $sql = "UPDATE feedback_templates SET response_text = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$response_text, $response_id]);

        if ($result) {
            return ['success' => true, 'message' => 'Prepared response updated successfully'];
        } else {
            error_log("Failed to update prepared response - SQL error: " . implode(', ', $stmt->errorInfo()));
            return ['success' => false, 'message' => 'Database error: Failed to update prepared response'];
        }
    } catch (Exception $e) {
        error_log("Update prepared response error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update prepared response. Please try again.'];
    }
}

/**
 * Update project progress based on steps
 */
function update_project_progress($project_id) {
    global $pdo;

    try {
        // Get step statistics
        $stmt = $pdo->prepare("SELECT 
                                COUNT(*) as total_steps,
                                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_steps,
                                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_steps
                              FROM project_steps WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $step_data = $stmt->fetch();

        $total_steps = $step_data['total_steps'] ?? 0;
        $completed_steps = $step_data['completed_steps'] ?? 0;
        $in_progress_steps = $step_data['in_progress_steps'] ?? 0;

        // Calculate progress percentage with weighted values
        // completed = 100%, in_progress = 50%, pending = 0%
        if ($total_steps > 0) {
            $weighted_progress = ($completed_steps * 100) + ($in_progress_steps * 50);
            $progress_percentage = round($weighted_progress / ($total_steps * 100) * 100);
        } else {
            $progress_percentage = 0;
        }

        // Determine project status
        $new_status = 'planning'; // default
        if ($completed_steps == $total_steps && $total_steps > 0) {
            $new_status = 'completed';
        } elseif ($completed_steps > 0 || $in_progress_steps > 0) {
            $new_status = 'ongoing';
        }

        // Update project
        $stmt = $pdo->prepare("UPDATE projects SET 
                              progress_percentage = ?, 
                              status = ?,
                              completed_steps = ?,
                              total_steps = ?,
                              updated_at = NOW()
                              WHERE id = ?");
        $stmt->execute([$progress_percentage, $new_status, $completed_steps, $total_steps, $project_id]);

        return $progress_percentage;

    } catch (Exception $e) {
        error_log("Update project progress error: " . $e->getMessage());
        return false;
    }
}