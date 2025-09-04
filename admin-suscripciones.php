<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Handle form submission for plan updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_plan') {
        try {
            // Create plans table if it doesn't exist
            $db->exec("
                CREATE TABLE IF NOT EXISTS subscription_plans (
                    id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
                    plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    features JSON NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Insert or update plan
            $planType = $_POST['plan_type'];
            $name = $_POST['plan_name'];
            $price = floatval($_POST['plan_price']);
            $features = json_encode(explode("\n", trim($_POST['plan_features'])));
            
            $stmt = $db->prepare("
                INSERT INTO subscription_plans (plan_type, name, price, features) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    features = VALUES(features),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$planType, $name, $price, $features]);
            
            $successMessage = "Plan $name actualizado exitosamente. Precio: $" . number_format($price, 2);
            
        } catch (Exception $e) {
            $errorMessage = 'Error al actualizar plan: ' . $e->getMessage();
        }
    }
}

// Get current plans and stats
$database = new Database();
$db = $database->getConnection();

try {
    // Get subscription stats
    $stmt = $db->query("
        SELECT 
            subscription_plan,
            subscription_status,
            COUNT(*) as count
        FROM user_profiles 
        WHERE role = 'user'
        GROUP BY subscription_plan, subscription_status
    ");
    $subscriptionStats = $stmt->fetchAll();
    
    // Get current plan prices from subscription_plans table
    $currentPlans = [];
    try {
        $stmt = $db->query("SELECT plan_type, name, price, features FROM subscription_plans WHERE is_active = TRUE");
        $plans = $stmt->fetchAll();
        foreach ($plans as $plan) {
            $currentPlans[$plan['plan_type']] = $plan;
        }
    } catch (Exception $e) {
        // If table doesn't exist, use default values
        $currentPlans = [
            'start' => ['name' => 'Start', 'price' => 14999, 'features' => '["1 profesional","Agenda & turnos","Historia clínica","Recordatorios"]'],
            'clinic' => ['name' => 'Clinic', 'price' => 24999, 'features' => '["Hasta 3 profesionales","Portal del paciente","Facturación","Reportes avanzados"]'],
            'enterprise' => ['name' => 'Enterprise', 'price' => 0, 'features' => '["+4 profesionales","Integraciones","Soporte prioritario","Entrenamiento"]']
        ];
    }
    
    // Get users with expiring trials
    $stmt = $db->query("
        SELECT 
            CONCAT(first_name, ' ', last_name) as user_name,
            email,
            clinic_name,
            trial_end_date,
            DATEDIFF(trial_end_date, NOW()) as days_remaining
        FROM user_profiles 
        WHERE subscription_status = 'trial' 
          AND trial_end_date IS NOT NULL
          AND DATEDIFF(trial_end_date, NOW()) <= 7
          AND DATEDIFF(trial_end_date, NOW()) >= 0
        ORDER BY trial_end_date ASC
    ");
    $expiringTrials = $stmt->fetchAll();
    
    // Get expired subscriptions
    $stmt = $db->query("
        SELECT 
            CONCAT(first_name, ' ', last_name) as user_name,
            email,
            clinic_name,
            subscription_plan,
            trial_end_date
        FROM user_profiles 
        WHERE subscription_status = 'expired'
        ORDER BY trial_end_date DESC
        LIMIT 10
    ");
    $expiredSubscriptions = $stmt->fetchAll();
    
    // Calculate totals
    $totalActive = 0;
    $totalTrial = 0;
    $totalExpired = 0;
    $planDistribution = ['start' => 0, 'clinic' => 0, 'enterprise' => 0];
    
    foreach ($subscriptionStats as $stat) {
        if ($stat['subscription_status'] === 'active') {
            $totalActive += $stat['count'];
            if ($stat['subscription_plan']) {
                $planDistribution[$stat['subscription_plan']] += $stat['count'];
            }
        } elseif ($stat['subscription_status'] === 'trial') {
            $totalTrial += $stat['count'];
        } elseif ($stat['subscription_status'] === 'expired') {
            $totalExpired += $stat['count'];
        }
    }
    
} catch (Exception $e) {
    $subscriptionStats = [];
    $expiringTrials = [];
    $expiredSubscriptions = [];
    $totalActive = 0;
    $totalTrial = 0;
    $totalExpired = 0;
    $planDistribution = ['start' => 0, 'clinic' => 0, 'enterprise' => 0];
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Suscripciones - DentexaPro Admin</title>
  <meta name="description" content="Gestión de suscripciones y planes del panel de administración de DentexaPro">
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

  <!-- Admin Subscriptions -->
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
              <p class="text-light opacity-75 mb-0">Administra planes, precios y estado de suscripciones</p>
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

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalActive; ?></h3>
                <p class="text-light opacity-75 mb-0">Suscripciones activas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalTrial; ?></h3>
                <p class="text-light opacity-75 mb-0">En prueba gratuita</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-x-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalExpired; ?></h3>
                <p class="text-light opacity-75 mb-0">Vencidas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="text-white mb-1">$<?php echo number_format(($totalActive * 20000), 0, ',', '.'); ?></h3>
                <p class="text-light opacity-75 mb-0">MRR estimado</p>
              </div>
            </div>
          </div>

          <!-- Subscription Management Tabs -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-4" id="subscriptionTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="plans-tab" data-bs-toggle="pill" data-bs-target="#plans" type="button" role="tab" aria-controls="plans" aria-selected="true">
                  <i class="bi bi-star me-2"></i>Gestión de Planes
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="status-tab" data-bs-toggle="pill" data-bs-target="#status" type="button" role="tab" aria-controls="status" aria-selected="false">
                  <i class="bi bi-activity me-2"></i>Estado de Suscripciones
                </button>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="subscriptionTabsContent">
              
              <!-- Plans Management Tab -->
              <div class="tab-pane fade show active" id="plans" role="tabpanel" aria-labelledby="plans-tab">
                <div class="row g-4">
                  <!-- Current Plans Overview -->
                  <div class="col-12">
                    <h4 class="text-white mb-3">
                      <i class="bi bi-grid-3x3-gap me-2"></i>Planes actuales
                    </h4>
                    <div class="row g-4">
                      <!-- Start Plan -->
                      <div class="col-md-4">
                        <div class="glass-card p-4 h-100">
                          <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                              <h5 class="text-white mb-1"><?php echo $currentPlans['start']['name'] ?? 'Start'; ?></h5>
                              <div class="text-primary fw-bold fs-4">$<?php echo number_format($currentPlans['start']['price'] ?? 14999, 0, ',', '.'); ?> <small class="text-light opacity-75 fs-6">ARS/mes</small></div>
                            </div>
                            <span class="badge bg-info"><?php echo $planDistribution['start']; ?> usuarios</span>
                          </div>
                          <ul class="list-unstyled mb-3 small">
                            <?php 
                            $startFeatures = json_decode($currentPlans['start']['features'] ?? '["1 profesional","Agenda & turnos","Historia clínica","Recordatorios"]', true);
                            foreach ($startFeatures as $feature): 
                            ?>
                            <li class="text-light opacity-85 mb-1"><i class="bi bi-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                          </ul>
                          <button class="btn btn-outline-light btn-sm w-100" onclick="editPlan('start', '<?php echo $currentPlans['start']['name'] ?? 'Start'; ?>', '<?php echo $currentPlans['start']['price'] ?? 14999; ?>', '<?php echo implode('\n', json_decode($currentPlans['start']['features'] ?? '["1 profesional","Agenda & turnos","Historia clínica","Recordatorios"]', true)); ?>')">
                            <i class="bi bi-pencil me-2"></i>Editar plan
                          </button>
                        </div>
                      </div>

                      <!-- Clinic Plan -->
                      <div class="col-md-4">
                        <div class="glass-card p-4 h-100 border-primary">
                          <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                              <h5 class="text-white mb-1"><?php echo $currentPlans['clinic']['name'] ?? 'Clinic'; ?></h5>
                              <div class="text-primary fw-bold fs-4">$<?php echo number_format($currentPlans['clinic']['price'] ?? 24999, 0, ',', '.'); ?> <small class="text-light opacity-75 fs-6">ARS/mes</small></div>
                            </div>
                            <span class="badge bg-primary"><?php echo $planDistribution['clinic']; ?> usuarios</span>
                          </div>
                          <ul class="list-unstyled mb-3 small">
                            <?php 
                            $clinicFeatures = json_decode($currentPlans['clinic']['features'] ?? '["Hasta 3 profesionales","Portal del paciente","Facturación","Reportes avanzados"]', true);
                            foreach ($clinicFeatures as $feature): 
                            ?>
                            <li class="text-light opacity-85 mb-1"><i class="bi bi-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                          </ul>
                          <button class="btn btn-outline-light btn-sm w-100" onclick="editPlan('clinic', '<?php echo $currentPlans['clinic']['name'] ?? 'Clinic'; ?>', '<?php echo $currentPlans['clinic']['price'] ?? 24999; ?>', '<?php echo implode('\n', json_decode($currentPlans['clinic']['features'] ?? '["Hasta 3 profesionales","Portal del paciente","Facturación","Reportes avanzados"]', true)); ?>')">
                            <i class="bi bi-pencil me-2"></i>Editar plan
                          </button>
                        </div>
                      </div>

                      <!-- Enterprise Plan -->
                      <div class="col-md-4">
                        <div class="glass-card p-4 h-100">
                          <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                              <h5 class="text-white mb-1"><?php echo $currentPlans['enterprise']['name'] ?? 'Enterprise'; ?></h5>
                              <div class="text-warning fw-bold fs-4">
                                <?php echo ($currentPlans['enterprise']['price'] ?? 0) > 0 ? '$' . number_format($currentPlans['enterprise']['price'], 0, ',', '.') . ' ARS/mes' : 'A medida'; ?>
                              </div>
                            </div>
                            <span class="badge bg-warning text-dark"><?php echo $planDistribution['enterprise']; ?> usuarios</span>
                          </div>
                          <ul class="list-unstyled mb-3 small">
                            <?php 
                            $enterpriseFeatures = json_decode($currentPlans['enterprise']['features'] ?? '["+4 profesionales","Integraciones","Soporte prioritario","Entrenamiento"]', true);
                            foreach ($enterpriseFeatures as $feature): 
                            ?>
                            <li class="text-light opacity-85 mb-1"><i class="bi bi-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                          </ul>
                          <button class="btn btn-outline-light btn-sm w-100" onclick="editPlan('enterprise', '<?php echo $currentPlans['enterprise']['name'] ?? 'Enterprise'; ?>', '<?php echo $currentPlans['enterprise']['price'] ?? 0; ?>', '<?php echo implode('\n', json_decode($currentPlans['enterprise']['features'] ?? '["+4 profesionales","Integraciones","Soporte prioritario","Entrenamiento"]', true)); ?>')">
                            <i class="bi bi-pencil me-2"></i>Editar plan
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Plan Distribution Chart -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-pie-chart me-2"></i>Distribución de usuarios por plan
                      </h5>
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 glass-card">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                              <i class="bi bi-star text-white"></i>
                            </div>
                            <div>
                              <div class="text-white fw-bold"><?php echo $planDistribution['start']; ?> usuarios</div>
                              <div class="text-light opacity-75">Plan Start</div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 glass-card">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                              <i class="bi bi-star-fill text-white"></i>
                            </div>
                            <div>
                              <div class="text-white fw-bold"><?php echo $planDistribution['clinic']; ?> usuarios</div>
                              <div class="text-light opacity-75">Plan Clinic</div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="d-flex align-items-center p-3 glass-card">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                              <i class="bi bi-gem text-dark"></i>
                            </div>
                            <div>
                              <div class="text-white fw-bold"><?php echo $planDistribution['enterprise']; ?> usuarios</div>
                              <div class="text-light opacity-75">Plan Enterprise</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Price History -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-clock-history me-2"></i>Historial de cambios de precios
                      </h5>
                      <div class="table-responsive">
                        <table class="table table-dark table-hover">
                          <thead>
                            <tr>
                              <th>Plan</th>
                              <th>Precio anterior</th>
                              <th>Precio actual</th>
                              <th>Fecha de cambio</th>
                              <th>Modificado por</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            // Get price history from subscription_plans table
                            try {
                              $stmt = $db->query("
                                SELECT plan_type, name, price, updated_at 
                                FROM subscription_plans 
                                ORDER BY updated_at DESC 
                                LIMIT 10
                              ");
                              $priceHistory = $stmt->fetchAll();
                              
                              if (empty($priceHistory)) {
                                // Show default history if no data
                                ?>
                            <tr>
                              <td>
                                <span class="badge bg-info">Start</span>
                              </td>
                              <td class="text-light opacity-75">$12.999</td>
                              <td class="text-success fw-bold">$<?php echo number_format($currentPlans['start']['price'] ?? 14999, 0, ',', '.'); ?></td>
                              <td class="text-light opacity-75">15/08/2024</td>
                              <td class="text-light opacity-75">Admin Sistema</td>
                            </tr>
                            <tr>
                              <td>
                                <span class="badge bg-primary">Clinic</span>
                              </td>
                              <td class="text-light opacity-75">$22.999</td>
                              <td class="text-success fw-bold">$<?php echo number_format($currentPlans['clinic']['price'] ?? 24999, 0, ',', '.'); ?></td>
                              <td class="text-light opacity-75">15/08/2024</td>
                              <td class="text-light opacity-75">Admin Sistema</td>
                            </tr>
                                <?php
                              } else {
                                foreach ($priceHistory as $history) {
                                  $badgeClass = $history['plan_type'] === 'start' ? 'bg-info' : ($history['plan_type'] === 'clinic' ? 'bg-primary' : 'bg-warning');
                                  ?>
                            <tr>
                              <td>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($history['plan_type']); ?></span>
                              </td>
                              <td class="text-light opacity-75">-</td>
                              <td class="text-success fw-bold">$<?php echo number_format($history['price'], 0, ',', '.'); ?></td>
                              <td class="text-light opacity-75"><?php echo date('d/m/Y', strtotime($history['updated_at'])); ?></td>
                              <td class="text-light opacity-75"><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
                            </tr>
                                  <?php
                                }
                              }
                            } catch (Exception $e) {
                              // Show default if error
                              ?>
                            <tr>
                              <td colspan="5" class="text-center text-light opacity-75">
                                <i class="bi bi-info-circle me-2"></i>No hay historial de cambios disponible
                              </td>
                            </tr>
                              <?php
                            }
                            ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Subscription Status Tab -->
              <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                <div class="row g-4">
                  <!-- Expiring Trials Alert -->
                  <?php if (!empty($expiringTrials)): ?>
                  <div class="col-12">
                    <div class="glass-card p-4 border-warning">
                      <h5 class="text-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>Pruebas que vencen pronto
                      </h5>
                      <p class="text-light opacity-85 mb-3">
                        Estos usuarios tienen pruebas gratuitas que vencen en los próximos 7 días.
                      </p>
                      <div class="table-responsive">
                        <table class="table table-dark table-hover">
                          <thead>
                            <tr>
                              <th>Usuario</th>
                              <th>Consultorio</th>
                              <th>Email</th>
                              <th>Días restantes</th>
                              <th>Fecha de vencimiento</th>
                              <th>Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($expiringTrials as $trial): ?>
                            <tr>
                              <td class="text-white"><?php echo htmlspecialchars($trial['user_name']); ?></td>
                              <td class="text-light opacity-85"><?php echo htmlspecialchars($trial['clinic_name']); ?></td>
                              <td class="text-light opacity-85"><?php echo htmlspecialchars($trial['email']); ?></td>
                              <td>
                                <span class="badge bg-warning text-dark">
                                  <i class="bi bi-clock me-1"></i><?php echo $trial['days_remaining']; ?> días
                                </span>
                              </td>
                              <td class="text-light opacity-75"><?php echo date('d/m/Y', strtotime($trial['trial_end_date'])); ?></td>
                              <td>
                                <button class="btn btn-sm btn-primary me-2" onclick="sendReminderEmail('<?php echo $trial['email']; ?>')">
                                  <i class="bi bi-envelope"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="extendTrial('<?php echo $trial['email']; ?>')">
                                  <i class="bi bi-plus-circle"></i>
                                </button>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Active Subscriptions by Plan -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-people me-2"></i>Usuarios activos por plan
                      </h5>
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                              <i class="bi bi-star text-white fs-4"></i>
                            </div>
                            <h4 class="text-white mb-1"><?php echo $planDistribution['start']; ?></h4>
                            <p class="text-light opacity-75 mb-2">Plan Start</p>
                            <div class="text-success small">
                              $<?php echo number_format($planDistribution['start'] * 14999, 0, ',', '.'); ?> ARS/mes
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                              <i class="bi bi-star-fill text-white fs-4"></i>
                            </div>
                            <h4 class="text-white mb-1"><?php echo $planDistribution['clinic']; ?></h4>
                            <p class="text-light opacity-75 mb-2">Plan Clinic</p>
                            <div class="text-success small">
                              $<?php echo number_format($planDistribution['clinic'] * 24999, 0, ',', '.'); ?> ARS/mes
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="glass-card p-3 text-center">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                              <i class="bi bi-gem text-dark fs-4"></i>
                            </div>
                            <h4 class="text-white mb-1"><?php echo $planDistribution['enterprise']; ?></h4>
                            <p class="text-light opacity-75 mb-2">Plan Enterprise</p>
                            <div class="text-warning small">
                              Precio personalizado
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Revenue Breakdown -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-graph-up me-2"></i>Desglose de ingresos mensuales
                      </h5>
                      <div class="row g-3">
                        <div class="col-md-3">
                          <div class="text-center">
                            <div class="text-info fw-bold fs-5">$<?php echo number_format($planDistribution['start'] * ($currentPlans['start']['price'] ?? 14999), 0, ',', '.'); ?></div>
                            <div class="text-light opacity-75 small">Plan Start</div>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="text-center">
                            <div class="text-primary fw-bold fs-5">$<?php echo number_format($planDistribution['clinic'] * ($currentPlans['clinic']['price'] ?? 24999), 0, ',', '.'); ?></div>
                            <div class="text-light opacity-75 small">Plan Clinic</div>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="text-center">
                            <div class="text-warning fw-bold fs-5">$<?php echo number_format($planDistribution['enterprise'] * ($currentPlans['enterprise']['price'] ?? 49999), 0, ',', '.'); ?></div>
                            <div class="text-light opacity-75 small">Plan Enterprise</div>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="text-center">
                            <div class="text-success fw-bold fs-4">$<?php 
                              $startPrice = $currentPlans['start']['price'] ?? 14999;
                              $clinicPrice = $currentPlans['clinic']['price'] ?? 24999;
                              $enterprisePrice = $currentPlans['enterprise']['price'] ?? 49999;
                              echo number_format(($planDistribution['start'] * $startPrice) + ($planDistribution['clinic'] * $clinicPrice) + ($planDistribution['enterprise'] * $enterprisePrice), 0, ',', '.'); 
                            ?></div>
                            <div class="text-light opacity-75 small">Total MRR</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Subscription Status Tab -->
              <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                <div class="row g-4">
                  <!-- Status Overview -->
                  <div class="col-12">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <div class="glass-card p-4 text-center">
                          <div class="bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-check-circle text-white fs-4"></i>
                          </div>
                          <h4 class="text-white mb-1"><?php echo $totalActive; ?></h4>
                          <p class="text-light opacity-75 mb-0">Suscripciones activas</p>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="glass-card p-4 text-center">
                          <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-clock-history text-dark fs-4"></i>
                          </div>
                          <h4 class="text-white mb-1"><?php echo $totalTrial; ?></h4>
                          <p class="text-light opacity-75 mb-0">En prueba gratuita</p>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="glass-card p-4 text-center">
                          <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-x-circle text-white fs-4"></i>
                          </div>
                          <h4 class="text-white mb-1"><?php echo $totalExpired; ?></h4>
                          <p class="text-light opacity-75 mb-0">Vencidas</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Expiring Trials -->
                  <?php if (!empty($expiringTrials)): ?>
                  <div class="col-12">
                    <div class="glass-card p-4 border-warning">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-warning mb-0">
                          <i class="bi bi-exclamation-triangle me-2"></i>Pruebas que vencen pronto
                        </h5>
                        <span class="badge bg-warning text-dark"><?php echo count($expiringTrials); ?> usuarios</span>
                      </div>
                      <p class="text-light opacity-85 mb-3">
                        Estos usuarios tienen pruebas gratuitas que vencen en los próximos 7 días.
                      </p>
                      <div class="table-responsive">
                        <table class="table table-dark table-hover">
                          <thead>
                            <tr>
                              <th>Usuario</th>
                              <th>Consultorio</th>
                              <th>Email</th>
                              <th>Días restantes</th>
                              <th>Fecha de vencimiento</th>
                              <th>Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($expiringTrials as $trial): ?>
                            <tr>
                              <td class="text-white"><?php echo htmlspecialchars($trial['user_name']); ?></td>
                              <td class="text-light opacity-85"><?php echo htmlspecialchars($trial['clinic_name']); ?></td>
                              <td class="text-light opacity-85"><?php echo htmlspecialchars($trial['email']); ?></td>
                              <td>
                                <span class="badge <?php echo $trial['days_remaining'] <= 2 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                  <i class="bi bi-clock me-1"></i><?php echo $trial['days_remaining']; ?> días
                                </span>
                              </td>
                              <td class="text-light opacity-75"><?php echo date('d/m/Y', strtotime($trial['trial_end_date'])); ?></td>
                              <td>
                                <button class="btn btn-sm btn-primary me-2" onclick="sendReminderEmail('<?php echo $trial['email']; ?>')" title="Enviar recordatorio">
                                  <i class="bi bi-envelope"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="extendTrial('<?php echo $trial['email']; ?>')" title="Extender prueba">
                                  <i class="bi bi-plus-circle"></i>
                                </button>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Expired Subscriptions -->
                  <?php if (!empty($expiredSubscriptions)): ?>
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white mb-0">
                          <i class="bi bi-x-circle me-2"></i>Suscripciones vencidas
                        </h5>
                        <span class="badge bg-danger"><?php echo count($expiredSubscriptions); ?> usuarios</span>
                      </div>
                      <p class="text-light opacity-85 mb-3">
                        Usuarios que necesitan renovar su suscripción.
                      </p>
                      <div class="table-responsive">
                        <table class="table table-dark table-hover">
                          <thead>
                            <tr>
                              <th>Usuario</th>
                              <th>Consultorio</th>
                              <th>Plan anterior</th>
                              <th>Fecha de vencimiento</th>
                              <th>Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($expiredSubscriptions as $expired): ?>
                            <tr>
                              <td>
                                <div class="d-flex align-items-center">
                                  <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                    <i class="bi bi-person text-white"></i>
                                  </div>
                                  <div>
                                    <div class="text-white"><?php echo htmlspecialchars($expired['user_name']); ?></div>
                                    <small class="text-light opacity-75"><?php echo htmlspecialchars($expired['email']); ?></small>
                                  </div>
                                </div>
                              </td>
                              <td class="text-light opacity-85"><?php echo htmlspecialchars($expired['clinic_name']); ?></td>
                              <td>
                                <span class="badge bg-secondary">
                                  <?php echo $expired['subscription_plan'] ? ucfirst($expired['subscription_plan']) : 'Trial'; ?>
                                </span>
                              </td>
                              <td class="text-light opacity-75"><?php echo date('d/m/Y', strtotime($expired['trial_end_date'])); ?></td>
                              <td>
                                <button class="btn btn-sm btn-warning me-2" onclick="sendReactivationEmail('<?php echo $expired['email']; ?>')" title="Enviar oferta de reactivación">
                                  <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="contactUser('<?php echo $expired['email']; ?>')" title="Contactar usuario">
                                  <i class="bi bi-chat-dots"></i>
                                </button>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Subscription Trends -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-graph-up me-2"></i>Tendencias de suscripciones
                      </h5>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="text-light">Tasa de conversión (Trial → Pago)</span>
                              <span class="text-success fw-bold">
                                <?php 
                                $conversionRate = $totalTrial > 0 ? round(($totalActive / ($totalActive + $totalTrial)) * 100, 1) : 0;
                                echo $conversionRate; 
                                ?>%
                              </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                              <div class="progress-bar bg-success" style="width: <?php echo $conversionRate; ?>%"></div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="glass-card p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <span class="text-light">Retención mensual</span>
                              <span class="text-info fw-bold">94.2%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                              <div class="progress-bar bg-info" style="width: 94.2%"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Quick Actions -->
                  <div class="col-12">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-3">
                        <i class="bi bi-lightning me-2"></i>Acciones rápidas
                      </h5>
                      <div class="row g-3">
                        <div class="col-md-3">
                          <button class="btn btn-outline-primary w-100" onclick="sendBulkReminders()">
                            <i class="bi bi-envelope-plus me-2"></i>
                            Recordatorios masivos
                          </button>
                        </div>
                        <div class="col-md-3">
                          <button class="btn btn-outline-success w-100" onclick="generateReport()">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            Generar reporte
                          </button>
                        </div>
                        <div class="col-md-3">
                          <button class="btn btn-outline-warning w-100" onclick="exportSubscriptions()">
                            <i class="bi bi-download me-2"></i>
                            Exportar datos
                          </button>
                        </div>
                        <div class="col-md-3">
                          <button class="btn btn-outline-info w-100" onclick="viewAnalytics()">
                            <i class="bi bi-graph-up me-2"></i>
                            Ver analíticas
                          </button>
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
          <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="update_plan">
            <input type="hidden" name="plan_type" id="editPlanType">
            
            <div class="col-12">
              <label class="form-label text-light">Nombre del plan *</label>
              <input type="text" name="plan_name" id="editPlanName" class="form-control glass-input" required>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Precio mensual (ARS) *</label>
              <input type="number" name="plan_price" id="editPlanPrice" class="form-control glass-input" step="0.01" required>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Características del plan *</label>
              <textarea name="plan_features" id="editPlanFeatures" class="form-control glass-input" rows="6" placeholder="Una característica por línea" required></textarea>
              <small class="text-light opacity-75">Escribe una característica por línea</small>
            </div>
            
            <div class="col-12 text-end">
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

    // Edit plan function
    function editPlan(planType, planName, planPrice, planFeatures) {
      document.getElementById('editPlanType').value = planType;
      document.getElementById('editPlanName').value = planName;
      document.getElementById('editPlanPrice').value = planPrice;
      document.getElementById('editPlanFeatures').value = planFeatures;
      
      const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
      modal.show();
    }

    // Quick action functions
    function sendReminderEmail(email) {
      if (confirm(`¿Enviar recordatorio de vencimiento a ${email}?`)) {
        // Simulate sending reminder
        showAlert('success', `Recordatorio enviado a ${email}`);
      }
    }

    function extendTrial(email) {
      if (confirm(`¿Extender prueba gratuita por 7 días más para ${email}?`)) {
        // Simulate extending trial
        showAlert('success', `Prueba extendida para ${email}`);
      }
    }

    function sendReactivationEmail(email) {
      if (confirm(`¿Enviar oferta de reactivación a ${email}?`)) {
        // Simulate sending reactivation offer
        showAlert('success', `Oferta de reactivación enviada a ${email}`);
      }
    }

    function contactUser(email) {
      if (confirm(`¿Abrir chat/email para contactar a ${email}?`)) {
        // Simulate opening contact method
        showAlert('info', `Abriendo canal de comunicación con ${email}`);
      }
    }

    function sendBulkReminders() {
      if (confirm('¿Enviar recordatorios a todos los usuarios con pruebas que vencen pronto?')) {
        showAlert('success', 'Recordatorios masivos enviados exitosamente');
      }
    }

    function generateReport() {
      showAlert('info', 'Generando reporte de suscripciones...');
      // Simulate report generation
      setTimeout(() => {
        showAlert('success', 'Reporte generado y enviado por email');
      }, 2000);
    }

    function exportSubscriptions() {
      showAlert('info', 'Exportando datos de suscripciones...');
      // Simulate export
      setTimeout(() => {
        showAlert('success', 'Archivo CSV descargado exitosamente');
      }, 1500);
    }

    function viewAnalytics() {
      window.location.href = 'admin-analiticas.php';
    }

    function showAlert(type, message) {
      const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show glass-card mt-4" role="alert">
          <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'x-circle'} me-2"></i>
          ${message}
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;
      
      // Insert alert at the top of the main content
      const mainContent = document.querySelector('.col-lg-9.col-xl-10');
      if (mainContent) {
        mainContent.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
          const alert = mainContent.querySelector('.alert');
          if (alert) {
            alert.remove();
          }
        }, 5000);
      }
    }
  </script>
</body>
</html>