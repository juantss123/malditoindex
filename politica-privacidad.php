<?php
session_start();
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Política de Privacidad - DentexaPro</title>
  <meta name="description" content="Política de privacidad de DentexaPro - Cómo protegemos y manejamos tus datos">
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
    .privacy-highlight {
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.3);
      border-radius: 8px;
      padding: 1rem;
      margin: 1rem 0;
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

  <!-- Privacy Policy -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="800">
            <h1 class="text-white mb-3">
              <i class="bi bi-shield-check me-2"></i>Política de Privacidad
            </h1>
            <p class="text-light opacity-85">
              Última actualización: <?php echo date('d/m/Y'); ?>
            </p>
          </div>

          <!-- Privacy Commitment -->
          <div class="privacy-highlight mb-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
            <div class="text-center">
              <i class="bi bi-shield-fill-check text-success fs-1 mb-3"></i>
              <h3 class="text-white mb-2">Nuestro compromiso con tu privacidad</h3>
              <p class="text-light opacity-85 mb-0">
                En DentexaPro, la protección de tus datos y los de tus pacientes es nuestra máxima prioridad. 
                Esta política explica cómo recopilamos, usamos y protegemos tu información.
              </p>
            </div>
          </div>

          <!-- Table of Contents -->
          <div class="legal-toc mb-5" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
            <h3 class="text-white mb-3">
              <i class="bi bi-list-ul me-2"></i>Índice de contenidos
            </h3>
            <a href="#informacion">1. Información que recopilamos</a>
            <a href="#uso-datos">2. Cómo usamos tu información</a>
            <a href="#compartir">3. Compartir información</a>
            <a href="#seguridad">4. Seguridad de los datos</a>
            <a href="#cookies">5. Cookies y tecnologías similares</a>
            <a href="#derechos">6. Tus derechos</a>
            <a href="#retencion">7. Retención de datos</a>
            <a href="#menores">8. Privacidad de menores</a>
            <a href="#internacional">9. Transferencias internacionales</a>
            <a href="#cambios">10. Cambios en esta política</a>
            <a href="#contacto-privacidad">11. Contacto</a>
          </div>

          <!-- Content -->
          <div class="glass-card p-4 p-sm-5 legal-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
            
            <h2 id="informacion">1. Información que recopilamos</h2>
            <h3>1.1 Información que nos proporcionas directamente</h3>
            <ul>
              <li><strong>Datos de registro:</strong> Nombre, apellido, email, teléfono, información profesional</li>
              <li><strong>Información del consultorio:</strong> Nombre de la clínica, dirección, especialidad</li>
              <li><strong>Datos de pacientes:</strong> Información que ingresas sobre tus pacientes (bajo tu control)</li>
              <li><strong>Comunicaciones:</strong> Mensajes que nos envías a través del sistema de soporte</li>
            </ul>

            <h3>1.2 Información que recopilamos automáticamente</h3>
            <ul>
              <li><strong>Datos de uso:</strong> Cómo utilizas la plataforma, funciones más usadas</li>
              <li><strong>Información técnica:</strong> Dirección IP, tipo de navegador, sistema operativo</li>
              <li><strong>Logs de actividad:</strong> Registros de acceso y actividades dentro del sistema</li>
            </ul>

            <h2 id="uso-datos">2. Cómo usamos tu información</h2>
            <p>Utilizamos tu información para:</p>
            <ul>
              <li><strong>Proporcionar el servicio:</strong> Gestionar tu cuenta y proporcionar las funcionalidades de DentexaPro</li>
              <li><strong>Comunicación:</strong> Enviarte notificaciones importantes, actualizaciones y soporte técnico</li>
              <li><strong>Mejoras del servicio:</strong> Analizar el uso para mejorar nuestras funcionalidades</li>
              <li><strong>Cumplimiento legal:</strong> Cumplir con obligaciones legales y regulatorias</li>
              <li><strong>Seguridad:</strong> Detectar y prevenir fraudes o uso no autorizado</li>
            </ul>

            <h2 id="compartir">3. Compartir información</h2>
            <h3>3.1 No vendemos tus datos</h3>
            <p>Nunca vendemos, alquilamos o comercializamos tu información personal a terceros.</p>

            <h3>3.2 Cuándo podemos compartir información</h3>
            <p>Podemos compartir información limitada en estas situaciones:</p>
            <ul>
              <li><strong>Proveedores de servicios:</strong> Empresas que nos ayudan a operar la plataforma (hosting, pagos)</li>
              <li><strong>Cumplimiento legal:</strong> Cuando sea requerido por ley o autoridades competentes</li>
              <li><strong>Protección de derechos:</strong> Para proteger nuestros derechos legales o los de nuestros usuarios</li>
              <li><strong>Consentimiento:</strong> Cuando tengas tu consentimiento explícito</li>
            </ul>

            <h2 id="seguridad">4. Seguridad de los datos</h2>
            <h3>4.1 Medidas de seguridad técnicas</h3>
            <ul>
              <li><strong>Cifrado:</strong> Todos los datos se transmiten usando cifrado SSL/TLS</li>
              <li><strong>Almacenamiento seguro:</strong> Datos almacenados en servidores seguros con acceso restringido</li>
              <li><strong>Autenticación:</strong> Sistemas de autenticación robustos y control de acceso</li>
              <li><strong>Monitoreo:</strong> Supervisión continua para detectar actividades sospechosas</li>
            </ul>

            <h3>4.2 Medidas organizativas</h3>
            <ul>
              <li>Acceso limitado a datos personales solo para personal autorizado</li>
              <li>Capacitación regular en seguridad y privacidad</li>
              <li>Políticas internas estrictas de manejo de datos</li>
              <li>Auditorías regulares de seguridad</li>
            </ul>

            <h2 id="cookies">5. Cookies y tecnologías similares</h2>
            <p>Utilizamos cookies y tecnologías similares para:</p>
            <ul>
              <li><strong>Funcionalidad:</strong> Mantener tu sesión activa y preferencias</li>
              <li><strong>Seguridad:</strong> Proteger contra ataques y uso no autorizado</li>
              <li><strong>Análisis:</strong> Entender cómo se usa la plataforma para mejorarla</li>
            </ul>
            <p>Puedes controlar las cookies a través de la configuración de tu navegador.</p>

            <h2 id="derechos">6. Tus derechos</h2>
            <p>Tienes derecho a:</p>
            <ul>
              <li><strong>Acceso:</strong> Solicitar una copia de tus datos personales</li>
              <li><strong>Rectificación:</strong> Corregir datos inexactos o incompletos</li>
              <li><strong>Eliminación:</strong> Solicitar la eliminación de tus datos personales</li>
              <li><strong>Portabilidad:</strong> Exportar tus datos en un formato legible</li>
              <li><strong>Oposición:</strong> Oponerte al procesamiento de tus datos en ciertas circunstancias</li>
              <li><strong>Limitación:</strong> Solicitar la limitación del procesamiento</li>
            </ul>

            <div class="privacy-highlight">
              <h4 class="text-success mb-2">
                <i class="bi bi-info-circle me-2"></i>Ejercer tus derechos
              </h4>
              <p class="mb-0">Para ejercer cualquiera de estos derechos, contáctanos en <strong>privacidad@dentexapro.com</strong>. Responderemos a tu solicitud dentro de 30 días.</p>
            </div>

            <h2 id="retencion">7. Retención de datos</h2>
            <h3>7.1 Datos de usuario</h3>
            <p>Mantenemos tus datos de cuenta mientras tu suscripción esté activa y durante 12 meses adicionales después de la cancelación para cumplir con obligaciones legales.</p>

            <h3>7.2 Datos de pacientes</h3>
            <p>Los datos de pacientes son controlados por ti como profesional de la salud. Puedes exportar y eliminar estos datos en cualquier momento. Después de la cancelación de tu cuenta, tienes 30 días para exportar todos los datos antes de que sean eliminados permanentemente.</p>

            <h2 id="menores">8. Privacidad de menores</h2>
            <p>DentexaPro no está dirigido a menores de 18 años. No recopilamos intencionalmente información personal de menores. Si descubrimos que hemos recopilado información de un menor, la eliminaremos inmediatamente.</p>
            <p>Los datos de pacientes menores de edad son responsabilidad del profesional de la salud que los ingresa, quien debe contar con el consentimiento apropiado de los padres o tutores.</p>

            <h2 id="internacional">9. Transferencias internacionales</h2>
            <p>Tus datos pueden ser procesados en servidores ubicados fuera de tu país de residencia. Cuando esto ocurra, implementamos salvaguardas apropiadas para proteger tu información, incluyendo:</p>
            <ul>
              <li>Contratos de transferencia de datos con proveedores</li>
              <li>Certificaciones de seguridad reconocidas internacionalmente</li>
              <li>Medidas técnicas y organizativas apropiadas</li>
            </ul>

            <h2 id="cambios">10. Cambios en esta política</h2>
            <p>Podemos actualizar esta política de privacidad ocasionalmente. Te notificaremos sobre cambios significativos a través de:</p>
            <ul>
              <li>Notificación en tu panel de usuario</li>
              <li>Email a tu dirección registrada</li>
              <li>Aviso en nuestro sitio web</li>
            </ul>
            <p>Te recomendamos revisar esta política periódicamente para mantenerte informado sobre cómo protegemos tu información.</p>

            <h2 id="contacto-privacidad">11. Contacto</h2>
            <p>Si tienes preguntas sobre esta política de privacidad o sobre cómo manejamos tus datos, puedes contactarnos:</p>
            
            <div class="glass-card p-3 mt-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-envelope text-primary me-2"></i>
                    <div>
                      <strong class="text-white">Email de privacidad:</strong>
                      <div class="text-light">privacidad@dentexapro.com</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-telephone text-success me-2"></i>
                    <div>
                      <strong class="text-white">Teléfono:</strong>
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
                      <strong class="text-white">Horario de atención:</strong>
                      <div class="text-light">Lun-Vie 9:00-18:00 ART</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Data Protection Officer -->
            <div class="privacy-highlight mt-4">
              <h4 class="text-info mb-2">
                <i class="bi bi-person-badge me-2"></i>Oficial de Protección de Datos
              </h4>
              <p class="mb-2">Para consultas específicas sobre protección de datos, puedes contactar directamente a nuestro Oficial de Protección de Datos:</p>
              <p class="mb-0">
                <strong>Email:</strong> dpo@dentexapro.com<br>
                <strong>Respuesta garantizada:</strong> Dentro de 72 horas
              </p>
            </div>

            <!-- Compliance -->
            <div class="mt-4 p-3 glass-card">
              <h4 class="text-white mb-3">
                <i class="bi bi-award me-2"></i>Cumplimiento normativo
              </h4>
              <div class="row g-3">
                <div class="col-md-4 text-center">
                  <i class="bi bi-shield-check text-success fs-3 mb-2"></i>
                  <div class="text-white fw-bold">GDPR</div>
                  <small class="text-light opacity-75">Reglamento Europeo</small>
                </div>
                <div class="col-md-4 text-center">
                  <i class="bi bi-file-medical text-info fs-3 mb-2"></i>
                  <div class="text-white fw-bold">HIPAA</div>
                  <small class="text-light opacity-75">Estándares de salud</small>
                </div>
                <div class="col-md-4 text-center">
                  <i class="bi bi-geo-alt text-warning fs-3 mb-2"></i>
                  <div class="text-white fw-bold">Ley Argentina</div>
                  <small class="text-light opacity-75">Protección de datos</small>
                </div>
              </div>
            </div>

            <div class="mt-4 p-3 glass-card">
              <p class="text-center text-light opacity-75 mb-0">
                <i class="bi bi-calendar-check me-2"></i>
                Esta política de privacidad es efectiva a partir del <?php echo date('d/m/Y'); ?> y se aplica a todos los usuarios de DentexaPro.
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
          <a href="terminos-condiciones.php" class="link-light me-3">Términos y Condiciones</a>
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