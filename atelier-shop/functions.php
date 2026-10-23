<?php
/**
 * Atelier Shop functions.
 * Keep this file small. Features (wishlist, swatches, etc.) belong in a companion plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_editor_style( 'assets/css/theme.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'atelier-shop',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	// Output any Customizer color or layout overrides dynamically.
	$custom_css = atelier_shop_get_customizer_css();
	if ( $custom_css ) {
		wp_add_inline_style( 'atelier-shop', $custom_css );
	}
} );

/**
 * Enable the WordPress Customizer (Appearance → Customize) alongside the Site Editor
 * and register Atelier Shop theme sections (Colors, Layout & Header).
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {

	// 1. Panel: Atelier Shop Options
	$wp_customize->add_panel( 'atelier_shop_panel', array(
		'title'       => __( 'Atelier Shop Options', 'atelier-shop' ),
		'description' => __( 'Customize Atelier Shop colors, layout, header, and e-commerce features.', 'atelier-shop' ),
		'priority'    => 25,
	) );

	// 2. Section: Theme Colors
	$wp_customize->add_section( 'atelier_shop_colors', array(
		'title'    => __( 'Color Palette', 'atelier-shop' ),
		'panel'    => 'atelier_shop_panel',
		'priority' => 10,
	) );

	$palette_defaults = array(
		'base'     => array( __( 'Base Background', 'atelier-shop' ), '#ffffff' ),
		'surface'  => array( __( 'Surface Background', 'atelier-shop' ), '#f6f5f3' ),
		'contrast' => array( __( 'Primary Text (Contrast)', 'atelier-shop' ), '#1c1c1c' ),
		'muted'    => array( __( 'Muted Text', 'atelier-shop' ), '#5f5f5f' ),
		'border'   => array( __( 'Border Color', 'atelier-shop' ), '#e3e1dd' ),
		'accent'   => array( __( 'Accent / Brand Color', 'atelier-shop' ), '#1f3a5f' ),
		'sale'     => array( __( 'Sale / Alert Color', 'atelier-shop' ), '#b3261e' ),
	);

	foreach ( $palette_defaults as $slug => $data ) {
		$setting_id = "atelier_color_{$slug}";
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $data[1],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control(
			$wp_customize,
			$setting_id,
			array(
				'label'   => $data[0],
				'section' => 'atelier_shop_colors',
			)
		) );
	}

	// 3. Section: Layout & Container
	$wp_customize->add_section( 'atelier_shop_layout', array(
		'title'    => __( 'Layout & Container', 'atelier-shop' ),
		'panel'    => 'atelier_shop_panel',
		'priority' => 20,
	) );

	$wp_customize->add_setting( 'atelier_wide_size', array(
		'default'           => 1280,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'atelier_wide_size', array(
		'label'       => __( 'Wide Container Max Width (px)', 'atelier-shop' ),
		'section'     => 'atelier_shop_layout',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 960, 'max' => 1680, 'step' => 10 ),
	) );

	$wp_customize->add_setting( 'atelier_button_radius', array(
		'default'           => 4,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );

	$wp_customize->add_control( 'atelier_button_radius', array(
		'label'       => __( 'Button Corner Radius (px)', 'atelier-shop' ),
		'section'     => 'atelier_shop_layout',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0, 'max' => 40, 'step' => 1 ),
	) );
} );

/**
 * Build inline CSS from Customizer theme_mod values when customized.
 *
 * @return string
 */
function atelier_shop_get_customizer_css(): string {
	$defaults = array(
		'base'     => '#ffffff',
		'surface'  => '#f6f5f3',
		'contrast' => '#1c1c1c',
		'muted'    => '#5f5f5f',
		'border'   => '#e3e1dd',
		'accent'   => '#1f3a5f',
		'sale'     => '#b3261e',
	);

	$vars = array();
	foreach ( $defaults as $slug => $default_hex ) {
		$val = get_theme_mod( "atelier_color_{$slug}", $default_hex );
		if ( $val && strtolower( $val ) !== strtolower( $default_hex ) ) {
			$vars[] = "--wp--preset--color--{$slug}: " . esc_attr( $val ) . ';';
		}
	}

	$wide_size = (int) get_theme_mod( 'atelier_wide_size', 1280 );
	if ( $wide_size && 1280 !== $wide_size ) {
		$vars[] = '--wp--style--global--wide-size: ' . $wide_size . 'px;';
	}

	$css = '';
	if ( ! empty( $vars ) ) {
		$css .= 'html:not([data-darkmode="dark"]) body {' . implode( ' ', $vars ) . '}';
	}

	$btn_radius = (int) get_theme_mod( 'atelier_button_radius', 4 );
	if ( 4 !== $btn_radius ) {
		$css .= '.wp-element-button, .wp-block-button__link, button.button, a.button { border-radius: ' . $btn_radius . 'px !important; }';
	}

	return $css;
}
