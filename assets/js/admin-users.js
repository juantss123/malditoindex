// Admin users management functionality

let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
const usersPerPage = 10;

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

  // Load users on page load
  loadUsers();

  // Search and filter functionality
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const planFilter = document.getElementById('planFilter');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterUsers, 300));
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterUsers);
  }
  if (planFilter) {
    planFilter.addEventListener('change', filterUsers);
  }

  // Form handlers
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.addEventListener('submit', handleAddUser);
  }

  const editUserForm = document.getElementById('editUserForm');
  if (editUserForm) {
    editUserForm.addEventListener('submit', handleEditUser);
  }

  // Plan access form handler
  const planAccessForm = document.getElementById('planAccessForm');
  if (planAccessForm) {
    planAccessForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const submitBtn = e.target.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
      
      try {
        const formData = new FormData(e.target);
        const accessData = Object.fromEntries(formData.entries());
        
        const response = await fetch('api/plan-access.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(accessData)
        });
        
        const data = await response.json();
        
        if (data.error) {
          showAlert('danger', data.error);
          return;
        }
        
        showAlert('success', data.message);
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('planAccessModal'));
        modal.hide();
        
      } catch (error) {
        console.error('Error saving plan access:', error);
        showAlert('danger', 'Error al guardar datos de acceso');
      } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }
});

