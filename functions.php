<?php
/**
 * Ugolini Group theme setup.
 *
 * @package UgoliniGroup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable editor parity with the front end.
 */
function ugolini_group_setup() {
	load_theme_textdomain( 'ugolini-group', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style(
		array(
			'assets/css/base.css',
			'assets/css/header.css',
			'assets/css/home.css',
			'assets/css/pages.css',
			'assets/css/content.css',
			'assets/css/footer.css',
			'assets/css/responsive.css',
			'assets/css/surecart.css',
		)
	);
}
add_action( 'after_setup_theme', 'ugolini_group_setup' );

/**
 * Return the block markup for a bundled page pattern.
 *
 * @param string $file Pattern filename without the extension.
 * @return string
 */
function ugolini_group_page_pattern_content( $file ) {
	$path = get_theme_file_path( 'patterns/' . $file . '.php' );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	ob_start();
	include $path;
	return trim( (string) ob_get_clean() );
}

/**
 * Synchronize the static preview's production pages in WordPress once.
 * WordPress revisions retain the replaced page bodies for recovery.
 */
function ugolini_group_seed_preview_pages() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_pages_665_seeded' ) ) {
		return;
	}

	$pages = array(
		'chi-siamo' => array( 'Chi siamo', 'page-about', true ),
		'b2b'        => array( 'B2B / Wholesale', 'page-b2b', true ),
		'blog'       => array( 'Blog', 'page-blog', true ),
		'faq'        => array( 'FAQ', 'page-faq', true ),
		'contatti'   => array( 'Contatti', 'page-contact', true ),
		'catalogo'   => array( 'Catalogo', 'page-catalogue', true ),
		'supporto'   => array( 'Supporto e informazioni legali', 'page-legal', true ),
		'editoriale' => array( 'Pagina editoriale', 'page-editorial', true ),
		'events'     => array( 'Eventi', 'page-events', true ),
		'shop'       => array( 'Shop', 'page-shop', true ),
	);
	$created  = false;
	$complete = true;
	$page_ids = array();

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$page_ids[ $slug ] = (int) $existing->ID;
			$replace_content   = ! empty( $page[2] );
			if ( 'publish' !== $existing->post_status || $replace_content ) {
				$content = $replace_content ? ugolini_group_page_pattern_content( $page[1] ) : $existing->post_content;
				if ( $replace_content && '' === $content ) {
					$complete = false;
					continue;
				}
				$result = wp_update_post(
					array(
						'ID'          => $existing->ID,
						'post_title'  => $page[0],
						'post_status' => 'publish',
						'post_content' => $content,
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					$complete = false;
				} else {
					$created = true;
				}
			}
			continue;
		}

		$content = ugolini_group_page_pattern_content( $page[1] );
		if ( '' === $content ) {
			$complete = false;
			continue;
		}

		$result = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page[0],
				'post_name'    => $slug,
				'post_content' => $content,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			$complete = false;
		} else {
			$page_ids[ $slug ] = (int) $result;
			$created = true;
		}
	}

	$front_id      = (int) get_option( 'page_on_front' );
	$front_content = ugolini_group_page_pattern_content( 'homepage' );
	if ( ! $front_id && '' !== $front_content ) {
		$home = get_page_by_path( 'home', OBJECT, 'page' );
		if ( $home ) {
			$front_id = (int) $home->ID;
		} else {
			$result = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Home',
					'post_name'    => 'home',
					'post_content' => $front_content,
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				$complete = false;
			} else {
				$front_id = (int) $result;
				$created  = true;
			}
		}
		if ( $front_id ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front_id );
		}
	}
	if ( $front_id && '' !== $front_content ) {
		$result = wp_update_post(
			array(
				'ID'           => $front_id,
				'post_status'  => 'publish',
				'post_content' => $front_content,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			$complete = false;
		}
	}
	if ( isset( $page_ids['blog'] ) && (int) get_option( 'page_for_posts' ) === $page_ids['blog'] ) {
		update_option( 'page_for_posts', 0 );
	}

	if ( $created ) {
		flush_rewrite_rules( false );
	}
	if ( $complete ) {
		update_option( 'ugolini_group_pages_665_seeded', 1, false );
	}
}
add_action( 'init', 'ugolini_group_seed_preview_pages', 99 );

/** Keep the public Blog label and card excerpts aligned with the theme preview. */
function ugolini_group_refresh_blog_presentation() {
	if ( ! get_option( 'ugolini_group_blog_630_refreshed' ) ) {
		$blog = get_page_by_path( 'blog', OBJECT, 'page' );
		if ( $blog && 'Blog' !== $blog->post_title ) {
			wp_update_post( array( 'ID' => $blog->ID, 'post_title' => 'Blog' ) );
		}
		update_option( 'ugolini_group_blog_630_refreshed', 1, false );
	}
}
add_action( 'init', 'ugolini_group_refresh_blog_presentation', 100 );

/**
 * WordPress imported several manual excerpts as a few characters only. Build a
 * useful card preview from the article body while keeping the original post intact.
 *
 * @param string  $excerpt Existing excerpt.
 * @param WP_Post $post    Current post.
 * @return string
 */
function ugolini_group_blog_card_excerpt( $excerpt, $post ) {
	if ( is_admin() || ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return $excerpt;
	}

	$content = html_entity_decode( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post->ID ) ) ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
	return $content ? wp_trim_words( $content, 28, '…' ) : $excerpt;
}
add_filter( 'get_the_excerpt', 'ugolini_group_blog_card_excerpt', 10, 2 );

/** Force card blocks to use a real three-line article preview, not imported stubs. */
function ugolini_group_render_blog_excerpt( $block_content, $block ) {
	if ( is_admin() || ! preg_match( '/href=["\']([^"\']+)["\']/', $block_content, $match ) ) {
		return $block_content;
	}

	$post_id = url_to_postid( html_entity_decode( $match[1], ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ) );
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return $block_content;
	}

	$content = html_entity_decode( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
	$data    = json_decode( (string) file_get_contents( get_theme_file_path( 'data/blog-posts.json' ) ), true );
	$slug    = basename( untrailingslashit( (string) wp_parse_url( $match[1], PHP_URL_PATH ) ) );
	foreach ( is_array( $data ) ? $data : array() as $article ) {
		if ( isset( $article['slug'], $article['content'] ) && $slug === $article['slug'] ) {
			$content = html_entity_decode( wp_strip_all_tags( $article['content'] ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
			break;
		}
	}
	$excerpt = wp_trim_words( preg_replace( '/\s+/u', ' ', trim( $content ) ), 32, '…' );
	if ( ! $excerpt ) {
		return $block_content;
	}

	return '<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">' . esc_html( $excerpt ) . '</p><p class="wp-block-post-excerpt__more-text"><a class="wp-block-post-excerpt__more-link" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( 'Leggi di più →', 'ugolini-group' ) . '</a></p></div>';
}
add_filter( 'render_block_core/post-excerpt', 'ugolini_group_render_blog_excerpt', 10, 2 );

/** Keep the public byline identical to the curated local preview. */
function ugolini_group_render_post_author_name( $block_content ) {
	if ( is_admin() || 'post' !== get_post_type() ) {
		return $block_content;
	}

	return '<div class="wp-block-post-author-name">Ugolini Gourmet</div>';
}
add_filter( 'render_block_core/post-author-name', 'ugolini_group_render_post_author_name' );

/** Format dates in Italian even when the WordPress dashboard locale is different. */
function ugolini_group_render_post_date( $block_content ) {
	if ( is_admin() || 'post' !== get_post_type() ) {
		return $block_content;
	}

	$months = array( 1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre' );
	$time   = get_post_timestamp();
	$date   = wp_date( 'j', $time ) . ' ' . $months[ (int) wp_date( 'n', $time ) ] . ' ' . wp_date( 'Y', $time );
	return '<time class="wp-block-post-date" datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( $date ) . '</time>';
}
add_filter( 'render_block_core/post-date', 'ugolini_group_render_post_date' );

/**
 * Retire Site Editor copies from older theme versions so filesystem templates
 * remain the single source of truth. Trashed copies remain recoverable.
 */
function ugolini_group_reset_template_overrides() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_templates_665_reset' ) ) {
		return;
	}

	foreach ( array( 'wp_template', 'wp_template_part' ) as $post_type ) {
		$overrides = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'slug',
						'terms'    => get_stylesheet(),
					),
				),
			)
		);
		foreach ( $overrides as $override_id ) {
			wp_trash_post( $override_id );
		}
	}
	update_option( 'ugolini_group_templates_665_reset', 1, false );
}
add_action( 'wp_loaded', 'ugolini_group_reset_template_overrides', 5 );

/**
 * Copy a bundled recipe image into the media library and reuse it thereafter.
 *
 * @param string $filename Bundled image filename.
 * @return int Attachment ID, or zero on failure.
 */
