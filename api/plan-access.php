<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Require admin access for all operations
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetPlanAccess();
        break;
    case 'POST':
        handleSavePlanAccess();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetPlanAccess() {
    global $db;
    
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    try {
        // Create plan_access table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS plan_access (
                id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL UNIQUE,
                panel_url VARCHAR(500) DEFAULT NULL,
                panel_username VARCHAR(100) DEFAULT NULL,
                panel_password VARCHAR(100) DEFAULT NULL,
                access_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $stmt = $db->prepare("SELECT * FROM plan_access WHERE user_id = ?");
        $stmt->execute([$userId]);
        $access = $stmt->fetch();
        
        echo json_encode(['success' => true, 'access' => $access]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar datos de acceso: ' . $e->getMessage()]);
    }
}

function handleSavePlanAccess() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['userId'] ?? '';
    $panelUrl = $input['panel_url'] ?? '';
    $panelUsername = $input['panel_username'] ?? '';
    $panelPassword = $input['panel_password'] ?? '';
    $accessNotes = $input['access_notes'] ?? '';
    
    if (empty($userId) || empty($panelUrl) || empty($panelUsername) || empty($panelPassword)) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos obligatorios son requeridos']);
        return;
    }
    
    try {
        // Create table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS plan_access (
                id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL UNIQUE,
                panel_url VARCHAR(500) DEFAULT NULL,
                panel_username VARCHAR(100) DEFAULT NULL,
                panel_password VARCHAR(100) DEFAULT NULL,
                access_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Insert or update access data
        $stmt = $db->prepare("
            INSERT INTO plan_access (user_id, panel_url, panel_username, panel_password, access_notes)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                panel_url = VALUES(panel_url),
                panel_username = VALUES(panel_username),
                panel_password = VALUES(panel_password),
                access_notes = VALUES(access_notes),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$userId, $panelUrl, $panelUsername, $panelPassword, $accessNotes]);
        
        echo json_encode(['success' => true, 'message' => 'Datos de acceso guardados exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar datos de acceso: ' . $e->getMessage()]);
    }
}
?>