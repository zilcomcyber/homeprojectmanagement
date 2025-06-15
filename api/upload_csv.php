<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    json_response(['success' => false, 'message' => 'Unauthorized access']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method']);
}

if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File too large (exceeds php.ini limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)',
        UPLOAD_ERR_PARTIAL => 'File upload was interrupted',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
    ];
    $error_msg = $error_messages[$_FILES['csv_file']['error']] ?? 'Unknown upload error';
    json_response(['success' => false, 'message' => $error_msg]);
}

$file_size = $_FILES['csv_file']['size'];
$file_name = $_FILES['csv_file']['name'];
$temp_path = $_FILES['csv_file']['tmp_name'];

if ($file_size === 0) {
    json_response(['success' => false, 'message' => 'Uploaded file is empty']);
}

// Validate file extension
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if ($file_extension !== 'csv') {
    json_response(['success' => false, 'message' => 'Only CSV files are allowed']);
}

try {
    // Process CSV file
    $handle = fopen($temp_path, 'r');
    if ($handle === false) {
        json_response(['success' => false, 'message' => 'Failed to read uploaded file']);
    }

    // Read header row
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        json_response(['success' => false, 'message' => 'Invalid CSV format - no headers found']);
    }

    // Expected headers
    $expected_headers = [
        'project_name',
        'description', 
        'department',
        'county',
        'sub_county',
        'ward',
        'location_address',
        'location_coordinates',
        'project_year',
        'start_date',
        'expected_completion_date',
        'contractor_name',
        'contractor_contact',
        'step_name',
        'step_description'
    ];

    // Validate headers
    if (count($headers) !== count($expected_headers)) {
        fclose($handle);
        json_response(['success' => false, 'message' => 'CSV header count mismatch. Expected ' . count($expected_headers) . ' columns, got ' . count($headers)]);
    }

    $pdo->beginTransaction();

    $success_count = 0;
    $error_count = 0;
    $errors = [];
    $row_number = 1; // Start from 1 (header row)

    while (($data = fgetcsv($handle)) !== false) {
        $row_number++;

        // Skip if this looks like a header row (check if first column matches expected header)
        if (strtolower(trim($data[0])) === 'project_name' || 
            in_array(strtolower(trim($data[0])), ['project_name', 'project title', 'title', 'name'])) {
            continue;
        }

        // Skip empty rows
        if (empty(array_filter($data))) {
            continue;
        }

        // Validate column count - allow for missing trailing columns
        if (count($data) < count($expected_headers)) {
            // Pad with empty strings if columns are missing
            $data = array_pad($data, count($expected_headers), '');
        }

        // Map data to variables
        $project_name = trim($data[0]);
        $description = trim($data[1]);
        $department_name = trim($data[2]);
        $county_name = trim($data[3]);
        $sub_county_name = trim($data[4]);
        $ward_name = trim($data[5]);
        $location_address = trim($data[6]);
        $location_coordinates = trim($data[7]);
        $project_year = trim($data[8]);
        $start_date = trim($data[9]);
        $expected_completion_date = trim($data[10]);
        $contractor_name = trim($data[11]);
        $contractor_contact = trim($data[12]);
        $step_name = trim($data[13]);
        $step_description = trim($data[14]);

        // Validate required fields
        if (empty($project_name) || empty($department_name) || empty($county_name) || empty($sub_county_name) || empty($ward_name)) {
            $errors[] = "Row {$row_number}: Missing required fields (project_name, department, county, sub_county, ward)";
            $error_count++;
            continue;
        }

        try {
            // Check for duplicate project name
            $duplicate_check = $pdo->prepare("SELECT id FROM projects WHERE project_name = ?");
            $duplicate_check->execute([$project_name]);
            if ($duplicate_check->fetch()) {
                $errors[] = "Row {$row_number}: Project '{$project_name}' already exists";
                $error_count++;
                continue;
            }

            // Get or create department
            $dept_stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
            $dept_stmt->execute([$department_name]);
            $department_id = $dept_stmt->fetchColumn();

            if (!$department_id) {
                $dept_insert = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
                $dept_insert->execute([$department_name]);
                $department_id = $pdo->lastInsertId();
            }

            // Get or create county
            $county_stmt = $pdo->prepare("SELECT id FROM counties WHERE name = ?");
            $county_stmt->execute([$county_name]);
            $county_id = $county_stmt->fetchColumn();

            if (!$county_id) {
                $county_insert = $pdo->prepare("INSERT INTO counties (name) VALUES (?)");
                $county_insert->execute([$county_name]);
                $county_id = $pdo->lastInsertId();
            }

            // Get or create sub-county
            $sub_county_stmt = $pdo->prepare("SELECT id FROM sub_counties WHERE name = ? AND county_id = ?");
            $sub_county_stmt->execute([$sub_county_name, $county_id]);
            $sub_county_id = $sub_county_stmt->fetchColumn();

            if (!$sub_county_id) {
                $sub_county_insert = $pdo->prepare("INSERT INTO sub_counties (name, county_id) VALUES (?, ?)");
                $sub_county_insert->execute([$sub_county_name, $county_id]);
                $sub_county_id = $pdo->lastInsertId();
            }

            // Get or create ward
            $ward_stmt = $pdo->prepare("SELECT id FROM wards WHERE name = ? AND sub_county_id = ?");
            $ward_stmt->execute([$ward_name, $sub_county_id]);
            $ward_id = $ward_stmt->fetchColumn();

            if (!$ward_id) {
                $ward_insert = $pdo->prepare("INSERT INTO wards (name, sub_county_id) VALUES (?, ?)");
                $ward_insert->execute([$ward_name, $sub_county_id]);
                $ward_id = $pdo->lastInsertId();
            }

            // Validate and format dates
            $start_date_formatted = null;
            if (!empty($start_date)) {
                $start_date_formatted = date('Y-m-d', strtotime($start_date));
                if ($start_date_formatted === '1970-01-01') {
                    $start_date_formatted = null;
                }
            }

            $expected_completion_formatted = null;
            if (!empty($expected_completion_date)) {
                $expected_completion_formatted = date('Y-m-d', strtotime($expected_completion_date));
                if ($expected_completion_formatted === '1970-01-01') {
                    $expected_completion_formatted = null;
                }
            }

            // Insert project with default status and visibility
            $project_sql = "INSERT INTO projects (
                project_name, description, department_id, county_id, sub_county_id, ward_id,
                location_address, location_coordinates, project_year, start_date,
                expected_completion_date, contractor_name, contractor_contact, status,
                visibility, total_steps, completed_steps, progress_percentage, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planning', 'private', 1, 0, 0, ?, NOW())";

            $project_stmt = $pdo->prepare($project_sql);
            $project_stmt->execute([
                $project_name,
                $description,
                $department_id,
                $county_id,
                $sub_county_id,
                $ward_id,
                $location_address,
                $location_coordinates,
                intval($project_year),
                $start_date_formatted,
                $expected_completion_formatted,
                $contractor_name,
                $contractor_contact,
                $_SESSION['admin_id']
            ]);

            $project_id = $pdo->lastInsertId();

            // Insert default step or use provided step
            $step_name_final = !empty($step_name) ? $step_name : 'Project Planning & Approval';
            $step_description_final = !empty($step_description) ? $step_description : 'Initial project planning, design review, and regulatory approval process';

            $step_sql = "INSERT INTO project_steps (
                project_id, step_number, step_name, description, status, created_at
            ) VALUES (?, 1, ?, ?, 'pending', NOW())";

            $step_stmt = $pdo->prepare($step_sql);
            $step_stmt->execute([
                $project_id,
                $step_name_final,
                $step_description_final
            ]);

            $success_count++;

        } catch (Exception $e) {
            $errors[] = "Row {$row_number}: Database error - " . $e->getMessage();
            $error_count++;
        }
    }

    fclose($handle);

    $pdo->commit();

    if ($success_count > 0) {
        $message = "Successfully imported {$success_count} projects";
        if ($error_count > 0) {
            $message .= " with {$error_count} errors";
        }

        $response_data = [
            'success' => true,
            'message' => $message,
            'imported_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors
        ];
    } else {
        $response_data = [
            'success' => false,
            'message' => 'No projects were imported due to errors',
            'imported_count' => 0,
            'error_count' => $error_count,
            'errors' => $errors
        ];
    }

    json_response($response_data);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("CSV upload error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'An error occurred while processing the CSV file']);
}


?>