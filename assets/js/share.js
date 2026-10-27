(() => {
	const fallbackCopy = value => {
		const input = document.createElement('textarea');
		input.value = value;
		input.setAttribute('readonly', '');
		input.style.cssText = 'position:fixed;left:-9999px;top:0';
		document.body.append(input);
		input.select();
		const copied = document.execCommand('copy');
		input.remove();
		return copied;
	};
	const copy = value => navigator.clipboard && isSecureContext
		? navigator.clipboard.writeText(value).then(() => true, () => fallbackCopy(value))
		: Promise.resolve(fallbackCopy(value));

	for (const section of document.querySelectorAll('[data-share]')) {
		const url = new URL(section.dataset.shareUrl || location.href, location.href);
		url.hash = '';
		if (!section.dataset.shareUrl) url.searchParams.delete('v');
		const shareUrl = url.href;
		const title = document.querySelector('main h1')?.textContent?.trim() || document.title;
		const copyLabel = section.querySelector('[data-share-copy-label]');
		let resetLabel;
		const facebook = section.querySelector('[data-share-facebook]');
		const x = section.querySelector('[data-share-x]');
		facebook.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
		x.href = `https://x.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(title)}`;
		section.querySelector('[data-share-copy]').addEventListener('click', () => {
			copy(shareUrl).then(ok => {
				copyLabel.textContent = ok ? 'Copiato' : 'Riprova';
				clearTimeout(resetLabel);
				resetLabel = setTimeout(() => { copyLabel.textContent = 'Copia link'; }, 2000);
			});
		});
		section.querySelector('[data-share-instagram]').addEventListener('click', () => {
			copy(shareUrl);
		});
	}
})();
