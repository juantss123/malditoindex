// DentexaPro landing interactions
document.addEventListener('DOMContentLoaded', () => {
  // Init AOS (scroll animations)
  if (window.AOS) {
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
      throttleDelay: 99
    });
  }

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
