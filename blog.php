<?php
session_start();
require_once 'config/database.php';

// Check if maintenance mode is enabled
$database = new Database();
$db = $database->getConnection();

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

// Get blog posts

try {
    // Create blog_posts table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS blog_posts (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT DEFAULT NULL,
            content LONGTEXT NOT NULL,
            featured_image VARCHAR(500) DEFAULT NULL,
            author_id VARCHAR(36) NOT NULL,
            category VARCHAR(100) DEFAULT 'general',
            tags JSON DEFAULT NULL,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            published_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_blog_posts_slug (slug),
            INDEX idx_blog_posts_status (status),
            INDEX idx_blog_posts_category (category),
            INDEX idx_blog_posts_published (published_at),
            FOREIGN KEY (author_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert sample posts if table is empty
    $stmt = $db->query("SELECT COUNT(*) as count FROM blog_posts");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Get admin user ID
        $stmt = $db->query("SELECT user_id FROM user_profiles WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            $samplePosts = [
                [
                    'title' => '5 Beneficios de Digitalizar tu Consultorio Dental',
                    'slug' => '5-beneficios-digitalizar-consultorio-dental',
                    'excerpt' => 'Descubre cómo la tecnología puede transformar tu práctica dental y mejorar la experiencia de tus pacientes.',
                    'content' => '<p>La digitalización de los consultorios dentales no es solo una tendencia, es una necesidad en el mundo moderno. En este artículo, exploramos los principales beneficios que puede aportar a tu práctica profesional.</p><h3>1. Mejora en la Gestión de Pacientes</h3><p>Con un sistema digital, puedes acceder instantáneamente al historial completo de cualquier paciente, incluyendo radiografías, tratamientos previos y notas importantes.</p><h3>2. Reducción de Ausencias</h3><p>Los recordatorios automáticos por WhatsApp y email pueden reducir las ausencias hasta en un 80%, optimizando tu agenda y aumentando tus ingresos.</p><h3>3. Ahorro de Tiempo</h3><p>La automatización de tareas administrativas te permite dedicar más tiempo a lo que realmente importa: el cuidado de tus pacientes.</p><h3>4. Mejor Experiencia del Paciente</h3><p>Los pacientes valoran poder agendar turnos online, recibir recordatorios y acceder a su información médica cuando lo necesiten.</p><h3>5. Crecimiento del Negocio</h3><p>Con reportes detallados y análisis de datos, puedes tomar decisiones informadas para hacer crecer tu consultorio.</p>',
                    'category' => 'tecnologia',
                    'tags' => '["digitalización", "consultorio", "tecnología", "pacientes"]',
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
                ],
                [
                    'title' => 'Cómo Elegir el Software Ideal para tu Clínica Dental',
                    'slug' => 'como-elegir-software-ideal-clinica-dental',
                    'excerpt' => 'Guía completa para seleccionar la plataforma de gestión que mejor se adapte a las necesidades de tu consultorio.',
                    'content' => '<p>Elegir el software adecuado para tu clínica dental es una decisión crucial que impactará en la eficiencia de tu práctica y la satisfacción de tus pacientes.</p><h3>Factores Clave a Considerar</h3><h4>1. Facilidad de Uso</h4><p>El sistema debe ser intuitivo tanto para ti como para tu equipo. Una curva de aprendizaje empinada puede generar resistencia al cambio.</p><h4>2. Funcionalidades Específicas</h4><p>Busca características como odontograma digital, gestión de imágenes, recordatorios automáticos y facturación integrada.</p><h4>3. Soporte y Capacitación</h4><p>Es fundamental contar con soporte técnico en español y capacitación para aprovechar al máximo el sistema.</p><h4>4. Escalabilidad</h4><p>El software debe crecer contigo. Si planeas expandir tu equipo, asegúrate de que el sistema lo permita.</p><h4>5. Seguridad y Respaldos</h4><p>Los datos de tus pacientes son sensibles. Verifica que el sistema cumpla con estándares de seguridad y realice respaldos automáticos.</p>',
                    'category' => 'guias',
                    'tags' => '["software", "selección", "clínica", "guía"]',
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
                ],
                [
                    'title' => 'Tendencias en Odontología Digital para 2025',
                    'slug' => 'tendencias-odontologia-digital-2025',
                    'excerpt' => 'Las últimas innovaciones tecnológicas que están revolucionando la práctica dental moderna.',
                    'content' => '<p>El 2025 promete ser un año revolucionario para la odontología digital. Estas son las principales tendencias que marcarán el futuro de la profesión.</p><h3>Inteligencia Artificial en Diagnóstico</h3><p>Los algoritmos de IA están mejorando la precisión diagnóstica, especialmente en la detección temprana de caries y enfermedades periodontales.</p><h3>Telemedicina Dental</h3><p>Las consultas virtuales se están volviendo más comunes, especialmente para seguimientos post-tratamiento y consultas de urgencia.</p><h3>Realidad Aumentada</h3><p>La AR está transformando la educación del paciente, permitiendo mostrar tratamientos de forma visual e interactiva.</p><h3>Impresión 3D</h3><p>La fabricación de prótesis y aparatos ortodónticos in-house está reduciendo tiempos y costos significativamente.</p><h3>Blockchain en Historiales</h3><p>La tecnología blockchain promete mayor seguridad y portabilidad en los registros médicos.</p>',
                    'category' => 'innovacion',
                    'tags' => '["tendencias", "2025", "tecnología", "innovación"]',
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ]
            ];
            
            foreach ($samplePosts as $post) {
                $stmt = $db->prepare("
                    INSERT INTO blog_posts (title, slug, excerpt, content, category, tags, status, published_at, author_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $post['title'],
                    $post['slug'],
                    $post['excerpt'],
                    $post['content'],
                    $post['category'],
                    $post['tags'],
                    $post['status'],
                    $post['published_at'],
                    $admin['user_id']
                ]);
            }
        }
    }
    
    // Get published posts
    $stmt = $db->prepare("
        SELECT 
            bp.*,
            CONCAT(up.first_name, ' ', up.last_name) as author_name
        FROM blog_posts bp
        LEFT JOIN user_profiles up ON bp.author_id = up.user_id
        WHERE bp.status = 'published'
        ORDER BY bp.published_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $db->prepare("
        SELECT DISTINCT category, COUNT(*) as count
        FROM blog_posts 
        WHERE status = 'published'
        GROUP BY category
        ORDER BY count DESC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $posts = [];
    $categories = [];
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Blog - DentexaPro</title>
  <meta name="description" content="Artículos, guías y novedades sobre gestión de consultorios dentales y tecnología odontológica">
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

  <style>
    .blog-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }
    .blog-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 50px rgba(47,150,238,.25);
    }
    .blog-image {
      height: 200px;
      object-fit: cover;
      border-radius: 12px;
    }
    .blog-category {
      background: rgba(47,150,238,0.2);
      color: #68c4ff;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    .blog-meta {
      color: rgba(255,255,255,0.7);
      font-size: 0.9rem;
    }
    .blog-excerpt {
      color: rgba(255,255,255,0.85);
      line-height: 1.6;
    }
  </style>
