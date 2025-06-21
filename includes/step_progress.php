<?php
/**
 * Step-based progress calculation functions
 */

/**
 * Calculate project progress based on step states (planning=0%, in_progress=50%, completed=100%)
 */
function calculate_step_progress($project_id) {
    global $pdo;

    try {
        // Get all steps for this project
        $stmt = $pdo->prepare("SELECT status FROM project_steps WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $steps = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($steps)) {
            return 0;
        }

        $total_possible_points = count($steps) * 2; // Each step can contribute max 2 points
        $earned_points = 0;

        foreach ($steps as $status) {
            switch ($status) {
                case 'pending':
                case 'planning':
                    $earned_points += 0; // 0% contribution
                    break;
                case 'in_progress':
                    $earned_points += 1; // 50% contribution
                    break;
                case 'completed':
                    $earned_points += 2; // 100% contribution
                    break;
            }
        }

        return round(($earned_points / $total_possible_points) * 100, 2);

    } catch (Exception $e) {
        error_log("Calculate step progress error: " . $e->getMessage());
        return 0;
    }
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
            // Update project progress using new calculation
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

        // Mark step as incomplete (pending)
        $stmt = $pdo->prepare("UPDATE project_steps SET status = 'pending', actual_end_date = NULL WHERE id = ?");
        $stmt->execute([$step_id]);

        // Get project ID
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $project_id = $stmt->fetchColumn();

        if ($project_id) {
            // Update project progress using new calculation
            update_project_progress($project_id);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Step marked as incomplete'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error updating step: ' . $e->getMessage()];
    }
}

/**
 * Mark step as in progress
 */
function mark_step_in_progress($step_id) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Mark step as in progress
        $stmt = $pdo->prepare("UPDATE project_steps SET status = 'in_progress', actual_end_date = NULL WHERE id = ?");
        $stmt->execute([$step_id]);

        // Get project ID
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $project_id = $stmt->fetchColumn();

        if ($project_id) {
            // Update project progress using new calculation
            update_project_progress($project_id);
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Step marked as in progress'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error updating step: ' . $e->getMessage()];
    }
}
?>