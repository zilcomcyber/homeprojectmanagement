<?php
$page_title = "Manage Projects";
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
$visibility = $_GET['visibility'] ?? '';

// Build filters array
$filters = array_filter([
    'search' => $search,
    'status' => $status,
    'department' => $department,
    'year' => $year,
    'visibility' => $visibility
]);

// Get data for dropdowns
$departments = get_departments();
$counties = get_counties();
$years = get_project_years();

// Get projects (use admin function to see all projects)
$projects = get_all_projects($filters);

// Get single project for editing if specified
$edit_project = null;
if (isset($_GET['edit'])) {
    $edit_project = get_project_by_id($_GET['edit']);
}

function handle_project_form($data, $action) {
    global $pdo, $current_admin;

    try {
        // Validate required fields
        $required_fields = ['project_name', 'department_id', 'ward_id', 'sub_county_id', 'county_id', 'project_year'];
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
                        contractor_name, contractor_contact, start_date, expected_completion_date, 
                        actual_completion_date, status, progress_percentage, location_coordinates, 
                        location_address, project_year, created_by, step_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
                        contractor_name = ?, contractor_contact = ?, start_date = ?, expected_completion_date = ?, 
                        actual_completion_date = ?, status = ?, progress_percentage = ?, location_coordinates = ?, 
                        location_address = ?, project_year = ?
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

<!-- Actions Bar -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Projects</h2>

    <div class="flex space-x-3">
        <a href="../index" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-eye mr-2"></i>
            View Public Portal
        </a>
        <a href="import_csv" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-file-csv mr-2"></i>
            Import CSV
        </a>
        <a href="create_project" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
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
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibility</label>
                    <select name="visibility" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Visibility</option>
                        <option value="private" <?php echo $visibility === 'private' ? 'selected' : ''; ?>>Private</option>
                        <option value="published" <?php echo $visibility === 'published' ? 'selected' : ''; ?>>Published</option>
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
            <?php

            $current_admin = get_current_admin();
            $role = $current_admin['role'];

            // Prepare role-based filtering
            $role_filter = '';
            $role_params = [];

            if ($role === 'viewer') {
                $role_filter = " AND p.visibility = 'published'";
            } elseif ($role === 'admin') {
                // Admin role can only see their own projects
                $role_filter = " AND p.created_by = ?";
                $role_params[] = $current_admin['id'];
            }
            // super_admin sees all projects (no additional filter)

            // Prepare filters
            $filter_clauses = [];
            $params = [];

            if (!empty($filters['search'])) {
                $filter_clauses[] = "p.project_name LIKE ?";
                $params[] = "%" . $filters['search'] . "%";
            }

            if (!empty($filters['status'])) {
                $filter_clauses[] = "p.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['department'])) {
                $filter_clauses[] = "p.department_id = ?";
                $params[] = $filters['department'];
            }

            if (!empty($filters['year'])) {
                $filter_clauses[] = "p.project_year = ?";
                $params[] = $filters['year'];
            }

             if (!empty($filters['visibility'])) {
                $filter_clauses[] = "p.visibility = ?";
                $params[] = $filters['visibility'];
            }

            $filters = !empty($filter_clauses) ? ' AND ' . implode(' AND ', $filter_clauses) : '';

            // Sorting
            $sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'p.project_name';
            $sort_direction = isset($_GET['dir']) ? $_GET['dir'] : 'ASC';

            // Pagination
            $per_page = 20;
            $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($current_page - 1) * $per_page;

            // Get the total number of projects
            $count_sql = "SELECT COUNT(*) FROM projects p 
                  JOIN departments d ON p.department_id = d.id 
                  JOIN wards w ON p.ward_id = w.id 
                  JOIN sub_counties sc ON w.sub_county_id = sc.id 
                  WHERE 1=1 $filters $role_filter";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute(array_merge($params, $role_params));
            $total_projects = $count_stmt->fetchColumn();

            // Modify SQL query with role filtering
            $sql = "SELECT p.*, d.name as department_name, sc.name as sub_county_name, w.name as ward_name 
            FROM projects p 
            JOIN departments d ON p.department_id = d.id 
            JOIN wards w ON p.ward_id = w.id 
            JOIN sub_counties sc ON w.sub_county_id = sc.id 
            WHERE 1=1 $filters $role_filter 
            ORDER BY $sort_column $sort_direction 
            LIMIT $offset, $per_page";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($params, $role_params));

            $projects = $stmt->fetchAll();

            // Calculate total pages for pagination
            $total_pages = ceil($total_projects / $per_page);

            echo $total_projects; ?> Project<?php echo $total_projects !== 1 ? 's' : ''; ?> Found
        </h3>
    </div>

    <?php if (empty($projects)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-folder-open text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Projects Found</h3>
            <p class="text-gray-600 dark:text-gray-400">Get started by creating a new project.</p>
            <a href="create_project.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Add First Project
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progress</th>
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
                                <!-- Show department and location on mobile -->
                                <div class="md:hidden mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($project['department_name']); ?> • <?php echo htmlspecialchars($project['ward_name']); ?>
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($project['department_name']); ?>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($project['ward_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $project['visibility'] === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'; ?>">
                                        <?php echo $project['visibility'] === 'published' ? 'Public' : 'Private'; ?>
                                    </span>
                                </div>
                                <!-- Show progress on mobile -->
                                <div class="sm:hidden mt-2">
                                    <div class="flex items-center">
                                        <div class="flex-1">
                                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full <?php echo get_progress_color_class($project['progress_percentage']); ?>" 
                                                     style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                                            </div>
                                        </div>
                                        <span class="ml-2 text-xs text-gray-900 dark:text-white"><?php echo $project['progress_percentage']; ?>%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
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
                                        <span class="hidden sm:inline">Manage</span>
                                        <span class="sm:hidden">Mgr</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to 
                        <?php echo min($current_page * $per_page, $total_projects); ?> of 
                        <?php echo number_format($total_projects); ?> results
                    </div>
                    <nav class="flex items-center space-x-2">
                        <?php if ($current_page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php 
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 border <?php echo $i === $current_page ? 'border-blue-500 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-400' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'; ?> rounded text-sm font-medium">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
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

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>