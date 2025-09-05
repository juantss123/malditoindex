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
    
    $stmt = $db->query("SELECT COUNT(*) as expired FROM user_profiles WHERE subscription_status = 'expired'");
    $expiredUsers = $stmt->fetch()['expired'];
    
} catch (Exception $e) {
    $totalUsers = 0;
    $activeSubscriptions = 0;
    $trialUsers = 0;
    $expiredUsers = 0;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Usuarios - DentexaPro Admin</title>
  <meta name="description" content="Gestión de usuarios del panel de administración de DentexaPro">
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
      <a class="navbar-brand d-flex align-items-center gap-2" href="admin.php">
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
        <?php include 'includes/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
          <!-- Header -->
          <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-down" data-aos-duration="800">
            <div>
              <h1 class="text-white mb-1">
                <i class="bi bi-people me-2"></i>Gestión de Usuarios
              </h1>
              <p class="text-light opacity-75 mb-0">Administra cuentas de usuarios, suscripciones y permisos</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
              <i class="bi bi-person-plus me-2"></i>Agregar usuario
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-people"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalUsers; ?></h3>
                <p class="text-light opacity-75 mb-0">Total usuarios</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
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
                <p class="text-light opacity-75 mb-0">En prueba</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-x-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $expiredUsers; ?></h3>
                <p class="text-light opacity-75 mb-0">Vencidos</p>
              </div>
            </div>
          </div>

          <!-- Filters and Search -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label text-light">Buscar usuario</label>
                <div class="position-relative">
                  <input type="text" id="searchInput" class="form-control glass-input" placeholder="Nombre, email o consultorio...">
                  <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label text-light">Estado</label>
                <select id="statusFilter" class="form-select glass-input">
                  <option value="">Todos los estados</option>
                  <option value="trial">En prueba</option>
                  <option value="active">Activos</option>
                  <option value="expired">Vencidos</option>
                  <option value="cancelled">Cancelados</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-light">Plan</label>
                <select id="planFilter" class="form-select glass-input">
                  <option value="">Todos los planes</option>
                  <option value="start">Start</option>
                  <option value="clinic">Clinic</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-light w-100" onclick="clearFilters()">
                  <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                </button>
              </div>
            </div>
          </div>

          <!-- Users Table -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-table me-2"></i>Lista de usuarios
              </h4>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-light" onclick="exportUsers()">
                  <i class="bi bi-download me-2"></i>Exportar
                </button>
                <button class="btn btn-primary-soft" onclick="loadUsers()">
                  <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                </button>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-person me-2"></i>Usuario
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-envelope me-2"></i>Contacto
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-building me-2"></i>Consultorio
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-credit-card me-2"></i>Plan
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-activity me-2"></i>Estado
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-calendar me-2"></i>Registro
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-gear me-2"></i>Acciones
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody id="usersTable">
                  <!-- Users will be loaded here -->
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
              <div class="text-light opacity-75 small">
                Mostrando <span id="showingCount">0</span> de <span id="totalCount">0</span> usuarios
              </div>
              <nav aria-label="Paginación de usuarios">
                <ul class="pagination pagination-sm mb-0" id="pagination">
                  <!-- Pagination will be generated here -->
                </ul>
              </nav>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
              <div class="text-light opacity-75 small">
                Mostrando <span id="showingCount">0</span> de <span id="totalCount">0</span> usuarios
              </div>
              <nav aria-label="Paginación de usuarios">
                <ul class="pagination pagination-sm mb-0" id="pagination">
                  <!-- Pagination will be generated here -->
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

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
            <div class="col-12">
              <h6 class="text-white mb-3">
                <i class="bi bi-person-circle me-2"></i>Información personal
              </h6>
            </div>
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
            
            <div class="col-12 mt-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-briefcase me-2"></i>Información profesional
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Teléfono *</label>
              <input type="tel" name="phone" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Matrícula</label>
              <input type="text" name="licenseNumber" class="form-control glass-input">
            </div>
            <div class="col-12">
              <label class="form-label text-light">Nombre del consultorio *</label>
              <input type="text" name="clinicName" class="form-control glass-input" required>
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
            <div class="col-md-6">
              <label class="form-label text-light">Tamaño del equipo</label>
              <select name="teamSize" class="form-select glass-input">
                <option value="1">Solo yo</option>
                <option value="2-3">2-3 profesionales</option>
                <option value="4-10">4-10 profesionales</option>
                <option value="10+">Más de 10 profesionales</option>
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

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="editUserModalLabel">
            <i class="bi bi-pencil me-2"></i>Editar usuario
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="editUserForm" class="row g-3">
            <input type="hidden" name="userId" id="editUserId">
            
            <div class="col-12">
              <h6 class="text-white mb-3">
                <i class="bi bi-person-circle me-2"></i>Información personal
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Nombre *</label>
              <input type="text" name="firstName" id="editFirstName" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Apellido *</label>
              <input type="text" name="lastName" id="editLastName" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Teléfono *</label>
              <input type="tel" name="phone" id="editPhone" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Matrícula</label>
              <input type="text" name="licenseNumber" id="editLicenseNumber" class="form-control glass-input">
            </div>
            
            <div class="col-12 mt-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-briefcase me-2"></i>Información profesional
              </h6>
            </div>
            <div class="col-12">
              <label class="form-label text-light">Nombre del consultorio *</label>
              <input type="text" name="clinicName" id="editClinicName" class="form-control glass-input" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Especialidad</label>
              <select name="specialty" id="editSpecialty" class="form-select glass-input">
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
            <div class="col-md-6">
              <label class="form-label text-light">Tamaño del equipo</label>
              <select name="teamSize" id="editTeamSize" class="form-select glass-input">
                <option value="1">Solo yo</option>
                <option value="2-3">2-3 profesionales</option>
                <option value="4-10">4-10 profesionales</option>
                <option value="10+">Más de 10 profesionales</option>
              </select>
            </div>
            
            <div class="col-12 mt-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-credit-card me-2"></i>Suscripción
              </h6>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Estado de suscripción</label>
              <select name="subscription_status" id="editSubscriptionStatus" class="form-select glass-input">
                <option value="trial">Prueba gratuita</option>
                <option value="active">Activo</option>
                <option value="expired">Vencido</option>
                <option value="cancelled">Cancelado</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-light">Plan</label>
              <select name="subscription_plan" id="editSubscriptionPlan" class="form-select glass-input">
                <option value="">Sin plan</option>
                <option value="start">Start</option>
                <option value="clinic">Clinic</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
            
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Guardar cambios
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- View User Modal -->
  <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="viewUserModalLabel">
            <i class="bi bi-eye me-2"></i>Detalles del usuario
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div id="userDetailsContent">
            <!-- User details will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Plan Access Modal -->
  <div class="modal fade" id="planAccessModal" tabindex="-1" aria-labelledby="planAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="planAccessModalLabel">
            <i class="bi bi-globe me-2"></i>Datos de acceso al plan
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="planAccessForm">
            <input type="hidden" id="accessUserId" name="userId">
            
            <div class="mb-4">
              <h6 class="text-white mb-3">Información del usuario</h6>
              <div class="glass-card p-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <strong class="text-light">Usuario:</strong>
                    <div class="text-white" id="accessUserName"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Email:</strong>
                    <div class="text-white" id="accessUserEmail"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Plan actual:</strong>
                    <div class="text-white" id="accessUserPlan"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Estado:</strong>
                    <div class="text-white" id="accessUserStatus"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-globe me-2"></i>Datos de acceso al panel
              </h6>
              <div class="mb-3">
                <label class="form-label text-light">URL del panel *</label>
                <input type="url" name="panel_url" id="panelUrl" class="form-control glass-input" 
                       placeholder="https://panel.dentexapro.com/cliente123" required>
                <small class="text-light opacity-75">URL donde el usuario accederá a su panel personalizado</small>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-light">Usuario de acceso *</label>
                  <input type="text" name="panel_username" id="panelUsername" class="form-control glass-input" 
                         placeholder="usuario_cliente" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Contraseña de acceso *</label>
                  <div class="input-group">
                    <input type="text" name="panel_password" id="panelPassword" class="form-control glass-input" 
                           placeholder="contraseña123" required>
                    <button type="button" class="btn btn-outline-primary" onclick="generatePassword()">
                      <i class="bi bi-arrow-clockwise"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Notas adicionales</label>
              <textarea name="access_notes" id="accessNotes" class="form-control glass-input" rows="3" 
                        placeholder="Información adicional sobre el acceso..."></textarea>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Guardar datos de acceso
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
  <script src="assets/js/admin-users.js"></script>
</body>
</html>