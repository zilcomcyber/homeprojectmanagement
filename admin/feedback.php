
<?php
$page_title = "Citizen Feedback";
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('admin'); // Admin and super_admin can manage feedback

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'respond') {
            $result = respond_to_feedback($_POST);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'update_status') {
            $result = update_feedback_status($_POST);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = delete_feedback($_POST['feedback_id']);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$project_id = $_GET['project_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build filters array
$filters = array_filter([
    'status' => $status,
    'project_id' => $project_id,
    'search' => $search
]);

// Get feedback data
$feedback_list = get_feedback($filters);

// Get projects for dropdown
$projects = get_projects();

function get_feedback($filters = []) {
    global $pdo;

    $sql = "SELECT f.*, p.project_name, a.name as responded_by_name
            FROM feedback f
            JOIN projects p ON f.project_id = p.id
            LEFT JOIN admins a ON f.responded_by = a.id
            WHERE 1=1";

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
        $sql .= " AND (f.subject LIKE ? OR f.message LIKE ? OR f.citizen_name LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $sql .= " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function respond_to_feedback($data) {
    global $pdo, $current_admin;

    try {
        $feedback_id = intval($data['feedback_id']);
        $response = sanitize_input($data['admin_response']);

        if (empty($response)) {
            return ['success' => false, 'message' => 'Response cannot be empty'];
        }

        // Check if feedback exists
        $stmt = $pdo->prepare("SELECT id, citizen_name, subject FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch();

        if (!$feedback) {
            return ['success' => false, 'message' => 'Feedback not found'];
        }

        // Update feedback with response
        $sql = "UPDATE feedback SET 
                admin_response = ?, 
                status = 'responded', 
                responded_by = ?, 
                updated_at = NOW() 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$response, $current_admin['id'], $feedback_id])) {
            log_activity("Responded to feedback: " . $feedback['subject'], $current_admin['id']);
            return ['success' => true, 'message' => 'Response sent successfully'];
        }

        return ['success' => false, 'message' => 'Failed to save response'];

    } catch (Exception $e) {
        error_log("Feedback response error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while saving the response'];
    }
}

function update_feedback_status($data) {
    global $pdo, $current_admin;

    try {
        $feedback_id = intval($data['feedback_id']);
        $new_status = $data['new_status'];

        $valid_statuses = ['pending', 'reviewed', 'responded'];
        if (!in_array($new_status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $stmt = $pdo->prepare("UPDATE feedback SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $feedback_id])) {
            log_activity("Feedback status updated to: " . $new_status, $current_admin['id']);
            return ['success' => true, 'message' => 'Status updated successfully'];
        }

        return ['success' => false, 'message' => 'Failed to update status'];

    } catch (Exception $e) {
        error_log("Feedback status update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating status'];
    }
}

function delete_feedback($feedback_id) {
    global $pdo, $current_admin;

    try {
        $feedback_id = intval($feedback_id);

        // Get feedback info for logging
        $stmt = $pdo->prepare("SELECT subject FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch();

        if (!$feedback) {
            return ['success' => false, 'message' => 'Feedback not found'];
        }

        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
        if ($stmt->execute([$feedback_id])) {
            log_activity("Feedback deleted: " . $feedback['subject'], $current_admin['id']);
            return ['success' => true, 'message' => 'Feedback deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete feedback'];

    } catch (Exception $e) {
        error_log("Delete feedback error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting feedback'];
    }
}

ob_start();
?>

<!-- Messages -->
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

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Citizen Feedback</h2>
    <div class="flex space-x-3">
        <a href="../index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-eye mr-2"></i>
            View Public Portal
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filter Feedback</h3>
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Subject, message, or citizen name" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="responded" <?php echo $status === 'responded' ? 'selected' : ''; ?>>Responded</option>
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

            <div class="flex space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Filter
                </button>
                <a href="feedback.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Feedback List -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            <?php echo count($feedback_list); ?> Feedback Message<?php echo count($feedback_list) !== 1 ? 's' : ''; ?> Found
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
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center mb-2">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mr-3">
                                    <?php echo htmlspecialchars($feedback['subject']); ?>
                                </h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_feedback_status_badge_class($feedback['status']); ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-3">
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">From:</span>
                                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($feedback['citizen_name']); ?></p>
                                    <?php if (!empty($feedback['citizen_email'])): ?>
                                        <p class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($feedback['citizen_email']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($feedback['citizen_phone'])): ?>
                                        <p class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($feedback['citizen_phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Project:</span>
                                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($feedback['project_name']); ?></p>
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Submitted:</span>
                                    <p class="text-gray-600 dark:text-gray-300"><?php echo format_date($feedback['created_at']); ?></p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <span class="font-medium text-gray-500 dark:text-gray-400 block mb-1">Message:</span>
                                <p class="text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                                    <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                </p>
                            </div>

                            <?php if (!empty($feedback['admin_response'])): ?>
                                <div class="mb-4">
                                    <span class="font-medium text-gray-500 dark:text-gray-400 block mb-1">Admin Response:</span>
                                    <p class="text-gray-900 dark:text-white bg-green-50 dark:bg-green-900 p-3 rounded-md">
                                        <?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                        Responded by <?php echo htmlspecialchars($feedback['responded_by_name']); ?> 
                                        on <?php echo format_date($feedback['updated_at']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ml-4 flex-shrink-0 flex space-x-2">
                            <?php if ($feedback['status'] !== 'responded'): ?>
                                <button onclick="showResponseForm(<?php echo $feedback['id']; ?>, '<?php echo htmlspecialchars($feedback['subject'], ENT_QUOTES); ?>')" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-reply mr-1"></i>
                                    Respond
                                </button>
                            <?php endif; ?>

                            <div class="relative">
                                <button onclick="toggleDropdown(<?php echo $feedback['id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="dropdown-<?php echo $feedback['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-600">
                                    <div class="py-1">
                                        <button onclick="updateStatus(<?php echo $feedback['id']; ?>, 'pending')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            Mark as Pending
                                        </button>
                                        <button onclick="updateStatus(<?php echo $feedback['id']; ?>, 'reviewed')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            Mark as Reviewed
                                        </button>
                                        <button onclick="deleteFeedback(<?php echo $feedback['id']; ?>, '<?php echo htmlspecialchars($feedback['subject'], ENT_QUOTES); ?>')" 
                                                class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Response Modal -->
<div id="responseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full">
            <form id="responseForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="feedback_id" id="responseFeedbackId">

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="responseModalTitle">Respond to Feedback</h3>
                    <button type="button" onclick="closeResponseModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Response Message</label>
                        <textarea name="admin_response" rows="6" required 
                                  placeholder="Type your response to the citizen here..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeResponseModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Form -->
<form id="statusForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="feedback_id" id="statusFeedbackId">
    <input type="hidden" name="new_status" id="newStatus">
</form>

<!-- Delete Form -->
<form id="deleteForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="feedback_id" id="deleteFeedbackId">
</form>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin.js"></script>

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
