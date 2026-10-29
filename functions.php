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
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_pages_662_seeded' ) ) {
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
		update_option( 'ugolini_group_pages_662_seeded', 1, false );
	}
}
add_action( 'init', 'ugolini_group_seed_preview_pages', 99 );

/** Create or refresh the gallery page without touching other pages. */
function ugolini_group_seed_gallery_page() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_gallery_page_v18_seeded' ) ) return;
	$content = ugolini_group_page_pattern_content( 'page-gallery' );
	if ( ! $content ) return;
	$page = get_page_by_path( 'galleria', OBJECT, 'page' );
	if ( $page ) {
		if ( ! str_contains( $page->post_content, 'data-company-gallery' ) ) return;
		$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
	} else {
		$result = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Galleria aziendale', 'post_name' => 'galleria', 'post_content' => $content ), true );
	}
	if ( ! is_wp_error( $result ) ) update_option( 'ugolini_group_gallery_page_v18_seeded', 1, false );
}
add_action( 'init', 'ugolini_group_seed_gallery_page', 100 );


/** Refresh only Chi siamo for the v6.64 family-image update. */
function ugolini_group_refresh_about_664() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_about_664_refreshed' ) ) {
		return;
	}
	$page    = get_page_by_path( 'chi-siamo', OBJECT, 'page' );
	$content = ugolini_group_page_pattern_content( 'page-about' );
	if ( $page && $content ) {
		$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
		if ( ! is_wp_error( $result ) ) {
			update_option( 'ugolini_group_about_664_refreshed', 1, false );
		}
	}
}
add_action( 'init', 'ugolini_group_refresh_about_664', 100 );

/** Refresh Home once for the v6.65.55 image update. */
function ugolini_group_refresh_home_6655() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_home_6655_refreshed' ) ) {
		return;
	}
	$page_id = (int) get_option( 'page_on_front' );
	$content = ugolini_group_page_pattern_content( 'homepage' );
	if ( $page_id && $content ) {
		$result = wp_update_post( array( 'ID' => $page_id, 'post_content' => $content ), true );
		if ( ! is_wp_error( $result ) ) {
			update_option( 'ugolini_group_home_6655_refreshed', 1, false );
		}
	}
}
add_action( 'init', 'ugolini_group_refresh_home_6655', 100 );

/** Publish the current support and policy content while retaining WordPress revisions. */
function ugolini_group_refresh_support_66573() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_support_66573_refreshed' ) ) {
		return;
	}
	$pages = array(
		'supporto'             => array( 'Supporto e informazioni legali', 'page-legal' ),
		'privacy-policy'       => array( 'Privacy policy', 'page-privacy' ),
		'cookie-policy'        => array( 'Cookie policy', 'page-cookie' ),
		'termini-e-condizioni' => array( 'Termini e condizioni', 'page-terms' ),
	);
	foreach ( $pages as $slug => $page ) {
		$content = ugolini_group_page_pattern_content( $page[1] );
		if ( ! $content ) {
			return;
		}
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$fields = array( 'post_title' => $page[0], 'post_name' => $slug, 'post_status' => 'publish', 'post_content' => $content, 'post_type' => 'page' );
		if ( $existing ) {
			$fields['ID'] = $existing->ID;
			$result = wp_update_post( $fields, true );
		} else {
			$result = wp_insert_post( $fields, true );
		}
		if ( is_wp_error( $result ) ) {
			return;
		}
	}
	flush_rewrite_rules( false );
	update_option( 'ugolini_group_support_66573_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_support_66573', 101 );

/** Publish the three editorial closing carousels, retaining page revisions. */
function ugolini_group_refresh_context_carousels_66574() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_context_carousels_66574_refreshed' ) ) return;
	foreach ( array( 'blog' => 'page-blog', 'events' => 'page-events', 'b2b' => 'page-b2b' ) as $slug => $pattern ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$content = ugolini_group_page_pattern_content( $pattern );
		if ( ! $page || ! $content ) return;
		$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
		if ( is_wp_error( $result ) ) return;
	}
	update_option( 'ugolini_group_context_carousels_66574_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_context_carousels_66574', 102 );

/** Refresh the published B2B copy once, retaining the previous WordPress revision. */
function ugolini_group_refresh_b2b_66586() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_b2b_66586_refreshed' ) ) return;
	$page = get_page_by_path( 'b2b', OBJECT, 'page' );
	$content = ugolini_group_page_pattern_content( 'page-b2b' );
	if ( ! $page || ! $content ) return;
	$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
	if ( ! is_wp_error( $result ) ) update_option( 'ugolini_group_b2b_66586_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_b2b_66586', 103 );

/** Publish the customer and commercial support layout with a recoverable page revision. */
function ugolini_group_refresh_support_66587() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_support_66587_refreshed' ) ) return;
	$page = get_page_by_path( 'supporto', OBJECT, 'page' );
	$content = ugolini_group_page_pattern_content( 'page-legal' );
	if ( ! $page || ! $content ) return;
	$result = wp_update_post( array( 'ID' => $page->ID, 'post_title' => 'Supporto', 'post_content' => $content ), true );
	if ( ! is_wp_error( $result ) ) update_option( 'ugolini_group_support_66587_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_support_66587', 104 );

/** Publish the revised first screen, B2B copy and Supporto layout with page revisions. */
function ugolini_group_refresh_pages_66588() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_pages_66588_refreshed' ) ) return;
	$pages = array(
		array( (int) get_option( 'page_on_front' ), 'homepage' ),
		array( (int) ( get_page_by_path( 'b2b', OBJECT, 'page' )->ID ?? 0 ), 'page-b2b' ),
		array( (int) ( get_page_by_path( 'supporto', OBJECT, 'page' )->ID ?? 0 ), 'page-legal' ),
	);
	foreach ( $pages as $page ) {
		$content = ugolini_group_page_pattern_content( $page[1] );
		if ( ! $page[0] || ! $content ) return;
		$result = wp_update_post( array( 'ID' => $page[0], 'post_content' => $content ), true );
		if ( is_wp_error( $result ) ) return;
	}
	update_option( 'ugolini_group_pages_66588_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_pages_66588', 105 );

/** Add the closing home carousel to the published contact page once. */
function ugolini_group_refresh_contact_carousel_66576() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_contact_carousel_66576_refreshed' ) ) return;
	$page = get_page_by_path( 'contatti', OBJECT, 'page' );
	if ( ! $page ) return;
	if ( ! str_contains( $page->post_content, 'ugolini-group/contact-context-cta' ) ) {
		$content = rtrim( $page->post_content ) . "\n\n" . '<!-- wp:pattern {"slug":"ugolini-group/contact-context-cta"} /-->';
		$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
		if ( is_wp_error( $result ) ) return;
	}
	update_option( 'ugolini_group_contact_carousel_66576_refreshed', 1, false );
}
add_action( 'init', 'ugolini_group_refresh_contact_carousel_66576', 102 );

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
add_action( 'init', 'ugolini_group_refresh_blog_presentation', 101 );

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
	if ( is_admin() || ! in_array( get_post_type(), array( 'post', 'ugolini_news' ), true ) ) {
		return $block_content;
	}

	$author = 'ugolini_news' === get_post_type() ? get_post_meta( get_the_ID(), '_ugolini_news_author', true ) : 'Ugolini Gourmet';
	return '<div class="wp-block-post-author-name">' . esc_html( $author ?: 'LuxurEat' ) . '</div>';
}
add_filter( 'render_block_core/post-author-name', 'ugolini_group_render_post_author_name' );

/** Format dates in Italian even when the WordPress dashboard locale is different. */
function ugolini_group_render_post_date( $block_content ) {
	if ( is_admin() || ! in_array( get_post_type(), array( 'post', 'ugolini_news' ), true ) ) {
		return $block_content;
	}

	$months = array( 1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre' );
	$time   = get_post_timestamp();
	$date   = wp_date( 'j', $time ) . ' ' . $months[ (int) wp_date( 'n', $time ) ] . ' ' . wp_date( 'Y', $time );
	return '<time class="wp-block-post-date" datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( $date ) . '</time>';
}
add_filter( 'render_block_core/post-date', 'ugolini_group_render_post_date' );

/** Use the approved page heroes even when an older cover block is saved in WordPress. */
function ugolini_group_render_page_hero_image( $html, $block ) {
	if ( ! ( is_page() || is_tax( 'sc_collection' ) ) || ! str_contains( $block['attrs']['className'] ?? '', 'ugolini-page-hero' ) ) {
		return $html;
	}
	$images = array(
		'shop'      => array( 'collection-heroes/all.jpg', 'Selezione delle specialità Ugolini Gourmet' ),
		'collections' => array( 'collection-heroes/all.jpg', 'Selezione delle specialità Ugolini Gourmet' ),
		'caviareat' => array( 'collection-heroes/caviareat.jpg', 'Storione in acqua' ),
		'luxureat' => array( 'collection-heroes/luxureat.jpg', 'Specialità LuxurEat al tartufo' ),
		'marmellate' => array( 'collection-heroes/marmellate.jpg', 'Marmellate e frutta' ),
		'olio-al-tartufo' => array( 'collection-heroes/olio-al-tartufo.jpg', 'Oli aromatizzati alle erbe' ),
		'pesto' => array( 'collection-heroes/pesto.jpg', 'Pesto verde in un vasetto' ),
		'salse-funghi' => array( 'collection-heroes/salse-funghi.jpg', 'Funghi freschi affettati' ),
		'salse-tartufo' => array( 'collection-heroes/salse-tartufo.jpg', 'Tartufi neri' ),
		'sughi' => array( 'collection-heroes/sughi.jpg', 'Sugo al pomodoro con basilico' ),
		'tin-caviar' => array( 'collection-heroes/tin-caviar.jpg', 'Caviale servito su crostini' ),
		'truffleat' => array( 'collection-heroes/truffleat.jpg', 'Gamma Truffleat' ),
		'chi-siamo' => array( 'about/about-hero.jpg', 'Condivisione a tavola' ),
		'b2b'       => array( 'page-heroes/b2b-20261007.jpg', 'Professionisti al lavoro' ),
		'blog'      => array( 'page-heroes/blog.jpg', 'Tazza di tè e quaderno' ),
		'faq'       => array( 'page-heroes/faq.jpg', 'Persona al lavoro con computer' ),
		'contatti'  => array( 'page-heroes/contact.jpg', 'Colleghi in un incontro di lavoro' ),
		'supporto'  => array( 'support/hero.jpg', 'Team di assistenza al lavoro' ),
	);
	$slug = is_tax( 'sc_collection' ) ? get_queried_object()->slug : get_post_field( 'post_name', get_queried_object_id() );
	if ( ! isset( $images[ $slug ] ) ) {
		return $html;
	}
	$url = esc_url( add_query_arg( 'v', '6.65.75', get_theme_file_uri( 'assets/images/' . $images[ $slug ][0] ) ) );
	$alt = esc_attr( $images[ $slug ][1] );
	return preg_replace_callback(
		'/<img\b(?=[^>]*\bclass="[^"]*\bwp-block-cover__image-background\b)[^>]*>/',
		static function ( $match ) use ( $url, $alt ) {
			$image = preg_replace( '/\bsrc="[^"]*"/', 'src="' . $url . '"', $match[0], 1 );
			$image = preg_replace( '/\balt="[^"]*"/', 'alt="' . $alt . '"', $image, 1 );
			return preg_replace( '/\s+srcset="[^"]*"/', '', $image );
		},
		$html,
		1
	);
}
add_filter( 'render_block_core/cover', 'ugolini_group_render_page_hero_image', 10, 2 );

/** Apply payment-logo semantics to saved footer templates as well as theme files. */
add_filter( 'render_block_core/html', static function ( $html ) {
	if ( ! str_contains( $html, 'class="ugolini-footer-payments"' ) ) return $html;
	$html = str_replace( '<div class="ugolini-footer-payments" aria-label=', '<div class="ugolini-footer-payments" role="group" aria-label=', $html );
	return preg_replace( '/(<span class="is-[^"]+" )aria-label=/', '$1role="img" aria-label=', $html );
} );

/** Give the visible homepage cover priority without changing saved block content. */
add_filter( 'render_block_core/cover', static function ( $html, $block ) {
	if ( ! is_front_page() || ! str_contains( $block['attrs']['className'] ?? '', 'ugolini-hero' ) ) return $html;
	$small = esc_url( get_theme_file_uri( 'assets/images/home-hero-ugolini-800.webp' ) );
	$large = esc_url( get_theme_file_uri( 'assets/images/home-hero-ugolini.webp' ) );
	return preg_replace_callback( '/<img\b(?=[^>]*\bclass="[^"]*\bwp-block-cover__image-background\b)[^>]*>/', static function ( $match ) use ( $small, $large ) {
		$image = preg_replace( '/\s+(?:loading|fetchpriority|decoding|srcset|sizes|width|height)="[^"]*"/', '', $match[0] );
		$image = preg_replace( '/\s+src="[^"]*"/', ' src="' . $small . '"', $image, 1 );
		$attrs = ' fetchpriority="high" decoding="async" width="1600" height="659" srcset="' . $small . ' 800w, ' . $large . ' 1600w" sizes="(max-width: 767px) 60vw, 100vw"';
		return str_replace( '<img ', '<img' . $attrs . ' ', $image );
	}, $html, 1 );
}, 20, 2 );

/** Start the visible hero request before the browser reaches the page body. */
add_action( 'wp_head', static function () {
	if ( ! is_front_page() ) return;
	$small = esc_url( get_theme_file_uri( 'assets/images/home-hero-ugolini-800.webp' ) );
	$large = esc_url( get_theme_file_uri( 'assets/images/home-hero-ugolini.webp' ) );
	echo '<link rel="preload" as="image" href="' . $small . '" imagesrcset="' . $small . ' 800w, ' . $large . ' 1600w" imagesizes="(max-width: 767px) 60vw, 100vw" fetchpriority="high">' . "\n";
} );

/** Keep the catalogue image current when the B2B pattern is saved in a page. */
add_filter( 'render_block_core/cover', function ( $html, $block ) {
	if ( ! is_page( 'b2b' ) || ! str_contains( $block['attrs']['className'] ?? '', 'ugolini-catalogue-panel' ) ) return $html;
	$url = esc_url( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8862-ragu-vegano-ugolini-gourmet-1-1024x683.jpg' );
	return preg_replace_callback( '/<img\b(?=[^>]*\bclass="[^"]*\bwp-block-cover__image-background\b)[^>]*>/', static function ( $match ) use ( $url ) {
		$image = preg_replace( '/\bsrc="[^"]*"/', 'src="' . $url . '"', $match[0], 1 );
		return preg_replace( '/\s+srcset="[^"]*"/', '', $image );
	}, $html, 1 );
}, 10, 2 );

/** Existing page content stores image URLs independently of theme patterns. */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_page( 'b2b' ) ) return $content;
	$groups = array(
		'ugolini-wholesale-services__media' => array( 'service-01.jpg', 'service-02.jpg', 'service-03.jpg', 'service-04.jpg', 'service-05.jpg' ),
		'ugolini-context-cta' => array( 'context-01.jpg', 'context-02.jpg', 'context-03.jpg' ),
	);
	foreach ( $groups as $class => $files ) {
		$content = preg_replace_callback( '~<[^>]+class="[^"]*' . preg_quote( $class, '~' ) . '[^"]*"[^>]*>.*?(?=<div class="ugolini-context-cta__copy"|</div>|</section>)~s', static function ( $match ) use ( $files ) {
			$index = 0;
			return preg_replace_callback( '/<img\b[^>]*>/', static function ( $image ) use ( $files, &$index ) {
				if ( ! isset( $files[ $index ] ) ) return $image[0];
				$url = esc_url( get_theme_file_uri( 'assets/images/b2b/' . $files[ $index++ ] ) );
				return preg_replace( '/\bsrc="[^"]*"/', 'src="' . $url . '"', $image[0], 1 );
			}, $match[0] );
		}, $content, 1 );
	}
	return $content;
}, 20 );

