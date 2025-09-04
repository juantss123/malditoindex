// Admin invoices management functionality

let allInvoices = [];
let filteredInvoices = [];
let currentPage = 1;
const invoicesPerPage = 10;

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

  // Load invoices on page load
  loadInvoices();

  // Search and filter functionality
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const planFilter = document.getElementById('planFilter');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterInvoices, 300));
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterInvoices);
  }
  if (planFilter) {
    planFilter.addEventListener('change', filterInvoices);
  }

  // Form handlers
  const createInvoiceForm = document.getElementById('createInvoiceForm');
  if (createInvoiceForm) {
    createInvoiceForm.addEventListener('submit', handleCreateInvoice);
    
    // Auto-fill invoice number
    generateInvoiceNumber();
    
    // Set default date to today
    const dateInput = createInvoiceForm.querySelector('input[name="invoice_date"]');
    if (dateInput) {
      dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // User selection handler
    const userSelect = document.getElementById('userSelect');
    const planSelect = document.getElementById('planSelect');
    const baseAmountInput = document.getElementById('baseAmount');
    
    if (userSelect && planSelect) {
      userSelect.addEventListener('change', (e) => {
        const selectedOption = e.target.selectedOptions[0];
        if (selectedOption && selectedOption.dataset.plan) {
          planSelect.value = selectedOption.dataset.plan;
          
          // Auto-fill amount based on plan
          const planPrices = {
            'start': 14999,
            'clinic': 24999,
            'enterprise': 49999
          };
          
          if (baseAmountInput && planPrices[selectedOption.dataset.plan]) {
            baseAmountInput.value = planPrices[selectedOption.dataset.plan];
            calculateTotals();
          }
        }
      });
    }
    
    // Amount calculation
    if (baseAmountInput) {
      baseAmountInput.addEventListener('input', calculateTotals);
    }
  }
});

