<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleAuthCheck();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function handleRegister() {
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
        
        // Create user profile
        $userId = generateUUID();
        $isAdmin = $input['email'] === 'admin@dentexapro.com';
        
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
            $isAdmin ? 'admin' : 'user',
            'trial',
            hashPassword($input['password'])
        ]);
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $input['email'];
        $_SESSION['user_role'] = $isAdmin ? 'admin' : 'user';
        $_SESSION['user_name'] = $input['firstName'] . ' ' . $input['lastName'];
        
        echo json_encode([
            'success' => true,
            'message' => '¡Cuenta creada exitosamente!',
            'redirect' => $isAdmin ? 'admin.php' : 'dashboard.php'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear la cuenta: ' . $e->getMessage()]);
    }
}

function handleLogin() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email y contraseña son requeridos']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT id, user_id, first_name, last_name, email, role, password_hash 
            FROM user_profiles 
            WHERE email = ?
        ");
        $stmt->execute([$input['email']]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($input['password'], $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Email o contraseña incorrectos']);
            return;
        }
        
        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        echo json_encode([
            'success' => true,
            'message' => '¡Bienvenido de vuelta!',
            'redirect' => $user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al iniciar sesión: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
}

function handleAuthCheck() {
    if (isLoggedIn()) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}
?>