/** Give the product catalogue its own URL beside collection archives. */
add_action( 'init', function () { add_rewrite_rule( '^products/?$', 'index.php?pagename=shop', 'top' ); } );
add_action( 'admin_init', function () {
	if ( get_option( 'ugolini_group_products_route_66599' ) ) return;
	flush_rewrite_rules();
	update_option( 'ugolini_group_products_route_66599', 1, false );
} );
add_filter( 'redirect_canonical', function ( $redirect ) {
	return trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ) === 'products' ? false : $redirect;
} );
add_action( 'template_redirect', function () {
	$path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( 'collections/ugolini-products' === $path ) $destination = '/products/';
	elseif ( 'collections' === $path || 'shop' === $path ) $destination = '/products/';
	else return;
	wp_safe_redirect( home_url( $destination ), 301 );
	exit;
}, 0 );
add_filter( 'the_content', function ( $content ) {
	$content = str_replace( 'href="/shop/#ugolini-products"', 'href="/products/#ugolini-products"', $content );
	$content = preg_replace( '~href="/shop/#([a-z0-9-]+)"~', 'href="/collections/$1/"', $content );
	return str_replace( array( 'href="/shop/"', 'href="/collections/"', 'href="/collections/ugolini-products/"' ), array( 'href="/products/"', 'href="/products/"', 'href="/products/"' ), $content );
}, 100 );

/**
 * Retire Site Editor copies from older theme versions so filesystem templates
 * remain the single source of truth. Trashed copies remain recoverable.
 */
function ugolini_group_reset_template_overrides() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_templates_625_reset' ) ) {
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
	update_option( 'ugolini_group_templates_625_reset', 1, false );
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

/** Publish the new spaghetti article on installations that already seeded the blog. */
function ugolini_group_seed_spaghetti_article() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_spaghetti_article_seeded' ) ) return;
	$data = json_decode( (string) file_get_contents( get_theme_file_path( 'data/blog-posts.json' ) ), true );
	if ( ! is_array( $data ) ) return;
	foreach ( $data as $article ) {
		if ( 'chi-ha-inventato-gli-spaghetti-storia' !== ( $article['slug'] ?? '' ) ) continue;
		if ( get_page_by_path( $article['slug'], OBJECT, 'post' ) ) {
			update_option( 'ugolini_group_spaghetti_article_seeded', 1, false );
			return;
		}
		$term = term_exists( $article['category'], 'category' );
		if ( ! $term ) $term = wp_insert_term( $article['category'], 'category' );
		if ( is_wp_error( $term ) ) return;
		$image_id = ugolini_group_import_recipe_image( $article['image'] );
		if ( ! $image_id ) return;
		$post_id = wp_insert_post( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post_name'      => $article['slug'],
			'post_title'     => sanitize_text_field( $article['title'] ),
			'post_content'   => wp_kses_post( $article['content'] ),
			'post_excerpt'   => sanitize_text_field( $article['excerpt'] ),
			'post_date'      => str_replace( 'T', ' ', $article['date'] ),
			'post_category'  => array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		), true );
		if ( is_wp_error( $post_id ) ) return;
		set_post_thumbnail( $post_id, $image_id );
		update_option( 'ugolini_group_spaghetti_article_seeded', 1, false );
		return;
	}
}
add_action( 'init', 'ugolini_group_seed_spaghetti_article', 101 );

/** Read the 20-product dataset that powers the local preview. */
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
		$image      = $images[ $slug ] ?? '';
		$price      = number_format( (float) $product['current_price'], 2, ',', '.' ) . ' €';
		$name       = $product['official_product_name'];
		$collection = $product['collections'];
		$html      .= '<article class="ugolini-product-card preview-product-card" data-product-card data-name="' . esc_attr( strtolower( $name ) ) . '" data-collections="' . esc_attr( strtolower( $collection ) ) . '" data-price="' . esc_attr( $product['current_price'] ) . '">';
		$html      .= '<a class="preview-product-media" href="' . esc_url( $link ) . '">' . ( $image ? '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">' : '<span aria-hidden="true"></span>' ) . '</a>';
		$html      .= '<p class="ugolini-product-card-meta">' . esc_html( $collection ) . '</p><h3><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></h3><p class="preview-product-price">' . esc_html( $price ) . '</p><a class="preview-quick-view" href="' . esc_url( $link ) . '">Vista prodotto</a></article>';
	}
	return '<div class="preview-product-grid">' . $html . '</div>';
}

/** Render published SureCart products before JavaScript runs, including on phones. */
function ugolini_group_live_product_grid( $collection = '', $product_ids = array(), $related = false ) {
	$args = array( 'post_type' => 'sc_product', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' );
	if ( $product_ids ) {
		$args['post__in'] = array_map( 'absint', $product_ids );
		$args['orderby'] = 'post__in';
	}
	$html = '';
	$seen = array();
	foreach ( get_posts( $args ) as $post ) {
		$key = get_post_meta( $post->ID, 'sc_id', true ) ?: $post->ID;
		if ( isset( $seen[ $key ] ) ) continue;
		$seen[ $key ] = true;
		$terms = get_the_terms( $post, 'sc_collection' );
		$terms = is_array( $terms ) ? $terms : array();
		$names = implode( ' | ', wp_list_pluck( $terms, 'name' ) );
		$term_slugs = wp_list_pluck( $terms, 'slug' );
		$slugs = implode( '|', $term_slugs );
		$name = get_the_title( $post );
		$link = get_permalink( $post );
		$image = get_the_post_thumbnail_url( $post, 'large' );
		$price = (float) get_post_meta( $post->ID, 'min_price_amount', true );
		$html .= '<article class="ugolini-product-card preview-product-card" data-product-card data-name="' . esc_attr( strtolower( $name ) ) . '" data-collections="' . esc_attr( $slugs ) . '" data-price="' . esc_attr( $price ?: '' ) . '"' . ( $collection && ! in_array( $collection, $term_slugs, true ) ? ' hidden' : '' ) . '>';
		$html .= '<a class="preview-product-media" href="' . esc_url( $link ) . '">' . ( $image ? '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">' : '<span aria-hidden="true"></span>' );
		if ( $related ) {
			$description = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 18, '…' );
			$html .= '<span class="ugolini-related-overlay" aria-hidden="true"><strong>' . esc_html( $name ) . '</strong><span>' . esc_html( $description ?: 'Scopri il prodotto' ) . '</span></span>';
		}
		$html .= '</a>';
		$html .= '<p class="ugolini-product-card-meta">' . esc_html( $names ) . '</p><h3><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></h3><p class="preview-product-price">' . esc_html( $price > 0 ? number_format( $price, 2, ',', '.' ) . ' €' : 'Prezzo su richiesta' ) . '</p><a class="preview-quick-view" href="' . esc_url( $link ) . '">Vista prodotto</a></article>';
	}
	return '<div class="preview-product-grid">' . $html . '</div>';
}

/** Render the catalogue controls and live SureCart product cards. */
function ugolini_group_preview_shop() {
	$collection = is_tax( 'sc_collection' ) ? get_queried_object()->slug : '';
	$grid = ugolini_group_live_product_grid();
	$count = $collection ? ugolini_group_collection_count( get_queried_object() ) : substr_count( $grid, 'data-product-card' );
	$tabs = '<a class="' . ( $collection ? '' : 'is-active' ) . '" href="' . esc_url( home_url( '/products/' ) ) . '" data-shop-collection="" data-shop-label="">Tutti i prodotti</a>';
	foreach ( ugolini_group_get_collections() as $term ) {
		$tabs .= '<a class="' . ( $collection === $term->slug ? 'is-active' : '' ) . '" href="' . esc_url( get_term_link( $term ) ) . '" data-shop-collection="' . esc_attr( $term->slug ) . '" data-shop-label="' . esc_attr( strtolower( $term->name ) ) . '">' . esc_html( $term->name ) . '</a>';
	}
	return '<div class="alignwide preview-shop" data-preview-shop data-catalogue-complete><div class="preview-shop-bar"><div class="preview-shop-tabs">' . $tabs . '</div><div class="preview-shop-actions"><p class="ugolini-shop-count" data-shop-count aria-live="polite">' . esc_html( $count . ' prodotti' ) . '</p><button type="button" data-filter-open>' . ugolini_group_icon( 'sliders-horizontal' ) . 'Filtri</button><label><span>Cerca</span><input type="search" data-shop-search placeholder="Cerca prodotti"></label><label><span>Ordina</span><select data-shop-sort><option value="featured">In evidenza</option><option value="az">Nome, A–Z</option><option value="za">Nome, Z–A</option><option value="low">Prezzo crescente</option><option value="high">Prezzo decrescente</option></select></label></div></div><dialog class="preview-filter-dialog" data-filter-dialog><form method="dialog"><button class="preview-filter-close" value="close" aria-label="Chiudi filtri">' . ugolini_group_icon( 'x' ) . '</button><h2>Filtri</h2><fieldset><legend>Disponibilità</legend><label><input type="checkbox" checked disabled> Disponibile</label><label class="is-disabled"><input type="checkbox" disabled> Non disponibile</label></fieldset><fieldset class="preview-price-filter"><legend>Prezzo</legend><div class="preview-price-inputs"><label>Minimo € <input type="number" min="0" step="0.01" data-price-min></label><label>Massimo € <input type="number" min="0" step="0.01" data-price-max></label></div></fieldset><button type="button" data-filter-clear>Azzera filtri</button></form></dialog>' . $grid . '</div>';
}

/** Replace SureCart's version-dependent list wrapper with preview-identical cards. */
function ugolini_group_render_product_list_parity( $block_content, $block ) {
	$class = $block['attrs']['className'] ?? '';
	$limit = $block['attrs']['limit'] ?? ( $block['attrs']['query']['perPage'] ?? 8 );
	return false !== strpos( $class, 'ugolini-shop-list' ) ? ugolini_group_preview_shop() : '<div class="alignwide preview-product-list">' . ugolini_group_preview_product_grid( $limit ) . '</div>';
}
add_filter( 'render_block_surecart/product-list', 'ugolini_group_render_product_list_parity', 10, 2 );