function ugolini_group_import_recipe_image( $filename ) {
	$filename = sanitize_file_name( wp_basename( $filename ) );
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_ugolini_bundled_recipe_image',
			'meta_value'     => $filename,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = get_theme_file_path( 'assets/images/blog/' . $filename );
	if ( ! is_readable( $source ) ) {
		return 0;
	}

	$upload = wp_upload_bits( $filename, null, file_get_contents( $source ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype      = wp_check_filetype( $upload['file'] );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attachment_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_ugolini_bundled_recipe_image', $filename );
	return (int) $attachment_id;
}

/**
 * Publish the recipe library used by the local preview once per installation.
 */
function ugolini_group_seed_recipe_posts() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_posts_624_seeded' ) ) {
		return;
	}

	$data_file = get_theme_file_path( 'data/blog-posts.json' );
	$recipes    = is_readable( $data_file ) ? json_decode( file_get_contents( $data_file ), true ) : null;
	if ( ! is_array( $recipes ) || ! $recipes ) {
		return;
	}

	$complete = true;
	foreach ( $recipes as $recipe ) {
		if ( empty( $recipe['slug'] ) || empty( $recipe['title'] ) || empty( $recipe['content'] ) ) {
			$complete = false;
			continue;
		}

		$category_id = 0;
		if ( ! empty( $recipe['category'] ) ) {
			$term = term_exists( $recipe['category'], 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $recipe['category'], 'category' );
			}
			if ( ! is_wp_error( $term ) ) {
				$category_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}

		$content = str_replace(
			array( 'https://ugolinigourmet.it', 'https://ugolinigourmet.com' ),
			home_url(),
			wp_kses_post( $recipe['content'] )
		);
		$post = get_page_by_path( sanitize_title( $recipe['slug'] ), OBJECT, 'post' );
		if ( ! $post ) {
			$post_id = wp_insert_post(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'post_title'     => sanitize_text_field( $recipe['title'] ),
					'post_name'      => sanitize_title( $recipe['slug'] ),
					'post_content'   => $content,
					'post_excerpt'   => wp_trim_words( wp_strip_all_tags( $content ), 28 ),
					'post_date'      => ! empty( $recipe['date'] ) ? str_replace( 'T', ' ', substr( $recipe['date'], 0, 19 ) ) : current_time( 'mysql' ),
					'post_category'  => $category_id ? array( $category_id ) : array(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				$complete = false;
				continue;
			}
		} else {
			$post_id = (int) $post->ID;
			$result  = wp_update_post(
				array(
					'ID'             => $post_id,
					'post_status'    => 'publish',
					'post_title'     => sanitize_text_field( $recipe['title'] ),
					'post_content'   => $content,
					'post_excerpt'   => wp_trim_words( wp_strip_all_tags( $content ), 28 ),
					'post_date'      => ! empty( $recipe['date'] ) ? str_replace( 'T', ' ', substr( $recipe['date'], 0, 19 ) ) : $post->post_date,
					'post_category'  => $category_id ? array( $category_id ) : array(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				$complete = false;
			}
		}

		if ( ! has_post_thumbnail( $post_id ) && ! empty( $recipe['image'] ) ) {
			$image_id = ugolini_group_import_recipe_image( $recipe['image'] );
			if ( $image_id ) {
				set_post_thumbnail( $post_id, $image_id );
			} else {
				$complete = false;
			}
		}
	}

	if ( $complete ) {
		$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello && 'Hello world!' === $hello->post_title && false !== strpos( $hello->post_content, 'Welcome to WordPress' ) ) {
			wp_update_post( array( 'ID' => $hello->ID, 'post_status' => 'draft' ) );
		}
		update_option( 'ugolini_group_posts_624_seeded', 1, false );
	}
}
add_action( 'init', 'ugolini_group_seed_recipe_posts', 100 );

/** Read the product dataset that powers the local preview. */
function ugolini_group_bundled_products() {
	$path   = get_theme_file_path( 'data/products.csv' );
	$handle = is_readable( $path ) ? fopen( $path, 'r' ) : false;
	if ( ! $handle ) {
		return array();
	}

	$headers = fgetcsv( $handle );
	if ( $headers ) {
		$headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] );
	}
	$rows    = array();
	while ( $headers && ( $values = fgetcsv( $handle ) ) !== false ) {
		$values = array_pad( $values, count( $headers ), '' );
		$rows[] = array_combine( $headers, array_slice( $values, 0, count( $headers ) ) );
	}
	fclose( $handle );
	return $rows;
}

/** Return the first migrated WordPress media URL for each product slug. */
function ugolini_group_bundled_product_images() {
	$path   = get_theme_file_path( 'data/media-targets.csv' );
	$handle = is_readable( $path ) ? fopen( $path, 'r' ) : false;
	if ( ! $handle ) {
		return array();
	}

	$headers = fgetcsv( $handle );
	$images  = array();
	while ( $headers && ( $values = fgetcsv( $handle ) ) !== false ) {
		$values = array_pad( $values, count( $headers ), '' );
		$row    = array_combine( $headers, array_slice( $values, 0, count( $headers ) ) );
		$slug   = sanitize_title( $row['product_slug'] ?? '' );
		if ( $slug && ! isset( $images[ $slug ] ) && ! empty( $row['target_url'] ) ) {
			$images[ $slug ] = $row['target_url'];
		}
	}
	fclose( $handle );
	return $images;
}

/** Render the identical product-card markup used by the generated preview. */
function ugolini_group_preview_product_grid( $limit = 20 ) {
	$products = array_slice( ugolini_group_bundled_products(), 0, absint( $limit ) );
	$images   = ugolini_group_bundled_product_images();
	$html     = '';
	foreach ( $products as $product ) {
		$slug       = sanitize_title( $product['slug'] );
		$post       = get_page_by_path( $slug, OBJECT, 'sc_product' );
		$link       = $post ? get_permalink( $post ) : home_url( '/products/' . $slug . '/' );
		$image      = $images[ $slug ] ?? ( $post ? (string) get_the_post_thumbnail_url( $post, 'large' ) : '' );
		$price      = '' === trim( $product['current_price'] ) ? 'Prezzo su richiesta' : number_format( (float) $product['current_price'], 2, ',', '.' ) . ' €';
		$name       = $product['official_product_name'];
		$collection = $product['collections'];
		$html      .= '<article class="ugolini-product-card preview-product-card" data-product-card data-name="' . esc_attr( strtolower( $name ) ) . '" data-collections="' . esc_attr( strtolower( $collection ) ) . '" data-price="' . esc_attr( $product['current_price'] ) . '">';
		$html      .= '<a class="preview-product-media" href="' . esc_url( $link ) . '">' . ( $image ? '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">' : '<span aria-hidden="true"></span>' ) . '</a>';
		$html      .= '<p class="ugolini-product-card-meta">' . esc_html( $collection ) . '</p><h3><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></h3><p class="preview-product-price">' . esc_html( $price ) . '</p><a class="preview-quick-view" href="' . esc_url( $link ) . '">Vista prodotto</a></article>';
	}
	return '<div class="preview-product-grid">' . $html . '</div>';
}

/** Render the exact preview catalogue controls and every product card. */
function ugolini_group_preview_shop() {
	$collections = array(
		'marmellate'      => 'Marmellate',
		'olio-al-tartufo' => 'Olio al tartufo',
		'pesto'           => 'Pesto',
		'salse-funghi'    => 'Salse ai funghi',
		'salse-tartufo'   => 'Salse con tartufo',
		'sughi'           => 'Sughi',
		'caviareat'       => 'CaviarEat',
		'truffleat'       => 'Truffleat',
		'tin-caviar'      => 'Tin Caviar',
		'luxureat'        => 'LuxurEat',
	);
	$products = ugolini_group_bundled_products();
	$prices   = array_map( static fn( $product ) => (float) $product['current_price'], array_filter( $products, static fn( $product ) => '' !== trim( $product['current_price'] ) ) );
	$minimum  = $prices ? floor( min( $prices ) ) : 0;
	$maximum  = $prices ? ceil( max( $prices ) ) : 0;
	$tabs = '<button class="is-active" type="button" data-shop-collection="" data-shop-label="">Tutti i prodotti</button>';
	foreach ( $collections as $slug => $label ) {
		$tabs .= '<button type="button" data-shop-collection="' . esc_attr( $slug ) . '" data-shop-label="' . esc_attr( strtolower( $label ) ) . '">' . esc_html( $label ) . '</button>';
	}
	return '<div class="alignwide preview-shop" data-preview-shop><div class="preview-shop-bar"><div class="preview-shop-tabs">' . $tabs . '</div><div class="preview-shop-actions"><button type="button" data-filter-open>' . ugolini_group_icon( 'sliders-horizontal' ) . 'Filtri</button><label><span>Cerca</span><input type="search" data-shop-search placeholder="Cerca prodotti"></label><label><span>Ordina</span><select data-shop-sort><option value="featured">In evidenza</option><option value="az">Nome, A–Z</option><option value="za">Nome, Z–A</option><option value="low">Prezzo crescente</option><option value="high">Prezzo decrescente</option></select></label></div></div><dialog class="preview-filter-dialog" data-filter-dialog><form method="dialog"><button class="preview-filter-close" value="close" aria-label="Chiudi filtri">' . ugolini_group_icon( 'x' ) . '</button><h2>Filtri</h2><fieldset><legend>Catalogo</legend><label><input type="checkbox" checked disabled> Tutti i prodotti pubblicati</label></fieldset><fieldset class="preview-price-filter"><legend>Prezzo</legend><div class="preview-price-inputs"><label>Minimo € <input type="number" min="' . esc_attr( $minimum ) . '" max="' . esc_attr( $maximum ) . '" step="0.01" placeholder="' . esc_attr( $minimum ) . '" data-price-min></label><label>Massimo € <input type="number" min="' . esc_attr( $minimum ) . '" max="' . esc_attr( $maximum ) . '" step="0.01" placeholder="' . esc_attr( $maximum ) . '" data-price-max></label></div></fieldset><button type="button" data-filter-clear>Azzera filtri</button></form></dialog>' . ugolini_group_preview_product_grid( count( $products ) ) . '</div>';
}

