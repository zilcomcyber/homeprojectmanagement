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
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - <?php echo APP_NAME; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>generated-icon.png">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Admin Styles -->
    <style>
        .sidebar-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 2px;
        }
        .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.7);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo BASE_URL; ?>generated-icon.png" alt="Logo" class="h-8 w-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo APP_NAME; ?></h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Admin Panel</p>
                        </div>
                    </div>
                    <button id="sidebar-close" class="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto sidebar-scrollbar">
                    <a href="admin.php" class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-home mr-3 flex-shrink-0"></i>
                        Admin Home
                    </a>

                    <?php if ($current_admin['role'] === 'super_admin'): ?>
                        <a href="admin/dashboard.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-tachometer-alt mr-3 flex-shrink-0"></i>
                            Detailed Dashboard
                        </a>
                    <?php endif; ?>

                    <?php if ($current_admin['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-chart-bar mr-3 flex-shrink-0"></i>
                            Dashboard
                        </a>
                    <?php endif; ?>

                    <?php if ($current_admin['role'] !== 'viewer'): ?>
                        <a href="admin/projects.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-project-diagram mr-3 flex-shrink-0"></i>
                            Manage Projects
                        </a>

                        <a href="admin/create_project.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-plus-circle mr-3 flex-shrink-0"></i>
                            Add Project
                        </a>

                        <a href="admin/comments.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-comments mr-3 flex-shrink-0"></i>
                            Feedback
                        </a>
                    <?php endif; ?>

                    <?php if ($current_admin['role'] === 'super_admin'): ?>
                        <a href="admin/import_csv.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-upload mr-3 flex-shrink-0"></i>
                            Import CSV
                        </a>

                        <a href="admin/manage_admins.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-users-cog mr-3 flex-shrink-0"></i>
                            Manage Admins
                        </a>
                    <?php endif; ?>

                    <!-- Divider -->
                    <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Profile Settings - Available to all roles -->
                    <a href="admin/profile.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-user-cog mr-3 flex-shrink-0"></i>
                        Profile Settings
                    </a>
                </nav>

                <!-- User Info -->
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                <?php echo htmlspecialchars($current_admin['name']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo ucfirst($current_admin['role']); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <button id="user-menu-button" class="p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>

                    <!-- User Menu Dropdown -->
                    <div id="user-menu" class="hidden absolute bottom-16 left-4 right-4 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Admin Portal
                        </h1>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <button id="theme-toggle" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:block"></i>
                        </button>

                        <!-- Notifications -->
                        <button class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 relative">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-400"></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="px-4 py-6 sm:px-0">
                    <div class="border-4 border-dashed border-gray-200 dark:border-gray-700 rounded-lg p-8">
                        <div class="text-center">
                            <i class="fas fa-tachometer-alt text-4xl text-blue-600 dark:text-blue-400 mb-4"></i>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">County Project Management</h2>
                            <p class="text-gray-600 dark:text-gray-300 mb-8">Select an option below to manage your county projects</p>

                            <!-- Quick Actions -->
                            <div class="grid grid-cols-1 md:grid-cols-<?php echo $current_admin['role'] === 'super_admin' ? '3' : '2'; ?> gap-6 mt-8">
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

                                <!-- Import Data - Only for Super Admin -->
                                <?php if ($current_admin['role'] === 'super_admin'): ?>
                                <a href="admin/import_csv.php" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-green-300 dark:hover:border-green-600 transition-all group">
                                    <div class="text-center">
                                        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900 group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                                            <i class="fas fa-file-upload text-green-600 dark:text-green-400 text-xl"></i>
                                        </div>
                                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Import Data</h3>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Upload project data from CSV/Excel files</p>
                                    </div>
                                </a>
                                <?php endif; ?>

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
                                <?php if ($current_admin['role'] === 'super_admin' || $current_admin['role'] === 'admin'): ?>
                                    <a href="admin/dashboard.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-chart-bar mr-2"></i>
                                        <?php echo $current_admin['role'] === 'super_admin' ? 'Detailed Dashboard' : 'Dashboard'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Scripts -->
    <script>
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        sidebarToggle?.addEventListener('click', openSidebar);
        sidebarClose?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

        // User menu toggle
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');

        userMenuButton?.addEventListener('click', function() {
            userMenu.classList.toggle('hidden');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!userMenuButton?.contains(event.target) && !userMenu?.contains(event.target)) {
                userMenu?.classList.add('hidden');
            }
        });

        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark');
        }

        themeToggle?.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });

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

                    }
                })
                .catch(error => {
                    console.error('Error updating stats:', error);
                });
        }
    </script>
</body>
</html>