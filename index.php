<?php
session_start();
require_once 'config/database.php';

// Check if maintenance mode is enabled
$database = new Database();
$db = $database->getConnection();

// Get dynamic plans data
$plans = [];
try {
    // Create subscription_plans table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            price_monthly DECIMAL(10,2) NOT NULL,
            price_yearly DECIMAL(10,2) NOT NULL,
            features JSON NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Check if table has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM subscription_plans");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Insert default plans
        $defaultPlans = [
            [
                'plan_type' => 'start',
                'name' => 'Start',
                'price_monthly' => 14999.00,
                'price_yearly' => 11999.00,
                'features' => json_encode([
                    '1 profesional',
                    'Agenda & turnos',
                    'Historia clínica',
                    'Recordatorios'
                ])
            ],
            [
                'plan_type' => 'clinic',
                'name' => 'Clinic',
                'price_monthly' => 24999.00,
                'price_yearly' => 19999.00,
                'features' => json_encode([
                    'Hasta 3 profesionales',
                    'Portal del paciente',
                    'Facturación',
                    'Reportes avanzados'
                ])
            ],
            [
                'plan_type' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly' => 49999.00,
                'price_yearly' => 39999.00,
                'features' => json_encode([
                    'Profesionales ilimitados',
                    'Integraciones',
                    'Soporte prioritario',
                    'Entrenamiento'
                ])
            ]
        ];
        
        foreach ($defaultPlans as $plan) {
            $stmt = $db->prepare("
                INSERT INTO subscription_plans (plan_type, name, price_monthly, price_yearly, features)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $plan['plan_type'],
                $plan['name'],
                $plan['price_monthly'],
                $plan['price_yearly'],
                $plan['features']
            ]);
        }
    }
    
    // Get plans from database
    $stmt = $db->prepare("SELECT * FROM subscription_plans ORDER BY FIELD(plan_type, 'start', 'clinic', 'enterprise')");
    $stmt->execute();
    $plansData = $stmt->fetchAll();
    
    // Convert to associative array for easy access
    foreach ($plansData as $plan) {
        $plans[$plan['plan_type']] = [
            'name' => $plan['name'],
            'price_monthly' => (float) $plan['price_monthly'],
            'price_yearly' => (float) $plan['price_yearly'],
            'features' => json_decode($plan['features'], true) ?: []
        ];
    }
    
} catch (Exception $e) {
    // Fallback to default plans if database fails
    $plans = [
        'start' => [
            'name' => 'Start',
            'price_monthly' => 14999.00,
            'price_yearly' => 11999.00,
            'features' => ['1 profesional', 'Agenda & turnos', 'Historia clínica', 'Recordatorios']
        ],
        'clinic' => [
            'name' => 'Clinic',
            'price_monthly' => 24999.00,
            'price_yearly' => 19999.00,
            'features' => ['Hasta 3 profesionales', 'Portal del paciente', 'Facturación', 'Reportes avanzados']
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 49999.00,
            'price_yearly' => 39999.00,
            'features' => ['Profesionales ilimitados', 'Integraciones', 'Soporte prioritario', 'Entrenamiento']
        ]
    ];
}

try {
    $stmt = $db->prepare("SELECT maintenance_enabled FROM maintenance_settings WHERE id = 1");
    $stmt->execute();
    $maintenance = $stmt->fetch();
    
    // If maintenance is enabled and user is not admin, redirect to maintenance page
    if ($maintenance && $maintenance['maintenance_enabled'] && !isAdmin()) {
        header('Location: maintenance.php');
        exit();
    }
} catch (Exception $e) {
    // Table might not exist, continue normally
}

