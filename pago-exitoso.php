<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();

$plan = $_GET['plan'] ?? '';
$paymentId = $_GET['payment_id'] ?? '';
$status = $_GET['status'] ?? '';
$externalReference = $_GET['external_reference'] ?? '';

// Validate payment success
if ($status !== 'approved' || empty($paymentId)) {
    header('Location: pago-fallido.php?plan=' . $plan);
    exit();
}

// Get user data
$database = new Database();
$db = $database->getConnection();

try {
    // Verify payment and update user subscription
    $stmt = $db->prepare("
        SELECT first_name, last_name, email, clinic_name 
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Update user subscription status
    $stmt = $db->prepare("
        UPDATE user_profiles 
        SET subscription_status = 'active', subscription_plan = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$plan, $_SESSION['user_id']]);
    
    // Update payment attempt status
    $stmt = $db->prepare("
        UPDATE payment_attempts 
        SET status = 'approved' 
        WHERE external_reference = ?
    ");
    $stmt->execute([$externalReference]);
    
    // Get plan details
    $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_type = ?");
    $stmt->execute([$plan]);
    $planData = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Payment success error: " . $e->getMessage());
    header('Location: pago-fallido.php?plan=' . $plan);
    exit();
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>¡Pago Exitoso! - DentexaPro</title>
  <meta name="description" content="Tu suscripción a DentexaPro ha sido activada exitosamente">
  <meta name="theme-color" content="#28a745" />
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

  <style>
    .success-hero {
      background: linear-gradient(135deg, rgba(40,167,69,0.2), rgba(34,197,94,0.1));
      border: 1px solid rgba(40,167,69,0.3);
      border-radius: 24px;
      position: relative;
      overflow: hidden;
    }

    .success-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: conic-gradient(from 0deg, transparent, rgba(40,167,69,0.1), transparent);
      animation: success-rotate 6s linear infinite;
      z-index: -1;
    }

    @keyframes success-rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .success-icon {
      font-size: 6rem;
      color: #28a745;
      animation: success-bounce 2s ease-in-out infinite;
      filter: drop-shadow(0 10px 30px rgba(40,167,69,0.4));
    }

    @keyframes success-bounce {
      0%, 100% { 
        transform: translateY(0px) scale(1);
      }
      50% { 
        transform: translateY(-15px) scale(1.1);
      }
    }

    .confetti {
      position: absolute;
      width: 10px;
      height: 10px;
      background: #ffc107;
      animation: confetti-fall 3s ease-out infinite;
    }

    .confetti:nth-child(1) { left: 10%; animation-delay: 0s; background: #28a745; }
    .confetti:nth-child(2) { left: 20%; animation-delay: 0.5s; background: #17a2b8; }
    .confetti:nth-child(3) { left: 30%; animation-delay: 1s; background: #ffc107; }
    .confetti:nth-child(4) { left: 40%; animation-delay: 1.5s; background: #dc3545; }
    .confetti:nth-child(5) { left: 50%; animation-delay: 2s; background: #6f42c1; }
    .confetti:nth-child(6) { left: 60%; animation-delay: 0.3s; background: #fd7e14; }
    .confetti:nth-child(7) { left: 70%; animation-delay: 0.8s; background: #20c997; }
    .confetti:nth-child(8) { left: 80%; animation-delay: 1.3s; background: #e83e8c; }
    .confetti:nth-child(9) { left: 90%; animation-delay: 1.8s; background: #6610f2; }

    @keyframes confetti-fall {
      0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
      }
      100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
      }
    }

    .success-card {
      background: rgba(40,167,69,0.1);
      border: 1px solid rgba(40,167,69,0.3);
      border-radius: 16px;
      transition: all 0.3s ease;
    }

    .success-card:hover {
      background: rgba(40,167,69,0.15);
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(40,167,69,0.2);
    }

    .plan-badge {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 10px 30px rgba(40,167,69,0.3);
      animation: plan-glow 2s ease-in-out infinite;
    }

    @keyframes plan-glow {
      0%, 100% { 
        box-shadow: 0 10px 30px rgba(40,167,69,0.3);
      }
      50% { 
        box-shadow: 0 15px 40px rgba(40,167,69,0.5);
      }
    }

    .next-steps {
      background: rgba(47,150,238,0.1);
      border: 1px solid rgba(47,150,238,0.3);
      border-radius: 16px;
    }

    .step-item {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 1.5rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .step-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(47,150,238,0.2), transparent);
      transition: left 0.6s ease;
    }

    .step-item:hover::before {
      left: 100%;
    }

    .step-item:hover {
      transform: translateY(-3px);
      border-color: rgba(47,150,238,0.4);
      box-shadow: 0 10px 30px rgba(47,150,238,0.2);
    }

    .step-number {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #2F96EE, #68c4ff);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: white;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body class="bg-dark-ink text-body min-vh-100">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Confetti Animation -->
  <div class="position-fixed w-100 h-100" style="top: 0; left: 0; pointer-events: none; z-index: 1000;">
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="dashboard.php" class="btn btn-success">
          <i class="bi bi-house me-2"></i>Ir al dashboard
        </a>
      </div>
    </div>
  </nav>

  <!-- Success Page -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
          <!-- Success Hero -->
          <div class="success-hero p-5 text-center mb-5" data-aos="zoom-in" data-aos-duration="1000">
            <div class="success-icon mb-4">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            
            <h1 class="text-white mb-3">
              ¡Pago exitoso!
            </h1>
            
            <p class="text-light opacity-85 lead mb-4">
              Tu suscripción al plan <span class="plan-badge"><?php echo htmlspecialchars($planData['name'] ?? $plan); ?></span> 
              ha sido activada exitosamente.
            </p>

            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="success-card p-3">
                  <i class="bi bi-person-check text-success fs-3 mb-2"></i>
                  <div class="text-white fw-bold">Usuario</div>
                  <div class="text-light opacity-85"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="success-card p-3">
                  <i class="bi bi-building text-info fs-3 mb-2"></i>
                  <div class="text-white fw-bold">Consultorio</div>
                  <div class="text-light opacity-85"><?php echo htmlspecialchars($user['clinic_name']); ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="success-card p-3">
                  <i class="bi bi-calendar-check text-warning fs-3 mb-2"></i>
                  <div class="text-white fw-bold">Activo desde</div>
                  <div class="text-light opacity-85"><?php echo date('d/m/Y'); ?></div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-3 justify-content-center">
              <a href="dashboard.php" class="btn btn-success btn-lg">
                <i class="bi bi-speedometer2 me-2"></i>Acceder al dashboard
              </a>
              <a href="guia-usuario.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-book me-2"></i>Ver guía de usuario
              </a>
            </div>
          </div>

          <!-- Next Steps -->
          <div class="next-steps p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
            <h3 class="text-white mb-4 text-center">
              <i class="bi bi-list-check me-2"></i>Próximos pasos recomendados
            </h3>
            
            <div class="row g-4">
              <div class="col-md-6">
                <div class="step-item">
                  <div class="step-number">1</div>
                  <h5 class="text-white mb-2">Configura tu perfil</h5>
                  <p class="text-light opacity-85 mb-3">Completa la información de tu consultorio y personaliza tu experiencia.</p>
                  <a href="dashboard.php#profile" class="btn btn-primary-soft btn-sm">
                    <i class="bi bi-person-gear me-2"></i>Configurar ahora
                  </a>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="step-item">
                  <div class="step-number">2</div>
                  <h5 class="text-white mb-2">Agrega tus pacientes</h5>
                  <p class="text-light opacity-85 mb-3">Importa o crea los perfiles de tus pacientes para comenzar a gestionar citas.</p>
                  <a href="dashboard.php#patients" class="btn btn-primary-soft btn-sm">
                    <i class="bi bi-people me-2"></i>Gestionar pacientes
                  </a>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="step-item">
                  <div class="step-number">3</div>
                  <h5 class="text-white mb-2">Configura recordatorios</h5>
                  <p class="text-light opacity-85 mb-3">Activa los recordatorios automáticos por WhatsApp y email.</p>
                  <a href="dashboard.php#reminders" class="btn btn-primary-soft btn-sm">
                    <i class="bi bi-bell me-2"></i>Configurar recordatorios
                  </a>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="step-item">
                  <div class="step-number">4</div>
                  <h5 class="text-white mb-2">Explora todas las funciones</h5>
                  <p class="text-light opacity-85 mb-3">Descubre todas las herramientas disponibles en tu plan.</p>
                  <a href="guia-usuario.php" class="btn btn-primary-soft btn-sm">
                    <i class="bi bi-compass me-2"></i>Ver guía completa
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Support -->
          <div class="glass-card p-4 text-center" data-aos="fade-up" data-aos-duration="800" data-aos-delay="500">
            <h4 class="text-white mb-3">
              <i class="bi bi-headset me-2"></i>¿Necesitas ayuda para comenzar?
            </h4>
            <p class="text-light opacity-85 mb-4">
              Nuestro equipo está aquí para ayudarte a sacar el máximo provecho de DentexaPro.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="https://wa.me/5491112345678?text=Hola%2C%20acabo%20de%20suscribirme%20y%20necesito%20ayuda%20para%20comenzar" 
                 target="_blank" class="btn btn-success">
                <i class="bi bi-whatsapp me-2"></i>WhatsApp
              </a>
              <a href="mailto:soporte@dentexapro.com?subject=Ayuda para comenzar - Nuevo suscriptor" 
                 class="btn btn-outline-light">
                <i class="bi bi-envelope me-2"></i>Email
              </a>
              <a href="dashboard.php#support" class="btn btn-primary-soft">
                <i class="bi bi-ticket-perforated me-2"></i>Crear ticket
              </a>
            </div>
          </div>

          <!-- Welcome Email Notice -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="glass-card p-3">
              <p class="text-light opacity-75 mb-0">
                <i class="bi bi-envelope-check text-info me-2"></i>
                Te hemos enviado un email de bienvenida con información importante a 
                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
              </p>
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

    // Auto-redirect to dashboard after 10 seconds
    let countdown = 10;
    const redirectTimer = setInterval(() => {
      countdown--;
      if (countdown <= 0) {
        clearInterval(redirectTimer);
        window.location.href = 'dashboard.php';
      }
    }, 1000);

    // Add celebration sound effect (optional)
    document.addEventListener('DOMContentLoaded', () => {
      // Create audio context for celebration sound
      try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Simple success sound
        const playSuccessSound = () => {
          const oscillator = audioContext.createOscillator();
          const gainNode = audioContext.createGain();
          
          oscillator.connect(gainNode);
          gainNode.connect(audioContext.destination);
          
          oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
          oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
          oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.2);
          
          gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
          gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
          
          oscillator.start(audioContext.currentTime);
          oscillator.stop(audioContext.currentTime + 0.3);
        };
        
        // Play sound after a short delay
        setTimeout(playSuccessSound, 500);
        
      } catch (error) {
        // Audio not supported or blocked, continue without sound
        console.log('Audio not available');
      }
    });
  </script>
</body>
</html>