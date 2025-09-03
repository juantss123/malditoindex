<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get user stats
$database = new Database();
$db = $database->getConnection();

try {
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_profiles");
    $totalUsers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as active FROM user_profiles WHERE subscription_status = 'active'");
    $activeSubscriptions = $stmt->fetch()['active'];
    
    $stmt = $db->query("SELECT COUNT(*) as trial FROM user_profiles WHERE subscription_status = 'trial'");
    $trialUsers = $stmt->fetch()['trial'];
    
    // Calculate revenue (mock)
    $monthlyRevenue = $activeSubscriptions * 20;
    
} catch (Exception $e) {
    $totalUsers = 0;
    $activeSubscriptions = 0;
    $trialUsers = 0;
    $monthlyRevenue = 0;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel de Administrador - DentexaPro</title>
  <meta name="description" content="Panel de administración de DentexaPro">
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
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
        <span class="badge bg-danger ms-2">Admin</span>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-outline-light">
          <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
        </a>

      </div>
    </div>
  </nav>

  <!-- Admin Dashboard -->
  <main class="section-pt pb-5">
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 mb-4">
          <div class="glass-card p-3" data-aos="slide-right" data-aos-duration="800">
            <h5 class="text-white mb-3">
              <i class="bi bi-speedometer2 me-2"></i>Panel Admin
            </h5>
            <nav class="nav flex-column">
              <a class="nav-link text-light active" href="#dashboard">
                <i class="bi bi-house me-2"></i>Dashboard
              </a>
              <a class="nav-link text-light" href="#users">
                <i class="bi bi-people me-2"></i>Usuarios
              </a>
              <a class="nav-link text-light" href="#subscriptions">
                <i class="bi bi-credit-card me-2"></i>Suscripciones
              </a>
              <a class="nav-link text-light" href="#analytics">
                <i class="bi bi-graph-up me-2"></i>Analíticas
              </a>
              <a class="nav-link text-light" href="#settings">
                <i class="bi bi-gear me-2"></i>Configuración
              </a>
            </nav>
          </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
          <!-- Header -->
          <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="800">
            <div>
              <h1 class="text-white mb-1">Panel de Administrador</h1>
              <p class="text-light opacity-75 mb-0">Gestiona usuarios, suscripciones y configuraciones del sistema</p>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-people"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalUsers; ?></h3>
                <p class="text-light opacity-75 mb-0">Usuarios totales</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-credit-card"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $activeSubscriptions; ?></h3>
                <p class="text-light opacity-75 mb-0">Suscripciones activas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $trialUsers; ?></h3>
                <p class="text-light opacity-75 mb-0">Usuarios en prueba</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="text-white mb-1">$<?php echo $monthlyRevenue; ?></h3>
                <p class="text-light opacity-75 mb-0">Ingresos mensuales</p>
              </div>
            </div>
          </div>

          <!-- Recent Users -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-person-plus me-2"></i>Usuarios registrados
              </h4>
              <button class="btn btn-primary-soft" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg me-2"></i>Agregar usuario
              </button>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="usersTable">
                  <!-- Users will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Trial Requests -->
          <div class="glass-card p-4 mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-clock-history me-2"></i>Solicitudes de prueba gratuita
              </h4>
              <button class="btn btn-primary-soft" onclick="loadTrialRequests()">
                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
              </button>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Consultorio</th>
                    <th>Fecha solicitud</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="trialRequestsTable">
                  <tr>
                    <td colspan="5" class="text-center text-light opacity-75 py-4">
                      <span class="spinner-border spinner-border-sm me-2"></span>Cargando solicitudes...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Trial Request Modal -->
  <div class="modal fade" id="trialRequestModal" tabindex="-1" aria-labelledby="trialRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="trialRequestModalLabel">
            <i class="bi bi-clock-history me-2"></i>Procesar solicitud de prueba
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="trialRequestForm">
            <input type="hidden" id="requestId" name="requestId">
            
            <div class="mb-4">
              <h6 class="text-white mb-3">Información del usuario</h6>
              <div class="glass-card p-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <strong class="text-light">Usuario:</strong>
                    <div class="text-white" id="modalUserName"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Email:</strong>
                    <div class="text-white" id="modalUserEmail"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Consultorio:</strong>
                    <div class="text-white" id="modalClinicName"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Teléfono:</strong>
                    <div class="text-white" id="modalUserPhone"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Decisión *</label>
              <select name="status" class="form-select form-select-lg glass-input" required>
                <option value="">Seleccionar acción</option>
                <option value="approved">Aprobar prueba gratuita</option>
                <option value="rejected">Rechazar solicitud</option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Notas del administrador</label>
              <textarea name="admin_notes" class="form-control glass-input" rows="3" placeholder="Comentarios opcionales sobre la decisión..."></textarea>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Procesar solicitud
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="addUserModalLabel">
            <i class="bi bi-person-plus me-2"></i>Agregar nuevo usuario
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="addUserForm" class="row g-3">
            <div class="col-md-6">
              <label class="form-label text-light">Nombre *</label>
              <input type="text" name="firstName" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Apellido *</label>
              <input type="text" name="lastName" class="form-control glass-input" required>
            </div>
            <div class="col-12">
              <label class="form-label text-light">Email *</label>
              <input type="email" name="email" class="form-control glass-input" required>
            </div>
            <div class="col-12">
              <label class="form-label text-light">Contraseña *</label>
              <input type="password" name="password" class="form-control glass-input" required minlength="8">
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Teléfono *</label>
              <input type="tel" name="phone" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Consultorio *</label>
              <input type="text" name="clinicName" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Matrícula</label>
              <input type="text" name="licenseNumber" class="form-control glass-input">
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Especialidad</label>
              <select name="specialty" class="form-select glass-input">
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
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Crear usuario
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-php.js"></script>
</body>
</html>