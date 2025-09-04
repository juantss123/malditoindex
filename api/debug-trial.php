<?php
// Archivo de debug para identificar el problema
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DEBUG TRIAL REQUESTS ===\n";

// 1. Test básico PHP
echo "1. PHP funcionando: OK\n";

// 2. Test de sesión
session_start();
echo "2. Sesión iniciada: OK\n";
echo "   - User ID: " . ($_SESSION['user_id'] ?? 'NO SET') . "\n";
echo "   - User Role: " . ($_SESSION['user_role'] ?? 'NO SET') . "\n";

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

// 4. Test de tabla trial_requests
try {
    if (isset($db) && $db) {
        $stmt = $db->query("SHOW TABLES LIKE 'trial_requests'");
        if ($stmt->rowCount() > 0) {
            echo "4. Tabla trial_requests: EXISTE\n";
        } else {
            echo "4. Tabla trial_requests: NO EXISTE\n";
        }
    }
} catch (Exception $e) {
    echo "4. Error verificando tabla: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===";
?>