<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

// Get recent import logs
$recent_imports = [];
try {
    $stmt = $pdo->query("SELECT il.*, au.full_name 
                         FROM import_logs il 
                         JOIN admin_users au ON il.imported_by = au.id 
                         ORDER BY il.imported_at DESC LIMIT 10");
    $recent_imports = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Import logs error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="../admin.php" class="flex items-center">
                            <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400 text-xl mr-3"></i>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Import Data</h1>
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:inline"></i>
                        </button>
                        
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Welcome, <strong><?php echo htmlspecialchars($current_admin['name']); ?></strong>
                            </span>
                            <a href="../logout.php" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

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
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Import Data</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Import Project Data</h2>
                <div class="flex space-x-3">
                    <a href="#sample-download" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Download Sample CSV
                    </a>
                </div>
            </div>

            <!-- Import Guidelines -->
            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Import Guidelines</h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Only CSV files are supported (Excel files require PHPSpreadsheet library)</li>
                                <li>Maximum file size: <?php echo number_format(MAX_FILE_SIZE / 1024 / 1024); ?>MB</li>
                                <li>Required columns: project_name, department, ward, sub_county, county, year</li>
                                <li>The system will automatically create missing departments, counties, sub-counties, and wards</li>
                                <li>Date format should be YYYY-MM-DD or any standard date format</li>
                                <li>Budget values should be numeric (commas will be removed automatically)</li>
                                <li>Status should be one of: planning, ongoing, completed, suspended, cancelled</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upload CSV File</h3>
                </div>
                <div class="p-6">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="space-y-6">
                            <!-- File Upload Area -->
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" id="csvFile" name="csv_file" accept=".csv" class="hidden" onchange="handleFileSelect(this)">
                                <div class="text-center">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                    <div class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Drop your CSV file here or <button type="button" onclick="document.getElementById('csvFile').click()" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">browse</button>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Maximum file size: <?php echo number_format(MAX_FILE_SIZE / 1024 / 1024); ?>MB
                                    </p>
                                </div>
                            </div>

                            <!-- Selected File Info -->
                            <div id="fileInfo" class="hidden bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-csv text-green-500 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white" id="fileName"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" id="fileSize"></p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="clearFile()" class="text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitBtn" disabled 
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                                    <span id="submitText">
                                        <i class="fas fa-upload mr-2"></i>
                                        Import Projects
                                    </span>
                                    <span id="uploadingText" class="hidden">
                                        <div class="spinner mr-2"></div>
                                        Importing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Results -->
            <div id="importResults" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Results</h3>
                </div>
                <div class="p-6" id="resultsContent">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>

            <!-- Sample CSV Format -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sample CSV Format</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">project_name</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">description</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">department</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">ward</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">sub_county</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">county</th>
                                    
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">year</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">New Water Plant</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Construction of new water treatment plant</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Water and Sanitation</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Central Ward</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Nairobi Central</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Nairobi</td>
                                    
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">2024</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">ongoing</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <button onclick="downloadSampleCSV()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Download Sample CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Imports -->
            <?php if (!empty($recent_imports)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Imports</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">File</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Results</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Imported By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($recent_imports as $import): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($import['filename']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo number_format($import['total_rows']); ?> rows
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                <span class="text-green-600 dark:text-green-400"><?php echo number_format($import['successful_imports']); ?> successful</span>
                                            </div>
                                            <?php if ($import['failed_imports'] > 0): ?>
                                                <div class="text-sm text-red-600 dark:text-red-400">
                                                    <?php echo number_format($import['failed_imports']); ?> failed
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($import['full_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo format_date($import['imported_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if (!empty($import['error_details'])): ?>
                                                <button onclick="showErrorDetails('<?php echo htmlspecialchars($import['error_details'], ENT_QUOTES); ?>')" 
                                                        class="text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300">
                                                    View Errors
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Error Details Modal -->
    <div id="errorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Errors</h3>
                    <button onclick="closeErrorModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <pre id="errorContent" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md text-sm text-gray-900 dark:text-white whitespace-pre-wrap"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
