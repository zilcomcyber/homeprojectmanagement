
<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Validate CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        $project_id = (int)($_POST['project_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $user_name = sanitize_input($_POST['user_name'] ?? '');
        $user_email = sanitize_input($_POST['user_email'] ?? '');
        $comment = sanitize_input($_POST['comment'] ?? '');

        if (!$project_id) {
            throw new Exception('Project ID is required');
        }

        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5 stars');
        }

        // Validate email if provided
        if ($user_email && !validate_email($user_email)) {
            throw new Exception('Invalid email format');
        }

        $result = add_project_rating($project_id, $rating, $user_name, $user_email, $comment);

        if ($result['success']) {
            // Get updated project rating
            $stmt = $pdo->prepare("SELECT average_rating, total_ratings FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'average_rating' => $project['average_rating'],
                'total_ratings' => $project['total_ratings']
            ]);
        } else {
            echo json_encode($result);
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $project_id = (int)($_GET['project_id'] ?? 0);
        
        if (!$project_id) {
            throw new Exception('Project ID is required');
        }

        $ratings = get_project_ratings($project_id, 10);
        
        echo json_encode([
            'success' => true,
            'ratings' => $ratings
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
