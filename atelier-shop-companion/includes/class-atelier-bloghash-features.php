<?php
/**
 * Atelier Companion — Bloghash Pro & WooCommerce Customizer Features Module.
 *
 * Implements all WooCommerce Customizer options & Bloghash Pro storefront features:
 * - Shop Grid Columns (2–6), Products Per Page, Card Alignment, Card Frame Style, Image Aspect Ratio
 * - Product Image Hover Effects (None, Image Swap, Zoom, Swap + Zoom)
 * - Sale Badge Display Modes (Percentage -25%, Custom Text, or Hide) & Custom "Sold Out" Badge
 * - Catalog Element Visibility (Category, Ratings, Price, Add to Cart button)
 * - Single Product Gallery Controls (Zoom, Lightbox, Slider, Sticky Gallery, Meta Visibility)
 * - Related Products, Up-Sells, and Cart Cross-Sells (Visibility, Columns & Count)
 * - Empty Cart "Return to Shop" Button & Distraction-Free Checkout Mode
 * - Plus / Minus (+/-) Quantity Stepper Buttons
 * - Dark / Light Mode Header Switcher, Sticky Header, Top Marquee Ticker, Scroll-to-Top, TOC & Quick Likes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Bloghash_Features {

	public const LIKE_META_KEY = '_atelier_post_likes_count';

	/**
	 * Initialize enabled Bloghash Pro & WooCommerce Customizer features.
	 */
	public static function init(): void {
		// 1. Body classes & Dynamic Customizer CSS for WooCommerce Catalog & Single Product.
		add_filter( 'body_class', array( __CLASS__, 'filter_body_classes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_dynamic_customizer_css' ), 25 );
		add_action( 'wp', array( __CLASS__, 'apply_single_product_and_catalog_rules' ) );

		// 2. Shop Columns & Products Per Page (Classic + Block Product Collection).
		add_filter( 'loop_shop_columns', array( __CLASS__, 'filter_shop_columns' ), 20 );
		add_filter( 'loop_shop_per_page', array( __CLASS__, 'filter_products_per_page' ), 20 );
		add_filter( 'render_block_data', array( __CLASS__, 'filter_product_collection_block_data' ), 10, 1 );

		// 3. Product Image Swap / Hover Effect.
		$hover_mode = (string) Atelier_Companion_Settings::get( 'shop_product_hover', 'image-swap' );
		if ( Atelier_Companion_Settings::get( 'enable_image_swap', true ) && in_array( $hover_mode, array( 'image-swap', 'swap-zoom' ), true ) ) {
			add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_classic_image_swap' ), 11 );
			add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'inject_block_image_swap' ), 8, 2 );
		}

		// 4. Sale Badge Display Mode (Percentage / Text / Hide) & Sold Out Badge.
		if ( Atelier_Companion_Settings::get( 'enable_percentage_sale_badge', true ) ) {
			add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'percentage_sale_badge_html' ), 20, 3 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_out_of_stock_badge' ), 10 );
			add_filter( 'render_block_woocommerce/product-sale-badge', array( __CLASS__, 'filter_block_sale_badge' ), 10, 2 );
			add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'filter_product_image_badges' ), 12, 2 );
		}

		// 5. Related Products, Up-Sells & Cart Cross-Sells Customization.
		add_filter( 'woocommerce_output_related_products_args', array( __CLASS__, 'filter_related_products_args' ), 20 );
		add_filter( 'woocommerce_upsell_display_args', array( __CLASS__, 'filter_upsell_products_args' ), 20 );
		add_filter( 'woocommerce_cross_sells_columns', array( __CLASS__, 'filter_cross_sells_columns' ), 20 );
		add_filter( 'render_block_woocommerce/related-products', array( __CLASS__, 'filter_related_products_block' ), 10, 1 );

		// 6. Empty Cart "Return to Shop" Button (Bloghash Pro).
		if ( Atelier_Companion_Settings::get( 'wc_empty_cart_button', true ) ) {
			add_action( 'woocommerce_cart_is_empty', array( __CLASS__, 'render_empty_cart_return_button' ), 15 );
		}

		// 7. Dark / Light Mode Header Toggle Button.
		if ( Atelier_Companion_Settings::get( 'enable_dark_mode', true ) ) {
			add_action( 'wp_head', array( __CLASS__, 'output_early_dark_mode_script' ), 1 );
			add_filter( 'render_block_woocommerce/mini-cart', array( __CLASS__, 'inject_dark_mode_toggle' ), 8, 2 );
		}

		// 8. Top Marquee Announcement Ticker Bar.
		if ( Atelier_Companion_Settings::get( 'enable_top_ticker', true ) ) {
			add_action( 'wp_body_open', array( __CLASS__, 'render_top_ticker_bar' ), 5 );
		}

		// 9. Smooth Scroll-to-Top Button & Optional Cursor Dot in Footer.
		add_action( 'wp_footer', array( __CLASS__, 'render_footer_ui_elements' ), 20 );

		// 10. Collapsible Table of Contents (TOC) & Post Quick-Like Button on Blog Posts.
		if ( Atelier_Companion_Settings::get( 'enable_toc', true ) || Atelier_Companion_Settings::get( 'enable_post_likes', true ) ) {
			add_filter( 'the_content', array( __CLASS__, 'enhance_single_post_content' ), 15 );
		}
		add_shortcode( 'atelier_toc', array( __CLASS__, 'shortcode_toc' ) );

		// AJAX handler for Post Quick-Like button.
		add_action( 'wp_ajax_atelier_post_like', array( __CLASS__, 'ajax_record_post_like' ) );
		add_action( 'wp_ajax_nopriv_atelier_post_like', array( __CLASS__, 'ajax_record_post_like' ) );
	}

	/**
	 * Add body classes for Sticky Header, Card Alignment, Card Style, Hover Effect, and Visibility toggles.
	 *
	 * @param array<string> $classes Existing body classes.
	 * @return array<string>
	 */
	public static function filter_body_classes( array $classes ): array {
		if ( Atelier_Companion_Settings::get( 'enable_sticky_header', true ) ) {
			$classes[] = 'atelier-sticky-header';
		}
		if ( Atelier_Companion_Settings::get( 'enable_qty_buttons', true ) ) {
			$classes[] = 'atelier-has-qty-buttons';
		}

		$align      = (string) Atelier_Companion_Settings::get( 'shop_card_alignment', 'left' );
		$card_style = (string) Atelier_Companion_Settings::get( 'shop_card_style', 'minimal' );
		$hover      = (string) Atelier_Companion_Settings::get( 'shop_product_hover', 'image-swap' );

		$classes[] = 'atelier-card-align-' . sanitize_html_class( $align );
		$classes[] = 'atelier-card-style-' . sanitize_html_class( $card_style );
		$classes[] = 'atelier-hover-' . sanitize_html_class( $hover );

		if ( ! Atelier_Companion_Settings::get( 'catalog_show_category', true ) ) {
			$classes[] = 'atelier-hide-loop-cat';
		}
		if ( ! Atelier_Companion_Settings::get( 'catalog_show_rating', true ) ) {
			$classes[] = 'atelier-hide-loop-rating';
		}
		if ( ! Atelier_Companion_Settings::get( 'catalog_show_price', true ) ) {
			$classes[] = 'atelier-hide-loop-price';
		}
		if ( ! Atelier_Companion_Settings::get( 'catalog_show_add_to_cart', true ) ) {
			$classes[] = 'atelier-hide-loop-btn';
		}
		if ( ! Atelier_Companion_Settings::get( 'single_show_meta', true ) ) {
			$classes[] = 'atelier-hide-single-meta';
		}
		if ( ! Atelier_Companion_Settings::get( 'wc_sticky_gallery', true ) ) {
			$classes[] = 'atelier-disable-sticky-gallery';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && Atelier_Companion_Settings::get( 'wc_distraction_free_checkout', false ) ) {
			$classes[] = 'atelier-distraction-free-checkout';
		}

		return $classes;
	}

	/**
	 * Output dynamic CSS variables for Shop Grid Columns & Product Image Aspect Ratio.
	 */
	public static function enqueue_dynamic_customizer_css(): void {
		$cols  = max( 2, min( 6, (int) Atelier_Companion_Settings::get( 'shop_columns', 4 ) ) );
		$ratio = (string) Atelier_Companion_Settings::get( 'shop_image_aspect_ratio', '3/4' );

		$css = ":root { --atelier-shop-columns: {$cols}; }";
		if ( 'auto' !== $ratio ) {
			$css .= ".wc-block-product-template__responsive, ul.products li.product .atelier-product-card-media-wrap img, .wc-block-components-product-image img { aspect-ratio: {$ratio}; object-fit: cover; }";
		}

		wp_add_inline_style( 'atelier-companion', $css );
	}

	/**
	 * Apply Single Product Gallery supports, Up-Sell/Cross-Sell visibility, and Catalog element hooks.
	 */
	public static function apply_single_product_and_catalog_rules(): void {
		if ( ! Atelier_Companion_Settings::get( 'wc_gallery_zoom', true ) ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
		}
		if ( ! Atelier_Companion_Settings::get( 'wc_gallery_lightbox', true ) ) {
			remove_theme_support( 'wc-product-gallery-lightbox' );
		}
		if ( ! Atelier_Companion_Settings::get( 'wc_gallery_slider', true ) ) {
			remove_theme_support( 'wc-product-gallery-slider' );
		}

		if ( ! Atelier_Companion_Settings::get( 'wc_related_products', true ) ) {
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		}
		if ( ! Atelier_Companion_Settings::get( 'wc_upsell_products', true ) ) {
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		}
		if ( ! Atelier_Companion_Settings::get( 'wc_cross_sell_products', true ) ) {
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}
	}

	/**
	 * Filter classic WooCommerce shop columns.
	 */
	public static function filter_shop_columns(): int {
		return max( 2, min( 6, (int) Atelier_Companion_Settings::get( 'shop_columns', 4 ) ) );
	}

	/**
	 * Filter classic WooCommerce products per page.
	 */
	public static function filter_products_per_page(): int {
		return max( 4, min( 48, (int) Atelier_Companion_Settings::get( 'shop_products_per_page', 12 ) ) );
	}

	/**
	 * Dynamically apply Customizer `shop_columns` and `shop_products_per_page` to WooCommerce Product Collection blocks.
	 *
	 * @param array<string, mixed> $parsed_block Parsed block data.
	 * @return array<string, mixed>
	 */
	public static function filter_product_collection_block_data( array $parsed_block ): array {
		$name = $parsed_block['blockName'] ?? '';
		if ( 'woocommerce/product-collection' === $name ) {
			$cols     = max( 2, min( 6, (int) Atelier_Companion_Settings::get( 'shop_columns', 4 ) ) );
			$per_page = max( 4, min( 48, (int) Atelier_Companion_Settings::get( 'shop_products_per_page', 12 ) ) );

			if ( isset( $parsed_block['attrs']['displayLayout']['columns'] ) ) {
				$parsed_block['attrs']['displayLayout']['columns'] = $cols;
			}
			if ( isset( $parsed_block['attrs']['query']['perPage'] ) ) {
				$parsed_block['attrs']['query']['perPage'] = $per_page;
			}
		}
		return $parsed_block;
	}

	/**
	 * Filter Related Products count and columns.
	 *
	 * @param array<string, mixed> $args Related products args.
	 * @return array<string, mixed>
	 */
	public static function filter_related_products_args( array $args ): array {
		$args['posts_per_page'] = max( 1, min( 12, (int) Atelier_Companion_Settings::get( 'wc_related_count', 4 ) ) );
		$args['columns']        = max( 1, min( 6, (int) Atelier_Companion_Settings::get( 'wc_related_columns', 4 ) ) );
		return $args;
	}

	/**
	 * Filter Up-Sell Products columns.
	 *
	 * @param array<string, mixed> $args Upsell args.
	 * @return array<string, mixed>
	 */
	public static function filter_upsell_products_args( array $args ): array {
		$args['columns'] = max( 1, min( 6, (int) Atelier_Companion_Settings::get( 'wc_upsells_columns', 4 ) ) );
		return $args;
	}

	/**
	 * Filter Cart Cross-Sells columns.
	 */
	public static function filter_cross_sells_columns(): int {
		return max( 1, min( 6, (int) Atelier_Companion_Settings::get( 'wc_cross_sell_columns', 4 ) ) );
	}

	/**
	 * Hide `woocommerce/related-products` block when disabled in Customizer.
	 */
	public static function filter_related_products_block( string $block_content ): string {
		if ( ! Atelier_Companion_Settings::get( 'wc_related_products', true ) ) {
			return '';
		}
		return $block_content;
	}

	/**
	 * Render styled "Return to Shop" button on Empty Cart (ported from bloghash_wc_empty_cart_button).
	 */
	public static function render_empty_cart_return_button(): void {
		if ( ! function_exists( 'wc_get_page_permalink' ) ) {
			return;
		}
		?>
		<p class="atelier-empty-cart-return">
			<a class="button wc-backward" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				&larr; <?php esc_html_e( 'Return to Shop', 'atelier-companion' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Output tiny inline script in <head> to apply saved Dark Mode preference before first paint (zero flash).
	 */
	public static function output_early_dark_mode_script(): void {
		?>
		<script>
		(function(){
			try {
				var mode = localStorage.getItem('atelier_darkmode');
				if (mode === 'dark') {
					document.documentElement.setAttribute('data-darkmode', 'dark');
				}
			} catch (e) {}
		})();
		</script>
		<?php
	}

	/**
	 * Inject Dark / Light Mode toggle button in the header before the Mini-Cart / Wishlist icons.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function inject_dark_mode_toggle( string $block_content, array $block ): string {
		if ( str_contains( $block_content, 'atelier-darkmode-toggle' ) ) {
			return $block_content;
		}

		ob_start();
		?>
		<button
			type="button"
			class="atelier-darkmode-toggle"
			data-atelier-darkmode-toggle
			aria-label="<?php esc_attr_e( 'Toggle dark or light color mode', 'atelier-companion' ); ?>"
			title="<?php esc_attr_e( 'Toggle Dark / Light Mode', 'atelier-companion' ); ?>"
		>
			<svg class="atelier-icon-moon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
			</svg>
			<svg class="atelier-icon-sun" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<circle cx="12" cy="12" r="5"></circle>
				<line x1="12" y1="1" x2="12" y2="3"></line>
				<line x1="12" y1="21" x2="12" y2="23"></line>
				<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
				<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
				<line x1="1" y1="12" x2="3" y2="12"></line>
				<line x1="21" y1="12" x2="23" y2="12"></line>
				<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
				<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
			</svg>
		</button>
		<?php
		return (string) ob_get_clean() . $block_content;
	}

	/**
	 * Output secondary gallery image on hover in classic WooCommerce loops (ported from Bloghash_Woocommerce::product_image_swap).
	 */
	public static function render_classic_image_swap(): void {
		global $product;
		if ( ! ( $product instanceof WC_Product ) ) {
			return;
		}
		$hover_img = self::get_hover_image_html( $product );
		if ( $hover_img ) {
			echo wp_kses_post( $hover_img );
		}
	}

	/**
	 * Inject secondary gallery image on hover into WooCommerce Block product image cards.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function inject_block_image_swap( string $block_content, array $block ): string {
		global $product;
		$prod = ( $product instanceof WC_Product ) ? $product : wc_get_product( get_the_ID() );
		if ( ! ( $prod instanceof WC_Product ) || str_contains( $block_content, 'atelier-swap-on-hover' ) ) {
			return $block_content;
		}

		$hover_img = self::get_hover_image_html( $prod );
		if ( ! $hover_img ) {
			return $block_content;
		}

		if ( str_contains( $block_content, '</a>' ) ) {
			$block_content = preg_replace( '/<\/a>/', $hover_img . '</a>', $block_content, 1 ) ?? $block_content;
		} else {
			$block_content .= $hover_img;
		}

		return '<div class="atelier-swap-on-hover">' . $block_content . '</div>';
	}

	/**
	 * Get the first gallery image HTML for product hover swap.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	private static function get_hover_image_html( WC_Product $product ): string {
		$attachment_ids = $product->get_gallery_image_ids();
		if ( empty( $attachment_ids ) ) {
			return '';
		}
		$first_gallery_id = (int) reset( $attachment_ids );
		if ( ! $first_gallery_id ) {
			return '';
		}
		return wp_get_attachment_image(
			$first_gallery_id,
			'woocommerce_thumbnail',
			false,
			array(
				'class'   => 'atelier-show-on-hover',
				'loading' => 'lazy',
			)
		);
	}

	/**
	 * Calculate sale badge text based on Customizer `product_sale_badge` mode (`percentage`, `text`, or `hide`).
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function get_discount_percentage_text( WC_Product $product ): string {
		if ( ! $product->is_on_sale() ) {
			return '';
		}

		$mode = (string) Atelier_Companion_Settings::get( 'product_sale_badge', 'percentage' );
		if ( 'hide' === $mode ) {
			return '__HIDE__';
		}
		if ( 'text' === $mode ) {
			return (string) Atelier_Companion_Settings::get( 'product_sale_badge_text', __( 'Sale!', 'atelier-companion' ) );
		}

		if ( $product->is_type( 'variable' ) ) {
			$percentages = array();
			$prices      = $product->get_variation_prices();

			if ( ! empty( $prices['price'] ) && is_array( $prices['price'] ) ) {
				foreach ( $prices['price'] as $key => $price ) {
					$regular = (float) ( $prices['regular_price'][ $key ] ?? 0 );
					$sale    = (float) ( $prices['sale_price'][ $key ] ?? $price );
					if ( $regular > 0 && $regular > $sale ) {
						$percentages[] = (int) round( 100 - ( ( $sale / $regular ) * 100 ) );
					}
				}
			}

			if ( ! empty( $percentages ) ) {
				return '-' . max( $percentages ) . '%';
			}
		} else {
			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();
			if ( $regular > 0 && $regular > $sale ) {
				return '-' . (int) round( 100 - ( ( $sale / $regular ) * 100 ) ) . '%';
			}
		}

		return (string) Atelier_Companion_Settings::get( 'product_sale_badge_text', __( 'Sale!', 'atelier-companion' ) );
	}

	/**
	 * Filter classic WooCommerce sale flash to show percentage discount, custom text, or hide.
	 *
	 * @param string      $html    Default sale badge HTML.
	 * @param WP_Post     $post    Post object.
	 * @param WC_Product  $product Product object.
	 * @return string
	 */
	public static function percentage_sale_badge_html( string $html, $post, $product ): string {
		if ( ! ( $product instanceof WC_Product ) ) {
			return $html;
		}
		$sold_out_label = (string) Atelier_Companion_Settings::get( 'sold_out_badge_text', __( 'Sold Out', 'atelier-companion' ) );
		if ( ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
			return '<span class="onsale atelier-sold-out-badge">' . esc_html( $sold_out_label ) . '</span>';
		}
		$badge_text = self::get_discount_percentage_text( $product );
		if ( '__HIDE__' === $badge_text ) {
			return '';
		}
		return $badge_text ? '<span class="onsale atelier-pct-badge">' . esc_html( $badge_text ) . '</span>' : $html;
	}

	/**
	 * Output "Sold Out" badge on out-of-stock products in classic loops.
	 */
	public static function render_out_of_stock_badge(): void {
		global $product;
		if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			$sold_out_label = (string) Atelier_Companion_Settings::get( 'sold_out_badge_text', __( 'Sold Out', 'atelier-companion' ) );
			echo '<span class="onsale atelier-sold-out-badge">' . esc_html( $sold_out_label ) . '</span>';
		}
	}

	/**
	 * Filter WooCommerce Sale Badge blocks according to Customizer `product_sale_badge` setting.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function filter_block_sale_badge( string $block_content, array $block ): string {
		global $product;
		$prod = ( $product instanceof WC_Product ) ? $product : wc_get_product( get_the_ID() );
		if ( ! ( $prod instanceof WC_Product ) ) {
			return $block_content;
		}
		$badge_text = self::get_discount_percentage_text( $prod );
		if ( '__HIDE__' === $badge_text ) {
			return '';
		}
		if ( $badge_text ) {
			$block_content = preg_replace( '/>(\s*Sale!?\s*)</i', '>' . esc_html( $badge_text ) . '<', $block_content ) ?? $block_content;
		}
		return $block_content;
	}

	/**
	 * Filter inline sale badge inside `woocommerce/product-image` block and append Sold Out badge when out of stock.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function filter_product_image_badges( string $block_content, array $block ): string {
		global $product;
		$prod = ( $product instanceof WC_Product ) ? $product : wc_get_product( get_the_ID() );
		if ( ! ( $prod instanceof WC_Product ) ) {
			return $block_content;
		}

		if ( ! $prod->is_in_stock() && ! str_contains( $block_content, 'atelier-sold-out-badge' ) ) {
			$sold_out_label = (string) Atelier_Companion_Settings::get( 'sold_out_badge_text', __( 'Sold Out', 'atelier-companion' ) );
			$sold_out       = '<span class="atelier-sold-out-badge">' . esc_html( $sold_out_label ) . '</span>';
			$block_content  = $sold_out . $block_content;
		} else {
			$badge_text = self::get_discount_percentage_text( $prod );
			if ( '__HIDE__' === $badge_text ) {
				$block_content = preg_replace( '/<div class="wc-block-components-product-sale-badge[^>]*>.*?<\/div>/is', '', $block_content ) ?? $block_content;
			} elseif ( $badge_text ) {
				$block_content = preg_replace( '/>(\s*Sale!?\s*)</i', '>' . esc_html( $badge_text ) . '<', $block_content ) ?? $block_content;
			}
		}

		return $block_content;
	}

	/**
	 * Render Top Marquee Announcement Ticker Bar at wp_body_open (ported from Bloghash Pro topbar + ticker).
	 */
	public static function render_top_ticker_bar(): void {
		if ( is_admin() ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && Atelier_Companion_Settings::get( 'wc_distraction_free_checkout', false ) ) {
			return;
		}

		$raw_items = (string) Atelier_Companion_Settings::get(
			'ticker_items_text',
			"Complimentary express shipping on orders over £50\nNew Autumn/Winter Capsule Collection now live\nEasy 30-day returns & exchanges on all orders"
		);
		$items     = array_values( array_filter( array_map( 'trim', explode( "\n", $raw_items ) ) ) );

		if ( empty( $items ) ) {
			return;
		}

		$label = (string) Atelier_Companion_Settings::get( 'ticker_label', __( 'Highlights', 'atelier-companion' ) );
		?>
		<div class="atelier-topbar-ticker" id="atelier-topbar-ticker" role="region" aria-label="<?php esc_attr_e( 'Store announcements', 'atelier-companion' ); ?>">
			<div class="atelier-topbar-ticker__inner">
				<div class="atelier-topbar-ticker__left">
					<?php if ( $label ) : ?>
						<span class="atelier-topbar-ticker__badge"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
					<div class="atelier-topbar-ticker__viewport">
						<div class="atelier-topbar-ticker__track" id="atelier-ticker-track">
							<?php foreach ( array_merge( $items, $items ) as $msg ) : ?>
								<span class="atelier-topbar-ticker__item"><?php echo esc_html( $msg ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="atelier-topbar-ticker__right">
					<span class="atelier-topbar-ticker__date" id="atelier-live-date" aria-live="off"></span>
					<button type="button" class="atelier-topbar-ticker__pause" data-atelier-ticker-toggle aria-label="<?php esc_attr_e( 'Pause or play announcement ticker', 'atelier-companion' ); ?>" title="<?php esc_attr_e( 'Pause / Play', 'atelier-companion' ); ?>">
						<span class="atelier-ticker-icon-pause" aria-hidden="true">&#10074;&#10074;</span>
						<span class="atelier-ticker-icon-play" aria-hidden="true" style="display:none;">&#9654;</span>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Scroll-to-Top button and optional Cursor Dot Follower in wp_footer (ported from Bloghash Pro misc template-parts).
	 */
	public static function render_footer_ui_elements(): void {
		if ( Atelier_Companion_Settings::get( 'enable_scroll_top', true ) ) :
			?>
			<a href="#" id="atelier-scroll-top" class="atelier-scroll-top" aria-label="<?php esc_attr_e( 'Scroll to Top', 'atelier-companion' ); ?>" title="<?php esc_attr_e( 'Scroll to Top', 'atelier-companion' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<polyline points="18 15 12 9 6 15"></polyline>
				</svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Scroll to Top', 'atelier-companion' ); ?></span>
			</a>
			<?php
		endif;

		if ( Atelier_Companion_Settings::get( 'enable_cursor_dot', false ) ) :
			?>
			<div class="atelier-js-cursor" id="atelier-js-cursor" aria-hidden="true">
				<div class="atelier-js-follower"></div>
			</div>
			<?php
		endif;
	}

	/**
	 * Auto-inject Collapsible Table of Contents (TOC) & Post Quick-Like button on single blog posts (ported from Bloghash_TOC & record_post_like).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function enhance_single_post_content( string $content ): string {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( Atelier_Companion_Settings::get( 'enable_toc', true ) && ! str_contains( $content, 'atelier-toc-wrap' ) ) {
			$toc_data = self::generate_toc_from_content( $content );
			$content  = $toc_data['toc_html'] . $toc_data['content'];
		}

		if ( Atelier_Companion_Settings::get( 'enable_post_likes', true ) ) {
			$content .= self::get_post_like_button_html( (int) get_the_ID() );
		}

		return $content;
	}

	/**
	 * Shortcode [atelier_toc] handler.
	 *
	 * @return string
	 */
	public static function shortcode_toc(): string {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		$toc_data = self::generate_toc_from_content( $post->post_content );
		return $toc_data['toc_html'];
	}

	/**
	 * Parse H2/H3 headings in content, inject anchor targets, and build collapsible TOC box.
	 *
	 * @param string $content HTML content.
	 * @return array{toc_html: string, content: string}
	 */
	private static function generate_toc_from_content( string $content ): array {
		$matches = array();
		preg_match_all( '/<h([2-3])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER );

		if ( count( $matches ) < 2 ) {
			return array(
				'toc_html' => '',
				'content'  => $content,
			);
		}

		$items_html = '';
		foreach ( $matches as $index => $match ) {
			$level      = (int) $match[1];
			$attrs      = $match[2];
			$inner_html = $match[3];
			$plain_text = wp_strip_all_tags( $inner_html );
			$anchor_id  = 'atelier-toc-' . ( $index + 1 ) . '-' . sanitize_title( $plain_text );

			$replacement_heading = sprintf(
				'<h%1$d%2$s id="%3$s" class="atelier-toc-section">%4$s</h%1$d>',
				$level,
				$attrs,
				esc_attr( $anchor_id ),
				$inner_html
			);

			$pos = strpos( $content, $match[0] );
			if ( false !== $pos ) {
				$content = substr_replace( $content, $replacement_heading, $pos, strlen( $match[0] ) );
			}

			$indent_class = 3 === $level ? ' class="atelier-toc-subitem"' : '';
			$items_html  .= sprintf(
				'<li%1$s><a href="#%2$s" class="atelier-toc-link">%3$s</a></li>',
				$indent_class,
				esc_attr( $anchor_id ),
				esc_html( $plain_text )
			);
		}

		ob_start();
		?>
		<nav class="atelier-toc-wrap" aria-label="<?php esc_attr_e( 'Table of Contents', 'atelier-companion' ); ?>">
			<details class="atelier-toc-details" open>
				<summary class="atelier-toc-header">
					<span class="atelier-toc-header__title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<line x1="8" y1="6" x2="21" y2="6"></line>
							<line x1="8" y1="12" x2="21" y2="12"></line>
							<line x1="8" y1="18" x2="21" y2="18"></line>
							<line x1="3" y1="6" x2="3.01" y2="6"></line>
							<line x1="3" y1="12" x2="3.01" y2="12"></line>
							<line x1="3" y1="18" x2="3.01" y2="18"></line>
						</svg>
						<?php esc_html_e( 'Table of Contents', 'atelier-companion' ); ?>
					</span>
					<span class="atelier-accordion__icon" aria-hidden="true"></span>
				</summary>
				<ol class="atelier-toc-list">
					<?php echo wp_kses_post( $items_html ); ?>
				</ol>
			</details>
		</nav>
		<?php
		return array(
			'toc_html' => (string) ob_get_clean(),
			'content'  => $content,
		);
	}

	/**
	 * Build the Post Quick-Like heart button HTML (ported from Bloghash Pro like-button).
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_post_like_button_html( int $post_id ): string {
		$count = (int) get_post_meta( $post_id, self::LIKE_META_KEY, true );
		ob_start();
		?>
		<div class="atelier-post-like-wrap">
			<button type="button" class="atelier-post-like-btn" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>" aria-label="<?php esc_attr_e( 'Like this article', 'atelier-companion' ); ?>">
				<svg class="atelier-icon-heart" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
				</svg>
				<span class="atelier-post-like-label"><?php esc_html_e( 'Helpful', 'atelier-companion' ); ?></span>
				<span class="atelier-post-like-count"><?php echo esc_html( (string) $count ); ?></span>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX handler: Toggle / increment post like counter.
	 */
	public static function ajax_record_post_like(): void {
		check_ajax_referer( 'atelier_companion_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error();
		}

		$liked = isset( $_POST['liked'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['liked'] ) );
		$count = (int) get_post_meta( $post_id, self::LIKE_META_KEY, true );

		if ( $liked ) {
			$count     = max( 0, $count - 1 );
			$new_state = false;
		} else {
			++$count;
			$new_state = true;
		}

		update_post_meta( $post_id, self::LIKE_META_KEY, $count );

		wp_send_json_success(
			array(
				'liked' => $new_state,
				'count' => $count,
			)
		);
	}
}
