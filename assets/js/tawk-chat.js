(() => {
	const config = window.ugoliniChatConfig;
	if (!config?.embedUrl || !config?.chatUrl) return;

	const storageKey = 'ugolini-chat-welcome-shown';
	let wasShown = false;
	try { wasShown = sessionStorage.getItem(storageKey) === '1'; } catch (error) {}
	const showWelcome = !wasShown;
	try { sessionStorage.setItem(storageKey, '1'); } catch (error) {}

	const language = (document.querySelector('#gt_float_wrapper .gt-current')?.getAttribute('data-gt-lang') || 'it').toLowerCase();
	const locale = language.startsWith('zh') ? 'zh' : language.startsWith('it') ? 'it' : 'en';
	const copy = {
		en: { greeting: 'Hello! How can we help?', question: 'I have a question', discover: 'Learn more', loading: 'Connecting you with Ugolini…', failed: 'The chat could not load. Select the chat button to open it in a new tab.' },
		it: { greeting: 'Ciao! Come possiamo aiutarti?', question: 'Ho una domanda', discover: 'Scopri di più', loading: 'Ti stiamo mettendo in contatto con Ugolini…', failed: 'La chat non si è caricata. Seleziona il pulsante per aprirla in una nuova scheda.' },
		zh: { greeting: '您好！需要什么帮助？', question: '我有一个问题', discover: '了解更多', loading: '正在为您连接 Ugolini…', failed: '对话加载失败。请再次点击客服按钮，在新页面中打开。' },
	}[locale];

	const root = document.createElement('div');
	root.className = 'ugolini-chat';
	root.innerHTML = `
		${showWelcome ? `<section class="ugolini-chat__welcome" aria-label="Assistenza Ugolini">
			<div class="ugolini-chat__message">
				<span class="ugolini-chat__wave" aria-hidden="true">👋</span>
				<p>${copy.greeting}</p>
				<button class="ugolini-chat__close" type="button" aria-label="Chiudi il messaggio di benvenuto">×</button>
			</div>
			<div class="ugolini-chat__actions">
				<button type="button" data-chat-topic="Prodotti e ordini">${copy.question}</button>
				<button type="button" data-chat-topic="Ugolini Gourmet">${copy.discover}</button>
			</div>
		</section>` : ''}
		<div class="ugolini-chat__status" role="status" hidden>
			<span class="ugolini-chat__spinner" aria-hidden="true"></span>
			<span data-chat-status></span>
		</div>
		<button class="ugolini-chat__launcher" type="button" aria-label="Apri la chat con Ugolini">
			<svg viewBox="0 0 800 800" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M400 26.2c-193.3 0-350 156.7-350 350 0 136.2 77.9 254.3 191.5 312.1 15.4 8.1 31.4 15.1 48.1 20.8l-16.5 63.5c-2 7.8 5.4 14.7 13 12.1l229.8-77.6c14.6-5.3 28.8-11.6 42.4-18.7C672 630.6 750 512.5 750 376.2c0-193.3-156.7-350-350-350zm211.1 510.7c-10.8 26.5-41.9 77.2-121.5 77.2-79.9 0-110.9-51-121.6-77.4-2.8-6.8 5-13.4 13.8-11.8 76.2 13.7 147.7 13 215.3.3 8.9-1.8 16.8 4.8 14 11.7z"/></svg>
		</button>`;
	document.body.append(root);

	let positionFrame = 0;
	let observedPicker;
	const pickerSize = new ResizeObserver(() => schedulePosition());
	const pickerChanges = new MutationObserver(() => schedulePosition());
	const widgetChanges = new MutationObserver(() => schedulePosition());
	const observedFrames = new WeakSet();
	const schedulePosition = () => {
		if (!positionFrame) positionFrame = requestAnimationFrame(positionChat);
	};
	const placeFrame = (frame, bottom) => {
		if (!frame) return;
		if (!observedFrames.has(frame)) {
			widgetChanges.observe(frame, { attributes: true, attributeFilter: ['style'] });
			observedFrames.add(frame);
		}
		if (frame.style.getPropertyValue('position') !== 'fixed' || frame.style.getPropertyPriority('position') !== 'important') {
			frame.style.setProperty('position', 'fixed', 'important');
		}
		const value = `${bottom.toFixed(2)}px`;
		const inset = `auto auto ${value} 20px`;
		if (frame.style.getPropertyValue('inset') !== inset || frame.style.getPropertyPriority('inset') !== 'important') {
			frame.style.setProperty('inset', inset, 'important');
		}
		if (frame.style.getPropertyValue('bottom') !== value || frame.style.getPropertyPriority('bottom') !== 'important') {
			frame.style.setProperty('bottom', value, 'important');
		}
	};
	const positionChat = () => {
		positionFrame = 0;
		const picker = document.querySelector('#gt_float_wrapper');
		if (picker && picker !== observedPicker) {
			pickerSize.disconnect();
			pickerChanges.disconnect();
			pickerSize.observe(picker);
			pickerChanges.observe(picker, { attributes: true, childList: true, subtree: true, attributeFilter: ['class', 'style'] });
			observedPicker = picker;
		}
		const selected = picker?.querySelector('.gt-selected');
		const options = picker?.querySelector('.gt_options');
		const menu = options && getComputedStyle(options).display !== 'none' ? options.getBoundingClientRect() : null;
		const pickerTop = Math.min(
			selected ? selected.getBoundingClientRect().top : picker ? picker.getBoundingClientRect().top : innerHeight - 72,
			menu ? menu.top : Infinity
		);
		const controlsBottom = Math.max(12, innerHeight - pickerTop + 12);
		if (picker) root.style.setProperty('--ugolini-chat-bottom', `${controlsBottom.toFixed(2)}px`);
		placeFrame(document.querySelector('#min-widget > iframe'), controlsBottom);
		placeFrame(document.querySelector('#branding-widget > iframe'), controlsBottom);
		const maxFrame = document.querySelector('#max-widget > iframe');
		if (maxFrame) {
			if (matchMedia('(max-width: 767px)').matches) {
				for (const [property, value] of Object.entries({ position: 'fixed', inset: '0px', top: '0px', right: '0px', bottom: '0px', left: '0px', width: '100vw', 'min-width': '100vw', 'max-width': '100vw', height: '100dvh', 'min-height': '100dvh', 'max-height': '100dvh', 'border-radius': '0px' })) {
					if (maxFrame.style.getPropertyValue(property) !== value || maxFrame.style.getPropertyPriority(property) !== 'important') {
						maxFrame.style.setProperty(property, value, 'important');
					}
				}
			} else {
				for (const property of ['top', 'right', 'left', 'width', 'min-width', 'max-width', 'border-radius']) maxFrame.style.removeProperty(property);
				placeFrame(maxFrame, controlsBottom + 72);
				const height = `${Math.max(120, Math.min(520, Math.floor(pickerTop - 96)))}px`;
				for (const property of ['height', 'min-height', 'max-height']) {
					if (maxFrame.style.getPropertyValue(property) !== height || maxFrame.style.getPropertyPriority(property) !== 'important') {
						maxFrame.style.setProperty(property, height, 'important');
					}
				}
			}
		}
	};
	new MutationObserver(schedulePosition).observe(document.body, { childList: true, subtree: true });
	addEventListener('scroll', schedulePosition, { passive: true });
	addEventListener('resize', schedulePosition);
	window.visualViewport?.addEventListener('resize', schedulePosition);
	schedulePosition();

	const welcome = root.querySelector('.ugolini-chat__welcome');
	const launcher = root.querySelector('.ugolini-chat__launcher');
	const status = root.querySelector('.ugolini-chat__status');
	const statusText = root.querySelector('[data-chat-status]');
	let loading = false;
	let failed = false;
	let topic = '';
	let loadTimer = 0;

	const setBusy = busy => {
		loading = busy;
		for (const button of root.querySelectorAll('button')) button.disabled = busy;
		launcher.setAttribute('aria-busy', String(busy));
	};

	const openChat = () => {
		clearTimeout(loadTimer);
		failed = false;
		welcome?.remove();
		status.hidden = true;
		launcher.hidden = true;
		window.Tawk_API?.maximize?.();
		if (topic) window.Tawk_API?.addEvent?.('quick-consultation', { topic }, () => {});
	};

	const failChat = () => {
		clearTimeout(loadTimer);
		failed = true;
		setBusy(false);
		status.classList.add('is-error');
		statusText.textContent = copy.failed;
		status.hidden = false;
		launcher.hidden = false;
	};

	const loadChat = selectedTopic => {
		if (loading) return;
		if (failed) {
			window.open(config.chatUrl, '_blank', 'noopener,noreferrer');
			return;
		}
		topic = selectedTopic || '';
		if (window.Tawk_API?.maximize) {
			openChat();
			return;
		}

		welcome?.remove();
		status.classList.remove('is-error');
		statusText.textContent = copy.loading;
		status.hidden = false;
		launcher.hidden = true;
		setBusy(true);
		window.Tawk_API = window.Tawk_API || {};
		window.Tawk_LoadStart = new Date();
		window.Tawk_API.onLoad = () => {
			setBusy(false);
			openChat();
		};
		window.Tawk_API.onChatMaximized = () => { status.hidden = true; launcher.hidden = true; };
		window.Tawk_API.onChatMinimized = () => {
			window.Tawk_API.hideWidget?.();
			launcher.hidden = false;
		};
		window.Tawk_API.onChatHidden = () => { launcher.hidden = false; };

		const script = document.createElement('script');
		script.async = true;
		script.src = config.embedUrl;
		script.charset = 'UTF-8';
		script.crossOrigin = 'anonymous';
		script.onerror = failChat;
		loadTimer = window.setTimeout(failChat, 12000);
		document.head.append(script);
	};

	root.querySelector('.ugolini-chat__close')?.addEventListener('click', () => welcome.remove());
	for (const button of root.querySelectorAll('[data-chat-topic]')) {
		button.addEventListener('click', () => loadChat(button.dataset.chatTopic));
	}
	launcher.addEventListener('click', () => loadChat(''));
})();
