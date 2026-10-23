<?php
/**
 * Atelier Companion Quick View Module.
 *
 * Adds a Quick View button to product cards and renders an accessible modal
 * displaying gallery thumbnails, rating, price, description, and Add to Cart form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Quick_View {

	/**
	 * Initialize Quick View hooks.
	 */
	public static function init(): void {
		// Classic loop hook (if Wishlist isn't already wrapping the block image).
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'render_loop_button' ), 15 );

		// If Wishlist module is disabled, still inject Quick View button onto block product images.
		if ( ! Atelier_Companion_Settings::get( 'enable_wishlist', true ) ) {
			add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'inject_block_loop_button' ), 10, 2 );
		}

		// Render Quick View modal shell in footer.
		add_action( 'wp_footer', array( __CLASS__, 'render_modal_shell' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_atelier_quick_view', array( __CLASS__, 'ajax_load_product' ) );
		add_action( 'wp_ajax_nopriv_atelier_quick_view', array( __CLASS__, 'ajax_load_product' ) );
	}

	/**
	 * Return Quick View trigger button HTML for a product ID.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_button_html( int $product_id ): string {
		if ( ! $product_id ) {
			return '';
		}
		ob_start();
		?>
		<button
			type="button"
			class="atelier-quick-view-btn"
			data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
			aria-label="<?php esc_attr_e( 'Quick view product', 'atelier-companion' ); ?>"
			title="<?php esc_attr_e( 'Quick View', 'atelier-companion' ); ?>"
		>
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
				<circle cx="12" cy="12" r="3"></circle>
			</svg>
			<span class="atelier-quick-view-btn__label"><?php esc_html_e( 'Quick View', 'atelier-companion' ); ?></span>
		</button>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Output Quick View button in classic WooCommerce product loop.
	 */
	public static function render_loop_button(): void {
		global $product;
		if ( $product instanceof WC_Product ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::get_button_html( $product->get_id() );
		}
	}

	/**
	 * Inject Quick View button into WooCommerce Product Image blocks when Wishlist is off.
	 *
	 * @param string               $block_content Rendered block output.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function inject_block_loop_button( string $block_content, array $block ): string {
		global $product;
		$product_id = ( $product instanceof WC_Product ) ? $product->get_id() : (int) get_the_ID();
		if ( ! $product_id || str_contains( $block_content, 'atelier-quick-view-btn' ) ) {
			return $block_content;
		}
		return '<div class="atelier-product-card-media-wrap">' . $block_content . '<div class="atelier-card-actions">' . self::get_button_html( $product_id ) . '</div></div>';
	}

	/**
	 * Output the Quick View modal shell in the footer.
	 */
	public static function render_modal_shell(): void {
		?>
		<div id="atelier-quick-view-modal" class="atelier-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Product Quick View', 'atelier-companion' ); ?>" aria-hidden="true">
			<div class="atelier-modal__backdrop" data-atelier-close-modal></div>
			<div class="atelier-modal__dialog">
				<button type="button" class="atelier-modal__close" data-atelier-close-modal aria-label="<?php esc_attr_e( 'Close Quick View', 'atelier-companion' ); ?>">
					&times;
				</button>
				<div class="atelier-modal__content" id="atelier-quick-view-content"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler: Load product summary & Add to Cart form for Quick View.
	 */
	public static function ajax_load_product(): void {
		check_ajax_referer( 'atelier_companion_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_visible() ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'atelier-companion' ) ) );
		}

		// Set up global product context so WooCommerce template tags work properly.
		global $post;
		$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		$GLOBALS['product'] = $product;

		$attachment_ids = $product->get_gallery_image_ids();
		$main_image_id  = $product->get_image_id();
		$images         = array();

		if ( $main_image_id ) {
			$images[] = wp_get_attachment_image_url( (int) $main_image_id, 'woocommerce_single' );
		}
		foreach ( array_slice( $attachment_ids, 0, 4 ) as $att_id ) {
			$url = wp_get_attachment_image_url( (int) $att_id, 'woocommerce_single' );
			if ( $url ) {
				$images[] = $url;
			}
		}

		ob_start();
		?>
		<div class="atelier-qv-grid">
			<div class="atelier-qv-gallery">
				<?php if ( ! empty( $images ) ) : ?>
					<div class="atelier-qv-main-img">
						<img src="<?php echo esc_url( (string) $images[0] ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" id="atelier-qv-active-image" />
					</div>
					<?php if ( count( $images ) > 1 ) : ?>
						<div class="atelier-qv-thumbs">
							<?php foreach ( $images as $idx => $img_url ) : ?>
								<button type="button" class="atelier-qv-thumb<?php echo 0 === $idx ? ' is-active' : ''; ?>" data-full-src="<?php echo esc_url( (string) $img_url ); ?>">
									<img src="<?php echo esc_url( (string) $img_url ); ?>" alt="" />
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<?php echo wp_kses_post( wc_placeholder_img( 'woocommerce_single' ) ); ?>
				<?php endif; ?>
			</div>

			<div class="atelier-qv-summary">
				<?php if ( $product->is_on_sale() ) : ?>
					<span class="atelier-badge atelier-badge--sale"><?php esc_html_e( 'Sale', 'atelier-companion' ); ?></span>
				<?php endif; ?>

				<h2 class="atelier-qv-title">
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				</h2>

				<?php if ( $product->get_rating_count() > 0 ) : ?>
					<div class="atelier-qv-rating">
						<?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ) ); ?>
					</div>
				<?php endif; ?>

				<div class="atelier-qv-price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</div>

				<div class="atelier-qv-excerpt">
					<?php echo wp_kses_post( wpautop( $product->get_short_description() ?: wp_trim_words( $product->get_description(), 28 ) ) ); ?>
				</div>

				<div class="atelier-qv-cart-form">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>

				<div class="atelier-qv-footer">
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="atelier-qv-full-link">
						<?php esc_html_e( 'View full product details →', 'atelier-companion' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
		$html = (string) ob_get_clean();
		wp_reset_postdata();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
