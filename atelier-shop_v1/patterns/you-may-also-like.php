<?php
/**
 * Title: You May Also Like (Editorial & Products)
 * Slug: atelier-shop/you-may-also-like
 * Categories: featured
 * Description: 4-column "You May Also Like" showcase section ported from Bloghash Pro PYML.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignwide atelier-pyml-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"align":"wide","fontSize":"large"} -->
	<h2 class="wp-block-heading alignwide has-large-font-size"><?php esc_html_e( 'You May Also Like', 'atelier-shop' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:query {"queryId":2,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide"} -->
	<div class="wp-block-query alignwide">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":4}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->
		<!-- wp:post-terms {"term":"category","fontSize":"small"} /-->
		<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
		<!-- wp:post-date {"fontSize":"small","textColor":"muted"} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
