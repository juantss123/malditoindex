<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle password change
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errorMessage = 'Todos los campos de contraseña son requeridos';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'Las contraseñas nuevas no coinciden';
            } elseif (strlen($newPassword) < 8) {
                $errorMessage = 'La nueva contraseña debe tener al menos 8 caracteres';
            } else {
                // Verify current password
                $stmt = $db->prepare("SELECT password_hash FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
                    $errorMessage = 'La contraseña actual es incorrecta';
                } else {
                    // Update password
                    $newPasswordHash = hashPassword($newPassword);
                    $stmt = $db->prepare("UPDATE user_profiles SET password_hash = ? WHERE user_id = ?");
                    $stmt->execute([$newPasswordHash, $_SESSION['user_id']]);
                    
                    $successMessage = 'Contraseña actualizada exitosamente';
                }
            }
        } catch (Exception $e) {
            $errorMessage = 'Error al cambiar contraseña: ' . $e->getMessage();
        }
    } else {
        // Handle payment settings
        try {
            // Create payment_settings table if it doesn't exist
            $db->exec("
                CREATE TABLE IF NOT EXISTS payment_settings (
                    id INT PRIMARY KEY DEFAULT 1,
                    mercadopago_enabled BOOLEAN DEFAULT FALSE,
                    mercadopago_access_token TEXT DEFAULT NULL,
                    mercadopago_public_key TEXT DEFAULT NULL,
                    bank_transfer_enabled BOOLEAN DEFAULT FALSE,
                    bank_name VARCHAR(255) DEFAULT NULL,
                    account_holder VARCHAR(255) DEFAULT NULL,
                    cbu_cvu VARCHAR(22) DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Update or insert settings
            $stmt = $db->prepare("
                INSERT INTO payment_settings (
                    id, mercadopago_enabled, mercadopago_access_token, mercadopago_public_key,
                    bank_transfer_enabled, bank_name, account_holder, cbu_cvu
                ) VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    mercadopago_enabled = VALUES(mercadopago_enabled),
                    mercadopago_access_token = VALUES(mercadopago_access_token),
                    mercadopago_public_key = VALUES(mercadopago_public_key),
                    bank_transfer_enabled = VALUES(bank_transfer_enabled),
                    bank_name = VALUES(bank_name),
                    account_holder = VALUES(account_holder),
                    cbu_cvu = VALUES(cbu_cvu),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                isset($_POST['mercadopago_enabled']) ? 1 : 0,
                $_POST['mercadopago_access_token'] ?? null,
                $_POST['mercadopago_public_key'] ?? null,
                isset($_POST['bank_transfer_enabled']) ? 1 : 0,
                $_POST['bank_name'] ?? null,
                $_POST['account_holder'] ?? null,
                $_POST['cbu_cvu'] ?? null
            ]);
            
            $successMessage = 'Configuración de pagos guardada exitosamente';
            
        } catch (Exception $e) {
            $errorMessage = 'Error al guardar configuración: ' . $e->getMessage();
        }
    }
}

