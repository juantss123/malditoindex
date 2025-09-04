// Admin blog management functionality

let allPosts = [];
let filteredPosts = [];
let quillEditor;
let editQuillEditor;

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

  // Initialize Quill editors
  initializeEditors();

  // Load posts on page load
  loadPosts();

  // Search and filter functionality
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const categoryFilter = document.getElementById('categoryFilter');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterPosts, 300));
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', filterPosts);
  }
  if (categoryFilter) {
    categoryFilter.addEventListener('change', filterPosts);
  }

  // Form handlers
  const createPostForm = document.getElementById('createPostForm');
  if (createPostForm) {
    createPostForm.addEventListener('submit', handleCreatePost);
  }

  const editPostForm = document.getElementById('editPostForm');
  if (editPostForm) {
    editPostForm.addEventListener('submit', handleEditPost);
  }

  // Auto-generate slug from title
  const postTitle = document.getElementById('postTitle');
  const postSlug = document.getElementById('postSlug');
  
  if (postTitle && postSlug) {
    postTitle.addEventListener('input', (e) => {
      const slug = generateSlug(e.target.value);
      postSlug.value = slug;
    });
  }
});

function initializeEditors() {
  // Create post editor
  quillEditor = new Quill('#editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean']
      ]
    },
    placeholder: 'Escribe el contenido de tu artículo aquí...'
  });

  // Edit post editor
  editQuillEditor = new Quill('#editEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean']
      ]
    },
    placeholder: 'Escribe el contenido de tu artículo aquí...'
  });
}

async function loadPosts() {
  const tbody = document.getElementById('postsTable');
  
  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="6" class="text-center text-light opacity-75 py-4">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Cargando artículos...
      </td>
    </tr>
  `;

  try {
    const response = await fetch('api/blog.php');
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    allPosts = data.posts || [];
    filteredPosts = [...allPosts];
    
    renderPosts();

  } catch (error) {
    console.error('Error loading posts:', error);
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>Error al cargar artículos
        </td>
      </tr>
    `;
  }
}

function filterPosts() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const categoryFilter = document.getElementById('categoryFilter').value;

  filteredPosts = allPosts.filter(post => {
    const matchesSearch = !searchTerm || 
      post.title.toLowerCase().includes(searchTerm) ||
      post.excerpt.toLowerCase().includes(searchTerm);

    const matchesStatus = !statusFilter || post.status === statusFilter;
    const matchesCategory = !categoryFilter || post.category === categoryFilter;

    return matchesSearch && matchesStatus && matchesCategory;
  });

  renderPosts();
}

