<?php

/**
 * functions.php — Spoke Theme
 * WordPress Full Site Editing (FSE) block theme
 * studymatecentral.org.uk — UK e-learning platform
 *
 * @package SpokeTheme
 */


// ─────────────────────────────────────────────────────────────────
// 1. THEME SETUP
// ─────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {

	// FSE core supports
	add_theme_support('wp-block-styles');
	add_theme_support('editor-styles');
	add_theme_support('align-wide');
	add_theme_support('responsive-embeds');
	add_theme_support('html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'navigation-widgets',
	]);

	// WooCommerce supports
	add_theme_support('woocommerce');
	add_theme_support('wc-product-gallery-zoom');
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');

	// patterns support 
	add_theme_support('core-block-patterns');
	
	// Register Spoke pattern category
	register_block_pattern_category('spoke-theme', [
		'label'       => __('Spoke Theme', 'spoke-theme'),
		'description' => __('Patterns for the Spoke e-learning theme.', 'spoke-theme'),
	]);
});


// ─────────────────────────────────────────────────────────────────
// 2. TAILWIND CSS CDN + CONFIG
//
// CRITICAL: Must use wp_enqueue_scripts (not wp_head) so WordPress
// places the script tag correctly and wp_head fires after it.
// The inline config script must come AFTER the CDN script loads.
// ─────────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {

	// Step 1: Register Tailwind CDN (in <head>, not footer)
	wp_enqueue_script(
		'tailwindcss',
		'https://cdn.tailwindcss.com',
		[],   // no dependencies
		null, // no version (CDN manages this)
		false // FALSE = loads in <head>, required for Tailwind to work
	);

	// Step 2: Inline config runs immediately after Tailwind CDN script
	// Uses wp_add_inline_script with 'after' position
	wp_add_inline_script(
		'tailwindcss',
		'tailwind.config = {
			theme: {
				extend: {
					fontFamily: {
						inter: ["Inter", "system-ui", "sans-serif"],
					},
					colors: {
						primary:         "#1A3C6E",
						accent:          "#F4A726",
						"accent-dark":   "#6B4500",
						light:           "#F8F9FA",
						dark:            "#1A1A2E",
						success:         "#E8F4EC",
						surface:         "#F3F4F5",
						muted:           "#43474F",
						error:           "#BA1A1A",
					},
					boxShadow: {
						card:         "0 2px 8px rgba(0,0,0,0.08)",
						"card-hover": "0 8px 32px rgba(26,60,110,0.08)",
						enrol:        "0 20px 50px rgba(0,0,0,0.15)",
						nav:          "0 2px 12px rgba(26,60,110,0.12)",
					},
					maxWidth: {
						site: "1280px",
					},
				},
			},
		};',
		'after'
	);
}, 1); // Priority 1 — runs before other enqueue hooks


// ─────────────────────────────────────────────────────────────────
// 3. ENQUEUE THEME ASSETS
// ─────────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
	$ver = wp_get_theme()->get('Version');
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'spoke-inter-font',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'spoke-global',
		$uri . '/assets/css/global.css',
		['spoke-inter-font'],
		$ver
	);
	wp_enqueue_style(
		'spoke-lms',
		$uri . '/assets/css/lms-overrides.css',
		['spoke-global'],
		$ver
	);
	wp_enqueue_style(
		'spoke-woo',
		$uri . '/assets/css/woocommerce-overrides.css',
		['spoke-global'],
		$ver
	);

	wp_enqueue_script(
		'spoke-countdown',
		$uri . '/assets/js/countdown-timer.js',
		[],
		$ver,
		true
	);
}, 10);


// ─────────────────────────────────────────────────────────────────
// 4. FIX: REMOVE WORDPRESS BLOCK GAP FROM wp:html TEMPLATE PARTS
//
// WordPress wraps <!-- wp:html --> blocks in <div class="wp-block-html">
// which inherits blockGap spacing from theme.json (32px by default).
// This creates the "extra padding above header" issue.
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
	echo '<style>
		/* Remove block gap from wp:html wrappers in template parts */
		.wp-block-template-part .wp-block-html,
		.wp-site-blocks > .wp-block-html {
			margin-block-start: 0 !important;
			margin-block-end: 0 !important;
		}
		/* Remove default body/html margin that WP sometimes adds */
		body { margin: 0; }
		/* Ensure Inter font applies everywhere */
		body, * { font-family: "Inter", system-ui, sans-serif; }
	</style>' . "\n";
}, 1);


