<?php
/**
 * Atelier Visual Builder - Server-Side HTML & Scoped CSS Renderer.
 *
 * @package Atelier_Visual_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders builder element trees and individual widgets into clean semantic HTML + scoped CSS.
 */
class AVB_Renderer {

	/**
	 * Render an entire array of builder elements for a page.
	 *
	 * @param array $elements Array of element items.
	 * @param int   $post_id  Post ID.
	 * @param bool  $is_editor Whether rendering inside the visual editor canvas.
	 * @return string
	 */
	public static function render_page( $elements, $post_id = 0, $is_editor = false ) {
		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return '';
		}

		ob_start();
		echo '<div class="avb-page-canvas" data-post-id="' . esc_attr( $post_id ) . '">';
		foreach ( $elements as $element ) {
			echo self::render_element( $element, $is_editor ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Render a single element with its wrapper styles, advanced attributes, and inner widget markup.
	 *
	 * @param array $element   Element data array.
	 * @param bool  $is_editor Whether rendering inside the visual editor canvas.
	 * @return string
	 */
	public static function render_element( $element, $is_editor = false ) {
		if ( ! is_array( $element ) || empty( $element['type'] ) ) {
			return '';
		}

		$widgets = AVB_Registry::get_widgets();
		$type    = sanitize_key( $element['type'] );
		if ( ! isset( $widgets[ $type ] ) ) {
			return '';
		}

		$defaults = $widgets[ $type ]['defaults'];
		$id       = ! empty( $element['id'] ) ? sanitize_html_class( $element['id'] ) : 'avb_' . wp_rand( 1000, 9999 );
		$content  = isset( $element['content'] ) && is_array( $element['content'] )
			? array_merge( $defaults['content'], $element['content'] )
			: $defaults['content'];
		$style    = isset( $element['style'] ) && is_array( $element['style'] )
			? array_merge( $defaults['style'], $element['style'] )
			: $defaults['style'];
		$advanced = isset( $element['advanced'] ) && is_array( $element['advanced'] )
			? array_merge( $defaults['advanced'], $element['advanced'] )
			: $defaults['advanced'];

		// Build wrapper classes.
		$classes   = array(
			'avb-section',
			'avb-widget-' . $type,
			'avb-align-' . sanitize_html_class( $style['text_align'] ?: 'left' ),
			'avb-scale-' . sanitize_html_class( $style['typo_scale'] ?: 'normal' ),
			'avb-width-' . sanitize_html_class( $advanced['container_width'] ?: 'wide' ),
		);

		if ( ! empty( $style['box_shadow'] ) && 'none' !== $style['box_shadow'] ) {
			$classes[] = 'avb-shadow-' . sanitize_html_class( $style['box_shadow'] );
		}
		if ( ! empty( $advanced['animation'] ) && 'none' !== $advanced['animation'] ) {
			$classes[] = 'avb-anim-' . sanitize_html_class( $advanced['animation'] );
		}
		if ( ! empty( $advanced['hide_desktop'] ) ) {
			$classes[] = 'avb-hide-desktop';
		}
		if ( ! empty( $advanced['hide_tablet'] ) ) {
			$classes[] = 'avb-hide-tablet';
		}
		if ( ! empty( $advanced['hide_mobile'] ) ) {
			$classes[] = 'avb-hide-mobile';
		}
		if ( ! empty( $advanced['custom_class'] ) ) {
			$classes[] = sanitize_html_class( $advanced['custom_class'] );
		}

		// Inline CSS variables and box styles.
		$inline_styles = array();
		if ( ! empty( $style['bg_color'] ) ) {
			$inline_styles[] = 'background-color:' . esc_attr( $style['bg_color'] );
		}
		if ( ! empty( $style['bg_image'] ) ) {
			$inline_styles[] = 'background-image:url(' . esc_url( $style['bg_image'] ) . ')';
			$inline_styles[] = 'background-size:cover';
			$inline_styles[] = 'background-position:center';
		}
		if ( ! empty( $style['text_color'] ) ) {
			$inline_styles[] = '--avb-text-color:' . esc_attr( $style['text_color'] );
			$inline_styles[] = 'color:' . esc_attr( $style['text_color'] );
		}
		if ( ! empty( $style['heading_color'] ) ) {
			$inline_styles[] = '--avb-heading-color:' . esc_attr( $style['heading_color'] );
		}
		if ( ! empty( $style['accent_color'] ) ) {
			$inline_styles[] = '--avb-accent:' . esc_attr( $style['accent_color'] );
		}
		if ( ! empty( $style['border_radius'] ) ) {
			$inline_styles[] = 'border-radius:' . intval( $style['border_radius'] ) . 'px';
			$inline_styles[] = 'overflow:hidden';
		}
		if ( ! empty( $style['border_style'] ) && 'none' !== $style['border_style'] ) {
			$b_color         = ! empty( $style['border_color'] ) ? $style['border_color'] : '#e6e1da';
			$inline_styles[] = 'border:1px ' . esc_attr( $style['border_style'] ) . ' ' . esc_attr( $b_color );
		}

		$inline_styles[] = 'padding:' . intval( $advanced['padding_top'] ) . 'px ' . intval( $advanced['padding_right'] ) . 'px ' . intval( $advanced['padding_bottom'] ) . 'px ' . intval( $advanced['padding_left'] ) . 'px';
		$inline_styles[] = 'margin-top:' . intval( $advanced['margin_top'] ) . 'px';
		$inline_styles[] = 'margin-bottom:' . intval( $advanced['margin_bottom'] ) . 'px';

		$dom_id = ! empty( $advanced['custom_id'] ) ? sanitize_html_class( $advanced['custom_id'] ) : $id;

		ob_start();
		?>
		<section id="<?php echo esc_attr( $dom_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-avb-id="<?php echo esc_attr( $id ); ?>" data-avb-type="<?php echo esc_attr( $type ); ?>" style="<?php echo esc_attr( implode( ';', $inline_styles ) ); ?>">
			<?php if ( ! empty( $style['bg_image'] ) && intval( $style['bg_overlay'] ) > 0 ) : ?>
				<div class="avb-bg-overlay" style="opacity:<?php echo esc_attr( round( intval( $style['bg_overlay'] ) / 100, 2 ) ); ?>;"></div>
			<?php endif; ?>
			<div class="avb-section-inner">
				<?php echo self::render_widget_inner( $type, $content, $style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Dispatch widget inner HTML rendering by type.
	 *
	 * @param string $type    Widget type.
	 * @param array  $content Widget content settings.
	 * @param array  $style   Widget style settings.
	 * @return string
	 */
	private static function render_widget_inner( $type, $content, $style ) {
		ob_start();
		switch ( $type ) {
			case 'hero-banner':
				$min_h = max( 240, intval( $content['min_height'] ) );
				$max_w = max( 380, intval( $content['content_width'] ) );
				?>
				<div class="avb-hero-wrap" style="min-height:<?php echo esc_attr( $min_h ); ?>px;">
					<div class="avb-hero-content" style="max-width:<?php echo esc_attr( $max_w ); ?>px;">
						<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
							<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $content['title'] ) ) : ?>
							<h1 class="avb-heading avb-hero-title"><?php echo nl2br( esc_html( $content['title'] ) ); ?></h1>
						<?php endif; ?>
						<?php if ( ! empty( $content['subtitle'] ) ) : ?>
							<p class="avb-subtitle avb-hero-subtitle"><?php echo nl2br( esc_html( $content['subtitle'] ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $content['btn_1_text'] ) || ! empty( $content['btn_2_text'] ) ) : ?>
							<div class="avb-btn-row">
								<?php if ( ! empty( $content['btn_1_text'] ) ) : ?>
									<a href="<?php echo esc_url( $content['btn_1_url'] ?: '#' ); ?>" class="avb-btn avb-btn-solid"><?php echo esc_html( $content['btn_1_text'] ); ?></a>
								<?php endif; ?>
								<?php if ( ! empty( $content['btn_2_text'] ) ) : ?>
									<a href="<?php echo esc_url( $content['btn_2_url'] ?: '#' ); ?>" class="avb-btn avb-btn-outline-light"><?php echo esc_html( $content['btn_2_text'] ); ?></a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
				break;

			case 'wc-product-grid':
				self::render_wc_product_grid( $content );
				break;

			case 'wc-category-cards':
				$cols  = max( 2, min( 4, intval( $content['columns'] ) ) );
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-section-header">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
				</div>
				<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?>">
					<?php foreach ( $items as $card ) : ?>
						<a href="<?php echo esc_url( ! empty( $card['url'] ) ? $card['url'] : '#' ); ?>" class="avb-cat-card">
							<div class="avb-cat-card-media">
								<?php if ( ! empty( $card['image'] ) ) : ?>
									<img src="<?php echo esc_url( $card['image'] ); ?>" alt="<?php echo esc_attr( $card['title'] ?? '' ); ?>" loading="lazy" />
								<?php endif; ?>
								<div class="avb-cat-card-overlay"></div>
							</div>
							<div class="avb-cat-card-body">
								<?php if ( ! empty( $card['subtitle'] ) ) : ?>
									<span class="avb-cat-count"><?php echo esc_html( $card['subtitle'] ); ?></span>
								<?php endif; ?>
								<h3 class="avb-cat-title"><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
								<span class="avb-cat-arrow"><?php esc_html_e( 'Explore →', 'atelier-visual-builder' ); ?></span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'wc-lookbook':
				$pins = ! empty( $content['pins'] ) && is_array( $content['pins'] ) ? $content['pins'] : array();
				?>
				<div class="avb-section-header">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $content['subtitle'] ) ) : ?>
						<p class="avb-subtitle"><?php echo esc_html( $content['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="avb-lookbook-stage">
					<?php if ( ! empty( $content['image'] ) ) : ?>
						<img src="<?php echo esc_url( $content['image'] ); ?>" alt="<?php echo esc_attr( $content['title'] ?? 'Lookbook' ); ?>" class="avb-lookbook-img" />
					<?php endif; ?>
					<?php foreach ( $pins as $idx => $pin ) :
						$x = max( 5, min( 95, floatval( $pin['x'] ?? 50 ) ) );
						$y = max( 5, min( 95, floatval( $pin['y'] ?? 50 ) ) );
						?>
						<div class="avb-hotspot" style="left:<?php echo esc_attr( $x ); ?>%;top:<?php echo esc_attr( $y ); ?>%;">
							<button type="button" class="avb-hotspot-btn" aria-label="<?php echo esc_attr( $pin['title'] ?? 'Product Pin' ); ?>">
								<span>+</span>
							</button>
							<div class="avb-hotspot-card <?php echo ( 0 === $idx ) ? 'is-open' : ''; ?>">
								<strong class="avb-hotspot-title"><?php echo esc_html( $pin['title'] ?? '' ); ?></strong>
								<?php if ( ! empty( $pin['price'] ) ) : ?>
									<span class="avb-hotspot-price"><?php echo esc_html( $pin['price'] ); ?></span>
								<?php endif; ?>
								<a href="<?php echo esc_url( ! empty( $pin['url'] ) ? $pin['url'] : '#' ); ?>" class="avb-hotspot-link">
									<?php esc_html_e( 'View Object →', 'atelier-visual-builder' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'split-showcase':
				$pos = ( isset( $content['image_position'] ) && 'right' === $content['image_position'] ) ? 'right' : 'left';
				?>
				<div class="avb-split-grid avb-split-img-<?php echo esc_attr( $pos ); ?>">
					<div class="avb-split-media">
						<?php if ( ! empty( $content['image'] ) ) : ?>
							<img src="<?php echo esc_url( $content['image'] ); ?>" alt="<?php echo esc_attr( $content['title'] ?? '' ); ?>" loading="lazy" />
						<?php endif; ?>
					</div>
					<div class="avb-split-copy">
						<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
							<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $content['title'] ) ) : ?>
							<h2 class="avb-heading"><?php echo nl2br( esc_html( $content['title'] ) ); ?></h2>
						<?php endif; ?>
						<?php if ( ! empty( $content['text'] ) ) : ?>
							<p class="avb-body-copy"><?php echo nl2br( esc_html( $content['text'] ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $content['btn_text'] ) ) : ?>
							<div class="avb-btn-row" style="margin-top:24px;">
								<a href="<?php echo esc_url( $content['btn_url'] ?: '#' ); ?>" class="avb-btn avb-btn-solid"><?php echo esc_html( $content['btn_text'] ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
				break;

			case 'countdown-banner':
				?>
				<div class="avb-countdown-wrap" data-target-date="<?php echo esc_attr( $content['target_date'] ?? '' ); ?>">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $content['subtitle'] ) ) : ?>
						<p class="avb-subtitle"><?php echo esc_html( $content['subtitle'] ); ?></p>
					<?php endif; ?>
					<div class="avb-timer-boxes">
						<div class="avb-timer-unit"><span class="avb-timer-num" data-unit="days">04</span><small>Days</small></div>
						<div class="avb-timer-unit"><span class="avb-timer-num" data-unit="hours">18</span><small>Hours</small></div>
						<div class="avb-timer-unit"><span class="avb-timer-num" data-unit="mins">42</span><small>Minutes</small></div>
						<div class="avb-timer-unit"><span class="avb-timer-num" data-unit="secs">19</span><small>Seconds</small></div>
					</div>
					<?php if ( ! empty( $content['btn_text'] ) ) : ?>
						<div class="avb-btn-row">
							<a href="<?php echo esc_url( $content['btn_url'] ?: '#' ); ?>" class="avb-btn avb-btn-solid"><?php echo esc_html( $content['btn_text'] ); ?></a>
						</div>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'feature-icons':
				$cols  = max( 2, min( 4, intval( $content['columns'] ) ) );
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?> avb-features-grid">
					<?php foreach ( $items as $item ) : ?>
						<div class="avb-feature-card">
							<?php if ( ! empty( $item['icon'] ) ) : ?>
								<div class="avb-feature-icon"><?php echo esc_html( $item['icon'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $item['title'] ) ) : ?>
								<h3 class="avb-feature-title"><?php echo esc_html( $item['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $item['text'] ) ) : ?>
								<p class="avb-feature-text"><?php echo esc_html( $item['text'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'testimonials':
				$cols  = max( 1, min( 3, intval( $content['columns'] ) ) );
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-section-header">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
				</div>
				<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?>">
					<?php foreach ( $items as $rev ) :
						$stars = max( 1, min( 5, intval( $rev['rating'] ?? 5 ) ) );
						?>
						<blockquote class="avb-testimonial-card">
							<div class="avb-stars" aria-label="<?php echo esc_attr( $stars . ' out of 5 stars' ); ?>">
								<?php echo esc_html( str_repeat( '★', $stars ) ); ?>
							</div>
							<p class="avb-quote">“<?php echo esc_html( $rev['quote'] ?? '' ); ?>”</p>
							<footer class="avb-quote-meta">
								<strong><?php echo esc_html( $rev['author'] ?? '' ); ?></strong>
								<?php if ( ! empty( $rev['role'] ) ) : ?>
									<span><?php echo esc_html( $rev['role'] ); ?></span>
								<?php endif; ?>
							</footer>
						</blockquote>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'accordion-faq':
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-faq-container">
					<div class="avb-section-header">
						<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
							<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $content['title'] ) ) : ?>
							<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
						<?php endif; ?>
						<?php if ( ! empty( $content['subtitle'] ) ) : ?>
							<p class="avb-subtitle"><?php echo esc_html( $content['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
					<div class="avb-accordion-list">
						<?php foreach ( $items as $idx => $faq ) : ?>
							<details class="avb-accordion-item" <?php echo ( 0 === $idx ) ? 'open' : ''; ?>>
								<summary class="avb-accordion-summary">
									<span><?php echo esc_html( $faq['question'] ?? '' ); ?></span>
									<span class="avb-accordion-icon">+</span>
								</summary>
								<div class="avb-accordion-body">
									<p><?php echo nl2br( esc_html( $faq['answer'] ?? '' ) ); ?></p>
								</div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
				break;

			case 'pricing-cards':
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-section-header">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
				</div>
				<div class="avb-grid avb-cols-3 avb-pricing-grid">
					<?php foreach ( $items as $tier ) :
						$is_featured = ( isset( $tier['featured'] ) && 'yes' === strtolower( trim( $tier['featured'] ) ) );
						$lines       = array_filter( array_map( 'trim', explode( "\n", $tier['features'] ?? '' ) ) );
						?>
						<div class="avb-pricing-card <?php echo $is_featured ? 'is-featured' : ''; ?>">
							<?php if ( ! empty( $tier['badge'] ) ) : ?>
								<span class="avb-pricing-badge"><?php echo esc_html( $tier['badge'] ); ?></span>
							<?php endif; ?>
							<h3 class="avb-pricing-name"><?php echo esc_html( $tier['name'] ?? '' ); ?></h3>
							<div class="avb-pricing-price">
								<strong><?php echo esc_html( $tier['price'] ?? '' ); ?></strong>
								<?php if ( ! empty( $tier['period'] ) ) : ?>
									<span>/ <?php echo esc_html( $tier['period'] ); ?></span>
								<?php endif; ?>
							</div>
							<ul class="avb-pricing-features">
								<?php foreach ( $lines as $line ) : ?>
									<li><span>✓</span> <?php echo esc_html( $line ); ?></li>
								<?php endforeach; ?>
							</ul>
							<?php if ( ! empty( $tier['btn_text'] ) ) : ?>
								<a href="<?php echo esc_url( $tier['btn_url'] ?: '#' ); ?>" class="avb-btn <?php echo $is_featured ? 'avb-btn-solid' : 'avb-btn-outline'; ?>" style="width:100%;text-align:center;">
									<?php echo esc_html( $tier['btn_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'newsletter-box':
				?>
				<div class="avb-newsletter-card">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $content['subtitle'] ) ) : ?>
						<p class="avb-subtitle"><?php echo esc_html( $content['subtitle'] ); ?></p>
					<?php endif; ?>
					<form class="avb-newsletter-form" onsubmit="event.preventDefault(); this.classList.add('is-submitted');">
						<input type="email" placeholder="<?php echo esc_attr( $content['placeholder'] ?? 'Enter your email...' ); ?>" required />
						<button type="submit" class="avb-btn avb-btn-solid"><?php echo esc_html( $content['btn_text'] ?? 'Subscribe' ); ?></button>
					</form>
					<p class="avb-newsletter-success"><?php esc_html_e( 'Thank you for subscribing to The Atelier Journal.', 'atelier-visual-builder' ); ?></p>
					<?php if ( ! empty( $content['disclaimer'] ) ) : ?>
						<small class="avb-newsletter-note"><?php echo esc_html( $content['disclaimer'] ); ?></small>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'columns-row':
				$cols  = max( 1, min( 4, intval( $content['columns'] ) ) );
				$gap   = max( 0, min( 80, intval( $content['gap'] ?? 28 ) ) );
				$items = ! empty( $content['items'] ) && is_array( $content['items'] ) ? $content['items'] : array();
				?>
				<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?>" style="gap:<?php echo esc_attr( $gap ); ?>px;">
					<?php foreach ( $items as $col ) : ?>
						<div class="avb-col-card">
							<?php if ( ! empty( $col['image'] ) ) : ?>
								<div class="avb-col-media">
									<img src="<?php echo esc_url( $col['image'] ); ?>" alt="<?php echo esc_attr( $col['title'] ?? '' ); ?>" loading="lazy" />
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $col['title'] ) ) : ?>
								<h3 class="avb-col-title"><?php echo esc_html( $col['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $col['text'] ) ) : ?>
								<p class="avb-col-text"><?php echo nl2br( esc_html( $col['text'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $col['btn_text'] ) ) : ?>
								<a href="<?php echo esc_url( $col['btn_url'] ?: '#' ); ?>" class="avb-btn-link"><?php echo esc_html( $col['btn_text'] ); ?> →</a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'heading':
				$allowed_tags = array( 'h1', 'h2', 'h3', 'h4' );
				$tag          = in_array( $content['tag'] ?? 'h2', $allowed_tags, true ) ? $content['tag'] : 'h2';
				?>
				<div class="avb-heading-block">
					<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
						<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="avb-heading"><?php echo nl2br( esc_html( $content['title'] ) ); ?></<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
					<?php if ( ! empty( $content['subtitle'] ) ) : ?>
						<p class="avb-subtitle"><?php echo nl2br( esc_html( $content['subtitle'] ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'rich-text':
				$max_w      = max( 420, intval( $content['max_width'] ?? 760 ) );
				$drop_class = ! empty( $content['drop_cap'] ) ? 'avb-has-dropcap' : '';
				$paragraphs = array_filter( array_map( 'trim', explode( "\n\n", $content['body'] ?? '' ) ) );
				?>
				<div class="avb-richtext <?php echo esc_attr( $drop_class ); ?>" style="max-width:<?php echo esc_attr( $max_w ); ?>px;">
					<?php foreach ( $paragraphs as $p ) : ?>
						<p><?php echo nl2br( esc_html( $p ) ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'image-box':
				$ratio     = ! empty( $content['aspect_ratio'] ) && 'auto' !== $content['aspect_ratio'] ? 'aspect-ratio:' . esc_attr( $content['aspect_ratio'] ) . ';' : '';
				$zoom_cls  = ! empty( $content['hover_zoom'] ) ? 'avb-img-zoom' : '';
				?>
				<figure class="avb-image-figure <?php echo esc_attr( $zoom_cls ); ?>">
					<?php if ( ! empty( $content['link_url'] ) ) : ?>
						<a href="<?php echo esc_url( $content['link_url'] ); ?>" class="avb-image-frame" style="<?php echo esc_attr( $ratio ); ?>">
							<img src="<?php echo esc_url( $content['image'] ); ?>" alt="<?php echo esc_attr( $content['caption'] ?? '' ); ?>" loading="lazy" />
						</a>
					<?php else : ?>
						<div class="avb-image-frame" style="<?php echo esc_attr( $ratio ); ?>">
							<img src="<?php echo esc_url( $content['image'] ); ?>" alt="<?php echo esc_attr( $content['caption'] ?? '' ); ?>" loading="lazy" />
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $content['caption'] ) ) : ?>
						<figcaption class="avb-image-caption"><?php echo esc_html( $content['caption'] ); ?></figcaption>
					<?php endif; ?>
				</figure>
				<?php
				break;

			case 'video-showcase':
				$video_url = trim( $content['video_url'] ?? '' );
				$embed_url = '';
				if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $m ) ) {
					$embed_url = 'https://www.youtube.com/embed/' . $m[1];
				} elseif ( preg_match( '/vimeo\.com\/(\d+)/', $video_url, $m ) ) {
					$embed_url = 'https://player.vimeo.com/video/' . $m[1];
				}
				?>
				<div class="avb-video-block">
					<?php if ( ! empty( $content['title'] ) ) : ?>
						<h2 class="avb-heading" style="margin-bottom:20px;"><?php echo esc_html( $content['title'] ); ?></h2>
					<?php endif; ?>
					<div class="avb-video-frame">
						<?php if ( $embed_url ) : ?>
							<iframe src="<?php echo esc_url( $embed_url ); ?>" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="<?php echo esc_attr( $content['title'] ?? 'Video' ); ?>"></iframe>
						<?php elseif ( ! empty( $video_url ) ) : ?>
							<video src="<?php echo esc_url( $video_url ); ?>" controls playsinline></video>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $content['caption'] ) ) : ?>
						<p class="avb-image-caption"><?php echo esc_html( $content['caption'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'button-group':
				$s1 = in_array( $content['btn_1_style'] ?? 'solid', array( 'solid', 'outline', 'link' ), true ) ? $content['btn_1_style'] : 'solid';
				$s2 = in_array( $content['btn_2_style'] ?? 'outline', array( 'solid', 'outline', 'link' ), true ) ? $content['btn_2_style'] : 'outline';
				?>
				<div class="avb-btn-row">
					<?php if ( ! empty( $content['btn_1_text'] ) ) : ?>
						<a href="<?php echo esc_url( $content['btn_1_url'] ?: '#' ); ?>" class="avb-btn avb-btn-<?php echo esc_attr( $s1 ); ?>"><?php echo esc_html( $content['btn_1_text'] ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $content['btn_2_text'] ) ) : ?>
						<a href="<?php echo esc_url( $content['btn_2_url'] ?: '#' ); ?>" class="avb-btn avb-btn-<?php echo esc_attr( $s2 ); ?>"><?php echo esc_html( $content['btn_2_text'] ); ?></a>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'spacer-divider':
				$h          = max( 8, min( 240, intval( $content['height'] ?? 48 ) ) );
				$line_style = in_array( $content['line_style'] ?? 'solid', array( 'solid', 'dashed', 'dotted' ), true ) ? $content['line_style'] : 'solid';
				?>
				<div class="avb-spacer" style="min-height:<?php echo esc_attr( $h ); ?>px;">
					<?php if ( ! empty( $content['show_line'] ) ) : ?>
						<div class="avb-divider-line" style="border-top-style:<?php echo esc_attr( $line_style ); ?>;">
							<?php if ( ! empty( $content['ornament'] ) ) : ?>
								<span class="avb-divider-ornament"><?php echo esc_html( $content['ornament'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php
				break;
		}
		return ob_get_clean();
	}

	/**
	 * Render WooCommerce Product Grid with real WooCommerce products (plus curated preview fallback).
	 *
	 * @param array $content Widget content settings.
	 */
	private static function render_wc_product_grid( $content ) {
		$cols  = max( 2, min( 4, intval( $content['columns'] ?? 4 ) ) );
		$limit = max( 2, min( 12, intval( $content['limit'] ?? 4 ) ) );
		$qtype = sanitize_key( $content['query_type'] ?? 'latest' );
		?>
		<div class="avb-section-header">
			<?php if ( ! empty( $content['eyebrow'] ) ) : ?>
				<span class="avb-eyebrow"><?php echo esc_html( $content['eyebrow'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $content['title'] ) ) : ?>
				<h2 class="avb-heading"><?php echo esc_html( $content['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $content['subtitle'] ) ) : ?>
				<p class="avb-subtitle"><?php echo esc_html( $content['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php

		$products = array();
		if ( function_exists( 'wc_get_products' ) ) {
			$args = array(
				'status' => 'publish',
				'limit'  => $limit,
			);
			if ( ! empty( $content['category_slug'] ) ) {
				$args['category'] = array( sanitize_title( $content['category_slug'] ) );
			}
			if ( 'featured' === $qtype ) {
				$args['featured'] = true;
			} elseif ( 'on_sale' === $qtype && function_exists( 'wc_get_product_ids_on_sale' ) ) {
				$sale_ids        = wc_get_product_ids_on_sale();
				$args['include'] = ! empty( $sale_ids ) ? $sale_ids : array( 0 );
			} elseif ( 'best_selling' === $qtype ) {
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			}
			$products = wc_get_products( $args );
		}

		if ( ! empty( $products ) ) :
			?>
			<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?> avb-wc-products">
				<?php
				foreach ( $products as $product ) :
					$img_id  = $product->get_image_id();
					$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
					?>
					<article class="avb-product-card">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="avb-product-media">
							<?php if ( $product->is_on_sale() ) : ?>
								<span class="avb-product-badge"><?php esc_html_e( 'Sale', 'atelier-visual-builder' ); ?></span>
							<?php endif; ?>
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
						</a>
						<div class="avb-product-body">
							<h3 class="avb-product-title">
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
							</h3>
							<div class="avb-product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
							<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="avb-product-btn">
								<?php echo esc_html( $product->add_to_cart_text() ); ?>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php
		else :
			// Curated showcase fallback when store has no matching products yet.
			$demo_items = array(
				array(
					'title' => 'Kyoto Matte Stoneware Vase',
					'price' => '$145.00',
					'badge' => 'New',
					'img'   => 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=800&q=80',
				),
				array(
					'title' => 'Unbleached Belgian Linen Throw',
					'price' => '$210.00',
					'badge' => '',
					'img'   => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=800&q=80',
				),
				array(
					'title' => 'Brushed Brass Table Lamp',
					'price' => '$380.00',
					'badge' => 'Limited',
					'img'   => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',
				),
				array(
					'title' => 'Hand-Carved Oak Serving Bowl',
					'price' => '$125.00',
					'badge' => '',
					'img'   => 'https://images.unsplash.com/photo-1615529182904-14819c35db37?auto=format&fit=crop&w=800&q=80',
				),
			);
			?>
			<div class="avb-grid avb-cols-<?php echo esc_attr( $cols ); ?> avb-wc-products">
				<?php for ( $i = 0; $i < min( $limit, count( $demo_items ) ); $i++ ) :
					$item = $demo_items[ $i ];
					?>
					<article class="avb-product-card">
						<a href="#shop" class="avb-product-media">
							<?php if ( ! empty( $item['badge'] ) ) : ?>
								<span class="avb-product-badge"><?php echo esc_html( $item['badge'] ); ?></span>
							<?php endif; ?>
							<img src="<?php echo esc_url( $item['img'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" />
						</a>
						<div class="avb-product-body">
							<h3 class="avb-product-title"><a href="#shop"><?php echo esc_html( $item['title'] ); ?></a></h3>
							<div class="avb-product-price"><?php echo esc_html( $item['price'] ); ?></div>
							<a href="#shop" class="avb-product-btn"><?php esc_html_e( 'Add to Cart', 'atelier-visual-builder' ); ?></a>
						</div>
					</article>
				<?php endfor; ?>
			</div>
			<?php
		endif;

		if ( ! empty( $content['show_cta'] ) && ! empty( $content['cta_text'] ) ) :
			?>
			<div class="avb-btn-row" style="justify-content:center;margin-top:36px;">
				<a href="<?php echo esc_url( $content['cta_url'] ?: '/shop' ); ?>" class="avb-btn avb-btn-outline"><?php echo esc_html( $content['cta_text'] ); ?></a>
			</div>
			<?php
		endif;
	}
}
