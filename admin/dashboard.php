<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

// Get dashboard statistics
try {
    // Project statistics
    $stats = [];
    $stats['total_projects'] = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $stats['planning_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'planning'")->fetchColumn();
    $stats['ongoing_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'ongoing'")->fetchColumn();
    $stats['completed_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();
    $stats['suspended_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'suspended'")->fetchColumn();
    $stats['cancelled_projects'] = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'cancelled'")->fetchColumn();

    // Budget statistics
    $budget_stats = $pdo->query("SELECT 
        SUM(budget) as total_budget,
        SUM(CASE WHEN status = 'completed' THEN budget ELSE 0 END) as completed_budget,
        SUM(CASE WHEN status = 'ongoing' THEN budget ELSE 0 END) as ongoing_budget,
        AVG(budget) as average_budget
        FROM projects")->fetch();

    // Feedback statistics
    $feedback_stats = [];
    $feedback_stats['total_feedback'] = $pdo->query("SELECT COUNT(*) FROM project_feedback")->fetchColumn();
    $feedback_stats['pending_feedback'] = $pdo->query("SELECT COUNT(*) FROM project_feedback WHERE status = 'pending'")->fetchColumn();
    $feedback_stats['responded_feedback'] = $pdo->query("SELECT COUNT(*) FROM project_feedback WHERE status = 'responded'")->fetchColumn();

    // Recent projects
    $recent_projects = $pdo->query("SELECT p.*, d.name as department_name 
                                   FROM projects p 
                                   JOIN departments d ON p.department_id = d.id 
                                   ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

    // Projects by year
    $projects_by_year = $pdo->query("SELECT project_year, COUNT(*) as count, SUM(budget) as total_budget 
                                    FROM projects 
                                    GROUP BY project_year 
                                    ORDER BY project_year DESC")->fetchAll();

    // Projects by status
    $projects_by_status = $pdo->query("SELECT status, COUNT(*) as count, SUM(budget) as total_budget 
                                      FROM projects 
                                      GROUP BY status")->fetchAll();

    // Projects by department
    $projects_by_department = $pdo->query("SELECT d.name, COUNT(*) as count, SUM(p.budget) as total_budget 
                                          FROM projects p 
                                          JOIN departments d ON p.department_id = d.id 
                                          GROUP BY d.id, d.name 
                                          ORDER BY count DESC")->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $stats = [];
    $budget_stats = [];
    $feedback_stats = [];
    $recent_projects = [];
    $projects_by_year = [];
    $projects_by_status = [];
    $projects_by_department = [];
}

$page_title = "Admin Dashboard";
$is_admin_page = true;
$show_nav = true;
include '../includes/header.php';
?>

<div class="min-h-full">
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
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Dashboard</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($current_admin['name']); ?>!</h1>
            <p class="text-gray-600 dark:text-gray-400">Here's what's happening with your projects today.</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-md flex items-center justify-center">
                            <i class="fas fa-project-diagram text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Projects</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['total_projects'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Ongoing Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-md flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Ongoing</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['ongoing_projects'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Completed Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-md flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($stats['completed_projects'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Feedback -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-md flex items-center justify-center">
                            <i class="fas fa-comments text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pending Feedback</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($feedback_stats['pending_feedback'] ?? 0); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Budget Overview</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            <?php echo format_currency($budget_stats['total_budget'] ?? 0); ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Budget</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            <?php echo format_currency($budget_stats['completed_budget'] ?? 0); ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Completed Projects Budget</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            <?php echo format_currency($budget_stats['ongoing_budget'] ?? 0); ?>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ongoing Projects Budget</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Projects by Status Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projects by Status</h3>
                </div>
                <div class="p-6">
                    <canvas id="statusChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Projects by Department -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Projects by Department</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($projects_by_department as $dept): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo format_currency($dept['total_budget']); ?>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo $dept['count']; ?> projects
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Projects</h3>
                    <a href="projects.php" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 text-sm font-medium">
                        View all projects <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Budget</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recent_projects as $project): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($project['department_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo format_currency($project['budget']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo format_date($project['created_at']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="create_project.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-md flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                            <i class="fas fa-plus text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add New Project</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Create a new project</p>
                    </div>
                </div>
            </a>

            <a href="import_csv.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-green-300 dark:hover:border-green-600 transition-all group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-md flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                            <i class="fas fa-upload text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Import Projects</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Upload CSV file</p>
                    </div>
                </div>
            </a>

            <a href="feedback.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-600 transition-all group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-md flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                            <i class="fas fa-comments text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Manage Feedback</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Respond to citizens</p>
                    </div>
                </div>
            </a>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Status Chart
const statusData = <?php echo json_encode($projects_by_status); ?>;
const statusLabels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
const statusCounts = statusData.map(item => item.count);
const statusColors = ['#f59e0b', '#3b82f6', '#10b981', '#f97316', '#ef4444'];

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: statusColors,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>