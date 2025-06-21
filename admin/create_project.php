<?php
$page_title = "Create New Project";
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('admin'); // Only admin and super_admin can create projects

$current_admin = get_current_admin();

// Get data for dropdowns
$departments = get_departments();
$counties = get_counties();

ob_start();
?>

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

<!-- Multi-Step Form -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Step Indicator -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <div class="flex items-center" id="step1-indicator">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-white">1</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Basic Information</p>
                    </div>
                </div>
                <div class="flex items-center" id="step2-indicator">
                    <div class="flex-shrink-0 w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-white">2</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Location & Demographics</p>
                    </div>
                </div>
                <div class="flex items-center" id="step3-indicator">
                    <div class="flex-shrink-0 w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-white">3</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Timeline & Contractor</p>
                    </div>
                </div>
                <div class="flex items-center" id="step4-indicator">
                    <div class="flex-shrink-0 w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-white">4</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Project Steps</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="projectForm" method="POST" action="submit_project.php">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="action" value="create_project">

        <!-- Step 1: Basic Information -->
        <div id="step1" class="step-content p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Basic Project Information</h3>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Name *</label>
                    <input type="text" name="project_name" id="projectName" required 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea name="description" id="projectDescription" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department *</label>
                        <select name="department_id" id="departmentId" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Year *</label>
                        <input type="number" name="project_year" id="projectYear" min="2020" max="2030" required 
                               value="<?php echo date('Y'); ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Location & Demographics -->
        <div id="step2" class="step-content p-6 hidden">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Location & Demographics</h3>
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">County *</label>
                        <select name="county_id" id="countyId" required onchange="loadSubCounties(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select County</option>
                            <?php foreach ($counties as $county): ?>
                                <option value="<?php echo $county['id']; ?>"><?php echo htmlspecialchars($county['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub County *</label>
                        <select name="sub_county_id" id="subCountyId" required onchange="loadWards(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Sub County</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ward *</label>
                        <select name="ward_id" id="wardId" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Ward</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Address</label>
                        <input type="text" name="location_address" id="locationAddress" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GPS Coordinates</label>
                        <input type="text" name="location_coordinates" id="locationCoordinates" 
                               placeholder="latitude,longitude" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Timeline -->
        <div id="step3" class="step-content p-6 hidden">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Timeline Information</h3>
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="startDate" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Completion</label>
                        <input type="date" name="expected_completion_date" id="expectedCompletion" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Name</label>
                        <input type="text" name="contractor_name" id="contractorName" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contractor Contact</label>
                        <input type="text" name="contractor_contact" id="contractorContact" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Project Steps -->
        <div id="step4" class="step-content p-6 hidden">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Project Steps</h3>
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Define the steps that this project will go through</p>
                    <button type="button" onclick="addProjectStep()" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Add Step
                    </button>
                </div>

                <div id="projectSteps" class="space-y-4">
                    <!-- Steps will be added dynamically -->
                </div>

                <div class="flex justify-between items-center">
                    <button type="button" onclick="generateDefaultSteps()" class="inline-flex items-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 transition-colors">
                        <i class="fas fa-magic mr-2"></i>
                        Generate Default Steps
                    </button>
                    <button type="button" onclick="clearAllSteps()" class="inline-flex items-center px-4 py-2 border border-red-600 text-sm font-medium rounded-md text-red-600 hover:bg-red-50 dark:hover:bg-red-900 transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Clear All Steps
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
            <button type="button" id="prevBtn" onclick="changeStep(-1)" class="hidden inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Previous
            </button>
            <div class="flex space-x-3">
                <button type="button" id="nextBtn" onclick="changeStep(1)" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    Next
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="submit" id="submitBtn" class="hidden inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Create Project
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;
let stepCounter = 0;

function changeStep(direction) {
    if (direction === 1 && currentStep < totalSteps) {
        if (validateCurrentStep()) {
            currentStep++;
            updateStepDisplay();
        }
    } else if (direction === -1 && currentStep > 1) {
        currentStep--;
        updateStepDisplay();
    }
}

function updateStepDisplay() {
    // Hide all steps
    for (let i = 1; i <= totalSteps; i++) {
        document.getElementById(`step${i}`).classList.add('hidden');
        const indicator = document.getElementById(`step${i}-indicator`);
        const circle = indicator.querySelector('div');
        const text = indicator.querySelector('p');
        
        if (i < currentStep) {
            circle.className = 'flex-shrink-0 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center';
            circle.innerHTML = '<i class="fas fa-check text-white text-sm"></i>';
            text.className = 'text-sm font-medium text-gray-900 dark:text-white';
        } else if (i === currentStep) {
            circle.className = 'flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center';
            circle.innerHTML = `<span class="text-sm font-medium text-white">${i}</span>`;
            text.className = 'text-sm font-medium text-gray-900 dark:text-white';
        } else {
            circle.className = 'flex-shrink-0 w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center';
            circle.innerHTML = `<span class="text-sm font-medium text-white">${i}</span>`;
            text.className = 'text-sm font-medium text-gray-500 dark:text-gray-400';
        }
    }

    // Show current step
    document.getElementById(`step${currentStep}`).classList.remove('hidden');

    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    if (currentStep === 1) {
        prevBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
    }

    if (currentStep === totalSteps) {
        nextBtn.classList.add('hidden');
        submitBtn.classList.remove('hidden');
    } else {
        nextBtn.classList.remove('hidden');
        submitBtn.classList.add('hidden');
    }
}

function validateCurrentStep() {
    const step = document.getElementById(`step${currentStep}`);
    const requiredFields = step.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            field.focus();
            alert('Please fill in all required fields before proceeding.');
            return false;
        }
    }
    return true;
}

