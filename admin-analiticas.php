<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get analytics data
$database = new Database();
$db = $database->getConnection();

try {
    // Get basic stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM user_profiles");
    $totalUsers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as active FROM user_profiles WHERE subscription_status = 'active'");
    $activeSubscriptions = $stmt->fetch()['active'];
    
    $stmt = $db->query("SELECT COUNT(*) as trial FROM user_profiles WHERE subscription_status = 'trial'");
    $trialUsers = $stmt->fetch()['trial'];
    
    $stmt = $db->query("SELECT COUNT(*) as expired FROM user_profiles WHERE subscription_status = 'expired'");
    $expiredUsers = $stmt->fetch()['expired'];
    
    // Calculate monthly revenue (mock calculation)
    $monthlyRevenue = $activeSubscriptions * 25000; // Average plan price
    
    // Get plan distribution
    $stmt = $db->query("
        SELECT subscription_plan as plan_type, COUNT(*) as count 
        FROM user_profiles 
        WHERE subscription_plan IS NOT NULL 
        GROUP BY subscription_plan
    ");
    $planStats = $stmt->fetchAll();
    
    // Get monthly registrations (last 6 months)
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM user_profiles 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRegistrations = $stmt->fetchAll();
    
    // Calculate conversion rate
    $totalTrialAndActive = $activeSubscriptions + $trialUsers;
    $conversionRate = $totalTrialAndActive > 0 ? ($activeSubscriptions / $totalTrialAndActive * 100) : 0;
    
} catch (Exception $e) {
    $totalUsers = 0;
    $activeSubscriptions = 0;
    $trialUsers = 0;
    $expiredUsers = 0;
    $monthlyRevenue = 0;
    $planStats = [];
    $monthlyRegistrations = [];
    $conversionRate = 0;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Analíticas - DentexaPro Admin</title>
  <meta name="description" content="Analíticas y métricas del panel de administración de DentexaPro">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

  <!-- Admin Analytics -->
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
                <i class="bi bi-graph-up me-2"></i>Analíticas y Métricas
              </h1>
              <p class="text-light opacity-75 mb-0">Monitorea el crecimiento y rendimiento de DentexaPro</p>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-light" onclick="refreshData()">
                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
              </button>
              <button class="btn btn-primary-soft" onclick="exportData()">
                <i class="bi bi-download me-2"></i>Exportar
              </button>
            </div>
          </div>

          <!-- Main Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center h-100">
                <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                  <i class="bi bi-people"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo number_format($totalUsers); ?></h3>
                <p class="text-light opacity-75 mb-2">Total usuarios</p>
                <div class="small text-success">
                  <i class="bi bi-arrow-up me-1"></i>
                  <?php echo $trialUsers; ?> en prueba
                </div>
              </div>
            </div>
            
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center h-100">
                <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                  <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo number_format($activeSubscriptions); ?></h3>
                <p class="text-light opacity-75 mb-2">Suscripciones activas</p>
                <div class="small text-info">
                  <i class="bi bi-percent me-1"></i>
                  <?php echo number_format($conversionRate, 1); ?>% conversión
                </div>
              </div>
            </div>
            
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center h-100">
                <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="text-white mb-1">$<?php echo number_format($monthlyRevenue); ?></h3>
                <p class="text-light opacity-75 mb-2">Ingresos mensuales</p>
                <div class="small text-warning">
                  <i class="bi bi-calendar me-1"></i>
                  <?php echo date('F Y'); ?>
                </div>
              </div>
            </div>
            
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center h-100">
                <div class="feature-icon mx-auto mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                  <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo number_format($conversionRate, 1); ?>%</h3>
                <p class="text-light opacity-75 mb-2">Tasa de conversión</p>
                <div class="small text-danger">
                  <i class="bi bi-trending-up me-1"></i>
                  Trial → Pago
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="row g-4 mb-5">
            <!-- Registrations Chart -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
              <div class="glass-card p-4">
                <h4 class="text-white mb-4">
                  <i class="bi bi-person-plus me-2"></i>Registros por mes
                </h4>
                <div style="height: 300px;">
                  <canvas id="registrationsChart"></canvas>
                </div>
              </div>
            </div>

            <!-- Revenue Chart -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
              <div class="glass-card p-4">
                <h4 class="text-white mb-4">
                  <i class="bi bi-cash-stack me-2"></i>Ingresos por mes
                </h4>
                <div style="height: 300px;">
                  <canvas id="revenueChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Plans Distribution and Recent Activity -->
          <div class="row g-4 mb-5">
            <!-- Plans Distribution -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
              <div class="glass-card p-4">
                <h4 class="text-white mb-4">
                  <i class="bi bi-pie-chart me-2"></i>Distribución de planes
                </h4>
                <div style="height: 300px;">
                  <canvas id="plansChart"></canvas>
                </div>
                
                <!-- Plan details -->
                <div class="mt-4">
                  <?php foreach ($planStats as $plan): ?>
                  <div class="d-flex justify-content-between align-items-center mb-2 p-2 glass-card">
                    <div class="d-flex align-items-center">
                      <div class="bg-<?php echo getPlanColor($plan['plan_type']); ?> rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                        <i class="bi bi-star text-white small"></i>
                      </div>
                      <span class="text-light">Plan <?php echo ucfirst($plan['plan_type']); ?></span>
                    </div>
                    <span class="text-white fw-bold"><?php echo $plan['count']; ?> usuarios</span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Key Metrics -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-duration="800" data-aos-delay="900">
              <div class="glass-card p-4">
                <h4 class="text-white mb-4">
                  <i class="bi bi-speedometer2 me-2"></i>Métricas clave
                </h4>
                
                <!-- Metric items -->
                <div class="row g-3">
                  <div class="col-6">
                    <div class="glass-card p-3 text-center">
                      <div class="text-primary fs-2 fw-bold"><?php echo number_format($totalUsers); ?></div>
                      <div class="text-light opacity-75 small">Total usuarios</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="glass-card p-3 text-center">
                      <div class="text-success fs-2 fw-bold"><?php echo number_format($activeSubscriptions); ?></div>
                      <div class="text-light opacity-75 small">Suscripciones activas</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="glass-card p-3 text-center">
                      <div class="text-warning fs-2 fw-bold"><?php echo number_format($trialUsers); ?></div>
                      <div class="text-light opacity-75 small">En prueba</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="glass-card p-3 text-center">
                      <div class="text-info fs-2 fw-bold">$<?php echo number_format($monthlyRevenue); ?></div>
                      <div class="text-light opacity-75 small">Ingresos estimados</div>
                    </div>
                  </div>
                </div>

                <!-- Growth indicators -->
                <div class="mt-4">
                  <h6 class="text-white mb-3">Indicadores de crecimiento</h6>
                  <div class="glass-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-light">Tasa de conversión</span>
                      <span class="text-success fw-bold"><?php echo number_format($conversionRate, 1); ?>%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-light">Usuarios activos</span>
                      <span class="text-info fw-bold"><?php echo number_format(($activeSubscriptions / max($totalUsers, 1)) * 100, 1); ?>%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-light">Retención</span>
                      <span class="text-primary fw-bold"><?php echo number_format((($totalUsers - $expiredUsers) / max($totalUsers, 1)) * 100, 1); ?>%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1000">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-clock-history me-2"></i>Actividad reciente
              </h4>
              <button class="btn btn-primary-soft" onclick="loadRecentActivity()">
                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
              </button>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Plan</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody id="recentActivityTable">
                  <!-- Content will be loaded here -->
                </tbody>
              </table>
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

    // Chart data from PHP
    const monthlyRegistrationsData = <?php echo json_encode($monthlyRegistrations); ?>;
    const planStatsData = <?php echo json_encode($planStats); ?>;

    // Chart colors
    const colors = {
      primary: '#2F96EE',
      success: '#28a745',
      info: '#17a2b8',
      warning: '#ffc107',
      danger: '#dc3545'
    };

    // Registrations Chart
    const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
    new Chart(registrationsCtx, {
      type: 'line',
      data: {
        labels: monthlyRegistrationsData.map(item => {
          const date = new Date(item.month + '-01');
          return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
          label: 'Nuevos registros',
          data: monthlyRegistrationsData.map(item => item.count),
          borderColor: colors.primary,
          backgroundColor: colors.primary + '20',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: colors.primary,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255,255,255,0.1)'
            },
            ticks: {
              color: 'rgba(255,255,255,0.7)'
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: 'rgba(255,255,255,0.7)'
            }
          }
        }
      }
    });

    // Revenue Chart (mock data based on subscriptions)
    const revenueData = monthlyRegistrationsData.map(item => {
      return Math.round(item.count * 20000 * Math.random()); // Mock revenue calculation
    });

    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
      type: 'bar',
      data: {
        labels: monthlyRegistrationsData.map(item => {
          const date = new Date(item.month + '-01');
          return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
          label: 'Ingresos (ARS)',
          data: revenueData,
          backgroundColor: colors.success + '80',
          borderColor: colors.success,
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(255,255,255,0.1)'
            },
            ticks: {
              color: 'rgba(255,255,255,0.7)',
              callback: function(value) {
                return '$' + value.toLocaleString();
              }
            }
          },
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: 'rgba(255,255,255,0.7)'
            }
          }
        }
      }
    });

    // Plans Distribution Chart
    const plansCtx = document.getElementById('plansChart').getContext('2d');
    new Chart(plansCtx, {
      type: 'doughnut',
      data: {
        labels: planStatsData.map(item => item.plan_type.charAt(0).toUpperCase() + item.plan_type.slice(1)),
        datasets: [{
          data: planStatsData.map(item => item.count),
          backgroundColor: [
            colors.info,
            colors.primary,
            colors.warning,
            colors.success,
            colors.danger
          ],
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              color: 'rgba(255,255,255,0.8)'
            }
          }
        }
      }
    });

    // Load recent activity
    function loadRecentActivity() {
      const tbody = document.getElementById('recentActivityTable');
      
      // Show loading
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-light opacity-75 py-4">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            Cargando actividad...
          </td>
        </tr>
      `;

      // Simulate recent activity with actual user data
      setTimeout(() => {
        const activities = [
          {
            user_name: 'Dr. Juan Pérez',
            action: 'Registro',
            plan_type: 'start',
            created_at: new Date().toISOString(),
            status: 'trial'
          },
          {
            user_name: 'Dr. Fernando García',
            action: 'Activación',
            plan_type: 'clinic',
            created_at: new Date(Date.now() - 86400000).toISOString(),
            status: 'active'
          },
          {
            user_name: 'Dra. María López',
            action: 'Upgrade',
            plan_type: 'enterprise',
            created_at: new Date(Date.now() - 172800000).toISOString(),
            status: 'active'
          }
        ];

        tbody.innerHTML = activities.map(activity => `
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                  <i class="bi bi-person text-white"></i>
                </div>
                <span class="text-white">${activity.user_name}</span>
              </div>
            </td>
            <td>
              <span class="badge ${getActionBadgeClass(activity.action)}">
                <i class="bi bi-${getActionIcon(activity.action)} me-1"></i>
                ${activity.action}
              </span>
            </td>
            <td>
              <span class="badge ${getPlanBadgeClass(activity.plan_type)}">
                ${getPlanName(activity.plan_type)}
              </span>
            </td>
            <td class="text-light opacity-75">
              ${new Date(activity.created_at).toLocaleDateString('es-AR')}
            </td>
            <td>
              <span class="badge ${getStatusBadgeClass(activity.status)}">
                ${getStatusName(activity.status)}
              </span>
            </td>
          </tr>
        `).join('');
      }, 1000);
    }

    // Helper functions
    function getActionBadgeClass(action) {
      switch(action) {
        case 'Registro': return 'bg-info';
        case 'Activación': return 'bg-success';
        case 'Upgrade': return 'bg-warning text-dark';
        case 'Cancelación': return 'bg-danger';
        default: return 'bg-secondary';
      }
    }

    function getActionIcon(action) {
      switch(action) {
        case 'Registro': return 'person-plus';
        case 'Activación': return 'check-circle';
        case 'Upgrade': return 'arrow-up-circle';
        case 'Cancelación': return 'x-circle';
        default: return 'circle';
      }
    }

    function getPlanBadgeClass(plan) {
      switch(plan) {
        case 'start': return 'bg-info';
        case 'clinic': return 'bg-primary';
        case 'enterprise': return 'bg-warning text-dark';
        default: return 'bg-secondary';
      }
    }

    function getPlanName(plan) {
      switch(plan) {
        case 'start': return 'Start';
        case 'clinic': return 'Clinic';
        case 'enterprise': return 'Enterprise';
        default: return 'Sin plan';
      }
    }

    function getStatusBadgeClass(status) {
      switch(status) {
        case 'active': return 'bg-success';
        case 'trial': return 'bg-warning text-dark';
        case 'expired': return 'bg-danger';
        case 'cancelled': return 'bg-secondary';
        default: return 'bg-secondary';
      }
    }

    function getStatusName(status) {
      switch(status) {
        case 'active': return 'Activo';
        case 'trial': return 'Prueba';
        case 'expired': return 'Vencido';
        case 'cancelled': return 'Cancelado';
        default: return 'Sin estado';
      }
    }

    function refreshData() {
      location.reload();
    }

    function exportData() {
      const exportData = {
        fecha: new Date().toISOString(),
        metricas: {
          totalUsuarios: <?php echo $totalUsers; ?>,
          suscripcionesActivas: <?php echo $activeSubscriptions; ?>,
          usuariosEnPrueba: <?php echo $trialUsers; ?>,
          ingresosMensuales: <?php echo $monthlyRevenue; ?>,
          tasaConversion: <?php echo number_format($conversionRate, 1); ?>
        },
        registrosPorMes: monthlyRegistrationsData,
        distribucionPlanes: planStatsData
      };

      const dataStr = JSON.stringify(exportData, null, 2);
      const dataBlob = new Blob([dataStr], {type: 'application/json'});
      const url = URL.createObjectURL(dataBlob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `analiticas-dentexapro-${new Date().toISOString().split('T')[0]}.json`;
      link.click();
      URL.revokeObjectURL(url);

      // Show success message
      const toast = document.createElement('div');
      toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success glass-card';
      toast.style.zIndex = '9999';
      toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>Datos exportados correctamente';
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.remove();
      }, 3000);
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', () => {
      loadRecentActivity();
    });

    // Auto-refresh every 5 minutes
    setInterval(loadRecentActivity, 300000);
  </script>
</body>
</html>

<?php
// Helper function for plan colors
function getPlanColor($planType) {
  switch($planType) {
    case 'start': return 'info';
    case 'clinic': return 'primary';
    case 'enterprise': return 'warning';
    default: return 'secondary';
  }
}
?>