function renderPosts() {
  const tbody = document.getElementById('postsTable');

  if (filteredPosts.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-light opacity-75 py-4">
          <i class="bi bi-inbox me-2"></i>No se encontraron artículos
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = filteredPosts.map(post => `
    <tr>
      <td>
        <div class="d-flex align-items-center">
          <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
            <i class="bi bi-${getCategoryIcon(post.category)} text-white"></i>
          </div>
          <div>
            <div class="text-white fw-medium">${post.title}</div>
            <small class="text-light opacity-75">${truncateText(post.excerpt, 60)}</small>
          </div>
        </div>
      </td>
      <td>
        <span class="badge ${getCategoryBadgeClass(post.category)}">
          <i class="bi bi-${getCategoryIcon(post.category)} me-1"></i>
          ${getCategoryName(post.category)}
        </span>
      </td>
      <td>
        <span class="badge ${getStatusBadgeClass(post.status)}">
          <i class="bi bi-${getStatusIcon(post.status)} me-1"></i>
          ${getStatusName(post.status)}
        </span>
      </td>
      <td class="text-light">${post.author_name}</td>
      <td class="text-light opacity-75">
        <div>${formatDate(post.published_at || post.created_at)}</div>
        <small>${formatTime(post.published_at || post.created_at)}</small>
      </td>
      <td>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li>
              <a class="dropdown-item" href="blog-post.php?slug=${post.slug}" target="_blank">
                <i class="bi bi-eye me-2"></i>Ver artículo
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="editPost('${post.id}')">
                <i class="bi bi-pencil me-2"></i>Editar
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            ${post.status === 'draft' ? `
            <li>
              <a class="dropdown-item text-success" href="#" onclick="publishPost('${post.id}')">
                <i class="bi bi-check-circle me-2"></i>Publicar
              </a>
            </li>
            ` : ''}
            ${post.status === 'published' ? `
            <li>
              <a class="dropdown-item text-warning" href="#" onclick="unpublishPost('${post.id}')">
                <i class="bi bi-pause-circle me-2"></i>Despublicar
              </a>
            </li>
            ` : ''}
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="deletePost('${post.id}', '${post.title}')">
                <i class="bi bi-trash me-2"></i>Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    </tr>
  `).join('');
}

async function handleCreatePost(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando artículo...';
  
  try {
    const formData = new FormData(e.target);
    const postData = Object.fromEntries(formData.entries());
    
    // Get content from Quill editor
    postData.content = quillEditor.root.innerHTML;
    postData.status = 'published'; // Default to published when using main submit button
    
    const response = await fetch('api/blog.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(postData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload posts
    const modal = bootstrap.Modal.getInstance(document.getElementById('createPostModal'));
    modal.hide();
    e.target.reset();
    quillEditor.setContents([]);
    loadPosts();
    
  } catch (error) {
    console.error('Error creating post:', error);
    showAlert('danger', 'Error al crear artículo');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

async function handleEditPost(e) {
  e.preventDefault();
  
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  
  // Disable submit button
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando cambios...';
  
  try {
    const formData = new FormData(e.target);
    const postData = Object.fromEntries(formData.entries());
    const postId = postData.postId;
    delete postData.postId;
    
    // Get content from Quill editor
    postData.content = editQuillEditor.root.innerHTML;
    
    const response = await fetch(`api/blog.php?id=${postId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(postData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
      return;
    }
    
    showAlert('success', data.message);
    
    // Close modal and reload posts
    const modal = bootstrap.Modal.getInstance(document.getElementById('editPostModal'));
    modal.hide();
    loadPosts();
    
  } catch (error) {
    console.error('Error updating post:', error);
    showAlert('danger', 'Error al actualizar artículo');
  } finally {
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Global functions
window.loadPosts = loadPosts;

window.saveAsDraft = async function() {
  const form = document.getElementById('createPostForm');
  const formData = new FormData(form);
  const postData = Object.fromEntries(formData.entries());
  
  // Get content from Quill editor
  postData.content = quillEditor.root.innerHTML;
  postData.status = 'draft';
  
  try {
    const response = await fetch('api/blog.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(postData)
    });
    
    const data = await response.json();
    
    if (data.error) {
      showAlert('danger', data.error);
    } else {
      showAlert('success', 'Borrador guardado exitosamente');
      
      // Close modal and reload posts
      const modal = bootstrap.Modal.getInstance(document.getElementById('createPostModal'));
      modal.hide();
      form.reset();
      quillEditor.setContents([]);
      loadPosts();
    }
    
  } catch (error) {
    console.error('Error saving draft:', error);
    showAlert('danger', 'Error al guardar borrador');
  }
}

window.editPost = function(postId) {
  const post = allPosts.find(p => p.id === postId);
  if (!post) return;

  // Fill form with post data
  document.getElementById('editPostId').value = post.id;
  document.getElementById('editPostTitle').value = post.title;
  document.getElementById('editPostCategory').value = post.category;
  document.getElementById('editPostStatus').value = post.status;
  document.getElementById('editPostImage').value = post.featured_image || '';
  document.getElementById('editPostExcerpt').value = post.excerpt;
  document.getElementById('editPostTags').value = Array.isArray(post.tags) ? post.tags.join(', ') : '';
  
  // Set content in Quill editor
  editQuillEditor.root.innerHTML = post.content;
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('editPostModal'));
  modal.show();
}

window.publishPost = function(postId) {
  if (confirm('¿Publicar este artículo?')) {
    updatePostStatus(postId, 'published');
  }
}

window.unpublishPost = function(postId) {
  if (confirm('¿Despublicar este artículo? Volverá a estado de borrador.')) {
    updatePostStatus(postId, 'draft');
  }
}

window.deletePost = function(postId, postTitle) {
  if (confirm(`¿Estás seguro de que quieres eliminar el artículo "${postTitle}"?\n\nEsta acción no se puede deshacer.`)) {
    fetch(`api/blog.php?id=${postId}`, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        showAlert('danger', data.error);
      } else {
        showAlert('success', data.message);
        loadPosts();
      }
    })
    .catch(error => {
      console.error('Error deleting post:', error);
      showAlert('danger', 'Error al eliminar artículo');
    });
  }
}

async function updatePostStatus(postId, newStatus) {
  try {
    const response = await fetch(`api/blog.php?id=${postId}`, {
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
      loadPosts();
    }
    
  } catch (error) {
    console.error('Error updating post status:', error);
    showAlert('danger', 'Error al actualizar estado del artículo');
  }
}

window.clearFilters = function() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('categoryFilter').value = '';
  filterPosts();
}

window.exportPosts = function() {
  // Create CSV content
  const headers = ['Título', 'Categoría', 'Estado', 'Autor', 'Fecha Publicación', 'Fecha Creación'];
  const csvContent = [
    headers.join(','),
    ...filteredPosts.map(post => [
      post.title,
      getCategoryName(post.category),
      getStatusName(post.status),
      post.author_name || '',
      formatDate(post.published_at),
      formatDate(post.created_at)
    ].map(field => `"${field}"`).join(','))
  ].join('\n');

  // Download CSV
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `blog_posts_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  
  showAlert('success', 'Archivo CSV descargado exitosamente');
}

// Helper functions
function generateSlug(title) {
  return title
    .toLowerCase()
    .replace(/[áéíóúñ]/g, match => {
      const map = { 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ñ': 'n' };
      return map[match];
    })
    .replace(/[^a-z0-9\s\-]/g, '')
    .replace(/[\s\-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function getCategoryBadgeClass(category) {
  switch(category) {
    case 'tecnologia': return 'bg-info';
    case 'guias': return 'bg-primary';
    case 'innovacion': return 'bg-warning text-dark';
    case 'casos': return 'bg-success';
    case 'noticias': return 'bg-danger';
    case 'tips': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getCategoryName(category) {
  switch(category) {
    case 'tecnologia': return 'Tecnología';
    case 'guias': return 'Guías';
    case 'innovacion': return 'Innovación';
    case 'casos': return 'Casos de Éxito';
    case 'noticias': return 'Noticias';
    case 'tips': return 'Tips';
    default: return 'General';
  }
}

function getCategoryIcon(category) {
  switch(category) {
    case 'tecnologia': return 'cpu';
    case 'guias': return 'book';
    case 'innovacion': return 'lightbulb';
    case 'casos': return 'trophy';
    case 'noticias': return 'newspaper';
    case 'tips': return 'star';
    default: return 'journal-text';
  }
}

function getStatusBadgeClass(status) {
  switch(status) {
    case 'published': return 'bg-success';
    case 'draft': return 'bg-warning text-dark';
    case 'archived': return 'bg-secondary';
    default: return 'bg-secondary';
  }
}

function getStatusName(status) {
  switch(status) {
    case 'published': return 'Publicado';
    case 'draft': return 'Borrador';
    case 'archived': return 'Archivado';
    default: return 'Sin estado';
  }
}

function getStatusIcon(status) {
  switch(status) {
    case 'published': return 'eye';
    case 'draft': return 'file-earmark-text';
    case 'archived': return 'archive';
    default: return 'question-circle';
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