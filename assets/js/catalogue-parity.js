const shop = document.querySelector('[data-preview-shop]');

if (shop) {
	const grid = shop.querySelector('.preview-product-grid');
	const count = shop.querySelector('[data-shop-count]');
	let cards = [...shop.querySelectorAll('[data-product-card]')];
	const search = shop.querySelector('[data-shop-search]');
	const sort = shop.querySelector('[data-shop-sort]');
	const minimum = shop.querySelector('[data-price-min]');
	const maximum = shop.querySelector('[data-price-max]');
	const dialog = shop.querySelector('[data-filter-dialog]');
	let collection = '';
	const priceOf = card => card.dataset.price ? Number(card.dataset.price) : Infinity;
	minimum.removeAttribute('max');
	maximum.removeAttribute('max');
	minimum.min = maximum.min = '0';
	minimum.placeholder = maximum.placeholder = '';

	const loadCatalog = async () => {
		const fields = '_fields=id,link,title,meta,sc_collection,gallery';
		const [termsResponse, firstResponse] = await Promise.all([
			fetch('/wp-json/wp/v2/sc_collection?per_page=100&_fields=id,name,slug,count'),
			fetch(`/wp-json/wp/v2/sc_product?per_page=100&page=1&${fields}`),
		]);
		if (!termsResponse.ok || !firstResponse.ok) throw new Error('Catalogo non disponibile');
		const terms = await termsResponse.json();
		const products = await firstResponse.json();
		for (let page = 2, batch = products; batch.length === 100; page++) {
			const response = await fetch(`/wp-json/wp/v2/sc_product?per_page=100&page=${page}&${fields}`);
			if (!response.ok) break;
			batch = await response.json();
			products.push(...batch);
		}
		if (!products.length) return;
		const byId = new Map(terms.map(term => [term.id, term]));
		const template = cards[0];
		const seen = new Set();
		const nextCards = [];
		for (const product of products) {
			const key = product.meta?.sc_id || product.id;
			if (seen.has(key)) continue;
			seen.add(key);
			const link = new URL(product.link, location.origin);
			if (link.origin !== location.origin) continue;
			const name = new DOMParser().parseFromString(product.title.rendered, 'text/html').body.textContent.trim();
			const collections = product.sc_collection.map(id => byId.get(id)).filter(Boolean);
			const amount = Number(product.meta?.min_price_amount) || 0;
			const card = template.cloneNode(true);
			card.dataset.name = name.toLowerCase();
			card.dataset.collections = collections.map(term => term.slug).join('|');
			card.dataset.price = amount > 0 ? String(amount) : '';
			for (const anchor of card.querySelectorAll('a')) anchor.href = link.href;
			const image = card.querySelector('.preview-product-media img');
			if (product.gallery?.[0]?.url) { image.src = product.gallery[0].url; image.alt = name; }
			else image.replaceWith(document.createElement('span'));
			card.querySelector('.ugolini-product-card-meta').textContent = collections.map(term => term.name).join(' | ');
			card.querySelector('h3 a').textContent = name;
			card.querySelector('.preview-product-price').textContent = amount > 0 ? `${amount.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €` : 'Prezzo su richiesta';
			nextCards.push(card);
		}
		if (!nextCards.length) return;
		const tabs = shop.querySelector('.preview-shop-tabs');
		const all = tabs.querySelector('[data-shop-collection=""]');
		tabs.replaceChildren(all);
		for (const term of terms.filter(term => term.count > 0)) {
			const button = all.cloneNode(true);
			button.classList.remove('is-active');
			button.dataset.shopCollection = term.slug;
			button.href = `/collections/${encodeURIComponent(term.slug)}/`;
			button.textContent = term.name;
			tabs.append(button);
		}
		cards = nextCards;
		grid.replaceChildren(...cards);
		selectCollection(routeCollection());
	};

	const update = () => {
		const query = search.value.trim().toLowerCase();
		const min = minimum.value ? Number(minimum.value) : 0;
		const max = maximum.value ? Number(maximum.value) : Infinity;
		for (const card of cards) {
			const matches = card.dataset.name.includes(query) && (!collection || card.dataset.collections.split('|').includes(collection)) && (!minimum.value && !maximum.value || priceOf(card) >= min && priceOf(card) <= max);
			card.dataset.filterMatch = String(matches);
			card.hidden = !matches;
		}
		if (count) count.textContent = `${cards.filter(card => card.dataset.filterMatch === 'true').length} prodotti`;
		const direction = sort.value;
		const ordered = [...cards].sort((a, b) => direction === 'az' ? a.dataset.name.localeCompare(b.dataset.name) : direction === 'za' ? b.dataset.name.localeCompare(a.dataset.name) : direction === 'low' || direction === 'high' ? (!Number.isFinite(priceOf(a)) ? 1 : !Number.isFinite(priceOf(b)) ? -1 : direction === 'low' ? priceOf(a) - priceOf(b) : priceOf(b) - priceOf(a)) : cards.indexOf(a) - cards.indexOf(b));
		grid.append(...ordered);
		document.dispatchEvent(new Event('ugolini:catalogue-updated'));
	};

	const heroImages = {
		'': ['all.jpg', 'Selezione delle specialità Ugolini Gourmet'],
		caviareat: ['caviareat.jpg', 'Storione in acqua'],
		luxureat: ['luxureat.jpg', 'Specialità LuxurEat al tartufo'],
		marmellate: ['marmellate.jpg', 'Marmellate e frutta'],
		'olio-al-tartufo': ['olio-al-tartufo.jpg', 'Oli aromatizzati alle erbe'],
		pesto: ['pesto.jpg', 'Pesto verde in un vasetto'],
		'salse-funghi': ['salse-funghi.jpg', 'Funghi freschi affettati'],
		'salse-tartufo': ['salse-tartufo.jpg', 'Tartufi neri'],
		sughi: ['sughi.jpg', 'Sugo al pomodoro con basilico'],
		'tin-caviar': ['tin-caviar.jpg', 'Caviale servito su crostini'],
		truffleat: ['truffleat.jpg', 'Gamma Truffleat'],
	};
	const cover = document.querySelector('.ugolini-page-hero .wp-block-cover__image-background');
	const coverBase = cover?.src.match(/^(.*\/collection-heroes\/)[^/]+(?:\?.*)?$/)?.[1];
	const syncHero = slug => {
		const [file, alt] = heroImages[slug] || heroImages[''];
		if (!cover || !coverBase) return;
		const src = `${coverBase}${file}?v=6.65.75`;
		if (cover.src !== src) cover.src = src;
		cover.alt = alt;
		cover.removeAttribute('srcset');
	};

	const selectCollection = slug => {
		const button = shop.querySelector(`[data-shop-collection="${CSS.escape(slug)}"]`) || shop.querySelector('[data-shop-collection=""]');
		collection = button.dataset.shopCollection;
		syncHero(collection);
		for (const item of shop.querySelectorAll('[data-shop-collection]')) item.classList.toggle('is-active', item === button);
		update();
		const panels = [...document.querySelectorAll('[data-collection-editorial]')];
		for (const panel of panels) panel.hidden = panel.dataset.collectionEditorial !== collection;
		const panel = panels.find(item => item.dataset.collectionEditorial === collection);
		const hero = document.querySelector('.ugolini-page-hero');
		if (panel && hero) {
			const title = hero.querySelector('h1');
			const description = hero.querySelector('.wp-block-term-description p, .ugolini-page-hero-content > p:not(.ugolini-eyebrow)');
			if (title) title.textContent = button.textContent.trim();
			if (description) description.textContent = panel.querySelector('.ugolini-collection-editorial__intro > p')?.textContent || '';
		}
	};
	const isPreview = location.pathname.endsWith('/shop.html');
	const routeCollection = () => decodeURIComponent(isPreview ? location.hash.slice(1) : location.pathname.match(/\/collections\/([^/]+)/)?.[1] || '');
	const legacyHash = decodeURIComponent(location.hash.slice(1));
	if (!isPreview && legacyHash && shop.querySelector(`[data-shop-collection="${CSS.escape(legacyHash)}"]`)) {
		location.replace(`/collections/${encodeURIComponent(legacyHash)}/`);
	}

	shop.querySelector('.preview-shop-tabs').addEventListener('click', event => {
		const button = event.target.closest('[data-shop-collection]');
		if (!button || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || (button.tagName === 'A' && button.target === '_blank')) return;
		event.preventDefault();
		const slug = button.dataset.shopCollection;
		history.pushState(null, '', isPreview ? (slug ? `#${encodeURIComponent(slug)}` : 'shop.html') : (slug ? `/collections/${encodeURIComponent(slug)}/` : '/products/'));
		selectCollection(slug);
	});
	addEventListener('popstate', () => selectCollection(routeCollection()));
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
	selectCollection(routeCollection());
	if (!shop.hasAttribute('data-catalogue-complete')) loadCatalog().catch(error => console.warn('Catalogo completo non caricato:', error));
}

for (const deal of document.querySelectorAll('[data-deal-rail]')) {
	const rail = deal.querySelector('.preview-product-grid');
	if (!rail) continue;
	for (const card of rail.querySelectorAll('.preview-product-card')) {
		const media = card.querySelector('.preview-product-media');
		if (!media || card.querySelector('.ugolini-deal-thumb')) continue;
		const thumb = document.createElement('span');
		thumb.className = 'ugolini-deal-thumb';
		thumb.innerHTML = media.innerHTML;
		media.after(thumb);
	}
	const move = direction => rail.scrollBy({ left: direction * Math.max(280, rail.clientWidth * 0.8), behavior: 'smooth' });
	deal.querySelector('[data-deal-prev]')?.addEventListener('click', () => move(-1));
	deal.querySelector('[data-deal-next]')?.addEventListener('click', () => move(1));
}
