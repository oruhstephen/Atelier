<?php
/**
 * Title: Featured Category Links
 * Slug: atelier-shop/featured-links
 * Categories: featured
 * Description: Three interactive featured category cards ported from Bloghash Pro Featured Links.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div id="featured-categories" class="wp-block-group alignwide atelier-featured-links" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30","top":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"backgroundColor":"surface","className":"atelier-featured-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"6px","width":"1px","color":"var:preset|color|border"}}} -->
			<div class="wp-block-group atelier-featured-card has-surface-background-color has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:6px;padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
				<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-large-font-size"><a href="/shop/"><?php esc_html_e( 'New Arrivals', 'atelier-shop' ); ?></a></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'Fresh seasonal releases →', 'atelier-shop' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"backgroundColor":"surface","className":"atelier-featured-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"6px","width":"1px","color":"var:preset|color|border"}}} -->
			<div class="wp-block-group atelier-featured-card has-surface-background-color has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:6px;padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
				<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-large-font-size"><a href="/shop/"><?php esc_html_e( 'Editor’s Choice', 'atelier-shop' ); ?></a></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'Most-loved signature pieces →', 'atelier-shop' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"backgroundColor":"surface","className":"atelier-featured-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"6px","width":"1px","color":"var:preset|color|border"}}} -->
			<div class="wp-block-group atelier-featured-card has-surface-background-color has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:6px;padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
				<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
				<h3 class="wp-block-heading has-text-align-center has-large-font-size"><a href="/shop/"><?php esc_html_e( 'Archive & Sale', 'atelier-shop' ); ?></a></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"muted"} -->
				<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'Up to 30% off past seasons →', 'atelier-shop' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
