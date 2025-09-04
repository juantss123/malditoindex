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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email es requerido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email exists
    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, email 
        FROM user_profiles 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode([
            'success' => true, 
            'message' => 'Si el email existe en nuestro sistema, recibirás las instrucciones de recuperación.'
        ]);
        exit();
    }
    
    // Generate password reset token
    $resetToken = bin2hex(random_bytes(32));
    $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Create password_resets table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_email (email),
            INDEX idx_expires (expires_at),
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert reset token
    $stmt = $db->prepare("
        INSERT INTO password_resets (user_id, email, token, expires_at) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['user_id'], $email, $resetToken, $resetExpiry]);
    
    // Send recovery email
    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $resetToken;
    $subject = "Recuperación de contraseña - DentexaPro";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2F96EE, #68c4ff); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #2F96EE; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Recuperación de contraseña</h1>
                <p>DentexaPro - Gestión para dentistas</p>
            </div>
            <div class='content'>
                <h2>Hola " . htmlspecialchars($user['first_name']) . ",</h2>
                
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en DentexaPro.</p>
                
                <p>Si fuiste vos quien solicitó este cambio, hace clic en el siguiente botón para crear una nueva contraseña:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $resetLink . "' class='button'>Restablecer mi contraseña</a>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Importante:</strong>
                    <ul>
                        <li>Este enlace es válido por <strong>1 hora</strong></li>
                        <li>Solo puede usarse una vez</li>
                        <li>Si no solicitaste este cambio, ignora este email</li>
                    </ul>
                </div>
                
                <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace;'>" . $resetLink . "</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                
                <p><strong>¿Necesitas ayuda?</strong></p>
                <p>Si tienes problemas para restablecer tu contraseña, contactanos:</p>
                <ul>
                    <li>📧 Email: soporte@dentexapro.com</li>
                    <li>📱 WhatsApp: +54 9 11 1234-5678</li>
                </ul>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " DentexaPro. Todos los derechos reservados.</p>
                <p>Este email fue enviado a " . htmlspecialchars($email) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: DentexaPro <recuperaciondecuenta@dentexapro.com>',
        'Reply-To: recuperaciondecuenta@dentexapro.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email
    $emailSent = mail($email, $subject, $message, implode("\r\n", $headers));
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Si el email existe en nuestro sistema, recibirás las instrucciones de recuperación en tu bandeja de entrada.'
        ]);
    } else {
        // Log error but don't reveal to user
        error_log("Failed to send password recovery email to: $email");
        echo json_encode([
            'success' => true,
            'message' => 'Si el email existe en nuestro sistema, recibirás las instrucciones de recuperación en tu bandeja de entrada.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Password recovery error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor. Por favor, intentá nuevamente más tarde.']);
}
?>