(() => {
	const priceSelectors = [
		'[data-wp-text*="display_amount"]',
		'[data-wp-text*="DisplayAmount"]',
		'.preview-product-price',
		'.wp-block-surecart-product-list-price',
		'.wp-block-surecart-product-price',
		'.wp-block-surecart-product-selected-price-amount',
		'.wp-block-surecart-product-selected-price-scratch-amount',
		'.wp-block-surecart-price-amount',
		'.wp-block-surecart-price-scratch-amount',
		'sc-total',
	].join(',');
	const watched = new WeakSet();
	const protectPrices = (root = document) => {
		const selectors = root === document ? priceSelectors : `${priceSelectors}, sc-total, .total-price, .price, [slot="price"], [slot="price-description"]`;
		for (const price of root.querySelectorAll(selectors)) {
			price.classList.add('notranslate');
			price.setAttribute('translate', 'no');
		}
		for (const element of root.querySelectorAll('*')) if (element.shadowRoot) protectPrices(element.shadowRoot);
		if (watched.has(root)) return;
		watched.add(root);
		new MutationObserver(() => protectPrices(root)).observe(root, { childList: true, subtree: true });
	};
	protectPrices();
	for (const tag of ['sc-total', 'sc-product-line-item', 'sc-line-item-total', 'sc-order-summary']) {
		customElements.whenDefined(tag).then(() => protectPrices());
	}
})();

(() => {
	const products = document.querySelector('.ugolini-products-section');
	if (!products) return;
	products.id = 'ugolini-products';
	const align = () => {
		const target = products.querySelector('.ugolini-shop-tabs') || products.querySelector('.ugolini-section-heading') || products;
		const previous = document.documentElement.style.scrollBehavior;
		document.documentElement.style.scrollBehavior = 'auto';
		scrollTo(0, scrollY + target.getBoundingClientRect().top - 92);
		requestAnimationFrame(() => { document.documentElement.style.scrollBehavior = previous; });
	};
	if (location.hash === '#ugolini-products') {
		requestAnimationFrame(align);
		addEventListener('load', align, { once: true });
	}
})();

(() => {
	const sections = [...document.querySelectorAll('.ugolini-footer-section')];
	if (!sections.length) return;
	const mobile = matchMedia('(max-width: 767px)');
	const sync = () => sections.forEach(section => section.toggleAttribute('open', !mobile.matches));
	sync();
	mobile.addEventListener('change', sync);
})();

(() => {
	for (const deck of document.querySelectorAll('[data-event-deck]')) {
		const slides = [...deck.querySelectorAll('[data-event-slide]')];
		const thumbs = [...deck.querySelectorAll('[data-event-go]')];
		const output = deck.querySelector('output');
		if (!slides.length) continue;
		let active = Math.max(0, slides.findIndex(slide => `#${slide.id}` === location.hash));
		const show = index => {
			active = (index + slides.length) % slides.length;
			slides.forEach((slide, itemIndex) => slide.hidden = itemIndex !== active);
			thumbs.forEach((thumb, itemIndex) => thumb.setAttribute('aria-current', itemIndex === active ? 'true' : 'false'));
			if (output) output.value = `${active + 1} / ${slides.length}`;
		};
		thumbs.forEach((thumb, index) => thumb.addEventListener('click', () => show(index)));
		deck.querySelector('[data-event-prev]')?.addEventListener('click', () => show(active - 1));
		deck.querySelector('[data-event-next]')?.addEventListener('click', () => show(active + 1));
		show(active);
	}
})();

