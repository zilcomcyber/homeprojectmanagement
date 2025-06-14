<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
        header("Location: create_project.php?error=" . urlencode($error));
        exit();
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_project') {
            $result = handle_project_creation($_POST);
            if ($result['success']) {
                $project_id = $result['project_id'];
                header("Location: manage_project.php?id=$project_id&created=1");
                exit();
            } else {
                $error = $result['message'];
                header("Location: create_project.php?error=" . urlencode($error));
                exit();
            }
        }
    }
} else {
    // Redirect if not POST request
    header("Location: create_project.php");
    exit();
}

function handle_project_creation($data) {
    global $pdo, $current_admin;

    try {
        // Validate required fields
        $required_fields = ['project_name', 'department_id', 'project_year', 'county_id', 'sub_county_id', 'ward_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Please fill in all required fields. Missing: $field"];
            }
        }

        $pdo->beginTransaction();

        // Create project
        $sql = "INSERT INTO projects (
                    project_name, description, department_id, project_year,
                    county_id, sub_county_id, ward_id, location_address, location_coordinates,
                    contractor_name, contractor_contact, start_date, expected_completion_date, 
                    status, visibility, step_status, progress_percentage, total_steps, completed_steps, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            sanitize_input($data['project_name']),
            sanitize_input($data['description'] ?? ''),
            intval($data['department_id']),
            intval($data['project_year']),
            intval($data['county_id']),
            intval($data['sub_county_id']),
            intval($data['ward_id']),
            sanitize_input($data['location_address'] ?? ''),
            sanitize_input($data['location_coordinates'] ?? ''),
            sanitize_input($data['contractor_name'] ?? ''),
            sanitize_input($data['contractor_contact'] ?? ''),
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['expected_completion_date']) ? $data['expected_completion_date'] : null,
            'planning', // default status
            'private', // default visibility
            'awaiting', // default step_status
            0, // default progress_percentage
            0, // total_steps (will be updated after inserting steps)
            0, // completed_steps
            $current_admin['id']
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $project_id = $pdo->lastInsertId();

        // Create project steps if provided
        $total_steps = 0;
        $completed_steps = 0;

        if (!empty($data['steps']) && is_array($data['steps'])) {
            $step_sql = "INSERT INTO project_steps (project_id, step_number, step_name, description, status, expected_end_date, actual_end_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $step_stmt = $pdo->prepare($step_sql);

            foreach ($data['steps'] as $index => $step) {
                if (!empty($step['name'])) {
                    $step_status = 'pending';
                    $actual_end_date = null;

                    // If expected_date is set and is in the past, mark as completed
                    if (!empty($step['expected_date'])) {
                        $expected_date = $step['expected_date'];
                        if (strtotime($expected_date) <= time()) {
                            $step_status = 'completed';
                            $actual_end_date = $expected_date;
                            $completed_steps++;
                        }
                    } else {
                        $expected_date = null;
                    }

                    $step_stmt->execute([
                        $project_id,
                        $index + 1,
                        sanitize_input($step['name']),
                        sanitize_input($step['description'] ?? ''),
                        $step_status,
                        $expected_date,
                        $actual_end_date
                    ]);

                    $total_steps++;
                }
            }
        }

        // Calculate progress percentage
        $progress_percentage = ($total_steps > 0) ? round(($completed_steps / $total_steps) * 100, 2) : 0;

        // Update project status based on progress
        $project_status = 'planning';
        $step_status = 'awaiting';

        if ($progress_percentage > 0 && $progress_percentage < 100) {
            $project_status = 'ongoing';
            $step_status = 'running';
        } elseif ($progress_percentage == 100) {
            $project_status = 'completed';
            $step_status = 'completed';
        }

        // Update project with step counts and progress
        $update_sql = "UPDATE projects SET total_steps = ?, completed_steps = ?, progress_percentage = ?, status = ?, step_status = ? WHERE id = ?";
        $pdo->prepare($update_sql)->execute([$total_steps, $completed_steps, $progress_percentage, $project_status, $step_status, $project_id]);

        $pdo->commit();
        log_activity("Project created: " . $data['project_name'], $current_admin['id']);

        return ['success' => true, 'message' => 'Project created successfully', 'project_id' => $project_id];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Project creation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while creating the project: ' . $e->getMessage()];
    }
}
?>