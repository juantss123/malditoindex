<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();

// Redirect admin to admin panel
if (isAdmin()) {
    header('Location: admin.php');
    exit();
}

// Get user data
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT first_name, last_name, subscription_status, subscription_plan, 
               trial_end_date, clinic_name
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userProfile = $stmt->fetch();
    
    // Calculate trial days remaining
    $trialDaysRemaining = 0;
    if ($userProfile['subscription_status'] === 'trial' && $userProfile['trial_end_date']) {
        $trialEnd = new DateTime($userProfile['trial_end_date']);
        $today = new DateTime();
        $diff = $today->diff($trialEnd);
        $trialDaysRemaining = $diff->invert ? 0 : $diff->days;
    }
    
} catch (Exception $e) {
    $userProfile = null;
    $trialDaysRemaining = 0;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - DentexaPro</title>
  <meta name="description" content="Panel de control de DentexaPro">
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
      <a class="navbar-brand d-flex align-items-center gap-2" href="#">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-outline-light">
          <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
        </a>
      </div>
    </div>
  </nav>

  <!-- Dashboard -->
  <main class="section-pt pb-5">
    <div class="container">
      <!-- Welcome Header -->
      <div class="row mb-5">
        <div class="col-12" data-aos="fade-down" data-aos-duration="800">
          <div class="glass-card p-4 p-sm-5">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <h1 class="text-white mb-2">
                  ¡Bienvenido a <span class="gradient-text">DentexaPro</span>!
                </h1>
                <p class="text-light opacity-85 mb-3">
                  <?php 
                  if ($userProfile && $userProfile['subscription_status'] === 'trial') {
                      // Check if user has approved trial request
                      $hasApprovedTrial = false;
                      try {
                          $stmt = $db->prepare("SELECT status FROM trial_requests WHERE user_id = ? AND status = 'approved' LIMIT 1");
                          $stmt->execute([$_SESSION['user_id']]);
                          $hasApprovedTrial = $stmt->fetch() !== false;
                      } catch (Exception $e) {
                          // Ignore error if table doesn't exist
                      }
                      
                      if ($hasApprovedTrial) {
                          echo "Tu prueba gratuita está activa. Explorá todas las funciones sin límites.";
                      } else {
                          echo "Para acceder a la prueba gratuita completa, solicita la activación usando el botón de iniciar prueba gratuita.";
                      }
                  } elseif ($userProfile && $userProfile['subscription_status'] === 'active') {
                      echo "Tu plan está activo y funcionando perfectamente.";
                  } else {
                      echo "Bienvenido a tu panel de control.";
                  }
?>
</p>
<script>
console.log('Dashboard: No features found for start plan or features array is empty');
                  // Set default features if none found
                  const modalStartFeaturesEl = document.getElementById('modalStartFeatures');
                  if (modalStartFeaturesEl) {
                    modalStartFeaturesEl.innerHTML = `
                      <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>1 profesional</li>
                      <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Agenda & turnos</li>
                      <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Historia clínica</li>
                      <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Recordatorios</li>
                    `;
  }
</script>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div class="d-flex align-items-center text-light">
                    <i class="bi bi-clock me-2"></i>
                    <span>
                      <?php 
                      if ($userProfile && $userProfile['subscription_status'] === 'trial') {
                          // Check if user has approved trial request
                          $hasApprovedTrial = false;
                          try {
                              $stmt = $db->prepare("SELECT status FROM trial_requests WHERE user_id = ? AND status = 'approved' LIMIT 1");
                              $stmt->execute([$_SESSION['user_id']]);
                              $hasApprovedTrial = $stmt->fetch() !== false;
                          } catch (Exception $e) {
                              // Ignore error if table doesn't exist
                          }
                          
                          if ($hasApprovedTrial) {
                              echo "Prueba gratuita: $trialDaysRemaining días restantes";
                          } else {
                              echo "Cuenta en período de prueba";
                          }
                      } elseif ($userProfile && $userProfile['subscription_status'] === 'active') {
                          echo "Plan activo: " . ucfirst($userProfile['subscription_plan']);
                      } else {
                          echo "Estado: " . ucfirst($userProfile['subscription_status'] ?? 'Desconocido');
                      }
                      ?>
                    </span>
                  </div>
                  <div class="d-flex align-items-center text-success">
                    <i class="bi bi-shield-check me-2"></i>
                    <span>Cuenta verificada</span>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-lg-flex justify-content-lg-end align-items-center">
                <?php if ($userProfile && $userProfile['subscription_status'] === 'trial'): ?>
                    <?php
                    // Check if user has approved trial request
                    $hasApprovedTrial = false;
                    try {
                        $stmt = $db->prepare("SELECT status FROM trial_requests WHERE user_id = ? AND status = 'approved' LIMIT 1");
                        $stmt->execute([$_SESSION['user_id']]);
                        $hasApprovedTrial = $stmt->fetch() !== false;
                    } catch (Exception $e) {
                        // Ignore error if table doesn't exist
                    }
                    ?>
                    
                    <?php if (!$hasApprovedTrial): ?>
                    <button class="btn btn-primary btn-lg me-2" id="startTrialBtn">
                        <i class="bi bi-play-circle me-2"></i>Iniciar prueba gratuita
                    </button>
                    <?php endif; ?>

                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#plansModal">
                        <i class="bi bi-star me-2"></i>Actualizar plan
                    </button>

                <?php else: ?>
                
                    <button class="btn btn-primary-soft btn-lg" data-bs-toggle="modal" data-bs-target="#managePlanModal">
                        <i class="bi bi-gear me-2"></i>Gestionar plan
                    </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Subscription Status -->
      <?php if ($userProfile && $userProfile['subscription_status'] === 'trial'): ?>
      
      <!-- Trial Request Status -->
      <?php
      // Check if user has trial request and get status
      $trialRequest = null;
      try {
          $stmt = $db->prepare("
              SELECT status, trial_website, trial_username, trial_password, admin_notes, processed_at
              FROM trial_requests 
              WHERE user_id = ? 
              ORDER BY request_date DESC 
              LIMIT 1
          ");
          $stmt->execute([$_SESSION['user_id']]);
          $trialRequest = $stmt->fetch();
      } catch (Exception $e) {
          // Ignore error if table doesn't exist
      }
      ?>
      
      <?php if ($trialRequest && $trialRequest['status'] === 'approved'): ?>
      <div class="row mb-4">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
          <div class="glass-card p-4 p-sm-5 border-success">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <h3 class="text-success mb-2">
                  <i class="bi bi-check-circle-fill me-2"></i>¡Prueba gratuita aprobada!
                </h3>
                <p class="text-light opacity-85 mb-3">
                  Tu solicitud de prueba gratuita ha sido aprobada. Aquí tienes los datos de acceso:
                </p>
                <div class="row g-3">
                  <div class="col-12">
                    <div class="glass-card p-3">
                      <!-- Website Access -->
                      <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                          <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-globe text-white"></i>
                          </div>
                          <div>
                            <strong class="text-white">Página web de tu prueba</strong>
                            <div class="text-light opacity-75 small">Accede a tu demo personalizada</div>
                          </div>
                        </div>
                        <div class="glass-card p-3 ms-5">
                          <div class="d-flex align-items-center justify-content-between">
                            <a href="<?php echo htmlspecialchars($trialRequest['trial_website']); ?>" target="_blank" class="text-primary text-decoration-none">
                              <i class="bi bi-link-45deg me-2"></i><span class="text-white"><?php echo htmlspecialchars($trialRequest['trial_website']); ?></span>
                            </a>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($trialRequest['trial_website']); ?>')">
                              <i class="bi bi-clipboard"></i>
                            </button>
                          </div>
                        </div>
                      </div>

                      <!-- Login Credentials -->
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="d-flex align-items-center mb-2">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                              <i class="bi bi-person-circle text-white"></i>
                            </div>
                            <div>
                              <strong class="text-white">Usuario</strong>
                              <div class="text-light opacity-75 small">Tu nombre de usuario</div>
                            </div>
                          </div>
                          <div class="glass-card p-3 ms-5">
                            <div class="d-flex align-items-center justify-content-between">
                              <code class="text-white bg-transparent border border-primary rounded px-3 py-2 flex-grow-1 me-2">
                                <?php echo htmlspecialchars($trialRequest['trial_username']); ?>
                              </code>
                              <button class="btn btn-sm btn-outline-info" onclick="copyToClipboard('<?php echo htmlspecialchars($trialRequest['trial_username']); ?>')" title="Copiar usuario">
                                <i class="bi bi-clipboard"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                        
                        <div class="col-md-6">
                          <div class="d-flex align-items-center mb-2">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                              <i class="bi bi-key text-dark"></i>
                            </div>
                            <div>
                              <strong class="text-white">Contraseña</strong>
                              <div class="text-light opacity-75 small">Tu contraseña de acceso</div>
                            </div>
                          </div>
                          <div class="glass-card p-3 ms-5">
                            <div class="d-flex align-items-center justify-content-between">
                              <code class="text-white bg-transparent border border-warning rounded px-3 py-2 flex-grow-1 me-2">
                                <?php echo htmlspecialchars($trialRequest['trial_password']); ?>
                              </code>
                              <button class="btn btn-sm btn-outline-warning" onclick="copyToClipboard('<?php echo htmlspecialchars($trialRequest['trial_password']); ?>')" title="Copiar contraseña">
                                <i class="bi bi-clipboard"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php if ($trialRequest['admin_notes']): ?>
                      <div class="mt-4">
                        <div class="d-flex align-items-center mb-2">
                          <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-chat-square-text text-white"></i>
                          </div>
                          <div>
                            <strong class="text-white">Notas del administrador</strong>
                            <div class="text-light opacity-75 small">Información adicional</div>
                          </div>
                        </div>
                        <div class="glass-card p-3 ms-5">
                          <div class="text-light opacity-85"><?php echo htmlspecialchars($trialRequest['admin_notes']); ?></div>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="<?php echo htmlspecialchars($trialRequest['trial_website']); ?>" target="_blank" class="btn btn-success btn-lg w-100">
                  <i class="bi bi-play-circle me-2"></i>Acceder a la prueba
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php elseif ($trialRequest && $trialRequest['status'] === 'pending'): ?>
      <div class="row mb-4">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
          <div class="glass-card p-4 p-sm-5 border-warning">
            <div class="text-center">
              <h3 class="text-warning mb-2">
                <i class="bi bi-clock-history me-2"></i>Solicitud en revisión
              </h3>
              <p class="text-light opacity-85 mb-0">
                Tu solicitud de prueba gratuita está siendo revisada por nuestro equipo. Te notificaremos cuando esté lista.
              </p>
            </div>
          </div>
        </div>
      </div>
      <?php elseif ($trialRequest && $trialRequest['status'] === 'rejected'): ?>
      <div class="row mb-4">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
          <div class="glass-card p-4 p-sm-5 border-danger">
            <div class="text-center">
              <h3 class="text-danger mb-2">
                <i class="bi bi-x-circle-fill me-2"></i>Solicitud no aprobada
              </h3>
              <p class="text-light opacity-85 mb-3">
                Tu solicitud de prueba gratuita no pudo ser aprobada en este momento.
              </p>
              <?php if ($trialRequest['admin_notes']): ?>
              <div class="glass-card p-3">
                <strong class="text-light">Motivo:</strong>
                <div class="text-light opacity-85 mt-1"><?php echo htmlspecialchars($trialRequest['admin_notes']); ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
          <div class="glass-card p-4 p-sm-5">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <h3 class="text-white mb-2">
                  <i class="bi bi-gift me-2"></i>Tu suscripción gratuita
                </h3>
                <p class="text-light opacity-85 mb-3">
                  Estás usando DentexaPro con todas las funciones incluidas. Cuando termine tu prueba, elegí el plan que mejor se adapte a tu consultorio.
                </p>
                <div class="row g-3">
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-check-circle-fill text-success me-2"></i>
                      <span>Agenda ilimitada</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-check-circle-fill text-success me-2"></i>
                      <span>Historia clínica completa</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-check-circle-fill text-success me-2"></i>
                      <span>Recordatorios automáticos</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-check-circle-fill text-success me-2"></i>
                      <span>Soporte incluido</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <button class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#plansModal">
                  <i class="bi bi-arrow-up-circle me-2"></i>Ver planes
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Plans Modal -->
  <div class="modal fade" id="plansModal" tabindex="-1" aria-labelledby="plansModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="plansModalLabel">
            <i class="bi bi-star me-2"></i>Elegí tu plan
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-4">
            <!-- Start Plan -->
            <div class="col-md-6">
              <div class="glass-card p-4 h-100">
                <div class="text-center mb-3">
                  <h4 class="text-white">Start</h4>
                  <div class="display-6 fw-bold text-white">$<span id="modalStartPrice">14.999</span><small class="fs-6 text-light"> ARS/mes</small></div>
                </div>
                <ul class="list-unstyled mb-4" id="modalStartFeatures">
                  <!-- Features will be loaded dynamically -->
                </ul>
                <button class="btn btn-outline-light w-100" onclick="selectPlan('start')">
                  Seleccionar Start
                </button>
              </div>
            </div>

            <!-- Clinic Plan -->
            <div class="col-md-6">
              <div class="glass-card p-4 h-100 border-primary">
                <div class="text-center mb-3">
                  <span class="badge bg-primary mb-2">Recomendado</span>
                  <h4 class="text-white">Clinic</h4>
                  <div class="display-6 fw-bold text-white">$<span id="modalClinicPrice">24.999</span><small class="fs-6 text-light"> ARS/mes</small></div>
                </div>
                <ul class="list-unstyled mb-4">
                  <div id="modalClinicFeatures">
                    <!-- Features will be loaded dynamically -->
                  </div>
                </ul>
                <button class="btn btn-primary w-100" onclick="selectPlan('clinic')">
                  Seleccionar Clinic
                </button>
              </div>
            </div>
          </div>

          <div class="text-center mt-4">
            <p class="text-light opacity-75 small">
              <i class="bi bi-shield-check me-1"></i>Cancelás cuando quieras • Sin compromisos a largo plazo
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Manage Plan Modal -->
  <div class="modal fade" id="managePlanModal" tabindex="-1" aria-labelledby="managePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="managePlanModalLabel">
            <i class="bi bi-gear me-2"></i>Gestionar mi plan
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Plan Information -->
          <div class="glass-card p-4 mb-4">
            <h5 class="text-white mb-3">
              <i class="bi bi-star me-2"></i>Información de tu plan
            </h5>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-award text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">Plan actual</strong>
                    <div class="text-light opacity-75">
                      <span class="badge <?php echo getPlanBadgeClass($userProfile['subscription_plan'] ?? ''); ?>">
                        <?php echo getPlanDisplayName($userProfile['subscription_plan'] ?? ''); ?>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">Estado</strong>
                    <div class="text-light opacity-75">
                      <span class="badge <?php echo getStatusBadgeClass($userProfile['subscription_status'] ?? ''); ?>">
                        <?php echo getStatusDisplayName($userProfile['subscription_status'] ?? ''); ?>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">Próximo pago</strong>
                    <div class="text-light opacity-75" id="nextPaymentDate">
                      <?php 
                      if ($userProfile && $userProfile['subscription_status'] === 'active') {
                          $nextPayment = new DateTime();
                          $nextPayment->add(new DateInterval('P1M')); // Add 1 month
                          echo $nextPayment->format('d/m/Y');
                      } else {
                          echo 'No programado';
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <i class="bi bi-clock text-dark"></i>
                  </div>
                  <div>
                    <strong class="text-white">Días restantes</strong>
                    <div class="text-light opacity-75" id="daysRemaining">
                      <?php 
                      if ($userProfile && $userProfile['subscription_status'] === 'active') {
                          $today = new DateTime();
                          $nextMonth = clone $today;
                          $nextMonth->add(new DateInterval('P1M'));
                          $daysInMonth = $today->format('t');
                          $currentDay = $today->format('j');
                          $daysRemaining = $daysInMonth - $currentDay;
                          echo $daysRemaining . ' días';
                      } else {
                          echo 'N/A';
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Plan Access Data -->

      <!-- Support Section -->
      <div class="row mt-4">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
          <div class="glass-card p-4 p-sm-5">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <h3 class="text-white mb-2">
                  <i class="bi bi-headset me-2"></i>¿Necesitas ayuda?
                </h3>
                <p class="text-light opacity-85 mb-3">
                  Nuestro equipo de soporte está aquí para ayudarte. Crea un ticket y te responderemos lo antes posible.
                </p>
                <div class="row g-3">
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-clock-fill text-info me-2"></i>
                      <span>Respuesta en 24 horas</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="d-flex align-items-center text-light">
                      <i class="bi bi-chat-dots-fill text-success me-2"></i>
                      <span>Soporte en español</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <button class="btn btn-info btn-lg w-100" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                  <i class="bi bi-plus-circle me-2"></i>Crear ticket de soporte
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- My Tickets Section -->
      <div class="row mt-4">
        <div class="col-12" data-aos="fade-up" data-aos-duration="800" data-aos-delay="900">
          <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-ticket-perforated me-2"></i>Mis tickets de soporte
              </h4>
              <button class="btn btn-primary-soft" onclick="loadMyTickets()">
                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
              </button>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Número</th>
                    <th>Asunto</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Última actualización</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="myTicketsTable">
                  <!-- User tickets will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
          <div class="glass-card p-4 mb-4" id="planAccessSection">
            <h5 class="text-white mb-3">
              <i class="bi bi-globe me-2"></i>Acceso a tu panel
            </h5>
            <div id="planAccessContent">
              <div class="text-center text-light opacity-75 py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Cargando datos de acceso...
              </div>
            </div>
          </div>

          <!-- Plan Actions -->
          <div class="row g-3">
            <div class="col-md-6">
              <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#plansModal" data-bs-dismiss="modal">
                <i class="bi bi-arrow-up-circle me-2"></i>Cambiar plan
              </button>
            </div>
            <div class="col-md-6">
              <button class="btn btn-outline-danger w-100" onclick="cancelSubscription()">
                <i class="bi bi-x-circle me-2"></i>Cancelar suscripción
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Ticket Modal -->
  <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="createTicketModalLabel">
            <i class="bi bi-plus-circle me-2"></i>Crear ticket de soporte
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="createTicketForm" class="row g-3">
            <div class="col-12">
              <label class="form-label text-light">Asunto *</label>
              <input type="text" name="subject" class="form-control glass-input" placeholder="Describe brevemente tu consulta..." required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Categoría *</label>
              <select name="category" class="form-select glass-input" required>
                <option value="">Seleccionar categoría</option>
                <option value="technical">Problema técnico</option>
                <option value="billing">Facturación y pagos</option>
                <option value="feature">Solicitud de funcionalidad</option>
                <option value="bug">Reporte de error</option>
                <option value="general">Consulta general</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Prioridad</label>
              <select name="priority" class="form-select glass-input">
                <option value="low">Baja</option>
                <option value="medium" selected>Media</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Descripción detallada *</label>
              <textarea name="description" class="form-control glass-input" rows="5" 
                        placeholder="Describe tu problema o consulta con el mayor detalle posible. Incluye pasos para reproducir el problema si es técnico..." required></textarea>
            </div>
            
            <div class="col-12">
              <div class="glass-card p-3">
                <h6 class="text-info mb-2">
                  <i class="bi bi-lightbulb me-2"></i>Consejos para un mejor soporte:
                </h6>
                <ul class="text-light opacity-85 small mb-0">
                  <li>Sé específico en tu descripción</li>
                  <li>Incluye capturas de pantalla si es posible</li>
                  <li>Menciona qué navegador usas</li>
                  <li>Describe los pasos que seguiste antes del problema</li>
                </ul>
              </div>
            </div>
            
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-info">
                <i class="bi bi-send me-2"></i>Crear ticket
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- View My Ticket Modal -->
  <div class="modal fade" id="viewMyTicketModal" tabindex="-1" aria-labelledby="viewMyTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="viewMyTicketModalLabel">
            <i class="bi bi-eye me-2"></i>Mi ticket de soporte
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div id="myTicketDetailsContent">
            <!-- Ticket details will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    // Copy to clipboard function
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        // Show temporary success message
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success glass-card';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>Copiado al portapapeles';
        document.body.appendChild(toast);
        
        setTimeout(() => {
          toast.remove();
        }, 2000);
      }).catch(err => {
        console.error('Error copying to clipboard:', err);
      });
    }
    
    // Load plan access data when manage plan modal opens
    const managePlanModal = document.getElementById('managePlanModal');
    if (managePlanModal) {
      managePlanModal.addEventListener('show.bs.modal', loadPlanAccessData);
    }
    
    async function loadPlanAccessData() {
      const planAccessContent = document.getElementById('planAccessContent');
      
      // Show loading state
      planAccessContent.innerHTML = `
        <div class="text-center text-light opacity-75 py-4">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Cargando datos de acceso...
        </div>
      `;
      
      try {
        console.log('Loading plan access for user:', '<?php echo $_SESSION['user_id']; ?>');
        const response = await fetch(`api/plan-access.php?user_id=<?php echo $_SESSION['user_id']; ?>`);
        console.log('Response status:', response.status);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Plan access data:', data);
        
        if (data.success && data.access) {
          console.log('Access data found:', data.access);
          // Show access data
          planAccessContent.innerHTML = `
            <div class="row g-3">
              <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-globe text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">URL de tu panel</strong>
                    <div class="text-light opacity-75 small">Accede a tu panel personalizado</div>
                  </div>
                </div>
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between">
                    <a href="${data.access.panel_url}" target="_blank" class="text-primary text-decoration-none flex-grow-1">
                      <i class="bi bi-link-45deg me-2"></i><span class="text-white">${data.access.panel_url}</span>
                    </a>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('${data.access.panel_url}')">
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-person-circle text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">Usuario</strong>
                    <div class="text-light opacity-75 small">Tu nombre de usuario</div>
                  </div>
                </div>
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between">
                    <code class="text-white bg-transparent border border-info rounded px-3 py-2 flex-grow-1 me-2">
                      ${data.access.panel_username}
                    </code>
                    <button class="btn btn-sm btn-outline-info" onclick="copyToClipboard('${data.access.panel_username}')" title="Copiar usuario">
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-key text-dark"></i>
                  </div>
                  <div>
                    <strong class="text-white">Contraseña</strong>
                    <div class="text-light opacity-75 small">Tu contraseña de acceso</div>
                  </div>
                </div>
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between">
                    <code class="text-white bg-transparent border border-warning rounded px-3 py-2 flex-grow-1 me-2">
                      ${data.access.panel_password}
                    </code>
                    <button class="btn btn-sm btn-outline-warning" onclick="copyToClipboard('${data.access.panel_password}')" title="Copiar contraseña">
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              ${data.access.access_notes ? `
              <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-chat-square-text text-white"></i>
                  </div>
                  <div>
                    <strong class="text-white">Notas adicionales</strong>
                    <div class="text-light opacity-75 small">Información del administrador</div>
                  </div>
                </div>
                <div class="glass-card p-3">
                  <div class="text-light opacity-85">${data.access.access_notes}</div>
                </div>
              </div>
              ` : ''}
              
              <div class="col-12 text-center">
                <a href="${data.access.panel_url}" target="_blank" class="btn btn-success btn-lg">
                  <i class="bi bi-box-arrow-up-right me-2"></i>Acceder a mi panel
                </a>
              </div>
            </div>
          `;
        } else {
          console.log('No access data found');
          // No access data configured
          planAccessContent.innerHTML = `
            <div class="text-center text-light opacity-75 py-4">
              <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
              <h5 class="text-warning mb-2">Datos de acceso no configurados</h5>
              <p class="mb-0">
                El administrador aún no ha configurado los datos de acceso a tu panel personalizado.
                <br>Contacta al soporte para obtener tus credenciales.
              </p>
            </div>
          `;
        }
        
      } catch (error) {
        console.error('Error loading plan access data:', error);
        planAccessContent.innerHTML = `
          <div class="text-center text-danger py-4">
            <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar datos de acceso: ${error.message}
          </div>
        `;
      }
    }
    
    // Cancel subscription function
    window.cancelSubscription = function() {
      if (confirm('¿Estás seguro de que quieres cancelar tu suscripción?\n\nPerderás acceso a todas las funciones al final del período actual.')) {
        showAlert('info', 'Funcionalidad de cancelación en desarrollo. Contacta al soporte para cancelar tu suscripción.');
      }
    }
    
    // Dashboard functionality inline
    document.addEventListener('DOMContentLoaded', () => {
      // Init AOS
      if (window.AOS) {
        AOS.init({
          duration: 1000,
          once: true,
          offset: 100,
          easing: 'ease-out-quart'
        });
      }

      // Load dynamic pricing for plans modal
      loadDynamicPricing();

      async function loadDynamicPricing() {
        try {
          console.log('Dashboard: Loading dynamic pricing for modal...');
          const response = await fetch('api/plans.php');
          console.log('Dashboard: API response status:', response.status);
          const data = await response.json();
          console.log('Dashboard: API response data:', data);
          
          if (data.success && data.plans) {
            const clinicPlan = data.plans.find(p => p.plan_type === 'clinic');
            const startPlan = data.plans.find(p => p.plan_type === 'start');
            
            if (clinicPlan) {
              console.log('Dashboard: Clinic plan data:', clinicPlan);
              const monthlyPrice = Math.round(clinicPlan.price_monthly).toLocaleString('es-AR');
              console.log('Dashboard: Updating modal clinic price to:', monthlyPrice);
              
              const modalClinicPriceEl = document.getElementById('modalClinicPrice');
              if (modalClinicPriceEl) {
                modalClinicPriceEl.textContent = monthlyPrice;
              }
              
              // Update features if available
              if (clinicPlan.features && clinicPlan.features.length > 0) {
                const modalClinicFeaturesEl = document.getElementById('modalClinicFeatures');
                if (modalClinicFeaturesEl) {
                  modalClinicFeaturesEl.innerHTML = clinicPlan.features.map(feature => 
                    `<li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>${feature}</li>`
                  ).join('');
                  console.log('Dashboard: Updated clinic features:', clinicPlan.features);
                }
              } else {
                console.log('Dashboard: No features found for clinic plan or features array is empty');
                // Set default features if none found
                const modalClinicFeaturesEl = document.getElementById('modalClinicFeatures');
                if (modalClinicFeaturesEl) {
                  modalClinicFeaturesEl.innerHTML = `
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Hasta 3 profesionales</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Portal del paciente</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Facturación</li>
                    <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Reportes avanzados</li>
                  `;
                }
              }
            }
            
            // Update Start plan in modal if needed
            if (startPlan) {
              console.log('Dashboard: Start plan data:', startPlan);
              const startMonthlyPrice = Math.round(startPlan.price_monthly).toLocaleString('es-AR');
              console.log('Dashboard: Start plan price:', startMonthlyPrice);
              
              // Update Start plan price if element exists
              const modalStartPriceEl = document.getElementById('modalStartPrice');
              if (modalStartPriceEl) {
                modalStartPriceEl.textContent = startMonthlyPrice;
              }
              
              // Update Start plan features if available
              if (startPlan.features && startPlan.features.length > 0) {
                const modalStartFeaturesEl = document.getElementById('modalStartFeatures');
                if (modalStartFeaturesEl) {
                  modalStartFeaturesEl.innerHTML = startPlan.features.map(feature => 
                    `<li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>${feature}</li>`
                  ).join('');
                  console.log('Dashboard: Updated start features:', startPlan.features);
                }
              }
            }
            
            console.log('Dashboard: Dynamic pricing loaded successfully');
          } else {
            console.log('Dashboard: No plans data received or API error');
          }
        } catch (error) {
          console.error('Dashboard: Error loading dynamic pricing:', error);
          // Keep default prices if API fails
        }
      }

      // Start trial button functionality
      const startTrialBtn = document.getElementById('startTrialBtn');
      if (startTrialBtn) {
        startTrialBtn.addEventListener('click', async () => {
          // Disable button and show loading
          startTrialBtn.disabled = true;
          startTrialBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando solicitud...';
          
          try {
            const response = await fetch('api/trial-requests.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              }
            });
            
            const data = await response.json();
            
            if (data.error) {
              showAlert('danger', data.error);
            } else {
              showAlert('success', data.message);
              // Hide the button after successful request
              startTrialBtn.style.display = 'none';
            }
            
          } catch (error) {
            console.error('Error sending trial request:', error);
            showAlert('danger', 'Error al enviar solicitud. Por favor, intentá nuevamente.');
          } finally {
            // Re-enable button
            startTrialBtn.disabled = false;
            startTrialBtn.innerHTML = '<i class="bi bi-play-circle me-2"></i>Iniciar prueba gratuita';
          }
        });
      }

      // Plan selection functionality
      window.selectPlan = function(planType) {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('plansModal'));
        if (modal) {
          modal.hide();
        }

        // Redirect to payment page
        window.location.href = `pago.php?plan=${planType}`;
      }

      function showAlert(type, message) {
        const alertHtml = `
          <div class="alert alert-${type} alert-dismissible fade show glass-card mt-4" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'info' ? 'info-circle' : 'x-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        `;
        
        const container = document.querySelector('.container');
        if (container) {
          container.insertAdjacentHTML('beforeend', alertHtml);
          
          // Scroll to alert
          setTimeout(() => {
            const alert = document.querySelector('.alert:last-of-type');
            if (alert) {
              alert.scrollIntoView({ behavior: 'smooth' });
            }
          }, 100);
        }
      }
    });
  </script>
</body>
</html>

<?php
// Helper function for plan display names
function getPlanDisplayName($plan) {
  switch($plan) {
    case 'start': return 'Start';
    case 'clinic': return 'Clinic';
    case 'enterprise': return 'Enterprise';
    default: return 'Sin plan';
  }
}

function getPlanBadgeClass($plan) {
  switch($plan) {
    case 'start': return 'bg-info';
    case 'clinic': return 'bg-primary';
    case 'enterprise': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
}

function getStatusBadgeClass($status) {
  switch($status) {
    case 'active': return 'bg-success';
    case 'trial': return 'bg-warning text-dark';
    case 'expired': return 'bg-danger';
    case 'cancelled': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getStatusDisplayName($status) {
  switch($status) {
    case 'active': return 'Activo';
    case 'trial': return 'Prueba gratuita';
    case 'expired': return 'Vencido';
    case 'cancelled': return 'Cancelado';
    default: return 'Sin estado';
  }
}
?>