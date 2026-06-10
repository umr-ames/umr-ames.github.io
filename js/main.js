/* =====================================================
   UMR-AMES — Scripts principaux
   ===================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* ---- Navbar : fond blanc au scroll ---- */
  const header = document.getElementById('header');
  function updateHeader() {
    header.classList.toggle('scrolled', window.scrollY > 60);
  }
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

  /* ---- Menu hamburger mobile ---- */
  const navToggle = document.getElementById('navToggle');
  const navMenu   = document.getElementById('navMenu');

  navToggle.addEventListener('click', function () {
    const isOpen = navMenu.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  navMenu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      navMenu.classList.remove('open');
      navToggle.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  /* ---- Lien actif selon la section visible ---- */
  const sections  = document.querySelectorAll('section[id]');
  const navLinks  = document.querySelectorAll('.nav-menu a[href^="#"]');

  function updateActiveLink() {
    let current = '';
    sections.forEach(function (s) {
      if (window.scrollY >= s.offsetTop - 90) current = s.id;
    });
    navLinks.forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
  }
  window.addEventListener('scroll', updateActiveLink, { passive: true });

  /* ---- Onglets Membres : Permanents / Associés ---- */
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      const tab = btn.dataset.tab;
      document.querySelectorAll('.membres-grid').forEach(function (g) {
        g.classList.toggle('hidden', g.id !== 'tab-' + tab);
      });
    });
  });

  /* ---- Filtres Publications ---- */
  document.querySelectorAll('.pub-filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.pub-filter-btn').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');

      const filter = btn.dataset.filter;
      document.querySelectorAll('.pub-card').forEach(function (card) {
        if (filter === 'all') {
          card.style.display = '';
        } else {
          const axes = (card.dataset.axis || '').split(' ');
          card.style.display = axes.indexOf(filter) !== -1 ? '' : 'none';
        }
      });
    });
  });

  /* ---- Bouton Retour en haut ---- */
  const backBtn = document.getElementById('backToTop');
  window.addEventListener('scroll', function () {
    backBtn.classList.toggle('visible', window.scrollY > 500);
  }, { passive: true });
  backBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---- Animations d'apparition au scroll ---- */
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12 }
    );
    document.querySelectorAll('.fade-up').forEach(function (el) { observer.observe(el); });
  } else {
    /* Fallback pour navigateurs anciens */
    document.querySelectorAll('.fade-up').forEach(function (el) { el.classList.add('visible'); });
  }

  /* ---- Formulaire de contact ---- */
  const form = document.getElementById('contactForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const submitBtn  = form.querySelector('.btn-submit');
      const successMsg = form.querySelector('.form-success');

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours…';

      /* Simuler l'envoi (à remplacer par une vraie API) */
      setTimeout(function () {
        form.style.display = 'none';
        if (successMsg) successMsg.style.display = 'block';
      }, 1500);
    });
  }

  /* ---- Compteur animé des statistiques héro ---- */
  function animateCounter(el, target, duration) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(function () {
      start += step;
      if (start >= target) { el.textContent = target + (el.dataset.suffix || ''); clearInterval(timer); }
      else { el.textContent = Math.floor(start) + (el.dataset.suffix || ''); }
    }, 16);
  }

  const countersObserver = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.querySelectorAll('.stat-number[data-target]').forEach(function (el) {
            animateCounter(el, parseInt(el.dataset.target), 1200);
          });
          countersObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  const heroStats = document.querySelector('.hero-stats');
  if (heroStats) countersObserver.observe(heroStats);

});
