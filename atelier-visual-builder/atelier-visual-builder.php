<?php
/**
 * Plugin Name:       Atelier Visual Builder
 * Plugin URI:        https://example.com/atelier-visual-builder
 * Description:       Elementor-style visual drag-and-drop page builder for WordPress & WooCommerce. Features 23+ luxury e-commerce & editorial widgets, dedicated Shop/Cart/Checkout/Product/My Account/Policy builders, live responsive canvas, and 11 starter templates.
 * Version:           1.1.0
 * Author:            Atelier Studio
 * Author URI:        https://example.com
 * Text Domain:       atelier-visual-builder
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   9.5
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AVB_VERSION', '1.1.0' );
define( 'AVB_PATH', plugin_dir_path( __FILE__ ) );
define( 'AVB_URL', plugin_dir_url( __FILE__ ) );

require_once AVB_PATH . 'includes/class-avb-registry.php';
require_once AVB_PATH . 'includes/class-avb-renderer.php';
require_once AVB_PATH . 'includes/class-avb-editor.php';

/**
 * Main Atelier Visual Builder Bootstrap Class.
 */
final class Atelier_Visual_Builder {

	/**
	 * Singleton instance.
	 *
	 * @var Atelier_Visual_Builder|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Atelier_Visual_Builder
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Declare WooCommerce HPOS compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibility' ) );

		// Initialize components.
		AVB_Editor::instance();

		// Frontend rendering hooks.
		add_filter( 'the_content', array( $this, 'filter_frontend_content' ), 20 );
		add_filter( 'render_block', array( $this, 'filter_shop_archive_block' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_filter( 'body_class', array( $this, 'filter_body_classes' ) );

		// Admin hooks for "Edit with Atelier Builder" links & meta box.
		add_filter( 'page_row_actions', array( $this, 'add_row_action_link' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'add_row_action_link' ), 10, 2 );
		add_filter( 'display_post_states', array( $this, 'add_builder_post_state' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'register_builder_metabox' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_button' ), 80 );
	}

	/**
	 * Declare WooCommerce High-Performance Order Storage compatibility.
	 */
	public function declare_wc_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Supported post types for Atelier Visual Builder.
	 *
	 * @return array
	 */
	public static function get_supported_post_types() {
		return array( 'page', 'post', 'product' );
	}

