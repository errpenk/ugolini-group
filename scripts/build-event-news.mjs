// Build the Italian Events news dataset from LuxurEat's published news centre.
// Run with: node ugolini-group/scripts/build-event-news.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sourceUrl = 'https://luxureat.cn/wp-content/themes/luxureat-wordpress-theme/assets/data/brand-news.js';
const response = await fetch(sourceUrl);
if (!response.ok) throw new Error(`LuxurEat news source: HTTP ${response.status}`);
const context = { window: {}, document: { currentScript: { src: sourceUrl } }, location: { href: 'https://luxureat.cn/brand/' }, URL };
runInNewContext(await response.text(), context, { timeout: 1000 });

// Italian translations follow the source's paragraph and section order. No new claims.
const it = {
  'sial-paris-2026-preview': {
    sourceUrl: 'https://luxureat.cn/news/sial-paris-2026-preview/',
    title: 'LuxurEat, Truffleat, Caviareat e Ugolini Gourmet insieme a SIAL Paris 2026',
    intro: 'Dal 17 al 21 ottobre 2026, quattro brand si incontreranno a Parigi per presentare tartufi, caviale e specialità gastronomiche italiane a SIAL Paris 2026.',
    opening: [],
    sections: [
      ['Quattro brand si incontrano a Parigi', ['Dal 17 al 21 ottobre 2026, LuxurEat, Truffleat, Caviareat e Ugolini Gourmet parteciperanno insieme a SIAL Paris 2026, presentando una selezione dedicata all’eccellenza gastronomica italiana nella Hall 1 – Regione Lazio.', 'SIAL Paris è uno dei punti d’incontro internazionali dell’industria alimentare. Riunisce produttori, distributori, buyer, importatori e professionisti della ristorazione da tutto il mondo. L’edizione 2026 si terrà a Paris Nord Villepinte. Sono attesi circa 8.000 espositori.']],
      ['Tartufi, caviale e gastronomia italiana', ['La partecipazione congiunta dei quattro brand riunisce diverse espressioni della gastronomia italiana attraverso ingredienti selezionati, prodotti gourmet e soluzioni per i mercati professionali e internazionali.', 'Truffleat presenterà tartufi e prodotti al tartufo, valorizzandone gli aromi e le applicazioni in cucina. Caviareat si concentrerà sul caviale e sull’alta gastronomia; LuxurEat proporrà una selezione premium che collega la cultura gastronomica italiana ai mercati internazionali. Ugolini Gourmet arricchirà la presentazione con la sua attenzione alla qualità e alle specialità italiane selezionate.', 'Quattro identità distinte si incontrano a Parigi, unite dall’attenzione alla qualità dei prodotti e alla cultura gastronomica italiana.']],
      ['Collegare la qualità italiana ai mercati internazionali', ['Partecipare a SIAL Paris è un’occasione per presentare i prodotti e creare nuove relazioni nell’industria alimentare internazionale.', 'Durante i cinque giorni della manifestazione, LuxurEat, Truffleat, Caviareat e Ugolini Gourmet incontreranno buyer, distributori, professionisti della ristorazione e potenziali partner per confrontarsi sulle esigenze dei diversi mercati ed esplorare nuove collaborazioni internazionali.', 'A partire dai prodotti, i quattro brand vogliono rafforzare il legame tra tradizione gastronomica italiana, esigenze dei consumatori contemporanei e opportunità commerciali internazionali.', 'SIAL Paris sarà uno spazio in cui potranno nascere nuove idee, collaborazioni e progetti futuri.']],
      ['L’eccellenza italiana incontra il futuro del food', ['SIAL Paris 2026 darà spazio all’innovazione e ai cambiamenti dell’industria alimentare globale, riunendo aziende affermate, brand emergenti e operatori del settore per esplorare nuovi prodotti, tecnologie e tendenze di consumo.', 'In questo contesto internazionale, LuxurEat, Truffleat, Caviareat e Ugolini Gourmet presenteranno la qualità e l’identità gastronomica italiana, dialogando con professionisti di paesi e mercati diversi.', 'Dai tartufi e dal caviale alle specialità gastronomiche italiane, ogni prodotto diventa un punto di partenza per esplorare nuove applicazioni, mercati e collaborazioni.']],
      ['Ci vediamo a Parigi', ['Dal 17 al 21 ottobre 2026, LuxurEat, Truffleat, Caviareat e Ugolini Gourmet incontreranno i professionisti del settore alimentare a SIAL Paris 2026, nella Hall 1 · Regione Lazio.', 'Cinque giorni in cui i sapori italiani incontreranno il mercato internazionale e apriranno nuove possibilità per prodotti, idee e relazioni commerciali.', 'SIAL Paris 2026', '17–21 ottobre 2026', 'Paris Nord Villepinte · Parigi, Francia', 'Hall 1 · Regione Lazio', 'LuxurEat · Truffleat · Caviareat · Ugolini Gourmet', 'Assapora l’eccellenza italiana. Ispira nuovi sapori, connessioni e opportunità.']],
    ],
  },
  'cifce-changsha-2026-spotlight': {
    title: 'Focus sull’espositore: TRUFFLE EAT S.R.L. porta LuxurEat a Changsha',
    intro: 'L’11ª China International Food & Catering Expo si terrà dal 18 al 20 settembre 2026 al Changsha International Convention and Exhibition Center. TRUFFLE EAT S.R.L. presenterà LuxurEat allo stand M10 del padiglione W2.',
    opening: ['La China International Food & Catering Expo è una manifestazione importante per il commercio e la distribuzione alimentare in Cina. Dalla prima edizione del 2016 ha continuato a crescere, rafforzando il suo profilo professionale, commerciale e internazionale.'],
    sections: [
      ['La fiera', ['L’11ª edizione si svolgerà dal 18 al 20 settembre 2026 al Changsha International Convention and Exhibition Center. Le aree professionali comprenderanno ristorazione, ingredienti, cereali e oli, caffè e bevande, preparazioni alimentari, prodotti importati e snack, condimenti, ristorazione intelligente, forniture alberghiere, attrezzature per produzione, confezionamento e cucina, franchising, agricoltura ecologica e prodotti trasformati.', 'La fiera occuperà circa 70.000 metri quadrati e ospiterà oltre 20 attività professionali, tra forum, concorsi, valutazioni e incontri commerciali. Sono attese più di 150.000 presenze.']],
      ['L’azienda', ['Azienda: TRUFFLE EAT S.R.L.', 'Stand: M10, padiglione W2.', 'LuxurEat coniuga rispetto per la cultura gastronomica tradizionale, apertura e innovazione. Qualità, carattere distintivo e tracciabilità guidano la selezione dei prodotti. Collaboriamo con partner qualificati che condividono i nostri valori, seguendo principi di approvvigionamento responsabile e standard di produzione rigorosi.']],
      ['I prodotti', ['LuxurEat affonda le proprie radici nella cultura gastronomica italiana, nello sviluppo dei prodotti e nella cura artigianale. La maggior parte delle specialità viene prodotta e confezionata in Italia. Caviale e altri ingredienti pregiati provengono da partner professionali selezionati in Italia e all’estero.', 'Questo approccio conserva l’autenticità dei sapori italiani e risponde alle esigenze dei mercati internazionali in termini di qualità e flessibilità della fornitura. Tra i prodotti presentati figurano olio al tartufo bianco, salsa al pomodoro e basilico, salsa al tartufo nero, salsa piccante al pomodoro, salsa ai funghi e pesto italiano.']],
    ],
  },
  'roma-bar-show-2026': {
    title: 'LuxurEat presenta TRUFFLEAT e CAVIAREAT al Roma Bar Show',
    intro: 'Il gruppo LuxurEat ha partecipato al Roma Bar Show con TRUFFLEAT e CAVIAREAT, esplorando nuove possibilità per gli ingredienti italiani di alta qualità nella mixology contemporanea e nell’ospitalità internazionale.',
    opening: [],
    sections: [
      ['Roma Bar Show', ['Il Roma Bar Show riunisce aziende, professionisti e nuove tendenze del settore beverage e mixology. Con il sostegno di ARSIAL e Regione Lazio, LuxurEat ha partecipato a una collettiva di 13 aziende laziali, dedicata alla qualità dei prodotti, alla sicurezza alimentare e a esperienze beverage più inclusive.']],
      ['TRUFFLEAT e CAVIAREAT', ['LuxurEat ha presentato TRUFFLEAT e CAVIAREAT, portando tartufo, caviale e altre specialità gastronomiche al dialogo con il mondo del beverage e della mixology. L’evento ha offerto l’occasione di esplorare nuove applicazioni nella ristorazione, nei bar e nell’ospitalità, incontrando professionisti e partner di diversi mercati.']],
      ['Produzione senza glutine e inclusività', ['Un tema centrale della partecipazione collettiva è stato quello dei prodotti senza glutine e dell’inclusività. L’incontro tra prodotti sicuri e certificati senza glutine e il settore internazionale del beverage mostra come qualità, gusto e sicurezza alimentare possano creare esperienze accessibili a più consumatori.', 'Per LuxurEat, il Roma Bar Show è stato una vetrina e un’occasione di collegamento tra l’eccellenza alimentare italiana e i mercati internazionali di beverage, ospitalità e ristorazione. Il gruppo continuerà a sviluppare la presenza internazionale di TRUFFLEAT e CAVIAREAT attraverso qualità, cultura e collaborazione professionale.']],
    ],
  },
  'marca-china-2026-recap': {
    title: 'Da Guangzhou a nuove esperienze gastronomiche globali: Truffleat, Luxureat e Ugolini a Marca China 2026',
    intro: 'L’8 e il 9 settembre il team di Truffleat Thailand ha partecipato a Marca China Private Label Fair 2026 a Guangzhou, riunendo quattro marchi del gruppo in un unico spazio espositivo.',
    opening: ['La fiera, ospitata al Guangzhou Poly World Trade Expo Centre, ha riunito alimentari, bevande, salute, bellezza, tecnologia, prodotti industriali e innovazioni. Truffleat, Caviareat, Luxureat e Ugolini hanno presentato insieme l’ampiezza dell’offerta del gruppo.'],
    sections: [
      ['Che cos’è Marca China e perché è importante?', ['Marca China Private Label Fair è collegata a Marca by BolognaFiere, una delle principali fiere professionali europee dedicate alla marca del distributore. La manifestazione di Guangzhou estende questa rete, mettendo in contatto produttori di qualità con acquirenti asiatici e internazionali.', 'Giunta alla quinta edizione, Marca China riunisce oltre 500 stand di prodotti alimentari e non alimentari. Attira supermercati, distribuzione moderna, piattaforme di e-commerce, MCN e operatori di hotel, ristoranti e del più ampio settore HoReCa.']],
      ['Che cosa significano HoReCa e MCN?', ['HO indica hotel, RE ristorante e CA café o catering. Una MCN è una rete o società intermediaria che gestisce e supporta gruppi di creatori di contenuti e influencer, soprattutto sulle piattaforme social.']],
      ['Due giorni di incontri', ['Partecipare a una fiera internazionale di queste dimensioni significa più che allestire uno stand e vendere prodotti: è un’opportunità per costruire relazioni commerciali con acquirenti e distributori di tutto il mondo.', 'Il nostro stand ha accolto molti visitatori interessati a tartufi freschi, caviale, salse, oli e pasta. Con loro abbiamo discusso anche delle possibilità di portare i prodotti Truffleat in Cina e nei mercati vicini.']],
      ['Quattro marchi, un unico stand', ['Riunire Truffleat, Caviareat, Luxureat e Ugolini è stata una scelta precisa: presentare il gruppo come partner completo per prodotti di alta qualità in diverse categorie, dagli ingredienti rari alle proposte lifestyle di fascia alta.']],
      ['Cosa abbiamo imparato dal mercato cinese', ['L’esperienza diretta ci ha mostrato quanto consumatori e acquirenti cinesi attribuiscano crescente importanza alla trasparenza sull’origine dei prodotti e alla storia del marchio. È un approccio coerente con l’attenzione di Truffleat per la qualità e la competenza specialistica sul tartufo.']],
      ['Il prossimo passo di Truffleat', ['La partecipazione a Marca China Private Label Fair 2026 rappresenta un’altra tappa e l’inizio di nuove opportunità di collaborazione. Ringraziamo tutte le persone che hanno visitato lo stand, scoperto i prodotti e parlato con il nostro team.', 'Per informazioni sui prodotti o su possibili collaborazioni, è possibile contattarci tramite Luxureat.cn, Xiaohongshu (LuxurEat) o Douyin (露意膳（上海）贸易有限公司).']],
    ],
  },
  'marca-china-2026-countdown': {
    title: 'Mancano sette giorni a Marca China: scopri i prodotti protagonisti',
    intro: 'La Guangzhou International Private Label Fair riunisce espositori dei settori alimentare, bellezza, casa e prodotti per animali, offrendo nuove idee di selezione a rivenditori, marchi, e-commerce e buyer.',
    opening: ['Dalle bevande al cocco senza zucchero, kombucha e polpette di manzo di Chaoshan all’olio extravergine di oliva e alla pizza surgelata; dalla cura dei capelli ai sieri alla vitamina C e alla cosmesi profumata, fino ai prodotti per la pulizia e per gli animali: si prepara una rassegna dedicata a qualità, innovazione e nuove tendenze di consumo.'],
    sections: [
      ['Categorie diverse e filiere di qualità', ['La fiera riunisce espositori dell’alimentare, della bellezza e cura della persona, della casa, dei prodotti per animali e di altri settori, presentando novità, specialità e prodotti popolari con potenziale di mercato.', 'Dall’innovazione della filiera cinese alla qualità e ai sapori del Padiglione Italia, l’offerta propone nuove idee a rivenditori, marchi, e-commerce e buyer. È un’occasione per scoprire prodotti, individuare possibili bestseller, incontrare fornitori e valutare collaborazioni.']],
      ['Le salse LuxurEat', ['LuxurEat unisce il rispetto per la cultura gastronomica tradizionale a un approccio aperto e innovativo, selezionando prodotti per qualità, carattere distintivo e tracciabilità. La fiera è un’occasione per scoprirli in anteprima.']],
    ],
  },
  'marca-china-2026-italian-pavilion': {
    title: 'Il Padiglione Italia debutta a Marca China 2026 con dieci aziende',
    intro: 'L’Agenzia ICE parteciperà per la prima volta con un Padiglione Italia ufficiale, portando dieci aziende a Marca China 2026. LuxurEat sarà allo stand 1D04.',
    opening: ['Marca China 2026 si terrà l’8 e il 9 settembre nel padiglione 1 del Poly World Trade Center Expo di Guangzhou. Sono previsti 10.000 metri quadrati di esposizione, oltre 300 produttori e più di 10.000 buyer professionali.'],
    sections: [
      ['Il Padiglione Italia', ['Una delle novità è la prima partecipazione dell’Agenzia ICE con un Padiglione Italia ufficiale di dieci aziende. I prodotti comprenderanno olio extravergine di oliva, caffè di qualità, pizza italiana, salse, formaggi, specialità al tartufo, snack da forno, conserve vegetali, piatti pronti e prodotti per la pulizia della casa.', 'Le aziende italiane intendono rafforzare i rapporti con rivenditori e distributori cinesi ed esplorare soluzioni personalizzate per il mercato locale.']],
      ['LuxurEat nel Padiglione Italia', ['Stand: 1D04.', 'Azienda: LUXUREAT.', 'LuxurEat combina il rispetto per la cultura gastronomica tradizionale con apertura e innovazione. Selezioniamo i prodotti in base a qualità, unicità e tracciabilità, collaborando con partner qualificati che condividono i nostri valori e seguono principi di approvvigionamento responsabile e standard produttivi rigorosi.']],
      ['I prodotti principali', ['LuxurEat nasce dalla cultura gastronomica italiana, dallo sviluppo dei prodotti e dalla cura artigianale. Molte specialità sono prodotte e confezionate in Italia; caviale e altri ingredienti pregiati provengono da partner selezionati in Italia e all’estero, con attenzione al gusto autentico, alla qualità e alla flessibilità della fornitura.']],
    ],
  },
  'marca-china-2026-taste-of-italy': {
    title: 'Il gusto d’Italia a Marca China: tradizione e innovazione nel private label',
    intro: 'Quando la lunga tradizione gastronomica italiana incontra l’evoluzione della domanda globale, nasce un dialogo tra sapore, qualità e innovazione.',
    opening: ['Dall’olio mediterraneo alle salse tradizionali, dalla pizza surgelata al caffè, ai latticini e alle specialità alimentari: il Padiglione Italia riunirà aziende capaci di presentare sapori autentici, cura artigianale e moderne capacità produttive.'],
    sections: [
      ['Dalle ricette tradizionali alla produzione moderna', ['Dalla selezione degli ingredienti all’origine alle soluzioni personalizzate per la marca del distributore, il Padiglione Italia porterà specialità classiche e competenze di sviluppo e filiera per i mercati internazionali, creando opportunità per rivenditori, marchi e buyer professionali.']],
      ['LuxurEat', ['LuxurEat coniuga rispetto per la cultura gastronomica tradizionale e apertura all’innovazione, scegliendo prodotti per qualità, sapori distintivi e tracciabilità.', 'L’azienda collabora con partner affidabili che condividono i suoi valori e seguono principi di approvvigionamento responsabile e standard produttivi rigorosi, per offrire sapori tradizionali, qualità affidabile ed esperienze innovative.']],
      ['I prodotti', ['Le salse LuxurEat affondano le radici nella cultura gastronomica italiana, nello sviluppo dei prodotti e nella cura artigianale. Molte specialità vengono prodotte e confezionate in Italia, mentre caviale e altri ingredienti pregiati provengono da partner selezionati in Italia e all’estero.', 'Luogo: Poly World Trade Center Expo, Guangzhou. Date: 8–9 settembre 2026.']],
      ['Marca China', ['Marca China 2026 è una fiera professionale dedicata al private label e alla collaborazione nelle filiere OEM/ODM. Si prevedono 10.000 metri quadrati espositivi, oltre 300 produttori e più di 10.000 visitatori professionali nei settori alimentare, casa, bellezza, animali e benessere.']],
    ],
  },
  'cicc-member-luxureat-2025': {
    title: 'La Camera di Commercio Italiana in Cina presenta LuxurEat',
    intro: 'Fondata da Roberto Ugolini, LuxurEat unisce oltre 25 anni di esperienza nei mercati asiatici a una profonda tradizione gastronomica italiana.',
    opening: ['Originario di Roma, Ugolini ha lavorato per decenni in Asia, soprattutto in Thailandia, costruendo tra consumatori e operatori la fiducia nell’autenticità e nella qualità dei prodotti gastronomici italiani.'],
    sections: [
      ['Tradizione italiana e innovazione di prodotto', ['LuxurEat si dedica alla selezione, produzione e distribuzione di specialità gastronomiche italiane di fascia alta: tartufi, caviale, salse, oli, condimenti e altri prodotti selezionati. I prodotti sono realizzati in Italia, con attenzione alla tradizione, alla cura artigianale e all’innovazione.', 'Ugolini collabora strettamente con la famiglia nello sviluppo dei prodotti, esplorando nuove proposte, comprese opzioni Kosher e adatte a consumatori vegani, per rispondere all’evoluzione delle preferenze alimentari.']],
      ['Un ponte tra Italia e mercati globali', ['Grazie all’esperienza nel commercio alimentare internazionale e nell’ospitalità, Roberto Ugolini ha costruito relazioni con chef, distributori e importatori in Asia e in Europa. Le sue competenze nello sviluppo dei mercati, nella logistica e nel posizionamento premium aiutano LuxurEat a collegare l’eccellenza italiana ai consumatori gastronomici internazionali.', 'LuxurEat si rivolge a chef, consumatori attenti alla qualità, importatori e ristorazione di alto livello. Il catalogo comprende tartufi freschi e conservati, oli e salse al tartufo, condimenti artigianali, caviale vegano, linee Kosher e conserve italiane, con attenzione al gusto, alla tracciabilità e all’autenticità.']],
      ['Un ponte tra tradizione e mercato', ['LuxurEat è più di un marchio: collega la tradizione culinaria italiana ai mercati esteri del food di alta gamma. L’esperienza nella regione Asia-Pacifico, in particolare in Thailandia, offre conoscenze di mercato ai partner interessati a importazione, distribuzione e collaborazioni.', 'LuxurEat sta inoltre preparando l’ingresso nel mercato cinese, portando tartufi, caviale e specialità gastronomiche a nuovi consumatori interessati ai sapori italiani autentici.']],
    ],
  },
};

