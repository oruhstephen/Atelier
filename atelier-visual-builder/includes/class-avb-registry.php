<?php
/**
 * Atelier Visual Builder - Widget & Template Registry.
 *
 * @package Atelier_Visual_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides widget schemas, default settings, and 1-click starter templates.
 */
class AVB_Registry {

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public static function get_categories() {
		return array(
			'wc_pages'    => array(
				'title' => __( 'WooCommerce Store Pages', 'atelier-visual-builder' ),
				'icon'  => '🏬',
			),
			'woocommerce' => array(
				'title' => __( 'WooCommerce Grids & Merch', 'atelier-visual-builder' ),
				'icon'  => '🛍️',
			),
			'marketing'   => array(
				'title' => __( 'Hero & Conversion', 'atelier-visual-builder' ),
				'icon'  => '✨',
			),
			'basic'       => array(
				'title' => __( 'Editorial, Policy & Media', 'atelier-visual-builder' ),
				'icon'  => '✒️',
			),
			'layout'      => array(
				'title' => __( 'Layout & Structure', 'atelier-visual-builder' ),
				'icon'  => '📐',
			),
		);
	}

	/**
	 * Default Style settings shared across all widgets.
	 *
	 * @return array
	 */
	public static function get_default_style() {
		return array(
			'bg_color'       => '',
			'bg_image'       => '',
			'bg_overlay'     => 0,
			'text_color'     => '',
			'heading_color'  => '',
			'accent_color'   => '#b47b56',
			'text_align'     => 'left',
			'typo_scale'     => 'normal',
			'border_radius'  => 0,
			'border_style'   => 'none',
			'border_color'   => '#e6e1da',
			'box_shadow'     => 'none',
		);
	}

	/**
	 * Default Advanced settings shared across all widgets.
	 *
	 * @return array
	 */
	public static function get_default_advanced() {
		return array(
			'container_width' => 'wide',
			'padding_top'     => 64,
			'padding_bottom'  => 64,
			'padding_left'    => 24,
			'padding_right'   => 24,
			'margin_top'      => 0,
			'margin_bottom'   => 0,
			'animation'       => 'none',
			'hide_desktop'    => false,
			'hide_tablet'     => false,
			'hide_mobile'     => false,
			'custom_class'    => '',
			'custom_id'       => '',
		);
	}

