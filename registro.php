<?php
session_start();

// Primero, incluimos el archivo con nuestras funciones
require_once 'config/database.php';

// Ahora sí, podemos usar las funciones porque ya están definidas
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
  <title>Crear cuenta - DentexaPro</title>
  <meta name="description" content="Crea tu cuenta en DentexaPro y comienza tu prueba gratuita de 15 días">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark-ink text-body">
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

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

  <main class="hero section-pt pb-5 position-relative overflow-hidden">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="display-6 fw-bold text-white mb-3">
              Crear tu cuenta en <span class="gradient-text">DentexaPro</span>
            </h1>
            <p class="lead text-light opacity-85">
              Comenzá tu prueba gratuita de 15 días. Sin tarjeta de crédito requerida.
            </p>
          </div>

          <div class="glass-card p-4 p-sm-5" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="300">
            <form id="registrationForm" class="row g-4">
              <div class="col-12" data-aos="slide-right" data-aos-duration="800" data-aos-delay="500">
                <h3 class="text-white mb-3 fs-5">
                  <i class="bi bi-person-circle me-2"></i>Información personal
                </h3>
              </div>
              
              <div class="col-md-6" data-aos="slide-right" data-aos-duration="800" data-aos-delay="600">
                <label class="form-label text-light">Nombre *</label>
                <input type="text" name="firstName" class="form-control form-control-lg glass-input" placeholder="Tu nombre" required>
              </div>
              
              <div class="col-md-6" data-aos="slide-left" data-aos-duration="800" data-aos-delay="700">
                <label class="form-label text-light">Apellido *</label>
                <input type="text" name="lastName" class="form-control form-control-lg glass-input" placeholder="Tu apellido" required>
              </div>

              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="800">
                <label class="form-label text-light">Email *</label>
                <input type="email" name="email" class="form-control form-control-lg glass-input" placeholder="tu@email.com" required>
              </div>

              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="900">
                <label class="form-label text-light">Contraseña *</label>
                <input type="password" name="password" class="form-control form-control-lg glass-input" placeholder="Mínimo 8 caracteres" required minlength="8">
              </div>

              <div class="col-12 mt-5" data-aos="slide-left" data-aos-duration="800" data-aos-delay="1000">
                <h3 class="text-white mb-3 fs-5">
                  <i class="bi bi-briefcase-fill me-2"></i>Información profesional
                </h3>
              </div>

              <div class="col-md-6" data-aos="slide-right" data-aos-duration="800" data-aos-delay="1100">
                <label class="form-label text-light">Número de matrícula</label>
                <input type="text" name="licenseNumber" class="form-control form-control-lg glass-input" placeholder="Ej: 12345">
              </div>

              <div class="col-md-6" data-aos="slide-left" data-aos-duration="800" data-aos-delay="1200">
                <label class="form-label text-light">Teléfono *</label>
                <input type="tel" name="phone" class="form-control form-control-lg glass-input" placeholder="+54 9 11..." required>
              </div>

              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="1300">
                <label class="form-label text-light">Nombre del consultorio *</label>
                <input type="text" name="clinicName" class="form-control form-control-lg glass-input" placeholder="Ej: Consultorio Dr. González" required>
              </div>

              <div class="col-md-6" data-aos="slide-right" data-aos-duration="800" data-aos-delay="1400">
                <label class="form-label text-light">Especialidad</label>
                <select name="specialty" class="form-select form-select-lg glass-input">
                  <option value="">Seleccionar especialidad</option>
                  <option value="general">Odontología General</option>
                  <option value="ortodontia">Ortodoncia</option>
                  <option value="endodoncia">Endodoncia</option>
                  <option value="periodoncia">Periodoncia</option>
                  <option value="cirugia">Cirugía Oral</option>
                  <option value="pediatrica">Odontopediatría</option>
                  <option value="estetica">Odontología Estética</option>
                  <option value="implantes">Implantología</option>
                </select>
              </div>

              <div class="col-md-6" data-aos="slide-left" data-aos-duration="800" data-aos-delay="1500">
                <label class="form-label text-light">Tamaño del equipo</label>
                <select name="teamSize" class="form-select form-select-lg glass-input">
                  <option value="1">Solo yo</option>
                  <option value="2-3">2-3 profesionales</option>
                  <option value="4-10">4-10 profesionales</option>
                  <option value="10+">Más de 10 profesionales</option>
                </select>
              </div>

              <div class="col-12 mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1600">
                <div class="form-check mb-4">
                  <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                  <label class="form-check-label text-light" for="terms">
                    Acepto los <a href="terminos-condiciones.php" class="text-primary" target="_blank">términos y condiciones</a> y la <a href="politica-privacidad.php" class="text-primary" target="_blank">política de privacidad</a>
                  </label>
                </div>
              </div>

              <div class="col-12" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="1700">
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="submitBtn">
                  <i class="bi bi-rocket-takeoff me-2"></i>Crear mi cuenta y comenzar prueba
                </button>
                <p class="small text-center text-light opacity-75 mb-0">
                  <i class="bi bi-shield-check me-1"></i>15 días gratis • Sin tarjeta de crédito • Cancelás cuando quieras
                </p>
              </div>
            </form>

            <div id="alertContainer" class="mt-4"></div>
          </div>

          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1800">
            <p class="text-light opacity-75">
              ¿Ya tenés una cuenta? <a href="login.php" class="text-primary">Iniciar sesión</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/registration-php.js"></script>
</body>
</html>