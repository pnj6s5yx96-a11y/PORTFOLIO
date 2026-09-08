(() => {
  const root = document.documentElement;
  const appBase = root.dataset.appBase || "";
  if (appBase) {
    document.querySelectorAll("a[href^=\"/\"]").forEach(link => {
      const href = link.getAttribute("href");
      if (href && !href.startsWith("//") && !href.startsWith(appBase + "/")) link.setAttribute("href", appBase + href);
    });
  }
  const storageKey = 'portfolio-theme';
  const savedTheme = localStorage.getItem(storageKey);
  if (savedTheme === 'light' || savedTheme === 'dark') root.dataset.theme = savedTheme;

  const toggle = document.querySelector('[data-theme-toggle]');
  const setThemeLabel = () => {
    if (toggle) toggle.setAttribute('aria-label', root.dataset.theme === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre');
  };
  setThemeLabel();
  toggle?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem(storageKey, root.dataset.theme);
    setThemeLabel();
  });

  const header = document.querySelector('[data-header]');
  const onScroll = () => header?.classList.toggle('is-scrolled', window.scrollY > 12);
  onScroll(); window.addEventListener('scroll', onScroll, {passive: true});

  const menuButton = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    menu?.classList.toggle('is-open', !isOpen);
  });
  menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    menuButton?.setAttribute('aria-expanded', 'false'); menu.classList.remove('is-open');
  }));

  const observer = !window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const target = entry.target;
        target.style.transition = `opacity .65s ease ${target.dataset.delay || 0}ms, transform .65s cubic-bezier(.2,.7,.2,1) ${target.dataset.delay || 0}ms`;
        target.style.opacity = '1'; target.style.transform = 'none'; observer.unobserve(target);
      }), {threshold: .12}) : null;
  document.querySelectorAll('[data-reveal]').forEach(element => {
    if (observer) { element.style.opacity = '0'; element.style.transform = 'translateY(22px)'; observer.observe(element); }
  });

  const count = document.querySelector('[data-character-count]');
  const message = document.querySelector('#message');
  const refreshCount = () => { if (count && message) count.textContent = `${message.value.length.toLocaleString('fr-FR')} / 2 000`; };
  message?.addEventListener('input', refreshCount); refreshCount();

  const contactForm = document.querySelector('[data-contact-form]');
  const messages = {
    name: 'Indiquez votre nom (au moins 2 caractères).',
    email: 'Saisissez une adresse e-mail valide.',
    project_type: 'Choisissez le type de projet.',
    budget: 'Choisissez une tranche de budget.',
    deadline: 'Indiquez le délai souhaité.',
    message: 'Décrivez votre besoin en au moins 30 caractères.',
    consent: 'Votre accord est nécessaire pour envoyer la demande.',
  };
  const setFieldError = (field, error = '') => {
    const element = contactForm?.elements.namedItem(field);
    const errorNode = document.getElementById(`${field}-error`);
    element?.classList.toggle('is-invalid', Boolean(error));
    if (errorNode) errorNode.textContent = error;
  };
  const validateField = field => {
    const input = contactForm?.elements.namedItem(field);
    if (!input) return true;
    let invalid = false;
    if (field === 'consent') invalid = !input.checked;
    else if (field === 'email') invalid = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
    else if (field === 'name') invalid = input.value.trim().length < 2;
    else if (field === 'message') invalid = input.value.trim().length < 30;
    else invalid = !input.value.trim();
    setFieldError(field, invalid ? messages[field] : ''); return !invalid;
  };
  contactForm?.querySelectorAll('input, select, textarea').forEach(input => {
    if (messages[input.name]) input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'blur', () => validateField(input.name));
  });
  contactForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const fields = Object.keys(messages);
    const valid = fields.map(validateField).every(Boolean);
    const status = contactForm.querySelector('[data-form-status]');
    const submit = contactForm.querySelector('[data-submit-button]');
    if (!valid) { status.textContent = 'Certains champs demandent votre attention.'; status.className = 'form-status is-error'; contactForm.querySelector('.is-invalid')?.focus(); return; }
    status.textContent = 'Envoi de votre demande…'; status.className = 'form-status'; submit.disabled = true; submit.textContent = 'Envoi en cours…';
    try {
      const response = await fetch(contactForm.action, {method: 'POST', body: new FormData(contactForm), headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
      const payload = await response.json();
      if (!response.ok || !payload.ok) {
        Object.entries(payload.errors || {}).forEach(([field, error]) => setFieldError(field, error));
        throw new Error(payload.message || 'La demande n’a pas pu être envoyée.');
      }
      contactForm.reset(); refreshCount();
      status.textContent = payload.message; status.className = 'form-status is-success';
    } catch (error) { status.textContent = error.message || 'Une erreur est survenue. Réessayez dans quelques instants.'; status.className = 'form-status is-error'; }
    finally { submit.disabled = false; submit.innerHTML = 'Envoyer ma demande <span aria-hidden="true">↗</span>'; }
  });

  const filterControls = document.querySelector('[data-filter-controls]');
  const cards = [...document.querySelectorAll('[data-project-card]')];
  const emptyState = document.querySelector('[data-empty-state]');
  filterControls?.addEventListener('click', event => {
    const button = event.target.closest('[data-filter]'); if (!button) return;
    filterControls.querySelectorAll('button').forEach(item => item.classList.toggle('is-active', item === button));
    const filter = button.dataset.filter; let visible = 0;
    cards.forEach(card => {
      const show = filter === 'all' || card.dataset.tags.includes(filter);
      card.hidden = !show; if (show) visible++;
    });
    if (emptyState) emptyState.hidden = visible !== 0;
  });

  const dialog = document.querySelector('[data-project-dialog]');
  const closeDialog = () => dialog?.close();
  document.querySelectorAll('[data-project-open]').forEach(button => button.addEventListener('click', () => {
    if (!dialog) return;
    try {
      const project = JSON.parse(button.dataset.project);
      dialog.querySelector('[data-dialog-title]').textContent = project.title;
      dialog.querySelector('[data-dialog-summary]').textContent = project.summary;
      dialog.querySelector('[data-dialog-challenge]').textContent = project.challenge;
      dialog.querySelector('[data-dialog-link]').href = (window.PORTFOLIO?.projectUrl || "/projet.php") + "?slug=" + encodeURIComponent(project.slug);
      const tags = dialog.querySelector('[data-dialog-tags]'); tags.innerHTML = project.tags.map(tag => `<span>${tag}</span>`).join('');
      dialog.querySelector('[data-dialog-visual]').innerHTML = button.closest('[data-project-card]')?.querySelector('.project-visual')?.outerHTML || '';
      dialog.showModal();
    } catch (_) { /* Ignore malformed data; page detail remains available. */ }
  }));
  dialog?.querySelector('[data-dialog-close]')?.addEventListener('click', closeDialog);
  dialog?.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });

  if (window.PORTFOLIO?.trackUrl && navigator.sendBeacon) {
    const tracked = sessionStorage.getItem(`tracked:${location.pathname}`);
    if (!tracked) {
      const data = new FormData(); data.append('path', location.pathname); data.append('csrf_token', window.PORTFOLIO.csrf);
      if (window.PORTFOLIO.projectId) data.append('project_id', window.PORTFOLIO.projectId);
      navigator.sendBeacon(window.PORTFOLIO.trackUrl, data); sessionStorage.setItem(`tracked:${location.pathname}`, '1');
    }
  }
})();
