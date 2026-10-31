for (const link of document.querySelectorAll('a[href]')) {
	const url = new URL(link.href, location.href);
	if (url.origin !== location.origin) continue;
	const collection = /^#([a-z0-9-]+)$/.exec(url.hash)?.[1];
	if (collection && collection !== 'ugolini-products' && url.pathname === '/shop/') {
		link.href = `/collections/${collection}/`;
	} else if (url.pathname === '/shop/' && !url.hash) {
		link.href = '/products/';
	}
}

(() => {
	const newsletter = document.getElementById('newsletter');
	if (!newsletter) return;
	const align = () => {
		if (location.hash !== '#newsletter') return;
		const previous = document.documentElement.style.scrollBehavior;
		document.documentElement.style.scrollBehavior = 'auto';
		newsletter.scrollIntoView({ block: 'start' });
		requestAnimationFrame(() => { document.documentElement.style.scrollBehavior = previous; });
	};
	document.querySelector('.ugolini-site-footer a[href="/#newsletter"]')?.addEventListener('click', event => {
		event.preventDefault();
		history.pushState(null, '', '#newsletter');
		align();
	});
	addEventListener('pageshow', align);
	addEventListener('load', align);
	requestAnimationFrame(align);
})();

(() => {
	const columns = document.querySelectorAll('.ugolini-footer-column');
	if (!columns.length) return;
	const mobile = matchMedia('(max-width: 680px)');
	const sync = () => columns.forEach(column => { column.open = !mobile.matches; });
	mobile.addEventListener('change', sync);
	columns.forEach(column => column.querySelector('summary').addEventListener('click', event => {
		if (!mobile.matches) event.preventDefault();
	}));
	sync();
})();

(() => {
	const key = 'ugolini-shop-menu-top';
	document.addEventListener('click', event => {
		const link = event.target.closest('.ugolini-mega-panel a, .ugolini-mega-menu > summary a');
		if (link && link.origin === location.origin) {
			if (link.hash === '#ugolini-products') link.hash = '';
			try { sessionStorage.setItem(key, new URL(link.href).pathname); } catch { /* Private browsing may block storage. */ }
		}
	}, true);
	try {
		if (sessionStorage.getItem(key) !== location.pathname) return;
		sessionStorage.removeItem(key);
	} catch { return; }
	if (location.hash) history.replaceState(null, '', location.pathname + location.search);
	history.scrollRestoration = 'manual';
	const top = () => {
		const previous = document.documentElement.style.scrollBehavior;
		document.documentElement.style.scrollBehavior = 'auto';
		scrollTo(0, 0);
		requestAnimationFrame(() => { document.documentElement.style.scrollBehavior = previous; });
	};
	addEventListener('pageshow', top, { once: true });
	addEventListener('load', top, { once: true });
	requestAnimationFrame(top);
})();

(() => {
	const products = document.querySelector('.ugolini-products-section');
	if (!products) return;
	products.id = 'ugolini-products';
	const align = () => {
		const target = products.querySelector('.preview-shop-tabs') || products.querySelector('.ugolini-shop-tabs') || products.querySelector('.ugolini-section-heading') || products;
		const offset = Math.max(92, (document.querySelector('.ugolini-site-header')?.getBoundingClientRect().bottom || 0) + 16);
		const previous = document.documentElement.style.scrollBehavior;
		document.documentElement.style.scrollBehavior = 'auto';
		scrollTo(0, scrollY + target.getBoundingClientRect().top - offset);
		requestAnimationFrame(() => { document.documentElement.style.scrollBehavior = previous; });
	};
	const alignIfRequested = () => { if (location.hash === '#ugolini-products') align(); };
	requestAnimationFrame(alignIfRequested);
	addEventListener('pageshow', alignIfRequested);
	addEventListener('hashchange', alignIfRequested);
})();

