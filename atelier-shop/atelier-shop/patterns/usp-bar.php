<?php
/**
 * Title: USP bar
 * Slug: atelier-shop/usp-bar
 * Categories: featured
 * Description: Three short trust messages (delivery, returns, support).
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size"><?php esc_html_e( 'Free delivery over £50', 'atelier-shop' ); ?></p><!-- /wp:paragraph --></div><!-- /wp:column -->
		<!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size"><?php esc_html_e( '30-day returns', 'atelier-shop' ); ?></p><!-- /wp:paragraph --></div><!-- /wp:column -->
		<!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"align":"center","fontSize":"small"} --><p class="has-text-align-center has-small-font-size"><?php esc_html_e( 'Secure checkout', 'atelier-shop' ); ?></p><!-- /wp:paragraph --></div><!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
