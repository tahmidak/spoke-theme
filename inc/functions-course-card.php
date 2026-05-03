<?php

/**
 * SPOKE COURSE CARD — inc/functions-course-card.php
 *
 * Single source of truth for every course card rendered on the site:
 *   • Archive page   (functions-archive-addon.php  → spoke_render_course_archive)
 *   • Hot Deals      (functions-hot-deals.php       → spoke_render_hot_deals_section)
 *   • Single course  (functions-single-course.php   → related courses grid)
 *
 * Public API
 * ──────────
 *  spoke_get_card_data( int $post_id ) : array|null
 *      Fetches & normalises all data for one course post.
 *      Returns null when the post doesn't exist / isn't published.
 *
 *  spoke_render_course_card( array $card, array $opts = [] ) : void
 *      Echoes one card.
 *
 *  spoke_get_course_card_html( array $card, array $opts = [] ) : string
 *      Returns card HTML as a string (useful when building grids).
 *
 * $opts keys (all optional)
 * ─────────────────────────
 *  'context'   string  'archive' | 'hot-deals' | 'related'   default: 'archive'
 *  'show_badge' bool   Show top-left promo badge              default: false
 *  'badge_text' string Text for the badge                     default: ''
 *  'img_height' int    Thumbnail height in px                 default: 170
 *  'lazy'       bool   Lazy-load image                        default: true
 *
 * Usage examples
 * ──────────────
 *  // In a WP_Query loop — cheapest path:
 *  $card = spoke_get_card_data( get_the_ID() );
 *  if ( $card ) { spoke_render_course_card( $card ); }
 *
 *  // Build an array of HTML strings for a JS-seeded grid:
 *  $html = spoke_get_course_card_html( $card, [ 'context' => 'hot-deals' ] );
 *
 * @package SpokeTheme
 */

/**
 * Get all WooCommerce product IDs currently in the cart.
 * Returns an empty array if WC is not active or cart is empty.
 */
function spoke_get_cart_product_ids(): array
{
	if (! function_exists('WC') || ! WC()->cart) {
		return [];
	}
	$ids = [];
	foreach (WC()->cart->get_cart() as $item) {
		$ids[] = (int) $item['product_id'];
	}
	return array_unique($ids);
}

// ─────────────────────────────────────────────────────────────────
// 1. DATA HELPER
// ─────────────────────────────────────────────────────────────────

/**
 * Collect and normalise all data needed for a course card.
 *
 * Priority rules (same as the rest of the theme):
 *   fake display-data   >  real Tutor LMS data
 *   linked WC product   >  raw Tutor price meta
 *
 * @param  int $post_id  WP post ID of a 'courses' post.
 * @return array|null    Normalised card data, or null if unusable.
 */