// ─────────────────────────────────────────────────────────────────
// 5. FIX: WOOCOMMERCE CART & CHECKOUT — USE FULL-WIDTH TEMPLATE
//
// WooCommerce Cart and Checkout pages use page.html by default.
// page.html wraps content in a constrained group with padding.
// We override this so WooCommerce pages get no extra wrapper padding.
// ─────────────────────────────────────────────────────────────────

// Remove the default WooCommerce content wrappers so our template
// controls the full layout
add_action('after_setup_theme', function (): void {
	remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
	remove_action('woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10);
});

// Add our own minimal wrappers that don't fight the page.html layout
add_action('woocommerce_before_main_content', function (): void {
	echo '<div class="spoke-woo-main">';
});
add_action('woocommerce_after_main_content', function (): void {
	echo '</div>';
});

// Inline CSS for WooCommerce wrapper — keeps it full-width and clean
add_action('wp_head', function (): void {
	if (! (is_cart() || is_checkout() || is_account_page())) {
		return;
	}
	echo '<style>
		/* Override page.html group padding for WooCommerce pages */
		.woocommerce-cart .wp-block-group,
		.woocommerce-checkout .wp-block-group,
		.woocommerce-account .wp-block-group {
			padding: 0 !important;
			max-width: none !important;
		}
		.spoke-woo-main {
			max-width: 1280px;
			margin: 0 auto;
			padding: 40px 24px 80px;
		}
		@media (max-width: 640px) {
			.spoke-woo-main { padding: 24px 16px 60px; }
		}
	</style>' . "\n";
});


// ─────────────────────────────────────────────────────────────────
// 6. HELPER — all-virtual order check
// ─────────────────────────────────────────────────────────────────

function spoke_order_is_all_virtual(\WC_Order $order): bool
{
	foreach ($order->get_items() as $item) {
		$product = $item->get_product();
		if ($product && ! $product->is_virtual()) {
			return false;
		}
	}
	return true;
}


// ─────────────────────────────────────────────────────────────────
// 7. WOOCOMMERCE — CHECKOUT PAGE HARDENING
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_checkout_registration_required', '__return_false');

add_filter('woocommerce_checkout_login_message', function (): string {
	return esc_html__('Returning student? Log in via your dashboard.', 'spoke-theme');
});

add_filter('woocommerce_checkout_show_login_reminder', '__return_false');

add_filter('woocommerce_checkout_get_value', function ($value, string $input) {
	if ('billing_email' === $input && is_user_logged_in()) {
		$user = wp_get_current_user();
		return $user->user_email;
	}
	return $value;
}, 10, 2);


// ─────────────────────────────────────────────────────────────────
// 8. WOOCOMMERCE — ORDER EMAILS
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_email_styles', function (string $css): string {
	return $css . '
		body       { background-color: #f8f9fa !important; }
		#wrapper   { background-color: #f8f9fa !important; }
		h1,h2,h3   { color: #1a3c6e !important; font-family: Arial, sans-serif !important; }
		#header_wrapper { background-color: #1a3c6e !important; }
		.order_details tfoot tr:last-child td { font-weight:700; font-size:1.1em; }
		a          { color: #1a3c6e !important; }
		.button    { background-color: #f4a726 !important; color: #1a1a2e !important; border-radius:8px !important; }
	';
});

add_action('woocommerce_email_footer', function (\WC_Email $email): void {
	echo '<p style="font-size:12px;color:#666;text-align:center;margin-top:16px;">'
		. esc_html__('All prices include UK VAT at 20%. StudyMate Central is a trading name of [Company Ltd], registered in England & Wales.', 'spoke-theme')
		. '</p>';
});


// ─────────────────────────────────────────────────────────────────
// 9. WOOCOMMERCE — VAT DISPLAY & UK COMPLIANCE
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_tax_display_cart', fn() => 'incl');
add_filter('woocommerce_tax_display_shop', fn() => 'incl');

add_filter('woocommerce_get_price_suffix', function (string $suffix, \WC_Product $product): string {
	if (is_cart() || is_checkout()) {
		return '';
	}
	return ' <small class="woocommerce-price-suffix">' . esc_html__('inc. VAT', 'spoke-theme') . '</small>';
}, 10, 2);

add_action('woocommerce_cart_totals_after_order_total', function (): void {
	echo '<tr class="spoke-vat-notice"><td colspan="2">'
		. esc_html__('All prices include UK VAT at 20%.', 'spoke-theme')
		. '</td></tr>';
});

add_action('woocommerce_review_order_after_order_total', function (): void {
	echo '<tr class="spoke-vat-notice"><td colspan="2">'
		. esc_html__('All prices include UK VAT at 20%.', 'spoke-theme')
		. '</td></tr>';
});


// ─────────────────────────────────────────────────────────────────
// 10. WOOCOMMERCE — STRIPE GATEWAY INTEGRATION
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_available_payment_gateways', function (array $gateways): array {
	if (isset($gateways['stripe'])) {
		$stripe = $gateways['stripe'];
		unset($gateways['stripe']);
		return array_merge(['stripe' => $stripe], $gateways);
	}
	return $gateways;
});

add_action('woocommerce_review_order_before_payment', function (): void {
	echo '<p class="spoke-stripe-notice" style="display:flex;align-items:center;gap:6px;font-size:0.8125rem;font-weight:600;color:#43474F;margin-bottom:0.75rem;">🔒 '
		. esc_html__('Payment secured by Stripe — we never store your card details.', 'spoke-theme')
		. '</p>';
});

add_filter('woocommerce_payment_complete_order_status', function (string $status, int $order_id, \WC_Order $order): string {
	if ($order->has_downloadable_item() || spoke_order_is_all_virtual($order)) {
		return 'completed';
	}
	return $status;
}, 10, 3);


// ─────────────────────────────────────────────────────────────────
// 11. WOOCOMMERCE — DIGITAL PRODUCT HARDENING
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_cart_needs_shipping_address', function (bool $needs): bool {
	if (WC()->cart && WC()->cart->needs_shipping()) {
		return $needs;
	}
	return false;
});

