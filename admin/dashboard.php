<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('admin'); // Only admin and super_admin can access dashboard

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

    // Feedback statistics
    $feedback_stats = [];
    $feedback_stats['total_feedback'] = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    $feedback_stats['pending_feedback'] = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn();
    $feedback_stats['reviewed_feedback'] = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'reviewed'")->fetchColumn();
    $feedback_stats['responded_feedback'] = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'responded'")->fetchColumn();

    // Recent projects
    $recent_projects = $pdo->query("SELECT p.*, d.name as department_name 
                                   FROM projects p 
                                   JOIN departments d ON p.department_id = d.id 
                                   ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

    // Projects by year
    $projects_by_year = $pdo->query("SELECT project_year, COUNT(*) as count 
                                    FROM projects 
                                    GROUP BY project_year 
                                    ORDER BY project_year DESC")->fetchAll();

    // Projects by status
    $projects_by_status = $pdo->query("SELECT status, COUNT(*) as count 
                                      FROM projects 
                                      GROUP BY status")->fetchAll();

    // Projects by department
    $projects_by_department = $pdo->query("SELECT d.name, COUNT(*) as count 
                                          FROM projects p 
                                          JOIN departments d ON p.department_id = d.id 
                                          GROUP BY d.id, d.name 
                                          ORDER BY count DESC")->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $stats = [];
    $feedback_stats = [];
    $recent_projects = [];
    $projects_by_year = [];
    $projects_by_status = [];
    $projects_by_department = [];
}

$page_title = "Dashboard";

// Start output buffering for content
ob_start();
?>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Projects -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-project-diagram text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Projects</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_projects'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Ongoing Projects -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ongoing</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['ongoing_projects'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Completed Projects -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['completed_projects'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Pending Feedback -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Feedback</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($feedback_stats['pending_feedback'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Tables -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
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
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($dept['name']); ?>
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
            <a href="projects" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 text-sm font-medium">
                View all projects <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Project</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider hidden sm:table-cell">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider hidden md:table-cell">Created</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                            <?php echo htmlspecialchars($project['department_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell">
                            <?php echo format_date($project['created_at']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
