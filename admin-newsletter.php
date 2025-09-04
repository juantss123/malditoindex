<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get newsletter stats
$database = new Database();
$db = $database->getConnection();

try {
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
    $totalSubscribers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as active FROM newsletter_subscribers WHERE status = 'active'");
    $activeSubscribers = $stmt->fetch()['active'];
    
    $stmt = $db->query("SELECT COUNT(*) as unsubscribed FROM newsletter_subscribers WHERE status = 'unsubscribed'");
    $unsubscribedCount = $stmt->fetch()['unsubscribed'];
    
    $stmt = $db->query("SELECT COUNT(*) as campaigns FROM newsletter_campaigns");
    $totalCampaigns = $stmt->fetch()['campaigns'];
    
    // Get growth stats (last 30 days)
    $stmt = $db->query("
        SELECT COUNT(*) as new_subscribers 
        FROM newsletter_subscribers 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $newSubscribers = $stmt->fetch()['new_subscribers'];
    
} catch (Exception $e) {
    $totalSubscribers = 0;
    $activeSubscribers = 0;
    $unsubscribedCount = 0;
    $totalCampaigns = 0;
    $newSubscribers = 0;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Newsletter - DentexaPro Admin</title>
  <meta name="description" content="Gestión de newsletter del panel de administración de DentexaPro">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Quill Editor -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

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

  <!-- Admin Newsletter -->
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
                <i class="bi bi-envelope-heart me-2"></i>Newsletter
              </h1>
              <p class="text-light opacity-75 mb-0">Gestiona suscriptores y envía campañas de email marketing</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
              <i class="bi bi-plus-lg me-2"></i>Nueva campaña
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-people"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalSubscribers; ?></h3>
                <p class="text-light opacity-75 mb-0">Total suscriptores</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $activeSubscribers; ?></h3>
                <p class="text-light opacity-75 mb-0">Activos</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $newSubscribers; ?></h3>
                <p class="text-light opacity-75 mb-0">Nuevos (30 días)</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-send"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalCampaigns; ?></h3>
                <p class="text-light opacity-75 mb-0">Campañas enviadas</p>
              </div>
            </div>
          </div>

          <!-- Newsletter Tabs -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-4" id="newsletterTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="subscribers-tab" data-bs-toggle="pill" data-bs-target="#subscribers" type="button" role="tab" aria-controls="subscribers" aria-selected="true">
                  <i class="bi bi-people me-2"></i>Suscriptores
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="campaigns-tab" data-bs-toggle="pill" data-bs-target="#campaigns" type="button" role="tab" aria-controls="campaigns" aria-selected="false">
                  <i class="bi bi-send me-2"></i>Campañas
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics" aria-selected="false">
                  <i class="bi bi-graph-up me-2"></i>Analíticas
                </button>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="newsletterTabsContent">
              
              <!-- Subscribers Tab -->
              <div class="tab-pane fade show active" id="subscribers" role="tabpanel" aria-labelledby="subscribers-tab">
                <!-- Filters -->
                <div class="row g-3 mb-4">
                  <div class="col-md-4">
                    <div class="position-relative">
                      <input type="text" id="searchSubscribers" class="form-control glass-input" placeholder="Buscar por email...">
                      <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <select id="statusFilter" class="form-select glass-input">
                      <option value="">Todos los estados</option>
                      <option value="active">Activos</option>
                      <option value="unsubscribed">Desuscritos</option>
                      <option value="bounced">Rebotados</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <select id="sourceFilter" class="form-select glass-input">
                      <option value="">Todas las fuentes</option>
                      <option value="blog">Blog</option>
                      <option value="landing">Landing page</option>
                      <option value="manual">Manual</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <button class="btn btn-outline-light w-100" onclick="clearSubscriberFilters()">
                      <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                    </button>
                  </div>
                </div>

                <!-- Subscribers Table -->
                <div class="table-responsive">
                  <table class="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Email</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Fuente</th>
                        <th>Fecha suscripción</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="subscribersTable">
                      <!-- Subscribers will be loaded here -->
                    </tbody>
                  </table>
                </div>

                <!-- Export Button -->
                <div class="text-end mt-3">
                  <button class="btn btn-outline-light" onclick="exportSubscribers()">
                    <i class="bi bi-download me-2"></i>Exportar suscriptores
                  </button>
                </div>
              </div>

              <!-- Campaigns Tab -->
              <div class="tab-pane fade" id="campaigns" role="tabpanel" aria-labelledby="campaigns-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="text-white mb-0">
                    <i class="bi bi-send me-2"></i>Campañas de email
                  </h5>
                  <button class="btn btn-primary-soft" onclick="loadCampaigns()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                  </button>
                </div>

                <div class="table-responsive">
                  <table class="table table-dark table-hover">
                    <thead>
                      <tr>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Destinatarios</th>
                        <th>Enviados</th>
                        <th>Abiertos</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="campaignsTable">
                      <!-- Campaigns will be loaded here -->
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Analytics Tab -->
              <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                <div class="row g-4">
                  <!-- Growth Chart -->
                  <div class="col-lg-8">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-4">
                        <i class="bi bi-graph-up me-2"></i>Crecimiento de suscriptores
                      </h5>
                      <div style="height: 300px;">
                        <canvas id="growthChart"></canvas>
                      </div>
                    </div>
                  </div>

                  <!-- Key Metrics -->
                  <div class="col-lg-4">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-4">
                        <i class="bi bi-speedometer2 me-2"></i>Métricas clave
                      </h5>
                      
                      <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-light">Tasa de crecimiento</span>
                          <span class="text-success fw-bold" id="growthRate">+12.5%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                          <div class="progress-bar bg-success" style="width: 75%"></div>
                        </div>
                      </div>

                      <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-light">Tasa de apertura</span>
                          <span class="text-info fw-bold" id="openRate">24.8%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                          <div class="progress-bar bg-info" style="width: 60%"></div>
                        </div>
                      </div>

                      <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-light">Tasa de clics</span>
                          <span class="text-warning fw-bold" id="clickRate">3.2%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                          <div class="progress-bar bg-warning" style="width: 40%"></div>
                        </div>
                      </div>

                      <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-light">Tasa de baja</span>
                          <span class="text-danger fw-bold" id="unsubscribeRate">1.1%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                          <div class="progress-bar bg-danger" style="width: 15%"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Source Distribution -->
                  <div class="col-lg-6">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-4">
                        <i class="bi bi-pie-chart me-2"></i>Fuentes de suscripción
                      </h5>
                      <div style="height: 250px;">
                        <canvas id="sourcesChart"></canvas>
                      </div>
                    </div>
                  </div>

                  <!-- Recent Activity -->
                  <div class="col-lg-6">
                    <div class="glass-card p-4">
                      <h5 class="text-white mb-4">
                        <i class="bi bi-clock-history me-2"></i>Actividad reciente
                      </h5>
                      <div id="recentActivity">
                        <!-- Recent activity will be loaded here -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Create Campaign Modal -->
  <div class="modal fade" id="createCampaignModal" tabindex="-1" aria-labelledby="createCampaignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="createCampaignModalLabel">
            <i class="bi bi-plus-lg me-2"></i>Crear nueva campaña
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="createCampaignForm" class="row g-4">
            <div class="col-12">
              <label class="form-label text-light">Asunto del email *</label>
              <input type="text" name="subject" class="form-control form-control-lg glass-input" 
                     placeholder="Ej: Novedades de DentexaPro - Enero 2025" required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Destinatarios</label>
              <select name="recipients" class="form-select glass-input">
                <option value="all">Todos los suscriptores activos (<?php echo $activeSubscribers; ?>)</option>
                <option value="recent">Suscriptores recientes (últimos 30 días)</option>
                <option value="engaged">Suscriptores más activos</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Programar envío</label>
              <select name="schedule" class="form-select glass-input">
                <option value="now">Enviar ahora</option>
                <option value="schedule">Programar para más tarde</option>
                <option value="draft">Guardar como borrador</option>
              </select>
            </div>
            
            <div class="col-12" id="scheduleFields" style="display: none;">
              <label class="form-label text-light">Fecha y hora de envío</label>
              <input type="datetime-local" name="scheduled_at" class="form-control glass-input">
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Contenido del email *</label>
              <div id="campaignEditor" style="height: 400px; background: rgba(255,255,255,0.95); border-radius: 8px;"></div>
              <input type="hidden" name="content" id="campaignContent">
            </div>
            
            <div class="col-12">
              <div class="glass-card p-3">
                <h6 class="text-info mb-2">
                  <i class="bi bi-lightbulb me-2"></i>Plantillas rápidas
                </h6>
                <div class="d-flex gap-2 flex-wrap">
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('welcome')">
                    Bienvenida
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('update')">
                    Actualización
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('tips')">
                    Tips dentales
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('promo')">
                    Promoción
                  </button>
                </div>
              </div>
            </div>
            
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-2"></i>Crear campaña
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Subscriber Modal -->
  <div class="modal fade" id="editSubscriberModal" tabindex="-1" aria-labelledby="editSubscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="editSubscriberModalLabel">
            <i class="bi bi-pencil me-2"></i>Editar suscriptor
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="editSubscriberForm">
            <input type="hidden" id="editSubscriberId" name="subscriberId">
            
            <div class="mb-3">
              <label class="form-label text-light">Email</label>
              <input type="email" id="editSubscriberEmail" class="form-control glass-input" readonly>
            </div>
            
            <div class="mb-3">
              <label class="form-label text-light">Nombre</label>
              <input type="text" name="name" id="editSubscriberName" class="form-control glass-input">
            </div>
            
            <div class="mb-4">
              <label class="form-label text-light">Estado</label>
              <select name="status" id="editSubscriberStatus" class="form-select glass-input">
                <option value="active">Activo</option>
                <option value="unsubscribed">Desuscrito</option>
                <option value="bounced">Rebotado</option>
              </select>
            </div>
            
            <div class="text-end">
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-newsletter.js"></script>
</body>
</html>