/** Replace SureCart's version-dependent list wrapper with preview-identical cards. */
function ugolini_group_render_product_list_parity( $block_content, $block ) {
	$class = $block['attrs']['className'] ?? '';
	$limit = $block['attrs']['limit'] ?? ( $block['attrs']['query']['perPage'] ?? 8 );
	return false !== strpos( $class, 'ugolini-shop-list' ) ? ugolini_group_preview_shop() : '<div class="alignwide preview-product-list">' . ugolini_group_preview_product_grid( $limit ) . '</div>';
}
add_filter( 'render_block_surecart/product-list', 'ugolini_group_render_product_list_parity', 10, 2 );

/** Split imported descriptions at their original section headings. */
function ugolini_group_description_sections( $description ) {
	$sections = array( 'Descrizione' => array() );
	$label    = 'Descrizione';
	foreach ( preg_split( '/\R/', trim( $description ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( 0 === strpos( $line, '## ' ) ) {
			$label = trim( substr( $line, 3 ) );
			$sections[ $label ] = $sections[ $label ] ?? array();
		} else {
			$sections[ $label ][] = $line;
		}
	}
	return array_filter( $sections );
}

/** Build the same open product-fact list used by the static preview. */
function ugolini_group_product_facts_content( $product ) {
	$fields = array(
		'ingredients'                   => 'Ingredienti',
		'allergens'                     => 'Allergeni',
		'nutritional_information'       => 'Informazioni nutrizionali',
		'shelf_life'                    => 'Shelf life',
		'storage_instructions'          => 'Conservazione',
		'usage_instructions'            => 'Modalità di utilizzo',
		'primary_packaging'             => 'Imballo primario',
		'weight_format'                 => 'Formato',
		'certifications_dietary_claims' => 'Certificazioni e dichiarazioni',
		'organoleptic_characteristics'  => 'Caratteristiche organolettiche',
	);

	$html     = '<div class="preview-product-facts">';
	$rendered = array();
	foreach ( ugolini_group_description_sections( $product['full_description'] ?? '' ) as $label => $lines ) {
		$body = '';
		foreach ( $lines as $line ) {
			$body .= 0 === strpos( $line, '### ' )
				? '<h4>' . esc_html( trim( substr( $line, 4 ) ) ) . '</h4>'
				: '<p>' . esc_html( $line ) . '</p>';
		}
		$html      .= '<details class="ugolini-product-fact" open><summary>' . esc_html( $label ) . '</summary><div class="ugolini-product-fact__body">' . $body . '</div></details>';
		$rendered[] = sanitize_title( $label );
	}
	foreach ( $fields as $key => $label ) {
		if ( empty( $product[ $key ] ) || in_array( sanitize_title( $label ), $rendered, true ) ) {
			continue;
		}
		$html .= '<details class="ugolini-product-fact" open><summary>' . esc_html( $label ) . '</summary><p>' . nl2br( esc_html( $product[ $key ] ) ) . '</p></details>';
	}
	return '<!-- wp:html -->' . $html . '</div><!-- /wp:html -->';
}

/** Synchronize SureCart product order and factual detail content with preview. */
function ugolini_group_seed_product_details() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_products_730_seeded' ) ) {
		return;
	}

	$products = ugolini_group_bundled_products();
	if ( ! $products ) {
		return;
	}

	$complete = true;
	foreach ( $products as $index => $product ) {
		$post = get_page_by_path( sanitize_title( $product['slug'] ), OBJECT, 'sc_product' );
		if ( ! $post ) {
			$complete = false;
			continue;
		}
		$result = wp_update_post(
			array(
				'ID'           => $post->ID,
				'menu_order'   => $index,
				'post_content' => ugolini_group_product_facts_content( $product ),
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			$complete = false;
		}
	}

	if ( $complete ) {
		update_option( 'ugolini_group_products_730_seeded', 1, false );
	}
}
add_action( 'init', 'ugolini_group_seed_product_details', 101 );

/** Expand dynamic theme tokens even when a nested pattern saved them as text. */
function ugolini_group_expand_legacy_shortcodes( $content ) {
	$shortcodes = array(
		'[ugolini_collection_grid]'      => 'ugolini_group_collection_grid_shortcode',
		'[ugolini_collection_tabs]'      => 'ugolini_group_collection_tabs_shortcode',
		'[ugolini_collection_editorial]' => 'ugolini_group_collection_editorial_shortcode',
		'[ugolini_product_story]'        => 'ugolini_group_product_story_shortcode',
		'[ugolini_product_guide]'        => 'ugolini_group_product_guide_shortcode',
		'[ugolini_cooking_suggestions]'  => 'ugolini_group_cooking_suggestions_shortcode',
		'[ugolini_events context="home"]' => static fn() => ugolini_group_events_shortcode( array( 'context' => 'home' ) ),
		'[ugolini_events context="archive"]' => static fn() => ugolini_group_events_shortcode( array( 'context' => 'archive' ) ),
	);
	foreach ( $shortcodes as $token => $callback ) {
		if ( false !== strpos( $content, $token ) ) {
			$rendered = call_user_func( $callback );
			$content  = preg_replace( '#<p(?:\s[^>]*)?>\s*' . preg_quote( $token, '#' ) . '\s*</p>#', $rendered, $content );
			$content  = str_replace( $token, $rendered, $content );
		}
	}
	return $content;
}
add_filter( 'render_block', 'ugolini_group_expand_legacy_shortcodes', 99 );
add_filter( 'the_content', 'ugolini_group_expand_legacy_shortcodes', 99 );

/**
 * Load the theme stylesheet and the conservative SureCart layer when relevant.
 */
function ugolini_group_enqueue_assets() {
	$theme = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_style(
		'ugolini-group',
		get_stylesheet_uri(),
		array(),
		$version
	);

	$styles = array(
		'base'       => array( 'ugolini-group' ),
		'header'     => array( 'ugolini-group-base' ),
		'home'       => array( 'ugolini-group-base' ),
		'pages'      => array( 'ugolini-group-base' ),
		'content'    => array( 'ugolini-group-base' ),
		'footer'     => array( 'ugolini-group-base' ),
	);

	foreach ( $styles as $name => $dependencies ) {
		wp_enqueue_style(
			'ugolini-group-' . $name,
			get_theme_file_uri( 'assets/css/' . $name . '.css' ),
			$dependencies,
			$version
		);
	}

	/* This small layer is inert when SureCart is absent and keeps the editor
	 * ready while the independent store is configured. */
	wp_enqueue_style(
		'ugolini-group-surecart',
		get_theme_file_uri( 'assets/css/surecart.css' ),
		array( 'ugolini-group-footer' ),
		$version
	);
	wp_enqueue_style(
		'ugolini-group-responsive',
		get_theme_file_uri( 'assets/css/responsive.css' ),
		array( 'ugolini-group-surecart' ),
		$version
	);
	wp_enqueue_style(
		'ugolini-group-preview-parity',
		get_theme_file_uri( 'assets/css/preview-parity.css' ),
		array( 'ugolini-group-responsive' ),
		$version
	);
	wp_enqueue_script( 'ugolini-group-interactions', get_theme_file_uri( 'assets/js/theme.js' ), array(), $version, true );
	wp_enqueue_script( 'ugolini-group-catalogue-parity', get_theme_file_uri( 'assets/js/catalogue-parity.js' ), array(), $version, true );
}
add_action( 'wp_enqueue_scripts', 'ugolini_group_enqueue_assets' );

/**
 * Group bundled patterns in the block inserter.
 */
function ugolini_group_register_pattern_categories() {
	register_block_pattern_category(
		'ugolini-sections',
		array( 'label' => __( 'Ugolini sections', 'ugolini-group' ) )
	);

	register_block_pattern_category(
		'ugolini-pages',
		array( 'label' => __( 'Ugolini pages', 'ugolini-group' ) )
	);

	register_block_style( 'core/button', array( 'name' => 'outline-dark', 'label' => __( 'Outline dark', 'ugolini-group' ) ) );
	register_block_style( 'core/button', array( 'name' => 'outline-light', 'label' => __( 'Outline light', 'ugolini-group' ) ) );
}
add_action( 'init', 'ugolini_group_register_pattern_categories' );

/**
 * Return SureCart collection terms for live navigation and discovery.
 * Editors can preview draft-product relationships during this build phase.
 *
 * @return WP_Term[]
 */
function ugolini_group_get_collections() {
	if ( ! taxonomy_exists( 'sc_collection' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'sc_collection',
			'hide_empty' => ! current_user_can( 'edit_posts' ),
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Find a representative image from a product in a collection.
 *
 * @param WP_Term $term Collection term.
 * @return string
 */
function ugolini_group_collection_image( $term ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'sc_product',
			'post_status'            => current_user_can( 'edit_posts' ) ? array( 'publish', 'draft' ) : 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'sc_collection',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);

	return $query->posts ? (string) get_the_post_thumbnail_url( $query->posts[0], 'large' ) : '';
}

/** Return up to four images belonging to the requested SureCart collection. */
function ugolini_group_collection_images( $term, $limit = 4 ) {
	if ( ! $term instanceof WP_Term ) return array();
	$query = new WP_Query(
		array(
			'post_type' => 'sc_product', 'post_status' => current_user_can( 'edit_posts' ) ? array( 'publish', 'draft' ) : 'publish',
			'posts_per_page' => $limit, 'orderby' => 'menu_order', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true,
			'tax_query' => array( array( 'taxonomy' => 'sc_collection', 'field' => 'term_id', 'terms' => $term->term_id ) ),
		)
	);
	return array_values( array_filter( array_map( static fn( $id ) => (string) get_the_post_thumbnail_url( $id, 'large' ), $query->posts ) ) );
}

/** Count products related to a collection, including drafts for editors. */
function ugolini_group_collection_count( $term ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'sc_product',
			'post_status'    => current_user_can( 'edit_posts' ) ? array( 'publish', 'draft' ) : 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'sc_collection',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/** Render the desktop Shop mega menu from real SureCart collections. */
function ugolini_group_collection_menu_shortcode() {
	$terms = ugolini_group_get_collections();
	if ( ! $terms ) {
		return '<a class="ugolini-shop-direct" href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Shop', 'ugolini-group' ) . '</a>';
	}

	$items = '';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$count = ugolini_group_collection_count( $term );
		$items .= '<li><a href="' . esc_url( $link ) . '#ugolini-products"><span>' . esc_html( $term->name ) . '</span><small>' . esc_html( sprintf( _n( '%d prodotto', '%d prodotti', $count, 'ugolini-group' ), $count ) ) . '</small></a></li>';
	}

	return '<details class="ugolini-mega-menu"><summary><a href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Shop', 'ugolini-group' ) . '</a></summary><div class="ugolini-mega-panel"><div><p class="ugolini-eyebrow">' . esc_html__( 'Collezioni Ugolini', 'ugolini-group' ) . '</p><h2>' . esc_html__( 'Esplora il gusto italiano', 'ugolini-group' ) . '</h2><a class="ugolini-text-link" href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a></div><ul>' . $items . '</ul></div></details>';
}
add_shortcode( 'ugolini_collection_menu', 'ugolini_group_collection_menu_shortcode' );

/** Render image-led collection cards from real SureCart collection terms. */
function ugolini_group_collection_grid_shortcode() {
	$terms = ugolini_group_get_collections();
	if ( ! $terms ) {
		return '';
	}

	$cards = '';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$image = ugolini_group_collection_image( $term );
		$media = $image ? '<img src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async">' : '<span class="ugolini-collection-fallback" aria-hidden="true"></span>';
		$count = ugolini_group_collection_count( $term );
		$cards .= '<article class="ugolini-collection-card"><a href="' . esc_url( $link ) . '#ugolini-products"><span class="ugolini-collection-media">' . $media . '</span><span class="ugolini-collection-copy"><strong>' . esc_html( $term->name ) . '</strong><small>' . esc_html( sprintf( _n( '%d prodotto', '%d prodotti', $count, 'ugolini-group' ), $count ) ) . '</small></span></a></article>';
	}

	return '<div class="ugolini-collection-grid">' . $cards . '</div>';
}
add_shortcode( 'ugolini_collection_grid', 'ugolini_group_collection_grid_shortcode' );