(() => {
	const sections = [...document.querySelectorAll('.ugolini-faq-sections > .ugolini-faq-section')];
	if (sections.length < 3) return;
	const images = [
		'/wp-content/themes/ugolini-group/assets/images/faq/divider-one.jpg',
		'/wp-content/themes/ugolini-group/assets/images/faq/divider-two.jpg',
	];
	sections[1].classList.add('ugolini-faq-section--products');
	sections[2].classList.add('ugolini-faq-section--orders');
	sections[3]?.classList.add('ugolini-faq-section--account');
	const dividers = images.map((src, index) => {
		const divider = document.createElement('div');
		divider.className = 'ugolini-faq-divider';
		divider.setAttribute('aria-hidden', 'true');
		const image = document.createElement('img');
		image.src = src;
		image.alt = '';
		divider.append(image);
		sections[index + 1].before(divider);
		return [divider, image];
	});
	let pending = false;
	const position = () => {
		for (const [divider, image] of dividers) image.style.transform = `translate3d(0, ${-divider.getBoundingClientRect().top}px, 0)`;
		pending = false;
	};
	addEventListener('scroll', () => { if (!pending) { pending = true; requestAnimationFrame(position); } }, { passive: true });
	addEventListener('resize', position);
	position();
})();

(() => {
	const title = document.querySelector('.ugolini-product-information .alignwide > .ugolini-eyebrow');
	const gallery = document.querySelector('.ugolini-product-gallery');
	const updateProductImage = () => {
		const image = gallery?.querySelector('.swiper-slide-active img[src], .swiper-wrapper img[src], img[src]');
		if (title && image) title.style.setProperty('--ugolini-sticky-image', `url("${image.currentSrc || image.src}")`);
	};
	updateProductImage();
	if (title && gallery) new MutationObserver(updateProductImage).observe(gallery, { attributes: true, attributeFilter: ['class', 'src', 'srcset'], childList: true, subtree: true });
	const visuals = document.querySelectorAll('.ugolini-faq-section > :first-child, .ugolini-product-information .alignwide > .ugolini-eyebrow, .ugolini-product-guide__visual');
	if (!('IntersectionObserver' in window)) { visuals.forEach(visual => visual.classList.add('ugolini-image-revealed')); return; }
	const observer = new IntersectionObserver(entries => {
		for (const entry of entries) entry.target.classList.toggle('ugolini-image-revealed', entry.isIntersecting);
	}, { rootMargin: '0px 0px -8% 0px' });
	visuals.forEach(visual => observer.observe(visual));
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
		const pagination = document.createElement('div');
		pagination.className = 'ugolini-showcase__pagination';
		pagination.setAttribute('role', 'group');
		pagination.setAttribute('aria-label', 'Seleziona collezione');
		const buttons = tabs.map((tab, index) => {
			const button = document.createElement('button');
			button.type = 'button';
			button.setAttribute('aria-label', `${index + 1}: ${tab.textContent.trim()}`);
			button.addEventListener('click', () => activate(index));
			pagination.append(button);
			return button;
		});
		showcase.append(pagination);
		let active = 0;
		const activate = index => {
			active = index;
			const tab = tabs[index];
			for (const item of tabs) item.setAttribute('aria-selected', String(item === tab));
			buttons.forEach((button, item) => button.setAttribute('aria-current', String(item === index)));
			showcase.classList.toggle('has-deeper-overlay', tab.dataset.overlay === 'deeper');
			if (image.src !== tab.dataset.image) {
				showcase.classList.add('is-changing');
				image.addEventListener('load', () => showcase.classList.remove('is-changing'), { once: true });
				image.src = tab.dataset.image;
			}
			image.alt = tab.dataset.alt;
			title.textContent = tab.dataset.title;
			description.textContent = tab.dataset.description;
			link.href = tab.dataset.href;
		};
		tabs.forEach((tab, index) => tab.addEventListener('click', () => activate(index)));
		activate(0);
		if (!matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => {
			if (!document.hidden && !showcase.matches(':hover, :focus-within')) activate((active + 1) % tabs.length);
		}, 5000);
	}
})();

