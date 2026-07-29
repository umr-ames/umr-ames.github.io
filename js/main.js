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
      if (s.offsetParent === null) return; // rubrique masquée (autre page) : on l'ignore
      if (window.scrollY >= s.offsetTop - 90) current = s.id;
    });
    navLinks.forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
  }
  function setActiveLink(id) {
    navLinks.forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('href') === '#' + id);
    });
  }
  window.addEventListener('scroll', updateActiveLink, { passive: true });

  /* ---- Navigation par page : une seule rubrique visible à la fois ---- */
  const PAGE_GROUPS = {
    accueil:    ['accueil'],
    unite:      ['presentation', 'instances', 'membres', 'partenaires'],
    recherche:  ['axes', 'equipes', 'projets', 'publications'],
    actualites: ['actualites'],
    contact:    ['contact']
  };
  const SECTION_TO_GROUP = {};
  Object.keys(PAGE_GROUPS).forEach(function (g) {
    PAGE_GROUPS[g].forEach(function (id) { SECTION_TO_GROUP[id] = g; });
  });

  function showPageGroup(groupKey) {
    const ids = PAGE_GROUPS[groupKey] || PAGE_GROUPS.accueil;
    document.querySelectorAll('section[data-page]').forEach(function (s) {
      // data-locked : rubrique non publiée (ex. Instances tant que l'admin ne l'a pas activée)
      const locked = s.dataset.locked === '1';
      s.classList.toggle('hidden', locked || ids.indexOf(s.id) === -1);
    });
    document.querySelectorAll('.has-dropdown').forEach(function (d) {
      const active = Array.prototype.some.call(d.querySelectorAll('.dropdown-menu a[href^="#"]'), function (a) {
        return SECTION_TO_GROUP[a.getAttribute('href').slice(1)] === groupKey;
      });
      d.classList.toggle('group-active', active);
    });
  }

  function isLocked(id) {
    const s = document.getElementById(id);
    return !!(s && s.dataset.locked === '1');
  }

  /* Masque les entrées de menu qui pointent vers une rubrique non publiée */
  function syncLockedNav() {
    document.querySelectorAll('.nav-menu a[href^="#"], .footer-col a[href^="#"]').forEach(function (a) {
      const id = a.getAttribute('href').slice(1);
      if (!SECTION_TO_GROUP.hasOwnProperty(id)) return;
      const li = a.closest('li') || a;
      li.classList.toggle('hidden', isLocked(id));
    });
  }

  function goToSection(targetId, opts) {
    opts = opts || {};
    if (!SECTION_TO_GROUP.hasOwnProperty(targetId) || isLocked(targetId)) targetId = 'accueil';
    showPageGroup(SECTION_TO_GROUP[targetId]);
    setActiveLink(targetId);
    const el = document.getElementById(targetId);
    if (el) { el.scrollIntoView({ behavior: opts.instant ? 'auto' : 'smooth', block: 'start' }); }
    if (opts.updateHash !== false && location.hash !== '#' + targetId) {
      history.pushState(null, '', '#' + targetId);
    }
  }

  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    const id = a.getAttribute('href').slice(1);
    if (!SECTION_TO_GROUP.hasOwnProperty(id)) return;
    a.addEventListener('click', function (e) {
      e.preventDefault();
      goToSection(id);
    });
  });

  window.addEventListener('popstate', function () {
    const id = location.hash.replace('#', '') || 'accueil';
    goToSection(id, { updateHash: false, instant: true });
  });

  /* État initial : respecte un lien direct vers une rubrique (#membres…), sinon Accueil */
  syncLockedNav();
  const initialId = location.hash.replace('#', '');
  if (initialId && SECTION_TO_GROUP.hasOwnProperty(initialId) && !isLocked(initialId)) {
    showPageGroup(SECTION_TO_GROUP[initialId]);
    setActiveLink(initialId);
    window.requestAnimationFrame(function () {
      const el = document.getElementById(initialId);
      if (el) el.scrollIntoView({ behavior: 'auto', block: 'start' });
    });
  } else {
    showPageGroup('accueil');
  }

  /* ---- Instances de gouvernance (publiées depuis l'espace admin) ---- */
  (function () {
    const grid = document.getElementById('instancesGrid');
    const section = document.getElementById('instances');
    if (!grid || !section) return;

    fetch('/api/instances.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.ok || !data.public || !data.blocs || !data.blocs.length) return;

        const frag = document.createDocumentFragment();
        data.blocs.forEach(function (bloc, n) {
          const card = document.createElement('article');
          card.className = 'instance-card fade-up fade-up-delay-' + Math.min(n + 1, 3);

          const head = document.createElement('div');
          head.className = 'instance-header';
          const icon = document.createElement('div');
          icon.className = 'instance-icon';
          icon.innerHTML = '<i class="fas ' + (bloc.icon || 'fa-users').replace(/[^a-z0-9-]/gi, '') + '" aria-hidden="true"></i>';
          const h3 = document.createElement('h3');
          h3.textContent = bloc.label;
          head.appendChild(icon); head.appendChild(h3);
          card.appendChild(head);

          const body = document.createElement('div');
          body.className = 'instance-body';
          bloc.items.forEach(function (it) {
            const row = document.createElement('div');
            row.className = 'instance-item' + (it.note ? ' instance-note' : '');
            if (!it.note && it.role) {
              const role = document.createElement('span');
              role.className = 'instance-role';
              role.textContent = it.role;
              row.appendChild(role);
            }
            const name = document.createElement('span');
            name.className = 'instance-name';
            name.textContent = it.name;
            row.appendChild(name);
            body.appendChild(row);
          });
          card.appendChild(body);
          frag.appendChild(card);
        });

        grid.innerHTML = '';
        grid.appendChild(frag);

        // La rubrique devient accessible : on lève le verrou et on réaffiche le menu
        delete section.dataset.locked;
        syncLockedNav();
        grid.querySelectorAll('.fade-up').forEach(function (el) { el.classList.add('visible'); });
        const cur = location.hash.replace('#', '');
        showPageGroup(SECTION_TO_GROUP[cur] || 'accueil');
      })
      .catch(function () { /* site statique ou hors-ligne : la rubrique reste masquée */ });
  })();

  /* ---- Recherche de chercheurs par nom ---- */
  const membreSearch = document.getElementById('membreSearch');
  if (membreSearch) {
    membreSearch.addEventListener('input', function () {
      const q = this.value.trim().toLowerCase();
      document.querySelectorAll('.membres-group').forEach(function (group) {
        let visible = 0;
        group.querySelectorAll('.membre-card').forEach(function (card) {
          const name = card.querySelector('.membre-name').textContent.toLowerCase();
          const match = name.indexOf(q) !== -1;
          card.classList.toggle('hidden', !match);
          if (match) visible++;
        });
        const empty = group.querySelector('.membres-empty');
        if (empty) empty.classList.toggle('hidden', visible !== 0);
      });
    });
  }

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
      /* threshold 0 + marge basse : se déclenche dès que l'élément effleure
         l'écran, même s'il est plus haut que la fenêtre (ex : longue liste de publications) */
      { threshold: 0, rootMargin: '0px 0px -10% 0px' }
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

  /* ---- Publications dynamiques (chercheurs) ---- */
  (function () {
    const list = document.querySelector('.publications-list');
    if (!list) return;
    const axisLabels = { env: 'Environnement', sante: 'Santé & Épidémiologie', math: 'Modélisation Mathématique', ia: 'Statistiques & IA' };

    fetch('/api/publications.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.ok || !data.publications || !data.publications.length) return;
        const frag = document.createDocumentFragment();
        data.publications.forEach(function (p) {
          const card = document.createElement('article');
          card.className = 'pub-card';
          if (p.axis) card.dataset.axis = p.axis;

          const meta = document.createElement('div');
          meta.className = 'pub-meta';
          if (p.year) { const y = document.createElement('span'); y.className = 'pub-year'; y.textContent = p.year; meta.appendChild(y); }
          if (p.axis && axisLabels[p.axis]) { const a = document.createElement('span'); a.className = 'pub-axis'; a.textContent = axisLabels[p.axis]; meta.appendChild(a); }
          card.appendChild(meta);

          const title = document.createElement('div');
          title.className = 'pub-title';
          if (p.url) { const link = document.createElement('a'); link.href = p.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = p.title; title.appendChild(link); }
          else { title.textContent = p.title; }
          card.appendChild(title);

          const authors = document.createElement('div');
          authors.className = 'pub-authors';
          if (p.authors) { authors.appendChild(document.createTextNode(p.authors + ' — ')); }
          const who = document.createElement('a');
          who.href = '/chercheur.php?slug=' + encodeURIComponent(p.slug);
          who.className = 'pub-researcher-link';
          who.textContent = p.researcher;
          authors.appendChild(who);
          card.appendChild(authors);

          if (p.journal) { const j = document.createElement('div'); j.className = 'pub-journal'; j.textContent = p.journal; card.appendChild(j); }
          frag.appendChild(card);
        });
        list.insertBefore(frag, list.firstChild);
      })
      .catch(function () { /* hors-ligne ou site statique : on garde la liste statique */ });
  })();

  /* ---- Noms de membres cliquables (si le chercheur l'a activé) ---- */
  (function () {
    function norm(s) {
      return (s || '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/\b(dr|pr|prof|professeur|mr|mme|m)\.?\b/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
    }
    fetch('/api/chercheurs.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.ok || !data.researchers || !data.researchers.length) return;
        const map = {};
        data.researchers.forEach(function (x) { map[norm(x.full_name)] = x.slug; });
        document.querySelectorAll('.instance-name, .membre-name').forEach(function (el) {
          if (el.querySelector('a')) return;
          const slug = map[norm(el.textContent)];
          if (!slug) return;
          const a = document.createElement('a');
          a.href = '/chercheur.php?slug=' + encodeURIComponent(slug);
          a.className = 'member-link';
          a.textContent = el.textContent;
          el.textContent = '';
          el.appendChild(a);
        });
      })
      .catch(function () { /* site statique / hors-ligne : noms non cliquables */ });
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
