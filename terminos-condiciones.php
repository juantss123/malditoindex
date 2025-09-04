<?php
session_start();
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Términos y Condiciones - DentexaPro</title>
  <meta name="description" content="Términos y condiciones de uso de DentexaPro - Plataforma de gestión para dentistas">
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
    .legal-content {
      color: rgba(255,255,255,0.9);
      line-height: 1.8;
    }
    .legal-content h2 {
      color: #68c4ff;
      margin-top: 2.5rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid rgba(47,150,238,0.3);
      padding-bottom: 0.5rem;
    }
    .legal-content h3 {
      color: #9ad8ff;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }
    .legal-content p {
      margin-bottom: 1.2rem;
    }
    .legal-content ul, .legal-content ol {
      margin-bottom: 1.5rem;
      padding-left: 1.5rem;
    }
    .legal-content li {
      margin-bottom: 0.5rem;
    }
    .legal-content strong {
      color: #68c4ff;
    }
    .legal-toc {
      background: rgba(47,150,238,0.1);
      border: 1px solid rgba(47,150,238,0.3);
      border-radius: 12px;
      padding: 1.5rem;
    }
    .legal-toc a {
      color: #68c4ff;
      text-decoration: none;
      display: block;
      padding: 0.5rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .legal-toc a:hover {
      color: #9ad8ff;
      padding-left: 0.5rem;
      transition: all 0.3s ease;
    }
    .legal-toc a:last-child {
      border-bottom: none;
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
        <a href="index.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al inicio
        </a>
      </div>
    </div>
  </nav>

  <!-- Terms and Conditions -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="800">
            <h1 class="text-white mb-3">
              <i class="bi bi-file-text me-2"></i>Términos y Condiciones
            </h1>
            <p class="text-light opacity-85">
              Última actualización: <?php echo date('d/m/Y'); ?>
            </p>
          </div>

          <!-- Table of Contents -->
          <div class="legal-toc mb-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <h3 class="text-white mb-3">
              <i class="bi bi-list-ul me-2"></i>Índice de contenidos
            </h3>
            <a href="#aceptacion">1. Aceptación de los términos</a>
            <a href="#descripcion">2. Descripción del servicio</a>
            <a href="#registro">3. Registro y cuenta de usuario</a>
            <a href="#suscripcion">4. Suscripción y pagos</a>
            <a href="#uso">5. Uso del servicio</a>
            <a href="#datos">6. Protección de datos</a>
            <a href="#responsabilidades">7. Responsabilidades</a>
            <a href="#propiedad">8. Propiedad intelectual</a>
            <a href="#suspension">9. Suspensión y terminación</a>
            <a href="#limitaciones">10. Limitaciones de responsabilidad</a>
            <a href="#modificaciones">11. Modificaciones</a>
            <a href="#contacto">12. Contacto</a>
          </div>

          <!-- Content -->
          <div class="glass-card p-4 p-sm-5 legal-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
            
            <h2 id="aceptacion">1. Aceptación de los términos</h2>
            <p>Al acceder y utilizar DentexaPro, usted acepta estar sujeto a estos términos y condiciones de uso. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestro servicio.</p>
            <p>Estos términos constituyen un acuerdo legal vinculante entre usted y DentexaPro. El uso continuado del servicio implica la aceptación de cualquier modificación a estos términos.</p>

            <h2 id="descripcion">2. Descripción del servicio</h2>
            <p><strong>DentexaPro</strong> es una plataforma de gestión integral para consultorios dentales que incluye:</p>
            <ul>
              <li>Sistema de gestión de citas y agenda</li>
              <li>Historia clínica digital</li>
              <li>Gestión de pacientes</li>
              <li>Sistema de facturación</li>
              <li>Recordatorios automáticos</li>
              <li>Portal del paciente</li>
              <li>Reportes y analíticas</li>
              <li>Gestión de inventario</li>
            </ul>
            <p>El servicio se proporciona "tal como está" y nos reservamos el derecho de modificar, suspender o discontinuar cualquier aspecto del servicio en cualquier momento.</p>

            <h2 id="registro">3. Registro y cuenta de usuario</h2>
            <h3>3.1 Requisitos de registro</h3>
            <p>Para utilizar DentexaPro, debe:</p>
            <ul>
              <li>Ser mayor de 18 años</li>
              <li>Ser un profesional de la salud dental licenciado</li>
              <li>Proporcionar información veraz y actualizada</li>
              <li>Mantener la confidencialidad de sus credenciales de acceso</li>
            </ul>

            <h3>3.2 Responsabilidades del usuario</h3>
            <p>Usted es responsable de:</p>
            <ul>
              <li>Mantener la seguridad de su cuenta</li>
              <li>Todas las actividades que ocurran bajo su cuenta</li>
              <li>Notificar inmediatamente cualquier uso no autorizado</li>
              <li>Mantener actualizada su información de contacto</li>
            </ul>

            <h2 id="suscripcion">4. Suscripción y pagos</h2>
            <h3>4.1 Planes de suscripción</h3>
            <p>DentexaPro ofrece diferentes planes de suscripción mensual y anual. Los precios están claramente indicados en nuestro sitio web y pueden variar según el plan seleccionado.</p>

            <h3>4.2 Período de prueba</h3>
            <p>Ofrecemos un período de prueba gratuita de 15 días. Durante este período, tendrá acceso completo a todas las funcionalidades del plan seleccionado.</p>

            <h3>4.3 Facturación y pagos</h3>
            <ul>
              <li>Los pagos se procesan de forma segura a través de nuestros proveedores de pago</li>
              <li>Las suscripciones se renuevan automáticamente</li>
              <li>Los precios incluyen todos los impuestos aplicables</li>
              <li>No se realizan reembolsos por períodos parciales no utilizados</li>
            </ul>

            <h2 id="uso">5. Uso del servicio</h2>
            <h3>5.1 Uso permitido</h3>
            <p>DentexaPro está destinado exclusivamente para la gestión de consultorios dentales y actividades relacionadas con la práctica odontológica profesional.</p>

            <h3>5.2 Uso prohibido</h3>
            <p>Está prohibido:</p>
            <ul>
              <li>Utilizar el servicio para actividades ilegales</li>
              <li>Intentar acceder a cuentas de otros usuarios</li>
              <li>Realizar ingeniería inversa del software</li>
              <li>Sobrecargar o interferir con los servidores</li>
              <li>Compartir credenciales de acceso con terceros no autorizados</li>
            </ul>

            <h2 id="datos">6. Protección de datos</h2>
            <h3>6.1 Datos del paciente</h3>
            <p>Usted mantiene la propiedad y control total de todos los datos de pacientes ingresados en el sistema. DentexaPro actúa únicamente como procesador de datos bajo sus instrucciones.</p>

            <h3>6.2 Seguridad</h3>
            <p>Implementamos medidas de seguridad técnicas y organizativas apropiadas para proteger sus datos, incluyendo:</p>
            <ul>
              <li>Cifrado SSL/TLS para todas las transmisiones</li>
              <li>Copias de seguridad automáticas diarias</li>
              <li>Control de acceso basado en roles</li>
              <li>Monitoreo continuo de seguridad</li>
            </ul>

            <h3>6.3 Exportación de datos</h3>
            <p>Puede exportar sus datos en cualquier momento en formatos estándar. En caso de cancelación del servicio, tendrá 30 días para exportar sus datos antes de que sean eliminados permanentemente.</p>

            <h2 id="responsabilidades">7. Responsabilidades</h2>
            <h3>7.1 Responsabilidades de DentexaPro</h3>
            <p>Nos comprometemos a:</p>
            <ul>
              <li>Mantener el servicio disponible con un uptime del 99.5%</li>
              <li>Proporcionar soporte técnico durante horarios comerciales</li>
              <li>Realizar copias de seguridad regulares</li>
              <li>Notificar sobre mantenimientos programados</li>
            </ul>

            <h3>7.2 Responsabilidades del usuario</h3>
            <p>Usted es responsable de:</p>
            <ul>
              <li>El cumplimiento de las regulaciones locales de salud</li>
              <li>La veracidad y legalidad de los datos ingresados</li>
              <li>El uso apropiado del sistema</li>
              <li>Mantener actualizado su navegador y sistema operativo</li>
            </ul>

            <h2 id="propiedad">8. Propiedad intelectual</h2>
            <p>DentexaPro y todos sus componentes, incluyendo pero no limitado a software, diseño, texto, gráficos y marcas comerciales, son propiedad de DentexaPro y están protegidos por las leyes de propiedad intelectual.</p>
            <p>Se le otorga una licencia limitada, no exclusiva y revocable para utilizar el servicio únicamente para los fines previstos.</p>

            <h2 id="suspension">9. Suspensión y terminación</h2>
            <h3>9.1 Terminación por parte del usuario</h3>
            <p>Puede cancelar su suscripción en cualquier momento desde su panel de usuario. La cancelación será efectiva al final del período de facturación actual.</p>

            <h3>9.2 Terminación por parte de DentexaPro</h3>
            <p>Nos reservamos el derecho de suspender o terminar su cuenta si:</p>
            <ul>
              <li>Viola estos términos y condiciones</li>
              <li>No realiza los pagos correspondientes</li>
              <li>Utiliza el servicio de manera que pueda dañar a otros usuarios</li>
              <li>Proporciona información falsa o engañosa</li>
            </ul>

            <h2 id="limitaciones">10. Limitaciones de responsabilidad</h2>
            <p>En la máxima medida permitida por la ley:</p>
            <ul>
              <li>DentexaPro no será responsable por daños indirectos, incidentales o consecuentes</li>
              <li>Nuestra responsabilidad total no excederá el monto pagado por el servicio en los últimos 12 meses</li>
              <li>No garantizamos que el servicio esté libre de errores o interrupciones</li>
              <li>El usuario es responsable de mantener copias de seguridad adicionales de sus datos</li>
            </ul>

            <h2 id="modificaciones">11. Modificaciones</h2>
            <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Las modificaciones serán notificadas a través de:</p>
            <ul>
              <li>Notificación en el panel de usuario</li>
              <li>Email a la dirección registrada</li>
              <li>Publicación en nuestro sitio web</li>
            </ul>
            <p>El uso continuado del servicio después de las modificaciones constituye la aceptación de los nuevos términos.</p>

            <h2 id="contacto">12. Contacto</h2>
            <p>Para cualquier consulta sobre estos términos y condiciones, puede contactarnos a través de:</p>
            <div class="glass-card p-3 mt-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-envelope text-primary me-2"></i>
                    <div>
                      <strong class="text-white">Email:</strong>
                      <div class="text-light">legal@dentexapro.com</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-whatsapp text-success me-2"></i>
                    <div>
                      <strong class="text-white">WhatsApp:</strong>
                      <div class="text-light">+54 9 11 1234-5678</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-geo-alt text-info me-2"></i>
                    <div>
                      <strong class="text-white">Dirección:</strong>
                      <div class="text-light">Buenos Aires, Argentina</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-clock text-warning me-2"></i>
                    <div>
                      <strong class="text-white">Horario:</strong>
                      <div class="text-light">Lun-Vie 9:00-18:00</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 p-3 glass-card">
              <p class="text-center text-light opacity-75 mb-0">
                <i class="bi bi-calendar-check me-2"></i>
                Estos términos y condiciones son efectivos a partir del <?php echo date('d/m/Y'); ?> y se aplican a todos los usuarios de DentexaPro.
              </p>
            </div>
          </div>

          <!-- Back to Registration -->
          <div class="text-center mt-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
            <a href="registro.php" class="btn btn-primary btn-lg">
              <i class="bi bi-arrow-left me-2"></i>Volver al registro
            </a>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-4 border-top border-ink-subtle">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <div class="d-flex align-items-center gap-2">
            <img src="assets/img/logo.svg" width="24" height="24" alt="DentexaPro logo">
            <strong class="text-white">DentexaPro</strong>
          </div>
          <p class="small text-light opacity-75 mb-0">© <?php echo date('Y'); ?> DentexaPro. Todos los derechos reservados.</p>
        </div>
        <div class="col-md-6 text-md-end">
          <a href="politica-privacidad.php" class="link-light me-3">Política de Privacidad</a>
          <a href="index.php" class="link-light">Inicio</a>
        </div>
      </div>
    </div>
  </footer>

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

    // Smooth scroll for table of contents
    document.querySelectorAll('.legal-toc a').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>
</body>
</html>