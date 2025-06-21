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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>migoriLogo.png">

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

    <!-- Custom Admin Mobile CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-mobile.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/app.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="admin-wrapper">
        <!-- Mobile Overlay -->
        <div id="mobile-overlay" class="mobile-overlay"></div>
        
        <!-- Sidebar -->
        <aside id="admin-sidebar" class="admin-sidebar">
            <!-- Logo/Brand -->
            <div class="sidebar-brand">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo BASE_URL; ?>migoriLogo.png" alt="Logo" class="h-8 w-8">
                    <div>
                        <h2><?php echo APP_NAME; ?></h2>
                        <p>Admin Panel</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <a href="./index" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    Admin Dashboard
                </a>

                <?php if ($current_admin['role'] === 'super_admin' || $current_admin['role'] === 'admin'): ?>
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Analytics
                    </a>
                <?php endif; ?>

                <?php if ($current_admin['role'] !== 'viewer'): ?>
                    <a href="projects.php" class="<?php echo $current_page === 'projects.php' ? 'active' : ''; ?>">
                        <i class="fas fa-project-diagram"></i>
                        Manage Projects
                    </a>

                    <a href="create_project.php" class="<?php echo $current_page === 'create_project.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        Add New Project
                    </a>

                    <a href="feedback.php" class="<?php echo $current_page === 'feedback.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        Community Feedback
                    </a>
                <?php endif; ?>

                <?php if ($current_admin['role'] === 'super_admin'): ?>
                    <div class="sidebar-divider"></div>
                    
                    <a href="import_csv.php" class="<?php echo $current_page === 'import_csv.php' ? 'active' : ''; ?>">
                        <i class="fas fa-upload"></i>
                        Import Data
                    </a>

                    <a href="manage_admins.php" class="<?php echo $current_page === 'manage_admins.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i>
                        Admin Users
                    </a>
                <?php endif; ?>

                <div class="sidebar-divider"></div>

                <a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    Profile Settings
                </a>

                <a href="../index.php">
                    <i class="fas fa-external-link-alt"></i>
                    View Public Site
                </a>
            </nav>

            <!-- User Info -->
            <div class="sidebar-user">
                <a href="profile.php" class="sidebar-user-profile">
                    <div class="sidebar-user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="sidebar-user-info">
                        <h4><?php echo htmlspecialchars($current_admin['name']); ?></h4>
                        <p><?php 
                            switch($current_admin['role']) {
                                case 'super_admin': echo 'Super Admin'; break;
                                case 'admin': echo 'Administrator'; break;
                                case 'viewer': echo 'Viewer'; break;
                                default: echo 'Staff';
                            }
                        ?></p>
                    </div>
                </a>

                <a href="../logout.php" class="sidebar-nav" style="color: #ef4444; padding: 0.5rem 1rem; margin-top: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="admin-header-content">
                    <div class="admin-header-left">
                        <button id="mobile-menu-toggle" class="mobile-menu-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></h1>
                    </div>

                    <div class="admin-header-right">
                        <!-- Theme Toggle -->
                        <button id="theme-toggle" class="header-btn">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:block"></i>
                        </button>

                        <!-- Notifications -->
                        <button class="header-btn relative">
                            <i class="fas fa-bell"></i>
                            <span class="absolute -top-1 -right-1 block h-2 w-2 rounded-full bg-red-400"></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="admin-content">
                <?php 
                // Display any flash messages
                if (isset($_SESSION['success_message'])): ?>
                    <div class="wp-notice wp-notice-success">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2"></i>
                            <div><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="wp-notice wp-notice-error">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <div><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($content)) echo $content; ?>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Mobile menu functionality
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const adminSidebar = document.getElementById('admin-sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        function toggleMobileMenu() {
            adminSidebar.classList.toggle('mobile-open');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = adminSidebar.classList.contains('mobile-open') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            adminSidebar.classList.remove('mobile-open');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        mobileMenuToggle?.addEventListener('click', toggleMobileMenu);
        mobileOverlay?.addEventListener('click', closeMobileMenu);

        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                closeMobileMenu();
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

        // Auto-hide notices
        setTimeout(function() {
            const notices = document.querySelectorAll('.wp-notice');
            notices.forEach(function(notice) {
                notice.style.opacity = '0';
                notice.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    notice.remove();
                }, 300);
            });
        }, 5000);
    </script>

    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
