<?php
/**
 * Plugin Name:       Atelier Shop Companion
 * Plugin URI:        https://example.com/atelier-shop-companion
 * Description:       Modern, customizable e-commerce & Bloghash Pro features for the Atelier Shop WooCommerce block theme — including Wishlist, Variation Swatches, Quick View, Free Shipping Progress Bar, Image Swap on Hover, Percentage Sale Badges, +/- Quantity Buttons, Dark Mode, Top Marquee Ticker, Scroll-to-Top, TOC, Product Accordions, Size Guide, Sticky Add-to-Cart, Stock Urgency, and Live AJAX Search.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Atelier Shop
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       atelier-companion
 * Domain Path:       /languages
 * WC requires at least: 8.5
 * WC tested up to:   9.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATELIER_COMPANION_VERSION', '1.1.0' );
define( 'ATELIER_COMPANION_FILE', __FILE__ );
define( 'ATELIER_COMPANION_PATH', plugin_dir_path( __FILE__ ) );
define( 'ATELIER_COMPANION_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * and Cart/Checkout Blocks.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Main Atelier Companion Plugin Class.
 */
final class Atelier_Shop_Companion {

	/**
	 * Singleton instance.
	 *
	 * @var Atelier_Shop_Companion|null
	 */
	private static ?Atelier_Shop_Companion $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Atelier_Shop_Companion
	 */
	public static function instance(): Atelier_Shop_Companion {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin modules once WooCommerce is loaded.
	 */
	public function init(): void {
		// Always load settings so admin can configure even if WooCommerce is being set up.
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-settings.php';
		Atelier_Companion_Settings::init();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Load active feature modules based on settings.
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-wishlist.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-swatches.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-quick-view.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-shipping-bar.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-product-enhancements.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-live-search.php';
		require_once ATELIER_COMPANION_PATH . 'includes/class-atelier-bloghash-features.php';

		if ( Atelier_Companion_Settings::get( 'enable_wishlist', true ) ) {
			Atelier_Companion_Wishlist::init();
		}

		if ( Atelier_Companion_Settings::get( 'enable_swatches', true ) ) {
			Atelier_Companion_Swatches::init();
		}

		if ( Atelier_Companion_Settings::get( 'enable_quick_view', true ) ) {
			Atelier_Companion_Quick_View::init();
		}

		if ( Atelier_Companion_Settings::get( 'enable_shipping_bar', true ) ) {
			Atelier_Companion_Shipping_Bar::init();
		}

		Atelier_Companion_Product_Enhancements::init();
		Atelier_Companion_Bloghash_Features::init();

		if ( Atelier_Companion_Settings::get( 'enable_live_search', true ) ) {
			Atelier_Companion_Live_Search::init();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'init', array( $this, 'register_block_patterns' ) );
	}

	/**
	 * Enqueue frontend CSS & JS and pass localized configuration.
	 */
	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'atelier-companion',
			ATELIER_COMPANION_URL . 'assets/css/frontend.css',
			array(),
			ATELIER_COMPANION_VERSION
		);

		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}

		wp_enqueue_script(
			'atelier-companion',
			ATELIER_COMPANION_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			ATELIER_COMPANION_VERSION,
			true
		);

		$settings = Atelier_Companion_Settings::get_all();

		wp_localize_script(
			'atelier-companion',
			'atelierCompanion',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'atelier_companion_nonce' ),
				'isLoggedIn'         => is_user_logged_in(),
				'wishlistIds'        => Atelier_Companion_Settings::get( 'enable_wishlist', true )
					? Atelier_Companion_Wishlist::get_user_wishlist()
					: array(),
				'cartUrl'            => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart/',
				'checkoutUrl'        => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '/checkout/',
				'currencySymbol'     => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '£',
				'freeShippingGoal'   => (float) ( $settings['free_shipping_threshold'] ?? 50 ),
				'swatchShape'        => $settings['swatch_shape'] ?? 'circle',
				'enableLiveSearch'   => ! empty( $settings['enable_live_search'] ),
				'enableStickyCart'   => ! empty( $settings['enable_sticky_cart'] ),
				'enableQtyButtons'   => ! empty( $settings['enable_qty_buttons'] ),
				'enableDarkMode'     => ! empty( $settings['enable_dark_mode'] ),
				'enableStickyHeader' => ! empty( $settings['enable_sticky_header'] ),
				'enableScrollTop'    => ! empty( $settings['enable_scroll_top'] ),
				'enableCursorDot'    => ! empty( $settings['enable_cursor_dot'] ),
				'i18n'               => array(
					'addedToWishlist'     => __( 'Saved to Wishlist', 'atelier-companion' ),
					'removedFromWishlist' => __( 'Removed from Wishlist', 'atelier-companion' ),
					'emptyWishlist'       => __( 'Your wishlist is currently empty.', 'atelier-companion' ),
					'addToCart'           => __( 'Add to cart', 'atelier-companion' ),
					'adding'              => __( 'Adding…', 'atelier-companion' ),
					'added'               => __( 'Added!', 'atelier-companion' ),
					'noResults'           => __( 'No matching products found.', 'atelier-companion' ),
					'searching'           => __( 'Searching…', 'atelier-companion' ),
					'close'               => __( 'Close', 'atelier-companion' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin scripts for attribute swatches & settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if (
			str_contains( $hook_suffix, 'atelier-companion' ) ||
			str_contains( $hook_suffix, 'edit-tags.php' ) ||
			str_contains( $hook_suffix, 'term.php' )
		) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script(
				'atelier-companion-admin',
				ATELIER_COMPANION_URL . 'assets/js/admin.js',
				array( 'jquery', 'wp-color-picker' ),
				ATELIER_COMPANION_VERSION,
				true
			);
			wp_enqueue_style(
				'atelier-companion-admin',
				ATELIER_COMPANION_URL . 'assets/css/admin.css',
				array(),
				ATELIER_COMPANION_VERSION
			);
		}
	}

	/**
	 * Register companion block pattern category & patterns.
	 */
	public function register_block_patterns(): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		register_block_pattern_category(
			'atelier-ecommerce',
			array( 'label' => __( 'Atelier E-Commerce', 'atelier-companion' ) )
		);

		register_block_pattern(
			'atelier-companion/free-shipping-banner',
			array(
				'title'       => __( 'Dynamic Free Shipping Bar', 'atelier-companion' ),
				'description' => __( 'Displays a live progress bar showing how close the customer is to free shipping.', 'atelier-companion' ),
				'categories'  => array( 'atelier-ecommerce', 'featured' ),
				'content'     => '<!-- wp:shortcode -->[atelier_shipping_bar]<!-- /wp:shortcode -->',
			)
		);

		register_block_pattern(
			'atelier-companion/wishlist-grid',
			array(
				'title'       => __( 'Customer Wishlist Grid', 'atelier-companion' ),
				'description' => __( 'Displays the customer saved wishlist items with quick Add to Cart buttons.', 'atelier-companion' ),
				'categories'  => array( 'atelier-ecommerce' ),
				'content'     => '<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html__( 'Saved Items', 'atelier-companion' ) . '</h1><!-- /wp:heading --><!-- wp:shortcode -->[atelier_wishlist]<!-- /wp:shortcode --></div><!-- /wp:group -->',
			)
		);
	}

	/**
	 * Admin notice when WooCommerce is not active.
	 */
	public function woocommerce_missing_notice(): void {
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo esc_html__( 'Atelier Shop Companion requires WooCommerce to be installed and activated for e-commerce features.', 'atelier-companion' );
		echo '</p></div>';
	}
}

Atelier_Shop_Companion::instance();
