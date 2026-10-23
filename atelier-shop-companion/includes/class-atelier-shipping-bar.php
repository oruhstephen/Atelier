<?php
/**
 * Atelier Companion Free Shipping Progress Bar Module.
 *
 * Displays a dynamic free shipping goal progress bar on Cart, Checkout,
 * Mini-Cart block, and via the [atelier_shipping_bar] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Shipping_Bar {

	/**
	 * Initialize Free Shipping Bar hooks.
	 */
	public static function init(): void {
		// Classic & hybrid WooCommerce cart/checkout hooks.
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'output_bar' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'output_bar' ), 5 );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( __CLASS__, 'output_bar' ), 5 );

		// Block-based Cart & Checkout compatibility via render_block filters.
		add_filter( 'render_block_woocommerce/cart', array( __CLASS__, 'prepend_to_block' ), 10, 1 );
		add_filter( 'render_block_woocommerce/checkout', array( __CLASS__, 'prepend_to_block' ), 10, 1 );

		// Refresh progress bar automatically on WooCommerce AJAX cart fragments.
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'register_cart_fragment' ) );

		// Shortcode [atelier_shipping_bar].
		add_shortcode( 'atelier_shipping_bar', array( __CLASS__, 'get_bar_html' ) );
	}

	/**
	 * Calculate current cart subtotal and free shipping goal status.
	 *
	 * @return array{goal: float, subtotal: float, remaining: float, percent: int, unlocked: bool}
	 */
	public static function get_progress_data(): array {
		$goal     = (float) Atelier_Companion_Settings::get( 'free_shipping_threshold', 50.0 );
		$subtotal = 0.0;

		if ( function_exists( 'WC' ) && WC()->cart ) {
			$subtotal = (float) WC()->cart->get_displayed_subtotal();
			$discount = (float) WC()->cart->get_discount_total();
			if ( WC()->cart->display_prices_including_tax() ) {
				$discount += (float) WC()->cart->get_discount_tax();
			}
			$subtotal = max( 0.0, $subtotal - $discount );
		}

		$remaining = max( 0.0, $goal - $subtotal );
		$percent   = $goal > 0 ? (int) min( 100, round( ( $subtotal / $goal ) * 100 ) ) : 100;
		$unlocked  = $remaining <= 0.001;

		return array(
			'goal'      => $goal,
			'subtotal'  => $subtotal,
			'remaining' => $remaining,
			'percent'   => $percent,
			'unlocked'  => $unlocked,
		);
	}

	/**
	 * Build the Free Shipping Progress Bar HTML.
	 *
	 * @return string
	 */
	public static function get_bar_html(): string {
		$data = self::get_progress_data();
		if ( $data['goal'] <= 0 ) {
			return '';
		}

		if ( $data['unlocked'] ) {
			$message = (string) Atelier_Companion_Settings::get(
				'shipping_bar_Complete_text',
				__( 'You have unlocked free standard delivery!', 'atelier-companion' )
			);
		} else {
			$template        = (string) Atelier_Companion_Settings::get(
				'shipping_bar_initial_text',
				__( 'Spend {remaining} more to unlock free delivery', 'atelier-companion' )
			);
			$formatted_price = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $data['remaining'] ) ) : '£' . number_format( $data['remaining'], 2 );
			$message         = str_replace( '{remaining}', '<strong>' . esc_html( $formatted_price ) . '</strong>', esc_html( $template ) );
		}

		$state_class = $data['unlocked'] ? ' is-unlocked' : '';

		ob_start();
		?>
		<div class="atelier-shipping-bar<?php echo esc_attr( $state_class ); ?>" data-atelier-shipping-bar data-goal="<?php echo esc_attr( (string) $data['goal'] ); ?>">
			<div class="atelier-shipping-bar__header">
				<svg class="atelier-shipping-bar__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<rect x="1" y="3" width="15" height="13"></rect>
					<polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
					<circle cx="5.5" cy="18.5" r="2.5"></circle>
					<circle cx="18.5" cy="18.5" r="2.5"></circle>
				</svg>
				<p class="atelier-shipping-bar__text"><?php echo wp_kses_post( $message ); ?></p>
			</div>
			<div class="atelier-shipping-bar__track" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $data['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100">
				<div class="atelier-shipping-bar__fill" style="width: <?php echo esc_attr( (string) $data['percent'] ); ?>%;"></div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Echo the Free Shipping Progress Bar.
	 */
	public static function output_bar(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_bar_html();
	}

	/**
	 * Prepend the Free Shipping Progress Bar to WooCommerce Cart/Checkout blocks.
	 *
	 * @param string $block_content Block HTML.
	 * @return string
	 */
	public static function prepend_to_block( string $block_content ): string {
		if ( str_contains( $block_content, 'atelier-shipping-bar' ) ) {
			return $block_content;
		}
		return self::get_bar_html() . $block_content;
	}

	/**
	 * Register WooCommerce AJAX cart fragment so the shipping bar updates live.
	 *
	 * @param array<string, string> $fragments Cart fragments.
	 * @return array<string, string>
	 */
	public static function register_cart_fragment( array $fragments ): array {
		$fragments['[data-atelier-shipping-bar]'] = self::get_bar_html();
		return $fragments;
	}
}
