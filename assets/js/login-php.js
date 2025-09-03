// Login form functionality for PHP version
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

  // Password toggle functionality
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const toggleIcon = document.getElementById('toggleIcon');

  if (togglePassword && passwordInput && toggleIcon) {
    togglePassword.addEventListener('click', () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Toggle icon
      if (type === 'text') {
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
      } else {
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
      }
    });
  }

  // Login form handler
  const form = document.getElementById('loginForm');
  const loginBtn = document.getElementById('loginBtn');
  const alertContainer = document.getElementById('alertContainer');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Disable submit button
      loginBtn.disabled = true;
      loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando sesión...';
      
      // Clear previous alerts
      alertContainer.innerHTML = '';

      try {
        // Get form data
        const formData = new FormData(form);
        const loginData = {
          email: formData.get('email'),
          password: formData.get('password'),
          rememberMe: formData.get('rememberMe')
        };

        // Send login request
        const response = await fetch('api/auth.php?action=login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(loginData)
        });

        const data = await response.json();

        if (data.error) {
          showAlert('danger', data.error);
          return;
        }

        // Show success message
        showAlert('success', data.message);
        
        // Redirect after delay
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1500);

      } catch (error) {
        console.error('Error during login:', error);
        showAlert('danger', 'Error de conexión. Por favor, intentá nuevamente.');
      } finally {
        // Re-enable submit button
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión';
      }
    });
  }

  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show glass-card" role="alert">
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    alertContainer.innerHTML = alertHtml;
  }
});