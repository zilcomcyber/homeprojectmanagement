<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Track county development projects and stay informed about ongoing and completed projects in your area'; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>migoriLogo.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    },
                    animation: {
                        'gradient-shift': 'gradient-shift 3s ease-in-out infinite',
                        'float': 'float 20s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.6s ease-out',
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/app.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/app.css'); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">

    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-900 transition-all duration-300">
    <!-- Modern Header with Glass Effect -->
    <header class="glass-header fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 lg:h-20">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-4">
                    <!-- Mobile Menu Button - Only for logged-in users -->
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <button id="mobile-menu-button" class="lg:hidden p-2 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 backdrop-blur-sm">
                        <i class="fas fa-bars text-gray-700"></i>
                    </button>
                    <?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center space-x-3 group">
                        <div class="relative">
                            <img src="<?php echo BASE_URL; ?>migoriLogo.png" alt="County Logo" class="h-10 w-10 lg:h-12 lg:w-12 rounded-xl shadow-lg group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-blue-400/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-lg lg:text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">
                                <?php echo APP_NAME; ?>
                            </h1>
                            <p class="text-xs lg:text-sm text-gray-600">Migori County</p>
                        </div>
                    </a>

                    <!-- Desktop Navigation -->
                    <nav class="hidden lg:flex space-x-8 ml-8">
                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <a href="<?php echo BASE_URL; ?>index.php" 
                           class="nav-link relative px-4 py-2 text-gray-700 hover:text-blue-600 transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 font-medium' : ''; ?>">
                            <span class="relative z-10">Projects</span>
                            <div class="absolute inset-0 bg-white/10 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" 
                           class="nav-link relative px-4 py-2 text-gray-700 hover:text-blue-600 transition-all duration-300">
                            <span class="relative z-10">Dashboard</span>
                            <div class="absolute inset-0 bg-white/10 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/projects.php" 
                           class="nav-link relative px-4 py-2 text-gray-700 hover:text-blue-600 transition-all duration-300">
                            <span class="relative z-10">Manage Projects</span>
                            <div class="absolute inset-0 bg-white/10 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/feedback.php" 
                           class="nav-link relative px-4 py-2 text-gray-700 hover:text-blue-600 transition-all duration-300">
                            <span class="relative z-10">Feedback</span>
                            <div class="absolute inset-0 bg-white/10 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Controls -->
                <div class="flex items-center space-x-3">

                    <!-- Desktop Auth Button -->
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <div class="hidden lg:flex items-center space-x-3">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                            </div>
                            <div class="text-xs text-gray-600">Administrator</div>
                        </div>
                        <a href="<?php echo BASE_URL; ?>logout.php" 
                           class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl" 
                           title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modern Mobile Navigation Menu -->
            <div id="mobile-menu" class="lg:hidden hidden border-t border-white/10 backdrop-blur-lg">
                <div class="px-4 py-6 space-y-2">
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <a href="<?php echo BASE_URL; ?>index.php" 
                       class="mobile-nav-link flex items-center px-4 py-3 rounded-xl text-gray-700 hover:text-blue-600 hover:bg-white/10 transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 font-medium bg-white/10' : ''; ?>">
                        <i class="fas fa-project-diagram mr-3 w-5"></i>
                        <span>Projects</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" 
                       class="mobile-nav-link flex items-center px-4 py-3 rounded-xl text-gray-700 hover:text-blue-600 hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/projects.php" 
                       class="mobile-nav-link flex items-center px-4 py-3 rounded-xl text-gray-700 hover:text-blue-600 hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-cogs mr-3 w-5"></i>
                        <span>Manage Projects</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/feedback.php" 
                       class="mobile-nav-link flex items-center px-4 py-3 rounded-xl text-gray-700 hover:text-blue-600 hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-comments mr-3 w-5"></i>
                        <span>Feedback</span>
                    </a>

                    <!-- Mobile Auth Section -->
                    <div class="border-t border-white/10 pt-4 mt-4">
                        <div class="px-4 py-3 text-sm text-gray-600">
                            Logged in as: <span class="font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>logout.php" 
                           class="mobile-nav-link flex items-center px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-all duration-300">
                            <i class="fas fa-sign-out-alt mr-3 w-5"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main content with proper spacing -->
    <main class="pt-16 lg:pt-20">