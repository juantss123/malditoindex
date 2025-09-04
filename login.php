<?php
session_start();
require_once 'config/database.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $redirect = isAdmin() ? 'admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit();
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iniciar sesión - DentexaPro</title>
  <meta name="description" content="Accede a tu cuenta de DentexaPro y gestiona tu consultorio dental">
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
<body class="bg-dark-ink text-body">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto">
        <a href="index.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al inicio
        </a>
      </div>
    </div>
  </nav>

  <!-- Login Form -->
  <main class="hero section-pt pb-5 position-relative overflow-hidden">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="display-6 fw-bold text-white mb-3">
              Acceder a <span class="gradient-text">DentexaPro</span>
            </h1>
            <p class="lead text-light opacity-85">
              Ingresa a tu cuenta y gestiona tu consultorio dental.
            </p>
          </div>

          <!-- Login Form -->
          <div class="glass-card p-4 p-sm-5" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="300">
            <form id="loginForm" class="row g-4">
              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
                <label class="form-label text-light">
                  <i class="bi bi-envelope me-2"></i>Email
                </label>
                <input type="email" name="email" class="form-control form-control-lg glass-input" placeholder="tu@email.com" required>
              </div>

              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="600">
                <label class="form-label text-light">
                  <i class="bi bi-lock me-2"></i>Contraseña
                </label>
                <div class="position-relative">
                  <input type="password" name="password" id="password" class="form-control form-control-lg glass-input" placeholder="Tu contraseña" required>
                  <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" id="togglePassword" style="z-index: 10;">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                  </button>
                </div>
              </div>

              <div class="col-12 d-flex justify-content-between align-items-center" data-aos="slide-up" data-aos-duration="800" data-aos-delay="700">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                  <label class="form-check-label text-light" for="rememberMe">
                    Recordarme
                  </label>
                </div>
                <a href="forgot-password.php" class="text-primary small">¿Olvidaste tu contraseña?</a>
              </div>

              <div class="col-12" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="800">
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="loginBtn">
                  <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                </button>
                
                <!-- Register Link -->
                <div class="text-center">
                  <p class="text-light opacity-75 mb-0">
                    ¿No tenés cuenta? <a href="registro.php" class="text-primary">Crear cuenta gratis</a>
                  </p>
                </div>
              </div>
            </form>

            <!-- Success/Error Messages -->
            <div id="alertContainer" class="mt-4"></div>
          </div>

          <!-- Quick Access -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1000">
            <div class="glass-card p-3">
              <p class="small text-light opacity-75 mb-2">
                <i class="bi bi-shield-check me-1"></i>Acceso seguro con cifrado SSL
              </p>
              <div class="d-flex justify-content-center gap-3 small">
                <span class="text-light opacity-75">
                  <i class="bi bi-clock me-1"></i>Disponible 24/7
                </span>
                <span class="text-light opacity-75">
                  <i class="bi bi-cloud-check me-1"></i>Sincronización automática
                </span>
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
  <script src="assets/js/login-php.js"></script>
</body>
</html>