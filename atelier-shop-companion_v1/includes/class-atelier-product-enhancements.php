<?php
/**
 * Atelier Companion Single Product Enhancements Module.
 *
 * Includes:
 * 1. Product Details Accordions (Product Information, Specifications, Delivery & Returns, Reviews)
 * 2. Interactive Size & Fit Guide Modal (with per-product override metabox)
 * 3. Low-Stock Urgency Indicator Bar
 * 4. Estimated Delivery & Trust Highlights
 * 5. Sticky Bottom Add-to-Cart Bar on Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Product_Enhancements {

	public const SIZE_GUIDE_META = '_atelier_custom_size_guide';

	/**
	 * Initialize product enhancement hooks.
	 */
	public static function init(): void {
		// 1. Replace woocommerce/product-details block with clean Accordions when enabled.
		if ( Atelier_Companion_Settings::get( 'enable_product_accordions', true ) ) {
			add_filter( 'render_block_woocommerce/product-details', array( __CLASS__, 'render_product_accordions_block' ), 10, 2 );
			add_shortcode( 'atelier_product_accordions', array( __CLASS__, 'render_accordions_shortcode' ) );
		}

		// 2. Size Guide button, modal, and per-product metabox.
		if ( Atelier_Companion_Settings::get( 'enable_size_guide', true ) ) {
			add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_size_guide_trigger' ), 15 );
			add_action( 'wp_footer', array( __CLASS__, 'render_size_guide_modal' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_size_guide_metabox' ) );
			add_action( 'save_post_product', array( __CLASS__, 'save_size_guide_metabox' ) );
		}

		// 3. Low-stock urgency indicator before Add to Cart form.
		if ( Atelier_Companion_Settings::get( 'enable_stock_urgency', true ) ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'render_stock_urgency' ), 10 );
		}

		// 4. Estimated delivery & trust badges after Add to Cart form.
		if ( Atelier_Companion_Settings::get( 'enable_trust_badges', true ) ) {
			add_action( 'woocommerce_after_add_to_cart_form', array( __CLASS__, 'render_trust_and_delivery' ), 15 );
		}

		// 5. Sticky Add-to-Cart bar in footer on single product pages.
		if ( Atelier_Companion_Settings::get( 'enable_sticky_cart', true ) ) {
			add_action( 'wp_footer', array( __CLASS__, 'render_sticky_add_to_cart' ) );
		}
	}

	/**
	 * Replace the WooCommerce Product Details block with accessible HTML5 accordions.
	 *
	 * @param string               $block_content Default block HTML.
	 * @param array<string, mixed> $block         Block array.
	 * @return string
	 */
	public static function render_product_accordions_block( string $block_content, array $block ): string {
		$accordions = self::build_product_accordions_html();
		return $accordions ? $accordions : $block_content;
	}

	/**
	 * Shortcode [atelier_product_accordions] callback.
	 *
	 * @return string
	 */
	public static function render_accordions_shortcode(): string {
		return self::build_product_accordions_html();
	}

	/**
	 * Generate the 4-panel Product Accordions HTML (Product Information, Specifications, Delivery & Returns, Reviews).
	 *
	 * @return string
	 */
	public static function build_product_accordions_html(): string {
		global $product;
		if ( ! ( $product instanceof WC_Product ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! ( $product instanceof WC_Product ) ) {
			return '';
		}

		$description = $product->get_description() ?: $product->get_short_description();
		$delivery    = (string) Atelier_Companion_Settings::get( 'delivery_returns_summary', '' );
		$specs_def   = (string) Atelier_Companion_Settings::get( 'specifications_default', '' );
		$review_cnt  = $product->get_review_count();

		ob_start();
		?>
		<section class="atelier-accordions alignwide" aria-label="<?php esc_attr_e( 'Product details', 'atelier-companion' ); ?>">
			<!-- 1. Product Information -->
			<details class="atelier-accordion" open>
				<summary class="atelier-accordion__summary">
					<span><?php esc_html_e( 'Product information', 'atelier-companion' ); ?></span>
					<span class="atelier-accordion__icon" aria-hidden="true"></span>
				</summary>
				<div class="atelier-accordion__content">
					<?php
					if ( $description ) {
						echo wp_kses_post( wpautop( $description ) );
					} else {
						echo '<p>' . esc_html__( 'Thoughtfully designed for everyday versatility and crafted with attention to detail.', 'atelier-companion' ) . '</p>';
					}
					?>
				</div>
			</details>

			<!-- 2. Specifications -->
			<details class="atelier-accordion">
				<summary class="atelier-accordion__summary">
					<span><?php esc_html_e( 'Specifications', 'atelier-companion' ); ?></span>
					<span class="atelier-accordion__icon" aria-hidden="true"></span>
				</summary>
				<div class="atelier-accordion__content">
					<?php
					ob_start();
					wc_display_product_attributes( $product );
					$attributes_table = trim( (string) ob_get_clean() );

					if ( $attributes_table ) {
						echo wp_kses_post( $attributes_table );
					} else {
						echo wp_kses_post( wpautop( $specs_def ) );
					}
					if ( $product->get_sku() ) :
						?>
						<p class="atelier-sku-meta">
							<strong><?php esc_html_e( 'SKU:', 'atelier-companion' ); ?></strong>
							<?php echo esc_html( $product->get_sku() ); ?>
						</p>
					<?php endif; ?>
				</div>
			</details>

			<!-- 3. Delivery & Returns -->
			<details class="atelier-accordion">
				<summary class="atelier-accordion__summary">
					<span><?php esc_html_e( 'Delivery & returns', 'atelier-companion' ); ?></span>
					<span class="atelier-accordion__icon" aria-hidden="true"></span>
				</summary>
				<div class="atelier-accordion__content">
					<?php echo wp_kses_post( wpautop( $delivery ) ); ?>
				</div>
			</details>

			<!-- 4. Reviews -->
			<?php if ( comments_open( $product->get_id() ) ) : ?>
				<details class="atelier-accordion" id="atelier-reviews-accordion">
					<summary class="atelier-accordion__summary">
						<span>
							<?php
							printf(
								/* translators: %d: review count */
								esc_html__( 'Reviews (%d)', 'atelier-companion' ),
								(int) $review_cnt
							);
							?>
						</span>
						<span class="atelier-accordion__icon" aria-hidden="true"></span>
					</summary>
					<div class="atelier-accordion__content">
						<?php
						if ( $review_cnt > 0 ) {
							echo '<div class="atelier-reviews-summary">' . wp_kses_post( wc_get_rating_html( $product->get_average_rating(), $review_cnt ) ) . '</div>';
						}
						comments_template();
						?>
					</div>
				</details>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the Size & Fit Guide trigger button on single product pages.
	 */
	public static function render_size_guide_trigger(): void {
		global $product;
		if ( ! ( $product instanceof WC_Product ) ) {
			return;
		}
		?>
		<div class="atelier-size-guide-row">
			<button type="button" class="atelier-size-guide-btn" data-atelier-open-size-guide aria-haspopup="dialog" aria-controls="atelier-size-guide-modal">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"></path>
					<path d="m14.5 12.5 2-2"></path>
					<path d="m11.5 9.5 2-2"></path>
					<path d="m8.5 6.5 2-2"></path>
					<path d="m17.5 15.5 2-2"></path>
				</svg>
				<span><?php echo esc_html( (string) Atelier_Companion_Settings::get( 'size_guide_title', __( 'Size & Fit Guide', 'atelier-companion' ) ) ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Render the Size & Fit Guide modal in the footer of single product pages.
	 */
	public static function render_size_guide_modal(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product_id = (int) get_the_ID();
		$custom     = (string) get_post_meta( $product_id, self::SIZE_GUIDE_META, true );
		$raw_text   = trim( $custom ) !== ''
			? $custom
			: (string) Atelier_Companion_Settings::get( 'size_guide_content', '' );
		$title      = (string) Atelier_Companion_Settings::get( 'size_guide_title', __( 'Size & Fit Guide', 'atelier-companion' ) );

		?>
		<div id="atelier-size-guide-modal" class="atelier-modal" role="dialog" aria-modal="true" aria-labelledby="atelier-size-guide-title" aria-hidden="true">
			<div class="atelier-modal__backdrop" data-atelier-close-modal></div>
			<div class="atelier-modal__dialog atelier-modal__dialog--narrow">
				<button type="button" class="atelier-modal__close" data-atelier-close-modal aria-label="<?php esc_attr_e( 'Close Size Guide', 'atelier-companion' ); ?>">
					&times;
				</button>
				<div class="atelier-modal__content">
					<h2 id="atelier-size-guide-title" class="atelier-modal__heading"><?php echo esc_html( $title ); ?></h2>
					<?php echo self::format_size_guide_content( $raw_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Convert structured lines (`S: Chest 87-92 cm | Waist 71-76 cm`) into a clean responsive table.
	 *
	 * @param string $raw Raw size guide text.
	 * @return string
	 */
	private static function format_size_guide_content( string $raw ): string {
		$lines      = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
		$intro      = array();
		$table_rows = array();

		foreach ( $lines as $line ) {
			if ( str_contains( $line, ':' ) && str_contains( $line, '|' ) ) {
				list( $size_label, $rest ) = array_map( 'trim', explode( ':', $line, 2 ) );
				$cols                      = array_map( 'trim', explode( '|', $rest ) );
				$table_rows[]              = array(
					'size' => $size_label,
					'cols' => $cols,
				);
			} else {
				$intro[] = $line;
			}
		}

		ob_start();
		foreach ( $intro as $paragraph ) {
			echo '<p class="atelier-size-guide-intro">' . esc_html( $paragraph ) . '</p>';
		}

		if ( ! empty( $table_rows ) ) {
			echo '<div class="atelier-size-table-wrap"><table class="atelier-size-table"><thead><tr>';
			echo '<th>' . esc_html__( 'Size', 'atelier-companion' ) . '</th>';
			foreach ( $table_rows[0]['cols'] as $idx => $col_sample ) {
				$parts = explode( ' ', $col_sample, 2 );
				echo '<th>' . esc_html( $parts[0] ?: __( 'Measurement', 'atelier-companion' ) ) . '</th>';
			}
			echo '</tr></thead><tbody>';

			foreach ( $table_rows as $row ) {
				echo '<tr><td><strong>' . esc_html( $row['size'] ) . '</strong></td>';
				foreach ( $row['cols'] as $cell ) {
					$parts = explode( ' ', $cell, 2 );
					$val   = $parts[1] ?? $cell;
					echo '<td>' . esc_html( $val ) . '</td>';
				}
				echo '</tr>';
			}
			echo '</tbody></table></div>';
		}

		return (string) ob_get_clean();
	}

	/**
	 * Register per-product Size Guide metabox in WP Admin.
	 */
	public static function register_size_guide_metabox(): void {
		add_meta_box(
			'atelier_size_guide_box',
			__( 'Atelier Size & Fit Guide Override', 'atelier-companion' ),
			array( __CLASS__, 'render_size_guide_metabox' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render per-product Size Guide metabox content.
	 *
	 * @param WP_Post $post Product post object.
	 */
	public static function render_size_guide_metabox( WP_Post $post ): void {
		$val = (string) get_post_meta( $post->ID, self::SIZE_GUIDE_META, true );
		wp_nonce_field( 'atelier_save_size_guide', 'atelier_size_guide_nonce' );
		?>
		<p class="description"><?php esc_html_e( 'Leave blank to use the global Size Guide from WooCommerce → Atelier Companion.', 'atelier-companion' ); ?></p>
		<textarea name="atelier_custom_size_guide" rows="5" style="width:100%;margin-top:6px;"><?php echo esc_textarea( $val ); ?></textarea>
		<?php
	}

	/**
	 * Save per-product Size Guide override.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save_size_guide_metabox( int $post_id ): void {
		if (
			! isset( $_POST['atelier_size_guide_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['atelier_size_guide_nonce'] ) ), 'atelier_save_size_guide' )
		) {
			return;
		}
		if ( isset( $_POST['atelier_custom_size_guide'] ) ) {
			update_post_meta(
				$post_id,
				self::SIZE_GUIDE_META,
				sanitize_textarea_field( wp_unslash( $_POST['atelier_custom_size_guide'] ) )
			);
		}
	}

	/**
	 * Render Low-Stock Urgency indicator when product stock is managed and at/below threshold.
	 */
	public static function render_stock_urgency(): void {
		global $product;
		if ( ! ( $product instanceof WC_Product ) || ! $product->managing_stock() || ! $product->is_in_stock() ) {
			return;
		}

		$qty       = (int) $product->get_stock_quantity();
		$threshold = (int) Atelier_Companion_Settings::get( 'low_stock_threshold', 8 );

		if ( $qty <= 0 || $qty > $threshold ) {
			return;
		}

		$percent = max( 12, min( 100, (int) round( ( $qty / max( 1, $threshold ) ) * 100 ) ) );
		?>
		<div class="atelier-stock-urgency" role="status">
			<p class="atelier-stock-urgency__text">
				<span class="atelier-stock-urgency__dot" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %d: remaining stock quantity */
					esc_html__( 'Only %d left in stock — order soon', 'atelier-companion' ),
					$qty
				);
				?>
			</p>
			<div class="atelier-stock-urgency__bar">
				<div class="atelier-stock-urgency__fill" style="width: <?php echo esc_attr( (string) $percent ); ?>%;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Estimated Delivery window & Trust Badges below the Add to Cart form.
	 */
	public static function render_trust_and_delivery(): void {
		$delivery_days = (string) Atelier_Companion_Settings::get( 'estimated_delivery_days', '2–4 business days' );
		$trust_text    = (string) Atelier_Companion_Settings::get( 'trust_badges_text', '' );
		?>
		<div class="atelier-product-reassurance">
			<?php if ( $delivery_days ) : ?>
				<div class="atelier-reassurance-row">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
					<span>
						<?php esc_html_e( 'Estimated delivery:', 'atelier-companion' ); ?>
						<strong><?php echo esc_html( $delivery_days ); ?></strong>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $trust_text ) : ?>
				<div class="atelier-reassurance-badges">
					<?php echo esc_html( $trust_text ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Sticky Bottom Add-to-Cart bar on single product pages.
	 */
	public static function render_sticky_add_to_cart(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;
		if ( ! ( $product instanceof WC_Product ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! ( $product instanceof WC_Product ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		?>
		<aside class="atelier-sticky-cart" id="atelier-sticky-cart" aria-label="<?php esc_attr_e( 'Quick add to cart bar', 'atelier-companion' ); ?>" aria-hidden="true">
			<div class="atelier-sticky-cart__inner">
				<div class="atelier-sticky-cart__product">
					<div class="atelier-sticky-cart__thumb">
						<?php echo wp_kses_post( $product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?>
					</div>
					<div class="atelier-sticky-cart__info">
						<strong class="atelier-sticky-cart__title"><?php echo esc_html( $product->get_name() ); ?></strong>
						<span class="atelier-sticky-cart__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					</div>
				</div>
				<div class="atelier-sticky-cart__action">
					<button type="button" class="button atelier-sticky-cart__btn" data-atelier-sticky-scroll>
						<?php echo esc_html( $product->is_type( 'variable' ) ? __( 'Select options', 'atelier-companion' ) : $product->single_add_to_cart_text() ); ?>
					</button>
				</div>
			</div>
		</aside>
		<?php
	}
}
