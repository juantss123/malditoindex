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

  // Battle of Systems Interactive Effects
  initializeBattleEffects();

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

function initializeBattleEffects() {
  // Add interactive battle effects when section comes into view
  const battleSection = document.getElementById('batalla-sistemas');
  if (!battleSection) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        startBattleAnimation();
      }
    });
  }, { threshold: 0.3 });

  observer.observe(battleSection);

  function startBattleAnimation() {
    // Animate battle stats with staggered delays
    const statItems = document.querySelectorAll('.battle-stat-item');
    statItems.forEach((item, index) => {
      setTimeout(() => {
        item.style.transform = 'translateX(0)';
        item.style.opacity = '1';
        
        // Add impact effect
        item.style.animation = `battle-impact 0.6s ease-out`;
      }, index * 200);
    });

    // Animate problems and advantages
    const problemItems = document.querySelectorAll('.battle-problem-item, .battle-advantage-item');
    problemItems.forEach((item, index) => {
      setTimeout(() => {
        item.style.transform = 'translateX(0)';
        item.style.opacity = '1';
      }, 1000 + (index * 100));
    });

    // Show winner badge with delay
    setTimeout(() => {
      const winnerBadge = document.querySelector('.battle-winner-badge');
      if (winnerBadge) {
        winnerBadge.style.animation = 'winner-appear 1s ease-out forwards, winner-badge-glow 2s ease-in-out infinite 1s';
      }
    }, 2000);
  }

  // Add click effects to battle stats
  const statItems = document.querySelectorAll('.battle-stat-item');
  statItems.forEach(item => {
    item.addEventListener('click', () => {
      // Create ripple effect
      const ripple = document.createElement('div');
      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        left: 50%;
        top: 50%;
        width: 20px;
        height: 20px;
        margin-left: -10px;
        margin-top: -10px;
      `;
      
      item.style.position = 'relative';
      item.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // Add CSS for battle animations
  const battleStyles = document.createElement('style');
  battleStyles.textContent = `
    @keyframes battle-impact {
      0% { transform: translateX(-20px) scale(0.9); }
      50% { transform: translateX(5px) scale(1.05); }
      100% { transform: translateX(0) scale(1); }
    }
    
    @keyframes winner-appear {
      0% { 
        transform: scale(0) rotate(-180deg);
        opacity: 0;
      }
      50% {
        transform: scale(1.2) rotate(0deg);
        opacity: 1;
      }
      100% { 
        transform: scale(1) rotate(0deg);
        opacity: 1;
      }
    }
    
    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(battleStyles);

  // Initialize battle stats as hidden for animation
  statItems.forEach(item => {
    item.style.transform = 'translateX(-30px)';
    item.style.opacity = '0';
    item.style.transition = 'all 0.6s ease';
  });

  const problemItems = document.querySelectorAll('.battle-problem-item, .battle-advantage-item');
  problemItems.forEach(item => {
    item.style.transform = 'translateX(-20px)';
    item.style.opacity = '0';
    item.style.transition = 'all 0.4s ease';
  });
}

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