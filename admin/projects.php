<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $result = handle_project_form($_POST, $action);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = delete_project($_POST['project_id']);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$department = $_GET['department'] ?? '';
$year = $_GET['year'] ?? '';

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'department' => $department,
    'year' => $year
]);

// Get data for dropdowns
$departments = get_departments();
$counties = get_counties();
$years = get_project_years();

// Get projects
$projects = get_projects($filters);

// Get single project for editing if specified
$edit_project = null;
if (isset($_GET['edit'])) {
    $edit_project = get_project_by_id($_GET['edit']);
}

function handle_project_form($data, $action) {
    global $pdo, $current_admin;

    try {
        // Validate required fields
        $required_fields = ['project_name', 'department_id', 'ward_id', 'sub_county_id', 'county_id', 'budget', 'project_year'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => 'Please fill in all required fields'];
            }
        }

        // Validate foreign key references
        $dept_check = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE id = ?");
        $dept_check->execute([intval($data['department_id'])]);
        if ($dept_check->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'Invalid department selected'];
        }

        $county_check = $pdo->prepare("SELECT COUNT(*) FROM counties WHERE id = ?");
        $county_check->execute([intval($data['county_id'])]);
        if ($county_check->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'Invalid county selected'];
        }

        $sub_county_check = $pdo->prepare("SELECT COUNT(*) FROM sub_counties WHERE id = ? AND county_id = ?");
        $sub_county_check->execute([intval($data['sub_county_id']), intval($data['county_id'])]);
        if ($sub_county_check->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'Invalid sub-county selected for the chosen county'];
        }

        $ward_check = $pdo->prepare("SELECT COUNT(*) FROM wards WHERE id = ? AND sub_county_id = ?");
        $ward_check->execute([intval($data['ward_id']), intval($data['sub_county_id'])]);
        if ($ward_check->fetchColumn() == 0) {
            return ['success' => false, 'message' => 'Invalid ward selected for the chosen sub-county'];
        }

        // Sanitize data
        $project_data = [
            'project_name' => sanitize_input($data['project_name']),
            'description' => sanitize_input($data['description'] ?? ''),
            'department_id' => intval($data['department_id']),
            'ward_id' => intval($data['ward_id']),
            'sub_county_id' => intval($data['sub_county_id']),
            'county_id' => intval($data['county_id']),
            'contractor_name' => sanitize_input($data['contractor_name'] ?? ''),
            'contractor_contact' => sanitize_input($data['contractor_contact'] ?? ''),
            'budget' => floatval(str_replace(',', '', $data['budget'])),
            'allocated_budget' => floatval(str_replace(',', '', $data['allocated_budget'] ?? 0)),
            'spent_budget' => floatval(str_replace(',', '', $data['spent_budget'] ?? 0)),
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'expected_completion_date' => !empty($data['expected_completion_date']) ? $data['expected_completion_date'] : null,
            'actual_completion_date' => !empty($data['actual_completion_date']) ? $data['actual_completion_date'] : null,
            'status' => $data['status'] ?? 'planning',
            'step_status' => 'awaiting', // All new projects start as awaiting
            'progress_percentage' => max(0, min(100, intval($data['progress_percentage'] ?? 0))),
            'location_coordinates' => sanitize_input($data['location_coordinates'] ?? ''),
            'location_address' => sanitize_input($data['location_address'] ?? ''),
            'project_year' => intval($data['project_year'])
        ];

        if ($action === 'create') {
            $sql = "INSERT INTO projects (
                        project_name, description, department_id, ward_id, sub_county_id, county_id,
                        contractor_name, contractor_contact, budget, allocated_budget, spent_budget,
                        start_date, expected_completion_date, actual_completion_date, status,
                        progress_percentage, location_coordinates, location_address, project_year, created_by, step_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = array_values($project_data);
            $params[] = $current_admin['id'];

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                // Get the newly created project ID
                $new_project_id = $pdo->lastInsertId();

                // Get department name for step generation
                $dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                $dept_stmt->execute([$project_data['department_id']]);
                $department_name = $dept_stmt->fetchColumn();

                // Generate project steps automatically
                if ($department_name) {
                    create_project_steps($new_project_id, $department_name);
                }

                log_activity("Project created: " . $project_data['project_name'], $current_admin['id']);
                return ['success' => true, 'message' => 'Project created successfully'];
            }
        } else {
            $project_id = intval($data['project_id']);
            $sql = "UPDATE projects SET 
                        project_name = ?, description = ?, department_id = ?, ward_id = ?, sub_county_id = ?, county_id = ?,
                        contractor_name = ?, contractor_contact = ?, budget = ?, allocated_budget = ?, spent_budget = ?,
                        start_date = ?, expected_completion_date = ?, actual_completion_date = ?, status = ?,
                        progress_percentage = ?, location_coordinates = ?, location_address = ?, project_year = ?
                    WHERE id = ?";

            $params = array_values($project_data);
            $params[] = $project_id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                log_activity("Project updated: " . $project_data['project_name'], $current_admin['id']);
                return ['success' => true, 'message' => 'Project updated successfully'];
            }
        }

        return ['success' => false, 'message' => 'Database operation failed'];

    } catch (Exception $e) {
        error_log("Project form error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while saving the project'];
    }
}

