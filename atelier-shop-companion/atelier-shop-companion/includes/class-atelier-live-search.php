<?php
/**
 * Atelier Companion Instant AJAX Live Product Search Module.
 *
 * Automatically enhances the WordPress `core/search` block in `header.html`
 * with an instant debounced dropdown showing matching WooCommerce products,
 * thumbnails, sale badges, and prices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Live_Search {

	/**
	 * Initialize Live Search hooks.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_atelier_live_search', array( __CLASS__, 'ajax_search_products' ) );
		add_action( 'wp_ajax_nopriv_atelier_live_search', array( __CLASS__, 'ajax_search_products' ) );
	}

	/**
	 * AJAX handler: Search published WooCommerce products by keyword or SKU.
	 */
	public static function ajax_search_products(): void {
		check_ajax_referer( 'atelier_companion_nonce', 'nonce' );

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			's'              => $term,
			'fields'         => 'ids',
		);

		$product_ids = get_posts( $query_args );

		// Also check SKU matches if fewer than 6 results returned.
		if ( count( $product_ids ) < 6 && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$sku_id = wc_get_product_id_by_sku( $term );
			if ( $sku_id && ! in_array( $sku_id, $product_ids, true ) ) {
				array_unshift( $product_ids, $sku_id );
			}
		}

		$results = array();
		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}

			$img_id  = $product->get_image_id();
			$img_url = $img_id ? wp_get_attachment_image_url( (int) $img_id, 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );

			$results[] = array(
				'id'         => $product->get_id(),
				'title'      => html_entity_decode( wp_strip_all_tags( $product->get_name() ) ),
				'url'        => get_permalink( $product->get_id() ),
				'price_html' => $product->get_price_html(),
				'image'      => (string) $img_url,
				'on_sale'    => $product->is_on_sale(),
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}
}
