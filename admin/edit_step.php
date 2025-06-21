<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_step') {
        $result = update_project_step($_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'reorder_steps') {
        $result = reorder_project_steps($_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$project_id = $_GET['project_id'] ?? 0;
$step_id = $_GET['step_id'] ?? 0;

if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Get project details
$project = get_project_by_id($project_id);
if (!$project) {
    header('Location: projects.php?error=Project not found');
    exit;
}

// Get specific step if editing
$step = null;
if ($step_id) {
    $stmt = $pdo->prepare("SELECT * FROM project_steps WHERE id = ? AND project_id = ?");
    $stmt->execute([$step_id, $project_id]);
    $step = $stmt->fetch();
}

// Get all steps for reordering
$stmt = $pdo->prepare("SELECT * FROM project_steps WHERE project_id = ? ORDER BY step_number");
$stmt->execute([$project_id]);
$all_steps = $stmt->fetchAll();

function update_project_step($data) {
    global $pdo, $current_admin;
    
    try {
        $step_id = intval($data['step_id']);
        $step_name = sanitize_input($data['step_name']);
        $description = sanitize_input($data['description']);
        $expected_end_date = $data['expected_end_date'] ?: null;
        $status = $data['status'];
        
        $valid_statuses = ['pending', 'in_progress', 'completed'];
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $sql = "UPDATE project_steps SET 
                step_name = ?, 
                description = ?, 
                expected_end_date = ?, 
                status = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$step_name, $description, $expected_end_date, $status, $step_id])) {
            // Update actual_end_date if marking as completed
            if ($status === 'completed') {
                $pdo->prepare("UPDATE project_steps SET actual_end_date = NOW() WHERE id = ?")->execute([$step_id]);
            } elseif ($status !== 'completed') {
                $pdo->prepare("UPDATE project_steps SET actual_end_date = NULL WHERE id = ?")->execute([$step_id]);
            }
            
            // Recalculate project progress
            $project_id = $data['project_id'];
            update_project_progress($project_id);
            
            log_activity("Updated project step: " . $step_name, $current_admin['id']);
            return ['success' => true, 'message' => 'Step updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update step'];
        
    } catch (Exception $e) {
        error_log("Update step error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the step'];
    }
}

function reorder_project_steps($data) {
    global $pdo, $current_admin;
    
    try {
        $project_id = intval($data['project_id']);
        $step_orders = json_decode($data['step_orders'], true);
        
        if (!$step_orders) {
            return ['success' => false, 'message' => 'Invalid step order data'];
        }
        
        $pdo->beginTransaction();
        
        foreach ($step_orders as $step_id => $new_order) {
            $stmt = $pdo->prepare("UPDATE project_steps SET step_number = ? WHERE id = ? AND project_id = ?");
            $stmt->execute([intval($new_order), intval($step_id), $project_id]);
        }
        
        $pdo->commit();
        
        log_activity("Reordered project steps for project ID: " . $project_id, $current_admin['id']);
        return ['success' => true, 'message' => 'Steps reordered successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Reorder steps error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while reordering steps'];
    }
}

$page_title = $step ? "Edit Step" : "Manage Project Steps";
$is_admin_page = true;
$show_nav = true;
include '../includes/header.php';
?>

<div class="min-h-full">
    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li><a href="../admin.php" class="text-gray-400 hover:text-gray-500"><i class="fas fa-home"></i></a></li>
                <li><div class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mr-4"></i><a href="projects.php" class="text-gray-400 hover:text-gray-500">Projects</a></div></li>
                <li><div class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mr-4"></i><span class="text-sm font-medium text-gray-900 dark:text-white"><?php echo $step ? 'Edit Step' : 'Manage Steps'; ?></span></div></li>
            </ol>
        </nav>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
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

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $step ? 'Edit Step' : 'Manage Project Steps'; ?></h2>
                <p class="text-gray-600 dark:text-gray-400">Project: <?php echo htmlspecialchars($project['project_name']); ?></p>
            </div>
            <a href="manage_project.php?id=<?php echo $project_id; ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Project
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Edit Step Form -->
            <?php if ($step): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Step Details</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_step">
                    <input type="hidden" name="step_id" value="<?php echo $step['id']; ?>">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Step Name</label>
                            <input type="text" name="step_name" value="<?php echo htmlspecialchars($step['step_name']); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($step['description']); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected End Date</label>
                            <input type="date" name="expected_end_date" value="<?php echo $step['expected_end_date']; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="pending" <?php echo $step['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $step['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $step['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mt-6">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Step
                        </button>
                        <a href="edit_step.php?project_id=<?php echo $project_id; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Steps List and Reordering -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Steps</h3>
                    <button onclick="saveStepOrder()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors" style="display: none;" id="saveOrderBtn">
                        <i class="fas fa-save mr-2"></i>Save Order
                    </button>
                </div>
                
                <div id="stepsList" class="space-y-3">
                    <?php foreach ($all_steps as $index => $s): ?>
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-move" data-step-id="<?php echo $s['id']; ?>" data-step-order="<?php echo $s['step_number']; ?>">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-grip-vertical text-gray-400"></i>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400"><?php echo $s['step_number']; ?></span>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($s['step_name']); ?></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($s['description']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $s['status'] === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 
                                          ($s['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 
                                           'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $s['status'])); ?>
                            </span>
                            <a href="edit_step.php?project_id=<?php echo $project_id; ?>&step_id=<?php echo $s['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/sortable.min.js"></script>
<script>
// Initialize sortable list
const stepsList = document.getElementById('stepsList');
const saveOrderBtn = document.getElementById('saveOrderBtn');

new Sortable(stepsList, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    onEnd: function() {
        saveOrderBtn.style.display = 'inline-flex';
    }
});

function saveStepOrder() {
    const stepItems = stepsList.querySelectorAll('[data-step-id]');
    const stepOrders = {};
    
    stepItems.forEach((item, index) => {
        const stepId = item.getAttribute('data-step-id');
        stepOrders[stepId] = index + 1;
    });
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="reorder_steps">
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
        <input type="hidden" name="step_orders" value='${JSON.stringify(stepOrders)}'>
    `;
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}
</style>

<?php include '../includes/footer.php'; ?>