function delete_project($project_id) {
    global $pdo, $current_admin;

    try {
        $project_id = intval($project_id);

        // Get project name for logging
        $stmt = $pdo->prepare("SELECT project_name FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if (!$project) {
            return ['success' => false, 'message' => 'Project not found'];
        }

        // Delete project
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        if ($stmt->execute([$project_id])) {
            log_activity("Project deleted: " . $project['project_name'], $current_admin['id']);
            return ['success' => true, 'message' => 'Project deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete project'];

    } catch (Exception $e) {
        error_log("Delete project error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the project'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - <?php echo APP_NAME; ?></title>
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
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Projects</h1>
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
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Projects</span>
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

            <!-- Actions Bar -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Projects</h2>
                
                <div class="flex space-x-3">
                    <a href="../index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-eye mr-2"></i>
                        View Public Portal
                    </a>
                    <a href="import_csv.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-file-csv mr-2"></i>
                        Import CSV
                    </a>
                    <a href="create_project.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Add New Project
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filter Projects</h3>
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Project name" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">All Statuses</option>
                                    <option value="planning" <?php echo $status === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                    <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                                <select name="department" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                                <select name="year" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">All Years</option>
                                    <?php foreach ($years as $yr): ?>
                                        <option value="<?php echo $yr; ?>" <?php echo $year == $yr ? 'selected' : ''; ?>>
                                            <?php echo $yr; ?>
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
                            <a href="projects.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?php echo count($projects); ?> Project<?php echo count($projects) !== 1 ? 's' : ''; ?> Found
                    </h3>
                </div>

                <?php if (empty($projects)): ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-folder-open text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Projects Found</h3>
                        <p class="text-gray-600 dark:text-gray-400">Get started by creating a new project.</p>
                        <button onclick="showProjectForm()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Add First Project
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Budget</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($projects as $project): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($project['project_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                Year: <?php echo $project['project_year']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($project['department_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($project['ward_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo format_currency($project['budget']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                        <div class="h-2 rounded-full <?php echo get_progress_color_class($project['progress_percentage']); ?>" 
                                                             style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                                                    </div>
                                                </div>
                                                <span class="ml-3 text-sm text-gray-900 dark:text-white"><?php echo $project['progress_percentage']; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="manage_project.php?id=<?php echo $project['id']; ?>" class="inline-flex items-center px-3 py-1 border border-green-600 text-xs font-medium rounded text-green-600 hover:bg-green-50 dark:hover:bg-green-900 transition-colors" title="Manage Project">
                                                    <i class="fas fa-cog mr-1"></i>
                                                    Manage
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Project Form Modal -->
    <div id="projectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                <form id="projectForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="project_id" id="projectId">

                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modalTitle">Add New Project</h3>
                        <button type="button" onclick="closeProjectForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Name *</label>
                                <input type="text" name="project_name" id="projectName" required 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea name="description" id="projectDescription" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department *</label>
                                <select name="department_id" id="departmentId" required 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">County *</label>
                                <select name="county_id" id="countyId" required onchange="loadSubCounties(this.value)"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select County</option>
                                    <?php foreach ($counties as $county): ?>
                                        <option value="<?php echo $county['id']; ?>"><?php echo htmlspecialchars($county['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub County *</label>
                                <select name="sub_county_id" id="subCountyId" required onchange="loadWards(this.value)"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select Sub County</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ward *</label>
                                <select name="ward_id" id="wardId" required 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select Ward</option>
                                </select>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Budget (KES) *</label>
                                <input type="number" name="budget" id="projectBudget" step="0.01" required 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allocated Budget (KES)</label>
                                <input type="number" name="allocated_budget" id="allocatedBudget" step="0.01" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Spent Budget (KES)</label>
                                <input type="number" name="spent_budget" id="spentBudget" step="0.01" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year *</label>
                                <input type="number" name="project_year" id="projectYear" min="2020" max="2030" required 
                                       value="<?php echo date('Y'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status" id="projectStatus" 
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="planning">Planning</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Progress Percentage</label>
                                <input type="number" name="progress_percentage" id="progressPercentage" min="0" max="100" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Name</label>
                                <input type="text" name="contractor_name" id="contractorName" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Contact</label>
                                <input type="text" name="contractor_contact" id="contractorContact" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                <input type="date" name="start_date" id="startDate" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Completion</label>
                                <input type="date" name="expected_completion_date" id="expectedCompletion" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual Completion</label>
                                <input type="date" name="actual_completion_date" id="actualCompletion" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Coordinates</label>
                                <input type="text" name="location_coordinates" id="locationCoordinates" 
                                       placeholder="latitude,longitude" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Address</label>
                                <input type="text" name="location_address" id="locationAddress" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" onclick="closeProjectForm()" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                            <span id="submitText">Create Project</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="project_id" id="deleteProjectId">

                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Project</h3>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Are you sure you want to delete "<span id="deleteProjectName" class="font-medium"></span>"? 
                            This action cannot be undone and will also delete all associated feedback.
                        </p>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeDeleteModal()" 
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors">
                                Delete Project
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($edit_project): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                editProject(<?php echo json_encode($edit_project); ?>);
            });
        </script>
    <?php endif; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Load sub-counties based on county selection
        function loadSubCounties(countyId) {
            const subCountySelect = document.getElementById('subCountyId');
            const wardSelect = document.getElementById('wardId');

            // Clear existing options
            subCountySelect.innerHTML = '<option value="">Select Sub County</option>';
            wardSelect.innerHTML = '<option value="">Select Ward</option>';

            if (countyId) {
                fetch(`../api/locations.php?action=sub_counties&county_id=${countyId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(subCounty => {
                                const option = document.createElement('option');
                                option.value = subCounty.id;
                                option.textContent = subCounty.name;
                                subCountySelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading sub-counties:', error));
            }
        }

        // Load wards based on sub-county selection
        function loadWards(subCountyId) {
            const wardSelect = document.getElementById('wardId');

            // Clear existing options
            wardSelect.innerHTML = '<option value="">Select Ward</option>';

            if (subCountyId) {
                fetch(`../api/locations.php?action=wards&sub_county_id=${subCountyId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.data.forEach(ward => {
                                const option = document.createElement('option');
                                option.value = ward.id;
                                option.textContent = ward.name;
                                wardSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading wards:', error));
            }
        }
    </script>
</body>
</html>