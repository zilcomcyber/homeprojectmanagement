<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$current_admin = get_current_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: projects.php');
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: projects.php?error=Invalid security token');
    exit();
}

try {
    $project_id = intval($_POST['project_id']);

    // Validate required fields
    $required_fields = ['project_name', 'department_id', 'project_year', 'county_id', 'sub_county_id', 'ward_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: edit_project.php?id=$project_id&error=Please fill in all required fields");
            exit();
        }
    }

    // Check if project exists
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    if (!$stmt->fetch()) {
        header('Location: projects.php?error=Project not found');
        exit();
    }

    // Update project
    $sql = "UPDATE projects SET 
                project_name = ?, description = ?, department_id = ?, project_year = ?,
                county_id = ?, sub_county_id = ?, ward_id = ?, location_address = ?, 
                location_coordinates = ?, contractor_name = ?, contractor_contact = ?, 
                start_date = ?, expected_completion_date = ?, status = ?, visibility = ?, updated_at = NOW()
            WHERE id = ?";

    $params = [
        sanitize_input($_POST['project_name']),
        sanitize_input($_POST['description'] ?? ''),
        intval($_POST['department_id']),
        intval($_POST['project_year']),
        intval($_POST['county_id']),
        intval($_POST['sub_county_id']),
        intval($_POST['ward_id']),
        sanitize_input($_POST['location_address'] ?? ''),
        sanitize_input($_POST['location_coordinates'] ?? ''),
        sanitize_input($_POST['contractor_name'] ?? ''),
        sanitize_input($_POST['contractor_contact'] ?? ''),
        !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null,
        $_POST['status'] ?? 'planning',
        $_POST['visibility'] ?? 'private',
        $project_id
    ];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        log_activity("Project updated: " . $_POST['project_name'], $current_admin['id']);
        header("Location: projects.php?success=Project updated successfully");
    } else {
        header("Location: edit_project.php?id=$project_id&error=Failed to update project");
    }

} catch (Exception $e) {
    error_log("Project update error: " . $e->getMessage());
    header("Location: edit_project.php?id=" . ($_POST['project_id'] ?? 0) . "&error=An error occurred while updating the project");
}
?>