<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mantenimiento - DentexaPro</title>
  <meta name="description" content="DentexaPro está en mantenimiento. Volveremos pronto con mejoras.">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- App styles -->
  <link href="assets/css/styles.css" rel="stylesheet">

  <style>
    /* Enhanced maintenance page styles */
    .maintenance-hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    .maintenance-icon {
      font-size: 8rem;
      color: #2F96EE;
      animation: float-maintenance 3s ease-in-out infinite;
      filter: drop-shadow(0 10px 30px rgba(47,150,238,0.4));
    }

    @keyframes float-maintenance {
      0%, 100% { 
        transform: translateY(0px) scale(1);
        opacity: 0.8;
      }
      50% { 
        transform: translateY(-20px) scale(1.05);
        opacity: 1;
      }
    }

    .maintenance-card {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 24px;
      backdrop-filter: saturate(120%) blur(20px);
      -webkit-backdrop-filter: saturate(120%) blur(20px);
      box-shadow: 0 30px 80px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
    }

    .maintenance-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 40px 100px rgba(47,150,238,0.2);
    }

    .countdown-container {
      background: linear-gradient(135deg, rgba(47,150,238,0.2), rgba(104,196,255,0.1));
      border: 1px solid rgba(47,150,238,0.3);
      border-radius: 20px;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }

    .countdown-container::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: conic-gradient(from 0deg, transparent, rgba(47,150,238,0.1), transparent);
      animation: rotate-border 4s linear infinite;
      z-index: -1;
    }

    @keyframes rotate-border {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .countdown-item {
      text-align: center;
      position: relative;
    }

    .countdown-number {
      font-size: 3rem;
      font-weight: 800;
      color: #68c4ff;
      display: block;
      text-shadow: 0 0 20px rgba(104,196,255,0.5);
      animation: pulse-number 2s ease-in-out infinite;
    }

    @keyframes pulse-number {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .countdown-label {
      color: rgba(255,255,255,0.9);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
      margin-top: 0.5rem;
    }

    .maintenance-progress {
      height: 8px;
      background: rgba(255,255,255,0.1);
      border-radius: 20px;
      overflow: hidden;
      position: relative;
      margin: 2rem 0;
    }

    .maintenance-progress::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, #2F96EE, #68c4ff, transparent);
      animation: loading-wave 2.5s ease-in-out infinite;
    }

    @keyframes loading-wave {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    .contact-card {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 16px;
      padding: 1.5rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .contact-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      transition: left 0.6s ease;
    }

    .contact-card:hover::before {
      left: 100%;
    }

    .contact-card:hover {
      transform: translateY(-3px);
      border-color: rgba(47,150,238,0.4);
      box-shadow: 0 15px 40px rgba(47,150,238,0.2);
    }

    .social-links {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
    }

    .social-link {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #68c4ff;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 1.2rem;
    }

    .social-link:hover {
      background: rgba(47,150,238,0.3);
      border-color: rgba(47,150,238,0.5);
      color: #fff;
      transform: translateY(-3px) scale(1.1);
      box-shadow: 0 10px 25px rgba(47,150,238,0.3);
    }

    .floating-particles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      overflow: hidden;
    }

    .particle {
      position: absolute;
      width: 6px;
      height: 6px;
      background: rgba(47,150,238,0.6);
      border-radius: 50%;
      animation: float-particle 8s ease-in-out infinite;
    }

    .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
    .particle:nth-child(2) { left: 20%; animation-delay: 1s; }
    .particle:nth-child(3) { left: 30%; animation-delay: 2s; }
    .particle:nth-child(4) { left: 40%; animation-delay: 3s; }
    .particle:nth-child(5) { left: 50%; animation-delay: 4s; }
    .particle:nth-child(6) { left: 60%; animation-delay: 5s; }
    .particle:nth-child(7) { left: 70%; animation-delay: 6s; }
    .particle:nth-child(8) { left: 80%; animation-delay: 7s; }

    @keyframes float-particle {
      0%, 100% { 
        transform: translateY(100vh) translateX(0px);
        opacity: 0;
      }
      10%, 90% {
        opacity: 1;
      }
      50% { 
        transform: translateY(-20px) translateX(20px);
        opacity: 0.8;
      }
    }

    .status-indicator {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ffc107;
      animation: pulse-status 2s ease-in-out infinite;
      margin-right: 0.5rem;
    }

    @keyframes pulse-status {
      0%, 100% { 
        box-shadow: 0 0 0 0 rgba(255,193,7,0.7);
        transform: scale(1);
      }
      50% { 
        box-shadow: 0 0 0 10px rgba(255,193,7,0);
        transform: scale(1.1);
      }
    }

    .maintenance-logo {
      animation: logo-glow 3s ease-in-out infinite;
    }

    @keyframes logo-glow {
      0%, 100% { 
        filter: drop-shadow(0 0 10px rgba(47,150,238,0.5));
      }
      50% { 
        filter: drop-shadow(0 0 25px rgba(47,150,238,0.8));
      }
    }

    .feature-preview {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px;
      padding: 1rem;
      margin: 0.5rem 0;
      transition: all 0.3s ease;
    }

    .feature-preview:hover {
      background: rgba(47,150,238,0.1);
      border-color: rgba(47,150,238,0.3);
      transform: translateX(5px);
    }

    .feature-preview i {
      color: #68c4ff;
      margin-right: 0.75rem;
      font-size: 1.1rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .maintenance-icon {
        font-size: 5rem;
      }
      
      .countdown-number {
        font-size: 2rem;
      }
      
      .countdown-container {
        padding: 1.5rem;
      }
      
      .social-link {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
      }
    }

    /* Accessibility improvements */
    @media (prefers-reduced-motion: reduce) {
      .maintenance-icon,
      .countdown-number,
      .particle,
      .maintenance-logo {
        animation: none !important;
      }
      
      .maintenance-progress::before {
        animation: none !important;
      }
    }
  </style>
</head>
<body class="bg-dark-ink text-body min-vh-100">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Floating Particles -->
  <div class="floating-particles" aria-hidden="true">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <!-- Maintenance Page -->
  <div class="maintenance-hero">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
          <div class="text-center">
            <!-- Logo with glow effect -->
            <div class="mb-5">
              <img src="assets/img/logo.svg" width="80" height="80" alt="DentexaPro logo" class="maintenance-logo mb-4" />
              <h2 class="text-white mb-2">DentexaPro</h2>
              <div class="d-flex align-items-center justify-content-center">
                <div class="status-indicator"></div>
                <span class="text-warning small fw-medium">Sistema en mantenimiento</span>
              </div>
            </div>

            <!-- Enhanced Maintenance Icon -->
            <div class="mb-5">
              <div class="position-relative d-inline-block">
                <i class="bi bi-gear-fill maintenance-icon"></i>
                <div class="position-absolute top-50 start-50 translate-middle">
                  <i class="bi bi-wrench text-white" style="font-size: 2rem; animation: rotate 2s linear infinite;"></i>
                </div>
              </div>
            </div>

            <!-- Main Content Card -->
            <div class="maintenance-card p-5 mb-4">
              <h1 class="text-white mb-4 display-6 fw-bold">
                <?php
                try {
                    require_once 'config/database.php';
                    $database = new Database();
                    $db = $database->getConnection();
                    $stmt = $db->prepare("SELECT maintenance_title FROM maintenance_settings WHERE id = 1");
                    $stmt->execute();
                    $maintenance = $stmt->fetch();
                    echo htmlspecialchars($maintenance['maintenance_title'] ?? 'Estamos mejorando DentexaPro');
                } catch (Exception $e) {
                    echo 'Estamos mejorando DentexaPro';
                }
                ?>
              </h1>
              
              <p class="text-light opacity-90 lead mb-4">
                <?php
                try {
                    $stmt = $db->prepare("SELECT maintenance_message FROM maintenance_settings WHERE id = 1");
                    $stmt->execute();
                    $maintenance = $stmt->fetch();
                    echo htmlspecialchars($maintenance['maintenance_message'] ?? 'Estamos trabajando en mejoras para ofrecerte una mejor experiencia. Volveremos pronto con nuevas funcionalidades.');
                } catch (Exception $e) {
                    echo 'Estamos trabajando en mejoras para ofrecerte una mejor experiencia. Volveremos pronto con nuevas funcionalidades.';
                }
                ?>
              </p>

              <!-- Enhanced Progress Bar -->
              <div class="maintenance-progress"></div>

              <!-- What's Coming Preview -->
              <div class="mb-4">
                <h4 class="text-white mb-3">
                  <i class="bi bi-stars me-2"></i>¿Qué estamos mejorando?
                </h4>
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="feature-preview">
                      <i class="bi bi-lightning-charge"></i>
                      <span class="text-light">Rendimiento optimizado</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="feature-preview">
                      <i class="bi bi-palette"></i>
                      <span class="text-light">Interfaz renovada</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="feature-preview">
                      <i class="bi bi-shield-plus"></i>
                      <span class="text-light">Seguridad mejorada</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="feature-preview">
                      <i class="bi bi-robot"></i>
                      <span class="text-light">Nuevas funciones IA</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Countdown Timer (if end time is set) -->
              <?php
              $endTime = null;
              try {
                  $stmt = $db->prepare("SELECT maintenance_end_time FROM maintenance_settings WHERE id = 1");
                  $stmt->execute();
                  $maintenance = $stmt->fetch();
                  $endTime = $maintenance['maintenance_end_time'] ?? null;
              } catch (Exception $e) {
                  // No end time set
              }
              
              if ($endTime && strtotime($endTime) > time()):
              ?>
              <div class="countdown-container mb-4">
                <h4 class="text-white mb-4 text-center">
                  <i class="bi bi-clock-history me-2"></i>Tiempo estimado de finalización
                </h4>
                <div class="row g-3" id="countdown">
                  <div class="col-3">
                    <div class="countdown-item">
                      <span class="countdown-number" id="days">00</span>
                      <div class="countdown-label">Días</div>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="countdown-item">
                      <span class="countdown-number" id="hours">00</span>
                      <div class="countdown-label">Horas</div>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="countdown-item">
                      <span class="countdown-number" id="minutes">00</span>
                      <div class="countdown-label">Minutos</div>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="countdown-item">
                      <span class="countdown-number" id="seconds">00</span>
                      <div class="countdown-label">Segundos</div>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Enhanced Contact Info -->
              <div class="contact-card">
                <h5 class="text-white mb-4 text-center">
                  <i class="bi bi-headset me-2"></i>¿Necesitas ayuda urgente?
                </h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <a href="mailto:<?php
                    try {
                        $stmt = $db->prepare("SELECT maintenance_contact_email FROM maintenance_settings WHERE id = 1");
                        $stmt->execute();
                        $maintenance = $stmt->fetch();
                        echo htmlspecialchars($maintenance['maintenance_contact_email'] ?? 'soporte@dentexapro.com');
                    } catch (Exception $e) {
                        echo 'soporte@dentexapro.com';
                    }
                    ?>" class="btn btn-outline-light w-100 btn-lg">
                      <i class="bi bi-envelope-heart me-2"></i>Enviar email
                    </a>
                  </div>
                  <div class="col-md-6">
                    <a href="https://wa.me/5491112345678?text=Hola%2C%20necesito%20ayuda%20urgente%20con%20DentexaPro" target="_blank" class="btn btn-success w-100 btn-lg">
                      <i class="bi bi-whatsapp me-2"></i>WhatsApp
                    </a>
                  </div>
                </div>
                
                <!-- Additional contact methods -->
                <div class="row g-3 mt-2">
                  <div class="col-md-6">
                    <div class="text-center">
                      <i class="bi bi-telephone text-info fs-4 mb-2"></i>
                      <div class="text-white fw-medium">Teléfono</div>
                      <div class="text-light opacity-75">+54 9 11 1234-5678</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="text-center">
                      <i class="bi bi-clock text-warning fs-4 mb-2"></i>
                      <div class="text-white fw-medium">Horario</div>
                      <div class="text-light opacity-75">Lun-Vie 9:00-18:00</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Social Links -->
            <div class="text-center">
              <p class="text-light opacity-85 mb-3">Seguinos para estar al día con las novedades:</p>
              <div class="social-links">
                <a href="#" class="social-link" title="Facebook">
                  <i class="bi bi-facebook"></i>
                </a>
                <a href="#" class="social-link" title="Twitter">
                  <i class="bi bi-twitter"></i>
                </a>
                <a href="#" class="social-link" title="LinkedIn">
                  <i class="bi bi-linkedin"></i>
                </a>
                <a href="#" class="social-link" title="Instagram">
                  <i class="bi bi-instagram"></i>
                </a>
                <a href="#" class="social-link" title="YouTube">
                  <i class="bi bi-youtube"></i>
                </a>
              </div>
            </div>

            <!-- Fun fact -->
            <div class="mt-4 text-center">
              <div class="glass-card p-3">
                <p class="text-light opacity-75 mb-0 small">
                  <i class="bi bi-lightbulb text-warning me-2"></i>
                  <strong>¿Sabías que?</strong> Durante el mantenimiento, estamos implementando mejoras que harán DentexaPro hasta 40% más rápido.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <?php if ($endTime && strtotime($endTime) > time()): ?>
  <script>
    // Enhanced countdown timer with smooth animations
    const endTime = new Date('<?php echo date('c', strtotime($endTime)); ?>').getTime();
    
    function updateCountdown() {
      const now = new Date().getTime();
      const distance = endTime - now;
      
      if (distance < 0) {
        // Countdown finished, show completion message and reload
        document.getElementById('countdown').innerHTML = `
          <div class="col-12 text-center">
            <div class="text-success fs-2 mb-2">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="text-white fw-bold">¡Mantenimiento completado!</div>
            <div class="text-light opacity-75">Recargando página...</div>
          </div>
        `;
        setTimeout(() => location.reload(), 3000);
        return;
      }
      
      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);
      
      // Smooth number updates
      animateNumber('days', days);
      animateNumber('hours', hours);
      animateNumber('minutes', minutes);
      animateNumber('seconds', seconds);
    }
    
    function animateNumber(elementId, newValue) {
      const element = document.getElementById(elementId);
      if (element) {
        const currentValue = parseInt(element.textContent) || 0;
        if (currentValue !== newValue) {
          element.style.transform = 'scale(1.1)';
          element.style.color = '#9ad8ff';
          setTimeout(() => {
            element.textContent = newValue.toString().padStart(2, '0');
            element.style.transform = 'scale(1)';
            element.style.color = '#68c4ff';
          }, 150);
        }
      }
    }
    
    // Update countdown every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
    
    // Add some visual feedback when countdown updates
    setInterval(() => {
      const numbers = document.querySelectorAll('.countdown-number');
      numbers.forEach((num, index) => {
        setTimeout(() => {
          num.style.textShadow = '0 0 30px rgba(104,196,255,0.8)';
          setTimeout(() => {
            num.style.textShadow = '0 0 20px rgba(104,196,255,0.5)';
          }, 200);
        }, index * 100);
      });
    }, 5000);
  </script>
  <?php endif; ?>

  <script>
    // Add rotation animation to wrench icon
    const style = document.createElement('style');
    style.textContent = `
      @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);

    // Add some interactive effects
    document.addEventListener('DOMContentLoaded', () => {
      // Add hover effect to maintenance card
      const maintenanceCard = document.querySelector('.maintenance-card');
      if (maintenanceCard) {
        maintenanceCard.addEventListener('mouseenter', () => {
          maintenanceCard.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        maintenanceCard.addEventListener('mouseleave', () => {
          maintenanceCard.style.transform = 'translateY(0) scale(1)';
        });
      }

      // Add click effect to contact buttons
      const contactButtons = document.querySelectorAll('.contact-card .btn');
      contactButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          // Create ripple effect
          const ripple = document.createElement('span');
          ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            left: ${e.offsetX}px;
            top: ${e.offsetY}px;
            width: 20px;
            height: 20px;
            margin-left: -10px;
            margin-top: -10px;
          `;
          
          btn.style.position = 'relative';
          btn.style.overflow = 'hidden';
          btn.appendChild(ripple);
          
          setTimeout(() => ripple.remove(), 600);
        });
      });

      // Add typing effect to the main title (if no custom title is set)
      const title = document.querySelector('h1');
      if (title && title.textContent.includes('Estamos mejorando DentexaPro')) {
        const originalText = title.textContent;
        title.textContent = '';
        let i = 0;
        
        const typeWriter = () => {
          if (i < originalText.length) {
            title.textContent += originalText.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
          }
        };
        
        setTimeout(typeWriter, 1000);
      }

      // Add parallax effect to floating particles
      document.addEventListener('mousemove', (e) => {
        const particles = document.querySelectorAll('.particle');
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        particles.forEach((particle, index) => {
          const speed = (index + 1) * 0.5;
          const x = (mouseX - 0.5) * speed * 20;
          const y = (mouseY - 0.5) * speed * 20;
          particle.style.transform = `translate(${x}px, ${y}px)`;
        });
      });
    });

    // Add CSS for ripple animation
    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `
      @keyframes ripple {
        to {
          transform: scale(4);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(rippleStyle);
  </script>
</body>
</html>