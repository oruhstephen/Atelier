<?php
/**
 * Title: Hero Collection Showcase
 * Slug: atelier-shop/hero-showcase
 * Categories: featured, banner
 * Description: Editorial hero showcase banner inspired by Bloghash Pro with featured badge, headline, and dual CTA buttons.
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background atelier-hero-showcase" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.12em","fontWeight":"600"}},"textColor":"accent"} -->
			<p class="has-accent-color has-text-color has-small-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase"><?php esc_html_e( 'New Season Editorial', 'atelier-shop' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":1,"fontSize":"x-large"} -->
			<h1 class="wp-block-heading has-x-large-font-size"><?php esc_html_e( 'Timeless silhouettes crafted for modern living.', 'atelier-shop' ); ?></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"muted"} -->
			<p class="has-muted-color has-text-color"><?php esc_html_e( 'Discover sustainably sourced essentials designed with refined textures, effortless drape, and enduring quality.', 'atelier-shop' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Explore Collection', 'atelier-shop' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#featured-categories"><?php esc_html_e( 'Shop by Category', 'atelier-shop' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:group {"backgroundColor":"base","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"6px","width":"1px","color":"var:preset|color|border"}}} -->
			<div class="wp-block-group has-base-background-color has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:6px;padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
				<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'Editor’s Pick', 'atelier-shop' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-large-font-size"><?php esc_html_e( 'Autumn / Winter Capsule', 'atelier-shop' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'Limited-edition knitwear, tailored outerwear, and handcrafted leather accessories.', 'atelier-shop' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
