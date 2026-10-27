import assert from 'node:assert/strict';

const origin = 'https://ugolinigroup.com';
const mobile = { 'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile Safari/604.1' };
const get = async path => {
	const response = await fetch(`${origin}${path}`, { headers: mobile });
	assert.equal(response.status, 200, path);
	return response.text();
};
const [shop, caviar, schrenkii, oscietra] = await Promise.all([
	get('/shop/'),
	get('/collections/caviareat/'),
	get('/products/caviale-schrenkii-imperial/'),
	get('/products/caviale-royal-oscietra/'),
]);
const fresh = `?verify=${Date.now()}`;
const [freshShop, about, sughi, home, b2b, pageSitemap] = await Promise.all([
	get(`/shop/${fresh}`),
	get(`/chi-siamo/${fresh}`),
	get(`/collections/sughi/${fresh}`),
	get(`/${fresh}`),
	get(`/b2b/${fresh}`),
	get(`/wp-sitemap-posts-page-1.xml${fresh}`),
]);
const cards = html => [...html.matchAll(/data-product-card[^>]*>/g)].map(match => match[0]);
assert.match(shop, /data-catalogue-complete/);
assert.ok(cards(shop).length >= 86, 'Shop must render the complete catalogue in HTML');
assert.equal(cards(caviar).filter(card => !/\shidden(?:\s|>)/.test(card)).length, 12);
assert.match(schrenkii, /"amount":98000[^}]*"option_1":"1000 gr"/);
assert.match(oscietra, /"amount":8000[^}]*"option_1":"100 gr"/);
assert.doesNotMatch(freshShop, /ugolini-home-collections/);
assert.match(about, /wp-content\/uploads\/2026\/10\/ugolini-family\.png/);
assert.match(about, /ugolini-timeline--about/);
assert.match(sughi, /ugolini-collection-editorial-section[\s\S]*ugolini-professional-assurances[\s\S]*ugolini-newsletter/);
assert.match(home, /ugolini-footer-layout[\s\S]*ugolini-footer-payments/);
assert.match(home, /aria-label="Visa"[\s\S]*aria-label="Alipay"/);
assert.match(home, /href="\/b2b\/#dalla-selezione-al-riordino"/);
assert.match(b2b, /id="dalla-selezione-al-riordino"/);
assert.equal([...pageSitemap.matchAll(/<loc>/g)].length, 8);
assert.doesNotMatch(pageSitemap, /\/(catalogo|editoriale|supporto|checkout|customer-dashboard)\//);
console.log('Mobile catalogue and Amazon-matched checkout variants verified.');