// If logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $redirect = isAdmin() ? 'admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DentexaPro — Gestión para dentistas por suscripción</title>
  <meta name="description" content="Vende, agenda y administra tu clínica con un CMS moderno: turnos online, historia clínica digital, recordatorios por WhatsApp/Email, odontograma, facturación y más. 15 días gratis.">
  <meta property="og:title" content="DentexaPro — Gestión para dentistas por suscripción" />
  <meta property="og:description" content="Agenda, historia clínica, recordatorios, facturación. Todo en un CMS para odontólogos." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="assets/img/og-image.png" />
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
  <!-- Promotion Banner -->
  <?php
  // Get marketing settings for promotion banner
  try {
      $stmt = $db->prepare("SELECT * FROM marketing_settings WHERE id = 1 AND promotion_enabled = TRUE");
      $stmt->execute();
      $promotion = $stmt->fetch();
  } catch (Exception $e) {
      $promotion = null;
  }
  ?>
  
  <?php if ($promotion && $promotion['promotion_text']): ?>
  <div id="promotionBanner" class="position-sticky" style="top: 0; z-index: 1030; background-color: <?php echo htmlspecialchars($promotion['promotion_bg_color']); ?>; color: <?php echo htmlspecialchars($promotion['promotion_text_color']); ?>;">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-center py-2 px-3">
        <div class="d-flex align-items-center flex-grow-1 justify-content-center">
          <i class="bi bi-megaphone me-3"></i>
          <span class="fw-medium">
            <?php echo htmlspecialchars($promotion['promotion_text']); ?>
          </span>
          <?php if ($promotion['promotion_button_text'] && $promotion['promotion_link']): ?>
          <a href="<?php echo htmlspecialchars($promotion['promotion_link']); ?>" 
             class="btn btn-light btn-sm ms-3" 
             style="color: <?php echo htmlspecialchars($promotion['promotion_bg_color']); ?>;">
            <?php echo htmlspecialchars($promotion['promotion_button_text']); ?>
          </a>
          <?php endif; ?>
        </div>
        <button type="button" class="btn-close ms-3" aria-label="Cerrar" 
                onclick="document.getElementById('promotionBanner').style.display='none'"
                style="filter: <?php echo $promotion['promotion_text_color'] === '#ffffff' ? 'invert(1)' : 'none'; ?>;">
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark <?php echo ($promotion && $promotion['promotion_text']) ? '' : 'sticky-top'; ?> glass-nav">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="#features"><i class="bi bi-grid-3x3-gap me-2"></i>Funciones</a></li>
          <li class="nav-item"><a class="nav-link" href="#pricing"><i class="bi bi-tag me-2"></i>Precios</a></li>
          <li class="nav-item"><a class="nav-link" href="blog.php"><i class="bi bi-journal-text me-2"></i>Blog</a></li>
          <li class="nav-item"><a class="nav-link" href="#faq"><i class="bi bi-question-circle me-2"></i>Preguntas</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión</a></li>
          <li class="nav-item ms-lg-3"><a class="btn btn-primary-soft" href="#cta"><i class="bi bi-rocket-takeoff me-2"></i>Probar gratis 15 días</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <?php if (isset($_GET['logout'])): ?>
  <div class="container mt-4">
    <div class="alert alert-success alert-dismissible fade show glass-card" role="alert" id="logoutMessage" style="max-width: 600px; margin: 0 auto;">
      <i class="bi bi-check-circle me-2"></i>
      Su sesión fue cerrada correctamente.
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>

  <script>
    // Auto-dismiss después de 4 segundos
    setTimeout(() => {
      const msg = document.getElementById("logoutMessage");
      if (msg) {
        const alert = bootstrap.Alert.getOrCreateInstance(msg);
        alert.close();
      }
    }, 4000);
  </script>