	/**
	 * Get all registered widgets with their Content field schemas and defaults.
	 *
	 * @return array
	 */
	public static function get_widgets() {
		return array(
			/* =============================================================
			   NEW: Dedicated WooCommerce Store Page Widgets (SHOP, CART, CHECKOUT, PRODUCT, MY ACCOUNT, POLICY)
			   ============================================================= */

			'wc-shop-catalog' => array(
				'type'        => 'wc-shop-catalog',
				'title'       => __( 'Full Shop Catalog + Filters', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '🏪',
				'description' => __( 'Complete Shop page catalog with live category filter pills, sorting toolbar, product grid, and pagination.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'           => 'ATELIER STOREFRONT',
						'title'             => 'All Studio Objects',
						'subtitle'          => 'Filter by department or sort our complete collection of small-batch home objects.',
						'show_filter_pills' => true,
						'show_sort_bar'     => true,
						'show_result_count' => true,
						'columns'           => 4,
						'per_page'          => 8,
						'default_orderby'   => 'date',
						'btn_label'         => 'Add to Bag',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Catalog Heading', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Catalog Subtitle', 'type' => 'textarea' ),
					array( 'key' => 'show_filter_pills', 'label' => 'Show Category Filter Bar', 'type' => 'toggle' ),
					array( 'key' => 'show_sort_bar', 'label' => 'Show Sort Dropdown', 'type' => 'toggle' ),
					array( 'key' => 'show_result_count', 'label' => 'Show Product Result Counter', 'type' => 'toggle' ),
					array( 'key' => 'columns', 'label' => 'Grid Columns (2-4)', 'type' => 'number', 'min' => 2, 'max' => 4 ),
					array( 'key' => 'per_page', 'label' => 'Products Per Page (4-24)', 'type' => 'number', 'min' => 4, 'max' => 24 ),
					array(
						'key'     => 'default_orderby',
						'label'   => 'Default Sort Order',
						'type'    => 'select',
						'options' => array(
							'date'       => 'Newest Arrivals',
							'popularity' => 'Best Selling',
							'price'      => 'Price: Low to High',
							'price-desc' => 'Price: High to Low',
						),
					),
					array( 'key' => 'btn_label', 'label' => 'Add to Cart Button Label', 'type' => 'text' ),
				),
			),

			'wc-cart-basket' => array(
				'type'        => 'wc-cart-basket',
				'title'       => __( 'Cart / Basket Experience', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '🧺',
				'description' => __( 'Complete WooCommerce Shopping Bag with Free Shipping goal bar, 2-column order summary, coupon box, and trust badges.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'            => 'YOUR SHOPPING BAG',
						'title'              => 'Review Your Selection',
						'show_shipping_bar'  => true,
						'shipping_goal'      => 200,
						'shipping_msg'       => 'You are {amount} away from complimentary express courier delivery.',
						'shipping_unlocked'  => '✓ You have unlocked complimentary express courier delivery!',
						'show_coupon'        => true,
						'show_trust_badges'  => true,
						'trust_text'         => 'Complimentary 30-Day Returns • Plastic-Free Crating • Encrypted Checkout',
						'checkout_btn_label' => 'Proceed to Checkout →',
						'continue_url'       => '/shop',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Cart Heading', 'type' => 'text' ),
					array( 'key' => 'show_shipping_bar', 'label' => 'Show Free Shipping Progress Bar', 'type' => 'toggle' ),
					array( 'key' => 'shipping_goal', 'label' => 'Free Shipping Goal Amount ($)', 'type' => 'number', 'min' => 10, 'max' => 2000 ),
					array( 'key' => 'shipping_msg', 'label' => 'Progress Message ({amount} placeholder)', 'type' => 'text' ),
					array( 'key' => 'shipping_unlocked', 'label' => 'Goal Unlocked Message', 'type' => 'text' ),
					array( 'key' => 'show_coupon', 'label' => 'Show Promo / Coupon Code Input', 'type' => 'toggle' ),
					array( 'key' => 'show_trust_badges', 'label' => 'Show Security & Returns Badge Strip', 'type' => 'toggle' ),
					array( 'key' => 'trust_text', 'label' => 'Trust Guarantee Text', 'type' => 'text' ),
					array( 'key' => 'checkout_btn_label', 'label' => 'Checkout Button Text', 'type' => 'text' ),
					array( 'key' => 'continue_url', 'label' => 'Continue Shopping URL', 'type' => 'text' ),
				),
			),

			'wc-checkout-suite' => array(
				'type'        => 'wc-checkout-suite',
				'title'       => __( 'Luxury Checkout Suite', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '💳',
				'description' => __( 'Full WooCommerce Checkout with 3-step progress bar, billing/shipping form, order summary, and concierge sidebar.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'show_steps'        => true,
						'step_1_label'      => '01. Shopping Bag',
						'step_2_label'      => '02. Delivery & Payment',
						'step_3_label'      => '03. Order Confirmation',
						'title'             => 'Client & Delivery Details',
						'subtitle'          => 'All transactions are 256-bit SSL encrypted. Duties and taxes are calculated automatically.',
						'show_concierge'    => true,
						'concierge_title'   => 'Atelier Client Concierge',
						'concierge_text'    => 'Need assistance with white-glove delivery, trade tax exemption, or bespoke gift notes? Contact our studio team.',
						'concierge_contact' => 'concierge@atelier-studio.com • +1 (800) 555-0194',
						'guarantee_1'       => '30-Day Home Trial & Free Courier Returns',
						'guarantee_2'       => 'Insured Carbon-Neutral Dispatch Within 48h',
						'guarantee_3'       => '2-Year Structural Craftsmanship Warranty',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'show_steps', 'label' => 'Show 3-Step Checkout Progress Header', 'type' => 'toggle' ),
					array( 'key' => 'step_1_label', 'label' => 'Step 1 Label', 'type' => 'text' ),
					array( 'key' => 'step_2_label', 'label' => 'Step 2 Label (Active)', 'type' => 'text' ),
					array( 'key' => 'step_3_label', 'label' => 'Step 3 Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Checkout Title', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Security Subtitle', 'type' => 'textarea' ),
					array( 'key' => 'show_concierge', 'label' => 'Show Client Concierge & Guarantees Bar', 'type' => 'toggle' ),
					array( 'key' => 'concierge_title', 'label' => 'Concierge Box Heading', 'type' => 'text' ),
					array( 'key' => 'concierge_text', 'label' => 'Concierge Description', 'type' => 'textarea' ),
					array( 'key' => 'concierge_contact', 'label' => 'Concierge Phone / Email', 'type' => 'text' ),
					array( 'key' => 'guarantee_1', 'label' => 'Guarantee Point #1', 'type' => 'text' ),
					array( 'key' => 'guarantee_2', 'label' => 'Guarantee Point #2', 'type' => 'text' ),
					array( 'key' => 'guarantee_3', 'label' => 'Guarantee Point #3', 'type' => 'text' ),
				),
			),

			'wc-single-product-spotlight' => array(
				'type'        => 'wc-single-product-spotlight',
				'title'       => __( 'Single Product Page Spotlight', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '🏺',
				'description' => __( 'Complete 2-column Single Product showcase with gallery thumbnails, swatches, quantity stepper, Add-to-Cart, and specs accordion.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'product_id'     => '',
						'eyebrow'        => 'HAND-THROWN STUDIO EDITION',
						'fallback_title' => 'Kyoto Travertine & Stoneware Vessel',
						'fallback_price' => '$245.00',
						'fallback_desc'  => 'Wheel-thrown in small batches from coarse iron-rich stoneware clay and finished with a tactile raw mineral glaze. Each vessel exhibits subtle variations in tone.',
						'fallback_img_1' => 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=1000&q=80',
						'fallback_img_2' => 'https://images.unsplash.com/photo-1615529182904-14819c35db37?auto=format&fit=crop&w=800&q=80',
						'fallback_img_3' => 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=800&q=80',
						'swatches_label' => 'Finish / Glaze: Warm Limestone, Raw Umber, Kiln Ash',
						'btn_text'       => 'Add to Bag',
						'delivery_note'  => 'In stock — Dispatches within 24–48 hours with complimentary insured shipping.',
						'spec_1_title'   => 'Dimensions & Weight',
						'spec_1_body'    => 'Height: 32 cm • Diameter: 21 cm • Weight: 2.4 kg. Watertight interior glaze.',
						'spec_2_title'   => 'Materials & Care',
						'spec_2_body'    => '100% natural high-fired stoneware. Wipe exterior with a soft damp cloth.',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'product_id', 'label' => 'WooCommerce Product ID (blank = current or latest product)', 'type' => 'text' ),
					array( 'key' => 'eyebrow', 'label' => 'Edition / Category Eyebrow', 'type' => 'text' ),
					array( 'key' => 'fallback_title', 'label' => 'Product Title (used if no Product ID)', 'type' => 'text' ),
					array( 'key' => 'fallback_price', 'label' => 'Price Display (used if no Product ID)', 'type' => 'text' ),
					array( 'key' => 'fallback_desc', 'label' => 'Short Description Override', 'type' => 'textarea' ),
					array( 'key' => 'fallback_img_1', 'label' => 'Primary Gallery Image', 'type' => 'image' ),
					array( 'key' => 'fallback_img_2', 'label' => 'Secondary Thumbnail Image', 'type' => 'image' ),
					array( 'key' => 'fallback_img_3', 'label' => 'Third Thumbnail Image', 'type' => 'image' ),
					array( 'key' => 'swatches_label', 'label' => 'Finish / Variant Pills (comma-separated)', 'type' => 'text' ),
					array( 'key' => 'btn_text', 'label' => 'Add to Cart Button Label', 'type' => 'text' ),
					array( 'key' => 'delivery_note', 'label' => 'Shipping & Stock Note', 'type' => 'text' ),
					array( 'key' => 'spec_1_title', 'label' => 'Accordion Tab 1 Title', 'type' => 'text' ),
					array( 'key' => 'spec_1_body', 'label' => 'Accordion Tab 1 Content', 'type' => 'textarea' ),
					array( 'key' => 'spec_2_title', 'label' => 'Accordion Tab 2 Title', 'type' => 'text' ),
					array( 'key' => 'spec_2_body', 'label' => 'Accordion Tab 2 Content', 'type' => 'textarea' ),
				),
			),

			'wc-my-account-portal' => array(
				'type'        => 'wc-my-account-portal',
				'title'       => __( 'My Account & Collector Portal', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '👤',
				'description' => __( 'Branded WooCommerce My Account portal with collector perks banner, navigation tabs, order history, and login/register view.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'      => 'ATELIER CLIENT PORTAL',
						'title'        => 'My Collector Account',
						'subtitle'     => 'Manage your acquisitions, track active shipments, download invoices, and update your delivery preferences.',
						'show_perks'   => true,
						'perk_1'       => '✦ Priority Access to Small-Batch Releases',
						'perk_2'       => '📦 One-Click Courier Tracking & Returns',
						'perk_3'       => '🛡️ Lifetime Care & Restoration Registry',
						'support_note' => 'Need help with an existing order? Email concierge@atelier-studio.com',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Account Portal Heading', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Welcome Subtitle', 'type' => 'textarea' ),
					array( 'key' => 'show_perks', 'label' => 'Show Collector Member Perks Strip', 'type' => 'toggle' ),
					array( 'key' => 'perk_1', 'label' => 'Member Perk #1', 'type' => 'text' ),
					array( 'key' => 'perk_2', 'label' => 'Member Perk #2', 'type' => 'text' ),
					array( 'key' => 'perk_3', 'label' => 'Member Perk #3', 'type' => 'text' ),
					array( 'key' => 'support_note', 'label' => 'Client Support Footer Note', 'type' => 'text' ),
				),
			),

			'wc-order-tracking' => array(
				'type'        => 'wc-order-tracking',
				'title'       => __( 'Order Tracking Lookup', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '🚚',
				'description' => __( 'Live WooCommerce Order Tracking lookup box by Order ID and Billing Email.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'   => 'SHIPMENT DISPATCH TRACKER',
						'title'     => 'Track Your Atelier Order',
						'subtitle'  => 'Enter the Order ID found in your confirmation receipt along with your billing email address to inspect real-time courier status.',
						'btn_label' => 'Track Shipment',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color'   => '#f6f3ee',
							'text_align' => 'center',
						)
					),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Heading', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Instructions Copy', 'type' => 'textarea' ),
					array( 'key' => 'btn_label', 'label' => 'Submit Button Text', 'type' => 'text' ),
				),
			),

			'policy-document' => array(
				'type'        => 'policy-document',
				'title'       => __( 'Legal, Shipping & Policy Layout', 'atelier-visual-builder' ),
				'category'    => 'wc_pages',
				'icon'        => '📜',
				'description' => __( 'Two-column sticky table-of-contents sidebar + structured policy clauses for Shipping, Returns, Privacy, and Terms pages.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'        => 'CLIENT CARE & GOVERNANCE',
						'title'          => 'Shipping, Returns & Privacy Policy',
						'effective_date' => 'Last Updated: October 2026',
						'intro'          => 'We believe in complete transparency regarding how your objects are crafted, packed, insured in transit, and how your personal data is protected.',
						'contact_box'    => "Questions regarding customs, returns, or data privacy?\nEmail: legal@atelier-studio.com\nStudio Hours: Mon–Fri, 09:00–18:00 CET",
						'clauses'        => array(
							array(
								'number'  => '01',
								'heading' => 'Global Courier Dispatch & Lead Times',
								'body'    => "All in-stock pieces are inspected by hand and dispatched from our studio warehouse within 24 to 48 business hours.\n\n• Domestic Express Delivery: 2–3 business days.\n• International Air Courier (DHL / FedEx): 4–6 business days.\n• Made-to-Order Architectural Furniture: 4–6 weeks kiln and workshop lead time.",
								'callout' => 'Complimentary insured shipping is automatically applied to all orders over $200.',
							),
							array(
								'number'  => '02',
								'heading' => '30-Day Home Trial & Returns Protocol',
								'body'    => "We invite you to experience our objects in the natural light of your own space for 30 days. If an item does not harmonize with your interior, you may request a return authorization through your Collector Portal.\n\nReturned pieces must be unused and packed in their original biodegradable protective crating.",
								'callout' => 'Refunds are issued to your original payment method within 3 business days of studio inspection.',
							),
							array(
								'number'  => '03',
								'heading' => 'Transit Insurance & Breakage Replacement',
								'body'    => 'Every ceramic, glass, and stone object is fully insured against transit damage at no additional cost to you. In the rare event that a piece arrives damaged, photograph the outer crate and object within 48 hours of delivery for an immediate complimentary replacement.',
								'callout' => '',
							),
							array(
								'number'  => '04',
								'heading' => 'Client Data Privacy & GDPR Compliance',
								'body'    => 'Atelier collects only the essential information required to fulfill your order, provide courier tracking, and—where explicitly requested—send our seasonal journal. We never sell, rent, or trade client data with third-party advertisers.',
								'callout' => 'You may request a complete export or deletion of your personal records at any time.',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Policy Document Title', 'type' => 'text' ),
					array( 'key' => 'effective_date', 'label' => 'Effective Date / Version Badge', 'type' => 'text' ),
					array( 'key' => 'intro', 'label' => 'Lead Summary Paragraph', 'type' => 'textarea' ),
					array( 'key' => 'contact_box', 'label' => 'Sidebar Contact Card Text', 'type' => 'textarea' ),
					array(
						'key'       => 'clauses',
						'label'     => 'Policy Sections / Clauses',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'number', 'label' => 'Section Number (e.g. 01)', 'type' => 'text' ),
							array( 'key' => 'heading', 'label' => 'Clause Heading', 'type' => 'text' ),
							array( 'key' => 'body', 'label' => 'Policy Text', 'type' => 'textarea' ),
							array( 'key' => 'callout', 'label' => 'Highlight Callout Box (optional)', 'type' => 'text' ),
						),
					),
				),
			),

			/* =============================================================
			   Existing Merchandising, Hero, Editorial & Layout Widgets
			   ============================================================= */

			'hero-banner'      => array(
				'type'        => 'hero-banner',
				'title'       => __( 'Luxury Hero Banner', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '🏛️',
				'description' => __( 'Full-bleed or framed hero banner with badge, headline, and dual CTAs.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'        => 'AUTUMN / WINTER COLLECTION',
						'title'          => 'Timeless Objects for Quiet Living',
						'subtitle'       => 'Crafted in small batches using sustainably sourced natural materials, tactile ceramics, and organic linens.',
						'btn_1_text'     => 'Explore Collection',
						'btn_1_url'      => '/shop',
						'btn_2_text'     => 'Read Our Story',
						'btn_2_url'      => '#story',
						'min_height'     => 560,
						'content_width'  => 680,
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color'      => '#1c1a18',
							'bg_image'      => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1600&q=80',
							'bg_overlay'    => 45,
							'text_color'    => '#f5f2ed',
							'heading_color' => '#ffffff',
							'text_align'    => 'center',
						)
					),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'container_width' => 'full',
							'padding_top'     => 110,
							'padding_bottom'  => 110,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Badge', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Hero Headline', 'type' => 'textarea' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle Copy', 'type' => 'textarea' ),
					array( 'key' => 'btn_1_text', 'label' => 'Primary Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_1_url', 'label' => 'Primary Button URL', 'type' => 'text' ),
					array( 'key' => 'btn_2_text', 'label' => 'Secondary Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_2_url', 'label' => 'Secondary Button URL', 'type' => 'text' ),
					array( 'key' => 'min_height', 'label' => 'Minimum Height (px)', 'type' => 'number', 'min' => 280, 'max' => 960 ),
					array( 'key' => 'content_width', 'label' => 'Text Max Width (px)', 'type' => 'number', 'min' => 400, 'max' => 1100 ),
				),
			),

			'wc-product-grid'  => array(
				'type'        => 'wc-product-grid',
				'title'       => __( 'WooCommerce Product Grid', 'atelier-visual-builder' ),
				'category'    => 'woocommerce',
				'icon'        => '🛒',
				'description' => __( 'Live WooCommerce product grid filtered by latest, featured, on-sale, or category.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'       => 'CURATED SELECTION',
						'title'         => 'New Arrivals',
						'subtitle'      => 'Handpicked essentials designed to elevate your everyday rituals.',
						'query_type'    => 'latest',
						'category_slug' => '',
						'columns'       => 4,
						'limit'         => 4,
						'show_cta'      => true,
						'cta_text'      => 'View All Products',
						'cta_url'       => '/shop',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea' ),
					array(
						'key'     => 'query_type',
						'label'   => 'Product Filter',
						'type'    => 'select',
						'options' => array(
							'latest'       => 'Latest Products',
							'featured'     => 'Featured Products',
							'on_sale'      => 'On-Sale Products',
							'best_selling' => 'Best Selling Products',
						),
					),
					array( 'key' => 'category_slug', 'label' => 'Category Slug (optional)', 'type' => 'text' ),
					array( 'key' => 'columns', 'label' => 'Grid Columns (2-4)', 'type' => 'number', 'min' => 2, 'max' => 4 ),
					array( 'key' => 'limit', 'label' => 'Number of Products', 'type' => 'number', 'min' => 2, 'max' => 12 ),
					array( 'key' => 'show_cta', 'label' => 'Show Bottom CTA Button', 'type' => 'toggle' ),
					array( 'key' => 'cta_text', 'label' => 'Button Text', 'type' => 'text' ),
					array( 'key' => 'cta_url', 'label' => 'Button URL', 'type' => 'text' ),
				),
			),

			'wc-category-cards' => array(
				'type'        => 'wc-category-cards',
				'title'       => __( 'Category Bento Showcase', 'atelier-visual-builder' ),
				'category'    => 'woocommerce',
				'icon'        => '🗂️',
				'description' => __( 'Visual category cards with hover zoom and item count badges.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow' => 'SHOP BY DEPARTMENT',
						'title'   => 'Explore Collections',
						'columns' => 3,
						'items'   => array(
							array(
								'title'    => 'Ceramics & Tableware',
								'subtitle' => '24 Objects',
								'image'    => 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=900&q=80',
								'url'      => '/shop',
							),
							array(
								'title'    => 'Organic Home Textiles',
								'subtitle' => '18 Objects',
								'image'    => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=900&q=80',
								'url'      => '/shop',
							),
							array(
								'title'    => 'Architectural Lighting',
								'subtitle' => '12 Objects',
								'image'    => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=80',
								'url'      => '/shop',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array( 'key' => 'columns', 'label' => 'Columns (2-4)', 'type' => 'number', 'min' => 2, 'max' => 4 ),
					array(
						'key'        => 'items',
						'label'      => 'Category Cards',
						'type'       => 'repeater',
						'subfields'  => array(
							array( 'key' => 'title', 'label' => 'Category Name', 'type' => 'text' ),
							array( 'key' => 'subtitle', 'label' => 'Subtitle / Count', 'type' => 'text' ),
							array( 'key' => 'image', 'label' => 'Image URL', 'type' => 'image' ),
							array( 'key' => 'url', 'label' => 'Link URL', 'type' => 'text' ),
						),
					),
				),
			),

			'wc-lookbook'      => array(
				'type'        => 'wc-lookbook',
				'title'       => __( 'Shop-the-Look Hotspots', 'atelier-visual-builder' ),
				'category'    => 'woocommerce',
				'icon'        => '📍',
				'description' => __( 'Interactive editorial image with pulsing product hotspot pins and popovers.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'  => 'INTERACTIVE LOOKBOOK',
						'title'    => 'Shop the Living Room Edit',
						'subtitle' => 'Click any interactive pin on the photograph to inspect featured pieces.',
						'image'    => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1600&q=80',
						'pins'     => array(
							array(
								'x'     => 34,
								'y'     => 58,
								'title' => 'Travertine Low Coffee Table',
								'price' => '$640.00',
								'url'   => '/shop',
							),
							array(
								'x'     => 68,
								'y'     => 44,
								'title' => 'Bouclé Lounge Armchair',
								'price' => '$890.00',
								'url'   => '/shop',
							),
							array(
								'x'     => 52,
								'y'     => 28,
								'title' => 'Hand-Thrown Stoneware Vessel',
								'price' => '$115.00',
								'url'   => '/shop',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea' ),
					array( 'key' => 'image', 'label' => 'Lookbook Photograph', 'type' => 'image' ),
					array(
						'key'       => 'pins',
						'label'     => 'Interactive Hotspot Pins',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'x', 'label' => 'Horizontal Position X (%)', 'type' => 'number', 'min' => 5, 'max' => 95 ),
							array( 'key' => 'y', 'label' => 'Vertical Position Y (%)', 'type' => 'number', 'min' => 5, 'max' => 95 ),
							array( 'key' => 'title', 'label' => 'Product Name', 'type' => 'text' ),
							array( 'key' => 'price', 'label' => 'Price Label', 'type' => 'text' ),
							array( 'key' => 'url', 'label' => 'Product Link URL', 'type' => 'text' ),
						),
					),
				),
			),

			'split-showcase'   => array(
				'type'        => 'split-showcase',
				'title'       => __( '50/50 Split Story Showcase', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '🌓',
				'description' => __( 'Two-column editorial image and story layout with reversible alignment.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'image_position' => 'left',
						'image'          => 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=1000&q=80',
						'eyebrow'        => 'ARTISAN PHILOSOPHY',
						'title'          => 'Designed for Longevity, Shaped by Hand',
						'text'           => 'Every piece in our studio passes through the hands of master ceramicists and woodworkers. Imperfections in glaze and grain are celebrated as signatures of authentic human craft.',
						'btn_text'       => 'Discover Our Workshop',
						'btn_url'        => '/shop',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color' => '#f6f3ee',
						)
					),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array(
						'key'     => 'image_position',
						'label'   => 'Image Column Side',
						'type'    => 'select',
						'options' => array(
							'left'  => 'Image on Left',
							'right' => 'Image on Right',
						),
					),
					array( 'key' => 'image', 'label' => 'Feature Image', 'type' => 'image' ),
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Headline', 'type' => 'textarea' ),
					array( 'key' => 'text', 'label' => 'Body Narrative', 'type' => 'textarea' ),
					array( 'key' => 'btn_text', 'label' => 'Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_url', 'label' => 'Button Link URL', 'type' => 'text' ),
				),
			),

			'countdown-banner' => array(
				'type'        => 'countdown-banner',
				'title'       => __( 'Flash Sale Countdown Timer', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '⏳',
				'description' => __( 'Live ticking Days/Hours/Minutes/Seconds promotional timer banner.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'     => 'LIMITED EDITION ARCHIVE EVENT',
						'title'       => 'Up to 30% Off Seasonal Archive Pieces',
						'subtitle'    => 'Complimentary express courier shipping included on all orders before the timer expires.',
						'target_date' => gmdate( 'Y-m-d\TH:i', strtotime( '+5 days' ) ),
						'btn_text'    => 'Shop Archive Sale',
						'btn_url'     => '/shop',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color'      => '#1c1a18',
							'text_color'    => '#e8e2d9',
							'heading_color' => '#ffffff',
							'text_align'    => 'center',
						)
					),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Headline', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea' ),
					array( 'key' => 'target_date', 'label' => 'End Date & Time (YYYY-MM-DDTHH:MM)', 'type' => 'text' ),
					array( 'key' => 'btn_text', 'label' => 'Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_url', 'label' => 'Button URL', 'type' => 'text' ),
				),
			),