const source = context.window.LUXUREAT_BRAND_NEWS;
if (!Array.isArray(source) || source.length !== Object.keys(it).length) throw new Error('News source count changed; review translations before importing.');
const news = source.map((item) => {
  const translation = it[item.id];
  if (!translation || translation.sections.length !== item.en.sections.length || translation.opening.length !== item.en.opening.length) throw new Error(`Translation out of sync: ${item.id}`);
  return {
    kind: 'news', id: item.id, date: item.date, author: item.en.author,
    title: translation.title, intro: translation.intro, opening: translation.opening,
    category: 'Notizie', sourceUrl: translation.sourceUrl || item.en.sourceUrl || 'https://luxureat.cn/brand/#news-center',
    cardImage: item.cardImage, video: item.video || null, videoPoster: item.videoPoster || null,
    sections: translation.sections.map(([heading, paragraphs], index) => ({
      heading, paragraphs,
      media: (item.en.sections[index][2] || []).map((entry) => entry.type === 'video' ? { type: 'video' } : { type: 'image', src: entry.src, alt: entry.alt?.en || '' }),
    })),
  };
});
const destination = resolve(root, 'data/events.json');
const original = JSON.parse(readFileSync(destination, 'utf8')).filter((entry) => entry.kind !== 'news');
writeFileSync(destination, JSON.stringify([...original, ...news], null, 2) + '\n');
console.log(`Prepared ${news.length} Italian news stories from LuxurEat.`);