// Get current settings
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM payment_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
} catch (Exception $e) {
    $settings = null;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Configuración - DentexaPro Admin</title>
  <meta name="description" content="Configuración del panel de administración de DentexaPro">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- AOS (Animate On Scroll) -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <!-- App styles -->
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark-ink text-body min-vh-100">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="admin.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
        <span class="badge bg-danger ms-2">Admin</span>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-outline-light">
          <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
        </a>
      </div>
    </div>
  </nav>

  <!-- Admin Configuration -->
  <main class="section-pt pb-5">
    <div class="container-fluid">
      <div class="row">
        <?php include 'includes/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
          <!-- Header -->
          <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="800">
            <div>
              <h1 class="text-white mb-1">
                <i class="bi bi-gear me-2"></i>Configuración del Sistema
              </h1>
              <p class="text-light opacity-75 mb-0">Administra todas las configuraciones de DentexaPro</p>
            </div>
          </div>

          <!-- Success/Error Messages -->
          <?php if (isset($successMessage)): ?>
          <div class="alert alert-success alert-dismissible fade show glass-card mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <?php if (isset($errorMessage)): ?>
          <div class="alert alert-danger alert-dismissible fade show glass-card mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <!-- Configuration Tabs -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-4" id="configTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="payments-tab" data-bs-toggle="pill" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="true">
                  <i class="bi bi-credit-card me-2"></i>Métodos de Pago
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                  <i class="bi bi-shield-lock me-2"></i>Seguridad
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">
                  <i class="bi bi-bell me-2"></i>Notificaciones
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="system-tab" data-bs-toggle="pill" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                  <i class="bi bi-server me-2"></i>Sistema
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                  <i class="bi bi-tools me-2"></i>Mantenimiento
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                  <i class="bi bi-tools me-2"></i>Mantenimiento
                </button>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="configTabsContent">
              
              <!-- Payments Tab -->
              <div class="tab-pane fade show active" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                <form method="POST" class="row g-4">
                  <input type="hidden" name="action" value="payment_settings">
                  
                  <!-- MercadoPago Configuration -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="text-white mb-0">
                          <i class="bi bi-credit-card me-2"></i>MercadoPago
                        </h4>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="mercadopago_enabled" name="mercadopago_enabled" 
                                 <?php echo ($settings && $settings['mercadopago_enabled']) ? 'checked' : ''; ?>>
                          <label class="form-check-label text-light" for="mercadopago_enabled">
                            Habilitar MercadoPago
                          </label>
                        </div>
                      </div>

                      <div id="mercadopagoFields" style="<?php echo ($settings && $settings['mercadopago_enabled']) ? '' : 'display: none;'; ?>">
                        <div class="row g-3">
                          <div class="col-12">
                            <label class="form-label text-light">Access Token *</label>
                            <input type="text" name="mercadopago_access_token" class="form-control glass-input" 
                                   placeholder="APP_USR-..." 
                                   value="<?php echo htmlspecialchars($settings['mercadopago_access_token'] ?? ''); ?>">
                            <small class="text-light opacity-75">Token de acceso de tu cuenta de MercadoPago</small>
                          </div>
                          <div class="col-12">
                            <label class="form-label text-light">Public Key *</label>
                            <input type="text" name="mercadopago_public_key" class="form-control glass-input" 
                                   placeholder="APP_USR-..." 
                                   value="<?php echo htmlspecialchars($settings['mercadopago_public_key'] ?? ''); ?>">
                            <small class="text-light opacity-75">Clave pública de tu cuenta de MercadoPago</small>
                          </div>
                        </div>
                        
                        <div class="mt-3 p-3 glass-card">
                          <h6 class="text-info mb-2">
                            <i class="bi bi-info-circle me-2"></i>¿Cómo obtener las credenciales?
                          </h6>
                          <ol class="text-light opacity-85 small mb-0">
                            <li>Ingresa a tu <a href="https://www.mercadopago.com.ar/developers" target="_blank" class="text-primary">panel de desarrolladores de MercadoPago</a></li>
                            <li>Ve a "Credenciales" en el menú lateral</li>
                            <li>Copia el "Access Token" y "Public Key" de producción</li>
                            <li>Pega las credenciales en los campos de arriba</li>
                          </ol>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Bank Transfer Configuration -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <div class="d-flex align-items-center justify-content-between mb-4">
                        <h4 class="text-white mb-0">
                          <i class="bi bi-bank me-2"></i>Transferencia Bancaria
                        </h4>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="bank_transfer_enabled" name="bank_transfer_enabled"
                                 <?php echo ($settings && $settings['bank_transfer_enabled']) ? 'checked' : ''; ?>>
                          <label class="form-check-label text-light" for="bank_transfer_enabled">
                            Habilitar transferencias
                          </label>
                        </div>
                      </div>

                      <div id="bankTransferFields" style="<?php echo ($settings && $settings['bank_transfer_enabled']) ? '' : 'display: none;'; ?>">
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label text-light">Nombre del banco *</label>
                            <input type="text" name="bank_name" class="form-control glass-input" 
                                   placeholder="Ej: Banco Galicia" 
                                   value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label text-light">Titular de la cuenta *</label>
                            <input type="text" name="account_holder" class="form-control glass-input" 
                                   placeholder="Nombre del titular" 
                                   value="<?php echo htmlspecialchars($settings['account_holder'] ?? ''); ?>">
                          </div>
                          <div class="col-12">
                            <label class="form-label text-light">CBU/CVU *</label>
                            <input type="text" name="cbu_cvu" class="form-control glass-input" 
                                   placeholder="0000003100010000000001" maxlength="22"
                                   value="<?php echo htmlspecialchars($settings['cbu_cvu'] ?? ''); ?>">
                            <small class="text-light opacity-75">CBU de 22 dígitos o CVU de tu cuenta bancaria</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Payment Status -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-activity me-2"></i>Estado actual de los métodos de pago
                      </h4>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="d-flex align-items-center p-3 glass-card">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                              <i class="bi bi-credit-card text-white"></i>
                            </div>
                            <div>
                              <div class="text-white fw-bold">MercadoPago</div>
                              <span class="badge <?php echo ($settings && $settings['mercadopago_enabled']) ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ($settings && $settings['mercadopago_enabled']) ? 'Habilitado' : 'Deshabilitado'; ?>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="d-flex align-items-center p-3 glass-card">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                              <i class="bi bi-bank text-white"></i>
                            </div>
                            <div>
                              <div class="text-white fw-bold">Transferencia Bancaria</div>
                              <span class="badge <?php echo ($settings && $settings['bank_transfer_enabled']) ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ($settings && $settings['bank_transfer_enabled']) ? 'Habilitado' : 'Deshabilitado'; ?>
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Save Button -->
                  <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                      <i class="bi bi-check-lg me-2"></i>Guardar configuración de pagos
                    </button>
                  </div>
                </form>
              </div>

              <!-- Security Tab -->
              <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="row g-4">
                  <!-- Change Password -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-shield-lock me-2"></i>Cambiar contraseña
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Actualiza tu contraseña de administrador para mantener la seguridad de tu cuenta.
                      </p>
                      
                      <form method="POST" class="row g-3" style="max-width: 600px;">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="col-12">
                          <label class="form-label text-light">Contraseña actual *</label>
                          <div class="position-relative">
                            <input type="password" name="current_password" id="currentPassword" class="form-control glass-input" required>
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" onclick="togglePassword('currentPassword', 'currentPasswordIcon')" style="z-index: 10;">
                              <i class="bi bi-eye" id="currentPasswordIcon"></i>
                            </button>
                          </div>
                        </div>
                        
                        <div class="col-md-6">
                          <label class="form-label text-light">Nueva contraseña *</label>
                          <div class="position-relative">
                            <input type="password" name="new_password" id="newPassword" class="form-control glass-input" required minlength="8">
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" onclick="togglePassword('newPassword', 'newPasswordIcon')" style="z-index: 10;">
                              <i class="bi bi-eye" id="newPasswordIcon"></i>
                            </button>
                          </div>
                          <small class="text-light opacity-75">Mínimo 8 caracteres</small>
                        </div>
                        
                        <div class="col-md-6">
                          <label class="form-label text-light">Confirmar nueva contraseña *</label>
                          <div class="position-relative">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control glass-input" required minlength="8">
                            <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" onclick="togglePassword('confirmPassword', 'confirmPasswordIcon')" style="z-index: 10;">
                              <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                            </button>
                          </div>
                        </div>
                        
                        <div class="col-12">
                          <button type="submit" class="btn btn-warning">
                            <i class="bi bi-shield-check me-2"></i>Cambiar contraseña
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>

                  <!-- Two Factor Authentication -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-phone me-2"></i>Autenticación de dos factores
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Agrega una capa extra de seguridad a tu cuenta de administrador.
                      </p>
                      
                      <div class="d-flex align-items-center justify-content-between">
                        <div>
                          <div class="text-white fw-bold">Estado actual</div>
                          <span class="badge bg-secondary">Deshabilitado</span>
                        </div>
                        <button class="btn btn-outline-primary" disabled>
                          <i class="bi bi-plus-lg me-2"></i>Configurar 2FA
                        </button>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Función disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Login Notifications -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-bell-fill me-2"></i>Notificaciones de acceso
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Recibe alertas cuando alguien acceda a tu cuenta de administrador.
                      </p>
                      
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="loginNotifications" checked disabled>
                        <label class="form-check-label text-light" for="loginNotifications">
                          Notificar inicios de sesión por email
                        </label>
                      </div>
                      
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="suspiciousActivity" checked disabled>
                        <label class="form-check-label text-light" for="suspiciousActivity">
                          Alertas de actividad sospechosa
                        </label>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Configuración avanzada disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notifications Tab -->
              <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                <div class="row g-4">
                  <!-- Email Configuration -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-envelope me-2"></i>Configuración de Email
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Configura el servidor SMTP para envío de emails automáticos.
                      </p>
                      
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label text-light">Servidor SMTP</label>
                          <input type="text" class="form-control glass-input" placeholder="smtp.gmail.com" disabled>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label text-light">Puerto</label>
                          <input type="number" class="form-control glass-input" placeholder="587" disabled>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label text-light">Usuario SMTP</label>
                          <input type="email" class="form-control glass-input" placeholder="tu@email.com" disabled>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label text-light">Contraseña SMTP</label>
                          <input type="password" class="form-control glass-input" placeholder="••••••••" disabled>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Configuración SMTP disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- WhatsApp Configuration -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-whatsapp me-2"></i>Integración WhatsApp
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Conecta WhatsApp Business API para recordatorios automáticos.
                      </p>
                      
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="whatsappEnabled" disabled>
                        <label class="form-check-label text-light" for="whatsappEnabled">
                          Habilitar WhatsApp Business
                        </label>
                      </div>
                      
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label text-light">Token de acceso</label>
                          <input type="text" class="form-control glass-input" placeholder="EAAG..." disabled>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label text-light">Número de teléfono</label>
                          <input type="tel" class="form-control glass-input" placeholder="+54 9 11..." disabled>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Integración WhatsApp Business disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Email Templates -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-file-text me-2"></i>Plantillas de Email
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Personaliza los mensajes automáticos que se envían a los usuarios.
                      </p>
                      
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-envelope-check text-success fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Bienvenida</div>
                            <small class="text-light opacity-75">Email de registro</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-light" disabled>Editar</button>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-clock text-warning fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Recordatorios</div>
                            <small class="text-light opacity-75">Turnos próximos</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-light" disabled>Editar</button>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-credit-card text-info fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Facturación</div>
                            <small class="text-light opacity-75">Pagos y facturas</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-light" disabled>Editar</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Editor de plantillas disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- System Tab -->
              <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                <div class="row g-4">
                  <!-- System Information -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-info-circle me-2"></i>Información del Sistema
                      </h4>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center mb-2">
                              <i class="bi bi-code-square text-primary me-2"></i>
                              <strong class="text-white">Versión</strong>
                            </div>
                            <div class="text-light">DentexaPro v2.1.0</div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center mb-2">
                              <i class="bi bi-database text-info me-2"></i>
                              <strong class="text-white">Base de Datos</strong>
                            </div>
                            <div class="text-light">MySQL <?php echo mysqli_get_server_info(mysqli_connect('localhost', 'root', '', 'dentexaproaaa')) ?? '8.0'; ?></div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center mb-2">
                              <i class="bi bi-server text-warning me-2"></i>
                              <strong class="text-white">PHP</strong>
                            </div>
                            <div class="text-light">PHP <?php echo phpversion(); ?></div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center mb-2">
                              <i class="bi bi-calendar text-success me-2"></i>
                              <strong class="text-white">Última actualización</strong>
                            </div>
                            <div class="text-light"><?php echo date('d/m/Y H:i'); ?></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Backup Configuration -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-cloud-arrow-up me-2"></i>Respaldos Automáticos
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Configura la frecuencia y destino de los respaldos automáticos.
                      </p>
                      
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label text-light">Frecuencia de respaldo</label>
                          <select class="form-select glass-input" disabled>
                            <option>Diario (recomendado)</option>
                            <option>Semanal</option>
                            <option>Mensual</option>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label text-light">Retención</label>
                          <select class="form-select glass-input" disabled>
                            <option>30 días</option>
                            <option>60 días</option>
                            <option>90 días</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="mt-4">
                        <button class="btn btn-outline-primary me-2" disabled>
                          <i class="bi bi-download me-2"></i>Descargar respaldo
                        </button>
                        <button class="btn btn-outline-info" disabled>
                          <i class="bi bi-cloud-check me-2"></i>Verificar respaldos
                        </button>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Sistema de respaldos automáticos disponible en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Maintenance Tools -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h4 class="text-white mb-3">
                        <i class="bi bi-tools me-2"></i>Herramientas de Mantenimiento
                      </h4>
                      <p class="text-light opacity-75 mb-4">
                        Herramientas para optimizar y mantener el sistema.
                      </p>
                      
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-speedometer2 text-primary fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Optimizar DB</div>
                            <small class="text-light opacity-75">Limpiar y optimizar</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-primary" disabled>Ejecutar</button>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-trash text-warning fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Limpiar Cache</div>
                            <small class="text-light opacity-75">Borrar archivos temp</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-warning" disabled>Limpiar</button>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <i class="bi bi-graph-up text-success fs-3 mb-2"></i>
                            <div class="text-white fw-bold">Estadísticas</div>
                            <small class="text-light opacity-75">Generar reportes</small>
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-success" disabled>Ver</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-3 glass-card">
                        <small class="text-info">
                          <i class="bi bi-info-circle me-1"></i>
                          Herramientas de mantenimiento disponibles en próximas versiones
                        </small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    // Init AOS
    if (window.AOS) {
      AOS.init({
        duration: 1000,
        once: true,
        offset: 100,
        easing: 'ease-out-quart'
      });
    }

    // Toggle MercadoPago fields
    const mercadopagoToggle = document.getElementById('mercadopago_enabled');
    const mercadopagoFields = document.getElementById('mercadopagoFields');

    if (mercadopagoToggle && mercadopagoFields) {
      mercadopagoToggle.addEventListener('change', (e) => {
        mercadopagoFields.style.display = e.target.checked ? 'block' : 'none';
      });
    }

    // Toggle Bank Transfer fields
    const bankTransferToggle = document.getElementById('bank_transfer_enabled');
    const bankTransferFields = document.getElementById('bankTransferFields');

    if (bankTransferToggle && bankTransferFields) {
      bankTransferToggle.addEventListener('change', (e) => {
        bankTransferFields.style.display = e.target.checked ? 'block' : 'none';
      });
    }

    // Password toggle function
    function togglePassword(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    // CBU/CVU validation
    const cbuInput = document.querySelector('input[name="cbu_cvu"]');
    if (cbuInput) {
      cbuInput.addEventListener('input', (e) => {
        // Remove non-numeric characters
        e.target.value = e.target.value.replace(/\D/g, '');
        
        // Limit to 22 characters
        if (e.target.value.length > 22) {
          e.target.value = e.target.value.slice(0, 22);
        }
      });
    }
  </script>
</body>
</html>