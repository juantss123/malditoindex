<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Require admin access for all operations
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetTransferProofs();
        break;
    case 'PUT':
        handleProcessTransferProof();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetTransferProofs() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                tp.*,
                CONCAT(up.first_name, ' ', up.last_name) as user_name,
                up.email,
                up.clinic_name,
                CONCAT(admin.first_name, ' ', admin.last_name) as processed_by_name
            FROM transfer_proofs tp
            LEFT JOIN user_profiles up ON tp.user_id = up.user_id
            LEFT JOIN user_profiles admin ON tp.processed_by = admin.user_id
            ORDER BY tp.created_at DESC
        ");
        $stmt->execute();
        $proofs = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'proofs' => $proofs]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar comprobantes: ' . $e->getMessage()]);
    }
}

function handleProcessTransferProof() {
    global $db;
    
    $proofId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($proofId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de comprobante requerido']);
        return;
    }
    
    $status = $input['status'] ?? '';
    $adminNotes = $input['admin_notes'] ?? '';
    
    if (!in_array($status, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado inválido']);
        return;
    }
    
    try {
        // Get transfer proof details
        $stmt = $db->prepare("SELECT user_id, plan_type FROM transfer_proofs WHERE id = ?");
        $stmt->execute([$proofId]);
        $proof = $stmt->fetch();
        
        if (!$proof) {
            http_response_code(404);
            echo json_encode(['error' => 'Comprobante no encontrado']);
            return;
        }
        
        // Update transfer proof status
        $stmt = $db->prepare("
            UPDATE transfer_proofs 
            SET status = ?, admin_notes = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $adminNotes, $_SESSION['user_id'], $proofId]);
        
        // If approved, update user subscription
        if ($status === 'approved') {
            $stmt = $db->prepare("
                UPDATE user_profiles 
                SET subscription_status = 'active', subscription_plan = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$proof['plan_type'], $proof['user_id']]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Comprobante procesado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al procesar comprobante: ' . $e->getMessage()]);
    }
}
?>