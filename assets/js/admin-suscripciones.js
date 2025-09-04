// Admin subscriptions management functionality

let allPlans = [];
let allUsers = [];

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

  // Load data
  loadPlans();
  loadUsers();

  // Form handlers
  const editPlanForm = document.getElementById('editPlanForm');
  if (editPlanForm) {
    editPlanForm.addEventListener('submit', handleEditPlan);
  }
});

async function loadPlans() {
  try {
    const response = await fetch('api/plans.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allPlans = data.plans || [];
    renderPlansTable();
    
  } catch (error) {
    console.error('Error loading plans:', error);
    showAlert('danger', 'Error al cargar planes');
  }
}

async function loadUsers() {
  try {
    const response = await fetch('api/users.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allUsers = data.users || [];
    renderSubscriptionStatus();
    
  } catch (error) {
    console.error('Error loading users:', error);
    showAlert('danger', 'Error al cargar usuarios');
  }
}

function renderPlansTable() {
  const tbody = document.getElementById('plansTable');
  
  if (allPlans.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No hay planes configurados
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = allPlans.map(plan => {
    const usersInPlan = allUsers.filter(u => u.subscription_plan === plan.plan_type).length;
    const monthlyRevenue = usersInPlan * (plan.price_monthly / 100); // Convert from cents
    
    return `
      <tr>
        <td>
          <div class="d-flex align-items-center">
            <div class="bg-${getPlanColor(plan.plan_type)} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
              <i class="bi bi-star text-white"></i>
            </div>
            <div>
              <div class="text-white fw-bold">${plan.name}</div>
              <small class="text-light opacity-75">${plan.plan_type}</small>
            </div>
          </div>
        </td>
        <td>
          <div class="text-white fw-bold">$${formatPrice(plan.price_monthly)}</div>
          <small class="text-light opacity-75">por mes</small>
        </td>
        <td>
          <div class="text-white fw-bold">$${formatPrice(plan.price_yearly)}</div>
          <small class="text-light opacity-75">por año</small>
        </td>
        <td>
          <span class="badge bg-info">${usersInPlan} usuarios</span>
        </td>
        <td>
          <div class="text-success fw-bold">$${monthlyRevenue.toFixed(0)}</div>
          <small class="text-light opacity-75">ARS/mes</small>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="editPlan('${plan.plan_type}')">
            <i class="bi bi-pencil"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderSubscriptionStatus() {
  // Update stats
  const totalActive = allUsers.filter(u => u.subscription_status === 'active').length;
  const totalTrial = allUsers.filter(u => u.subscription_status === 'trial').length;
  const totalExpired = allUsers.filter(u => u.subscription_status === 'expired').length;
  
  document.getElementById('activeCount').textContent = totalActive;
  document.getElementById('trialCount').textContent = totalTrial;
  document.getElementById('expiredCount').textContent = totalExpired;
  
  // Calculate conversion rate
  const totalTrialAndActive = totalActive + totalTrial;
  const conversionRate = totalTrialAndActive > 0 ? (totalActive / totalTrialAndActive * 100).toFixed(1) : 0;
  document.getElementById('conversionRate').textContent = conversionRate + '%';
  
  // Render expiring trials
  renderExpiringTrials();
  
  // Render expired subscriptions
  renderExpiredSubscriptions();
}

function renderExpiringTrials() {
  const tbody = document.getElementById('expiringTrialsTable');
  
  // Filter users with trials expiring in next 7 days
  const expiringUsers = allUsers.filter(user => {
    if (user.subscription_status !== 'trial' || !user.trial_end_date) return false;
    
    const trialEnd = new Date(user.trial_end_date);
    const today = new Date();
    const daysUntilExpiry = Math.ceil((trialEnd - today) / (1000 * 60 * 60 * 24));
    
    return daysUntilExpiry >= 0 && daysUntilExpiry <= 7;
  });
  
  if (expiringUsers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-check-circle text-success me-2"></i>No hay pruebas venciendo pronto
        </td>
      </tr>
    `;
    return;
  }
  
  tbody.innerHTML = expiringUsers.map(user => {
    const trialEnd = new Date(user.trial_end_date);
    const today = new Date();
    const daysRemaining = Math.ceil((trialEnd - today) / (1000 * 60 * 60 * 24));
    
    return `
      <tr>
        <td>
          <div class="d-flex align-items-center">
            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
              <i class="bi bi-person text-dark"></i>
            </div>
            <div>
              <div class="text-white">${user.first_name} ${user.last_name}</div>
              <small class="text-light opacity-75">${user.email}</small>
            </div>
          </div>
        </td>
        <td class="text-light">${user.clinic_name || 'Sin consultorio'}</td>
        <td>
          <span class="badge ${daysRemaining <= 1 ? 'bg-danger' : daysRemaining <= 3 ? 'bg-warning text-dark' : 'bg-info'}">
            ${daysRemaining} día${daysRemaining !== 1 ? 's' : ''}
          </span>
        </td>
        <td>
          <button class="btn btn-sm btn-success me-2" onclick="sendReminderEmail('${user.user_id}')">
            <i class="bi bi-envelope"></i>
          </button>
          <button class="btn btn-sm btn-primary" onclick="extendTrial('${user.user_id}')">
            <i class="bi bi-clock-history"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderExpiredSubscriptions() {
  const tbody = document.getElementById('expiredSubscriptionsTable');
  
  const expiredUsers = allUsers.filter(user => user.subscription_status === 'expired');
  
  if (expiredUsers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-check-circle text-success me-2"></i>No hay suscripciones vencidas
        </td>
      </tr>
    `;
    return;
  }
  
  tbody.innerHTML = expiredUsers.map(user => `
    <tr>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
            <i class="bi bi-person text-white"></i>
          </div>
          <div>
            <div class="text-white">${user.first_name} ${user.last_name}</div>
            <small class="text-light opacity-75">${user.email}</small>
          </div>
        </div>
      </td>
      <td class="text-light">${user.clinic_name || 'Sin consultorio'}</td>
      <td>
        <span class="badge bg-${getPlanColor(user.subscription_plan)}">
          ${getPlanName(user.subscription_plan)}
        </span>
      </td>
      <td class="text-light opacity-75">${formatDate(user.updated_at)}</td>
      <td>
        <button class="btn btn-sm btn-warning me-2" onclick="sendRenewalEmail('${user.user_id}')">
          <i class="bi bi-envelope"></i>
        </button>
        <button class="btn btn-sm btn-success" onclick="reactivateSubscription('${user.user_id}')">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
      </td>
    </tr>
  `).join('');
}

async function handleEditPlan(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
  
  try {
    const formData = new FormData(e.target);
    const planData = {
      name: formData.get('name'),
      price_monthly: parseFloat(formData.get('price_monthly')) || 0,
      price_yearly: parseFloat(formData.get('price_yearly')) || 0,
      features: formData.getAll('features').filter(f => f.trim() !== ''),
      change_reason: formData.get('change_reason')
    };
    
    const planType = formData.get('plan_type');
    
    const response = await fetch(`api/plans.php?plan=${planType}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(planData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload data
    const modal = bootstrap.Modal.getInstance(document.getElementById('editPlanModal'));
    modal.hide();
    
    // Reload plans and users to update calculations
    await loadPlans();
    await loadUsers();
    
  } catch (error) {
    console.error('Error updating plan:', error);
    showAlert('danger', 'Error al actualizar plan');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions
window.editPlan = function(planType) {
  const plan = allPlans.find(p => p.plan_type === planType);
  if (!plan) return;
  
  // Fill form with current plan data
  document.getElementById('editPlanType').value = plan.plan_type;
  document.getElementById('editPlanName').value = plan.name;
  document.getElementById('editPriceMonthly').value = plan.price_monthly / 100; // Convert from cents
  document.getElementById('editPriceYearly').value = plan.price_yearly / 100; // Convert from cents
  
  // Fill features
  const featuresContainer = document.getElementById('editFeaturesContainer');
  featuresContainer.innerHTML = '';
  
  plan.features.forEach((feature, index) => {
    addFeatureInput(feature);
  });
  
  // Add empty input for new feature
  addFeatureInput('');
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
  modal.show();
}

window.addFeatureInput = function(value = '') {
  const container = document.getElementById('editFeaturesContainer');
  const featureDiv = document.createElement('div');
  featureDiv.className = 'input-group mb-2';
  featureDiv.innerHTML = `
    <input type="text" name="features" class="form-control glass-input" placeholder="Característica del plan" value="${value}">
    <button type="button" class="btn btn-outline-danger" onclick="removeFeatureInput(this)">
      <i class="bi bi-trash"></i>
    </button>
  `;
  container.appendChild(featureDiv);
}

window.removeFeatureInput = function(button) {
  button.closest('.input-group').remove();
}

window.sendReminderEmail = function(userId) {
  showAlert('info', 'Recordatorio enviado (funcionalidad simulada)');
}

window.extendTrial = function(userId) {
  if (confirm('¿Extender la prueba gratuita por 7 días más?')) {
    showAlert('success', 'Prueba extendida exitosamente (funcionalidad simulada)');
  }
}

window.sendRenewalEmail = function(userId) {
  showAlert('info', 'Email de renovación enviado (funcionalidad simulada)');
}

window.reactivateSubscription = function(userId) {
  if (confirm('¿Reactivar la suscripción de este usuario?')) {
    showAlert('success', 'Suscripción reactivada exitosamente (funcionalidad simulada)');
  }
}

// Helper functions
function getPlanColor(plan) {
  switch(plan) {
    case 'start': return 'info';
    case 'clinic': return 'primary';
    case 'enterprise': return 'warning';
    default: return 'secondary';
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

function formatPrice(priceInCents) {
  return (priceInCents / 100).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
}

function showAlert(type, message) {
  const alertHtml = `
    <div class="alert alert-${type} alert-dismissible fade show glass-card mt-4" role="alert">
      <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'} me-2"></i>
      ${message}
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  `;
  
  // Insert alert at the top of the main content
  const mainContent = document.querySelector('.col-lg-9.col-xl-10');
  if (mainContent) {
    mainContent.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      const alert = mainContent.querySelector('.alert');
      if (alert) {
        alert.remove();
      }
    }, 5000);
  }
}