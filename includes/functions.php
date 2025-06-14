
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
    // Simple activity logging - could be enhanced later
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
 * Add project rating
 */
function add_project_rating($project_id, $rating, $user_name = null, $user_email = null, $comment = null) {
    global $pdo;

    try {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5 stars'];
        }

        // Get IP address with proper fallback
        $ip_address = '';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip_address = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        // Check for existing rating by IP or email
        $check_conditions = ["project_id = ? AND ip_address = ?"];
        $check_params = [$project_id, $ip_address];
        
        if (!empty($user_email)) {
            $check_conditions[] = "project_id = ? AND user_email = ?";
            $check_params[] = $project_id;
            $check_params[] = $user_email;
        }
        
        $check_sql = "SELECT id FROM project_ratings WHERE (" . implode(") OR (", $check_conditions) . ")";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute($check_params);

        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You have already rated this project'];
        }

        $pdo->beginTransaction();

        // Insert the rating
        $stmt = $pdo->prepare("INSERT INTO project_ratings (project_id, rating, user_name, user_email, comment, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$project_id, $rating, $user_name, $user_email, $comment, $ip_address]);

        // Update project average rating
        update_project_average_rating($project_id);

        $pdo->commit();
        return ['success' => true, 'message' => 'Thank you! Your rating has been submitted successfully'];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Add rating error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit rating. Please try again.'];
    }
}

/**
 * Update project average rating
 */
function update_project_average_rating($project_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM project_ratings WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $result = $stmt->fetch();

        $avg_rating = round($result['avg_rating'], 2);
        $total_ratings = $result['total_ratings'];

        $stmt = $pdo->prepare("UPDATE projects SET average_rating = ?, total_ratings = ? WHERE id = ?");
        $stmt->execute([$avg_rating, $total_ratings, $project_id]);

        return $avg_rating;

    } catch (Exception $e) {
        error_log("Update average rating error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get project ratings
 */
function get_project_ratings($project_id, $limit = 5) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM project_ratings WHERE project_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$project_id, $limit]);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Get project ratings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate star rating HTML
 */
function generate_star_rating($rating, $total_ratings = 0, $show_count = true) {
    $stars = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $stars .= '<i class="fas fa-star text-yellow-400"></i>';
    }

    // Half star
    if ($half_star) {
        $stars .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    }

    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars .= '<i class="far fa-star text-gray-300 dark:text-gray-600"></i>';
    }

    $count_text = $show_count ? " ({$total_ratings})" : '';

    return "<div class=\"flex items-center space-x-1\">
                <div class=\"flex space-x-1\">{$stars}</div>
                <span class=\"text-sm text-gray-600 dark:text-gray-400\">" . number_format($rating, 1) . "{$count_text}</span>
            </div>";
}

/**
 * Create project steps based on department type
 */
function create_project_steps($project_id, $department_name) {
    global $pdo;

    try {
        // Check if steps already exist for this project
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

        $stmt = $pdo->prepare("INSERT INTO project_steps (project_id, step_number, step_name, description, status) VALUES (?, ?, ?, ?, 'pending')");

        foreach ($steps_template as $index => $step) {
            $stmt->execute([
                $project_id,
                $index + 1,
                $step['step_name'],
                $step['description']
            ]);
        }

        // Update total_steps in projects table
        $total_steps = count($steps_template);
        $stmt = $pdo->prepare("UPDATE projects SET total_steps = ? WHERE id = ?");
        $stmt->execute([$total_steps, $project_id]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Project steps created successfully'];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create project steps error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create project steps'];
    }
}

/**
 * Calculate progress based on step states (planning=0%, in_progress=50%, completed=100%)
 */
function calculate_project_progress($project_id) {
    global $pdo;

    try {
        // Get all steps for this project
        $stmt = $pdo->prepare("SELECT status FROM project_steps WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $steps = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($steps)) {
            return 0;
        }

        $total_possible_points = count($steps) * 2; // Each step can contribute max 2 points (in_progress=1, completed=2)
        $earned_points = 0;

        foreach ($steps as $status) {
            switch ($status) {
                case 'pending':
                case 'planning':
                    $earned_points += 0; // 0% contribution
                    break;
                case 'in_progress':
                    $earned_points += 1; // 50% contribution (1 out of 2 points)
                    break;
                case 'completed':
                    $earned_points += 2; // 100% contribution (2 out of 2 points)
                    break;
            }
        }

        return round(($earned_points / $total_possible_points) * 100, 2);

    } catch (Exception $e) {
        error_log("Calculate progress error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Update project progress based on steps
 */
function update_project_progress($project_id) {
    global $pdo;

    try {
        $progress = calculate_project_progress($project_id);

        // Determine project status based on progress and step states
        $stmt = $pdo->prepare("SELECT status FROM project_steps WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $step_statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $project_status = 'planning'; // default

        if ($progress == 100) {
            $project_status = 'completed';
        } elseif ($progress > 0) {
            // Check if any step is in progress or completed
            $has_active_steps = false;
            foreach ($step_statuses as $status) {
                if ($status === 'in_progress' || $status === 'completed') {
                    $has_active_steps = true;
                    break;
                }
            }
            $project_status = $has_active_steps ? 'ongoing' : 'planning';
        }

        $stmt = $pdo->prepare("UPDATE projects SET progress_percentage = ?, status = ? WHERE id = ?");
        $stmt->execute([$progress, $project_status, $project_id]);

        return $progress;

    } catch (Exception $e) {
        error_log("Update progress error: " . $e->getMessage());
        return false;
    }
}
?>
