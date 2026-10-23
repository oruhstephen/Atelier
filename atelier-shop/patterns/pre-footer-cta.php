<?php
/**
 * Title: Pre-Footer Call to Action
 * Slug: atelier-shop/pre-footer-cta
 * Categories: featured, call-to-action
 * Description: Pre-footer promotional banner ported from Bloghash Pro pre-footer CTA.
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"accent","textColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull has-base-color has-accent-background-color has-text-color has-background atelier-pre-footer-cta" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":3,"fontSize":"large","textColor":"base"} -->
		<h3 class="wp-block-heading has-base-color has-text-color has-large-font-size"><?php esc_html_e( 'Join the Atelier Circle & enjoy 10% off your first order.', 'atelier-shop' ); ?></h3>
		<!-- /wp:heading -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"base","textColor":"contrast"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-contrast-color has-base-background-color has-text-color has-background wp-element-button" href="/my-account/"><?php esc_html_e( 'Create Account', 'atelier-shop' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