/** Build the same open product-fact list used by the static preview. */
function ugolini_group_product_facts_content( $product ) {
	$fields = array(
		'full_description'              => 'Descrizione',
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

	$html = '<div class="preview-product-facts">';
	foreach ( $fields as $key => $label ) {
		if ( empty( $product[ $key ] ) ) {
			continue;
		}
		$html .= '<details class="ugolini-product-fact" open><summary>' . esc_html( $label ) . '</summary><p>' . nl2br( esc_html( $product[ $key ] ) ) . '</p></details>';
	}
	return '<!-- wp:html -->' . $html . '</div><!-- /wp:html -->';
}

/** Synchronize SureCart product order and factual detail content with preview. */
function ugolini_group_seed_product_details() {
	if ( ! current_user_can( 'manage_options' ) || get_option( 'ugolini_group_products_624_seeded' ) ) {
		return;
	}

	$products = ugolini_group_bundled_products();
	if ( 20 !== count( $products ) ) {
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
		update_option( 'ugolini_group_products_624_seeded', 1, false );
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
		'[ugolini_news]'                 => 'ugolini_group_news_archive',
		'[ugolini_events context="home"]' => 'ugolini_group_events_shortcode',
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
	wp_enqueue_script( 'ugolini-group-interactions', get_theme_file_uri( 'assets/js/theme.js' ), array(), $version, true );
	if ( is_singular( array( 'post', 'ugolini_news' ) ) || ( is_page( 'events' ) && ugolini_group_requested_event() ) ) {
		wp_enqueue_script( 'ugolini-group-share', get_theme_file_uri( 'assets/js/share.js' ), array(), $version, true );
	}
	if ( is_page( 'galleria' ) ) wp_enqueue_script( 'ugolini-group-gallery', get_theme_file_uri( 'assets/js/gallery.js' ), array(), $version, true );
	wp_enqueue_script( 'ugolini-group-catalogue-parity', get_theme_file_uri( 'assets/js/catalogue-parity.js' ), array(), $version, true );
}
add_action( 'wp_enqueue_scripts', 'ugolini_group_enqueue_assets' );

/**
 * Replace the Tawk plugin's eager footer embed with the theme's click gate.
 * The plugin remains installed so its property/widget settings stay editable.
 */
function ugolini_group_disable_tawk_auto_embed() {
	global $tawkto;

	if ( is_object( $tawkto ) ) {
		remove_action( 'wp_footer', array( $tawkto, 'print_embed_code' ) );
	}
}
add_action( 'after_setup_theme', 'ugolini_group_disable_tawk_auto_embed', 99 );

/** Load the local chat UI without contacting Tawk until a visitor clicks it. */
function ugolini_group_enqueue_chat_gate() {
	$version   = wp_get_theme()->get( 'Version' );
	$page_id   = get_option( 'tawkto-embed-widget-page-id', '6ab93e3ca532fa3442d54ef6' );
	$widget_id = get_option( 'tawkto-embed-widget-widget-id', '1k3hpm76n' );

	if ( ! preg_match( '/^[a-zA-Z0-9]+$/', $page_id ) || ! preg_match( '/^[a-zA-Z0-9]+$/', $widget_id ) ) {
		return;
	}

	wp_enqueue_script(
		'ugolini-group-chat',
		get_theme_file_uri( 'assets/js/tawk-chat.js' ),
		array(),
		$version,
		true
	);
	wp_add_inline_script(
		'ugolini-group-chat',
		'window.ugoliniChatConfig=' . wp_json_encode(
			array(
				'embedUrl' => 'https://embed.tawk.to/' . $page_id . '/' . $widget_id,
				'chatUrl'  => 'https://tawk.to/chat/' . $page_id . '/' . $widget_id,
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'ugolini_group_enqueue_chat_gate', 20 );

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

	$image = $query->posts ? (string) get_the_post_thumbnail_url( $query->posts[0], 'medium' ) : '';
	return 'cdn.shopify.com' === wp_parse_url( $image, PHP_URL_HOST ) ? add_query_arg( 'width', 360, $image ) : $image;
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

/** Use the actual gallery for single-product collections with editorial image blocks. */
function ugolini_group_editorial_images( $term, $limit = 4 ) {
	$galleries = array(
		'tincaviar-lattine-per-caviale' => array(
			'https://cdn.shopify.com/s/files/1/0837/7650/4140/files/lattine-tincaviar_2.png?v=1776183930',
			'https://cdn.shopify.com/s/files/1/0837/7650/4140/files/lattine-tincaviar_3.png?v=1776183931',
			'https://cdn.shopify.com/s/files/1/0837/7650/4140/files/lattine-tincaviar_1.png?v=1776183931',
		),
		'confit-cipolle-rosse' => array(
			'https://ugolinigroup.com/wp-content/uploads/2026/08/8763-confit-cipolle-rosse-ugolini-gourmet-1.jpg',
			'https://ugolinigroup.com/wp-content/uploads/2026/08/8763-confit-cipolle-rosse-ugolini-gourmet-10.jpg',
			'https://ugolinigroup.com/wp-content/uploads/2026/08/8763-confit-cipolle-rosse-ugolini-gourmet-11.jpg',
		),
	);
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	return isset( $galleries[ $slug ] ) ? array_slice( $galleries[ $slug ], 0, $limit ) : ugolini_group_collection_images( $term, $limit );
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
		return '<a class="ugolini-shop-direct" href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Shop', 'ugolini-group' ) . '</a>';
	}

	$items = '';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$count = ugolini_group_collection_count( $term );
		$items .= '<li><a href="' . esc_url( $link ) . '"><span>' . esc_html( $term->name ) . '</span><small>' . esc_html( sprintf( _n( '%d prodotto', '%d prodotti', $count, 'ugolini-group' ), $count ) ) . '</small></a></li>';
	}

	return '<details class="ugolini-mega-menu"><summary><a href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Shop', 'ugolini-group' ) . '</a></summary><div class="ugolini-mega-panel"><div><p class="ugolini-eyebrow">' . esc_html__( 'Collezioni Ugolini', 'ugolini-group' ) . '</p><h2>' . esc_html__( 'Esplora il gusto italiano', 'ugolini-group' ) . '</h2><a class="ugolini-text-link" href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a></div><ul>' . $items . '</ul></div></details>';
}
add_shortcode( 'ugolini_collection_menu', 'ugolini_group_collection_menu_shortcode' );

add_filter( 'body_class', function ( $classes ) {
	if ( is_front_page() || is_page( array( 'shop', 'b2b' ) ) || is_tax( 'sc_collection' ) ) $classes[] = 'has-overlay-header';
	return $classes;
} );

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
		$cards .= '<article class="ugolini-collection-card"><a href="' . esc_url( $link ) . '"><span class="ugolini-collection-media">' . $media . '</span><span class="ugolini-collection-copy"><strong>' . esc_html( $term->name ) . '</strong><small>' . esc_html( sprintf( _n( '%d prodotto', '%d prodotti', $count, 'ugolini-group' ), $count ) ) . '</small></span></a></article>';
	}

	return '<div class="ugolini-collection-grid">' . $cards . '</div>';
}
add_shortcode( 'ugolini_collection_grid', 'ugolini_group_collection_grid_shortcode' );

/** Render compact collection navigation above the native SureCart filters. */
function ugolini_group_collection_tabs_shortcode() {
	$links   = '<a class="' . ( is_tax( 'sc_collection' ) ? '' : 'is-active' ) . '" href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a>';
	$current = get_queried_object_id();
	foreach ( ugolini_group_get_collections() as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$links .= '<a class="' . ( $current === $term->term_id ? 'is-active' : '' ) . '" href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
		}
	}

	return '<nav class="ugolini-shop-tabs" aria-label="' . esc_attr__( 'Filtra per collezione', 'ugolini-group' ) . '">' . $links . '</nav>';
}
add_shortcode( 'ugolini_collection_tabs', 'ugolini_group_collection_tabs_shortcode' );