function spoke_get_card_data(int $post_id, array $cart_pids = []): ?array
{

	$post = get_post($post_id);
	if (! $post || 'publish' !== $post->post_status) {
		return null;
	}

	$user_id   = get_current_user_id();
	$is_logged = $user_id > 0;

	// ── Display data (fake overrides real) ────────────────────────
	$display    = function_exists('spoke_get_course_display_data')
		? spoke_get_course_display_data($post_id)
		: ['rating_avg' => 0.0, 'rating_cnt' => 0, 'students' => 0, 'has_fake' => false];

	// ── WooCommerce product ───────────────────────────────────────
	$wc_pid     = (int) get_post_meta($post_id, '_tutor_course_product_id', true);
	$wc_product = ($wc_pid && function_exists('wc_get_product')) ? wc_get_product($wc_pid) : null;

	if ($wc_product) {
		$price           = (float) $wc_product->get_regular_price();
		$sale_price      = (float) $wc_product->get_sale_price();
		$add_to_cart_url = $wc_product->add_to_cart_url();
		$can_add         = $wc_product->is_purchasable() && $wc_product->is_in_stock();
	} else {
		$price           = (float) get_post_meta($post_id, '_spoke_price', true);
		$sale_price      = 0.0;
		$add_to_cart_url = get_permalink($post_id);
		$can_add         = false;
		$wc_pid          = 0;
	}

	$effective_price = $sale_price > 0 ? $sale_price : $price;
	$discount_pct    = ($sale_price > 0 && $price > 0)
		? (int) round((1 - $sale_price / $price) * 100)
		: 0;

	// ── Is in cart? ───────────────────────────────────────────────
	// Use pre-fetched cart IDs if passed, otherwise fall back to
	// checking the cart directly (single card render scenario).
	$in_cart = false;
	if ($wc_product) {
		if (! empty($cart_pids)) {
			$in_cart = in_array($wc_product->get_id(), $cart_pids, true);
		} elseif (function_exists('WC') && WC()->cart) {
			foreach (WC()->cart->get_cart() as $item) {
				if ((int) $item['product_id'] === $wc_product->get_id()) {
					$in_cart = true;
					break;
				}
			}
		}
	}

	// ── Purchased / enrolled ──────────────────────────────────────
	$purchased = false;
	if ($is_logged) {
		if (function_exists('tutor_utils')) {
			$purchased = (bool) tutor_utils()->is_enrolled($post_id, $user_id);
		}
		// WC order fallback handled inline per-card to avoid N+1;
		// callers that pre-fetch $bought_pids should pass purchased = true themselves.
	}

	// ── Taxonomy ──────────────────────────────────────────────────
	$cats     = get_the_terms($post_id, 'course-category');
	$cat_name = ($cats && ! is_wp_error($cats)) ? $cats[0]->name : '';
	$cat_slug = ($cats && ! is_wp_error($cats)) ? $cats[0]->slug : '';

	// ── Misc post meta ────────────────────────────────────────────
	$level        = (string) get_post_meta($post_id, '_tutor_course_level', true);
	$duration_raw = get_post_meta($post_id, '_course_duration', true);
	$duration     = is_array($duration_raw) ? implode(' ', array_filter($duration_raw)) : (string) $duration_raw;

	$lesson_count = 0;
	$quiz_count   = 0;
	if (function_exists('tutor_utils')) {
		$lesson_count = (int) tutor_utils()->get_course_content_count_by($post_id, 'lesson');
		$quiz_count   = (int) tutor_utils()->get_quiz_count_by_course($post_id);
	}

	// ── Thumbnail ─────────────────────────────────────────────────
	$thumb_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: '';

	// ── Excerpt ───────────────────────────────────────────────────
	$excerpt = wp_trim_words(get_the_excerpt($post_id), 18, '…');

	// ── Instructor ────────────────────────────────────────────────
	$instructor_id   = (int) get_post_field('post_author', $post_id);
	$instructor_data = get_userdata($instructor_id);
	$instructor_name = $instructor_data ? $instructor_data->display_name : '';

	// ── URLs ──────────────────────────────────────────────────────
	$cart_url      = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
	$dashboard_url = home_url('/dashboard/');

	return [
		'id'              => $post_id,
		'title'           => get_the_title($post_id),
		'url'             => get_permalink($post_id),
		'excerpt'         => $excerpt,
		'thumb'           => $thumb_url,
		'cat_name'        => $cat_name,
		'cat_slug'        => $cat_slug,
		'level'           => $level,
		'duration'        => $duration,
		'lesson_count'    => $lesson_count,
		'quiz_count'      => $quiz_count,
		'price'           => $price,
		'sale_price'      => $sale_price,
		'effective_price' => $effective_price,
		'discount_pct'    => $discount_pct,
		'rating_avg'      => (float) $display['rating_avg'],
		'rating_cnt'      => (int)   $display['rating_cnt'],
		'students'        => (int)   $display['students'],
		'wc_product_id'   => $wc_pid,
		'add_to_cart_url' => $add_to_cart_url,
		'can_add'         => $can_add,
		'in_cart'         => $in_cart,
		'purchased'       => $purchased,
		'instructor_name' => $instructor_name,
		'cart_url'        => $cart_url,
		'dashboard_url'   => $dashboard_url,
	];
}


// ─────────────────────────────────────────────────────────────────
// 2. STAR BUILDER (self-contained so the template part is portable)
// ─────────────────────────────────────────────────────────────────

