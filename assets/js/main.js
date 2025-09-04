// DentexaPro landing interactions
document.addEventListener('DOMContentLoaded', () => {
  // Fix para navegación por anchor
  fixAnchorNavigation();
  
  // Delay AOS initialization para evitar layout shift
  setTimeout(() => {
    initializeAOS();
  }, 100);

  // Year in footer
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Billing toggle (monthly/yearly)
  const toggle = document.getElementById('billingToggle');
  const amounts = document.querySelectorAll('.price-amount');
  if (toggle) {
    toggle.addEventListener('change', (e) => {
      const yearly = e.target.checked;
      amounts.forEach(el => {
        el.textContent = yearly ? el.dataset.yearly : el.dataset.monthly;
      });
    });
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e){
      const href = this.getAttribute('href');
      if(href && href !== '#'){
        const target = document.querySelector(href);
        if(target){
          e.preventDefault();
          
          // Asegurar que el layout esté estable antes del scroll
          setTimeout(() => {
            target.scrollIntoView({behavior:'smooth'});
          }, 50);
        }
      }
    });
  });
});

function fixAnchorNavigation() {
  // Si hay un hash en la URL, manejar la navegación
  if (window.location.hash) {
    const targetId = window.location.hash.substring(1);
    const targetElement = document.getElementById(targetId);
    
    if (targetElement) {
      // Asegurar que el elemento esté visible inmediatamente
      targetElement.style.opacity = '1';
      targetElement.style.visibility = 'visible';
      targetElement.style.transform = 'none';
      
      // Asegurar que el container padre esté bien alineado
      const container = targetElement.querySelector('.container');
      if (container) {
        container.style.width = '100%';
        container.style.maxWidth = '1320px';
        container.style.margin = '0 auto';
        container.style.paddingLeft = '15px';
        container.style.paddingRight = '15px';
      }
      
      // Scroll suave después de un pequeño delay
      setTimeout(() => {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 200);
    }
  }
}

function initializeAOS() {
  // Init AOS (scroll animations)
  if (window.AOS) {
    // Marcar como cargado para activar las animaciones CSS
    document.body.classList.add('aos-loaded');
    
    AOS.init({
      duration: 1000,
      once: true,
      offset: 100,
      easing: 'ease-out-quart',
      delay: 0,
      startEvent: 'DOMContentLoaded',
      animatedClassName: 'aos-animate',
      initClassName: 'aos-init',
      useClassNames: false,
      disableMutationObserver: false,
      debounceDelay: 50,
      throttleDelay: 99,
      disable: false
    });
    
    // Forzar recálculo del layout
    setTimeout(() => {
      window.dispatchEvent(new Event('resize'));
      
      // Si hay un hash, volver a navegar a él
      if (window.location.hash) {
        const targetElement = document.querySelector(window.location.hash);
        if (targetElement) {
          targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    }, 100);
  }
}

// Manejar cambios de hash en la URL
window.addEventListener('hashchange', () => {
  fixAnchorNavigation();
});

// Asegurar layout estable en resize
window.addEventListener('resize', () => {
  // Forzar recálculo de containers
  document.querySelectorAll('.container').forEach(container => {
    container.style.width = '100%';
    container.style.maxWidth = '1320px';
    container.style.margin = '0 auto';
  });
});

  // Year in footer
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Billing toggle (monthly/yearly)
  const toggle = document.getElementById('billingToggle');
  const amounts = document.querySelectorAll('.price-amount');
  if (toggle) {
    toggle.addEventListener('change', (e) => {
      const yearly = e.target.checked;
      amounts.forEach(el => {
        el.textContent = yearly ? el.dataset.yearly : el.dataset.monthly;
      });
    });
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e){
      const href = this.getAttribute('href');
      if(href && href !== '#'){
        const target = document.querySelector(href);
        if(target){
          e.preventDefault();
          target.scrollIntoView({behavior:'smooth'});
        }
      }
    });
  });
});
