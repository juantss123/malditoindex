<?php
session_start();
require_once 'config/database.php';

// Check admin access
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Create marketing_settings table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS marketing_settings (
                id INT PRIMARY KEY DEFAULT 1,
                promotion_enabled BOOLEAN DEFAULT FALSE,
                promotion_text TEXT DEFAULT NULL,
                promotion_link VARCHAR(500) DEFAULT NULL,
                promotion_button_text VARCHAR(100) DEFAULT NULL,
                promotion_bg_color VARCHAR(7) DEFAULT '#dc3545',
                promotion_text_color VARCHAR(7) DEFAULT '#ffffff',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Update or insert settings
        $stmt = $db->prepare("
            INSERT INTO marketing_settings (
                id, promotion_enabled, promotion_text, promotion_link, 
                promotion_button_text, promotion_bg_color, promotion_text_color
            ) VALUES (1, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                promotion_enabled = VALUES(promotion_enabled),
                promotion_text = VALUES(promotion_text),
                promotion_link = VALUES(promotion_link),
                promotion_button_text = VALUES(promotion_button_text),
                promotion_bg_color = VALUES(promotion_bg_color),
                promotion_text_color = VALUES(promotion_text_color),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            isset($_POST['promotion_enabled']) ? 1 : 0,
            $_POST['promotion_text'] ?? null,
            $_POST['promotion_link'] ?? null,
            $_POST['promotion_button_text'] ?? null,
            $_POST['promotion_bg_color'] ?? '#dc3545',
            $_POST['promotion_text_color'] ?? '#ffffff'
        ]);
        
        $successMessage = 'Configuración de marketing guardada exitosamente';
        
    } catch (Exception $e) {
        $errorMessage = 'Error al guardar configuración: ' . $e->getMessage();
    }
}

