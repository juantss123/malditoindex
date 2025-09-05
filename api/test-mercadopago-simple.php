<?php
// Test simple de MercadoPago para identificar el problema exacto
session_start();
require_once '../config/database.php';

header('Content-Type: text/plain');

echo "=== TEST SIMPLE MERCADOPAGO ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get settings
    $stmt = $db->prepare("SELECT * FROM payment_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if (!$settings || !$settings['mercadopago_access_token']) {
        echo "ERROR: No hay Access Token configurado\n";
        exit();
    }
    
    echo "1. Access Token encontrado: " . substr($settings['mercadopago_access_token'], 0, 15) . "...\n";
    
    // Test 1: Verificar que el token funciona
    echo "\n2. Verificando token...\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/users/me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $settings['mercadopago_access_token']
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $userData = json_decode($response, true);
        echo "   ✓ Token válido - Usuario: " . ($userData['email'] ?? 'N/A') . "\n";
    } else {
        echo "   ✗ Token inválido - HTTP $httpCode\n";
        echo "   Respuesta: $response\n";
        exit();
    }
    
    // Test 2: Crear preferencia mínima
    echo "\n3. Creando preferencia mínima...\n";
    
    $minimalPreference = [
        'items' => [
            [
                'title' => 'Test DentexaPro',
                'quantity' => 1,
                'unit_price' => 100.00
            ]
        ]
    ];
    
    echo "   Enviando: " . json_encode($minimalPreference, JSON_PRETTY_PRINT) . "\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($minimalPreference),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['mercadopago_access_token']
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "   HTTP Code: $httpCode\n";
    
    if ($curlError) {
        echo "   ✗ Error cURL: $curlError\n";
    } elseif ($httpCode === 201) {
        echo "   ✓ Preferencia creada exitosamente!\n";
        $responseData = json_decode($response, true);
        echo "   ID: " . ($responseData['id'] ?? 'N/A') . "\n";
        echo "   Init Point: " . ($responseData['init_point'] ?? 'N/A') . "\n";
    } else {
        echo "   ✗ Error HTTP $httpCode\n";
        echo "   Respuesta completa:\n";
        echo "   " . $response . "\n";
        
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['cause'])) {
            echo "\n   Detalles del error:\n";
            foreach ($responseData['cause'] as $cause) {
                echo "   - " . ($cause['description'] ?? 'Sin descripción') . "\n";
            }
        }
    }
    
    echo "\n=== FIN TEST ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>