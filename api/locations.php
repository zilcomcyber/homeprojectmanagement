
<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'sub_counties':
            $county_id = intval($_GET['county_id'] ?? 0);
            if ($county_id > 0) {
                $stmt = $pdo->prepare("SELECT id, name FROM sub_counties WHERE county_id = ? ORDER BY name");
                $stmt->execute([$county_id]);
                $sub_counties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'data' => $sub_counties,
                    'count' => count($sub_counties)
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid county ID',
                    'data' => []
                ]);
            }
            break;
            
        case 'wards':
            $sub_county_id = intval($_GET['sub_county_id'] ?? 0);
            if ($sub_county_id > 0) {
                $stmt = $pdo->prepare("SELECT id, name FROM wards WHERE sub_county_id = ? ORDER BY name");
                $stmt->execute([$sub_county_id]);
                $wards = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'data' => $wards,
                    'count' => count($wards)
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid sub-county ID',
                    'data' => []
                ]);
            }
            break;
            
        case 'counties':
            $stmt = $pdo->prepare("SELECT id, name FROM counties ORDER BY name");
            $stmt->execute();
            $counties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'data' => $counties,
                'count' => count($counties)
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action. Use: sub_counties, wards, or counties',
                'data' => []
            ]);
    }
    
} catch (Exception $e) {
    error_log("Locations API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
