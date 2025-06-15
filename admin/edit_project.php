<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();
$project_id = $_GET['id'] ?? 0;

if (!$project_id) {
    header('Location: projects.php?error=Project not found');
    exit();
}

$project = get_project_by_id($project_id);
if (!$project) {
    header('Location: projects.php?error=Project not found');
    exit();
}

// Check if user can edit this specific project
if (!can_manage_project($project_id)) {
    header('Location: projects.php?error=You do not have permission to edit this project');
    exit();
}

// Get data for dropdowns
$departments = get_departments();
$counties = get_counties();
$sub_counties = get_sub_counties($project['county_id']);
$wards = get_wards($project['sub_county_id']);

$page_title = "Edit Project";

ob_start();
?>

<div class="min-h-full">
    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="../admin.php" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <a href="projects.php" class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400">Projects</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Edit Project</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($_GET['success']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Edit Project</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Update project information</p>
            </div>

            <form method="POST" action="update_project.php" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">

                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Name *</label>
                        <input type="text" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department *</label>
                        <select name="department_id" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $project['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Year *</label>
                        <input type="number" name="project_year" min="2020" max="2030" value="<?php echo $project['project_year']; ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <!-- Location -->
                <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Location</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">County *</label>
                            <select name="county_id" id="countyId" required onchange="loadSubCounties(this.value)"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select County</option>
                                <?php foreach ($counties as $county): ?>
                                    <option value="<?php echo $county['id']; ?>" <?php echo $county['id'] == $project['county_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($county['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub County *</label>
                            <select name="sub_county_id" id="subCountyId" required onchange="loadWards(this.value)"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Sub County</option>
                                <?php foreach ($sub_counties as $sub_county): ?>
                                    <option value="<?php echo $sub_county['id']; ?>" <?php echo $sub_county['id'] == $project['sub_county_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub_county['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ward *</label>
                            <select name="ward_id" id="wardId" required 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Ward</option>
                                <?php foreach ($wards as $ward): ?>
                                    <option value="<?php echo $ward['id']; ?>" <?php echo $ward['id'] == $project['ward_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ward['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Address</label>
                            <input type="text" name="location_address" value="<?php echo htmlspecialchars($project['location_address']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GPS Coordinates</label>
                            <input type="text" name="location_coordinates" value="<?php echo htmlspecialchars($project['location_coordinates']); ?>" 
                                   placeholder="latitude,longitude" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Financial & Timeline -->
                <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Financial & Timeline</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">




                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $project['start_date']; ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Completion</label>
                            <input type="date" name="expected_completion_date" value="<?php echo $project['expected_completion_date']; ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Name</label>
                            <input type="text" name="contractor_name" value="<?php echo htmlspecialchars($project['contractor_name']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Contact</label>
                            <input type="text" name="contractor_contact" value="<?php echo htmlspecialchars($project['contractor_contact']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-600">
                    <a href="projects.php" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        Update Project
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Load sub-counties based on county selection
function loadSubCounties(countyId, selectedSubCountyId = null) {
    const subCountySelect = document.getElementById('subCountyId');
    const wardSelect = document.getElementById('wardId');

    subCountySelect.innerHTML = '<option value="">Select Sub County</option>';
    wardSelect.innerHTML = '<option value="">Select Ward</option>';

    if (countyId) {
        fetch(`../api/locations.php?action=sub_counties&county_id=${countyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(subCounty => {
                        const option = document.createElement('option');
                        option.value = subCounty.id;
                        option.textContent = subCounty.name;
                        if (selectedSubCountyId && subCounty.id == selectedSubCountyId) {
                            option.selected = true;
                        }
                        subCountySelect.appendChild(option);
                    });
                    
                    // If we have a selected sub-county, load its wards
                    if (selectedSubCountyId) {
                        loadWards(selectedSubCountyId, <?php echo $project['ward_id']; ?>);
                    }
                }
            })
            .catch(error => console.error('Error loading sub-counties:', error));
    }
}

function loadWards(subCountyId, selectedWardId = null) {
    const wardSelect = document.getElementById('wardId');
    wardSelect.innerHTML = '<option value="">Select Ward</option>';

    if (subCountyId) {
        fetch(`../api/locations.php?action=wards&sub_county_id=${subCountyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(ward => {
                        const option = document.createElement('option');
                        option.value = ward.id;
                        option.textContent = ward.name;
                        if (selectedWardId && ward.id == selectedWardId) {
                            option.selected = true;
                        }
                        wardSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading wards:', error));
    }
}

// Load the dependent dropdowns when page loads
document.addEventListener('DOMContentLoaded', function() {
    const countyId = <?php echo $project['county_id']; ?>;
    const subCountyId = <?php echo $project['sub_county_id']; ?>;
    
    if (countyId) {
        loadSubCounties(countyId, subCountyId);
    }
});
</script>

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
