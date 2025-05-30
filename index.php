<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get filter parameters for Migori County
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';
$year_filter = $_GET['year'] ?? '';
$sub_county_filter = $_GET['sub_county'] ?? '';
$ward_filter = $_GET['ward'] ?? '';
$search_query = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'grid'; // grid, list, map

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Fetch projects with enhanced search (name, sub-county, year) and pagination
$projects = get_migori_projects($department_filter, $status_filter, $year_filter, $search_query, $sub_county_filter, $per_page, $offset);

// Get total count for pagination
$total_projects = get_total_projects_count($department_filter, $status_filter, $year_filter, $search_query, $sub_county_filter);
$total_pages = ceil($total_projects / $per_page);

// Get filter options for Migori
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

    <!-- Hero Section -->
    <section class="hero-bg text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Track County Development Projects</h1>
            <h3 class="text-xl md:text-2xl mb-8 text-blue-100">Stay informed about ongoing and completed projects in your area</h3>

            <!-- Hero Search Bar -->
            <div class="max-w-2xl mx-auto">
                <form method="GET" class="relative">
                    <!-- Preserve existing filters -->
                    <?php if (!empty($department_filter)): ?>
                        <input type="hidden" name="department" value="<?php echo htmlspecialchars($department_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($year_filter)): ?>
                        <input type="hidden" name="year" value="<?php echo htmlspecialchars($year_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($sub_county_filter)): ?>
                        <input type="hidden" name="sub_county" value="<?php echo htmlspecialchars($sub_county_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($ward_filter)): ?>
                        <input type="hidden" name="ward" value="<?php echo htmlspecialchars($ward_filter); ?>">
                    <?php endif; ?>

                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search projects by name, location, or year..." 
                           class="block w-full pl-12 pr-4 py-4 text-lg border-0 rounded-full text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                    <button type="submit" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <div class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-full transition-colors">
                            Search
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                $stats = get_project_stats();
                $statItems = [
                    ['icon' => 'fas fa-project-diagram', 'label' => 'Total Projects', 'value' => $stats['total'], 'color' => 'blue'],
                    ['icon' => 'fas fa-check-circle', 'label' => 'Completed', 'value' => $stats['completed'], 'color' => 'green'],
                    ['icon' => 'fas fa-play-circle', 'label' => 'In Progress', 'value' => $stats['ongoing'], 'color' => 'yellow'],
                    ['icon' => 'fas fa-coins', 'label' => 'Total Budget', 'value' => 'KES ' . number_format($stats['total_budget']/1000000, 1) . 'M', 'color' => 'purple']
                ];
                foreach ($statItems as $stat): ?>
                    <div class="text-center">
                        <div class="bg-<?php echo $stat['color']; ?>-100 dark:bg-<?php echo $stat['color']; ?>-900 w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <i class="<?php echo $stat['icon']; ?> text-<?php echo $stat['color']; ?>-600 dark:text-<?php echo $stat['color']; ?>-400 text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $stat['value']; ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400"><?php echo $stat['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Filters and View Controls -->
    <section class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
                <!-- Filters -->
                <form method="GET" class="flex space-x-3" id="filterForm">
                    <!-- Preserve search query -->
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>

                    <select name="ward" id="ward_filter" onchange="document.getElementById('filterForm').submit()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-40 p-2">
                        <option value="">Select Ward</option>
                        <?php foreach ($migori_wards as $ward): ?>
                            <option value="<?php echo $ward['id']; ?>" <?php echo $ward_filter == $ward['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ward['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" id="status_filter" onchange="document.getElementById('filterForm').submit()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-40 p-2">
                        <option value="">Select Status</option>
                        <option value="planning" <?php echo $status_filter == 'planning' ? 'selected' : ''; ?>>Planning</option>
                        <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </form>

                <!-- View Mode Controls -->
                <div class="flex space-x-2">
                    <button onclick="switchView('grid')" id="gridView" class="view-btn bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-th"></i>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button onclick="switchView('list')" id="listView" class="view-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors hover:bg-blue-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-list"></i>
                        <span class="hidden sm:inline">List</span>
                    </button>
                    <button onclick="switchView('map')" id="mapView" class="view-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors hover:bg-blue-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-map-marked-alt"></i>
                        <span class="hidden sm:inline">Map</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Projects Grid View -->
        <section id="gridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projects as $project): ?>
                                    <!-- Project Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                                <!-- Map Preview -->
                                <?php if (!empty($project['location_coordinates'])): ?>
                                    <div class="h-32 bg-gray-200 dark:bg-gray-700 relative cursor-pointer" onclick="window.location.href='project_details.php?id=<?php echo $project['id']; ?>'">
                                        <div id="map-preview-<?php echo $project['id']; ?>" class="w-full h-full"></div>
                                        <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-xs">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            Location
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="h-32 bg-gray-200 dark:bg-gray-700 flex items-center justify-center cursor-pointer" onclick="window.location.href='project_details.php?id=<?php echo $project['id']; ?>'">
                                        <div class="text-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-map-marker-alt text-2xl mb-2"></i>
                                            <p class="text-sm">No location data</p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="p-6">
                                    <!-- Project Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <a href="project_details.php?id=<?php echo $project['id']; ?>" class="block hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                                </h3>
                                            </a>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="flex items-center">
                                                    <i class="fas fa-building mr-1"></i>
                                                    <?php echo htmlspecialchars($project['department_name']); ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <?php echo htmlspecialchars($project['sub_county_name']); ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo $project['project_year']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </div>

                                    <!-- Project Description -->
                                    <?php if (!empty($project['description'])): ?>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                                            <?php echo htmlspecialchars(substr($project['description'], 0, 150)); ?>
                                            <?php if (strlen($project['description']) > 150): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Project Progress -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                            <div class="circular-progress">
                                                <svg class="w-12 h-12" viewBox="0 0 36 36">
                                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                                          fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="100, 100" 
                                                          class="text-gray-200 dark:text-gray-700"/>
                                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                                          fill="none" stroke="currentColor" stroke-width="2" 
                                                          stroke-dasharray="<?php echo $project['progress_percentage']; ?>, 100" 
                                                          class="<?php echo get_progress_color_class($project['progress_percentage']); ?>"/>
                                                </svg>
                                                <div class="progress-text text-gray-900 dark:text-white">
                                                    <?php echo number_format($project['progress_percentage'], 0); ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Rating -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rating</span>
                                            <?php echo generate_star_rating($project['average_rating'], $project['total_ratings']); ?>
                                        </div>
                                    </div>

                                    <!-- Project Details -->
                                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Budget:</span>
                                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo format_currency($project['budget']); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Location:</span>
                                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['ward_name']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <div class="flex space-x-2">
                                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                                               class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-eye mr-2"></i>
                                                View Details
                                            </a>
                                            <button onclick="openFeedbackModal(<?php echo $project['id']; ?>)" 
                                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                <?php endforeach; ?>
        </section>

        <!-- Projects List View -->
        <section id="listContainer" class="hidden space-y-4">
            <?php foreach ($projects as $project): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-4 mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_status_badge_class($project['status']); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            <?php echo htmlspecialchars(substr($project['description'], 0, 200)); ?>
                            <?php if (strlen($project['description']) > 200): ?>...<?php endif; ?>
                        </p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">Department:</span>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['department_name']); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">Location:</span>
                                <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($project['ward_name'] . ', ' . $project['sub_county_name']); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">Budget:</span>
                                <p class="text-gray-900 dark:text-white"><?php echo format_currency($project['budget']); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">Progress:</span>
                                <div class="flex items-center space-x-2">
                                    <div class="relative w-8 h-8">
                                        <svg class="w-8 h-8 transform -rotate-90" viewBox="0 0 36 36">
                                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                                  fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="100, 100" 
                                                  class="text-gray-200 dark:text-gray-700"/>
                                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                                  fill="none" stroke="currentColor" stroke-width="3" 
                                                  stroke-dasharray="<?php echo $project['progress_percentage']; ?>, 100" 
                                                  class="<?php echo get_progress_color_class($project['progress_percentage']); ?>"/>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-medium"><?php echo number_format($project['progress_percentage'], 0); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Map Preview -->
                    <div class="ml-6 flex-shrink-0 flex space-x-4">
                        <?php if (!empty($project['location_coordinates'])): ?>
                            <div class="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                                <div id="list-map-preview-<?php echo $project['id']; ?>" class="w-full h-full"></div>
                            </div>
                        <?php endif; ?>
                        <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                            <i class="fas fa-eye mr-2"></i>
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <!-- Projects Map View -->
        <section id="mapContainer" class="hidden">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-3">
                    <div id="map" class="main-map"></div>
                </div>
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <h4 class="mb-3 font-semibold text-gray-900 dark:text-white">Projects on Map</h4>
                        <div id="mapProjectsList" class="space-y-2 overflow-y-auto max-h-[50vh]">
                            <?php foreach ($projects as $project): ?>
                                <?php if (!empty($project['location_coordinates'])): ?>
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                    <h5 class="font-medium text-gray-900 dark:text-white text-sm mb-1">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </h5>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                        <?php echo htmlspecialchars($project['ward_name']); ?>
                                    </p>
                                    <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                                       class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                        <i class="fas fa-eye mr-1"></i>
                                        View Details
                                    </a>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 sm:px-6 mt-8 rounded-lg">
            <div class="flex flex-1 justify-between sm:hidden">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['department' => $department_filter, 'status' => $status_filter, 'year' => $year_filter, 'search' => $search_query])); ?>" 
                       class="relative inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Previous
                    </a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['department' => $department_filter, 'status' => $status_filter, 'year' => $year_filter, 'search' => $search_query])); ?>" 
                       class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Next
                    </a>
                <?php endif; ?>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                        <span class="font-medium"><?php echo min($offset + $per_page, $total_projects); ?></span> of 
                        <span class="font-medium"><?php echo $total_projects; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['department' => $department_filter, 'status' => $status_filter, 'year' => $year_filter, 'search' => $search_query])); ?>" 
                               class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['department' => $department_filter, 'status' => $status_filter, 'year' => $year_filter, 'search' => $search_query])); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['department' => $department_filter, 'status' => $status_filter, 'year' => $year_filter, 'search' => $search_query])); ?>" 
                               class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Feedback Modal -->
    <div id="feedback-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full">
                <form id="feedback-form" action="api/feedback.php" method="POST">
                    <input type="hidden" name="project_id" id="feedback-project-id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Share Your Feedback</h3>
                        <button type="button" onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Name</label>
                                <input type="text" name="citizen_name" required 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">                                </div>
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
                        <button type="button" onclick="closeFeedbackModal()" 
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

