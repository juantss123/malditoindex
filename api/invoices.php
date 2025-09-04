<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

// Require admin access
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
        handleGetInvoices();
        break;
    case 'POST':
        handleCreateInvoice();
        break;
    case 'PUT':
        handleUpdateInvoice();
        break;
    case 'DELETE':
        handleDeleteInvoice();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetInvoices() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                i.*,
                CONCAT(up.first_name, ' ', up.last_name) as user_name,
                up.email,
                up.clinic_name
            FROM invoices i
            LEFT JOIN user_profiles up ON i.user_id = up.user_id
            ORDER BY i.created_at DESC
        ");
        $stmt->execute();
        $invoices = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'invoices' => $invoices]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar facturas: ' . $e->getMessage()]);
    }
}

function handleCreateInvoice() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['user_id', 'invoice_number', 'plan_type', 'amount', 'invoice_date'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        // Check if invoice number already exists
        $stmt = $db->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
        $stmt->execute([$input['invoice_number']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'El número de factura ya existe']);
            return;
        }
        
        // Calculate amounts
        $baseAmount = (float) $input['amount'];
        $taxAmount = $baseAmount * 0.21; // 21% IVA
        $totalAmount = $baseAmount + $taxAmount;
        
        // Set due date (30 days from invoice date)
        $invoiceDate = new DateTime($input['invoice_date']);
        $dueDate = clone $invoiceDate;
        $dueDate->add(new DateInterval('P30D'));
        
        // Create invoice
        $stmt = $db->prepare("
            INSERT INTO invoices (
                invoice_number, user_id, plan_type, amount, tax_amount, total_amount,
                invoice_date, due_date, status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)
        ");
        
        $stmt->execute([
            $input['invoice_number'],
            $input['user_id'],
            $input['plan_type'],
            $baseAmount,
            $taxAmount,
            $totalAmount,
            $input['invoice_date'],
            $dueDate->format('Y-m-d'),
            $input['notes'] ?? '',
            $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Factura creada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear factura: ' . $e->getMessage()]);
    }
}

function handleUpdateInvoice() {
    global $db;
    
    $invoiceId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($invoiceId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de factura requerido']);
        return;
    }
    
    try {
        $fields = [];
        $values = [];
        
        $allowedFields = ['status', 'payment_method', 'payment_date', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No hay campos para actualizar']);
            return;
        }
        
        $values[] = $invoiceId;
        
        $stmt = $db->prepare("UPDATE invoices SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        
        echo json_encode(['success' => true, 'message' => 'Factura actualizada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar factura: ' . $e->getMessage()]);
    }
}

function handleDeleteInvoice() {
    global $db;
    
    $invoiceId = $_GET['id'] ?? '';
    
    if (empty($invoiceId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de factura requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        
        echo json_encode(['success' => true, 'message' => 'Factura eliminada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar factura: ' . $e->getMessage()]);
    }
}
?>