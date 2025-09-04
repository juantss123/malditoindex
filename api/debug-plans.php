<?php
// Debug script para identificar problemas con la API de planes
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== DEBUG API PLANES ===\n";

// 1. Test básico PHP
echo "1. PHP funcionando: OK\n";

// 2. Test de archivo database.php
if (file_exists('../config/database.php')) {
    echo "2. Archivo database.php: EXISTE\n";
    try {
        require_once '../config/database.php';
        echo "   - Incluido correctamente: OK\n";
        
        // Test de conexión
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            echo "   - Conexión DB: OK\n";
            echo "   - Base de datos: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n";
        } else {
            echo "   - Conexión DB: FALLO\n";
        }
    } catch (Exception $e) {
        echo "   - Error al incluir: " . $e->getMessage() . "\n";
    }
} else {
    echo "2. Archivo database.php: NO EXISTE\n";
}

// 3. Test de tabla subscription_plans
try {
    if (isset($db) && $db) {
        echo "3. Verificando tabla subscription_plans...\n";
        
        $stmt = $db->query("SHOW TABLES LIKE 'subscription_plans'");
        if ($stmt->rowCount() > 0) {
            echo "   - Tabla subscription_plans: EXISTE\n";
            
            // Verificar estructura
            $stmt = $db->query("DESCRIBE subscription_plans");
            $columns = $stmt->fetchAll();
            echo "   - Columnas:\n";
            foreach ($columns as $column) {
                echo "     * {$column['Field']} ({$column['Type']})\n";
            }
            
            // Verificar datos
            $stmt = $db->query("SELECT COUNT(*) as count FROM subscription_plans");
            $count = $stmt->fetch()['count'];
            echo "   - Registros: $count\n";
            
            if ($count > 0) {
                $stmt = $db->query("SELECT plan_type, name, price_monthly, price_yearly FROM subscription_plans ORDER BY FIELD(plan_type, 'start', 'clinic', 'enterprise')");
                $plans = $stmt->fetchAll();
                echo "   - Datos:\n";
                foreach ($plans as $plan) {
                    echo "     * {$plan['plan_type']}: {$plan['name']} - \${$plan['price_monthly']}/mes\n";
                }
            }
        } else {
            echo "   - Tabla subscription_plans: NO EXISTE\n";
            echo "   - Intentando crear tabla...\n";
            
            $createSQL = "
                CREATE TABLE IF NOT EXISTS subscription_plans (
                    id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                    plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    price_monthly DECIMAL(10,2) NOT NULL,
                    price_yearly DECIMAL(10,2) NOT NULL,
                    features JSON NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($createSQL);
            echo "   - Tabla creada: OK\n";
            
            // Insertar datos por defecto
            $defaultPlans = [
                ['start', 'Start', 14999.00, 9999.00, '["1 profesional","Agenda & turnos","Historia clínica","Recordatorios"]'],
                ['clinic', 'Clinic', 24999.00, 19999.00, '["Hasta 3 profesionales","Portal del paciente","Facturación","Reportes avanzados"]'],
                ['enterprise', 'Enterprise', 49999.00, 39999.00, '["Profesionales ilimitados","Integraciones","Soporte prioritario","Entrenamiento"]']
            ];
            
            foreach ($defaultPlans as $plan) {
                $stmt = $db->prepare("INSERT INTO subscription_plans (plan_type, name, price_monthly, price_yearly, features) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute($plan);
            }
            echo "   - Datos por defecto insertados: OK\n";
        }
    }
} catch (Exception $e) {
    echo "3. Error verificando tabla: " . $e->getMessage() . "\n";
}

// 4. Test de API directa
echo "\n4. Test de API directa...\n";
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    include 'plans.php';
    $output = ob_get_clean();
    echo "   - Respuesta API: " . substr($output, 0, 200) . "...\n";
} catch (Exception $e) {
    echo "   - Error en API: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>