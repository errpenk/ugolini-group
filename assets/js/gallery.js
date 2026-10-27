(() => {
  const gallery = document.querySelector('[data-company-gallery]');
  if (!gallery) return;
  const items = [...gallery.querySelectorAll('[data-gallery-item]')];
  const densityButtons = [...gallery.querySelectorAll('[data-gallery-density-button]')];
  const search = gallery.querySelector('[data-gallery-search]');
  const place = gallery.querySelector('[data-gallery-location]');
  const month = gallery.querySelector('[data-gallery-month]');
  const event = gallery.querySelector('[data-gallery-event]');
  const advanced = gallery.querySelector('[data-gallery-advanced]');
  const advancedPanel = gallery.querySelector('[data-gallery-advanced-panel]');
  const empty = gallery.querySelector('[data-gallery-empty]');
  const pagination = gallery.querySelector('[data-gallery-pagination]');
  const grid = gallery.querySelector('.ugolini-gallery__grid');
  const dialog = gallery.querySelector('[data-gallery-dialog]');
  const large = gallery.querySelector('[data-gallery-large]');
  const video = gallery.querySelector('[data-gallery-video]');
  const position = gallery.querySelector('[data-gallery-position]');
  const count = gallery.querySelector('[data-gallery-count]');
  const meta = gallery.querySelector('[data-gallery-meta]');
  const eventLink = gallery.querySelector('[data-gallery-event-link]');
  let current = 0;
  let page = 1;
  let matching = items;
  let trigger;
  const visible = () => matching;
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('it');
  for (const label of [...new Set(items.map(item => item.dataset.location).filter(Boolean))].sort()) place.add(new Option(label, label));
  for (const [id, title] of new Map(items.filter(item => item.dataset.eventId).map(item => [item.dataset.eventId, item.dataset.eventTitle])).entries()) event.add(new Option(title, id));
  const requestedEvent = new URLSearchParams(window.location.search).get('evento');
  if ([...event.options].some(option => option.value === requestedEvent)) {
    event.value = requestedEvent;
    advanced.open = true;
  }
  let filtersExpanded = advanced.open;
  advanced.dataset.expanded = String(filtersExpanded);
  let filtersAnimation;
  advanced.querySelector('summary').addEventListener('click', click => {
    click.preventDefault();
    const opening = !filtersExpanded;
    filtersExpanded = opening;
    advanced.dataset.expanded = String(opening);
    const from = advancedPanel.getBoundingClientRect().height;
    const fromOpacity = filtersAnimation ? getComputedStyle(advancedPanel).opacity : opening ? 0 : 1;
    filtersAnimation?.cancel();
    if (opening) advanced.open = true;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      advanced.open = opening;
      return;
    }
    filtersAnimation = advancedPanel.animate([
      { height: `${from}px`, opacity: fromOpacity },
      { height: `${opening ? advancedPanel.scrollHeight : 0}px`, opacity: opening ? 1 : 0 }
    ], { duration: 320, easing: 'cubic-bezier(.22, 1, .36, 1)' });
    filtersAnimation.finished.then(() => {
      if (!filtersExpanded) advanced.open = false;
      filtersAnimation = null;
    }).catch(() => {});
  });
  const renderPage = () => {
    const pages = Math.max(1, Math.ceil(matching.length / 20));
    page = Math.min(page, pages);
    const shown = new Set(matching.slice((page - 1) * 20, page * 20));
    items.forEach(item => { item.hidden = !shown.has(item); });
    pagination.replaceChildren();
    if (!matching.length) return;
    const button = (label, target, active = false) => {
      const control = document.createElement('button');
      control.type = 'button';
      control.textContent = label;
      control.setAttribute('aria-label', /^\d+$/.test(label) ? `Pagina ${label}` : label);
      if (active) control.setAttribute('aria-current', 'page');
      control.addEventListener('click', () => {
        page = target;
        renderPage();
        grid.scrollIntoView({ block: 'start', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      });
      pagination.append(control);
    };
    if (page > 1) button('Precedenti', page - 1);
    for (let number = 1; number <= pages; number++) button(String(number), number, number === page);
    if (page < pages) button('Successivi', page + 1);
  };
  const update = () => {
    const query = normalize(search.value.trim());
    matching = items.filter(item => {
      const startMonth = (item.dataset.startDate || '').slice(0, 7);
      const endMonth = (item.dataset.endDate || item.dataset.startDate || '').slice(0, 7);
      const name = [item.getAttribute('aria-label'), item.querySelector('img').alt, item.dataset.eventTitle, item.dataset.location].join(' ');
      return !((place.value && item.dataset.location !== place.value)
        || (month.value && (!startMonth || month.value < startMonth || month.value > endMonth))
        || (event.value && item.dataset.eventId !== event.value)
        || !normalize(name).includes(query));
    });
    page = 1;
    renderPage();
    const total = matching.length;
    count.textContent = `${total} ${total === 1 ? "contenuto" : "contenuti"}`;
    empty.hidden = total !== 0;
  };
  const show = index => {
    const shown = visible();
    current = (index + shown.length) % shown.length;
    const item = shown[current];
    const image = item.querySelector('img');
    video.pause();
    video.removeAttribute('src');
    video.hidden = !item.dataset.video;
    large.hidden = !!item.dataset.video;
    if (item.dataset.video) {
      video.poster = image.src;
      video.src = item.dataset.video;
      video.play().catch(() => {});
    } else {
      large.src = image.currentSrc || image.src;
      large.alt = image.alt;
    }
    position.textContent = `${current + 1} / ${shown.length}`;
    const dateLabel = date => new Intl.DateTimeFormat('it-IT', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${date}T12:00:00`));
    const dates = item.dataset.startDate ? (item.dataset.endDate && item.dataset.endDate !== item.dataset.startDate ? `${dateLabel(item.dataset.startDate)} – ${dateLabel(item.dataset.endDate)}` : dateLabel(item.dataset.startDate)) : '';
    meta.textContent = [item.dataset.location, dates].filter(Boolean).join(' · ');
    meta.hidden = !meta.textContent;
    eventLink.hidden = !item.dataset.eventUrl;
    if (item.dataset.eventUrl) {
      eventLink.href = item.dataset.eventUrl;
      eventLink.textContent = `Scopri ${item.dataset.eventTitle || 'l’evento'} →`;
    }
  };
  search.addEventListener('input', update);
  [place, month, event].forEach(input => input.addEventListener('change', update));
  gallery.querySelector('[data-gallery-clear]').addEventListener('click', () => {
    search.value = '';
    place.value = '';
    month.value = '';
    event.value = '';
    update();
  });
  densityButtons.forEach(button => button.addEventListener('click', () => {
    const device = button.dataset.galleryDevice;
    gallery.dataset[device === 'mobile' ? 'galleryDensityMobile' : 'galleryDensityDesktop'] = button.dataset.galleryDensityButton;
    densityButtons.filter(choice => choice.dataset.galleryDevice === device).forEach(choice => choice.setAttribute('aria-pressed', String(choice === button)));
  }));
  items.forEach(item => item.addEventListener('click', () => {
    trigger = item;
    dialog.showModal();
    show(visible().indexOf(item));
  }));
  gallery.querySelector('[data-gallery-close]').addEventListener('click', () => dialog.close());
  gallery.querySelector('[data-gallery-prev]').addEventListener('click', () => show(current - 1));
  gallery.querySelector('[data-gallery-next]').addEventListener('click', () => show(current + 1));
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('close', () => { video.pause(); video.removeAttribute('src'); trigger?.focus(); });
  dialog.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft') { event.preventDefault(); show(current - 1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); show(current + 1); }
  });
  update();
})();
