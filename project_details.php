<?php
require_once 'config.php';
require_once 'includes/functions.php';

$project_id = $_GET['id'] ?? 0;
$project = get_project_by_id($project_id);

if (!$project) {
    header('Location: ' . BASE_URL . 'index.php?error=Project not found');
    exit();
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
                       LIMIT 5");
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

// Get budget information
$stmt = $pdo->prepare("SELECT allocated_budget, spent_budget FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$budget_info = $stmt->fetch();

$page_title = htmlspecialchars($project['project_name']);
$page_description = 'View details, progress and location information for ' . htmlspecialchars($project['project_name']);
$show_nav = true;
$additional_css = ['assets/css/style.css'];
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Hero Section -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-4">
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php" class="text-gray-400 hover:text-gray-500">
                                    <i class="fas fa-home"></i>
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Project Details</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['project_name']); ?></h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2"><?php echo htmlspecialchars($project['description']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo get_status_badge_class($project['status']); ?>">
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                    <div class="flex items-center space-x-2">
                        <?php echo generate_star_rating($project['average_rating'], $project['total_ratings']); ?>
                    </div>
                    <button onclick="toggleRatingForm()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-star mr-2"></i>
                        Rate Project
                    </button>
                    <button onclick="toggleFeedbackForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-comment mr-2"></i>
                        Give Feedback
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Project Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Project Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Department</label>
                            <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['department_name']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Location</label>
                            <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Budget</label>
                            <p class="text-gray-900 dark:text-white"><?php echo format_currency($project['budget']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Project Year</label>
                            <p class="text-gray-900 dark:text-white"><?php echo $project['project_year']; ?></p>
                        </div>
                        <?php if ($project['contractor_name']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Contractor</label>
                            <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['contractor_name']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($project['start_date']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</label>
                            <p class="text-gray-900 dark:text-white"><?php echo format_date($project['start_date']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Tracking -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Progress Tracking</h2>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo $project['progress_percentage']; ?>%</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Complete</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-6">
                        <div class="<?php echo get_progress_color_class($project['progress_percentage']); ?> h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                    </div>

                    <!-- Project Steps -->
                    <?php if (!empty($project_steps)): ?>
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Project Steps</h3>
                        <div class="space-y-3">
                            <?php foreach ($project_steps as $step): ?>
                            <div class="flex items-start space-x-4 p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <div class="flex-shrink-0">
                                    <?php if ($step['status'] === 'completed'): ?>
                                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-white text-sm"></i>
                                        </div>
                                    <?php elseif ($step['status'] === 'in_progress'): ?>
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300"><?php echo $step['step_number']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($step['step_name']); ?></h4>
                                    <?php if ($step['description']): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($step['description']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($step['expected_end_date']): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            Expected: <?php echo format_date($step['expected_end_date']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $step['status'] === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 
                                                  ($step['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 
                                                   'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $step['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-tasks text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Steps Defined</h3>
                        <p class="text-gray-600 dark:text-gray-400">Project steps have not been defined yet.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Feedback -->
                <?php if (!empty($recent_feedback)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Recent Feedback</h2>
                    <div class="space-y-4">
                        <?php foreach ($recent_feedback as $feedback): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($feedback['subject']); ?></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars(substr($feedback['message'], 0, 120)); ?>...</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        By <?php echo htmlspecialchars($feedback['citizen_name']); ?> - <?php echo format_date($feedback['created_at']); ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_feedback_status_badge_class($feedback['status']); ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Map -->
                <?php if ($project['location_coordinates']): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Location</h3>
                    <div id="projectMap" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        <?php echo htmlspecialchars($project['location_address'] ?: 'Location coordinates: ' . $project['location_coordinates']); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Steps</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo $total_steps_count; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Completed</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo $completed_steps_count; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Remaining</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo $total_steps_count - $completed_steps_count; ?></span>
                        </div>
                        <?php if ($budget_info && $budget_info['allocated_budget']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Allocated</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo format_currency($budget_info['allocated_budget']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($budget_info && $budget_info['spent_budget']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Spent</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?php echo format_currency($budget_info['spent_budget']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related Projects -->
                <?php if (!empty($related_projects)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Related Ongoing Projects</h3>
                    <div class="space-y-3">
                        <?php foreach ($related_projects as $related_project): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <a href="project_details.php?id=<?php echo $related_project['id']; ?>" class="block hover:bg-gray-50 dark:hover:bg-gray-700 rounded p-2 transition-colors">
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars($related_project['project_name']); ?></h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    <?php echo htmlspecialchars($related_project['ward_name'] . ', ' . $related_project['sub_county_name']); ?>
                                </p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo number_format($related_project['progress_percentage'], 1); ?>% complete
                                    </span>
                                    <?php echo generate_star_rating($related_project['average_rating'], $related_project['total_ratings'], 'text-xs'); ?>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rating Form Modal -->
    <div id="ratingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <form id="ratingForm" method="POST" action="<?php echo BASE_URL; ?>api/rating.php">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="rating" id="selectedRating" value="5">

                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rate This Project</h3>
                        <button type="button" onclick="toggleRatingForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Star Rating -->
                        <div class="text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Click on stars to rate:</p>
                            <div class="flex justify-center space-x-1" id="starRating">
                                <i class="fas fa-star text-3xl cursor-pointer text-yellow-400 hover:text-yellow-500" data-rating="1"></i>
                                <i class="fas fa-star text-3xl cursor-pointer text-yellow-400 hover:text-yellow-500" data-rating="2"></i>
                                <i class="fas fa-star text-3xl cursor-pointer text-yellow-400 hover:text-yellow-500" data-rating="3"></i>
                                <i class="fas fa-star text-3xl cursor-pointer text-yellow-400 hover:text-yellow-500" data-rating="4"></i>
                                <i class="fas fa-star text-3xl cursor-pointer text-yellow-400 hover:text-yellow-500" data-rating="5"></i>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                You selected: <span id="ratingText" class="font-medium">5 stars</span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Name (Optional)</label>
                            <input type="text" name="user_name" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Optional)</label>
                            <input type="email" name="user_email" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment (Optional)</label>
                            <textarea name="comment" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" onclick="toggleRatingForm()" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors">
                            Submit Rating
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Feedback Form Modal -->
    <div id="feedbackModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full">
                <form id="feedbackForm" method="POST" action="<?php echo BASE_URL; ?>api/feedback.php">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Share Your Feedback</h3>
                        <button type="button" onclick="toggleFeedbackForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Name</label>
                                <input type="text" name="citizen_name" required 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Optional)</label>
                                <input type="email" name="citizen_email" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                            <input type="text" name="subject" required 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                            <textarea name="message" rows="4" required 
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" onclick="toggleFeedbackForm()" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                            Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            selectedRatingInput.value = rating;
            ratingText.textContent = rating + ' star' + (rating !== 1 ? 's' : '');
            
            // Update star display
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('fas');
                    s.classList.add('far');
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                }
            });
        });

        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('text-yellow-500');
                } else {
                    s.classList.remove('text-yellow-500');
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            stars.forEach(s => {
                s.classList.remove('text-yellow-500');
            });
        });
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

            L.marker([lat, lng]).addTo(map)
                .bindPopup('<?php echo htmlspecialchars($project['project_name']); ?>');
        }
    }
    <?php endif; ?>
});

// Handle rating form submission
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
            // Update the rating display on the page
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

// Handle feedback form submission
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

<?php include 'includes/footer.php'; ?>