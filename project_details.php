<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'config.php';
require_once 'includes/functions.php';

$project_id = $_GET['id'] ?? 0;

// Get project details
$project = get_project_by_id($project_id);

if (!$project) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Check if project is private and user is not logged in
if ($project['visibility'] === 'private' && !isset($_SESSION['admin_id'])) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// Check if project is accessible to public
if ($project['visibility'] === 'private' && !$is_admin) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Get project steps
$stmt = $pdo->prepare("SELECT * FROM project_steps WHERE project_id = ? ORDER BY step_number");
$stmt->execute([$project_id]);
$project_steps = $stmt->fetchAll();

// Get project feedback
$stmt = $pdo->prepare("SELECT * FROM project_feedback WHERE project_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$project_id]);
$recent_feedback = $stmt->fetchAll();

// Get related ongoing projects
$stmt = $pdo->prepare("SELECT p.*, d.name as department_name, sc.name as sub_county_name, 
                              w.name as ward_name, COALESCE(AVG(r.rating), 0) as average_rating,
                              COUNT(DISTINCT r.id) as total_ratings
                       FROM projects p 
                       LEFT JOIN departments d ON p.department_id = d.id
                       LEFT JOIN sub_counties sc ON p.sub_county_id = sc.id  
                       LEFT JOIN wards w ON p.ward_id = w.id
                       LEFT JOIN project_ratings r ON p.id = r.project_id
                       WHERE p.status = 'ongoing' AND p.id != ?
                       GROUP BY p.id 
                       ORDER BY p.created_at DESC 
                       LIMIT 3");
$stmt->execute([$project_id]);
$related_projects = $stmt->fetchAll();

// Calculate actual quick stats
$total_steps_count = count($project_steps);
$completed_steps_count = 0;
foreach ($project_steps as $step) {
    if ($step['status'] === 'completed') {
        $completed_steps_count++;
    }
}

$page_title = htmlspecialchars($project['project_name']);
$page_description = 'View details, progress and location information for ' . htmlspecialchars($project['project_name']);
$show_nav = true;
include 'includes/header.php';
?>

<!-- Animated Background -->
<div class="animated-bg"></div>

<!-- Compact Header Section -->
<div class="bg-gradient-to-r from-slate-600 to-slate-700 text-white py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb Navigation -->
        <nav class="flex mb-3" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="<?php echo BASE_URL; ?>index.php" class="text-white/70 hover:text-white transition-colors flex items-center">
                        <i class="fas fa-home mr-1"></i>
                        Home
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-white/50 mx-2"></i>
                    <span class="text-white/90">Project Details</span>
                </li>
            </ol>
        </nav>

        <!-- Project Title and Status -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white leading-tight">
                    <?php echo htmlspecialchars($project['project_name']); ?>
                </h1>
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <span class="status-badge-modern <?php echo 'status-' . $project['status']; ?>">
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                    <span class="text-white/80 text-sm">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo $project['project_year']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex gap-2">
                <button onclick="toggleRatingForm()" class="btn-modern btn-primary-modern text-sm px-4 py-2">
                    <i class="fas fa-star"></i>
                    Rate
                </button>
                <button onclick="toggleFeedbackForm()" class="btn-modern btn-secondary-modern text-sm px-4 py-2">
                    <i class="fas fa-comment"></i>
                    Feedback
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 relative z-10">
    
    <!-- Project Overview Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Progress Card -->
        <div class="glass-card p-4 text-center">
            <?php 
            $progress = $project['progress_percentage'];
            $stroke_color = '#64748b';
            if ($progress <= 25) {
                $stroke_color = '#ef4444';
            } elseif ($progress <= 50) {
                $stroke_color = '#f59e0b';
            } elseif ($progress <= 75) {
                $stroke_color = '#3b82f6';
            } else {
                $stroke_color = '#10b981';
            }
            ?>
            <div class="progress-circle-modern mx-auto mb-2 relative" style="width: 60px; height: 60px;">
                <svg width="60" height="60" viewBox="0 0 36 36" class="circular-progress">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                          fill="none" stroke="rgba(148,163,184,0.2)" stroke-width="2"/>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                          fill="none" stroke="<?php echo $stroke_color; ?>" stroke-width="3" 
                          stroke-dasharray="<?php echo $progress; ?>, 100"
                          stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-gray-900 dark:text-white text-sm font-bold">
                        <?php echo $progress; ?>%
                    </span>
                </div>
            </div>
            <div class="text-gray-900 dark:text-white text-xs font-medium">Progress</div>
        </div>

        <!-- Steps Card -->
        <div class="glass-card p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                <?php echo $completed_steps_count; ?>/<?php echo $total_steps_count; ?>
            </div>
            <div class="text-gray-600 dark:text-gray-400 text-xs">Steps Complete</div>
        </div>

        <!-- Rating Card -->
        <div class="glass-card p-4 text-center">
            <div class="flex justify-center mb-1">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star text-sm <?php echo $i <= $project['average_rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                <?php endfor; ?>
            </div>
            <div class="text-gray-900 dark:text-white text-sm font-medium">
                <?php echo number_format($project['average_rating'], 1); ?>/5
            </div>
            <div class="text-gray-600 dark:text-gray-400 text-xs">
                (<?php echo $project['total_ratings']; ?> ratings)
            </div>
        </div>

        <!-- Department Card -->
        <div class="glass-card p-4 text-center">
            <div class="text-gray-900 dark:text-white text-sm font-medium mb-1">
                <i class="fas fa-building text-blue-500 mr-1"></i>
                Department
            </div>
            <div class="text-gray-600 dark:text-gray-400 text-xs">
                <?php echo htmlspecialchars($project['department_name']); ?>
            </div>
        </div>
    </div>

    <!-- Project Details Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Column -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Project Information Card -->
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                    Project Information
                </h2>
                
                <div class="prose dark:prose-invert mb-6">
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        <?php echo htmlspecialchars($project['description']); ?>
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-map-marker-alt text-red-500"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Location</span>
                        </div>
                        <p class="text-gray-900 dark:text-white text-sm">
                            <?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?>
                        </p>
                    </div>

                    <?php if ($project['contractor_name']): ?>
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-hard-hat text-yellow-500"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Contractor</span>
                        </div>
                        <p class="text-gray-900 dark:text-white text-sm">
                            <?php echo htmlspecialchars($project['contractor_name']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($project['start_date']): ?>
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-play-circle text-green-500"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</span>
                        </div>
                        <p class="text-gray-900 dark:text-white text-sm">
                            <?php echo format_date($project['start_date']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-calendar text-purple-500"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Project Year</span>
                        </div>
                        <p class="text-gray-900 dark:text-white text-sm">
                            <?php echo $project['project_year']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- Project Steps -->
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-tasks mr-3 text-blue-500"></i>
                    Project Timeline
                </h2>

                <?php if (!empty($project_steps)): ?>
                <div class="space-y-6">
                    <?php foreach ($project_steps as $index => $step): ?>
                    <div class="relative flex items-start space-x-4 fade-in-up" style="--stagger-delay: <?php echo $index * 0.1; ?>s">
                        <!-- Timeline line -->
                        <?php if ($index < count($project_steps) - 1): ?>
                        <div class="absolute left-6 top-12 w-0.5 h-16 bg-gradient-to-b from-gray-300 to-gray-200 dark:from-gray-600 dark:to-gray-700"></div>
                        <?php endif; ?>

                        <!-- Step indicator -->
                        <div class="flex-shrink-0 mt-1">
                            <?php if ($step['status'] === 'completed'): ?>
                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                    <i class="fas fa-check text-white text-lg"></i>
                                </div>
                            <?php elseif ($step['status'] === 'in_progress'): ?>
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-lg animate-pulse">
                                    <div class="w-4 h-4 bg-white rounded-full"></div>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-sm"><?php echo $step['step_number']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Step content -->
                        <div class="flex-1 min-w-0 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-2xl">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        <?php echo htmlspecialchars($step['step_name']); ?>
                                    </h4>
                                    <?php if ($step['description']): ?>
                                        <p class="text-gray-600 dark:text-gray-400 mb-3 leading-relaxed">
                                            <?php echo htmlspecialchars($step['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($step['expected_end_date']): ?>
                                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-calendar mr-2"></i>
                                            Expected completion: <?php echo format_date($step['expected_end_date']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    <?php echo $step['status'] === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 
                                              ($step['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 
                                               'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $step['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-tasks text-3xl text-gray-400 dark:text-gray-600"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No Timeline Available</h3>
                    <p class="text-gray-600 dark:text-gray-400">Project timeline steps have not been defined yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Feedback -->
            <?php if (!empty($recent_feedback)): ?>
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-comments mr-3 text-purple-500"></i>
                    Community Feedback
                </h2>
                <div class="space-y-4">
                    <?php foreach ($recent_feedback as $feedback): ?>
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-6 rounded-2xl border-l-4 border-blue-500">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php echo htmlspecialchars($feedback['subject']); ?>
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 mb-3 leading-relaxed">
                                    <?php echo htmlspecialchars(substr($feedback['message'], 0, 150)); ?>
                                    <?php if (strlen($feedback['message']) > 150): ?>...<?php endif; ?>
                                </p>
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-user mr-2"></i>
                                    <?php echo htmlspecialchars($feedback['citizen_name']); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock mr-2"></i>
                                    <?php echo format_date($feedback['created_at']); ?>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo get_feedback_status_badge_class($feedback['status']); ?>">
                                <?php echo ucfirst($feedback['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column -->
        <div class="space-y-6">
            <!-- Project Map Card -->
            <?php if ($project['location_coordinates']): ?>
            <div class="glass-card p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                    Location
                </h3>
                <div id="projectMap" class="h-48 bg-gray-200 dark:bg-gray-700 rounded-lg mb-2 overflow-hidden"></div>
                <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                    <?php echo htmlspecialchars($project['location_address'] ?: 'View project location on map'); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Related Projects Card -->
            <?php if (!empty($related_projects)): ?>
            <div class="glass-card p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-project-diagram mr-2 text-green-500"></i>
                    Related Projects
                </h3>
                <div class="space-y-3">
                    <?php foreach ($related_projects as $related_project): ?>
                    <a href="project_details.php?id=<?php echo $related_project['id']; ?>" 
                       class="block p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 group">
                        <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
                            <?php echo htmlspecialchars($related_project['project_name']); ?>
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            <?php echo htmlspecialchars($related_project['ward_name'] . ', ' . $related_project['sub_county_name']); ?>
                        </p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-1">
                                    <span class="text-white text-xs font-bold"><?php echo number_format($related_project['progress_percentage']); ?>%</span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">complete</span>
                            </div>
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-xs <?php echo $i <= $related_project['average_rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals remain the same but with updated styling classes -->
<!-- Rating Form Modal -->
<div id="ratingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="glass-card max-w-md w-full mx-4">
            <!-- Modal content remains the same but with glass-card styling -->
            <form id="ratingForm" method="POST" action="<?php echo BASE_URL; ?>api/rating.php">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="rating" id="selectedRating" value="5">

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Rate This Project</h3>
                    <button type="button" onclick="toggleRatingForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Star Rating -->
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Rate this project:</p>
                        <div class="flex justify-center space-x-3 mb-4" id="starRating">
                            <i class="fas fa-star text-5xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors" data-rating="1"></i>
                            <i class="fas fa-star text-5xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors" data-rating="2"></i>
                            <i class="fas fa-star text-5xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors" data-rating="3"></i>
                            <i class="fas fa-star text-5xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors" data-rating="4"></i>
                            <i class="fas fa-star text-5xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors" data-rating="5"></i>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">
                            <span id="ratingText" class="text-yellow-600">Select a rating</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="user_email" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white transition-all"
                               placeholder="your.email@example.com">
                    </div>
                </div>

                <div class="flex gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="toggleRatingForm()" 
                            class="btn-modern btn-secondary-modern flex-1 justify-center">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="btn-modern btn-primary-modern flex-1 justify-center">
                        <i class="fas fa-star"></i>
                        Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Feedback Form Modal -->
<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="glass-card max-w-2xl w-full mx-4">
            <form id="feedbackForm" method="POST" action="<?php echo BASE_URL; ?>api/feedback.php">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Share Your Feedback</h3>
                    <button type="button" onclick="toggleFeedbackForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your Name</label>
                            <input type="text" name="citizen_name" required 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email (Optional)</label>
                            <input type="email" name="citizen_email" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                        <input type="text" name="subject" required 
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                        <textarea name="message" rows="4" required 
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all"
                                  placeholder="Share your detailed feedback about this project..."></textarea>
                    </div>
                </div>

                <div class="flex gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="toggleFeedbackForm()" 
                            class="btn-modern btn-secondary-modern flex-1 justify-center">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="btn-modern btn-primary-modern flex-1 justify-center">
                        <i class="fas fa-paper-plane"></i>
                        Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scroll Progress Indicator -->
<div class="scroll-indicator"></div>

<script>
function toggleRatingForm() {
    const modal = document.getElementById('ratingModal');
    const isHidden = modal.classList.contains('hidden');

    if (isHidden) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

function toggleFeedbackForm() {
    const modal = document.getElementById('feedbackModal');
    const isHidden = modal.classList.contains('hidden');

    if (isHidden) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Rating system functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('#starRating i');
    const selectedRatingInput = document.getElementById('selectedRating');
    const ratingText = document.getElementById('ratingText');

    // Reset all stars to gray initially
    stars.forEach(star => {
        star.classList.remove('text-yellow-400');
        star.classList.add('text-gray-300');
    });
    selectedRatingInput.value = '';
    ratingText.textContent = 'Select a rating';

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            selectedRatingInput.value = rating;
            ratingText.textContent = rating + ' star' + (rating !== 1 ? 's' : '');

            // Update star display
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                }
            });
        });

        // Add hover effect
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('text-yellow-300');
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            stars.forEach(s => {
                s.classList.remove('text-yellow-300');
            });
        });
    });

    // Scroll Progress Indicator
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const maxHeight = document.body.scrollHeight - window.innerHeight;
        const progress = scrolled / maxHeight;
        document.querySelector('.scroll-indicator').style.setProperty('--scroll-progress', progress);
    });

    // Stagger animations
    const fadeElements = document.querySelectorAll('.fade-in-up');
    fadeElements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
});

// Initialize map if coordinates exist
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($project['location_coordinates']): ?>
    const coordinates = '<?php echo $project['location_coordinates']; ?>'.split(',');
    if (coordinates.length === 2) {
        const lat = parseFloat(coordinates[0]);
        const lng = parseFloat(coordinates[1]);

        if (!isNaN(lat) && !isNaN(lng)) {
            const map = L.map('projectMap').setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Use custom marker function
            if (typeof createCustomMarker === 'function') {
                createCustomMarker(lat, lng, {
                    project_name: '<?php echo addslashes($project['project_name']); ?>',
                    ward_name: '<?php echo addslashes($project['ward_name']); ?>',
                    sub_county_name: '<?php echo addslashes($project['sub_county_name']); ?>'
                }).addTo(map)
                .bindPopup('<?php echo addslashes($project['project_name']); ?>');
            } else {
                // Fallback to regular marker
                L.marker([lat, lng]).addTo(map)
                    .bindPopup('<?php echo addslashes($project['project_name']); ?>');
            }
        }
    }
    <?php endif; ?>
});

// Handle form submissions
document.getElementById('ratingForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your rating!');
            toggleRatingForm();
            this.reset();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your rating.');
    });
});

document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your feedback!');
            toggleFeedbackForm();
            this.reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your feedback.');
    });
});
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<?php include 'includes/footer.php'; ?>