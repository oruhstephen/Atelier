<?php
/**
 * Atelier Companion — Bloghash Pro Ported Features Module.
 *
 * Ports signature e-commerce, UI, and editorial features from Bloghash Pro into
 * Atelier Shop & Atelier Shop Companion:
 * 1. Product Image Swap on Hover (Block & Classic product cards)
 * 2. Percentage Discount Sale Badges (-25%) & "Sold Out" Badges
 * 3. Plus / Minus (+/-) Quantity Stepper Buttons
 * 4. Dark / Light Mode Header Switcher (persistent via localStorage)
 * 5. Sticky Header with Scroll Elevation
 * 6. Top Marquee Announcement Ticker Bar with Live Date & Play/Pause
 * 7. Floating Smooth Scroll-to-Top Button
 * 8. Auto Collapsible Table of Contents (TOC) & Post Quick-Like Heart Counter
 * 9. Interactive Desktop Cursor Dot Follower (optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Bloghash_Features {

	public const LIKE_META_KEY = '_atelier_post_likes_count';

	/**
	 * Initialize enabled Bloghash Pro features.
	 */
	public static function init(): void {
		// 1. Body classes for Sticky Header & Dark Mode default.
		add_filter( 'body_class', array( __CLASS__, 'filter_body_classes' ) );

		// 2. Product Image Swap on Hover.
		if ( Atelier_Companion_Settings::get( 'enable_image_swap', true ) ) {
			add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_classic_image_swap' ), 11 );
			add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'inject_block_image_swap' ), 8, 2 );
		}

		// 3. Percentage Discount Sale Badge (-XX%) & Sold Out Badge.
		if ( Atelier_Companion_Settings::get( 'enable_percentage_sale_badge', true ) ) {
			add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'percentage_sale_badge_html' ), 20, 3 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_out_of_stock_badge' ), 10 );
			add_filter( 'render_block_woocommerce/product-sale-badge', array( __CLASS__, 'filter_block_sale_badge' ), 10, 2 );
			add_filter( 'render_block_woocommerce/product-image', array( __CLASS__, 'filter_product_image_badges' ), 12, 2 );
		}

		// 4. Dark / Light Mode Header Toggle Button.
		if ( Atelier_Companion_Settings::get( 'enable_dark_mode', true ) ) {
			add_action( 'wp_head', array( __CLASS__, 'output_early_dark_mode_script' ), 1 );
			add_filter( 'render_block_woocommerce/mini-cart', array( __CLASS__, 'inject_dark_mode_toggle' ), 8, 2 );
		}

		// 5. Top Marquee Announcement Ticker Bar.
		if ( Atelier_Companion_Settings::get( 'enable_top_ticker', true ) ) {
			add_action( 'wp_body_open', array( __CLASS__, 'render_top_ticker_bar' ), 5 );
		}

		// 6. Smooth Scroll-to-Top Button & Optional Cursor Dot in Footer.
		add_action( 'wp_footer', array( __CLASS__, 'render_footer_ui_elements' ), 20 );

		// 7. Collapsible Table of Contents (TOC) & Post Quick-Like Button on Blog Posts.
		if ( Atelier_Companion_Settings::get( 'enable_toc', true ) || Atelier_Companion_Settings::get( 'enable_post_likes', true ) ) {
			add_filter( 'the_content', array( __CLASS__, 'enhance_single_post_content' ), 15 );
		}
		add_shortcode( 'atelier_toc', array( __CLASS__, 'shortcode_toc' ) );

		// AJAX handler for Post Quick-Like button.
		add_action( 'wp_ajax_atelier_post_like', array( __CLASS__, 'ajax_record_post_like' ) );
		add_action( 'wp_ajax_nopriv_atelier_post_like', array( __CLASS__, 'ajax_record_post_like' ) );
	}

	/**
	 * Add body classes for Sticky Header and UI enhancements.
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
		return $classes;
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

		// Insert the secondary hover image right before the closing </a> or </div>.
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
	 * Calculate percentage discount text (e.g. "-25%") for a product (ported from bloghash_wc_add_percentage_to_sale_badge).
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public static function get_discount_percentage_text( WC_Product $product ): string {
		if ( ! $product->is_on_sale() ) {
			return '';
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

		return '';
	}

	/**
	 * Filter classic WooCommerce sale flash to show percentage discount.
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
		if ( ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
			return '<span class="onsale atelier-sold-out-badge">' . esc_html__( 'Sold Out', 'atelier-companion' ) . '</span>';
		}
		$pct = self::get_discount_percentage_text( $product );
		return $pct ? '<span class="onsale atelier-pct-badge">' . esc_html( $pct ) . '</span>' : $html;
	}

	/**
	 * Output "Sold Out" badge on out-of-stock products in classic loops.
	 */
	public static function render_out_of_stock_badge(): void {
		global $product;
		if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			echo '<span class="onsale atelier-sold-out-badge">' . esc_html__( 'Sold Out', 'atelier-companion' ) . '</span>';
		}
	}

	/**
	 * Replace generic "Sale" text in WooCommerce Sale Badge blocks with exact "-XX%" percentage.
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
		$pct = self::get_discount_percentage_text( $prod );
		if ( $pct ) {
			$block_content = preg_replace( '/>(\s*Sale!?\s*)</i', '>' . esc_html( $pct ) . '<', $block_content ) ?? $block_content;
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
			$sold_out      = '<span class="atelier-sold-out-badge">' . esc_html__( 'Sold Out', 'atelier-companion' ) . '</span>';
			$block_content = $sold_out . $block_content;
		} else {
			$pct = self::get_discount_percentage_text( $prod );
			if ( $pct ) {
				$block_content = preg_replace( '/>(\s*Sale!?\s*)</i', '>' . esc_html( $pct ) . '<', $block_content ) ?? $block_content;
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

		// 1. Auto Table of Contents if post has at least 2 H2/H3 headings.
		if ( Atelier_Companion_Settings::get( 'enable_toc', true ) && ! str_contains( $content, 'atelier-toc-wrap' ) ) {
			$toc_data = self::generate_toc_from_content( $content );
			$content  = $toc_data['toc_html'] . $toc_data['content'];
		}

		// 2. Post Quick-Like button at bottom of article.
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

			// Replace first occurrence of this heading.
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
			$count = max( 0, $count - 1 );
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
