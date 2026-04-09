<?php
/**
 * SPOKE SINGLE COURSE — TEMPLATE OVERRIDE + SHORTCODE
 * File: inc/functions-single-course.php
 *
 * TWO JOBS:
 *
 * JOB 1 — Force FSE to render single-course.html for 'courses' post type.
 *   Tutor LMS hooks into template_include with priority 99 and replaces
 *   the template with its own file. We hook at priority 100 (fires after)
 *   and restore FSE block rendering via template-canvas.php.
 *
 * JOB 2 — Shortcode [spoke_single_course] renders the full course page
 *   content inside the FSE template. Called from templates/single-course.html.
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. FORCE FSE TEMPLATE — override Tutor LMS at priority 100
// ─────────────────────────────────────────────────────────────────

add_filter( 'template_include', function ( string $template ): string {

	if ( ! is_singular( 'courses' ) ) {
		return $template;
	}

	// Get the FSE block template registered for single-course
	$fse = get_block_template( get_stylesheet() . '//single-course', 'wp_template' );

	if ( $fse && ! empty( $fse->content ) ) {
		// Set the globals FSE uses so template-canvas.php renders the right content
		global $_wp_current_template_content, $_wp_current_template_id;
		$_wp_current_template_content = $fse->content;
		$_wp_current_template_id      = $fse->id;

		$canvas = ABSPATH . WPINC . '/template-canvas.php';
		if ( file_exists( $canvas ) ) {
			return $canvas;
		}
	}

	// Hard fallback: if FSE template lookup failed, use the raw HTML file
	$html_file = get_template_directory() . '/templates/single-course.html';
	if ( file_exists( $html_file ) ) {
		// Provide a minimal PHP wrapper that parses and renders the block content
		// by hooking into the_content
		add_filter( 'the_content', function () use ( $html_file ): string {
			return do_blocks( file_get_contents( $html_file ) );
		}, 1 );
	}

	return $template;

}, 100 ); // Must be > 99 to fire after Tutor LMS


// ─────────────────────────────────────────────────────────────────
// 2. SUPPRESS TUTOR LMS CONTENT INJECTION
//    Tutor also injects its layout via the_content and action hooks.
//    Since we provide our own layout, we remove those.
// ─────────────────────────────────────────────────────────────────

add_action( 'template_redirect', function (): void {

	if ( ! is_singular( 'courses' ) ) {
		return;
	}

	// Attempt to remove known Tutor content filters (class method signatures vary by version)
	$tutor_content_hooks = [
		[ 'the_content', 'tutor_course_content_wrap' ],
	];
	foreach ( $tutor_content_hooks as $hook ) {
		remove_filter( $hook[0], $hook[1] );
	}


} );


// ─────────────────────────────────────────────────────────────────
// 3. STAR HELPER (declared once — never inside a shortcode/render)
// ─────────────────────────────────────────────────────────────────

if ( ! function_exists( 'spoke_render_stars' ) ) {
	function spoke_render_stars( float $avg, string $size = 'sm' ): string {
		$w   = $size === 'lg' ? '20' : '14';
		$out = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$fill = $i <= round( $avg ) ? '#F4A726' : '#D1D5DB';
			$out .= "<svg width='{$w}' height='{$w}' fill='{$fill}' viewBox='0 0 20 20'><path d='M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'/></svg>";
		}
		return $out;
	}
}


// ─────────────────────────────────────────────────────────────────
// 4. SHORTCODE REGISTRATION + wpautop REMOVAL
// ─────────────────────────────────────────────────────────────────

add_filter( 'the_content', function ( string $content ): string {
	if ( has_shortcode( $content, 'spoke_single_course' ) ) {
		remove_filter( 'the_content', 'wpautop' );
	}
	return $content;
}, 9 );

add_shortcode( 'spoke_single_course', function (): string {
	remove_filter( 'the_content', 'wpautop' );
	remove_filter( 'the_content', 'wptexturize' );

	if ( ! is_singular( [ 'courses', 'product' ] ) ) {
		return '';
	}

	ob_start();
	spoke_render_single_course();
	$html = ob_get_clean();

	// Strip every HTML comment — wpautop wraps them in <p> tags
	$html = preg_replace( '/<!--.*?-->/s', '', $html );
	// Collapse any blank lines left behind
	$html = preg_replace( '/(\s*\n){3,}/', "\n", $html );

	add_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wptexturize' );
	return $html;
} );


// ─────────────────────────────────────────────────────────────────
// 5. MAIN RENDER FUNCTION
// ─────────────────────────────────────────────────────────────────

function spoke_render_single_course(): void {

	$post_id      = get_the_ID();
	$post_type    = get_post_type( $post_id );
	$tutor_active = function_exists( 'tutor_utils' );

	// ── DATA ──────────────────────────────────────────────────────

	if ( $tutor_active && 'courses' === $post_type ) {
		$rd           = tutor_utils()->get_course_rating( $post_id );
		$rating_avg   = (float) ( $rd->rating_avg   ?? 0 );
		$rating_cnt   = (int)   ( $rd->rating_count ?? 0 );
		$rating_bars  = [ 5 => $rd->count_5_star ?? 0, 4 => $rd->count_4_star ?? 0, 3 => $rd->count_3_star ?? 0, 2 => $rd->count_2_star ?? 0, 1 => $rd->count_1_star ?? 0 ];
		$students     = (int) tutor_utils()->count_enrolled_users_by_course( $post_id );
		$raw_price    = tutor_utils()->get_raw_course_price( $post_id );
		$price        = is_object( $raw_price ) ? (float) ( $raw_price->regular_price ?? 0 ) : (float) $raw_price;
		$sale_meta    = get_post_meta( $post_id, '_sale_price', true );
		$sale_price   = is_scalar( $sale_meta ) ? (float) $sale_meta : 0;
		$lesson_count = (int) tutor_utils()->get_course_content_count_by( $post_id, 'lesson' );
		$quiz_count   = (int) tutor_utils()->get_quiz_count_by_course( $post_id );
		$learn_items  = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $post_id, '_tutor_course_benefits', true ) ) ) );
		$requirements = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $post_id, '_tutor_course_requirements', true ) ) ) );
		$topics       = tutor_utils()->get_topics_by_course( $post_id );
		$cat_tax      = 'course-category';
	} else {
		$wc           = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
		$rating_avg   = $wc ? (float) $wc->get_average_rating() : 0;
		$rating_cnt   = $wc ? (int)   $wc->get_rating_count()   : 0;
		$rating_bars  = [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
		$students     = 0;
		$price        = $wc ? (float) $wc->get_regular_price() : 0;
		$sale_price   = $wc ? (float) $wc->get_sale_price()    : 0;
		$lesson_count = $quiz_count = 0;
		$learn_items  = $requirements = [];
		$topics       = null;
		$cat_tax      = 'product_cat';
	}

	$eff_price    = $sale_price > 0 ? $sale_price : $price;
	$duration_raw = get_post_meta( $post_id, '_course_duration', true );
	$duration     = is_array( $duration_raw ) ? implode( ' ', array_filter( $duration_raw ) ) : (string) $duration_raw;
	$level        = get_post_meta( $post_id, '_tutor_course_level', true );
	$last_upd     = get_the_modified_date( 'j M Y', $post_id );
	$cats         = get_the_terms( $post_id, $cat_tax );
	$cat_name     = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
	$cat_url      = ( $cats && ! is_wp_error( $cats ) ) ? get_term_link( $cats[0] ) : home_url( '/courses/' );
	$thumb_url    = get_the_post_thumbnail_url( $post_id, 'large' ) ?: '';
	$instructor   = get_userdata( (int) get_post_field( 'post_author', $post_id ) );

	// Enrol button
	$wc_obj = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
	if ( $wc_obj && $wc_obj->is_purchasable() && $wc_obj->is_in_stock() ) {
		$enrol_url     = $wc_obj->add_to_cart_url();
		$enrol_label   = 'Enrol Now — Add to Cart';
		$enrol_cls     = 'ajax_add_to_cart add_to_cart_button';
		$enrol_data    = 'data-quantity="1" data-product_id="' . esc_attr( $post_id ) . '"';
	} else {
		$enrol_url     = get_permalink( $post_id );
		$enrol_label   = 'Enrol Now';
		$enrol_cls     = '';
		$enrol_data    = '';
	}

	// ── HERO ──────────────────────────────────────────────────────
	?>
	<div class="sc-wrap" style="font-family:'Inter',sans-serif;">

	<section style="background:linear-gradient(160deg,#1A3C6E 0%,#1A1A2E 100%);padding:64px 24px;" aria-label="Course overview">
		<div class="sc-hero-grid" style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 400px;gap:64px;align-items:flex-start;">
			<div>
				<nav class="flex items-center flex-wrap gap-1.5 text-[13px] mb-6" aria-label="Breadcrumb">
					<a href="/" class="no-underline hover:text-white transition-colors" style="color:rgba(255,255,255,0.55);">Home</a>
					<span style="color:rgba(255,255,255,0.25);">/</span>
					<a href="<?php echo esc_url( home_url('/courses/') ); ?>" class="no-underline hover:text-white transition-colors" style="color:rgba(255,255,255,0.55);">Courses</a>
					<?php if ( $cat_name ) : ?>
					<span style="color:rgba(255,255,255,0.25);">/</span>
					<a href="<?php echo esc_url( $cat_url ); ?>" class="no-underline hover:text-white transition-colors" style="color:rgba(255,255,255,0.55);"><?php echo esc_html( $cat_name ); ?></a>
					<?php endif; ?>
					<span style="color:rgba(255,255,255,0.25);">/</span>
					<span class="font-medium truncate" style="max-width:200px;color:#F4A726;"><?php the_title(); ?></span>
				</nav>

				<div class="flex flex-wrap gap-2 mb-4">
					<span class="text-[11px] font-bold uppercase tracking-[0.06em] px-2.5 py-1 rounded" style="background:rgba(244,167,38,0.2);color:#F4A726;border:1px solid rgba(244,167,38,0.35);">CPD Accredited</span>
					<?php if ( $level ) : ?><span class="text-[11px] font-bold uppercase tracking-[0.06em] px-2.5 py-1 rounded" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.9);border:1px solid rgba(255,255,255,0.2);"><?php echo esc_html( ucfirst( $level ) ); ?></span><?php endif; ?>
					<?php if ( $duration ) : ?><span class="text-[11px] font-bold uppercase tracking-[0.06em] px-2.5 py-1 rounded" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.9);border:1px solid rgba(255,255,255,0.2);"><?php echo esc_html( $duration ); ?></span><?php endif; ?>
				</div>

				<h1 class="text-white font-bold m-0 mb-4 leading-[1.15]" style="font-size:clamp(1.75rem,4vw,3rem);letter-spacing:-0.02em;"><?php the_title(); ?></h1>
				<p class="m-0 mb-5 leading-[1.7]" style="font-size:1.0625rem;color:rgba(255,255,255,0.75);"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>

				<div class="flex flex-wrap items-center gap-3 mb-4">
					<span class="font-bold" style="color:#F4A726;"><?php echo number_format( $rating_avg, 1 ); ?></span>
					<span class="flex gap-0.5"><?php echo spoke_render_stars( $rating_avg ); ?></span>
					<span class="text-[13px]" style="color:rgba(255,255,255,0.65);">(<?php echo number_format( $rating_cnt ); ?> reviews)</span>
					<?php if ( $students ) : ?>
					<span style="color:rgba(255,255,255,0.35);">·</span>
					<span class="text-[13px]" style="color:rgba(255,255,255,0.75);"><?php echo number_format( $students ); ?> students</span>
					<?php endif; ?>
				</div>

				<?php if ( $instructor ) :
					$av = get_user_meta( $instructor->ID, '_tutor_profile_photo', true ) ?: get_avatar_url( $instructor->ID, ['size'=>40] );
				?>
				<div class="flex items-center gap-3 mb-4">
					<?php if ( $av ) : ?>
					<img src="<?php echo esc_url( $av ); ?>" alt="<?php echo esc_attr( $instructor->display_name ); ?>" class="w-9 h-9 rounded-lg object-cover flex-shrink-0" width="36" height="36" loading="lazy">
					<?php else : ?>
					<div class="w-9 h-9 rounded-lg flex items-center justify-center text-[12px] font-bold flex-shrink-0" style="background:rgba(255,255,255,0.2);color:#fff;"><?php echo esc_html( mb_substr( $instructor->display_name, 0, 2 ) ); ?></div>
					<?php endif; ?>
					<div>
						<span class="block text-[10px] uppercase tracking-[0.08em]" style="color:rgba(255,255,255,0.5);">Instructor</span>
						<span class="block text-[14px] font-semibold text-white"><?php echo esc_html( $instructor->display_name ); ?></span>
					</div>
				</div>
				<?php endif; ?>

				<p class="flex items-center gap-1.5 text-[13px] m-0" style="color:rgba(255,255,255,0.5);">
					<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
					Last updated <?php echo esc_html( $last_upd ); ?>
				</p>
			</div>
			<div>
				<div class="sc-enrol-card" style="background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.15);overflow:hidden;position:sticky;top:90px;">

					<div style="position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#1A3C6E,#1A1A2E);overflow:hidden;">
						<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" style="width:100%;height:100%;object-fit:cover;" width="400" height="225" loading="eager">
						<?php else : ?>
						<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
							<svg style="width:64px;height:64px;opacity:0.2;" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
						</div>
						<?php endif; ?>
						<button aria-label="Watch preview" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(26,60,110,0.45);border:none;cursor:pointer;">
							<span style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:#1A3C6E;">
								<svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
							</span>
						</button>
					</div>

					<div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:8px;padding:20px 20px 0;">
						<?php if ( $sale_price > 0 ) : ?>
							<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">£<?php echo number_format( $sale_price, 2 ); ?></span>
							<span style="font-size:1rem;text-decoration:line-through;color:#43474f;">£<?php echo number_format( $price, 2 ); ?></span>
							<?php $pct = $price > 0 ? round( (1 - $sale_price/$price) * 100 ) : 0; if ( $pct > 0 ) : ?>
							<span style="font-size:0.75rem;font-weight:700;background:rgba(186,26,26,0.08);color:#BA1A1A;padding:2px 6px;border-radius:4px;"><?php echo $pct; ?>% off</span>
							<?php endif; ?>
						<?php elseif ( $eff_price > 0 ) : ?>
							<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">£<?php echo number_format( $eff_price, 2 ); ?></span>
						<?php else : ?>
							<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">Free</span>
						<?php endif; ?>
					</div><?php if ( $tutor_active && 'courses' === $post_type ) : ?>
					<div style="padding:12px 20px 0;"><?php do_action( 'tutor_course/single/enrol_btn' ); ?></div>
					<?php else : ?>
					<div style="padding:12px 20px 0;">
						<a href="<?php echo esc_url( $enrol_url ); ?>" <?php echo $enrol_data; ?> class="<?php echo esc_attr( $enrol_cls ); ?>" style="display:block;width:100%;text-align:center;font-family:'Inter',sans-serif;font-size:1.0625rem;font-weight:700;padding:14px 20px;background:#F4A726;color:#6b4500;border-radius:8px;text-decoration:none;box-sizing:border-box;">
							<?php echo esc_html( $enrol_label ); ?>
						</a>
					</div>
					<?php endif; ?>

					<div style="margin:12px 20px 0;display:flex;align-items:center;justify-content:center;gap:6px;background:#E8F4EC;border-radius:8px;padding:10px;font-size:14px;font-weight:600;color:#1A3C6E;">
						<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>30-Day Money-Back Guarantee
					</div>
					<ul style="list-style:none;margin:0;padding:16px 20px 8px;display:flex;flex-direction:column;gap:10px;">
						<?php if ( $lesson_count ) : ?><li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?php echo (int)$lesson_count; ?> lessons</li><?php endif; ?>
						<?php if ( $quiz_count ) : ?><li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><?php echo (int)$quiz_count; ?> quizzes</li><?php endif; ?>
						<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Mobile &amp; desktop access</li>
						<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>CPD certificate on completion</li>
						<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Direct Q&amp;A with instructor</li>
					</ul>
					<div style="display:flex;align-items:center;justify-content:center;gap:24px;padding:12px 20px 20px;border-top:1px solid rgba(0,0,0,0.06);">
						<button style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#1A3C6E;background:none;border:none;cursor:pointer;">
							<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>Wishlist</button>
						<button style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#1A3C6E;background:none;border:none;cursor:pointer;">
							<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>Share</button>
					</div>
				</div>
			</div></div>
	</section>

	<div style="background:#f8f9fa;padding:48px 24px 80px;">
	<div style="max-width:1280px;margin:0 auto;">

		<div class="sc-tab-nav" role="tablist" aria-label="Course sections">
			<button class="sc-tab sc-tab--active" role="tab" aria-selected="true"  aria-controls="sc-tab-overview"   id="sc-btn-overview">Overview</button>
			<button class="sc-tab"                role="tab" aria-selected="false" aria-controls="sc-tab-curriculum" id="sc-btn-curriculum">Curriculum</button>
			<button class="sc-tab"                role="tab" aria-selected="false" aria-controls="sc-tab-instructor" id="sc-btn-instructor">Instructor</button>
			<button class="sc-tab"                role="tab" aria-selected="false" aria-controls="sc-tab-reviews"    id="sc-btn-reviews">Reviews</button>
		</div>

		<div id="sc-tab-overview" class="sc-tab-panel" role="tabpanel" aria-labelledby="sc-btn-overview">
			<?php if ( ! empty( $learn_items ) ) : ?>
			<div class="sc-what-learn"><h2 class="sc-section-h">What you'll learn</h2><ul class="sc-learn-list"><?php foreach ( $learn_items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<div class="sc-description" style="margin-top:24px;"><?php the_content(); ?></div>
			<?php if ( ! empty( $requirements ) ) : ?>
			<div style="margin-top:32px;"><h2 class="sc-section-h">Requirements</h2><ul style="padding-left:20px;margin:0;display:flex;flex-direction:column;gap:8px;"><?php foreach ( $requirements as $r ) : ?><li style="font-size:14px;color:#43474f;line-height:1.6;"><?php echo esc_html( $r ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
		</div>

		<div id="sc-tab-curriculum" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-curriculum">
			<h2 class="sc-section-h">Course Curriculum</h2>
			<?php if ( $tutor_active && $topics && ! empty( $topics->posts ) ) : ?>
			<div style="display:flex;flex-direction:column;gap:12px;">
				<?php foreach ( $topics->posts as $idx => $topic ) :
					$lessons = tutor_utils()->get_lessons_by_topic( $topic->ID );
					$lcnt    = $lessons ? count( $lessons ) : 0;
					$pid     = 'sc-topic-' . $topic->ID;
				?>
				<div style="border:1px solid rgba(0,0,0,0.08);border-radius:8px;overflow:hidden;">
					<button class="sc-topic-btn" style="width:100%;display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:14px 20px;text-align:left;background:#1A3C6E;border:none;cursor:pointer;" aria-expanded="<?php echo $idx === 0 ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $pid ); ?>">
						<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.6);flex-shrink:0;">Section <?php echo $idx+1; ?></span>
						<h3 style="flex:1;font-size:15px;font-weight:600;color:#fff;margin:0;"><?php echo esc_html( $topic->post_title ); ?></h3>
						<span style="font-size:13px;color:rgba(255,255,255,0.65);flex-shrink:0;"><?php echo $lcnt; ?> lesson<?php echo $lcnt!==1?'s':''; ?></span>
						<svg class="sc-topic-chevron" style="width:16px;height:16px;color:#fff;flex-shrink:0;transition:transform 200ms;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
					</button>
					<ul class="sc-topic-lessons <?php echo $idx===0?'sc-topic-lessons--open':''; ?>" style="list-style:none;margin:0;padding:0;" id="<?php echo esc_attr( $pid ); ?>">
						<?php if ( $lessons ) : foreach ( $lessons as $lesson ) :
							$prev = get_post_meta( $lesson->ID, '_is_preview', true );
							$dur  = get_post_meta( $lesson->ID, '_lesson_duration', true );
							$qz   = ( 'tutor_quiz' === $lesson->post_type );
						?>
						<li style="display:flex;align-items:center;gap:12px;padding:12px 20px;background:#fff;border-top:1px solid rgba(0,0,0,0.06);">
							<?php if ( $qz ) : ?><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg><?php else : ?><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?php endif; ?>
							<?php if ( $prev ) : ?><a href="<?php echo esc_url( get_permalink($lesson->ID) ); ?>" style="flex:1;font-size:14px;font-weight:500;color:#1A3C6E;text-decoration:none;"><?php echo esc_html( $lesson->post_title ); ?></a><span style="font-size:10px;font-weight:700;background:rgba(26,60,110,0.08);color:#1A3C6E;padding:2px 6px;border-radius:4px;flex-shrink:0;">Free Preview</span>
							<?php else : ?><span style="flex:1;font-size:14px;font-weight:500;color:#43474f;"><?php echo esc_html( $lesson->post_title ); ?></span><svg style="opacity:0.4;flex-shrink:0;" width="14" height="14" fill="none" stroke="#43474f" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><?php endif; ?>
							<?php if ( $dur ) : ?><span style="font-size:12px;color:#43474f;opacity:0.7;flex-shrink:0;"><?php echo esc_html($dur); ?></span><?php endif; ?>
						</li>
						<?php endforeach; endif; ?>
					</ul>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p style="font-size:15px;color:#43474f;">Curriculum will be available after enrolment.</p>
			<?php endif; ?>
		</div>

		<div id="sc-tab-instructor" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-instructor">
			<?php if ( $instructor ) :
				$bio   = get_user_meta( $instructor->ID, 'description', true );
				$photo = get_user_meta( $instructor->ID, '_tutor_profile_photo', true ) ?: get_avatar_url( $instructor->ID, ['size'=>120] );
				$jtit  = get_user_meta( $instructor->ID, '_tutor_profile_job_title', true );
				$icrs  = get_posts(['post_type'=>'courses','author'=>$instructor->ID,'numberposts'=>-1,'fields'=>'ids']);
			?>
			<h2 class="sc-section-h">Your Instructor</h2>
			<div style="display:flex;flex-wrap:wrap;gap:24px;padding:24px;background:#f3f4f5;border-radius:12px;">
				<?php if ( $photo ) : ?><img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($instructor->display_name); ?>" style="width:100px;height:100px;border-radius:8px;object-fit:cover;flex-shrink:0;" width="100" height="100" loading="lazy"><?php else : ?><div style="width:100px;height:100px;border-radius:8px;background:#1A3C6E;color:#fff;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?php echo esc_html(mb_substr($instructor->display_name,0,2)); ?></div><?php endif; ?>
				<div style="flex:1;min-width:200px;">
					<h3 style="font-size:20px;font-weight:700;color:#1A3C6E;margin:0 0 4px;"><?php echo esc_html($instructor->display_name); ?></h3>
					<?php if ( $jtit ) : ?><p style="font-size:14px;font-weight:600;color:#F4A726;margin:0 0 12px;"><?php echo esc_html($jtit); ?></p><?php endif; ?>
					<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
						<div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;"><?php echo count($icrs); ?></strong><span style="font-size:11px;color:#43474f;">Courses</span></div>
						<?php if ( $students ) : ?><div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;"><?php echo number_format($students); ?>+</strong><span style="font-size:11px;color:#43474f;">Students</span></div><?php endif; ?>
						<div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;"><?php echo number_format($rating_avg,1); ?></strong><span style="font-size:11px;color:#43474f;">Rating</span></div>
					</div>
					<?php if ( $bio ) : ?><p style="font-size:14px;color:#43474f;line-height:1.7;margin:0;"><?php echo esc_html($bio); ?></p><?php endif; ?>
				</div>
			</div>
			<?php else : ?><p style="font-size:15px;color:#43474f;">Instructor information not available.</p><?php endif; ?>
		</div>

		<div id="sc-tab-reviews" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-reviews">
			<h2 class="sc-section-h">Student Feedback</h2>
			<div style="display:flex;flex-wrap:wrap;align-items:center;gap:32px;padding:24px;background:#f3f4f5;border-radius:12px;margin-bottom:24px;">
				<div style="text-align:center;">
					<strong style="display:block;font-size:3.5rem;font-weight:900;color:#F4A726;line-height:1;"><?php echo number_format($rating_avg,1); ?></strong>
					<span style="display:flex;justify-content:center;gap:2px;margin:4px 0;"><?php echo spoke_render_stars($rating_avg,'lg'); ?></span>
					<span style="font-size:14px;font-weight:600;color:#1A3C6E;">Course Rating</span>
				</div>
				<div style="flex:1;min-width:200px;display:flex;flex-direction:column;gap:8px;">
					<?php foreach ( $rating_bars as $star => $cnt ) :
						$pct = $rating_cnt > 0 ? round( ($cnt/$rating_cnt)*100 ) : 0;
					?>
					<div style="display:flex;align-items:center;gap:12px;">
						<span style="font-size:12px;color:#43474f;width:16px;text-align:right;flex-shrink:0;"><?php echo $star; ?></span>
						<div style="flex:1;height:8px;background:rgba(0,0,0,0.08);border-radius:4px;overflow:hidden;"><div style="height:100%;width:<?php echo $pct; ?>%;background:#F4A726;border-radius:4px;"></div></div>
						<span style="font-size:12px;font-weight:600;color:#43474f;width:32px;flex-shrink:0;"><?php echo $pct; ?>%</span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( $tutor_active ) : do_action( 'tutor_course/single/reviews' ); endif; ?>
		</div>

	</div>
	</div>

	<?php
	$rel = new WP_Query(['post_type'=>$post_type,'posts_per_page'=>3,'post__not_in'=>[$post_id],'orderby'=>'rand','post_status'=>'publish']);
	if ( $rel->have_posts() ) :
	?>
	<section style="background:#f3f4f5;padding:56px 24px;border-top:1px solid rgba(0,0,0,0.06);">
		<div style="max-width:1280px;margin:0 auto;">
			<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-bottom:32px;">
				<h2 style="font-size:24px;font-weight:700;color:#1A3C6E;margin:0;">Continue Your Learning</h2>
				<a href="<?php echo esc_url(home_url('/courses/')); ?>" style="font-size:14px;font-weight:700;color:#1A3C6E;text-decoration:none;">View All Courses →</a>
			</div>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;">
				<?php while ( $rel->have_posts() ) : $rel->the_post();
					$rid    = get_the_ID();
					$rthumb = get_the_post_thumbnail_url($rid,'medium_large');
					$rcats  = get_the_terms($rid,$cat_tax);
					$rcat   = ($rcats && !is_wp_error($rcats)) ? $rcats[0]->name : '';
					if ( $tutor_active && 'courses'===$post_type ) {
						$rr = tutor_utils()->get_course_rating($rid);
						$ravg  = (float)($rr->rating_avg??0);
						$rraw  = tutor_utils()->get_raw_course_price($rid);
						$rprice = is_object($rraw) ? (float)($rraw->regular_price??0) : (float)$rraw;
					} else {
						$rp = function_exists('wc_get_product')?wc_get_product($rid):null;
						$ravg  = $rp?(float)$rp->get_average_rating():0;
						$rprice = $rp?(float)$rp->get_regular_price():0;
					}
				?>
				<article style="background:#fff;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
					<div style="height:170px;overflow:hidden;">
						<a href="<?php the_permalink(); ?>" style="display:block;width:100%;height:100%;" tabindex="-1" aria-hidden="true">
							<?php if ($rthumb) : ?><img src="<?php echo esc_url($rthumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy" width="400" height="170"><?php else : ?><div style="width:100%;height:100%;background:linear-gradient(135deg,#1A3C6E,#1A1A2E);display:flex;align-items:center;justify-content:center;"><svg style="width:40px;height:40px;opacity:0.2;" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div><?php endif; ?>
						</a>
					</div>
					<div style="padding:20px;display:flex;flex-direction:column;flex:1;gap:8px;">
						<?php if ($rcat) : ?><span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:rgba(26,60,110,0.08);color:#1A3C6E;padding:2px 8px;border-radius:4px;align-self:flex-start;"><?php echo esc_html($rcat); ?></span><?php endif; ?>
						<h3 style="font-size:15px;font-weight:700;color:#1A3C6E;margin:0;line-height:1.3;"><a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none;"><?php the_title(); ?></a></h3>
						<div style="display:flex;align-items:center;gap:6px;"><?php echo spoke_render_stars($ravg); ?><span style="font-size:12px;font-weight:700;color:#1A3C6E;"><?php echo number_format($ravg,1); ?></span></div>
						<div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);">
							<span style="font-size:18px;font-weight:900;color:#1A3C6E;">£<?php echo number_format($rprice,2); ?></span>
							<a href="<?php the_permalink(); ?>" style="display:inline-flex;align-items:center;height:36px;padding:0 16px;background:#1A3C6E;color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;">Learn More</a>
						</div>
					</div>
				</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	</div>
	<style>
	.sc-tab-nav{display:flex;border-bottom:2px solid rgba(0,0,0,0.08);margin-bottom:32px;overflow-x:auto;scrollbar-width:none;}
	.sc-tab-nav::-webkit-scrollbar{display:none;}
	.sc-tab{flex-shrink:0;height:48px;padding:0 24px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-family:'Inter',sans-serif;font-size:16px;font-weight:500;color:#43474f;cursor:pointer;transition:color 200ms,border-color 200ms;}
	.sc-tab:hover{color:#1A3C6E;}
	.sc-tab--active{color:#1A3C6E;font-weight:700;border-bottom-color:#F4A726;}
	.sc-tab-panel{padding-top:0;}
	.sc-tab-panel--hidden{display:none!important;}
	.sc-section-h{font-size:22px;font-weight:700;color:#1A3C6E;margin:0 0 16px;}
	.sc-what-learn{padding:24px;background:#f3f4f5;border-radius:12px;margin-bottom:32px;}
	.sc-learn-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;list-style:none;margin:0;padding:0;}
	.sc-learn-list li{display:flex;align-items:flex-start;gap:8px;font-size:14px;color:#1A3C6E;}
	.sc-learn-list li::before{content:'✓';color:#1A3C6E;font-weight:700;flex-shrink:0;margin-top:1px;}
	.sc-description p{font-size:15px;line-height:1.8;color:#43474f;margin:0 0 16px;}
	.sc-description h2{font-size:22px;font-weight:700;color:#1A3C6E;margin:24px 0 12px;}
	.sc-description h3{font-size:18px;font-weight:700;color:#1A3C6E;margin:20px 0 10px;}
	.sc-description ul,.sc-description ol{padding-left:20px;margin:0 0 16px;}
	.sc-description li{font-size:15px;color:#43474f;line-height:1.7;margin-bottom:6px;}
	.sc-topic-lessons{display:none;}
	.sc-topic-lessons--open{display:block;}
	.sc-topic-btn[aria-expanded="true"] .sc-topic-chevron{transform:rotate(180deg);}
	.sc-enrol-card .tutor-btn.tutor-btn-primary,.sc-enrol-card .tutor-add-to-cart-button{display:block!important;margin:0!important;padding:14px 0!important;width:100%!important;background:#F4A726!important;color:#6b4500!important;border:none!important;border-radius:8px!important;font-family:'Inter',sans-serif!important;font-size:1.0625rem!important;font-weight:700!important;text-align:center!important;box-sizing:border-box!important;}
	.sc-enrol-card form{margin:0!important;}
	@media(max-width:1024px){.sc-hero-grid{grid-template-columns:1fr!important;}.sc-hero-grid>div:last-child{order:-1;}.sc-enrol-card{position:static!important;max-width:480px;}}
	@media(max-width:640px){.sc-learn-list{grid-template-columns:1fr;}}
	</style>

	<script>
	(function(){
		'use strict';
		var tabs=document.querySelectorAll('.sc-tab'),panels=document.querySelectorAll('.sc-tab-panel');
		tabs.forEach(function(tab){
			tab.addEventListener('click',function(){
				var t=tab.getAttribute('aria-controls');
				tabs.forEach(function(x){x.classList.remove('sc-tab--active');x.setAttribute('aria-selected','false');});
				panels.forEach(function(p){p.classList.add('sc-tab-panel--hidden');});
				tab.classList.add('sc-tab--active');tab.setAttribute('aria-selected','true');
				var p=document.getElementById(t);if(p)p.classList.remove('sc-tab-panel--hidden');
			});
		});
		document.querySelectorAll('.sc-topic-btn').forEach(function(btn){
			btn.addEventListener('click',function(){
				var p=document.getElementById(btn.getAttribute('aria-controls'));
				if(!p)return;
				var o=btn.getAttribute('aria-expanded')==='true';
				btn.setAttribute('aria-expanded',String(!o));
				p.classList.toggle('sc-topic-lessons--open',!o);
			});
		});
	})();
	</script>
	<?php
}