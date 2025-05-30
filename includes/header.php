<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Track county development projects and stay informed about ongoing and completed projects in your area'; ?>">

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

    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/app.css">
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white transition-colors duration-200">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo and Navigation -->
                <div class="flex items-center space-x-8">
                    <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center space-x-3">
                        <img src="<?php echo BASE_URL; ?>generated-icon.png" alt="County Logo" class="h-10 w-10">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo APP_NAME; ?></h1>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Migori County</p>
                        </div>
                    </a>

                    <?php if (isset($show_nav) && $show_nav): ?>
                    <nav class="hidden md:flex space-x-6">
                        <a href="<?php echo BASE_URL; ?>index.php" 
                           class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 dark:text-blue-400 font-medium' : ''; ?>">
                            Projects
                        </a>
                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" 
                           class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600 dark:text-blue-400 font-medium' : ''; ?>">
                            Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/projects.php" 
                           class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            Manage Projects
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/feedback.php" 
                           class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            Feedback
                        </a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>

                <!-- Controls -->
                <div class="flex items-center space-x-3">
                    <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                        <i class="fas fa-moon dark:hidden text-gray-600"></i>
                        <i class="fas fa-sun hidden dark:inline text-gray-300"></i>
                    </button>

                    <?php if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']): ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white w-10 h-10 rounded-lg transition-all" title="Admin Login">
                        <i class="fas fa-user-shield"></i>
                    </a>
                    <?php else: ?>
                    <!-- Admin User Menu -->
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400 hidden md:inline">
                            <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        </span>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white w-10 h-10 rounded-lg transition-all" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>