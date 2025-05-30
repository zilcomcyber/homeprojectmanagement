<?php
/**
 * Step-based progress calculation functions
 */

/**
 * Calculate project progress based on completed steps
 */
function calculate_step_progress($project_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_steps FROM project_steps WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $total_steps = $stmt->fetchColumn();

    if ($total_steps == 0) {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_steps FROM project_steps WHERE project_id = ? AND status = 'completed'");
    $stmt->execute([$project_id]);
    $completed_steps = $stmt->fetchColumn();

    return round(($completed_steps / $total_steps) * 100);
}



/**
 * Mark step as completed
 */
function complete_step($step_id) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Mark step as completed
        $stmt = $pdo->prepare("UPDATE project_steps SET status = 'completed', actual_end_date = CURDATE() WHERE id = ?");
        $stmt->execute([$step_id]);

        // Get project ID
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $project_id = $stmt->fetchColumn();

        if ($project_id) {
            // Update project progress
            update_project_progress($project_id);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Step marked as completed'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error updating step: ' . $e->getMessage()];
    }
}

/**
 * Mark step as incomplete
 */
function incomplete_step($step_id) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Mark step as incomplete
        $stmt = $pdo->prepare("UPDATE project_steps SET status = 'pending', actual_end_date = NULL WHERE id = ?");
        $stmt->execute([$step_id]);

        // Get project ID
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $project_id = $stmt->fetchColumn();

        if ($project_id) {
            // Update project progress
            update_project_progress($project_id);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Step marked as incomplete'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error updating step: ' . $e->getMessage()];
    }
}
?>