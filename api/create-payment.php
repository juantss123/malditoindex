<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get payment settings
    $stmt = $db->prepare("SELECT * FROM payment_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if (!$settings || !$settings['mercadopago_enabled'] || !$settings['mercadopago_access_token']) {
        echo json_encode(['error' => 'MercadoPago no está configurado']);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $planType = $input['plan_type'] ?? '';
    $amount = (float) ($input['amount'] ?? 0);
    $planName = $input['plan_name'] ?? '';
    
    if (empty($planType) || $amount <= 0) {
        echo json_encode(['error' => 'Datos de pago inválidos']);
        exit();
    }
    
    // Get user data
    $stmt = $db->prepare("
        SELECT first_name, last_name, email, clinic_name 
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit();
    }
    
    // Create payment preference
    $preference = [
        'items' => [
            [
                'title' => "DentexaPro - Plan {$planName}",
                'description' => "Suscripción mensual al plan {$planName} de DentexaPro",
                'quantity' => 1,
                'unit_price' => $amount,
                'currency_id' => 'ARS'
            ]
        ],
        'payer' => [
            'name' => $user['first_name'],
            'surname' => $user['last_name'],
            'email' => $user['email']
        ],
        'back_urls' => [
            'success' => "http://" . $_SERVER['HTTP_HOST'] . "/pago-exitoso.php?plan=" . urlencode($planType),
            'failure' => "http://" . $_SERVER['HTTP_HOST'] . "/pago-fallido.php?plan=" . urlencode($planType),
            'pending' => "http://" . $_SERVER['HTTP_HOST'] . "/pago-pendiente.php?plan=" . urlencode($planType)
        ],
        'auto_return' => 'approved',
        'external_reference' => $_SESSION['user_id'] . '_' . $planType . '_' . time()
    ];
    
    // Send request to MercadoPago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $settings['mercadopago_access_token']
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DentexaPro/1.0');
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    // Check for cURL errors first
    if ($curlError) {
        echo json_encode([
            'error' => 'Error de conexión con MercadoPago: ' . $curlError,
            'debug' => [
                'curl_error' => $curlError,
                'curl_info' => $curlInfo,
                'suggestion' => 'Verifica tu conexión a internet y configuración de firewall'
            ]
        ]);
        exit();
    }
    
    if ($httpCode !== 201) {
        echo json_encode([
            'error' => 'Error HTTP ' . $httpCode . ' de MercadoPago',
            'debug' => [
                'http_code' => $httpCode,
                'response' => $response,
                'request_data' => $preference,
                'suggestion' => 'Verifica que las credenciales sean de producción si estás en producción'
            ]
        ]);
        exit();
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData || !isset($responseData['init_point'])) {
        echo json_encode(['error' => 'Respuesta inválida de MercadoPago']);
        exit();
    }
    
    // Save payment attempt in database
    $stmt = $db->prepare("
        CREATE TABLE IF NOT EXISTS payment_attempts (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            mp_preference_id VARCHAR(255) NOT NULL,
            external_reference VARCHAR(255) NOT NULL,
            status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $stmt->execute();
    
    $stmt = $db->prepare("
        INSERT INTO payment_attempts (user_id, plan_type, amount, mp_preference_id, external_reference)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $planType,
        $amount,
        $responseData['id'],
        $preference['external_reference']
    ]);
    
    echo json_encode([
        'success' => true,
        'init_point' => $responseData['init_point'],
        'preference_id' => $responseData['id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>