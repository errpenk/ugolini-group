import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const javascript = readFileSync(new URL('../assets/js/tawk-chat.js', import.meta.url), 'utf8');
const php = readFileSync(new URL('../functions.php', import.meta.url), 'utf8');
assert(javascript.includes("launcher.addEventListener('click', () => loadChat(''))"), 'Launcher must open chat on click.');
assert(javascript.includes("sessionStorage.getItem(storageKey)"), 'Missing session visit guard.');
assert(javascript.includes('const showWelcome = !wasShown'), 'Welcome must appear once per tab session, regardless of landing page.');
assert(javascript.includes("statusText.textContent = copy.loading"), 'Missing visible loading state.');
assert(javascript.includes("frame.style.setProperty('position', 'fixed', 'important')"), 'The desktop chat iframe must be positioned for the 12px gap to apply.');
assert(!javascript.includes('document.createElement(\'iframe\')') && !javascript.includes('document.write('), 'Tawk must not be nested in a second iframe.');
assert(javascript.indexOf('window.Tawk_API.onLoad') < javascript.indexOf('document.head.append(script)'), 'Tawk must open after its load callback is registered.');
assert(javascript.includes('window.setTimeout(failChat, 12000)'), 'Missing timeout fallback for stalled Safari loads.');
assert(php.includes("remove_action( 'wp_footer', array( $tawkto, 'print_embed_code' ) )"), 'The plugin auto-embed is still enabled.');
assert(!/preconnect|dns-prefetch/i.test(javascript + php), 'Tawk resource hints are forbidden.');

console.log('Tawk click gate checks passed.');
