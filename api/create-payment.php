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
        echo json_encode(['error' => 'MercadoPago no está configurado correctamente']);
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
    
    // Create external reference
    $externalReference = $_SESSION['user_id'] . '_' . $planType . '_' . time();
    
    // Detect if we're on localhost and handle URLs accordingly
    $isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                   strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    
    if ($isLocalhost) {
        // For localhost, use a simpler structure without back_urls
        $preference = [
            'items' => [
                [
                    'title' => "Suscripción DentexaPro - Plan " . ucfirst($planType),
                    'quantity' => 1,
                    'unit_price' => $amount
                ]
            ],
            'external_reference' => $externalReference,
            'statement_descriptor' => 'DentexaPro'
        ];
    } else {
        // For production, use full structure with back_urls
        $baseUrl = "https://" . $_SERVER['HTTP_HOST'];
        $preference = [
            'items' => [
                [
                    'title' => "Suscripción DentexaPro - Plan " . ucfirst($planType),
                    'quantity' => 1,
                    'unit_price' => $amount
                ]
            ],
            'back_urls' => [
                'success' => $baseUrl . "/pago-exitoso.php?plan=" . urlencode($planType),
                'failure' => $baseUrl . "/pago-fallido.php?plan=" . urlencode($planType),
                'pending' => $baseUrl . "/pago-pendiente.php?plan=" . urlencode($planType)
            ],
            'auto_return' => 'approved',
            'external_reference' => $externalReference,
            'statement_descriptor' => 'DentexaPro'
        ];
    }
    
    error_log("MercadoPago Request: " . json_encode($preference, JSON_PRETTY_PRINT));
    
    // Send request to MercadoPago using EXACT same configuration as test
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($preference),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['mercadopago_access_token']
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log the response for debugging
    error_log("MercadoPago Response Code: " . $httpCode);
    error_log("MercadoPago Response: " . $response);
    
    // Check for cURL errors first
    if ($curlError) {
        echo json_encode([
            'error' => 'Error de conexión: ' . $curlError
        ]);
        exit();
    }
    
    if ($httpCode !== 201) {
        $responseData = json_decode($response, true);
        
        // Extract specific error message from MercadoPago
        $errorMessage = 'Error HTTP ' . $httpCode;
        if ($responseData) {
            if (isset($responseData['message'])) {
                $errorMessage = $responseData['message'];
            } elseif (isset($responseData['error'])) {
                $errorMessage = $responseData['error'];
            } elseif (isset($responseData['cause'])) {
                $causes = [];
                foreach ($responseData['cause'] as $cause) {
                    $causes[] = $cause['description'] ?? 'Error desconocido';
                }
                $errorMessage = implode(', ', $causes);
            }
        }
        
        echo json_encode([
            'error' => $errorMessage,
            'debug' => [
                'http_code' => $httpCode,
                'mercadopago_response' => $responseData,
                'request_sent' => $preference,
                'access_token_configured' => !empty($settings['mercadopago_access_token'])
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
    try {
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
            $externalReference
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the payment
        error_log("Error saving payment attempt: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'init_point' => $responseData['init_point'],
        'preference_id' => $responseData['id']
    ]);
    
} catch (Exception $e) {
    error_log("MercadoPago error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>