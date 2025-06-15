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

// Get project comments
$project_comments = get_project_comments($project_id);

// Debug: Log comment count for troubleshooting
error_log("Project ID: $project_id, Comments found: " . count($project_comments));

// Get related ongoing projects
$stmt = $pdo->prepare("SELECT p.*, d.name as department_name, sc.name as sub_county_name, 
                              w.name as ward_name
                       FROM projects p 
                       LEFT JOIN departments d ON p.department_id = d.id
                       LEFT JOIN sub_counties sc ON p.sub_county_id = sc.id  
                       LEFT JOIN wards w ON p.ward_id = w.id
                       WHERE p.status = 'ongoing' AND p.id != ?
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

// Helper function to format time ago
function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    if ($time < 31536000) return floor($time / 2592000) . ' months ago';
    return floor($time / 31536000) . ' years ago';
}

$page_title = htmlspecialchars($project['project_name']);
$page_description = 'View details, progress and location information for ' . htmlspecialchars($project['project_name']);
$show_nav = true;

// Add Leaflet CSS and JS
echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>';

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
                <button onclick="scrollToComments()" class="btn-modern btn-secondary-modern text-sm px-4 py-2">
                    <i class="fas fa-comments"></i>
                    Join Discussion
                </button>
                <span class="text-white/80 text-sm bg-white/20 px-3 py-2 rounded-full">
                    <i class="fas fa-comment-dots mr-1"></i>
                    <?php echo count($project_comments); ?> comments
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 relative z-10">

    <!-- Project Overview Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <!-- Progress Card -->
        <div class="glass-card p-6 text-center">
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
            <div class="progress-circle-modern mx-auto mb-3 relative" style="width: 100px; height: 100px;">
                <svg width="100" height="100" viewBox="0 0 36 36" class="circular-progress">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                          fill="none" stroke="rgba(148,163,184,0.2)" stroke-width="2"/>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                          fill="none" stroke="<?php echo $stroke_color; ?>" stroke-width="3" 
                          stroke-dasharray="<?php echo $progress; ?>, 100"
                          stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-gray-900 dark:text-white text-xl font-bold">
                        <?php echo $progress; ?>%
                    </span>
                </div>
            </div>
            <div class="text-gray-900 dark:text-white text-sm font-medium">Progress</div>
        </div>

        <!-- Steps Card -->
        <div class="glass-card p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                <?php echo $completed_steps_count; ?>/<?php echo $total_steps_count; ?>
            </div>
            <div class="text-gray-600 dark:text-gray-400 text-xs">Steps Complete</div>
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

    <!-- Project Location Map -->
    <?php if (!empty($project['location_coordinates'])): ?>
    <div class="glass-card p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-map-marker-alt mr-3 text-red-500"></i>
            Project Location
        </h2>
        <div id="projectMap" class="w-full h-64 rounded-lg border border-gray-200 dark:border-gray-700"></div>
    </div>
    <?php endif; ?>

    <!-- Project Details Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Column -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Project Timeline -->
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
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

            <!-- Community Comments Section -->
            <div id="comments-section" class="comment-system p-6">
                <h3 class="text-xl font-semibold text-primary mb-6 flex items-center">
                    <i class="fas fa-comments mr-3 text-blue-500"></i>
                    Community Discussion
                    <span class="ml-auto text-sm font-normal text-muted">
                        <?php echo count($project_comments); ?> <?php echo count($project_comments) === 1 ? 'comment' : 'comments'; ?>
                    </span>
                </h3>

                <!-- Display Comments -->
                <div id="comments-container" class="space-y-6 mb-6">
                    <?php 
                    function displayComments($comments, $parent_id = 0, $depth = 0) {
                        $max_depth = 3; // Limit nesting depth
                        foreach ($comments as $comment) {
                            if (isset($comment['parent_comment_id']) && $comment['parent_comment_id'] == $parent_id) {
                                $margin_class = $depth > 0 ? 'ml-' . min($depth * 8, 16) : '';
                                $border_class = $depth > 0 ? 'border-l-2 border-gray-200 dark:border-gray-600 pl-4' : '';
                                ?>
                                <div class="comment-item <?php echo $margin_class; ?> <?php echo $border_class; ?>" data-comment-id="<?php echo $comment['id']; ?>">

                                    <!-- Show pending approval notice for user's pending comments -->
                                    <?php if (isset($comment['is_user_pending']) && $comment['is_user_pending']): ?>
                                    <div class="pending-approval mb-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                            <span class="text-yellow-800 text-sm font-medium">
                                                Awaiting approval - Only you can see this comment until it's approved
                                            </span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="comment-bubble <?php echo !empty($comment['is_admin_comment']) ? 'admin-comment' : ''; ?>">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($comment['is_admin_comment'])): ?>
                                                    <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-lg">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-lg">
                                                        <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <h4 class="comment-author">
                                                        <?php echo htmlspecialchars($comment['user_name']); ?>
                                                        <?php if (!empty($comment['is_admin_comment'])): ?>
                                                            <span class="status-badge bg-red-100 text-red-800 ml-2">
                                                                <i class="fas fa-shield-alt mr-1"></i>Admin
                                                            </span>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <span class="comment-meta">
                                                        <?php echo time_ago($comment['created_at']); ?>
                                                    </span>
                                                </div>
                                                <div class="comment-text">
                                                    <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                                </div>

                                                <!-- Show admin response if exists -->
                                                <?php if (!empty($comment['admin_response'])): ?>
                                                <div class="admin-response mt-3 p-3 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 rounded-r-lg">
                                                    <div class="flex items-start space-x-2">
                                                        <div class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center text-white text-xs">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center space-x-2 mb-1">
                                                                <span class="text-sm font-medium text-red-600 dark:text-red-400">Admin Response</span>
                                                            </div>
                                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                                <?php echo nl2br(htmlspecialchars($comment['admin_response'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <!-- Reply Button -->
                                                <?php if ($depth < $max_depth): ?>
                                                <button onclick="replyToComment(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['user_name']); ?>')" 
                                                        class="btn-reply">
                                                    <i class="fas fa-reply mr-1"></i>Reply
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nested Replies -->
                                    <?php if ($depth < $max_depth) displayComments($comments, $comment['id'], $depth + 1); ?>
                                </div>
                                <?php
                            }
                        }
                    }

                    if (!empty($project_comments)) {
                        displayComments($project_comments);
                    } else {
                        echo '<div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-comments text-4xl mb-4"></i>
                            <p>No comments yet. Be the first to share your thoughts!</p>
                        </div>';
                    }
                    ?>
                </div>

                <!-- Add New Comment Form -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-lg font-semibold text-primary mb-4">Join the Discussion</h4>
                    <form id="commentForm" class="reply-form">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <input type="hidden" name="parent_comment_id" value="0">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-secondary mb-1">Your Name *</label>
                                <input type="text" name="citizen_name" required 
                                       class="form-input w-full px-3 py-2 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-secondary mb-1">Email (Optional)</label>
                                <input type="email" name="citizen_email" 
                                       class="form-input w-full px-3 py-2 rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-secondary mb-1">Your Comment *</label>
                            <textarea name="message" rows="4" required 
                                      placeholder="Share your thoughts about this project..."
                                      class="form-input w-full px-3 py-2 rounded-md resize-none"></textarea>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Submit Comment
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Sidebar Content -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Stats Card -->
            <div class="glass-card p-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-3 text-blue-500"></i>
                    Quick Stats
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Progress:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?php echo $project['progress_percentage']; ?>%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Steps Complete:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?php echo $completed_steps_count; ?>/<?php echo $total_steps_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Related Projects Card -->
            <?php if (!empty($related_projects)): ?>
            <div class="glass-card p-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-project-diagram mr-3 text-blue-500"></i>
                    Related Projects
                </h3>
                <div class="space-y-4">
                    <?php foreach ($related_projects as $related_project): ?>
                    <a href="project_details.php?id=<?php echo $related_project['id']; ?>" class="block hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg p-3 transition-colors duration-200">
                        <h4 class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($related_project['project_name']); ?></h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            <i class="fas fa-map-marker-alt mr-1 text-red-500"></i>
                            <?php echo htmlspecialchars($related_project['ward_name'] . ', ' . $related_project['sub_county_name']); ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/app.js"></script>

<script>
// Scroll to comments functionality
function scrollToComments() {
    const commentsSection = document.getElementById('comments-section');
    if (commentsSection) {
        commentsSection.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });

        // Add a subtle highlight effect
        commentsSection.style.transform = 'scale(1.02)';
        commentsSection.style.transition = 'transform 0.3s ease';
        setTimeout(() => {
            commentsSection.style.transform = 'scale(1)';
        }, 300);
    }
}

