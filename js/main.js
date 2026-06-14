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

  /* ---- Menus déroulants ---- */
  document.querySelectorAll('.has-dropdown .dropdown-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const parent = toggle.parentElement;
      const isOpen = parent.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      /* Fermer les autres déroulants */
      document.querySelectorAll('.has-dropdown').forEach(function (d) {
        if (d !== parent) { d.classList.remove('open'); d.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false'); }
      });
    });
  });
  /* Fermer le menu mobile quand on clique un lien de sous-menu */
  document.querySelectorAll('.dropdown-menu a').forEach(function (link) {
    link.addEventListener('click', function () {
      if (navMenu) { navMenu.classList.remove('open'); }
      if (navToggle) { navToggle.classList.remove('open'); }
      document.body.style.overflow = '';
      document.querySelectorAll('.has-dropdown').forEach(function (d) { d.classList.remove('open'); });
    });
  });
  /* Clic en dehors -> fermer les déroulants (desktop) */
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.has-dropdown')) {
      document.querySelectorAll('.has-dropdown.open').forEach(function (d) {
        d.classList.remove('open');
        d.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
      });
    }
  });

  /* ---- Diaporama « À la une » ---- */
  (function () {
    const slides = Array.prototype.slice.call(document.querySelectorAll('.news-slide'));
    const dotsBox = document.getElementById('newsDots');
    if (slides.length < 2 || !dotsBox) return;

    let idx = 0, timer = null;
    const dots = slides.map(function (_, i) {
      const dot = document.createElement('span');
      dot.className = 'news-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', function () { go(i); restart(); });
      dotsBox.appendChild(dot);
      return dot;
    });

    function go(n) {
      slides[idx].classList.remove('is-active');
      dots[idx].classList.remove('active');
      idx = (n + slides.length) % slides.length;
      slides[idx].classList.add('is-active');
      dots[idx].classList.add('active');
    }
    function next() { go(idx + 1); }
    function restart() { clearInterval(timer); timer = setInterval(next, 4800); }
    restart();

    const band = document.getElementById('news-band');
    if (band) {
      band.addEventListener('mouseenter', function () { clearInterval(timer); });
      band.addEventListener('mouseleave', restart);
    }
  })();

  /* ---- Lightbox galeries ---- */
  const lightbox = document.getElementById('lightbox');
  if (lightbox) {
    const lbImg     = lightbox.querySelector('.lightbox-img');
    const lbCounter = lightbox.querySelector('.lightbox-counter');
    const btnClose  = lightbox.querySelector('.lightbox-close');
    const btnPrev   = lightbox.querySelector('.lightbox-prev');
    const btnNext   = lightbox.querySelector('.lightbox-next');

    let currentSet = [];
    let currentIdx = 0;

    function show(idx) {
      currentIdx = (idx + currentSet.length) % currentSet.length;
      const img = currentSet[currentIdx];
      lbImg.src = img.src;
      lbImg.alt = img.alt || '';
      lbCounter.textContent = (currentIdx + 1) + ' / ' + currentSet.length;
      const multiple = currentSet.length > 1;
      btnPrev.style.display = multiple ? '' : 'none';
      btnNext.style.display = multiple ? '' : 'none';
    }
    function open(set, idx) {
      currentSet = set;
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
      show(idx);
    }
    function close() {
      lightbox.hidden = true;
      document.body.style.overflow = '';
      lbImg.src = '';
    }

    document.querySelectorAll('.actu-gallery').forEach(function (gallery) {
      const imgs = Array.prototype.slice.call(gallery.querySelectorAll('.actu-gallery-img'));
      imgs.forEach(function (img, i) {
        img.addEventListener('click', function () { open(imgs, i); });
      });
    });

    btnClose.addEventListener('click', close);
    btnPrev.addEventListener('click', function () { show(currentIdx - 1); });
    btnNext.addEventListener('click', function () { show(currentIdx + 1); });
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) close();
    });
    document.addEventListener('keydown', function (e) {
      if (lightbox.hidden) return;
      if (e.key === 'Escape')     close();
      if (e.key === 'ArrowLeft')  show(currentIdx - 1);
      if (e.key === 'ArrowRight') show(currentIdx + 1);
    });
  }

});
