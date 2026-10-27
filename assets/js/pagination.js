for (const list of document.querySelectorAll('[data-paginate="prodotti"]')) {
	const shop = list.closest('[data-preview-shop]');
	const count = shop?.querySelector('[data-shop-count]') || document.createElement('output');
	if (!count.isConnected) {
		count.className = 'ugolini-shop-count';
		count.setAttribute('aria-live', 'polite');
		shop?.querySelector('.preview-shop-actions')?.prepend(count);
	}
	const controls = document.createElement('nav');
	controls.className = 'ugolini-pagination';
	controls.setAttribute('aria-label', `Paginazione ${list.dataset.paginate}`);
	controls.innerHTML = '<label>Per pagina <select aria-label="Elementi per pagina"><option value="10">10</option><option value="20" selected>20</option><option value="40">40</option></select></label><div><button type="button" data-page-prev>← Precedente</button><output aria-live="polite"></output><button type="button" data-page-next>Successiva →</button></div>';
	list.after(controls);
	const select = controls.querySelector('select');
	const previous = controls.querySelector('[data-page-prev]');
	const next = controls.querySelector('[data-page-next]');
	const output = controls.querySelector('output');
	let page = 1;

	function render() {
		const items = [...list.children];
		const matching = items.filter(item => item.dataset.filterMatch !== 'false');
		const pages = Math.max(1, Math.ceil(matching.length / Number(select.value)));
		page = Math.min(page, pages);
		for (const item of items) item.hidden = true;
		matching.slice((page - 1) * Number(select.value), page * Number(select.value)).forEach(item => { item.hidden = false; });
		output.textContent = matching.length ? `${page} / ${pages}` : '0 risultati';
		count.textContent = `${matching.length} ${matching.length === 1 ? 'prodotto' : 'prodotti'}`;
		previous.disabled = page === 1;
		next.disabled = page === pages;
	}

	function goTo(value) {
		page = value;
		render();
		shop?.scrollIntoView({ block: 'start' });
	}
	previous.addEventListener('click', () => goTo(page - 1));
	next.addEventListener('click', () => goTo(page + 1));
	select.addEventListener('change', () => { page = 1; render(); });
	document.addEventListener('ugolini:catalogue-updated', () => { page = 1; render(); });
	render();
}
