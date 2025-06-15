<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/step_progress.php';

require_admin();

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_step') {
            $result = update_step_status($_POST);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'generate_steps') {
            $result = generate_project_steps($_POST['project_id']);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get project ID from URL
$project_id = intval($_GET['project_id'] ?? 0);

if ($project_id <= 0) {
    header('Location: projects.php');
    exit;
}

// Get project details
$project = get_project_by_id($project_id);
if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get project steps
$steps = get_project_steps($project_id);

function get_project_steps($project_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM project_steps WHERE project_id = ? ORDER BY step_number");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

function update_step_status($data) {
    global $pdo, $current_admin;

    try {
        $step_id = intval($data['step_id']);
        $status = $data['status'];
        $notes = sanitize_input($data['notes'] ?? '');

        $valid_statuses = ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $pdo->beginTransaction();

        // Update step
        $update_fields = ['status = ?', 'notes = ?', 'updated_at = NOW()'];
        $params = [$status, $notes];

        if ($status === 'completed') {
            $update_fields[] = 'actual_end_date = CURDATE()';
        } elseif ($status === 'in_progress') {
            $update_fields[] = 'actual_start_date = COALESCE(actual_start_date, CURDATE())';
        }

        $sql = "UPDATE project_steps SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $params[] = $step_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Get project ID and update progress and status
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $project_id = $stmt->fetchColumn();

        if ($project_id) {
            update_project_progress($project_id);
            // Auto-update project status based on step completion
            require_once '../includes/update_project_status.php';
            update_project_status_based_on_steps($project_id);
        }

        $pdo->commit();

        log_activity("Project step updated to: " . $status, $current_admin['id']);
        return ['success' => true, 'message' => 'Step updated successfully'];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update step error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the step'];
    }
}

function generate_project_steps($project_id) {
    global $current_admin;

    try {
        // Get project department
        $project = get_project_by_id($project_id);
        if (!$project) {
            return ['success' => false, 'message' => 'Project not found'];
        }

        $result = create_project_steps($project_id, $project['department_name']);

        if ($result['success']) {
            update_project_progress($project_id);
            log_activity("Generated steps for project: " . $project['project_name'], $current_admin['id']);
        }

        return $result;

    } catch (Exception $e) {
        error_log("Generate steps error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while generating steps'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Project Steps - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="../admin.php" class="flex items-center">
                            <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400 text-xl mr-3"></i>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Project Steps</h1>
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:inline"></i>
                        </button>

                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Welcome, <strong><?php echo htmlspecialchars($current_admin['name']); ?></strong>
                            </span>
                            <a href="../logout.php" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Breadcrumb -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="../admin.php" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                            <a href="projects.php" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">Projects</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Steps</span>
                        </div>
                    </li>
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

            <!-- Project Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['project_name']); ?></h2>
                            <p class="text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($project['department_name']); ?> • <?php echo htmlspecialchars($project['ward_name']); ?></p>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Progress: <?php echo $project['progress_percentage']; ?>%</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

            <!-- Steps Management -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Steps</h3>
                        <?php if (empty($steps)): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="generate_steps">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Generate Steps
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($steps)): ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-list-ol text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Steps Defined</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">This project doesn't have any steps yet. Generate standard steps based on the project department.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($steps as $step): ?>
                            <div class="p-6">
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_step">
                                    <input type="hidden" name="step_id" value="<?php echo $step['id']; ?>">

                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm font-medium px-2.5 py-0.5 rounded-full mr-3">
                                                    Step <?php echo $step['step_number']; ?>
                                                </span>
                                                <h4 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($step['step_name']); ?></h4>
                                            </div>
                                            <?php if ($step['description']): ?>
                                                <p class="text-gray-600 dark:text-gray-400 mb-3"><?php echo htmlspecialchars($step['description']); ?></p>
                                            <?php endif; ?>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                        <option value="pending" <?php echo $step['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="in_progress" <?php echo $step['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="completed" <?php echo $step['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="delayed" <?php echo $step['status'] === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                                        <option value="cancelled" <?php echo $step['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                                    <input type="text" name="notes" value="<?php echo htmlspecialchars($step['notes'] ?? ''); ?>" 
                                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ml-4">
                                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-save mr-1"></i>
                                                Update
                                            </button>
                                        </div>
                                    </div>

                                    <?php if ($step['actual_start_date'] || $step['actual_end_date']): ?>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <?php if ($step['actual_start_date']): ?>
                                                <span class="mr-4">Started: <?php echo format_date($step['actual_start_date']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($step['actual_end_date']): ?>
                                                <span>Completed: <?php echo format_date($step['actual_end_date']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>