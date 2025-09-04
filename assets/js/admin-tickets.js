// Admin tickets management functionality

let allTickets = [];
let filteredTickets = [];
let currentPage = 1;
const ticketsPerPage = 10;

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

  // Load tickets on page load
  loadTickets();

  // Search and filter functionality
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const priorityFilter = document.getElementById('priorityFilter');
  const categoryFilter = document.getElementById('categoryFilter');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterTickets, 300));
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterTickets);
  }
  if (priorityFilter) {
    priorityFilter.addEventListener('change', filterTickets);
  }
  if (categoryFilter) {
    categoryFilter.addEventListener('change', filterTickets);
  }

  // Form handlers
  const assignTicketForm = document.getElementById('assignTicketForm');
  if (assignTicketForm) {
    assignTicketForm.addEventListener('submit', handleAssignTicket);
  }
});

async function loadTickets() {
  const tbody = document.getElementById('ticketsTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="9" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando tickets...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/tickets.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allTickets = data.tickets || [];
    filteredTickets = [...allTickets];
    currentPage = 1;
    
    renderTickets();
    updateStats();

  } catch (error) {
    console.error('Error loading tickets:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="9" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar tickets
        </td>
      </tr>
    `;
  }
}

function filterTickets() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const priorityFilter = document.getElementById('priorityFilter').value;
  const categoryFilter = document.getElementById('categoryFilter').value;

  filteredTickets = allTickets.filter(ticket => {
    const matchesSearch = !searchTerm || 
      ticket.ticket_number.toLowerCase().includes(searchTerm) ||
      (ticket.user_name && ticket.user_name.toLowerCase().includes(searchTerm)) ||
      ticket.subject.toLowerCase().includes(searchTerm);

    const matchesStatus = !statusFilter || ticket.status === statusFilter;
    const matchesPriority = !priorityFilter || ticket.priority === priorityFilter;
    const matchesCategory = !categoryFilter || ticket.category === categoryFilter;

    return matchesSearch && matchesStatus && matchesPriority && matchesCategory;
  });

  currentPage = 1;
  renderTickets();
}

function renderTickets() {
  const tbody = document.getElementById('ticketsTable');
  const startIndex = (currentPage - 1) * ticketsPerPage;
  const endIndex = startIndex + ticketsPerPage;
  const pageTickets = filteredTickets.slice(startIndex, endIndex);

  if (pageTickets.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No se encontraron tickets
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = pageTickets.map(ticket => `
    <tr>
      <td>
        <div class="text-white fw-bold">${ticket.ticket_number}</div>
        <small class="text-light opacity-75">
          <i class="bi bi-chat-dots me-1"></i>${ticket.response_count || 0} respuestas
        </small>
      </td>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
            <i class="bi bi-person text-white"></i>
          </div>
          <div>
            <div class="text-white">${ticket.user_name}</div>
            <small class="text-light opacity-75">${ticket.clinic_name || 'Sin consultorio'}</small>
          </div>
        </div>
      </td>
      <td>
        <div class="text-white">${ticket.subject}</div>
        <small class="text-light opacity-75">${truncateText(ticket.description, 50)}</small>
      </td>
      <td>
        <span class="badge ${getCategoryBadgeClass(ticket.category)}">
          <i class="bi bi-${getCategoryIcon(ticket.category)} me-1"></i>
          ${getCategoryName(ticket.category)}
        </span>
      </td>
      <td>
        <span class="badge ${getPriorityBadgeClass(ticket.priority)}">
          <i class="bi bi-${getPriorityIcon(ticket.priority)} me-1"></i>
          ${getPriorityName(ticket.priority)}
        </span>
      </td>
      <td>
        <span class="badge ${getStatusBadgeClass(ticket.status)}">
          <i class="bi bi-${getStatusIcon(ticket.status)} me-1"></i>
          ${getStatusName(ticket.status)}
        </span>
      </td>
      <td>
        ${ticket.assigned_to_name ? 
          `<span class="text-light">${ticket.assigned_to_name}</span>` : 
          `<span class="text-light opacity-50">Sin asignar</span>`
        }
      </td>
      <td class="text-light opacity-75">${formatDateTime(ticket.created_at)}</td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li>
              <a class="dropdown-item" href="#" onclick="viewTicket('${ticket.id}')">
                <i class="bi bi-eye me-2"></i>Ver detalles
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="assignTicket('${ticket.id}', '${ticket.priority}', '${ticket.assigned_to || ''}')">
                <i class="bi bi-person-check me-2"></i>Asignar
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-success" href="#" onclick="updateTicketStatus('${ticket.id}', 'resolved')">
                <i class="bi bi-check-circle me-2"></i>Marcar resuelto
              </a>
            </li>
            <li>
              <a class="dropdown-item text-warning" href="#" onclick="updateTicketStatus('${ticket.id}', 'in_progress')">
                <i class="bi bi-play-circle me-2"></i>En progreso
              </a>
            </li>
            <li>
              <a class="dropdown-item text-secondary" href="#" onclick="updateTicketStatus('${ticket.id}', 'closed')">
                <i class="bi bi-x-circle me-2"></i>Cerrar
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deleteTicket('${ticket.id}', '${ticket.ticket_number}')">
                <i class="bi bi-trash me-2"></i>Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  `).join('');
}

function updateStats() {
  // Update stats based on current data
  const total = allTickets.length;
  const open = allTickets.filter(t => ['open', 'in_progress'].includes(t.status)).length;
  const resolved = allTickets.filter(t => t.status === 'resolved').length;
  const urgent = allTickets.filter(t => t.priority === 'urgent' && !['resolved', 'closed'].includes(t.status)).length;

  // Update DOM if elements exist
  const statsCards = document.querySelectorAll('.feature-icon');
  if (statsCards.length >= 4) {
    statsCards[0].closest('.glass-card').querySelector('h3').textContent = total;
    statsCards[1].closest('.glass-card').querySelector('h3').textContent = open;
    statsCards[2].closest('.glass-card').querySelector('h3').textContent = resolved;
    statsCards[3].closest('.glass-card').querySelector('h3').textContent = urgent;
  }
}

async function handleAssignTicket(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Asignando...';
  
  try {
    const formData = new FormData(e.target);
    const ticketData = Object.fromEntries(formData.entries());
    const ticketId = ticketData.ticketId;
    delete ticketData.ticketId;
    
    const response = await fetch(`api/tickets.php?id=${ticketId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(ticketData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload tickets
    const modal = bootstrap.Modal.getInstance(document.getElementById('assignTicketModal'));
    modal.hide();
    e.target.reset();
    loadTickets();
    
  } catch (error) {
    console.error('Error assigning ticket:', error);
    showAlert('danger', 'Error al asignar ticket');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions
window.loadTickets = loadTickets;

window.viewTicket = async function(ticketId) {
  try {
    const response = await fetch(`api/tickets.php?id=${ticketId}`);
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    const ticket = data.ticket;
    const responses = data.responses || [];
    
    const content = `
      <div class="row g-4">
        <!-- Ticket Info -->
        <div class="col-md-8">
          <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h4 class="text-white mb-1">${ticket.subject}</h4>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <span class="badge ${getStatusBadgeClass(ticket.status)}">
                    <i class="bi bi-${getStatusIcon(ticket.status)} me-1"></i>
                    ${getStatusName(ticket.status)}
                  </span>
                  <span class="badge ${getPriorityBadgeClass(ticket.priority)}">
                    <i class="bi bi-${getPriorityIcon(ticket.priority)} me-1"></i>
                    ${getPriorityName(ticket.priority)}
                  </span>
                  <span class="badge ${getCategoryBadgeClass(ticket.category)}">
                    <i class="bi bi-${getCategoryIcon(ticket.category)} me-1"></i>
                    ${getCategoryName(ticket.category)}
                  </span>
                </div>
              </div>
              <div class="text-end">
                <div class="text-white fw-bold">${ticket.ticket_number}</div>
                <small class="text-light opacity-75">${formatDateTime(ticket.created_at)}</small>
              </div>
            </div>
            
            <div class="glass-card p-3 mb-4">
              <h6 class="text-white mb-2">Descripción original:</h6>
              <p class="text-light opacity-85 mb-0">${ticket.description}</p>
            </div>
            
            <!-- Responses -->
            <div class="mb-4">
              <h6 class="text-white mb-3">
                <i class="bi bi-chat-dots me-2"></i>Conversación (${responses.length} respuestas)
              </h6>
              <div id="ticketResponses" style="max-height: 400px; overflow-y: auto;">
                ${responses.map(response => `
                  <div class="glass-card p-3 mb-3 ${response.is_internal ? 'border-warning' : ''}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="d-flex align-items-center">
                        <div class="bg-${response.role === 'admin' ? 'danger' : 'primary'} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                          <i class="bi bi-${response.role === 'admin' ? 'shield-check' : 'person'} text-white"></i>
                        </div>
                        <div>
                          <div class="text-white fw-medium">${response.user_name}</div>
                          <small class="text-light opacity-75">
                            ${response.role === 'admin' ? 'Administrador' : 'Usuario'}
                            ${response.is_internal ? ' • Nota interna' : ''}
                          </small>
                        </div>
                      </div>
                      <small class="text-light opacity-75">${formatDateTime(response.created_at)}</small>
                    </div>
                    <p class="text-light opacity-85 mb-0">${response.message}</p>
                  </div>
                `).join('')}
              </div>
            </div>
            
            <!-- Add Response -->
            <div class="glass-card p-3">
              <h6 class="text-white mb-3">Agregar respuesta</h6>
              <form id="addResponseForm">
                <input type="hidden" name="ticketId" value="${ticket.id}">
                <div class="mb-3">
                  <textarea name="message" class="form-control glass-input" rows="3" placeholder="Escribe tu respuesta..." required></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_internal" id="isInternal">
                    <label class="form-check-label text-light" for="isInternal">
                      Nota interna (solo visible para admins)
                    </label>
                  </div>
                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-2"></i>Enviar respuesta
                    </button>
                    <button type="button" class="btn btn-success" onclick="addResponseAndResolve('${ticket.id}')">
                      <i class="bi bi-check-circle me-2"></i>Responder y resolver
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        
        <!-- Ticket Sidebar -->
        <div class="col-md-4">
          <div class="glass-card p-4">
            <h6 class="text-white mb-3">
              <i class="bi bi-info-circle me-2"></i>Información del ticket
            </h6>
            
            <!-- User Info -->
            <div class="mb-4">
              <div class="d-flex align-items-center mb-3">
                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                  <i class="bi bi-person text-white"></i>
                </div>
                <div>
                  <div class="text-white fw-bold">${ticket.user_name}</div>
                  <small class="text-light opacity-75">${ticket.email}</small>
                </div>
              </div>
              <div class="glass-card p-3">
                <div class="mb-2">
                  <strong class="text-light">Consultorio:</strong>
                  <div class="text-white">${ticket.clinic_name || 'No especificado'}</div>
                </div>
              </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mb-4">
              <h6 class="text-white mb-3">Acciones rápidas</h6>
              <div class="d-grid gap-2">
                <button class="btn btn-outline-primary" onclick="assignTicket('${ticket.id}', '${ticket.priority}', '${ticket.assigned_to || ''}')">
                  <i class="bi bi-person-check me-2"></i>Asignar ticket
                </button>
                <button class="btn btn-outline-warning" onclick="updateTicketStatus('${ticket.id}', 'in_progress')">
                  <i class="bi bi-play-circle me-2"></i>Marcar en progreso
                </button>
                <button class="btn btn-outline-success" onclick="updateTicketStatus('${ticket.id}', 'resolved')">
                  <i class="bi bi-check-circle me-2"></i>Marcar resuelto
                </button>
              </div>
            </div>
            
            <!-- Ticket Timeline -->
            <div>
              <h6 class="text-white mb-3">Timeline</h6>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-marker bg-primary"></div>
                  <div class="timeline-content">
                    <small class="text-light opacity-75">Ticket creado</small>
                    <div class="text-white">${formatDateTime(ticket.created_at)}</div>
                  </div>
                </div>
                ${ticket.resolved_at ? `
                <div class="timeline-item">
                  <div class="timeline-marker bg-success"></div>
                  <div class="timeline-content">
                    <small class="text-light opacity-75">Resuelto</small>
                    <div class="text-white">${formatDateTime(ticket.resolved_at)}</div>
                  </div>
                </div>
                ` : ''}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    document.getElementById('ticketDetailsContent').innerHTML = content;
    
    // Set up response form handler
    const addResponseForm = document.getElementById('addResponseForm');
    if (addResponseForm) {
      addResponseForm.addEventListener('submit', handleAddResponse);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('viewTicketModal'));
    modal.show();
    
  } catch (error) {
    console.error('Error loading ticket details:', error);
    showAlert('danger', 'Error al cargar detalles del ticket');
  }
}

window.assignTicket = function(ticketId, currentPriority, currentAssignedTo) {
  document.getElementById('assignTicketId').value = ticketId;
  
  // Set current values
  const prioritySelect = document.querySelector('#assignTicketForm select[name="priority"]');
  const assignedSelect = document.querySelector('#assignTicketForm select[name="assigned_to"]');
  
  if (prioritySelect) prioritySelect.value = currentPriority;
  if (assignedSelect) assignedSelect.value = currentAssignedTo;
  
  const modal = new bootstrap.Modal(document.getElementById('assignTicketModal'));
  modal.show();
}

window.updateTicketStatus = async function(ticketId, newStatus) {
  try {
    const response = await fetch(`api/tickets.php?id=${ticketId}`, {
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
      loadTickets();
    }
    
  } catch (error) {
    console.error('Error updating ticket status:', error);
    showAlert('danger', 'Error al actualizar estado del ticket');
  }
}

window.deleteTicket = function(ticketId, ticketNumber) {
  if (confirm(`¿Estás seguro de que quieres eliminar el ticket ${ticketNumber}?\n\nEsta acción no se puede deshacer.`)) {
    fetch(`api/tickets.php?id=${ticketId}`, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', data.message);
        loadTickets();
      }
    })
    .catch(error => {
      console.error('Error deleting ticket:', error);
      showAlert('danger', 'Error al eliminar ticket');
    });
  }
}

window.clearFilters = function() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('priorityFilter').value = '';
  document.getElementById('categoryFilter').value = '';
  filterTickets();
}

window.exportTickets = function() {
  // Create CSV content
  const headers = ['Número', 'Usuario', 'Email', 'Asunto', 'Categoría', 'Prioridad', 'Estado', 'Asignado', 'Fecha'];
  const csvContent = [
    headers.join(','),
    ...filteredTickets.map(ticket => [
      ticket.ticket_number,
      ticket.user_name || '',
      ticket.email || '',
      ticket.subject,
      getCategoryName(ticket.category),
      getPriorityName(ticket.priority),
      getStatusName(ticket.status),
      ticket.assigned_to_name || 'Sin asignar',
      formatDate(ticket.created_at)
    ].map(field => `"${field}"`).join(','))
  ].join('\n');

  // Download CSV
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `tickets_soporte_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  showAlert('success', 'Archivo CSV descargado exitosamente');
}

async function handleAddResponse(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
  
  try {
    const formData = new FormData(e.target);
    const responseData = {
      message: formData.get('message'),
      is_internal: formData.get('is_internal') === 'on'
    };
    const ticketId = formData.get('ticketId');
    
    const response = await fetch(`api/tickets.php?id=${ticketId}&action=add_response`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(responseData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Reload ticket details
    viewTicket(ticketId);
    
  } catch (error) {
    console.error('Error adding response:', error);
    showAlert('danger', 'Error al agregar respuesta');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

window.addResponseAndResolve = async function(ticketId) {
  const form = document.getElementById('addResponseForm');
  const message = form.querySelector('textarea[name="message"]').value;
  
  if (!message.trim()) {
    showAlert('warning', 'Debes escribir una respuesta antes de resolver el ticket');
    return;
  }
  
  try {
    const response = await fetch(`api/tickets.php?id=${ticketId}&action=add_response`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: message,
        is_internal: false,
        new_status: 'resolved'
      })
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
    } else {
      showAlert('success', 'Respuesta enviada y ticket marcado como resuelto');
      
      // Close modal and reload tickets
      const modal = bootstrap.Modal.getInstance(document.getElementById('viewTicketModal'));
      modal.hide();
      loadTickets();
    }
    
  } catch (error) {
    console.error('Error resolving ticket:', error);
    showAlert('danger', 'Error al resolver ticket');
  }
}

// Helper functions
function getCategoryBadgeClass(category) {
  switch(category) {
    case 'technical': return 'bg-info';
    case 'billing': return 'bg-warning text-dark';
    case 'feature': return 'bg-primary';
    case 'bug': return 'bg-danger';
    case 'general': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getCategoryName(category) {
  switch(category) {
    case 'technical': return 'Técnico';
    case 'billing': return 'Facturación';
    case 'feature': return 'Funcionalidad';
    case 'bug': return 'Error';
    case 'general': return 'General';
    default: return 'General';
  }
}

function getCategoryIcon(category) {
  switch(category) {
    case 'technical': return 'gear';
    case 'billing': return 'credit-card';
    case 'feature': return 'lightbulb';
    case 'bug': return 'bug';
    case 'general': return 'chat-dots';
    default: return 'chat-dots';
  }
}

function getPriorityBadgeClass(priority) {
  switch(priority) {
    case 'urgent': return 'bg-danger';
    case 'high': return 'bg-warning text-dark';
    case 'medium': return 'bg-info';
    case 'low': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getPriorityName(priority) {
  switch(priority) {
    case 'urgent': return 'Urgente';
    case 'high': return 'Alta';
    case 'medium': return 'Media';
    case 'low': return 'Baja';
    default: return 'Media';
  }
}

function getPriorityIcon(priority) {
  switch(priority) {
    case 'urgent': return 'exclamation-triangle-fill';
    case 'high': return 'exclamation-triangle';
    case 'medium': return 'dash-circle';
    case 'low': return 'circle';
    default: return 'dash-circle';
  }
}

function getStatusBadgeClass(status) {
  switch(status) {
    case 'open': return 'bg-primary';
    case 'in_progress': return 'bg-warning text-dark';
    case 'waiting_user': return 'bg-info';
    case 'resolved': return 'bg-success';
    case 'closed': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'open': return 'Abierto';
    case 'in_progress': return 'En progreso';
    case 'waiting_user': return 'Esperando usuario';
    case 'resolved': return 'Resuelto';
    case 'closed': return 'Cerrado';
    default: return 'Abierto';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'open': return 'circle';
    case 'in_progress': return 'play-circle';
    case 'waiting_user': return 'clock-history';
    case 'resolved': return 'check-circle';
    case 'closed': return 'x-circle';
    default: return 'circle';
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
}

function formatDateTime(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleString('es-AR');
}

function truncateText(text, maxLength) {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
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