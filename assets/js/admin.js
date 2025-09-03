// Admin dashboard functionality
import { supabase, supabaseAdmin, isSupabaseConfigured } from './supabase.js'

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

  // Check authentication and admin role
  checkAdminAuth();

  // Load dashboard data
  loadDashboardData();

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

  async function checkAdminAuth() {
    if (!isSupabaseConfigured()) {
      // Simulate admin access for demo
      const urlParams = new URLSearchParams(window.location.search);
      const email = urlParams.get('email') || 'admin@dentexapro.com';
      
      if (email === 'admin@dentexapro.com') {
        document.getElementById('adminName').textContent = 'Admin Demo';
        return;
      } else {
        alert('Acceso denegado. Solo administradores pueden acceder a esta página.');
        window.location.href = 'dashboard.html';
        return;
      }
    }

    try {
      // Check if we can connect to Supabase first
      const { data: { user }, error } = await supabase.auth.getUser().catch(fetchError => {
        console.error('Supabase connection failed:', fetchError);
        // If connection fails, treat as demo mode
        document.getElementById('adminName').textContent = 'Admin Demo (Sin conexión)';
        return { data: { user: null }, error: null };
      });
      
      if (error || !user) {
        // If no user and we have a connection error, stay in demo mode
        if (error && error.message.includes('fetch')) {
          document.getElementById('adminName').textContent = 'Admin Demo (Sin conexión)';
          return;
        }
        // Otherwise redirect to login
        window.location.href = 'login.html';
        return;
      }

      // Check if user is admin by email first (for admin@dentexapro.com)
      if (user.email === 'admin@dentexapro.com') {
        document.getElementById('adminName').textContent = 'Administrador';
        return;
      }

      // Check if user is admin in database
      const { data: profile, error: profileError } = await supabase
        .from('user_profiles')
        .select('role, first_name, last_name')
        .eq('user_id', user.id)
        .single()
        .catch(fetchError => {
          console.error('Profile fetch failed:', fetchError);
          return { data: null, error: fetchError };
        });

      if (profileError || !profile || profile.role !== 'admin') {
        alert('Acceso denegado. Solo administradores pueden acceder a esta página.');
        window.location.href = 'dashboard.html';
        return;
      }

      // Update admin name
      document.getElementById('adminName').textContent = `${profile.first_name} ${profile.last_name}`;

    } catch (error) {
      console.error('Error checking admin auth:', error);
      // If it's a connection error, stay in demo mode
      if (error.message && error.message.includes('fetch')) {
        document.getElementById('adminName').textContent = 'Admin Demo (Sin conexión)';
        return;
      }
      window.location.href = 'login.html';
    }
  }

  async function loadDashboardData() {
    console.log('Loading dashboard data...');
    console.log('Supabase configured:', isSupabaseConfigured());

    try {
      // Check if Supabase is configured first
      if (!isSupabaseConfigured()) {
        console.log('Supabase not configured, loading mock users');
        loadMockUsers();
        return;
      }

      console.log('Supabase is configured, attempting to load real users...');
      
      // Load real data from Supabase
      const { data: users, error } = await supabaseAdmin
        .from('user_profiles')
        .select('*')
        .order('created_at', { ascending: false });

      console.log('Supabase query result:', { users, error });

      if (error) {
        console.error('Error loading users from Supabase:', error);
        showAlert('danger', `Error cargando usuarios: ${error.message}`);
        loadMockUsers(); // Fallback to mock data
        return;
      }

      // Show real data from Supabase
      const usersList = users || [];
      console.log('Processing users list:', usersList);
      console.log('Users details:', usersList.map(u => ({ 
        email: u.email, 
        name: `${u.first_name} ${u.last_name}`,
        created_at: u.created_at,
        subscription_status: u.subscription_status 
      })));
      console.log('Total users found:', usersList.length);

      if (usersList.length === 0) {
        console.log('No users found in database, showing empty state');
        // Update stats with zeros
        document.getElementById('totalUsers').textContent = '0';
        document.getElementById('activeSubscriptions').textContent = '0';
        document.getElementById('trialUsers').textContent = '0';
        document.getElementById('monthlyRevenue').textContent = '$0';
        loadUsersTable([]);
      } else {
        console.log('Found users, updating dashboard...');
        // Update stats
        const totalUsers = usersList.length;
        const activeSubscriptions = usersList.filter(u => u.subscription_status === 'active').length;
        const trialUsers = usersList.filter(u => u.subscription_status === 'trial').length;
        
        document.getElementById('totalUsers').textContent = totalUsers;
        document.getElementById('activeSubscriptions').textContent = activeSubscriptions;
        document.getElementById('trialUsers').textContent = trialUsers;
        
        // Calculate monthly revenue (mock calculation)
        const monthlyRevenue = (activeSubscriptions * 20).toFixed(0); // Average $20 per subscription
        document.getElementById('monthlyRevenue').textContent = `$${monthlyRevenue}`;

        // Load users table
        loadUsersTable(usersList);
      }

    } catch (error) {
      console.error('Error loading dashboard data:', error);
      showAlert('danger', `Error de conexión: ${error.message}`);
      loadMockUsers(); // Fallback to mock data
    }
  }

  function loadMockUsers() {
    const mockUsers = [
      {
        first_name: 'Dr. María',
        last_name: 'González',
        email: 'maria@clinica.com',
        clinic_name: 'Clínica González',
        subscription_plan: 'clinic',
        subscription_status: 'active',
        created_at: '2024-01-15'
      },
      {
        first_name: 'Dr. Carlos',
        last_name: 'Rodríguez',
        email: 'carlos@dental.com',
        clinic_name: 'Dental Rodríguez',
        subscription_plan: 'start',
        subscription_status: 'trial',
        created_at: '2024-01-20'
      },
      {
        first_name: 'Dra. Ana',
        last_name: 'López',
        email: 'ana@odonto.com',
        clinic_name: 'Odontología López',
        subscription_plan: 'clinic',
        subscription_status: 'active',
        created_at: '2024-01-18'
      },
      {
        first_name: 'Dr. Juan',
        last_name: 'Martínez',
        email: 'juan@dental.com',
        clinic_name: 'Consultorio Martínez',
        subscription_plan: 'start',
        subscription_status: 'trial',
        created_at: '2024-01-22'
      },
      {
        first_name: 'Dra. Laura',
        last_name: 'Fernández',
        email: 'laura@smile.com',
        clinic_name: 'Smile Center',
        subscription_plan: 'enterprise',
        subscription_status: 'active',
        created_at: '2024-01-10'
      }
    ];
    
    // Update stats with mock data
    document.getElementById('totalUsers').textContent = mockUsers.length;
    document.getElementById('activeSubscriptions').textContent = mockUsers.filter(u => u.subscription_status === 'active').length;
    document.getElementById('trialUsers').textContent = mockUsers.filter(u => u.subscription_status === 'trial').length;
    document.getElementById('monthlyRevenue').textContent = '$1,247';
    
    loadUsersTable(mockUsers);
  }

  function loadUsersTable(users) {
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
              <li><a class="dropdown-item" href="#"><i class="bi bi-eye me-2"></i>Ver detalles</a></li>
              <li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i>Editar</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
            </ul>
          </div>
        </td>
      </tr>
    `).join('');
  }

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
});