// Get current settings
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM marketing_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
} catch (Exception $e) {
    $settings = null;
}
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Marketing - DentexaPro Admin</title>
  <meta name="description" content="Configuración de marketing del panel de administración de DentexaPro">
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

  <!-- Admin Marketing -->
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
                <i class="bi bi-megaphone me-2"></i>Marketing y Promociones
              </h1>
              <p class="text-light opacity-75 mb-0">Gestiona banners promocionales y campañas de marketing</p>
            </div>
          </div>

          <!-- Success/Error Messages -->
          <?php if (isset($successMessage)): ?>
          <div class="alert alert-success alert-dismissible fade show glass-card mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $successMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <?php if (isset($errorMessage)): ?>
          <div class="alert alert-danger alert-dismissible fade show glass-card mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php endif; ?>

          <!-- Promotion Banner Configuration -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <form method="POST" class="row g-4">
              <div class="col-12">
                <div class="d-flex align-items-center justify-content-between mb-4">
                  <h4 class="text-white mb-0">
                    <i class="bi bi-flag me-2"></i>Banner promocional
                  </h4>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="promotion_enabled" name="promotion_enabled" 
                           <?php echo ($settings && $settings['promotion_enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label text-light" for="promotion_enabled">
                      Mostrar banner en la página principal
                    </label>
                  </div>
                </div>
              </div>

              <div id="promotionFields" style="<?php echo ($settings && $settings['promotion_enabled']) ? '' : 'display: none;'; ?>">
                <div class="col-12">
                  <label class="form-label text-light">Texto promocional *</label>
                  <textarea name="promotion_text" class="form-control glass-input" rows="2" 
                            placeholder="¡Oferta especial! 50% de descuento en tu primer mes..."><?php echo htmlspecialchars($settings['promotion_text'] ?? ''); ?></textarea>
                  <small class="text-light opacity-75">Mensaje que aparecerá en el banner</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label text-light">Enlace de la promoción</label>
                  <input type="url" name="promotion_link" class="form-control glass-input" 
                         placeholder="https://..." 
                         value="<?php echo htmlspecialchars($settings['promotion_link'] ?? ''); ?>">
                  <small class="text-light opacity-75">URL a donde dirigir cuando hagan clic</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label text-light">Texto del botón</label>
                  <input type="text" name="promotion_button_text" class="form-control glass-input" 
                         placeholder="¡Aprovechá ahora!" 
                         value="<?php echo htmlspecialchars($settings['promotion_button_text'] ?? ''); ?>">
                  <small class="text-light opacity-75">Texto del botón de acción (opcional)</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label text-light">Color de fondo</label>
                  <div class="input-group">
                    <input type="color" name="promotion_bg_color" class="form-control form-control-color glass-input" 
                           value="<?php echo htmlspecialchars($settings['promotion_bg_color'] ?? '#dc3545'); ?>">
                    <input type="text" class="form-control glass-input" 
                           value="<?php echo htmlspecialchars($settings['promotion_bg_color'] ?? '#dc3545'); ?>" 
                           readonly>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label text-light">Color del texto</label>
                  <div class="input-group">
                    <input type="color" name="promotion_text_color" class="form-control form-control-color glass-input" 
                           value="<?php echo htmlspecialchars($settings['promotion_text_color'] ?? '#ffffff'); ?>">
                    <input type="text" class="form-control glass-input" 
                           value="<?php echo htmlspecialchars($settings['promotion_text_color'] ?? '#ffffff'); ?>" 
                           readonly>
                  </div>
                </div>
              </div>

              <!-- Preview -->
              <div class="col-12">
                <h5 class="text-white mb-3">
                  <i class="bi bi-eye me-2"></i>Vista previa
                </h5>
                <div id="promotionPreview" class="border border-secondary rounded p-3" 
                     style="background-color: <?php echo htmlspecialchars($settings['promotion_bg_color'] ?? '#dc3545'); ?>; 
                            color: <?php echo htmlspecialchars($settings['promotion_text_color'] ?? '#ffffff'); ?>;">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1">
                      <i class="bi bi-megaphone me-3"></i>
                      <span id="previewText">
                        <?php echo htmlspecialchars($settings['promotion_text'] ?? 'Tu mensaje promocional aparecerá aquí...'); ?>
                      </span>
                      <?php if ($settings && $settings['promotion_button_text']): ?>
                      <button class="btn btn-light btn-sm ms-3" id="previewButton">
                        <?php echo htmlspecialchars($settings['promotion_button_text']); ?>
                      </button>
                      <?php endif; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-3" aria-label="Cerrar"></button>
                  </div>
                </div>
              </div>

              <!-- Save Button -->
              <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-check-lg me-2"></i>Guardar configuración
                </button>
              </div>
            </form>
          </div>

          <!-- Marketing Analytics -->
          <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
            <h4 class="text-white mb-4">
              <i class="bi bi-graph-up me-2"></i>Estadísticas de marketing
            </h4>
            
            <div class="row g-4">
              <div class="col-md-3">
                <div class="glass-card p-3 text-center">
                  <div class="feature-icon mx-auto mb-3">
                    <i class="bi bi-eye"></i>
                  </div>
                  <h3 class="text-white mb-1" id="bannerViews">0</h3>
                  <p class="text-light opacity-75 mb-0">Visualizaciones</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="glass-card p-3 text-center">
                  <div class="feature-icon mx-auto mb-3">
                    <i class="bi bi-cursor"></i>
                  </div>
                  <h3 class="text-white mb-1" id="bannerClicks">0</h3>
                  <p class="text-light opacity-75 mb-0">Clics</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="glass-card p-3 text-center">
                  <div class="feature-icon mx-auto mb-3">
                    <i class="bi bi-percent"></i>
                  </div>
                  <h3 class="text-white mb-1" id="bannerCTR">0%</h3>
                  <p class="text-light opacity-75 mb-0">CTR</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="glass-card p-3 text-center">
                  <div class="feature-icon mx-auto mb-3">
                    <i class="bi bi-people"></i>
                  </div>
                  <h3 class="text-white mb-1" id="bannerConversions">0</h3>
                  <p class="text-light opacity-75 mb-0">Conversiones</p>
                </div>
              </div>
            </div>

            <div class="mt-4 p-3 glass-card">
              <small class="text-info">
                <i class="bi bi-info-circle me-1"></i>
                Las estadísticas de marketing estarán disponibles en próximas versiones
              </small>
            </div>
          </div>

          <!-- Campaign Templates -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <h4 class="text-white mb-4">
              <i class="bi bi-collection me-2"></i>Plantillas de campaña
            </h4>
            
            <div class="row g-3">
              <div class="col-md-6">
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="text-white mb-0">
                      <i class="bi bi-percent me-2"></i>Descuento especial
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('discount')">
                      <i class="bi bi-arrow-right"></i>
                    </button>
                  </div>
                  <p class="text-light opacity-75 small mb-0">
                    "¡50% OFF en tu primer mes! Aprovechá esta oferta limitada y comenzá a digitalizar tu consultorio."
                  </p>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="text-white mb-0">
                      <i class="bi bi-gift me-2"></i>Prueba gratuita
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('trial')">
                      <i class="bi bi-arrow-right"></i>
                    </button>
                  </div>
                  <p class="text-light opacity-75 small mb-0">
                    "¡Probá DentexaPro GRATIS por 30 días! Sin tarjeta de crédito, sin compromisos."
                  </p>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="text-white mb-0">
                      <i class="bi bi-star me-2"></i>Nuevo plan
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('newplan')">
                      <i class="bi bi-arrow-right"></i>
                    </button>
                  </div>
                  <p class="text-light opacity-75 small mb-0">
                    "🚀 ¡Nuevo plan Enterprise! Más funciones, mejor precio. Conocé todas las novedades."
                  </p>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="glass-card p-3">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="text-white mb-0">
                      <i class="bi bi-calendar-event me-2"></i>Evento especial
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('event')">
                      <i class="bi bi-arrow-right"></i>
                    </button>
                  </div>
                  <p class="text-light opacity-75 small mb-0">
                    "📅 Webinar GRATUITO: 'Digitalización del consultorio dental' - 15 de enero, 19hs."
                  </p>
                </div>
              </div>
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

    // Toggle promotion fields
    const promotionToggle = document.getElementById('promotion_enabled');
    const promotionFields = document.getElementById('promotionFields');

    if (promotionToggle && promotionFields) {
      promotionToggle.addEventListener('change', (e) => {
        promotionFields.style.display = e.target.checked ? 'block' : 'none';
        updatePreview();
      });
    }

    // Live preview functionality
    const promotionText = document.querySelector('textarea[name="promotion_text"]');
    const promotionButtonText = document.querySelector('input[name="promotion_button_text"]');
    const promotionBgColor = document.querySelector('input[name="promotion_bg_color"]');
    const promotionTextColor = document.querySelector('input[name="promotion_text_color"]');

    if (promotionText) {
      promotionText.addEventListener('input', updatePreview);
    }
    if (promotionButtonText) {
      promotionButtonText.addEventListener('input', updatePreview);
    }
    if (promotionBgColor) {
      promotionBgColor.addEventListener('input', updatePreview);
    }
    if (promotionTextColor) {
      promotionTextColor.addEventListener('input', updatePreview);
    }

    function updatePreview() {
      const preview = document.getElementById('promotionPreview');
      const previewText = document.getElementById('previewText');
      const previewButton = document.getElementById('previewButton');
      
      if (!preview || !previewText) return;

      const text = promotionText?.value || 'Tu mensaje promocional aparecerá aquí...';
      const buttonText = promotionButtonText?.value || '';
      const bgColor = promotionBgColor?.value || '#dc3545';
      const textColor = promotionTextColor?.value || '#ffffff';

      preview.style.backgroundColor = bgColor;
      preview.style.color = textColor;
      previewText.textContent = text;

      // Update button
      if (buttonText.trim()) {
        if (!previewButton) {
          const button = document.createElement('button');
          button.id = 'previewButton';
          button.className = 'btn btn-light btn-sm ms-3';
          button.textContent = buttonText;
          previewText.parentNode.insertBefore(button, previewText.nextSibling);
        } else {
          previewButton.textContent = buttonText;
          previewButton.style.display = 'inline-block';
        }
      } else if (previewButton) {
        previewButton.style.display = 'none';
      }

      // Update color input displays
      const bgColorDisplay = promotionBgColor?.nextElementSibling;
      const textColorDisplay = promotionTextColor?.nextElementSibling;
      
      if (bgColorDisplay) bgColorDisplay.value = bgColor;
      if (textColorDisplay) textColorDisplay.value = textColor;
    }

    // Template functions
    function useTemplate(templateType) {
      const templates = {
        discount: {
          text: '¡50% OFF en tu primer mes! Aprovechá esta oferta limitada y comenzá a digitalizar tu consultorio.',
          button: '¡Aprovechá ahora!',
          link: 'registro.php',
          bgColor: '#dc3545',
          textColor: '#ffffff'
        },
        trial: {
          text: '¡Probá DentexaPro GRATIS por 30 días! Sin tarjeta de crédito, sin compromisos.',
          button: 'Comenzar prueba',
          link: 'registro.php',
          bgColor: '#28a745',
          textColor: '#ffffff'
        },
        newplan: {
          text: '🚀 ¡Nuevo plan Enterprise! Más funciones, mejor precio. Conocé todas las novedades.',
          button: 'Ver planes',
          link: 'index.php#pricing',
          bgColor: '#ffc107',
          textColor: '#000000'
        },
        event: {
          text: '📅 Webinar GRATUITO: "Digitalización del consultorio dental" - 15 de enero, 19hs.',
          button: 'Registrarme',
          link: '#',
          bgColor: '#17a2b8',
          textColor: '#ffffff'
        }
      };

      const template = templates[templateType];
      if (!template) return;

      // Fill form with template data
      if (promotionText) promotionText.value = template.text;
      if (promotionButtonText) promotionButtonText.value = template.button;
      if (document.querySelector('input[name="promotion_link"]')) {
        document.querySelector('input[name="promotion_link"]').value = template.link;
      }
      if (promotionBgColor) promotionBgColor.value = template.bgColor;
      if (promotionTextColor) promotionTextColor.value = template.textColor;

      // Enable promotion if not already enabled
      if (promotionToggle && !promotionToggle.checked) {
        promotionToggle.checked = true;
        promotionFields.style.display = 'block';
      }

      // Update preview
      updatePreview();

      // Show success message
      const toast = document.createElement('div');
      toast.className = 'position-fixed top-0 end-0 m-3 alert alert-info glass-card';
      toast.style.zIndex = '9999';
      toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>Plantilla aplicada. Guarda los cambios para activarla.';
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.remove();
      }, 3000);
    }

    // Initialize preview on page load
    document.addEventListener('DOMContentLoaded', updatePreview);
  </script>
</body>
</html>