/** Render compact collection navigation above the native SureCart filters. */
function ugolini_group_collection_tabs_shortcode() {
	$links   = '<a class="' . ( is_tax( 'sc_collection' ) ? '' : 'is-active' ) . '" href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a>';
	$current = get_queried_object_id();
	foreach ( ugolini_group_get_collections() as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$links .= '<a class="' . ( $current === $term->term_id ? 'is-active' : '' ) . '" href="' . esc_url( $link ) . '#ugolini-products">' . esc_html( $term->name ) . '</a>';
		}
	}

	return '<nav class="ugolini-shop-tabs" aria-label="' . esc_attr__( 'Filtra per collezione', 'ugolini-group' ) . '">' . $links . '</nav>';
}
add_shortcode( 'ugolini_collection_tabs', 'ugolini_group_collection_tabs_shortcode' );

/** Render an image-led, collapsible introduction for the current collection. */
function ugolini_group_collection_editorial_shortcode() {
	$copy = array(
		'marmellate'       => array(
			'Marmellate gastronomiche Ugolini Gourmet',
			'Note agrodolci pensate per accompagnare formaggi, salumi e carni.',
			'La collezione nasce dall’incontro tra ortaggi selezionati e ingredienti della tradizione italiana. La confit di cipolle rosse all’Aceto Balsamico di Modena IGP porta in tavola una consistenza morbida e un profilo agrodolce, ideale per creare contrasti equilibrati negli antipasti e nei secondi piatti.',
		),
		'olio-al-tartufo'   => array(
			'Oli al tartufo Ugolini Gourmet',
			'Il profumo del tartufo bianco e nero incontra l’olio extra vergine di oliva.',
			'Pensati per completare il piatto al momento del servizio, gli oli al tartufo Ugolini valorizzano pasta, risotti, uova, carne e verdure. Bastano poche gocce per aggiungere una nota aromatica riconoscibile, mantenendo semplice e immediato l’utilizzo in cucina.',
		),
		'pesto'             => array(
			'Pesti Ugolini Gourmet',
			'Ricette pronte per condire pasta, bruschette e preparazioni creative.',
			'Dal Pesto alla Genovese alle versioni rosse, vegane e al tartufo, la collezione riunisce interpretazioni diverse di uno dei condimenti più versatili della cucina italiana. Ogni vaso è pronto all’uso e può diventare condimento, farcitura o base per una ricetta.',
		),
		'salse-funghi'      => array(
			'Salse ai funghi Ugolini Gourmet',
			'Porcini e champignon in creme morbide, pronte per la cucina di ogni giorno.',
			'Le salse ai funghi sono pensate per risotti, pasta fresca, crostini e ripieni. La consistenza cremosa consente di utilizzarle direttamente oppure di stemperarle con panna o latte, adattandole con facilità alla preparazione desiderata.',
		),
		'salse-tartufo'     => array(
			'Specialità al tartufo Ugolini Gourmet',
			'Salse, creme e condimenti che portano il carattere del tartufo in tavola.',
			'La collezione comprende salsa tartufata, creme, pesto, ketchup, sugo al tartufo e tartufo estivo intero. Formati e ricette differenti permettono di completare pasta, riso, uova, carne, verdure e crostini con un ingrediente pronto all’uso e facile da dosare.',
		),
		'sughi'             => array(
			'Sughi Ugolini Gourmet',
			'Ricette italiane pronte per una tavola semplice, generosa e ricca di gusto.',
			'Dal ragù alla Bolognese alla versione vegana, dall’arrabbiata alla bruschetta piccantina e al sugo al tartufo, la collezione offre condimenti pronti per pasta, crostini e piatti conviviali. Una gamma costruita intorno al pomodoro italiano e a ricette immediatamente riconoscibili.',
		),
		'caviareat'         => array(
			'CaviarEat',
			'Caviale, specialità di mare e alternative vegetali in diversi formati.',
			'La selezione comprende caviali da storioni differenti, formati per la degustazione e per la ristorazione, acciughe sott’olio e caviale vegetale. Conservazione, disponibilità e modalità di servizio sono indicate nella singola scheda.',
		),
		'truffleat'         => array(
			'Truffleat',
			'Specialità al tartufo, condimenti e accessori per la cucina.',
			'Creme, salse, oli, pasta, riso e accessori compongono una gamma dedicata al tartufo. Ogni referenza mantiene ingredienti, formato e indicazioni d’uso propri, riportati nella scheda prodotto.',
		),
		'tin-caviar'        => array(
			'Tin Caviar',
			'Lattine professionali per il confezionamento di caviale e uova di pesce.',
			'Formati e quantità per cartone sono disponibili come varianti acquistabili. Consulta le combinazioni presenti nella scheda e seleziona quella adatta alle esigenze di confezionamento.',
		),
		'luxureat'          => array(
			'LuxurEat',
			'Caviale, tartare, burger gourmet, condimenti e specialità al tartufo.',
			'Una selezione dedicata alla tavola e alla ristorazione. Consulta formati, ingredienti, allergeni e conservazione nella scheda di ogni prodotto.',
		),
	);

	$term = is_tax( 'sc_collection' ) ? get_queried_object() : get_term_by( 'slug', 'salse-tartufo', 'sc_collection' );
	if ( ! $term instanceof WP_Term ) {
		$terms = ugolini_group_get_collections();
		$term  = $terms ? $terms[0] : null;
	}
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$content = $copy[ $term->slug ] ?? array(
		sprintf( __( 'Esplora %s', 'ugolini-group' ), $term->name ),
		wp_strip_all_tags( term_description( $term ) ),
		wp_strip_all_tags( term_description( $term ) ),
	);
	$image = ugolini_group_collection_image( $term );
	if ( ! $image ) {
		return '';
	}

	return '<section class="ugolini-collection-editorial alignwide"><div class="ugolini-collection-editorial__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async"><div class="ugolini-collection-editorial__content"><div class="ugolini-collection-editorial__title"><p class="ugolini-eyebrow">' . esc_html__( 'Collezione Ugolini Gourmet', 'ugolini-group' ) . '</p><h2>' . esc_html( $content[0] ) . '</h2></div><div class="ugolini-collection-editorial__intro"><p>' . esc_html( $content[1] ) . '</p><details open><summary>' . esc_html__( 'Scopri la collezione', 'ugolini-group' ) . '</summary><p>' . esc_html( $content[2] ) . '</p><p>' . esc_html__( 'Consulta ogni scheda per ingredienti, formati, modalità d’uso e certificazioni disponibili.', 'ugolini-group' ) . '</p></details></div></div></div></section>';
}
add_shortcode( 'ugolini_collection_editorial', 'ugolini_group_collection_editorial_shortcode' );

