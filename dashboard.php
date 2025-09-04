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

// Helper function to display plan names
function getPlanDisplayName($plan) {
    switch($plan) {
        case 'start': return 'Start';
        case 'clinic': return 'Clinic';
        case 'enterprise': return 'Enterprise';
        default: return 'Sin plan';
    }
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
              <h3 class="text-white mb-1">Plan activo: <?php echo $userProfile ? getPlanDisplayName($userProfile['subscription_plan']) : 'Sin plan'; ?></h3>

                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#plansModal">
                        <i class="bi bi-star me-2"></i>Actualizar plan
                    </button>
                  Suscripción activa - <?php echo getPlanDisplayName($userProfile['subscription_plan']); ?>
                <?php else: ?>
                
                    <button class="btn btn-primary-soft btn-lg" data-bs-toggle="modal" data-bs-target="#managePlanModal">
                        <i class="bi bi-gear me-2"></i>Gestionar plan
                    </button>
                <?php endif; ?>
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
                      <div class="