/**
 * Build star SVGs for a given rating average.
 *
 * @param float  $avg   0–5 rating average.
 * @param string $size  'sm' (13px) | 'md' (16px) | 'lg' (20px).
 * @return string  Raw HTML string of SVG stars.
 */
function spoke_card_stars(float $avg, string $size = 'sm'): string
{
	$sizes = ['sm' => 13, 'md' => 16, 'lg' => 20];
	$px    = $sizes[$size] ?? 13;
	$path  = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
	$out   = '';
	for ($i = 1; $i <= 5; $i++) {
		$fill  = $i <= (int) round($avg) ? '#F4A726' : '#D1D5DB';
		$out  .= "<svg width='{$px}' height='{$px}' fill='{$fill}' viewBox='0 0 20 20' aria-hidden='true'><path d='{$path}'/></svg>";
	}
	return $out;
}


// ─────────────────────────────────────────────────────────────────
// 3. CTA BUTTON BUILDER
// ─────────────────────────────────────────────────────────────────

/**
 * Returns the correct CTA markup for a course card.
 *
 * Three states, consistent across all contexts:
 *   1. Purchased / enrolled  → "Go to Dashboard"  (navy)
 *   2. In cart               → "View Cart →"       (navy)
 *   3. Can add to cart       → "Add to Cart"       (amber, WC AJAX classes)
 *   4. No WC product         → "View Course"       (navy)
 *
 * @param array  $card   Output of spoke_get_card_data().
 * @param string $height CSS height value for the button.  default: '40px'
 * @return string
 */
function spoke_card_cta(array $card, string $height = '40px'): string
{

	$base_style = "display:inline-flex;align-items:center;height:{$height};padding:0 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;transition:filter 150ms ease;";
	$navy_style = $base_style . 'background:#1A3C6E;color:#fff;';
	$amber_style = $base_style . 'background:#F4A726;color:#6b4500;';

	$wrap_open  = '<div class="spoke-card-cta" data-course="' . esc_attr($card['id']) . '" data-pid="' . esc_attr($card['wc_product_id']) . '">';
	$wrap_close = '</div>';

	if ($card['purchased']) {
		return $wrap_open
			. '<a href="' . esc_url($card['dashboard_url']) . '" class="spoke-cta-dashboard" style="' . $navy_style . '">Go to Dashboard</a>'
			. $wrap_close;
	}

	if ($card['in_cart']) {
		return $wrap_open
			. '<a href="' . esc_url($card['cart_url']) . '" class="spoke-cta-view-cart" style="' . $navy_style . '">View Cart →</a>'
			. $wrap_close;
	}

	if ($card['can_add']) {
		return $wrap_open
			. '<a href="' . esc_url($card['add_to_cart_url']) . '" '
			. 'data-quantity="1" data-product_id="' . esc_attr($card['wc_product_id']) . '" '
			. 'class="ajax_add_to_cart add_to_cart_button spoke-cta-add" rel="nofollow" '
			. 'style="' . $amber_style . '" '
			. 'onmouseenter="this.style.filter=\'brightness(1.07)\'" onmouseleave="this.style.filter=\'\'">Add to Cart</a>'
			// Hidden "View Cart" link — JS shows it after WC fires added_to_cart.
			. '<a href="' . esc_url($card['cart_url']) . '" class="spoke-cta-view-cart" style="' . $navy_style . 'display:none;">View Cart →</a>'
			. $wrap_close;
	}

	// Fallback: no purchasable product linked.
	return $wrap_open
		. '<a href="' . esc_url($card['url']) . '" class="spoke-cta-view" style="' . $navy_style . '">View Course</a>'
		. $wrap_close;
}


// ─────────────────────────────────────────────────────────────────
// 4. PRICE DISPLAY BUILDER
// ─────────────────────────────────────────────────────────────────

/**
 * Returns formatted price HTML.
 *
 * @param array $card
 * @return string
 */