/** Minimal inline Lucide set: official 24px paths, no runtime dependency. */
function ugolini_group_icon( $name, $label = '' ) {
	$icons = array(
		'search'        => '<path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>',
		'user'          => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
		'shopping-bag'  => '<path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/>',
		'badge-check'   => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m16 9-5.5 5.5L8 12"/>',
		'package-check' => '<path d="M12 22V12"/><path d="m16 17 2 2 4-4"/><path d="M21 11.127V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.729l7 4a2 2 0 0 0 2 .001l1.32-.753"/><path d="M3.29 7 12 12l8.71-5"/>',
		'shield-check'  => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
		'arrow-up'      => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
		'x'             => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'sliders-horizontal' => '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
		'mail'           => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'phone'          => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92z"/>',
		'map-pin'        => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'calendar'       => '<path d="M8 2v4M16 2v4M3 10h18"/><rect width="18" height="18" x="3" y="4" rx="2"/>',
		'message-circle' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3 1.7-5.1A7 7 0 0 1 3 12V8a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>',
		'utensils'       => '<path d="M3 2v7c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
		'sparkles'       => '<path d="m12 3-1.9 5.1L5 10l5.1 1.9L12 17l1.9-5.1L19 10l-5.1-1.9Z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',
		'book-open'      => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V5a2 2 0 0 1 2-2h5a3 3 0 0 1 3 3v15a3 3 0 0 0-3-3Z"/><path d="M21 18a1 1 0 0 0 1-1V5a2 2 0 0 0-2-2h-5a3 3 0 0 0-3 3v15a3 3 0 0 1 3-3Z"/>',
		'tag'            => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42Z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) return '';
	return '<svg class="lucide lucide-' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' . ( $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"' ) . '>' . $icons[ $name ] . '</svg>';
}

/** Read and validate the single event data source shared by Home and Events. */
function ugolini_group_events_data() {
	static $events = null;
	if ( null !== $events ) return $events;
	$path = get_theme_file_path( 'data/events.json' );
	$data = is_readable( $path ) ? json_decode( file_get_contents( $path ), true ) : array();
	$events = array_values(
		array_filter(
			is_array( $data ) ? $data : array(),
			static function ( $event ) {
				return is_array( $event )
					&& ! empty( $event['id'] )
					&& ! empty( $event['title'] )
					&& preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event['startDate'] ?? '' )
					&& preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event['endDate'] ?? '' )
					&& $event['startDate'] <= $event['endDate'];
			}
		)
	);
	return $events;
}

/** Compare calendar dates only, using the WordPress site timezone for today. */
function ugolini_group_event_status( $event, $today = '' ) {
	$today = $today ?: wp_date( 'Y-m-d' );
	if ( $event['endDate'] < $today ) return 'past';
	if ( $event['startDate'] <= $today ) return 'current';
	return 'upcoming';
}

