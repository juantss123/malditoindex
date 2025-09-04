// Admin newsletter management functionality

let allSubscribers = [];
let filteredSubscribers = [];
let allCampaigns = [];
let quillEditor;

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

  // Initialize Quill editor
  initializeEditor();

  // Load data
  loadSubscribers();
  loadCampaigns();
  loadAnalytics();

  // Search and filter functionality
  const searchInput = document.getElementById('searchSubscribers');
  const statusFilter = document.getElementById('statusFilter');
  const sourceFilter = document.getElementById('sourceFilter');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterSubscribers, 300));
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterSubscribers);
  }
  if (sourceFilter) {
    sourceFilter.addEventListener('change', filterSubscribers);
  }

  // Form handlers
  const createCampaignForm = document.getElementById('createCampaignForm');
  if (createCampaignForm) {
    createCampaignForm.addEventListener('submit', handleCreateCampaign);
    
    // Schedule toggle
    const scheduleSelect = createCampaignForm.querySelector('select[name="schedule"]');
    const scheduleFields = document.getElementById('scheduleFields');
    
    if (scheduleSelect && scheduleFields) {
      scheduleSelect.addEventListener('change', (e) => {
        scheduleFields.style.display = e.target.value === 'schedule' ? 'block' : 'none';
      });
    }
  }

  const editSubscriberForm = document.getElementById('editSubscriberForm');
  if (editSubscriberForm) {
    editSubscriberForm.addEventListener('submit', handleEditSubscriber);
  }
});

function initializeEditor() {
  quillEditor = new Quill('#campaignEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean']
      ]
    },
    placeholder: 'Escribe el contenido de tu campaña de email aquí...'
  });
}

async function loadSubscribers() {
  const tbody = document.getElementById('subscribersTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="6" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando suscriptores...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/newsletter.php?action=subscribers');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allSubscribers = data.subscribers || [];
    filteredSubscribers = [...allSubscribers];
    
    renderSubscribers();

  } catch (error) {
    console.error('Error loading subscribers:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar suscriptores
        </td>
      </tr>
    `;
  }
}

async function loadCampaigns() {
  const tbody = document.getElementById('campaignsTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando campañas...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/newsletter.php?action=campaigns');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allCampaigns = data.campaigns || [];
    renderCampaigns();

  } catch (error) {
    console.error('Error loading campaigns:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar campañas
        </td>
      </tr>
    `;
  }
}

function filterSubscribers() {
  const searchTerm = document.getElementById('searchSubscribers').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const sourceFilter = document.getElementById('sourceFilter').value;

  filteredSubscribers = allSubscribers.filter(subscriber => {
    const matchesSearch = !searchTerm || 
      subscriber.email.toLowerCase().includes(searchTerm) ||
      (subscriber.name && subscriber.name.toLowerCase().includes(searchTerm));

    const matchesStatus = !statusFilter || subscriber.status === statusFilter;
    const matchesSource = !sourceFilter || subscriber.source === sourceFilter;

    return matchesSearch && matchesStatus && matchesSource;
  });

  renderSubscribers();
}

