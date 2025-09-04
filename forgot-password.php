<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['user_role'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit();
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Recuperar contraseña - DentexaPro</title>
  <meta name="description" content="Recupera tu contraseña de DentexaPro">
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
        <a href="login.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al login
        </a>
      </div>
    </div>
  </nav>

  <!-- Forgot Password Form -->
  <main class="hero section-pt pb-5 position-relative overflow-hidden">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="display-6 fw-bold text-white mb-3">
              <i class="bi bi-key me-2"></i>Recuperar <span class="gradient-text">contraseña</span>
            </h1>
            <p class="lead text-light opacity-85">
              Ingresa tu email y te enviaremos las instrucciones para restablecer tu contraseña.
            </p>
          </div>

          <!-- Forgot Password Form -->
          <div class="glass-card p-4 p-sm-5" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="300">
            <form id="forgotPasswordForm" class="row g-4">
              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
                <label class="form-label text-light">Email de tu cuenta</label>
                <div class="position-relative">
                  <input type="email" name="email" class="form-control form-control-lg glass-input" placeholder="tu@email.com" required>
                  <i class="bi bi-envelope position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                </div>
                <small class="text-light opacity-75">Te enviaremos un enlace para restablecer tu contraseña</small>
              </div>

              <div class="col-12" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="600">
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="submitBtn">
                  <i class="bi bi-send me-2"></i>Enviar instrucciones
                </button>
              </div>
            </form>

            <!-- Success/Error Messages -->
            <div id="alertContainer" class="mt-4"></div>
          </div>

          <!-- Back to Login -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <p class="text-light opacity-75">
              ¿Recordaste tu contraseña? <a href="login.php" class="text-primary">Volver al login</a>
            </p>
          </div>

          <!-- Security Info -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
            <div class="glass-card p-3">
              <p class="small text-light opacity-75 mb-2">
                <i class="bi bi-shield-check me-1"></i>Proceso seguro de recuperación
              </p>
              <div class="d-flex justify-content-center gap-3 small">
                <span class="text-light opacity-75">
                  <i class="bi bi-clock me-1"></i>Enlace válido por 1 hora
                </span>
                <span class="text-light opacity-75">
                  <i class="bi bi-envelope-check me-1"></i>Verificación por email
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

    // Forgot password form handler
    const form = document.getElementById('forgotPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alertContainer');

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
        
        // Clear previous alerts
        alertContainer.innerHTML = '';

        try {
          // Get form data
          const formData = new FormData(form);
          const email = formData.get('email');

          // Send recovery request
          const response = await fetch('api/password-recovery.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email })
          });

          const data = await response.json();

          if (data.error) {
            showAlert('danger', data.error);
            return;
          }

          // Show success message
          showAlert('success', data.message);
          
          // Reset form
          form.reset();

        } catch (error) {
          console.error('Error during password recovery:', error);
          showAlert('danger', 'Error de conexión. Por favor, intentá nuevamente.');
        } finally {
          // Re-enable submit button
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar instrucciones';
        }
      });
    }

    function showAlert(type, message) {
      const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show glass-card" role="alert">
          <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
          ${message}
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;
      alertContainer.innerHTML = alertHtml;
    }
  </script>
</body>
</html>