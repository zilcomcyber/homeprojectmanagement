<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // Submit feedback
        $project_id = intval($_POST['project_id'] ?? 0);
        $citizen_name = sanitize_input($_POST['citizen_name'] ?? '');
        $citizen_email = sanitize_input($_POST['citizen_email'] ?? '');
        $citizen_phone = sanitize_input($_POST['citizen_phone'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        // Validation
        if (empty($project_id) || empty($citizen_name) || empty($subject) || empty($message)) {
            json_response(['success' => false, 'message' => 'Please fill in all required fields'], 400);
        }
        
        // Validate email if provided
        if (!empty($citizen_email) && !validate_email($citizen_email)) {
            json_response(['success' => false, 'message' => 'Please enter a valid email address'], 400);
        }
        
        // Check if project exists
        $project = get_project_by_id($project_id);
        if (!$project) {
            json_response(['success' => false, 'message' => 'Project not found'], 404);
        }
        
        // Insert feedback
        $sql = "INSERT INTO feedback (project_id, citizen_name, citizen_email, citizen_phone, subject, message) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$project_id, $citizen_name, $citizen_email, $citizen_phone, $subject, $message])) {
            log_activity("New feedback submitted for project ID: $project_id by $citizen_name");
            json_response(['success' => true, 'message' => 'Feedback submitted successfully']);
        } else {
            throw new Exception('Failed to insert feedback');
        }
        
    } else {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Feedback API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to submit feedback'], 500);
}
?>
