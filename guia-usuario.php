<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Guía de Usuario - DentexaPro</title>
  <meta name="description" content="Guía completa para usar DentexaPro y optimizar la gestión de tu clínica dental">
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
    /* Custom styles for user guide */
    .guide-sidebar {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 20px;
      backdrop-filter: saturate(120%) blur(12px);
      -webkit-backdrop-filter: saturate(120%) blur(12px);
      position: sticky;
      top: 100px;
      max-height: calc(100vh - 120px);
      overflow-y: auto;
    }

    .guide-nav .nav-link {
      color: rgba(255,255,255,0.8);
      padding: 12px 20px;
      border-radius: 12px;
      margin: 2px 0;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .guide-nav .nav-link:hover {
      background: rgba(47,150,238,0.15);
      color: #68c4ff;
      border-left-color: var(--primary);
    }

    .guide-nav .nav-link.active {
      background: rgba(47,150,238,0.2);
      color: #68c4ff;
      border-left-color: var(--primary);
      font-weight: 500;
    }

    .guide-content {
      scroll-margin-top: 100px;
    }

    .guide-content h3 {
      color: #fff;
      border-bottom: 2px solid var(--primary);
      padding-bottom: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .guide-content h4 {
      color: #68c4ff;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .guide-content p {
      color: rgba(255,255,255,0.85);
      line-height: 1.7;
      margin-bottom: 1rem;
    }

    .guide-content ul, .guide-content ol {
      color: rgba(255,255,255,0.85);
      margin-bottom: 1.5rem;
    }

    .guide-content li {
      margin-bottom: 0.5rem;
    }

    .guide-content strong {
      color: #68c4ff;
    }

    .guide-content code {
      background: rgba(47,150,238,0.2);
      color: #68c4ff;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: 'Courier New', monospace;
    }

    .search-highlight {
      background: rgba(255,193,7,0.3);
      color: #ffc107;
      padding: 2px 4px;
      border-radius: 3px;
    }

    .guide-search {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: #fff;
      border-radius: 12px;
    }

    .guide-search::placeholder {
      color: rgba(255,255,255,0.6);
    }

    .guide-search:focus {
      background: rgba(255,255,255,0.08);
      border-color: rgba(47,150,238,0.5);
      box-shadow: 0 0 0 0.25rem rgba(47,150,238,0.15);
      color: #fff;
    }

    .no-results {
      text-align: center;
      padding: 3rem;
      color: rgba(255,255,255,0.6);
    }

    /* Smooth scrolling */
    html {
      scroll-behavior: smooth;
    }

    /* Custom scrollbar for sidebar */
    .guide-sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .guide-sidebar::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.1);
      border-radius: 3px;
    }

    .guide-sidebar::-webkit-scrollbar-thumb {
      background: rgba(47,150,238,0.5);
      border-radius: 3px;
    }

    .guide-sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(47,150,238,0.7);
    }
  </style>
