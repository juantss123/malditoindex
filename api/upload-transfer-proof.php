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
    
    // Create transfer_proofs table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS transfer_proofs (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_size INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            processed_by VARCHAR(36) DEFAULT NULL,
            processed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES user_profiles(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Get form data
    $planType = $_POST['plan_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    
    if (empty($planType) || empty($amount)) {
        http_response_code(400);
        echo json_encode(['error' => 'Plan y monto son requeridos']);
        exit();
    }
    
    // Handle file upload
    if (!isset($_FILES['transfer_proof']) || $_FILES['transfer_proof']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Error al subir el archivo']);
        exit();
    }
    
    $file = $_FILES['transfer_proof'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de archivo no permitido. Solo JPG, PNG y PDF']);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'El archivo es demasiado grande. Máximo 5MB']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = '../uploads/transfer_proofs';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'transfer_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadsDir . '/' . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo']);
        exit();
    }
    
    // Save to database
    $stmt = $db->prepare("
        INSERT INTO transfer_proofs (
            user_id, plan_type, amount, file_name, file_path, file_type, file_size, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $planType,
        $amount,
        $file['name'],
        $filePath,
        $file['type'],
        $file['size']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comprobante enviado exitosamente. Verificaremos tu transferencia y activaremos tu plan dentro de 24-48 horas.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>