/** Format an event range without converting it through UTC. */
function ugolini_group_event_date_label( $start, $end ) {
	$months = array( 1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre' );
	$start_parts = array_map( 'intval', explode( '-', $start ) );
	$end_parts   = array_map( 'intval', explode( '-', $end ) );
	if ( $start_parts[0] === $end_parts[0] && $start_parts[1] === $end_parts[1] ) {
		return $start_parts[2] . '–' . $end_parts[2] . ' ' . $months[ $end_parts[1] ] . ' ' . $end_parts[0];
	}
	return $start_parts[2] . ' ' . $months[ $start_parts[1] ] . ' ' . $start_parts[0] . ' – ' . $end_parts[2] . ' ' . $months[ $end_parts[1] ] . ' ' . $end_parts[0];
}

/** Render one accessible event deck with image, details, thumbnails and controls. */
function ugolini_group_event_deck( $events, $heading, $home = false ) {
	if ( ! $events ) return '';
	$status_labels = array( 'current' => 'In corso', 'upcoming' => 'In programma', 'past' => 'Concluso' );
	$slides = '';
	$thumbs = '';
	foreach ( $events as $index => $event ) {
		$status = ugolini_group_event_status( $event );
		$image  = get_theme_file_uri( 'assets/images/events/' . $event['image'] );
		$link   = home_url( '/events/#' . sanitize_title( $event['id'] ) );
		$hidden = $index ? ' hidden' : '';
		$slides .= '<article id="' . esc_attr( $event['id'] ) . '" class="ugolini-event-slide" data-event-slide data-event-index="' . esc_attr( $index ) . '"' . $hidden . '>';
		$slides .= '<figure class="ugolini-event-media"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $event['alt'] ?? $event['title'] ) . '" loading="' . ( $index ? 'lazy' : 'eager' ) . '"></figure>';
		$slides .= '<div class="ugolini-event-copy"><p class="ugolini-eyebrow">' . esc_html( $status_labels[ $status ] ) . '</p><h3>' . esc_html( $event['title'] ) . '</h3>';
		$slides .= '<p class="ugolini-event-lead"><strong>' . esc_html( $event['lead'] ) . '</strong></p><p>' . esc_html( $event['description'] ) . '</p><p class="ugolini-event-highlight"><strong>' . esc_html( $event['highlight'] ) . '</strong></p>';
		$slides .= '<p class="ugolini-event-brands">' . esc_html( implode( ' · ', $event['brands'] ?? array() ) ) . '</p><div class="ugolini-event-meta"><span>' . ugolini_group_icon( 'map-pin' ) . esc_html( $event['location'] ) . '</span><span>' . ugolini_group_icon( 'calendar' ) . esc_html( ugolini_group_event_date_label( $event['startDate'], $event['endDate'] ) ) . '</span></div>';
		if ( $home ) $slides .= '<a class="ugolini-event-link" href="' . esc_url( $link ) . '">Scopri l’evento <span aria-hidden="true">→</span></a>';
		$slides .= '</div></article>';
		$thumbs .= '<button type="button" data-event-go="' . esc_attr( $index ) . '" aria-label="Mostra ' . esc_attr( $event['title'] . ', ' . implode( ', ', $event['brands'] ?? array() ) ) . '" aria-current="' . ( $index ? 'false' : 'true' ) . '"><img src="' . esc_url( $image ) . '" alt="" loading="lazy"></button>';
	}
	return '<section class="ugolini-events-group"><div class="ugolini-section-heading"><div><p class="ugolini-eyebrow">Eventi</p><h2>' . esc_html( $heading ) . '</h2></div></div><div class="ugolini-event-deck" data-event-deck><div class="ugolini-event-slides">' . $slides . '</div><nav class="ugolini-event-thumbs" aria-label="Seleziona evento">' . $thumbs . '</nav><div class="ugolini-event-controls"><button type="button" data-event-prev aria-label="Evento precedente">←</button><output aria-live="polite">1 / ' . count( $events ) . '</output><button type="button" data-event-next aria-label="Evento successivo">→</button></div></div></section>';
}

/** Render every event as an editorial card on the Events archive. */
function ugolini_group_event_list( $events, $heading, $id ) {
	$status_labels = array( 'current' => 'In corso', 'upcoming' => 'In programma', 'past' => 'Concluso' );
	$cards = '';
	foreach ( $events as $event ) {
		$status = ugolini_group_event_status( $event );
		$image  = get_theme_file_uri( 'assets/images/events/' . $event['image'] );
		$cards .= '<article id="' . esc_attr( $event['id'] ) . '" class="ugolini-event-card"><figure><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $event['alt'] ?? $event['title'] ) . '" loading="lazy"></figure><div><p class="ugolini-eyebrow">' . esc_html( $status_labels[ $status ] ) . '</p><h3>' . esc_html( $event['title'] ) . '</h3><p class="ugolini-event-lead"><strong>' . esc_html( $event['lead'] ) . '</strong></p><p>' . esc_html( $event['description'] ) . '</p><p class="ugolini-event-highlight"><strong>' . esc_html( $event['highlight'] ) . '</strong></p><p class="ugolini-event-brands">' . esc_html( implode( ' · ', $event['brands'] ?? array() ) ) . '</p><div class="ugolini-event-meta"><span>' . ugolini_group_icon( 'map-pin' ) . esc_html( $event['location'] ) . '</span><span>' . ugolini_group_icon( 'calendar' ) . esc_html( ugolini_group_event_date_label( $event['startDate'], $event['endDate'] ) ) . '</span></div></div></article>';
	}
	if ( ! $cards ) $cards = '<p class="ugolini-event-empty">Nessun evento in questa sezione.</p>';
	return '<section id="' . esc_attr( $id ) . '" class="ugolini-event-list-section"><div class="ugolini-section-heading"><div><p class="ugolini-eyebrow">Eventi</p><h2>' . esc_html( $heading ) . '</h2></div></div><div class="ugolini-event-list">' . $cards . '</div></section>';
}

/** Render active events on Home, and active plus past events on the archive. */
function ugolini_group_events_shortcode( $attributes = array() ) {
	$attributes = shortcode_atts( array( 'context' => 'home' ), $attributes, 'ugolini_events' );
	$today      = wp_date( 'Y-m-d' );
	$active     = array();
	$past       = array();
	foreach ( ugolini_group_events_data() as $event ) {
		if ( 'past' === ugolini_group_event_status( $event, $today ) ) $past[] = $event;
		else $active[] = $event;
	}
	usort( $active, static fn( $a, $b ) => strcmp( $b['startDate'], $a['startDate'] ) ?: strcmp( $b['endDate'], $a['endDate'] ) );
	usort( $past, static fn( $a, $b ) => strcmp( $b['endDate'], $a['endDate'] ) ?: strcmp( $b['startDate'], $a['startDate'] ) );
	if ( 'home' === $attributes['context'] ) {
		if ( ! $active ) return '';
		return '<section class="ugolini-home-events ugolini-section"><div class="alignwide">' . ugolini_group_event_deck( $active, 'Prossimi appuntamenti', true ) . '</div></section>';
	}
	$html = ugolini_group_event_list( $active, 'Prossimi eventi', 'sezione-prossimi-eventi' );
	$html .= ugolini_group_event_list( $past, 'Eventi passati', 'sezione-eventi-passati' );
	return '<div class="ugolini-events-archive ugolini-section"><div class="alignwide">' . $html . '</div></div>';
}
add_shortcode( 'ugolini_events', 'ugolini_group_events_shortcode' );

/** Collection-aware editorial and cooking blocks for product pages. */
function ugolini_group_product_story_shortcode() {
	$terms = is_singular() ? wp_get_post_terms( get_the_ID(), 'sc_collection' ) : array();
	$term  = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	$slug = $term instanceof WP_Term ? $term->slug : 'salse-tartufo';
	$link = $term instanceof WP_Term ? get_term_link( $term ) : home_url( '/shop/' );
	$copy = array(
		'pesto'           => array( 'Pesti Ugolini Gourmet', 'Ricette pronte per condire pasta, bruschette e preparazioni creative.', 'Dal pesto alla Genovese alle varianti rosse e vegetali, ogni referenza mantiene ingredienti, formati e indicazioni d’uso propri: consulta la scheda del prodotto prima del servizio.' ),
		'sughi'           => array( 'Sughi Ugolini Gourmet', 'Ricette italiane pronte per una tavola semplice, generosa e ricca di gusto.', 'La collezione comprende preparazioni al pomodoro, ragù e proposte dal profilo più deciso. Scalda dolcemente e completa il piatto seguendo sempre le indicazioni della singola referenza.' ),
		'marmellate'      => array( 'Marmellate gastronomiche Ugolini Gourmet', 'Note agrodolci pensate per accompagnare formaggi, salumi e carni.', 'Le diverse combinazioni di frutta e ortaggi aiutano a costruire contrasti equilibrati su taglieri, aperitivi e secondi piatti. Parti da una piccola quantità e regola l’abbinamento al gusto.' ),
		'olio-al-tartufo' => array( 'Oli al tartufo Ugolini Gourmet', 'Il profumo del tartufo bianco e nero incontra l’olio extra vergine di oliva.', 'Pensati come condimenti di finitura, valorizzano pasta, risotti, uova, carne e verdure. Bastano poche gocce; ingredienti e modalità di conservazione restano quelli riportati in etichetta.' ),
		'salse-funghi'    => array( 'Salse ai funghi Ugolini Gourmet', 'Porcini e champignon in creme morbide, pronte per la cucina di ogni giorno.', 'Servile su primi piatti, crostini, carne o verdure e regola la consistenza con moderazione. Ogni ricetta conserva il proprio profilo e le informazioni specifiche della confezione.' ),
		'salse-tartufo'   => array( 'Specialità al tartufo Ugolini Gourmet', 'Salse, creme e condimenti che portano il carattere del tartufo in tavola.', 'La collezione riunisce ricette e formati diversi per completare pasta, riso, uova, carne, verdure e crostini con un prodotto pronto all’uso e facile da dosare.' ),
		'caviareat'       => array( 'CaviarEat', 'Caviale e specialità selezionate per degustazione, ristorazione e occasioni speciali.', 'Scegli specie e formato dalla scheda prodotto. Per il caviale refrigerato, rispetta sempre temperature e tempi indicati dal produttore.' ),
		'truffleat'       => array( 'Truffleat', 'Una gamma di specialità al tartufo, condimenti e accessori dedicati alla cucina.', 'Formati e ricette cambiano per ogni referenza: consulta ingredienti, allergeni e indicazioni d’uso prima dell’acquisto.' ),
		'tin-caviar'      => array( 'Tin Caviar', 'Lattine professionali disponibili in quantità e formati differenti.', 'Seleziona nella scheda la combinazione tra pezzi per cartone e capacità della lattina.' ),
		'luxureat'        => array( 'LuxurEat', 'Caviale, tartare, burger gourmet, condimenti e specialità al tartufo.', 'Consulta formati, ingredienti, allergeni e conservazione nella scheda di ogni prodotto.' ),
	);
	$content = $copy[ $slug ] ?? $copy['salse-tartufo'];
	$images = $term instanceof WP_Term ? ugolini_group_collection_images( $term ) : array();
	if ( ! $images ) $images = array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8831-sugo-tartufo-nero-ugolini-gourmet-7.jpg' );
	$slides = '';
	foreach ( $images as $index => $image ) $slides .= '<img class="ugolini-carousel__slide' . ( 0 === $index ? ' is-active' : '' ) . '" src="' . esc_url( $image ) . '" alt="" loading="' . ( 0 === $index ? 'eager' : 'lazy' ) . '">';
	if ( is_wp_error( $link ) ) $link = home_url( '/shop/' );
	return '<section class="ugolini-product-story"><div class="ugolini-carousel ugolini-product-story__media" data-carousel>' . $slides . '</div><div class="ugolini-product-story__copy"><div><p class="ugolini-eyebrow">Continua a scoprire</p><h2>' . esc_html( $content[0] ) . '</h2></div><div><p>' . esc_html( $content[1] ) . '</p><p>' . esc_html( $content[2] ) . '</p><a href="' . esc_url( $link ) . '#ugolini-products">Vai alla collezione</a></div></div></section>';
}
add_shortcode( 'ugolini_product_story', 'ugolini_group_product_story_shortcode' );

/** Series-specific tasting and use guide. Product labels remain the authority. */
function ugolini_group_product_guide_shortcode() {
	$terms = is_singular() ? wp_get_post_terms( get_the_ID(), 'sc_collection' ) : array();
	$term  = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	$slug  = $term instanceof WP_Term ? $term->slug : 'salse-tartufo';
	$guides = array(
		'pesto' => array(
			array( 'utensils', 'Come servirlo', 'Usalo per condire la pasta oppure come finitura per bruschette, focacce e preparazioni creative. Aggiungilo fuori dal fuoco o a calore moderato per conservarne il profilo aromatico.' ),
			array( 'sparkles', 'Equilibrio nel piatto', 'Allungalo, se necessario, con poca acqua di cottura della pasta: aiuta a distribuire il condimento senza coprirne il gusto.' ),
			array( 'book-open', 'Tradizione ligure', 'Il pesto è tradizionalmente associato alla pasta e al minestrone genovese; ogni referenza Ugolini mantiene però ingredienti e caratteristiche proprie.' ),
			array( 'tag', 'Prima dell’uso', 'Controlla sempre in etichetta ingredienti, allergeni, quantità e indicazioni di conservazione della specifica referenza.' ),
		),
		'sughi' => array(
			array( 'utensils', 'Come servirli', 'Scalda dolcemente e abbina a pasta, gnocchi o cereali; le referenze più dense possono accompagnare anche crostini e piatti conviviali.' ),
			array( 'sparkles', 'Regolare la consistenza', 'Per la pasta, completa la preparazione in padella con poca acqua di cottura, così il sugo si lega in modo uniforme.' ),
			array( 'book-open', 'Una gamma italiana', 'La collezione comprende ricette riconoscibili, dal ragù alla Bolognese alle proposte vegane e piccanti, costruite intorno al pomodoro italiano.' ),
			array( 'tag', 'Prima dell’uso', 'Segui le condizioni di impiego e conservazione riportate sulla confezione; dopo l’apertura fa sempre fede l’etichetta del prodotto.' ),
		),
		'marmellate' => array(
			array( 'utensils', 'Abbinamenti salati', 'Servi in piccole quantità con formaggi, salumi o carni: la componente agrodolce crea contrasto senza sostituire il sapore principale.' ),
			array( 'sparkles', 'Costruire l’assaggio', 'Parti da una dose contenuta e aumenta gradualmente. Su un tagliere, prova prima il prodotto da solo e poi insieme all’accompagnamento.' ),
			array( 'book-open', 'Non solo colazione', 'Queste sono marmellate gastronomiche: la collezione è pensata anche per aperitivi, taglieri e cucina salata.' ),
			array( 'tag', 'Prima dell’uso', 'Verifica in etichetta ingredienti, allergeni e conservazione; usa sempre un utensile pulito per prelevare il prodotto.' ),
		),
		'olio-al-tartufo' => array(
			array( 'utensils', 'Usalo a finitura', 'Versane poche gocce sul piatto pronto: pasta, risotti, uova, patate e verdure accolgono bene una finitura aromatica.' ),
			array( 'sparkles', 'Dosaggio progressivo', 'Il profumo è protagonista: aggiungi il condimento poco alla volta e assaggia, per non coprire gli altri ingredienti.' ),
			array( 'book-open', 'Bianco e nero', 'Le referenze al tartufo bianco e nero hanno profili distinti. Scegli la bottiglia in base alla ricetta e alle informazioni riportate nella scheda.' ),
			array( 'tag', 'Luce e conservazione', 'Conserva secondo etichetta; per gli oli è buona pratica evitare luce e fonti di calore, che ne accelerano l’alterazione.' ),
		),
		'salse-funghi' => array(
			array( 'utensils', 'Come servirle', 'Scalda dolcemente e usa con pasta, risotti, polenta, carni o crostini. Le creme possono anche completare ripieni e fondi.' ),
			array( 'sparkles', 'Valorizzare la consistenza', 'Per un risultato più fluido, incorpora poco liquido caldo; per un crostino, mantieni invece la crema più compatta.' ),
			array( 'book-open', 'Porcini e champignon', 'La collezione riunisce creme a base di funghi con caratteri differenti: consulta nome e ingredienti della referenza prima dell’abbinamento.' ),
			array( 'tag', 'Prima dell’uso', 'Leggi sempre allergeni e modalità di conservazione in etichetta, soprattutto dopo l’apertura.' ),
		),
		'salse-tartufo' => array(
			array( 'utensils', 'Come servirle', 'Usa la salsa con pasta, risotti, uova, patate, carni o crostini. Un riscaldamento delicato evita di appiattire la componente aromatica.' ),
			array( 'sparkles', 'Dosare il tartufo', 'Inizia con una piccola quantità e completa dopo l’assaggio: il condimento deve sostenere, non coprire, la preparazione.' ),
			array( 'book-open', 'Salse e creme', 'La gamma comprende consistenze e ricette diverse; ingredienti, percentuali e certificazioni vanno verificati sulla singola scheda prodotto.' ),
			array( 'tag', 'Prima dell’uso', 'Rispetta le istruzioni riportate in etichetta per apertura, conservazione e durata; sono specifiche per ciascuna referenza.' ),
		),
		'caviareat' => array(
			array( 'utensils', 'Come servirlo', 'Servi il caviale alla temperatura indicata nella scheda, usando utensili che non ne alterino il profilo aromatico.' ),
			array( 'sparkles', 'Scegliere il formato', 'I formati più piccoli sono adatti alla degustazione; quelli maggiori rispondono alle esigenze della ristorazione e degli eventi.' ),
			array( 'book-open', 'Specie e gusto', 'Calibro, consistenza e profilo aromatico dipendono dalla specie: consulta le caratteristiche riportate per ogni caviale.' ),
			array( 'tag', 'Conservazione', 'Per i prodotti refrigerati, rispetta senza interruzioni la temperatura e i tempi indicati dal produttore.' ),
		),
		'truffleat' => array(
			array( 'utensils', 'Come utilizzarlo', 'Segui la modalità d’uso della singola referenza: salse, oli, pasta, riso e accessori richiedono impieghi differenti.' ),
			array( 'sparkles', 'Dosaggio progressivo', 'Per i condimenti al tartufo, parti da una quantità contenuta e regola dopo l’assaggio.' ),
			array( 'book-open', 'Una gamma completa', 'La collezione comprende alimenti e utensili: formato, materiali e caratteristiche restano quelli della scheda prodotto.' ),
			array( 'tag', 'Prima dell’uso', 'Verifica sempre ingredienti, allergeni, conservazione e certificazioni della specifica referenza.' ),
		),
		'tin-caviar' => array(
			array( 'utensils', 'Uso professionale', 'Le lattine sono destinate al confezionamento di caviale e uova di pesce.' ),
			array( 'sparkles', 'Quantità per cartone', 'Seleziona il numero di pezzi richiesto tra le combinazioni attualmente acquistabili.' ),
			array( 'book-open', 'Formato della lattina', 'Abbina la capacità della lattina alle esigenze di confezionamento indicate nella scheda.' ),
			array( 'tag', 'Prima dell’acquisto', 'Controlla entrambe le opzioni della variante: quantità per cartone e formato in grammi.' ),
		),
	);
	$items = $guides[ $slug ] ?? $guides['salse-tartufo'];
	$images = $term instanceof WP_Term ? ugolini_group_collection_images( $term ) : array();
	if ( ! $images ) $images = array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8831-sugo-tartufo-nero-ugolini-gourmet-7.jpg' );
	$html  = '';
	foreach ( $items as $index => $item ) {
		$html .= '<article class="ugolini-product-guide__item"><div><h3>' . ugolini_group_icon( $item[0] ) . esc_html( $item[1] ) . '</h3><p>' . esc_html( $item[2] ) . '</p></div><img src="' . esc_url( $images[ $index % count( $images ) ] ) . '" alt="" loading="lazy"></article>';
	}
	$image = $images[0];
	$style = $image ? ' style="--ugolini-sticky-image:url(\'' . esc_url( $image ) . '\')"' : '';
	return '<section class="ugolini-product-guide"><div class="alignwide"><div class="ugolini-product-guide__list">' . $html . '</div><div class="ugolini-product-guide__visual"' . $style . '><span>Guida alla collezione</span><h2>Assaggia, abbina, conosci</h2><p>Indicazioni pratiche per valorizzare questa famiglia di prodotti.</p></div></div></section>';
}
add_shortcode( 'ugolini_product_guide', 'ugolini_group_product_guide_shortcode' );

function ugolini_group_cooking_suggestions_shortcode() {
	$cards = array(
		array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8886-pesto-rosso-bio-ugolini-gourmet-9-scaled-1.jpg', 'Tagliatelle mediterranee', 'Pesto rosso biologico' ),
		array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8831-sugo-tartufo-nero-ugolini-gourmet-7.jpg', 'Pasta al tartufo', 'Sugo al tartufo nero' ),
		array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8800-pesto-alla-genovese-ugolini-gourmet-10.jpg', 'Crostini al pesto', 'Pesto alla Genovese' ),
	);
	$html = '';
	foreach ( $cards as $card ) $html .= '<article><img src="' . esc_url( $card[0] ) . '" alt="' . esc_attr( $card[1] ) . '" loading="lazy"><div><strong>' . esc_html( $card[1] ) . '</strong><span>Prodotto consigliato: ' . esc_html( $card[2] ) . '</span></div></article>';
	return '<section class="ugolini-cooking"><div class="ugolini-section-heading"><div><p class="ugolini-eyebrow">Consigli dalla cucina</p><h2>Idee da portare in tavola</h2></div></div><div class="ugolini-cooking-grid">' . $html . '</div></section>';
}
add_shortcode( 'ugolini_cooking_suggestions', 'ugolini_group_cooking_suggestions_shortcode' );

/** Render circular previous/next links so the first and last posts connect. */
function ugolini_group_post_navigation_shortcode() {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}

	$previous = get_next_post();
	$next     = get_previous_post();
	if ( ! $previous ) {
		$previous = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'post__not_in' => array( get_the_ID() ), 'orderby' => 'date', 'order' => 'ASC' ) )[0] ?? null;
	}
	if ( ! $next ) {
		$next = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'post__not_in' => array( get_the_ID() ), 'orderby' => 'date', 'order' => 'DESC' ) )[0] ?? null;
	}

	$link = static function ( $post, $label ) {
		return $post ? '<a href="' . esc_url( get_permalink( $post ) ) . '"><small>' . esc_html( $label ) . '</small><span>' . esc_html( get_the_title( $post ) ) . '</span></a>' : '<span></span>';
	};
	return '<nav class="ugolini-post-navigation alignwide" aria-label="' . esc_attr__( 'Navigazione articoli', 'ugolini-group' ) . '">' . $link( $previous, '← Articolo precedente' ) . $link( $next, 'Articolo successivo →' ) . '</nav>';
}
add_shortcode( 'ugolini_post_navigation', 'ugolini_group_post_navigation_shortcode' );

