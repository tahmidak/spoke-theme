<?php
/**
 * Title: Testimonials Row
 * Slug: spoke-theme/testimonials-row
 * Categories: spoke-theme
 * Description: Three student testimonial cards.
 */
?>
<!-- wp:group {"tagName":"section","className":"testimonials","style":{"spacing":{"padding":{"top":"var(--wp--preset--spacing--8)","bottom":"var(--wp--preset--spacing--8)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group testimonials">

	<!-- wp:group {"className":"testimonials__header","style":{"spacing":{"margin":{"bottom":"var(--wp--preset--spacing--6)"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","flexDirection":"column","alignItems":"center"}} -->
	<div class="wp-block-group testimonials__header" style="margin-bottom:var(--wp--preset--spacing--6);">
		<!-- wp:heading {"level":2,"textAlign":"center","textColor":"primary","style":{"typography":{"fontSize":"clamp(1.75rem,3vw,2.5rem)","fontWeight":"500","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"0","bottom":"var(--wp--preset--spacing--2)"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:500;letter-spacing:-0.02em;margin-top:0;margin-bottom:var(--wp--preset--spacing--2);">What our professional students say</h2>
		<!-- /wp:heading -->
		<!-- wp:html -->
		<div class="testimonials__divider"></div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"testimonials__grid","style":{"spacing":{"blockGap":{"left":"var(--wp--preset--spacing--4)"}}}} -->
	<div class="wp-block-columns testimonials__grid">

		<!-- Testimonial 1 -->
		<!-- wp:column {"className":"testimonial-card"} -->
		<div class="wp-block-column testimonial-card">
			<!-- wp:html -->
			<div class="testimonial-card__inner">
				<div class="testimonial-card__header">
					<div class="testimonial-avatar">JW</div>
					<div>
						<div class="testimonial-name">James Whitaker</div>
						<div class="testimonial-role">Senior Project Manager</div>
					</div>
				</div>
				<div class="testimonial-stars">★★★★★</div>
				<blockquote class="testimonial-quote">"The curriculum was incredibly thorough. Within three months of completing the Strategic Management course, I secured a promotion to Senior level. Truly a career-changing platform."</blockquote>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- Testimonial 2 -->
		<!-- wp:column {"className":"testimonial-card"} -->
		<div class="wp-block-column testimonial-card">
			<!-- wp:html -->
			<div class="testimonial-card__inner">
				<div class="testimonial-card__header">
					<div class="testimonial-avatar">EC</div>
					<div>
						<div class="testimonial-name">Emily Chen</div>
						<div class="testimonial-role">Operations Lead</div>
					</div>
				</div>
				<div class="testimonial-stars">★★★★★</div>
				<blockquote class="testimonial-quote">"As a busy professional, I needed flexibility without sacrificing quality. StudyMate Central provided both. The accreditation is respected everywhere in the UK market."</blockquote>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- Testimonial 3 -->
		<!-- wp:column {"className":"testimonial-card"} -->
		<div class="wp-block-column testimonial-card">
			<!-- wp:html -->
			<div class="testimonial-card__inner">
				<div class="testimonial-card__header">
					<div class="testimonial-avatar">RH</div>
					<div>
						<div class="testimonial-name">Robert Hughes</div>
						<div class="testimonial-role">Marketing Director</div>
					</div>
				</div>
				<div class="testimonial-stars">★★★★★</div>
				<blockquote class="testimonial-quote">"The quality of the instructors is what sets this platform apart. They aren't just academics — they are industry veterans sharing real-world insights that are immediately applicable."</blockquote>
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
.testimonials__divider {
	width: 64px;
	height: 4px;
	background: var(--wp--preset--color--accent);
	border-radius: 2px;
}
.testimonial-card__inner {
	background: var(--wp--preset--color--white);
	border-radius: 12px;
	padding: var(--wp--preset--spacing--4);
	box-shadow: var(--wp--preset--shadow--card);
	border: 1px solid rgba(196,198,208,0.15);
	height: 100%;
}
.testimonial-card__header {
	display: flex;
	align-items: center;
	gap: var(--wp--preset--spacing--2);
	margin-bottom: var(--wp--preset--spacing--2);
}
.testimonial-avatar {
	width: 48px;
	height: 48px;
	border-radius: 6px;
	background: var(--wp--preset--color--primary);
	color: var(--wp--preset--color--white);
	font-size: 0.875rem;
	font-weight: 700;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}
.testimonial-name {
	font-weight: 700;
	color: var(--wp--preset--color--primary);
	font-size: var(--wp--preset--font-size--body);
}
.testimonial-role {
	font-size: var(--wp--preset--font-size--small);
	color: var(--wp--preset--color--on-surface-variant);
}
.testimonial-stars {
	color: var(--wp--preset--color--accent);
	font-size: 1rem;
	letter-spacing: 2px;
	margin-bottom: var(--wp--preset--spacing--2);
}
.testimonial-quote {
	font-size: 0.9375rem;
	color: var(--wp--preset--color--on-surface-variant);
	line-height: 1.7;
	font-style: italic;
	margin: 0;
	border-left: 3px solid var(--wp--preset--color--accent);
	padding-left: var(--wp--preset--spacing--2);
}
</style>
<!-- /wp:html -->