/** Render an image-led, collapsible introduction for the current collection. */
function ugolini_group_collection_editorial_shortcode() {
	$copy = array(
		'caviareat'       => array( 'CaviarEat', 'Caviale di storione, caviale vegano e altre specialità della collezione.', 'La gamma comprende caviali di storione, caviale vegano e filetti di acciughe. Consulta la singola scheda per conoscere le caratteristiche di ogni prodotto.' ),
		'luxureat'        => array( 'LuxurEat', 'Tartufi, caviali, tartare e altre specialità gourmet.', 'La collezione riunisce tartufi, caviali, tartare di pesce, oli, salse e preparazioni al tartufo. Ogni referenza conserva ingredienti, formati e indicazioni propri.' ),
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
		'tin-caviar'       => array( 'Tin Caviar', 'Lattine per il confezionamento del caviale e delle uova di pesce.', 'La collezione presenta lattine in banda stagnata e alluminio dedicate al confezionamento del caviale e delle uova di pesce.' ),
		'truffleat'        => array( 'Truffleat', 'Tartufi, condimenti e accessori dedicati al tartufo.', 'La gamma comprende tartufi, oli, salse, condimenti, pasta, riso e strumenti per il taglio del tartufo. Le informazioni specifiche sono riportate nelle schede dei singoli prodotti.' ),
	);
	$terms   = ugolini_group_get_collections();
	$current = is_tax( 'sc_collection' ) ? get_queried_object()->slug : '';
	$editorial_image = static function( $slug ) {
		return get_theme_file_uri( 'assets/images/collection-editorial/' . ( $slug ?: 'all' ) . '.jpg' );
	};
	$panels = '';
	$render = static function( $slug, $name, $lead, $details, $image, $active ) {
		if ( ! $image ) return '';
		$film = 'olio-al-tartufo' === $slug ? '<p><a href="' . esc_url( home_url( '/galleria/#quattrociocchi-video' ) ) . '">Guarda la raccolta e la produzione dell’olio con Quattrociocchi Americo →</a></p>' : '';
		return '<section class="ugolini-collection-editorial alignwide" data-collection-editorial="' . esc_attr( $slug ) . '"' . ( $active ? '' : ' hidden' ) . '><div class="ugolini-collection-editorial__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async"><div class="ugolini-collection-editorial__content"><div class="ugolini-collection-editorial__title"><p class="ugolini-eyebrow">' . esc_html__( 'Collezione Ugolini Gourmet', 'ugolini-group' ) . '</p><h2>' . esc_html( $name ) . '</h2></div><div class="ugolini-collection-editorial__intro"><p>' . esc_html( $lead ) . '</p><details open><summary>' . esc_html__( 'Scopri la collezione', 'ugolini-group' ) . '</summary><p>' . esc_html( $details ) . '</p><p>' . esc_html__( 'Consulta ogni scheda per ingredienti, formati, modalità d’uso e certificazioni disponibili.', 'ugolini-group' ) . '</p></details>' . $film . '</div></div></div></section>';
	};
	$panels .= $render( '', 'Tutti i prodotti', 'Tutta la gamma Ugolini Gourmet.', 'Esplora le collezioni e consulta la scheda di ciascun prodotto.', $editorial_image( '' ), '' === $current );
	foreach ( $terms as $term ) {
		$description = trim( wp_strip_all_tags( term_description( $term ) ) );
		$content = $copy[ $term->slug ] ?? array( $term->name, $description, $description );
		$panels .= $render( $term->slug, $term->name, $description ?: $content[1], $content[2], $editorial_image( $term->slug ), $current === $term->slug );
	}
	return $panels;
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

/** Resolve a requested event only from the bundled event data. */
function ugolini_group_requested_event() {
	$id = isset( $_GET['evento'] ) && is_string( $_GET['evento'] ) ? sanitize_title( wp_unslash( $_GET['evento'] ) ) : '';
	if ( ! $id ) return null;
	foreach ( ugolini_group_events_data() as $event ) {
		if ( sanitize_title( $event['id'] ) === $id && ! empty( $event['detailHtml'] ) ) return $event;
	}
	return null;
}

function ugolini_group_event_url( $event ) {
	return add_query_arg( 'evento', sanitize_title( $event['id'] ), home_url( '/events/' ) );
}

function ugolini_group_event_detail( $event ) {
	$image = get_theme_file_uri( 'assets/images/events/' . $event['image'] );
	$gallery_link = ! empty( $event['galleryId'] ) ? '<p><a href="' . esc_url( add_query_arg( 'evento', sanitize_title( $event['galleryId'] ), home_url( '/galleria/' ) ) ) . '#ugolini-gallery-title">Guarda i video dell’evento →</a></p>' : '';
	return '<article class="ugolini-event-detail"><div class="alignwide"><header><p class="ugolini-eyebrow">Eventi · SIAL Paris 2026</p><h1>' . esc_html( $event['detailTitle'] ?? $event['title'] ) . '</h1><p class="ugolini-event-detail__meta">' . esc_html( $event['detailMeta'] ?? ugolini_group_event_date_label( $event['startDate'], $event['endDate'] ) ) . '</p>' . ugolini_group_share_markup( ugolini_group_event_url( $event ), $event['detailTitle'] ?? $event['title'] ) . '</header><figure><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $event['alt'] ?? $event['title'] ) . '"></figure><div class="ugolini-event-detail__body">' . wp_kses_post( $event['detailHtml'] ) . $gallery_link . '</div><a class="ugolini-event-detail__back" href="' . esc_url( home_url( '/events/' ) ) . '">← Tutti gli eventi</a></div></article>';
}

add_filter( 'document_title_parts', static function ( $parts ) {
	if ( is_page( 'events' ) && ( $event = ugolini_group_requested_event() ) ) $parts['title'] = $event['detailTitle'] ?? $event['title'];
	return $parts;
} );
add_filter( 'body_class', static function ( $classes ) {
	if ( is_page( 'events' ) && ugolini_group_requested_event() ) $classes[] = 'ugolini-event-detail-page';
	if ( is_page( 'blog' ) ) $classes[] = 'has-overlay-header';
	return $classes;
} );

/** Render one accessible event deck with image, details, thumbnails and controls. */
function ugolini_group_event_deck( $events, $heading, $home = false ) {
	if ( ! $events ) return '';
	$status_labels = array( 'current' => 'In corso', 'upcoming' => 'In programma', 'past' => 'Concluso' );
	$slides = '';
	$thumbs = '';
	foreach ( $events as $index => $event ) {
		$status = ugolini_group_event_status( $event );
		$image  = get_theme_file_uri( 'assets/images/events/' . $event['image'] );
		$base   = pathinfo( $event['image'], PATHINFO_FILENAME );
		$has_sizes = file_exists( get_theme_file_path( 'assets/images/events/' . $base . '-400.jpg' ) ) && file_exists( get_theme_file_path( 'assets/images/events/' . $base . '-1200.jpg' ) );
		$small  = $has_sizes ? get_theme_file_uri( 'assets/images/events/' . $base . '-400.jpg' ) : $image;
		$large  = $has_sizes ? get_theme_file_uri( 'assets/images/events/' . $base . '-1200.jpg' ) : $image;
		$srcset = $has_sizes ? ' srcset="' . esc_url( $small ) . ' 400w, ' . esc_url( $large ) . ' 1200w" sizes="(max-width: 767px) 100vw, 50vw"' : '';
		$link   = ugolini_group_event_url( $event );
		$hidden = $index ? ' hidden' : '';
		$slides .= '<article id="' . esc_attr( $event['id'] ) . '" class="ugolini-event-slide" data-event-slide data-event-index="' . esc_attr( $index ) . '"' . $hidden . '>';
		$slides .= '<figure class="ugolini-event-media"><a href="' . esc_url( $link ) . '"><img src="' . esc_url( $large ) . '"' . $srcset . ' alt="' . esc_attr( $event['alt'] ?? $event['title'] ) . '" loading="lazy" decoding="async"></a></figure>';
		$slides .= '<div class="ugolini-event-copy"><p class="ugolini-eyebrow">' . esc_html( $status_labels[ $status ] ) . '</p><h3><a href="' . esc_url( $link ) . '">' . esc_html( $event['title'] ) . '</a></h3>';
		$slides .= '<p class="ugolini-event-lead"><strong>' . esc_html( $event['lead'] ) . '</strong></p><p>' . esc_html( $event['description'] ) . '</p><p class="ugolini-event-highlight"><strong>' . esc_html( $event['highlight'] ) . '</strong></p>';
		$slides .= '<p class="ugolini-event-brands">' . esc_html( implode( ' · ', $event['brands'] ?? array() ) ) . '</p><div class="ugolini-event-meta"><span>' . ugolini_group_icon( 'map-pin' ) . esc_html( $event['location'] ) . '</span><span>' . ugolini_group_icon( 'calendar' ) . esc_html( ugolini_group_event_date_label( $event['startDate'], $event['endDate'] ) ) . '</span></div>';
		if ( $home ) $slides .= '<a class="ugolini-event-link" href="' . esc_url( $link ) . '">Scopri l’evento <span aria-hidden="true">→</span></a>';
		$slides .= '</div></article>';
		$thumbs .= '<button type="button" data-event-go="' . esc_attr( $index ) . '" aria-label="Mostra ' . esc_attr( $event['title'] . ', ' . implode( ', ', $event['brands'] ?? array() ) ) . '" aria-current="' . ( $index ? 'false' : 'true' ) . '"><img src="' . esc_url( $small ) . '" alt="" loading="lazy" decoding="async"></button>';
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
		$link   = ugolini_group_event_url( $event );
		$cards .= '<article id="' . esc_attr( $event['id'] ) . '" class="ugolini-event-card"><a class="ugolini-event-card__link" href="' . esc_url( $link ) . '"><figure><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $event['alt'] ?? $event['title'] ) . '" loading="lazy"></figure><div><p class="ugolini-eyebrow">' . esc_html( $status_labels[ $status ] ) . '</p><h3>' . esc_html( $event['title'] ) . '</h3><p class="ugolini-event-lead"><strong>' . esc_html( $event['lead'] ) . '</strong></p><p>' . esc_html( $event['description'] ) . '</p><p class="ugolini-event-highlight"><strong>' . esc_html( $event['highlight'] ) . '</strong></p><p class="ugolini-event-brands">' . esc_html( implode( ' · ', $event['brands'] ?? array() ) ) . '</p><div class="ugolini-event-meta"><span>' . ugolini_group_icon( 'map-pin' ) . esc_html( $event['location'] ) . '</span><span>' . ugolini_group_icon( 'calendar' ) . esc_html( ugolini_group_event_date_label( $event['startDate'], $event['endDate'] ) ) . '</span></div><span class="ugolini-event-card__more">Scopri l’evento →</span></div></a></article>';
	}
	if ( ! $cards ) $cards = '<p class="ugolini-event-empty">Nessun evento in questa sezione.</p>';
	return '<section id="' . esc_attr( $id ) . '" class="ugolini-event-list-section"><div class="ugolini-section-heading"><div><h2>' . esc_html( $heading ) . '</h2></div></div><div class="ugolini-event-list">' . $cards . '</div></section>';
}

/** Render active events on Home, and active plus past events on the archive. */
function ugolini_group_events_shortcode( $attributes = array() ) {
	$attributes = shortcode_atts( array( 'context' => 'home' ), $attributes, 'ugolini_events' );
	if ( 'archive' === $attributes['context'] && ( $event = ugolini_group_requested_event() ) ) return ugolini_group_event_detail( $event );
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

/** LuxurEat brand news live in Events but use the existing Blog article template. */
function ugolini_group_register_news() {
	register_post_type( 'ugolini_news', array(
		'labels'       => array( 'name' => 'Notizie Eventi', 'singular_name' => 'Notizia Evento' ),
		'public'       => true,
		'has_archive'  => false,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-megaphone',
		'rewrite'      => array( 'slug' => 'events/notizie' ),
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
		'taxonomies'   => array( 'category' ),
	) );
}
add_action( 'init', 'ugolini_group_register_news', 0 );

function ugolini_group_news_data() {
	$data = json_decode( (string) file_get_contents( get_theme_file_path( 'data/events.json' ) ), true );
	return array_values( array_filter( is_array( $data ) ? $data : array(), static fn( $item ) => is_array( $item ) && ( $item['kind'] ?? '' ) === 'news' && ! empty( $item['id'] ) && ! empty( $item['title'] ) ) );
}

/** Keep article media on Ugolini, reusing a previously imported source image. */
function ugolini_group_news_image( $url, $post_id ) {
	if ( ! $url || ! wp_http_validate_url( $url ) || 'luxureat.cn' !== wp_parse_url( $url, PHP_URL_HOST ) ) return 0;
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_ugolini_news_source', 'meta_value' => $url ) );
	if ( $existing ) return (int) $existing[0];
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_sideload_image( $url, $post_id, null, 'id' );
	if ( is_wp_error( $id ) ) return 0;
	update_post_meta( $id, '_ugolini_news_source', $url );
	return (int) $id;
}

/** Import one article per administrator visit, so media processing stays bounded. */
function ugolini_group_import_news() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! get_option( 'ugolini_group_news_701_initialized' ) ) {
		$page = get_page_by_path( 'events', OBJECT, 'page' );
		$content = ugolini_group_page_pattern_content( 'page-events' );
		if ( $page && $content ) wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ) );
		flush_rewrite_rules( false );
		update_option( 'ugolini_group_news_701_initialized', 1, false );
	}
	foreach ( ugolini_group_news_data() as $news ) {
		$slug = sanitize_title( $news['id'] );
		$existing = get_page_by_path( $slug, OBJECT, 'ugolini_news' );
		if ( $existing && 'publish' === $existing->post_status && ( ! in_array( $news['id'], array( 'fhc-shanghai-2026-breakfast', 'fhc-shanghai-2026-pancakes' ), true ) || get_post_meta( $existing->ID, '_ugolini_news_full_2026', true ) ) ) {
			if ( get_the_date( 'Y-m-d', $existing ) !== $news['date'] ) wp_update_post( array( 'ID' => $existing->ID, 'post_date' => $news['date'] . ' 12:00:00', 'edit_date' => true ) );
			continue;
		}
		$terms = term_exists( 'Notizie', 'category' );
		if ( ! $terms ) $terms = wp_insert_term( 'Notizie', 'category' );
		$category = ! is_wp_error( $terms ) ? (int) ( is_array( $terms ) ? $terms['term_id'] : $terms ) : 0;
		$post_id = $existing ? (int) $existing->ID : wp_insert_post( array(
			'post_type' => 'ugolini_news', 'post_status' => 'draft', 'post_name' => $slug,
			'post_title' => sanitize_text_field( $news['title'] ),
			'post_excerpt' => sanitize_text_field( $news['intro'] ),
			'post_date' => $news['date'] . ' 12:00:00',
			'post_category' => $category ? array( $category ) : array(),
			'comment_status' => 'closed', 'ping_status' => 'closed',
		), true );
		if ( is_wp_error( $post_id ) ) return;
		update_post_meta( $post_id, '_ugolini_news_author', sanitize_text_field( $news['author'] ?? 'LuxurEat' ) );
		update_post_meta( $post_id, '_ugolini_news_source', esc_url_raw( $news['sourceUrl'] ?? '' ) );
		$images = array();
		$cover_id = ugolini_group_news_image( $news['cardImage'] ?? '', $post_id );
		if ( $cover_id ) {
			set_post_thumbnail( $post_id, $cover_id );
			$images[ $news['cardImage'] ] = wp_get_attachment_url( $cover_id );
		}
		$content = '<p class="ugolini-news-intro"><strong>' . esc_html( $news['intro'] ) . '</strong></p>';
		foreach ( $news['opening'] ?? array() as $paragraph ) $content .= '<p>' . esc_html( $paragraph ) . '</p>';
		foreach ( $news['sections'] ?? array() as $section ) {
			$content .= '<h2>' . esc_html( $section['heading'] ) . '</h2>';
			foreach ( $section['paragraphs'] ?? array() as $paragraph ) $content .= '<p>' . esc_html( $paragraph ) . '</p>';
			foreach ( $section['media'] ?? array() as $media ) {
				if ( 'video' === ( $media['type'] ?? '' ) && ! empty( $news['video'] ) ) {
					$video_url = ! empty( $news['videoAsset'] ) ? get_theme_file_uri( $news['videoAsset'] ) : $news['video'];
					$poster_url = ! empty( $news['videoPosterAsset'] ) ? get_theme_file_uri( $news['videoPosterAsset'] ) : ( $news['videoPoster'] ?? '' );
					$content .= '<figure class="wp-block-video"><video controls playsinline preload="none" poster="' . esc_url( $poster_url ) . '" src="' . esc_url( $video_url ) . '"></video>';
					if ( ! empty( $news['videoCreditUrl'] ) ) $content .= '<figcaption>Video: <a href="' . esc_url( $news['videoCreditUrl'] ) . '" rel="noopener noreferrer" target="_blank">' . esc_html( $news['videoCredit'] ?? 'Fonte' ) . '</a></figcaption>';
					$content .= '</figure>';
				} elseif ( ! empty( $media['src'] ) ) {
					if ( ! isset( $images[ $media['src'] ] ) ) {
						$id = ugolini_group_news_image( $media['src'], $post_id );
						$images[ $media['src'] ] = $id ? wp_get_attachment_url( $id ) : $media['src'];
					}
					$content .= '<figure class="wp-block-image"><img src="' . esc_url( $images[ $media['src'] ] ) . '" alt="' . esc_attr( $media['alt'] ?? '' ) . '" loading="lazy"></figure>';
				}
			}
		}
		if ( ! empty( $news['galleryId'] ) ) $content .= '<p><a href="' . esc_url( add_query_arg( 'evento', sanitize_title( $news['galleryId'] ), home_url( '/galleria/' ) ) ) . '#ugolini-gallery-title">Guarda i video dell’evento →</a></p>';
		if ( ! empty( $news['sourceUrl'] ) ) $content .= '<p class="ugolini-news-source"><a href="' . esc_url( $news['sourceUrl'] ) . '" rel="noopener noreferrer" target="_blank">Fonte originale ↗</a></p>';
		wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_kses_post( $content ), 'post_status' => 'publish', 'post_date' => $news['date'] . ' 12:00:00', 'edit_date' => true ) );
		if ( in_array( $news['id'], array( 'fhc-shanghai-2026-breakfast', 'fhc-shanghai-2026-pancakes' ), true ) ) update_post_meta( $post_id, '_ugolini_news_full_2026', '1' );
		return;
	}
}
add_action( 'admin_init', 'ugolini_group_import_news', 30 );

