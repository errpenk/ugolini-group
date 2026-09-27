# Ugolini Group block theme

Full Site Editing theme for the independent `ugolinigroup.com` WordPress and
SureCart store.

## Ownership boundaries

- SureCart owns products, prices, inventory, collections, cart and checkout.
- WordPress owns pages, posts, media, navigation and editable content.
- The theme renders the public presentation and reads SureCart's
  `sc_collection` taxonomy.
- Ugolini supplies the factual content and media. Urbani is used only as a
  visual reference; no Urbani copy, products, images or branding are included.

## Store behaviour

- Product lists, details, cart and checkout use the store's real EUR prices.
- GTranslate language selection is manual and never translates price nodes.
- Checkout text follows the visitor's system language for Italian and
  Simplified Chinese, with English as the fallback.
- Product stories, guides and serving ideas follow the product collection.
- GitHub pushes deploy through Deployer for Git, which clears LiteSpeed cache.

## WordPress setup

1. Activate the theme and keep the approved Ugolini logo as the Site Logo.
2. Retain SureCart-generated cart, checkout, customer dashboard and order
   confirmation content.
3. Configure inventory, taxes, shipping and payment processors in SureCart.
4. Test the complete purchase flow in SureCart test mode before launch.
5. Review legal pages, company details, consent, analytics and SEO metadata.

## Front-end structure

- `assets/css/base.css`: reset, controls, spacing and shared utilities
- `assets/css/header.css`: announcement, navigation and header actions
- `assets/css/home.css`: homepage and collection discovery
- `assets/css/pages.css`: content pages and product-detail sections
- `assets/css/content.css`: editorial and archive content
- `assets/css/footer.css`: footer layout and links
- `assets/css/responsive.css`: tablet and mobile layouts
- `assets/css/surecart.css`: supported SureCart block styling
- `assets/css/preview-parity.css`: fallback catalogue and preview markup
- `assets/js/theme.js`: shared interactions
- `assets/js/catalogue-parity.js`: fallback catalogue filtering and sorting

## Verification

Run the regression checks before deployment:

```sh
node tests/theme-regressions.mjs
```

The repository deliberately does not store payment credentials, live orders,
customer records or inventory state.