// Comment functionality
function replyToComment(commentId, userName) {
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        // Set parent comment id
        commentForm.querySelector('input[name="parent_comment_id"]').value = commentId;

        // Update the comment form's title/placeholder to indicate replying
        const commentTextarea = commentForm.querySelector('textarea[name="message"]');
        commentTextarea.placeholder = `Replying to ${userName}...`;
        
        // Scroll to comment form
        commentForm.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        //Focus to the textarea
        commentTextarea.focus();
    }
}

// Initialize map if coordinates exist
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($project['location_coordinates'])): ?>
    const coordinates = '<?php echo $project['location_coordinates']; ?>'.split(',');
    if (coordinates.length === 2) {
        const lat = parseFloat(coordinates[0]);
        const lng = parseFloat(coordinates[1]);

        if (!isNaN(lat) && !isNaN(lng)) {
            // Initialize map
            const map = L.map('projectMap').setView([lat, lng], 15);

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(map);

            // Custom marker icon
            const markerIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-8 h-8 rounded-full border-2 border-white shadow-lg bg-red-500 flex items-center justify-center">
                         <i class="fas fa-map-marker-alt text-white"></i>
                       </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            // Create marker with popup
            const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);
            marker.bindPopup(`
                <div class="text-center p-2">
                    <h4 class="font-semibold text-gray-900 mb-1"><?php echo addslashes($project['project_name']); ?></h4>
                    <p class="text-sm text-gray-600"><?php echo addslashes($project['ward_name'] . ', ' . $project['sub_county_name']); ?></p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </div>
                </div>
            `).openPopup();

            // Add map controls
            L.control.scale().addTo(map);
        }
    }
    <?php endif; ?>
});

// Handle main comment form submission
document.getElementById('commentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitComment(this);
});

async function submitComment(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Disable submit button and show loading
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('api/feedback.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            showNotification('Comment submitted successfully! It will appear after approval.', 'success');

            // Reset form
            form.reset();

            // Optionally reload page after a delay to show new comment
            setTimeout(() => {
                window.location.reload();
            }, 2000);

        } else {
            showNotification(data.message || 'Failed to submit comment', 'error');
        }
    } catch (error) {
        console.error('Comment submission error:', error);
        showNotification('Failed to submit comment. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 max-w-sm`;
    notification.style.background = type === 'success' ? '#10b981' : '#ef4444';
    notification.style.color = 'white';

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
</body>
</html>