<?php
include 'includes/footer.php';
?>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
        // Initialize theme
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // View switching functionality
        function switchView(viewType) {
            // Hide all views
            document.getElementById('gridContainer').classList.add('hidden');
            document.getElementById('listContainer').classList.add('hidden');
            document.getElementById('mapContainer').classList.add('hidden');

            // Reset button styles
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });

            // Show selected view and update button
            if (viewType === 'grid') {
                document.getElementById('gridContainer').classList.remove('hidden');
                document.getElementById('gridView').classList.remove('bg-gray-200', 'text-gray-700');
                document.getElementById('gridView').classList.add('bg-blue-600', 'text-white');
                // Reinitialize maps when switching to grid view
                setTimeout(initializeMiniMaps, 100);
            } else if (viewType === 'list') {
                document.getElementById('listContainer').classList.remove('hidden');
                document.getElementById('listView').classList.remove('bg-gray-200', 'text-gray-700');
                document.getElementById('listView').classList.add('bg-blue-600', 'text-white');
                // Initialize list view maps
                setTimeout(initializeListViewMaps, 100);
            } else if (viewType === 'map') {
                document.getElementById('mapContainer').classList.remove('hidden');
                document.getElementById('mapView').classList.remove('bg-gray-200', 'text-gray-700');
                document.getElementById('mapView').classList.add('bg-blue-600', 'text-white');
                initializeMainMap();
            }
        }

        // View switching functionality (no changes needed for applyFilters since we're using form submission)

        // Initialize main map for map view
        function initializeMainMap() {
            const mapElement = document.getElementById('map');
            if (!mapElement || mapElement.hasChildNodes()) return;

            const mainMap = L.map('map').setView([-1.0635, 34.4741], 10); // Migori County coordinates

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mainMap);

            // Add project markers
            <?php foreach ($projects as $project): ?>
                <?php if (!empty($project['location_coordinates'])): ?>
                    <?php
                    $coords = explode(',', $project['location_coordinates']);
                    if (count($coords) == 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                    ?>
                    L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
                        .addTo(mainMap)
                        .bindPopup(`
                            <div class="p-2">
                                <h3 class="font-semibold"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($project['ward_name']); ?></p>
                                <p class="text-sm"><?php echo format_currency($project['budget']); ?></p>
                                <a href="project_details.php?id=<?php echo $project['id']; ?>" class="text-blue-600 hover:text-blue-800">View Details</a>
                            </div>
                        `);
                    <?php
                    }
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>