<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Require admin access
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
        handleGetUsers();
        break;
    case 'POST':
        handleCreateUser();
        break;
    case 'PUT':
        handleUpdateUser();
        break;
    case 'DELETE':
        handleDeleteUser();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetUsers() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                id, user_id, first_name, last_name, email, phone, clinic_name,
                license_number, specialty, team_size, role, subscription_status,
                subscription_plan, trial_start_date, trial_end_date, created_at
            FROM user_profiles 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        // Calculate trial days remaining for each user
        foreach ($users as &$user) {
            if ($user['subscription_status'] === 'trial' && $user['trial_end_date']) {
                $trialEnd = new DateTime($user['trial_end_date']);
                $today = new DateTime();
                $diff = $today->diff($trialEnd);
                $user['trial_days_remaining'] = $diff->invert ? 0 : $diff->days;
            } else {
                $user['trial_days_remaining'] = null;
            }
        }
        
        echo json_encode(['users' => $users]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar usuarios: ' . $e->getMessage()]);
    }
}

function handleCreateUser() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['firstName', 'lastName', 'email', 'password', 'phone', 'clinicName'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM user_profiles WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'El email ya está registrado']);
            return;
        }
        
        // Create user
        $userId = generateUUID();
        $stmt = $db->prepare("
            INSERT INTO user_profiles (
                id, user_id, first_name, last_name, email, phone, clinic_name,
                license_number, specialty, team_size, role, subscription_status,
                password_hash, trial_start_date, trial_end_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY))
        ");
        
        $stmt->execute([
            generateUUID(),
            $userId,
            $input['firstName'],
            $input['lastName'],
            $input['email'],
            $input['phone'],
            $input['clinicName'],
            $input['licenseNumber'] ?? '',
            $input['specialty'] ?? '',
            $input['teamSize'] ?? '1',
            'user',
            'trial',
            hashPassword($input['password'])
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear usuario: ' . $e->getMessage()]);
    }
}

function handleUpdateUser() {
    global $db;
    
    $userId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    try {
        $fields = [];
        $values = [];
        
        $allowedFields = ['first_name', 'last_name', 'phone', 'clinic_name', 'license_number', 'specialty', 'team_size', 'subscription_status', 'subscription_plan'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No hay campos para actualizar']);
            return;
        }
        
        $values[] = $userId;
        
        $stmt = $db->prepare("UPDATE user_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?");
        $stmt->execute($values);
        
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar usuario: ' . $e->getMessage()]);
    }
}

function handleDeleteUser() {
    global $db;
    
    $userId = $_GET['id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar usuario: ' . $e->getMessage()]);
    }
}
?>