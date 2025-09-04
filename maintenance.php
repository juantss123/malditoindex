<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mantenimiento - DentexaPro</title>
  <meta name="description" content="DentexaPro está en mantenimiento. Volveremos pronto con mejoras.">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- App styles -->
  <link href="assets/css/styles.css" rel="stylesheet">

  <style>
    .maintenance-icon {
      font-size: 8rem;
      color: #2F96EE;
      animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.8; }
      50% { transform: scale(1.1); opacity: 1; }
    }
    
    .countdown-timer {
      background: rgba(47,150,238,0.2);
      border: 1px solid rgba(47,150,238,0.4);
      border-radius: 16px;
      padding: 2rem;
    }
    
    .countdown-item {
      text-align: center;
    }
    
    .countdown-number {
      font-size: 2.5rem;
      font-weight: bold;
      color: #68c4ff;
      display: block;
    }
    
    .countdown-label {
      color: rgba(255,255,255,0.8);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .maintenance-progress {
      height: 8px;
      background: rgba(255,255,255,0.1);
      border-radius: 4px;
      overflow: hidden;
      position: relative;
    }
    
    .maintenance-progress::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, #2F96EE, transparent);
      animation: loading 2s ease-in-out infinite;
    }
    
    @keyframes loading {
      0% { left: -100%; }
      100% { left: 100%; }
    }
  </style>
</head>
<body class="bg-dark-ink text-body min-vh-100 d-flex align-items-center">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Maintenance Page -->
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-6">
        <div class="text-center">
          <!-- Logo -->
          <div class="mb-4">
            <img src="assets/img/logo.svg" width="60" height="60" alt="DentexaPro logo" class="mb-3" />
            <h2 class="text-white">DentexaPro</h2>
          </div>

          <!-- Maintenance Icon -->
          <div class="mb-4">
            <i class="bi bi-tools maintenance-icon"></i>
          </div>

          <!-- Main Content -->
          <div class="glass-card p-4 p-sm-5 mb-4">
            <h1 class="text-white mb-4">
              <?php
              try {
                require_once 'config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                $stmt = $db->prepare("SELECT maintenance_title FROM maintenance_settings WHERE id = 1");
                $stmt->execute();
                $maintenance = $stmt->fetch();
                echo htmlspecialchars($maintenance['maintenance_title'] ?? 'Estamos mejorando DentexaPro');
              } catch (Exception $e) {
                echo 'Estamos mejorando DentexaPro';
              }
              ?>
            </h1>
            
            <p class="text-light opacity-85 lead mb-4">
              <?php
              try {
                $stmt = $db->prepare("SELECT maintenance_message FROM maintenance_settings WHERE id = 1");
                $stmt->execute();
                $maintenance = $stmt->fetch();
                echo htmlspecialchars($maintenance['maintenance_message'] ?? 'Estamos trabajando en mejoras para ofrecerte una mejor experiencia. Volveremos pronto.');
              } catch (Exception $e) {
                echo 'Estamos trabajando en mejoras para ofrecerte una mejor experiencia. Volveremos pronto.';
              }
              ?>
            </p>

            <!-- Progress Bar -->
            <div class="maintenance-progress mb-4"></div>

            <!-- Countdown Timer (if end time is set) -->
            <?php
            $endTime = null;
            try {
              $stmt = $db->prepare("SELECT maintenance_end_time FROM maintenance_settings WHERE id = 1");
              $stmt->execute();
              $maintenance = $stmt->fetch();
              $endTime = $maintenance['maintenance_end_time'] ?? null;
            } catch (Exception $e) {
              // No end time set
            }
            
            if ($endTime && strtotime($endTime) > time()):
            ?>
            <div class="countdown-timer mb-4">
              <h5 class="text-white mb-3">
                <i class="bi bi-clock me-2"></i>Tiempo estimado de finalización
              </h5>
              <div class="row g-3" id="countdown">
                <div class="col-3">
                  <div class="countdown-item">
                    <span class="countdown-number" id="days">00</span>
                    <span class="countdown-label">Días</span>
                  </div>
                </div>
                <div class="col-3">
                  <div class="countdown-item">
                    <span class="countdown-number" id="hours">00</span>
                    <span class="countdown-label">Horas</span>
                  </div>
                </div>
                <div class="col-3">
                  <div class="countdown-item">
                    <span class="countdown-number" id="minutes">00</span>
                    <span class="countdown-label">Minutos</span>
                  </div>
                </div>
                <div class="col-3">
                  <div class="countdown-item">
                    <span class="countdown-number" id="seconds">00</span>
                    <span class="countdown-label">Segundos</span>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Contact Info -->
            <div class="glass-card p-3">
              <h6 class="text-white mb-3">
                <i class="bi bi-headset me-2"></i>¿Necesitas ayuda urgente?
              </h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <a href="mailto:<?php
                  try {
                    $stmt = $db->prepare("SELECT maintenance_contact_email FROM maintenance_settings WHERE id = 1");
                    $stmt->execute();
                    $maintenance = $stmt->fetch();
                    echo htmlspecialchars($maintenance['maintenance_contact_email'] ?? 'soporte@dentexapro.com');
                  } catch (Exception $e) {
                    echo 'soporte@dentexapro.com';
                  }
                  ?>" class="btn btn-outline-light w-100">
                    <i class="bi bi-envelope me-2"></i>Enviar email
                  </a>
                </div>
                <div class="col-md-6">
                  <a href="https://wa.me/5491112345678" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-whatsapp me-2"></i>WhatsApp
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Social Links -->
          <div class="text-center">
            <p class="text-light opacity-75 mb-3">Seguinos para estar al día con las novedades:</p>
            <div class="d-flex justify-content-center gap-3">
              <a href="#" class="btn btn-outline-light btn-sm">
                <i class="bi bi-facebook"></i>
              </a>
              <a href="#" class="btn btn-outline-light btn-sm">
                <i class="bi bi-twitter"></i>
              </a>
              <a href="#" class="btn btn-outline-light btn-sm">
                <i class="bi bi-linkedin"></i>
              </a>
              <a href="#" class="btn btn-outline-light btn-sm">
                <i class="bi bi-instagram"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <?php if ($endTime && strtotime($endTime) > time()): ?>
  <script>
    // Countdown timer
    const endTime = new Date('<?php echo date('c', strtotime($endTime)); ?>').getTime();
    
    function updateCountdown() {
      const now = new Date().getTime();
      const distance = endTime - now;
      
      if (distance < 0) {
        // Countdown finished, reload page
        location.reload();
        return;
      }
      
      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);
      
      document.getElementById('days').textContent = days.toString().padStart(2, '0');
      document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
      document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
      document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    // Update countdown every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
  </script>
  <?php endif; ?>
</body>
</html>