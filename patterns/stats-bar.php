<?php
/**
 * Title: Stats Bar
 * Slug: spoke-theme/stats-bar
 * Categories: spoke-theme
 * Description: Key statistics bar shown below hero section.
 */
?>
<!-- wp:group {"tagName":"section","className":"stats-bar","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var(--wp--preset--spacing--6)","bottom":"var(--wp--preset--spacing--6)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}},"border":{"radius":"12px"},"shadow":"var(--wp--preset--shadow--card)"},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group stats-bar has-white-background-color has-background">

	<!-- wp:columns {"className":"stats-bar__grid","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns stats-bar__grid">

		<!-- wp:column {"className":"stats-bar__item"} -->
		<div class="wp-block-column stats-bar__item">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"2rem","fontWeight":"500"},"spacing":{"margin":{"top":"0","bottom":"4px"}}}} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:2rem;font-weight:500;margin-top:0;margin-bottom:4px;">150+</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"0.875rem","fontWeight":"500"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p class="has-text-align-center" style="font-size:0.875rem;font-weight:500;color:var(--wp--preset--color--on-surface-variant);margin:0;">Total Courses</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"stats-bar__item stats-bar__item--divided"} -->
		<div class="wp-block-column stats-bar__item stats-bar__item--divided">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"2rem","fontWeight":"500"},"spacing":{"margin":{"top":"0","bottom":"4px"}}}} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:2rem;font-weight:500;margin-top:0;margin-bottom:4px;">10,000+</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"0.875rem","fontWeight":"500"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p class="has-text-align-center" style="font-size:0.875rem;font-weight:500;color:var(--wp--preset--color--on-surface-variant);margin:0;">Students Enrolled</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"stats-bar__item stats-bar__item--divided"} -->
		<div class="wp-block-column stats-bar__item stats-bar__item--divided">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"2rem","fontWeight":"500"},"spacing":{"margin":{"top":"0","bottom":"4px"}}}} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:2rem;font-weight:500;margin-top:0;margin-bottom:4px;">8,500+</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"0.875rem","fontWeight":"500"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p class="has-text-align-center" style="font-size:0.875rem;font-weight:500;color:var(--wp--preset--color--on-surface-variant);margin:0;">Certified Graduates</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"stats-bar__item stats-bar__item--divided"} -->
		<div class="wp-block-column stats-bar__item stats-bar__item--divided">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"2rem","fontWeight":"500"},"spacing":{"margin":{"top":"0","bottom":"4px"}}}} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:2rem;font-weight:500;margin-top:0;margin-bottom:4px;">4.9/5 ⭐</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"0.875rem","fontWeight":"500"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p class="has-text-align-center" style="font-size:0.875rem;font-weight:500;color:var(--wp--preset--color--on-surface-variant);margin:0;">Average Rating</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</section>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.stats-bar {
	margin-left: var(--wp--preset--spacing--4);
	margin-right: var(--wp--preset--spacing--4);
	margin-top: calc(-1 * var(--wp--preset--spacing--4));
	position: relative;
	z-index: 10;
}
.stats-bar__item {
	text-align: center;
	padding: 0 var(--wp--preset--spacing--3);
}
.stats-bar__item--divided {
	border-left: 1px solid rgba(0,0,0,0.08);
}
@media (max-width: 768px) {
	.stats-bar__item--divided { border-left: none; border-top: 1px solid rgba(0,0,0,0.08); }
	.stats-bar { margin-top: 0; }
}
</style>
<!-- /wp:html -->