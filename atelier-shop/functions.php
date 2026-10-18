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
	add_editor_style( 'assets/css/theme.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'atelier-shop',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
} );