const productFactsViewport = matchMedia('(max-width: 767px)');
const syncProductFacts = () => {
	for (const detail of document.querySelectorAll('.ugolini-product-fact')) detail.open = !productFactsViewport.matches;
};
syncProductFacts();
productFactsViewport.addEventListener('change', syncProductFacts);

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

(() => {
	const page = document.querySelector('.ugolini-product-page');
	const title = page?.querySelector('.ugolini-product-summary h1');
	const amazonSource = page?.querySelector('.ugolini-product-summary .ugolini-amazon-buy');
	const source = amazonSource || page?.querySelector('.ugolini-product-summary .wp-block-surecart-product-buy-button button');
	if (!title || !source) return;
	const price = page.querySelector('.ugolini-product-price [data-wp-text="state.selectedDisplayAmount"]');
	const gallery = page.querySelector('.ugolini-product-gallery');
	const bar = document.createElement('aside');
	bar.className = 'ugolini-sticky-product';
	bar.setAttribute('aria-label', 'Acquisto rapido');
	bar.innerHTML = `<div class="ugolini-sticky-product__inner"><img class="ugolini-sticky-product__image" alt=""><div class="ugolini-sticky-product__details"><strong class="ugolini-sticky-product__title"></strong><span class="ugolini-sticky-product__price"></span></div>${amazonSource ? '<a class="ugolini-sticky-product__button" rel="noopener noreferrer"></a>' : '<button class="ugolini-sticky-product__button" type="button"></button>'}</div>`;
	const thumbnail = bar.querySelector('img');
	const updateThumbnail = () => {
		const firstImage = gallery?.querySelector('.sc-image-slider__thumbs .swiper-slide:first-child img') || gallery?.querySelector('.swiper-slide:first-child img');
		if (!firstImage) return;
		const src = firstImage.currentSrc || firstImage.src;
		if (thumbnail.src !== src) thumbnail.src = src;
		thumbnail.hidden = false;
	};
	thumbnail.hidden = true;
	if (gallery) new MutationObserver(updateThumbnail).observe(gallery, { childList: true, subtree: true });
	updateThumbnail();
	const button = bar.querySelector('.ugolini-sticky-product__button');
	const sync = () => {
		bar.querySelector('.ugolini-sticky-product__title').textContent = title.textContent.trim();
		bar.querySelector('.ugolini-sticky-product__price').textContent = price?.textContent.trim() || '';
		button.textContent = source.querySelector('.sc-button__link-text')?.textContent.trim() || source.textContent.trim();
		if (amazonSource) button.href = amazonSource.href;
		else button.disabled = source.disabled || source.classList.contains('sc-button__link--busy');
	};
	if (!amazonSource) button.addEventListener('click', () => source.click());
	new MutationObserver(sync).observe(source, { attributes: true, childList: true, characterData: true, subtree: true });
	if (price) new MutationObserver(sync).observe(price, { childList: true, characterData: true, subtree: true });
	new MutationObserver(sync).observe(title, { childList: true, characterData: true, subtree: true });
	const toggle = () => bar.classList.toggle('is-visible', scrollY > 16);
	addEventListener('scroll', toggle, { passive: true });
	document.body.append(bar);
	sync();
	toggle();
})();

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

