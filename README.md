# Ugolini Group block theme — version 6

Uploadable Full Site Editing theme for the independent `ugolinigroup.com`
WordPress + SureCart store. It contains no product records, prices, inventory,
payments or checkout logic.

## Data boundary

- SureCart owns products, prices, purchase state, collections, cart and checkout.
- WordPress owns pages, posts, media, navigation and editable page content.
- The theme reads the real `sc_collection` taxonomy for collection navigation.
- Native SureCart 4.6.6 blocks render the catalogue, filters, product detail,
  purchasing controls, cart and customer entry points.
- The old Ugolini site is never modified or used as an image hotlink.

## WordPress setup after upload

1. Activate the theme and assign the approved Ugolini logo as Site Logo.
2. On the first administrator visit, the theme synchronizes Home, Chi siamo,
   B2B, Catalogo, Ricette, FAQ, Contatti, Supporto, Editoriale and Shop from the
   bundled preview patterns. WordPress revisions retain the replaced page bodies.
3. The theme sets or creates Home as the static front page when none is assigned.
   It keeps Ricette as a normal page because its bundled Query block supplies the
   recipe archive layout.
4. Retain the SureCart-generated cart, checkout, customer dashboard and order
   confirmation content. If their slugs differ from the bundled template names,
   assign the matching template in the page editor.
5. Upload the approved catalogue PDF to the new Media Library and replace the
   current contact CTA with its local attachment URL.
6. Connect a consent-aware contact/newsletter service only after privacy mapping
   and legal approval. The theme currently uses transparent mail links and does
   not simulate data collection.
7. Review legal entity details, legal pages, social profiles, SEO metadata,
   analytics and consent configuration before launch.

## SureCart setup before launch

1. Publish the 20 migrated products when the public catalogue is ready.
2. Confirm the six populated collection terms are synced into WordPress.
3. Preview product and collection templates while logged in as an editor.
4. Configure inventory, taxes, shipping and payment processors only in the new
   store admin; none are changed by this theme.
5. Test cart, checkout, confirmation and customer dashboard in SureCart test
   mode before any product is published.

## CSS structure

- `base.css`: reset, controls, spacing and shared utilities
- `header.css`: announcement, navigation, mega menu and header actions
- `home.css`: homepage sections and collection discovery
- `pages.css`: content pages, product detail and transactional shells
- `content.css`: editorial/archive content
- `footer.css`: footer hierarchy and links
- `responsive.css`: tablet/mobile layouts
- `surecart.css`: supported SureCart variables and public block hosts

Version 6 standardizes a 1440px container with 64px desktop, 32px tablet,
20px mobile and 16px compact-mobile gutters. Product typography and spacing
are deliberately subordinate to imagery. Product cards and gallery thumbnails
follow the audited 3:2 source-image ratio, and the native SureCart gallery
enables thumbnails and lightbox.
The B2B campaign uses the
already migrated Ugolini white-truffle olive-oil image from the new Media
Library. The header/footer Site Logo remains editable in the Site Editor.

V6 adds a native WordPress search overlay, a factual FAQ page/preview, native
Details blocks for product facts, and a verified-review component that renders
nothing until a site-level integration supplies a quote, name and provenance.
No unverified review appears in production.

V6.2 adds a full-bleed interactive Ugolini collection showcase, a three-card
Shop/FAQ/Blog discovery section, and one shared image-led hero treatment for
Chi siamo, FAQ and Catalogo. Image-overlay controls retain explicit contrast in
their default, hover and keyboard-focus states.

V6.3 adds native SureCart collection navigation, search, filtering and sorting;
normalizes one- and multi-image product thumbnail sizing; reorders the homepage
editorial sections; expands the FAQ with open-by-default accordions; and unifies
the image-led page heroes. The Blog archive and single-post template keep all
editorial navigation inside the new WordPress site. Original Ugolini articles
can be imported as unpublished drafts with the separate dry-run-first migration
helper.

V6.4 gives single Blog posts an image-led, bottom-fading hero, native previous
and next links, and two same-category recommendations. Contact now uses a strict
16:9 catalogue banner followed by a mail-client form treatment. Shop and each
SureCart collection archive end with a full-width 16:9 editorial panel whose
collapsible copy is specific to the active Ugolini product series.

