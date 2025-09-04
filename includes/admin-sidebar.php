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
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-comprobantes') ? 'active' : ''; ?>" href="admin-comprobantes.php" id="comprobantesLink">
        <span class="d-inline-flex align-items-center">
          <i class="bi bi-receipt me-2"></i>
          Comprobantes
          <span class="badge bg-danger ms-2" id="comprobantesNotification" style="display: none;">0</span>
        </span>
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-tickets') ? 'active' : ''; ?>" href="admin-tickets.php">
        <i class="bi bi-headset me-2"></i>Soporte
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-facturas') ? 'active' : ''; ?>" href="admin-facturas.php">
        <i class="bi bi-file-earmark-text me-2"></i>Facturas
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-suscripciones') ? 'active' : ''; ?>" href="admin-suscripciones.php">
        <i class="bi bi-credit-card me-2"></i>Suscripciones
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-analiticas') ? 'active' : ''; ?>" href="admin-analiticas.php">
        <i class="bi bi-graph-up me-2"></i>Analíticas
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-marketing') ? 'active' : ''; ?>" href="admin-marketing.php">
        <i class="bi bi-megaphone me-2"></i>Marketing
      </a>
      <a class="nav-link text-light <?php echo ($currentPage === 'admin-configuracion') ? 'active' : ''; ?>" href="admin-configuracion.php">
        <i class="bi bi-gear me-2"></i>Configuración
      </a>
    </nav>
  </div>
  
  <!-- Notification Script -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Load notifications when sidebar loads
      loadNotifications();
      
      // Refresh notifications every 30 seconds
      setInterval(loadNotifications, 30000);
      
      // Mark as viewed when clicking on comprobantes
      const comprobantesLink = document.getElementById('comprobantesLink');
      if (comprobantesLink) {
        comprobantesLink.addEventListener('click', () => {
          markNotificationsAsViewed('transfer_proofs');
        });
      }
    });
    
    async function loadNotifications() {
      try {
        const response = await fetch('api/notifications.php?action=get-counts');
        const data = await response.json();
        
        if (data.success && data.notifications) {
          const transferProofsCount = data.notifications.transfer_proofs || 0;
          const notification = document.getElementById('comprobantesNotification');
          
          if (notification) {
            if (transferProofsCount > 0) {
              notification.textContent = transferProofsCount;
              notification.style.display = 'inline-block';
              
              // Add pulsing animation for new notifications
              notification.style.animation = 'pulse 2s infinite';
            } else {
              notification.style.display = 'none';
            }
          }
        }
      } catch (error) {
        console.error('Error loading notifications:', error);
      }
    }
    
    async function markNotificationsAsViewed(type) {
      try {
        await fetch(`api/notifications.php?action=mark-viewed&type=${type}`, {
          method: 'POST'
        });
        
        // Hide notification immediately
        const notification = document.getElementById('comprobantesNotification');
        if (notification) {
          notification.style.display = 'none';
        }
      } catch (error) {
        console.error('Error marking notifications as viewed:', error);
      }
    }
    
    window.markNotificationsAsViewed = markNotificationsAsViewed;
  </script>
  
  <style>
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    
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
    
    .nav-link {
      position: relative;
      transition: all 0.3s ease;
    }

    /* Fix badge alignment */
    #comprobantesNotification {
      vertical-align: middle;
    }
  </style>
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
