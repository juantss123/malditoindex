// Dashboard functionality
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

  // Check authentication
  checkAuth();

  // Load user data
  loadUserData();

  // Logout functionality
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      if (isSupabaseConfigured()) {
        await supabase.auth.signOut();
      }
      window.location.href = 'index.html';
    });
  }

  async function checkAuth() {
    if (!isSupabaseConfigured()) {
      // Simulate user access for demo
      document.getElementById('userName').textContent = 'Dr. Demo';
      return;
    }

    try {
      const { data: { user }, error } = await supabase.auth.getUser().catch(fetchError => {
        console.error('Supabase connection failed:', fetchError);
        // If connection fails, treat as demo mode
        document.getElementById('userName').textContent = 'Dr. Demo (Sin conexión)';
        return { data: { user: null }, error: null };
      });
      
      if (error || !user) {
        // If no user and we have a connection error, stay in demo mode
        if (error && error.message && error.message.includes('fetch')) {
          document.getElementById('userName').textContent = 'Dr. Demo (Sin conexión)';
          return;
        }
        // Otherwise redirect to login
        window.location.href = 'login.html';
        return;
      }

      // Check if this is the admin user - redirect to admin panel
      if (user.email === 'admin@dentexapro.com') {
        window.location.href = 'admin.html';
        return;
      }

      // Get user profile
      const { data: profile, error: profileError } = await supabase
        .from('user_profiles')
        .select('*')
        .eq('user_id', user.id)
        .single()
        .catch(fetchError => {
          console.error('Profile fetch failed:', fetchError);
          return { data: null, error: fetchError };
        });

      if (profileError) {
        console.error('Error loading profile:', profileError);
        // If it's a connection error, stay in demo mode
        if (profileError.message && profileError.message.includes('fetch')) {
          document.getElementById('userName').textContent = 'Dr. Demo (Sin conexión)';
          return;
        }
        // Otherwise show error and redirect
        alert('No se encontró tu perfil. Por favor, inicia sesión nuevamente.');
        window.location.href = 'login.html';
        return;
      }

      // Update user name
      document.getElementById('userName').textContent = `${profile.first_name} ${profile.last_name}`;

    } catch (error) {
      console.error('Error checking auth:', error);
      // If it's a connection error, stay in demo mode
      if (error.message && error.message.includes('fetch')) {
        document.getElementById('userName').textContent = 'Dr. Demo (Sin conexión)';
        return;
      }
      window.location.href = 'login.html';
    }
  }

  async function loadUserData() {
    if (!isSupabaseConfigured()) {
      // Simulate trial data for demo
      updateTrialStatus(12); // 12 days remaining
      return;
    }

    try {
      const { data: { user }, error } = await supabase.auth.getUser().catch(fetchError => {
        console.error('User fetch failed:', fetchError);
        return { data: { user: null }, error: fetchError };
      });
      
      if (error || !user) return;

      // Get user profile with subscription info
      const { data: profile, error: profileError } = await supabase
        .from('user_profiles')
        .select('*')
        .eq('user_id', user.id)
        .single()
        .catch(fetchError => {
          console.error('Profile fetch failed:', fetchError);
          return { data: null, error: fetchError };
        });

      if (profileError) {
        console.error('Error loading profile:', profileError);
        return;
      }

      // Update UI based on subscription status
      updateSubscriptionUI(profile);

    } catch (error) {
      console.error('Error loading user data:', error);
    }
  }

  function updateSubscriptionUI(profile) {
    const welcomeMessage = document.getElementById('welcomeMessage');
    const trialStatus = document.getElementById('trialStatus');
    const upgradeBtn = document.getElementById('upgradeBtn');

    if (profile.subscription_status === 'trial') {
      // Calculate days remaining
      const trialEnd = new Date(profile.trial_end_date);
      const today = new Date();
      const daysRemaining = Math.ceil((trialEnd - today) / (1000 * 60 * 60 * 24));
      
      updateTrialStatus(daysRemaining);
    } else if (profile.subscription_status === 'active') {
      welcomeMessage.textContent = `Tu plan ${getPlanName(profile.subscription_plan)} está activo y funcionando perfectamente.`;
      trialStatus.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i>Plan activo: ${getPlanName(profile.subscription_plan)}`;
      upgradeBtn.innerHTML = '<i class="bi bi-gear me-2"></i>Gestionar plan';
    }
  }

  function updateTrialStatus(daysRemaining) {
    const welcomeMessage = document.getElementById('welcomeMessage');
    const trialStatus = document.getElementById('trialStatus');

    if (daysRemaining > 0) {
      welcomeMessage.textContent = 'Tu prueba gratuita está activa. Explorá todas las funciones sin límites.';
      trialStatus.innerHTML = `<i class="bi bi-clock me-2"></i>Prueba gratuita: ${daysRemaining} días restantes`;
    } else {
      welcomeMessage.textContent = 'Tu prueba gratuita ha terminado. Elegí un plan para continuar usando DentexaPro.';
      trialStatus.innerHTML = `<i class="bi bi-exclamation-triangle text-warning me-2"></i>Prueba gratuita vencida`;
      
      // Show upgrade modal automatically
      setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('plansModal'));
        modal.show();
      }, 2000);
    }
  }

  function getPlanName(plan) {
    switch(plan) {
      case 'start': return 'Start';
      case 'clinic': return 'Clinic';
      case 'enterprise': return 'Enterprise';
      default: return 'Sin plan';
    }
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
});