V6.5 aligns the Shop controls, makes collection cards square and connects every
collection link to its selected catalogue view. The preview price filter accepts
typed values and synchronized range controls without decorative dividers.
Product facts open by default. Shop and legal pages use image-led heroes, while
Blog posts add outlined hero titles, circular previous/next navigation and three
centered recommendations.

V6.8 adds the Urbani-style product discovery rail, centered quality and
newsletter bands, and the square-gallery product detail layout while keeping
all public content and imagery exclusive to Ugolini.

V6.9 makes the homepage references literal: full-bleed discovery cards with
overlapping thumbnails, vertical culinary collections, Ugolini testimonials,
a larger newsletter form, a scroll-linked company timeline and square CTA cards.

V6.10 displays every homepage collection without horizontal scrolling, restores
the compact deal-card design, moves testimonials below Newsletter with working
arrow navigation, and adopts the clone's exact Newsletter dimensions.

V6.11 expands the carousel to ten Ugolini texts with two-up desktop paging,
reuses it after related products on every product page, and restores full-image
deal cards with non-overlapping circular thumbnails.

V6.12 adds Lucide UI icons, stable collection jump positioning, a global
back-to-top control, square product cards, sticky product details,
collection-aware product stories, cooking suggestions and the revised contact form.

V6.13 restores the full-image product rail below the collection list, keeps the
header and desktop product gallery fixed while scrolling, aligns cooking content,
adds pill collection navigation, full-bleed 16:9 collection stories and a 16:9
overlay-style B2B campaign. All three header actions remain visible on mobile.

V6.14 moves that rail below “I più amati”, prevents the circular thumbnail from
colliding with product text, adds the official Ugolini story sequence, rebuilds
the B2B page around a full-width wholesale layout and enlarges the contact form
to the approved reference proportions.

V6.15 moves the official story exclusively into Scopri, connects it to four
products, adds multiply-blended Ugolini image layers across the B2B cards and
gallery, and uses a slimmer 2:1 closing campaign on FAQ and B2B pages.

V6.16 restores legibility to the B2B image overlays, moves the product rail
below the Scopri story products, makes closing campaigns full-width at 5:2 and
matches the official article header's image-to-white editorial transition.

V6.17 removes the duplicated Scopri product quartet and moves the editorial
product rail directly below the brand story with an even image/white split.
Recipe cards now reproduce the official Ugolini hierarchy with category,
author, date, excerpt and read-more link; article heroes use a continuous image
mask instead of a hard lower edge. B2B adds a numbered professional-service
band inspired by wholesale-page rhythm, enlarges value thumbnails and restores
the dark card photography. The contact form returns to a narrower editorial
measure.

V6.18 keeps long product galleries fixed by scrolling their thumbnail track
independently, matches homepage product-media backgrounds to the page, and
shortens recipe heroes. B2B replaces the numbered boxes with native accordions,
adds an Ugolini-sourced professional benefits panel and inquiry form, enlarges
circular product images and improves card-photo visibility. Contact aligns a
new commercial image with the WhatsApp action, while Scopri tightens the story
rhythm and joins its white manifesto directly to the product rail.

V6.19 places Search, Account and Cart in three equal header columns on desktop
and mobile. The cart quantity badge is anchored inside the cart link, so it
keeps the same position relative to the shopping-bag icon while the header moves.

V6.20 integrates each collection's introduction and open details directly into
its 16:9 photographic stage. The contact columns use compact 16px text spacing;
email, telephone and address remain between Parliamo and the bottom-aligned
WhatsApp action, while the commercial image keeps the same baseline.

V6.21 materializes the production pages represented by the static preview.
On the first administrator request it publishes only missing Chi siamo, B2B,
Catalogo, Ricette, FAQ and Contatti pages from the bundled block patterns. It
does not overwrite existing pages or modify SureCart-owned transactional pages.

V6.22 runs that one-time materialization on any request made by a logged-in
administrator, including the immediate front-end visit after a same-theme ZIP
replacement, and publishes matching pages that already exist as drafts.

V6.23 sizes the actual SureCart list items in editorial product rails, removes
the core content-width constraint from FAQ sections and restores the bundled
wordmark when no custom logo is assigned. It replaces the default Shop page
body once with the complete preview layout and imports the preview's 12 recipe
posts plus eight local featured images on the first administrator request.
Existing same-slug article content is preserved.

