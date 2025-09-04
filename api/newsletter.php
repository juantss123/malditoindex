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

$database = new Database();
$db = $database->getConnection();

// Create newsletter_subscribers table if it doesn't exist
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) DEFAULT NULL,
            status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
            source VARCHAR(100) DEFAULT 'blog',
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            confirmed_at TIMESTAMP NULL DEFAULT NULL,
            unsubscribed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_newsletter_email (email),
            INDEX idx_newsletter_status (status),
            INDEX idx_newsletter_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create newsletter_campaigns table
    $db->exec("
        CREATE TABLE IF NOT EXISTS newsletter_campaigns (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            subject VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            status ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft',
            scheduled_at TIMESTAMP NULL DEFAULT NULL,
            sent_at TIMESTAMP NULL DEFAULT NULL,
            total_recipients INT DEFAULT 0,
            total_sent INT DEFAULT 0,
            total_opened INT DEFAULT 0,
            total_clicked INT DEFAULT 0,
            created_by VARCHAR(36) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_newsletter_campaigns_status (status),
            INDEX idx_newsletter_campaigns_created (created_at),
            FOREIGN KEY (created_by) REFERENCES user_profiles(user_id) ON DELETE CASCADE
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
        $action = $_GET['action'] ?? 'subscribers';
        if ($action === 'subscribers') {
            handleGetSubscribers();
        } elseif ($action === 'campaigns') {
            handleGetCampaigns();
        } elseif ($action === 'stats') {
            handleGetStats();
        }
        break;
    case 'POST':
        $action = $_GET['action'] ?? 'subscribe';
        if ($action === 'subscribe') {
            handleSubscribe();
        } elseif ($action === 'campaign') {
            handleCreateCampaign();
        }
        break;
    case 'PUT':
        handleUpdateSubscriber();
        break;
    case 'DELETE':
        handleDeleteSubscriber();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetSubscribers() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM newsletter_subscribers 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $subscribers = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'subscribers' => $subscribers]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar suscriptores: ' . $e->getMessage()]);
    }
}

function handleGetCampaigns() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                nc.*,
                CONCAT(up.first_name, ' ', up.last_name) as created_by_name
            FROM newsletter_campaigns nc
            LEFT JOIN user_profiles up ON nc.created_by = up.user_id
            ORDER BY nc.created_at DESC
        ");
        $stmt->execute();
        $campaigns = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'campaigns' => $campaigns]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar campañas: ' . $e->getMessage()]);
    }
}

function handleGetStats() {
    global $db;
    
    try {
        // Get subscriber stats
        $stmt = $db->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
        $totalSubscribers = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as active FROM newsletter_subscribers WHERE status = 'active'");
        $activeSubscribers = $stmt->fetch()['active'];
        
        $stmt = $db->query("SELECT COUNT(*) as unsubscribed FROM newsletter_subscribers WHERE status = 'unsubscribed'");
        $unsubscribedCount = $stmt->fetch()['unsubscribed'];
        
        // Get campaign stats
        $stmt = $db->query("SELECT COUNT(*) as total FROM newsletter_campaigns");
        $totalCampaigns = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as sent FROM newsletter_campaigns WHERE status = 'sent'");
        $sentCampaigns = $stmt->fetch()['sent'];
        
        // Get growth stats (last 30 days)
        $stmt = $db->query("
            SELECT COUNT(*) as new_subscribers 
            FROM newsletter_subscribers 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $newSubscribers = $stmt->fetch()['new_subscribers'];
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_subscribers' => $totalSubscribers,
                'active_subscribers' => $activeSubscribers,
                'unsubscribed_count' => $unsubscribedCount,
                'total_campaigns' => $totalCampaigns,
                'sent_campaigns' => $sentCampaigns,
                'new_subscribers' => $newSubscribers
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar estadísticas: ' . $e->getMessage()]);
    }
}

function handleSubscribe() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $name = $input['name'] ?? '';
    $source = $input['source'] ?? 'blog';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email es requerido']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email inválido']);
        return;
    }
    
    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                echo json_encode(['success' => true, 'message' => 'Ya estás suscripto a nuestro newsletter']);
                return;
            } else {
                // Reactivate subscription
                $stmt = $db->prepare("
                    UPDATE newsletter_subscribers 
                    SET status = 'active', confirmed_at = NOW(), unsubscribed_at = NULL
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                echo json_encode(['success' => true, 'message' => '¡Suscripción reactivada exitosamente!']);
                return;
            }
        }
        
        // Create new subscription
        $stmt = $db->prepare("
            INSERT INTO newsletter_subscribers (
                email, name, source, ip_address, user_agent, confirmed_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $email,
            $name,
            $source,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => '¡Gracias! Te has suscripto exitosamente al newsletter.']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al suscribir: ' . $e->getMessage()]);
    }
}

function handleCreateCampaign() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['subject', 'content'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        // Count active subscribers
        $stmt = $db->query("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE status = 'active'");
        $totalRecipients = $stmt->fetch()['count'];
        
        // Create campaign
        $stmt = $db->prepare("
            INSERT INTO newsletter_campaigns (
                subject, content, status, total_recipients, created_by
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['subject'],
            $input['content'],
            $input['status'] ?? 'draft',
            $totalRecipients,
            $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Campaña creada exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear campaña: ' . $e->getMessage()]);
    }
}

function handleUpdateSubscriber() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $subscriberId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($subscriberId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de suscriptor requerido']);
        return;
    }
    
    try {
        $fields = [];
        $values = [];
        
        $allowedFields = ['status', 'name'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        // Set unsubscribed_at if status is unsubscribed
        if (isset($input['status']) && $input['status'] === 'unsubscribed') {
            $fields[] = "unsubscribed_at = NOW()";
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No hay campos para actualizar']);
            return;
        }
        
        $values[] = $subscriberId;
        
        $stmt = $db->prepare("UPDATE newsletter_subscribers SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        
        echo json_encode(['success' => true, 'message' => 'Suscriptor actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar suscriptor: ' . $e->getMessage()]);
    }
}

function handleDeleteSubscriber() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $subscriberId = $_GET['id'] ?? '';
    
    if (empty($subscriberId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de suscriptor requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$subscriberId]);
        
        echo json_encode(['success' => true, 'message' => 'Suscriptor eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar suscriptor: ' . $e->getMessage()]);
    }
}
?>