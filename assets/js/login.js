// Login form functionality
import { supabase, isSupabaseConfigured } from './supabase.js'

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

      // Check if Supabase is properly configured
      if (!isSupabaseConfigured()) {
        showAlert('warning', 'Para iniciar sesión con cuentas reales, necesitas conectar Supabase usando el botón "Connect to Supabase" en la parte superior derecha. Por ahora, simularemos el login.');
        
        // Simulate successful login for demo
        setTimeout(() => {
          showAlert('success', '¡Login exitoso! (Simulación - conecta Supabase para funcionalidad real)');
          // Simulate redirect to dashboard
          setTimeout(() => {
            window.location.href = 'index.html#dashboard-simulation';
          }, 2000);
        }, 1500);
        
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión';
        return;
      }

      try {
        // Get form data
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');
        const rememberMe = formData.get('rememberMe');

        // Sign in with Supabase
        const { data, error } = await supabase.auth.signInWithPassword({
          email: email,
          password: password
        });

        if (error) throw error;

        // Handle remember me functionality
        if (rememberMe) {
          localStorage.setItem('dentexapro_remember', 'true');
        } else {
          localStorage.removeItem('dentexapro_remember');
        }

        // Show success message
        showAlert('success', '¡Bienvenido de vuelta! Redirigiendo al panel...');
        
        // Check user role and redirect accordingly
        checkUserRoleAndRedirect(data.user);

      } catch (error) {
        console.error('Error during login:', error);
        
        let errorMessage = 'Error al iniciar sesión. Verifica tus credenciales.';
        
        if (error.message.includes('Invalid login credentials')) {
          errorMessage = 'Email o contraseña incorrectos. Por favor, verifica tus datos.';
        } else if (error.message.includes('Email not confirmed')) {
          errorMessage = 'Debes confirmar tu email antes de iniciar sesión. Revisa tu bandeja de entrada.';
        }
        
        showAlert('danger', errorMessage);
      } finally {
        // Re-enable submit button
        loginBtn.disabled = false;
        loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión';
      }
    });
  }

  async function checkUserRoleAndRedirect(user) {
    if (!isSupabaseConfigured()) {
      // For demo, check if it's the admin email
      const formData = new FormData(form);
      const email = formData.get('email');
      const isAdmin = email === 'admin@dentexapro.com';
      window.location.href = isAdmin ? 'admin.html' : 'dashboard.html';
      return;
    }

    try {
      // Get user profile to check role
      const { data: profile, error } = await supabase
        .from('user_profiles')
        .select('role')
        .eq('user_id', user.id)
        .single();

      if (error) {
        console.error('Error checking user role:', error);
        window.location.href = 'dashboard.html'; // Default to user dashboard
        return;
      }

      // Redirect based on role
      if (profile.role === 'admin') {
        window.location.href = 'admin.html';
      } else {
        window.location.href = 'dashboard.html';
      }

    } catch (error) {
      console.error('Error during role check:', error);
      window.location.href = 'dashboard.html'; // Default to user dashboard
    }
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