(() => {
	const strip = document.querySelector('[data-home-videos]');
	const dialog = document.querySelector('[data-home-video-dialog]');
	if (!strip || !dialog) return;
	const player = dialog.querySelector('video');
	const videos = [...strip.querySelectorAll('video')];
	let opener = null;
	const start = video => {
		if (!video.src) video.src = video.dataset.src;
		video.play().catch(() => {});
	};
	if ('IntersectionObserver' in window) {
		const posters = new IntersectionObserver(entries => entries.forEach(({ target, isIntersecting }) => {
			if (!isIntersecting) return;
			target.poster = target.dataset.poster;
			posters.unobserve(target);
		}), { rootMargin: '300px 0px' });
		videos.forEach(video => posters.observe(video));
		const observer = new IntersectionObserver(entries => entries.forEach(({ target, isIntersecting }) => {
			if (isIntersecting && !dialog.open && !matchMedia('(prefers-reduced-motion: reduce)').matches) start(target);
			else target.pause();
		}), { threshold: .25 });
		videos.forEach(video => observer.observe(video));
	} else videos.forEach(video => video.poster = video.dataset.poster);
	strip.addEventListener('click', event => {
		const card = event.target.closest('.ugolini-home-video');
		if (!card) return;
		opener = card.querySelector('[data-video-open]');
		const inline = card.querySelector('video');
		videos.forEach(video => video.pause());
		player.src = inline.dataset.src;
		player.poster = inline.poster || inline.dataset.poster;
		player.currentTime = 0;
		dialog.showModal();
		player.play().catch(() => {});
	});
	dialog.querySelector('[data-video-close]').addEventListener('click', () => dialog.close());
	dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
	dialog.addEventListener('close', () => {
		player.pause();
		player.removeAttribute('src');
		player.load();
		opener?.focus();
		if (!matchMedia('(prefers-reduced-motion: reduce)').matches) videos.forEach(video => {
			const bounds = video.getBoundingClientRect();
			if (bounds.bottom > 0 && bounds.top < innerHeight) start(video);
		});
	});
})();

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
	const background = document.createElement('div');
	background.className = 'ugolini-wholesale-services__background';
	background.setAttribute('aria-hidden', 'true');
	const backdrops = images.map(image => {
		const backdrop = image.cloneNode();
		backdrop.removeAttribute('loading');
		return background.appendChild(backdrop);
	});
	story.prepend(background);
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
		backdrops.forEach((image, index) => image.classList.toggle('is-active', index === active));
	};
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	update();
}

const ugoliniCurrentPath = location.pathname.replace(/\/(?:index\.html)?$/, '') || '/';
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
		clearTimeout(closeTimer);
		closeTimer = setTimeout(() => {
			if (ugoliniShopMenu.matches(':hover, :focus-within')) return;
			ugoliniShopMenu.classList.remove('is-panel-visible');
			ugoliniShopMenu.open = false;
		}, 250);
	};
	ugoliniCloseShopMenu = close;
	if (/\/(?:shop|collections|products|prodotto)(?:\/|$)/.test(ugoliniCurrentPath)) ugoliniShopMenu.classList.add('is-current');
	ugoliniShopMenu.addEventListener('mouseenter', open);
	ugoliniShopMenu.addEventListener('mouseleave', close);
	const summary = ugoliniShopMenu.querySelector('summary');
	summary?.addEventListener('pointermove', () => { if (!ugoliniShopMenu.classList.contains('is-panel-visible')) open(); });
	summary?.addEventListener('click', event => { if (event.target.closest('a')) return; event.preventDefault(); open(); });
	ugoliniShopMenu.addEventListener('focusin', open);
	ugoliniShopMenu.addEventListener('focusout', () => requestAnimationFrame(() => { if (!ugoliniShopMenu.contains(document.activeElement)) ugoliniShopMenu.open = false; }));
}

for (const nav of document.querySelectorAll('.ugolini-primary-navigation')) {
	const list = nav.querySelector('.wp-block-navigation__container, ul');
	const links = list ? [...list.querySelectorAll(':scope > li > a, :scope > li > .wp-block-navigation-item__content')] : [];
	if (!list || !links.length) continue;
	let current = links.find(link => { const path = new URL(link.href, location.href).pathname.replace(/\/(?:index\.html)?$/, '') || '/'; return path === ugoliniCurrentPath; });
	if (!current && document.body.classList.contains('single-post')) current = links.find(link => link.textContent.trim() === 'Blog');
	current?.classList.add('is-current');
}

