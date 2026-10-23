<?php
/**
 * Atelier Companion Wishlist Module.
 *
 * Provides guest (cookie/localStorage) + logged-in (user_meta) wishlists,
 * block-compatible product card heart toggles, a header wishlist icon counter,
 * a slide-out wishlist drawer, and the [atelier_wishlist] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Wishlist {

	public const META_KEY   = '_atelier_wishlist_ids';
	public const COOKIE_KEY = 'atelier_wishlist_ids';

	/**
	 * Initialize wishlist hooks.
	 */
	public static function init(): void {
		// Classic WooCommerce loop & single product hooks.
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_loop_button' ), 15 );
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'render_single_button' ), 15 );

		// Block theme compatibility: inject wishlist button onto product image blocks & header mini-cart.
		add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'inject_block_loop_button' ), 10, 2 );
		add_filter( 'render_block_woocommerce/mini-cart', array( __CLASS__, 'inject_header_wishlist_trigger' ), 10, 2 );

		// Slide-out wishlist drawer in footer.
		add_action( 'wp_footer', array( __CLASS__, 'render_wishlist_drawer' ) );

		// Shortcode [atelier_wishlist].
		add_shortcode( 'atelier_wishlist', array( __CLASS__, 'render_shortcode' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_atelier_toggle_wishlist', array( __CLASS__, 'ajax_toggle_wishlist' ) );
		add_action( 'wp_ajax_nopriv_atelier_toggle_wishlist', array( __CLASS__, 'ajax_toggle_wishlist' ) );
		add_action( 'wp_ajax_atelier_get_wishlist_items', array( __CLASS__, 'ajax_get_wishlist_items' ) );
		add_action( 'wp_ajax_nopriv_atelier_get_wishlist_items', array( __CLASS__, 'ajax_get_wishlist_items' ) );
	}

	/**
	 * Retrieve current user's or guest's wishlist product IDs.
	 *
	 * @return array<int>
	 */
	public static function get_user_wishlist(): array {
		$ids = array();

		if ( is_user_logged_in() ) {
			$saved = get_user_meta( get_current_user_id(), self::META_KEY, true );
			if ( is_array( $saved ) ) {
				$ids = array_map( 'absint', $saved );
			}
		}

		if ( isset( $_COOKIE[ self::COOKIE_KEY ] ) ) {
			$cookie_raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_KEY ] ) );
			$decoded    = json_decode( $cookie_raw, true );
			if ( is_array( $decoded ) ) {
				$ids = array_unique( array_merge( $ids, array_map( 'absint', $decoded ) ) );
			}
		}

		return array_values( array_filter( $ids ) );
	}

	/**
	 * Build the HTML for a wishlist heart button.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $context    'loop' or 'single'.
	 * @return string
	 */
	public static function get_button_html( int $product_id, string $context = 'loop' ): string {
		if ( ! $product_id ) {
			return '';
		}

		$wishlist  = self::get_user_wishlist();
		$is_active = in_array( $product_id, $wishlist, true );
		$classes   = 'atelier-wishlist-btn atelier-wishlist-btn--' . esc_attr( $context ) . ( $is_active ? ' is-active' : '' );
		$label     = $is_active
			? __( 'Remove from Wishlist', 'atelier-companion' )
			: __( 'Save to Wishlist', 'atelier-companion' );

		ob_start();
		?>
		<button
			type="button"
			class="<?php echo esc_attr( $classes ); ?>"
			data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
			aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			title="<?php echo esc_attr( $label ); ?>"
		>
			<svg class="atelier-icon-heart" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
			</svg>
			<?php if ( 'single' === $context ) : ?>
				<span class="atelier-wishlist-btn__text">
					<?php echo esc_html( $is_active ? __( 'Saved to Wishlist', 'atelier-companion' ) : __( 'Save to Wishlist', 'atelier-companion' ) ); ?>
				</span>
			<?php endif; ?>
		</button>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Output wishlist button in classic shop loop.
	 */
	public static function render_loop_button(): void {
		global $product;
		if ( $product instanceof WC_Product ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::get_button_html( $product->get_id(), 'loop' );
		}
	}

	/**
	 * Output wishlist button next to single product Add to Cart button.
	 */
	public static function render_single_button(): void {
		global $product;
		if ( $product instanceof WC_Product ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::get_button_html( $product->get_id(), 'single' );
		}
	}

	/**
	 * Inject wishlist heart button into WooCommerce Product Image blocks inside Product Collection / Loop.
	 *
	 * @param string               $block_content Rendered block output.
	 * @param array<string, mixed> $block         Block definition.
	 * @return string
	 */
	public static function inject_block_loop_button( string $block_content, array $block ): string {
		global $product;
		$product_id = 0;

		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		} elseif ( get_the_ID() && 'product' === get_post_type( get_the_ID() ) ) {
			$product_id = (int) get_the_ID();
		}

		if ( ! $product_id || str_contains( $block_content, 'atelier-wishlist-btn' ) ) {
			return $block_content;
		}

		$button = self::get_button_html( $product_id, 'loop' );

		// Also inject Quick View button if enabled so both sit neatly in the product card overlay.
		$quick_view = '';
		if ( Atelier_Companion_Settings::get( 'enable_quick_view', true ) && class_exists( 'Atelier_Companion_Quick_View' ) ) {
			$quick_view = Atelier_Companion_Quick_View::get_button_html( $product_id );
		}

		return '<div class="atelier-product-card-media-wrap">' . $block_content . '<div class="atelier-card-actions">' . $button . $quick_view . '</div></div>';
	}

	/**
	 * Prepend a header Wishlist icon button with live badge counter right before the Mini-Cart block.
	 *
	 * @param string               $block_content Rendered block output.
	 * @param array<string, mixed> $block         Block definition.
	 * @return string
	 */
	public static function inject_header_wishlist_trigger( string $block_content, array $block ): string {
		$count = count( self::get_user_wishlist() );
		ob_start();
		?>
		<button
			type="button"
			class="atelier-header-wishlist-trigger"
			aria-label="<?php esc_attr_e( 'Open Wishlist', 'atelier-companion' ); ?>"
			aria-controls="atelier-wishlist-drawer"
			aria-expanded="false"
		>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
			</svg>
			<span class="atelier-wishlist-count<?php echo $count > 0 ? ' is-visible' : ''; ?>" data-wishlist-count>
				<?php echo esc_html( (string) $count ); ?>
			</span>
		</button>
		<?php
		$trigger = (string) ob_get_clean();
		return $trigger . $block_content;
	}

	/**
	 * Render slide-out Wishlist Drawer in the site footer.
	 */
	public static function render_wishlist_drawer(): void {
		?>
		<div id="atelier-wishlist-drawer" class="atelier-drawer" role="dialog" aria-modal="true" aria-labelledby="atelier-wishlist-drawer-title" aria-hidden="true">
			<div class="atelier-drawer__backdrop" data-atelier-close-drawer></div>
			<div class="atelier-drawer__panel">
				<header class="atelier-drawer__header">
					<h2 id="atelier-wishlist-drawer-title" class="atelier-drawer__title">
						<?php esc_html_e( 'Saved Items', 'atelier-companion' ); ?>
						<span class="atelier-drawer__count" data-wishlist-count>0</span>
					</h2>
					<button type="button" class="atelier-drawer__close" data-atelier-close-drawer aria-label="<?php esc_attr_e( 'Close Wishlist', 'atelier-companion' ); ?>">
						&times;
					</button>
				</header>
				<div class="atelier-drawer__body" id="atelier-wishlist-drawer-items">
					<?php echo self::render_wishlist_items_html( self::get_user_wishlist() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render [atelier_wishlist] shortcode for a dedicated Wishlist page.
	 *
	 * @return string
	 */
	public static function render_shortcode(): string {
		$ids = self::get_user_wishlist();
		return '<div class="atelier-wishlist-page" id="atelier-wishlist-page-container">' . self::render_wishlist_items_html( $ids ) . '</div>';
	}

	/**
	 * Build HTML list of wishlisted products.
	 *
	 * @param array<int> $product_ids Product IDs.
	 * @return string
	 */
	public static function render_wishlist_items_html( array $product_ids ): string {
		$product_ids = array_values( array_filter( array_map( 'absint', $product_ids ) ) );

		if ( empty( $product_ids ) ) {
			return '<div class="atelier-empty-state"><p>' . esc_html__( 'Your wishlist is currently empty. Explore our collection and tap the heart icon to save your favorite pieces.', 'atelier-companion' ) . '</p></div>';
		}

		ob_start();
		echo '<div class="atelier-wishlist-items">';

		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}
			$permalink = get_permalink( $pid );
			$image     = $product->get_image( 'woocommerce_thumbnail' );
			$price     = $product->get_price_html();
			?>
			<article class="atelier-wishlist-item" data-product-id="<?php echo esc_attr( (string) $pid ); ?>">
				<a href="<?php echo esc_url( $permalink ); ?>" class="atelier-wishlist-item__thumb">
					<?php echo wp_kses_post( $image ); ?>
				</a>
				<div class="atelier-wishlist-item__details">
					<h3 class="atelier-wishlist-item__title">
						<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
					</h3>
					<div class="atelier-wishlist-item__price"><?php echo wp_kses_post( $price ); ?></div>
					<div class="atelier-wishlist-item__actions">
						<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
							<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
							   data-quantity="1"
							   data-product_id="<?php echo esc_attr( (string) $pid ); ?>"
							   class="button add_to_cart_button ajax_add_to_cart atelier-btn-sm">
								<?php esc_html_e( 'Add to cart', 'atelier-companion' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( $permalink ); ?>" class="button atelier-btn-sm">
								<?php esc_html_e( 'Select options', 'atelier-companion' ); ?>
							</a>
						<?php endif; ?>

						<button type="button" class="atelier-wishlist-remove" data-product-id="<?php echo esc_attr( (string) $pid ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'atelier-companion' ); ?>">
							<?php esc_html_e( 'Remove', 'atelier-companion' ); ?>
						</button>
					</div>
				</div>
			</article>
			<?php
		}

		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * AJAX handler: Toggle product in wishlist.
	 */
	public static function ajax_toggle_wishlist(): void {
		check_ajax_referer( 'atelier_companion_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'atelier-companion' ) ) );
		}

		$ids = self::get_user_wishlist();
		if ( isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {
			$ids = array_unique( array_merge( $ids, array_map( 'absint', wp_unslash( $_POST['ids'] ) ) ) );
		}

		if ( in_array( $product_id, $ids, true ) ) {
			$ids    = array_values( array_diff( $ids, array( $product_id ) ) );
			$status = 'removed';
		} else {
			$ids[]  = $product_id;
			$ids    = array_values( array_unique( $ids ) );
			$status = 'added';
		}

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $ids );
		}

		wp_send_json_success(
			array(
				'status'      => $status,
				'wishlistIds' => $ids,
				'count'       => count( $ids ),
				'html'        => self::render_wishlist_items_html( $ids ),
			)
		);
	}

	/**
	 * AJAX handler: Get rendered wishlist items for given IDs.
	 */
	public static function ajax_get_wishlist_items(): void {
		check_ajax_referer( 'atelier_companion_nonce', 'nonce' );

		$ids = self::get_user_wishlist();
		if ( isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {
			$client_ids = array_map( 'absint', wp_unslash( $_POST['ids'] ) );
			$ids        = array_values( array_unique( array_merge( $ids, $client_ids ) ) );
		}

		wp_send_json_success(
			array(
				'wishlistIds' => $ids,
				'count'       => count( $ids ),
				'html'        => self::render_wishlist_items_html( $ids ),
			)
		);
	}
}