			'feature-icons'    => array(
				'type'        => 'feature-icons',
				'title'       => __( 'Feature / USP Icon Grid', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '💎',
				'description' => __( 'Multi-column store guarantees, value propositions, or service highlights.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'columns' => 4,
						'items'   => array(
							array(
								'icon'  => '✦',
								'title' => 'Small-Batch Craft',
								'text'  => 'Ethically produced in limited numbers with full material traceability.',
							),
							array(
								'icon'  => '📦',
								'title' => 'Carbon-Neutral Delivery',
								'text'  => 'Plastic-free protective packaging shipped worldwide with tracked couriers.',
							),
							array(
								'icon'  => '↺',
								'title' => '30-Day Home Trial',
								'text'  => 'Experience our pieces in your space with effortless complimentary returns.',
							),
							array(
								'icon'  => '🛡️',
								'title' => 'Lifetime Care Guarantee',
								'text'  => 'Dedicated repair and restoration guidance for every Atelier object.',
							),
						),
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color'   => '#fbf9f6',
							'text_align' => 'center',
						)
					),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'padding_top'    => 44,
							'padding_bottom' => 44,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'columns', 'label' => 'Columns (2-4)', 'type' => 'number', 'min' => 2, 'max' => 4 ),
					array(
						'key'       => 'items',
						'label'     => 'Feature Items',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'icon', 'label' => 'Icon / Symbol', 'type' => 'text' ),
							array( 'key' => 'title', 'label' => 'Title', 'type' => 'text' ),
							array( 'key' => 'text', 'label' => 'Description', 'type' => 'textarea' ),
						),
					),
				),
			),

			'testimonials'     => array(
				'type'        => 'testimonials',
				'title'       => __( 'Customer Testimonials', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '💬',
				'description' => __( 'Verified customer reviews with star ratings and editorial typography.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow' => 'CLIENT REFLECTIONS',
						'title'   => 'Loved in Homes Worldwide',
						'columns' => 3,
						'items'   => array(
							array(
								'quote'  => 'The stoneware dinner set completely transformed our dining table. The matte glaze feels extraordinary in hand.',
								'author' => 'Clara Lindqvist',
								'role'   => 'Interior Architect — Stockholm',
								'rating' => 5,
							),
							array(
								'quote'  => 'Unmatched attention to packaging and detail. Every piece arrived in pristine condition and looks like a gallery sculpture.',
								'author' => 'Marcus Vance',
								'role'   => 'Creative Director — London',
								'rating' => 5,
							),
							array(
								'quote'  => 'Finally a store that values timeless quality over fast trends. The washed Belgian linen bedding is pure heaven.',
								'author' => 'Elena Rostova',
								'role'   => 'Verified Collector — Paris',
								'rating' => 5,
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array( 'key' => 'columns', 'label' => 'Columns (1-3)', 'type' => 'number', 'min' => 1, 'max' => 3 ),
					array(
						'key'       => 'items',
						'label'     => 'Testimonials',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'quote', 'label' => 'Review Quote', 'type' => 'textarea' ),
							array( 'key' => 'author', 'label' => 'Author Name', 'type' => 'text' ),
							array( 'key' => 'role', 'label' => 'Role / City', 'type' => 'text' ),
							array( 'key' => 'rating', 'label' => 'Stars (1-5)', 'type' => 'number', 'min' => 1, 'max' => 5 ),
						),
					),
				),
			),

			'accordion-faq'    => array(
				'type'        => 'accordion-faq',
				'title'       => __( 'Interactive FAQ Accordion', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '❓',
				'description' => __( 'Collapsible questions and answers for shipping, sizing, and care guides.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'  => 'FREQUENTLY ASKED QUESTIONS',
						'title'    => 'Shipping, Care & Returns',
						'subtitle' => 'Everything you need to know about ordering and caring for your pieces.',
						'items'    => array(
							array(
								'question' => 'How quickly are orders dispatched?',
								'answer'   => 'In-stock objects are carefully inspected, packed in plastic-free protective crating, and dispatched within 24–48 business hours with full tracking.',
							),
							array(
								'question' => 'Are your ceramics dishwasher and microwave safe?',
								'answer'   => 'Yes. Our high-fired stoneware ceramics are safe for daily dishwasher use, though we recommend gentle hand washing for oversized sculptural vessels.',
							),
							array(
								'question' => 'What is your return and exchange policy?',
								'answer'   => 'We offer a 30-day home trial on all standard items. If a piece does not harmonize with your space, initiate a return from your account for a full refund.',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea' ),
					array(
						'key'       => 'items',
						'label'     => 'FAQ Items',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'question', 'label' => 'Question', 'type' => 'text' ),
							array( 'key' => 'answer', 'label' => 'Answer', 'type' => 'textarea' ),
						),
					),
				),
			),

			'pricing-cards'    => array(
				'type'        => 'pricing-cards',
				'title'       => __( 'Pricing & Trade Tiers', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '🏷️',
				'description' => __( 'Comparison cards for memberships, trade programs, or styling packages.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow' => 'ATELIER COLLECTOR & TRADE PROGRAM',
						'title'   => 'Membership & Styling Tiers',
						'items'   => array(
							array(
								'name'     => 'Collector Insider',
								'price'    => 'Free',
								'period'   => 'Forever',
								'badge'    => '',
								'features' => "Early access to seasonal drops\nComplimentary shipping over $150\nDigital care & styling lookbooks",
								'btn_text' => 'Join Insider',
								'btn_url'  => '/my-account',
								'featured' => 'no',
							),
							array(
								'name'     => 'Atelier Private Patron',
								'price'    => '$190',
								'period'   => 'per year',
								'badge'    => 'MOST POPULAR',
								'features' => "15% collector privilege on all orders\nUnlimited priority express courier delivery\n1-on-1 virtual interior curation session\nAnnual limited-edition ceramic gift",
								'btn_text' => 'Become a Patron',
								'btn_url'  => '/shop',
								'featured' => 'yes',
							),
							array(
								'name'     => 'Architect & Trade Studio',
								'price'    => 'Custom',
								'period'   => 'Trade Account',
								'badge'    => '',
								'features' => "Dedicated trade pricing up to 25% off\nBespoke finish & glaze commissioning\nConsolidated white-glove project delivery",
								'btn_text' => 'Apply for Trade',
								'btn_url'  => '/contact',
								'featured' => 'no',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Section Title', 'type' => 'text' ),
					array(
						'key'       => 'items',
						'label'     => 'Pricing Tiers',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'name', 'label' => 'Tier Name', 'type' => 'text' ),
							array( 'key' => 'price', 'label' => 'Price (e.g. $190)', 'type' => 'text' ),
							array( 'key' => 'period', 'label' => 'Billing Period', 'type' => 'text' ),
							array( 'key' => 'badge', 'label' => 'Highlight Badge', 'type' => 'text' ),
							array( 'key' => 'features', 'label' => 'Features (one per line)', 'type' => 'textarea' ),
							array( 'key' => 'btn_text', 'label' => 'Button Label', 'type' => 'text' ),
							array( 'key' => 'btn_url', 'label' => 'Button URL', 'type' => 'text' ),
							array( 'key' => 'featured', 'label' => 'Highlight Card (yes/no)', 'type' => 'text' ),
						),
					),
				),
			),

			'newsletter-box'   => array(
				'type'        => 'newsletter-box',
				'title'       => __( 'Newsletter Signup Banner', 'atelier-visual-builder' ),
				'category'    => 'marketing',
				'icon'        => '✉️',
				'description' => __( 'Email capture section with incentive copy and privacy note.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'     => 'THE ATELIER JOURNAL',
						'title'       => 'Receive 10% Off Your First Acquisition',
						'subtitle'    => 'Join our private dispatch for studio notes, exhibition invitations, and early access to small-batch releases.',
						'placeholder' => 'Enter your email address...',
						'btn_text'    => 'Subscribe',
						'disclaimer'  => 'No spam. Unsubscribe with a single click at any time.',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'bg_color'   => '#f4efe8',
							'text_align' => 'center',
						)
					),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Label', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Headline', 'type' => 'text' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle Copy', 'type' => 'textarea' ),
					array( 'key' => 'placeholder', 'label' => 'Input Placeholder', 'type' => 'text' ),
					array( 'key' => 'btn_text', 'label' => 'Submit Button Text', 'type' => 'text' ),
					array( 'key' => 'disclaimer', 'label' => 'Privacy Note', 'type' => 'text' ),
				),
			),

			'columns-row'      => array(
				'type'        => 'columns-row',
				'title'       => __( 'Multi-Column Content Row', 'atelier-visual-builder' ),
				'category'    => 'layout',
				'icon'        => '🏛️',
				'description' => __( '1 to 4 responsive columns with optional images, headings, text, and links.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'columns' => 3,
						'gap'     => 28,
						'items'   => array(
							array(
								'image'    => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=800&q=80',
								'title'    => '01. Raw Earth Selection',
								'text'     => 'We source unrefined mineral clays from family-operated quarries with centuries of heritage.',
								'btn_text' => 'Learn More',
								'btn_url'  => '#',
							),
							array(
								'image'    => 'https://images.unsplash.com/photo-1490312278390-ab64016e0aa9?auto=format&fit=crop&w=800&q=80',
								'title'    => '02. Wheel & Kiln Firing',
								'text'     => 'Each silhouette is hand-thrown and bisque-fired before receiving our signature mineral glaze.',
								'btn_text' => 'Learn More',
								'btn_url'  => '#',
							),
							array(
								'image'    => 'https://images.unsplash.com/photo-1615529182904-14819c35db37?auto=format&fit=crop&w=800&q=80',
								'title'    => '03. Curated for Your Home',
								'text'     => 'Numbered, stamped, and delivered directly from our studio shelves to your living space.',
								'btn_text' => 'Learn More',
								'btn_url'  => '#',
							),
						),
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'columns', 'label' => 'Columns (1-4)', 'type' => 'number', 'min' => 1, 'max' => 4 ),
					array( 'key' => 'gap', 'label' => 'Column Gap (px)', 'type' => 'number', 'min' => 0, 'max' => 80 ),
					array(
						'key'       => 'items',
						'label'     => 'Columns',
						'type'      => 'repeater',
						'subfields' => array(
							array( 'key' => 'image', 'label' => 'Column Image URL', 'type' => 'image' ),
							array( 'key' => 'title', 'label' => 'Column Heading', 'type' => 'text' ),
							array( 'key' => 'text', 'label' => 'Column Text', 'type' => 'textarea' ),
							array( 'key' => 'btn_text', 'label' => 'Link Label (optional)', 'type' => 'text' ),
							array( 'key' => 'btn_url', 'label' => 'Link URL', 'type' => 'text' ),
						),
					),
				),
			),

			'heading'          => array(
				'type'        => 'heading',
				'title'       => __( 'Editorial Heading', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '𝗛',
				'description' => __( 'Section title with eyebrow badge and optional lead paragraph.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'eyebrow'  => 'STUDIO ARCHIVE',
						'title'    => 'Form Follows Quiet Function',
						'subtitle' => 'Explore the architectural silhouettes that define our current seasonal release.',
						'tag'      => 'h2',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'text_align' => 'center',
						)
					),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'padding_top'    => 48,
							'padding_bottom' => 24,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'eyebrow', 'label' => 'Eyebrow Tag', 'type' => 'text' ),
					array( 'key' => 'title', 'label' => 'Heading Text', 'type' => 'textarea' ),
					array( 'key' => 'subtitle', 'label' => 'Subtitle / Lead', 'type' => 'textarea' ),
					array(
						'key'     => 'tag',
						'label'   => 'HTML Tag',
						'type'    => 'select',
						'options' => array(
							'h1' => 'H1 — Page Title',
							'h2' => 'H2 — Section Title',
							'h3' => 'H3 — Subheading',
							'h4' => 'H4 — Small Heading',
						),
					),
				),
			),

			'rich-text'        => array(
				'type'        => 'rich-text',
				'title'       => __( 'Editorial Paragraph / Story', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '¶',
				'description' => __( 'Long-form editorial storytelling block with optional luxury drop-cap.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'body'      => "Founded on the belief that everyday objects shape our state of mind, Atelier collaborates with independent ceramicists, weavers, and glassblowers across Europe and Japan.\n\nEach collection is conceived as a quiet dialogue between raw organic texture and contemporary architectural restraint — made to age with grace over decades rather than seasons.",
						'drop_cap'  => true,
						'max_width' => 760,
					),
					'style'    => self::get_default_style(),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'padding_top'    => 36,
							'padding_bottom' => 36,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'body', 'label' => 'Editorial Text (double newline for paragraphs)', 'type' => 'textarea' ),
					array( 'key' => 'drop_cap', 'label' => 'Enable Editorial Drop Cap', 'type' => 'toggle' ),
					array( 'key' => 'max_width', 'label' => 'Reading Width (px)', 'type' => 'number', 'min' => 420, 'max' => 1200 ),
				),
			),

			'image-box'        => array(
				'type'        => 'image-box',
				'title'       => __( 'Image & Caption Frame', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '🖼️',
				'description' => __( 'High-impact photograph with aspect ratio control, hover zoom, and caption.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'image'        => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1600&q=80',
						'caption'      => 'Photograph 01 — Kyoto Ceramic Studio, Autumn Collection.',
						'aspect_ratio' => '16/9',
						'hover_zoom'   => true,
						'link_url'     => '',
					),
					'style'    => self::get_default_style(),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'image', 'label' => 'Image URL', 'type' => 'image' ),
					array( 'key' => 'caption', 'label' => 'Caption Text', 'type' => 'text' ),
					array(
						'key'     => 'aspect_ratio',
						'label'   => 'Aspect Ratio',
						'type'    => 'select',
						'options' => array(
							'auto' => 'Original Ratio',
							'16/9' => '16:9 Widescreen',
							'21/9' => '21:9 Cinematic Panorama',
							'4/3'  => '4:3 Classic Frame',
							'1/1'  => '1:1 Square',
							'4/5'  => '4:5 Editorial Portrait',
						),
					),
					array( 'key' => 'hover_zoom', 'label' => 'Enable Smooth Hover Zoom', 'type' => 'toggle' ),
					array( 'key' => 'link_url', 'label' => 'Click Link URL (optional)', 'type' => 'text' ),
				),
			),

			'video-showcase'   => array(
				'type'        => 'video-showcase',
				'title'       => __( 'Video Showcase', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '🎬',
				'description' => __( 'Embed YouTube, Vimeo, or direct MP4 studio films with caption.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'title'     => 'Inside the Studio',
						'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
						'caption'   => 'Short film documenting our glazing and kiln-firing process.',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'text_align' => 'center',
						)
					),
					'advanced' => self::get_default_advanced(),
				),
				'fields'      => array(
					array( 'key' => 'title', 'label' => 'Heading (optional)', 'type' => 'text' ),
					array( 'key' => 'video_url', 'label' => 'YouTube, Vimeo, or .mp4 URL', 'type' => 'text' ),
					array( 'key' => 'caption', 'label' => 'Video Caption', 'type' => 'text' ),
				),
			),

			'button-group'     => array(
				'type'        => 'button-group',
				'title'       => __( 'Dual Action Buttons', 'atelier-visual-builder' ),
				'category'    => 'basic',
				'icon'        => '🔘',
				'description' => __( 'Primary and secondary call-to-action buttons with flexible alignment.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'btn_1_text'  => 'Shop All Objects',
						'btn_1_url'   => '/shop',
						'btn_1_style' => 'solid',
						'btn_2_text'  => 'Download Lookbook',
						'btn_2_url'   => '#',
						'btn_2_style' => 'outline',
					),
					'style'    => array_merge(
						self::get_default_style(),
						array(
							'text_align' => 'center',
						)
					),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'padding_top'    => 24,
							'padding_bottom' => 24,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'btn_1_text', 'label' => 'Primary Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_1_url', 'label' => 'Primary Button URL', 'type' => 'text' ),
					array(
						'key'     => 'btn_1_style',
						'label'   => 'Primary Button Style',
						'type'    => 'select',
						'options' => array(
							'solid'   => 'Solid Dark / Accent',
							'outline' => 'Minimal Outline',
							'link'    => 'Underlined Editorial Link',
						),
					),
					array( 'key' => 'btn_2_text', 'label' => 'Secondary Button Label', 'type' => 'text' ),
					array( 'key' => 'btn_2_url', 'label' => 'Secondary Button URL', 'type' => 'text' ),
					array(
						'key'     => 'btn_2_style',
						'label'   => 'Secondary Button Style',
						'type'    => 'select',
						'options' => array(
							'outline' => 'Minimal Outline',
							'solid'   => 'Solid Dark / Accent',
							'link'    => 'Underlined Editorial Link',
						),
					),
				),
			),

			'spacer-divider'   => array(
				'type'        => 'spacer-divider',
				'title'       => __( 'Spacer & Editorial Divider', 'atelier-visual-builder' ),
				'category'    => 'layout',
				'icon'        => '➖',
				'description' => __( 'Vertical breathing room with optional hairline rule or ornament.', 'atelier-visual-builder' ),
				'defaults'    => array(
					'content'  => array(
						'height'     => 48,
						'show_line'  => true,
						'line_style' => 'solid',
						'ornament'   => '✦',
					),
					'style'    => self::get_default_style(),
					'advanced' => array_merge(
						self::get_default_advanced(),
						array(
							'padding_top'    => 16,
							'padding_bottom' => 16,
						)
					),
				),
				'fields'      => array(
					array( 'key' => 'height', 'label' => 'Vertical Space (px)', 'type' => 'number', 'min' => 8, 'max' => 240 ),
					array( 'key' => 'show_line', 'label' => 'Show Horizontal Divider Line', 'type' => 'toggle' ),
					array(
						'key'     => 'line_style',
						'label'   => 'Line Style',
						'type'    => 'select',
						'options' => array(
							'solid'  => 'Solid Hairline',
							'dashed' => 'Dashed Line',
							'dotted' => 'Dotted Line',
						),
					),
					array( 'key' => 'ornament', 'label' => 'Center Symbol (optional)', 'type' => 'text' ),
				),
			),
		);
	}

	/**
	 * Instantiate a fresh widget element by type with a unique ID.
	 *
	 * @param string $type Widget type key.
	 * @param array  $overrides Optional overrides for content/style/advanced.
	 * @return array|null
	 */
	public static function create_element( $type, $overrides = array() ) {
		$widgets = self::get_widgets();
		if ( ! isset( $widgets[ $type ] ) ) {
			return null;
		}

		$defaults = $widgets[ $type ]['defaults'];
		$element  = array(
			'id'       => 'avb_' . substr( md5( uniqid( wp_rand(), true ) ), 0, 9 ),
			'type'     => $type,
			'content'  => isset( $overrides['content'] ) ? array_merge( $defaults['content'], $overrides['content'] ) : $defaults['content'],
			'style'    => isset( $overrides['style'] ) ? array_merge( $defaults['style'], $overrides['style'] ) : $defaults['style'],
			'advanced' => isset( $overrides['advanced'] ) ? array_merge( $defaults['advanced'], $overrides['advanced'] ) : $defaults['advanced'],
		);

		return $element;
	}

	/**
	 * Get 1-click Pre-Built Starter Templates for the Template Library Modal.
	 * Includes dedicated templates for SHOP, CART/BASKET, CHECKOUT, PRODUCT, MY ACCOUNT, and POLICY pages.
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'wc-shop-page'      => array(
				'id'          => 'wc-shop-page',
				'title'       => __( 'WooCommerce Shop / Catalog Page', 'atelier-visual-builder' ),
				'category'    => __( 'Shop Page', 'atelier-visual-builder' ),
				'badge'       => 'SHOP',
				'description' => __( 'Complete Shop page with editorial collection header, department Bento cards, live filterable product catalog grid, and store guarantees.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element(
						'hero-banner',
						array(
							'content' => array(
								'eyebrow'    => 'THE COMPLETE ARCHIVE',
								'title'      => 'Objects for Architectural Living',
								'subtitle'   => 'Explore our seasonal ceramics, organic Belgian linens, and sculptural lighting.',
								'btn_1_text' => '',
								'btn_2_text' => '',
								'min_height' => 340,
							),
						)
					),
					self::create_element( 'wc-category-cards' ),
					self::create_element( 'wc-shop-catalog' ),
					self::create_element( 'feature-icons' ),
					self::create_element( 'newsletter-box' ),
				),
			),

			'wc-cart-page'      => array(
				'id'          => 'wc-cart-page',
				'title'       => __( 'WooCommerce Cart / Basket Page', 'atelier-visual-builder' ),
				'category'    => __( 'Cart / Basket', 'atelier-visual-builder' ),
				'badge'       => 'CART / BASKET',
				'description' => __( 'High-converting Shopping Bag layout with Free Shipping progress bar, 2-column cart & summary, cross-sell recommendations, and shipping assurances.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'wc-cart-basket' ),
					self::create_element(
						'wc-product-grid',
						array(
							'content' => array(
								'eyebrow'    => 'COMPLETE YOUR SPACE',
								'title'      => 'Clients Also Acquired',
								'subtitle'   => 'Companion pieces curated to pair effortlessly with your selection.',
								'query_type' => 'featured',
								'columns'    => 4,
								'limit'      => 4,
								'show_cta'   => false,
							),
						)
					),
					self::create_element( 'feature-icons' ),
				),
			),

			'wc-checkout-page'  => array(
				'id'          => 'wc-checkout-page',
				'title'       => __( 'WooCommerce Luxury Checkout Page', 'atelier-visual-builder' ),
				'category'    => __( 'Checkout', 'atelier-visual-builder' ),
				'badge'       => 'CHECKOUT',
				'description' => __( 'Distraction-free luxury checkout page with 3-step progress bar, live WooCommerce checkout form, concierge support sidebar, and checkout FAQ.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'wc-checkout-suite' ),
					self::create_element(
						'accordion-faq',
						array(
							'content' => array(
								'eyebrow'  => 'CHECKOUT ASSISTANCE',
								'title'    => 'Payment, Duties & Delivery FAQ',
								'subtitle' => 'Answers to common questions before finalizing your acquisition.',
							),
						)
					),
				),
			),

			'wc-product-page'   => array(
				'id'          => 'wc-product-page',
				'title'       => __( 'Single Product Flagship Showcase', 'atelier-visual-builder' ),
				'category'    => __( 'Product Page', 'atelier-visual-builder' ),
				'badge'       => 'PRODUCT',
				'description' => __( 'Bespoke Single Product template with 2-column gallery & buy box, 50/50 artisan story, 3-column material craft notes, client reviews, and related products.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'wc-single-product-spotlight' ),
					self::create_element( 'feature-icons' ),
					self::create_element( 'split-showcase' ),
					self::create_element( 'columns-row' ),
					self::create_element( 'testimonials' ),
					self::create_element(
						'wc-product-grid',
						array(
							'content' => array(
								'eyebrow'    => 'COMPANION OBJECTS',
								'title'      => 'You May Also Like',
								'query_type' => 'latest',
								'columns'    => 4,
								'limit'      => 4,
								'show_cta'   => false,
							),
						)
					),
				),
			),

			'wc-myaccount-page' => array(
				'id'          => 'wc-myaccount-page',
				'title'       => __( 'My Account & Collector Portal Page', 'atelier-visual-builder' ),
				'category'    => __( 'My Account', 'atelier-visual-builder' ),
				'badge'       => 'MY ACCOUNT',
				'description' => __( 'Branded customer account dashboard with collector perks, live WooCommerce orders/addresses portal, shipment tracker, and care guarantees.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'wc-my-account-portal' ),
					self::create_element( 'wc-order-tracking' ),
					self::create_element( 'feature-icons' ),
				),
			),

			'wc-policy-page'    => array(
				'id'          => 'wc-policy-page',
				'title'       => __( 'Shipping, Returns & Privacy Policy Page', 'atelier-visual-builder' ),
				'category'    => __( 'Policy & Legal', 'atelier-visual-builder' ),
				'badge'       => 'POLICY',
				'description' => __( 'Structured legal & customer care page with sticky Table of Contents sidebar, numbered policy clauses, live Order Tracker, and collapsible FAQ.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'policy-document' ),
					self::create_element( 'wc-order-tracking' ),
					self::create_element( 'accordion-faq' ),
				),
			),

			'luxury-home'       => array(
				'id'          => 'luxury-home',
				'title'       => __( 'Atelier Flagship Homepage', 'atelier-visual-builder' ),
				'category'    => __( 'Full Page', 'atelier-visual-builder' ),
				'badge'       => 'HOMEPAGE',
				'description' => __( 'Complete luxury storefront with Hero Banner, USP Bar, WooCommerce New Arrivals, Split Story, Category Bento Grid, Testimonials, and Newsletter.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'hero-banner' ),
					self::create_element( 'feature-icons' ),
					self::create_element( 'wc-product-grid' ),
					self::create_element( 'split-showcase' ),
					self::create_element( 'wc-category-cards' ),
					self::create_element( 'testimonials' ),
					self::create_element( 'newsletter-box' ),
				),
			),

			'seasonal-lookbook' => array(
				'id'          => 'seasonal-lookbook',
				'title'       => __( 'Interactive Lookbook & Shop the Edit', 'atelier-visual-builder' ),
				'category'    => __( 'Lookbook', 'atelier-visual-builder' ),
				'badge'       => 'LOOKBOOK',
				'description' => __( 'Interactive hotspot lookbook with pulsing product pins, 3-column craftsmanship notes, and featured product grid.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element(
						'heading',
						array(
							'content' => array(
								'eyebrow'  => 'LOOKBOOK N° 04',
								'title'    => 'The Quiet Architecture of Home',
								'subtitle' => 'A study in warm travertine, unbleached linen, and hand-thrown stoneware.',
								'tag'      => 'h1',
							),
						)
					),
					self::create_element( 'wc-lookbook' ),
					self::create_element( 'columns-row' ),
					self::create_element(
						'wc-product-grid',
						array(
							'content' => array(
								'eyebrow'    => 'FEATURED IN THIS EDIT',
								'title'      => 'Acquire the Pieces',
								'query_type' => 'featured',
							),
						)
					),
				),
			),

			'flash-sale'        => array(
				'id'          => 'flash-sale',
				'title'       => __( 'Archive Sale & Limited Drop Page', 'atelier-visual-builder' ),
				'category'    => __( 'Landing Page', 'atelier-visual-builder' ),
				'badge'       => 'SALE DROP',
				'description' => __( 'High-converting campaign page with live Countdown Timer, On-Sale WooCommerce Product Grid, Guarantees, and FAQ Accordion.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'countdown-banner' ),
					self::create_element(
						'wc-product-grid',
						array(
							'content' => array(
								'eyebrow'    => 'ARCHIVE REDUCTIONS',
								'title'      => 'On-Sale Objects',
								'query_type' => 'on_sale',
								'limit'      => 8,
							),
						)
					),
					self::create_element( 'feature-icons' ),
					self::create_element( 'accordion-faq' ),
				),
			),

			'brand-story'       => array(
				'id'          => 'brand-story',
				'title'       => __( 'Our Craft / About Atelier Page', 'atelier-visual-builder' ),
				'category'    => __( 'Editorial', 'atelier-visual-builder' ),
				'badge'       => 'ABOUT',
				'description' => __( 'Editorial brand manifesto with hero banner, drop-cap essay, 50/50 split showcase, and 3-step studio process.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element(
						'hero-banner',
						array(
							'content' => array(
								'eyebrow'    => 'EST. 2019 — KYOTO & COPENHAGEN',
								'title'      => 'Slow Craft in a Hurried World',
								'subtitle'   => 'Behind the studios, kilns, and looms that shape every Atelier collection.',
								'btn_1_text' => 'Explore Collection',
								'btn_2_text' => '',
								'min_height' => 460,
							),
						)
					),
					self::create_element( 'rich-text' ),
					self::create_element( 'split-showcase' ),
					self::create_element( 'columns-row' ),
					self::create_element( 'newsletter-box' ),
				),
			),

			'vip-membership'    => array(
				'id'          => 'vip-membership',
				'title'       => __( 'Trade Program & Patron Membership', 'atelier-visual-builder' ),
				'category'    => __( 'Landing Page', 'atelier-visual-builder' ),
				'badge'       => 'B2B & VIP',
				'description' => __( 'Membership and interior designer trade program page with pricing tiers, reviews, and FAQ.', 'atelier-visual-builder' ),
				'elements'    => array(
					self::create_element( 'pricing-cards' ),
					self::create_element( 'testimonials' ),
					self::create_element( 'accordion-faq' ),
				),
			),
		);
	}
}