const ugoliniNormalizePath = value => {
	const path = new URL(value, location.href).pathname.replace(/\/index\.html$/, '/').replace(/\.html$/, '').replace(/\/$/, '');
	return path || '/';
};
const ugoliniPagePath = ugoliniNormalizePath(location.href);
const ugoliniPageMenus = {
	'Home': ['Scopri i prodotti', 'Sapori per ogni tavola', 'Perché scegliere Ugolini', 'Tradizione, esperienza e innovazione'],
	'Shop': ['Sapori per ogni tavola', 'Tutta la gamma Ugolini Gourmet'],
	'Chi siamo': ['Le radici di una passione italiana', 'Tradizione, esperienza e innovazione', 'Perché scegliere Ugolini', 'Scopri le specialità nate dalla passione Ugolini'],
	'Event': ['Prossimi eventi', 'Eventi passati', 'Notizie dal gruppo'],
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
			const id = label === 'Scopri i prodotti' ? 'ugolini-products' : ugoliniSlug(label);
			const target = path === ugoliniPagePath ? (document.getElementById(id) || ugoliniHeadings.find(heading => heading.textContent.trim() === label)) : null;
			if (target) {
				if (!target.id) target.id = id;
				ugoliniSectionTargets.set(target, label);
			}
			const row = document.createElement('li');
			const anchor = document.createElement('a');
			anchor.href = target ? `#${target.id}` : `${link.getAttribute('href').split('#')[0]}#${id}`;
			anchor.textContent = label;
			row.append(anchor);
			submenu.append(row);
		}
		item.addEventListener('mouseenter', () => {
			if (innerWidth < 768) return;
			ugoliniCloseShopMenu();
			ugoliniClosePageSubmenus(submenu);
			submenu.classList.add('is-open');
		});
		item.append(toggle, submenu);
	}
}

document.addEventListener('click', event => {
	const toggle = event.target.closest('.ugolini-submenu-toggle');
	if (!toggle) return;
	const list = toggle.closest('.wp-block-navigation__container');
	const submenu = toggle.nextElementSibling;
	if (!list || !submenu?.classList.contains('ugolini-page-submenu')) return;
	const open = !submenu.classList.contains('is-open');
	for (const sibling of list.querySelectorAll('.ugolini-page-submenu.is-open')) sibling.classList.remove('is-open');
	for (const button of list.querySelectorAll('.ugolini-submenu-toggle[aria-expanded="true"]')) button.setAttribute('aria-expanded', 'false');
	submenu.classList.toggle('is-open', open);
	toggle.setAttribute('aria-expanded', String(open));
});

const ugoliniOpenMobileSubmenuLink = link => {
	const url = new URL(link.href);
	if (ugoliniNormalizePath(url.href) !== ugoliniPagePath) {
		location.assign(url.href);
		return;
	}
	const menu = document.querySelector('.ugolini-primary-navigation .wp-block-navigation__responsive-container');
	menu?.querySelector('.wp-block-navigation__responsive-container-close')?.click();
	let checks = 0;
	const goToSection = () => {
		if (menu?.classList.contains('is-menu-open') && checks++ < 20) {
			setTimeout(goToSection, 50);
			return;
		}
		history.pushState(null, '', url.hash);
		const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
		requestAnimationFrame(() => requestAnimationFrame(() => target?.scrollIntoView({ block: 'start' })));
		setTimeout(() => target?.scrollIntoView({ block: 'start' }), 350);
	};
	goToSection();
};

