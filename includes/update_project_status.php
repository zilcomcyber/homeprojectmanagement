<?php

/**
 * Update project status based on its steps
 */
function update_project_status_based_on_steps($project_id) {
    global $pdo;

    try {
        // Get all steps for this project
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_steps,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_steps,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_steps,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_steps
            FROM project_steps 
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
        $step_data = $stmt->fetch();

        if (!$step_data || $step_data['total_steps'] == 0) {
            // No steps found, set to planning
            $new_status = 'planning';
            $progress = 0;
        } else {
            $total = $step_data['total_steps'];
            $completed = $step_data['completed_steps'];
            $in_progress = $step_data['in_progress_steps'];

            // Calculate progress percentage
            $progress = ($total > 0) ? round(($completed / $total) * 100) : 0;

            // Determine status based on step completion
            if ($completed == $total && $total > 0) {
                // All steps completed - mark as completed
                $new_status = 'completed';
            } elseif ($completed > 0 || $in_progress > 0) {
                // Some steps completed or in progress - mark as ongoing
                $new_status = 'ongoing';
            } else {
                // All steps are pending - set to planning (default status)
                $new_status = 'planning';
            }
        }

        // Update project
        $update_stmt = $pdo->prepare("
            UPDATE projects 
            SET status = ?, 
                progress_percentage = ?,
                completed_steps = ?,
                total_steps = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $update_stmt->execute([
            $new_status, 
            $progress, 
            $step_data['completed_steps'] ?? 0,
            $step_data['total_steps'] ?? 0,
            $project_id
        ]);

        return [
            'success' => true, 
            'status' => $new_status, 
            'progress' => $progress
        ];

    } catch (Exception $e) {
        error_log("Error updating project status: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update step status and trigger project status update
 */
function update_step_status($step_id, $new_status, $start_date = null, $end_date = null, $notes = null) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Get step and project info
        $stmt = $pdo->prepare("SELECT project_id FROM project_steps WHERE id = ?");
        $stmt->execute([$step_id]);
        $step = $stmt->fetch();

        if (!$step) {
            throw new Exception("Step not found");
        }

        // Update step
        $update_fields = ["status = ?"];
        $params = [$new_status];

        if ($start_date !== null) {
            $update_fields[] = "start_date = ?";
            $params[] = $start_date;
        }

        if ($end_date !== null) {
            $update_fields[] = "actual_end_date = ?";
            $params[] = $end_date;
        }

        if ($notes !== null) {
            $update_fields[] = "notes = ?";
            $params[] = $notes;
        }

        $update_fields[] = "updated_at = NOW()";
        $params[] = $step_id;

        $sql = "UPDATE project_steps SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Update project status based on steps
        $result = update_project_status_based_on_steps($step['project_id']);

        $pdo->commit();
        return $result;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating step status: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update all project statuses based on their steps
 */
function update_all_project_statuses() {
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT DISTINCT project_id FROM project_steps");
        $project_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($project_ids as $project_id) {
            update_project_status_based_on_steps($project_id);
        }

        return true;
    } catch (Exception $e) {
        error_log("Error updating all project statuses: " . $e->getMessage());
        return false;
    }
}
?>