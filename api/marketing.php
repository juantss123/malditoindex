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

$database = new Database();
$db = $database->getConnection();

try {
    // Create marketing_settings table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS marketing_settings (
            id INT PRIMARY KEY DEFAULT 1,
            promotion_enabled BOOLEAN DEFAULT FALSE,
            promotion_text TEXT DEFAULT NULL,
            promotion_link VARCHAR(500) DEFAULT NULL,
            promotion_button_text VARCHAR(100) DEFAULT NULL,
            promotion_bg_color VARCHAR(7) DEFAULT '#dc3545',
            promotion_text_color VARCHAR(7) DEFAULT '#ffffff',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Get current settings
    $stmt = $db->prepare("SELECT * FROM marketing_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if (!$settings) {
        // Insert default settings
        $stmt = $db->prepare("
            INSERT INTO marketing_settings (id, promotion_enabled) VALUES (1, FALSE)
        ");
        $stmt->execute();
        
        // Get the default settings
        $stmt = $db->prepare("SELECT * FROM marketing_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener configuración: ' . $e->getMessage()
    ]);
}
?>