let ugoliniTouchLink;
document.addEventListener('touchstart', event => {
	if (innerWidth >= 768 || event.touches.length !== 1) return;
	const point = event.touches[0];
	const link = document.elementFromPoint(point.clientX, point.clientY)?.closest('.ugolini-primary-navigation .ugolini-page-submenu a');
	ugoliniTouchLink = link ? { link, x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
}, true);
document.addEventListener('touchend', event => {
	const touch = ugoliniTouchLink;
	ugoliniTouchLink = null;
	if (!touch || Math.hypot(event.changedTouches[0].clientX - touch.x, event.changedTouches[0].clientY - touch.y) > 12) return;
	event.preventDefault();
	event.stopImmediatePropagation();
	ugoliniOpenMobileSubmenuLink(touch.link);
}, { capture: true, passive: false });
document.addEventListener('click', event => {
	if (innerWidth >= 768) return;
	const link = event.target.closest('.ugolini-primary-navigation .ugolini-page-submenu a');
	if (!link) return;
	event.preventDefault();
	ugoliniOpenMobileSubmenuLink(link);
}, true);

for (const menu of document.querySelectorAll('.ugolini-primary-navigation .wp-block-navigation__responsive-container')) {
	const sync = () => {
		document.body.classList.toggle('ugolini-mobile-menu-open', menu.classList.contains('is-menu-open'));
		dispatchEvent(new Event('ugolini:menu-change'));
	};
	new MutationObserver(sync).observe(menu, { attributes: true, attributeFilter: ['class'] });
	sync();
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
	const presets = [
		['.ugolini-hero', ['home/hero-slide-2.jpg', 'home/hero-slide-3.jpg']],
		['.ugolini-catalogue-panel', ['https://ugolinigroup.com/wp-content/uploads/2026/08/8763-confit-cipolle-rosse-ugolini-gourmet-1-1024x683.jpg', 'https://ugolinigroup.com/wp-content/uploads/2026/08/9265-crema-panna-tartufo-bianchetto-ugolini-gourmet-1-scaled-1-1024x683.jpg']],
	];
	for (const [selector, urls] of presets) for (const carousel of document.querySelectorAll(selector)) {
		if (carousel.dataset.carouselReady) continue;
		const first = carousel.querySelector('.wp-block-cover__image-background');
		if (!first) continue;
		if (carousel.closest('.ugolini-b2b-catalogue')) {
			first.src = 'https://ugolinigroup.com/wp-content/uploads/2026/08/8862-ragu-vegano-ugolini-gourmet-1-1024x683.jpg';
			first.alt = 'Ragù vegano Ugolini Gourmet';
			first.removeAttribute('srcset');
		}
		carousel.classList.add('ugolini-carousel');
		first.classList.add('ugolini-carousel__slide', 'is-active');
		for (const url of urls) {
			const slide = first.cloneNode();
			slide.dataset.src = new URL(url, first.src).href;
			slide.removeAttribute('src');
			slide.removeAttribute('fetchpriority');
			slide.removeAttribute('srcset');
			slide.classList.remove('is-active');
			carousel.insertBefore(slide, carousel.querySelector('.wp-block-cover__inner-container'));
		}
	}

	for (const gallery of document.querySelectorAll('.ugolini-home-story__gallery')) {
		gallery.classList.add('ugolini-carousel');
		for (const [index, image] of [...gallery.querySelectorAll('img')].entries()) image.classList.add('ugolini-carousel__slide', ...(index ? [] : ['is-active']));
	}
	const productStory = document.querySelector('[data-product-gallery-story]');
	const productGallery = document.querySelector('.ugolini-product-gallery .sc-image-slider > .swiper > .swiper-wrapper');
	if (productStory && productGallery) {
		const sources = [...productGallery.querySelectorAll(':scope > .swiper-slide img[src]')]
			.map(image => image.currentSrc || image.src)
			.filter((source, index, all) => source && all.indexOf(source) === index);
		if (sources.length) productStory.replaceChildren(...sources.map((source, index) => {
			const slide = document.createElement('img');
			slide.className = `ugolini-carousel__slide${index ? '' : ' is-active'}`;
			slide.src = source;
			slide.alt = '';
			slide.loading = 'lazy';
			return slide;
		}));
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
		let pending = false;
		const show = async index => {
			index = (index + slides.length) % slides.length;
			const next = slides[index];
			if (pending || index === active && !next.dataset.src) return;
			if (next.dataset.src) {
				pending = true;
				next.src = next.dataset.src;
				delete next.dataset.src;
				try { await next.decode(); } catch (_) {}
				pending = false;
			}
			active = index;
			slides.forEach((slide, item) => slide.classList.toggle('is-active', item === active));
			buttons.forEach((button, item) => button.classList.toggle('is-active', item === active));
		};
		(carousel.classList.contains('ugolini-product-story__media') ? carousel.parentElement : carousel).append(nav);
		buttons[active].classList.add('is-active');
		if (!matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => { if (!carousel.matches(':hover, :focus-within')) show(active + 1); }, 8000);
	}
})();

