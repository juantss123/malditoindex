// Registration form functionality for PHP version
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

  // Year in footer
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Registration form handler
  const form = document.getElementById('registrationForm');
  const submitBtn = document.getElementById('submitBtn');
  const alertContainer = document.getElementById('alertContainer');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando cuenta...';
      
      // Clear previous alerts
      alertContainer.innerHTML = '';

      try {
        // Get form data
        const formData = new FormData(form);
        const userData = Object.fromEntries(formData.entries());

        // Send registration request
        const response = await fetch('api/auth.php?action=register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(userData)
        });

        const data = await response.json();

        if (data.error) {
          showAlert('danger', data.error);
          return;
        }

        // Show success message
        const isAdmin = userData.email === 'admin@dentexapro.com';
        const successMessage = isAdmin ? 
          '¡Cuenta de administrador creada exitosamente! Serás redirigido al panel de administración.' :
          '¡Cuenta creada exitosamente! Tu prueba gratuita de 15 días ha comenzado.';
        
        showAlert('success', successMessage);
        
        // Reset form
        form.reset();
        
        // Redirect after delay
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 2000);

      } catch (error) {
        console.error('Error creating account:', error);
        showAlert('danger', 'Error de conexión. Por favor, intentá nuevamente.');
      } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-rocket-takeoff me-2"></i>Crear mi cuenta y comenzar prueba';
      }
    });
  }

  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show glass-card" role="alert">
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    alertContainer.innerHTML = alertHtml;
  }
});