for (const deal of document.querySelectorAll('[data-deal-rail]')) {
	const rail = deal.querySelector('.ugolini-product-template, .preview-product-grid');
	if (!rail) continue;
	for (const card of rail.querySelectorAll('.ugolini-product-card, .preview-product-card')) {
		const media = card.querySelector('.ugolini-product-card-media, .preview-product-media');
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

(() => {
	const dialog = document.querySelector('.ugolini-search-dialog');
	const openButton = document.querySelector('.ugolini-search-open');
	const closeButton = document.querySelector('.ugolini-search-close');
	const input = document.querySelector('#ugolini-search-input');
	const form = dialog?.querySelector('[data-live-search]');
	const results = dialog?.querySelector('.ugolini-live-search');
	if (!dialog || !openButton || !closeButton || !form) return;
	// Keep the fixed overlay outside the blurred/sticky header's containing block.
	document.body.append(dialog);
	let previousFocus;
	let searchTimer;
	let request;
	function setOpen(open) {
		if (open) previousFocus = document.activeElement;
		dialog.hidden = !open;
		openButton.setAttribute('aria-expanded', String(open));
		document.body.classList.toggle('has-search-open', open);
		(open ? input : previousFocus)?.focus();
	}
	openButton.addEventListener('click', () => setOpen(true));
	closeButton.addEventListener('click', () => setOpen(false));
	dialog.addEventListener('click', event => { if (event.target === dialog) setOpen(false); });
	dialog.addEventListener('keydown', event => {
		if (event.key === 'Escape') setOpen(false);
		if (event.key !== 'Tab') return;
		const focusable = [...dialog.querySelectorAll('button, input, [href], [tabindex]:not([tabindex="-1"])')];
		const first = focusable[0], last = focusable.at(-1);
		if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
		else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
	});
	input?.addEventListener('input', () => {
		clearTimeout(searchTimer);
		request?.abort();
		const query = input.value.trim();
		if (!results) return;
		if (!query) { results.hidden = true; results.replaceChildren(); return; }
		if (query.length < 2) { results.hidden = false; results.textContent = 'Inserisci almeno due caratteri.'; return; }
		results.hidden = false;
		results.textContent = 'Ricerca in corso…';
		searchTimer = setTimeout(async () => {
			request = new AbortController();
			try {
				const url = new URL(form.dataset.searchEndpoint, location.origin);
				url.searchParams.set('s', query);
				const response = await fetch(url, { signal: request.signal, headers: { Accept: 'application/json' } });
				if (!response.ok) throw new Error('Search request failed');
				const items = await response.json();
				if (!items.length) { results.textContent = 'Nessun risultato trovato.'; return; }
				const list = document.createElement('ul');
				for (const item of items) {
					const link = document.createElement('a');
					link.href = item.url;
					const type = document.createElement('small');
					type.textContent = item.type;
					const title = document.createElement('span');
					title.textContent = item.title;
					link.append(type, title);
					const row = document.createElement('li');
					row.append(link);
					list.append(row);
				}
				results.replaceChildren(list);
			} catch (error) {
				if (error.name !== 'AbortError') results.textContent = 'La ricerca non è disponibile. Premi Cerca per continuare.';
			}
		}, 180);
	});
})();

(() => {
	for (const showcase of document.querySelectorAll('[data-showcase]')) {
		const image = showcase.querySelector('[data-showcase-image]');
		const title = showcase.querySelector('[data-showcase-title]');
		const description = showcase.querySelector('[data-showcase-description]');
		const link = showcase.querySelector('[data-showcase-link]');
		const tabs = [...showcase.querySelectorAll('[data-showcase-tab]')];
		if (!image || !title || !description || !link || !tabs.length) continue;

		for (const tab of tabs) tab.addEventListener('click', () => {
			for (const item of tabs) item.setAttribute('aria-selected', String(item === tab));
			showcase.classList.add('is-changing');
			image.addEventListener('load', () => showcase.classList.remove('is-changing'), { once: true });
			image.src = tab.dataset.image;
			image.alt = tab.dataset.alt;
			title.textContent = tab.dataset.title;
			description.textContent = tab.dataset.description;
			link.href = tab.dataset.href;
		});
	}
})();

for (const detail of document.querySelectorAll('.ugolini-product-fact')) detail.open = true;

(() => {
	const cards = [...document.querySelectorAll('.ugolini-journal-grid > .wp-block-post')];
	const script = [...document.scripts].find(item => item.src.includes('/assets/js/theme.js'));
	if (!cards.length || !script) return;
	fetch(new URL('../../data/blog-posts.json', script.src))
		.then(response => response.ok ? response.json() : [])
		.then(articles => {
			const bySlug = new Map(articles.map(article => [article.slug, article.content]));
			for (const card of cards) {
				const link = card.querySelector('.wp-block-post-title a, .wp-block-post-featured-image a');
				const excerpt = card.querySelector('.wp-block-post-excerpt__excerpt');
				if (!link || !excerpt) continue;
				const slug = new URL(link.href, location.href).pathname.split('/').filter(Boolean).at(-1);
				const html = bySlug.get(slug);
				if (!html) continue;
				const documentCopy = new DOMParser().parseFromString(html, 'text/html').body.textContent.replace(/\s+/g, ' ').trim();
				excerpt.textContent = `${documentCopy.split(' ').slice(0, 32).join(' ')}…`;
			}
		})
		.catch(() => {});
})();

for (const gallery of document.querySelectorAll('.ugolini-product-gallery .sc-image-slider')) {
	const stage = gallery.querySelector(':scope > .swiper');
	if (!stage || gallery.querySelector('.ugolini-gallery-count')) continue;
	const count = document.createElement('span');
	count.className = 'ugolini-gallery-count';
	count.setAttribute('aria-live', 'polite');
	stage.append(count);
	const update = () => {
		const slides = [...stage.querySelectorAll(':scope > .swiper-wrapper > .swiper-slide')].filter(slide => getComputedStyle(slide).display !== 'none');
		const active = Math.max(0, slides.findIndex(slide => slide.classList.contains('swiper-slide-active')));
		count.textContent = `${active + 1} / ${Math.max(1, slides.length)}`;
	};
	new MutationObserver(update).observe(stage, { subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
	gallery.addEventListener('click', () => requestAnimationFrame(update));
	update();
}

for (const testimonials of document.querySelectorAll('[data-testimonials]')) {
	const rail = testimonials.querySelector('[data-testimonials-rail]');
	const move = direction => {
		const atStart = rail.scrollLeft < 2;
		const atEnd = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 2;
		const left = direction < 0 && atStart ? rail.scrollWidth : direction > 0 && atEnd ? 0 : rail.scrollLeft + direction * rail.clientWidth;
		rail.scrollTo({ left, behavior: 'smooth' });
	};
	testimonials.querySelector('[data-testimonials-prev]')?.addEventListener('click', () => move(-1));
	testimonials.querySelector('[data-testimonials-next]')?.addEventListener('click', () => move(1));
}

for (const copy of document.querySelectorAll('.ugolini-catalogue-panel__copy')) {
	if (copy.querySelector('.ugolini-editorial-left')) continue;
	const left = document.createElement('div');
	const right = document.createElement('div');
	left.className = 'ugolini-editorial-left';
	right.className = 'ugolini-editorial-right';
	for (const child of [...copy.children]) (child.matches('.ugolini-eyebrow, h2, h3') ? left : right).append(child);
	const firstParagraph = right.querySelector(':scope > p');
	if (firstParagraph) {
		const detail = document.createElement('p');
		detail.textContent = 'La selezione comprende specialità italiane per la tavola e per i professionisti. Consulta ogni scheda per ingredienti, formati, modalità d’uso e certificazioni disponibili, oppure contatta il team Ugolini per una richiesta commerciale dedicata.';
		firstParagraph.after(detail);
	}
	copy.append(left, right);
}

for (const story of document.querySelectorAll('[data-service-story]')) {
	const slides = [...story.querySelectorAll('.ugolini-wholesale-services__slides article')];
	const markers = [...story.querySelectorAll('.ugolini-wholesale-services__axis span')];
	const images = [...story.querySelectorAll('.ugolini-wholesale-services__media img')];
	let active = -1;
	const update = () => {
		const rect = story.getBoundingClientRect();
		const progress = Math.max(0, Math.min(1, -rect.top / Math.max(1, rect.height - innerHeight)));
		const next = Math.min(slides.length - 1, Math.round(progress * (slides.length - 1)));
		if (next === active) return;
		active = next;
		slides.forEach((slide, index) => { slide.classList.toggle('is-active', index === active); slide.setAttribute('aria-hidden', String(index !== active)); });
		markers.forEach((marker, index) => marker.classList.toggle('is-active', index === active));
		images.forEach((image, index) => image.classList.toggle('is-active', index === active));
	};
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	update();
}

const ugoliniCurrentPath = location.pathname.replace(/\/(?:index\.html)?$/, '') || '/';
const ugoliniIsShopPath = /\/(?:shop|products?|collections?|prodotto)(?:\/|$)/.test(ugoliniCurrentPath);
const ugoliniClosePageSubmenus = except => {
	for (const submenu of document.querySelectorAll('.ugolini-page-submenu.is-open')) {
		if (submenu === except) continue;
		submenu.classList.remove('is-open');
		submenu.previousElementSibling?.setAttribute('aria-expanded', 'false');
	}
};
const ugoliniShopMenu = document.querySelector('.ugolini-mega-menu');
let ugoliniCloseShopMenu = () => {};
if (ugoliniShopMenu) {
	let closeTimer;
	const open = () => {
		clearTimeout(closeTimer);
		if (innerWidth < 768) return;
		ugoliniClosePageSubmenus();
		ugoliniShopMenu.open = true;
		requestAnimationFrame(() => ugoliniShopMenu.classList.add('is-panel-visible'));
	};
	const close = () => {
		ugoliniShopMenu.classList.remove('is-panel-visible');
		closeTimer = setTimeout(() => { if (innerWidth >= 768 && !ugoliniShopMenu.classList.contains('is-panel-visible')) ugoliniShopMenu.open = false; }, 220);
	};
	ugoliniCloseShopMenu = close;
	if (ugoliniIsShopPath) ugoliniShopMenu.classList.add('is-current');
	ugoliniShopMenu.addEventListener('mouseenter', open);
	ugoliniShopMenu.addEventListener('focusin', open);
	ugoliniShopMenu.addEventListener('focusout', () => requestAnimationFrame(() => { if (!ugoliniShopMenu.contains(document.activeElement)) ugoliniShopMenu.open = false; }));
}

for (const nav of document.querySelectorAll('.ugolini-primary-navigation')) {
	const list = nav.querySelector('.wp-block-navigation__container, ul');
	const links = list ? [...list.querySelectorAll(':scope > li > a, :scope > li > .wp-block-navigation-item__content')] : [];
	if (!list || !links.length) continue;
	links.forEach(link => { link.classList.remove('is-current'); link.removeAttribute('aria-current'); });
	let current = links.find(link => { const path = new URL(link.href, location.href).pathname.replace(/\/(?:index\.html)?$/, '') || '/'; return path === ugoliniCurrentPath; });
	if (!current && document.body.classList.contains('single-post')) current = links.find(link => link.textContent.trim() === 'Blog');
	if (!current && ugoliniIsShopPath) current = links.find(link => link.textContent.trim() === 'Shop');
	current?.classList.add('is-current');
	current?.setAttribute('aria-current', 'page');
}

for (const menu of document.querySelectorAll('.ugolini-primary-navigation .wp-block-navigation__responsive-container')) {
	const sync = () => {
		document.body.classList.toggle('has-mobile-menu-open', menu.classList.contains('is-menu-open'));
		dispatchEvent(new CustomEvent('ugolini:mobile-menu'));
	};
	new MutationObserver(sync).observe(menu, { attributes: true, attributeFilter: ['class'] });
	sync();
}

const ugoliniNormalizePath = value => {
	const path = new URL(value, location.href).pathname.replace(/\/index\.html$/, '/').replace(/\.html$/, '').replace(/\/$/, '');
	return path || '/';
};
const ugoliniCloseMobileNavigation = nav => {
	const menu = nav.querySelector('.wp-block-navigation__responsive-container.is-menu-open');
	menu?.querySelector('.wp-block-navigation__responsive-container-close')?.click();
	nav.querySelector('#preview-menu.is-open')?.classList.remove('is-open');
	requestAnimationFrame(() => {
		if (!menu?.classList.contains('is-menu-open')) return;
		menu.classList.remove('is-menu-open');
		menu.setAttribute('aria-hidden', 'true');
		nav.querySelector('.wp-block-navigation__responsive-container-open')?.setAttribute('aria-expanded', 'false');
		document.documentElement.classList.remove('has-modal-open');
		document.body.classList.remove('has-mobile-menu-open');
		dispatchEvent(new CustomEvent('ugolini:mobile-menu'));
	});
};
const ugoliniPagePath = ugoliniNormalizePath(location.href);
const ugoliniPageMenus = {
	'Home': ['Scopri i prodotti', 'Sapori per ogni tavola', 'Perché scegliere Ugolini', 'Tradizione, esperienza e innovazione'],
	'Shop': ['Sapori per ogni tavola', 'Tutta la gamma Ugolini Gourmet'],
	'Chi siamo': ['Le radici di una passione italiana', 'Tradizione, esperienza e innovazione', 'Perché scegliere Ugolini', 'Scopri le specialità nate dalla passione Ugolini'],
	'Event': ['Prossimi eventi', 'Eventi passati'],
	'B2B': ['Sei uno chef, ristoratore o distributore?', 'Dalla selezione al riordino', 'Una gamma per ogni servizio', 'Un rapporto costruito sulla tua attività'],
	'FAQ': ['Ugolini Gourmet', 'Scelta e utilizzo', 'Acquisto e assistenza', 'Area clienti'],
};
const ugoliniSectionTargets = new Map();
const ugoliniSlug = value => `sezione-${value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`;
const ugoliniHeadings = [...document.querySelectorAll('main :is(h1, h2, h3)')];

for (const nav of document.querySelectorAll('.ugolini-primary-navigation')) {
	const list = nav.querySelector('.wp-block-navigation__container, ul');
	const links = list ? [...list.querySelectorAll(':scope > li > a, :scope > li > .wp-block-navigation-item__content')] : [];
	for (const link of links) {
		const path = ugoliniNormalizePath(link.href);
		const labels = ugoliniPageMenus[link.textContent.trim()];
		const item = link.closest('li');
		if (!labels || !item || item.querySelector('.ugolini-page-submenu')) {
			if (item && !labels) item.addEventListener('mouseenter', () => {
				if (innerWidth < 768) return;
				ugoliniClosePageSubmenus();
				ugoliniCloseShopMenu();
			});
			continue;
		}
		item.classList.add('ugolini-has-page-submenu');
		const toggle = document.createElement('button');
		toggle.className = 'ugolini-submenu-toggle';
		toggle.type = 'button';
		toggle.setAttribute('aria-label', `Apri le sezioni di ${link.textContent.trim()}`);
		toggle.setAttribute('aria-expanded', 'false');
		const submenu = document.createElement('ul');
		submenu.className = 'ugolini-page-submenu';
		for (const label of labels) {
			const id = ugoliniSlug(label);
			const heading = path === ugoliniPagePath ? ugoliniHeadings.find(item => item.textContent.trim() === label) : null;
			const target = label === 'Scopri i prodotti' && path === ugoliniPagePath
				? document.querySelector('.ugolini-products-section')
				: heading?.closest('.ugolini-faq-section > :first-child, .ugolini-b2b-partner__benefits, .ugolini-wholesale-services__stage') || heading;
			if (target) {
				target.id ||= id;
				ugoliniSectionTargets.set(target, label);
			}
			const row = document.createElement('li');
			const anchor = document.createElement('a');
			anchor.href = target ? `#${target.id}` : `${link.getAttribute('href').split('#')[0]}#${id}`;
			anchor.textContent = label;
			row.append(anchor);
			submenu.append(row);
		}
		toggle.addEventListener('click', event => {
			event.preventDefault();
			event.stopPropagation();
			const open = !submenu.classList.contains('is-open');
			for (const sibling of list.querySelectorAll('.ugolini-page-submenu.is-open')) sibling.classList.remove('is-open');
			for (const button of list.querySelectorAll('.ugolini-submenu-toggle[aria-expanded="true"]')) button.setAttribute('aria-expanded', 'false');
			submenu.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', String(open));
		});
		item.addEventListener('mouseenter', () => {
			if (innerWidth < 768) return;
			ugoliniCloseShopMenu();
			ugoliniClosePageSubmenus(submenu);
			submenu.classList.add('is-open');
		});
		item.append(toggle, submenu);
	}
	const followSubmenuLink = event => {
		const anchor = event.target.closest?.('.ugolini-page-submenu a');
		if (innerWidth >= 768 || !anchor) return;
		const url = new URL(anchor.href, location.href);
		event.preventDefault();
		event.stopPropagation();
		if (ugoliniNormalizePath(url.href) !== ugoliniPagePath || !url.hash) {
			location.assign(url.href);
			return;
		}
		const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
		if (!target) {
			location.assign(url.href);
			return;
		}
		ugoliniCloseMobileNavigation(nav);
		history.pushState(null, '', url.hash);
		requestAnimationFrame(() => requestAnimationFrame(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' })));
	};
	nav.addEventListener('click', followSubmenuLink, true);
}

(() => {
	const button = document.createElement('button');
	button.className = 'ugolini-scroll-top';
	button.type = 'button';
	button.setAttribute('aria-label', 'Torna all\u2019inizio');
	button.innerHTML = '<svg class="lucide lucide-arrow-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
	button.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
	const update = () => button.classList.toggle('is-visible', scrollY > 600);
	document.body.append(button);
	addEventListener('scroll', update, { passive: true });
	update();
})();

(() => {
	const propertyId = '6ab93e3ca532fa3442d54ef6';
	const widgets = {
		en: '1k3hpm76n',
		it: '1k3i2gojt',
		zh: '1k3i2i3lg',
	};
	const language = (navigator.languages?.[0] || navigator.language || 'en').toLowerCase();
	const widgetId = widgets[language.startsWith('it') ? 'it' : language.startsWith('zh') ? 'zh' : 'en'];
	let switched = widgetId === widgets.en;
	let positioningFrame = 0;

	const positionWidget = () => {
		positioningFrame = 0;
		const languagePicker = document.querySelector('#gt_float_wrapper');
		const languageTop = languagePicker?.getBoundingClientRect().top;
		const launcherBottom = languageTop == null ? 85 : Math.max(20, innerHeight - languageTop + 12);
		for (const frame of document.querySelectorAll('iframe[title="Chat widget"], iframe[title="Visit tawk.to"]')) {
			const set = (property, value) => {
				if (frame.style.getPropertyValue(property) !== value || frame.style.getPropertyPriority(property) !== 'important') {
					frame.style.setProperty(property, value, 'important');
				}
			};
			set('left', '20px');
			set('right', 'auto');
			const bounds = frame.getBoundingClientRect();
			if (bounds.width <= 100 && bounds.height <= 100) {
				set('bottom', `${launcherBottom}px`);
				set('transform', 'scale(.8)');
				set('transform-origin', 'left bottom');
			}
		}
	};
	const schedulePosition = () => {
		if (!positioningFrame) positioningFrame = requestAnimationFrame(positionWidget);
	};
	const activateWidget = () => {
		if (!switched && typeof window.Tawk_API?.switchWidget === 'function') {
			switched = true;
			window.Tawk_API.switchWidget(propertyId, widgetId);
		}
		schedulePosition();
	};

	window.Tawk_API = window.Tawk_API || {};
	const previousOnLoad = window.Tawk_API.onLoad;
	window.Tawk_API.onLoad = function (...args) {
		previousOnLoad?.apply(this, args);
		activateWidget();
	};
	new MutationObserver(records => {
		if (records.some(({ target, type }) => type === 'childList' || target instanceof HTMLIFrameElement)) {
			activateWidget();
		}
	}).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] });
	addEventListener('resize', schedulePosition, { passive: true });
	activateWidget();
})();

(() => {
	const presets = [
		['.ugolini-hero', ['https://ugolinigroup.com/wp-content/uploads/2026/08/8800-pesto-alla-genovese-ugolini-gourmet-10.jpg', 'https://ugolinigroup.com/wp-content/uploads/2026/08/8848-sugo-allarrabbiata-ugolini-gourmet-5.jpg']],
		['.ugolini-catalogue-panel', ['https://ugolinigroup.com/wp-content/uploads/2026/08/8817-pesto-rosso-ugolini-gourmet-4.jpg', 'https://ugolinigroup.com/wp-content/uploads/2026/08/8855-pesto-vegano-ugolini-gourmet-8-scaled-1.jpg']],
	];
	for (const [selector, urls] of presets) for (const carousel of document.querySelectorAll(selector)) {
		if (carousel.dataset.carouselReady) continue;
		const first = carousel.querySelector('.wp-block-cover__image-background');
		if (!first) continue;
		carousel.classList.add('ugolini-carousel');
		first.classList.add('ugolini-carousel__slide', 'is-active');
		for (const url of urls) {
			const slide = first.cloneNode();
			slide.src = url;
			slide.removeAttribute('srcset');
			slide.classList.remove('is-active');
			carousel.insertBefore(slide, carousel.querySelector('.wp-block-cover__inner-container'));
		}
	}

	for (const gallery of document.querySelectorAll('.ugolini-home-story__gallery')) {
		gallery.classList.add('ugolini-carousel');
		for (const [index, image] of [...gallery.querySelectorAll('img')].entries()) image.classList.add('ugolini-carousel__slide', ...(index ? [] : ['is-active']));
	}

	for (const carousel of document.querySelectorAll('.ugolini-carousel, [data-carousel]')) {
		if (carousel.dataset.carouselReady) continue;
		const slides = [...carousel.querySelectorAll(':scope > .ugolini-carousel__slide, :scope > figure > .ugolini-carousel__slide')];
		if (slides.length < 2) continue;
		carousel.dataset.carouselReady = 'true';
		let active = Math.max(0, slides.findIndex(slide => slide.classList.contains('is-active')));
		const nav = document.createElement('div');
		nav.className = 'ugolini-carousel-pagination';
		nav.setAttribute('aria-label', 'Seleziona immagine');
		const buttons = slides.map((_, index) => {
			const button = document.createElement('button');
			button.type = 'button';
			button.setAttribute('aria-label', `Immagine ${index + 1}`);
			button.addEventListener('click', () => show(index));
			nav.append(button);
			return button;
		});
		const show = index => {
			active = (index + slides.length) % slides.length;
			slides.forEach((slide, item) => slide.classList.toggle('is-active', item === active));
			buttons.forEach((button, item) => button.classList.toggle('is-active', item === active));
		};
		carousel.append(nav);
		show(active);
		if (!matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => { if (!carousel.matches(':hover, :focus-within')) show(active + 1); }, 5000);
	}
})();

(() => {
	const header = document.querySelector('body > header, header.wp-block-template-part');
	const firstSection = document.querySelector('main > :first-child, main > article > :first-child');
	if (!header) return;
	const hero = firstSection?.matches('.ugolini-hero, .ugolini-page-hero, .ugolini-single-hero, .preview-article-hero') || firstSection?.querySelector(':scope > :first-child:is(.ugolini-hero, .ugolini-page-hero, .ugolini-single-hero, .preview-article-hero)');
	if (hero) document.body.classList.add('has-overlay-header');
	const update = () => document.body.classList.toggle('is-scrolled', scrollY > 12);
	const engage = engaged => {
		document.body.classList.toggle('is-header-engaged', engaged);
		if (!engaged) {
			ugoliniClosePageSubmenus();
			ugoliniCloseShopMenu();
		}
		dispatchEvent(new CustomEvent('ugolini:header-engagement', { detail: engaged }));
	};
	header.addEventListener('pointerenter', event => { if (event.pointerType !== 'touch') engage(true); });
	header.addEventListener('pointerleave', event => { if (event.pointerType !== 'touch') engage(false); });
	header.addEventListener('focusin', () => engage(true));
	header.addEventListener('focusout', () => requestAnimationFrame(() => {
		if (!header.contains(document.activeElement)) engage(false);
	}));
	addEventListener('scroll', update, { passive: true });
	update();
})();

(() => {
	const header = document.querySelector('body > header, header.wp-block-template-part');
	if (!header) return;
	const update = () => document.documentElement.style.setProperty('--ugolini-header-bottom', `${Math.max(0, header.getBoundingClientRect().bottom)}px`);
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	update();
})();

(() => {
	if (document.querySelector('.ugolini-breadcrumbs')) return;
	const title = document.querySelector('main h1')?.textContent.trim() || document.title.split('—')[0].trim();
	if (!title) return;
	const header = document.querySelector('body > header, header.wp-block-template-part');
	if (!header) return;
	const isProduct = Boolean(document.querySelector('.ugolini-product-page, .wp-block-surecart-product-page'));
	const isArticle = document.body.classList.contains('single-post');
	const isHome = ugoliniCurrentPath === '/';
	const home = isHome ? '' : '<a class="ugolini-breadcrumbs__ancestor" href="/">Home</a><i class="ugolini-breadcrumbs__ancestor" aria-hidden="true"></i>';
	const parent = isProduct ? '<a class="ugolini-breadcrumbs__ancestor" href="/shop/">Shop</a><i class="ugolini-breadcrumbs__ancestor" aria-hidden="true"></i>' : isArticle ? '<a class="ugolini-breadcrumbs__ancestor" href="/blog/">Blog</a><i class="ugolini-breadcrumbs__ancestor" aria-hidden="true"></i>' : '';
	const breadcrumb = document.createElement('nav');
	breadcrumb.className = `ugolini-breadcrumbs${isProduct ? ' is-product' : ''}`;
	breadcrumb.setAttribute('aria-label', 'Breadcrumb');
	breadcrumb.innerHTML = `<div class="ugolini-breadcrumbs__inner">${home}${parent}<a class="ugolini-breadcrumbs__page" aria-current="page"></a><i class="ugolini-breadcrumbs__section-separator" aria-hidden="true" hidden></i><span class="ugolini-breadcrumbs__section" hidden></span></div>`;
	const page = breadcrumb.querySelector('.ugolini-breadcrumbs__page');
	page.textContent = title;
	const pageUrl = new URL(location.href);
	pageUrl.hash = '';
	page.href = pageUrl;
	header.append(breadcrumb);
	const section = breadcrumb.querySelector('.ugolini-breadcrumbs__section');
	const separator = breadcrumb.querySelector('.ugolini-breadcrumbs__section-separator');
	const showSection = label => {
		const value = label && label !== title ? label : '';
		section.textContent = value;
		section.hidden = !value;
		separator.hidden = !value;
		breadcrumb.classList.toggle('has-section', Boolean(value));
		page.toggleAttribute('aria-current', !value);
		section.toggleAttribute('aria-current', Boolean(value));
	};
	for (const link of document.querySelectorAll('.ugolini-page-submenu a[href^="#"]')) {
		const target = document.querySelector(link.getAttribute('href'));
		link.addEventListener('click', () => showSection(ugoliniSectionTargets.get(target)));
	}
	const update = () => {
		const menuOpen = document.body.classList.contains('has-mobile-menu-open');
		const topContext = !menuOpen && !isProduct && scrollY <= 12 && document.body.classList.contains('is-header-engaged');
		const visible = menuOpen || isProduct || scrollY > 12 || topContext;
		breadcrumb.classList.toggle('is-top-context', topContext);
		breadcrumb.classList.toggle('is-visible', visible);
		document.documentElement.style.setProperty('--ugolini-submenu-offset', visible ? '48px' : '0px');
		const siteHeaderBottom = header.querySelector('.ugolini-site-header')?.getBoundingClientRect().bottom ?? header.getBoundingClientRect().bottom;
		document.documentElement.style.setProperty('--ugolini-header-bottom', `${Math.max(0, siteHeaderBottom + (visible ? 48 : 0))}px`);
		let active = '';
		const edge = Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--ugolini-header-bottom')) + 72;
		if (topContext) active = 'Sottomenu';
		else for (const [target, label] of ugoliniSectionTargets) if (target.getBoundingClientRect().top <= edge) active = label;
		if (menuOpen && !active) active = ugoliniSectionTargets.values().next().value || '';
		showSection(active);
	};
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	addEventListener('ugolini:header-engagement', update);
	addEventListener('ugolini:mobile-menu', update);
	update();
	if (location.hash) requestAnimationFrame(() => document.querySelector(location.hash)?.scrollIntoView());
})();

(() => {
	const icons = [
		'<path d="M20 6 9 17l-5-5"/>',
		'<path d="M12 22V12"/><path d="m16 17 2 2 4-4"/><path d="M3.29 7 12 12l8.71-5"/>',
		'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
		'<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>',
	];
	document.querySelectorAll('.ugolini-wholesale-benefits li').forEach((item, index) => {
		if (item.querySelector('.lucide')) return;
		const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		svg.setAttribute('class', 'lucide'); svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '2'); svg.setAttribute('aria-hidden', 'true');
		svg.innerHTML = icons[index % icons.length];
		item.querySelector('strong')?.after(svg);
	});
})();

(() => {
	const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
	for (const section of document.querySelectorAll('[data-counter-section]')) {
		const animate = () => {
			for (const counter of section.querySelectorAll('[data-counter]')) {
				const target = Number(counter.dataset.counter);
				const suffix = counter.dataset.suffix || '';
				if (reduceMotion) { counter.textContent = `${target}${suffix}`; continue; }
				const started = performance.now();
				const tick = now => {
					const progress = Math.min(1, (now - started) / 1200);
					counter.textContent = `${Math.round(target * (1 - Math.pow(1 - progress, 3)))}${suffix}`;
					if (progress < 1) requestAnimationFrame(tick);
				};
				requestAnimationFrame(tick);
			}
		};
		new IntersectionObserver(entries => { if (entries[0].isIntersecting) animate(); }, { threshold: .35 }).observe(section);
	}
})();

(() => {
	if (!ugoliniPagePath.endsWith('/chi-siamo')) return;
	const timeline = document.querySelector('[data-timeline]');
	if (!timeline) return;
	timeline.classList.add('ugolini-timeline--about');
	const header = timeline.querySelector('header');
	const timelineTitle = header?.querySelector('h2');
	if (timelineTitle) timelineTitle.textContent = 'Dalla cucina a Ugolini Gourmet';
	const intro = header?.querySelector('p') || document.createElement('p');
	intro.classList.add('ugolini-timeline-about-intro');
	intro.textContent = 'Persone, scelte e incontri che hanno trasformato un sapere gastronomico in un progetto italiano contemporaneo.';
	if (!intro.isConnected) header?.append(intro);
	const chapters = [
		['Il punto di partenza', 'Una cucina prima di un marchio', 'L’intuizione nasce osservando il lavoro quotidiano in cucina: tempi precisi, materie prime riconoscibili e ricette capaci di conservare carattere anche nel servizio professionale.'],
		['Il metodo', 'L’esperienza diventa metodo', 'Il confronto con ristoratori e chef trasforma l’esperienza in un metodo fatto di ascolto, prove e continuità. Ogni prodotto deve essere semplice da usare, ma mai anonimo nel gusto.'],
		['Il progetto', 'Un’identità prende forma', 'Ugolini Gourmet riunisce tradizione italiana e ricerca in una collezione coerente. Non una semplice gamma, ma strumenti pensati per creare piatti riconoscibili con libertà e precisione.'],
		['La direzione', 'La qualità guarda avanti', 'Oggi il progetto cresce attraverso filiere attente, ricette inclusive e nuove collaborazioni. L’obiettivo resta lo stesso: portare a ogni tavola un gusto autentico, affidabile e aperto al futuro.'],
	];
	const images = [
		'https://ugolinigroup.com/wp-content/uploads/2026/08/8800-pesto-alla-genovese-ugolini-gourmet-10.jpg',
		'https://ugolinigroup.com/wp-content/uploads/2026/08/8831-sugo-tartufo-nero-ugolini-gourmet-4.jpg',
		'https://ugolinigroup.com/wp-content/uploads/2026/08/8817-pesto-rosso-ugolini-gourmet-4.jpg',
		'https://ugolinigroup.com/wp-content/uploads/2026/08/8855-pesto-vegano-ugolini-gourmet-8-scaled-1.jpg',
	];
	const observer = new IntersectionObserver(entries => {
		for (const entry of entries) if (entry.isIntersecting) {
			entry.target.classList.add('is-visible');
			observer.unobserve(entry.target);
		}
	}, { threshold: .28 });
	[...timeline.querySelectorAll('article')].forEach((article, index) => {
		const [eyebrow, title, copy] = chapters[index] || chapters.at(-1);
		const text = article.querySelector(':scope > div');
		if (text) {
			const label = text.querySelector('small');
			const heading = text.querySelector('h3');
			const paragraph = text.querySelector('p');
			if (label) label.textContent = eyebrow;
			if (heading) heading.textContent = title;
			if (paragraph) paragraph.textContent = copy;
		}
		let figure = article.querySelector(':scope > figure');
		if (!figure) {
			figure = document.createElement('figure');
			figure.innerHTML = `<img src="${images[index]}" alt="" loading="lazy">`;
			article.append(figure);
		}
		figure.classList.add('ugolini-timeline-about-figure');
		observer.observe(figure);
	});
})();

for (const timeline of document.querySelectorAll('[data-timeline]')) {
	const update = () => {
		const bounds = timeline.getBoundingClientRect();
		const progress = Math.max(0, Math.min(1, (innerHeight * 0.55 - bounds.top) / Math.max(1, bounds.height - innerHeight * 0.45)));
		timeline.style.setProperty('--timeline-progress', `${progress * 100}%`);
	};
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	update();
}

(() => {
	const panes = [...document.querySelectorAll('.ugolini-faq-section > :first-child, .ugolini-product-information .alignwide > .ugolini-eyebrow, .ugolini-product-guide__visual')];
	if (!panes.length) return;
	let frame;
	const update = () => {
		frame = 0;
		for (const pane of panes) {
			const peer = pane.classList.contains('ugolini-product-guide__visual') ? pane.previousElementSibling : pane.nextElementSibling;
			const section = pane.parentElement;
			if (!peer || !section) continue;
			const children = [...pane.children];
			const paneCopyHeight = children.length ? children.at(-1).getBoundingClientRect().bottom - children[0].getBoundingClientRect().top : parseFloat(getComputedStyle(pane).lineHeight) || 24;
			const styles = getComputedStyle(pane);
			const stickyTop = parseFloat(styles.top) || 0;
			const imageGap = Math.max(24, Math.min(48, innerWidth * 0.03));
			const peerHeight = peer.getBoundingClientRect().height;
			const availableHeight = innerHeight - stickyTop - paneCopyHeight - imageGap - 24;
			const imageHeightValue = Math.max(160, Math.min(availableHeight, (peerHeight - paneCopyHeight - imageGap) / 2));
			const imageHeight = `${imageHeightValue}px`;
			if (pane.style.getPropertyValue('--ugolini-sticky-image-height') !== imageHeight) pane.style.setProperty('--ugolini-sticky-image-height', imageHeight);
			const bounds = section.getBoundingClientRect();
			const travel = Math.max(imageHeightValue, bounds.height - paneCopyHeight - imageGap - imageHeightValue);
			const revealStart = Math.max(0, travel - imageHeightValue);
			const revealed = Math.max(0, Math.min(imageHeightValue, stickyTop - bounds.top - revealStart));
			pane.style.setProperty('--ugolini-sticky-image-clip', `${imageHeightValue - revealed}px`);
		}
	};
	const requestUpdate = () => { if (!frame) frame = requestAnimationFrame(update); };
	addEventListener('scroll', requestUpdate, { passive: true });
	addEventListener('resize', requestUpdate, { passive: true });
	const observer = new ResizeObserver(requestUpdate);
	for (const pane of panes) if (pane.nextElementSibling) observer.observe(pane.nextElementSibling);
	update();
})();
