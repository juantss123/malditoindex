<?php
// MercadoPago webhook handler
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get payment settings
    $stmt = $db->prepare("SELECT mercadopago_access_token FROM payment_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if (!$settings || !$settings['mercadopago_access_token']) {
        http_response_code(400);
        echo json_encode(['error' => 'MercadoPago no configurado']);
        exit();
    }
    
    // Get webhook data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['data']['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos de webhook inválidos']);
        exit();
    }
    
    $paymentId = $input['data']['id'];
    
    // Get payment details from MercadoPago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$paymentId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $settings['mercadopago_access_token']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        http_response_code(400);
        echo json_encode(['error' => 'Error al consultar pago en MercadoPago']);
        exit();
    }
    
    $paymentData = json_decode($response, true);
    
    if (!$paymentData) {
        http_response_code(400);
        echo json_encode(['error' => 'Respuesta inválida de MercadoPago']);
        exit();
    }
    
    // Extract user_id and plan from external_reference
    $externalReference = $paymentData['external_reference'] ?? '';
    $referenceParts = explode('_', $externalReference);
    
    if (count($referenceParts) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Referencia externa inválida']);
        exit();
    }
    
    $userId = $referenceParts[0];
    $planType = $referenceParts[1];
    
    // Update payment attempt
    $stmt = $db->prepare("
        UPDATE payment_attempts 
        SET status = ? 
        WHERE external_reference = ?
    ");
    $stmt->execute([$paymentData['status'], $externalReference]);
    
    // If payment is approved, update user subscription
    if ($paymentData['status'] === 'approved') {
        $stmt = $db->prepare("
            UPDATE user_profiles 
            SET subscription_status = 'active', subscription_plan = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$planType, $userId]);
        
        // Create subscription history record
        $stmt = $db->prepare("
            CREATE TABLE IF NOT EXISTS subscription_history (
                id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                plan ENUM('start', 'clinic', 'enterprise') NOT NULL,
                status ENUM('active', 'cancelled', 'expired') NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT NULL,
                payment_id VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $stmt->execute();
        
        $stmt = $db->prepare("
            INSERT INTO subscription_history (user_id, plan, status, start_date, amount, payment_method, payment_id)
            VALUES (?, ?, 'active', CURDATE(), ?, 'mercadopago', ?)
        ");
        $stmt->execute([$userId, $planType, $paymentData['transaction_amount'], $paymentId]);
    }
    
    echo json_encode(['success' => true, 'status' => $paymentData['status']]);
    
} catch (Exception $e) {
    error_log("MercadoPago webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>