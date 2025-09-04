<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Comprobantes de Transferencia - DentexaPro Admin</title>
  <meta name="description" content="Gestión de comprobantes de transferencia del panel de administración de DentexaPro">
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
                <i class="bi bi-receipt me-2"></i>Comprobantes de Transferencia
              </h1>
              <p class="text-light opacity-75 mb-0">Revisa y aprueba los comprobantes de transferencia bancaria</p>
            </div>
            <button class="btn btn-primary-soft" onclick="loadTransferProofs()">
              <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-white mb-1" id="pendingCount">0</h3>
                <p class="text-light opacity-75 mb-0">Pendientes</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1" id="approvedCount">0</h3>
                <p class="text-light opacity-75 mb-0">Aprobados</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-x-circle"></i>
                </div>
                <h3 class="text-white mb-1" id="rejectedCount">0</h3>
                <p class="text-light opacity-75 mb-0">Rechazados</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="text-white mb-1" id="totalAmount">$0</h3>
                <p class="text-light opacity-75 mb-0">Total aprobado</p>
              </div>
            </div>
          </div>

          <!-- Transfer Proofs Table -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-table me-2"></i>Comprobantes recibidos
              </h4>
              <div class="d-flex gap-2">
                <select id="statusFilter" class="form-select glass-input" style="width: auto;">
                  <option value="">Todos los estados</option>
                  <option value="pending">Pendientes</option>
                  <option value="approved">Aprobados</option>
                  <option value="rejected">Rechazados</option>
                </select>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Archivo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="transferProofsTable">
                  <!-- Content will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Process Transfer Proof Modal -->
  <div class="modal fade" id="processTransferModal" tabindex="-1" aria-labelledby="processTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="processTransferModalLabel">
            <i class="bi bi-receipt me-2"></i>Procesar comprobante de transferencia
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="processTransferForm">
            <input type="hidden" id="proofId" name="proofId">
            
            <div class="mb-4">
              <h6 class="text-white mb-3">Información del comprobante</h6>
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
                    <strong class="text-light">Plan:</strong>
                    <div class="text-white" id="modalPlanType"></div>
                  </div>
                  <div class="col-md-6">
                    <strong class="text-light">Monto:</strong>
                    <div class="text-white" id="modalAmount"></div>
                  </div>
                  <div class="col-12">
                    <strong class="text-light">Archivo:</strong>
                    <div class="mt-2">
                      <button type="button" class="btn btn-outline-primary" id="viewFileBtn">
                        <i class="bi bi-eye me-2"></i>Ver comprobante
                      </button>
                      <button type="button" class="btn btn-outline-info" id="downloadFileBtn">
                        <i class="bi bi-download me-2"></i>Descargar
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Decisión *</label>
              <select name="status" class="form-select form-select-lg glass-input" required>
                <option value="">Seleccionar acción</option>
                <option value="approved">Aprobar transferencia</option>
                <option value="rejected">Rechazar transferencia</option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Notas del administrador</label>
              <textarea name="admin_notes" class="form-control glass-input" rows="3" placeholder="Comentarios sobre la decisión..."></textarea>
            </div>
            
            <div class="text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Procesar comprobante
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- File Preview Modal -->
  <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="filePreviewModalLabel">
            <i class="bi bi-eye me-2"></i>Vista previa del comprobante
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 text-center">
          <div id="filePreviewContent">
            <!-- File preview will be loaded here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-transfer-proofs.js"></script>
</body>
</html>