<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get invoicing data
$database = new Database();
$db = $database->getConnection();

try {
    // Create invoices table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            invoice_number VARCHAR(50) NOT NULL UNIQUE,
            user_id VARCHAR(36) NOT NULL,
            plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            invoice_date DATE NOT NULL,
            due_date DATE DEFAULT NULL,
            status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_by VARCHAR(36) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM invoices");
    $totalInvoices = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as paid FROM invoices WHERE status = 'paid'");
    $paidInvoices = $stmt->fetch()['paid'];
    
    $stmt = $db->query("SELECT COUNT(*) as pending FROM invoices WHERE status IN ('draft', 'sent')");
    $pendingInvoices = $stmt->fetch()['pending'];
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM invoices WHERE status = 'paid'");
    $totalRevenue = $stmt->fetch()['revenue'];
    
    // Get users with active subscriptions for invoicing
    $stmt = $db->query("
        SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name, 
               email, clinic_name, subscription_plan, subscription_status
        FROM user_profiles 
        WHERE subscription_status = 'active' AND subscription_plan IS NOT NULL
        ORDER BY first_name, last_name
    ");
    $activeUsers = $stmt->fetchAll();
    
} catch (Exception $e) {
    $totalInvoices = 0;
    $paidInvoices = 0;
    $pendingInvoices = 0;
    $totalRevenue = 0;
    $activeUsers = [];
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Facturas - DentexaPro Admin</title>
  <meta name="description" content="Gestión de facturas del panel de administración de DentexaPro">
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

  <!-- Admin Facturas -->
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
                <i class="bi bi-receipt me-2"></i>Gestión de Facturas
              </h1>
              <p class="text-light opacity-75 mb-0">Crea, gestiona y envía facturas a usuarios con suscripciones activas</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
              <i class="bi bi-plus-lg me-2"></i>Nueva factura
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-receipt"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalInvoices; ?></h3>
                <p class="text-light opacity-75 mb-0">Total facturas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $paidInvoices; ?></h3>
                <p class="text-light opacity-75 mb-0">Facturas pagadas</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $pendingInvoices; ?></h3>
                <p class="text-light opacity-75 mb-0">Pendientes</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="text-white mb-1">$<?php echo number_format($totalRevenue, 0, ',', '.'); ?></h3>
                <p class="text-light opacity-75 mb-0">Ingresos totales</p>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label text-light">Buscar factura</label>
                <div class="position-relative">
                  <input type="text" id="searchInput" class="form-control glass-input" placeholder="Número, usuario o consultorio...">
                  <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label text-light">Estado</label>
                <select id="statusFilter" class="form-select glass-input">
                  <option value="">Todos los estados</option>
                  <option value="draft">Borrador</option>
                  <option value="sent">Enviada</option>
                  <option value="paid">Pagada</option>
                  <option value="overdue">Vencida</option>
                  <option value="cancelled">Cancelada</option>
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

          <!-- Invoices Table -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-table me-2"></i>Lista de facturas
              </h4>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-light" onclick="exportInvoices()">
                  <i class="bi bi-download me-2"></i>Exportar
                </button>
                <button class="btn btn-primary-soft" onclick="loadInvoices()">
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
                        <i class="bi bi-hash me-2"></i>Número
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-person me-2"></i>Usuario
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-star me-2"></i>Plan
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-currency-dollar me-2"></i>Monto
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-calendar me-2"></i>Fecha
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-activity me-2"></i>Estado
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-gear me-2"></i>Acciones
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody id="invoicesTable">
                  <!-- Invoices will be loaded here -->
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
              <div class="text-light opacity-75 small">
                Mostrando <span id="showingCount">0</span> de <span id="totalCount">0</span> facturas
              </div>
              <nav aria-label="Paginación de facturas">
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

  <!-- Create Invoice Modal -->
  <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-labelledby="createInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="createInvoiceModalLabel">
            <i class="bi bi-plus-lg me-2"></i>Crear nueva factura
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="createInvoiceForm" class="row g-3">
            <div class="col-12">
              <h6 class="text-white mb-3">
                <i class="bi bi-person-circle me-2"></i>Información del cliente
              </h6>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Usuario *</label>
              <select name="clinic_id" id="userSelect" class="form-select glass-input" required>
                <option value="">Seleccionar usuario</option>
                <?php foreach ($activeUsers as $user): ?>
                <option value="<?php echo $user['user_id']; ?>" 
                        data-plan="<?php echo $user['subscription_plan']; ?>"
                        data-clinic="<?php echo htmlspecialchars($user['clinic_name']); ?>">
                  <?php echo htmlspecialchars($user['full_name']); ?> - <?php echo htmlspecialchars($user['clinic_name']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-12 mt-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-receipt me-2"></i>Detalles de la factura
              </h6>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Número de factura *</label>
              <input type="text" name="invoice_number" id="invoiceNumber" class="form-control glass-input" required readonly>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Fecha de factura *</label>
              <input type="date" name="invoice_date" class="form-control glass-input" required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Plan facturado *</label>
              <select name="plan_type" id="planSelect" class="form-select glass-input" required>
                <option value="">Seleccionar plan</option>
                <option value="start">Start</option>
                <option value="clinic">Clinic</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Período facturado</label>
              <select name="billing_period" class="form-select glass-input">
                <option value="monthly">Mensual</option>
                <option value="yearly">Anual</option>
              </select>
            </div>
            
            <div class="col-md-4">
              <label class="form-label text-light">Monto base (ARS) *</label>
              <input type="number" name="amount" id="baseAmount" class="form-control glass-input" step="0.01" min="0" required>
            </div>
            
            <div class="col-md-4">
              <label class="form-label text-light">IVA (21%)</label>
              <input type="number" name="tax_amount" id="taxAmount" class="form-control glass-input" step="0.01" readonly>
            </div>
            
            <div class="col-md-4">
              <label class="form-label text-light">Total *</label>
              <input type="number" name="total_amount" id="totalAmount" class="form-control glass-input" step="0.01" readonly>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Notas adicionales</label>
              <textarea name="notes" class="form-control glass-input" rows="3" placeholder="Información adicional para la factura..."></textarea>
            </div>
            
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Crear factura
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- View Invoice Modal -->
  <div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-labelledby="viewInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="viewInvoiceModalLabel">
            <i class="bi bi-eye me-2"></i>Vista previa de factura
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div id="invoicePreviewContent">
            <!-- Invoice preview will be loaded here -->
          </div>
        </div>
        <div class="modal-footer border-top border-secondary">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-info" id="downloadPdfBtn">
            <i class="bi bi-download me-2"></i>Descargar PDF
          </button>
          <button type="button" class="btn btn-success" id="sendEmailBtn">
            <i class="bi bi-envelope me-2"></i>Enviar por email
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-facturas.js"></script>
</body>
</html>