function ugolini_group_news_archive() {
	if ( ugolini_group_requested_event() ) return '';
	$posts = get_posts( array( 'post_type' => 'ugolini_news', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	$cards = '';
	$by_slug = array();
	foreach ( $posts as $post ) $by_slug[ $post->post_name ] = $post;
	foreach ( ugolini_group_news_data() as $news ) {
		$slug = sanitize_title( $news['id'] );
		$post = $by_slug[ $slug ] ?? null;
		if ( $post ) {
			$cards .= '<article class="ugolini-news-card"><a href="' . esc_url( get_permalink( $post ) ) . '">' . get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy' ) ) . '<span class="ugolini-news-card__body"><time datetime="' . esc_attr( get_the_date( 'Y-m-d', $post ) ) . '">' . esc_html( get_the_date( 'j F Y', $post ) ) . '</time><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( get_the_excerpt( $post ) ) . '</p><span class="ugolini-news-card__link">Leggi la notizia →</span></span></a></article>';
			unset( $by_slug[ $slug ] );
		} elseif ( ! empty( $news['sourceUrl'] ) ) {
			$cards .= '<article class="ugolini-news-card"><a href="' . esc_url( $news['sourceUrl'] ) . '"><img src="' . esc_url( $news['cardImage'] ?? '' ) . '" alt="' . esc_attr( $news['title'] ) . '" loading="lazy"><span class="ugolini-news-card__body"><time datetime="' . esc_attr( $news['date'] ?? '' ) . '">' . esc_html( $news['date'] ?? '' ) . '</time><h3>' . esc_html( $news['title'] ) . '</h3><p>' . esc_html( $news['intro'] ?? '' ) . '</p><span class="ugolini-news-card__link">Leggi la notizia →</span></span></a></article>';
		}
	}
	foreach ( $by_slug as $post ) {
		$cards .= '<article class="ugolini-news-card"><a href="' . esc_url( get_permalink( $post ) ) . '">' . get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy' ) ) . '<span class="ugolini-news-card__body"><time datetime="' . esc_attr( get_the_date( 'Y-m-d', $post ) ) . '">' . esc_html( get_the_date( 'j F Y', $post ) ) . '</time><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( get_the_excerpt( $post ) ) . '</p><span class="ugolini-news-card__link">Leggi la notizia →</span></span></a></article>';
	}
	if ( ! $cards ) return '';
	return '<div class="ugolini-news-banner" role="img" aria-label="Crostini con salmone, uova e aneto"></div><section id="sezione-notizie-dal-gruppo" class="ugolini-news-archive ugolini-section"><div class="alignwide"><div class="ugolini-section-heading"><div><h2>Notizie dal gruppo</h2></div></div><div class="ugolini-news-grid">' . $cards . '</div></div></section>';
}
add_shortcode( 'ugolini_news', 'ugolini_group_news_archive' );

/** Collection-aware editorial and cooking blocks for product pages. */
function ugolini_group_product_story_shortcode() {
	$terms = is_singular() ? wp_get_post_terms( get_the_ID(), 'sc_collection' ) : array();
	$term  = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	$slug = $term instanceof WP_Term ? $term->slug : 'salse-tartufo';
	$link = $term instanceof WP_Term ? get_term_link( $term ) : home_url( '/products/' );
	$copy = array(
		'caviareat'       => array( 'CaviarEat', 'Caviali di storione, caviale vegano e altre specialità della collezione.', 'La gamma comprende referenze diverse: consulta la singola scheda per caratteristiche, formato e indicazioni di conservazione.' ),
		'luxureat'        => array( 'LuxurEat', 'Tartufi, caviali, tartare e altre specialità gourmet.', 'Scopri le diverse referenze della collezione e consulta ogni scheda per ingredienti, formati e modalità d’uso.' ),
		'tin-caviar'      => array( 'Tin Caviar', 'Lattine per il confezionamento del caviale e delle uova di pesce.', 'La collezione comprende confezioni in banda stagnata e alluminio; consulta la scheda per i dettagli della referenza.' ),
		'truffleat'       => array( 'Truffleat', 'Tartufi, oli, salse, condimenti e accessori.', 'Esplora le specialità al tartufo e gli strumenti della collezione, verificando le caratteristiche di ogni prodotto nella relativa scheda.' ),
		'pesto'           => array( 'Pesti Ugolini Gourmet', 'Ricette pronte per condire pasta, bruschette e preparazioni creative.', 'Dal pesto alla Genovese alle varianti rosse e vegetali, ogni referenza mantiene ingredienti, formati e indicazioni d’uso propri: consulta la scheda del prodotto prima del servizio.' ),
		'sughi'           => array( 'Sughi Ugolini Gourmet', 'Ricette italiane pronte per una tavola semplice, generosa e ricca di gusto.', 'La collezione comprende preparazioni al pomodoro, ragù e proposte dal profilo più deciso. Scalda dolcemente e completa il piatto seguendo sempre le indicazioni della singola referenza.' ),
		'marmellate'      => array( 'Marmellate gastronomiche Ugolini Gourmet', 'Note agrodolci pensate per accompagnare formaggi, salumi e carni.', 'Le diverse combinazioni di frutta e ortaggi aiutano a costruire contrasti equilibrati su taglieri, aperitivi e secondi piatti. Parti da una piccola quantità e regola l’abbinamento al gusto.' ),
		'olio-al-tartufo' => array( 'Oli al tartufo Ugolini Gourmet', 'Il profumo del tartufo bianco e nero incontra l’olio extra vergine di oliva.', 'Pensati come condimenti di finitura, valorizzano pasta, risotti, uova, carne e verdure. Bastano poche gocce; ingredienti e modalità di conservazione restano quelli riportati in etichetta.' ),
		'salse-funghi'    => array( 'Salse ai funghi Ugolini Gourmet', 'Porcini e champignon in creme morbide, pronte per la cucina di ogni giorno.', 'Servile su primi piatti, crostini, carne o verdure e regola la consistenza con moderazione. Ogni ricetta conserva il proprio profilo e le informazioni specifiche della confezione.' ),
		'salse-tartufo'   => array( 'Specialità al tartufo Ugolini Gourmet', 'Salse, creme e condimenti che portano il carattere del tartufo in tavola.', 'La collezione riunisce ricette e formati diversi per completare pasta, riso, uova, carne, verdure e crostini con un prodotto pronto all’uso e facile da dosare.' ),
	);
	$content = $copy[ $slug ] ?? $copy['salse-tartufo'];
	$image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	$slides = $image ? '<img class="ugolini-carousel__slide is-active" src="' . esc_url( $image ) . '" alt="" loading="lazy">' : '';
	if ( is_wp_error( $link ) ) $link = home_url( '/products/' );
	$film = 'olio-al-tartufo' === $slug ? '<p><a href="' . esc_url( home_url( '/galleria/#quattrociocchi-video' ) ) . '">Guarda il percorso dell’olio con Quattrociocchi Americo →</a></p>' : '';
	return '<section class="ugolini-product-story"><div class="ugolini-carousel ugolini-product-story__media" data-carousel data-product-gallery-story>' . $slides . '</div><div class="ugolini-product-story__copy"><div><p class="ugolini-eyebrow">Continua a scoprire</p><h2>' . esc_html( $content[0] ) . '</h2></div><div><p>' . esc_html( $content[1] ) . '</p><p>' . esc_html( $content[2] ) . '</p><a href="' . esc_url( $link ) . '#ugolini-products">Vai alla collezione</a>' . $film . '</div></div></section>';
}
add_shortcode( 'ugolini_product_story', 'ugolini_group_product_story_shortcode' );

/** Licensed product imagery shared by the guide and collection editorial. */
function ugolini_group_product_editorial_media( $post_id = 0 ) {
	static $data = null;
	if ( null === $data ) {
		$path = get_theme_file_path( 'data/product-films.json' );
		$data = is_readable( $path ) ? json_decode( file_get_contents( $path ), true ) : array();
	}
	$post_id = $post_id ?: get_queried_object_id();
	$keys = $data['products'][ get_post_field( 'post_name', $post_id ) ] ?? array();
	$photos = array();
	$posters = array();
	foreach ( $keys as $key ) {
		$media = $data['media'][ $key ] ?? null;
		if ( ! $media || empty( $media['photo'] ) ) continue;
		$photos[] = get_theme_file_uri( 'assets/images/' . $media['photo'] );
		$posters[] = get_theme_file_uri( 'assets/videos/' . $media['file'] . '.jpg' );
	}
	return array( 'photos' => $photos, 'guide' => array_merge( $photos, $posters ) );
}

/** Series-specific tasting and use guide. Product labels remain the authority. */
function ugolini_group_product_guide_shortcode() {
	$terms = is_singular() ? wp_get_post_terms( get_the_ID(), 'sc_collection' ) : array();
	$term  = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	$slug  = $term instanceof WP_Term ? $term->slug : 'salse-tartufo';
	$product_slug = get_post_field( 'post_name', get_the_ID() );
	$is_sturgeon_caviar = 'caviareat' === $slug && ! in_array( $product_slug, array( 'caviale-vegano', 'acciughe-sott-olio-100-gr' ), true );
	$guides = array(
		'caviareat' => array(
			array( 'sparkles', 'Eccellenza della Materia Prima', 'Solo materie prime selezionate danno vita a un caviale dal gusto autentico e raffinato.\n\nOgni fase della produzione è curata con precisione per garantire qualità, purezza e un sapore inconfondibile, espressione della nostra dedizione all’eccellenza.' ),
			array( 'book-open', 'Benessere e Cura dello Storione', 'Gli storioni crescono in un ambiente controllato e rispettoso, seguendo i loro ritmi naturali.\n\nAttenzione quotidiana, alimentazione equilibrata e tempi di maturazione lunghi assicurano il loro benessere e un caviale di qualità superiore.' ),
			array( 'package-check', 'Confezionamento di Precisione', 'Ogni confezione viene realizzata con cura e competenza per preservare freschezza e gusto.\n\nIl rigoroso controllo della catena del freddo e la tracciabilità completa della filiera garantiscono un prodotto perfetto in ogni dettaglio.' ),
			array( 'badge-check', 'Consulenza e Servizio Dedicato', 'Offriamo un servizio personalizzato, rapido e attento alle esigenze di ogni cliente.\n\nDalla scelta del caviale alla consegna, accompagniamo ogni esperienza con professionalità, competenza e una passione che fa la differenza.' ),
		),
		'caviareat-vegano' => array(
			array( 'sparkles', 'Ispirazione marina', 'Il caviale vegano nasce da alghe marine. Presentalo in piccole quantità per valorizzarne colore, consistenza e carattere.' ),
			array( 'utensils', 'Come servirlo', 'Usalo come finitura per antipasti e piccoli assaggi. La scheda prodotto indica le caratteristiche della referenza.' ),
			array( 'book-open', 'Abbinamenti', 'Scegli accompagnamenti delicati e costruisci l’assaggio gradualmente, lasciando spazio al sapore del prodotto.' ),
			array( 'tag', 'Prima dell’uso', 'Verifica sempre ingredienti, allergeni, formato e conservazione riportati sulla confezione.' ),
		),
		'caviareat-acciughe' => array(
			array( 'sparkles', 'Il carattere delle acciughe', 'I filetti di acciughe hanno un gusto deciso: inizia con una piccola quantità e completa il piatto secondo il tuo gusto.' ),
			array( 'utensils', 'Come servirle', 'Provale su crostini oppure come ingrediente per salse e primi piatti, seguendo le indicazioni della referenza.' ),
			array( 'book-open', 'Abbinamenti', 'Accosta le acciughe a preparazioni semplici, così la loro componente sapida resta riconoscibile.' ),
			array( 'tag', 'Prima dell’uso', 'Consulta l’etichetta per ingredienti, allergeni, formato e conservazione, anche dopo l’apertura.' ),
		),
		'luxureat' => array(
			array( 'sparkles', 'Una specialità da scoprire', 'Ogni referenza LuxurEat ha ingredienti e caratteristiche propri. Parti dalla sua scheda per conoscere il prodotto.' ),
			array( 'utensils', 'Idee di utilizzo', 'Scegli preparazione e servizio in base alle indicazioni della singola referenza e al piatto che desideri creare.' ),
			array( 'book-open', 'Costruire l’esperienza', 'Valorizza il carattere del prodotto con abbinamenti misurati, lasciando spazio al suo gusto e alla sua consistenza.' ),
			array( 'tag', 'Prima dell’uso', 'Controlla in etichetta ingredienti, allergeni, formato, istruzioni e conservazione.' ),
		),
		'truffleat' => array(
			array( 'sparkles', 'Il mondo del tartufo', 'La gamma comprende tartufi, condimenti e accessori. Ogni referenza ha una funzione e caratteristiche specifiche.' ),
			array( 'utensils', 'Nel piatto', 'Per le referenze alimentari, segui le indicazioni d’uso e dosa il prodotto in funzione della preparazione.' ),
			array( 'book-open', 'Scelta della referenza', 'Confronta ingredienti, formato e modalità d’impiego della singola scheda prima di scegliere la specialità o l’accessorio.' ),
			array( 'tag', 'Prima dell’uso', 'Leggi sempre etichetta e istruzioni per uso, conservazione e manutenzione quando previste.' ),
		),
		'tin-caviar' => array(
			array( 'package-check', 'Confezionamento', 'Le lattine sono pensate per il confezionamento del caviale e delle uova di pesce.' ),
			array( 'sparkles', 'La presentazione', 'Scegli il formato più adatto alla referenza e al contesto di servizio consultando le specifiche del prodotto.' ),
			array( 'book-open', 'Materiali e formati', 'La collezione comprende diverse soluzioni in metallo. Le caratteristiche sono descritte nella scheda.' ),
			array( 'tag', 'Prima dell’uso', 'Verifica materiali, misure e indicazioni specifiche prima del confezionamento.' ),
		),
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
	);
	$guide_slug = $is_sturgeon_caviar ? 'caviareat' : ( 'caviareat' === $slug ? ( 'caviale-vegano' === $product_slug ? 'caviareat-vegano' : 'caviareat-acciughe' ) : $slug );
	if ( ! isset( $guides[ $guide_slug ] ) ) return '';
	$items = $guides[ $guide_slug ];
	$images = ugolini_group_product_editorial_media()['guide'];
	if ( ! $images ) $images = $term instanceof WP_Term ? ugolini_group_editorial_images( $term ) : array();
	if ( ! $images ) $images = array( 'https://ugolinigroup.com/wp-content/uploads/2026/08/8831-sugo-tartufo-nero-ugolini-gourmet-7.jpg' );
	$html  = '';
	foreach ( $items as $index => $item ) {
		$html .= '<article class="ugolini-product-guide__item"><div><h3>' . ugolini_group_icon( $item[0] ) . esc_html( $item[1] ) . '</h3><p>' . nl2br( esc_html( str_replace( '\\n', "\n", $item[2] ) ) ) . '</p></div><img src="' . esc_url( $images[ $index % count( $images ) ] ) . '" alt="" loading="lazy"></article>';
	}
	$image = $images[0];
	$style = $image ? ' style="--ugolini-sticky-image:url(\'' . esc_url( $image ) . '\')"' : '';
	return '<section class="ugolini-product-guide"><div class="alignwide"><div class="ugolini-product-guide__list">' . $html . '</div><div class="ugolini-product-guide__visual"' . $style . '><span>Guida alla collezione</span><h2>Assaggia, abbina, conosci</h2><p>Indicazioni pratiche per valorizzare questa famiglia di prodotti.</p></div></div></section>';
}
add_shortcode( 'ugolini_product_guide', 'ugolini_group_product_guide_shortcode' );

/** Collection editorial, rendered as its own section immediately after product details. */
function ugolini_group_product_editorial() {
	$post_id = get_queried_object_id();
	if ( ! is_singular( 'sc_product' ) ) return '';
	$terms = wp_get_post_terms( $post_id, 'sc_collection' );
	$term = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	if ( ! $term instanceof WP_Term ) return '';
	$slug = $term->slug;
	$non_sturgeon = in_array( get_post_field( 'post_name', $post_id ), array( 'caviale-vegano', 'acciughe-sott-olio-100-gr' ), true );
	if ( 'caviareat' !== $slug || $non_sturgeon ) {
		$copy = array(
			'caviareat'       => array( 'Le specialità CaviarEat', 'La collezione riunisce caviali di storione, caviale vegano e filetti di acciughe. Le referenze presentano caratteristiche e formati differenti, descritti nelle rispettive schede prodotto.', 'Ogni prodotto ha caratteristiche, formati e indicazioni propri; la singola scheda ne riporta i dettagli.' ),
			'luxureat'        => array( 'L’universo LuxurEat', 'La gamma riunisce tartufi, caviali, tartare e altre specialità gourmet. Comprende inoltre oli, salse e preparazioni al tartufo: ingredienti e formati cambiano da una referenza all’altra.', 'La gamma comprende anche oli, salse e preparazioni al tartufo, con referenze diverse per ingredienti e formato.' ),
			'marmellate'      => array( 'Marmellate gastronomiche', 'La confit di cipolle rosse all’Aceto Balsamico di Modena IGP ha una consistenza morbida e un profilo agrodolce. È proposta come accompagnamento per formaggi, salumi e carni.', 'La confit di cipolle rosse all’Aceto Balsamico di Modena IGP unisce consistenza morbida e profilo agrodolce.' ),
			'olio-al-tartufo' => array( 'Oli al tartufo', 'Due referenze a base di olio extra vergine di oliva portano il profumo del tartufo bianco o nero nel piatto. Sono pensate come condimento per crostini, ripieni, antipasti, primi e secondi.', 'Due referenze pensate come condimento per crostini, ripieni, antipasti, primi e secondi piatti.' ),
			'pesto'           => array( 'Pesti Ugolini Gourmet', 'La collezione comprende il Pesto alla Genovese, le varianti rosse, il pesto vegano e quello al tartufo. Le diverse ricette possono accompagnare pasta, bruschette e altre preparazioni.', 'Le diverse ricette della collezione possono accompagnare pasta, bruschette e altre preparazioni.' ),
			'salse-funghi'    => array( 'Salse ai funghi', 'Le creme ai porcini e champignon riuniscono due referenze con caratteri differenti. La collezione è pensata per accompagnare risotti, pasta fresca, crostini e ripieni; ingredienti e formati sono indicati in ogni scheda.', 'Le salse della collezione sono pensate per risotti, pasta fresca, crostini e ripieni.' ),
			'salse-tartufo'   => array( 'Specialità al tartufo', 'Salse e creme al tartufo nero, bianco e bianchetto affiancano pesto, ketchup e sugo al tartufo, oltre al tartufo estivo intero. La collezione comprende ricette e formati diversi per completare i piatti.', 'La collezione comprende salsa tartufata, creme, pesto, ketchup, sugo al tartufo e tartufo estivo intero.' ),
			'sughi'           => array( 'Sughi Ugolini Gourmet', 'La gamma comprende il ragù alla Bolognese, proposte vegane e piccanti, condimenti al pomodoro e sughi al tartufo. Sono ricette pronte per accompagnare pasta e altri piatti conviviali.', 'Dal ragù alla Bolognese alle proposte vegane e piccanti, la gamma comprende condimenti al pomodoro e sughi al tartufo.' ),
			'tin-caviar'      => array( 'Tin Caviar', 'La collezione presenta lattine per il confezionamento del caviale e delle uova di pesce. La referenza comprende formati in banda stagnata e alluminio; i dettagli sono riportati nella scheda prodotto.', 'La referenza della collezione comprende lattine in banda stagnata e alluminio.' ),
			'truffleat'       => array( 'Le specialità Truffleat', 'La gamma riunisce tartufi, oli, salse e condimenti, accanto ad accessori dedicati. Comprende anche pasta, riso e strumenti per il taglio del tartufo: ogni referenza ha caratteristiche proprie.', 'La gamma comprende anche pasta, riso e strumenti per il taglio del tartufo.' ),
		);
		if ( ! isset( $copy[ $slug ] ) ) return '';
		$images = ugolini_group_product_editorial_media( $post_id )['photos'];
		if ( ! $images ) $images = ugolini_group_editorial_images( $term, 2 );
		$series_images = array(
			'marmellate' => array( '192687642_m.jpg', '181382010_m.jpg' ),
			'olio-al-tartufo' => array( '98843234_m.jpg', '178273414_m.jpg' ),
			'salse-funghi' => array( '74609046_m.jpg', '229065274_m.jpg' ),
			'tin-caviar' => array( '211109345_m.jpg', '203333167_m.jpg' ),
		);
		if ( ! ugolini_group_product_editorial_media( $post_id )['photos'] && isset( $series_images[ $slug ] ) ) {
			$images = array_map( static fn( $file ) => get_theme_file_uri( 'assets/images/series-editorial/' . $file ), $series_images[ $slug ] );
		}
		if ( ! $images ) return '';
		$products = get_posts( array( 'post_type' => 'sc_product', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'sc_collection', 'field' => 'term_id', 'terms' => $term->term_id ) ) ) );
		$names = '';
		foreach ( $products as $product_id ) $names .= '<li>' . esc_html( get_the_title( $product_id ) ) . '</li>';
		$data = $copy[ $slug ];
		$description = trim( wp_strip_all_tags( term_description( $term ) ) ) ?: $data[1];
		$editorial = '<section class="ugolini-caviareat-editorial ugolini-caviareat-editorial--series" aria-label="' . esc_attr( $term->name ) . '"><div class="ugolini-caviareat-editorial__row"><div><h2>' . esc_html( $data[0] ) . '</h2><p>' . esc_html( $description ) . '</p><p>' . esc_html( $data[1] ) . '</p></div><img src="' . esc_url( $images[0] ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy"></div><div class="ugolini-caviareat-editorial__row"><img src="' . esc_url( $images[1] ?? $images[0] ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy"><div class="ugolini-caviareat-editorial__copy-grid"><div><h3>La collezione</h3><p>' . esc_html( $data[2] ) . '</p><h3>Ogni prodotto ha la sua scheda</h3><p>Consulta ingredienti, formati e indicazioni d’uso della singola referenza.</p></div><div><h3>' . esc_html( $term->name ) . '</h3><p>' . esc_html( $description ) . '</p><ul>' . $names . '</ul></div></div></div></section>';
		return '<section class="ugolini-caviareat-editorial-shell">' . $editorial . '</section>';
	}
	$caviar_images = ugolini_group_product_editorial_media( $post_id )['photos'];
	$spoons = $caviar_images[0] ?? get_theme_file_uri( 'assets/images/product-editorial/caviar.jpg' );
	$ice = $caviar_images[1] ?? get_theme_file_uri( 'assets/images/product-editorial/caviar2.jpg' );
	$editorial = '<section class="ugolini-caviareat-editorial" aria-label="CaviarEat"><div class="ugolini-caviareat-editorial__row"><div><h2>L’Equilibrio Perfetto tra Natura e Innovazione</h2><p>Ogni fase del nostro processo nasce dal rispetto per la vita e per l’ambiente.</p><p>Tecnologia, esperienza e attenzione quotidiana si fondono per creare un ecosistema armonioso, dove lo storione cresce in modo sano e naturale.</p><p>Un equilibrio che si riflette nella purezza e nell’eccellenza del nostro caviale.</p></div><img src="' . esc_url( $spoons ) . '" alt="Caviale nero, fotografia illustrativa" loading="lazy"></div><div class="ugolini-caviareat-editorial__row"><img src="' . esc_url( $ice ) . '" alt="Caviale nero, fotografia illustrativa" loading="lazy"><div class="ugolini-caviareat-editorial__copy-grid"><div><h3>Sapori autentici</h3><p>Ogni perla di caviale racchiude il piacere autentico della vita.</p><h3>La Cultura del Gusto</h3><p>Il caviale per noi è arte, tradizione e conoscenza.</p><p>Frutto di anni di esperienza e di una visione artigianale che valorizza la materia prima, rappresenta l’incontro tra passione italiana e perfezione naturale.</p></div><div><h3>CaviarEat</h3><p>Un invito a vivere ogni istante con eleganza e passione.</p><ul><li>Selezionato dalle acque più pure</li><li>Lavorato con cura artigianale</li><li>Sfumature, aromi e consistenze per veri intenditori.</li></ul></div></div></div></section>';
	return '<section class="ugolini-caviareat-editorial-shell">' . $editorial . '</section>';
}
/** Product-specific licensed films between facts and collection content. */
function ugolini_group_product_films() {
	if ( ! is_singular( 'sc_product' ) ) return '';
	$path = get_theme_file_path( 'data/product-films.json' );
	$data = is_readable( $path ) ? json_decode( file_get_contents( $path ), true ) : null;
	$slugs = $data['products'][ get_post_field( 'post_name', get_queried_object_id() ) ] ?? array();
	if ( ! $slugs ) return '';
	$html = '<section class="ugolini-product-films-shell" aria-label="Video delle specialità"><div class="ugolini-product-films">';
	foreach ( $slugs as $slug ) {
		$film = $data['media'][ $slug ] ?? null;
		if ( ! $film ) continue;
		$base = 'assets/videos/' . $film['file'];
		$html .= '<video autoplay muted loop playsinline controls preload="none" poster="' . esc_url( get_theme_file_uri( $base . '.jpg' ) ) . '" aria-label="' . esc_attr( $film['alt'] ) . '" src="' . esc_url( get_theme_file_uri( $base . '.mp4' ) ) . '"></video>';
	}
	return $html . '</div></section>';
}
function ugolini_group_caviareat_editorial_after_details( $html, $block ) {
	if ( is_singular( 'sc_product' ) && in_array( $block['blockName'] ?? '', array( 'core/shortcode', 'core/paragraph' ), true ) && str_contains( $html, '[ugolini_related_products]' ) ) return '';
	if ( is_singular( 'sc_product' ) && 'core/group' === ( $block['blockName'] ?? '' ) && str_contains( $block['attrs']['className'] ?? '', 'ugolini-related-products' ) ) return ugolini_group_related_products_shortcode();
	if ( is_page( 'b2b' ) && 'core/html' === ( $block['blockName'] ?? '' ) && str_contains( $html, '<section class="ugolini-wholesale-services"' ) ) {
		return str_replace( '<section class="ugolini-wholesale-services"', '<section id="dalla-selezione-al-riordino" class="ugolini-wholesale-services"', $html );
	}
	if ( 'core/html' === ( $block['blockName'] ?? '' ) && str_contains( $html, '/assets/images/ugolini-family.png' ) ) return str_replace( '/wp-content/themes/ugolini-group/assets/images/ugolini-family.png', 'https://ugolinigroup.com/wp-content/uploads/2026/10/ugolini-family.png', $html );
	if ( is_page( 'chi-siamo' ) && 'core/html' === ( $block['blockName'] ?? '' ) && str_contains( $html, 'class="ugolini-timeline"' ) ) {
		$moments = array(
			array( 'Le origini', 'Renato e Renata', 'Nonno Renato avvia l’attività agricola di famiglia; sua figlia Renata porta personalmente il raccolto al mercato. Questo rapporto diretto con il cibo fresco e naturale diventa parte della memoria di Roberto.', get_theme_file_uri( 'assets/images/about/timeline-1.jpg' ) ),
			array( 'L’esperienza', 'Dalla tavola alla cucina', 'Roberto cresce in questa cultura familiare e lavora per oltre vent’anni nella ristorazione. Il confronto con chef stellati gli permette di tradurre le esigenze quotidiane di cucina in criteri di qualità e funzionalità.', get_theme_file_uri( 'assets/images/about/timeline-2.jpg' ) ),
			array( 'Il progetto', 'Le prime salse gourmet', 'Dalla collaborazione con lo chef Nino Cannavale nasce la prima linea di salse gourmet e al tartufo. Ugolini Gourmet unisce così la memoria gastronomica di famiglia a prodotti pensati per la cucina professionale e la tavola.', get_theme_file_uri( 'assets/images/about/timeline-3.jpg' ) ),
			array( 'La gamma oggi', 'Una scelta più ampia', 'La linea si sviluppa per professionisti, distribuzione e famiglie. Alle salse si aggiungono proposte vegane e biologiche; eventuali certificazioni Halal o Kosher restano specifiche delle singole referenze.', get_theme_file_uri( 'assets/images/about/timeline-4.png' ) ),
		);
		$articles = '';
		foreach ( $moments as $moment ) $articles .= '<article><div><small>' . esc_html( $moment[0] ) . '</small><h3>' . esc_html( $moment[1] ) . '</h3><p>' . esc_html( $moment[2] ) . '</p><figure data-story-reveal><img src="' . esc_url( $moment[3] ) . '" alt="' . esc_attr( $moment[1] ) . '" loading="lazy"></figure></div><i aria-hidden="true">✦</i></article>';
		return '<section class="ugolini-timeline ugolini-timeline--about" data-timeline aria-labelledby="ugolini-about-timeline-title"><div class="ugolini-timeline__inner"><header><h2 id="ugolini-about-timeline-title">Tradizione, esperienza e innovazione</h2><span aria-hidden="true"></span></header><div class="ugolini-timeline__track">' . $articles . '</div></div></section>';
	}
	if ( is_page( 'shop' ) && 'core/group' === ( $block['blockName'] ?? '' ) ) {
		$class = $block['attrs']['className'] ?? '';
		if ( str_contains( $class, 'ugolini-home-collections' ) ) return '';
	}
	if ( is_tax( 'sc_collection' ) && 'core/group' === ( $block['blockName'] ?? '' ) && str_contains( $block['attrs']['className'] ?? '', 'ugolini-collection-editorial-section' ) ) return $html . do_blocks( ugolini_group_page_pattern_content( 'professional-assurances' ) );
	if ( 'core/group' !== ( $block['blockName'] ?? '' ) || ! str_contains( $block['attrs']['className'] ?? '', 'ugolini-product-information' ) ) return $html;
	return ugolini_group_product_films() . $html . ugolini_group_product_editorial() . ugolini_group_related_products_shortcode();
}
add_filter( 'render_block', 'ugolini_group_caviareat_editorial_after_details', 10, 2 );

/** Keep only the eight public editorial pages in the WordPress page sitemap. */
function ugolini_group_public_page_slugs() {
	return array( 'home', 'shop', 'chi-siamo', 'galleria', 'events', 'b2b', 'blog', 'faq', 'contatti', 'supporto', 'privacy-policy', 'cookie-policy', 'termini-e-condizioni', 'spedizioni-e-resi' );
}

add_filter( 'wp_sitemaps_posts_query_args', static function( $args, $post_type ) {
	if ( 'page' !== $post_type ) return $args;
	$ids = array();
	foreach ( ugolini_group_public_page_slugs() as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) $ids[] = $page->ID;
	}
	$args['post__in'] = $ids ?: array( 0 );
	return $args;
}, 10, 2 );

