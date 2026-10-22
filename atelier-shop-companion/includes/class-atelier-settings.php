<?php
/**
 * Atelier Companion Settings & Customization Center.
 *
 * Provides a clean, modular settings screen under WooCommerce > Atelier Companion
 * (or Settings > Atelier Companion) to toggle and customize every e-commerce
 * and Bloghash Pro feature.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Settings {

	public const OPTION_KEY = 'atelier_companion_settings';

	/**
	 * Initialize settings hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 60 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ATELIER_COMPANION_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Default configuration values for all modules.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			// Core E-Commerce Module toggles
			'enable_wishlist'               => true,
			'enable_swatches'               => true,
			'enable_quick_view'             => true,
			'enable_shipping_bar'           => true,
			'enable_product_accordions'     => true,
			'enable_sticky_cart'            => true,
			'enable_size_guide'             => true,
			'enable_stock_urgency'          => true,
			'enable_trust_badges'           => true,
			'enable_live_search'            => true,

			// Bloghash Pro Ported Module toggles
			'enable_image_swap'             => true,
			'enable_percentage_sale_badge'  => true,
			'enable_qty_buttons'            => true,
			'enable_dark_mode'              => true,
			'enable_sticky_header'          => true,
			'enable_top_ticker'             => true,
			'enable_scroll_top'             => true,
			'enable_toc'                    => true,
			'enable_post_likes'             => true,
			'enable_cursor_dot'             => false,

			// Swatch settings
			'swatch_shape'                  => 'circle', // circle | rounded | square
			'swatch_size'                   => 32,

			// Free shipping bar settings
			'free_shipping_threshold'       => 50.0,
			'shipping_bar_initial_text'     => 'Spend {remaining} more to unlock free delivery',
			'shipping_bar_Complete_text'    => 'You have unlocked free standard delivery!',
			'shipping_bar_show_mini'        => true,

			// Top Announcement Ticker settings (Bloghash Pro)
			'ticker_label'                  => 'Highlights',
			'ticker_items_text'             => "Complimentary express shipping on orders over £50\nNew Autumn/Winter Capsule Collection now live\nEasy 30-day returns & exchanges on all orders",

			// Urgency & delivery settings
			'low_stock_threshold'           => 8,
			'estimated_delivery_days'       => '2–4 business days',
			'delivery_returns_summary'      => 'Free standard delivery on orders over £50. Easy 30-day returns on all unworn items.',
			'specifications_default'        => 'Crafted with sustainably sourced materials and designed for longevity. Refer to product tags and attributes for exact composition.',
			'trust_badges_text'             => 'Secure SSL Checkout • 30-Day Returns • Carbon-Neutral Shipping',

			// Size guide default HTML/table
			'size_guide_title'              => 'Size & Fit Guide',
			'size_guide_content'            => "Use the measurements below to find your ideal fit.\n\nXS: Chest 82–86 cm | Waist 66–70 cm | Hip 90–94 cm\nS: Chest 87–92 cm | Waist 71–76 cm | Hip 95–100 cm\nM: Chest 93–98 cm | Waist 77–82 cm | Hip 101–106 cm\nL: Chest 99–105 cm | Waist 83–89 cm | Hip 107–113 cm\nXL: Chest 106–113 cm | Waist 90–97 cm | Hip 114–121 cm",
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback default.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$all = self::get_all();
		return $all[ $key ] ?? $default;
	}

	/**
	 * Register submenu page under WooCommerce (or Settings if WC is inactive).
	 */
	public static function register_menu(): void {
		$parent_slug = class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php';

		add_submenu_page(
			$parent_slug,
			__( 'Atelier Shop Companion', 'atelier-companion' ),
			__( 'Atelier Companion', 'atelier-companion' ),
			'manage_options',
			'atelier-companion',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Add quick "Settings" link on the Plugins list page.
	 *
	 * @param array<string> $links Existing plugin action links.
	 * @return array<string>
	 */
	public static function add_settings_link( array $links ): array {
		$url = admin_url( ( class_exists( 'WooCommerce' ) ? 'admin.php?page=atelier-companion' : 'options-general.php?page=atelier-companion' ) );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'atelier-companion' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Register setting and sanitization callback.
	 */
	public static function register_settings(): void {
		register_setting(
			'atelier_companion_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize incoming settings payload.
	 *
	 * @param mixed $input Raw form input.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::defaults();
		}

		$boolean_keys = array(
			'enable_wishlist',
			'enable_swatches',
			'enable_quick_view',
			'enable_shipping_bar',
			'enable_product_accordions',
			'enable_sticky_cart',
			'enable_size_guide',
			'enable_stock_urgency',
			'enable_trust_badges',
			'enable_live_search',
			'enable_image_swap',
			'enable_percentage_sale_badge',
			'enable_qty_buttons',
			'enable_dark_mode',
			'enable_sticky_header',
			'enable_top_ticker',
			'enable_scroll_top',
			'enable_toc',
			'enable_post_likes',
			'enable_cursor_dot',
			'shipping_bar_show_mini',
		);

		$clean = array();
		foreach ( $boolean_keys as $bkey ) {
			$clean[ $bkey ] = ! empty( $input[ $bkey ] );
		}

		$allowed_shapes        = array( 'circle', 'rounded', 'square' );
		$clean['swatch_shape'] = in_array( $input['swatch_shape'] ?? 'circle', $allowed_shapes, true )
			? $input['swatch_shape']
			: 'circle';

		$clean['swatch_size']             = max( 20, min( 64, (int) ( $input['swatch_size'] ?? 32 ) ) );
		$clean['free_shipping_threshold'] = max( 0, (float) ( $input['free_shipping_threshold'] ?? 50 ) );
		$clean['low_stock_threshold']     = max( 1, min( 100, (int) ( $input['low_stock_threshold'] ?? 8 ) ) );

		$text_keys = array(
			'shipping_bar_initial_text',
			'shipping_bar_Complete_text',
			'ticker_label',
			'estimated_delivery_days',
			'trust_badges_text',
			'size_guide_title',
		);

		$defaults = self::defaults();
		foreach ( $text_keys as $tkey ) {
			$clean[ $tkey ] = isset( $input[ $tkey ] )
				? sanitize_text_field( wp_unslash( $input[ $tkey ] ) )
				: $defaults[ $tkey ];
		}

		$textarea_keys = array(
			'ticker_items_text',
			'delivery_returns_summary',
			'specifications_default',
			'size_guide_content',
		);
		foreach ( $textarea_keys as $takey ) {
			$clean[ $takey ] = isset( $input[ $takey ] )
				? sanitize_textarea_field( wp_unslash( $input[ $takey ] ) )
				: $defaults[ $takey ];
		}

		return $clean;
	}

	/**
	 * Render the Settings & Customization page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s = self::get_all();
		?>
		<div class="wrap atelier-admin-wrap">
			<div class="atelier-admin-header">
				<div>
					<h1><?php esc_html_e( 'Atelier Shop Companion', 'atelier-companion' ); ?></h1>
					<p class="description">
						<?php esc_html_e( 'Modular e-commerce & Bloghash Pro features built specifically for the Atelier Shop block theme.', 'atelier-companion' ); ?>
					</p>
				</div>
				<span class="atelier-version-badge">v<?php echo esc_html( ATELIER_COMPANION_VERSION ); ?></span>
			</div>

			<form method="post" action="options.php" class="atelier-settings-form">
				<?php settings_fields( 'atelier_companion_group' ); ?>

				<div class="atelier-admin-grid">
					<!-- Card 1: Core & Bloghash Pro Modules -->
					<div class="atelier-admin-card">
						<h2><?php esc_html_e( '1. Active Storefront & Bloghash Pro Modules', 'atelier-companion' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Toggle individual storefront enhancements on or off. Disabled modules load zero frontend overhead.', 'atelier-companion' ); ?></p>

						<table class="form-table" role="presentation">
							<?php
							$toggles = array(
								'enable_wishlist'              => array(
									__( 'Wishlist & Saved Items', 'atelier-companion' ),
									__( 'Heart icon on product cards, header counter, slide-out wishlist drawer, and [atelier_wishlist] shortcode.', 'atelier-companion' ),
								),
								'enable_swatches'              => array(
									__( 'Variation Color & Image Swatches', 'atelier-companion' ),
									__( 'Replaces standard dropdowns on variable products with interactive color, image, and pill swatches.', 'atelier-companion' ),
								),
								'enable_quick_view'            => array(
									__( 'Product Quick View Modal', 'atelier-companion' ),
									__( 'Instant modal preview on shop archives with gallery, price, variations, and AJAX add-to-cart.', 'atelier-companion' ),
								),
								'enable_image_swap'            => array(
									__( 'Product Image Swap on Hover (Bloghash Pro)', 'atelier-companion' ),
									__( 'Smoothly cross-fades to the second product gallery image when hovering over product cards.', 'atelier-companion' ),
								),
								'enable_percentage_sale_badge' => array(
									__( 'Percentage Sale (-25%) & Sold Out Badges (Bloghash Pro)', 'atelier-companion' ),
									__( 'Displays exact discount percentage (e.g., -25%) on sale badges and a "Sold Out" badge when out of stock.', 'atelier-companion' ),
								),
								'enable_qty_buttons'           => array(
									__( 'Plus / Minus (+/-) Quantity Buttons (Bloghash Pro)', 'atelier-companion' ),
									__( 'Adds interactive - and + stepper buttons to product and cart quantity inputs.', 'atelier-companion' ),
								),
								'enable_shipping_bar'          => array(
									__( 'Free Shipping Progress Bar', 'atelier-companion' ),
									__( 'Dynamic goal tracker in Mini-Cart, Cart, and Checkout showing how close the shopper is to free shipping.', 'atelier-companion' ),
								),
								'enable_product_accordions'    => array(
									__( 'Single Product Accordions', 'atelier-companion' ),
									__( 'Replaces classic WooCommerce product tabs with clean, accessible accordions (Product Info, Specs, Delivery & Returns, Reviews).', 'atelier-companion' ),
								),
								'enable_sticky_cart'           => array(
									__( 'Sticky Add-to-Cart Bar', 'atelier-companion' ),
									__( 'Displays a compact bottom bar when scrolling past the main Add to Cart button on single product pages.', 'atelier-companion' ),
								),
								'enable_size_guide'            => array(
									__( 'Interactive Size & Fit Guide Modal', 'atelier-companion' ),
									__( 'Adds a Size Guide button and modal on single products (with global default and per-product override).', 'atelier-companion' ),
								),
								'enable_stock_urgency'         => array(
									__( 'Low-Stock Urgency Indicator', 'atelier-companion' ),
									__( 'Shows a subtle inventory meter when stock drops at or below your threshold.', 'atelier-companion' ),
								),
								'enable_trust_badges'          => array(
									__( 'Estimated Delivery & Trust Badges', 'atelier-companion' ),
									__( 'Displays estimated dispatch/delivery window and trust highlights under the Add to Cart button.', 'atelier-companion' ),
								),
								'enable_live_search'           => array(
									__( 'Instant AJAX Live Product Search', 'atelier-companion' ),
									__( 'Upgrades the header search block with real-time product suggestions, thumbnails, and prices.', 'atelier-companion' ),
								),
								'enable_dark_mode'             => array(
									__( 'Dark / Light Mode Header Switcher (Bloghash Pro)', 'atelier-companion' ),
									__( 'Adds a Sun/Moon toggle in the header with instant localStorage persistence.', 'atelier-companion' ),
								),
								'enable_sticky_header'         => array(
									__( 'Sticky Header on Scroll (Bloghash Pro)', 'atelier-companion' ),
									__( 'Keeps the site header pinned at the top of the viewport with subtle scroll shadow.', 'atelier-companion' ),
								),
								'enable_top_ticker'            => array(
									__( 'Top Marquee Announcement Ticker Bar (Bloghash Pro)', 'atelier-companion' ),
									__( 'Scrolling announcement bar at the top of the page with live date & Play/Pause button.', 'atelier-companion' ),
								),
								'enable_scroll_top'            => array(
									__( 'Floating Scroll-to-Top Button (Bloghash Pro)', 'atelier-companion' ),
									__( 'Smooth back-to-top button that appears after scrolling down 450px.', 'atelier-companion' ),
								),
								'enable_toc'                   => array(
									__( 'Auto Table of Contents on Blog Posts (Bloghash Pro)', 'atelier-companion' ),
									__( 'Generates a collapsible Table of Contents box for articles with 2+ headings (or via [atelier_toc]).', 'atelier-companion' ),
								),
								'enable_post_likes'            => array(
									__( 'Article Quick-Like Heart Counter (Bloghash Pro)', 'atelier-companion' ),
									__( 'Adds an interactive AJAX heart counter button at the bottom of blog posts.', 'atelier-companion' ),
								),
								'enable_cursor_dot'            => array(
									__( 'Interactive Desktop Cursor Follower (Bloghash Pro)', 'atelier-companion' ),
									__( 'Optional smooth cursor dot follower on desktop screens (>992px).', 'atelier-companion' ),
								),
							);

							foreach ( $toggles as $key => $info ) :
								?>
								<tr>
									<th scope="row"><?php echo esc_html( $info[0] ); ?></th>
									<td>
										<label class="atelier-switch">
											<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $s[ $key ] ) ); ?> />
											<span class="atelier-slider"></span>
										</label>
										<p class="description"><?php echo esc_html( $info[1] ); ?></p>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>

					<!-- Card 2: Customization Options -->
					<div class="atelier-admin-card">
						<h2><?php esc_html_e( '2. Top Ticker, Swatches & Free Shipping Bar', 'atelier-companion' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="ticker_label"><?php esc_html_e( 'Top Ticker Badge Label', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="ticker_label" name="<?php echo esc_attr( self::OPTION_KEY . '[ticker_label]' ); ?>" value="<?php echo esc_attr( $s['ticker_label'] ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="ticker_items_text"><?php esc_html_e( 'Top Ticker Announcements (1 per line)', 'atelier-companion' ); ?></label></th>
								<td>
									<textarea id="ticker_items_text" rows="3" name="<?php echo esc_attr( self::OPTION_KEY . '[ticker_items_text]' ); ?>" class="large-text"><?php echo esc_textarea( $s['ticker_items_text'] ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="swatch_shape"><?php esc_html_e( 'Swatch Style', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="swatch_shape" name="<?php echo esc_attr( self::OPTION_KEY . '[swatch_shape]' ); ?>">
										<option value="circle" <?php selected( $s['swatch_shape'], 'circle' ); ?>><?php esc_html_e( 'Circle (Modern Minimal)', 'atelier-companion' ); ?></option>
										<option value="rounded" <?php selected( $s['swatch_shape'], 'rounded' ); ?>><?php esc_html_e( 'Rounded Square (4px radius)', 'atelier-companion' ); ?></option>
										<option value="square" <?php selected( $s['swatch_shape'], 'square' ); ?>><?php esc_html_e( 'Sharp Square', 'atelier-companion' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Configure individual attribute colors or images under Products → Attributes → Configure terms.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="swatch_size"><?php esc_html_e( 'Swatch Size (px)', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="number" id="swatch_size" min="20" max="64" name="<?php echo esc_attr( self::OPTION_KEY . '[swatch_size]' ); ?>" value="<?php echo esc_attr( (string) $s['swatch_size'] ); ?>" class="small-text" /> px
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="free_shipping_threshold"><?php esc_html_e( 'Free Shipping Goal Amount', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="number" step="0.01" min="0" id="free_shipping_threshold" name="<?php echo esc_attr( self::OPTION_KEY . '[free_shipping_threshold]' ); ?>" value="<?php echo esc_attr( (string) $s['free_shipping_threshold'] ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Matches the £50 USP bar in Atelier Shop by default.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_bar_initial_text"><?php esc_html_e( 'Progress Message', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="shipping_bar_initial_text" name="<?php echo esc_attr( self::OPTION_KEY . '[shipping_bar_initial_text]' ); ?>" value="<?php echo esc_attr( $s['shipping_bar_initial_text'] ); ?>" class="large-text" />
									<p class="description"><?php esc_html_e( 'Use {remaining} as a placeholder for the remaining amount.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_bar_Complete_text"><?php esc_html_e( 'Goal Reached Message', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="shipping_bar_Complete_text" name="<?php echo esc_attr( self::OPTION_KEY . '[shipping_bar_Complete_text]' ); ?>" value="<?php echo esc_attr( $s['shipping_bar_Complete_text'] ); ?>" class="large-text" />
								</td>
							</tr>
						</table>

						<hr />

						<h2><?php esc_html_e( '3. Product Page & Size Guide Content', 'atelier-companion' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="low_stock_threshold"><?php esc_html_e( 'Low Stock Urgency Threshold', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="number" id="low_stock_threshold" min="1" max="100" name="<?php echo esc_attr( self::OPTION_KEY . '[low_stock_threshold]' ); ?>" value="<?php echo esc_attr( (string) $s['low_stock_threshold'] ); ?>" class="small-text" />
									<p class="description"><?php esc_html_e( 'Displays "Only X left in stock" urgency bar when product stock is at or below this number.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="estimated_delivery_days"><?php esc_html_e( 'Estimated Delivery Window', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="estimated_delivery_days" name="<?php echo esc_attr( self::OPTION_KEY . '[estimated_delivery_days]' ); ?>" value="<?php echo esc_attr( $s['estimated_delivery_days'] ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="trust_badges_text"><?php esc_html_e( 'Trust Badges Line', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="trust_badges_text" name="<?php echo esc_attr( self::OPTION_KEY . '[trust_badges_text]' ); ?>" value="<?php echo esc_attr( $s['trust_badges_text'] ); ?>" class="large-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="specifications_default"><?php esc_html_e( 'Accordion: Default Specifications Text', 'atelier-companion' ); ?></label></th>
								<td>
									<textarea id="specifications_default" rows="3" name="<?php echo esc_attr( self::OPTION_KEY . '[specifications_default]' ); ?>" class="large-text"><?php echo esc_textarea( $s['specifications_default'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Used when a product does not have custom attributes/dimensions.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="delivery_returns_summary"><?php esc_html_e( 'Accordion: Delivery & Returns Copy', 'atelier-companion' ); ?></label></th>
								<td>
									<textarea id="delivery_returns_summary" rows="3" name="<?php echo esc_attr( self::OPTION_KEY . '[delivery_returns_summary]' ); ?>" class="large-text"><?php echo esc_textarea( $s['delivery_returns_summary'] ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="size_guide_title"><?php esc_html_e( 'Size Guide Heading', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="size_guide_title" name="<?php echo esc_attr( self::OPTION_KEY . '[size_guide_title]' ); ?>" value="<?php echo esc_attr( $s['size_guide_title'] ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="size_guide_content"><?php esc_html_e( 'Default Size Guide Content', 'atelier-companion' ); ?></label></th>
								<td>
									<textarea id="size_guide_content" rows="6" name="<?php echo esc_attr( self::OPTION_KEY . '[size_guide_content]' ); ?>" class="large-text"><?php echo esc_textarea( $s['size_guide_content'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'One size per line (e.g., "S: Chest 87–92 cm | Waist 71–76 cm") automatically renders as a clean responsive table.', 'atelier-companion' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php submit_button( __( 'Save Atelier Companion Settings', 'atelier-companion' ) ); ?>
			</form>
		</div>
		<?php
	}
}
