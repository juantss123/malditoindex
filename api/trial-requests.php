<?php
// API simplificada para solicitudes de prueba gratuita
error_reporting(0); // Desactivar errores para evitar HTML en respuesta
ini_set('display_errors', 0);

// Headers JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para enviar respuesta JSON y terminar
function sendResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data);
    exit();
}

// Manejar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(['success' => true]);
}

try {
    // Iniciar sesión
    session_start();
    
    // Incluir database
    require_once '../config/database.php';
    
    // Conectar a DB
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendResponse(['error' => 'Error de conexión a base de datos'], 500);
    }
    
    // Verificar si la tabla existe y crearla si no
    $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
    if ($stmt->rowCount() == 0) {
        // Crear tabla con todas las columnas necesarias
        $createSQL = "
        CREATE TABLE trial_requests (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            trial_website VARCHAR(255) DEFAULT NULL,
            trial_username VARCHAR(100) DEFAULT NULL,
            trial_password VARCHAR(100) DEFAULT NULL,
            admin_notes TEXT DEFAULT NULL,
            processed_by VARCHAR(36) DEFAULT NULL,
            processed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createSQL);
        
        // Insertar datos de ejemplo
        $sampleRequests = [
            [
                'id' => 'sample-req-1',
                'user_id' => 'juan-user-1234-5678-9012-12345678901',
                'status' => 'pending'
            ],
            [
                'id' => 'sample-req-2',
                'user_id' => 'fernando-user-1234-5678-9012-1234567',
                'status' => 'approved',
                'trial_website' => 'https://demo.dentexapro.com/fernando',
                'trial_username' => 'fernando_demo',
                'trial_password' => 'demo123',
                'admin_notes' => 'Aprobado para prueba completa'
            ]
        ];
        
        foreach ($sampleRequests as $req) {
            $stmt = $db->prepare("
                INSERT INTO trial_requests (id, user_id, status, trial_website, trial_username, trial_password, admin_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $req['id'],
                $req['user_id'], 
                $req['status'],
                $req['trial_website'] ?? null,
                $req['trial_username'] ?? null,
                $req['trial_password'] ?? null,
                $req['admin_notes'] ?? null
            ]);
        }
    } else {
        // Verificar y agregar columnas faltantes
        $columnsToAdd = [
            'trial_website' => 'VARCHAR(255) DEFAULT NULL',
            'trial_username' => 'VARCHAR(100) DEFAULT NULL',
            'trial_password' => 'VARCHAR(100) DEFAULT NULL'
        ];
        
        foreach ($columnsToAdd as $columnName => $columnDef) {
            $stmt = $db->query("SHOW COLUMNS FROM trial_requests LIKE '$columnName'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE trial_requests ADD COLUMN $columnName $columnDef");
            }
        }
    }
    
    // Manejar petición según método
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Obtener solicitudes
        $stmt = $db->query("
            SELECT 
                tr.*,
                CONCAT(up.first_name, ' ', up.last_name) as user_name,
                up.email,
                up.clinic_name,
                up.phone
            FROM trial_requests tr
            LEFT JOIN user_profiles up ON tr.user_id = up.user_id
            ORDER BY tr.request_date DESC
        ");
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(['success' => true, 'requests' => $requests]);
        
    } elseif ($method === 'POST') {
        // Crear nueva solicitud
        if (!isset($_SESSION['user_id'])) {
            sendResponse(['error' => 'Usuario no autenticado'], 401);
        }
        
        $userId = $_SESSION['user_id'];
        
        // Verificar si ya existe solicitud pendiente
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Ya tienes una solicitud pendiente'], 400);
        }
        
        // Crear solicitud
        $requestId = 'req_' . uniqid();
        $stmt = $db->prepare("INSERT INTO trial_requests (id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$requestId, $userId]);
        
        sendResponse(['success' => true, 'message' => 'Solicitud enviada correctamente']);
        
    } elseif ($method === 'PUT') {
        // Actualizar solicitud
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            sendResponse(['error' => 'Acceso denegado'], 403);
        }
        
        $requestId = $_GET['id'] ?? '';
        if (empty($requestId)) {
            sendResponse(['error' => 'ID de solicitud requerido'], 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendResponse(['error' => 'Datos inválidos'], 400);
        }
        
        $status = $input['status'] ?? '';
        $adminNotes = $input['admin_notes'] ?? '';
        $processedBy = $_SESSION['user_id'];
        
        if ($status === 'approved') {
            $trialWebsite = $input['trial_website'] ?? '';
            $trialUsername = $input['trial_username'] ?? '';
            $trialPassword = $input['trial_password'] ?? '';
            
            if (empty($trialWebsite) || empty($trialUsername) || empty($trialPassword)) {
                sendResponse(['error' => 'Datos de acceso requeridos para aprobar'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE trial_requests 
                SET status = ?, trial_website = ?, trial_username = ?, trial_password = ?, 
                    admin_notes = ?, processed_by = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $trialWebsite, $trialUsername, $trialPassword, $adminNotes, $processedBy, $requestId]);
        } else {
            $stmt = $db->prepare("
                UPDATE trial_requests 
                SET status = ?, admin_notes = ?, processed_by = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminNotes, $processedBy, $requestId]);
        }
        
        sendResponse(['success' => true, 'message' => 'Solicitud procesada correctamente']);
        
    } else {
        sendResponse(['error' => 'Método no permitido'], 405);
    }
    
} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}
?>