add_filter('woocommerce_cart_needs_shipping', function (bool $needs): bool {
	if (! WC()->cart) {
		return $needs;
	}
	foreach (WC()->cart->get_cart() as $cart_item) {
		$product = $cart_item['data'];
		if (! $product->is_virtual()) {
			return $needs;
		}
	}
	return false;
});

add_action('woocommerce_thankyou', function (int $order_id): void {
	if (! $order_id) {
		return;
	}
	$order = wc_get_order($order_id);
	if ($order && spoke_order_is_all_virtual($order) && $order->has_status('processing')) {
		$order->update_status('completed', __('Auto-completed: all-virtual order.', 'spoke-theme'));
	}
});


// ─────────────────────────────────────────────────────────────────
// 12. WOOCOMMERCE — MY ACCOUNT MENU
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_account_menu_items', function (array $items): array {
	unset($items['dashboard']);
	$new = [];
	$new['tutor-dashboard'] = __('My Learning', 'spoke-theme');
	foreach ($items as $key => $label) {
		$new[$key] = $label;
	}
	return $new;
});

add_action('woocommerce_account_tutor-dashboard_endpoint', function (): void {
	wp_safe_redirect(home_url('/dashboard/'));
	exit;
});

add_filter('woocommerce_get_endpoint_url', function (string $url, string $endpoint): string {
	if ('tutor-dashboard' === $endpoint) {
		return home_url('/dashboard/');
	}
	return $url;
}, 10, 2);


// ─────────────────────────────────────────────────────────────────
// 13. WOOCOMMERCE — REMOVE UNWANTED DEFAULT CONTENT
// ─────────────────────────────────────────────────────────────────

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
add_filter('woocommerce_show_page_title', '__return_false');
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count',    20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

add_action('template_redirect', function (): void {
	if (is_shop() && ! is_admin()) {
		$courses_page = get_page_by_path('courses');
		if ($courses_page) {
			wp_safe_redirect(get_permalink($courses_page->ID), 301);
			exit;
		}
	}
});


// ─────────────────────────────────────────────────────────────────
// 14. WOOCOMMERCE — CHECKOUT FIELDS (UK-SPECIFIC)
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_billing_fields', function (array $fields): array {
	$remove = ['billing_company', 'billing_state', 'billing_phone'];
	foreach ($remove as $key) {
		unset($fields[$key]);
	}
	if (isset($fields['billing_address_1'])) {
		$fields['billing_address_1']['label']       = __('Street Address', 'spoke-theme');
		$fields['billing_address_1']['placeholder'] = __('123 High Street', 'spoke-theme');
	}
	if (isset($fields['billing_country'])) {
		$fields['billing_country']['default'] = 'GB';
	}
	if (isset($fields['billing_postcode'])) {
		$fields['billing_postcode']['label']       = __('Postcode', 'spoke-theme');
		$fields['billing_postcode']['placeholder'] = 'SW1A 1AA';
	}
	return $fields;
});


