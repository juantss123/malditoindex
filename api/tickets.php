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

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Create tickets table if it doesn't exist
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            ticket_number VARCHAR(20) NOT NULL UNIQUE,
            user_id VARCHAR(36) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category ENUM('technical', 'billing', 'feature', 'bug', 'general') DEFAULT 'general',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('open', 'in_progress', 'waiting_user', 'resolved', 'closed') DEFAULT 'open',
            assigned_to VARCHAR(36) DEFAULT NULL,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tickets_user_id (user_id),
            INDEX idx_tickets_status (status),
            INDEX idx_tickets_priority (priority),
            INDEX idx_tickets_assigned (assigned_to),
            INDEX idx_tickets_created (created_at),
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES user_profiles(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create ticket responses table
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_responses (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            ticket_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            message TEXT NOT NULL,
            is_internal BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_responses_ticket_id (ticket_id),
            INDEX idx_responses_user_id (user_id),
            INDEX idx_responses_created (created_at),
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error configurando tablas: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetTickets();
        break;
    case 'POST':
        handleCreateTicket();
        break;
    case 'PUT':
        handleUpdateTicket();
        break;
    case 'DELETE':
        handleDeleteTicket();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetTickets() {
    global $db;
    
    $ticketId = $_GET['id'] ?? '';
    $userId = $_GET['user_id'] ?? '';
    
    try {
        if ($ticketId) {
            // Get specific ticket with responses
            $stmt = $db->prepare("
                SELECT 
                    st.*,
                    CONCAT(up.first_name, ' ', up.last_name) as user_name,
                    up.email,
                    up.clinic_name,
                    CONCAT(admin.first_name, ' ', admin.last_name) as assigned_to_name
                FROM support_tickets st
                LEFT JOIN user_profiles up ON st.user_id = up.user_id
                LEFT JOIN user_profiles admin ON st.assigned_to = admin.user_id
                WHERE st.id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket no encontrado']);
                return;
            }
            
            // Get responses
            $stmt = $db->prepare("
                SELECT 
                    tr.*,
                    CONCAT(up.first_name, ' ', up.last_name) as user_name,
                    up.role
                FROM ticket_responses tr
                LEFT JOIN user_profiles up ON tr.user_id = up.user_id
                WHERE tr.ticket_id = ?
                ORDER BY tr.created_at ASC
            ");
            $stmt->execute([$ticketId]);
            $responses = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'ticket' => $ticket, 'responses' => $responses]);
            
        } else {
            // Get tickets list
            $whereClause = '';
            $params = [];
            
            // If not admin, only show user's own tickets
            if (!isAdmin()) {
                $whereClause = 'WHERE st.user_id = ?';
                $params[] = $_SESSION['user_id'];
            } elseif ($userId) {
                $whereClause = 'WHERE st.user_id = ?';
                $params[] = $userId;
            }
            
            $stmt = $db->prepare("
                SELECT 
                    st.*,
                    CONCAT(up.first_name, ' ', up.last_name) as user_name,
                    up.email,
                    up.clinic_name,
                    CONCAT(admin.first_name, ' ', admin.last_name) as assigned_to_name,
                    (SELECT COUNT(*) FROM ticket_responses WHERE ticket_id = st.id) as response_count
                FROM support_tickets st
                LEFT JOIN user_profiles up ON st.user_id = up.user_id
                LEFT JOIN user_profiles admin ON st.assigned_to = admin.user_id
                $whereClause
                ORDER BY st.created_at DESC
            ");
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'tickets' => $tickets]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar tickets: ' . $e->getMessage()]);
    }
}

function handleCreateTicket() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['subject', 'description', 'category'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        // Generate ticket number
        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if ticket number exists (very unlikely but just in case)
        $stmt = $db->prepare("SELECT id FROM support_tickets WHERE ticket_number = ?");
        $stmt->execute([$ticketNumber]);
        if ($stmt->fetch()) {
            $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Create ticket
        $stmt = $db->prepare("
            INSERT INTO support_tickets (
                ticket_number, user_id, subject, description, category, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'open')
        ");
        
        $stmt->execute([
            $ticketNumber,
            $_SESSION['user_id'],
            $input['subject'],
            $input['description'],
            $input['category'],
            $input['priority'] ?? 'medium'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ticket creado exitosamente',
            'ticket_number' => $ticketNumber
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear ticket: ' . $e->getMessage()]);
    }
}

function handleUpdateTicket() {
    global $db;
    
    $ticketId = $_GET['id'] ?? '';
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($ticketId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de ticket requerido']);
        return;
    }
    
    try {
        if ($action === 'add_response') {
            // Add response to ticket
            $message = $input['message'] ?? '';
            $isInternal = $input['is_internal'] ?? false;
            
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Mensaje requerido']);
                return;
            }
            
            $stmt = $db->prepare("
                INSERT INTO ticket_responses (ticket_id, user_id, message, is_internal)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$ticketId, $_SESSION['user_id'], $message, $isInternal]);
            
            // Update ticket status if needed
            if (isset($input['new_status'])) {
                $stmt = $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
                $stmt->execute([$input['new_status'], $ticketId]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Respuesta agregada exitosamente']);
            
        } else {
            // Update ticket properties
            $fields = [];
            $values = [];
            
            $allowedFields = ['status', 'priority', 'assigned_to', 'category'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            // Set resolved_at if status is resolved
            if (isset($input['status']) && $input['status'] === 'resolved') {
                $fields[] = "resolved_at = NOW()";
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No hay campos para actualizar']);
                return;
            }
            
            $values[] = $ticketId;
            
            $stmt = $db->prepare("UPDATE support_tickets SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($values);
            
            echo json_encode(['success' => true, 'message' => 'Ticket actualizado exitosamente']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar ticket: ' . $e->getMessage()]);
    }
}

function handleDeleteTicket() {
    global $db;
    
    // Only admins can delete tickets
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Solo administradores pueden eliminar tickets']);
        return;
    }
    
    $ticketId = $_GET['id'] ?? '';
    
    if (empty($ticketId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de ticket requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM support_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        
        echo json_encode(['success' => true, 'message' => 'Ticket eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar ticket: ' . $e->getMessage()]);
    }
}
?>