</head>
<body class="bg-dark-ink text-body">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Floating Icons Background -->
  <div class="floating-elements" aria-hidden="true">
    <div class="floating-icon" style="--delay: 0s; --duration: 8s;">
      <i class="bi bi-journal-text"></i>
    </div>
    <div class="floating-icon" style="--delay: 2s; --duration: 10s;">
      <i class="bi bi-lightbulb"></i>
    </div>
    <div class="floating-icon" style="--delay: 4s; --duration: 12s;">
      <i class="bi bi-book"></i>
    </div>
    <div class="floating-icon" style="--delay: 6s; --duration: 9s;">
      <i class="bi bi-cpu"></i>
    </div>
    <div class="floating-icon" style="--delay: 1s; --duration: 11s;">
      <i class="bi bi-trophy"></i>
    </div>
    <div class="floating-icon" style="--delay: 3s; --duration: 13s;">
      <i class="bi bi-newspaper"></i>
    </div>
    <div class="floating-icon" style="--delay: 5s; --duration: 7s;">
      <i class="bi bi-star"></i>
    </div>
    <div class="floating-icon" style="--delay: 7s; --duration: 14s;">
      <i class="bi bi-pencil"></i>
    </div>
    <div class="floating-icon" style="--delay: 1.5s; --duration: 10s;">
      <i class="bi bi-chat-quote"></i>
    </div>
    <div class="floating-icon" style="--delay: 4.5s; --duration: 8s;">
      <i class="bi bi-bookmark"></i>
    </div>
    <div class="floating-icon" style="--delay: 2.5s; --duration: 15s;">
      <i class="bi bi-eye"></i>
    </div>
    <div class="floating-icon" style="--delay: 6.5s; --duration: 9s;">
      <i class="bi bi-share"></i>
    </div>
    <div class="floating-icon" style="--delay: 0.5s; --duration: 12s;">
      <i class="bi bi-tags"></i>
    </div>
    <div class="floating-icon" style="--delay: 3.5s; --duration: 11s;">
      <i class="bi bi-heart"></i>
    </div>
    <div class="floating-icon" style="--delay: 5.5s; --duration: 13s;">
      <i class="bi bi-collection"></i>
    </div>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="index.php#features"><i class="bi bi-grid-3x3-gap me-2"></i>Funciones</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#pricing"><i class="bi bi-tag me-2"></i>Precios</a></li>
          <li class="nav-item"><a class="nav-link active" href="blog.php"><i class="bi bi-journal-text me-2"></i>Blog</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#faq"><i class="bi bi-question-circle me-2"></i>Preguntas</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión</a></li>
          <li class="nav-item ms-lg-3"><a class="btn btn-primary-soft" href="registro.php"><i class="bi bi-rocket-takeoff me-2"></i>Probar gratis 15 días</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Blog Header -->
  <header class="hero section-pt pb-5 position-relative overflow-hidden">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8" data-aos="fade-down" data-aos-duration="1000">
          <h1 class="display-5 fw-bold lh-1 text-white mb-3">
            <i class="bi bi-journal-text me-3"></i>Blog de <span class="gradient-text">DentexaPro</span>
          </h1>
          <p class="lead text-light opacity-85 mb-4">
            Artículos, guías y novedades sobre gestión de consultorios dentales, tecnología odontológica y mejores prácticas para profesionales.
          </p>
          <div class="d-flex gap-3 flex-wrap justify-content-center" data-aos="fade-up" data-aos-delay="300" data-aos-duration="800">
            <a href="#posts" class="btn btn-primary btn-lg"><i class="bi bi-arrow-down me-2"></i>Ver artículos</a>
            <a href="registro.php" class="btn btn-outline-light btn-lg"><i class="bi bi-rocket-takeoff me-2"></i>Comenzar gratis</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Blog Categories Filter -->
  <section class="py-4 border-bottom border-ink-subtle" data-aos="slide-up" data-aos-duration="800">
    <div class="container">
      <div class="d-flex justify-content-center flex-wrap gap-3">
        <button class="btn btn-outline-light btn-sm active" data-category="all">
          <i class="bi bi-grid me-2"></i>Todos
        </button>
        <?php foreach ($categories as $category): ?>
        <button class="btn btn-outline-light btn-sm" data-category="<?php echo $category['category']; ?>">
          <i class="bi bi-<?php echo getCategoryIcon($category['category']); ?> me-2"></i>
          <?php echo getCategoryName($category['category']); ?> (<?php echo $category['count']; ?>)
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Blog Posts -->
  <section id="posts" class="section-py">
    <div class="container">
      <?php if (empty($posts)): ?>
      <div class="text-center" data-aos="fade-up" data-aos-duration="800">
        <div class="glass-card p-5">
          <i class="bi bi-journal-x text-primary" style="font-size: 4rem; margin-bottom: 1rem;"></i>
          <h3 class="text-white mb-3">Próximamente</h3>
          <p class="text-light opacity-85 mb-4">
            Estamos preparando contenido valioso para ayudarte a optimizar tu consultorio dental.
          </p>
          <a href="registro.php" class="btn btn-primary">
            <i class="bi bi-rocket-takeoff me-2"></i>Comenzar con DentexaPro
          </a>
        </div>
      </div>
      <?php else: ?>
      <div class="row g-4" id="postsContainer">
        <?php foreach ($posts as $index => $post): ?>
        <div class="col-lg-4 col-md-6 blog-post" data-category="<?php echo $post['category']; ?>" 
             data-aos="fade-up" data-aos-duration="800" data-aos-delay="<?php echo ($index % 3) * 200; ?>">
          <article class="blog-card glass-card p-4">
            <?php if ($post['featured_image']): ?>
            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                 class="blog-image w-100 mb-3" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>">
            <?php else: ?>
            <div class="blog-image w-100 mb-3 d-flex align-items-center justify-content-center glass-card">
              <i class="bi bi-<?php echo getCategoryIcon($post['category']); ?> text-primary" style="font-size: 3rem;"></i>
            </div>
            <?php endif; ?>
            
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="blog-category">
                <i class="bi bi-<?php echo getCategoryIcon($post['category']); ?> me-1"></i>
                <?php echo getCategoryName($post['category']); ?>
              </span>
              <div class="blog-meta">
                <i class="bi bi-calendar me-1"></i>
                <?php echo formatBlogDate($post['published_at']); ?>
              </div>
            </div>
            
            <h3 class="text-white mb-3">
              <a href="blog-post.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none text-white">
                <?php echo htmlspecialchars($post['title']); ?>
              </a>
            </h3>
            
            <p class="blog-excerpt mb-4">
              <?php echo htmlspecialchars($post['excerpt']); ?>
            </p>
            
            <div class="d-flex justify-content-between align-items-center">
              <div class="blog-meta">
                <i class="bi bi-person me-1"></i>
                DentexaPro Team
              </div>
              <a href="blog-post.php?slug=<?php echo $post['slug']; ?>" class="btn btn-primary-soft btn-sm">
                <i class="bi bi-arrow-right me-2"></i>Leer más
              </a>
            </div>
          </article>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Load More Button -->
      <div class="text-center mt-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
        <button class="btn btn-outline-light btn-lg" id="loadMoreBtn">
          <i class="bi bi-arrow-down me-2"></i>Cargar más artículos
        </button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Newsletter Subscription -->
  <section class="section-py-sm" data-aos="fade-up" data-aos-duration="800">
    <div class="container">
      <div class="newsletter-enhanced-card position-relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="newsletter-bg-elements" aria-hidden="true">
          <div class="newsletter-blob newsletter-blob-1"></div>
          <div class="newsletter-blob newsletter-blob-2"></div>
          <div class="newsletter-blob newsletter-blob-3"></div>
        </div>
        
        <!-- Floating Icons -->
        <div class="newsletter-floating-icons" aria-hidden="true">
          <i class="bi bi-envelope-heart newsletter-float-icon" style="--delay: 0s; --duration: 6s; top: 20%; left: 10%;"></i>
          <i class="bi bi-send newsletter-float-icon" style="--delay: 2s; --duration: 8s; top: 30%; right: 15%;"></i>
          <i class="bi bi-newspaper newsletter-float-icon" style="--delay: 4s; --duration: 7s; bottom: 30%; left: 20%;"></i>
          <i class="bi bi-star newsletter-float-icon" style="--delay: 1s; --duration: 9s; top: 60%; right: 10%;"></i>
          <i class="bi bi-lightbulb newsletter-float-icon" style="--delay: 3s; --duration: 10s; bottom: 20%; right: 25%;"></i>
        </div>
        
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <!-- Header with animated icon -->
            <div class="text-center mb-4">
              <div class="newsletter-icon-container mb-3">
                <div class="newsletter-main-icon">
                  <i class="bi bi-envelope-heart"></i>
                </div>
                <div class="newsletter-icon-pulse"></div>
              </div>
              <h3 class="text-white mb-3 newsletter-title">
                Suscribite a nuestro <span class="gradient-text">newsletter</span>
              </h3>
              <p class="text-light opacity-85 mb-0 newsletter-subtitle">
                Recibe los últimos artículos, tips exclusivos y novedades de DentexaPro directamente en tu email.
              </p>
            </div>
            
            <!-- Benefits -->
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="newsletter-benefit">
                  <i class="bi bi-journal-medical text-primary"></i>
                  <span>Artículos exclusivos</span>
                </div>
              </div>
              <div class="col-md-4">
                <div class="newsletter-benefit">
                  <i class="bi bi-lightbulb text-warning"></i>
                  <span>Tips profesionales</span>
                </div>
              </div>
              <div class="col-md-4">
                <div class="newsletter-benefit">
                  <i class="bi bi-rocket-takeoff text-success"></i>
                  <span>Novedades primero</span>
                </div>
              </div>
            </div>
            
            <!-- Enhanced Form -->
            <div class="newsletter-form-container">
              <form class="newsletter-form">
                <div class="newsletter-input-group">
                  <div class="newsletter-input-wrapper">
                    <i class="bi bi-envelope newsletter-input-icon"></i>
                    <input type="email" name="email" class="newsletter-input" placeholder="tu@email.com" required>
                  </div>
                  <button type="submit" class="newsletter-submit-btn">
                    <span class="newsletter-btn-text">
                      <i class="bi bi-send me-2"></i>Suscribirme
                    </span>
                    <span class="newsletter-btn-loading" style="display: none;">
                      <span class="spinner-border spinner-border-sm me-2"></span>Suscribiendo...
                    </span>
                  </button>
                </div>
              </form>
              
              <!-- Trust indicators -->
              <div class="newsletter-trust-indicators">
                <div class="newsletter-trust-item">
                  <i class="bi bi-shield-check text-success"></i>
                  <span>Sin spam</span>
                </div>
                <div class="newsletter-trust-item">
                  <i class="bi bi-x-circle text-danger"></i>
                  <span>Cancelás cuando quieras</span>
                </div>
                <div class="newsletter-trust-item">
                  <i class="bi bi-calendar-week text-info"></i>
                  <span>1 email por semana</span>
                </div>
              </div>
            </div>
            
            <!-- Social Proof -->
            <div class="newsletter-social-proof text-center">
              <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                <div class="newsletter-stat">
                  <span class="newsletter-stat-number">500+</span>
                  <span class="newsletter-stat-label">Suscriptores</span>
                </div>
                <div class="newsletter-stat">
                  <span class="newsletter-stat-number">4.9★</span>
                  <span class="newsletter-stat-label">Valoración</span>
                </div>
                <div class="newsletter-stat">
                  <span class="newsletter-stat-number">98%</span>
                  <span class="newsletter-stat-label">Satisfacción</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Enhanced Newsletter Styles -->
  <style>
    .newsletter-enhanced-card {
      background: linear-gradient(135deg, rgba(47,150,238,0.15), rgba(104,196,255,0.08));
      border: 1px solid rgba(47,150,238,0.2);
      border-radius: 24px;
      padding: 3rem 2rem;
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }
    
    .newsletter-enhanced-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: conic-gradient(from 0deg, transparent, rgba(47,150,238,0.1), transparent);
      animation: newsletter-border-rotate 8s linear infinite;
      z-index: -1;
    }
    
    @keyframes newsletter-border-rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    .newsletter-bg-elements {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
    }
    
    .newsletter-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(40px);
      opacity: 0.3;
      animation: newsletter-blob-float 12s ease-in-out infinite;
    }
    
    .newsletter-blob-1 {
      width: 200px;
      height: 200px;
      background: linear-gradient(45deg, #2F96EE, #68c4ff);
      top: -50px;
      left: -50px;
      animation-delay: 0s;
    }
    
    .newsletter-blob-2 {
      width: 150px;
      height: 150px;
      background: linear-gradient(45deg, #68c4ff, #9ad8ff);
      bottom: -30px;
      right: -30px;
      animation-delay: -4s;
    }
    
    .newsletter-blob-3 {
      width: 100px;
      height: 100px;
      background: linear-gradient(45deg, #9ad8ff, #2F96EE);
      top: 50%;
      left: 80%;
      animation-delay: -8s;
    }
    
    @keyframes newsletter-blob-float {
      0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
      33% { transform: translateY(-20px) translateX(10px) scale(1.1); }
      66% { transform: translateY(10px) translateX(-15px) scale(0.9); }
    }
    
    .newsletter-floating-icons {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
    }
    
    .newsletter-float-icon {
      position: absolute;
      color: rgba(47,150,238,0.4);
      font-size: 1.5rem;
      animation: newsletter-icon-float var(--duration) ease-in-out infinite;
      animation-delay: var(--delay);
    }
    
    @keyframes newsletter-icon-float {
      0%, 100% { 
        transform: translateY(0px) translateX(0px) rotate(0deg);
        opacity: 0.4;
      }
      25% { 
        transform: translateY(-15px) translateX(8px) rotate(90deg);
        opacity: 0.6;
      }
      50% { 
        transform: translateY(-8px) translateX(-12px) rotate(180deg);
        opacity: 0.3;
      }
      75% { 
        transform: translateY(-20px) translateX(5px) rotate(270deg);
        opacity: 0.5;
      }
    }
    
    .newsletter-icon-container {
      position: relative;
      display: inline-block;
    }
    
    .newsletter-main-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--primary), #68c4ff);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: white;
      position: relative;
      z-index: 2;
      animation: newsletter-icon-pulse 3s ease-in-out infinite;
    }
    
    .newsletter-icon-pulse {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border: 2px solid var(--primary);
      border-radius: 50%;
      animation: newsletter-pulse-ring 2s ease-out infinite;
    }
    
    @keyframes newsletter-icon-pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    @keyframes newsletter-pulse-ring {
      0% {
        transform: scale(1);
        opacity: 0.8;
      }
      100% {
        transform: scale(1.8);
        opacity: 0;
      }
    }
    
    .newsletter-title {
      font-size: 2.2rem;
      font-weight: 700;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .newsletter-subtitle {
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .newsletter-benefit {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 12px;
      padding: 1rem;
      text-align: center;
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .newsletter-benefit:hover {
      background: rgba(47,150,238,0.15);
      border-color: rgba(47,150,238,0.3);
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(47,150,238,0.2);
    }
    
    .newsletter-benefit i {
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
      display: block;
    }
    
    .newsletter-benefit span {
      color: rgba(255,255,255,0.9);
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .newsletter-form-container {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 20px;
      padding: 2rem;
      margin: 2rem 0;
      position: relative;
      z-index: 2;
    }
    
    .newsletter-form-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.05), transparent);
      border-radius: 20px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .newsletter-form-container:hover::before {
      opacity: 1;
    }
    
    .newsletter-form {
      position: relative;
      z-index: 1;
    }
    
    .newsletter-input-group {
      display: flex;
      gap: 1rem;
      align-items: stretch;
      margin-bottom: 1.5rem;
    }
    
    .newsletter-input-wrapper {
      flex: 1;
      position: relative;
    }
    
    .newsletter-input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,0.6);
      font-size: 1.1rem;
      z-index: 2;
    }
    
    .newsletter-input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      background: rgba(255,255,255,0.08);
      border: 2px solid rgba(255,255,255,0.15);
      border-radius: 16px;
      color: #fff;
      font-size: 1.1rem;
      transition: all 0.3s ease;
    }
    
    .newsletter-input::placeholder {
      color: rgba(255,255,255,0.5);
    }
    
    .newsletter-input:focus {
      outline: none;
      background: rgba(255,255,255,0.12);
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(47,150,238,0.2);
      transform: translateY(-2px);
    }
    
    .newsletter-input:focus + .newsletter-input-icon {
      color: var(--primary);
    }
    
    .newsletter-submit-btn {
      background: linear-gradient(135deg, var(--primary), #68c4ff);
      border: none;
      border-radius: 16px;
      color: white;
      font-weight: 600;
      font-size: 1.1rem;
      padding: 1rem 2rem;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      min-width: 160px;
    }
    
    .newsletter-submit-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.6s ease;
    }
    
    .newsletter-submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(47,150,238,0.4);
    }
    
    .newsletter-submit-btn:hover::before {
      left: 100%;
    }
    
    .newsletter-submit-btn:active {
      transform: translateY(-1px);
    }
    
    .newsletter-trust-indicators {
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
    }
    
    .newsletter-trust-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: rgba(255,255,255,0.8);
      font-size: 0.9rem;
    }
    
    .newsletter-trust-item i {
      font-size: 1rem;
    }
    
    .newsletter-social-proof {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .newsletter-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.25rem;
    }
    
    .newsletter-stat-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: #68c4ff;
      text-shadow: 0 0 10px rgba(104,196,255,0.5);
    }
    
    .newsletter-stat-label {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.7);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .newsletter-enhanced-card {
        padding: 2rem 1.5rem;
      }
      
      .newsletter-input-group {
        flex-direction: column;
        gap: 1rem;
      }
      
      .newsletter-submit-btn {
        width: 100%;
      }
      
      .newsletter-trust-indicators {
        gap: 1rem;
      }
      
      .newsletter-floating-icons {
        display: none; /* Hide floating icons on mobile for performance */
      }
      
      .newsletter-title {
        font-size: 1.8rem;
      }
    }
    
    /* Success state */
    .newsletter-success .newsletter-input {
      border-color: #28a745;
      background: rgba(40,167,69,0.1);
    }
    
    .newsletter-success .newsletter-submit-btn {
      background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    /* Error state */
    .newsletter-error .newsletter-input {
      border-color: #dc3545;
      background: rgba(220,53,69,0.1);
    }
    
    /* Loading state */
    .newsletter-loading .newsletter-input {
      opacity: 0.7;
    }
    
    .newsletter-loading .newsletter-submit-btn {
      background: linear-gradient(135deg, #6c757d, #adb5bd);
      cursor: not-allowed;
    }
  </style>
            </h3>
            <p class="text-light opacity-85 mb-4">
              Recibe los últimos artículos, tips y novedades de DentexaPro directamente en tu email.
            </p>
            <form class="row g-3 justify-content-center">
              <div class="col-md-8">
                <input type="email" name="email" class="form-control form-control-lg glass-input" placeholder="tu@email.com" required>
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                  <i class="bi bi-send me-2"></i>Suscribirme
                </button>
              </div>
            </form>
            <small class="text-light opacity-75 mt-2 d-block">
              <i class="bi bi-shield-check me-1"></i>Sin spam. Cancelás cuando quieras.
            </small>
            
            <!-- Newsletter Messages -->
            <div id="newsletterMessages" class="mt-3"></div>
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
          <a href="index.php" class="link-light me-3">Inicio</a>
          <a href="blog.php" class="link-light me-3">Blog</a>
          <a href="#" class="link-light me-3">Términos</a>
          <a href="#" class="link-light">Privacidad</a>
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
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Category filter functionality
      const categoryButtons = document.querySelectorAll('[data-category]');
      const blogPosts = document.querySelectorAll('.blog-post');

      categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
          const category = button.dataset.category;
          
          // Update active button
          categoryButtons.forEach(btn => btn.classList.remove('active'));
          button.classList.add('active');
          
          // Filter posts
          blogPosts.forEach(post => {
            if (category === 'all' || post.dataset.category === category) {
              post.style.display = 'block';
              post.style.animation = 'fadeInUp 0.6s ease';
            } else {
              post.style.display = 'none';
            }
          });
        });
      });

      // Load more functionality (placeholder)
      const loadMoreBtn = document.getElementById('loadMoreBtn');
      if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
          loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';
          
          setTimeout(() => {
            loadMoreBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>No hay más artículos';
            loadMoreBtn.disabled = true;
          }, 1500);
        });
      }

      // Newsletter subscription
      const newsletterForm = document.querySelector('.glass-gradient form');
      if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
          e.preventDefault();
          const submitBtn = e.target.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;
          const emailInput = e.target.querySelector('input[type="email"]');
          const email = emailInput.value;
          const messagesContainer = document.getElementById('newsletterMessages');
          
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Suscribiendo...';
          
          // Clear previous messages
          messagesContainer.innerHTML = '';
          
          // Send subscription request
          fetch('api/newsletter.php?action=subscribe', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              email: email,
              source: 'blog'
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              // Show error message
              messagesContainer.innerHTML = `
                <div class="alert alert-danger glass-card">
                  <i class="bi bi-exclamation-triangle me-2"></i>${data.error}
                </div>
              `;
              
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
            } else {
              // Show success message
              messagesContainer.innerHTML = `
                <div class="alert alert-success glass-card">
                  <i class="bi bi-check-circle me-2"></i>${data.message}
                </div>
              `;
              
              submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>¡Suscripto!';
              submitBtn.classList.remove('btn-primary');
              submitBtn.classList.add('btn-success');
              
              e.target.reset();
              
              // Reset button after 3 seconds
              setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('btn-success');
                submitBtn.classList.add('btn-primary');
              }, 3000);
            }
            
            // Remove alert after 5 seconds
            setTimeout(() => {
              messagesContainer.innerHTML = '';
            }, 5000);
          })
          .catch(error => {
            console.error('Error subscribing to newsletter:', error);
            
            messagesContainer.innerHTML = `
              <div class="alert alert-danger glass-card">
                <i class="bi bi-exclamation-triangle me-2"></i>Error de conexión. Por favor, intentá nuevamente.
              </div>
            `;
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          });
        });
      }
    });
  </script>
  
  <style>
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</body>
</html>

<?php
// Helper functions for blog
function getCategoryName($category) {
    switch($category) {
        case 'tecnologia': return 'Tecnología';
        case 'guias': return 'Guías';
        case 'innovacion': return 'Innovación';
        case 'casos': return 'Casos de Éxito';
        case 'noticias': return 'Noticias';
        case 'tips': return 'Tips';
        default: return 'General';
    }
}

function getCategoryIcon($category) {
    switch($category) {
        case 'tecnologia': return 'cpu';
        case 'guias': return 'book';
        case 'innovacion': return 'lightbulb';
        case 'casos': return 'trophy';
        case 'noticias': return 'newspaper';
        case 'tips': return 'star';
        default: return 'journal-text';
    }
}

function formatBlogDate($date) {
    $datetime = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($datetime);
    
    if ($diff->days == 0) {
        return 'Hoy';
    } elseif ($diff->days == 1) {
        return 'Ayer';
    } elseif ($diff->days < 7) {
        return $diff->days . ' días atrás';
    } else {
        return $datetime->format('d/m/Y');
    }
}
?>