	/**
	 * Check if a post has Atelier Visual Builder enabled.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_builder_enabled( $post_id ) {
		if ( ! $post_id ) {
			return false;
		}
		$enabled = get_post_meta( $post_id, '_avb_enabled', true );
		$data    = get_post_meta( $post_id, '_avb_elements', true );
		return ( '1' === (string) $enabled && is_array( $data ) && ! empty( $data ) );
	}

	/**
	 * Get editor URL for a specific post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_editor_url( $post_id ) {
		return add_query_arg(
			array(
				'page'    => 'atelier-builder',
				'post_id' => absint( $post_id ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Resolve the current post ID (including the WooCommerce Shop page on shop archives).
	 *
	 * @return int
	 */
	public static function get_active_post_id() {
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
			return (int) wc_get_page_id( 'shop' );
		}
		if ( is_singular() ) {
			return (int) get_the_ID();
		}
		return 0;
	}

	/**
	 * Render builder elements on the frontend when enabled for a page/post/product.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_frontend_content( $content ) {
		if ( is_admin() || ! is_singular() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! self::is_builder_enabled( $post_id ) ) {
			return $content;
		}

		$elements = get_post_meta( $post_id, '_avb_elements', true );
		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return $content;
		}

		return AVB_Renderer::render_page( $elements, $post_id );
	}

	/**
	 * Render custom Atelier Builder Shop page on Block Theme shop archive when enabled.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data array.
	 * @return string
	 */
	public function filter_shop_archive_block( $block_content, $block ) {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! is_shop() ) {
			return $block_content;
		}
		if ( empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) {
			return $block_content;
		}
		if ( empty( $block['attrs']['tagName'] ) || 'main' !== $block['attrs']['tagName'] ) {
			return $block_content;
		}

		$shop_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
		if ( ! $shop_id || ! self::is_builder_enabled( $shop_id ) ) {
			return $block_content;
		}

		$elements = get_post_meta( $shop_id, '_avb_elements', true );
		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return $block_content;
		}

		return '<main class="wp-block-group avb-shop-main">' . AVB_Renderer::render_page( $elements, $shop_id ) . '</main>';
	}

	/**
	 * Enqueue frontend CSS and JS for builder pages.
	 */
	public function enqueue_frontend_assets() {
		wp_register_style(
			'avb-frontend',
			AVB_URL . 'assets/css/frontend.css',
			array(),
			AVB_VERSION
		);

		wp_register_script(
			'avb-frontend',
			AVB_URL . 'assets/js/frontend.js',
			array(),
			AVB_VERSION,
			true
		);

		$post_id = self::get_active_post_id();
		if ( $post_id && self::is_builder_enabled( $post_id ) ) {
			wp_enqueue_style( 'avb-frontend' );
			wp_enqueue_script( 'avb-frontend' );

			$page_settings = get_post_meta( $post_id, '_avb_page_settings', true );
			if ( is_array( $page_settings ) && ! empty( $page_settings['bg_color'] ) ) {
				$bg = sanitize_hex_color( $page_settings['bg_color'] );
				if ( $bg ) {
					wp_add_inline_style( 'avb-frontend', 'body.avb-page-active { background-color: ' . esc_attr( $bg ) . ' !important; }' );
				}
			}
		}
	}

	/**
	 * Add page layout classes to body when Atelier Builder is active.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function filter_body_classes( $classes ) {
		$post_id = self::get_active_post_id();
		if ( $post_id && self::is_builder_enabled( $post_id ) ) {
			$classes[]     = 'avb-page-active';
			$page_settings = get_post_meta( $post_id, '_avb_page_settings', true );
			$layout        = isset( $page_settings['layout'] ) ? $page_settings['layout'] : 'full-width';
			$classes[]     = 'avb-layout-' . sanitize_html_class( $layout );
		}
		return $classes;
	}

	/**
	 * Add "Edit with Atelier Builder" link in Pages/Posts/Products list table.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array
	 */
	public function add_row_action_link( $actions, $post ) {
		if ( ! in_array( $post->post_type, self::get_supported_post_types(), true ) ) {
			return $actions;
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = self::get_editor_url( $post->ID );
		$actions['atelier_builder'] = sprintf(
			'<a href="%s" style="color:#b47b56;font-weight:600;">%s</a>',
			esc_url( $url ),
			esc_html__( 'Edit with Atelier Builder', 'atelier-visual-builder' )
		);

		return $actions;
	}

	/**
	 * Show "Atelier Builder" badge next to page titles in WP Admin.
	 *
	 * @param array   $post_states Post states.
	 * @param WP_Post $post        Post object.
	 * @return array
	 */
	public function add_builder_post_state( $post_states, $post ) {
		if ( self::is_builder_enabled( $post->ID ) ) {
			$post_states['atelier_builder'] = __( 'Atelier Builder', 'atelier-visual-builder' );
		}
		return $post_states;
	}

	/**
	 * Register meta box inside the standard WordPress Post/Page/Product Editor.
	 */
	public function register_builder_metabox() {
		add_meta_box(
			'avb_launcher_metabox',
			__( 'Atelier Visual Builder', 'atelier-visual-builder' ),
			array( $this, 'render_launcher_metabox' ),
			self::get_supported_post_types(),
			'side',
			'high'
		);
	}

	/**
	 * Render launcher meta box in the standard editor.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_launcher_metabox( $post ) {
		$url     = self::get_editor_url( $post->ID );
		$enabled = self::is_builder_enabled( $post->ID );
		?>
		<div style="padding:8px 0;">
			<p style="margin:0 0 12px;color:#555;font-size:13px;">
				<?php esc_html_e( 'Design this page visually with drag-and-drop sections, WooCommerce Shop/Cart/Checkout/Account widgets, and live responsive controls.', 'atelier-visual-builder' ); ?>
			</p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary button-large" style="width:100%;text-align:center;background:#161514;border-color:#161514;color:#fff;font-weight:600;padding:6px 12px;display:inline-block;box-sizing:border-box;">
				&#9998; <?php esc_html_e( 'Edit with Atelier Builder', 'atelier-visual-builder' ); ?>
			</a>
			<?php if ( $enabled ) : ?>
				<p style="margin:10px 0 0;font-size:12px;color:#2e7d32;font-weight:600;">
					&#10003; <?php esc_html_e( 'Atelier Visual Builder is active on this page.', 'atelier-visual-builder' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add "Edit with Atelier Builder" button to the top WP Admin Bar on the frontend.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_admin_bar_button( $wp_admin_bar ) {
		if ( is_admin() ) {
			return;
		}

		$post_id = 0;
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
			$post_id = wc_get_page_id( 'shop' );
		} elseif ( is_singular( self::get_supported_post_types() ) ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id || $post_id < 1 || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'atelier-visual-builder',
				'title' => '&#9998; ' . esc_html__( 'Edit with Atelier Builder', 'atelier-visual-builder' ),
				'href'  => self::get_editor_url( $post_id ),
				'meta'  => array(
					'class' => 'avb-admin-bar-link',
				),
			)
		);
	}
}

Atelier_Visual_Builder::instance();
