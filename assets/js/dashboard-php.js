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