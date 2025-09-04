<?php
session_start();
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

if (empty($token)) {
    $error = 'Token de recuperación inválido o faltante.';
} else {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Verify token
        $stmt = $db->prepare("
            SELECT pr.user_id, pr.email, pr.expires_at, pr.used,
                   up.first_name, up.last_name
            FROM password_resets pr
            JOIN user_profiles up ON pr.user_id = up.user_id
            WHERE pr.token = ? AND pr.used = FALSE
        ");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();
        
        if (!$resetData) {
            $error = 'Token de recuperación inválido o ya utilizado.';
        } elseif (strtotime($resetData['expires_at']) < time()) {
            $error = 'El token de recuperación ha expirado. Solicita uno nuevo.';
        }
        
        // Handle password reset form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $error = 'Todos los campos son requeridos.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } else {
                // Update password
                $newPasswordHash = hashPassword($newPassword);
                $stmt = $db->prepare("UPDATE user_profiles SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$newPasswordHash, $resetData['user_id']]);
                
                // Mark token as used
                $stmt = $db->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
                $stmt->execute([$token]);
                
                $success = 'Tu contraseña ha sido actualizada exitosamente. Ya puedes iniciar sesión.';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error del servidor. Por favor, intentá nuevamente más tarde.';
        error_log("Password reset error: " . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Restablecer contraseña - DentexaPro</title>
  <meta name="description" content="Restablece tu contraseña de DentexaPro">
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

  <!-- Reset Password Form -->
  <main class="hero section-pt pb-5 position-relative overflow-hidden">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="display-6 fw-bold text-white mb-3">
              <i class="bi bi-key me-2"></i>Restablecer <span class="gradient-text">contraseña</span>
            </h1>
            <p class="lead text-light opacity-85">
              Crea una nueva contraseña para tu cuenta de DentexaPro.
            </p>
          </div>

          <!-- Messages -->
          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show glass-card mb-4" role="alert" data-aos="fade-up" data-aos-duration="800">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show glass-card mb-4" role="alert" data-aos="fade-up" data-aos-duration="800">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <a href="login.php" class="btn btn-primary btn-lg">
              <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
            </a>
          </div>
          <?php else: ?>

          <!-- Reset Form -->
          <?php if (!$error): ?>
          <div class="glass-card p-4 p-sm-5" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="300">
            <div class="text-center mb-4">
              <h3 class="text-white mb-2">Hola <?php echo htmlspecialchars($resetData['first_name']); ?>!</h3>
              <p class="text-light opacity-85">Crea tu nueva contraseña segura</p>
            </div>

            <form method="POST" class="row g-4">
              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
                <label class="form-label text-light">Nueva contraseña</label>
                <div class="position-relative">
                  <input type="password" name="new_password" id="newPassword" class="form-control form-control-lg glass-input" placeholder="Mínimo 8 caracteres" required minlength="8">
                  <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" id="toggleNewPassword" style="z-index: 10;">
                    <i class="bi bi-eye" id="newPasswordIcon"></i>
                  </button>
                </div>
                <small class="text-light opacity-75">Usa al menos 8 caracteres con letras y números</small>
              </div>

              <div class="col-12" data-aos="slide-up" data-aos-duration="800" data-aos-delay="600">
                <label class="form-label text-light">Confirmar nueva contraseña</label>
                <div class="position-relative">
                  <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg glass-input" placeholder="Repetir contraseña" required minlength="8">
                  <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-light opacity-75" id="toggleConfirmPassword" style="z-index: 10;">
                    <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                  </button>
                </div>
              </div>

              <div class="col-12" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="700">
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                  <i class="bi bi-shield-check me-2"></i>Actualizar contraseña
                </button>
              </div>
            </form>
          </div>
          <?php endif; ?>
          <?php endif; ?>

          <!-- Security Notice -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
            <div class="glass-card p-3">
              <p class="small text-light opacity-75 mb-0">
                <i class="bi bi-shield-check me-1"></i>Conexión segura con cifrado SSL
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

    // Password toggle functionality
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const newPasswordIcon = document.getElementById('newPasswordIcon');

    if (toggleNewPassword && newPasswordInput && newPasswordIcon) {
      toggleNewPassword.addEventListener('click', () => {
        const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        newPasswordInput.setAttribute('type', type);
        
        if (type === 'text') {
          newPasswordIcon.classList.remove('bi-eye');
          newPasswordIcon.classList.add('bi-eye-slash');
        } else {
          newPasswordIcon.classList.remove('bi-eye-slash');
          newPasswordIcon.classList.add('bi-eye');
        }
      });
    }

    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

    if (toggleConfirmPassword && confirmPasswordInput && confirmPasswordIcon) {
      toggleConfirmPassword.addEventListener('click', () => {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        
        if (type === 'text') {
          confirmPasswordIcon.classList.remove('bi-eye');
          confirmPasswordIcon.classList.add('bi-eye-slash');
        } else {
          confirmPasswordIcon.classList.remove('bi-eye-slash');
          confirmPasswordIcon.classList.add('bi-eye');
        }
      });
    }

    // Password strength indicator
    const newPasswordInput2 = document.getElementById('newPassword');
    if (newPasswordInput2) {
      newPasswordInput2.addEventListener('input', (e) => {
        const password = e.target.value;
        const strength = calculatePasswordStrength(password);
        
        // Remove existing strength indicator
        const existingIndicator = document.querySelector('.password-strength');
        if (existingIndicator) {
          existingIndicator.remove();
        }
        
        if (password.length > 0) {
          const indicator = document.createElement('div');
          indicator.className = 'password-strength mt-2';
          indicator.innerHTML = `
            <div class="d-flex align-items-center">
              <div class="flex-grow-1 me-2">
                <div class="progress" style="height: 4px;">
                  <div class="progress-bar bg-${strength.color}" style="width: ${strength.percentage}%"></div>
                </div>
              </div>
              <small class="text-${strength.color}">${strength.text}</small>
            </div>
          `;
          e.target.parentNode.appendChild(indicator);
        }
      });
    }

    function calculatePasswordStrength(password) {
      let score = 0;
      
      if (password.length >= 8) score += 25;
      if (password.length >= 12) score += 25;
      if (/[a-z]/.test(password)) score += 10;
      if (/[A-Z]/.test(password)) score += 10;
      if (/[0-9]/.test(password)) score += 15;
      if (/[^A-Za-z0-9]/.test(password)) score += 15;
      
      if (score < 30) return { percentage: score, color: 'danger', text: 'Débil' };
      if (score < 60) return { percentage: score, color: 'warning', text: 'Regular' };
      if (score < 90) return { percentage: score, color: 'info', text: 'Buena' };
      return { percentage: score, color: 'success', text: 'Excelente' };
    }
  </script>
</body>
</html>