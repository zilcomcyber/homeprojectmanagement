<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

header('Content-Type: application/json');

try {
    // Fetch all active templates
    $stmt = $pdo->prepare("SELECT id, name, subject, content, category FROM feedback_templates WHERE is_active = 1 ORDER BY category, name");
    $stmt->execute();
    $templates = $stmt->fetchAll();

    json_response([
        'success' => true,
        'templates' => $templates
    ]);

} catch (Exception $e) {
    error_log("Get templates error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Failed to load templates'
    ], 500);
}

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}
