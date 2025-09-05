<?php
// Script de debug para MercadoPago
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== DEBUG MERCADOPAGO ===\n";

// 1. Test básico PHP
echo "1. PHP funcionando: OK\n";

// 2. Test de cURL
if (function_exists('curl_init')) {
    echo "2. cURL disponible: OK\n";
    
    // Test de conexión básica
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "   - Test de conexión: FALLO - " . $curlError . "\n";
    } else {
        echo "   - Test de conexión: OK (HTTP $httpCode)\n";
    }
} else {
    echo "2. cURL disponible: NO - Extensión no instalada\n";
}

// 3. Test de archivo database.php
if (file_exists('../config/database.php')) {
    echo "3. Archivo database.php: EXISTE\n";
    try {
        require_once '../config/database.php';
        echo "   - Incluido correctamente: OK\n";
        
        // Test de conexión
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            echo "   - Conexión DB: OK\n";
        } else {
            echo "   - Conexión DB: FALLO\n";
        }
    } catch (Exception $e) {
        echo "   - Error al incluir: " . $e->getMessage() . "\n";
    }
} else {
    echo "3. Archivo database.php: NO EXISTE\n";
}

// 4. Test de configuración MercadoPago
try {
    if (isset($db) && $db) {
        echo "4. Verificando configuración MercadoPago...\n";
        
        $stmt = $db->query("SHOW TABLES LIKE 'payment_settings'");
        if ($stmt->rowCount() > 0) {
            echo "   - Tabla payment_settings: EXISTE\n";
            
            $stmt = $db->query("SELECT * FROM payment_settings WHERE id = 1");
            $settings = $stmt->fetch();
            
            if ($settings) {
                echo "   - Configuración encontrada: OK\n";
                echo "   - MercadoPago habilitado: " . ($settings['mercadopago_enabled'] ? 'SÍ' : 'NO') . "\n";
                echo "   - Access Token configurado: " . (!empty($settings['mercadopago_access_token']) ? 'SÍ' : 'NO') . "\n";
                echo "   - Public Key configurado: " . (!empty($settings['mercadopago_public_key']) ? 'SÍ' : 'NO') . "\n";
                
                if (!empty($settings['mercadopago_access_token'])) {
                    echo "   - Access Token (primeros 10 chars): " . substr($settings['mercadopago_access_token'], 0, 10) . "...\n";
                    
                    // Test de validez del token
                    echo "5. Test de validez del Access Token...\n";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/users/me');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $settings['mercadopago_access_token']
                    ]);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curlError) {
                        echo "   - Test de token: FALLO - " . $curlError . "\n";
                    } elseif ($httpCode === 200) {
                        echo "   - Test de token: OK - Token válido\n";
                        $userData = json_decode($response, true);
                        if ($userData && isset($userData['email'])) {
                            echo "   - Cuenta MercadoPago: " . $userData['email'] . "\n";
                        }
                    } else {
                        echo "   - Test de token: FALLO - HTTP $httpCode\n";
                        echo "   - Respuesta: " . $response . "\n";
                    }
                }
            } else {
                echo "   - Configuración: NO ENCONTRADA\n";
            }
        } else {
            echo "   - Tabla payment_settings: NO EXISTE\n";
        }
    }
} catch (Exception $e) {
    echo "4. Error verificando MercadoPago: " . $e->getMessage() . "\n";
}

// 6. Test de URLs
echo "\n6. Test de URLs de retorno...\n";
$baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
echo "   - Base URL: $baseUrl\n";
echo "   - URL de éxito: {$baseUrl}/pago-exitoso.php\n";
echo "   - URL de fallo: {$baseUrl}/pago-fallido.php\n";
echo "   - URL pendiente: {$baseUrl}/pago-pendiente.php\n";

echo "\n=== FIN DEBUG ===\n";
echo "\nSi ves errores arriba, esos son los problemas a solucionar.\n";
echo "Si todo está OK, el problema puede ser de firewall o configuración del servidor.\n";
?>