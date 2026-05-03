<?php

/**
 * SPOKE SINGLE COURSE — TEMPLATE OVERRIDE + SHORTCODE
 * File: inc/functions-single-course.php
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. FORCE FSE TEMPLATE — override Tutor LMS at priority 100
// ─────────────────────────────────────────────────────────────────



add_filter('template_include', function (string $template): string {

	if (! is_singular('courses')) {
		return $template;
	}

	$fse = get_block_template(get_stylesheet() . '//single-course', 'wp_template');

	if ($fse && ! empty($fse->content)) {
		global $_wp_current_template_content, $_wp_current_template_id;
		$_wp_current_template_content = $fse->content;
		$_wp_current_template_id      = $fse->id;

		$canvas = ABSPATH . WPINC . '/template-canvas.php';
		if (file_exists($canvas)) {
			return $canvas;
		}
	}

	$html_file = get_template_directory() . '/templates/single-course.html';
	if (file_exists($html_file)) {
		add_filter('the_content', function () use ($html_file): string {
			return do_blocks(file_get_contents($html_file));
		}, 1);
	}

	return $template;
}, 100);


// ─────────────────────────────────────────────────────────────────
// 2. SUPPRESS TUTOR LMS CONTENT INJECTION
// ─────────────────────────────────────────────────────────────────

add_action('template_redirect', function (): void {
	if (! is_singular('courses')) {
		return;
	}
	remove_filter('the_content', 'tutor_course_content_wrap');
});


// ─────────────────────────────────────────────────────────────────
// 3. STAR HELPER
// ─────────────────────────────────────────────────────────────────

if (! function_exists('spoke_render_stars')) {
	function spoke_render_stars(float $avg, string $size = 'sm'): string
	{
		$w   = $size === 'lg' ? '20' : '14';
		$out = '';
		for ($i = 1; $i <= 5; $i++) {
			$fill = $i <= round($avg) ? '#F4A726' : '#D1D5DB';
			$out .= "<svg width='{$w}' height='{$w}' fill='{$fill}' viewBox='0 0 20 20'><path d='M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'/></svg>";
		}
		return $out;
	}
}


// ─────────────────────────────────────────────────────────────────
// 4. SHORTCODE REGISTRATION + wpautop SUPPRESSION
//
// Key insight: wpautop turns every newline between tags into <br>.
// The only reliable fix is:
//   a) remove wpautop before it runs on this content
//   b) scrub any <br> / empty <p> that slipped through
//   c) use shortcode_unautop so the [shortcode] tag itself
//      is not wrapped in <p></p>
// ─────────────────────────────────────────────────────────────────

add_filter('the_content', function (string $content): string {
	if (has_shortcode($content, 'spoke_single_course')) {
		remove_filter('the_content', 'wpautop');
		remove_filter('the_content', 'wptexturize');
	}
	return $content;
}, 9);

add_filter('the_content', 'shortcode_unautop', 10);

add_shortcode('spoke_single_course', function (): string {

	remove_filter('the_content', 'wpautop');
	remove_filter('the_content', 'wptexturize');

	if (! is_singular(['courses', 'product'])) {
		return '';
	}

	ob_start();
	spoke_render_single_course();
	$html = ob_get_clean();

	// Strip HTML comments
	$html = preg_replace('/<!--.*?-->/s', '', $html);
	// Remove ALL <br> variants
	$html = preg_replace('/<br\s*\/?>/i', '', $html);
	// Remove empty <p> tags (whitespace / &nbsp; only)
	$html = preg_replace('/<p>(\s|&nbsp;)*<\/p>/i', '', $html);
	// Collapse excessive blank lines
	$html = preg_replace('/(\s*\n){3,}/', "\n", $html);

	add_filter('the_content', 'wpautop');
	add_filter('the_content', 'wptexturize');

	return $html;
});


// ─────────────────────────────────────────────────────────────────
// 5. MAIN RENDER FUNCTION
//
// IMPORTANT: All HTML is kept on single continuous lines with no
// blank lines between PHP tags. wpautop converts newlines adjacent
// to HTML tags into <br> so we must not leave stray whitespace.
// ─────────────────────────────────────────────────────────────────

function spoke_render_single_course(): void
{
	// Remove wpautop before the_content() runs inside our buffer.
	remove_filter('the_content', 'wpautop');
	remove_filter('the_content', 'wptexturize');

	$post_id      = get_the_ID();
	$post_type    = get_post_type($post_id);
	$tutor_active = function_exists('tutor_utils');

	// ── DATA ──────────────────────────────────────────────────────

	if ($tutor_active && 'courses' === $post_type) {
		$rd           = tutor_utils()->get_course_rating($post_id);
		$rating_avg   = (float) ($rd->rating_avg   ?? 0);
		$rating_cnt   = (int)   ($rd->rating_count ?? 0);
		$rating_bars  = [5 => $rd->count_5_star ?? 0, 4 => $rd->count_4_star ?? 0, 3 => $rd->count_3_star ?? 0, 2 => $rd->count_2_star ?? 0, 1 => $rd->count_1_star ?? 0];
		$students     = (int) tutor_utils()->count_enrolled_users_by_course($post_id);
		$lesson_count = (int) tutor_utils()->get_course_content_count_by($post_id, 'lesson');
		$quiz_count   = (int) tutor_utils()->get_quiz_count_by_course($post_id);
		$learn_items  = array_filter(array_map('trim', explode("\n", (string) get_post_meta($post_id, '_tutor_course_benefits', true))));
		$requirements = array_filter(array_map('trim', explode("\n", (string) get_post_meta($post_id, '_tutor_course_requirements', true))));
		$topics       = tutor_utils()->get_topics_by_course($post_id);
		if (!$topics || empty($topics->posts)) {
			// If Tutor's function returns nothing, query the 'topics' post type manually
			$topics_query = new WP_Query([
				'post_type'      => 'topics',
				'post_parent'    => $post_id,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			]);
			$topics = $topics_query;
		}
		$cat_tax      = 'course-category';

		$linked_product_id = (int) get_post_meta($post_id, '_tutor_course_product_id', true);
		$wc_linked         = ($linked_product_id && function_exists('wc_get_product')) ? wc_get_product($linked_product_id) : null;
		if ($wc_linked) {
			$price      = (float) $wc_linked->get_regular_price();
			$sale_price = (float) $wc_linked->get_sale_price();
		} else {
			$raw_price  = tutor_utils()->get_raw_course_price($post_id);
			$price      = is_object($raw_price) ? (float) ($raw_price->regular_price ?? 0) : (float) $raw_price;
			$sale_meta  = get_post_meta($post_id, '_sale_price', true);
			$sale_price = is_scalar($sale_meta) ? (float) $sale_meta : 0;
		}
	} else {
		$wc_linked    = function_exists('wc_get_product') ? wc_get_product($post_id) : null;
		$rating_avg   = $wc_linked ? (float) $wc_linked->get_average_rating() : 0;
		$rating_cnt   = $wc_linked ? (int)   $wc_linked->get_rating_count()   : 0;
		$rating_bars  = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
		$students     = 0;
		$price        = $wc_linked ? (float) $wc_linked->get_regular_price() : 0;
		$sale_price   = $wc_linked ? (float) $wc_linked->get_sale_price()    : 0;
		$lesson_count = $quiz_count = 0;
		$learn_items  = $requirements = [];
		$topics       = null;
		$cat_tax      = 'product_cat';
	}

	// ── Fake data override — takes priority over real Tutor LMS data ─
	// Set values in WP Admin › Courses › Edit Course › "Display Data" sidebar box.
	$display    = spoke_get_course_display_data($post_id);
	$rating_avg = $display['rating_avg'];
	$rating_cnt = $display['rating_cnt'];
	$students   = $display['students'];

	$eff_price    = $sale_price > 0 ? $sale_price : $price;
	$duration_raw = get_post_meta($post_id, '_course_duration', true);
	$duration     = is_array($duration_raw) ? implode(' ', array_filter($duration_raw)) : (string) $duration_raw;
	$level        = get_post_meta($post_id, '_tutor_course_level', true);
	$cats         = get_the_terms($post_id, $cat_tax);
	$cat_name     = ($cats && ! is_wp_error($cats)) ? $cats[0]->name : '';
	$cat_url      = ($cats && ! is_wp_error($cats)) ? get_term_link($cats[0]) : home_url('/courses/');
	$thumb_url    = get_the_post_thumbnail_url($post_id, 'large') ?: '';
	$instructor   = get_userdata((int) get_post_field('post_author', $post_id));

	// ── CART STATE — always defined before button HTML ─────────────
	$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
	$in_cart  = false;
	if ($wc_linked && WC()->cart) {
		foreach (WC()->cart->get_cart() as $cart_item) {
			if ((int) $cart_item['product_id'] === $wc_linked->get_id()) {
				$in_cart = true;
				break;
			}
		}
	}

	// ── BUILD HTML INTO A VARIABLE SO WE CONTROL EVERY BYTE ────────
	// We use output buffering + ob_get_clean then return — this lets
	// us write readable PHP/HTML while the final string has no stray
	// newlines that could trigger wpautop.
	ob_start();

	// ═══ HERO ═══════════════════════════════════════════════════════
	echo '<div class="sc-wrap" style="font-family:\'Inter\',sans-serif;">';
	echo '<section style="background:linear-gradient(160deg,#1A3C6E 0%,#1A1A2E 100%);padding:64px 24px;" aria-label="Course overview">';
	echo '<div class="sc-hero-grid" style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 400px;gap:64px;align-items:flex-start;">';

	// Left col
	echo '<div>';
	echo '<nav style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;font-size:13px;margin-bottom:24px;" aria-label="Breadcrumb">';
	echo '<a href="/" style="color:rgba(255,255,255,0.55);text-decoration:none;">Home</a>';
	echo '<span style="color:rgba(255,255,255,0.25);">/</span>';
	echo '<a href="' . esc_url(home_url('/courses/')) . '" style="color:rgba(255,255,255,0.55);text-decoration:none;">Courses</a>';
	if ($cat_name) {
		echo '<span style="color:rgba(255,255,255,0.25);">/</span>';
		echo '<a href="' . esc_url($cat_url) . '" style="color:rgba(255,255,255,0.55);text-decoration:none;">' . esc_html($cat_name) . '</a>';
	}
	echo '<span style="color:rgba(255,255,255,0.25);">/</span>';
	echo '<span style="color:#F4A726;font-weight:500;">' . get_the_title() . '</span>';
	echo '</nav>';

	echo '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">';
	echo '<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4px 10px;border-radius:4px;background:rgba(244,167,38,0.2);color:#F4A726;border:1px solid rgba(244,167,38,0.35);">CPD Accredited</span>';
	if ($level) {
		echo '<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4px 10px;border-radius:4px;background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.9);border:1px solid rgba(255,255,255,0.2);">' . esc_html(ucfirst($level)) . '</span>';
	}
	if ($duration) {
		echo '<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4px 10px;border-radius:4px;background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.9);border:1px solid rgba(255,255,255,0.2);">' . esc_html($duration) . '</span>';
	}
	echo '</div>';

	echo '<h1 style="color:#fff;font-weight:700;margin:0 0 16px;line-height:1.15;letter-spacing:-0.02em;font-size:clamp(1.75rem,4vw,3rem);">' . get_the_title() . '</h1>';
	echo '<p style="margin:0 0 20px;line-height:1.7;font-size:1.0625rem;color:rgba(255,255,255,0.75);">' . wp_kses_post(get_the_excerpt()) . '</p>';

	// Determine what will be shown
	$show_reviews_row  = ($rating_cnt > 0);            // true when reviews exist
	$show_students     = ($students > 1);              // true when >1 student
	$show_review_count = ($rating_cnt > 1);            // true when >1 review
	$show_dot          = ($show_review_count && $show_students);

	if ($show_reviews_row || $show_students) {
		echo '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;">';

		if ($show_reviews_row) {
			echo '<span style="font-weight:700;color:#F4A726;">' . number_format($rating_avg, 1) . '</span>';
			echo '<span style="display:flex;gap:2px;">' . spoke_render_stars($rating_avg) . '</span>';
			if ($show_review_count) {
				echo '<span style="font-size:13px;color:rgba(255,255,255,0.65);">(' . number_format($rating_cnt) . ' reviews)</span>';
			}
		}

		if ($show_dot) {
			echo '<span style="color:rgba(255,255,255,0.35);">·</span>';
		}

		if ($show_students) {
			echo '<span style="font-size:13px;color:rgba(255,255,255,0.75);">' . number_format($students) . ' Students</span>';
		}

		echo '</div>';
	}
	echo '</div>'; // end left col

	// Right col — enrol card
	echo '<div>';
	echo '<div class="sc-enrol-card" style="background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.15);overflow:hidden;position:sticky;top:90px;">';

	// Thumbnail
	echo '<div style="position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#1A3C6E,#1A1A2E);overflow:hidden;">';
	if ($thumb_url) {
		echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr(get_the_title()) . '" style="width:100%;height:100%;object-fit:cover;" width="400" height="225" loading="eager">';
	} else {
		echo '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><svg style="width:64px;height:64px;opacity:0.2;" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>';
	}
	/* echo '<button aria-label="Watch preview" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(26,60,110,0.45);border:none;cursor:pointer;"><span style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:#1A3C6E;"><svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span></button>'; */
	echo '</div>';

	// Price
	echo '<div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:8px;padding:20px 20px 0;">';
	if ($sale_price > 0) {
		echo '<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">£' . number_format($sale_price, 2) . '</span>';
		echo '<span style="font-size:1rem;text-decoration:line-through;color:#43474f;">£' . number_format($price, 2) . '</span>';
		$pct = $price > 0 ? round((1 - $sale_price / $price) * 100) : 0;
		if ($pct > 0) {
			echo '<span style="font-size:0.75rem;font-weight:700;background:rgba(186,26,26,0.08);color:#BA1A1A;padding:2px 6px;border-radius:4px;">' . $pct . '% off</span>';
		}
	} elseif ($eff_price > 0) {
		echo '<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">£' . number_format($eff_price, 2) . '</span>';
	} else {
		echo '<span style="font-size:2rem;font-weight:700;color:#1A3C6E;line-height:1;">Free</span>';
	}
	echo '</div>';

	// ── ENROL BUTTON (card‑style logic) ─────────────────────────
	$user_id      = get_current_user_id();
	$is_enrolled  = ($tutor_active && $user_id) ? tutor_utils()->is_enrolled($post_id, $user_id) : false;
	$purchased    = ($wc_linked && $user_id) ? wc_customer_bought_product('', $user_id, $wc_linked->get_id()) : false;
	$fully_owns   = $is_enrolled || $purchased;

	$cart_url     = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
	$dashboard_url = home_url('/dashboard/');

	// Determine which button to show
	$show_enrol     = ! $fully_owns && ! $in_cart && $wc_linked && $wc_linked->is_purchasable() && $wc_linked->is_in_stock();
	$show_view_cart = ! $fully_owns && $in_cart;

	echo '<div style="padding:12px 20px 0;" id="sc-btn-wrap">';

	// 1. Enrol Now (Add to Cart)
	if ($wc_linked && $wc_linked->is_purchasable() && $wc_linked->is_in_stock()) {
		$add_url  = $wc_linked->add_to_cart_url();
		$add_cls  = 'ajax_add_to_cart add_to_cart_button';
		$add_data = 'data-quantity="1" data-product_id="' . esc_attr($wc_linked->get_id()) . '" data-product_sku="' . esc_attr($wc_linked->get_sku()) . '"';
	} else {
		$add_url  = get_permalink($post_id);
		$add_cls  = '';
		$add_data = '';
	}
	$display_enrol = $show_enrol ? 'block' : 'none';
	echo '<a id="sc-enrol-btn" href="' . esc_url($add_url) . '" ' . $add_data . ' class="' . esc_attr($add_cls) . '" style="display:' . $display_enrol . ';width:100%;text-align:center;font-family:\'Inter\',sans-serif;font-size:18px;font-weight:800;padding:16px 0;background:#fdaf2e;color:#6b4500;border-radius:8px;text-decoration:none;box-sizing:border-box;box-shadow:0 4px 12px rgba(253,175,46,0.3);">Enrol Now</a>';

	// 2. View Cart
	$display_cart = $show_view_cart ? 'block' : 'none';
	echo '<a id="sc-view-cart-btn" href="' . esc_url($cart_url) . '" style="display:' . $display_cart . ';width:100%;text-align:center;font-family:\'Inter\',sans-serif;font-size:18px;font-weight:800;padding:16px 0;background:#1A3C6E;color:#fff;border-radius:8px;text-decoration:none;box-sizing:border-box;">View Cart →</a>';

	// 3. Go to Dashboard (already owns)
	$display_dash = $fully_owns ? 'block' : 'none';
	echo '<a id="sc-dashboard-btn" href="' . esc_url($dashboard_url) . '" style="display:' . $display_dash . ';width:100%;text-align:center;font-family:\'Inter\',sans-serif;font-size:18px;font-weight:800;padding:16px 0;background:#1A3C6E;color:#fff;border-radius:8px;text-decoration:none;box-sizing:border-box;">Go to Dashboard</a>';

	echo '</div>';

	// Guarantee
	echo '<div style="margin:12px 20px 0;display:flex;align-items:center;justify-content:center;gap:6px;background:#E8F4EC;border-radius:8px;padding:10px;font-size:14px;font-weight:600;color:#1A3C6E;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>30-Day Money-Back Guarantee</div>';

	// Includes list
	echo '<ul style="list-style:none;margin:0;padding:16px 20px 8px;display:flex;flex-direction:column;gap:10px;">';
	if ($lesson_count) {
		echo '<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' . (int) $lesson_count . ' lessons</li>';
	}
	if ($quiz_count) {
		echo '<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' . (int) $quiz_count . ' quizzes</li>';
	}
	echo '<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Mobile &amp; desktop access</li>';
	echo '<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>CPD certificate on completion</li>';
	echo '<li style="display:flex;align-items:center;gap:10px;font-size:14px;color:#43474f;"><svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Direct Q&amp;A with instructor</li>';
	echo '</ul>';

	// Wishlist / Share
	$share_url   = esc_js(get_permalink());
	$share_title = esc_js(get_the_title());
	echo '<div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px 20px;border-top:1px solid rgba(0,0,0,0.06);">';
	echo '<button id="sc-share-btn" onclick="scShare(\'' . $share_url . '\',\'' . $share_title . '\')" style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:#1A3C6E;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:6px;transition:background 150ms;" onmouseenter="this.style.background=\'rgba(26,60,110,0.07)\'" onmouseleave="this.style.background=\'none\'">';
	echo '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>';
	echo '<span id="sc-share-label">Share</span>';
	echo '</button>';
	echo '</div>';

	echo '</div>'; // end enrol card
	echo '</div>'; // end right col
	echo '</div>'; // end sc-hero-grid
	echo '</section>';

	// ═══ TABS ════════════════════════════════════════════════════════
	echo '<div style="background:#f8f9fa;padding:48px 24px 80px;">';
	echo '<div style="max-width:1280px;margin:0 auto;">';
	echo '<div class="sc-tab-nav" role="tablist" aria-label="Course sections">';
	echo '<button class="sc-tab sc-tab--active" role="tab" aria-selected="true" aria-controls="sc-tab-overview" id="sc-btn-overview">Overview</button>';
	echo '<button class="sc-tab" role="tab" aria-selected="false" aria-controls="sc-tab-curriculum" id="sc-btn-curriculum">Curriculum</button>';
	/* echo '<button class="sc-tab" role="tab" aria-selected="false" aria-controls="sc-tab-instructor" id="sc-btn-instructor">Instructor</button>';*/

	echo '<button class="sc-tab" role="tab" aria-selected="false" aria-controls="sc-tab-reviews" id="sc-btn-reviews">Reviews</button>';

	echo '</div>';

	// ── Overview tab ──────────────────────────────────────────────
	echo '<div id="sc-tab-overview" class="sc-tab-panel" role="tabpanel" aria-labelledby="sc-btn-overview">';
	if (! empty($learn_items)) {
		echo '<div class="sc-what-learn"><h2 class="sc-section-h">What you\'ll learn</h2><ul class="sc-learn-list">';
		foreach ($learn_items as $item) {
			echo '<li>' . esc_html($item) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '<div class="sc-description" style="margin-top:24px;">';
	the_content();
	echo '</div>';
	if (! empty($requirements)) {
		echo '<div style="margin-top:32px;"><h2 class="sc-section-h">Requirements</h2><ul style="padding-left:20px;margin:0;display:flex;flex-direction:column;gap:8px;">';
		foreach ($requirements as $r) {
			echo '<li style="font-size:14px;color:#43474f;line-height:1.6;">' . esc_html($r) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '</div>';

	// ── Curriculum tab ────────────────────────────────────────────
	echo '<div id="sc-tab-curriculum" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-curriculum">';
	echo '<h2 class="sc-section-h">Course Curriculum</h2>';

	$is_enrolled = $tutor_active && is_user_logged_in() && tutor_utils()->is_enrolled($post_id, get_current_user_id());

	if ($tutor_active && $topics && !empty($topics->posts)) {
		echo '<div style="display:flex;flex-direction:column;gap:12px;">';
		foreach ($topics->posts as $idx => $topic) {
			$lessons = tutor_utils()->get_lessons_by_topic($topic->ID);
			if (! $lessons || ! is_array($lessons) || empty($lessons)) {
				$lessons = get_posts([
					'post_type'      => 'lesson',
					'post_parent'    => $topic->ID,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				]);
			}
			$lcnt = $lessons ? count($lessons) : 0;
			$lcnt    = $lessons ? count($lessons) : 0;
			$pid     = 'sc-topic-' . $topic->ID;
			echo '<div style="border:1px solid rgba(0,0,0,0.08);border-radius:8px;overflow:hidden;">';
			echo '<button class="sc-topic-btn" style="width:100%;display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:14px 20px;text-align:left;background:#1A3C6E;border:none;cursor:pointer;" aria-expanded="' . ($idx === 0 ? 'true' : 'false') . '" aria-controls="' . esc_attr($pid) . '">';
			echo '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.6);flex-shrink:0;">Section ' . ($idx + 1) . '</span>';
			echo '<h3 style="flex:1;font-size:15px;font-weight:600;color:#fff;margin:0;">' . esc_html($topic->post_title) . '</h3>';
			echo '<span style="font-size:13px;color:rgba(255,255,255,0.65);flex-shrink:0;">' . $lcnt . ' lesson' . ($lcnt !== 1 ? 's' : '') . '</span>';
			echo '<svg class="sc-topic-chevron" style="width:16px;height:16px;color:#fff;flex-shrink:0;transition:transform 200ms;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
			echo '</button>';
			echo '<ul class="sc-topic-lessons ' . ($idx === 0 ? 'sc-topic-lessons--open' : '') . '" style="list-style:none;margin:0;padding:0;" id="' . esc_attr($pid) . '">';
			if ($lessons) {
				foreach ($lessons as $lesson) {
					$prev = get_post_meta($lesson->ID, '_is_preview', true);
					$dur  = get_post_meta($lesson->ID, '_lesson_duration', true);
					$qz   = ('tutor_quiz' === $lesson->post_type);
					echo '<li style="display:flex;align-items:center;gap:12px;padding:12px 20px;background:#fff;border-top:1px solid rgba(0,0,0,0.06);">';
					if ($qz) {
						echo '<svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
					} else {
						echo '<svg style="color:#1A3C6E;flex-shrink:0;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
					}
					if ($prev) {
						echo '<a href="' . esc_url(get_permalink($lesson->ID)) . '" style="flex:1;font-size:14px;font-weight:500;color:#1A3C6E;text-decoration:none;">' . esc_html($lesson->post_title) . '</a>';
						echo '<span style="font-size:10px;font-weight:700;background:rgba(26,60,110,0.08);color:#1A3C6E;padding:2px 6px;border-radius:4px;flex-shrink:0;">Free Preview</span>';
					} else {
						echo '<span style="flex:1;font-size:14px;font-weight:500;color:#43474f;">' . esc_html($lesson->post_title) . '</span>';
						echo '<svg style="opacity:0.4;flex-shrink:0;" width="14" height="14" fill="none" stroke="#43474f" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>';
					}
					if ($dur) {
						echo '<span style="font-size:12px;color:#43474f;opacity:0.7;flex-shrink:0;">' . esc_html($dur) . '</span>';
					}
					echo '</li>';
				}
			}
			echo '</ul></div>';
		}
		echo '</div>';
	} elseif ($tutor_active && $is_enrolled) {
		echo '<p style="font-size:15px;color:#43474f;">Curriculum data is not yet available for this course.</p>';
	} else {
		echo '<p style="font-size:15px;color:#43474f;">Curriculum will be available after enrolment.</p>';
	}
	echo '</div>';

	// ── Instructor tab ────────────────────────────────────────────
	/* echo '<div id="sc-tab-instructor" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-instructor">';
	if ($instructor) {
		$bio   = get_user_meta($instructor->ID, 'description', true);
		$photo = get_user_meta($instructor->ID, '_tutor_profile_photo', true) ?: get_avatar_url($instructor->ID, ['size' => 120]);
		$jtit  = get_user_meta($instructor->ID, '_tutor_profile_job_title', true);
		$icrs  = get_posts(['post_type' => 'courses', 'author' => $instructor->ID, 'numberposts' => -1, 'fields' => 'ids']);
		echo '<h2 class="sc-section-h">Your Instructor</h2>';
		echo '<div style="display:flex;flex-wrap:wrap;gap:24px;padding:24px;background:#f3f4f5;border-radius:12px;">';
		if ($photo) {
			echo '<img src="' . esc_url($photo) . '" alt="' . esc_attr($instructor->display_name) . '" style="width:100px;height:100px;border-radius:8px;object-fit:cover;flex-shrink:0;" width="100" height="100" loading="lazy">';
		} else {
			echo '<div style="width:100px;height:100px;border-radius:8px;background:#1A3C6E;color:#fff;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' . esc_html(mb_substr($instructor->display_name, 0, 2)) . '</div>';
		}
		echo '<div style="flex:1;min-width:200px;">';
		echo '<h3 style="font-size:20px;font-weight:700;color:#1A3C6E;margin:0 0 4px;">' . esc_html($instructor->display_name) . '</h3>';
		if ($jtit) {
			echo '<p style="font-size:14px;font-weight:600;color:#F4A726;margin:0 0 12px;">' . esc_html($jtit) . '</p>';
		}
		echo '<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">';
		echo '<div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;">' . count($icrs) . '</strong><span style="font-size:11px;color:#43474f;">Courses</span></div>';
		if ($students) {
			echo '<div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;">' . number_format($students) . '+</strong><span style="font-size:11px;color:#43474f;">Students</span></div>';
		}
		echo '<div style="background:#fff;border-radius:8px;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);"><strong style="display:block;font-size:18px;font-weight:700;color:#1A3C6E;">' . number_format($rating_avg, 1) . '</strong><span style="font-size:11px;color:#43474f;">Rating</span></div>';
		echo '</div>';
		if ($bio) {
			echo '<p style="font-size:14px;color:#43474f;line-height:1.7;margin:0;">' . esc_html($bio) . '</p>';
		}
		echo '</div></div>';
	} else {
		echo '<p style="font-size:15px;color:#43474f;">Instructor information not available.</p>';
	}
	echo '</div>'; */

	// ── Reviews tab ───────────────────────────────────────────────

	echo '<div id="sc-tab-reviews" class="sc-tab-panel sc-tab-panel--hidden" role="tabpanel" aria-labelledby="sc-btn-reviews">';
	/* echo '<h2 class="sc-section-h">Student Feedback</h2>';
	echo '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:32px;padding:24px;background:#f3f4f5;border-radius:12px;margin-bottom:24px;">';
	echo '<div style="text-align:center;"><strong style="display:block;font-size:3.5rem;font-weight:900;color:#F4A726;line-height:1;">' . number_format($rating_avg, 1) . '</strong><span style="display:flex;justify-content:center;gap:2px;margin:4px 0;">' . spoke_render_stars($rating_avg, 'lg') . '</span><span style="font-size:14px;font-weight:600;color:#1A3C6E;">Course Rating</span></div>';
	echo '<div style="flex:1;min-width:200px;display:flex;flex-direction:column;gap:8px;">';
	foreach ($rating_bars as $star => $cnt) {
		$pct = $rating_cnt > 0 ? round(($cnt / $rating_cnt) * 100) : 0;
		echo '<div style="display:flex;align-items:center;gap:12px;"><span style="font-size:12px;color:#43474f;width:16px;text-align:right;flex-shrink:0;">' . $star . '</span><div style="flex:1;height:8px;background:rgba(0,0,0,0.08);border-radius:4px;overflow:hidden;"><div style="height:100%;width:' . $pct . '%;background:#F4A726;border-radius:4px;"></div></div><span style="font-size:12px;font-weight:600;color:#43474f;width:32px;flex-shrink:0;">' . $pct . '%</span></div>';
	}
	echo '</div></div>'; */
	if ($tutor_active) {
		if (function_exists('tutor_course_reviews_template')) {
			tutor_course_reviews_template();
		} else {
			// Fallback: include Tutor's reviews template manually
			$template = tutor()->path . 'templates/single/course/reviews.php';
			if (file_exists($template)) {
				include $template;
			}
		}
	}
	echo '</div>';

	echo '</div></div>'; // close tabs wrapper

	// ═══ RELATED COURSES ═════════════════════════════════════════════
	$rel_ids = get_posts([
		'post_type'      => 'courses',
		'posts_per_page' => 3,
		'post__not_in'   => [$post_id],
		'orderby'        => 'rand',
		'post_status'    => 'publish',
		'fields'         => 'ids',
	]);

	if (! empty($rel_ids)) {
		echo '<section style="background:#f3f4f5;padding:56px 24px;border-top:1px solid rgba(0,0,0,0.06);">';
		echo '<div style="max-width:1280px;margin:0 auto;">';
		echo '<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-bottom:32px;">';
		echo '<h2 style="font-size:24px;font-weight:700;color:#1A3C6E;margin:0;">Continue Your Learning</h2>';
		echo '<a href="' . esc_url(home_url('/courses/')) . '" style="font-size:14px;font-weight:700;color:#1A3C6E;text-decoration:none;">View All Courses →</a>';
		echo '</div>';
		spoke_render_card_grid($rel_ids, [
			'context'    => 'related',
			'img_height' => 170,
			'lazy'       => true,
		], 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6');
		echo '</div></section>';
	}

	echo '</div>'; // close sc-wrap

	// ═══ CSS ═════════════════════════════════════════════════════════
	echo '<style>
.sc-tab-nav{display:flex;border-bottom:2px solid rgba(0,0,0,0.08);margin-bottom:32px;overflow-x:auto;scrollbar-width:none;}
.sc-tab-nav::-webkit-scrollbar{display:none;}
.sc-tab{flex-shrink:0;height:48px;padding:0 24px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-family:\'Inter\',sans-serif;font-size:16px;font-weight:500;color:#43474f;cursor:pointer;transition:color 200ms,border-color 200ms;}
.sc-tab:hover{color:#1A3C6E;}
.sc-tab--active{color:#1A3C6E;font-weight:700;border-bottom-color:#F4A726;}
.sc-tab-panel{padding-top:0;}
.sc-tab-panel--hidden{display:none!important;}
.sc-section-h{font-size:22px;font-weight:700;color:#1A3C6E;margin:0 0 16px;}
.sc-what-learn{padding:24px;background:#f3f4f5;border-radius:12px;margin-bottom:32px;}
.sc-learn-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;list-style:none;margin:0;padding:0;}
.sc-learn-list li{display:flex;align-items:flex-start;gap:8px;font-size:14px;color:#1A3C6E;}
.sc-learn-list li::before{content:\'✓\';color:#1A3C6E;font-weight:700;flex-shrink:0;margin-top:1px;}
.sc-description p{font-size:15px;line-height:1.8;color:#43474f;margin:0 0 16px;}
.sc-description h2{font-size:22px;font-weight:700;color:#1A3C6E;margin:24px 0 12px;}
.sc-description h3{font-size:18px;font-weight:700;color:#1A3C6E;margin:20px 0 10px;}
.sc-description ul,.sc-description ol{padding-left:20px;margin:0 0 16px;}
.sc-description li{font-size:15px;color:#43474f;line-height:1.7;margin-bottom:6px;}
.sc-topic-lessons{display:none;}
.sc-topic-lessons--open{display:block;}
.sc-topic-btn[aria-expanded="true"] .sc-topic-chevron{transform:rotate(180deg);}
.sc-enrol-card .tutor-btn.tutor-btn-primary,.sc-enrol-card .tutor-add-to-cart-button{display:block!important;margin:0!important;padding:14px 0!important;width:100%!important;background:#F4A726!important;color:#6b4500!important;border:none!important;border-radius:8px!important;font-family:\'Inter\',sans-serif!important;font-size:1.0625rem!important;font-weight:700!important;text-align:center!important;box-sizing:border-box!important;}
.sc-enrol-card form{margin:0!important;}
#sc-enrol-btn,#sc-view-cart-btn{transition:filter 150ms ease,transform 150ms ease;}
#sc-enrol-btn:hover,#sc-view-cart-btn:hover{filter:brightness(1.07);transform:translateY(-1px);}
#sc-btn-wrap .added_to_cart,
#sc-btn-wrap .wc-forward,
#sc-btn-wrap a[href*="cart"]:not(#sc-view-cart-btn):not(#sc-enrol-btn){
    display:none!important;
}
@media(max-width:1024px){.sc-hero-grid{grid-template-columns:1fr!important;}.sc-hero-grid>div:last-child{order:-1;}.sc-enrol-card{position:static!important;max-width:480px;}}
@media(max-width:640px){.sc-learn-list{grid-template-columns:1fr;}}
</style>';
	// Flush the output buffer
	$html = ob_get_clean();

	// Final scrub of empty tags.
	$html = preg_replace('/<br\s*\/?>/i', '', $html);
	$html = preg_replace('/<p>(\s|&nbsp;|\xc2\xa0)*<\/p>/i', '', $html);
	$html = preg_replace('/(\s*\n){3,}/', "\n", $html);

	echo $html;
}


// At the top of the file, outside any function
add_action('wp_footer', function (): void {
	if (!is_singular('courses')) {
		return;
	}
?>
	<script>
		(function() {
			'use strict';

			// Remove all <br> tags
			document.querySelectorAll('br').forEach(el => el.remove());
			// ── Remove empty <p> tags ─────────────────────────────────────
			document.querySelectorAll('p').forEach(function(p) {
				if (p.innerHTML.replace(/&nbsp;/g, '').replace(/\xc2\xa0/g, '').trim() === '') {
					p.remove();
				}
			});


			// ── Tab switching ─────────────────────────────────────────────
			var tabs = document.querySelectorAll('.sc-tab');
			var panels = document.querySelectorAll('.sc-tab-panel');
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function() {
					var t = tab.getAttribute('aria-controls');
					tabs.forEach(function(x) {
						x.classList.remove('sc-tab--active');
						x.setAttribute('aria-selected', 'false');
					});
					panels.forEach(function(p) {
						p.classList.add('sc-tab-panel--hidden');
					});
					tab.classList.add('sc-tab--active');
					tab.setAttribute('aria-selected', 'true');
					var p = document.getElementById(t);
					if (p) p.classList.remove('sc-tab-panel--hidden');
				});
			});

			// ── Curriculum accordion ──────────────────────────────────────
			document.querySelectorAll('.sc-topic-btn').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var p = document.getElementById(btn.getAttribute('aria-controls'));
					if (!p) return;
					var o = btn.getAttribute('aria-expanded') === 'true';
					btn.setAttribute('aria-expanded', String(!o));
					p.classList.toggle('sc-topic-lessons--open', !o);
				});
			});

			// ── Share button ──────────────────────────────────────────────
			window.scShare = function(url, title) {
				function copyFallback(u) {
					var ta = document.createElement('textarea');
					ta.value = u;
					ta.style.position = 'fixed';
					ta.style.opacity = '0';
					document.body.appendChild(ta);
					ta.focus();
					ta.select();
					try {
						document.execCommand('copy');
					} catch (e) {}
					document.body.removeChild(ta);
					var l = document.getElementById('sc-share-label');
					if (l) {
						l.textContent = 'Copied!';
						setTimeout(function() {
							l.textContent = 'Share';
						}, 2000);
					}
				}
				if (navigator.share && window.isSecureContext) {
					navigator.share({
						title: title,
						url: url
					}).catch(function() {});
				} else if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(url).then(function() {
						var l = document.getElementById('sc-share-label');
						if (l) {
							l.textContent = 'Copied!';
							setTimeout(function() {
								l.textContent = 'Share';
							}, 2000);
						}
					}).catch(function() {
						copyFallback(url);
					});
				} else {
					copyFallback(url);
				}
			};

			// ── Enrol button swap ─────────────────────────────────────────
			var btnWrap = document.getElementById('sc-btn-wrap');
			var enrolBtn = document.getElementById('sc-enrol-btn');
			var viewCartBtn = document.getElementById('sc-view-cart-btn');
			var dashBtn = document.getElementById('sc-dashboard-btn');

			function showViewCart() {
				if (enrolBtn) enrolBtn.style.display = 'none';
				if (viewCartBtn) viewCartBtn.style.display = 'block';
				if (dashBtn) dashBtn.style.display = 'none';
				if (btnWrap) {
					btnWrap.querySelectorAll('a.added_to_cart, a.wc-forward').forEach(function(a) {
						a.remove();
					});
				}
			}

			if (typeof jQuery !== 'undefined') {
				jQuery(document.body).on('added_to_cart', function() {
					showViewCart();
				});
			}

			if (btnWrap) {
				new MutationObserver(function(mutations) {
					mutations.forEach(function(m) {
						m.addedNodes.forEach(function(node) {
							if (node.nodeType !== 1) return;
							if (node.classList && (node.classList.contains('added_to_cart') || node.classList.contains('wc-forward'))) {
								node.remove();
								showViewCart();
							}
						});
						if (m.type === 'attributes' && m.attributeName === 'class') {
							var el = m.target;
							if (el && (el.classList.contains('added') || el.classList.contains('added_to_cart'))) {
								showViewCart();
							}
						}
					});
				}).observe(btnWrap, {
					childList: true,
					subtree: true,
					attributes: true,
					attributeFilter: ['class']
				});
			}

			// ── Card CTA swap (related courses) ───────────────────────────
			new MutationObserver(function(mutations) {
				mutations.forEach(function(m) {
					m.addedNodes.forEach(function(node) {
						if (node.nodeType !== 1) return;
						var wrap = node.closest ? node.closest('.spoke-card-cta') : null;
						if (wrap && node.classList && (node.classList.contains('added_to_cart') || node.classList.contains('wc-forward'))) {
							node.remove();
							var cartBtn = wrap.querySelector('.spoke-cta-view-cart');
							var addBtn = wrap.querySelector('.spoke-cta-add');
							if (cartBtn) cartBtn.style.display = 'inline-flex';
							if (addBtn) addBtn.style.display = 'none';
						}
					});
				});
			}).observe(document.body, {
				childList: true,
				subtree: true
			});

		})();
	</script>
<?php
}, 20);
