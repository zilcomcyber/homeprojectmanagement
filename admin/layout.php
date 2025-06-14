<?php
require_once '../config.php';
require_once '../includes/auth.php';

require_admin();
$current_admin = get_current_admin();

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : 'Admin - ' . APP_NAME; ?></title>

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
                    <a href="../admin.php" class="<?php echo $current_page === 'admin.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-home mr-3 flex-shrink-0"></i>
                        Admin Home
                    </a>
                    
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-tachometer-alt mr-3 flex-shrink-0"></i>
                        Dashboard
                    </a>

                    <?php if ($current_admin['role'] !== 'viewer'): ?>
                        <a href="projects.php" class="<?php echo $current_page === 'projects.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-project-diagram mr-3 flex-shrink-0"></i>
                            Manage Projects
                        </a>

                        <a href="create_project.php" class="<?php echo $current_page === 'create_project.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-plus-circle mr-3 flex-shrink-0"></i>
                            Add Project
                        </a>

                        <a href="feedback.php" class="<?php echo $current_page === 'feedback.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-comments mr-3 flex-shrink-0"></i>
                            Feedback
                        </a>
                    <?php endif; ?>

                    <?php if ($current_admin['role'] === 'super_admin'): ?>
                        <a href="import_csv.php" class="<?php echo $current_page === 'import_csv.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-upload mr-3 flex-shrink-0"></i>
                            Import CSV
                        </a>

                        <a href="manage_admins.php" class="<?php echo $current_page === 'manage_admins.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                            <i class="fas fa-users-cog mr-3 flex-shrink-0"></i>
                            Manage Admins
                        </a>
                    <?php endif; ?>

                    <!-- Divider -->
                    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                    <!-- Profile Settings - Available to all roles -->
                    <a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-r-2 border-blue-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-user-cog mr-3 flex-shrink-0"></i>
                        Profile Settings
                    </a>

                    <a href="../index.php" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 group flex items-center px-3 py-2 text-sm font-medium rounded-l-md transition-colors">
                        <i class="fas fa-home mr-3 flex-shrink-0"></i>
                        View Site
                    </a>
                </nav>

                <!-- User Info -->
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                    <!-- User Profile Card -->
                    <a href="profile.php" class="block mb-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    <?php echo htmlspecialchars($current_admin['name']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php 
                                    switch($current_admin['role']) {
                                        case 'super_admin':
                                            echo 'Administrator';
                                            break;
                                        case 'admin':
                                            echo 'Manager';
                                            break;
                                        case 'viewer':
                                            echo 'Viewer';
                                            break;
                                        default:
                                            echo 'Staff';
                                    }
                                    ?>
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                    <i class="fas fa-cog mr-1"></i>
                                    Manage Profile
                                </p>
                            </div>
                        </div>
                    </a>

                    <!-- Additional Admin Actions -->
                    <?php if ($current_admin['role'] === 'super_admin'): ?>
                        <div class="space-y-1 mb-3">
                            <a href="manage_admins.php" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                                <i class="fas fa-users-cog mr-2"></i>
                                Manage Admins
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Logout -->
                    <a href="../logout.php" class="block px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
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
                            <?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?>
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
                <?php if (isset($content)) echo $content; ?>
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
    </script>

    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>