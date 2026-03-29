<?php
/**
 * Title: How It Works
 * Slug: spoke-theme/how-it-works
 * Categories: spoke-theme
 * Description: Three-step path to mastery section.
 */
?>
<!-- wp:group {"tagName":"section","className":"how-it-works","style":{"spacing":{"padding":{"top":"var(--wp--preset--spacing--8)","bottom":"var(--wp--preset--spacing--8)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group how-it-works">

	<!-- wp:group {"className":"how-it-works__header","style":{"spacing":{"margin":{"bottom":"var(--wp--preset--spacing--6)"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","flexDirection":"column","alignItems":"center"}} -->
	<div class="wp-block-group how-it-works__header" style="margin-bottom:var(--wp--preset--spacing--6);">
		<!-- wp:heading {"level":2,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"clamp(1.75rem,3vw,2.5rem)","fontWeight":"500","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:500;letter-spacing:-0.02em;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">Your Path to Mastery</h2>
		<!-- /wp:heading -->
		<!-- wp:html -->
		<div class="how-it-works__divider"></div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"how-it-works__steps","style":{"spacing":{"blockGap":{"left":"var(--wp--preset--spacing--6)"}}}} -->
	<div class="wp-block-columns how-it-works__steps">

		<!-- wp:column {"className":"how-it-works__step"} -->
		<div class="wp-block-column how-it-works__step">
			<!-- wp:html -->
			<div class="step-icon">🔍</div>
			<!-- /wp:html -->
			<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.125rem","fontWeight":"700"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
			<h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.125rem;font-weight:700;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">1. Browse our accredited catalog</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p style="font-size:0.9375rem;line-height:1.7;color:var(--wp--preset--color--on-surface-variant);margin:0;">Discover a curated selection of courses tailored for the British job market and international standards.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"how-it-works__step"} -->
		<div class="wp-block-column how-it-works__step">
			<!-- wp:html -->
			<div class="step-icon">✏️</div>
			<!-- /wp:html -->
			<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.125rem","fontWeight":"700"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
			<h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.125rem;font-weight:700;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">2. Enrol in your professional course</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p style="font-size:0.9375rem;line-height:1.7;color:var(--wp--preset--color--on-surface-variant);margin:0;">Simple onboarding process designed for busy professionals. Immediate access to all learning materials.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"how-it-works__step"} -->
		<div class="wp-block-column how-it-works__step">
			<!-- wp:html -->
			<div class="step-icon">🏆</div>
			<!-- /wp:html -->
			<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.125rem","fontWeight":"700"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
			<h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.125rem;font-weight:700;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">3. Get certified for career growth</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"},"color":{"text":"var(--wp--preset--color--on-surface-variant)"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
			<p style="font-size:0.9375rem;line-height:1.7;color:var(--wp--preset--color--on-surface-variant);margin:0;">Earn industry-recognised credentials that validate your expertise and open doors to new opportunities.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</section>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.how-it-works__divider {
	width: 64px;
	height: 4px;
	background: var(--wp--preset--color--accent);
	border-radius: 2px;
}
.how-it-works__step {
	padding: var(--wp--preset--spacing--3);
	border-radius: 12px;
	transition: background 200ms ease, box-shadow 200ms ease;
}
.how-it-works__step:hover {
	background: var(--wp--preset--color--white);
	box-shadow: var(--wp--preset--shadow--card-hover);
}
.step-icon {
	width: 64px;
	height: 64px;
	background: var(--wp--preset--color--light);
	border-radius: 8px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 1.75rem;
	margin-bottom: var(--wp--preset--spacing--2);
	transition: background 200ms ease;
}
.how-it-works__step:hover .step-icon {
	background: var(--wp--preset--color--primary);
}
</style>
<!-- /wp:html -->