function spoke_card_price(array $card): string
{
	if ($card['effective_price'] <= 0) {
		return '<span style="font-size:20px;font-weight:900;color:#1A3C6E;">Free</span>';
	}

	$html = '<span style="font-size:20px;font-weight:900;color:#1A3C6E;">£' . number_format($card['effective_price'], 2) . '</span>';

	if ($card['discount_pct'] > 0) {
		$html .= '<span style="font-size:13px;text-decoration:line-through;margin-left:6px;color:#43474f;">£' . number_format($card['price'], 2) . '</span>';
	}

	return $html;
}


// ─────────────────────────────────────────────────────────────────
// 5. MAIN CARD RENDERER
// ─────────────────────────────────────────────────────────────────

/**
 * Echo a single course card.
 *
 * @param array $card  Output of spoke_get_card_data().
 * @param array $opts  See file header for available keys.
 */
function spoke_render_course_card(array $card, array $opts = []): void
{
	echo spoke_get_course_card_html($card, $opts);
}

/**
 * Return a single course card as an HTML string.
 *
 * @param array $card
 * @param array $opts
 * @return string
 */
function spoke_get_course_card_html(array $card, array $opts = []): string
{

	$context    = $opts['context']    ?? 'archive';
	$show_badge = $opts['show_badge'] ?? false;
	$badge_text = $opts['badge_text'] ?? '';
	$img_height = $opts['img_height'] ?? 170;
	$lazy       = $opts['lazy']       ?? true;
	$loading    = $lazy ? 'lazy' : 'eager';

	ob_start();

	// ── Thumbnail ─────────────────────────────────────────────────
	$thumb_html = $card['thumb']
		? '<img src="' . esc_url($card['thumb']) . '" alt="' . esc_attr($card['title']) . '" class="w-full h-full object-cover" loading="' . $loading . '" width="400" height="' . $img_height . '">'
		: '<div class="hdb-ph" style="background:#1A3C6E;display:flex;align-items:center;justify-content:center;padding:16px;">'
		. '<span style="color:#fff;font-size:14px;font-weight:700;text-align:center;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">'
		. esc_html($card["title"])
		. '</span></div>';


	// ── Overlay badges on thumbnail ───────────────────────────────
	$cat_badge   = $card['cat_name']
		? '<span style="position:absolute;top:10px;left:10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:3px 10px;border-radius:4px;background:#F4A726;color:#6b4500;">' . esc_html($card['cat_name']) . '</span>'
		: '';

	$disc_badge  = $card['discount_pct'] > 0
		? '<span style="position:absolute;bottom:10px;right:10px;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;background:#BA1A1A;color:#fff;">' . (int) $card['discount_pct'] . '% OFF</span>'
		: '';

	$promo_badge = ($show_badge && $badge_text)
		? '<span style="position:absolute;top:10px;right:10px;font-size:10px;font-weight:700;text-transform:uppercase;padding:3px 10px;border-radius:4px;background:#1A3C6E;color:#fff;">' . esc_html($badge_text) . '</span>'
		: '';

	// ── Stars + meta ──────────────────────────────────────────────
	$stars = spoke_card_stars($card['rating_avg']);

	$rating_html = '';
	if ($card['rating_avg'] > 0) {
		$rating_html .= '<span style="display:flex;gap:2px;">' . $stars . '</span>';
		$rating_html .= '<span style="font-size:12px;font-weight:700;color:#1A3C6E;">' . number_format($card['rating_avg'], 1) . '</span>';
		if ($card['rating_cnt'] > 0) {
			$rating_html .= '<span style="font-size:12px;color:#43474f;">(' . number_format($card['rating_cnt']) . ')</span>';
		}
	}
	if ($card['students'] > 0) {
		$rating_html .= '<span style="font-size:12px;color:#43474f;margin-left:auto;">' . number_format($card['students']) . ' students</span>';
	}

	// ── Instructor meta (shown in 'archive' & 'hot-deals') ────────
	/*$instructor_html = '';
	if ( in_array( $context, [ 'archive', 'hot-deals' ], true ) && $card['instructor_name'] ) {
		$instructor_html = '<p style="font-size:12px;color:#43474f;margin:0;">By ' . esc_html( $card['instructor_name'] ) . '</p>';
	} */

	// ── Excerpt (shown in 'archive' context only) ─────────────────
	$excerpt_html = '';
	if ('archive' === $context && $card['excerpt']) {
		$excerpt_html = '<p style="font-size:13px;color:#43474f;line-height:1.6;margin:0 0 4px;">' . esc_html($card['excerpt']) . '</p>';
	}

	// ── Level / duration meta pills ───────────────────────────────
	$meta_pills = '';
	if ($card['level']) {
		$meta_pills .= '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:2px 8px;border-radius:4px;background:rgba(26,60,110,0.07);color:#1A3C6E;">' . esc_html(ucfirst($card['level'])) . '</span>';
	}
	if ($card['duration']) {
		$meta_pills .= '<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:2px 8px;border-radius:4px;background:rgba(26,60,110,0.07);color:#1A3C6E;">' . esc_html($card['duration']) . '</span>';
	}

	// ── Assemble card ─────────────────────────────────────────────
?>
	<article class="spoke-course-card"
		data-category="<?php echo esc_attr($card['cat_slug']); ?>"
		style="background:#fff;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:box-shadow 200ms ease,transform 200ms ease;"
		onmouseenter="this.style.boxShadow='0 8px 32px rgba(26,60,110,0.12)';this.style.transform='translateY(-3px)';"
		onmouseleave="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';this.style.transform='';">

		<!-- Thumbnail -->
		<div style="position:relative;height:<?php echo (int) $img_height; ?>px;overflow:hidden;flex-shrink:0;">
			<a href="<?php echo esc_url($card['url']); ?>" class="spoke-card-thumb-link" tabindex="-1" aria-hidden="true"
				style="display:block;width:100%;height:100%;overflow:hidden;">
				<?php echo $thumb_html; ?>
			</a>
			<?php echo $cat_badge . $disc_badge . $promo_badge; ?>
		</div>

		<!-- Body -->
		<div style="padding:20px;display:flex;flex-direction:column;gap:8px;flex:1;">

			<?php if ($meta_pills) : ?>
				<div style="display:flex;flex-wrap:wrap;gap:4px;"><?php echo $meta_pills; ?></div>
			<?php endif; ?>

			<h3 style="font-size:16px;font-weight:700;color:#1A3C6E;margin:0;line-height:1.3;">
				<a href="<?php echo esc_url($card['url']); ?>" style="color:inherit;text-decoration:none;"
					onmouseover="this.style.textDecoration='underline'"
					onmouseout="this.style.textDecoration='none'">
					<?php echo esc_html($card['title']); ?>
				</a>
			</h3>

			<?php /* echo $instructor_html;  */ ?>
			<?php echo $excerpt_html; ?>

			<?php if ($rating_html) : ?>
				<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;"><?php echo $rating_html; ?></div>
			<?php endif; ?>

			<!-- Footer: price + CTA -->
			<div style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;margin-top:auto;border-top:1px solid rgba(0,0,0,0.07);">
				<div style="display:flex;align-items:baseline;gap:4px;">
					<?php echo spoke_card_price($card); ?>
				</div>
				<?php echo spoke_card_cta($card); ?>
			</div>

		</div><!-- /body -->

	</article>
<?php

	return ob_get_clean();
}