// ─────────────────────────────────────────────────────────────────
// 15. RANK MATH SEO — BLOG POST SCHEMA + BREADCRUMBS
// ─────────────────────────────────────────────────────────────────

add_filter('rank_math/frontend/breadcrumb/args', function (array $args): array {
	$args['separator'] = ' › ';
	return $args;
});

add_action('wp_head', function (): void {
	if (! is_single() || 'post' !== get_post_type()) {
		return;
	}
	$post        = get_post();
	$author_name = get_the_author_meta('display_name', $post->post_author);
	$author_url  = get_author_posts_url($post->post_author);
	$image_url   = get_the_post_thumbnail_url($post->ID, 'large') ?: get_site_icon_url();
	$cats        = get_the_category($post->ID);
	$cat_name    = $cats ? $cats[0]->name : 'Blog';

	$schema = [
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => esc_html(get_the_title($post->ID)),
		'description'      => esc_html(wp_strip_all_tags(get_the_excerpt($post->ID))),
		'image'            => $image_url,
		'author'           => ['@type' => 'Person', 'name' => $author_name, 'url' => $author_url],
		'publisher'        => [
			'@type' => 'Organization',
			'name'  => get_bloginfo('name'),
			'url'   => home_url(),
			'logo'  => ['@type' => 'ImageObject', 'url' => get_site_icon_url(512)],
		],
		'datePublished'    => get_the_date('c', $post->ID),
		'dateModified'     => get_the_modified_date('c', $post->ID),
		'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => get_permalink($post->ID)],
		'articleSection'   => $cat_name,
		'wordCount'        => (int) str_word_count(wp_strip_all_tags($post->post_content)),
		'url'              => get_permalink($post->ID),
		'inLanguage'       => 'en-GB',
	];

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);
}, 5);


// ─────────────────────────────────────────────────────────────────
// 16. RANK MATH SEO — TUTOR LMS COURSE SCHEMA (JSON-LD)
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
	if (! is_singular('courses')) {
		return;
	}
	$course_id    = get_the_ID();
	$price        = get_post_meta($course_id, '_price', true);
	$sale_price   = get_post_meta($course_id, '_sale_price', true);
	$final_price  = $sale_price ?: $price;
	$duration     = get_post_meta($course_id, '_course_duration', true);
	$level        = get_post_meta($course_id, '_tutor_course_level', true);
	$instructor   = get_userdata(get_post_field('post_author', $course_id));
	$thumb_url    = get_the_post_thumbnail_url($course_id, 'large');
	$cats         = get_the_terms($course_id, 'course-category');
	$cat_name     = ($cats && ! is_wp_error($cats)) ? $cats[0]->name : 'Professional Development';
	$rating_data  = function_exists('tutor_utils') ? tutor_utils()->get_course_rating($course_id) : null;
	$rating_avg   = $rating_data ? (float) $rating_data->rating_avg   : null;
	$rating_count = $rating_data ? (int)   $rating_data->rating_count : null;
	$students     = function_exists('tutor_utils') ? tutor_utils()->count_enrolled_users_by_course($course_id) : null;
	$benefits_raw = get_post_meta($course_id, '_tutor_course_benefits', true);
	$benefits     = $benefits_raw ? array_filter(array_map('trim', explode("\n", $benefits_raw))) : [];
	$req_raw      = get_post_meta($course_id, '_tutor_course_requirements', true);
	$reqs         = $req_raw ? array_filter(array_map('trim', explode("\n", $req_raw))) : [];

	$schema = [
		'@context'            => 'https://schema.org',
		'@type'               => 'Course',
		'name'                => esc_html(get_the_title()),
		'description'         => esc_html(wp_strip_all_tags(get_the_excerpt())),
		'url'                 => get_permalink(),
		'image'               => $thumb_url ?: get_site_icon_url(),
		'provider'            => ['@type' => 'Organization', 'name' => get_bloginfo('name'), 'sameAs' => home_url()],
		'educationalLevel'    => $level ? ucfirst($level) : 'Intermediate',
		'inLanguage'          => 'en-GB',
		'availableLanguage'   => 'English',
		'courseMode'          => 'online',
		'isAccessibleForFree' => false,
		'datePublished'       => get_the_date('c'),
		'dateModified'        => get_the_modified_date('c'),
		'about'               => $cat_name,
	];

	if ($instructor) {
		$schema['instructor'] = ['@type' => 'Person', 'name' => $instructor->display_name, 'url' => get_author_posts_url($instructor->ID)];
	}
	if ($duration) {
		$schema['timeRequired'] = $duration;
	}
	if ($final_price) {
		$schema['offers'] = [
			'@type'         => 'Offer',
			'price'         => number_format((float) $final_price, 2, '.', ''),
			'priceCurrency' => 'GBP',
			'availability'  => 'https://schema.org/InStock',
			'url'           => get_permalink(),
			'validFrom'     => get_the_date('c'),
		];
	}
	if ($rating_avg && $rating_count && $rating_count > 0) {
		$schema['aggregateRating'] = [
			'@type'       => 'AggregateRating',
			'ratingValue' => round($rating_avg, 1),
			'reviewCount' => $rating_count,
			'bestRating'  => 5,
			'worstRating' => 1,
		];
	}
	if (! empty($benefits)) {
		$schema['teaches'] = array_values($benefits);
	}
	if (! empty($reqs)) {
		$schema['coursePrerequisites'] = array_values($reqs);
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);
}, 5);