/** The catalogue route is /products/, even though its WordPress page is shop. */
add_filter( 'get_canonical_url', static function( $url, $post ) {
	if ( $post->ID !== get_queried_object_id() ) return $url;
	if ( is_page( 'shop' ) ) return home_url( '/products/' );
	if ( is_page( 'events' ) && ( $event = ugolini_group_requested_event() ) ) return ugolini_group_event_url( $event );
	return $url;
}, 10, 2 );

add_filter( 'wp_sitemaps_posts_entry', static function( $entry, $post, $post_type ) {
	if ( 'page' === $post_type && 'shop' === $post->post_name ) $entry['loc'] = home_url( '/products/' );
	return $entry;
}, 10, 3 );

/** Event details are query URLs backed by data/events.json, so they need their own sitemap. */
add_action( 'wp_sitemaps_init', static function() {
	wp_register_sitemap_provider( 'events', new class extends WP_Sitemaps_Provider {
		protected $name = 'events';
		protected $object_type = 'event';

		public function get_url_list( $page_num, $object_subtype = '' ) {
			$page = get_page_by_path( 'events' );
			if ( 1 !== (int) $page_num || ! $page || 'publish' !== $page->post_status ) return array();
			$urls = array();
			foreach ( ugolini_group_events_data() as $event ) {
				if ( ! empty( $event['detailHtml'] ) ) $urls[] = array( 'loc' => ugolini_group_event_url( $event ) );
			}
			return $urls;
		}

		public function get_max_num_pages( $object_subtype = '' ) {
			$page = get_page_by_path( 'events' );
			if ( ! $page || 'publish' !== $page->post_status ) return 0;
			foreach ( ugolini_group_events_data() as $event ) {
				if ( ! empty( $event['detailHtml'] ) ) return 1;
			}
			return 0;
		}
	} );
} );

