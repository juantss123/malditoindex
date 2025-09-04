// Admin transfer proofs management functionality

let allProofs = [];
let filteredProofs = [];

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

  // Load transfer proofs on page load
  loadTransferProofs();

  // Filter functionality
  const statusFilter = document.getElementById('statusFilter');
  if (statusFilter) {
    statusFilter.addEventListener('change', filterProofs);
  }

  // Form handler
  const processTransferForm = document.getElementById('processTransferForm');
  if (processTransferForm) {
    processTransferForm.addEventListener('submit', handleProcessTransfer);
  }
});

async function loadTransferProofs() {
  const tbody = document.getElementById('transferProofsTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando comprobantes...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/transfer-proofs.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allProofs = data.proofs || [];
    filteredProofs = [...allProofs];
    
    renderTransferProofs();
    updateStats();

  } catch (error) {
    console.error('Error loading transfer proofs:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar comprobantes
        </td>
      </tr>
    `;
  }
}

function filterProofs() {
  const statusFilter = document.getElementById('statusFilter').value;

  filteredProofs = allProofs.filter(proof => {
    return !statusFilter || proof.status === statusFilter;
  });

  renderTransferProofs();
}

function renderTransferProofs() {
  const tbody = document.getElementById('transferProofsTable');

  if (filteredProofs.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No hay comprobantes de transferencia
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = filteredProofs.map(proof => `
    <tr>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
            <i class="bi bi-person text-white"></i>
          </div>
          <div>
            <div class="text-white">${proof.user_name}</div>
            <small class="text-light opacity-75">${proof.email}</small>
          </div>
        </div>
      </td>
      <td>
        <span class="badge ${getPlanBadgeClass(proof.plan_type)}">
          <i class="bi bi-star me-1"></i>${getPlanName(proof.plan_type)}
        </span>
      </td>
      <td class="text-white fw-bold">$${parseFloat(proof.amount).toFixed(2)}</td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-${getFileIcon(proof.file_type)} text-primary"></i>
          <span class="text-light small">${proof.file_name}</span>
        </div>
        <small class="text-light opacity-75">${formatFileSize(proof.file_size)}</small>
      </td>
      <td class="text-light opacity-75">${formatDateTime(proof.created_at)}</td>
      <td>
        <span class="badge ${getStatusBadgeClass(proof.status)}">
          <i class="bi bi-${getStatusIcon(proof.status)} me-1"></i>
          ${getStatusName(proof.status)}
        </span>
      </td>
      <td>
        ${proof.status === 'pending' ? `
          <button class="btn btn-sm btn-success me-2" onclick="processTransferProof('${proof.id}', '${proof.user_name}', '${proof.email}', '${proof.plan_type}', '${proof.amount}', '${proof.file_path}', '${proof.file_name}')">
            <i class="bi bi-check-lg"></i>
          </button>
        ` : `
          <span class="text-light opacity-75 small">
            ${proof.processed_at ? formatDate(proof.processed_at) : 'Procesado'}
            ${proof.processed_by_name ? '<br>por ' + proof.processed_by_name : ''}
          </span>
        `}
      </td>
    </tr>
  `).join('');
}

function updateStats() {
  const pending = allProofs.filter(p => p.status === 'pending').length;
  const approved = allProofs.filter(p => p.status === 'approved').length;
  const rejected = allProofs.filter(p => p.status === 'rejected').length;
  const totalAmount = allProofs
    .filter(p => p.status === 'approved')
    .reduce((sum, p) => sum + parseFloat(p.amount), 0);

  document.getElementById('pendingCount').textContent = pending;
  document.getElementById('approvedCount').textContent = approved;
  document.getElementById('rejectedCount').textContent = rejected;
  document.getElementById('totalAmount').textContent = `$${totalAmount.toFixed(2)}`;
}

async function handleProcessTransfer(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
  
  try {
    const formData = new FormData(e.target);
    const transferData = Object.fromEntries(formData.entries());
    const proofId = transferData.proofId;
    delete transferData.proofId;
    
    const response = await fetch(`api/transfer-proofs.php?id=${proofId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(transferData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload proofs
    const modal = bootstrap.Modal.getInstance(document.getElementById('processTransferModal'));
    modal.hide();
    e.target.reset();
    loadTransferProofs();
    
  } catch (error) {
    console.error('Error processing transfer proof:', error);
    showAlert('danger', 'Error al procesar comprobante');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions
window.loadTransferProofs = loadTransferProofs;

window.processTransferProof = function(proofId, userName, userEmail, planType, amount, filePath, fileName) {
  // Fill modal with proof data
  document.getElementById('proofId').value = proofId;
  document.getElementById('modalUserName').textContent = userName;
  document.getElementById('modalUserEmail').textContent = userEmail;
  document.getElementById('modalPlanType').textContent = getPlanName(planType);
  document.getElementById('modalAmount').textContent = `$${parseFloat(amount).toFixed(2)}`;
  
  // Set up file preview buttons
  const viewFileBtn = document.getElementById('viewFileBtn');
  const downloadFileBtn = document.getElementById('downloadFileBtn');
  
  viewFileBtn.onclick = () => previewFile(filePath, fileName);
  downloadFileBtn.onclick = () => downloadFile(filePath, fileName);
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('processTransferModal'));
  modal.show();
}

function previewFile(filePath, fileName) {
  const fileExtension = fileName.split('.').pop().toLowerCase();
  const previewContent = document.getElementById('filePreviewContent');
  
  if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
    previewContent.innerHTML = `
      <img src="${filePath}" class="img-fluid rounded" alt="Comprobante de transferencia" style="max-height: 70vh;">
    `;
  } else if (fileExtension === 'pdf') {
    previewContent.innerHTML = `
      <embed src="${filePath}" type="application/pdf" width="100%" height="600px" class="rounded">
    `;
  } else {
    previewContent.innerHTML = `
      <div class="text-center text-light opacity-75 py-5">
        <i class="bi bi-file-earmark fs-1 mb-3"></i>
        <p>Vista previa no disponible para este tipo de archivo</p>
        <button class="btn btn-primary" onclick="downloadFile('${filePath}', '${fileName}')">
          <i class="bi bi-download me-2"></i>Descargar archivo
        </button>
      </div>
    `;
  }
  
  const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
  modal.show();
}

function downloadFile(filePath, fileName) {
  const link = document.createElement('a');
  link.href = filePath;
  link.download = fileName;
  link.click();
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
    case 'pending': return 'bg-warning text-dark';
    case 'approved': return 'bg-success';
    case 'rejected': return 'bg-danger';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'pending': return 'Pendiente';
    case 'approved': return 'Aprobado';
    case 'rejected': return 'Rechazado';
    default: return 'Sin estado';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'pending': return 'clock-history';
    case 'approved': return 'check-circle';
    case 'rejected': return 'x-circle';
    default: return 'question-circle';
  }
}

function getFileIcon(fileType) {
  if (fileType.includes('image')) return 'image';
  if (fileType.includes('pdf')) return 'file-earmark-pdf';
  return 'file-earmark';
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleDateString('es-AR');
}

function formatDateTime(dateString) {
  if (!dateString) return 'Sin fecha';
  return new Date(dateString).toLocaleString('es-AR');
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