<?php
/**
 * Atelier Companion Variation Swatches Module.
 *
 * Adds color picker and image swatch fields to WooCommerce product attributes
 * in WP Admin and transforms frontend variable product dropdowns into
 * accessible, interactive color, image, or pill swatches.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atelier_Companion_Swatches {

	public const COLOR_META = 'atelier_swatch_color';
	public const IMAGE_META = 'atelier_swatch_image';

	/**
	 * Initialize swatch hooks.
	 */
	public static function init(): void {
		// Enhance variable product attribute dropdown HTML on frontend.
		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( __CLASS__, 'render_swatches_html' ), 20, 2 );

		// Register term meta fields on all product attribute taxonomies in admin.
		add_action( 'admin_init', array( __CLASS__, 'register_attribute_taxonomy_hooks' ) );
	}

	/**
	 * Attach term meta form fields & save handlers to every WooCommerce attribute taxonomy.
	 */
	public static function register_attribute_taxonomy_hooks(): void {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( empty( $attribute_taxonomies ) ) {
			return;
		}

		foreach ( $attribute_taxonomies as $tax ) {
			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
			add_action( "{$taxonomy}_add_form_fields", array( __CLASS__, 'add_term_fields' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'edit_term_fields' ), 10, 2 );
			add_action( "created_{$taxonomy}", array( __CLASS__, 'save_term_fields' ) );
			add_action( "edited_{$taxonomy}", array( __CLASS__, 'save_term_fields' ) );
		}
	}

	/**
	 * Render swatch fields on the "Add New Term" screen.
	 */
	public static function add_term_fields(): void {
		wp_nonce_field( 'atelier_save_swatch_meta', 'atelier_swatch_nonce' );
		?>
		<div class="form-field">
			<label for="atelier_swatch_color"><?php esc_html_e( 'Swatch Color (Hex)', 'atelier-companion' ); ?></label>
			<input type="text" name="atelier_swatch_color" id="atelier_swatch_color" value="" class="atelier-color-picker" />
			<p class="description"><?php esc_html_e( 'Optional hex color for color swatches (e.g., #1f3a5f).', 'atelier-companion' ); ?></p>
		</div>
		<div class="form-field">
			<label for="atelier_swatch_image"><?php esc_html_e( 'Swatch Image URL', 'atelier-companion' ); ?></label>
			<input type="url" name="atelier_swatch_image" id="atelier_swatch_image" value="" placeholder="https://" />
			<button type="button" class="button atelier-upload-swatch-image"><?php esc_html_e( 'Choose Image', 'atelier-companion' ); ?></button>
			<p class="description"><?php esc_html_e( 'Optional texture/pattern thumbnail URL for image swatches.', 'atelier-companion' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render swatch fields on the "Edit Term" screen.
	 *
	 * @param WP_Term $term Current term object.
	 */
	public static function edit_term_fields( WP_Term $term ): void {
		$color = (string) get_term_meta( $term->term_id, self::COLOR_META, true );
		$image = (string) get_term_meta( $term->term_id, self::IMAGE_META, true );
		wp_nonce_field( 'atelier_save_swatch_meta', 'atelier_swatch_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="atelier_swatch_color"><?php esc_html_e( 'Swatch Color (Hex)', 'atelier-companion' ); ?></label></th>
			<td>
				<input type="text" name="atelier_swatch_color" id="atelier_swatch_color" value="<?php echo esc_attr( $color ); ?>" class="atelier-color-picker" />
				<p class="description"><?php esc_html_e( 'Pick a color to display this term as a color swatch.', 'atelier-companion' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="atelier_swatch_image"><?php esc_html_e( 'Swatch Image URL', 'atelier-companion' ); ?></label></th>
			<td>
				<input type="url" name="atelier_swatch_image" id="atelier_swatch_image" value="<?php echo esc_url( $image ); ?>" class="regular-text" />
				<button type="button" class="button atelier-upload-swatch-image"><?php esc_html_e( 'Choose Image', 'atelier-companion' ); ?></button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term swatch metadata.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function save_term_fields( int $term_id ): void {
		if (
			! isset( $_POST['atelier_swatch_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['atelier_swatch_nonce'] ) ), 'atelier_save_swatch_meta' )
		) {
			return;
		}

		if ( isset( $_POST['atelier_swatch_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['atelier_swatch_color'] ) );
			update_term_meta( $term_id, self::COLOR_META, (string) $color );
		}

		if ( isset( $_POST['atelier_swatch_image'] ) ) {
			$image = esc_url_raw( wp_unslash( $_POST['atelier_swatch_image'] ) );
			update_term_meta( $term_id, self::IMAGE_META, $image );
		}
	}

	/**
	 * Guess a fallback CSS color for common color names when no explicit hex meta is set on a color attribute.
	 *
	 * @param string $slug Term slug or name.
	 * @return string
	 */
	private static function fallback_color_map( string $slug ): string {
		$map = array(
			'black'    => '#1c1c1c',
			'white'    => '#ffffff',
			'ivory'    => '#fffff0',
			'cream'    => '#f6f5f3',
			'beige'    => '#e5dccb',
			'sand'     => '#d8cab8',
			'stone'    => '#b8b2a6',
			'grey'     => '#808080',
			'gray'     => '#808080',
			'charcoal' => '#36454f',
			'navy'     => '#1f3a5f',
			'blue'     => '#2b5b84',
			'sky'      => '#87ceeb',
			'green'    => '#3b5d4f',
			'olive'    => '#556b2f',
			'sage'     => '#9caf88',
			'red'      => '#b3261e',
			'burgundy' => '#6b1d2f',
			'rust'     => '#a44a28',
			'terracotta' => '#c86548',
			'pink'     => '#e6b8b7',
			'blush'    => '#de98ab',
			'yellow'   => '#e3b448',
			'mustard'  => '#c89628',
			'brown'    => '#654321',
			'camel'    => '#c19a6b',
			'tan'      => '#d2b48c',
		);

		$normalized = strtolower( trim( $slug ) );
		return $map[ $normalized ] ?? '';
	}

	/**
	 * Append interactive swatch buttons after the standard variation <select> element.
	 *
	 * @param string               $htmlExisting Existing dropdown HTML.
	 * @param array<string, mixed> $args         Dropdown arguments.
	 * @return string
	 */
	public static function render_swatches_html( string $htmlExisting, array $args ): string {
		$options   = $args['options'] ?? array();
		$product   = $args['product'] ?? null;
		$attribute = $args['attribute'] ?? '';
		$selected  = $args['selected'] ?? '';

		if ( empty( $options ) || ! ( $product instanceof WC_Product ) ) {
			return $htmlExisting;
		}

		$shape       = (string) Atelier_Companion_Settings::get( 'swatch_shape', 'circle' );
		$size        = (int) Atelier_Companion_Settings::get( 'swatch_size', 32 );
		$is_color_attr = str_contains( strtolower( $attribute ), 'color' ) || str_contains( strtolower( $attribute ), 'colour' );

		$swatches_items = '';

		if ( taxonomy_exists( $attribute ) ) {
			$terms = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array( 'fields' => 'all' )
			);

			foreach ( $terms as $term ) {
				if ( ! in_array( $term->slug, $options, true ) ) {
					continue;
				}
				$color = (string) get_term_meta( $term->term_id, self::COLOR_META, true );
				$image = (string) get_term_meta( $term->term_id, self::IMAGE_META, true );

				if ( ! $color && $is_color_attr ) {
					$color = self::fallback_color_map( $term->slug );
				}

				$swatches_items .= self::build_single_swatch(
					$term->slug,
					$term->name,
					$selected === $term->slug,
					$color,
					$image,
					$size
				);
			}
		} else {
			foreach ( $options as $option ) {
				$color = $is_color_attr ? self::fallback_color_map( (string) $option ) : '';
				$swatches_items .= self::build_single_swatch(
					(string) $option,
					(string) $option,
					(string) $selected === (string) $option,
					$color,
					'',
					$size
				);
			}
		}

		if ( ! $swatches_items ) {
			return $htmlExisting;
		}

		$wrapper = sprintf(
			'<div class="atelier-swatches-wrap" data-attribute_name="attribute_%1$s">%2$s<div class="atelier-swatches atelier-swatches--%3$s" role="radiogroup" aria-label="%4$s">%5$s</div></div>',
			esc_attr( sanitize_title( $attribute ) ),
			$htmlExisting,
			esc_attr( $shape ),
			esc_attr( wc_attribute_label( $attribute, $product ) ),
			$swatches_items
		);

		return $wrapper;
	}

	/**
	 * Build a single swatch button HTML element.
	 *
	 * @param string $value       Option value/slug.
	 * @param string $label       Human-readable label.
	 * @param bool   $is_selected Whether currently selected.
	 * @param string $color       Hex color if applicable.
	 * @param string $image       Image URL if applicable.
	 * @param int    $size        Swatch pixel dimension.
	 * @return string
	 */
	private static function build_single_swatch(
		string $value,
		string $label,
		bool $is_selected,
		string $color,
		string $image,
		int $size
	): string {
		$type_class = 'atelier-swatch--label';
		$style      = '';
		$inner      = '<span class="atelier-swatch__text">' . esc_html( $label ) . '</span>';

		if ( $image ) {
			$type_class = 'atelier-swatch--image';
			$style      = sprintf( 'width:%1$dpx;height:%1$dpx;background-image:url(%2$s);', $size, esc_url( $image ) );
			$inner      = '<span class="screen-reader-text">' . esc_html( $label ) . '</span>';
		} elseif ( $color ) {
			$type_class = 'atelier-swatch--color';
			$style      = sprintf( 'width:%1$dpx;height:%1$dpx;background-color:%2$s;', $size, esc_attr( $color ) );
			$inner      = '<span class="screen-reader-text">' . esc_html( $label ) . '</span>';
		}

		$active_class = $is_selected ? ' is-selected' : '';

		return sprintf(
			'<button type="button" class="atelier-swatch %1$s%2$s" data-value="%3$s" role="radio" aria-checked="%4$s" title="%5$s" style="%6$s">%7$s</button>',
			esc_attr( $type_class ),
			esc_attr( $active_class ),
			esc_attr( $value ),
			$is_selected ? 'true' : 'false',
			esc_attr( $label ),
			$style,
			$inner
		);
	}
}
