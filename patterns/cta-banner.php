<?php
/**
 * Title: CTA Banner
 * Slug: spoke-theme/cta-banner
 * Categories: spoke-theme
 * Description: Full-width call-to-action banner for course browsing.
 */
?>
<!-- wp:group {"tagName":"section","className":"cta-banner","backgroundColor":"primary","textColor":"white","style":{"spacing":{"padding":{"top":"var(--wp--preset--spacing--8)","bottom":"var(--wp--preset--spacing--8)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group cta-banner has-primary-background-color has-white-color has-background has-text-color">

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","alignItems":"center","gap":"var(--wp--preset--spacing--4)"}} -->
	<div class="wp-block-group">

		<!-- wp:group {"layout":{"type":"constrained","contentSize":"640px"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.5rem,3vw,2rem)","fontWeight":"500","lineHeight":"1.2"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
			<h2 class="wp-block-heading has-white-color has-text-color" style="font-size:clamp(1.5rem,3vw,2rem);font-weight:500;line-height:1.2;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">Ready to advance your career?</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"color":{"text":"rgba(255,255,255,0.75)"},"typography":{"fontSize":"1rem"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p style="color:rgba(255,255,255,0.75);font-size:1rem;margin-top:0;margin-bottom:0;">Browse 150+ accredited courses designed for UK professionals. Start learning today.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"accent","textColor":"dark","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var(--wp--preset--spacing--2)","bottom":"var(--wp--preset--spacing--2)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}},"typography":{"fontWeight":"700","fontSize":"1.0625rem"}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-accent-background-color has-dark-color has-background has-text-color" href="/courses/" style="border-radius:8px;padding:var(--wp--preset--spacing--2) var(--wp--preset--spacing--4);font-weight:700;font-size:1.0625rem;">Browse All Courses</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

	</div>
	<!-- /wp:group -->

</section>
<!-- /wp:group -->