<?php endif; ?>

  <!-- Hero -->
  <header class="hero section-pt pb-5 position-relative overflow-hidden">
    <!-- Floating Elements -->
    <div class="floating-elements" aria-hidden="true">
      <div class="floating-icon" style="--delay: 0s; --duration: 8s;">
        <i class="bi bi-calendar-check"></i>
      </div>
      <div class="floating-icon" style="--delay: 2s; --duration: 10s;">
        <i class="bi bi-people"></i>
      </div>
      <div class="floating-icon" style="--delay: 4s; --duration: 12s;">
        <i class="bi bi-graph-up"></i>
      </div>
      <div class="floating-icon" style="--delay: 6s; --duration: 9s;">
        <i class="bi bi-shield-check"></i>
      </div>
      <div class="floating-icon" style="--delay: 1s; --duration: 11s;">
        <i class="bi bi-heart-pulse"></i>
      </div>
    </div>

    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
          <h1 class="display-5 fw-bold lh-1 text-white mb-3">
            El CMS para <span class="gradient-text animated-gradient">dentistas</span> que <span id="typingText" class="typing-text"></span>
          </h1>
          <p class="lead text-light opacity-85 mb-4">
            Agenda inteligente, historia clínica digital, recordatorios automáticos por WhatsApp/Email y facturación. Todo en una sola plataforma suscripción mensual.
          </p>
          <div class="d-flex gap-3 flex-wrap" data-aos="fade-up" data-aos-delay="300" data-aos-duration="800">
            <a href="#cta" class="btn btn-primary btn-lg btn-glow pulse-on-hover"><i class="bi bi-rocket-takeoff me-2"></i>Probar gratis 15 días</a>
            <a href="#features" class="btn btn-outline-light btn-lg btn-shimmer"><i class="bi bi-play-circle me-2"></i>Ver funciones</a>
          </div>
          <div class="mt-4 small text-light opacity-75" data-aos="fade-up" data-aos-delay="500" data-aos-duration="800">
            <i class="bi bi-shield-check me-1"></i> SSL & backups diarios • <i class="bi bi-cloud-arrow-up ms-2 me-1"></i> 100% en la nube
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
          <div class="ui-preview glass-card p-3 p-sm-4 magnetic-hover">
            <!-- CMS Preview Carousel -->
            <div id="cmsPreviewCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
              <div class="carousel-indicators">
                <button type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Dashboard"></button>
                <button type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide-to="1" aria-label="Agenda"></button>
                <button type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide-to="2" aria-label="Pacientes"></button>
                <button type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide-to="3" aria-label="Historia Clínica"></button>
                <button type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide-to="4" aria-label="Reportes"></button>
              </div>
              <div class="carousel-inner rounded-4">
                <!-- Dashboard Screenshot -->
                <div class="carousel-item active">
                  <img src="https://images.pexels.com/photos/4386467/pexels-photo-4386467.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" 
                       class="d-block w-100 rounded-4" 
                       alt="Dashboard principal de DentexaPro">
                  <div class="carousel-caption d-none d-md-block">
                    <h5 class="fw-bold">Dashboard Principal</h5>
                    <p>Vista general de tu consultorio en tiempo real</p>
                  </div>
                </div>
                
                <!-- Agenda Screenshot -->
                <div class="carousel-item">
                  <img src="https://images.pexels.com/photos/4386431/pexels-photo-4386431.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" 
                       class="d-block w-100 rounded-4" 
                       alt="Sistema de agenda de DentexaPro">
                  <div class="carousel-caption d-none d-md-block">
                    <h5 class="fw-bold">Agenda Inteligente</h5>
                    <p>Gestiona turnos y recordatorios automáticos</p>
                  </div>
                </div>
                
                <!-- Pacientes Screenshot -->
                <div class="carousel-item">
                  <img src="https://images.pexels.com/photos/4386370/pexels-photo-4386370.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" 
                       class="d-block w-100 rounded-4" 
                       alt="Gestión de pacientes en DentexaPro">
                  <div class="carousel-caption d-none d-md-block">
                    <h5 class="fw-bold">Gestión de Pacientes</h5>
                    <p>Fichas completas y historia clínica digital</p>
                  </div>
                </div>
                
                <!-- Historia Clínica Screenshot -->
                <div class="carousel-item">
                  <img src="https://images.pexels.com/photos/4386464/pexels-photo-4386464.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" 
                       class="d-block w-100 rounded-4" 
                       alt="Historia clínica digital de DentexaPro">
                  <div class="carousel-caption d-none d-md-block">
                    <h5 class="fw-bold">Historia Clínica Digital</h5>
                    <p>Odontograma y registros médicos completos</p>
                  </div>
                </div>
                
                <!-- Reportes Screenshot -->
                <div class="carousel-item">
                  <img src="https://images.pexels.com/photos/4386366/pexels-photo-4386366.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" 
                       class="d-block w-100 rounded-4" 
                       alt="Reportes y analíticas de DentexaPro">
                  <div class="carousel-caption d-none d-md-block">
                    <h5 class="fw-bold">Reportes Avanzados</h5>
                    <p>Analíticas y métricas de tu consultorio</p>
                  </div>
                </div>
              </div>
              
              <!-- Carousel Controls -->
              <button class="carousel-control-prev" type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#cmsPreviewCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
              </button>
            </div>
            
            <!-- Preview Labels -->
            <div class="text-center mt-3">
              <small class="text-light opacity-75">
                <i class="bi bi-eye me-1"></i>Vista previa del CMS DentexaPro
              </small>
          </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Trust badges -->
  <section class="py-4 border-top border-ink-subtle" data-aos="slide-right" data-aos-duration="1200">
    <div class="container">
      <div class="row text-center gy-3 align-items-center justify-content-center">
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
          <div class="trust-badge">
            <i class="bi bi-shield-check me-2"></i>ISO-like Seguridad
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300" data-aos-duration="800">
          <div class="trust-badge">
            <i class="bi bi-headset me-2"></i>Soporte en AR
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="800">
          <div class="trust-badge">
            <i class="bi bi-arrow-repeat me-2"></i>Migración guiada
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="500" data-aos-duration="800">
          <div class="trust-badge">
            <i class="bi bi-cloud-check me-2"></i>Sin instalación
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="section-py" data-aos="slide-left" data-aos-duration="1400" data-aos-offset="200">
    <div class="container">
      <!-- Decorative Grid -->
      <div class="features-grid-bg" aria-hidden="true"></div>
      
      <div class="text-center mb-5" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="300">
        <h2 class="fw-bold text-white">Todo lo que tu consultorio necesita</h2>
        <p class="text-light opacity-80 mb-0">Diseñado con y para odontólogos en Latinoamérica.</p>
      </div>
      <div class="row g-4">
        <!-- Feature cards -->
        <div class="col-md-6 col-lg-4" data-aos="slide-right" data-aos-duration="1000" data-aos-delay="500">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-calendar2-week"></i></div>
            <h3>Agenda & turnos</h3>
            <p>Calendario por profesional, recordatorios automáticos y confirmaciones en un click.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="slide-up" data-aos-duration="1000" data-aos-delay="600">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-journal-medical"></i></div>
            <h3>Historia clínica digital</h3>
            <p>Odontograma, tratamientos, notas y adjuntos (radiografías, fotos, PDFs).</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="slide-left" data-aos-duration="1000" data-aos-delay="700">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-whatsapp"></i></div>
            <h3>Recordatorios automáticos</h3>
            <p>WhatsApp/Email con texto personalizable y plantillas por tipo de turno.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="slide-right" data-aos-duration="1000" data-aos-delay="800">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-people"></i></div>
            <h3>Gestión de pacientes</h3>
            <p>Altas/bajas, datos de contacto, historial y alertas de seguimiento.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="slide-up" data-aos-duration="1000" data-aos-delay="900">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
            <h3>Facturación</h3>
            <p>Comprobantes, medios de pago, exportaciones y reportes.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="slide-left" data-aos-duration="1000" data-aos-delay="1000">
          <div class="feature-card glass-card h-100 tilt-card">
            <div class="feature-icon"><i class="bi bi-person-badge"></i></div>
            <h3>Portal del paciente</h3>
            <p>Turnos online, historial y documentación disponible 24/7.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