// ─────────────────────────────────────────────────────────────────
// 6. INLINE CSS  (printed once per page regardless of how many
//    cards are rendered — guarded by a static flag)
// ─────────────────────────────────────────────────────────────────

/**
 * Enqueue the shared card CSS once.
 * Call this before you start rendering any cards.
 */
function spoke_enqueue_card_styles(): void
{
	static $printed = false;
	if ($printed) {
		return;
	}
	$printed = true;

	echo '<style id="spoke-course-card-css">
/* ── Spoke Course Card ──────────────────────────────────── */
.spoke-course-card .spoke-card-thumb-link img {
    transition: transform 400ms ease;
}
.spoke-course-card:hover .spoke-card-thumb-link img {
    transform: scale(1.05);
}

/* Kill WooCommerce-appended "View cart" link inside our cards */
.spoke-course-card .added_to_cart.wc-forward,
.spoke-card-cta .added_to_cart { display: none !important; }

/* List-view override (toggled by archive JS via .is-list-view on the grid) */
#spoke-course-grid.is-list-view .spoke-course-card {
    flex-direction: row;
}
#spoke-course-grid.is-list-view .spoke-course-card > div:first-child {
    width: 220px;
    height: auto !important;
    flex-shrink: 0;
}
@media (max-width: 640px) {
    #spoke-course-grid.is-list-view .spoke-course-card { flex-direction: column; }
    #spoke-course-grid.is-list-view .spoke-course-card > div:first-child { width: 100%; }
}

