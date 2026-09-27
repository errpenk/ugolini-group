const shop = document.querySelector('[data-preview-shop]');

if (shop) {
	const grid = shop.querySelector('.preview-product-grid');
	const cards = [...shop.querySelectorAll('[data-product-card]')];
	const search = shop.querySelector('[data-shop-search]');
	const sort = shop.querySelector('[data-shop-sort]');
	const minimum = shop.querySelector('[data-price-min]');
	const maximum = shop.querySelector('[data-price-max]');
	const dialog = shop.querySelector('[data-filter-dialog]');
	let collection = '';

	const update = () => {
		const query = search.value.trim().toLowerCase();
		const min = Number(minimum.value || 0);
		const max = Number(maximum.value || Infinity);
		for (const card of cards) card.hidden = !(card.dataset.name.includes(query) && card.dataset.collections.includes(collection) && Number(card.dataset.price) >= min && Number(card.dataset.price) <= max);
		const direction = sort.value;
		const ordered = [...cards].sort((a, b) => direction === 'az' ? a.dataset.name.localeCompare(b.dataset.name) : direction === 'za' ? b.dataset.name.localeCompare(a.dataset.name) : direction === 'low' ? Number(a.dataset.price) - Number(b.dataset.price) : direction === 'high' ? Number(b.dataset.price) - Number(a.dataset.price) : cards.indexOf(a) - cards.indexOf(b));
		grid.append(...ordered);
	};

	const selectCollection = slug => {
		const button = shop.querySelector(`[data-shop-collection="${CSS.escape(slug)}"]`) || shop.querySelector('[data-shop-collection=""]');
		collection = button.dataset.shopLabel;
		for (const item of shop.querySelectorAll('[data-shop-collection]')) item.classList.toggle('is-active', item === button);
		update();
		for (const panel of document.querySelectorAll('[data-collection-editorial]')) panel.hidden = panel.dataset.collectionEditorial !== (slug || 'salse-tartufo');
	};
	const routeCollection = () => {
		const hash = decodeURIComponent(location.hash.slice(1));
		if (shop.querySelector(`[data-shop-collection="${CSS.escape(hash)}"]`)) return hash;
		return decodeURIComponent(location.pathname.match(/\/collections\/([^/]+)/)?.[1] || '');
	};

	for (const button of shop.querySelectorAll('[data-shop-collection]')) button.addEventListener('click', () => {
		const slug = button.dataset.shopCollection;
		history.replaceState(null, '', slug ? `#${slug}` : location.pathname + location.search);
		selectCollection(slug);
	});
	search.addEventListener('input', update);
	sort.addEventListener('change', update);
	minimum.addEventListener('input', update);
	maximum.addEventListener('input', update);
	shop.querySelector('[data-filter-open]').addEventListener('click', () => dialog.showModal());
	shop.querySelector('[data-filter-clear]').addEventListener('click', () => {
		search.value = minimum.value = maximum.value = '';
		sort.value = 'featured';
		history.replaceState(null, '', location.pathname + location.search);
		selectCollection('');
	});
	addEventListener('hashchange', () => selectCollection(routeCollection()));
	selectCollection(routeCollection());
}