function addProjectStep() {
    stepCounter++;
    const stepsContainer = document.getElementById('projectSteps');
    
    const stepDiv = document.createElement('div');
    stepDiv.className = 'border border-gray-200 dark:border-gray-600 rounded-lg p-4';
    stepDiv.innerHTML = `
        <div class="flex justify-between items-start mb-4">
            <h4 class="text-md font-medium text-gray-900 dark:text-white">Step ${stepCounter}</h4>
            <button type="button" onclick="removeProjectStep(this)" class="text-red-600 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Step Name</label>
                <input type="text" name="steps[${stepCounter}][name]" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected End Date</label>
                <input type="date" name="steps[${stepCounter}][expected_date]" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
            <textarea name="steps[${stepCounter}][description]" rows="2" 
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
        </div>
    `;
    
    stepsContainer.appendChild(stepDiv);
}

function removeProjectStep(button) {
    button.closest('.border').remove();
}

function generateDefaultSteps() {
    const departmentId = document.getElementById('departmentId').value;
    if (!departmentId) {
        alert('Please select a department first.');
        return;
    }

    // Clear existing steps
    clearAllSteps();

    // Fetch default steps for department
    fetch('../api/get_default_steps.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ department_id: departmentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.steps.forEach((step, index) => {
                addProjectStepWithData(step.step_name, step.description);
            });
        } else {
            alert('Error loading default steps: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading default steps');
    });
}

function addProjectStepWithData(name, description) {
    stepCounter++;
    const stepsContainer = document.getElementById('projectSteps');
    
    const stepDiv = document.createElement('div');
    stepDiv.className = 'border border-gray-200 dark:border-gray-600 rounded-lg p-4';
    stepDiv.innerHTML = `
        <div class="flex justify-between items-start mb-4">
            <h4 class="text-md font-medium text-gray-900 dark:text-white">Step ${stepCounter}</h4>
            <button type="button" onclick="removeProjectStep(this)" class="text-red-600 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Step Name</label>
                <input type="text" name="steps[${stepCounter}][name]" value="${name}"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected End Date</label>
                <input type="date" name="steps[${stepCounter}][expected_date]" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
            <textarea name="steps[${stepCounter}][description]" rows="2" 
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">${description}</textarea>
        </div>
    `;
    
    stepsContainer.appendChild(stepDiv);
}

function clearAllSteps() {
    document.getElementById('projectSteps').innerHTML = '';
    stepCounter = 0;
}

// Load sub-counties based on county selection
function loadSubCounties(countyId) {
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
                        subCountySelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading sub-counties:', error));
    }
}

function loadWards(subCountyId) {
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
                        wardSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading wards:', error));
    }
}

// Initialize with first step
document.addEventListener('DOMContentLoaded', function() {
    updateStepDisplay();
});
</script>

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