add_filter( 'wp_sitemaps_add_provider', static function( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

/** Present the existing WordPress sitemap entries as one live URL list. */
add_action( 'parse_request', static function() {
	if ( ! in_array( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), array( '/sitemap.xml', '/ugolini-sitemap.xml' ), true ) ) return;
	$server = wp_sitemaps_get_server();
	$urls = array();
	foreach ( $server->registry->get_providers() as $provider ) {
		foreach ( $provider->get_sitemap_type_data() as $type ) {
			for ( $page = 1; $page <= $type['pages']; $page++ ) {
				$urls = array_merge( $urls, $provider->get_url_list( $page, $type['name'] ) );
			}
		}
	}
	$server->renderer->render_sitemap( $urls );
	exit;
}, 0 );

add_filter( 'robots_txt', static function( $output ) {
	return str_replace( home_url( '/wp-sitemap.xml' ), home_url( '/ugolini-sitemap.xml' ), $output );
}, 20 );

add_filter( 'wp_robots', static function( $robots ) {
	if ( is_author() ) return wp_robots_no_robots( $robots );
	if ( is_page() && ! is_page( ugolini_group_public_page_slugs() ) ) return wp_robots_no_robots( $robots );
	return $robots;
} );

add_action( 'wp_head', static function() {
	if ( ! is_front_page() ) return;
	echo '<meta name="google-site-verification" content="toiuSxyGfHYXxxKSD-yP7M3Y9zf11Kh1WwOIxkGYxoQ" />' . "\n";
	echo '<meta name="baidu-site-verification" content="codeva-dyDL5Tym1F" />' . "\n";
} );

/** Supply editorial metadata where SureCart does not already own the page head. */
add_action( 'wp_head', static function() {
	if ( is_404() || is_search() || is_author() || is_singular( 'sc_product' ) ) return;
	if ( ! is_front_page() && ! is_singular( array( 'page', 'post', 'ugolini_news' ) ) && ! is_tax( 'sc_collection' ) && ! is_category() ) return;
	if ( is_page() && ! is_page( ugolini_group_public_page_slugs() ) ) return;

	$descriptions = array(
		'home'                 => 'Scopri Ugolini Gourmet: salse, tartufi, caviale e specialità italiane selezionate per portare gusto e qualità in tavola.',
		'shop'                 => 'Esplora le collezioni Ugolini Gourmet: salse, sughi, pesti, tartufi, caviale e altre specialità italiane.',
		'chi-siamo'            => 'Conosci la storia, la famiglia e la passione per la gastronomia italiana che guidano Ugolini Gourmet.',
		'galleria'             => 'Esplora la galleria aziendale Ugolini Gourmet: foto e video dagli eventi, dalle fiere internazionali e dal mondo dei nostri brand.',
		'events'               => 'Scopri gli eventi e le novità di Ugolini Gourmet, LuxurEat, Truffleat e Caviareat.',
		'b2b'                  => 'Scopri le specialità Ugolini Gourmet per professionisti, distributori e partner commerciali.',
		'blog'                 => 'Leggi gli articoli Ugolini Gourmet su ingredienti, cucina, ricette e specialità italiane.',
		'faq'                  => 'Trova risposte alle domande frequenti su prodotti, ordini, spedizioni e assistenza Ugolini Gourmet.',
		'contatti'             => 'Contatta Ugolini Gourmet per informazioni sui prodotti, ordini e collaborazioni commerciali.',
		'supporto'             => 'Trova informazioni e assistenza per acquisti, spedizioni e prodotti Ugolini Gourmet.',
		'privacy-policy'       => 'Leggi l’informativa sulla privacy e sul trattamento dei dati personali di Ugolini Group.',
		'cookie-policy'        => 'Scopri come Ugolini Group utilizza i cookie e gestisce le tue preferenze.',
		'termini-e-condizioni' => 'Consulta i termini e le condizioni di vendita e utilizzo del sito Ugolini Group.',
		'spedizioni-e-resi'     => 'Informazioni su spedizioni, consegne, recesso, resi e rimborsi per gli acquisti Ugolini Gourmet.',
	);
	$image = get_theme_file_uri( 'assets/images/home-hero-ugolini.jpeg' );
	$url = '';
	$description = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
		$description = $descriptions['home'];
	} elseif ( is_page() ) {
		$page = get_queried_object();
		$url = is_page( 'shop' ) ? home_url( '/products/' ) : get_permalink( $page );
		$description = $descriptions[ $page->post_name ] ?? '';
		if ( is_page( 'events' ) && ( $event = ugolini_group_requested_event() ) ) {
			$url = ugolini_group_event_url( $event );
			$description = $event['description'];
			$image = get_theme_file_uri( 'assets/images/events/' . $event['image'] );
		} else {
			$heroes = array( 'chi-siamo' => 'about/about-hero.jpg', 'galleria' => 'page-heroes/gallery-hero-caviar.jpg', 'events' => 'page-heroes/events.jpg', 'b2b' => 'page-heroes/b2b.jpg', 'blog' => 'page-heroes/blog.jpg', 'faq' => 'page-heroes/faq.jpg', 'contatti' => 'page-heroes/contact.jpg', 'supporto' => 'support/hero.jpg', 'shop' => 'collection-heroes/all.jpg' );
			if ( isset( $heroes[ $page->post_name ] ) ) $image = get_theme_file_uri( 'assets/images/' . $heroes[ $page->post_name ] );
		}
		if ( ! $description ) $description = $page->post_excerpt ?: $page->post_content;
	} elseif ( is_singular( array( 'post', 'ugolini_news' ) ) ) {
		$post = get_queried_object();
		$url = get_permalink( $post );
		$description = $post->post_excerpt ?: $post->post_content;
		if ( has_post_thumbnail( $post ) ) $image = get_the_post_thumbnail_url( $post, 'full' );
	} else {
		$term = get_queried_object();
		$url = get_term_link( $term );
		if ( is_wp_error( $url ) ) return;
		$description = term_description( $term );
		if ( ! $description ) $description = 'Scopri ' . $term->name . ' e le specialità selezionate da Ugolini Gourmet.';
		if ( is_tax( 'sc_collection' ) ) {
			$path = 'assets/images/collection-heroes/' . $term->slug . '.jpg';
			if ( file_exists( get_theme_file_path( $path ) ) ) $image = get_theme_file_uri( $path );
		}
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
	}
	$description = wp_trim_words( trim( wp_strip_all_tags( strip_shortcodes( $description ) ) ), 28, '…' );
	$title = wp_get_document_title();
	echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( array( 'post', 'ugolini_news' ) ) ? 'article' : 'website' ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
}, 20 );

