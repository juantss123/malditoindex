<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$paymentId = $_GET['payment_id'] ?? '';

if (empty($paymentId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pago requerido']);
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
        echo json_encode(['error' => 'MercadoPago no configurado']);
        exit();
    }
    
    // Query MercadoPago API for payment status
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
        echo json_encode(['error' => 'Error al consultar estado del pago']);
        exit();
    }
    
    $paymentData = json_decode($response, true);
    
    if (!$paymentData) {
        echo json_encode(['error' => 'Respuesta inválida de MercadoPago']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'status' => $paymentData['status'],
        'status_detail' => $paymentData['status_detail'] ?? '',
        'payment_method' => $paymentData['payment_method_id'] ?? ''
    ]);
    
} catch (Exception $e) {
    error_log("Check payment status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
}
?>