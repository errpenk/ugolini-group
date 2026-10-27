import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const theme = dirname(dirname(fileURLToPath(import.meta.url)));
const names = ['base', 'header', 'home', 'pages', 'gallery', 'content', 'footer', 'surecart', 'responsive', 'preview-parity', 'chat'];
const css = names.map(name => `/* ${name}.css */\n${readFileSync(join(theme, 'assets/css', `${name}.css`), 'utf8')}`).join('\n');
const styleFile = join(theme, 'style.css');
const header = readFileSync(styleFile, 'utf8').split('*/', 1)[0] + '*/\n';
// The source files live in assets/css; style.css is served from the theme root.
writeFileSync(styleFile, `${header}\n${css.replaceAll('../images/', 'assets/images/').replaceAll('../fonts/', 'assets/fonts/').trimEnd()}\n`);