// ─────────────────────────────────────────────────────────────────
// 17. RANK MATH — BREADCRUMB SCHEMA
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
	if (! is_singular('courses') && ! is_single()) {
		return;
	}
	$items   = [];
	$items[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')];

	if (is_singular('courses')) {
		$archive_url = get_post_type_archive_link('courses');
		if ($archive_url) {
			$items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Courses', 'item' => $archive_url];
		}
		$cats = get_the_terms(get_the_ID(), 'course-category');
		if ($cats && ! is_wp_error($cats)) {
			$items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $cats[0]->name, 'item' => get_term_link($cats[0])];
		}
		$items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => get_the_title(), 'item' => get_permalink()];
	} elseif (is_single()) {
		$blog_url = get_permalink(get_option('page_for_posts'));
		if ($blog_url) {
			$items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $blog_url];
		}
		$cats = get_the_category();
		if ($cats) {
			$items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $cats[0]->name, 'item' => get_category_link($cats[0]->term_id)];
		}
		$items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => get_the_title(), 'item' => get_permalink()];
	}

	if (empty($items)) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);
}, 6);


// ─────────────────────────────────────────────────────────────────
// 18. RANK MATH — OPEN GRAPH FALLBACK FOR COURSES
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
	if (! is_singular('courses')) {
		return;
	}
	$thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
	if (! $thumb) {
		return;
	}
	global $rank_math_og_image_output;
	if ($rank_math_og_image_output) {
		return;
	}
	printf('<meta property="og:image" content="%s" />' . "\n", esc_url($thumb));
	echo '<meta property="og:image:width" content="1200" />' . "\n";
	echo '<meta property="og:image:height" content="630" />' . "\n";
}, 20);


// ─────────────────────────────────────────────────────────────────
// 19. BLOG — EXCERPT & COMMENTS
// ─────────────────────────────────────────────────────────────────

add_filter('excerpt_length', fn() => 30, 999);
add_filter('excerpt_more',   fn() => '…');

add_filter('the_excerpt', function (string $excerpt): string {
	if (! $excerpt && is_singular('post')) {
		$excerpt = wp_trim_words(get_the_content(), 30, '…');
	}
	return $excerpt;
});