async function loadUsers() {
  const tbody = document.getElementById('usersTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando usuarios...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/users.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allUsers = data.users || [];
    filteredUsers = [...allUsers];
    currentPage = 1;
    
    renderUsers();
    updateStats();

  } catch (error) {
    console.error('Error loading users:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar usuarios
        </td>
      </tr>
    `;
  }
}

function filterUsers() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const planFilter = document.getElementById('planFilter').value;

  filteredUsers = allUsers.filter(user => {
    const matchesSearch = !searchTerm || 
      user.first_name.toLowerCase().includes(searchTerm) ||
      user.last_name.toLowerCase().includes(searchTerm) ||
      user.email.toLowerCase().includes(searchTerm) ||
      (user.clinic_name && user.clinic_name.toLowerCase().includes(searchTerm));

    const matchesStatus = !statusFilter || user.subscription_status === statusFilter;
    const matchesPlan = !planFilter || user.subscription_plan === planFilter;

    return matchesSearch && matchesStatus && matchesPlan;
  });

  currentPage = 1;
  renderUsers();
}

function renderUsers() {
  const tbody = document.getElementById('usersTable');
  const startIndex = (currentPage - 1) * usersPerPage;
  const endIndex = startIndex + usersPerPage;
  const pageUsers = filteredUsers.slice(startIndex, endIndex);

  if (pageUsers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No se encontraron usuarios
        </td>
      </tr>
    `;
    updatePagination();
    updateCounts();
    return;
  }

  tbody.innerHTML = pageUsers.map(user => `
    <tr>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
            <i class="bi bi-person text-white"></i>
          </div>
          <div>
            <div class="text-white fw-medium">${user.first_name} ${user.last_name}</div>
            <small class="text-light opacity-75">
              <i class="bi bi-shield${user.role === 'admin' ? '-check' : ''} me-1"></i>
              ${user.role === 'admin' ? 'Administrador' : 'Usuario'}
            </small>
          </div>
        </div>
      </td>
      <td>
        <div class="text-light">${user.email}</div>
        <small class="text-light opacity-75">
          <i class="bi bi-telephone me-1"></i>${user.phone || 'Sin teléfono'}
        </small>
      </td>
      <td>
        <div class="text-light">${user.clinic_name || 'Sin consultorio'}</div>
        <small class="text-light opacity-75">
          <i class="bi bi-award me-1"></i>${getSpecialtyName(user.specialty)}
        </small>
      </td>
      <td>
        <span class="badge ${getPlanBadgeClass(user.subscription_plan)}">
          <i class="bi bi-star me-1"></i>${getPlanName(user.subscription_plan)}
        </span>
      </td>
      <td>
        <span class="badge ${getStatusBadgeClass(user.subscription_status)}">
          <i class="bi bi-${getStatusIcon(user.subscription_status)} me-1"></i>
          ${getStatusName(user.subscription_status)}
        </span>
        ${user.subscription_status === 'trial' && user.trial_days_remaining !== null ? 
          `<br><small class="text-warning">${user.trial_days_remaining} días restantes</small>` : ''}
      </td>
      <td class="text-light opacity-75">
        <div>${formatDate(user.created_at)}</div>
        <small>${formatTime(user.created_at)}</small>
      </td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li>
              <a class="dropdown-item" href="#" onclick="viewUser('${user.user_id}')">
                <i class="bi bi-eye me-2"></i>Ver detalles
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="editUser('${user.user_id}')">
                <i class="bi bi-pencil me-2"></i>Editar
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="managePlanAccess('${user.user_id}')">
                <i class="bi bi-globe me-2"></i>Datos del plan
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item ${user.subscription_status === 'active' ? 'text-warning' : 'text-success'}" href="#" onclick="toggleUserStatus('${user.user_id}', '${user.subscription_status}')">
                <i class="bi bi-${user.subscription_status === 'active' ? 'pause' : 'play'} me-2"></i>
                ${user.subscription_status === 'active' ? 'Suspender' : 'Activar'}
              </a>
            </li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deleteUser('${user.user_id}', '${user.first_name} ${user.last_name}')">
                <i class="bi bi-trash me-2"></i>Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  `).join('');

  updatePagination();
  updateCounts();
}

function updatePagination() {
  const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
  const pagination = document.getElementById('pagination');
  
  if (totalPages <= 1) {
    pagination.innerHTML = '';
    return;
  }

  let paginationHTML = '';
  
  // Previous button
  paginationHTML += `
    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
      <a class="page-link bg-dark border-secondary text-light" href="#" onclick="changePage(${currentPage - 1})">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>
  `;

  // Page numbers
  for (let i = 1; i <= totalPages; i++) {
    if (i === currentPage || i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
      paginationHTML += `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
          <a class="page-link bg-dark border-secondary text-light" href="#" onclick="changePage(${i})">${i}</a>
        </li>
      `;
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      paginationHTML += `<li class="page-item disabled"><span class="page-link bg-dark border-secondary text-light">...</span></li>`;
    }
  }

  // Next button
  paginationHTML += `
    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
      <a class="page-link bg-dark border-secondary text-light" href="#" onclick="changePage(${currentPage + 1})">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>
  `;

  pagination.innerHTML = paginationHTML;
}

function updateCounts() {
  const startIndex = (currentPage - 1) * usersPerPage;
  const endIndex = Math.min(startIndex + usersPerPage, filteredUsers.length);
  
  document.getElementById('showingCount').textContent = filteredUsers.length === 0 ? '0' : `${startIndex + 1}-${endIndex}`;
  document.getElementById('totalCount').textContent = filteredUsers.length;
}

function updateStats() {
  // Update stats based on current data
  const total = allUsers.length;
  const active = allUsers.filter(u => u.subscription_status === 'active').length;
  const trial = allUsers.filter(u => u.subscription_status === 'trial').length;
  const expired = allUsers.filter(u => u.subscription_status === 'expired').length;

  // Update DOM if elements exist
  const totalElement = document.querySelector('.feature-icon:has(.bi-people)').closest('.glass-card').querySelector('h3');
  const activeElement = document.querySelector('.feature-icon:has(.bi-check-circle)').closest('.glass-card').querySelector('h3');
  const trialElement = document.querySelector('.feature-icon:has(.bi-clock-history)').closest('.glass-card').querySelector('h3');
  const expiredElement = document.querySelector('.feature-icon:has(.bi-x-circle)').closest('.glass-card').querySelector('h3');

  if (totalElement) totalElement.textContent = total;
  if (activeElement) activeElement.textContent = active;
  if (trialElement) trialElement.textContent = trial;
  if (expiredElement) expiredElement.textContent = expired;
}

async function handleAddUser(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando usuario...';
  
  try {
    const formData = new FormData(e.target);
    const userData = Object.fromEntries(formData.entries());
    
    const response = await fetch('api/users.php', {
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
    
    showAlert('success', data.message);
    
    // Close modal and reload users
    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
    modal.hide();
    e.target.reset();
    loadUsers();
    
  } catch (error) {
    console.error('Error creating user:', error);
    showAlert('danger', 'Error al crear usuario');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

async function handleEditUser(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando cambios...';
  
  try {
    const formData = new FormData(e.target);
    const userData = Object.fromEntries(formData.entries());
    const userId = userData.userId;
    delete userData.userId;
    
    // Map form field names to database field names
    if (userData.subscriptionStatus) {
      userData.subscription_status = userData.subscriptionStatus;
      delete userData.subscriptionStatus;
    }
    if (userData.subscriptionPlan) {
      userData.subscription_plan = userData.subscriptionPlan;
      delete userData.subscriptionPlan;
    }
    if (userData.firstName) {
      userData.first_name = userData.firstName;
      delete userData.firstName;
    }
    if (userData.lastName) {
      userData.last_name = userData.lastName;
      delete userData.lastName;
    }
    if (userData.clinicName) {
      userData.clinic_name = userData.clinicName;
      delete userData.clinicName;
    }
    if (userData.licenseNumber) {
      userData.license_number = userData.licenseNumber;
      delete userData.licenseNumber;
    }
    if (userData.teamSize) {
      userData.team_size = userData.teamSize;
      delete userData.teamSize;
    }
    
    const response = await fetch(`api/users.php?id=${userId}`, {
      method: 'PUT',
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
    
    showAlert('success', data.message);
    
    // Close modal and reload users
    const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
    modal.hide();
    loadUsers();
    
  } catch (error) {
    console.error('Error updating user:', error);
    showAlert('danger', 'Error al actualizar usuario');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions for user actions
window.changePage = function(page) {
  const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
  if (page >= 1 && page <= totalPages) {
    currentPage = page;
    renderUsers();
  }
}

window.clearFilters = function() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('planFilter').value = '';
  filterUsers();
}

window.exportUsers = function() {
  // Create CSV content
  const headers = ['Nombre', 'Apellido', 'Email', 'Teléfono', 'Consultorio', 'Plan', 'Estado', 'Fecha Registro'];
  const csvContent = [
    headers.join(','),
    ...filteredUsers.map(user => [
      user.first_name,
      user.last_name,
      user.email,
      user.phone || '',
      user.clinic_name || '',
      getPlanName(user.subscription_plan),
      getStatusName(user.subscription_status),
      formatDate(user.created_at)
    ].map(field => `"${field}"`).join(','))
  ].join('\n');

  // Download CSV
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `usuarios_dentexapro_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  showAlert('success', 'Archivo CSV descargado exitosamente');
}

window.viewUser = function(userId) {
  const user = allUsers.find(u => u.user_id === userId);
  if (!user) return;

  const content = `
    <div class="row g-4">
      <div class="col-md-6">
        <div class="glass-card p-3">
          <h6 class="text-white mb-3">
            <i class="bi bi-person-circle me-2"></i>Información personal
          </h6>
          <div class="mb-2">
            <strong class="text-light">Nombre completo:</strong>
            <div class="text-white">${user.first_name} ${user.last_name}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Email:</strong>
            <div class="text-white">${user.email}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Teléfono:</strong>
            <div class="text-white">${user.phone || 'No especificado'}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Rol:</strong>
            <div class="text-white">
              <span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-info'}">
                <i class="bi bi-shield${user.role === 'admin' ? '-check' : ''} me-1"></i>
                ${user.role === 'admin' ? 'Administrador' : 'Usuario'}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="glass-card p-3">
          <h6 class="text-white mb-3">
            <i class="bi bi-briefcase me-2"></i>Información profesional
          </h6>
          <div class="mb-2">
            <strong class="text-light">Consultorio:</strong>
            <div class="text-white">${user.clinic_name || 'No especificado'}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Matrícula:</strong>
            <div class="text-white">${user.license_number || 'No especificada'}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Especialidad:</strong>
            <div class="text-white">${getSpecialtyName(user.specialty)}</div>
          </div>
          <div class="mb-2">
            <strong class="text-light">Tamaño del equipo:</strong>
            <div class="text-white">${user.team_size || 'No especificado'}</div>
          </div>
        </div>
      </div>
      
      <div class="col-12">
        <div class="glass-card p-3">
          <h6 class="text-white mb-3">
            <i class="bi bi-credit-card me-2"></i>Información de suscripción
          </h6>
          <div class="row g-3">
            <div class="col-md-4">
              <strong class="text-light">Plan actual:</strong>
              <div class="text-white">
                <span class="badge ${getPlanBadgeClass(user.subscription_plan)}">
                  ${getPlanName(user.subscription_plan)}
                </span>
              </div>
            </div>
            <div class="col-md-4">
              <strong class="text-light">Estado:</strong>
              <div class="text-white">
                <span class="badge ${getStatusBadgeClass(user.subscription_status)}">
                  ${getStatusName(user.subscription_status)}
                </span>
              </div>
            </div>
            <div class="col-md-4">
              <strong class="text-light">Fecha de registro:</strong>
              <div class="text-white">${formatDate(user.created_at)}</div>
            </div>
            ${user.subscription_status === 'trial' && user.trial_days_remaining !== null ? `
            <div class="col-md-6">
              <strong class="text-light">Días de prueba restantes:</strong>
              <div class="text-warning fw-bold">${user.trial_days_remaining} días</div>
            </div>
            <div class="col-md-6">
              <strong class="text-light">Fin de prueba:</strong>
              <div class="text-white">${formatDate(user.trial_end_date)}</div>
            </div>
            ` : ''}
          </div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('userDetailsContent').innerHTML = content;
  const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
  modal.show();
}

window.editUser = function(userId) {
  const user = allUsers.find(u => u.user_id === userId);
  if (!user) return;

  // Fill form with user data
  document.getElementById('editUserId').value = user.user_id;
  document.getElementById('editFirstName').value = user.first_name;
  document.getElementById('editLastName').value = user.last_name;
  document.getElementById('editPhone').value = user.phone || '';
  document.getElementById('editLicenseNumber').value = user.license_number || '';
  document.getElementById('editClinicName').value = user.clinic_name || '';
  document.getElementById('editSpecialty').value = user.specialty || '';
  document.getElementById('editTeamSize').value = user.team_size || '1';
  document.getElementById('editSubscriptionStatus').value = user.subscription_status || 'trial';
  document.getElementById('editSubscriptionPlan').value = user.subscription_plan || '';

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
  modal.show();
}

window.toggleUserStatus = function(userId, currentStatus) {
  const newStatus = currentStatus === 'active' ? 'expired' : 'active';
  const action = newStatus === 'active' ? 'activar' : 'suspender';
  
  if (confirm(`¿Estás seguro de que quieres ${action} este usuario?`)) {
    fetch(`api/users.php?id=${userId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ subscription_status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', `Usuario ${action === 'activar' ? 'activado' : 'suspendido'} exitosamente`);
        loadUsers();
      }
    })
    .catch(error => {
      console.error('Error updating user status:', error);
      showAlert('danger', 'Error al actualizar estado del usuario');
    });
  }
}

window.deleteUser = function(userId, userName) {
  if (confirm(`¿Estás seguro de que quieres eliminar al usuario ${userName}?\n\nEsta acción no se puede deshacer.`)) {
    fetch(`api/users.php?id=${userId}`, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', data.message);
        loadUsers();
      }
    })
    .catch(error => {
      console.error('Error deleting user:', error);
      showAlert('danger', 'Error al eliminar usuario');
    });
  }
}

// Plan access management
window.managePlanAccess = function(userId) {
  const user = allUsers.find(u => u.user_id === userId);
  if (!user) return;

  // Fill modal with user data
  document.getElementById('accessUserId').value = user.user_id;
  document.getElementById('accessUserName').textContent = `${user.first_name} ${user.last_name}`;
  document.getElementById('accessUserEmail').textContent = user.email;
  document.getElementById('accessUserPlan').textContent = getPlanName(user.subscription_plan);
  document.getElementById('accessUserStatus').textContent = getStatusName(user.subscription_status);

  // Load existing access data if available
  loadPlanAccessData(userId);

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('planAccessModal'));
  modal.show();
}

async function loadPlanAccessData(userId) {
  try {
    const response = await fetch(`api/plan-access.php?user_id=${userId}`);
    const data = await response.json();
    
    if (data.success && data.access) {
      document.getElementById('panelUrl').value = data.access.panel_url || '';
      document.getElementById('panelUsername').value = data.access.panel_username || '';
      document.getElementById('panelPassword').value = data.access.panel_password || '';
      document.getElementById('accessNotes').value = data.access.access_notes || '';
    }
  } catch (error) {
    console.error('Error loading plan access data:', error);
  }
}

// Generate random password
window.generatePassword = function() {
  const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
  let password = '';
  for (let i = 0; i < 12; i++) {
    password += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  document.getElementById('panelPassword').value = password;
}

// Helper functions
function getPlanBadgeClass(plan) {
  switch(plan) {
    case 'start': return 'bg-info';
    case 'clinic': return 'bg-primary';
    case 'enterprise': return 'bg-warning text-dark';
    default: return 'bg-secondary';
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

function getStatusBadgeClass(status) {
  switch(status) {
    case 'active': return 'bg-success';
    case 'trial': return 'bg-warning text-dark';
    case 'expired': return 'bg-danger';
    case 'cancelled': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'active': return 'Activo';
    case 'trial': return 'Prueba';
    case 'expired': return 'Vencido';
    case 'cancelled': return 'Cancelado';
    default: return 'Sin estado';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'active': return 'check-circle';
    case 'trial': return 'clock-history';
    case 'expired': return 'x-circle';
    case 'cancelled': return 'dash-circle';
    default: return 'question-circle';
  }
}

function getSpecialtyName(specialty) {
  switch(specialty) {
    case 'general': return 'Odontología General';
    case 'ortodontia': return 'Ortodoncia';
    case 'endodoncia': return 'Endodoncia';
    case 'periodoncia': return 'Periodoncia';
    case 'cirugia': return 'Cirugía Oral';
    case 'pediatrica': return 'Odontopediatría';
    case 'estetica': return 'Odontología Estética';
    case 'implantes': return 'Implantología';
    default: return 'No especificada';
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
}

function formatTime(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleTimeString('es-AR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
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