function ugolini_group_cooking_suggestions_shortcode() {
	if ( ! is_singular( 'sc_product' ) ) return '';
	$terms = wp_get_post_terms( get_queried_object_id(), 'sc_collection' );
	$term = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	if ( ! $term instanceof WP_Term ) return '';
	if ( in_array( $term->slug, array( 'luxureat', 'tin-caviar', 'truffleat' ), true ) ) return '';
	if ( 'caviareat' === $term->slug && in_array( get_post_field( 'post_name', get_the_ID() ), array( 'caviale-vegano', 'acciughe-sott-olio-100-gr' ), true ) ) return '';
	$copy = array(
		'caviareat'       => array( 'Idee da portare in tavola', array( 'Servire il caviale', 'Portalo in tavola ben fresco e aggiungilo all’ultimo momento, senza scaldarlo: così le perle conservano consistenza e sapore.' ), array( 'Bocconi delicati', 'Provalo su blinis, tartellette o pane leggermente tostato, con una piccola quantità di crema fresca che accompagni senza coprire il gusto.' ), array( 'Abbinamenti essenziali', 'Assaggialo anche da solo; per un piatto più ricco, completa con uova o patate tiepide e aggiungi il caviale solo prima di servire.' ) ),
		'luxureat'        => array( 'Idee da portare in tavola', array( 'Tartare e specialità di pesce', 'Condisci con misura e servi fresche: erbe delicate, agrumi e un filo d’olio lasciano spazio al sapore degli ingredienti.' ), array( 'Il tartufo a finitura', 'Aggiungi scaglie di tartufo o un condimento al tartufo a fine preparazione, su tartare, uova o primi piatti, per mantenerne il profumo.' ), array( 'Aperitivo gourmet', 'Componi un tagliere con piccoli assaggi, pane, olive e salumi; servi salse e condimenti a parte perché ciascuno possa dosarli.' ) ),
		'marmellate'      => array( 'Idee da portare in tavola', array( 'Con pane e formaggi', 'Servi una piccola quantità su crostini con formaggi freschi o stagionati: la nota agrodolce completa il boccone.' ), array( 'In cucina', 'Usa la confit per accompagnare carni arrosto o aggiungila a fine cottura, così da conservarne consistenza e carattere.' ), array( 'Per l’aperitivo', 'Portala in tavola con un tagliere di salumi e formaggi, servendola a parte perché ogni ospite possa dosarla a piacere.' ) ),
		'olio-al-tartufo' => array( 'Idee da portare in tavola', array( 'Insalate a finitura', 'Aggiungi poche gocce d’olio al tartufo all’insalata già condita, poi assaggia: il profumo deve accompagnare le verdure senza coprirle.' ), array( 'Con pomodoro e burrata', 'Versa l’olio solo prima di servire su pomodori, basilico e formaggio fresco. Parti da una dose leggera per mantenere l’equilibrio del piatto.' ), array( 'Pane e antipasti', 'Provalo su crostini o piccoli antipasti a fine preparazione; servi l’olio a parte per permettere a ciascuno di dosarlo.' ) ),
		'pesto'           => array( 'Idee da portare in tavola', array( 'Scegli il pesto', 'La gamma comprende ricette verdi, rosse e al tartufo: scegli la referenza in base agli ingredienti del piatto e aggiungila senza cuocerla a lungo.' ), array( 'Con la pasta', 'Amalgama il pesto alla pasta fuori dal fuoco con poca acqua di cottura, fino a ottenere un condimento uniforme e cremoso.' ), array( 'Anche su crostini', 'Servi una piccola quantità su pane tostato o focaccia; le varianti rosse aggiungono colore e sapore anche agli antipasti.' ) ),
		'salse-funghi'    => array( 'Idee da portare in tavola', array( 'Antipasti ai funghi', 'Scalda delicatamente la crema ai funghi e servila in piccole porzioni su crostini o accanto a verdure e formaggi freschi.' ), array( 'Con piatti caldi', 'Abbina la salsa a patate, polenta o secondi piatti; aggiungi poca acqua calda se desideri una consistenza più fluida.' ), array( 'Vellutate e risotti', 'Incorpora la crema ai funghi a fine cottura in un risotto o usane una piccola dose per arricchire una vellutata.' ) ),
		'salse-tartufo'   => array( 'Idee da portare in tavola', array( 'Con pesce e verdure', 'Completa il piatto pronto con una piccola quantità di salsa al tartufo, senza coprire il sapore degli ingredienti più delicati.' ), array( 'Pasta al tartufo', 'Scalda la salsa dolcemente e amalgamala alla pasta con poca acqua di cottura; aggiungine ancora dopo l’assaggio, se serve.' ), array( 'Crostini per l’aperitivo', 'Spalma un velo di crema al tartufo sul pane tostato e abbina formaggio fresco o salumi, dosando la salsa con misura.' ) ),
		'sughi'           => array( 'Idee da portare in tavola', array( 'Sugo al pomodoro', 'Scalda il sugo a fuoco dolce e usalo per condire pasta o gnocchi; completa il piatto con erbe fresche, se gradite.' ), array( 'Varianti più vivaci', 'Per le ricette piccanti, parti da una piccola quantità e assaggia prima di aggiungerne altra. Unisci poca acqua di cottura per legare il condimento alla pasta.' ), array( 'A tavola', 'Scegli la referenza secondo il piatto, dal pomodoro ai ragù; scalda solo la porzione necessaria e servi subito.' ) ),
		'tin-caviar'      => array( 'Idee per la presentazione', array( 'Crostini al caviale', 'Dopo aver aperto la confezione di caviale, servilo fresco su pane appena tostato e lasciato intiepidire, con un velo di burro o crema delicata.' ), array( 'Piccoli assaggi', 'Distribuisci il caviale in piccole porzioni su blinis o tartellette e aggiungilo solo prima di portarle in tavola.' ), array( 'Pane e accompagnamenti', 'Abbina il caviale a pane scuro e burro senza scaldare le perle; segui sempre le indicazioni di conservazione della referenza confezionata.' ) ),
		'truffleat'       => array( 'Idee da portare in tavola', array( 'Crostini al tartufo', 'Stendi una piccola quantità di salsa al tartufo sul pane tostato e completa il crostino poco prima di servirlo.' ), array( 'Tartare e tartufo', 'Per una tartare, usa scaglie o condimenti al tartufo con misura e aggiungili a fine preparazione, senza coprire il gusto della carne.' ), array( 'Con i secondi', 'Scalda dolcemente la salsa scelta e servine una piccola dose accanto a carne o verdure; assaggia prima di aggiungerne altra.' ) ),
	);
	$data = $copy[ $term->slug ] ?? $copy['salse-tartufo'];
	$series_images = array(
		'caviareat'  => array( '211115873_m.jpg', '206245340_m.jpg', '298347544_m.jpg' ),
		'luxureat'   => array( '253553877_m.jpg', '59780622_m.jpg', '166425501_m.jpg' ),
		'marmellate' => array( '34452330_m.jpg', '111613897_m.jpg', '32972926_m.jpg' ),
		'olio-al-tartufo' => array( '183259666_m.jpg', '302161853_m.jpg', '39045492_m.jpg' ),
		'pesto' => array( '166835018_m.jpg', '143312877_m.jpg', '182397301_m.jpg' ),
		'salse-funghi' => array( '130394549_m.jpg', '298811785_m.jpg', '162740461_m.jpg' ),
		'salse-tartufo' => array( '308823080_m.jpg', '322831973_m.jpg', '181619574_m.jpg' ),
		'sughi' => array( '120055713_m.jpg', '48652897_m.jpg', '83068629_m.jpg' ),
		'tin-caviar' => array( '315399848_m.jpg', '180091432_m.jpg', '316165762_m.jpg' ),
		'truffleat' => array( '179951066_m.jpg', '157737860_m.jpg', '225963806_m.jpg' ),
	);
	$images = isset( $series_images[ $term->slug ] )
		? array_map( static fn( $file ) => get_theme_file_uri( 'assets/images/cooking-suggestions/' . $file ), $series_images[ $term->slug ] )
		: ugolini_group_editorial_images( $term, 3 );
	if ( ! $images ) return '';
	$html = '';
	for ( $i = 1; $i <= 3; $i++ ) $html .= '<article><img src="' . esc_url( $images[ ( $i - 1 ) % count( $images ) ] ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy"><div><strong>' . esc_html( $data[ $i ][0] ) . '</strong><span>' . esc_html( $data[ $i ][1] ) . '</span></div></article>';
	return '<section class="ugolini-cooking" aria-label="' . esc_attr( $data[0] ) . '"><div class="ugolini-cooking-grid">' . $html . '</div></section>';
}
add_shortcode( 'ugolini_cooking_suggestions', 'ugolini_group_cooking_suggestions_shortcode' );

/** Recommend products from the same collection first, then fill any remaining places. */
function ugolini_group_related_products_shortcode() {
	if ( ! is_singular( 'sc_product' ) ) return '';
	static $rendered = false;
	if ( $rendered ) return '';
	$rendered = true;
	$product_id = get_queried_object_id();
	$terms = wp_get_post_terms( $product_id, 'sc_collection' );
	$term = is_array( $terms ) ? ( $terms[0] ?? null ) : null;
	$ids = array();
	if ( $term instanceof WP_Term ) {
		$ids = get_posts( array( 'post_type' => 'sc_product', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids', 'orderby' => 'rand', 'post__not_in' => array( $product_id ), 'tax_query' => array( array( 'taxonomy' => 'sc_collection', 'field' => 'term_id', 'terms' => $term->term_id ) ) ) );
	}
	$needs_extra = count( $ids ) < 3;
	if ( $needs_extra ) {
		$extra = get_posts( array( 'post_type' => 'sc_product', 'post_status' => 'publish', 'posts_per_page' => 3 - count( $ids ), 'fields' => 'ids', 'orderby' => 'rand', 'post__not_in' => array_merge( array( $product_id ), $ids ) ) );
		$ids = array_merge( $ids, $extra );
	}
	if ( ! $ids ) return '';
	$title = $needs_extra ? 'Altre proposte' : 'Altre specialità ' . $term->name;
	$grid = ugolini_group_live_product_grid( '', $ids, true );
	return '<section class="alignfull ugolini-section ugolini-related-products"><div class="ugolini-section-heading alignwide"><h2>' . esc_html( $title ) . '</h2><p class="ugolini-editorial-link"><a href="' . esc_url( home_url( '/products/' ) ) . '">Tutti i prodotti</a></p></div>' . $grid . '</section>';
}
add_shortcode( 'ugolini_related_products', 'ugolini_group_related_products_shortcode' );

/** Remove confirmed duplicate uploads from SureCart's rendered gallery. */
add_filter( 'render_block', static function( $html, $block ) {
	if ( ! is_singular( 'sc_product' ) || 'surecart/product-media' !== ( $block['blockName'] ?? '' ) ) return $html;
	$duplicates = array(
		265 => array( 403, 404, 405, 406, 407, 408, 409 ),
		267 => array( 403, 404, 405, 406, 407, 408, 409 ),
		268 => array( 403, 404, 405, 406, 407, 408, 409 ),
		269 => array( 403, 404, 405, 406, 407, 408, 409 ),
		270 => array( 411, 412, 413, 414, 416 ),
		276 => array( 403, 404, 405, 406, 407, 408, 409 ),
	);
	$remove = $duplicates[ get_queried_object_id() ] ?? array();
	if ( ! $remove || ! class_exists( 'DOMDocument' ) ) return $html;
	$dom = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$loaded = $dom->loadHTML( '<?xml encoding="utf-8"?><div id="ugolini-gallery-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	if ( ! $loaded ) return $html;
	$xpath = new DOMXPath( $dom );
	foreach ( $remove as $id ) {
		foreach ( $xpath->query( '//*[@data-wp-key="' . $id . '"]' ) as $node ) $node->parentNode->removeChild( $node );
	}
	foreach ( $xpath->query( '//*[@data-wp-context]' ) as $node ) {
		$context = json_decode( $node->getAttribute( 'data-wp-context' ), true );
		if ( ! is_array( $context ) || ! isset( $context['images'] ) ) continue;
		$context['images'] = array_values( array_filter( $context['images'], static fn( $image ) => ! in_array( is_array( $image ) ? ( $image['id'] ?? null ) : $image, $remove, true ) ) );
		$node->setAttribute( 'data-wp-context', wp_json_encode( $context ) );
	}
	$root = $dom->getElementById( 'ugolini-gallery-root' );
	if ( ! $root ) return $html;
	$output = '';
	foreach ( $root->childNodes as $child ) $output .= $dom->saveHTML( $child );
	return $output;
}, 20, 2 );

/** Share the canonical detail URL; Instagram receives a copied link because it has no web URL share dialog. */
function ugolini_group_share_markup( $url, $title ) {
	$assets = get_theme_file_uri( 'assets/images/social/' );
	$facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
	$x = 'https://x.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
	$copy_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>';
	return '<section class="ugolini-share" data-share data-share-url="' . esc_attr( $url ) . '" aria-label="Condividi questa pagina"><div class="ugolini-share__actions">'
		. '<button type="button" data-share-copy>' . $copy_icon . '<span data-share-copy-label aria-live="polite">Copia link</span></button>'
		. '<a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" data-share-instagram aria-label="Copia il link e apri Instagram"><img src="' . esc_url( $assets . 'instagram.png' ) . '" alt=""><span>Instagram</span></a>'
		. '<a href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener noreferrer" data-share-facebook><img src="' . esc_url( $assets . 'facebook.png' ) . '" alt=""><span>Facebook</span></a>'
		. '<a href="' . esc_url( $x ) . '" target="_blank" rel="noopener noreferrer" data-share-x><img src="' . esc_url( $assets . 'x.png' ) . '" alt=""><span>X</span></a>'
		. '</div></section>';
}

function ugolini_group_share_shortcode() {
	if ( ! is_singular( array( 'post', 'ugolini_news' ) ) ) return '';
	return ugolini_group_share_markup( get_permalink(), get_the_title() );
}
add_shortcode( 'ugolini_share', 'ugolini_group_share_shortcode' );

/** Render circular previous/next links so the first and last posts connect. */
function ugolini_group_post_navigation_shortcode() {
	if ( ! is_singular( array( 'post', 'ugolini_news' ) ) ) {
		return '';
	}

	$type     = get_post_type();
	$previous = get_next_post();
	$next     = get_previous_post();
	if ( ! $previous ) {
		$previous = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 1, 'post__not_in' => array( get_the_ID() ), 'orderby' => 'date', 'order' => 'ASC' ) )[0] ?? null;
	}
	if ( ! $next ) {
		$next = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 1, 'post__not_in' => array( get_the_ID() ), 'orderby' => 'date', 'order' => 'DESC' ) )[0] ?? null;
	}

	$link = static function ( $post, $label ) {
		return $post ? '<a href="' . esc_url( get_permalink( $post ) ) . '"><small>' . esc_html( $label ) . '</small><span>' . esc_html( get_the_title( $post ) ) . '</span></a>' : '<span></span>';
	};
	return '<nav class="ugolini-post-navigation alignwide" aria-label="' . esc_attr__( 'Navigazione articoli', 'ugolini-group' ) . '">' . $link( $previous, '← Articolo precedente' ) . $link( $next, 'Articolo successivo →' ) . '</nav>';
}
add_shortcode( 'ugolini_post_navigation', 'ugolini_group_post_navigation_shortcode' );

/** Render three recommendations without repeating the current article. */
function ugolini_group_related_posts_shortcode() {
	if ( ! is_singular( array( 'post', 'ugolini_news' ) ) ) {
		return '';
	}
	$query = new WP_Query(
		array(
			'post_type'           => get_post_type(),
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
				'post_type'      => get_post_type(),
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
	$links = '<ul class="ugolini-footer-collection-links"><li><a href="' . esc_url( home_url( '/products/' ) ) . '">' . esc_html__( 'Tutti i prodotti', 'ugolini-group' ) . '</a></li>';
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
	return '<button class="ugolini-search-open" type="button" aria-expanded="false" aria-controls="ugolini-search-dialog">' . ugolini_group_icon( 'search' ) . '<span class="screen-reader-text">' . esc_html__( 'Apri ricerca', 'ugolini-group' ) . '</span></button>' .
		'<div id="ugolini-search-dialog" class="ugolini-search-dialog" role="dialog" aria-modal="true" aria-labelledby="ugolini-search-title" hidden><div class="ugolini-search-dialog-inner"><button class="ugolini-search-close" type="button">' . ugolini_group_icon( 'x' ) . '<span class="screen-reader-text">' . esc_html__( 'Chiudi ricerca', 'ugolini-group' ) . '</span></button><form action="' . esc_url( home_url( '/' ) ) . '" method="get" role="search" data-live-search data-search-endpoint="' . esc_url( rest_url( 'ugolini/v1/search' ) ) . '"><label id="ugolini-search-title" for="ugolini-search-input">' . esc_html__( 'Cerca prodotti, collezioni e ricette', 'ugolini-group' ) . '</label><div class="ugolini-search-row"><input id="ugolini-search-input" name="s" type="search" placeholder="' . esc_attr__( 'Cosa stai cercando?', 'ugolini-group' ) . '" autocomplete="off" aria-controls="ugolini-live-search-results"><button type="submit">' . ugolini_group_icon( 'search' ) . esc_html__( 'Cerca', 'ugolini-group' ) . '</button></div><div id="ugolini-live-search-results" class="ugolini-live-search" aria-live="polite" hidden></div></form></div></div>';
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
	$cart = shortcode_exists( 'sc_cart_menu_icon' )
		? '<span class="ugolini-surecart-cart">' . do_shortcode( '[sc_cart_menu_icon cart_icon="shopping-bag" cart_menu_always_shown=1]' ) . '</span>'
		: '<a class="ugolini-header-icon" href="' . esc_url( home_url( '/checkout/' ) ) . '" aria-label="' . esc_attr__( 'Carrello', 'ugolini-group' ) . '">' . ugolini_group_icon( 'shopping-bag' ) . '</a>';
	return $account . $cart;
}
add_shortcode( 'ugolini_header_commerce', 'ugolini_group_header_commerce_shortcode' );

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

/** Reject saved non-Italian shipping addresses during SureCart checkout finalization. */
add_filter( 'surecart/checkout/validate', function ( $errors, $args ) {
	$shipping_address = $args['shipping_address'] ?? array();
	$country = is_array( $shipping_address ) ? strtoupper( (string) ( $shipping_address['country'] ?? '' ) ) : '';
	if ( $country && 'IT' !== $country ) {
		$errors->add( 'ugolini_shipping_country', 'La spedizione online è disponibile solo in Italia. Contattaci per altre destinazioni.' );
	}
	return $errors;
}, 10, 2 );

/** Prioritize the payment options offered in the Stripe Payment Element. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'wp-hooks' );
	wp_add_inline_script(
		'wp-hooks',
		"wp.hooks.addFilter('surecart_stripe_payment_element_payment_method_order', 'ugolini-group/payment-method-order', function () { return ['card', 'satispay', 'revolut_pay', 'alipay']; });"
	);
} );
