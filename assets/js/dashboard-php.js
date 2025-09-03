// Dashboard functionality for PHP version
document.addEventListener('DOMContentLoaded', () => {
  // Init AOS
  if (window.AOS) {
    AOS.init({
      duration: 1000,
      once: true,
      offset: 100,
      easing: 'ease-out-quart'
    });
  }

  // Start trial button functionality
  const startTrialBtn = document.getElementById('startTrialBtn');
  if (startTrialBtn) {
    startTrialBtn.addEventListener('click', async () => {
      // Disable button and show loading
      startTrialBtn.disabled = true;
      startTrialBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando solicitud...';
      
      try {
        const response = await fetch('api/trial-requests.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          }
        });
        
        const data = await response.json();
        
        if (data.error) {
          showAlert('danger', data.error);
        } else {
          showAlert('success', data.message);
          // Hide the button after successful request
          startTrialBtn.style.display = 'none';
        }
        
      } catch (error) {
        console.error('Error sending trial request:', error);
        showAlert('danger', 'Error al enviar solicitud. Por favor, intentá nuevamente.');
      } finally {
        // Re-enable button
        startTrialBtn.disabled = false;
        startTrialBtn.innerHTML = '<i class="bi bi-play-circle me-2"></i>Iniciar prueba gratuita';
      }
    });
  }

  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show glass-card mt-4" role="alert">
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    
    document.querySelector('.container').insertAdjacentHTML('beforeend', alertHtml);
    
    // Scroll to alert
    setTimeout(() => {
      const alert = document.querySelector('.alert:last-of-type');
      if (alert) {
        alert.scrollIntoView({ behavior: 'smooth' });
      }
    }, 100);
  }

  // Plan selection
  window.selectPlan = function(planType) {
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('plansModal'));
    modal.hide();

    // Show confirmation
    const alertHtml = `
      <div class="alert alert-info alert-dismissible fade show glass-card mt-4" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        Has seleccionado el plan <strong>${getPlanName(planType)}</strong>. 
        En una implementación real, aquí se procesaría el pago con Stripe.
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    
    document.querySelector('.container').insertAdjacentHTML('beforeend', alertHtml);

    // Scroll to alert
    setTimeout(() => {
      document.querySelector('.alert').scrollIntoView({ behavior: 'smooth' });
    }, 100);
  }

  function getPlanName(plan) {
    switch(plan) {
      case 'start': return 'Start';
      case 'clinic': return 'Clinic';
      case 'enterprise': return 'Enterprise';
      default: return 'Sin plan';
    }
  }
});