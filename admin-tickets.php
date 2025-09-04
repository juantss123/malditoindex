<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get tickets stats
$database = new Database();
$db = $database->getConnection();

try {
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM support_tickets");
    $totalTickets = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as open FROM support_tickets WHERE status IN ('open', 'in_progress')");
    $openTickets = $stmt->fetch()['open'];
    
    $stmt = $db->query("SELECT COUNT(*) as resolved FROM support_tickets WHERE status = 'resolved'");
    $resolvedTickets = $stmt->fetch()['resolved'];
    
    $stmt = $db->query("SELECT COUNT(*) as urgent FROM support_tickets WHERE priority = 'urgent' AND status NOT IN ('resolved', 'closed')");
    $urgentTickets = $stmt->fetch()['urgent'];
    
    // Get admin users for assignment
    $stmt = $db->query("
        SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name
        FROM user_profiles 
        WHERE role = 'admin'
        ORDER BY first_name, last_name
    ");
    $adminUsers = $stmt->fetchAll();
    
} catch (Exception $e) {
    $totalTickets = 0;
    $openTickets = 0;
    $resolvedTickets = 0;
    $urgentTickets = 0;
    $adminUsers = [];
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tickets de Soporte - DentexaPro Admin</title>
  <meta name="description" content="Sistema de tickets de soporte del panel de administración de DentexaPro">
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

  <!-- Admin Tickets -->
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
                <i class="bi bi-headset me-2"></i>Tickets de Soporte
              </h1>
              <p class="text-light opacity-75 mb-0">Gestiona las consultas y problemas reportados por los usuarios</p>
            </div>
            <button class="btn btn-primary-soft" onclick="loadTickets()">
              <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-ticket-perforated"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalTickets; ?></h3>
                <p class="text-light opacity-75 mb-0">Total tickets</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $openTickets; ?></h3>
                <p class="text-light opacity-75 mb-0">Abiertos</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $resolvedTickets; ?></h3>
                <p class="text-light opacity-75 mb-0">Resueltos</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $urgentTickets; ?></h3>
                <p class="text-light opacity-75 mb-0">Urgentes</p>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="row g-3 align-items-end">
              <div class="col-md-3">
                <label class="form-label text-light">Buscar ticket</label>
                <div class="position-relative">
                  <input type="text" id="searchInput" class="form-control glass-input" placeholder="Número, usuario o asunto...">
                  <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                </div>
              </div>
              <div class="col-md-2">
                <label class="form-label text-light">Estado</label>
                <select id="statusFilter" class="form-select glass-input">
                  <option value="">Todos</option>
                  <option value="open">Abierto</option>
                  <option value="in_progress">En progreso</option>
                  <option value="waiting_user">Esperando usuario</option>
                  <option value="resolved">Resuelto</option>
                  <option value="closed">Cerrado</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label text-light">Prioridad</label>
                <select id="priorityFilter" class="form-select glass-input">
                  <option value="">Todas</option>
                  <option value="urgent">Urgente</option>
                  <option value="high">Alta</option>
                  <option value="medium">Media</option>
                  <option value="low">Baja</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label text-light">Categoría</label>
                <select id="categoryFilter" class="form-select glass-input">
                  <option value="">Todas</option>
                  <option value="technical">Técnico</option>
                  <option value="billing">Facturación</option>
                  <option value="feature">Funcionalidad</option>
                  <option value="bug">Error</option>
                  <option value="general">General</option>
                </select>
              </div>
              <div class="col-md-3">
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-light flex-fill" onclick="clearFilters()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                  </button>
                  <button class="btn btn-primary-soft flex-fill" onclick="exportTickets()">
                    <i class="bi bi-download me-2"></i>Exportar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Tickets Table -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-table me-2"></i>Lista de tickets
              </h4>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Ticket</th>
                    <th>Usuario</th>
                    <th>Asunto</th>
                    <th>Categoría</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Asignado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="ticketsTable">
                  <!-- Tickets will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- View Ticket Modal -->
  <div class="modal fade" id="viewTicketModal" tabindex="-1" aria-labelledby="viewTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="viewTicketModalLabel">
            <i class="bi bi-eye me-2"></i>Detalles del ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div id="ticketDetailsContent">
            <!-- Ticket details will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Ticket Modal -->
  <div class="modal fade" id="assignTicketModal" tabindex="-1" aria-labelledby="assignTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="assignTicketModalLabel">
            <i class="bi bi-person-check me-2"></i>Asignar ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="assignTicketForm">
            <input type="hidden" id="assignTicketId" name="ticketId">
            
            <div class="mb-4">
              <label class="form-label text-light">Asignar a *</label>
              <select name="assigned_to" class="form-select glass-input" required>
                <option value="">Seleccionar administrador</option>
                <?php foreach ($adminUsers as $admin): ?>
                <option value="<?php echo $admin['user_id']; ?>">
                  <?php echo htmlspecialchars($admin['full_name']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Prioridad</label>
              <select name="priority" class="form-select glass-input">
                <option value="low">Baja</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Asignar ticket
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
  <script src="assets/js/admin-tickets.js"></script>
</body>
</html>