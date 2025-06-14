<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get filter parameters for search functionality
$search_query = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'grid'; // grid, list, map

// Function to get projects by status with limit
function get_projects_by_status($status, $search_query = '', $limit = 6) {
    global $pdo;
    $sql = "SELECT p.*, d.name as department_name, w.name as ward_name, 
                   sc.name as sub_county_name, c.name as county_name,
                   COALESCE(AVG(r.rating), 0) as average_rating,
                   COUNT(DISTINCT r.id) as total_ratings
            FROM projects p
            JOIN departments d ON p.department_id = d.id
            JOIN wards w ON p.ward_id = w.id
            JOIN sub_counties sc ON p.sub_county_id = sc.id
            JOIN counties c ON p.county_id = c.id
            LEFT JOIN project_ratings r ON p.id = r.project_id
            WHERE p.visibility = 'published' AND p.status = ?";

    $params = [$status];

    if (!empty($search_query)) {
        $sql .= " AND (p.project_name LIKE ? OR p.description LIKE ? OR sc.name LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get projects for each category
$planning_projects = get_projects_by_status('planning', $search_query, 6);
$ongoing_projects = get_projects_by_status('ongoing', $search_query, 6);
$completed_projects = get_projects_by_status('completed', $search_query, 6);

// Get all projects for view switching
$all_projects = array_merge($planning_projects, $ongoing_projects, $completed_projects);

// Get filter options
$departments = get_departments();
$project_years = get_project_years();
$sub_counties = get_migori_sub_counties();
$migori_wards = get_wards();
?>
<?php
$page_title = "Track County Development Projects";
$page_description = "Track county development projects and stay informed about ongoing and completed projects in your area";
$show_nav = true;
include 'includes/header.php';
?>

<!-- Animated Background -->
<div class="animated-bg"></div>

<!-- Modern Hero Section -->
<section class="hero-modern">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="hero-content">
            <h1 class="hero-title">
                Transform Communities
                <br>
                Track Progress
            </h1>
            <p class="hero-subtitle">
                Discover, monitor, and engage with county development projects 
                that are shaping the future of Migori County
            </p>

            <!-- Modern Search Bar -->
            <div class="search-modern">
                <form method="GET" action="index.php" class="relative">
                    <?php if (!empty($view_mode) && $view_mode !== 'grid'): ?>
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    <?php endif; ?>

                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search projects by name, location, or department..." 
                           class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search mr-2"></i>
                        Search
                    </button>
                </form>
            </div>


        </div>
    </div>
</section>

<!-- Filters and View Controls -->
<section class="py-6 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="filter-section-modern">
            <div class="filter-grid-modern">
                <select id="departmentFilter" class="filter-select-modern">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="statusFilter" class="filter-select-modern">
                    <option value="">All Status</option>
                    <option value="planning">Planning</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>

                <select id="yearFilter" class="filter-select-modern">
                    <option value="">All Years</option>
                    <?php foreach ($project_years as $year): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- View Toggle -->
                <div class="view-toggle-modern">
                    <button id="gridView" onclick="switchView('grid')" class="view-btn-modern active">
                        <i class="fas fa-th-large"></i>
                        <span>Grid</span>
                    </button>
                    <button id="listView" onclick="switchView('list')" class="view-btn-modern">
                        <i class="fas fa-list"></i>
                        <span>List</span>
                    </button>
                    <button id="mapView" onclick="switchView('map')" class="view-btn-modern">
                        <i class="fas fa-map"></i>
                        <span>Map</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative z-10">
    <!-- Categorized Projects Grid View -->
    <section id="gridContainer">
        <!-- Ongoing Projects Section -->
        <?php if (!empty($ongoing_projects)): ?>
        <div class="mb-10 fade-in-up">
            <div class="category-header-modern">
                <div class="category-title-modern">
                    <span class="category-badge-modern status-ongoing">Ongoing</span>
                    Active Projects
                </div>
                <span class="category-count-modern">
                    <?php echo count($ongoing_projects); ?> projects
                </span>
            </div>
            <div class="grid-modern">
                <?php foreach ($ongoing_projects as $index => $project): ?>
                    <div class="project-card-modern fade-in-up" style="--stagger-delay: <?php echo $index * 0.1; ?>s">
                        <!-- Map Preview -->
                        <div class="map-preview-container mb-4">
                            <?php if (!empty($project['location_coordinates'])): ?>
                                <div id="map-preview-<?php echo $project['id']; ?>" class="w-full h-32 bg-gray-200 rounded-lg overflow-hidden"></div>
                                <div class="map-overlay">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-map-marker-alt text-2xl mb-2"></i>
                                        <p class="text-sm">No location data</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-header">
                            <div>
                                <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <div class="project-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?>
                                </div>
                            </div>
                            <span class="status-badge-modern status-ongoing">
                                Ongoing
                            </span>
                        </div>

                        

                        <!-- Project Info -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-building text-blue-500"></i>
                                <span><?php echo htmlspecialchars($project['department_name']); ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-calendar text-green-500"></i>
                                <span><?php echo $project['project_year']; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                               class="btn-modern btn-primary-modern flex-1 justify-center">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <button onclick="openFeedbackModal(<?php echo $project['id']; ?>)" 
                                    class="btn-modern btn-secondary-modern">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Completed Projects Section -->
        <?php if (!empty($completed_projects)): ?>
        <div class="mb-10 fade-in-up">
            <div class="category-header-modern">
                <div class="category-title-modern">
                    <span class="category-badge-modern status-completed">Completed</span>
                    Completed Projects
                </div>
                <span class="category-count-modern">
                    <?php echo count($completed_projects); ?> projects
                </span>
            </div>
            <div class="grid-modern">
                <?php foreach ($completed_projects as $index => $project): ?>
                    <div class="project-card-modern fade-in-up" style="--stagger-delay: <?php echo $index * 0.1; ?>s">
                        <!-- Map Preview -->
                        <div class="map-preview-container mb-4">
                            <?php if (!empty($project['location_coordinates'])): ?>
                                <div id="map-preview-<?php echo $project['id']; ?>" class="w-full h-32 bg-gray-200 rounded-lg overflow-hidden"></div>
                                <div class="map-overlay">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-map-marker-alt text-2xl mb-2"></i>
                                        <p class="text-sm">No location data</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-header">
                            <div>
                                <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <div class="project-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?>
                                </div>
                            </div>
                            <span class="status-badge-modern status-completed">
                                Completed
                            </span>
                        </div>

                        

                        <!-- Project Info -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-building text-blue-500"></i>
                                <span><?php echo htmlspecialchars($project['department_name']); ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-calendar text-green-500"></i>
                                <span><?php echo $project['project_year']; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                               class="btn-modern btn-primary-modern flex-1 justify-center">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <button onclick="openFeedbackModal(<?php echo $project['id']; ?>)" 
                                    class="btn-modern btn-secondary-modern">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Planning Projects Section -->
        <?php if (!empty($planning_projects)): ?>
        <div class="mb-10 fade-in-up">
            <div class="category-header-modern">
                <div class="category-title-modern">
                    <span class="category-badge-modern status-planning">Planning</span>
                    Planning Projects
                </div>
                <span class="category-count-modern">
                    <?php echo count($planning_projects); ?> projects
                </span>
            </div>
            <div class="grid-modern">
                <?php foreach ($planning_projects as $index => $project): ?>
                    <div class="project-card-modern fade-in-up" style="--stagger-delay: <?php echo $index * 0.1; ?>s">
                        <!-- Map Preview -->
                        <div class="map-preview-container mb-4">
                            <?php if (!empty($project['location_coordinates'])): ?>
                                <div id="map-preview-<?php echo $project['id']; ?>" class="w-full h-32 bg-gray-200 rounded-lg overflow-hidden"></div>
                                <div class="map-overlay">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-map-marker-alt text-2xl mb-2"></i>
                                        <p class="text-sm">No location data</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-header">
                            <div>
                                <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <div class="project-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?>
                                </div>
                            </div>
                            <span class="status-badge-modern status-planning">
                                Planning
                            </span>
                        </div>

                        

                        <!-- Project Info -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-building text-blue-500"></i>
                                <span><?php echo htmlspecialchars($project['department_name']); ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-calendar text-green-500"></i>
                                <span><?php echo $project['project_year']; ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                               class="btn-modern btn-primary-modern flex-1 justify-center">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <button onclick="openFeedbackModal(<?php echo $project['id']; ?>)" 
                                    class="btn-modern btn-secondary-modern">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- No Projects Found -->
        <?php if (empty($all_projects)): ?>
        <div class="glass-card text-center py-16">
            <div class="text-gray-500 dark:text-gray-400">
                <i class="fas fa-search text-6xl mb-6 opacity-50"></i>
                <?php if (!empty($search_query)): ?>
                    <h3 class="text-2xl font-medium mb-4">No projects found</h3>
                    <p class="text-lg mb-6">
                        No projects match your search for "<span class="font-medium"><?php echo htmlspecialchars($search_query); ?></span>"
                    </p>
                    <a href="index.php" class="btn-modern btn-primary-modern">
                        <i class="fas fa-arrow-left"></i>
                        View All Projects
                    </a>
                <?php else: ?>
                    <h3 class="text-2xl font-medium mb-4">No projects available</h3>
                    <p class="text-lg">There are currently no published projects to display.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- List View (Hidden by default) -->
    <section id="listContainer" class="hidden">
        <!-- List view content will be similar but in list format -->
    </section>

    <!-- Map View (Hidden by default) -->
    <section id="mapContainer" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Map Display - Takes more space -->
            <div class="lg:col-span-4 order-2 lg:order-1">
                <div class="glass-card p-4">
                    <div id="mainMapView" class="w-full h-[600px] bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                </div>
            </div>

            <!-- Project List Sidebar -->
            <div class="lg:col-span-1 order-1 lg:order-2">
                <div class="glass-card p-4 h-[600px] overflow-y-auto">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        All Projects
                        <span class="text-sm font-normal text-gray-500 block">
                            <?php echo count($all_projects); ?> projects
                        </span>
                    </h3>
                    <div id="mapProjectsList" class="space-y-2">
                        <!-- Project list will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Scroll Progress Indicator -->
<div class="scroll-indicator"></div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="glass-card max-w-2xl w-full">
            <form id="feedbackForm" action="api/feedback.php" method="POST">
                <input type="hidden" name="project_id" id="feedbackProjectId" value="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Share Your Feedback</h3>
                    <button type="button" onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="feedback_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Your Name
                            </label>
                            <input type="text" id="feedback_name" name="name" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="feedback_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email (Optional)
                            </label>
                            <input type="email" id="feedback_email" name="email" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="feedback_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Feedback <span class="text-red-500">*</span>
                        </label>
                        <textarea id="feedback_message" name="message" rows="4" required
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                  placeholder="Share your thoughts about this project..."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeFeedbackModal()" 
                            class="btn-modern btn-secondary-modern">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="btn-modern btn-primary-modern">
                        <i class="fas fa-paper-plane"></i>
                        Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rating Modal -->
<div id="rating-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999]">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <form id="rating-form" action="api/rating.php" method="POST">
                <input type="hidden" name="project_id" id="rating-project-id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rate Project</h3>
                    <button type="button" onclick="closeRatingModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">How would you rate this project?</p>
                        <div class="flex justify-center space-x-2" id="star-rating">
                            <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400" data-rating="1">
                                <i class="far fa-star"></i>
                            </button>
                            <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400" data-rating="2">
                                <i class="far fa-star"></i>
                            </button>
                            <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400" data-rating="3">
                                <i class="far fa-star"></i>
                            </button>
                            <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400" data-rating="4">
                                <i class="far fa-star"></i>
                            </button>
                            <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400" data-rating="5">
                                <i class="far fa-star"></i>
                            </button>
                        </div>
                        <input type="hidden" name="rating" id="selected-rating" value="">
                    </div>

                    <div>
                        <label for="rating_comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Comment (Optional)
                        </label>
                        <textarea id="rating_comment" name="comment" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="Share your thoughts..."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeRatingModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button id="scrollToTop" class="fixed bottom-6 right-6 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all z-50 opacity-0 invisible transform translate-y-4">
    <i class="fas fa-arrow-up"></i>
</button>

<?php include 'includes/footer.php'; ?>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Make projects data available to JavaScript
window.projectsData = <?php echo json_encode($all_projects); ?>;

// Scroll Progress Indicator
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const maxHeight = document.body.scrollHeight - window.innerHeight;
    const progress = scrolled / maxHeight;
    document.querySelector('.scroll-indicator').style.setProperty('--scroll-progress', progress);
});

// Stagger animations
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.fade-in-up');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});

// Global feedback modal functions
function openFeedbackModal(projectId) {
    document.getElementById('feedbackProjectId').value = projectId;
    document.getElementById('feedbackModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
    document.getElementById('feedbackForm').reset();
    document.body.style.overflow = 'auto';
}

// Handle feedback form submission
document.getElementById('feedbackForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('api/feedback.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Feedback submitted successfully!');
            closeFeedbackModal();
        } else {
            alert(data.message || 'Failed to submit feedback');
        }
    } catch (error) {
        console.error('Error submitting feedback:', error);
        alert('Failed to submit feedback');
    }
});
</script>
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
