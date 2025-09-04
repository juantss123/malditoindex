<?php
// Admin sidebar component
// Usage: include 'includes/admin-sidebar.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar -->
<div class="col-lg-3 col-xl-2 mb-4">
  <div class="glass-card p-3" data-aos="slide-right" data-aos-duration="800">
    <h5 class="text-white mb-3">
      <i class="bi bi-speedometer2 me-2"></i>Panel Admin
    </h5>
    <nav class="nav flex-column">
      <a class="nav-link text-light <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" href="admin.php">
        <i class="bi bi-house me-2"></i>Dashboard
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-usuarios') ? 'active' : ''; ?>" href="admin-usuarios.php">
        <i class="bi bi-people me-2"></i>Usuarios
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-comprobantes') ? 'active' : ''; ?>" href="admin-comprobantes.php">
        <i class="bi bi-receipt me-2"></i>Comprobantes
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-suscripciones') ? 'active' : ''; ?>" href="admin-suscripciones.php">
        <i class="bi bi-credit-card me-2"></i>Suscripciones
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-analiticas') ? 'active' : ''; ?>" href="admin-analiticas.php">
        <i class="bi bi-graph-up me-2"></i>Analíticas
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-configuracion') ? 'active' : ''; ?>" href="admin-configuracion.php">
        <i class="bi bi-gear me-2"></i>Configuración
      </a>
    </nav>
  </div>
</div>

<style>
.nav-link.active {
  background: rgba(47, 150, 238, 0.2) !important;
  border-radius: 8px;
  color: #68c4ff !important;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff !important;
}
</style>