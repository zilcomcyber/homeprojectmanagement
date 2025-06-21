<?php
$page_title = "Import Projects from CSV";
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('super_admin'); // Only super admin can import CSV

$current_admin = get_current_admin();

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

<!-- Import Instructions -->
<div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">CSV Import Instructions</h3>
            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <ul class="list-disc list-inside space-y-1">
                    <li>Download the CSV template to see the required format</li>
                    <li>Projects imported from CSV will be in "draft" status initially</li>
                    <li>Each project will have one default step created</li>
                    <li>You can add more steps after import</li>
                    <li>Location coordinates should be in "latitude,longitude" format</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Template Download -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">CSV Template</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Download the template to ensure proper formatting</p>
    </div>
    <div class="p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Project Import Template</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Includes sample data and all required columns</p>
            </div>
            <a href="<?php echo BASE_URL; ?>uploads/Migori_Projects_Realistic.csv" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors"
               download="Migori_Projects_Template.csv">
                <i class="fas fa-download mr-2"></i>
                Download Template
            </a>
        </div>
    </div>
</div>

<!-- Upload Form -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Upload CSV File</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Select your CSV file to import projects</p>
    </div>

    <form id="csvUploadForm" method="POST" action="<?php echo BASE_URL; ?>admin/import_csv" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <div class="p-6">
            <!-- File Upload Area -->
            <div class="mb-4">
                <label for="csvFile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select CSV File</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="file" name="csv_file" id="csvFile" accept=".csv" class="flex-grow focus:ring-blue-500 focus:border-blue-500 block w-full min-w-0 rounded-none rounded-l-md sm:text-sm border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">.csv</span>
                </div>
            </div>

            <!-- File Information and Progress -->
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span id="fileName">No file selected</span>
                    <span id="fileSize">0.00 MB</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2" style="display: none;" id="progressContainer">
                    <div id="uploadProgress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div id="uploadStatus" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ready to upload</div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
            <a href="projects.php" 
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" id="uploadBtn" disabled
                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                <i class="fas fa-upload mr-2"></i>
                Import Projects
            </button>
        </div>
    </form>
</div>

<!-- Import Results -->
<div id="importResults" class="hidden mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Results</h3>
    </div>
    <div class="p-6">
        <div id="importStats" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Stats will be populated here -->
        </div>
        <div id="importErrors" class="hidden">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Import Errors:</h4>
            <div class="bg-red-50 dark:bg-red-900 rounded-lg p-4">
                <ul id="errorList" class="text-sm text-red-700 dark:text-red-300 space-y-1">
                    <!-- Errors will be listed here -->
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('csvUploadForm');
    const fileInput = document.getElementById('csvFile');
    const uploadBtn = document.getElementById('uploadBtn');

    // File input handling
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name;

            // Calculate file size
            let fileSize;
            if (file.size === 0) {
                fileSize = '0.00 KB';
            } else if (file.size < 1024) {
                fileSize = file.size + ' B';
            } else if (file.size < 1024 * 1024) {
                fileSize = (file.size / 1024).toFixed(2) + ' KB';
            } else {
                fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            }
            document.getElementById('fileSize').textContent = fileSize;

            uploadBtn.disabled = false;
            uploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            document.getElementById('fileName').textContent = 'No file selected';
            document.getElementById('fileSize').textContent = '0.00 MB';
            uploadBtn.disabled = true;
            uploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    });

    // Upload form submission
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('uploadProgress');
        const statusElement = document.getElementById('uploadStatus');
        const resultsContainer = document.getElementById('importResults');

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        progressContainer.style.display = 'block';

        const xhr = new XMLHttpRequest();

        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                statusElement.innerHTML = 'Uploading... ' + Math.round(percentComplete) + '%';
            }
        });

        xhr.addEventListener('load', function() {
            try {
                const data = JSON.parse(xhr.responseText);
                
                // Show results
                resultsContainer.classList.remove('hidden');
                
                if (data.success) {
                    progressBar.style.width = '100%';
                    statusElement.innerHTML = '<span class="text-green-600">Import completed successfully!</span>';
                    
                    // Show import stats
                    document.getElementById('importStats').innerHTML = `
                        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${data.imported_count}</div>
                            <div class="text-sm text-green-700 dark:text-green-300">Projects Imported</div>
                        </div>
                        ${data.error_count > 0 ? `
                        <div class="bg-red-50 dark:bg-red-900 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">${data.error_count}</div>
                            <div class="text-sm text-red-700 dark:text-red-300">Errors Found</div>
                        </div>
                        ` : ''}
                    `;
                    
                    // Show errors if any
                    if (data.errors && data.errors.length > 0) {
                        document.getElementById('importErrors').classList.remove('hidden');
                        document.getElementById('errorList').innerHTML = data.errors.map(error => `<li>• ${error}</li>`).join('');
                    }
                } else {
                    statusElement.innerHTML = '<span class="text-red-600">Import Failed! Check the errors Listed bellow</span>';
                    
                    document.getElementById('importStats').innerHTML = `
                        <div class="bg-red-50 dark:bg-red-900 rounded-lg p-4 text-center">
                            <div class="text-lg font-bold text-red-600 dark:text-red-400">Import Failed! Check the errors Listed bellow</div>
                            <div class="text-sm text-red-700 dark:text-red-300">${data.message}</div>
                        </div>
                    `;
                    
                    if (data.errors && data.errors.length > 0) {
                        document.getElementById('importErrors').classList.remove('hidden');
                        document.getElementById('errorList').innerHTML = data.errors.map(error => `<li>• ${error}</li>`).join('');
                    }
                }
                
                // Reset form
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Import Projects';
                progressContainer.style.display = 'none';
                progressBar.style.width = '0%';
                
            } catch (error) {
                statusElement.innerHTML = '<span class="text-red-600">Processing failed: Invalid response</span>';
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Import Projects';
                progressContainer.style.display = 'none';
                progressBar.style.width = '0%';
            }
        });

        xhr.addEventListener('error', function() {
            statusElement.innerHTML = '<span class="text-red-600">Upload failed: Network error</span>';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Import Projects';
            progressContainer.style.display = 'none';
            progressBar.style.width = '0%';
        });

        xhr.open('POST', '../api/upload_csv.php');
        xhr.send(formData);
    });
});
</script>

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