function renderSubscribers() {
  const tbody = document.getElementById('subscribersTable');

  if (filteredSubscribers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No se encontraron suscriptores
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = filteredSubscribers.map(subscriber => `
    <tr>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
            <i class="bi bi-envelope text-white"></i>
          </div>
          <div class="text-white">${subscriber.email}</div>
        </div>
      </td>
      <td class="text-light">${subscriber.name || 'Sin nombre'}</td>
      <td>
        <span class="badge ${getStatusBadgeClass(subscriber.status)}">
          <i class="bi bi-${getStatusIcon(subscriber.status)} me-1"></i>
          ${getStatusName(subscriber.status)}
        </span>
      </td>
      <td>
        <span class="badge ${getSourceBadgeClass(subscriber.source)}">
          <i class="bi bi-${getSourceIcon(subscriber.source)} me-1"></i>
          ${getSourceName(subscriber.source)}
        </span>
      </td>
      <td class="text-light opacity-75">${formatDateTime(subscriber.created_at)}</td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="#" onclick="editSubscriber('${subscriber.id}')">
                <i class="bi bi-pencil me-2"></i>Editar
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="sendTestEmail('${subscriber.email}')">
                <i class="bi bi-envelope me-2"></i>Enviar email de prueba
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            ${subscriber.status === 'active' ? `
            <li>
              <a class="dropdown-item text-warning" href="#" onclick="unsubscribeUser('${subscriber.id}')">
                <i class="bi bi-person-dash me-2"></i>Desuscribir
              </a>
            </li>
            ` : `
            <li>
              <a class="dropdown-item text-success" href="#" onclick="resubscribeUser('${subscriber.id}')">
                <i class="bi bi-person-check me-2"></i>Reactivar
              </a>
            </li>
            `}
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deleteSubscriber('${subscriber.id}', '${subscriber.email}')">
                <i class="bi bi-trash me-2"></i>Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderCampaigns() {
  const tbody = document.getElementById('campaignsTable');

  if (allCampaigns.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No hay campañas creadas
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = allCampaigns.map(campaign => `
    <tr>
      <td>
        <div class="text-white fw-medium">${campaign.subject}</div>
        <small class="text-light opacity-75">Por ${campaign.created_by_name}</small>
      </td>
      <td>
        <span class="badge ${getCampaignStatusBadgeClass(campaign.status)}">
          <i class="bi bi-${getCampaignStatusIcon(campaign.status)} me-1"></i>
          ${getCampaignStatusName(campaign.status)}
        </span>
      </td>
      <td class="text-light">${campaign.total_recipients}</td>
      <td class="text-light">${campaign.total_sent}</td>
      <td>
        <div class="text-light">${campaign.total_opened}</div>
        <small class="text-success">${campaign.total_recipients > 0 ? ((campaign.total_opened / campaign.total_recipients) * 100).toFixed(1) : 0}%</small>
      </td>
      <td class="text-light opacity-75">${formatDateTime(campaign.sent_at || campaign.created_at)}</td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="#" onclick="viewCampaign('${campaign.id}')">
                <i class="bi bi-eye me-2"></i>Ver campaña
              </a>
            </li>
            ${campaign.status === 'draft' ? `
            <li>
              <a class="dropdown-item text-success" href="#" onclick="sendCampaign('${campaign.id}')">
                <i class="bi bi-send me-2"></i>Enviar ahora
              </a>
            </li>
            ` : ''}
            <li>
              <a class="dropdown-item" href="#" onclick="duplicateCampaign('${campaign.id}')">
                <i class="bi bi-files me-2"></i>Duplicar
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deleteCampaign('${campaign.id}', '${campaign.subject}')">
                <i class="bi bi-trash me-2"></i>Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  `).join('');
}

async function loadAnalytics() {
  try {
    // Load growth chart
    const growthData = generateMockGrowthData();
    renderGrowthChart(growthData);
    
    // Load sources chart
    const sourcesData = [
      { source: 'Blog', count: Math.floor(allSubscribers.length * 0.6) },
      { source: 'Landing', count: Math.floor(allSubscribers.length * 0.3) },
      { source: 'Manual', count: Math.floor(allSubscribers.length * 0.1) }
    ];
    renderSourcesChart(sourcesData);
    
    // Load recent activity
    renderRecentActivity();
    
  } catch (error) {
    console.error('Error loading analytics:', error);
  }
}

function renderGrowthChart(data) {
  const ctx = document.getElementById('growthChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.map(item => item.month),
      datasets: [{
        label: 'Nuevos suscriptores',
        data: data.map(item => item.count),
        borderColor: '#2F96EE',
        backgroundColor: '#2F96EE20',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#2F96EE',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(255,255,255,0.1)'
          },
          ticks: {
            color: 'rgba(255,255,255,0.7)'
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: 'rgba(255,255,255,0.7)'
          }
        }
      }
    }
  });
}

function renderSourcesChart(data) {
  const ctx = document.getElementById('sourcesChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: data.map(item => item.source),
      datasets: [{
        data: data.map(item => item.count),
        backgroundColor: [
          '#2F96EE',
          '#17a2b8',
          '#28a745'
        ],
        borderWidth: 0,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            color: 'rgba(255,255,255,0.8)'
          }
        }
      }
    }
  });
}

function renderRecentActivity() {
  const container = document.getElementById('recentActivity');
  
  const activities = [
    { type: 'subscribe', email: 'nuevo@email.com', time: '2 min ago' },
    { type: 'unsubscribe', email: 'usuario@test.com', time: '1 hora ago' },
    { type: 'campaign', subject: 'Newsletter Enero', time: '2 horas ago' },
    { type: 'subscribe', email: 'dentista@clinica.com', time: '3 horas ago' }
  ];

  container.innerHTML = activities.map(activity => `
    <div class="d-flex align-items-center mb-3 glass-card p-3">
      <div class="bg-${getActivityColor(activity.type)} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
        <i class="bi bi-${getActivityIcon(activity.type)} text-white"></i>
      </div>
      <div class="flex-grow-1">
        <div class="text-white">${getActivityText(activity)}</div>
        <small class="text-light opacity-75">${activity.time}</small>
      </div>
    </div>
  `).join('');
}

async function handleCreateCampaign(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando campaña...';
  
  try {
    const formData = new FormData(e.target);
    const campaignData = Object.fromEntries(formData.entries());
    
    // Get content from Quill editor
    campaignData.content = quillEditor.root.innerHTML;
    
    // Set status based on schedule selection
    if (campaignData.schedule === 'draft') {
      campaignData.status = 'draft';
    } else if (campaignData.schedule === 'schedule') {
      campaignData.status = 'scheduled';
    } else {
      campaignData.status = 'draft'; // Will be sent immediately in real implementation
    }
    
    const response = await fetch('api/newsletter.php?action=campaign', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(campaignData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload campaigns
    const modal = bootstrap.Modal.getInstance(document.getElementById('createCampaignModal'));
    modal.hide();
    e.target.reset();
    quillEditor.setContents([]);
    loadCampaigns();
    
  } catch (error) {
    console.error('Error creating campaign:', error);
    showAlert('danger', 'Error al crear campaña');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

async function handleEditSubscriber(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
  
  try {
    const formData = new FormData(e.target);
    const subscriberData = Object.fromEntries(formData.entries());
    const subscriberId = subscriberData.subscriberId;
    delete subscriberData.subscriberId;
    
    const response = await fetch(`api/newsletter.php?id=${subscriberId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(subscriberData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload subscribers
    const modal = bootstrap.Modal.getInstance(document.getElementById('editSubscriberModal'));
    modal.hide();
    loadSubscribers();
    
  } catch (error) {
    console.error('Error updating subscriber:', error);
    showAlert('danger', 'Error al actualizar suscriptor');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions
window.loadSubscribers = loadSubscribers;
window.loadCampaigns = loadCampaigns;

window.editSubscriber = function(subscriberId) {
  const subscriber = allSubscribers.find(s => s.id === subscriberId);
  if (!subscriber) return;

  document.getElementById('editSubscriberId').value = subscriber.id;
  document.getElementById('editSubscriberEmail').value = subscriber.email;
  document.getElementById('editSubscriberName').value = subscriber.name || '';
  document.getElementById('editSubscriberStatus').value = subscriber.status;

  const modal = new bootstrap.Modal(document.getElementById('editSubscriberModal'));
  modal.show();
}

window.unsubscribeUser = function(subscriberId) {
  if (confirm('¿Desuscribir a este usuario del newsletter?')) {
    updateSubscriberStatus(subscriberId, 'unsubscribed');
  }
}

window.resubscribeUser = function(subscriberId) {
  if (confirm('¿Reactivar la suscripción de este usuario?')) {
    updateSubscriberStatus(subscriberId, 'active');
  }
}

window.deleteSubscriber = function(subscriberId, email) {
  if (confirm(`¿Eliminar permanentemente a ${email} del newsletter?\n\nEsta acción no se puede deshacer.`)) {
    fetch(`api/newsletter.php?id=${subscriberId}`, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', data.message);
        loadSubscribers();
      }
    })
    .catch(error => {
      console.error('Error deleting subscriber:', error);
      showAlert('danger', 'Error al eliminar suscriptor');
    });
  }
}

window.sendTestEmail = function(email) {
  showAlert('info', `Email de prueba enviado a ${email} (funcionalidad simulada)`);
}

window.clearSubscriberFilters = function() {
  document.getElementById('searchSubscribers').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('sourceFilter').value = '';
  filterSubscribers();
}

window.exportSubscribers = function() {
  // Create CSV content
  const headers = ['Email', 'Nombre', 'Estado', 'Fuente', 'Fecha Suscripción'];
  const csvContent = [
    headers.join(','),
    ...filteredSubscribers.map(subscriber => [
      subscriber.email,
      subscriber.name || '',
      getStatusName(subscriber.status),
      getSourceName(subscriber.source),
      formatDate(subscriber.created_at)
    ].map(field => `"${field}"`).join(','))
  ].join('\n');

  // Download CSV
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `newsletter_suscriptores_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  showAlert('success', 'Lista de suscriptores exportada exitosamente');
}

window.useTemplate = function(templateType) {
  const templates = {
    welcome: {
      subject: '¡Bienvenido al newsletter de DentexaPro!',
      content: '<h2>¡Hola y bienvenido!</h2><p>Gracias por suscribirte al newsletter de DentexaPro. Cada semana recibirás:</p><ul><li>Tips para optimizar tu consultorio</li><li>Novedades del sector dental</li><li>Casos de éxito de colegas</li><li>Actualizaciones de la plataforma</li></ul><p>¡Esperamos que disfrutes el contenido!</p>'
    },
    update: {
      subject: 'Novedades de DentexaPro - Nuevas funcionalidades',
      content: '<h2>¡Nuevas funcionalidades disponibles!</h2><p>Nos complace anunciar las últimas mejoras en DentexaPro:</p><ul><li>🦷 Odontograma mejorado</li><li>📱 Recordatorios por WhatsApp</li><li>📊 Reportes avanzados</li><li>🔒 Mayor seguridad</li></ul><p>Ingresa a tu panel para explorar todas las novedades.</p>'
    },
    tips: {
      subject: '5 Tips para optimizar tu consultorio dental',
      content: '<h2>Tips de la semana</h2><p>Aquí tienes 5 consejos prácticos para mejorar tu práctica dental:</p><ol><li><strong>Digitaliza tu agenda:</strong> Reduce ausencias con recordatorios automáticos</li><li><strong>Portal del paciente:</strong> Mejora la experiencia con acceso 24/7</li><li><strong>Historia clínica digital:</strong> Acceso instantáneo a toda la información</li><li><strong>Reportes de gestión:</strong> Toma decisiones basadas en datos</li><li><strong>Facturación integrada:</strong> Simplifica tu administración</li></ol>'
    },
    promo: {
      subject: '¡Oferta especial para suscriptores del newsletter!',
      content: '<h2>🎉 ¡Oferta exclusiva!</h2><p>Como suscriptor de nuestro newsletter, tienes acceso a esta promoción especial:</p><div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;"><h3 style="color: #2F96EE;">50% OFF en tu primer mes</h3><p>Válido hasta fin de mes</p></div><p>Aprovecha esta oportunidad para digitalizar tu consultorio con DentexaPro.</p>'
    }
  };

  const template = templates[templateType];
  if (!template) return;

  // Fill form with template data
  document.querySelector('input[name="subject"]').value = template.subject;
  quillEditor.root.innerHTML = template.content;

  // Show success message
  showAlert('success', 'Plantilla aplicada exitosamente');
}

async function updateSubscriberStatus(subscriberId, newStatus) {
  try {
    const response = await fetch(`api/newsletter.php?id=${subscriberId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
    } else {
      showAlert('success', data.message);
      loadSubscribers();
    }
    
  } catch (error) {
    console.error('Error updating subscriber status:', error);
    showAlert('danger', 'Error al actualizar estado del suscriptor');
  }
}

// Helper functions
function getStatusBadgeClass(status) {
  switch(status) {
    case 'active': return 'bg-success';
    case 'unsubscribed': return 'bg-warning text-dark';
    case 'bounced': return 'bg-danger';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'active': return 'Activo';
    case 'unsubscribed': return 'Desuscrito';
    case 'bounced': return 'Rebotado';
    default: return 'Desconocido';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'active': return 'check-circle';
    case 'unsubscribed': return 'person-dash';
    case 'bounced': return 'exclamation-triangle';
    default: return 'question-circle';
  }
}

function getSourceBadgeClass(source) {
  switch(source) {
    case 'blog': return 'bg-primary';
    case 'landing': return 'bg-info';
    case 'manual': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getSourceName(source) {
  switch(source) {
    case 'blog': return 'Blog';
    case 'landing': return 'Landing';
    case 'manual': return 'Manual';
    default: return 'Desconocido';
  }
}

function getSourceIcon(source) {
  switch(source) {
    case 'blog': return 'journal-text';
    case 'landing': return 'house';
    case 'manual': return 'person-plus';
    default: return 'question-circle';
  }
}

function getCampaignStatusBadgeClass(status) {
  switch(status) {
    case 'draft': return 'bg-secondary';
    case 'scheduled': return 'bg-warning text-dark';
    case 'sending': return 'bg-info';
    case 'sent': return 'bg-success';
    case 'cancelled': return 'bg-danger';
    default: return 'bg-secondary';
  }
}

function getCampaignStatusName(status) {
  switch(status) {
    case 'draft': return 'Borrador';
    case 'scheduled': return 'Programada';
    case 'sending': return 'Enviando';
    case 'sent': return 'Enviada';
    case 'cancelled': return 'Cancelada';
    default: return 'Desconocido';
  }
}

function getCampaignStatusIcon(status) {
  switch(status) {
    case 'draft': return 'file-earmark';
    case 'scheduled': return 'clock';
    case 'sending': return 'arrow-repeat';
    case 'sent': return 'check-circle';
    case 'cancelled': return 'x-circle';
    default: return 'question-circle';
  }
}

function getActivityColor(type) {
  switch(type) {
    case 'subscribe': return 'success';
    case 'unsubscribe': return 'warning';
    case 'campaign': return 'primary';
    default: return 'secondary';
  }
}

function getActivityIcon(type) {
  switch(type) {
    case 'subscribe': return 'person-plus';
    case 'unsubscribe': return 'person-dash';
    case 'campaign': return 'send';
    default: return 'circle';
  }
}

function getActivityText(activity) {
  switch(activity.type) {
    case 'subscribe': return `Nuevo suscriptor: ${activity.email}`;
    case 'unsubscribe': return `Se desuscribió: ${activity.email}`;
    case 'campaign': return `Campaña enviada: ${activity.subject}`;
    default: return 'Actividad desconocida';
  }
}

function generateMockGrowthData() {
  const months = [];
  const now = new Date();
  
  for (let i = 5; i >= 0; i--) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
    months.push({
      month: date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' }),
      count: Math.floor(Math.random() * 20) + 5
    });
  }
  
  return months;
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
}

function formatDateTime(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleString('es-AR');
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