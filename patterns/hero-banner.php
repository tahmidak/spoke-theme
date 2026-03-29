<?php
/**
 * Title: Hero Banner
 * Slug: spoke-theme/hero-banner
 * Categories: spoke-theme
 * Description: Homepage hero section with headline, subtext and CTA buttons.
 */
?>
<!-- wp:group {"tagName":"section","className":"hero-banner","style":{"background":{"gradient":"linear-gradient(135deg, #1A3C6E 0%, #1A1A2E 100%)"},"spacing":{"padding":{"top":"var(--wp--preset--spacing--12)","bottom":"var(--wp--preset--spacing--12)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group hero-banner">

	<!-- wp:columns {"className":"hero-banner__layout","style":{"spacing":{"blockGap":{"left":"var(--wp--preset--spacing--8)"}}}} -->
	<div class="wp-block-columns hero-banner__layout">

		<!-- wp:column {"width":"55%","className":"hero-banner__content"} -->
		<div class="wp-block-column hero-banner__content" style="flex-basis:55%">

			<!-- wp:html -->
			<div class="hero-banner__badge">
				<span class="hero-badge-icon">✓</span>
				<span>10,000+ UK professionals enrolled</span>
			</div>
			<!-- /wp:html -->

			<!-- wp:heading {"level":1,"textColor":"white","className":"hero-banner__title","style":{"typography":{"fontSize":"clamp(2.25rem,5vw,3.5rem)","fontWeight":"500","lineHeight":"1.1","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var(--wp--preset--spacing--2)","bottom":"var(--wp--preset--spacing--3)"}}}} -->
			<h1 class="wp-block-heading hero-banner__title has-white-color has-text-color" style="font-size:clamp(2.25rem,5vw,3.5rem);font-weight:500;line-height:1.1;letter-spacing:-0.02em;margin-top:var(--wp--preset--spacing--2);margin-bottom:var(--wp--preset--spacing--3);">Upskill for Career Progression with Accredited Online Courses</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hero-banner__sub","style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.7"},"color":{"text":"rgba(255,255,255,0.75)"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--4)"}}}} -->
			<p class="hero-banner__sub" style="font-size:1.125rem;line-height:1.7;color:rgba(255,255,255,0.75);margin-top:0;margin-bottom:var(--wp--preset--spacing--4);">Specialised e-learning platform for UK professionals. Elevate your expertise with industry-recognised certifications.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"blockGap":"var(--wp--preset--spacing--2)"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"accent","textColor":"dark","className":"hero-banner__cta-primary","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var(--wp--preset--spacing--2)","bottom":"var(--wp--preset--spacing--2)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}},"typography":{"fontWeight":"700","fontSize":"1.0625rem"}}} -->
				<div class="wp-block-button hero-banner__cta-primary"><a class="wp-block-button__link has-accent-background-color has-dark-color has-background has-text-color" href="/courses/" style="border-radius:8px;padding:var(--wp--preset--spacing--2) var(--wp--preset--spacing--4);font-weight:700;font-size:1.0625rem;">Browse Courses</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"hero-banner__cta-secondary is-style-outline","style":{"border":{"radius":"8px","color":"rgba(255,255,255,0.3)","width":"2px"},"spacing":{"padding":{"top":"var(--wp--preset--spacing--2)","bottom":"var(--wp--preset--spacing--2)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"color":{"text":"#ffffff"}}} -->
				<div class="wp-block-button hero-banner__cta-secondary is-style-outline"><a class="wp-block-button__link has-white-color has-text-color" href="/about/" style="border-radius:8px;border:2px solid rgba(255,255,255,0.3);padding:var(--wp--preset--spacing--2) var(--wp--preset--spacing--4);font-weight:700;font-size:1.0625rem;background:transparent;">View Accreditation</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"45%","className":"hero-banner__image-col"} -->
		<div class="wp-block-column hero-banner__image-col" style="flex-basis:45%">
			<!-- wp:html -->
			<div class="hero-banner__image-wrap">
				<div class="hero-banner__card">
					<div class="hero-banner__card-icon">🎓</div>
					<div class="hero-banner__card-info">
						<div class="hero-banner__card-label">New Course</div>
						<div class="hero-banner__card-title">Project Leadership</div>
					</div>
					<div class="hero-banner__progress-bar">
						<div class="hero-banner__progress-fill"></div>
					</div>
				</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</section>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.hero-banner__badge {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	background: rgba(255,255,255,0.1);
	border: 1px solid rgba(255,255,255,0.15);
	border-radius: 100px;
	padding: 6px 16px;
	font-size: 0.875rem;
	font-weight: 600;
	color: rgba(255,255,255,0.9);
	margin-bottom: var(--wp--preset--spacing--2);
}
.hero-badge-icon {
	color: var(--wp--preset--color--accent);
	font-weight: 700;
}
.hero-banner__image-wrap {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: var(--wp--preset--spacing--4);
}
.hero-banner__card {
	background: var(--wp--preset--color--white);
	border-radius: 12px;
	padding: var(--wp--preset--spacing--3);
	box-shadow: 0 20px 50px rgba(0,0,0,0.2);
	max-width: 280px;
}
.hero-banner__card-icon {
	font-size: 2rem;
	margin-bottom: var(--wp--preset--spacing--1);
}
.hero-banner__card-label {
	font-size: 0.75rem;
	font-weight: 700;
	color: var(--wp--preset--color--primary);
	text-transform: uppercase;
	letter-spacing: 0.08em;
}
.hero-banner__card-title {
	font-size: 1rem;
	font-weight: 500;
	color: var(--wp--preset--color--on-surface-variant);
	margin-bottom: var(--wp--preset--spacing--2);
	font-style: italic;
}
.hero-banner__progress-bar {
	height: 8px;
	background: var(--wp--preset--color--surface);
	border-radius: 4px;
	overflow: hidden;
}
.hero-banner__progress-fill {
	width: 75%;
	height: 100%;
	background: var(--wp--preset--color--accent);
	border-radius: 4px;
}
@media (max-width: 768px) {
	.hero-banner__image-col { display: none !important; }
	.hero-banner__layout { flex-direction: column !important; }
}
</style>
<!-- /wp:html -->