<!-- CTA mid -->
  <section class="section-py" id="cta" data-aos="slide-right" data-aos-duration="1300" data-aos-offset="150">
    <div class="container">
      <div class="cta-banner glass-gradient" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="400">
        <div class="row align-items-center g-4">
          <div class="col-lg-8" data-aos="slide-right" data-aos-delay="600" data-aos-duration="1000">
            <h3 class="mb-1 text-white"><i class="bi bi-rocket-takeoff me-3"></i>Comenzá hoy con <span class="gradient-text">15 días gratis</span></h3>
            <p class="mb-0 text-light opacity-85">Sin tarjetas, cancelás cuando quieras. Migración asistida incluida.</p>
          </div>
          <div class="col-lg-4 text-lg-end" data-aos="slide-left" data-aos-delay="800" data-aos-duration="1000">
            <a href="registro.php" class="btn btn-primary btn-lg"><i class="bi bi-magic me-2"></i>Crear mi cuenta</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section id="pricing" class="section-py" data-aos="slide-left" data-aos-duration="1400" data-aos-offset="200">
    <div class="container">
      <div class="text-center mb-5" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="300">
        <h2 class="fw-bold text-white">Planes simples y claros</h2>
        <p class="text-light opacity-80 mb-0">Escalá a medida que crece tu consulta.</p>
      </div>

      <div class="row g-4 align-items-stretch">
        <!-- Start Plan -->
        <div class="col-md-6 col-lg-4" data-aos="slide-right" data-aos-duration="1100" data-aos-delay="500">
          <div class="price-card glass-card h-100">
            <div class="price-badge">Popular</div>
            <h3 class="mb-2"><?php echo htmlspecialchars($plans['start']['name']); ?></h3>
            <div class="display-6 fw-bold text-white mb-3">
              $<span class="price-amount" data-monthly="<?php echo number_format($plans['start']['price_monthly'], 0, ',', '.'); ?>" data-yearly="<?php echo number_format($plans['start']['price_yearly'], 0, ',', '.'); ?>" id="startPrice"><?php echo number_format($plans['start']['price_monthly'], 0, ',', '.'); ?></span><small class="fs-6 text-light"> ARS/mes</small>
            </div>
            <ul class="list-unstyled mb-4" id="startFeatures">
              <?php foreach ($plans['start']['features'] as $feature): ?>
              <li><i class="bi bi-check2-circle me-2"></i><?php echo htmlspecialchars($feature); ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="registro.php" class="btn btn-primary w-100">Probar gratis</a>
          </div>
        </div>

        <!-- Clinic Plan -->
        <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-duration="1100" data-aos-delay="700">
          <div class="price-card glass-card h-100 border-primary">
            <div class="price-badge bg-primary">Recomendado</div>
            <h3 class="mb-2"><?php echo htmlspecialchars($plans['clinic']['name']); ?></h3>
            <div class="display-6 fw-bold text-white mb-3">
              $<span class="price-amount" data-monthly="<?php echo number_format($plans['clinic']['price_monthly'], 0, ',', '.'); ?>" data-yearly="<?php echo number_format($plans['clinic']['price_yearly'], 0, ',', '.'); ?>" id="clinicPrice"><?php echo number_format($plans['clinic']['price_monthly'], 0, ',', '.'); ?></span><small class="fs-6 text-light"> ARS/mes</small>
            </div>
            <ul class="list-unstyled mb-4" id="clinicFeatures">
              <?php foreach ($plans['clinic']['features'] as $feature): ?>
              <li><i class="bi bi-check2-circle me-2"></i><?php echo htmlspecialchars($feature); ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="registro.php" class="btn btn-primary w-100">Probar gratis</a>
          </div>
        </div>

        <!-- Enterprise Plan -->
        <div class="col-md-6 col-lg-4" data-aos="slide-left" data-aos-duration="1100" data-aos-delay="900">
          <div class="price-card glass-card h-100">
            <h3 class="mb-2"><?php echo htmlspecialchars($plans['enterprise']['name']); ?></h3>
            <div class="display-6 fw-bold text-white mb-3">
              <?php if ($plans['enterprise']['price_monthly'] > 0): ?>
              $<span class="price-amount" data-monthly="<?php echo number_format($plans['enterprise']['price_monthly'], 0, ',', '.'); ?>" data-yearly="<?php echo number_format($plans['enterprise']['price_yearly'], 0, ',', '.'); ?>" id="enterprisePrice"><?php echo number_format($plans['enterprise']['price_monthly'], 0, ',', '.'); ?></span><small class="fs-6 text-light"> ARS/mes</small>
              <?php else: ?>
              A medida
              <?php endif; ?>
            </div>
            <ul class="list-unstyled mb-4" id="enterpriseFeatures">
              <?php foreach ($plans['enterprise']['features'] as $feature): ?>
              <li><i class="bi bi-check2-circle me-2"></i><?php echo htmlspecialchars($feature); ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="https://wa.me/5491112345678?text=Hola%2C%20me%20interesa%20el%20plan%20Enterprise%20de%20DentexaPro" target="_blank" rel="noopener" class="btn btn-outline-light w-100">Solicitar cotización</a>
          </div>
        </div>
      </div>

      <div class="text-center mt-4" data-aos="flip-up" data-aos-delay="1100" data-aos-duration="800">
        <div class="form-check form-switch d-inline-flex align-items-center gap-2 text-light">
          <input class="form-check-input" type="checkbox" role="switch" id="billingToggle">
          <label class="form-check-label" for="billingToggle">Facturación anual con descuento</label>
        </div>
      </div>
    </div>
  </section>

  <!-- Reviews/Testimonials -->
  <section class="section-py" data-aos="slide-up" data-aos-duration="1400" data-aos-offset="200">
    <div class="container">
      <div class="text-center mb-5" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="300">
        <h2 class="fw-bold text-white">Lo que dicen nuestros colegas</h2>
        <p class="text-light opacity-80 mb-0">Odontólogos de toda Latinoamérica confían en DentexaPro.</p>
      </div>
      
      <!-- Desktop: Grid layout -->
      <div class="row g-4 d-none d-md-flex">
        <!-- Review 1 -->
        <div class="col-md-4" data-aos="slide-up" data-aos-duration="1000" data-aos-delay="400">
          <div class="review-card glass-card h-100 p-4">
            <div class="d-flex align-items-center mb-3">
              <img src="https://images.pexels.com/photos/5215024/pexels-photo-5215024.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                   class="review-avatar me-3" alt="Dra. María González">
              <div>
                <h5 class="text-white mb-1">Dra. María González</h5>
                <p class="text-light opacity-75 mb-0 small">Ortodoncista • Buenos Aires</p>
              </div>
            </div>
            <div class="review-stars mb-3">
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
            </div>
            <blockquote class="text-light opacity-85 mb-0">
              "DentexaPro revolucionó mi consultorio. Los recordatorios automáticos redujeron las ausencias en un 80% y la historia clínica digital me ahorra horas cada día."
            </blockquote>
          </div>
        </div>

        <!-- Review 2 -->
        <div class="col-md-4" data-aos="slide-up" data-aos-duration="1000" data-aos-delay="500">
          <div class="review-card glass-card h-100 p-4">
            <div class="d-flex align-items-center mb-3">
              <img src="https://images.pexels.com/photos/6749778/pexels-photo-6749778.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                   class="review-avatar me-3" alt="Dr. Carlos Mendoza">
              <div>
                <h5 class="text-white mb-1">Dr. Carlos Mendoza</h5>
                <p class="text-light opacity-75 mb-0 small">Implantólogo • México DF</p>
              </div>
            </div>
            <div class="review-stars mb-3">
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
            </div>
            <blockquote class="text-light opacity-85 mb-0">
              "La facturación integrada y el portal del paciente me permitieron digitalizar completamente mi práctica. Mis pacientes aman poder agendar turnos online."
            </blockquote>
          </div>
        </div>

        <!-- Review 3 -->
        <div class="col-md-4" data-aos="slide-up" data-aos-duration="1000" data-aos-delay="600">
          <div class="review-card glass-card h-100 p-4">
            <div class="d-flex align-items-center mb-3">
              <img src="https://images.pexels.com/photos/5452293/pexels-photo-5452293.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                   class="review-avatar me-3" alt="Dra. Ana Rodríguez">
              <div>
                <h5 class="text-white mb-1">Dra. Ana Rodríguez</h5>
                <p class="text-light opacity-75 mb-0 small">Endodoncista • Bogotá</p>
              </div>
            </div>
            <div class="review-stars mb-3">
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
              <i class="bi bi-star-fill text-warning"></i>
            </div>
            <blockquote class="text-light opacity-85 mb-0">
              "El soporte es excepcional y la migración desde mi sistema anterior fue súper fácil. No puedo imaginar trabajar sin DentexaPro ahora."
            </blockquote>
          </div>
        </div>
      </div>



      <!-- Mobile: Carousel slider -->
      <div class="d-md-none">
        <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
          <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
              <div class="review-card glass-card p-4 mx-2">
                <div class="d-flex align-items-center mb-3">
                  <img src="https://images.pexels.com/photos/5215024/pexels-photo-5215024.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                       class="review-avatar me-3" alt="Dra. María González">
                  <div>
                    <h5 class="text-white mb-1">Dra. María González</h5>
                    <p class="text-light opacity-75 mb-0 small">Ortodoncista • Buenos Aires</p>
                  </div>
                </div>
                <div class="review-stars mb-3">
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
                <blockquote class="text-light opacity-85 mb-0">
                  "DentexaPro revolucionó mi consultorio. Los recordatorios automáticos redujeron las ausencias en un 80% y la historia clínica digital me ahorra horas cada día."
                </blockquote>
              </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
              <div class="review-card glass-card p-4 mx-2">
                <div class="d-flex align-items-center mb-3">
                  <img src="https://images.pexels.com/photos/6749778/pexels-photo-6749778.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                       class="review-avatar me-3" alt="Dr. Carlos Mendoza">
                  <div>
                    <h5 class="text-white mb-1">Dr. Carlos Mendoza</h5>
                    <p class="text-light opacity-75 mb-0 small">Implantólogo • México DF</p>
                  </div>
                </div>
                <div class="review-stars mb-3">
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
                <blockquote class="text-light opacity-85 mb-0">
                  "La facturación integrada y el portal del paciente me permitieron digitalizar completamente mi práctica. Mis pacientes aman poder agendar turnos online."
                </blockquote>
              </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="carousel-item">
              <div class="review-card glass-card p-4 mx-2">
                <div class="d-flex align-items-center mb-3">
                  <img src="https://images.pexels.com/photos/5452293/pexels-photo-5452293.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop&crop=face" 
                       class="review-avatar me-3" alt="Dra. Ana Rodríguez">
                  <div>
                    <h5 class="text-white mb-1">Dra. Ana Rodríguez</h5>
                    <p class="text-light opacity-75 mb-0 small">Endodoncista • Bogotá</p>
                  </div>
                </div>
                <div class="review-stars mb-3">
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
                <blockquote class="text-light opacity-85 mb-0">
                  "El soporte es excepcional y la migración desde mi sistema anterior fue súper fácil. No puedo imaginar trabajar sin DentexaPro ahora."
                </blockquote>
              </div>
            </div>
          </div>
          
          <!-- Carousel indicators -->
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Testimonio 1"></button>
            <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="1" aria-label="Testimonio 2"></button>
            <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="2" aria-label="Testimonio 3"></button>
          </div>
          
          <!-- Carousel controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>

      <!-- Trust indicators -->
      <div class="text-center mt-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1000">
        <div class="glass-card p-4">
          <div class="row g-4 align-items-center justify-content-center">
            <div class="col-6 col-md-3">
              <div class="text-center">
                <h4 class="text-white mb-1">500+</h4>
                <p class="text-light opacity-75 mb-0 small">Odontólogos activos</p>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-center">
                <h4 class="text-white mb-1">15K+</h4>
                <p class="text-light opacity-75 mb-0 small">Pacientes gestionados</p>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-center">
                <h4 class="text-white mb-1">98%</h4>
                <p class="text-light opacity-75 mb-0 small">Satisfacción</p>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-center">
                <h4 class="text-white mb-1">4.9★</h4>
                <p class="text-light opacity-75 mb-0 small">Calificación promedio</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="section-py-sm" data-aos="slide-right" data-aos-duration="1500" data-aos-offset="150">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-6" data-aos="slide-right" data-aos-duration="1000" data-aos-delay="300">
          <h2 class="fw-bold text-white mb-2" data-aos="slide-right" data-aos-delay="500" data-aos-duration="1000">Preguntas frecuentes</h2>
          <p class="text-light opacity-85 mb-4" data-aos="slide-right" data-aos-delay="700" data-aos-duration="1000">Transparencia total: sin costos ocultos, ni instalaciones. 100% web.</p>
          <div class="accordion" id="faqAcc">
            <div class="accordion-item glass-card" data-aos="slide-right" data-aos-delay="900" data-aos-duration="800">
              <h2 class="accordion-header" id="q1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="true" aria-controls="a1">
                  <i class="bi bi-gift me-2"></i>¿Cómo funciona la prueba gratis?
                </button>
              </h2>
              <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
                <div class="accordion-body">
                  Tenés 15 días con todas las funciones. Si te gusta, agregás tu medio de pago y seguís sin perder datos.
                </div>
              </div>
            </div>
            <div class="accordion-item glass-card" data-aos="slide-right" data-aos-delay="1100" data-aos-duration="800">
              <h2 class="accordion-header" id="q2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">
                  <i class="bi bi-arrow-repeat me-2"></i>¿Puedo migrar desde otro sistema?
                </button>
              </h2>
              <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                <div class="accordion-body">
                  Sí. Te acompañamos paso a paso para importar pacientes, turnos e historial clínico desde Excel/CSV u otros formatos.
                </div>
              </div>
            </div>
            <div class="accordion-item glass-card" data-aos="slide-right" data-aos-delay="1300" data-aos-duration="800">
              <h2 class="accordion-header" id="q3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3" aria-expanded="false" aria-controls="a3">
                  <i class="bi bi-shield-check me-2"></i>¿Cumple con seguridad y privacidad?
                </button>
              </h2>
              <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                <div class="accordion-body">
                  Cifrado SSL, backups diarios y control de accesos por rol. Tus datos son tuyos, podés exportarlos cuando quieras.
                </div>
              </div>
            </div>
            <div class="accordion-item glass-card" data-aos="slide-right" data-aos-delay="1500" data-aos-duration="800">
              <h2 class="accordion-header" id="q4">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4" aria-expanded="false" aria-controls="a4">
                  <i class="bi bi-cloud me-2"></i>¿Necesito descargar una aplicación?
                </button>
              </h2>
              <div id="a4" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                <div class="accordion-body">
                  No, DentexaPro funciona 100% desde el navegador. Podés acceder desde cualquier dispositivo: computadora, tablet o celular, sin instalar nada.
                </div>
              </div>
            </div>
            <div class="accordion-item glass-card" data-aos="slide-right" data-aos-delay="1700" data-aos-duration="800">
              <h2 class="accordion-header" id="q5">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a5" aria-expanded="false" aria-controls="a5">
                  <i class="bi bi-globe me-2"></i>¿Puedo usar mi propio dominio?
                </button>
              </h2>
              <div id="a5" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                <div class="accordion-body">
                  Sí, podés configurar tu dominio personalizado como "dragonzalezodontologia.com" para que tus pacientes accedan con tu marca profesional.
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="slide-left" data-aos-duration="1000" data-aos-delay="400" id="contacto">
          <div class="glass-card p-4 p-sm-4 h-100" data-aos="zoom-in" data-aos-delay="600" data-aos-duration="1000">
            <h3 class="text-white mb-2" data-aos="slide-left" data-aos-delay="800" data-aos-duration="800">Solicitar demo</h3>
            <p class="text-light opacity-85 mb-3" data-aos="slide-left" data-aos-delay="1000" data-aos-duration="800">Dejanos tus datos y coordinamos una demostración guiada.</p>
            <form class="row g-3">
              <div class="col-md-6" data-aos="slide-right" data-aos-delay="1200" data-aos-duration="600">
                <label class="form-label text-light">Nombre</label>
                <input type="text" class="form-control form-control-lg glass-input" placeholder="Tu nombre">
              </div>
              <div class="col-md-6" data-aos="slide-left" data-aos-delay="1300" data-aos-duration="600">
                <label class="form-label text-light">Email</label>
                <input type="email" class="form-control form-control-lg glass-input" placeholder="tu@email.com">
              </div>
              <div class="col-md-6" data-aos="slide-right" data-aos-delay="1400" data-aos-duration="600">
                <label class="form-label text-light">Teléfono</label>
                <input type="tel" class="form-control form-control-lg glass-input" placeholder="+54 9 11...">
              </div>
              <div class="col-md-6" data-aos="slide-left" data-aos-delay="1500" data-aos-duration="600">
                <label class="form-label text-light">Tamaño del equipo</label>
                <select class="form-select form-select-lg glass-input">
                  <option>1 profesional</option>
                  <option>2–3 profesionales</option>
                  <option>4–10 profesionales</option>
                  <option>+10 profesionales</option>
                </select>
              </div>
              <div class="col-12" data-aos="slide-up" data-aos-delay="1600" data-aos-duration="600">
                <label class="form-label text-light">Mensaje</label>
                <textarea class="form-control form-control-lg glass-input" rows="3" placeholder="Contanos tu caso"></textarea>
              </div>
              <div class="col-12 d-grid d-sm-flex gap-3" data-aos="zoom-in-up" data-aos-delay="1700" data-aos-duration="800">
                <button type="submit" class="btn btn-primary btn-lg flex-fill"><i class="bi bi-send me-2"></i>Enviar</button>
                <a class="btn btn-outline-light btn-lg flex-fill" href="https://wa.me/5491112345678" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-2"></i>Hablar por WhatsApp</a>
              </div>
              <p class="small text-light opacity-75 mt-2" data-aos="fade-up" data-aos-delay="1900" data-aos-duration="600"><i class="bi bi-shield-lock me-1"></i>Tus datos no se comparten con terceros.</p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="py-5 border-top border-ink-subtle" data-aos="slide-up" data-aos-duration="1200" data-aos-offset="50">
    <div class="container">
      <div class="row gy-4">
        <div class="col-md-6" data-aos="slide-right" data-aos-delay="300" data-aos-duration="800">
          <div class="d-flex align-items-center gap-2 mb-2">
            <img src="assets/img/logo.svg" width="24" height="24" alt="DentexaPro logo">
            <strong class="text-white">DentexaPro</strong>
          </div>
          <p class="small text-light opacity-75 mb-0">© <span id="year"></span> DentexaPro. Todos los derechos reservados.</p>
        </div>
        <div class="col-md-6 text-md-end" data-aos="slide-left" data-aos-delay="500" data-aos-duration="800">
          <a href="terminos-condiciones.php" class="link-light me-3">Términos</a>
          <a href="politica-privacidad.php" class="link-light">Privacidad</a>
        </div>
      </div>
    </div>
  </footer>


  

  <!-- WhatsApp Floating Button -->
  <a href="https://wa.me/5491112345678?text=Hola%2C%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20DentexaPro" 
     target="_blank" 
     rel="noopener" 
     class="whatsapp-float" 
     aria-label="Contactar por WhatsApp">
    <i class="bi bi-whatsapp"></i>
  </a>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/main.js"></script>
  
  <!-- Typing Animation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Typing animation
      const typingElement = document.getElementById('typingText');
      const phrases = [
        'acelera tu consulta',
        'organiza tu agenda',
        'digitaliza tu práctica',
        'potencia tu clínica',
        'simplifica tu trabajo'
      ];
      
      let currentPhraseIndex = 0;
      let currentCharIndex = 0;
      let isDeleting = false;
      