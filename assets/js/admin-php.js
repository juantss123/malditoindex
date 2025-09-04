// Admin dashboard functionality for PHP version

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

  // Load users table
  loadUsers();

  // Load trial requests
  loadTrialRequests();

  // Add user form handler
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.addEventListener('submit', handleAddUser);
  }

  // Trial request form handler
  const trialRequestForm = document.getElementById('trialRequestForm');
  if (trialRequestForm) {
    trialRequestForm.addEventListener('submit', handleTrialRequest);
    
    // Show/hide trial access fields based on status selection
    const statusSelect = trialRequestForm.querySelector('select[name="status"]');
    const trialAccessFields = document.getElementById('trialAccessFields');
    const trialWebsiteInput = trialRequestForm.querySelector('input[name="trial_website"]');
    const trialUsernameInput = trialRequestForm.querySelector('input[name="trial_username"]');
    const trialPasswordInput = trialRequestForm.querySelector('input[name="trial_password"]');
    
    if (statusSelect && trialAccessFields) {
      statusSelect.addEventListener('change', (e) => {
        if (e.target.value === 'approved') {
          trialAccessFields.style.display = 'block';
          trialWebsiteInput.required = true;
          trialUsernameInput.required = true;
          trialPasswordInput.required = true;
        } else {
          trialAccessFields.style.display = 'none';
          trialWebsiteInput.required = false;
          trialUsernameInput.required = false;
          trialPasswordInput.required = false;
        }
      });
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
      
      const users = data.users || [];
      const tbody = document.getElementById('usersTable');
      
      if (users.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-light opacity-75 py-4">
              <i class="bi bi-inbox me-2"></i>No hay usuarios registrados
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = users.map(user => `
        <tr>
          <td>
            <div class="d-flex align-items-center">
              <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <i class="bi bi-person text-white"></i>
              </div>
              <div>
                <div class="text-white">${user.first_name} ${user.last_name}</div>
                <small class="text-light opacity-75">${user.clinic_name || 'Sin consultorio'}</small>
              </div>
            </div>
          </td>
          <td class="text-light">${user.email}</td>
          <td>
            <span class="badge ${getPlanBadgeClass(user.subscription_plan)}">
              ${getPlanName(user.subscription_plan)}
            </span>
          </td>
          <td>
            <span class="badge ${getStatusBadgeClass(user.subscription_status)}">
              ${getStatusName(user.subscription_status)}
            </span>
          </td>
          <td class="text-light opacity-75">${formatDate(user.created_at)}</td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-dark">
                <li><a class="dropdown-item" href="#" onclick="viewUser('${user.user_id}')"><i class="bi bi-eye me-2"></i>Ver detalles</a></li>
                <li><a class="dropdown-item" href="#" onclick="editUser('${user.user_id}')"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser('${user.user_id}', '${user.first_name} ${user.last_name}')"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
              </ul>
            </div>
          </td>
        </tr>
      `).join('');

    } catch (error) {
      console.error('Error loading users:', error);
      showAlert('danger', 'Error al cargar usuarios');
    }
  }

  async function handleAddUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = Object.fromEntries(formData.entries());
    
    try {
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
    }
  }

  async function loadTrialRequests() {
    const tbody = document.getElementById('trialRequestsTable');
    if (!tbody) {
      console.error('Trial requests table not found');
      return;
    }
    
    // Show loading state
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-light opacity-75 py-4">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Cargando solicitudes...
        </td>
      </tr>
    `;
    
    try {
      const response = await fetch('api/trial-requests.php');
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      if (data.error) {
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-danger py-4">
              <i class="bi bi-exclamation-triangle me-2"></i>Error: ${data.error || 'Error desconocido'}
            </td>
          </tr>
        `;
        return;
      }
      
      const requests = data.requests || [];
      
      if (requests.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-light opacity-75 py-4">
              <i class="bi bi-inbox me-2"></i>No hay solicitudes de prueba gratuita
              <br><small class="mt-2 d-block">Las solicitudes aparecerán aquí cuando los usuarios las envíen desde el dashboard</small>
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = requests.map(request => `
        <tr>
          <td>
            <div class="d-flex align-items-center">
              <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <i class="bi bi-person text-white"></i>
              </div>
              <div>
                <div class="text-white">${request.user_name}</div>
                <small class="text-light opacity-75">${request.email}</small>
              </div>
            </div>
          </td>
          <td class="text-light">${request.clinic_name || 'Sin consultorio'}</td>
          <td class="text-light opacity-75">${formatDateTime(request.request_date)}</td>
          <td>
            <span class="badge ${getRequestStatusBadgeClass(request.status)}">
              ${getRequestStatusName(request.status)}
            </span>
          </td>
          <td>
            ${request.status === 'pending' ? `
              <button class="btn btn-sm btn-success me-2" onclick="processTrialRequest('${request.id}', '${request.user_name}', '${request.email}', '${request.clinic_name}', '${request.phone}')">
                <i class="bi bi-check-lg"></i>
              </button>
            ` : `
              <span class="text-light opacity-75 small">
                Procesado ${request.processed_at ? 'el ' + formatDate(request.processed_at) : ''}
                ${request.processed_by_name ? 'por ' + request.processed_by_name : ''}
              </span>
            `}
          </td>
        </tr>
      `).join('');
      

    } catch (error) {
      console.error('Error loading trial requests:', error);
      
      // Show error in table
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-light opacity-75 py-4">
            <i class="bi bi-exclamation-triangle text-warning me-2"></i>Error al cargar solicitudes
            <br><small class="mt-2 d-block text-danger">Error: ${error.message}</small>
          </td>
        </tr>
      `;
    }
  }

  async function handleTrialRequest(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const requestData = Object.fromEntries(formData.entries());
    
    try {
      const response = await fetch(`api/trial-requests.php?id=${requestData.requestId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
      });
      
      const data = await response.json();
      
      if (data.error) {
        showAlert('danger', data.error);
        return;
      }
      
      showAlert('success', data.message);
      
      // Close modal and reload requests
      const modal = bootstrap.Modal.getInstance(document.getElementById('trialRequestModal'));
      modal.hide();
      e.target.reset();
      loadTrialRequests();
      
    } catch (error) {
      console.error('Error processing trial request:', error);
      showAlert('danger', 'Error al procesar solicitud');
    }
  }

  // Helper functions
  function getPlanBadgeClass(plan) {
    switch(plan) {
      case 'start': return 'bg-info';
      case 'clinic': return 'bg-primary';
      case 'enterprise': return 'bg-warning';
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
      case 'trial': return 'bg-warning';
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

  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('es-AR');
  }

  function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('es-AR');
  }

  function getRequestStatusBadgeClass(status) {
    switch(status) {
      case 'pending': return 'bg-warning';
      case 'approved': return 'bg-success';
      case 'rejected': return 'bg-danger';
      default: return 'bg-secondary';
    }
  }

  function getRequestStatusName(status) {
    switch(status) {
      case 'pending': return 'Pendiente';
      case 'approved': return 'Aprobada';
      case 'rejected': return 'Rechazada';
      default: return 'Sin estado';
    }
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
    }
  }

  // Global functions for user actions
  window.loadTrialRequests = loadTrialRequests;

  window.processTrialRequest = function(requestId, userName, userEmail, clinicName, userPhone) {
    console.log('Processing trial request:', requestId);
    // Fill modal with request data
    document.getElementById('requestId').value = requestId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalUserEmail').textContent = userEmail;
    document.getElementById('modalClinicName').textContent = clinicName;
    document.getElementById('modalUserPhone').textContent = userPhone || 'No especificado';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('trialRequestModal'));
    modal.show();
  }

  window.viewUser = function(userId) {
    console.log('View user:', userId);
    showAlert('info', 'Función "Ver detalles" en desarrollo');
  }

  window.editUser = function(userId) {
    console.log('Edit user:', userId);
    showAlert('info', 'Función "Editar usuario" en desarrollo');
  }

  window.deleteUser = function(userId, userName) {
    if (confirm(`¿Estás seguro de que quieres eliminar al usuario ${userName}?`)) {
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

  window.viewUser = function(userId) {
    // Find user in the current users list
    fetch('api/users.php')
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showAlert('danger', data.error);
          return;
        }
        
        const user = data.users.find(u => u.user_id === userId);
        if (!user) {
          showAlert('danger', 'Usuario no encontrado');
          return;
        }

        // Create user details content
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

        // Show modal with user details
        document.getElementById('userDetailsContent').innerHTML = content;
        const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
        modal.show();
      })
      .catch(error => {
        console.error('Error loading user details:', error);
        showAlert('danger', 'Error al cargar detalles del usuario');
      });
  }

  window.editUser = function(userId) {
    // Find user in the current users list
    fetch('api/users.php')
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          showAlert('danger', data.error);
          return;
        }
        
        const user = data.users.find(u => u.user_id === userId);
        if (!user) {
          showAlert('danger', 'Usuario no encontrado');
          return;
        }

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
      })
      .catch(error => {
        console.error('Error loading user for edit:', error);
        showAlert('danger', 'Error al cargar datos del usuario');
      });
  }

  // Helper function for specialty names
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

  function formatDateTime(dateString) {
    if (!dateString) return 'Sin fecha';
    return new Date(dateString).toLocaleString('es-AR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getRequestStatusBadgeClass(status) {
    switch(status) {
      case 'pending': return 'bg-warning';
      case 'approved': return 'bg-success';
      case 'rejected': return 'bg-danger';
      default: return 'bg-secondary';
    }
  }

  function getRequestStatusName(status) {
    switch(status) {
      case 'pending': return 'Pendiente';
      case 'approved': return 'Aprobada';
      case 'rejected': return 'Rechazada';
      default: return 'Sin estado';
    }
  }
});