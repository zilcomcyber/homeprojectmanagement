<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in, if not redirect to login
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

require_admin();

$current_admin = get_current_admin();
?>
<?php
$page_title = "Admin Portal";
$is_admin_page = true;
$show_nav = true;
include 'includes/header.php';
?>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Dashboard Header -->
            <div class="px-4 py-6 sm:px-0">
                <div class="border-4 border-dashed border-gray-200 dark:border-gray-700 rounded-lg p-8">
                    <div class="text-center">
                        <i class="fas fa-tachometer-alt text-4xl text-blue-600 dark:text-blue-400 mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">County Project Management</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-8">Select an option below to manage your county projects</p>

                        <!-- Quick Actions -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                            <!-- Projects Management -->
                            <a href="admin/projects.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all group">
                                <div class="text-center">
                                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                                        <i class="fas fa-project-diagram text-blue-600 dark:text-blue-400 text-xl"></i>
                                    </div>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Manage Projects</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add, edit, and delete county projects</p>
                                </div>
                            </a>

                            <!-- Import Data -->
                            <a href="admin/import.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-green-300 dark:hover:border-green-600 transition-all group">
                                <div class="text-center">
                                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900 group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                                        <i class="fas fa-file-upload text-green-600 dark:text-green-400 text-xl"></i>
                                    </div>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Import Data</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Upload project data from CSV/Excel files</p>
                                </div>
                            </a>

                            <!-- Feedback Management -->
                            <a href="admin/feedback.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-600 transition-all group">
                                <div class="text-center">
                                    <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                                        <i class="fas fa-comments text-purple-600 dark:text-purple-400 text-xl"></i>
                                    </div>
                                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Citizen Feedback</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">View and respond to citizen feedback</p>
                                </div>
                            </a>
                        </div>

                        <!-- Quick Stats -->
                        <div class="mt-12 grid grid-cols-1 md:grid-cols-4 gap-6">
                            <?php
                            // Get quick stats
                            $total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
                            $ongoing_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'ongoing'")->fetchColumn();
                            $completed_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();
                            try {
                                $pending_feedback = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn();
                            } catch (PDOException $e) {
                                $pending_feedback = 0; // Handle missing feedback table gracefully
                            }
                            ?>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo $total_projects; ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Projects</div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $ongoing_projects; ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ongoing</div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo $completed_projects; ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Completed</div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?php echo $pending_feedback; ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pending Feedback</div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mt-8 flex justify-center space-x-4">
                            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-eye mr-2"></i>
                                View Public Portal
                            </a>
                            <a href="admin/dashboard.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Detailed Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
<?php
include 'includes/footer.php';
?>

<!-- Admin-specific JavaScript -->
<script>
    // Admin dashboard functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dashboard components
        updateStats();

        // Refresh stats every 30 seconds
        setInterval(updateStats, 30000);
    });

    function updateStats() {
        fetch('api/dashboard_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stat cards if they exist
                    const stats = data.stats;

                    // Update any displayed statistics
                    if (document.getElementById('totalProjects')) {
                        document.getElementById('totalProjects').textContent = stats.total_projects || '0';
                    }
                    if (document.getElementById('ongoingProjects')) {
                        document.getElementById('ongoingProjects').textContent = stats.ongoing_projects || '0';
                    }
                    if (document.getElementById('completedProjects')) {
                        document.getElementById('completedProjects').textContent = stats.completed_projects || '0';
                    }
                    if (document.getElementById('totalBudget')) {
                        document.getElementById('totalBudget').textContent = Utils.formatCurrency(stats.total_budget || 0);
                    }
                }
            })
            .catch(error => {
                console.error('Error updating stats:', error);
            });
    }
</script>