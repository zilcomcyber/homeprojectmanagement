<?php
require_once 'config.php';
require_once 'includes/functions.php';

/**
 * Recalculate progress for all projects using the new step-based system
 */
function recalculate_all_projects_progress() {
    global $pdo;
    
    try {
        // Get all projects
        $stmt = $pdo->query("SELECT id FROM projects");
        $projects = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updated_count = 0;
        
        foreach ($projects as $project_id) {
            $new_progress = update_project_progress($project_id);
            if ($new_progress !== false) {
                $updated_count++;
                echo "Updated project ID {$project_id} - Progress: {$new_progress}%\n";
            }
        }
        
        echo "\nRecalculation complete. Updated {$updated_count} projects.\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Run the recalculation if this script is called directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    recalculate_all_projects_progress();
}
?>
