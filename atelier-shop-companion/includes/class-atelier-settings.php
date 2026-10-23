<?php
/**
 * Atelier Companion Settings & WooCommerce Customizer Center.
 *
 * Provides:
 * 1. Deep WooCommerce Customizer sections inside `Appearance → Customize → WooCommerce`
 *    (Product Catalog, Single Product, Cart & Checkout, Wishlist/Quick View/Swatches)
 * 2. Header, Ticker, UI & Editorial Customizer sections inside `Appearance → Customize → Atelier Shop Options`
 * 3. A comprehensive Admin Settings dashboard under `WooCommerce → Atelier Companion`
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Settings {

	public const OPTION_KEY = 'atelier_companion_settings';

	/**
	 * Initialize settings & Customizer hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 60 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'customize_register', array( __CLASS__, 'register_customizer_sections' ), 30 );
		add_filter( 'plugin_action_links_' . plugin_basename( ATELIER_COMPANION_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Default configuration values for all modules and WooCommerce Customizer options.
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

			// WooCommerce Product Catalog Customizer Options
			'shop_columns'                  => 4,
			'shop_products_per_page'        => 12,
			'shop_card_alignment'           => 'left',     // left | center | right
			'shop_card_style'               => 'minimal',  // minimal | bordered | elevated
			'shop_image_aspect_ratio'       => '3/4',      // auto | 1/1 | 3/4 | 4/5 | 4/3
			'shop_product_hover'            => 'image-swap', // none | image-swap | zoom | swap-zoom
			'product_sale_badge'            => 'percentage', // percentage | text | hide
			'product_sale_badge_text'       => 'Sale!',
			'sold_out_badge_text'           => 'Sold Out',
			'catalog_show_category'         => true,
			'catalog_show_rating'           => true,
			'catalog_show_price'            => true,
			'catalog_show_add_to_cart'      => true,

			// WooCommerce Single Product Customizer Options
			'wc_gallery_zoom'               => true,
			'wc_gallery_lightbox'           => true,
			'wc_gallery_slider'             => true,
			'wc_sticky_gallery'             => true,
			'single_show_meta'              => true,
			'wc_related_products'           => true,
			'wc_related_columns'            => 4,
			'wc_related_count'              => 4,
			'wc_upsell_products'            => true,
			'wc_upsells_columns'            => 4,

			// WooCommerce Cart & Checkout Customizer Options
			'wc_cross_sell_products'        => true,
			'wc_cross_sell_columns'         => 4,
			'wc_empty_cart_button'          => true,
			'wc_distraction_free_checkout'  => false,

			// Swatch settings
			'swatch_shape'                  => 'circle', // circle | rounded | square
			'swatch_size'                   => 32,

			// Free shipping bar settings
			'free_shipping_threshold'       => 50.0,
			'shipping_bar_initial_text'     => 'Spend {remaining} more to unlock free delivery',
			'shipping_bar_Complete_text'    => 'You have unlocked free standard delivery!',
			'shipping_bar_show_mini'        => true,

			// Live search settings
			'live_search_max_results'       => 6,

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
	 * Register sections & controls inside the WordPress Customizer:
	 * - Directly enhances the native `woocommerce` panel (`Appearance → Customize → WooCommerce`)
	 * - Also populates the `atelier_shop_panel` (`Appearance → Customize → Atelier Shop Options`)
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
	 */
	public static function register_customizer_sections( WP_Customize_Manager $wp_customize ): void {
		$defaults = self::defaults();
		$opt      = self::OPTION_KEY;

		// Ensure the `woocommerce` panel exists and is prioritized near the top (like Bloghash Pro).
		if ( $wp_customize->get_panel( 'woocommerce' ) ) {
			$wp_customize->get_panel( 'woocommerce' )->priority = 20;
		} else {
			$wp_customize->add_panel( 'woocommerce', array(
				'title'       => __( 'WooCommerce', 'atelier-companion' ),
				'description' => __( 'Customize every aspect of your WooCommerce storefront, product catalog, single product page, and checkout.', 'atelier-companion' ),
				'priority'    => 20,
			) );
		}

		// Ensure `woocommerce_product_catalog` section exists inside `woocommerce` panel.
		if ( ! $wp_customize->get_section( 'woocommerce_product_catalog' ) ) {
			$wp_customize->add_section( 'woocommerce_product_catalog', array(
				'title'    => __( 'Product Catalog & Cards', 'atelier-companion' ),
				'panel'    => 'woocommerce',
				'priority' => 10,
			) );
		} else {
			$wp_customize->get_section( 'woocommerce_product_catalog' )->title = __( 'Product Catalog & Cards', 'atelier-companion' );
		}

		/* ======================================================================
		   A. WOOCOMMERCE PANEL -> 1. PRODUCT CATALOG & CARDS
		   ====================================================================== */
		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'shop_columns', __( 'Shop Grid Columns (Desktop)', 'atelier-companion' ), array(
			2 => __( '2 Columns', 'atelier-companion' ),
			3 => __( '3 Columns', 'atelier-companion' ),
			4 => __( '4 Columns (Default)', 'atelier-companion' ),
			5 => __( '5 Columns', 'atelier-companion' ),
			6 => __( '6 Columns', 'atelier-companion' ),
		) );

		self::add_customize_number( $wp_customize, 'woocommerce_product_catalog', 'shop_products_per_page', __( 'Products Per Page', 'atelier-companion' ), 4, 48, 1 );

		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'shop_card_alignment', __( 'Product Card Text Alignment', 'atelier-companion' ), array(
			'left'   => __( 'Left Aligned', 'atelier-companion' ),
			'center' => __( 'Center Aligned', 'atelier-companion' ),
			'right'  => __( 'Right Aligned', 'atelier-companion' ),
		) );

		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'shop_card_style', __( 'Product Card Frame Style', 'atelier-companion' ), array(
			'minimal'  => __( 'Minimal (Clean Borderless)', 'atelier-companion' ),
			'bordered' => __( 'Bordered Card (1px Frame)', 'atelier-companion' ),
			'elevated' => __( 'Elevated Card (Soft Shadow)', 'atelier-companion' ),
		) );

		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'shop_image_aspect_ratio', __( 'Product Image Aspect Ratio', 'atelier-companion' ), array(
			'auto' => __( 'Original / Uncropped', 'atelier-companion' ),
			'1/1'  => __( '1:1 Square', 'atelier-companion' ),
			'3/4'  => __( '3:4 Portrait (Fashion Standard)', 'atelier-companion' ),
			'4/5'  => __( '4:5 Editorial Portrait', 'atelier-companion' ),
			'4/3'  => __( '4:3 Landscape', 'atelier-companion' ),
		) );

		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'shop_product_hover', __( 'Product Image Hover Effect', 'atelier-companion' ), array(
			'none'       => __( 'No Effect', 'atelier-companion' ),
			'image-swap' => __( 'Gallery Image Swap (Bloghash Pro)', 'atelier-companion' ),
			'zoom'       => __( 'Smooth Image Scale Zoom', 'atelier-companion' ),
			'swap-zoom'  => __( 'Gallery Image Swap + Zoom', 'atelier-companion' ),
		) );

		self::add_customize_select( $wp_customize, 'woocommerce_product_catalog', 'product_sale_badge', __( 'Sale Badge Display Mode', 'atelier-companion' ), array(
			'percentage' => __( 'Show Discount Percentage (-25%)', 'atelier-companion' ),
			'text'       => __( 'Show Custom Text ("Sale!")', 'atelier-companion' ),
			'hide'       => __( 'Hide Sale Badge', 'atelier-companion' ),
		) );

		self::add_customize_text( $wp_customize, 'woocommerce_product_catalog', 'product_sale_badge_text', __( 'Custom Sale Badge Text', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'woocommerce_product_catalog', 'sold_out_badge_text', __( 'Out-of-Stock ("Sold Out") Badge Text', 'atelier-companion' ) );

		self::add_customize_checkbox( $wp_customize, 'woocommerce_product_catalog', 'catalog_show_category', __( 'Show Product Category on Cards', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'woocommerce_product_catalog', 'catalog_show_rating', __( 'Show Star Ratings on Cards', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'woocommerce_product_catalog', 'catalog_show_price', __( 'Show Product Price on Cards', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'woocommerce_product_catalog', 'catalog_show_add_to_cart', __( 'Show Add to Cart Button on Cards', 'atelier-companion' ) );

		/* ======================================================================
		   B. WOOCOMMERCE PANEL -> 2. SINGLE PRODUCT PAGE (Bloghash Pro + Atelier)
		   ====================================================================== */
		$wp_customize->add_section( 'atelier_wc_single_product', array(
			'title'       => __( 'Single Product Page', 'atelier-companion' ),
			'description' => __( 'Customize gallery behavior, accordions, sticky cart bar, urgency indicators, and related/upsell grids.', 'atelier-companion' ),
			'panel'       => 'woocommerce',
			'priority'    => 25,
		) );

		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_gallery_zoom', __( 'Enable Product Gallery Hover Zoom', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_gallery_lightbox', __( 'Enable Product Gallery Lightbox', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_gallery_slider', __( 'Enable Product Gallery Carousel Slider', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_sticky_gallery', __( 'Sticky Product Gallery on Desktop Scroll', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_product_accordions', __( 'Use Collapsible Accordions Instead of Tabs', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_qty_buttons', __( 'Enable Plus / Minus (+/-) Quantity Buttons', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'single_show_meta', __( 'Show Product SKU, Categories & Tags Meta', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_sticky_cart', __( 'Enable Sticky Bottom Add-to-Cart Bar', 'atelier-companion' ) );

		// Urgency & Reassurance
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_stock_urgency', __( 'Enable Low-Stock Urgency Progress Bar', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_single_product', 'low_stock_threshold', __( 'Low-Stock Urgency Threshold (Qty)', 'atelier-companion' ), 1, 100, 1 );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_trust_badges', __( 'Show Estimated Delivery & Trust Badges', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'atelier_wc_single_product', 'estimated_delivery_days', __( 'Estimated Delivery Window Text', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'atelier_wc_single_product', 'trust_badges_text', __( 'Trust Badges Line', 'atelier-companion' ) );

		// Size Guide & Accordion Copy
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'enable_size_guide', __( 'Enable Size & Fit Guide Modal', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'atelier_wc_single_product', 'size_guide_title', __( 'Size Guide Button & Modal Heading', 'atelier-companion' ) );
		self::add_customize_textarea( $wp_customize, 'atelier_wc_single_product', 'size_guide_content', __( 'Default Size Guide Table Content', 'atelier-companion' ) );
		self::add_customize_textarea( $wp_customize, 'atelier_wc_single_product', 'specifications_default', __( 'Default Specifications Accordion Text', 'atelier-companion' ) );
		self::add_customize_textarea( $wp_customize, 'atelier_wc_single_product', 'delivery_returns_summary', __( 'Delivery & Returns Accordion Copy', 'atelier-companion' ) );

		// Related & Up-Sell Products (Bloghash Pro)
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_related_products', __( 'Display Related Products Section', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_single_product', 'wc_related_columns', __( 'Related Products Columns (1–6)', 'atelier-companion' ), 1, 6, 1 );
		self::add_customize_number( $wp_customize, 'atelier_wc_single_product', 'wc_related_count', __( 'Related Products Total Count', 'atelier-companion' ), 1, 12, 1 );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_single_product', 'wc_upsell_products', __( 'Display Up-Sell Products Section', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_single_product', 'wc_upsells_columns', __( 'Up-Sell Products Columns (1–6)', 'atelier-companion' ), 1, 6, 1 );

		/* ======================================================================
		   C. WOOCOMMERCE PANEL -> 3. VARIATION SWATCHES, WISHLIST & QUICK VIEW
		   ====================================================================== */
		$wp_customize->add_section( 'atelier_wc_conversion', array(
			'title'       => __( 'Swatches, Wishlist & Quick View', 'atelier-companion' ),
			'description' => __( 'Configure variation swatches, customer wishlist drawer, product quick view modal, and instant search.', 'atelier-companion' ),
			'panel'       => 'woocommerce',
			'priority'    => 28,
		) );

		self::add_customize_checkbox( $wp_customize, 'atelier_wc_conversion', 'enable_swatches', __( 'Enable Variation Color, Image & Pill Swatches', 'atelier-companion' ) );
		self::add_customize_select( $wp_customize, 'atelier_wc_conversion', 'swatch_shape', __( 'Variation Swatch Shape', 'atelier-companion' ), array(
			'circle'  => __( 'Circle (Modern Minimal)', 'atelier-companion' ),
			'rounded' => __( 'Rounded Square (4px Radius)', 'atelier-companion' ),
			'square'  => __( 'Sharp Square', 'atelier-companion' ),
		) );
		self::add_customize_number( $wp_customize, 'atelier_wc_conversion', 'swatch_size', __( 'Variation Swatch Size (px)', 'atelier-companion' ), 20, 64, 2 );

		self::add_customize_checkbox( $wp_customize, 'atelier_wc_conversion', 'enable_wishlist', __( 'Enable Wishlist Heart Buttons & Drawer', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_conversion', 'enable_quick_view', __( 'Enable Product Quick View Modal', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_conversion', 'enable_live_search', __( 'Enable Instant AJAX Live Product Search', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_conversion', 'live_search_max_results', __( 'Max Live Search Suggestions (3–12)', 'atelier-companion' ), 3, 12, 1 );

		/* ======================================================================
		   D. WOOCOMMERCE PANEL -> 4. CART, FREE SHIPPING BAR & CHECKOUT
		   ====================================================================== */
		$wp_customize->add_section( 'atelier_wc_cart_checkout', array(
			'title'       => __( 'Cart & Free Shipping Goal', 'atelier-companion' ),
			'description' => __( 'Customize the dynamic free shipping progress bar, cart cross-sells, and checkout experience.', 'atelier-companion' ),
			'panel'       => 'woocommerce',
			'priority'    => 32,
		) );

		self::add_customize_checkbox( $wp_customize, 'atelier_wc_cart_checkout', 'enable_shipping_bar', __( 'Enable Free Shipping Progress Bar', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_cart_checkout', 'free_shipping_threshold', __( 'Free Shipping Goal Amount', 'atelier-companion' ), 0, 10000, 1 );
		self::add_customize_text( $wp_customize, 'atelier_wc_cart_checkout', 'shipping_bar_initial_text', __( 'Progress Message (use {remaining})', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'atelier_wc_cart_checkout', 'shipping_bar_Complete_text', __( 'Free Shipping Unlocked Message', 'atelier-companion' ) );

		self::add_customize_checkbox( $wp_customize, 'atelier_wc_cart_checkout', 'wc_cross_sell_products', __( 'Display Cross-Sell Products on Cart Page', 'atelier-companion' ) );
		self::add_customize_number( $wp_customize, 'atelier_wc_cart_checkout', 'wc_cross_sell_columns', __( 'Cross-Sell Products Columns (1–6)', 'atelier-companion' ), 1, 6, 1 );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_cart_checkout', 'wc_empty_cart_button', __( 'Show Styled "Return to Shop" Button on Empty Cart', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_wc_cart_checkout', 'wc_distraction_free_checkout', __( 'Distraction-Free Checkout (Hide Ticker & Extra Icons)', 'atelier-companion' ) );

		/* ======================================================================
		   E. ATELIER SHOP OPTIONS PANEL -> HEADER, TICKER, UI & EDITORIAL
		   ====================================================================== */
		if ( ! $wp_customize->get_panel( 'atelier_shop_panel' ) ) {
			$wp_customize->add_panel( 'atelier_shop_panel', array(
				'title'       => __( 'Atelier Shop Options', 'atelier-companion' ),
				'description' => __( 'Customize Atelier Shop colors, layout, header, and editorial features.', 'atelier-companion' ),
				'priority'    => 25,
			) );
		}

		$wp_customize->add_section( 'atelier_customizer_header_ui', array(
			'title'    => __( 'Header, Ticker & UI (Bloghash Pro)', 'atelier-companion' ),
			'panel'    => 'atelier_shop_panel',
			'priority' => 30,
		) );

		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_header_ui', 'enable_dark_mode', __( 'Enable Dark / Light Mode Header Toggle', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_header_ui', 'enable_sticky_header', __( 'Enable Sticky Header on Scroll', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_header_ui', 'enable_top_ticker', __( 'Enable Top Marquee Announcement Ticker Bar', 'atelier-companion' ) );
		self::add_customize_text( $wp_customize, 'atelier_customizer_header_ui', 'ticker_label', __( 'Top Ticker Badge Label', 'atelier-companion' ) );
		self::add_customize_textarea( $wp_customize, 'atelier_customizer_header_ui', 'ticker_items_text', __( 'Top Ticker Announcements (1 per line)', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_header_ui', 'enable_scroll_top', __( 'Enable Floating Scroll-to-Top Button', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_header_ui', 'enable_cursor_dot', __( 'Enable Desktop Cursor Dot Follower', 'atelier-companion' ) );

		$wp_customize->add_section( 'atelier_customizer_editorial', array(
			'title'    => __( 'Blog & Editorial (Bloghash Pro)', 'atelier-companion' ),
			'panel'    => 'atelier_shop_panel',
			'priority' => 50,
		) );

		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_editorial', 'enable_toc', __( 'Auto Table of Contents on Blog Posts', 'atelier-companion' ) );
		self::add_customize_checkbox( $wp_customize, 'atelier_customizer_editorial', 'enable_post_likes', __( 'Enable Article Quick-Like Heart Button', 'atelier-companion' ) );
	}

	/**
	 * Helper: Register a checkbox setting + control in WP_Customize_Manager.
	 */
	private static function add_customize_checkbox( WP_Customize_Manager $wp_customize, string $section, string $key, string $label ): void {
		$defaults   = self::defaults();
		$setting_id = self::OPTION_KEY . "[{$key}]";
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'option',
			'default'           => $defaults[ $key ] ?? true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'checkbox',
		) );
	}

	/**
	 * Helper: Register a select dropdown setting + control in WP_Customize_Manager.
	 *
	 * @param array<int|string, string> $choices Dropdown choices.
	 */
	private static function add_customize_select( WP_Customize_Manager $wp_customize, string $section, string $key, string $label, array $choices ): void {
		$defaults   = self::defaults();
		$setting_id = self::OPTION_KEY . "[{$key}]";
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'option',
			'default'           => $defaults[ $key ] ?? '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'select',
			'choices' => $choices,
		) );
	}

	/**
	 * Helper: Register a number setting + control in WP_Customize_Manager.
	 */
	private static function add_customize_number( WP_Customize_Manager $wp_customize, string $section, string $key, string $label, int $min, int $max, int $step = 1 ): void {
		$defaults   = self::defaults();
		$setting_id = self::OPTION_KEY . "[{$key}]";
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'option',
			'default'           => $defaults[ $key ] ?? $min,
			'sanitize_callback' => 'floatval',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'       => $label,
			'section'     => $section,
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => $min,
				'max'  => $max,
				'step' => $step,
			),
		) );
	}

	/**
	 * Helper: Register a text setting + control in WP_Customize_Manager.
	 */
	private static function add_customize_text( WP_Customize_Manager $wp_customize, string $section, string $key, string $label ): void {
		$defaults   = self::defaults();
		$setting_id = self::OPTION_KEY . "[{$key}]";
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'option',
			'default'           => $defaults[ $key ] ?? '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'text',
		) );
	}

	/**
	 * Helper: Register a textarea setting + control in WP_Customize_Manager.
	 */
	private static function add_customize_textarea( WP_Customize_Manager $wp_customize, string $section, string $key, string $label ): void {
		$defaults   = self::defaults();
		$setting_id = self::OPTION_KEY . "[{$key}]";
		$wp_customize->add_setting( $setting_id, array(
			'type'              => 'option',
			'default'           => $defaults[ $key ] ?? '',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'textarea',
		) );
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
	 * Add quick "Settings" and "Customize" links on the Plugins list page.
	 *
	 * @param array<string> $links Existing plugin action links.
	 * @return array<string>
	 */
	public static function add_settings_link( array $links ): array {
		$settings_url  = admin_url( ( class_exists( 'WooCommerce' ) ? 'admin.php?page=atelier-companion' : 'options-general.php?page=atelier-companion' ) );
		$customize_url = admin_url( 'customize.php?autofocus[panel]=woocommerce' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'atelier-companion' ) . '</a>',
			'<a href="' . esc_url( $customize_url ) . '">' . esc_html__( 'Customize WooCommerce', 'atelier-companion' ) . '</a>'
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

		// Merge with existing saved options so partial updates from Customizer don't reset untouched keys.
		$existing = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		$merged   = array_merge( self::defaults(), $existing, $input );
		$defaults = self::defaults();

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
			'catalog_show_category',
			'catalog_show_rating',
			'catalog_show_price',
			'catalog_show_add_to_cart',
			'wc_gallery_zoom',
			'wc_gallery_lightbox',
			'wc_gallery_slider',
			'wc_sticky_gallery',
			'single_show_meta',
			'wc_related_products',
			'wc_upsell_products',
			'wc_cross_sell_products',
			'wc_empty_cart_button',
			'wc_distraction_free_checkout',
		);

		$clean = array();
		foreach ( $boolean_keys as $bkey ) {
			// If submitted via admin form (`_atelier_admin_form_submit`), unchecked checkboxes are absent in $input.
			if ( ! empty( $input['_atelier_admin_form_submit'] ) ) {
				$clean[ $bkey ] = ! empty( $input[ $bkey ] );
			} else {
				$clean[ $bkey ] = ! empty( $merged[ $bkey ] );
			}
		}

		$allowed_shapes        = array( 'circle', 'rounded', 'square' );
		$clean['swatch_shape'] = in_array( $merged['swatch_shape'] ?? 'circle', $allowed_shapes, true )
			? $merged['swatch_shape']
			: 'circle';

		$allowed_align                = array( 'left', 'center', 'right' );
		$clean['shop_card_alignment'] = in_array( $merged['shop_card_alignment'] ?? 'left', $allowed_align, true )
			? $merged['shop_card_alignment']
			: 'left';

		$allowed_card_styles      = array( 'minimal', 'bordered', 'elevated' );
		$clean['shop_card_style'] = in_array( $merged['shop_card_style'] ?? 'minimal', $allowed_card_styles, true )
			? $merged['shop_card_style']
			: 'minimal';

		$allowed_ratios                   = array( 'auto', '1/1', '3/4', '4/5', '4/3' );
		$clean['shop_image_aspect_ratio'] = in_array( $merged['shop_image_aspect_ratio'] ?? '3/4', $allowed_ratios, true )
			? $merged['shop_image_aspect_ratio']
			: '3/4';

		$allowed_hover               = array( 'none', 'image-swap', 'zoom', 'swap-zoom' );
		$clean['shop_product_hover'] = in_array( $merged['shop_product_hover'] ?? 'image-swap', $allowed_hover, true )
			? $merged['shop_product_hover']
			: 'image-swap';

		$allowed_badge               = array( 'percentage', 'text', 'hide' );
		$clean['product_sale_badge'] = in_array( $merged['product_sale_badge'] ?? 'percentage', $allowed_badge, true )
			? $merged['product_sale_badge']
			: 'percentage';

		$clean['shop_columns']            = max( 2, min( 6, (int) ( $merged['shop_columns'] ?? 4 ) ) );
		$clean['shop_products_per_page']  = max( 4, min( 48, (int) ( $merged['shop_products_per_page'] ?? 12 ) ) );
		$clean['wc_related_columns']      = max( 1, min( 6, (int) ( $merged['wc_related_columns'] ?? 4 ) ) );
		$clean['wc_related_count']        = max( 1, min( 12, (int) ( $merged['wc_related_count'] ?? 4 ) ) );
		$clean['wc_upsells_columns']      = max( 1, min( 6, (int) ( $merged['wc_upsells_columns'] ?? 4 ) ) );
		$clean['wc_cross_sell_columns']   = max( 1, min( 6, (int) ( $merged['wc_cross_sell_columns'] ?? 4 ) ) );
		$clean['swatch_size']             = max( 20, min( 64, (int) ( $merged['swatch_size'] ?? 32 ) ) );
		$clean['free_shipping_threshold'] = max( 0, (float) ( $merged['free_shipping_threshold'] ?? 50 ) );
		$clean['low_stock_threshold']     = max( 1, min( 100, (int) ( $merged['low_stock_threshold'] ?? 8 ) ) );
		$clean['live_search_max_results'] = max( 3, min( 12, (int) ( $merged['live_search_max_results'] ?? 6 ) ) );

		$text_keys = array(
			'product_sale_badge_text',
			'sold_out_badge_text',
			'shipping_bar_initial_text',
			'shipping_bar_Complete_text',
			'ticker_label',
			'estimated_delivery_days',
			'trust_badges_text',
			'size_guide_title',
		);

		foreach ( $text_keys as $tkey ) {
			$clean[ $tkey ] = isset( $merged[ $tkey ] )
				? sanitize_text_field( wp_unslash( (string) $merged[ $tkey ] ) )
				: $defaults[ $tkey ];
		}

		$textarea_keys = array(
			'ticker_items_text',
			'delivery_returns_summary',
			'specifications_default',
			'size_guide_content',
		);
		foreach ( $textarea_keys as $takey ) {
			$clean[ $takey ] = isset( $merged[ $takey ] )
				? sanitize_textarea_field( wp_unslash( (string) $merged[ $takey ] ) )
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
				<div style="display:flex;align-items:center;gap:12px;">
					<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=woocommerce' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Customize WooCommerce Live →', 'atelier-companion' ); ?>
					</a>
					<span class="atelier-version-badge">v<?php echo esc_html( ATELIER_COMPANION_VERSION ); ?></span>
				</div>
			</div>

			<form method="post" action="options.php" class="atelier-settings-form">
				<?php settings_fields( 'atelier_companion_group' ); ?>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY . '[_atelier_admin_form_submit]' ); ?>" value="1" />

				<div class="atelier-admin-grid">
					<!-- Card 1: Core & Bloghash Pro Modules -->
					<div class="atelier-admin-card">
						<h2><?php esc_html_e( '1. Active Storefront & Bloghash Pro Modules', 'atelier-companion' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Toggle individual storefront enhancements on or off. You can also preview and customize all WooCommerce layout & card options live in Appearance → Customize → WooCommerce.', 'atelier-companion' ); ?></p>

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
						<h2><?php esc_html_e( '2. Product Catalog, Cards & Badges', 'atelier-companion' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="shop_columns"><?php esc_html_e( 'Shop Grid Columns (Desktop)', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="shop_columns" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_columns]' ); ?>">
										<?php foreach ( array( 2, 3, 4, 5, 6 ) as $col ) : ?>
											<option value="<?php echo esc_attr( (string) $col ); ?>" <?php selected( (int) $s['shop_columns'], $col ); ?>><?php echo esc_html( $col . ' ' . __( 'Columns', 'atelier-companion' ) ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shop_products_per_page"><?php esc_html_e( 'Products Per Page', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="number" id="shop_products_per_page" min="4" max="48" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_products_per_page]' ); ?>" value="<?php echo esc_attr( (string) $s['shop_products_per_page'] ); ?>" class="small-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shop_card_alignment"><?php esc_html_e( 'Product Card Alignment', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="shop_card_alignment" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_card_alignment]' ); ?>">
										<option value="left" <?php selected( $s['shop_card_alignment'], 'left' ); ?>><?php esc_html_e( 'Left Aligned', 'atelier-companion' ); ?></option>
										<option value="center" <?php selected( $s['shop_card_alignment'], 'center' ); ?>><?php esc_html_e( 'Center Aligned', 'atelier-companion' ); ?></option>
										<option value="right" <?php selected( $s['shop_card_alignment'], 'right' ); ?>><?php esc_html_e( 'Right Aligned', 'atelier-companion' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shop_card_style"><?php esc_html_e( 'Product Card Frame Style', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="shop_card_style" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_card_style]' ); ?>">
										<option value="minimal" <?php selected( $s['shop_card_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal (Clean Borderless)', 'atelier-companion' ); ?></option>
										<option value="bordered" <?php selected( $s['shop_card_style'], 'bordered' ); ?>><?php esc_html_e( 'Bordered Card (1px Frame)', 'atelier-companion' ); ?></option>
										<option value="elevated" <?php selected( $s['shop_card_style'], 'elevated' ); ?>><?php esc_html_e( 'Elevated Card (Soft Shadow)', 'atelier-companion' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shop_image_aspect_ratio"><?php esc_html_e( 'Product Image Aspect Ratio', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="shop_image_aspect_ratio" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_image_aspect_ratio]' ); ?>">
										<option value="auto" <?php selected( $s['shop_image_aspect_ratio'], 'auto' ); ?>><?php esc_html_e( 'Original / Uncropped', 'atelier-companion' ); ?></option>
										<option value="1/1" <?php selected( $s['shop_image_aspect_ratio'], '1/1' ); ?>><?php esc_html_e( '1:1 Square', 'atelier-companion' ); ?></option>
										<option value="3/4" <?php selected( $s['shop_image_aspect_ratio'], '3/4' ); ?>><?php esc_html_e( '3:4 Portrait (Fashion Standard)', 'atelier-companion' ); ?></option>
										<option value="4/5" <?php selected( $s['shop_image_aspect_ratio'], '4/5' ); ?>><?php esc_html_e( '4:5 Editorial Portrait', 'atelier-companion' ); ?></option>
										<option value="4/3" <?php selected( $s['shop_image_aspect_ratio'], '4/3' ); ?>><?php esc_html_e( '4:3 Landscape', 'atelier-companion' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shop_product_hover"><?php esc_html_e( 'Product Image Hover Effect', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="shop_product_hover" name="<?php echo esc_attr( self::OPTION_KEY . '[shop_product_hover]' ); ?>">
										<option value="none" <?php selected( $s['shop_product_hover'], 'none' ); ?>><?php esc_html_e( 'No Effect', 'atelier-companion' ); ?></option>
										<option value="image-swap" <?php selected( $s['shop_product_hover'], 'image-swap' ); ?>><?php esc_html_e( 'Gallery Image Swap', 'atelier-companion' ); ?></option>
										<option value="zoom" <?php selected( $s['shop_product_hover'], 'zoom' ); ?>><?php esc_html_e( 'Smooth Image Scale Zoom', 'atelier-companion' ); ?></option>
										<option value="swap-zoom" <?php selected( $s['shop_product_hover'], 'swap-zoom' ); ?>><?php esc_html_e( 'Gallery Image Swap + Zoom', 'atelier-companion' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="product_sale_badge"><?php esc_html_e( 'Sale Badge Display Mode', 'atelier-companion' ); ?></label></th>
								<td>
									<select id="product_sale_badge" name="<?php echo esc_attr( self::OPTION_KEY . '[product_sale_badge]' ); ?>">
										<option value="percentage" <?php selected( $s['product_sale_badge'], 'percentage' ); ?>><?php esc_html_e( 'Discount Percentage (-25%)', 'atelier-companion' ); ?></option>
										<option value="text" <?php selected( $s['product_sale_badge'], 'text' ); ?>><?php esc_html_e( 'Custom Text ("Sale!")', 'atelier-companion' ); ?></option>
										<option value="hide" <?php selected( $s['product_sale_badge'], 'hide' ); ?>><?php esc_html_e( 'Hide Sale Badge', 'atelier-companion' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="product_sale_badge_text"><?php esc_html_e( 'Custom Sale Badge Text', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="product_sale_badge_text" name="<?php echo esc_attr( self::OPTION_KEY . '[product_sale_badge_text]' ); ?>" value="<?php echo esc_attr( $s['product_sale_badge_text'] ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="sold_out_badge_text"><?php esc_html_e( 'Out-of-Stock Badge Text', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="sold_out_badge_text" name="<?php echo esc_attr( self::OPTION_KEY . '[sold_out_badge_text]' ); ?>" value="<?php echo esc_attr( $s['sold_out_badge_text'] ); ?>" class="regular-text" />
								</td>
							</tr>
						</table>

						<hr />

						<h2><?php esc_html_e( '3. Top Ticker, Swatches & Free Shipping Bar', 'atelier-companion' ); ?></h2>
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
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_bar_initial_text"><?php esc_html_e( 'Progress Message', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="text" id="shipping_bar_initial_text" name="<?php echo esc_attr( self::OPTION_KEY . '[shipping_bar_initial_text]' ); ?>" value="<?php echo esc_attr( $s['shipping_bar_initial_text'] ); ?>" class="large-text" />
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

						<h2><?php esc_html_e( '4. Product Page & Size Guide Content', 'atelier-companion' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="low_stock_threshold"><?php esc_html_e( 'Low Stock Urgency Threshold', 'atelier-companion' ); ?></label></th>
								<td>
									<input type="number" id="low_stock_threshold" min="1" max="100" name="<?php echo esc_attr( self::OPTION_KEY . '[low_stock_threshold]' ); ?>" value="<?php echo esc_attr( (string) $s['low_stock_threshold'] ); ?>" class="small-text" />
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