/** Render three recommendations without repeating the current article. */
function ugolini_group_related_posts_shortcode() {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'post__not_in'        => array( get_the_ID() ),
			'category__in'        => wp_get_post_categories( get_the_ID() ),
			'ignore_sticky_posts' => true,
		)
	);
	$posts = $query->posts;
	if ( count( $posts ) < 3 ) {
		$fallback = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 3 - count( $posts ),
				'post__not_in'   => array_merge( array( get_the_ID() ), wp_list_pluck( $posts, 'ID' ) ),
			)
		);
		$query->posts      = array_merge( $posts, $fallback );
		$query->post_count = count( $query->posts );
	}
	if ( ! $query->have_posts() ) {
		return '';
	}
	$cards = '';
	while ( $query->have_posts() ) {
		$query->the_post();
		$cards .= '<article class="ugolini-related-card"><a href="' . esc_url( get_permalink() ) . '"><span class="ugolini-related-media">' . get_the_post_thumbnail( get_the_ID(), 'large', array( 'loading' => 'lazy' ) ) . '</span><span class="ugolini-related-copy"><strong>' . esc_html( get_the_title() ) . '</strong><small>' . esc_html( get_the_date() ) . '</small></span></a></article>';
	}
	wp_reset_postdata();

	return '<div class="ugolini-related-grid">' . $cards . '</div>';
}
add_shortcode( 'ugolini_related_posts', 'ugolini_group_related_posts_shortcode' );

