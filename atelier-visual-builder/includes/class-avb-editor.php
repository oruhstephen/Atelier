<?php
/**
 * Atelier Visual Builder - Studio Editor & AJAX Controller.
 *
 * @package Atelier_Visual_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin menu, full-screen visual builder UI, and AJAX save/render endpoints.
 */
class AVB_Editor {

	/**
	 * Singleton instance.
	 *
	 * @var AVB_Editor|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AVB_Editor
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
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_avb_render_element', array( $this, 'ajax_render_element' ) );
		add_action( 'wp_ajax_avb_save_page', array( $this, 'ajax_save_page' ) );
		add_action( 'wp_ajax_avb_create_page', array( $this, 'ajax_create_page' ) );
	}

	/**
	 * Register top-level "Atelier Builder" menu in WP Admin.
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Atelier Visual Builder', 'atelier-visual-builder' ),
			__( 'Atelier Builder', 'atelier-visual-builder' ),
			'edit_pages',
			'atelier-builder',
			array( $this, 'render_admin_router' ),
			'dashicons-layout',
			58
		);
	}

	/**
	 * Enqueue editor CSS, JS, and WP Media Library on the builder screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_editor_assets( $hook ) {
		if ( 'toplevel_page_atelier-builder' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'avb-frontend',
			AVB_URL . 'assets/css/frontend.css',
			array(),
			AVB_VERSION
		);

		wp_enqueue_style(
			'avb-editor',
			AVB_URL . 'assets/css/editor.css',
			array( 'avb-frontend' ),
			AVB_VERSION
		);

		wp_enqueue_script(
			'avb-frontend',
			AVB_URL . 'assets/js/frontend.js',
			array(),
			AVB_VERSION,
			true
		);

		wp_enqueue_script(
			'avb-editor',
			AVB_URL . 'assets/js/editor.js',
			array( 'jquery', 'avb-frontend' ),
			AVB_VERSION,
			true
		);

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = $post_id ? get_post( $post_id ) : null;

		$saved_elements = array();
		$page_settings  = array(
			'layout'   => 'full-width',
			'bg_color' => '#fbf9f6',
			'wc_role'  => '',
		);

		if ( $post ) {
			$meta_elements = get_post_meta( $post_id, '_avb_elements', true );
			if ( is_array( $meta_elements ) ) {
				$saved_elements = $meta_elements;
			}
			$meta_settings = get_post_meta( $post_id, '_avb_page_settings', true );
			if ( is_array( $meta_settings ) ) {
				$page_settings = array_merge( $page_settings, $meta_settings );
			}
		}

		wp_localize_script(
			'avb-editor',
			'AVB_STUDIO',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'avb_studio_nonce' ),
				'postId'       => $post ? $post->ID : 0,
				'postTitle'    => $post ? $post->post_title : '',
				'postStatus'   => $post ? $post->post_status : 'draft',
				'permalink'    => $post ? get_permalink( $post->ID ) : home_url( '/' ),
				'exitUrl'      => admin_url( 'admin.php?page=atelier-builder' ),
				'categories'   => AVB_Registry::get_categories(),
				'widgets'      => AVB_Registry::get_widgets(),
				'templates'    => AVB_Registry::get_templates(),
				'defaultStyle' => AVB_Registry::get_default_style(),
				'defaultAdv'   => AVB_Registry::get_default_advanced(),
				'elements'     => $saved_elements,
				'pageSettings' => $page_settings,
			)
		);
	}

	/**
	 * Route between the Studio Hub (no post_id) and the Full-Screen Visual Builder (with post_id).
	 */
	public function render_admin_router() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id && get_post( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$this->render_visual_builder( get_post( $post_id ) );
		} else {
			$this->render_studio_hub();
		}
	}

	/**
	 * Render the Atelier Builder Studio Hub dashboard when no post_id is passed.
	 */
	private function render_studio_hub() {
		$templates = AVB_Registry::get_templates();
		$pages     = get_posts(
			array(
				'post_type'      => array( 'page', 'post', 'product' ),
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 30,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$wc_quick_pages = array(
			array(
				'role'     => 'shop',
				'icon'     => '🏪',
				'label'    => __( 'SHOP / CATALOG', 'atelier-visual-builder' ),
				'title'    => __( 'Shop', 'atelier-visual-builder' ),
				'template' => 'wc-shop-page',
				'desc'     => __( 'Category Bento + Filterable Catalog Grid', 'atelier-visual-builder' ),
			),
			array(
				'role'     => 'cart',
				'icon'     => '🧺',
				'label'    => __( 'CART / BASKET', 'atelier-visual-builder' ),
				'title'    => __( 'Shopping Bag', 'atelier-visual-builder' ),
				'template' => 'wc-cart-page',
				'desc'     => __( 'Free Shipping Goal Bar + 2-Col Bag & Cross-Sells', 'atelier-visual-builder' ),
			),
			array(
				'role'     => 'checkout',
				'icon'     => '💳',
				'label'    => __( 'CHECKOUT', 'atelier-visual-builder' ),
				'title'    => __( 'Checkout', 'atelier-visual-builder' ),
				'template' => 'wc-checkout-page',
				'desc'     => __( '3-Step Checkout Suite + Concierge & FAQ', 'atelier-visual-builder' ),
			),
			array(
				'role'     => 'product',
				'icon'     => '🏺',
				'label'    => __( 'PRODUCT SHOWCASE', 'atelier-visual-builder' ),
				'title'    => __( 'Flagship Product Showcase', 'atelier-visual-builder' ),
				'template' => 'wc-product-page',
				'desc'     => __( '2-Col Gallery & Buy Box + Story + Reviews', 'atelier-visual-builder' ),
			),
			array(
				'role'     => 'myaccount',
				'icon'     => '👤',
				'label'    => __( 'MY ACCOUNT', 'atelier-visual-builder' ),
				'title'    => __( 'My Collector Account', 'atelier-visual-builder' ),
				'template' => 'wc-myaccount-page',
				'desc'     => __( 'Client Portal + Order History + Shipment Tracker', 'atelier-visual-builder' ),
			),
			array(
				'role'     => 'policy',
				'icon'     => '📜',
				'label'    => __( 'POLICY & RETURNS', 'atelier-visual-builder' ),
				'title'    => __( 'Shipping, Returns & Privacy Policy', 'atelier-visual-builder' ),
				'template' => 'wc-policy-page',
				'desc'     => __( 'Sticky TOC Sidebar + Clauses + Order Tracker', 'atelier-visual-builder' ),
			),
		);
		?>
		<div class="avb-hub-wrap">
			<header class="avb-hub-header">
				<div>
					<span class="avb-hub-badge">ATELIER VISUAL STUDIO v<?php echo esc_html( AVB_VERSION ); ?></span>
					<h1><?php esc_html_e( 'Atelier Visual Drag & Drop Builder', 'atelier-visual-builder' ); ?></h1>
					<p><?php esc_html_e( 'Visually customize your Shop, Cart/Basket, Checkout, Product, My Account, Policy, and Landing Pages.', 'atelier-visual-builder' ); ?></p>
				</div>
				<div class="avb-hub-create-box">
					<input type="text" id="avb-new-page-title" placeholder="<?php esc_attr_e( 'New Page Title (e.g. Autumn Lookbook)...', 'atelier-visual-builder' ); ?>" />
					<select id="avb-new-page-template">
						<option value=""><?php esc_html_e( 'Start with Blank Canvas', 'atelier-visual-builder' ); ?></option>
						<?php foreach ( $templates as $tpl_id => $tpl ) : ?>
							<option value="<?php echo esc_attr( $tpl_id ); ?>">
								<?php echo esc_html( '[' . $tpl['badge'] . '] ' . $tpl['title'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" id="avb-create-page-btn" class="avb-hub-btn-primary">
						+ <?php esc_html_e( 'Create & Launch Builder', 'atelier-visual-builder' ); ?>
					</button>
				</div>
			</header>

			<section class="avb-hub-section">
				<h2>&#128717; <?php esc_html_e( '1-Click WooCommerce Core Pages (Shop, Cart, Checkout, Product, Account, Policy)', 'atelier-visual-builder' ); ?></h2>
				<div class="avb-hub-templates-grid">
					<?php foreach ( $wc_quick_pages as $qp ) : ?>
						<div class="avb-hub-tpl-card">
							<div class="avb-hub-tpl-top">
								<span class="avb-hub-tpl-cat"><?php echo esc_html( $qp['icon'] . ' ' . $qp['label'] ); ?></span>
								<span class="avb-hub-tpl-badge"><?php esc_html_e( 'CORE STORE PAGE', 'atelier-visual-builder' ); ?></span>
							</div>
							<h3><?php echo esc_html( $qp['title'] ); ?></h3>
							<p><?php echo esc_html( $qp['desc'] ); ?></p>
							<button type="button" class="avb-hub-use-tpl-btn" data-template="<?php echo esc_attr( $qp['template'] ); ?>" data-title="<?php echo esc_attr( $qp['title'] ); ?>" data-wc-role="<?php echo esc_attr( $qp['role'] ); ?>">
								&#9998; <?php esc_html_e( 'Build / Customize This Store Page →', 'atelier-visual-builder' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="avb-hub-section">
				<h2>&#10024; <?php esc_html_e( 'All 11 Starter Page Templates', 'atelier-visual-builder' ); ?></h2>
				<div class="avb-hub-templates-grid">
					<?php foreach ( $templates as $tpl_id => $tpl ) : ?>
						<div class="avb-hub-tpl-card">
							<div class="avb-hub-tpl-top">
								<span class="avb-hub-tpl-cat"><?php echo esc_html( $tpl['category'] ); ?></span>
								<span class="avb-hub-tpl-badge"><?php echo esc_html( $tpl['badge'] ); ?></span>
							</div>
							<h3><?php echo esc_html( $tpl['title'] ); ?></h3>
							<p><?php echo esc_html( $tpl['description'] ); ?></p>
							<button type="button" class="avb-hub-use-tpl-btn" data-template="<?php echo esc_attr( $tpl_id ); ?>" data-title="<?php echo esc_attr( $tpl['title'] ); ?>">
								<?php esc_html_e( 'Create Page with This Template →', 'atelier-visual-builder' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="avb-hub-section">
				<h2><?php esc_html_e( 'Your Pages, Products & Posts', 'atelier-visual-builder' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'atelier-visual-builder' ); ?></th>
							<th><?php esc_html_e( 'Type', 'atelier-visual-builder' ); ?></th>
							<th><?php esc_html_e( 'Builder Status', 'atelier-visual-builder' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'atelier-visual-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $pages ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No pages found yet. Use the form above to create your first visual page.', 'atelier-visual-builder' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $pages as $p ) :
								$is_active  = Atelier_Visual_Builder::is_builder_enabled( $p->ID );
								$editor_url = Atelier_Visual_Builder::get_editor_url( $p->ID );
								?>
								<tr>
									<td><strong><a href="<?php echo esc_url( $editor_url ); ?>"><?php echo esc_html( $p->post_title ?: '(Untitled)' ); ?></a></strong></td>
									<td><?php echo esc_html( ucfirst( $p->post_type ) . ' (' . $p->post_status . ')' ); ?></td>
									<td>
										<?php if ( $is_active ) : ?>
											<span class="avb-status-pill is-active">&#10003; <?php esc_html_e( 'Atelier Builder Active', 'atelier-visual-builder' ); ?></span>
										<?php else : ?>
											<span class="avb-status-pill"><?php esc_html_e( 'Standard Editor', 'atelier-visual-builder' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( $editor_url ); ?>" class="button button-primary"><?php esc_html_e( 'Edit with Atelier Builder', 'atelier-visual-builder' ); ?></a>
										<a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" target="_blank" class="button"><?php esc_html_e( 'View', 'atelier-visual-builder' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</section>
		</div>
		<?php
	}

	/**
	 * Render the Full-Screen Elementor-Style Visual Builder Application.
	 *
	 * @param WP_Post $post Active post object.
	 */
	private function render_visual_builder( $post ) {
		?>
		<div id="avb-studio-app" class="avb-studio-app">
			<!-- Top Studio Bar -->
			<header class="avb-topbar">
				<div class="avb-topbar-left">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=atelier-builder' ) ); ?>" class="avb-exit-btn" title="<?php esc_attr_e( 'Back to Atelier Studio Hub', 'atelier-visual-builder' ); ?>">
						&#8592;
					</a>
					<div class="avb-brand">
						<span class="avb-brand-dot"></span>
						<strong>ATELIER BUILDER</strong>
					</div>
					<input type="text" id="avb-page-title-input" class="avb-page-title-input" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="<?php esc_attr_e( 'Page Title...', 'atelier-visual-builder' ); ?>" />
					<select id="avb-post-status-select" class="avb-status-select">
						<option value="publish" <?php selected( $post->post_status, 'publish' ); ?>><?php esc_html_e( 'Published', 'atelier-visual-builder' ); ?></option>
						<option value="draft" <?php selected( $post->post_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'atelier-visual-builder' ); ?></option>
					</select>
				</div>

				<div class="avb-topbar-center">
					<div class="avb-viewport-Group" role="group" aria-label="<?php esc_attr_e( 'Responsive Viewport', 'atelier-visual-builder' ); ?>">
						<button type="button" class="avb-vp-btn is-active" data-viewport="desktop" title="<?php esc_attr_e( 'Desktop View (100%)', 'atelier-visual-builder' ); ?>">🖥️ Desktop</button>
						<button type="button" class="avb-vp-btn" data-viewport="tablet" title="<?php esc_attr_e( 'Tablet View (768px)', 'atelier-visual-builder' ); ?>">📱 Tablet</button>
						<button type="button" class="avb-vp-btn" data-viewport="mobile" title="<?php esc_attr_e( 'Mobile View (375px)', 'atelier-visual-builder' ); ?>">📲 Mobile</button>
					</div>
					<div class="avb-history-group">
						<button type="button" id="avb-undo-btn" class="avb-icon-btn" title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'atelier-visual-builder' ); ?>" disabled>&#8630;</button>
						<button type="button" id="avb-redo-btn" class="avb-icon-btn" title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'atelier-visual-builder' ); ?>" disabled>&#8631;</button>
					</div>
					<button type="button" id="avb-open-templates-btn" class="avb-top-action-btn">
						&#10024; <?php esc_html_e( 'Templates (11)', 'atelier-visual-builder' ); ?>
					</button>
					<button type="button" id="avb-page-settings-btn" class="avb-top-action-btn">
						&#9881; <?php esc_html_e( 'Page Settings', 'atelier-visual-builder' ); ?>
					</button>
				</div>

				<div class="avb-topbar-right">
					<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" id="avb-preview-link" class="avb-preview-btn">
						&#128065; <?php esc_html_e( 'View Page', 'atelier-visual-builder' ); ?>
					</a>
					<button type="button" id="avb-save-btn" class="avb-save-btn">
						<?php esc_html_e( 'Save & Publish', 'atelier-visual-builder' ); ?>
					</button>
				</div>
			</header>

			<!-- Main 3-Pane Workspace -->
			<div class="avb-workspace">
				<!-- Left Drawer: Widgets & Structure Navigator -->
				<aside class="avb-left-panel">
					<div class="avb-left-tabs">
						<button type="button" class="avb-left-tab is-active" data-left-tab="widgets">
							&#10010; <?php esc_html_e( 'Elements (23)', 'atelier-visual-builder' ); ?>
						</button>
						<button type="button" class="avb-left-tab" data-left-tab="layers">
							&#9776; <?php esc_html_e( 'Navigator', 'atelier-visual-builder' ); ?>
						</button>
					</div>

					<!-- Elements Palette -->
					<div class="avb-left-pane is-active" id="avb-left-widgets">
						<div class="avb-search-wrap">
							<input type="search" id="avb-widget-search" placeholder="<?php esc_attr_e( 'Search Shop, Cart, Checkout, Policy...', 'atelier-visual-builder' ); ?>" />
						</div>
						<div id="avb-widget-catalog" class="avb-widget-catalog"></div>
					</div>

					<!-- Navigator / Layers Tree -->
					<div class="avb-left-pane" id="avb-left-layers">
						<div class="avb-layers-header">
							<span><?php esc_html_e( 'Page Sections', 'atelier-visual-builder' ); ?></span>
							<button type="button" id="avb-clear-canvas-btn" class="avb-text-danger-btn"><?php esc_html_e( 'Clear All', 'atelier-visual-builder' ); ?></button>
						</div>
						<ul id="avb-layers-list" class="avb-layers-list"></ul>
					</div>
				</aside>

				<!-- Center Visual Canvas -->
				<main class="avb-canvas-stage" id="avb-canvas-stage">
					<div class="avb-canvas-viewport is-desktop" id="avb-canvas-viewport">
						<div id="avb-canvas-dropzone" class="avb-canvas-dropzone"></div>
						<div id="avb-empty-state" class="avb-empty-state">
							<div class="avb-empty-inner">
								<span class="avb-empty-icon">✦</span>
								<h2><?php esc_html_e( 'Start Building Your Page', 'atelier-visual-builder' ); ?></h2>
								<p><?php esc_html_e( 'Drag any widget from the left panel onto this canvas, click a widget to insert it, or load a complete pre-designed Shop, Cart, Checkout, Product, Account, or Policy template.', 'atelier-visual-builder' ); ?></p>
								<div class="avb-empty-actions">
									<button type="button" class="avb-btn-Empty-tpl" id="avb-empty-open-templates">
										&#10024; <?php esc_html_e( 'Browse 11 Starter Templates', 'atelier-visual-builder' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</main>

				<!-- Right Inspector: Content | Style | Advanced -->
				<aside class="avb-right-panel" id="avb-right-panel">
					<div class="avb-inspector-header" id="avb-inspector-header">
						<span id="avb-inspector-title"><?php esc_html_e( 'Select an Element', 'atelier-visual-builder' ); ?></span>
					</div>
					<div class="avb-inspector-tabs" id="avb-inspector-tabs">
						<button type="button" class="avb-insp-tab is-active" data-insp-tab="content"><?php esc_html_e( 'Content', 'atelier-visual-builder' ); ?></button>
						<button type="button" class="avb-insp-tab" data-insp-tab="style"><?php esc_html_e( 'Style', 'atelier-visual-builder' ); ?></button>
						<button type="button" class="avb-insp-tab" data-insp-tab="advanced"><?php esc_html_e( 'Advanced', 'atelier-visual-builder' ); ?></button>
					</div>
					<div class="avb-inspector-body" id="avb-inspector-body">
						<div class="avb-inspector-placeholder">
							<p><?php esc_html_e( 'Click any section on the canvas to customize its content, colors, typography, spacing, and animations.', 'atelier-visual-builder' ); ?></p>
						</div>
					</div>
				</aside>
			</div>

			<!-- Template Library Modal -->
			<div id="avb-templates-modal" class="avb-modal" hidden>
				<div class="avb-modal-backdrop"></div>
				<div class="avb-modal-dialog">
					<div class="avb-modal-header">
						<div>
							<h2>&#10024; <?php esc_html_e( 'Atelier Starter Template Library (11 Templates)', 'atelier-visual-builder' ); ?></h2>
							<p><?php esc_html_e( 'Insert a complete pre-built Shop, Cart, Checkout, Product, My Account, Policy, or Editorial layout with a single click.', 'atelier-visual-builder' ); ?></p>
						</div>
						<button type="button" class="avb-modal-close" id="avb-close-templates-btn">&times;</button>
					</div>
					<div class="avb-modal-body" id="avb-modal-templates-list"></div>
				</div>
			</div>

			<!-- Toast Notification -->
			<div id="avb-toast" class="avb-toast" hidden></div>
		</div>
		<?php
	}

	/**
	 * Assign a post ID to a WooCommerce or WordPress core page role if requested.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $role    Role key (shop, cart, checkout, myaccount, policy).
	 */
	private function assign_wc_page_role( $post_id, $role ) {
		$role = sanitize_key( $role );
		if ( ! $post_id || empty( $role ) ) {
			return;
		}
		if ( 'shop' === $role ) {
			update_option( 'woocommerce_shop_page_id', $post_id );
		} elseif ( 'cart' === $role ) {
			update_option( 'woocommerce_cart_page_id', $post_id );
		} elseif ( 'checkout' === $role ) {
			update_option( 'woocommerce_checkout_page_id', $post_id );
		} elseif ( 'myaccount' === $role ) {
			update_option( 'woocommerce_myaccount_page_id', $post_id );
		} elseif ( 'policy' === $role ) {
			update_option( 'wp_page_for_privacy_policy', $post_id );
			update_option( 'woocommerce_terms_page_id', $post_id );
		}
	}

	/**
	 * AJAX: Render a single element or array of elements and return HTML.
	 */
	public function ajax_render_element() {
		check_ajax_referer( 'avb_studio_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$raw_element = isset( $_POST['element'] ) ? wp_unslash( $_POST['element'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$element     = is_string( $raw_element ) ? json_decode( $raw_element, true ) : $raw_element;

		if ( ! is_array( $element ) ) {
			wp_send_json_error( array( 'message' => 'Invalid element data.' ) );
		}

		$html = AVB_Renderer::render_element( $element, true );
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Save page builder elements, page settings, post status, and compiled fallback HTML.
	 */
	public function ajax_save_page() {
		check_ajax_referer( 'avb_studio_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$raw_elements = isset( $_POST['elements'] ) ? wp_unslash( $_POST['elements'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$elements     = json_decode( $raw_elements, true );
		if ( ! is_array( $elements ) ) {
			$elements = array();
		}

		$raw_settings  = isset( $_POST['page_settings'] ) ? wp_unslash( $_POST['page_settings'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$page_settings = json_decode( $raw_settings, true );
		if ( ! is_array( $page_settings ) ) {
			$page_settings = array();
		}

		$post_title  = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
		$post_status = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'publish';
		if ( ! in_array( $post_status, array( 'publish', 'draft', 'private' ), true ) ) {
			$post_status = 'publish';
		}

		update_post_meta( $post_id, '_avb_elements', $elements );
		update_post_meta( $post_id, '_avb_page_settings', $page_settings );
		update_post_meta( $post_id, '_avb_enabled', ! empty( $elements ) ? '1' : '0' );

		if ( ! empty( $page_settings['wc_role'] ) ) {
			$this->assign_wc_page_role( $post_id, $page_settings['wc_role'] );
		}

		// Compile static HTML into post_content for SEO and standard search indexing.
		$compiled_html = AVB_Renderer::render_page( $elements, $post_id, false );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $post_title ?: get_the_title( $post_id ),
				'post_status'  => $post_status,
				'post_content' => $compiled_html,
			)
		);

		wp_send_json_success(
			array(
				'message'   => __( 'Page saved and published!', 'atelier-visual-builder' ),
				'permalink' => get_permalink( $post_id ),
			)
		);
	}

	/**
	 * AJAX: Create or open a WooCommerce page (optionally from a starter template) and return its builder URL.
	 */
	public function ajax_create_page() {
		check_ajax_referer( 'avb_studio_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_pages' ) && ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$template_id = isset( $_POST['template_id'] ) ? sanitize_key( wp_unslash( $_POST['template_id'] ) ) : '';
		$wc_role     = isset( $_POST['wc_role'] ) ? sanitize_key( wp_unslash( $_POST['wc_role'] ) ) : '';

		if ( empty( $title ) ) {
			$title = __( 'Atelier Custom Page', 'atelier-visual-builder' );
		}

		$post_id = 0;

		// Check if an existing WooCommerce page is already assigned for this role.
		if ( ! empty( $wc_role ) && function_exists( 'wc_get_page_id' ) && in_array( $wc_role, array( 'shop', 'cart', 'checkout', 'myaccount' ), true ) ) {
			$existing_id = wc_get_page_id( $wc_role );
			if ( $existing_id > 0 && get_post( $existing_id ) ) {
				$post_id = $existing_id;
			}
		} elseif ( 'policy' === $wc_role ) {
			$existing_policy = (int) get_option( 'wp_page_for_privacy_policy' );
			if ( $existing_policy > 0 && get_post( $existing_policy ) ) {
				$post_id = $existing_policy;
			}
		}

		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Could not create page.' ) );
		}

		$templates = AVB_Registry::get_templates();
		if ( $template_id && isset( $templates[ $template_id ] ) ) {
			$existing_elements = get_post_meta( $post_id, '_avb_elements', true );
			if ( empty( $existing_elements ) || ! is_array( $existing_elements ) ) {
				$elements = $templates[ $template_id ]['elements'];
				update_post_meta( $post_id, '_avb_elements', $elements );
				update_post_meta( $post_id, '_avb_enabled', '1' );
			}
		}

		if ( ! empty( $wc_role ) ) {
			$this->assign_wc_page_role( $post_id, $wc_role );
			update_post_meta(
				$post_id,
				'_avb_page_settings',
				array(
					'layout'   => 'full-width',
					'bg_color' => '#fbf9f6',
					'wc_role'  => $wc_role,
				)
			);
		}

		wp_send_json_success(
			array(
				'editorUrl' => Atelier_Visual_Builder::get_editor_url( $post_id ),
			)
		);
	}
}
