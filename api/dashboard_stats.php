
<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    json_response(['success' => false, 'message' => 'Unauthorized access']);
}

try {
    // Get project statistics
    $total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $ongoing_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'ongoing'")->fetchColumn();
    $completed_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();
    $planning_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'planning'")->fetchColumn();
    
    // Get feedback statistics
    try {
        $pending_feedback = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn();
        $total_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    } catch (PDOException $e) {
        $pending_feedback = 0;
        $total_feedback = 0;
    }
    
    $stats = [
        'total_projects' => $total_projects,
        'ongoing_projects' => $ongoing_projects,
        'completed_projects' => $completed_projects,
        'planning_projects' => $planning_projects,
        'pending_feedback' => $pending_feedback,
        'total_feedback' => $total_feedback
    ];
    
    json_response(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Failed to fetch statistics']);
}
?>