/** Render compact collection links in the footer. */
function ugolini_group_collection_links_shortcode() {
	$terms = ugolini_group_get_collections();
	$links = '<ul class="ugolini-footer-collection-links"><li><a href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a></li>';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$links .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a></li>';
		}
	}

	return $links . '</ul>';
}
add_shortcode( 'ugolini_collection_links', 'ugolini_group_collection_links_shortcode' );

/** Return compact live-search results from the same public content as search.php. */
function ugolini_group_live_search( WP_REST_Request $request ) {
	$search = sanitize_text_field( (string) $request->get_param( 's' ) );
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $search ) : strlen( $search );
	if ( $length < 2 ) {
		return rest_ensure_response( array() );
	}

	$query = new WP_Query(
		array(
			'post_type'           => array( 'sc_product', 'post', 'page' ),
			'post_status'         => 'publish',
			's'                   => $search,
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$labels = array( 'sc_product' => 'Prodotto', 'post' => 'Articolo', 'page' => 'Pagina' );
	$items  = array();
	foreach ( $query->posts as $post ) {
		$items[] = array(
			'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ),
			'url'   => get_permalink( $post ),
			'type'  => $labels[ $post->post_type ] ?? 'Risultato',
		);
	}

	return rest_ensure_response( $items );
}

/** Register the public, read-only endpoint used by the header search. */
function ugolini_group_register_live_search() {
	register_rest_route(
		'ugolini/v1',
		'/search',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ugolini_group_live_search',
			'permission_callback' => '__return_true',
			'args'                => array( 's' => array( 'sanitize_callback' => 'sanitize_text_field' ) ),
		)
	);
}
add_action( 'rest_api_init', 'ugolini_group_register_live_search' );

/** Render live WordPress search in an accessible editorial overlay. */
function ugolini_group_search_overlay_shortcode() {
	return '<div class="ugolini-search-action"><button class="ugolini-search-open" type="button" aria-expanded="false" aria-controls="ugolini-search-dialog">' . ugolini_group_icon( 'search' ) . '<span class="screen-reader-text">' . esc_html__( 'Apri ricerca', 'ugolini-group' ) . '</span></button>' .
		'<div id="ugolini-search-dialog" class="ugolini-search-dialog" role="dialog" aria-modal="true" aria-labelledby="ugolini-search-title" hidden><div class="ugolini-search-dialog-inner"><button class="ugolini-search-close" type="button">' . ugolini_group_icon( 'x' ) . '<span class="screen-reader-text">' . esc_html__( 'Chiudi ricerca', 'ugolini-group' ) . '</span></button><form action="' . esc_url( home_url( '/' ) ) . '" method="get" role="search" data-live-search data-search-endpoint="' . esc_url( rest_url( 'ugolini/v1/search' ) ) . '"><label id="ugolini-search-title" for="ugolini-search-input">' . esc_html__( 'Cerca prodotti, collezioni e ricette', 'ugolini-group' ) . '</label><div class="ugolini-search-row"><input id="ugolini-search-input" name="s" type="search" placeholder="' . esc_attr__( 'Cosa stai cercando?', 'ugolini-group' ) . '" autocomplete="off" aria-controls="ugolini-live-search-results"><button type="submit">' . ugolini_group_icon( 'search' ) . esc_html__( 'Cerca', 'ugolini-group' ) . '</button></div><div id="ugolini-live-search-results" class="ugolini-live-search" aria-live="polite" hidden></div></form></div></div></div>';
}
add_shortcode( 'ugolini_search_overlay', 'ugolini_group_search_overlay_shortcode' );

/** Include editorial content and SureCart products in the public site search. */
function ugolini_group_global_search( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
		$query->set( 'post_type', array( 'post', 'page', 'sc_product' ) );
	}
}
add_action( 'pre_get_posts', 'ugolini_group_global_search' );

/** Keep the public search heading Italian even when WordPress uses another admin locale. */
function ugolini_group_search_heading( $block_content ) {
	if ( is_admin() || ! is_search() ) {
		return $block_content;
	}

	return '<h1 class="wp-block-query-title alignwide">' . esc_html__( 'Risultati di ricerca per:', 'ugolini-group' ) . ' <span>“' . esc_html( get_search_query() ) . '”</span></h1>';
}
add_filter( 'render_block_core/query-title', 'ugolini_group_search_heading' );

/** Header account/cart links share the same Lucide visual language. */
function ugolini_group_header_commerce_shortcode() {
	$account = '<a class="ugolini-header-icon" href="' . esc_url( home_url( '/customer-dashboard/' ) ) . '" aria-label="' . esc_attr__( 'Account', 'ugolini-group' ) . '">' . ugolini_group_icon( 'user' ) . '</a>';
	if ( shortcode_exists( 'sc_cart_menu_icon' ) ) {
		$cart_markup = do_shortcode( '[sc_cart_menu_icon cart_icon="shopping-bag" cart_menu_always_shown=1]' );
		$cart_markup = preg_replace( '/<div(\s+class="sc-cart-icon"[^>]*)>/', '<span$1>', $cart_markup, 1 );
		$cart_markup = preg_replace( '#</div>\s*</a>#', '</span></a>', $cart_markup, 1 );
		$cart        = '<span class="ugolini-surecart-cart">' . preg_replace( '/>\s+</', '><', $cart_markup ) . '</span>';
	} else {
		$cart = '<a class="ugolini-header-icon" href="' . esc_url( home_url( '/checkout/' ) ) . '" aria-label="' . esc_attr__( 'Carrello', 'ugolini-group' ) . '">' . ugolini_group_icon( 'shopping-bag' ) . '</a>';
	}
	return '<div class="ugolini-commerce-actions">' . $account . $cart . '</div>';
}
add_shortcode( 'ugolini_header_commerce', 'ugolini_group_header_commerce_shortcode' );

/** Keep header shortcode markup out of the Shortcode block's wpautop pass. */
function ugolini_group_render_header_shortcode( $block_content, $block ) {
	$shortcode = trim( $block['innerHTML'] ?? '' );
	if ( '[ugolini_search_overlay]' === $shortcode ) {
		return ugolini_group_search_overlay_shortcode();
	}
	if ( '[ugolini_header_commerce]' === $shortcode ) {
		return ugolini_group_header_commerce_shortcode();
	}
	return $block_content;
}
add_filter( 'render_block_core/shortcode', 'ugolini_group_render_header_shortcode', 10, 2 );

/**
 * Render only business-approved reviews supplied by a site-level integration.
 * An empty array deliberately produces no production markup.
 */
function ugolini_group_verified_reviews_shortcode() {
	$reviews = apply_filters( 'ugolini_group_verified_reviews', array() );
	if ( ! is_array( $reviews ) || ! $reviews ) {
		return '';
	}

	$items = '';
	foreach ( $reviews as $review ) {
		if ( empty( $review['quote'] ) || empty( $review['name'] ) || empty( $review['source'] ) ) {
			continue;
		}
		$items .= '<figure class="ugolini-review-card"><blockquote>' . esc_html( $review['quote'] ) . '</blockquote><figcaption><strong>' . esc_html( $review['name'] ) . '</strong><span>' . esc_html( $review['source'] ) . '</span></figcaption></figure>';
	}

	return $items ? '<div class="ugolini-review-track" aria-label="' . esc_attr__( 'Recensioni verificate', 'ugolini-group' ) . '">' . $items . '</div>' : '';
}
add_shortcode( 'ugolini_verified_reviews', 'ugolini_group_verified_reviews_shortcode' );
