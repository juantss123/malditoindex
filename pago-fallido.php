<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();

$plan = $_GET['plan'] ?? '';
$paymentId = $_GET['payment_id'] ?? '';
$status = $_GET['status'] ?? '';

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
  <title>Pago no completado - DentexaPro</title>
  <meta name="description" content="El pago no pudo ser procesado">
  <meta name="theme-color" content="#dc3545" />
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

  <!-- Failure Page -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
          <div class="glass-card p-5 text-center" data-aos="fade-up" data-aos-duration="800">
            <div class="mb-4">
              <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
            </div>
            
            <h1 class="text-white mb-3">Pago no completado</h1>
            
            <p class="text-light opacity-85 mb-4">
              El pago no pudo ser procesado. Esto puede deberse a varios motivos como fondos insuficientes, 
              datos incorrectos o cancelación del proceso.
            </p>

            <div class="glass-card p-3 mb-4">
              <h5 class="text-warning mb-3">
                <i class="bi bi-info-circle me-2"></i>¿Qué puedes hacer?
              </h5>
              <ul class="list-unstyled text-start">
                <li class="mb-2">
                  <i class="bi bi-check text-success me-2"></i>
                  Verificar los datos de tu tarjeta
                </li>
                <li class="mb-2">
                  <i class="bi bi-check text-success me-2"></i>
                  Asegurarte de tener fondos suficientes
                </li>
                <li class="mb-2">
                  <i class="bi bi-check text-success me-2"></i>
                  Intentar con otra tarjeta o método de pago
                </li>
                <li class="mb-0">
                  <i class="bi bi-check text-success me-2"></i>
                  Contactar a tu banco si el problema persiste
                </li>
              </ul>
            </div>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="pago.php?plan=<?php echo htmlspecialchars($plan); ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-repeat me-2"></i>Intentar nuevamente
              </a>
              <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-house me-2"></i>Volver al dashboard
              </a>
            </div>

            <!-- Alternative Payment Methods -->
            <div class="mt-4 p-3 glass-card">
              <h6 class="text-white mb-3">Métodos de pago alternativos</h6>
              <div class="d-flex gap-2 justify-content-center">
                <a href="pago.php?plan=<?php echo htmlspecialchars($plan); ?>" class="btn btn-outline-info btn-sm">
                  <i class="bi bi-bank me-2"></i>Transferencia bancaria
                </a>
                <a href="https://wa.me/5491112345678?text=Hola%2C%20tuve%20problemas%20con%20el%20pago%20y%20necesito%20ayuda" 
                   target="_blank" class="btn btn-outline-success btn-sm">
                  <i class="bi bi-whatsapp me-2"></i>Ayuda por WhatsApp
                </a>
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
  </script>
</body>
</html>