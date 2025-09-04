<?php
// API para solicitudes de prueba gratuita - Versión paso a paso
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Paso 1: Iniciar sesión
    session_start();
    
    // Paso 2: Incluir database
    if (!file_exists('../config/database.php')) {
        throw new Exception('Archivo database.php no encontrado');
    }
    
    require_once '../config/database.php';
    
    // Paso 3: Verificar conexión
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    // Paso 4: Verificar que la tabla existe, si no, crearla
    $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
    if ($stmt->rowCount() == 0) {
        // Crear tabla
        $createTableSQL = "
        CREATE TABLE trial_requests (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
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
        
        $db->exec($createTableSQL);
        
        // Insertar datos de ejemplo
        $sampleData = [
            [
                'id' => 'sample-request-1',
                'user_id' => 'juan-user-1234-5678-9012-12345678901',
                'status' => 'pending'
            ],
            [
                'id' => 'sample-request-2', 
                'user_id' => 'fernando-user-1234-5678-9012-1234567',
                'status' => 'approved',
                'trial_website' => 'https://demo.dentexapro.com/fernando',
                'trial_username' => 'fernando_demo',
                'trial_password' => 'demo123',
                'admin_notes' => 'Aprobado para prueba completa'
            ]
        ];
        
        foreach ($sampleData as $sample) {
            $stmt = $db->prepare("
                INSERT INTO trial_requests (id, user_id, status, trial_website, trial_username, trial_password, admin_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sample['id'],
                $sample['user_id'],
                $sample['status'],
                $sample['trial_website'] ?? null,
                $sample['trial_username'] ?? null,
                $sample['trial_password'] ?? null,
                $sample['admin_notes'] ?? null
            ]);
        }
    }
    
    // Paso 5: Manejar la petición según el método
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequests($db);
            break;
        case 'POST':
            handleCreateRequest($db);
            break;
        case 'PUT':
            handleUpdateRequest($db);
            break;
        default:
            throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}

function handleGetRequests($db) {
    try {
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
        
        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al cargar solicitudes: ' . $e->getMessage());
    }
}

function handleCreateRequest($db) {
    try {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Usuario no autenticado');
        }
        
        $userId = $_SESSION['user_id'];
        
        // Verificar si ya existe una solicitud pendiente
        $stmt = $db->prepare("SELECT id FROM trial_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            throw new Exception('Ya tienes una solicitud pendiente');
        }
        
        // Crear nueva solicitud
        $requestId = 'req_' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO trial_requests (id, user_id, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$requestId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud enviada correctamente. Te notificaremos cuando esté lista.'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al crear solicitud: ' . $e->getMessage());
    }
}

function handleUpdateRequest($db) {
    try {
        $requestId = $_GET['id'] ?? '';
        if (empty($requestId)) {
            throw new Exception('ID de solicitud requerido');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Datos inválidos');
        }
        
        // Verificar que es admin
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acceso denegado');
        }
        
        $status = $input['status'] ?? '';
        $adminNotes = $input['admin_notes'] ?? '';
        $processedBy = $_SESSION['user_id'];
        
        if ($status === 'approved') {
            $trialWebsite = $input['trial_website'] ?? '';
            $trialUsername = $input['trial_username'] ?? '';
            $trialPassword = $input['trial_password'] ?? '';
            
            if (empty($trialWebsite) || empty($trialUsername) || empty($trialPassword)) {
                throw new Exception('Datos de acceso requeridos para aprobar la solicitud');
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud procesada correctamente'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al procesar solicitud: ' . $e->getMessage());
    }
}
?>