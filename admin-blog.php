<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Get blog stats
$database = new Database();
$db = $database->getConnection();

try {
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM blog_posts");
    $totalPosts = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as published FROM blog_posts WHERE status = 'published'");
    $publishedPosts = $stmt->fetch()['published'];
    
    $stmt = $db->query("SELECT COUNT(*) as drafts FROM blog_posts WHERE status = 'draft'");
    $draftPosts = $stmt->fetch()['drafts'];
    
    // Get categories
    $stmt = $db->query("
        SELECT category, COUNT(*) as count 
        FROM blog_posts 
        WHERE status = 'published' 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $totalPosts = 0;
    $publishedPosts = 0;
    $draftPosts = 0;
    $categories = [];
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Blog - DentexaPro Admin</title>
  <meta name="description" content="Gestión de blog del panel de administración de DentexaPro">
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

  <!-- Admin Blog -->
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
                <i class="bi bi-journal-text me-2"></i>Gestión de Blog
              </h1>
              <p class="text-light opacity-75 mb-0">Crea y gestiona artículos para el blog de DentexaPro</p>
            </div>
            <div class="d-flex gap-2">
              <a href="blog.php" target="_blank" class="btn btn-outline-light">
                <i class="bi bi-eye me-2"></i>Ver blog
              </a>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                <i class="bi bi-plus-lg me-2"></i>Nuevo artículo
              </button>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="row g-4 mb-5">
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="200">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-journal-text"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $totalPosts; ?></h3>
                <p class="text-light opacity-75 mb-0">Total artículos</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="300">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-eye"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $publishedPosts; ?></h3>
                <p class="text-light opacity-75 mb-0">Publicados</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="400">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo $draftPosts; ?></h3>
                <p class="text-light opacity-75 mb-0">Borradores</p>
              </div>
            </div>
            <div class="col-md-3" data-aos="slide-up" data-aos-duration="800" data-aos-delay="500">
              <div class="glass-card p-4 text-center">
                <div class="feature-icon mx-auto mb-3">
                  <i class="bi bi-tags"></i>
                </div>
                <h3 class="text-white mb-1"><?php echo count($categories); ?></h3>
                <p class="text-light opacity-75 mb-0">Categorías</p>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label text-light">Buscar artículo</label>
                <div class="position-relative">
                  <input type="text" id="searchInput" class="form-control glass-input" placeholder="Título o contenido...">
                  <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label text-light">Estado</label>
                <select id="statusFilter" class="form-select glass-input">
                  <option value="">Todos los estados</option>
                  <option value="published">Publicados</option>
                  <option value="draft">Borradores</option>
                  <option value="archived">Archivados</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-light">Categoría</label>
                <select id="categoryFilter" class="form-select glass-input">
                  <option value="">Todas las categorías</option>
                  <option value="tecnologia">Tecnología</option>
                  <option value="guias">Guías</option>
                  <option value="innovacion">Innovación</option>
                  <option value="casos">Casos de Éxito</option>
                  <option value="noticias">Noticias</option>
                  <option value="tips">Tips</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-light w-100" onclick="clearFilters()">
                  <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                </button>
              </div>
            </div>
          </div>

          <!-- Posts Table -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="text-white mb-0">
                <i class="bi bi-table me-2"></i>Lista de artículos
              </h4>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-light" onclick="exportPosts()">
                  <i class="bi bi-download me-2"></i>Exportar
                </button>
                <button class="btn btn-primary-soft" onclick="loadPosts()">
                  <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                </button>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-dark table-hover">
                <thead>
                  <tr>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Autor</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="postsTable">
                  <!-- Posts will be loaded here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Create Post Modal -->
  <div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="createPostModalLabel">
            <i class="bi bi-plus-lg me-2"></i>Crear nuevo artículo
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="createPostForm" class="row g-4">
            <div class="col-12">
              <label class="form-label text-light">Título del artículo *</label>
              <input type="text" name="title" id="postTitle" class="form-control form-control-lg glass-input" 
                     placeholder="Ej: 5 Beneficios de Digitalizar tu Consultorio" required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Categoría *</label>
              <select name="category" class="form-select glass-input" required>
                <option value="">Seleccionar categoría</option>
                <option value="tecnologia">Tecnología</option>
                <option value="guias">Guías</option>
                <option value="innovacion">Innovación</option>
                <option value="casos">Casos de Éxito</option>
                <option value="noticias">Noticias</option>
                <option value="tips">Tips</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">URL del artículo</label>
              <input type="text" name="slug" id="postSlug" class="form-control glass-input" 
                     placeholder="Se genera automáticamente" readonly>
              <small class="text-light opacity-75">Se genera automáticamente desde el título</small>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Imagen destacada (URL)</label>
              <input type="url" name="featured_image" class="form-control glass-input" 
                     placeholder="https://images.pexels.com/...">
              <small class="text-light opacity-75">URL de imagen desde Pexels o similar</small>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Resumen/Excerpt *</label>
              <textarea name="excerpt" class="form-control glass-input" rows="3" 
                        placeholder="Breve descripción del artículo que aparecerá en la lista..." required></textarea>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Contenido del artículo *</label>
              <div id="editor" style="height: 400px; background: rgba(255,255,255,0.95); border-radius: 8px;"></div>
              <input type="hidden" name="content" id="postContent">
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Etiquetas (separadas por comas)</label>
              <input type="text" name="tags" class="form-control glass-input" 
                     placeholder="digitalización, consultorio, tecnología">
              <small class="text-light opacity-75">Ej: digitalización, consultorio, tecnología</small>
            </div>
            
            <div class="col-12 text-end">
              <button type="button" class="btn btn-outline-light me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-outline-primary me-2" onclick="saveAsDraft()">
                <i class="bi bi-file-earmark me-2"></i>Guardar borrador
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-2"></i>Publicar artículo
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Post Modal -->
  <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="editPostModalLabel">
            <i class="bi bi-pencil me-2"></i>Editar artículo
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="editPostForm" class="row g-4">
            <input type="hidden" id="editPostId" name="postId">
            
            <div class="col-12">
              <label class="form-label text-light">Título del artículo *</label>
              <input type="text" name="title" id="editPostTitle" class="form-control form-control-lg glass-input" required>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Categoría *</label>
              <select name="category" id="editPostCategory" class="form-select glass-input" required>
                <option value="">Seleccionar categoría</option>
                <option value="tecnologia">Tecnología</option>
                <option value="guias">Guías</option>
                <option value="innovacion">Innovación</option>
                <option value="casos">Casos de Éxito</option>
                <option value="noticias">Noticias</option>
                <option value="tips">Tips</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="form-label text-light">Estado *</label>
              <select name="status" id="editPostStatus" class="form-select glass-input" required>
                <option value="draft">Borrador</option>
                <option value="published">Publicado</option>
                <option value="archived">Archivado</option>
              </select>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Imagen destacada (URL)</label>
              <input type="url" name="featured_image" id="editPostImage" class="form-control glass-input">
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Resumen/Excerpt *</label>
              <textarea name="excerpt" id="editPostExcerpt" class="form-control glass-input" rows="3" required></textarea>
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Contenido del artículo *</label>
              <div id="editEditor" style="height: 400px; background: rgba(255,255,255,0.95); border-radius: 8px;"></div>
              <input type="hidden" name="content" id="editPostContent">
            </div>
            
            <div class="col-12">
              <label class="form-label text-light">Etiquetas (separadas por comas)</label>
              <input type="text" name="tags" id="editPostTags" class="form-control glass-input">
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script src="assets/js/admin-blog.js"></script>
</body>
</html>