/* Responsive single-column on small screens */
@media (max-width: 480px) {
    .spoke-course-card { font-size: 14px; }
}
</style>';
}


// ─────────────────────────────────────────────────────────────────
// 7. WC ADD-TO-CART EVENT HANDLER  (printed once per page)
// ─────────────────────────────────────────────────────────────────

/**
 * Print the JS that wires up WooCommerce's added_to_cart event
 * to toggle Add-to-Cart → View-Cart on all spoke-card-cta wrappers.
 * Call once, after all cards have been rendered.
 */
function spoke_print_card_atc_script(): void
{
	static $printed = false;
	if ($printed) {
		return;
	}
	$printed = true;

	echo <<<'JS'
<script id="spoke-card-atc-js">
(function () {
    'use strict';

    function switchToViewCart(pid) {
        document.querySelectorAll('.spoke-card-cta[data-pid="' + pid + '"]').forEach(function (wrap) {
            var addBtn  = wrap.querySelector('.spoke-cta-add');
            var cartBtn = wrap.querySelector('.spoke-cta-view-cart');
            var wcLink  = wrap.querySelector('.added_to_cart');
            if (addBtn)  addBtn.style.display  = 'none';
            if (cartBtn) cartBtn.style.display  = 'inline-flex';
            if (wcLink)  wcLink.remove();
        });
    }

    // WooCommerce AJAX event
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('added_to_cart.spoke_card', function (e, fragments, hash, $btn) {
            var pid = parseInt($btn && $btn.data('product_id'), 10);
            if (pid) { switchToViewCart(pid); }
        });
    }

    // MutationObserver belt-and-suspenders: catches WC-appended links
    new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) { return; }
                var wrap = node.closest ? node.closest('.spoke-card-cta') : null;
                if (wrap && node.classList && node.classList.contains('added_to_cart')) {
                    node.remove();
                    var pid = parseInt(wrap.dataset.pid, 10);
                    if (pid) { switchToViewCart(pid); }
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
}());
</script>
JS;
}


// ─────────────────────────────────────────────────────────────────
// 8. CONVENIENCE GRID RENDERER
// ─────────────────────────────────────────────────────────────────

/**
 * Render a full grid of cards from an array of post IDs.
 *
 * @param int[]  $post_ids          Ordered list of course post IDs.
 * @param array  $opts              Card options (see file header).
 * @param string $grid_classes      Tailwind classes for the grid wrapper.
 * @param array  $bought_pids       Pre-fetched purchased WC product IDs
 *                                  (pass from the caller to avoid N+1 queries).
 */
function spoke_render_card_grid(
	array  $post_ids,
	array  $opts        = [],
	string $grid_classes = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6',
	array  $bought_pids  = []
): void {

	if (empty($post_ids)) {
		return;
	}

	spoke_enqueue_card_styles();

	echo '<div class="spoke-card-grid ' . esc_attr($grid_classes) . '">';

	$user_id   = get_current_user_id();
	$is_logged = $user_id > 0;

	foreach ($post_ids as $pid) {
		$card = spoke_get_card_data((int) $pid);
		if (! $card) {
			continue;
		}

		// Apply pre-fetched purchase status to avoid per-card queries.
		if ($is_logged && ! $card['purchased'] && ! empty($bought_pids)) {
			if ($card['wc_product_id'] && in_array($card['wc_product_id'], $bought_pids, true)) {
				$card['purchased'] = true;
			}
		}

		spoke_render_course_card($card, $opts);
	}

	echo '</div>';

	spoke_print_card_atc_script();
}