add_action('wp_footer', function (): void {
	if (! is_single() || ! comments_open()) {
		return;
	}
?>
	<style>
		#commentform input[type="text"],
		#commentform input[type="email"],
		#commentform input[type="url"],
		#commentform textarea {
			width: 100%;
			padding: 12px 16px;
			background: #f3f4f5;
			border: 1px solid rgba(0, 0, 0, 0.1);
			border-radius: 8px;
			font-family: inherit;
			font-size: 15px;
			color: #191c1d;
			transition: border-color 200ms, box-shadow 200ms;
			margin-bottom: 0;
		}

		#commentform input:focus,
		#commentform textarea:focus {
			outline: 2px solid #F4A726;
			outline-offset: 1px;
			border-color: transparent;
		}

		#commentform textarea {
			min-height: 140px;
			resize: vertical;
		}

		#commentform .form-submit input[type="submit"] {
			background: #1A3C6E;
			color: #fff;
			font-family: inherit;
			font-size: 15px;
			font-weight: 700;
			padding: 12px 28px;
			border: none;
			border-radius: 8px;
			cursor: pointer;
			transition: filter 150ms;
		}

		#commentform .form-submit input[type="submit"]:hover {
			filter: brightness(1.15);
		}

		.comment-list {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.comment {
			padding: 20px 0;
			border-bottom: 1px solid rgba(0, 0, 0, 0.07);
		}

		.comment:last-child {
			border-bottom: none;
		}

		.comment .avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			float: left;
			margin-right: 14px;
		}

		.comment-author .fn {
			font-weight: 700;
			font-size: 14px;
			color: #1A3C6E;
		}

		.comment-meta {
			font-size: 12px;
			color: #6b7280;
			margin-bottom: 8px;
		}

		.comment-content p {
			font-size: 14px;
			line-height: 1.7;
			color: #43474f;
			margin: 0;
		}

		#reply-title {
			font-size: 20px;
			font-weight: 700;
			color: #1A3C6E;
			margin-bottom: 16px;
		}
	</style>
<?php
});
/**
 * ─────────────────────────────────────────────────────────────────
 * ADD THIS TO functions.php
 * Spoke Theme — Nav Menu Registration + Custom Walker
 * ─────────────────────────────────────────────────────────────────
 */


// ─────────────────────────────────────────────────────────────────
// 1. REGISTER NAVIGATION MENUS
// ─────────────────────────────────────────────────────────────────

add_action( 'after_setup_theme', function (): void {
    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'spoke-theme' ),
        'footer'  => __( 'Footer Menu',  'spoke-theme' ),
    ] );
}, 5 );


// ─────────────────────────────────────────────────────────────────
// 2. CUSTOM NAV WALKER — adds Tailwind/Spoke CSS classes
//    and wraps sub-menus with the dropdown div
// ─────────────────────────────────────────────────────────────────

if ( ! class_exists( 'Spoke_Nav_Walker' ) ) :

class Spoke_Nav_Walker extends Walker_Nav_Menu {

    /**
     * Open <li> element.
     *
     * Adds:
     *   • "spoke-has-dropdown" when a sub-menu exists
     *   • WordPress's own current-menu-item / current-menu-ancestor classes
     */
    public function start_el(
        &$output,
        $data_object,
        $depth = 0,
        $args  = null,
        $id    = 0
    ) {
        $item = $data_object;

        // Build class string
        $classes   = empty( $item->classes ) ? [] : (array) $item->classes;
        $has_child = in_array( 'menu-item-has-children', $classes, true );
        if ( $has_child ) {
            $classes[] = 'spoke-has-dropdown';
        }
        $class_str = implode( ' ', array_filter( array_unique( $classes ) ) );

        $output .= '<li id="menu-item-' . esc_attr( $item->ID ) . '" class="' . esc_attr( $class_str ) . '">';

        // Build the <a> tag
        $atts = [];
        $atts['href']   = ! empty( $item->url ) ? $item->url : '#';
        $atts['class']  = 'spoke-nav-link';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        $atts['rel']    = ! empty( $item->xfn )    ? $item->xfn    : '';
        if ( ! empty( $item->attr_title ) ) {
            $atts['title'] = $item->attr_title;
        }

        $attr_str = '';
        foreach ( $atts as $attr => $val ) {
            if ( '' !== $val ) {
                $attr_str .= ' ' . $attr . '="' . esc_attr( $val ) . '"';
            }
        }

        $title  = apply_filters( 'the_title', $item->title, $item->ID );
        $title  = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

        $chevron = $has_child
            ? ' <svg class="spoke-nav-chevron" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>'
            : '';

        $output .= '<a' . $attr_str . '>' . esc_html( $title ) . $chevron . '</a>';
    }

    /**
     * Open the sub-menu <ul> — wraps it in the dropdown div.
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<div class="spoke-dropdown" role="menu"><ul>';
    }

    /**
     * Close the sub-menu <ul> and its wrapper div.
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</ul></div>';
    }

    /**
     * Close <li>.
     */
    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}

endif; // class_exists Spoke_Nav_Walker