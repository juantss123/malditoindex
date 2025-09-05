<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();

$plan = $_GET['plan'] ?? '';
$paymentId = $_GET['payment_id'] ?? '';

// Get user data
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT first_name, last_name, email 
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
} catch (Exception $e) {
    $user = null;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pago Pendiente - DentexaPro</title>
  <meta name="description" content="Tu pago está siendo procesado">
  <meta name="theme-color" content="#ffc107" />
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
      <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="dashboard.php" class="btn btn-outline-light">
          <i class="bi bi-house me-2"></i>Volver al dashboard
        </a>
      </div>
    </div>
  </nav>

  <!-- Pending Page -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
          <div class="glass-card p-5 text-center" data-aos="fade-up" data-aos-duration="800">
            <div class="mb-4">
              <i class="bi bi-clock-history text-warning" style="font-size: 4rem;"></i>
            </div>
            
            <h1 class="text-white mb-3">Pago pendiente</h1>
            
            <p class="text-light opacity-85 mb-4">
              Tu pago está siendo procesado. Esto puede tomar unos minutos. 
              Te notificaremos por email cuando se complete.
            </p>

            <div class="glass-card p-3 mb-4">
              <h5 class="text-info mb-3">
                <i class="bi bi-info-circle me-2"></i>¿Qué sigue?
              </h5>
              <ul class="list-unstyled text-start">
                <li class="mb-2">
                  <i class="bi bi-envelope text-primary me-2"></i>
                  Recibirás un email de confirmación cuando se apruebe
                </li>
                <li class="mb-2">
                  <i class="bi bi-bell text-warning me-2"></i>
                  Te notificaremos en tu dashboard
                </li>
                <li class="mb-0">
                  <i class="bi bi-clock text-info me-2"></i>
                  El proceso puede tomar hasta 24 horas
                </li>
              </ul>
            </div>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="dashboard.php" class="btn btn-primary btn-lg">
                <i class="bi bi-house me-2"></i>Ir al dashboard
              </a>
              <a href="https://wa.me/5491112345678?text=Hola%2C%20mi%20pago%20está%20pendiente%20y%20necesito%20información" 
                 target="_blank" class="btn btn-outline-success btn-lg">
                <i class="bi bi-whatsapp me-2"></i>Consultar estado
              </a>
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

    // Check payment status every 30 seconds
    const checkPaymentStatus = () => {
      fetch(`api/check-payment-status.php?payment_id=<?php echo htmlspecialchars($paymentId); ?>`)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'approved') {
            window.location.href = 'pago-exitoso.php?plan=<?php echo htmlspecialchars($plan); ?>';
          } else if (data.status === 'rejected') {
            window.location.href = 'pago-fallido.php?plan=<?php echo htmlspecialchars($plan); ?>';
          }
        })
        .catch(error => {
          console.error('Error checking payment status:', error);
        });
    };

    // Check status every 30 seconds
    setInterval(checkPaymentStatus, 30000);
  </script>
</body>
</html>