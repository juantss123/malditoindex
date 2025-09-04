<?php
session_start();
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: blog.php');
    exit();
}

// Get blog post
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT 
            bp.*,
            CONCAT(up.first_name, ' ', up.last_name) as author_name
        FROM blog_posts bp
        LEFT JOIN user_profiles up ON bp.author_id = up.user_id
        WHERE bp.slug = ? AND bp.status = 'published'
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: blog.php');
        exit();
    }
    
    // Get related posts
    $stmt = $db->prepare("
        SELECT title, slug, excerpt, published_at, category
        FROM blog_posts 
        WHERE category = ? AND slug != ? AND status = 'published'
        ORDER BY published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$post['category'], $slug]);
    $relatedPosts = $stmt->fetchAll();
    
} catch (Exception $e) {
    header('Location: blog.php');
    exit();
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($post['title']); ?> - Blog DentexaPro</title>
  <meta name="description" content="<?php echo htmlspecialchars($post['excerpt']); ?>">
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
    .blog-content {
      color: rgba(255,255,255,0.9);
      line-height: 1.8;
      font-size: 1.1rem;
    }
    .blog-content h3 {
      color: #68c4ff;
      margin-top: 2rem;
      margin-bottom: 1rem;
      border-bottom: 2px solid rgba(47,150,238,0.3);
      padding-bottom: 0.5rem;
    }
    .blog-content h4 {
      color: #9ad8ff;
      margin-top: 1.5rem;
      margin-bottom: 0.8rem;
    }
    .blog-content p {
      margin-bottom: 1.2rem;
    }
    .blog-content ul, .blog-content ol {
      margin-bottom: 1.5rem;
      padding-left: 1.5rem;
    }
    .blog-content li {
      margin-bottom: 0.5rem;
    }
    .blog-meta-large {
      color: rgba(255,255,255,0.7);
      font-size: 1rem;
    }
    .share-buttons .btn {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body class="bg-dark-ink text-body">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto">
        <a href="blog.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al blog
        </a>
      </div>
    </div>
  </nav>

  <!-- Blog Post -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <!-- Post Header -->
          <article class="glass-card p-4 p-sm-5" data-aos="fade-up" data-aos-duration="800">
            <div class="text-center mb-5">
              <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <span class="blog-category">
                  <i class="bi bi-<?php echo getCategoryIcon($post['category']); ?> me-1"></i>
                  <?php echo getCategoryName($post['category']); ?>
                </span>
                <div class="blog-meta-large">
                  <i class="bi bi-calendar me-1"></i>
                  <?php echo formatBlogDate($post['published_at']); ?>
                </div>
              </div>
              
              <h1 class="text-white mb-4">
                <?php echo htmlspecialchars($post['title']); ?>
              </h1>
              
              <div class="d-flex align-items-center justify-content-center gap-4 blog-meta-large">
                <div>
                  <i class="bi bi-person me-1"></i>
                  DentexaPro Team
                </div>
                <div>
                  <i class="bi bi-clock me-1"></i>
                  <?php echo estimateReadingTime($post['content']); ?> min de lectura
                </div>
              </div>
            </div>

            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
            <div class="text-center mb-5">
              <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                   class="img-fluid rounded-4" 
                   alt="<?php echo htmlspecialchars($post['title']); ?>"
                   style="max-height: 400px; object-fit: cover;">
            </div>
            <?php endif; ?>

            <!-- Post Content -->
            <div class="blog-content">
              <?php echo $post['content']; ?>
            </div>

            <!-- Tags -->
            <?php if ($post['tags']): ?>
            <div class="mt-5 pt-4 border-top border-secondary">
              <h6 class="text-white mb-3">
                <i class="bi bi-tags me-2"></i>Etiquetas
              </h6>
              <div class="d-flex flex-wrap gap-2">
                <?php 
                $tags = json_decode($post['tags'], true);
                if ($tags) {
                    foreach ($tags as $tag): ?>
                <span class="badge bg-primary-soft">
                  <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($tag); ?>
                </span>
                <?php endforeach;
                } ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Share Buttons -->
            <div class="mt-5 pt-4 border-top border-secondary">
              <h6 class="text-white mb-3">
                <i class="bi bi-share me-2"></i>Compartir artículo
              </h6>
              <div class="d-flex gap-3 share-buttons">
                <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                   target="_blank" class="btn btn-success" title="Compartir en WhatsApp">
                  <i class="bi bi-whatsapp"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                   target="_blank" class="btn btn-primary" title="Compartir en Facebook">
                  <i class="bi bi-facebook"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                   target="_blank" class="btn btn-info" title="Compartir en Twitter">
                  <i class="bi bi-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                   target="_blank" class="btn btn-primary" title="Compartir en LinkedIn">
                  <i class="bi bi-linkedin"></i>
                </a>
                <button class="btn btn-outline-light" onclick="copyToClipboard()" title="Copiar enlace">
                  <i class="bi bi-link-45deg"></i>
                </button>
              </div>
            </div>
          </article>

          <!-- Related Posts -->
          <?php if (!empty($relatedPosts)): ?>
          <div class="mt-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <h3 class="text-white mb-4">
              <i class="bi bi-collection me-2"></i>Artículos relacionados
            </h3>
            <div class="row g-4">
              <?php foreach ($relatedPosts as $relatedPost): ?>
              <div class="col-md-4">
                <div class="glass-card p-4 h-100">
                  <div class="mb-3">
                    <span class="blog-category">
                      <i class="bi bi-<?php echo getCategoryIcon($relatedPost['category']); ?> me-1"></i>
                      <?php echo getCategoryName($relatedPost['category']); ?>
                    </span>
                  </div>
                  <h5 class="text-white mb-3">
                    <a href="blog-post.php?slug=<?php echo $relatedPost['slug']; ?>" class="text-decoration-none text-white">
                      <?php echo htmlspecialchars($relatedPost['title']); ?>
                    </a>
                  </h5>
                  <p class="text-light opacity-85 mb-3">
                    <?php echo htmlspecialchars(substr($relatedPost['excerpt'], 0, 100)) . '...'; ?>
                  </p>
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-light opacity-75">
                      <?php echo formatBlogDate($relatedPost['published_at']); ?>
                    </small>
                    <a href="blog-post.php?slug=<?php echo $relatedPost['slug']; ?>" class="btn btn-primary-soft btn-sm">
                      <i class="bi bi-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- CTA Section -->
          <div class="mt-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
            <div class="glass-gradient text-center p-5">
              <h3 class="text-white mb-3">
                <i class="bi bi-rocket-takeoff me-2"></i>¿Listo para digitalizar tu consultorio?
              </h3>
              <p class="text-light opacity-85 mb-4">
                Comenzá tu prueba gratuita de 15 días y descubre cómo DentexaPro puede transformar tu práctica dental.
              </p>
              <a href="registro.php" class="btn btn-primary btn-lg">
                <i class="bi bi-play-circle me-2"></i>Comenzar prueba gratuita
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
    function copyToClipboard() {
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        // Show success message
        const btn = event.target.closest('button');
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        btn.classList.remove('btn-outline-light');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
          btn.innerHTML = originalIcon;
          btn.classList.remove('btn-success');
          btn.classList.add('btn-outline-light');
        }, 2000);
      });
    }
  </script>
</body>
</html>

<?php
// Helper functions (same as blog.php)
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

function estimateReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute
    return max(1, $readingTime);
}
?>