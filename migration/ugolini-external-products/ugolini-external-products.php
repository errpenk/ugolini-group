<?php
/**
 * Plugin Name: Ugolini External Products Import
 * Description: Idempotent SureCart import for Ugolini external brands.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ugolini_external_catalog() {
	$file = __DIR__ . '/products.json';
	$data = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
	return is_array( $data ) ? $data : array( 'collections' => array(), 'products' => array() );
}

function ugolini_external_existing_product( $slug ) {
	$product = \SureCart\Models\Product::find( $slug );
	return ! is_wp_error( $product ) && ! empty( $product->id ) ? $product : false;
}

add_action(
	'admin_menu',
	static function () {
		add_management_page(
			'Ugolini External Products',
			'Ugolini External Products',
			'manage_options',
			'ugolini-external-products',
			'ugolini_external_products_page'
		);
	}
);

function ugolini_external_products_page() {
	$catalog = ugolini_external_catalog();
	$total   = count( $catalog['collections'] ) + count( $catalog['products'] );
	$nonce   = wp_create_nonce( 'ugolini_external_import' );
	?>
	<div class="wrap">
		<h1>Ugolini External Products</h1>
		<p>Creates <?php echo esc_html( count( $catalog['collections'] ) ); ?> SureCart collections and imports <?php echo esc_html( count( $catalog['products'] ) ); ?> products. Existing slugs are skipped.</p>
		<button id="ugolini-external-start" class="button button-primary">Start / Resume Import</button>
		<progress id="ugolini-external-progress" max="<?php echo esc_attr( $total ); ?>" value="0" style="display:block;width:100%;margin:16px 0"></progress>
		<pre id="ugolini-external-log" style="background:#fff;padding:16px;max-height:420px;overflow:auto"></pre>
	</div>
	<script>
	(() => {
		const button = document.getElementById('ugolini-external-start');
		const progress = document.getElementById('ugolini-external-progress');
		const log = document.getElementById('ugolini-external-log');
		button.addEventListener('click', async () => {
			button.disabled = true;
			for (let index = 0; index < Number(progress.max); index++) {
				const body = new URLSearchParams({action: 'ugolini_external_import_one', nonce: <?php echo wp_json_encode( $nonce ); ?>, index});
				try {
					const response = await fetch(ajaxurl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body});
					const result = await response.json();
					log.textContent += `${result.success ? 'OK' : 'ERROR'} ${index + 1}/${progress.max}: ${result.data?.message || JSON.stringify(result.data)}\n`;
				} catch (error) {
					log.textContent += `ERROR ${index + 1}/${progress.max}: ${error.message}\n`;
				}
				progress.value = index + 1;
				log.scrollTop = log.scrollHeight;
			}
			button.disabled = false;
			log.textContent += 'QUEUED — allow SureCart a few minutes to synchronize products and media.\n';
		});
	})();
	</script>
	<?php
}

add_action(
	'wp_ajax_ugolini_external_import_one',
	static function () {
		check_ajax_referer( 'ugolini_external_import', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}
		if ( ! class_exists( '\\SureCart\\Models\\ProductImport' ) ) {
			wp_send_json_error( array( 'message' => 'SureCart product imports are unavailable.' ) );
		}

		set_time_limit( 120 );
		$catalog     = ugolini_external_catalog();
		$collections = $catalog['collections'];
		$index       = absint( $_POST['index'] ?? 0 );

		if ( $index < count( $collections ) ) {
			$collection = $collections[ $index ];
			$existing   = \SureCart\Models\ProductCollection::find( $collection['slug'] );
			if ( ! is_wp_error( $existing ) && ! empty( $existing->id ) ) {
				wp_send_json_success( array( 'message' => 'Skipped existing collection ' . $collection['name'] ) );
			}
			$created = \SureCart\Models\ProductCollection::create( $collection );
			if ( is_wp_error( $created ) ) {
				wp_send_json_error( array( 'message' => $created->get_error_message() ) );
			}
			wp_send_json_success( array( 'message' => 'Created collection ' . $collection['name'] ) );
		}

		$product = $catalog['products'][ $index - count( $collections ) ] ?? null;
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'Invalid import row.' ) );
		}
		$existing = ugolini_external_existing_product( $product['slug'] );
		if ( $existing ) {
			$collection_ids = (array) ( $existing->product_collections ?? array() );
			foreach ( $product['product_collection_slugs'] ?? array() as $collection_slug ) {
				$collection = \SureCart\Models\ProductCollection::find( $collection_slug );
				if ( ! is_wp_error( $collection ) && ! empty( $collection->id ) ) {
					$collection_ids[] = $collection->id;
				}
			}
			$updated = $existing->update( array( 'product_collections' => array_values( array_unique( $collection_ids ) ) ) );
			if ( is_wp_error( $updated ) ) {
				wp_send_json_error( array( 'message' => $updated->get_error_message() ) );
			}
			wp_send_json_success( array( 'message' => 'Skipped existing product ' . $product['name'] ) );
		}

		$import = \SureCart\Models\ProductImport::create( array( 'data' => array( $product ) ) );
		if ( is_wp_error( $import ) ) {
			wp_send_json_error( array( 'message' => $import->get_error_message() ) );
		}
		wp_send_json_success(
			array(
				'message' => sprintf(
					'Queued %s (import %s)',
					$product['name'],
					$import->id ?? 'pending'
				),
			)
		);
	}
);
