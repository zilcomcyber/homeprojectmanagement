<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if this is a comment submission or regular feedback
        $is_comment = isset($_POST['message']) || isset($_POST['parent_comment_id']) || isset($_POST['citizen_name']);

        if ($is_comment) {
            // Handle comment submission
            $project_id = intval($_POST['project_id'] ?? 0);
            $user_name = sanitize_input($_POST['citizen_name'] ?? '');
            $user_email = sanitize_input($_POST['citizen_email'] ?? '');
            $comment_text = sanitize_input($_POST['message'] ?? '');
            $parent_id = intval($_POST['parent_comment_id'] ?? 0);
            
            error_log("Comment submission data - Project ID: $project_id, User: '$user_name', Email: '$user_email', Comment: '$comment_text', Parent: $parent_id");

            // Validation
            if (empty($project_id) || empty($user_name) || empty($comment_text)) {
                error_log("Validation failed - Project ID: $project_id, User Name: '$user_name', Comment: '$comment_text'");
                json_response(['success' => false, 'message' => 'Please fill in all required fields (name and comment are required)'], 400);
            }

            // Validate name length
            if (strlen($user_name) < 2) {
                json_response(['success' => false, 'message' => 'Name must be at least 2 characters long'], 400);
            }

            // Validate comment length
            if (strlen($comment_text) < 10) {
                json_response(['success' => false, 'message' => 'Comment must be at least 10 characters long'], 400);
            }

            // Validate email if provided
            if (!empty($user_email) && !validate_email($user_email)) {
                json_response(['success' => false, 'message' => 'Please enter a valid email address'], 400);
            }

            // Check if project exists
            $project = get_project_by_id($project_id);
            if (!$project) {
                json_response(['success' => false, 'message' => 'Project not found'], 404);
            }

            // Add comment
            $result = add_project_comment($project_id, $comment_text, $user_name, $user_email, $parent_id);
            json_response($result);
        } else {
            // Submit regular feedback
            $project_id = intval($_POST['project_id'] ?? 0);
            $citizen_name = sanitize_input($_POST['citizen_name'] ?? '');
            $citizen_email = sanitize_input($_POST['citizen_email'] ?? '');
            $citizen_phone = sanitize_input($_POST['citizen_phone'] ?? '');
            $subject = sanitize_input($_POST['subject'] ?? 'General Feedback');
            $message = sanitize_input($_POST['message'] ?? '');

            // Validation
            if (empty($project_id) || empty($citizen_name) || empty($message)) {
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

            // Get user's IP address
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Insert into database
            $sql = "INSERT INTO feedback (project_id, citizen_name, citizen_email, citizen_phone, subject, message, status, user_ip, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$project_id, $citizen_name, $citizen_email, $citizen_phone, $subject, $message, $user_ip, $user_agent])) {
                log_activity("New feedback submitted for project ID: $project_id by $citizen_name");
                json_response(['success' => true, 'message' => 'Feedback submitted successfully']);
            } else {
                throw new Exception('Failed to insert feedback');
            }
        }

    } else {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("Feedback API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to submit feedback'], 500);
}