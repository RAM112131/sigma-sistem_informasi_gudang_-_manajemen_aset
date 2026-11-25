<script>
  // Accessible responsive navigation.
  const menuToggle = document.querySelector('.menu-toggle');
  const primaryNavigation = document.querySelector('#primary-navigation');

  function closeMenu() {
    if (!menuToggle || !primaryNavigation) return;
    primaryNavigation.classList.remove('is-open');
    menuToggle.classList.remove('is-active');
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Buka menu navigasi');
  }

  menuToggle?.addEventListener('click', () => {
    const isOpen = primaryNavigation.classList.toggle('is-open');
    menuToggle.classList.toggle('is-active', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
  });

  // Smooth anchor navigation and close the mobile menu after selecting a destination.
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = document.querySelector(link.getAttribute('href'));
      if (!target) return;
      event.preventDefault();
      closeMenu();
      history.pushState(null, '', link.getAttribute('href'));
      target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
      });
    });
  });

  // Close the mobile menu when clicking outside it or pressing Escape.
  document.addEventListener('click', (event) => {
    if (!menuToggle || !primaryNavigation) return;
    if (!menuToggle.contains(event.target) && !primaryNavigation.contains(event.target)) closeMenu();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { closeMenu(); menuToggle?.focus(); }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 700) closeMenu();
  });

  // Reveal content as it enters the viewport; content stays visible without JS.
  const revealItems = document.querySelectorAll('.module-card, .workflow-step');
  if ('IntersectionObserver' in window) {
    revealItems.forEach((item) => item.classList.add('reveal-ready'));
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealItems.forEach((item) => observer.observe(item));
  }

  const heroImageStage = document.querySelector('.hero-image-stage');
  const heroSlides = Array.from(heroImageStage?.querySelectorAll('.warehouse-hero-image') ?? []);
  const heroDots = Array.from(document.querySelectorAll('.hero-dots span'));
  if (heroImageStage && heroSlides.length > 1) {
    const loadDeferredHeroImages = () => {
      heroSlides.forEach((slide) => {
        if (slide.dataset.src) {
          slide.src = slide.dataset.src;
          delete slide.dataset.src;
        }
      });
    };

    if (document.readyState === 'complete') {
      window.setTimeout(loadDeferredHeroImages, 0);
    } else {
      window.addEventListener('load', loadDeferredHeroImages, { once: true });
    }

    let activeSlideIndex = 0;
    let isPaused = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let rotationTimer;

    function showNextHeroSlide() {
      const nextSlideIndex = (activeSlideIndex + 1) % heroSlides.length;
      const nextSlide = heroSlides[nextSlideIndex];

      if (!nextSlide.complete || nextSlide.naturalWidth === 0) {
        return;
      }

      const currentSlide = heroSlides[activeSlideIndex];
      activeSlideIndex = nextSlideIndex;

      currentSlide.classList.remove('is-active');
      currentSlide.classList.add('is-exiting');
      currentSlide.setAttribute('aria-hidden', 'true');
      nextSlide.classList.remove('is-exiting');
      nextSlide.classList.add('is-active');
      nextSlide.removeAttribute('aria-hidden');

      heroDots.forEach((dot, index) => {
        dot.classList.toggle('is-active', index === activeSlideIndex);
      });

      window.setTimeout(() => currentSlide.classList.remove('is-exiting'), 800);
    }

    function updateHeroCarousel() {
      window.clearInterval(rotationTimer);
      if (!isPaused && !document.hidden) {
        rotationTimer = window.setInterval(showNextHeroSlide, 6000);
      }
    }
    document.addEventListener('visibilitychange', updateHeroCarousel);
    updateHeroCarousel();
  }
</script>
