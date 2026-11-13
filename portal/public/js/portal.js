/* Interacciones progresivas: menú móvil, filtros, búsqueda, validación y confirmación. */
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
      nav.classList.remove('is-open'); toggle.setAttribute('aria-expanded','false');
    }));
  }

  // Filtros de recursos y novedades. Cada tarjeta puede declarar data-category y data-search.
  const chips = [...document.querySelectorAll('[data-filter]')];
  const cards = [...document.querySelectorAll('[data-filter-card]')];
  const search = document.querySelector('[data-search]');
  let active = 'Todas';
  function filterCards() {
    const term = (search?.value || '').trim().toLocaleLowerCase('es');
    let shown = 0;
    cards.forEach(card => {
      const category = card.dataset.category || '';
      const haystack = (card.dataset.search || card.textContent).toLocaleLowerCase('es');
      const match = (active === 'Todas' || category === active) && haystack.includes(term);
      card.hidden = !match; if (match) shown++;
    });
    const empty = document.querySelector('[data-empty-state]');
    if (empty) empty.hidden = shown !== 0;
  }
  chips.forEach(chip => chip.addEventListener('click', () => {
    active = chip.dataset.filter;
    chips.forEach(c => c.setAttribute('aria-pressed', String(c === chip)));
    filterCards();
  }));
  search?.addEventListener('input', filterCards);

  // Permite llegar filtrado desde otra página: /recursos?categoria=Para%20docentes
  const inicial = new URLSearchParams(location.search).get('categoria');
  const chipInicial = chips.find(c => c.dataset.filter === inicial);
  if (chipInicial) chipInicial.click();

  // Confirmación antes de eliminar. En Laravel, sustituir data-delete-url por una ruta real.
  document.querySelectorAll('[data-confirm-delete]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!window.confirm('¿Seguro que deseas eliminar esta novedad? Esta acción no se puede deshacer.')) event.preventDefault();
    });
  });

  // Validación de cliente de ejemplo; la validación definitiva debe repetirse en Laravel.
  const form = document.querySelector('[data-news-form]');
  if (form) {
    form.addEventListener('submit', event => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(input => {
        const field = input.closest('.field');
        const missing = !input.value.trim();
        field?.classList.toggle('invalid', missing);
        input.setAttribute('aria-invalid', String(missing));
        if (missing) valid = false;
      });
      if (!valid) event.preventDefault();
      else {
        // En una demo estática mostramos el estado. En Blade, dejar que Laravel procese el POST.
        if (form.dataset.demo === 'true') {
          event.preventDefault();
          const success = document.querySelector('[data-success-message]');
          if (success) { success.hidden = false; success.scrollIntoView({behavior:'smooth',block:'center'}); }
        }
      }
    });
    form.querySelectorAll('input,select,textarea').forEach(input => input.addEventListener('input', () => {
      if (input.value.trim()) input.closest('.field')?.classList.remove('invalid');
    }));
  }
});
