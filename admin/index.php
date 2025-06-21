<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();
$current_admin = get_current_admin();

// Get comprehensive stats for welcome page
try {
    // Basic project statistics
    $total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $this_month_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
    $ongoing_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'ongoing'")->fetchColumn();
    $completed_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();

    // Recent projects with proper data
    $recent_projects_stmt = $pdo->prepare("
        SELECT p.id, p.project_name, p.status, p.created_at, p.progress_percentage,
               d.name as department_name, sc.name as sub_county_name
        FROM projects p 
        LEFT JOIN departments d ON p.department_id = d.id 
        LEFT JOIN sub_counties sc ON p.sub_county_id = sc.id
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_projects_stmt->execute();
    $recent_projects = $recent_projects_stmt->fetchAll();

    // Recent feedback with proper data
    $recent_feedback_stmt = $pdo->prepare("
        SELECT f.id, f.subject, f.message, f.rating, f.created_at, f.citizen_name,
               p.project_name, f.status as feedback_status
        FROM feedback f 
        LEFT JOIN projects p ON f.project_id = p.id 
        ORDER BY f.created_at DESC 
        LIMIT 5
    ");
    $recent_feedback_stmt->execute();
    $recent_feedback = $recent_feedback_stmt->fetchAll();

    // Additional stats for dashboard
    $total_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    $responded_feedback = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'responded'")->fetchColumn();

} catch (Exception $e) {
    error_log("Admin Index Error: " . $e->getMessage());
    $total_projects = 0;
    $this_month_projects = 0;
    $ongoing_projects = 0;
    $completed_projects = 0;
    $total_feedback = 0;
    $responded_feedback = 0;
    $recent_projects = [];
    $recent_feedback = [];
}

$page_title = "Admin Dashboard";

// Start output buffering for content
ob_start();
?>

<!-- Welcome Banner -->
<div class="wp-card mb-6" style="background: linear-gradient(135deg, #0073aa, #005177); color: white; border: none; box-shadow: 0 4px 20px rgba(0, 115, 170, 0.3);">
    <div class="wp-card-content">
        <div class="text-center py-6 lg:py-8">
            <div class="mb-4 lg:mb-6">
                <i class="fas fa-user-shield text-4xl lg:text-5xl opacity-80"></i>
            </div>
            <h1 class="text-2xl lg:text-3xl font-bold mb-3 lg:mb-4">
                Welcome back, <?php echo htmlspecialchars($current_admin['name']); ?>!
            </h1>
            <p class="text-base lg:text-lg opacity-90 mb-4 lg:mb-6 px-4">
                <?php 
                $hour = date('H');
                if ($hour < 12) {
                    echo "Good morning! Ready to manage your projects?";
                } elseif ($hour < 17) {
                    echo "Good afternoon! Let's check on project progress.";
                } else {
                    echo "Good evening! Time to review today's activities.";
                }
                ?>
            </p>
            <div class="flex flex-col sm:flex-row gap-3 lg:gap-4 justify-center px-4">
                <a href="../index.php" class="wp-btn wp-btn-secondary">
                    <i class="fas fa-eye"></i>
                    <span class="hidden sm:inline">View Public Portal</span>
                    <span class="sm:hidden">Public Portal</span>
                </a>
                <?php if ($current_admin['role'] === 'super_admin' || $current_admin['role'] === 'admin'): ?>
                    <a href="dashboard.php" class="wp-btn" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.3);">
                        <i class="fas fa-chart-line"></i>
                        <span class="hidden sm:inline">Advanced Analytics</span>
                        <span class="sm:hidden">Analytics</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Grid (MOVED TO FIRST) -->
<div class="wp-card mb-6">
    <div class="wp-card-header">
        <h3 class="flex items-center">
            <i class="fas fa-bolt mr-2 text-yellow-500"></i>
            Quick Actions
        </h3>
    </div>
    <div class="wp-card-content">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <?php if ($current_admin['role'] !== 'viewer'): ?>
                <a href="import_csv.php" class="quick-action-card bg-blue-50 hover:bg-blue-100 border-blue-200">
                    <div class="quick-action-icon bg-blue-600">
                        <i class="fas fa-upload text-white"></i>
                    </div>
                    <h4>Import CSV</h4>
                    <p>Bulk import</p>
                </a>

                <a href="create_project.php" class="quick-action-card bg-green-50 hover:bg-green-100 border-green-200">
                    <div class="quick-action-icon bg-green-600">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                    <h4>Add Project</h4>
                    <p>Create new</p>
                </a>

                <a href="projects.php" class="quick-action-card bg-purple-50 hover:bg-purple-100 border-purple-200">
                    <div class="quick-action-icon bg-purple-600">
                        <i class="fas fa-tasks text-white"></i>
                    </div>
                    <h4>Manage</h4>
                    <p>Edit projects</p>
                </a>

                <a href="feedback.php" class="quick-action-card bg-orange-50 hover:bg-orange-100 border-orange-200">
                    <div class="quick-action-icon bg-orange-600">
                        <i class="fas fa-comments text-white"></i>
                    </div>
                    <h4>Feedback</h4>
                    <p>Review all</p>
                </a>
            <?php endif; ?>

            <?php if ($current_admin['role'] === 'super_admin'): ?>
                <a href="manage_admins.php" class="quick-action-card bg-red-50 hover:bg-red-100 border-red-200">
                    <div class="quick-action-icon bg-red-600">
                        <i class="fas fa-users-cog text-white"></i>
                    </div>
                    <h4>Admins</h4>
                    <p>User management</p>
                </a>
            <?php endif; ?>

            <a href="profile.php" class="quick-action-card bg-gray-50 hover:bg-gray-100 border-gray-200">
                <div class="quick-action-icon bg-gray-600">
                    <i class="fas fa-user-cog text-white"></i>
                </div>
                <h4>Profile</h4>
                <p>Settings</p>
            </a>
        </div>
    </div>
</div>

<!-- Main Content Grid (Recent Projects and Community Feedback) -->
<div class="wp-grid wp-grid-responsive mb-6">
    <!-- Recent Projects -->
    <div class="wp-card">
        <div class="wp-card-header">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center">
                    <i class="fas fa-project-diagram mr-2 text-blue-600"></i>
                    Recent Projects
                </h3>
                <a href="projects.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="wp-card-content">
            <?php if (empty($recent_projects)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-folder-open text-4xl opacity-30 mb-4"></i>
                    <p class="text-lg font-medium mb-2">No projects yet</p>
                    <p class="text-sm text-gray-400 mb-4">Start by importing data or adding your first project!</p>
                    <?php if ($current_admin['role'] !== 'viewer'): ?>
                        <div class="flex flex-col sm:flex-row gap-2 justify-center">
                            <a href="import_csv.php" class="wp-btn wp-btn-primary">
                                <i class="fas fa-upload"></i>
                                Import CSV
                            </a>
                            <a href="create_project.php" class="wp-btn wp-btn-secondary">
                                <i class="fas fa-plus"></i>
                                Add Project
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_projects as $project): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate text-sm lg:text-base">
                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                </h4>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span><?php echo htmlspecialchars($project['department_name'] ?? 'No Department'); ?></span>
                                    <span class="hidden sm:inline">•</span>
                                    <span><?php echo format_date($project['created_at']); ?></span>
                                    <?php if ($project['progress_percentage'] > 0): ?>
                                        <span class="hidden sm:inline">•</span>
                                        <span class="text-blue-600"><?php echo $project['progress_percentage']; ?>% Complete</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <span class="wp-badge wp-badge-<?php echo $project['status'] === 'completed' ? 'success' : ($project['status'] === 'ongoing' ? 'info' : 'secondary'); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                                <a href="projects.php?edit=<?php echo $project['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Community Feedback -->
    <div class="wp-card">
        <div class="wp-card-header">
            <div class="flex items-center justify-between">
                <h3 class="flex items-center">
                    <i class="fas fa-comments mr-2 text-purple-600"></i>
                    Community Feedback
                </h3>
                <a href="feedback.php" class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                    Manage All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="wp-card-content">
            <?php if (empty($recent_feedback)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-comment-slash text-4xl opacity-30 mb-4"></i>
                    <p class="text-lg font-medium mb-2">No feedback yet</p>
                    <p class="text-sm text-gray-400">Community feedback will appear here</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recent_feedback as $feedback): ?>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1 min-w-0">
                                    <h5 class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                        <?php echo htmlspecialchars($feedback['project_name'] ?? 'Unknown Project'); ?>
                                    </h5>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        by <?php echo htmlspecialchars($feedback['citizen_name']); ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <?php if ($feedback['rating']): ?>
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $feedback['rating'] ? '' : '-o'; ?> text-xs"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="wp-badge wp-badge-<?php echo $feedback['feedback_status'] === 'responded' ? 'success' : ($feedback['feedback_status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($feedback['feedback_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-2">
                                <?php echo htmlspecialchars(substr($feedback['message'] ?? $feedback['subject'], 0, 100)); ?>
                                <?php echo strlen($feedback['message'] ?? $feedback['subject']) > 100 ? '...' : ''; ?>
                            </p>
                            <div class="flex items-center justify-between">
                                <p class="text-xs text-gray-400">
                                    <?php echo format_date($feedback['created_at']); ?>
                                </p>
                                <a href="feedback.php?view=<?php echo $feedback['id']; ?>" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Review <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Stats Grid (Updated and Mobile-Friendly) -->
<div class="stats-grid-mobile mb-6">
    <div class="stat-card-compact">
        <div class="stat-card-icon" style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i class="fas fa-project-diagram"></i>
        </div>
        <h3 class="stat-card-value"><?php echo number_format($total_projects); ?></h3>
        <p class="stat-card-label">Total Projects</p>
        <div class="stat-card-change text-green-600">
            <i class="fas fa-arrow-up text-xs"></i>
            <span class="text-xs"><?php echo number_format($this_month_projects); ?> this month</span>
        </div>
    </div>

    <div class="stat-card-compact">
        <div class="stat-card-icon" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fas fa-tasks"></i>
        </div>
        <h3 class="stat-card-value"><?php echo number_format($ongoing_projects); ?></h3>
        <p class="stat-card-label">Ongoing</p>
        <div class="stat-card-change text-blue-600">
            <i class="fas fa-clock text-xs"></i>
            <span class="text-xs">In Progress</span>
        </div>
    </div>

    <div class="stat-card-compact">
        <div class="stat-card-icon" style="background-color: rgba(34, 197, 94, 0.1); color: #22c55e;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 class="stat-card-value"><?php echo number_format($completed_projects); ?></h3>
        <p class="stat-card-label">Completed</p>
        <div class="stat-card-change text-green-600">
            <i class="fas fa-trophy text-xs"></i>
            <span class="text-xs">Finished</span>
        </div>
    </div>
</div>

<!-- System Status (MOVED TO LAST) -->
<div class="wp-card">
    <div class="wp-card-header">
        <h3 class="flex items-center">
            <i class="fas fa-server mr-2 text-green-500"></i>
            System Overview
        </h3>
    </div>
    <div class="wp-card-content">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl text-green-600 mb-2">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white">System Online</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">All services running</p>
            </div>

            <div class="text-center">
                <div class="text-3xl text-blue-600 mb-2">
                    <i class="fas fa-database"></i>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white">Database</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo number_format($total_projects); ?> projects stored</p>
            </div>

            <div class="text-center">
                <div class="text-3xl text-purple-600 mb-2">
                    <i class="fas fa-comments"></i>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white">Feedback</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo number_format($total_feedback); ?> total received</p>
            </div>

            <div class="text-center">
                <div class="text-3xl text-orange-600 mb-2">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white">Security</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">All systems secure</p>
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem 1rem;
    border: 1px solid;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
    text-align: center;
}

.quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.quick-action-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.quick-action-card h4 {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.quick-action-card p {
    color: #6b7280;
    font-size: 0.75rem;
    margin: 0;
}

.wp-grid-responsive {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    .wp-grid-responsive {
        grid-template-columns: 1fr 1fr;
    }
}

/* Compact stats grid for mobile */
.stats-grid-mobile {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (min-width: 640px) {
    .stats-grid-mobile {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .stats-grid-mobile {
        grid-template-columns: repeat(3, 1fr);
    }
}

.stat-card-compact {
    background: white;
    padding: 1rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    text-align: center;
    transition: all 0.2s ease;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card-compact .stat-card-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
}

.stat-card-compact .stat-card-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-card-compact .stat-card-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.stat-card-compact .stat-card-change {
    font-size: 0.75rem;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .wp-card-content {
        padding: 1rem;
    }

    .quick-action-card {
        padding: 1rem 0.5rem;
    }

    .quick-action-icon {
        width: 2rem;
        height: 2rem;
        margin-bottom: 0.5rem;
    }

    .quick-action-card h4 {
        font-size: 0.75rem;
    }

    .quick-action-card p {
        font-size: 0.625rem;
    }

    .stat-card-compact {
        padding: 0.75rem;
        min-height: 100px;
    }

    .stat-card-compact .stat-card-value {
        font-size: 1.25rem;
    }
}
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
