<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Create plans table if it doesn't exist
try {
    $db->exec("
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
    ");
    
    // Insert default plans if table is empty
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscription_plans");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        $defaultPlans = [
            [
                'plan_type' => 'start',
                'name' => 'Start',
                'price_monthly' => 1499900, // 14999.00 ARS stored as cents
                'price_yearly' => 999900,   // 9999.00 ARS stored as cents
                'features' => json_encode([
                    '1 profesional',
                    'Agenda & turnos',
                    'Historia clínica',
                    'Recordatorios'
                ])
            ],
            [
                'plan_type' => 'clinic',
                'name' => 'Clinic',
                'price_monthly' => 2499900, // 24999.00 ARS stored as cents
                'price_yearly' => 1999900,  // 19999.00 ARS stored as cents
                'features' => json_encode([
                    'Hasta 3 profesionales',
                    'Portal del paciente',
                    'Facturación',
                    'Reportes'
                ])
            ],
            [
                'plan_type' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly' => 4999900, // 49999.00 ARS stored as cents
                'price_yearly' => 3999900,  // 39999.00 ARS stored as cents
                'features' => json_encode([
                    '+4 profesionales',
                    'Integraciones',
                    'Soporte prioritario',
                    'Entrenamiento'
                ])
            ]
        ];
        
        foreach ($defaultPlans as $plan) {
            $stmt = $db->prepare("
                INSERT INTO subscription_plans (plan_type, name, price_monthly, price_yearly, features)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $plan['plan_type'],
                $plan['name'],
                $plan['price_monthly'],
                $plan['price_yearly'],
                $plan['features']
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error creating plans table: ' . $e->getMessage()]);
    exit();
}

switch ($method) {
    case 'GET':
        handleGetPlans();
        break;
    case 'PUT':
        handleUpdatePlan();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetPlans() {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM subscription_plans ORDER BY FIELD(plan_type, 'start', 'clinic', 'enterprise')");
        $stmt->execute();
        $plans = $stmt->fetchAll();
        
        // Decode JSON features
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true);
            // Ensure numeric values are properly formatted
            $plan['price_monthly'] = (float) $plan['price_monthly'];
            $plan['price_yearly'] = (float) $plan['price_yearly'];
        }
        
        echo json_encode(['success' => true, 'plans' => $plans]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar planes: ' . $e->getMessage()]);
    }
}

function handleUpdatePlan() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $planType = $_GET['plan'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($planType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de plan requerido']);
        return;
    }
    
    try {
        // Create price history table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS plan_price_history (
                id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
                old_price_monthly DECIMAL(10,2),
                new_price_monthly DECIMAL(10,2),
                old_price_yearly DECIMAL(10,2),
                new_price_yearly DECIMAL(10,2),
                changed_by VARCHAR(36) NOT NULL,
                change_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (changed_by) REFERENCES user_profiles(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Get current plan data for history
        $stmt = $db->prepare("SELECT price_monthly, price_yearly FROM subscription_plans WHERE plan_type = ?");
        $stmt->execute([$planType]);
        $currentPlan = $stmt->fetch();
        
        // Update plan
        $stmt = $db->prepare("
            UPDATE subscription_plans 
            SET name = ?, price_monthly = ?, price_yearly = ?, features = ?, updated_at = CURRENT_TIMESTAMP
            WHERE plan_type = ?
        ");
        
        $monthlyPrice = $input['price_monthly'];
        $yearlyPrice = $input['price_yearly'];
        
        echo error_log("Updating plan $planType with monthly: $monthlyPrice, yearly: $yearlyPrice");
        
        $stmt->execute([
            $input['name'],
            $monthlyPrice,
            $yearlyPrice,
            json_encode($input['features']),
            $planType
        ]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['error' => 'No se encontró el plan para actualizar']);
            return;
        }
        
        // Save price history if prices changed
        if ($currentPlan && 
            ($currentPlan['price_monthly'] != $monthlyPrice || 
             $currentPlan['price_yearly'] != $yearlyPrice)) {
            
            $stmt = $db->prepare("
                INSERT INTO plan_price_history 
                (plan_type, old_price_monthly, new_price_monthly, old_price_yearly, new_price_yearly, changed_by, change_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $planType,
                $currentPlan['price_monthly'],
                $monthlyPrice,
                $currentPlan['price_yearly'],
                $yearlyPrice,
                $_SESSION['user_id'],
                $input['change_reason'] ?? 'Actualización de precios'
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Plan actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar plan: ' . $e->getMessage()]);
    }
}
?>