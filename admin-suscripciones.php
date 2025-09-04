<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Create subscription_plans table if it doesn't exist
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
        
        // Create price history table
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
        
        $successMessage = 'Configuración guardada exitosamente';
        
    } catch (Exception $e) {
        $errorMessage = 'Error al guardar configuración: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Suscripciones - DentexaPro Admin</title>
  <meta name="description" content="Gestión de planes y suscripciones del panel de administración de DentexaPro">
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

  <!-- Admin Dashboard -->
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
                <i class="bi bi-credit-card me-2"></i>Gestión de Suscripciones
              </h1>
              <p class="text-light opacity-75 mb-0">Administra planes, precios y suscripciones de usuarios</p>
            </div>
            <button class="btn btn-primary-soft" onclick="loadPlans()">
              <i class="bi bi-arrow-clockwise me-2"></i>Actualizar datos
            </button>
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

          <!-- Subscription Stats -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1" id="activeCount">0</h3>
                <p class="text-light opacity-75 mb-0">Suscripciones activas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1" id="trialCount">0</h3>
                <p class="text-light opacity-75 mb-0">Usuarios en prueba</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-x-circle"></i>
                </div>
                <h3 class="text-white mb-1" id="expiredCount">0</h3>
                <p class="text-light opacity-75 mb-0">Suscripciones vencidas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-graph-up"></i>
                </div>
                <h3 class="text-white mb-1" id="conversionRate">0%</h3>
                <p class="text-light opacity-75 mb-0">Tasa de conversión</p>
              </div>
            </div>
          </div>

          <!-- Plans Management -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-star me-2"></i>Gestión de planes
              </h4>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Plan</th>
                    <th>Precio mensual</th>
                    <th>Precio anual</th>
                    <th>Usuarios</th>
                    <th>MRR</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="plansTable">
                  <!-- Plans will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Expiring Trials -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <h4 class="text-white mb-4">
              <i class="bi bi-clock-history me-2"></i>Pruebas que vencen pronto
            </h4>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Consultorio</th>
                    <th>Días restantes</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="expiringTrialsTable">
                  <!-- Expiring trials will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Expired Subscriptions -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
            <h4 class="text-white mb-4">
              <i class="bi bi-x-circle me-2"></i>Suscripciones vencidas
            </h4>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Consultorio</th>
                    <th>Plan anterior</th>
                    <th>Fecha vencimiento</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="expiredSubscriptionsTable">
                  <!-- Expired subscriptions will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Plan Modal -->
  <div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="editPlanModalLabel">
            <i class="bi bi-pencil me-2"></i>Editar plan
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="editPlanForm">
            <input type="hidden" id="editPlanType" name="plan_type">
            
            <div class="mb-4">
              <label class="form-label text-light">Nombre del plan *</label>
              <input type="text" id="editPlanName" name="name" class="form-control glass-input" required>
            </div>
            
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label text-light">Precio mensual (ARS) *</label>
                <div class="input-group">
                  <span class="input-group-text bg-dark border-secondary text-light">$</span>
                  <input type="number" id="editPriceMonthly" name="price_monthly" class="form-control glass-input" step="0.01" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label text-light">Precio anual (ARS) *</label>
                <div class="input-group">
                  <span class="input-group-text bg-dark border-secondary text-light">$</span>
                  <input type="number" id="editPriceYearly" name="price_yearly" class="form-control glass-input" step="0.01" min="0" required>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Características del plan</label>
              <div id="editFeaturesContainer">
                <!-- Features will be added here -->
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addFeatureInput()">
                <i class="bi bi-plus me-2"></i>Agregar característica
              </button>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Motivo del cambio</label>
              <textarea name="change_reason" class="form-control glass-input" rows="2" placeholder="Describe por qué estás cambiando este plan..."></textarea>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Guardar cambios
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-suscripciones.js"></script>
</body>
</html>