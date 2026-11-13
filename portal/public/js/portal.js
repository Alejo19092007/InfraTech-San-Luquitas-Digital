/* Portal San Luquitas — mejoras progresivas. Todo funciona también sin JavaScript. */
document.addEventListener('DOMContentLoaded', () => {
  // Accesibilidad: tamaño de letra y contraste (se recuerdan en este dispositivo)
  const raiz = document.documentElement;
  const guardar = (k, v) => { try { v === null ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} };
  document.querySelectorAll('[data-letra]').forEach(b => b.addEventListener('click', () => {
    const actual = Number(raiz.dataset.letra || 0);
    const nuevo = Math.max(0, Math.min(2, actual + (b.dataset.letra === 'mas' ? 1 : -1)));
    if (nuevo) { raiz.dataset.letra = String(nuevo); guardar('sl-letra', String(nuevo)); }
    else { delete raiz.dataset.letra; guardar('sl-letra', null); }
  }));
  const contraste = document.querySelector('[data-contraste]');
  if (contraste) {
    const esOscuro = () => raiz.classList.contains('oscuro') ||
      (!raiz.classList.contains('claro') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    contraste.setAttribute('aria-pressed', String(esOscuro()));
    contraste.addEventListener('click', () => {
      const oscuro = !esOscuro();
      raiz.classList.toggle('oscuro', oscuro);
      raiz.classList.toggle('claro', !oscuro);
      guardar('sl-tema', oscuro ? 'oscuro' : 'claro');
      contraste.setAttribute('aria-pressed', String(oscuro));
    });
  }

  // Menú en celular
  const abrir = document.querySelector('[data-menu-abrir]');
  const menu = document.getElementById('menu');
  if (abrir && menu) {
    abrir.addEventListener('click', () => {
      const abierto = menu.classList.toggle('abierto');
      abrir.setAttribute('aria-expanded', String(abierto));
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && menu.classList.contains('abierto')) {
        menu.classList.remove('abierto'); abrir.setAttribute('aria-expanded', 'false'); abrir.focus();
      }
    });
  }

  // Recursos: filtro por categoría y búsqueda sin recargar la página
  const filtros = [...document.querySelectorAll('[data-filtro]')];
  const recursos = [...document.querySelectorAll('[data-recurso]')];
  const buscar = document.querySelector('[data-buscar]');
  const vacio = document.querySelector('[data-vacio]');
  const contador = document.querySelector('[data-contador]');
  if (recursos.length) {
    let activa = 'todas';
    const normalizar = t => t.toLocaleLowerCase('es').normalize('NFD').replace(/\p{Diacritic}/gu, '');
    const aplicar = () => {
      const termino = normalizar(buscar?.value.trim() || '');
      let visibles = 0;
      recursos.forEach(r => {
        const coincide = (activa === 'todas' || r.dataset.categoria === activa) && normalizar(r.textContent).includes(termino);
        r.hidden = !coincide;
        if (coincide) visibles++;
      });
      if (vacio) vacio.hidden = visibles > 0;
      if (contador) contador.textContent = visibles === 1 ? '1 recurso' : `${visibles} recursos`;
    };
    filtros.forEach(f => f.addEventListener('click', () => {
      activa = f.dataset.filtro;
      filtros.forEach(o => o.setAttribute('aria-pressed', String(o === f)));
      aplicar();
    }));
    buscar?.addEventListener('input', aplicar);
    const inicial = new URLSearchParams(location.search).get('categoria');
    filtros.find(f => f.dataset.filtro === inicial)?.click();
  }

  // Confirmación antes de eliminar
  document.querySelectorAll('[data-confirmar]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!window.confirm(form.dataset.confirmar)) e.preventDefault();
    });
  });

  // Ayuda inmediata en los campos obligatorios (Laravel valida de nuevo en el servidor)
  const formulario = document.querySelector('[data-formulario]');
  if (formulario) {
    formulario.addEventListener('submit', e => {
      let primero = null;
      formulario.querySelectorAll('[required]').forEach(c => {
        const falta = !c.value.trim();
        c.closest('.campo')?.classList.toggle('invalido', falta);
        c.setAttribute('aria-invalid', String(falta));
        if (falta && !primero) primero = c;
      });
      if (primero) { e.preventDefault(); primero.focus(); }
    });
    formulario.querySelectorAll('input, select, textarea').forEach(c => c.addEventListener('input', () => {
      if (c.value.trim()) { c.closest('.campo')?.classList.remove('invalido'); c.removeAttribute('aria-invalid'); }
    }));
  }
});
