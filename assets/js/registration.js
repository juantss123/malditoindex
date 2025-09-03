// Registration form functionality
import { supabase, supabaseUrl, supabaseKey } from './supabase.js'

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

      // Check if Supabase is properly configured
      if (!supabase || supabaseUrl.includes('placeholder') || supabaseKey.includes('placeholder')) {
        showAlert('warning', 'Para crear cuentas reales, necesitas conectar Supabase usando el botón "Connect to Supabase" en la parte superior derecha. Por ahora, simularemos el registro.');
        
        // Simulate successful registration for demo
        setTimeout(() => {
          showAlert('success', '¡Cuenta creada exitosamente! (Simulación - conecta Supabase para funcionalidad real)');
          form.reset();
        }, 2000);
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-rocket-takeoff me-2"></i>Crear mi cuenta y comenzar prueba';
        return;
      }

      try {
        // Get form data
        const formData = new FormData(form);
        const userData = {
          firstName: formData.get('firstName'),
          lastName: formData.get('lastName'),
          email: formData.get('email'),
          password: formData.get('password'),
          phone: formData.get('phone'),
          clinicName: formData.get('clinicName'),
          licenseNumber: formData.get('licenseNumber'),
          specialty: formData.get('specialty'),
          teamSize: formData.get('teamSize')
        };

        // Create user account
        const { data: authData, error: authError } = await supabase.auth.signUp({
          email: userData.email,
          password: userData.password,
          options: {
            data: {
              first_name: userData.firstName,
              last_name: userData.lastName,
              phone: userData.phone
            }
          }
        });

        if (authError) throw authError;

        // Save additional profile data
        if (authData.user) {
          // Check if this is the admin user
          const isAdmin = userData.email === 'admin@dentexapro.com';
          
          const { error: profileError } = await supabase
            .from('user_profiles')
            .insert({
              user_id: authData.user.id,
              first_name: userData.firstName,
              last_name: userData.lastName,
              email: userData.email,
              phone: userData.phone,
              clinic_name: userData.clinicName,
              license_number: userData.licenseNumber,
              specialty: userData.specialty,
              team_size: userData.teamSize,
              role: isAdmin ? 'admin' : 'user', // Admin role for admin@dentexapro.com
              subscription_status: 'trial',
              subscription_plan: null,
              trial_start_date: new Date().toISOString(),
              trial_end_date: new Date(Date.now() + 15 * 24 * 60 * 60 * 1000).toISOString()
            });

          if (profileError) throw profileError;
        }

        // Show success message
        const successMessage = isAdmin ? 
          '¡Cuenta de administrador creada exitosamente! Serás redirigido al panel de administración.' :
          '¡Cuenta creada exitosamente! Tu prueba gratuita de 15 días ha comenzado. Revisa tu email para confirmar tu cuenta.';
        
        showAlert('success', successMessage);
        
        // Reset form
        form.reset();
        
        // Redirect after delay
        setTimeout(() => {
          window.location.href = isAdmin ? 'admin.html' : 'dashboard.html';
        }, 3000);

      } catch (error) {
        console.error('Error creating account:', error);
        showAlert('danger', error.message || 'Error al crear la cuenta. Por favor, intentá nuevamente.');
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