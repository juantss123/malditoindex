<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Obtener estadísticas generales
try {
    // Total de usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    // Usuarios activos (últimos 30 días)
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt->fetch()['active'];

    // Total de suscripciones
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'");
    $totalSubscriptions = $stmt->fetch()['total'];

    // Ingresos totales del mes actual
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status = 'completed'");
    $monthlyRevenue = $stmt->fetch()['total'] ?? 0;

    // Suscripciones por plan
    $stmt = $pdo->query("SELECT plan_type, COUNT(*) as count FROM subscriptions WHERE status = 'active' GROUP BY plan_type");
    $planStats = $stmt->fetchAll();

    // Registros por mes (últimos 6 meses)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRegistrations = $stmt->fetchAll();

    // Ingresos por mes (últimos 6 meses)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as revenue
        FROM payments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status = 'completed'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRevenues = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Error al obtener estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analíticas - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            border-radius: 15px;
            color: white;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.revenue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stats-card.users {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stats-card.subscriptions {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .growth-indicator {
            font-size: 0.8rem;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <div class="d-flex align-items-center mb-4">
                    <i class="fas fa-tooth text-white me-2"></i>
                    <h4 class="text-white mb-0">DenteXa Admin</h4>
                </div>
                
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-2">
                        <a href="admin-dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin-usuarios.php" class="nav-link">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin-suscripciones.php" class="nav-link">
                            <i class="fas fa-credit-card me-2"></i>Suscripciones
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin-analiticas.php" class="nav-link active">
                            <i class="fas fa-chart-bar me-2"></i>Analíticas
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="admin-configuracion.php" class="nav-link">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar me-2"></i>Analíticas y Métricas</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                        <button class="btn btn-primary" onclick="exportData()">
                            <i class="fas fa-download me-1"></i>Exportar
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Métricas principales -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card users h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-3"></i>
                                <h3 class="metric-value"><?php echo number_format($totalUsers); ?></h3>
                                <p class="metric-label mb-1">Total Usuarios</p>
                                <div class="growth-indicator">
                                    <i class="fas fa-arrow-up"></i>
                                    <?php echo $activeUsers; ?> activos (30d)
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card subscriptions h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-credit-card fa-2x mb-3"></i>
                                <h3 class="metric-value"><?php echo number_format($totalSubscriptions); ?></h3>
                                <p class="metric-label mb-1">Suscripciones Activas</p>
                                <div class="growth-indicator">
                                    <i class="fas fa-chart-line"></i>
                                    <?php echo round(($totalSubscriptions / max($totalUsers, 1)) * 100, 1); ?>% conversión
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card revenue h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                                <h3 class="metric-value">$<?php echo number_format($monthlyRevenue); ?></h3>
                                <p class="metric-label mb-1">Ingresos del Mes</p>
                                <div class="growth-indicator">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('F Y'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-percentage fa-2x mb-3"></i>
                                <h3 class="metric-value"><?php echo round(($activeUsers / max($totalUsers, 1)) * 100, 1); ?>%</h3>
                                <p class="metric-label mb-1">Usuarios Activos</p>
                                <div class="growth-indicator">
                                    <i class="fas fa-clock"></i>
                                    Últimos 30 días
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row">
                    <!-- Registros por mes -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-user-plus me-2"></i>Registros por Mes</h5>
                            <canvas id="registrationsChart" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Ingresos por mes -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>Ingresos por Mes</h5>
                            <canvas id="revenueChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Distribución de planes -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Distribución de Planes</h5>
                            <canvas id="plansChart" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Métricas detalladas -->
                    <div class="col-md-6 mb-4">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Métricas Detalladas</h5>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded mb-3">
                                        <h4 class="text-primary"><?php echo number_format($totalUsers); ?></h4>
                                        <small class="text-muted">Total Usuarios</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded mb-3">
                                        <h4 class="text-success"><?php echo number_format($activeUsers); ?></h4>
                                        <small class="text-muted">Usuarios Activos</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded mb-3">
                                        <h4 class="text-warning"><?php echo number_format($totalSubscriptions); ?></h4>
                                        <small class="text-muted">Suscripciones</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 bg-light rounded mb-3">
                                        <h4 class="text-info">$<?php echo number_format($monthlyRevenue); ?></h4>
                                        <small class="text-muted">Ingresos Mes</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Planes más populares -->
                            <h6 class="mt-4 mb-3">Planes Más Populares</h6>
                            <?php foreach ($planStats as $plan): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary"><?php echo ucfirst($plan['plan_type']); ?></span>
                                    <span class="fw-bold"><?php echo $plan['count']; ?> usuarios</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tabla de actividad reciente -->
                <div class="row">
                    <div class="col-12">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Actividad Reciente</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Acción</th>
                                            <th>Plan</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentActivity">
                                        <!-- Se carga dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos para gráficos
        const monthlyRegistrationsData = <?php echo json_encode($monthlyRegistrations); ?>;
        const monthlyRevenuesData = <?php echo json_encode($monthlyRevenues); ?>;
        const planStatsData = <?php echo json_encode($planStats); ?>;

        // Configuración de colores
        const colors = {
            primary: '#667eea',
            success: '#28a745',
            info: '#17a2b8',
            warning: '#ffc107',
            danger: '#dc3545'
        };

        // Gráfico de registros por mes
        const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
        new Chart(registrationsCtx, {
            type: 'line',
            data: {
                labels: monthlyRegistrationsData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Nuevos Registros',
                    data: monthlyRegistrationsData.map(item => item.count),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
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
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gráfico de ingresos por mes
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: monthlyRevenuesData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: monthlyRevenuesData.map(item => item.revenue || 0),
                    backgroundColor: colors.success + '80',
                    borderColor: colors.success,
                    borderWidth: 2,
                    borderRadius: 8
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
                            color: '#f0f0f0'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gráfico de distribución de planes
        const plansCtx = document.getElementById('plansChart').getContext('2d');
        new Chart(plansCtx, {
            type: 'doughnut',
            data: {
                labels: planStatsData.map(item => item.plan_type.charAt(0).toUpperCase() + item.plan_type.slice(1)),
                datasets: [{
                    data: planStatsData.map(item => item.count),
                    backgroundColor: [
                        colors.primary,
                        colors.success,
                        colors.info,
                        colors.warning,
                        colors.danger
                    ],
                    borderWidth: 0
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
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Cargar actividad reciente
        function loadRecentActivity() {
            fetch('api/get_recent_activity.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('recentActivity');
                    if (data.success && data.activities) {
                        tbody.innerHTML = data.activities.map(activity => `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        ${activity.user_name || 'Usuario'}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">${activity.action}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">${activity.plan_type || 'N/A'}</span>
                                </td>
                                <td>
                                    <small class="text-muted">${new Date(activity.created_at).toLocaleDateString('es-ES')}</small>
                                </td>
                                <td>
                                    <span class="badge ${activity.status === 'active' ? 'bg-success' : 'bg-warning'}">
                                        ${activity.status}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i>No hay actividad reciente
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading recent activity:', error);
                    document.getElementById('recentActivity').innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error al cargar actividad
                            </td>
                        </tr>
                    `;
                });
        }

        // Funciones de utilidad
        function refreshData() {
            location.reload();
        }

        function exportData() {
            // Crear datos para exportar
            const exportData = {
                fecha: new Date().toISOString(),
                metricas: {
                    totalUsuarios: <?php echo $totalUsers; ?>,
                    usuariosActivos: <?php echo $activeUsers; ?>,
                    suscripcionesActivas: <?php echo $totalSubscriptions; ?>,
                    ingresosMes: <?php echo $monthlyRevenue; ?>
                },
                registrosPorMes: monthlyRegistrationsData,
                ingresosPorMes: monthlyRevenuesData,
                distribucionPlanes: planStatsData
            };

            // Crear y descargar archivo JSON
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `analiticas-dentexa-${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            URL.revokeObjectURL(url);

            // Mostrar notificación
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check me-2"></i>Datos exportados correctamente
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentActivity();
        });

        // Auto-refresh cada 5 minutos
        setInterval(loadRecentActivity, 300000);
    </script>
</body>
</html>