async function loadInvoices() {
  const tbody = document.getElementById('invoicesTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando facturas...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/invoices.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allInvoices = data.invoices || [];
    filteredInvoices = [...allInvoices];
    currentPage = 1;
    
    renderInvoices();

  } catch (error) {
    console.error('Error loading invoices:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar facturas
        </td>
      </tr>
    `;
  }
}

function filterInvoices() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const planFilter = document.getElementById('planFilter').value;

  filteredInvoices = allInvoices.filter(invoice => {
    const matchesSearch = !searchTerm || 
      invoice.invoice_number.toLowerCase().includes(searchTerm) ||
      (invoice.user_name && invoice.user_name.toLowerCase().includes(searchTerm)) ||
      (invoice.clinic_name && invoice.clinic_name.toLowerCase().includes(searchTerm));

    const matchesStatus = !statusFilter || invoice.status === statusFilter;
    const matchesPlan = !planFilter || invoice.plan_type === planFilter;

    return matchesSearch && matchesStatus && matchesPlan;
  });

  currentPage = 1;
  renderInvoices();
}

function renderInvoices() {
  const tbody = document.getElementById('invoicesTable');
  const startIndex = (currentPage - 1) * invoicesPerPage;
  const endIndex = startIndex + invoicesPerPage;
  const pageInvoices = filteredInvoices.slice(startIndex, endIndex);

  if (pageInvoices.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No se encontraron facturas
        </td>
      </tr>
    `;
    updatePagination();
    updateCounts();
    return;
  }

  tbody.innerHTML = pageInvoices.map(invoice => `
    <tr>
      <td>
        <div class="text-white fw-bold">${invoice.invoice_number}</div>
        <small class="text-light opacity-75">${formatDate(invoice.invoice_date)}</small>
      </td>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
            <i class="bi bi-person text-white"></i>
          </div>
          <div>
            <div class="text-white">${invoice.user_name || 'Usuario eliminado'}</div>
            <small class="text-light opacity-75">${invoice.clinic_name || 'Sin consultorio'}</small>
          </div>
        </div>
      </td>
      <td>
        <span class="badge ${getPlanBadgeClass(invoice.plan_type)}">
          <i class="bi bi-star me-1"></i>${getPlanName(invoice.plan_type)}
        </span>
      </td>
      <td>
        <div class="text-white fw-bold">$${parseFloat(invoice.total_amount).toLocaleString('es-AR')}</div>
        <small class="text-light opacity-75">Base: $${parseFloat(invoice.amount).toLocaleString('es-AR')}</small>
      </td>
      <td class="text-light opacity-75">${formatDate(invoice.invoice_date)}</td>
      <td>
        <span class="badge ${getStatusBadgeClass(invoice.status)}">
          <i class="bi bi-${getStatusIcon(invoice.status)} me-1"></i>
          ${getStatusName(invoice.status)}
        </span>
      </td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li>
              <a class="dropdown-item" href="#" onclick="viewInvoice('${invoice.id}')">
                <i class="bi bi-eye me-2"></i>Ver factura
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="downloadInvoice('${invoice.id}')">
                <i class="bi bi-download me-2"></i>Descargar PDF
              </a>
            </li>
            ${invoice.status === 'draft' ? `
            <li>
              <a class="dropdown-item" href="#" onclick="sendInvoice('${invoice.id}')">
                <i class="bi bi-envelope me-2"></i>Enviar por email
              </a>
            </li>
            ` : ''}
            ${invoice.status !== 'paid' ? `
            <li>
              <a class="dropdown-item text-success" href="#" onclick="markAsPaid('${invoice.id}')">
                <i class="bi bi-check-circle me-2"></i>Marcar como pagada
              </a>
            </li>
            ` : ''}
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deleteInvoice('${invoice.id}', '${invoice.invoice_number}')">
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

async function handleCreateInvoice(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando factura...';
  
  try {
    const formData = new FormData(e.target);
    const invoiceData = Object.fromEntries(formData.entries());
    
    const response = await fetch('api/invoices.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(invoiceData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload invoices
    const modal = bootstrap.Modal.getInstance(document.getElementById('createInvoiceModal'));
    modal.hide();
    e.target.reset();
    generateInvoiceNumber(); // Generate new number for next invoice
    loadInvoices();
    
  } catch (error) {
    console.error('Error creating invoice:', error);
    showAlert('danger', 'Error al crear factura');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

function generateInvoiceNumber() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
  
  const invoiceNumber = `INV-${year}${month}${day}-${random}`;
  
  const invoiceNumberInput = document.getElementById('invoiceNumber');
  if (invoiceNumberInput) {
    invoiceNumberInput.value = invoiceNumber;
  }
}

function calculateTotals() {
  const baseAmountInput = document.getElementById('baseAmount');
  const taxAmountInput = document.getElementById('taxAmount');
  const totalAmountInput = document.getElementById('totalAmount');
  
  if (baseAmountInput && taxAmountInput && totalAmountInput) {
    const baseAmount = parseFloat(baseAmountInput.value) || 0;
    const taxAmount = baseAmount * 0.21; // 21% IVA
    const totalAmount = baseAmount + taxAmount;
    
    taxAmountInput.value = taxAmount.toFixed(2);
    totalAmountInput.value = totalAmount.toFixed(2);
  }
}

function updatePagination() {
  const totalPages = Math.ceil(filteredInvoices.length / invoicesPerPage);
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
  const startIndex = (currentPage - 1) * invoicesPerPage;
  const endIndex = Math.min(startIndex + invoicesPerPage, filteredInvoices.length);
  
  document.getElementById('showingCount').textContent = filteredInvoices.length === 0 ? '0' : `${startIndex + 1}-${endIndex}`;
  document.getElementById('totalCount').textContent = filteredInvoices.length;
}

// Global functions
window.changePage = function(page) {
  const totalPages = Math.ceil(filteredInvoices.length / invoicesPerPage);
  if (page >= 1 && page <= totalPages) {
    currentPage = page;
    renderInvoices();
  }
}

window.clearFilters = function() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('planFilter').value = '';
  filterInvoices();
}

window.exportInvoices = function() {
  // Create CSV content
  const headers = ['Número', 'Usuario', 'Email', 'Plan', 'Monto Base', 'IVA', 'Total', 'Fecha', 'Estado'];
  const csvContent = [
    headers.join(','),
    ...filteredInvoices.map(invoice => [
      invoice.invoice_number,
      invoice.user_name || '',
      invoice.email || '',
      getPlanName(invoice.plan_type),
      parseFloat(invoice.amount).toFixed(2),
      parseFloat(invoice.tax_amount).toFixed(2),
      parseFloat(invoice.total_amount).toFixed(2),
      formatDate(invoice.invoice_date),
      getStatusName(invoice.status)
    ].map(field => `"${field}"`).join(','))
  ].join('\n');

  // Download CSV
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `facturas_dentexapro_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  showAlert('success', 'Archivo CSV descargado exitosamente');
}

window.viewInvoice = function(invoiceId) {
  const invoice = allInvoices.find(i => i.id === invoiceId);
  if (!invoice) return;

  const content = `
    <div class="invoice-preview bg-white text-dark p-5 rounded">
      <!-- Invoice Header -->
      <div class="row mb-4">
        <div class="col-md-6">
          <h2 class="text-primary mb-1">DentexaPro</h2>
          <p class="text-muted mb-0">Gestión para dentistas</p>
        </div>
        <div class="col-md-6 text-md-end">
          <h3 class="text-dark mb-1">FACTURA</h3>
          <p class="text-muted mb-0">${invoice.invoice_number}</p>
        </div>
      </div>

      <!-- Invoice Details -->
      <div class="row mb-4">
        <div class="col-md-6">
          <h5 class="text-dark mb-2">Facturar a:</h5>
          <div class="text-dark">
            <strong>${invoice.user_name}</strong><br>
            ${invoice.clinic_name}<br>
            ${invoice.email}
          </div>
        </div>
        <div class="col-md-6 text-md-end">
          <div class="mb-2">
            <strong>Fecha de factura:</strong> ${formatDate(invoice.invoice_date)}
          </div>
          <div class="mb-2">
            <strong>Fecha de vencimiento:</strong> ${formatDate(invoice.due_date)}
          </div>
          <div>
            <strong>Estado:</strong> 
            <span class="badge ${getStatusBadgeClass(invoice.status)}">${getStatusName(invoice.status)}</span>
          </div>
        </div>
      </div>

      <!-- Invoice Items -->
      <div class="table-responsive mb-4">
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>Descripción</th>
              <th>Plan</th>
              <th>Cantidad</th>
              <th class="text-end">Precio unitario</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Suscripción mensual DentexaPro</td>
              <td>
                <span class="badge ${getPlanBadgeClass(invoice.plan_type)}">${getPlanName(invoice.plan_type)}</span>
              </td>
              <td>1</td>
              <td class="text-end">$${parseFloat(invoice.amount).toLocaleString('es-AR')}</td>
              <td class="text-end">$${parseFloat(invoice.amount).toLocaleString('es-AR')}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Invoice Totals -->
      <div class="row">
        <div class="col-md-6">
          ${invoice.notes ? `
          <div>
            <h6 class="text-dark">Notas:</h6>
            <p class="text-muted">${invoice.notes}</p>
          </div>
          ` : ''}
        </div>
        <div class="col-md-6">
          <div class="table-responsive">
            <table class="table table-sm">
              <tr>
                <td class="text-end"><strong>Subtotal:</strong></td>
                <td class="text-end">$${parseFloat(invoice.amount).toLocaleString('es-AR')}</td>
              </tr>
              <tr>
                <td class="text-end"><strong>IVA (21%):</strong></td>
                <td class="text-end">$${parseFloat(invoice.tax_amount).toLocaleString('es-AR')}</td>
              </tr>
              <tr class="table-primary">
                <td class="text-end"><strong>TOTAL:</strong></td>
                <td class="text-end"><strong>$${parseFloat(invoice.total_amount).toLocaleString('es-AR')}</strong></td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-5 pt-4 border-top">
        <p class="text-muted small mb-0">
          DentexaPro - Gestión para dentistas por suscripción<br>
          Email: facturacion@dentexapro.com | Web: www.dentexapro.com
        </p>
      </div>
    </div>
  `;

  document.getElementById('invoicePreviewContent').innerHTML = content;
  
  // Set up modal buttons
  document.getElementById('downloadPdfBtn').onclick = () => downloadInvoice(invoiceId);
  document.getElementById('sendEmailBtn').onclick = () => sendInvoice(invoiceId);
  
  const modal = new bootstrap.Modal(document.getElementById('viewInvoiceModal'));
  modal.show();
}

window.downloadInvoice = function(invoiceId) {
  // In a real implementation, this would generate a PDF
  showAlert('info', 'Generando PDF... (Funcionalidad en desarrollo)');
}

window.sendInvoice = function(invoiceId) {
  if (confirm('¿Enviar esta factura por email al usuario?')) {
    // Update status to sent
    fetch(`api/invoices.php?id=${invoiceId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: 'sent' })
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', 'Factura enviada exitosamente');
        loadInvoices();
      }
    })
    .catch(error => {
      console.error('Error sending invoice:', error);
      showAlert('danger', 'Error al enviar factura');
    });
  }
}

window.markAsPaid = function(invoiceId) {
  if (confirm('¿Marcar esta factura como pagada?')) {
    fetch(`api/invoices.php?id=${invoiceId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ 
        status: 'paid',
        payment_date: new Date().toISOString().split('T')[0]
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', 'Factura marcada como pagada');
        loadInvoices();
      }
    })
    .catch(error => {
      console.error('Error updating invoice:', error);
      showAlert('danger', 'Error al actualizar factura');
    });
  }
}

window.deleteInvoice = function(invoiceId, invoiceNumber) {
  if (confirm(`¿Estás seguro de que quieres eliminar la factura ${invoiceNumber}?\n\nEsta acción no se puede deshacer.`)) {
    fetch(`api/invoices.php?id=${invoiceId}`, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', data.message);
        loadInvoices();
      }
    })
    .catch(error => {
      console.error('Error deleting invoice:', error);
      showAlert('danger', 'Error al eliminar factura');
    });
  }
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
    case 'draft': return 'bg-secondary';
    case 'sent': return 'bg-info';
    case 'paid': return 'bg-success';
    case 'overdue': return 'bg-danger';
    case 'cancelled': return 'bg-dark';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'draft': return 'Borrador';
    case 'sent': return 'Enviada';
    case 'paid': return 'Pagada';
    case 'overdue': return 'Vencida';
    case 'cancelled': return 'Cancelada';
    default: return 'Sin estado';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'draft': return 'file-earmark';
    case 'sent': return 'envelope-check';
    case 'paid': return 'check-circle';
    case 'overdue': return 'exclamation-triangle';
    case 'cancelled': return 'x-circle';
    default: return 'question-circle';
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
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