(() => {
	const header = document.querySelector('body > header, header.wp-block-template-part');
	if (!header) return;
	const first = document.querySelector('main > .entry-content > :first-child') || document.querySelector('main > :first-child');
	if (first?.matches('.ugolini-hero, .ugolini-home-first-screen, .ugolini-page-hero, .preview-article-hero') && !document.body.classList.contains('ugolini-event-detail-page')) document.body.classList.add('has-overlay-header');
	const update = () => document.body.classList.toggle('is-scrolled', scrollY > 12);
	const engage = engaged => {
		document.body.classList.toggle('is-header-engaged', engaged);
		if (!engaged) {
			ugoliniClosePageSubmenus();
			ugoliniCloseShopMenu();
		}
		dispatchEvent(new CustomEvent('ugolini:header-engagement', { detail: engaged }));
	};
	header.addEventListener('pointerenter', () => engage(true));
	header.addEventListener('pointerleave', () => engage(false));
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
	const update = () => {
		const bottom = Math.max(0, header.getBoundingClientRect().bottom);
		document.documentElement.style.setProperty('--ugolini-header-bottom', `${bottom}px`);
		document.documentElement.style.setProperty('--ugolini-site-header-bottom', `${Math.max(0, header.querySelector('.ugolini-site-header')?.getBoundingClientRect().bottom ?? bottom)}px`);
	};
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
	const parent = isProduct ? '<a class="ugolini-breadcrumbs__ancestor" href="/products/">Shop</a><i class="ugolini-breadcrumbs__ancestor" aria-hidden="true"></i>' : isArticle ? '<a class="ugolini-breadcrumbs__ancestor" href="/blog/">Blog</a><i class="ugolini-breadcrumbs__ancestor" aria-hidden="true"></i>' : '';
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
		const topContext = !isProduct && scrollY <= 12 && document.body.classList.contains('is-header-engaged');
		const visible = innerWidth >= 768 && document.body.classList.contains('is-header-engaged');
		breadcrumb.classList.toggle('is-top-context', topContext);
		breadcrumb.classList.toggle('is-visible', visible);
		document.documentElement.style.setProperty('--ugolini-submenu-offset', visible ? '48px' : '0px');
		const siteHeaderBottom = header.querySelector('.ugolini-site-header')?.getBoundingClientRect().bottom ?? header.getBoundingClientRect().bottom;
		document.documentElement.style.setProperty('--ugolini-header-bottom', `${Math.max(0, siteHeaderBottom + (visible ? 48 : 0))}px`);
		let active = '';
		const edge = Number.parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--ugolini-header-bottom')) + 72;
		if (topContext) active = '';
		else for (const [target, label] of ugoliniSectionTargets) if (target.getBoundingClientRect().top <= edge) active = label;
		showSection(active);
	};
	addEventListener('scroll', update, { passive: true });
	addEventListener('resize', update, { passive: true });
	addEventListener('ugolini:header-engagement', update);
	addEventListener('ugolini:menu-change', update);
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
	const figures = document.querySelectorAll('[data-story-reveal]');
	if (!figures.length) return;
	if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
		figures.forEach(figure => figure.classList.add('is-visible'));
		return;
	}
	const observer = new IntersectionObserver(entries => entries.forEach(entry => {
		if (!entry.isIntersecting) return;
		entry.target.classList.add('is-visible');
		observer.unobserve(entry.target);
	}), { threshold: .2 });
	figures.forEach(figure => {
		figure.classList.add('is-reveal-ready');
		observer.observe(figure);
	});
})();
