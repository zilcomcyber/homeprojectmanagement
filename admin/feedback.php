<?php
$page_title = "Community Feedback Management";
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_role('admin');
$current_admin = get_current_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'message' => 'Invalid action'];
    switch ($action) {
        case 'respond':
            $result = respond_to_feedback($_POST);
            break;
        case 'approve':
            $result = approve_feedback($_POST['feedback_id']);
            break;
        case 'reject':
            $result = reject_feedback($_POST['feedback_id']);
            break;
        case 'delete':
            $result = delete_feedback($_POST['feedback_id']);
            break;
        case 'mark_spam':
            $result = mark_as_spam($_POST['feedback_id']);
            break;
    }
    echo json_encode($result);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'respond':
                $result = respond_to_feedback($_POST);
                break;
            case 'approve':
                $result = approve_feedback($_POST['feedback_id']);
                break;
            case 'reject':
                $result = reject_feedback($_POST['feedback_id']);
                break;
            case 'delete':
                $result = delete_feedback($_POST['feedback_id']);
                break;
            case 'mark_spam':
                $result = mark_as_spam($_POST['feedback_id']);
                break;
        }
        if (isset($result)) {
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
$status = $_GET['status'] ?? '';
$project_id = $_GET['project_id'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$filters = array_filter([
    'status' => $status,
    'project_id' => $project_id,
    'search' => $search,
    'page' => $page,
    'per_page' => $per_page
]);
$feedback_data = get_feedback_with_pagination($filters);
$feedback_list = $feedback_data['data'];
$total_feedback = $feedback_data['total'];
$total_pages = ceil($total_feedback / $per_page);
$projects = get_projects();
$stats = get_feedback_statistics();
function get_feedback_with_pagination($filters = []) {
    global $pdo;
    $page = $filters['page'] ?? 1;
    $per_page = $filters['per_page'] ?? 20;
    $offset = ($page - 1) * $per_page;
    $sql = "SELECT f.*, p.project_name, p.department_id, d.name as department_name,
                   a.name as responded_by_name
            FROM feedback f
            JOIN projects p ON f.project_id = p.id
            JOIN departments d ON p.department_id = d.id
            LEFT JOIN admins a ON f.responded_by = a.id
            WHERE 1=1";
    $count_sql = "SELECT COUNT(DISTINCT f.id)
                  FROM feedback f
                  JOIN projects p ON f.project_id = p.id
                  JOIN departments d ON p.department_id = d.id
                  WHERE 1=1";
    $params = [];
    if (!empty($filters['status'])) {
        $sql .= " AND f.status = ?";
        $count_sql .= " AND f.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['project_id'])) {
        $sql .= " AND f.project_id = ?";
        $count_sql .= " AND f.project_id = ?";
        $params[] = $filters['project_id'];
    }
    if (!empty($filters['search'])) {
        $sql .= " AND (f.subject LIKE ? OR f.message LIKE ? OR f.citizen_name LIKE ? OR f.citizen_email LIKE ?)";
        $count_sql .= " AND (f.subject LIKE ? OR f.message LIKE ? OR f.citizen_name LIKE ? OR f.citizen_email LIKE ?)";
        $search_param = '%' . $filters['search'] . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    $sql .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total / $per_page)
    ];
}
function get_feedback_statistics() {
    global $pdo;
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded,
                SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week
            FROM feedback";
    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}
function respond_to_feedback($data) {
    global $pdo, $current_admin;
    try {
        $feedback_id = intval($data['feedback_id']);
        $response = sanitize_input($data['admin_response']);
        if (empty($response)) {
            return ['success' => false, 'message' => 'Response cannot be empty'];
        }
        $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch();
        if (!$feedback) {
            return ['success' => false, 'message' => 'Feedback not found'];
        }
        $sql = "UPDATE feedback SET 
                admin_response = ?, 
                status = 'responded', 
                responded_by = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$response, $current_admin['id'], $feedback_id])) {
            return ['success' => true, 'message' => 'Response sent successfully'];
        }
        return ['success' => false, 'message' => 'Failed to save response'];
    } catch (Exception $e) {
        error_log("Feedback response error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while saving the response'];
    }
}
function approve_feedback($feedback_id) {
    global $pdo, $current_admin;
    try {
        $feedback_id = intval($feedback_id);
        $stmt = $pdo->prepare("UPDATE feedback SET status = 'approved', updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$feedback_id])) {
            return ['success' => true, 'message' => 'Feedback approved successfully'];
        }
        return ['success' => false, 'message' => 'Failed to approve feedback'];
    } catch (Exception $e) {
        error_log("Approve feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while approving feedback'];
    }
}
function reject_feedback($feedback_id) {
    global $pdo, $current_admin;
    try {
        $feedback_id = intval($feedback_id);
        $stmt = $pdo->prepare("UPDATE feedback SET status = 'rejected', updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$feedback_id])) {
            return ['success' => true, 'message' => 'Feedback rejected successfully'];
        }
        return ['success' => false, 'message' => 'Failed to reject feedback'];
    } catch (Exception $e) {
        error_log("Reject feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while rejecting feedback'];
    }
}
function delete_feedback($feedback_id) {
    global $pdo, $current_admin;
    try {
        $feedback_id = intval($feedback_id);
        $stmt = $pdo->prepare("SELECT subject FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch();
        if (!$feedback) {
            return ['success' => false, 'message' => 'Feedback not found'];
        }
        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
        if ($stmt->execute([$feedback_id])) {
            return ['success' => true, 'message' => 'Feedback deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete feedback'];
    } catch (Exception $e) {
        error_log("Delete feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting feedback'];
    }
}
function mark_as_spam($feedback_id) {
    global $pdo, $current_admin;
    try {
        $feedback_id = intval($feedback_id);
        $stmt = $pdo->prepare("UPDATE feedback SET status = 'spam', updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$feedback_id])) {
            return ['success' => true, 'message' => 'Feedback marked as spam'];
        }
        return ['success' => false, 'message' => 'Failed to mark as spam'];
    } catch (Exception $e) {
        error_log("Mark spam error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while marking as spam'];
    }
}
ob_start();
?>
<style>
.status-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
.status-pending { background-color: #f59e0b; }
.status-approved { background-color: #10b981; }
.status-rejected { background-color: #ef4444; }
.status-responded { background-color: #3b82f6; }
.status-spam { background-color: #6b7280; }

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

#response-templates button:hover {
    transform: translateY(-1px);
}
</style>
<?php if (isset($success)): ?>
    <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Community Feedback Management</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage citizen feedback and respond to comments</p>
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Feedback</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Review</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['pending']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['approved']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Spam</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['spam']); ?></p>
            </div>
        </div>
    </div>
</div>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6">
        <form method="GET" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Subject, message, citizen name..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="responded" <?php echo $status === 'responded' ? 'selected' : ''; ?>>Responded</option>
                        <option value="spam" <?php echo $status === 'spam' ? 'selected' : ''; ?>>Spam</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
                    <select name="project_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?php echo $proj['id']; ?>" <?php echo $project_id == $proj['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proj['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Apply Filters
                </button>
                <a href="feedback.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Clear All
                </a>
            </div>
        </form>
    </div>
</div>
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            <?php echo number_format($total_feedback); ?> Feedback Message<?php echo $total_feedback !== 1 ? 's' : ''; ?> Found
        </h3>
    </div>
    <?php if (empty($feedback_list)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-comments text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Feedback Found</h3>
            <p class="text-gray-600 dark:text-gray-400">No citizen feedback matches your current filters.</p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($feedback_list as $feedback): ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="status-indicator status-<?php echo $feedback['status']; ?>"></span>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mr-3">
                                    <?php echo htmlspecialchars($feedback['subject']); ?>
                                </h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_feedback_status_badge_class($feedback['status']); ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-4">
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">From:</span>
                                    <p class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($feedback['citizen_name']); ?></p>
                                    <?php if (!empty($feedback['citizen_email'])): ?>
                                        <p class="text-gray-600 dark:text-gray-300 text-xs">
                                            <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($feedback['citizen_email']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Project:</span>
                                    <p class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($feedback['project_name']); ?></p>
                                    <p class="text-gray-600 dark:text-gray-300 text-xs">
                                        <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($feedback['department_name']); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Submitted:</span>
                                    <p class="text-gray-900 dark:text-white"><?php echo format_date($feedback['created_at']); ?></p>
                                </div>
                            </div>
                            <div class="mb-4">
                                <span class="font-medium text-gray-500 dark:text-gray-400 block mb-2">Message:</span>
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-gray-900 dark:text-white leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($feedback['admin_response'])): ?>
                                <div class="mb-4">
                                    <span class="font-medium text-gray-500 dark:text-gray-400 block mb-2">Admin Response:</span>
                                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 p-4 rounded-lg">
                                        <p class="text-gray-900 dark:text-white leading-relaxed">
                                            <?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?>
                                        </p>
                                        <div class="mt-3 flex items-center text-sm text-green-700 dark:text-green-300">
                                            <i class="fas fa-user-shield mr-2"></i>
                                            Responded by <?php echo htmlspecialchars($feedback['responded_by_name']); ?> 
                                            on <?php echo format_date($feedback['updated_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <div class="flex items-center space-x-2">
                                <?php if ($feedback['status'] === 'pending'): ?>
                                    <button onclick="quickApprove(<?php echo $feedback['id']; ?>)" 
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <i class="fas fa-check mr-1"></i>
                                        Approve
                                    </button>
                                    <button onclick="quickReject(<?php echo $feedback['id']; ?>)" 
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Reject
                                    </button>
                                <?php endif; ?>
                                <button onclick="showResponseForm(<?php echo $feedback['id']; ?>, '<?php echo htmlspecialchars($feedback['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($feedback['citizen_name'], ENT_QUOTES); ?>')" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-reply mr-1"></i>
                                    Respond
                                </button>
                                <div class="relative">
                                    <button onclick="toggleActionDropdown(<?php echo $feedback['id']; ?>)" 
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="action-dropdown-<?php echo $feedback['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-600">
                                        <div class="py-1">
                                            <button onclick="markAsSpam(<?php echo $feedback['id']; ?>)" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <i class="fas fa-ban text-gray-500 mr-2"></i>Mark as Spam
                                            </button>
                                            <button onclick="deleteFeedback(<?php echo $feedback['id']; ?>, '<?php echo htmlspecialchars($feedback['subject'], ENT_QUOTES); ?>')" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <i class="fas fa-trash mr-2"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing <?php echo (($page - 1) * $per_page) + 1; ?> to 
                        <?php echo min($page * $per_page, $total_feedback); ?> of 
                        <?php echo number_format($total_feedback); ?> results
                    </div>
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 border <?php echo $i === $page ? 'border-blue-500 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'; ?> rounded text-sm font-medium">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div id="responseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/5 xl:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Respond to Feedback</h3>
                <button onclick="closeResponseModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="responseForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="feedback_id" id="modal_feedback_id">
                <input type="hidden" name="ajax" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Responding to: <span id="modal_feedback_subject" class="font-semibold"></span>
                    </label>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        From: <span id="modal_citizen_name"></span>
                    </p>
                </div>
                
                <div class="mb-4">
                    <label for="admin_response" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Response</label>
                    <textarea name="admin_response" id="admin_response" rows="5" required
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                              placeholder="Type your response here..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Response Templates</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="response-templates">
                        <!-- Templates will be loaded here via JavaScript -->
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeResponseModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResponseForm(feedbackId, subject, citizenName) {
    document.getElementById('modal_feedback_id').value = feedbackId;
    document.getElementById('modal_feedback_subject').textContent = subject;
    document.getElementById('modal_citizen_name').textContent = citizenName;
    document.getElementById('admin_response').value = '';
    loadResponseTemplates();
    document.getElementById('responseModal').classList.remove('hidden');
}

function loadResponseTemplates() {
    fetch('get_templates.php')
        .then(response => response.json())
        .then(data => {
            const templatesContainer = document.getElementById('response-templates');
            templatesContainer.innerHTML = '';
            
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const categoryColors = {
                        'thank_you': 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-300',
                        'under_review': 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900 dark:border-blue-700 dark:text-blue-300',
                        'more_info': 'bg-yellow-50 border-yellow-200 text-yellow-700 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-300',
                        'resolved': 'bg-purple-50 border-purple-200 text-purple-700 dark:bg-purple-900 dark:border-purple-700 dark:text-purple-300',
                        'custom': 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'
                    };
                    
                    const categoryColor = categoryColors[template.category] || categoryColors['custom'];
                    
                    const templateButton = document.createElement('button');
                    templateButton.type = 'button';
                    templateButton.className = `text-left p-3 border rounded-md hover:shadow-sm transition-all duration-200 ${categoryColor}`;
                    templateButton.innerHTML = `
                        <div class="flex items-center justify-between mb-1">
                            <div class="font-medium text-sm">${template.name}</div>
                            <span class="text-xs px-2 py-1 rounded-full bg-white dark:bg-gray-800 bg-opacity-50">${template.category.replace('_', ' ')}</span>
                        </div>
                        <div class="text-xs opacity-75 line-clamp-2">${template.content.substring(0, 100)}${template.content.length > 100 ? '...' : ''}</div>
                    `;
                    templateButton.onclick = () => useTemplate(template.content);
                    templatesContainer.appendChild(templateButton);
                });
            } else {
                templatesContainer.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 col-span-2 text-center py-4">No templates available</p>';
            }
        })
        .catch(error => {
            console.error('Error loading templates:', error);
            document.getElementById('response-templates').innerHTML = '<p class="text-sm text-red-500 col-span-2 text-center py-4">Error loading templates</p>';
        });
}

function useTemplate(content) {
    const responseTextarea = document.getElementById('admin_response');
    responseTextarea.value = content;
    responseTextarea.focus();
}

function closeResponseModal() {
    document.getElementById('responseModal').classList.add('hidden');
}

function toggleActionDropdown(feedbackId) {
    const dropdown = document.getElementById('action-dropdown-' + feedbackId);
    dropdown.classList.toggle('hidden');
}

function quickApprove(feedbackId) {
    if (confirm('Are you sure you want to approve this feedback?')) {
        submitAction('approve', feedbackId);
    }
}

function quickReject(feedbackId) {
    if (confirm('Are you sure you want to reject this feedback?')) {
        submitAction('reject', feedbackId);
    }
}

function markAsSpam(feedbackId) {
    if (confirm('Are you sure you want to mark this feedback as spam?')) {
        submitAction('mark_spam', feedbackId);
    }
}

function deleteFeedback(feedbackId, subject) {
    if (confirm('Are you sure you want to delete "' + subject + '"? This action cannot be undone.')) {
        submitAction('delete', feedbackId);
    }
}

function submitAction(action, feedbackId) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('feedback_id', feedbackId);
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    formData.append('ajax', '1');
    
    fetch('feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request');
    });
}

document.getElementById('responseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeResponseModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the response');
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[onclick*="toggleActionDropdown"]')) {
        document.querySelectorAll('[id^="action-dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';