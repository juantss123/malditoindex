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

  // Add user form handler
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.addEventListener('submit', handleAddUser);
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
  window.viewUser = function(userId) {
    alert('Ver detalles del usuario: ' + userId);
  }

  window.editUser = function(userId) {
    alert('Editar usuario: ' + userId);
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
});