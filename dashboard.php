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
                      echo "Tu prueba gratuita está activa. Explorá todas las funciones sin límites.";
                  } elseif ($userProfile && $userProfile['subscription_status'] === 'active') {
                      echo "Tu plan está activo y funcionando perfectamente.";
                  } else {
                      echo "Bienvenido a tu panel de control.";
                  }
                  ?>
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div class="d-flex align-items-center text-light">
                    <i class="bi bi-clock me-2"></i>
                    <span>
                      <?php 
                      if ($userProfile && $userProfile['subscription_status'] === 'trial') {
                          echo "Prueba gratuita: $trialDaysRemaining días restantes";
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
        
        <button class="btn btn-primary btn-lg me-2" id="startTrialBtn">
            <i class="bi bi-play-circle me-2"></i>Iniciar prueba gratuita
        </button>

        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#plansModal">
            <i class="bi bi-star me-2"></i>Actualizar plan
        </button>

    <?php else: ?>
        <button class="btn btn-primary-soft btn-lg">
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
                  <div class="display-6 fw-bold text-white">$14.99<small class="fs-6 text-light">/mes</small></div>
                </div>
                <ul class="list-unstyled mb-4">
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>1 profesional</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Agenda & turnos</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Historia clínica</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Recordatorios</li>
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
                  <div class="display-6 fw-bold text-white">$24.99<small class="fs-6 text-light">/mes</small></div>
                </div>
                <ul class="list-unstyled mb-4">
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Hasta 3 profesionales</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Portal del paciente</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Facturación</li>
                  <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Reportes avanzados</li>
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

        // Show confirmation
        showAlert('info', `Has seleccionado el plan <strong>${getPlanName(planType)}</strong>. En una implementación real, aquí se procesaría el pago con Stripe.`);
      }

      function getPlanName(plan) {
        switch(plan) {
          case 'start': return 'Start';
          case 'clinic': return 'Clinic';
          case 'enterprise': return 'Enterprise';
          default: return 'Sin plan';
        }
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