</head>
<body class="bg-dark-ink text-body min-vh-100">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="dashboard.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al dashboard
        </a>
        <a href="logout.php" class="btn btn-outline-light">
          <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
        </a>
      </div>
    </div>
  </nav>

  <!-- User Guide -->
  <main class="section-pt pb-5">
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-xl-2">
          <div class="guide-sidebar p-4" data-aos="slide-right" data-aos-duration="800">
            <div class="text-center mb-4">
              <h4 class="text-white mb-3">
                <i class="bi bi-book me-2"></i>Guía de Usuario
              </h4>
              <div class="position-relative">
                <input type="text" id="searchGuide" class="form-control guide-search" placeholder="Buscar en la guía...">
                <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-light opacity-75"></i>
              </div>
            </div>
            
            <nav class="guide-nav">
              <div class="nav flex-column">
                <a class="nav-link active" href="#introduction">
                  <i class="bi bi-house me-2"></i>Introducción
                </a>
                <a class="nav-link" href="#dashboard">
                  <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="#global-features">
                  <i class="bi bi-globe me-2"></i>Funciones Globales
                </a>
                <a class="nav-link" href="#appointments">
                  <i class="bi bi-calendar-event me-2"></i>Gestión de Citas
                </a>
                <a class="nav-link" href="#patients">
                  <i class="bi bi-people me-2"></i>Gestión de Pacientes
                </a>
                <a class="nav-link" href="#estimates">
                  <i class="bi bi-calculator me-2"></i>Presupuestos
                </a>
                <a class="nav-link" href="#billing">
                  <i class="bi bi-receipt me-2"></i>Facturación
                </a>
                <a class="nav-link" href="#inventory">
                  <i class="bi bi-box-seam me-2"></i>Control de Inventario
                </a>
                <a class="nav-link" href="#employees">
                  <i class="bi bi-person-badge me-2"></i>Gestión de Personal
                </a>
                <a class="nav-link" href="#messages">
                  <i class="bi bi-chat-dots me-2"></i>Mensajería Interna
                </a>
                <a class="nav-link" href="#reports">
                  <i class="bi bi-graph-up me-2"></i>Reportes
                </a>
                <a class="nav-link" href="#portal">
                  <i class="bi bi-person-circle me-2"></i>Portal del Paciente
                </a>
                <a class="nav-link" href="#system">
                  <i class="bi bi-shield-check me-2"></i>Sistema y Seguridad
                </a>
              </div>
            </nav>
          </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="800" id="guideHeader">
            <h1 class="text-white mb-3">
              <i class="bi bi-book me-2"></i>Guía Definitiva de <span class="gradient-text">DentexaPro</span>
            </h1>
            <p class="text-light opacity-85 lead">
              Tu manual completo para dominar DentexaPro y optimizar la gestión de tu clínica dental.
            </p>
          </div>

          <!-- Guide Sections -->
          <div id="guideContent">
            <!-- Introduction -->
            <section id="introduction" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
              <h3>
                <i class="bi bi-star me-2"></i>Bienvenido a DentexaPro
              </h3>
              <p><strong>DentexaPro</strong> es una solución integral diseñada para simplificar y potenciar la administración de tu clínica dental. Esta guía ha sido creada a partir del análisis de toda la estructura del sistema para proporcionarte un recorrido detallado por cada una de sus funcionalidades, asegurando que aproveches al máximo su potencial.</p>
            </section>

            <!-- Dashboard -->
            <section id="dashboard" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
              <h3>
                <i class="bi bi-speedometer2 me-2"></i>Dashboard Principal
              </h3>
              <p>El <strong>Dashboard</strong> es tu centro de operaciones. Al iniciar sesión, tendrás una vista panorámica y en tiempo real del estado de tu clínica. Los componentes clave incluyen:</p>
              <ul>
                <li><strong>Resumen de Citas del Día:</strong> Un vistazo rápido a las citas programadas para la jornada actual.</li>
                <li><strong>Últimos Pacientes Registrados:</strong> Lista de los nuevos pacientes añadidos al sistema.</li>
                <li><strong>Notificaciones Importantes:</strong> Alertas sobre inventario bajo, mensajes no leídos y otras actualizaciones relevantes.</li>
                <li><strong>Accesos Directos:</strong> Botones para realizar las acciones más comunes, como "Crear Cita" o "Añadir Paciente".</li>
              </ul>
            </section>

            <!-- Global Features -->
            <section id="global-features" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
              <h3>
                <i class="bi bi-globe me-2"></i>Funciones Globales
              </h3>
              <p>Estas herramientas están disponibles en toda la plataforma para agilizar tu trabajo.</p>
              
              <h4>
                <i class="bi bi-search me-2"></i>Búsqueda Global
              </h4>
              <p>Ubicada en la barra de navegación superior, la <strong>Búsqueda Global</strong> te permite encontrar rápidamente pacientes, citas o personal tecleando su nombre o DNI. Los resultados aparecen de forma instantánea, ahorrándote tiempo de navegación.</p>
              
              <h4>
                <i class="bi bi-person-gear me-2"></i>Gestión de Perfil
              </h4>
              <p>Accede a tu perfil de usuario para actualizar tu información personal, cambiar tu contraseña y modificar tu foto de perfil. Mantener tus datos actualizados es fundamental para la seguridad y la correcta identificación dentro del sistema.</p>
              
              <h4>
                <i class="bi bi-bell me-2"></i>Notificaciones
              </h4>
              <p>El icono de la campana te mantendrá al día de todas las novedades. Recibirás notificaciones sobre nuevas citas, cancelaciones, mensajes recibidos y alertas de inventario, asegurando que no te pierdas ninguna información crucial.</p>
            </section>

            <!-- Appointments -->
            <section id="appointments" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="500">
              <h3>
                <i class="bi bi-calendar-event me-2"></i>Gestión de Citas
              </h3>
              <p>El corazón de la organización de tu clínica. Este módulo te permite controlar la agenda de todos los profesionales de manera eficiente.</p>
              
              <h4>
                <i class="bi bi-plus-circle me-2"></i>Agendar, Editar y Eliminar Citas
              </h4>
              <ol>
                <li>Para <strong>crear una cita</strong>, ve a la sección <code>Citas</code> y haz clic en "Crear Cita". Rellena el formulario seleccionando paciente, profesional, fecha, hora y motivo.</li>
                <li>Para <strong>editar una cita</strong>, haz clic sobre ella en el calendario o listado. Podrás modificar cualquier dato, incluyendo su estado (Confirmada, Cancelada, Completada).</li>
                <li>Para <strong>eliminar una cita</strong>, selecciónala y utiliza la opción de borrado. El sistema te pedirá confirmación para evitar borrados accidentales.</li>
              </ol>
              
              <h4>
                <i class="bi bi-calendar3 me-2"></i>Vistas del Calendario
              </h4>
              <p>Visualiza la agenda de la forma que más te convenga:</p>
              <ul>
                <li><strong>Vista de Calendario (Mensual/Semanal):</strong> Ideal para una planificación a medio y largo plazo.</li>
                <li><strong>Vista de Día:</strong> Muestra en detalle la agenda de la jornada actual, organizada por horas y profesionales.</li>
                <li><strong>Vista de Lista:</strong> Un listado cronológico de todas las citas, con opciones de filtrado y búsqueda.</li>
              </ul>
            </section>

            <!-- Patients -->
            <section id="patients" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="600">
              <h3>
                <i class="bi bi-people me-2"></i>Gestión de Pacientes
              </h3>
              <p>Un repositorio centralizado y seguro con toda la información clínica y administrativa de tus pacientes.</p>
              
              <h4>
                <i class="bi bi-person-plus me-2"></i>Administración de Fichas de Pacientes
              </h4>
              <p>Desde el listado principal de pacientes, puedes <strong>crear, editar o eliminar</strong> perfiles. Al crear un nuevo paciente, asegúrate de completar todos los campos de información personal y de contacto para una gestión completa.</p>
              
              <h4>
                <i class="bi bi-journal-medical me-2"></i>Historia Clínica Digital
              </h4>
              <p>Dentro del perfil de cada paciente, la pestaña de <strong>Historia Clínica</strong> es fundamental. Aquí puedes:</p>
              <ul>
                <li><strong>Añadir nuevas entradas:</strong> Registra cada consulta, diagnóstico o tratamiento realizado.</li>
                <li><strong>Editar y eliminar registros:</strong> Corrige o elimina entradas si es necesario.</li>
                <li><strong>Adjuntar archivos:</strong> Sube documentos importantes como radiografías, consentimientos informados o resultados de estudios en formatos como PDF, JPG, PNG, etc.</li>
                <li><strong>Exportar la historia:</strong> Genera un documento PDF con el historial completo del paciente para compartirlo de forma segura.</li>
              </ul>
              
              <h4>
                <i class="bi bi-teeth me-2"></i>Odontograma Interactivo
              </h4>
              <p>El odontograma gráfico es una herramienta visual clave para el seguimiento dental.</p>
              <ul>
                <li>Selecciona una pieza dental para registrar su estado o los tratamientos aplicados.</li>
                <li>El sistema guarda un historial de cambios en el odontograma, permitiendo ver la evolución del paciente a lo largo del tiempo.</li>
                <li>Guarda el estado del odontograma para futuras referencias.</li>
              </ul>
              
              <h4>
                <i class="bi bi-images me-2"></i>Galería de Imágenes del Paciente
              </h4>
              <p>Un espacio dedicado para organizar visualmente el progreso de los tratamientos. Sube y gestiona todas las imágenes relacionadas con un paciente, como fotos intraorales o del antes y después de un procedimiento.</p>
            </section>

            <!-- Estimates -->
            <section id="estimates" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="700">
              <h3>
                <i class="bi bi-calculator me-2"></i>Presupuestos
              </h3>
              <p>Crea y gestiona presupuestos detallados para tus pacientes de manera profesional.</p>
              
              <h4>
                <i class="bi bi-file-earmark-plus me-2"></i>Creación y Gestión de Presupuestos
              </h4>
              <ol>
                <li>Accede a <code>Presupuestos</code> y selecciona "Crear Nuevo".</li>
                <li>Busca y asocia un paciente.</li>
                <li>Añade los tratamientos o servicios detallando el coste de cada uno. El sistema calculará el total automáticamente.</li>
                <li>Puedes guardar el presupuesto como borrador o finalizarlo.</li>
              </ol>
              
              <h4>
                <i class="bi bi-gear me-2"></i>Acciones sobre Presupuestos
              </h4>
              <ul>
                <li><strong>Editar:</strong> Modifica cualquier presupuesto que no haya sido aceptado.</li>
                <li><strong>Generar PDF:</strong> Crea un documento PDF con un diseño profesional, listo para imprimir o enviar.</li>
                <li><strong>Enviar por Email:</strong> Reenvía el presupuesto directamente al correo electrónico del paciente desde el sistema.</li>
                <li><strong>Actualizar Estado:</strong> Cambia el estado del presupuesto a "Aceptado" o "Rechazado" para un mejor seguimiento.</li>
                <li><strong>Eliminar:</strong> Borra presupuestos que ya no sean necesarios.</li>
              </ul>
            </section>

            <!-- Billing -->
            <section id="billing" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="800">
              <h3>
                <i class="bi bi-receipt me-2"></i>Facturación
              </h3>
              <p>Gestiona la facturación de tu clínica y personaliza la apariencia de tus documentos.</p>
              
              <h4>
                <i class="bi bi-file-pdf me-2"></i>Personalización de PDFs
              </h4>
              <p>En la sección de <code>Facturación</code>, encontrarás una opción para <strong>personalizar el PDF</strong>. Desde aquí puedes:</p>
              <ul>
                <li>Subir el logo de tu clínica.</li>
                <li>Añadir la información fiscal y de contacto que aparecerá en el encabezado y pie de página de facturas y presupuestos.</li>
              </ul>
            </section>

            <!-- Inventory -->
            <section id="inventory" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="900">
              <h3>
                <i class="bi bi-box-seam me-2"></i>Control de Inventario
              </h3>
              <p>Mantén un control preciso del stock de tus materiales y productos.</p>
              
              <h4>
                <i class="bi bi-boxes me-2"></i>Gestión de Productos
              </h4>
              <p>En el módulo de <code>Inventario</code> puedes:</p>
              <ul>
                <li><strong>Crear nuevos productos:</strong> Especifica el nombre, proveedor, cantidad inicial y un nivel mínimo de stock.</li>
                <li><strong>Editar productos:</strong> Actualiza la información de cualquier ítem.</li>
                <li><strong>Ajustar Stock:</strong> Registra entradas (compras) y salidas (uso en tratamientos) de material para mantener el recuento actualizado.</li>
                <li><strong>Eliminar productos</strong> que ya no gestiones.</li>
              </ul>
              
              <h4>
                <i class="bi bi-clipboard-data me-2"></i>Registro de Movimientos (Stock Log)
              </h4>
              <p>Consulta el <strong>historial de movimientos de stock</strong> para cualquier producto. Esto te permite tener una trazabilidad completa de cómo y cuándo se ha usado el material, ideal para auditorías y control de costes.</p>
            </section>

            <!-- Employees -->
            <section id="employees" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1000">
              <h3>
                <i class="bi bi-person-badge me-2"></i>Gestión de Personal
              </h3>
              <p>Administra los perfiles de tu equipo y sus permisos de acceso al sistema.</p>
              
              <h4>
                <i class="bi bi-people-fill me-2"></i>Administrar Perfiles de Empleados
              </h4>
              <p>En la sección de <code>Personal</code> puedes:</p>
              <ul>
                <li><strong>Crear perfiles</strong> para cada miembro del equipo.</li>
                <li><strong>Editar su información</strong> de contacto y profesional.</li>
                <li><strong>Asignar roles y permisos:</strong> Controla qué secciones del CMS puede ver y modificar cada usuario (ej. Administrador, Doctor, Recepcionista).</li>
              </ul>
            </section>

            <!-- Messages -->
            <section id="messages" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1100">
              <h3>
                <i class="bi bi-chat-dots me-2"></i>Mensajería Interna
              </h3>
              <p>Una herramienta de comunicación segura y directa entre los miembros de la clínica.</p>
              
              <h4>
                <i class="bi bi-chat-square-text me-2"></i>Chat Interno
              </h4>
              <p>Desde <code>Mensajes</code>, selecciona un miembro del personal para iniciar una conversación. El sistema de chat en tiempo real facilita la coordinación de tareas, la consulta de dudas y la comunicación general, manteniendo las conversaciones dentro de un entorno profesional y seguro.</p>
            </section>

            <!-- Reports -->
            <section id="reports" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1200">
              <h3>
                <i class="bi bi-graph-up me-2"></i>Reportes
              </h3>
              <p>Toma decisiones informadas basadas en datos precisos sobre el rendimiento de tu clínica.</p>
              
              <h4>
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Generación de Informes
              </h4>
              <p>El módulo de <code>Reportes</code> te permite generar análisis clave:</p>
              <ul>
                <li><strong>Reporte de Citas por Periodo:</strong> Analiza el volumen de citas en un rango de fechas específico.</li>
                <li><strong>Reporte de Estado de Citas:</strong> Filtra las citas por su estado (ej. cuántas fueron completadas vs. canceladas en un mes).</li>
                <li><strong>Reporte de Nuevos Pacientes:</strong> Mide el crecimiento de tu clínica visualizando cuántos pacientes nuevos se han registrado en un período.</li>
              </ul>
            </section>

            <!-- Portal -->
            <section id="portal" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1300">
              <h3>
                <i class="bi bi-person-circle me-2"></i>Portal del Paciente
              </h3>
              <p>Ofrece a tus pacientes un canal digital para gestionar su relación con la clínica.</p>
              
              <h4>
                <i class="bi bi-laptop me-2"></i>Funcionalidades del Portal
              </h4>
              <p>Los pacientes con acceso al portal pueden:</p>
              <ul>
                <li><strong>Iniciar sesión</strong> de forma segura.</li>
                <li><strong>Consultar sus Citas:</strong> Ver fechas y horas de sus próximas citas.</li>
                <li><strong>Ver y Editar su Perfil:</strong> Mantener actualizada su información de contacto.</li>
                <li><strong>Comunicarse con la Clínica:</strong> Enviar y recibir mensajes directos con el personal administrativo.</li>
              </ul>
            </section>

            <!-- System -->
            <section id="system" class="glass-card p-4 p-sm-5 mb-4 guide-content" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1400">
              <h3>
                <i class="bi bi-shield-check me-2"></i>Sistema y Seguridad
              </h3>
              <p>Herramientas para el mantenimiento y la protección de los datos de tu CMS.</p>
              
              <h4>
                <i class="bi bi-cloud-arrow-down me-2"></i>Copia de Seguridad de la Base de Datos
              </h4>
              <p>En <code>Sistema > Copia de Seguridad</code>, puedes generar un archivo de respaldo de toda tu base de datos. Es una acción de vital importancia para proteger tu información ante cualquier imprevisto.</p>
            </section>

            <!-- No Results Message -->
            <div id="noResults" class="no-results glass-card" style="display: none;">
              <i class="bi bi-search text-primary" style="font-size: 3rem; margin-bottom: 1rem;"></i>
              <h3 class="text-white mb-2">No se encontraron resultados</h3>
              <p class="text-light opacity-75">Intenta con otros términos de búsqueda o revisa la ortografía.</p>
              <button class="btn btn-primary" onclick="clearSearch()">
                <i class="bi bi-arrow-clockwise me-2"></i>Limpiar búsqueda
              </button>
            </div>
          </div>

          <!-- Help Section -->
          <div class="glass-card p-4 text-center" data-aos="fade-up" data-aos-duration="800" data-aos-delay="1500">
            <h4 class="text-white mb-3">
              <i class="bi bi-question-circle me-2"></i>¿Necesitas más ayuda?
            </h4>
            <p class="text-light opacity-85 mb-4">
              Si no encontraste lo que buscabas en esta guía, nuestro equipo de soporte está aquí para ayudarte.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="dashboard.php#support" class="btn btn-primary">
                <i class="bi bi-headset me-2"></i>Crear ticket de soporte
              </a>
              <a href="https://wa.me/5491112345678" target="_blank" class="btn btn-success">
                <i class="bi bi-whatsapp me-2"></i>WhatsApp
              </a>
              <a href="mailto:soporte@dentexapro.com" class="btn btn-outline-light">
                <i class="bi bi-envelope me-2"></i>Email
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

    document.addEventListener('DOMContentLoaded', function() {
      // Search functionality
      const searchInput = document.getElementById('searchGuide');
      const sections = document.querySelectorAll('.guide-content');
      const noResultsMessage = document.getElementById('noResults');
      const guideHeader = document.getElementById('guideHeader');
      const navLinks = document.querySelectorAll('.guide-nav .nav-link');

      // Store original content to remove highlights later
      const originalContent = new Map();
      sections.forEach(section => {
        originalContent.set(section, section.innerHTML);
      });

      function performSearch() {
        const query = searchInput.value.trim().toLowerCase();
        let resultsFound = false;

        // Remove previous highlights
        sections.forEach(section => {
          section.innerHTML = originalContent.get(section);
        });

        if (query === '') {
          sections.forEach(section => {
            section.style.display = 'block';
          });
          noResultsMessage.style.display = 'none';
          guideHeader.style.display = 'block';
          return;
        }

        const queryRegex = new RegExp(query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');

        sections.forEach(section => {
          const sectionText = section.innerText.toLowerCase();
          if (sectionText.includes(query)) {
            section.style.display = 'block';
            resultsFound = true;
            
            // Highlight matching text
            const walker = document.createTreeWalker(section, NodeFilter.SHOW_TEXT, null, false);
            let node;
            const nodesToReplace = [];
            while (node = walker.nextNode()) {
              if (node.nodeValue.toLowerCase().includes(query)) {
                const newHtml = node.nodeValue.replace(queryRegex, match => `<span class="search-highlight">${match}</span>`);
                const span = document.createElement('span');
                span.innerHTML = newHtml;
                nodesToReplace.push({oldNode: node, newNode: span});
              }
            }
            nodesToReplace.forEach(item => {
              if (item.oldNode.parentNode) {
                item.oldNode.parentNode.replaceChild(item.newNode, item.oldNode);
              }
            });
          } else {
            section.style.display = 'none';
          }
        });

        noResultsMessage.style.display = resultsFound ? 'none' : 'block';
        guideHeader.style.display = resultsFound ? 'none' : 'block';
      }

      searchInput.addEventListener('input', performSearch);

      // Navigation functionality
      const activateLink = (id) => {
        navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href') === `#${id}`) {
            link.classList.add('active');
          }
        });
      };

      // Intersection Observer for active navigation
      const observer = new IntersectionObserver((entries) => {
        if (searchInput.value.trim() !== '') return; // Don't change active link while searching
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            activateLink(entry.target.id);
          }
        });
      }, { 
        rootMargin: '-20% 0px -80% 0px', 
        threshold: 0.1 
      });

      sections.forEach(section => {
        observer.observe(section);
      });

      // Smooth scroll navigation
      navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          if (searchInput.value.trim() !== '') {
            searchInput.value = '';
            performSearch();
          }
          const targetId = this.getAttribute('href');
          const targetElement = document.querySelector(targetId);
          if (targetElement) {
            targetElement.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
            activateLink(targetId.substring(1));
          }
        });
      });

      // Clear search function
      window.clearSearch = function() {
        searchInput.value = '';
        performSearch();
      }
    });
  </script>
</body>
</html>