V6.24 makes the generated preview and WordPress use the same theme source and
bundled content datasets. On the first administrator request it synchronizes
all production page bodies, the 12 recipe posts and the factual detail panels
for all 20 SureCart products. Site Editor template copies from older versions
are moved to the WordPress trash so current filesystem templates win. Legacy
shortcodes accidentally saved as paragraphs receive a rendering fallback, and
the live catalogue and product rails use the same card markup, controls and CSS
as the generated preview. Product-card links still open the real SureCart
product pages, where purchasing remains native. If no static front page exists,
the same pass creates and assigns the bundled Home.

V6.25 normalizes WordPress block spacing against the generated preview, expands
dynamic Ugolini tokens that survive nested pattern rendering, restores the dark
fallback wordmark, applies responsive rules after SureCart and fixes related
article media to the same 4:3 cards used by the local preview.

V6.25.1 resolves header and footer parts from the active theme directory, so
Git-based installs work regardless of the repository folder name.

V6.36 equalizes recipe-card rows, aligns the first links in every footer
column, makes FAQ and product-detail imagery reveal in exact step with the
section's sticky scroll, restores the quantity selector to the left between
product copy and purchase buttons, and removes excess product-rail card height.

V6.37 restores touch scrolling and complete mobile copy, normalizes B2B,
catalogue, contact, footer and purchase spacing, adds the article-hero liquid
blur treatment, and tightens the mobile product gallery while removing the
collection strip above product titles.

V6.38 replaces the article blur with a white lower-two-thirds mask, locks Blog
cards to a shared top edge, restores the black Ugolini wordmark in the footer,
centres the mobile header, and fixes the live SureCart gallery and compact
contact layout through tablet widths.

V6.50 fixes WordPress submenus in subdirectory installations by matching menu labels, follows the native mobile-menu open state at every breakpoint, and sizes the current-page underline to its text.

V6.49 attaches breadcrumbs to the measured header edge, adds hover/focus page-section menus on desktop, mobile accordion submenus, and synchronized section breadcrumbs.

V6.48 increases the synchronized B2B story image band and stage, adds a mobile current-page underline, and introduces responsive left-aligned breadcrumbs that stay hidden over the transparent hero header.

V6.47 expands all seven B2B service-story stages with factual lead and detail
copy, adds a synchronized full-width narrow image strip below the progress
axis, and keeps the shared scroll state responsible for text, marker and image.

V6.46 removes the remaining Chi siamo gap between the fixed-background review
showcase and the white Perché scegliere Ugolini section.

V6.45 further compacts the desktop B2B scroll story and keeps its title on one
line, strengthens catalogue responsiveness on narrow screens, removes the
Shop editorial gap, vertically centres the homepage quality section, replaces
the homepage split story with the existing full-width two-column carousel, and
adds the current-state underline to Shop while retaining natural menu scaling.

V6.44 restores the black B2B scroll story with a tighter title, active step and
gold progress axis; restyles the professional Lucide assurances as a white,
three-column editorial strip; keeps the current navigation underline still and
animates it only after page navigation; normalizes Shop and Home navigation
type, adds restrained menu hover scaling, preserves black mobile-drawer text at
the top of overlay pages, and fixes the Contact commercial showcase background.

V6.43 simplifies carousel pagination to white pills, adds a seven-step sticky
B2B scroll story with a gold progress axis, introduces professional assurance
cards, adds animated current-page navigation, and equalizes all image heroes
with the homepage. V6.42 keeps the fixed-background review showcase only on Chi siamo with two
cards, aligns catalogue and closing stories as explicit two-column layouts,
standardizes outlined calls to action and carousel markers, adds commercial
request cards to Contact, and turns the seven B2B service notes into a paced
scroll sequence. V6.41 adds the fixed-background review showcase, page-specific closing
carousels, editorial two-column carousel copy, and a transparent hero header.
V6.40 adds native image carousels with pill pagination, always-visible product
guides with circular series imagery, a scroll-animated company statistics band,
cleaner B2B benefit rows, and normalized single-article typography. V6.39
rebuilds Contact as a centered vertical flow with Lucide contact cues,
removes the commercial image and mobile drawer tail rule, renames Scopri to Chi
siamo, expands the supplied company story, and adds a collection-aware tasting
guide with four accessible accordions to every product page.

## Source boundary

Ugolini Gourmet supplies factual content and media. Urbani is used only as a
visual reference for premium ecommerce hierarchy and rhythm. No Urbani copy,
products, imagery, branding or claims are included.
