<?php

add_filter('template_include', function(string $template): string {

    $normalized = str_replace('\\', '/', $template);
    $is_tutor = (strpos($normalized, '/plugins/tutor/') !== false);

    $is_woo = (
        (function_exists('is_checkout') && is_checkout()) ||
        (function_exists('is_cart') && is_cart())
    );

    if (!$is_tutor && !$is_woo) {
        return $template;
    }

    if ($is_woo) {
        // WooCommerce — use page.html normally, post content exists
        $page_html = get_template_directory() . '/templates/page.html';
        $content   = ltrim(file_get_contents($page_html), "\xEF\xBB\xBF");

        global $_wp_current_template_content, $_wp_current_template_id;
        $_wp_current_template_content = $content;
        $_wp_current_template_id      = get_stylesheet() . '//page';

        return ABSPATH . WPINC . '/template-canvas.php';
    }

    // TutorLMS — capture its template output, inject into a fake post_content
    // so wp:post-content block renders it
    ob_start();
    include $template;
    $tutor_html = ob_get_clean();

    // Inject captured HTML as the post content so wp:post-content renders it
    add_filter('the_content', function() use ($tutor_html): string {
        return $tutor_html;
    }, 999);

    // Also handle cases where post_content is empty
    add_filter('get_the_content', function($content) use ($tutor_html): string {
        return $tutor_html;
    }, 999);

    $page_html = get_template_directory() . '/templates/page.html';
    $content   = ltrim(file_get_contents($page_html), "\xEF\xBB\xBF");

    global $_wp_current_template_content, $_wp_current_template_id;
    $_wp_current_template_content = $content;
    $_wp_current_template_id      = get_stylesheet() . '//page';

    return ABSPATH . WPINC . '/template-canvas.php';

}, 9999);


// ─────────────────────────────────────────────────────────────────
// 1. THEME SETUP
// ─────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {
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
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('core-block-patterns');
    register_block_pattern_category('spoke-theme', [
        'label'       => __('Spoke Theme', 'spoke-theme'),
        'description' => __('Patterns for the Spoke e-learning theme.', 'spoke-theme'),
    ]);
});


// ─────────────────────────────────────────────────────────────────
// 2. TAILWIND CSS CDN + CONFIG
// ─────────────────────────────────────────────────────────────────

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_script(
        'tailwindcss',
        'https://cdn.tailwindcss.com',
        [],
        null,
        false
    );
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
}, 1);


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
    wp_enqueue_style('spoke-global', $uri . '/assets/css/global.css', ['spoke-inter-font'], $ver);
    wp_enqueue_style('spoke-lms',    $uri . '/assets/css/lms-overrides.css', ['spoke-global'], $ver);
    wp_enqueue_style('spoke-woo',    $uri . '/assets/css/woocommerce-overrides.css', ['spoke-global'], $ver);
    wp_enqueue_script('spoke-countdown',    $uri . '/assets/js/countdown-timer.js',  [], $ver, true);
    wp_enqueue_script('spoke-header-scroll', $uri . '/assets/js/header-scroll.js', [], $ver, true);
}, 10);


// ─────────────────────────────────────────────────────────────────
// 4. BLOCK GAP FIX
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
    echo '<style>
        .wp-block-template-part .wp-block-html,
        .wp-site-blocks > .wp-block-html {
            margin-block-start: 0 !important;
            margin-block-end: 0 !important;
        }
        body { margin: 0; }
        body, * { font-family: "Inter", system-ui, sans-serif; }
    </style>' . "\n";
}, 1);

add_action('wp_head', function(): void {
    if (!function_exists('tutor_utils')) return;
    
    $normalized = str_replace('\\', '/', $_SERVER['REQUEST_URI'] ?? '');
    
    // Check if this is a tutor template page
    global $_wp_current_template_id;
    if (empty($_wp_current_template_id) && !is_singular(['lesson', 'tutor_quiz', 'tutor_assignments'])) return;
    
    $normalized_template = str_replace('\\', '/', $_wp_current_template_id ?? '');
    if (strpos($normalized_template, 'tutor') === false && !is_singular(['lesson', 'tutor_quiz', 'tutor_assignments'])) return;
    
    echo '<style>
        /* Remove constrained layout restriction on TutorLMS pages */
        .tutor-wrap .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)),
        .tutor-dashboard .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)),
        .tutor-page .is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    </style>';
}, 20);


// ─────────────────────────────────────────────────────────────────
// 5. WOOCOMMERCE — CONTENT WRAPPERS
// ─────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10);
});

add_action('woocommerce_before_main_content', function (): void {
    echo '<div class="spoke-woo-main">';
});
add_action('woocommerce_after_main_content', function (): void {
    echo '</div>';
});

add_action('wp_head', function (): void {
    if (! (is_cart() || is_checkout() || is_account_page())) {
        return;
    }
    echo '<style>
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
        .woocommerce-cart main.is-layout-constrained,
        .woocommerce-checkout main.is-layout-constrained,
        .woocommerce-account main.is-layout-constrained {
            max-width: 1280px !important;
            margin: 0 auto !important;
            padding: 40px 24px 80px !important;
        }
        @media (max-width: 768px) {
            .woocommerce-cart main.is-layout-constrained,
            .woocommerce-checkout main.is-layout-constrained {
                padding: 24px 16px 40px !important;
            }
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
// 7. WOOCOMMERCE — CHECKOUT HARDENING
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_checkout_registration_required', '__return_false');
add_filter('woocommerce_checkout_login_message', fn() => esc_html__('Returning student? Log in via your dashboard.', 'spoke-theme'));
add_filter('woocommerce_checkout_show_login_reminder', '__return_false');

add_filter('woocommerce_checkout_get_value', function ($value, string $input) {
    if ('billing_email' === $input && is_user_logged_in()) {
        return wp_get_current_user()->user_email;
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
// 9. WOOCOMMERCE — VAT DISPLAY
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_tax_display_cart', fn() => 'incl');
add_filter('woocommerce_tax_display_shop', fn() => 'incl');

add_filter('woocommerce_get_price_suffix', function (string $suffix, \WC_Product $product): string {
    if (is_cart() || is_checkout()) return '';
    return ' <small class="woocommerce-price-suffix">' . esc_html__('inc. VAT', 'spoke-theme') . '</small>';
}, 10, 2);

add_action('woocommerce_cart_totals_after_order_total', function (): void {
    echo '<tr class="spoke-vat-notice"><td colspan="2">' . esc_html__('All prices include UK VAT at 20%.', 'spoke-theme') . '</td></tr>';
});
add_action('woocommerce_review_order_after_order_total', function (): void {
    echo '<tr class="spoke-vat-notice"><td colspan="2">' . esc_html__('All prices include UK VAT at 20%.', 'spoke-theme') . '</td></tr>';
});


// ─────────────────────────────────────────────────────────────────
// 10. WOOCOMMERCE — STRIPE
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
    if (WC()->cart && WC()->cart->needs_shipping()) return $needs;
    return false;
});

add_filter('woocommerce_cart_needs_shipping', function (bool $needs): bool {
    if (! WC()->cart) return $needs;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (! $cart_item['data']->is_virtual()) return $needs;
    }
    return false;
});

add_action('woocommerce_thankyou', function (int $order_id): void {
    if (! $order_id) return;
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
    return array_merge(['tutor-dashboard' => __('My Learning', 'spoke-theme')], $items);
});

add_action('woocommerce_account_tutor-dashboard_endpoint', function (): void {
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
});

add_filter('woocommerce_get_endpoint_url', function (string $url, string $endpoint): string {
    if ('tutor-dashboard' === $endpoint) return home_url('/dashboard/');
    return $url;
}, 10, 2);


// ─────────────────────────────────────────────────────────────────
// 13. WOOCOMMERCE — REMOVE UNWANTED CONTENT + REDIRECTS
// ─────────────────────────────────────────────────────────────────

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
add_filter('woocommerce_show_page_title', '__return_false');
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

add_action('template_redirect', function (): void {
    if (function_exists('is_shop') && is_shop()) {
        wp_safe_redirect(home_url('/courses/'), 301);
        exit;
    }
    global $wp;
    if (trim($wp->request, '/') === 'shop') {
        wp_safe_redirect(home_url('/courses/'), 301);
        exit;
    }
    if (is_tax('course-category')) {
        $term = get_queried_object();
        if (! $term || is_wp_error($term)) {
            wp_safe_redirect(home_url('/courses/'), 301);
        } else {
            wp_safe_redirect(add_query_arg('cat', $term->slug, home_url('/courses/')), 302);
        }
        exit;
    }
});


// ─────────────────────────────────────────────────────────────────
// 14. WOOCOMMERCE — CHECKOUT FIELDS (UK)
// ─────────────────────────────────────────────────────────────────

add_filter('woocommerce_billing_fields', function (array $fields): array {
    foreach (['billing_company', 'billing_state', 'billing_phone'] as $key) {
        unset($fields[$key]);
    }
    if (isset($fields['billing_address_1'])) {
        $fields['billing_address_1']['label']       = __('Street Address', 'spoke-theme');
        $fields['billing_address_1']['placeholder'] = __('123 High Street', 'spoke-theme');
    }
    if (isset($fields['billing_country']))  $fields['billing_country']['default']      = 'GB';
    if (isset($fields['billing_postcode'])) {
        $fields['billing_postcode']['label']       = __('Postcode', 'spoke-theme');
        $fields['billing_postcode']['placeholder'] = 'SW1A 1AA';
    }
    return $fields;
});


// ─────────────────────────────────────────────────────────────────
// 15. RANK MATH — BREADCRUMB ARGS
// ─────────────────────────────────────────────────────────────────

add_filter('rank_math/frontend/breadcrumb/args', function (array $args): array {
    $args['separator'] = ' › ';
    return $args;
});


// ─────────────────────────────────────────────────────────────────
// 16. SCHEMA — BLOG POST
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
    if (! is_single() || 'post' !== get_post_type()) return;
    $post        = get_post();
    $author_name = get_the_author_meta('display_name', $post->post_author);
    $author_url  = get_author_posts_url($post->post_author);
    $image_url   = get_the_post_thumbnail_url($post->ID, 'large') ?: get_site_icon_url();
    $cats        = get_the_category($post->ID);
    $schema = [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => esc_html(get_the_title($post->ID)),
        'description'      => esc_html(wp_strip_all_tags(get_the_excerpt($post->ID))),
        'image'            => $image_url,
        'author'           => ['@type' => 'Person', 'name' => $author_name, 'url' => $author_url],
        'publisher'        => ['@type' => 'Organization', 'name' => get_bloginfo('name'), 'url' => home_url(), 'logo' => ['@type' => 'ImageObject', 'url' => get_site_icon_url(512)]],
        'datePublished'    => get_the_date('c', $post->ID),
        'dateModified'     => get_the_modified_date('c', $post->ID),
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => get_permalink($post->ID)],
        'articleSection'   => $cats ? $cats[0]->name : 'Blog',
        'wordCount'        => (int) str_word_count(wp_strip_all_tags($post->post_content)),
        'url'              => get_permalink($post->ID),
        'inLanguage'       => 'en-GB',
    ];
    printf('<script type="application/ld+json">%s</script>' . "\n", wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}, 5);


// ─────────────────────────────────────────────────────────────────
// 17. SCHEMA — COURSE
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
    if (! is_singular('courses')) return;
    $course_id   = get_the_ID();
    $price       = get_post_meta($course_id, '_price', true);
    $sale_price  = get_post_meta($course_id, '_sale_price', true);
    $final_price = $sale_price ?: $price;
    $duration    = get_post_meta($course_id, '_course_duration', true);
    $level       = get_post_meta($course_id, '_tutor_course_level', true);
    $instructor  = get_userdata(get_post_field('post_author', $course_id));
    $thumb_url   = get_the_post_thumbnail_url($course_id, 'large');
    $cats        = get_the_terms($course_id, 'course-category');
    $cat_name    = ($cats && ! is_wp_error($cats)) ? $cats[0]->name : 'Professional Development';
    $rating_data = function_exists('tutor_utils') ? tutor_utils()->get_course_rating($course_id) : null;
    $benefits    = array_filter(array_map('trim', explode("\n", get_post_meta($course_id, '_tutor_course_benefits', true) ?: '')));
    $reqs        = array_filter(array_map('trim', explode("\n", get_post_meta($course_id, '_tutor_course_requirements', true) ?: '')));
    $schema = [
        '@context' => 'https://schema.org', '@type' => 'Course',
        'name' => esc_html(get_the_title()), 'description' => esc_html(wp_strip_all_tags(get_the_excerpt())),
        'url' => get_permalink(), 'image' => $thumb_url ?: get_site_icon_url(),
        'provider' => ['@type' => 'Organization', 'name' => get_bloginfo('name'), 'sameAs' => home_url()],
        'educationalLevel' => $level ? ucfirst($level) : 'Intermediate',
        'inLanguage' => 'en-GB', 'courseMode' => 'online', 'isAccessibleForFree' => false,
        'datePublished' => get_the_date('c'), 'dateModified' => get_the_modified_date('c'), 'about' => $cat_name,
    ];
    if ($instructor) $schema['instructor'] = ['@type' => 'Person', 'name' => $instructor->display_name, 'url' => get_author_posts_url($instructor->ID)];
    if ($duration)   $schema['timeRequired'] = $duration;
    if ($final_price) $schema['offers'] = ['@type' => 'Offer', 'price' => number_format((float)$final_price, 2, '.', ''), 'priceCurrency' => 'GBP', 'availability' => 'https://schema.org/InStock', 'url' => get_permalink(), 'validFrom' => get_the_date('c')];
    if ($rating_data && $rating_data->rating_count > 0) $schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round((float)$rating_data->rating_avg, 1), 'reviewCount' => (int)$rating_data->rating_count, 'bestRating' => 5, 'worstRating' => 1];
    if (!empty($benefits)) $schema['teaches'] = array_values($benefits);
    if (!empty($reqs))     $schema['coursePrerequisites'] = array_values($reqs);
    printf('<script type="application/ld+json">%s</script>' . "\n", wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}, 5);


// ─────────────────────────────────────────────────────────────────
// 18. SCHEMA — BREADCRUMBS
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
    if (! is_singular('courses') && ! is_single()) return;
    $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')]];
    if (is_singular('courses')) {
        $archive_url = get_post_type_archive_link('courses');
        if ($archive_url) $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Courses', 'item' => $archive_url];
        $cats = get_the_terms(get_the_ID(), 'course-category');
        if ($cats && ! is_wp_error($cats)) $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $cats[0]->name, 'item' => get_term_link($cats[0])];
        $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => get_the_title(), 'item' => get_permalink()];
    } elseif (is_single()) {
        $blog_url = get_permalink(get_option('page_for_posts'));
        if ($blog_url) $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $blog_url];
        $cats = get_the_category();
        if ($cats) $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $cats[0]->name, 'item' => get_category_link($cats[0]->term_id)];
        $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => get_the_title(), 'item' => get_permalink()];
    }
    printf('<script type="application/ld+json">%s</script>' . "\n", wp_json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}, 6);


// ─────────────────────────────────────────────────────────────────
// 19. RANK MATH — OG IMAGE FALLBACK
// ─────────────────────────────────────────────────────────────────

add_action('wp_head', function (): void {
    if (! is_singular('courses')) return;
    $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
    if (! $thumb) return;
    global $rank_math_og_image_output;
    if ($rank_math_og_image_output) return;
    printf('<meta property="og:image" content="%s" />' . "\n", esc_url($thumb));
    echo '<meta property="og:image:width" content="1200" />' . "\n";
    echo '<meta property="og:image:height" content="630" />' . "\n";
}, 20);


// ─────────────────────────────────────────────────────────────────
// 20. BLOG — EXCERPT & COMMENTS
// ─────────────────────────────────────────────────────────────────

add_filter('excerpt_length', fn() => 30, 999);
add_filter('excerpt_more',   fn() => '…');
add_filter('the_excerpt', function (string $excerpt): string {
    if (! $excerpt && is_singular('post')) $excerpt = wp_trim_words(get_the_content(), 30, '…');
    return $excerpt;
});

add_action('wp_footer', function (): void {
    if (! is_single() || ! comments_open()) return;
    echo '<style>
        #commentform input[type="text"], #commentform input[type="email"],
        #commentform input[type="url"], #commentform textarea {
            width:100%; padding:12px 16px; background:#f3f4f5;
            border:1px solid rgba(0,0,0,0.1); border-radius:8px;
            font-family:inherit; font-size:15px; color:#191c1d; margin-bottom:0;
        }
        #commentform input:focus, #commentform textarea:focus { outline:2px solid #F4A726; border-color:transparent; }
        #commentform textarea { min-height:140px; resize:vertical; }
        #commentform .form-submit input[type="submit"] { background:#1A3C6E; color:#fff; font-family:inherit; font-size:15px; font-weight:700; padding:12px 28px; border:none; border-radius:8px; cursor:pointer; }
        .comment-list { list-style:none; margin:0; padding:0; }
        .comment { padding:20px 0; border-bottom:1px solid rgba(0,0,0,0.07); }
        .comment-author .fn { font-weight:700; font-size:14px; color:#1A3C6E; }
        .comment-meta { font-size:12px; color:#6b7280; margin-bottom:8px; }
        .comment-content p { font-size:14px; line-height:1.7; color:#43474f; margin:0; }
        #reply-title { font-size:20px; font-weight:700; color:#1A3C6E; margin-bottom:16px; }
    </style>';
});


// ─────────────────────────────────────────────────────────────────
// 21. NAV MENUS
// ─────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {
    register_nav_menus([
        'primary' => __('Primary Menu', 'spoke-theme'),
        'footer'  => __('Footer Menu',  'spoke-theme'),
    ]);
}, 5);

add_filter('render_block_core/navigation', function (string $html, array $block): string {
    if (!empty($block['attrs']['ref'])) return $html;
    $location = $block['attrs']['__experimentalLocation'] ?? '';
    if ($location !== 'primary' && $location !== '') return $html;
    $locations = get_nav_menu_locations();
    if (empty($locations['primary'])) return $html;
    $menu = wp_get_nav_menu_object($locations['primary']);
    if (!$menu) return $html;
    $block['attrs']['ref'] = (int) $menu->term_id;
    return render_block($block);
}, 10, 2);


// ─────────────────────────────────────────────────────────────────
// 22. CUSTOM NAV WALKER
// ─────────────────────────────────────────────────────────────────

if (! class_exists('Spoke_Nav_Walker')) :
class Spoke_Nav_Walker extends Walker_Nav_Menu
{
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $id = 0) {
        $item      = $data_object;
        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $has_child = in_array('menu-item-has-children', $classes, true);
        if ($has_child) $classes[] = 'spoke-has-dropdown';
        $output .= '<li id="menu-item-' . esc_attr($item->ID) . '" class="' . esc_attr(implode(' ', array_filter(array_unique($classes)))) . '">';
        $atts = ['href' => !empty($item->url) ? $item->url : '#', 'class' => 'spoke-nav-link', 'target' => !empty($item->target) ? $item->target : '', 'rel' => !empty($item->xfn) ? $item->xfn : ''];
        if (!empty($item->attr_title)) $atts['title'] = $item->attr_title;
        $attr_str = '';
        foreach ($atts as $attr => $val) {
            if ('' !== $val) $attr_str .= ' ' . $attr . '="' . esc_attr($val) . '"';
        }
        $title   = apply_filters('nav_menu_item_title', apply_filters('the_title', $item->title, $item->ID), $item, $args, $depth);
        $chevron = $has_child ? ' <svg class="spoke-nav-chevron" fill="none" stroke="rgba(255,255,255,0.65)" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>' : '';
        $output .= '<a' . $attr_str . '>' . esc_html($title) . $chevron . '</a>';
    }
    public function start_lvl(&$output, $depth = 0, $args = null) { $output .= '<div class="spoke-dropdown" role="menu"><ul>'; }
    public function end_lvl(&$output, $depth = 0, $args = null)   { $output .= '</ul></div>'; }
    public function end_el(&$output, $data_object, $depth = 0, $args = null) { $output .= '</li>'; }
}
endif;


// ─────────────────────────────────────────────────────────────────
// 23. EMPTY CART CTA
// ─────────────────────────────────────────────────────────────────

add_filter('render_block', function (string $content, array $block): string {
    if (!isset($block['blockName']) || $block['blockName'] !== 'woocommerce/empty-cart-block') return $content;
    $btn = '<a href="' . esc_url(home_url('/courses/')) . '" class="spoke-empty-cart-cta">'
        . '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
        . 'Browse Our Courses</a>';
    $content = preg_replace('/(<h2[^>]*class="[^"]*wc-block-cart__empty-cart__title[^"]*"[^>]*>.*?<\/h2>)/is', '$1' . $btn, $content, 1);
    return $content;
}, 10, 2);

add_action('wp_enqueue_scripts', function(): void {
    // Force WooCommerce cart fragments to load in footer AFTER React hydration
    wp_enqueue_script('wc-cart-fragments');
}, 99);

// Force jQuery to load in head so it's ready before everything else
add_filter('wp_enqueue_scripts', function(): void {
    wp_enqueue_script('jquery');
}, 1);

// ─────────────────────────────────────────────────────────────────
// 24. REQUIRE INC FILES
// ─────────────────────────────────────────────────────────────────

require_once get_template_directory() . '/inc/functions-course-meta.php';
require_once get_template_directory() . '/inc/functions-archive-addon.php';
require_once get_template_directory() . '/inc/functions-single-course.php';
require_once get_template_directory() . '/inc/functions-login.php';
require_once get_template_directory() . '/inc/functions-hot-deals.php';
require_once get_template_directory() . '/inc/functions-footer.php';
require_once get_template_directory() . '/inc/functions-contact.php';
require_once get_template_directory() . '/inc/functions-course-card.php';
require_once get_template_directory() . '/inc/functions-custom-logo.php';
require_once get_template_directory() . '/inc/functions-blog-archive.php';
require_once get_template_directory() . '/inc/functions-blog-single.php';
require_once get_template_directory() . '/inc/functions-reviews.php';

// ─────────────────────────────────────────────────────────────────
// 25. REMOVE EMPTY <p> TAGS GLOBALLY
// ─────────────────────────────────────────────────────────────────
add_filter( 'the_content', function( string $content ): string {
    // Remove <p> tags that contain only whitespace, &nbsp;, or nothing.
    $content = preg_replace( '/<p>(\s|&nbsp;|\xc2\xa0)*<\/p>/i', '', $content );
    return $content;
}, 99 );


//debug
add_action('template_redirect', function(): void {
    if (is_home()) {
        error_log('Template: ' . get_template_directory() . '/templates/archive.html');
        error_log('is_home: ' . var_export(is_home(), true));
        error_log('is_page: ' . var_export(is_page(), true));
    }
});

// ─────────────────────────────────────────────────────────────────
// 26. the single and archive teplate fix
// ─────────────────────────────────────────────────────────────────

add_filter( 'template_include', function ( string $template ): string {

    if ( is_singular( 'post' ) ) {
        $block_template = get_block_template(
            get_stylesheet() . '//single',
            'wp_template'
        );
        if ( $block_template && ! empty( $block_template->content ) ) {
            global $_wp_current_template_content, $_wp_current_template_id;
            $_wp_current_template_content = $block_template->content;
            $_wp_current_template_id      = $block_template->id;
            return ABSPATH . WPINC . '/template-canvas.php';
        }
    }

    return $template;

}, 99 ); // priority 99 — after WP resolves, before your priority 9999 handler

add_filter( 'template_include', function ( string $template ): string {

    if ( is_home() || is_category() || is_tag() || is_author() || is_date() ) {
        $block_template = get_block_template(
            get_stylesheet() . '//archive',
            'wp_template'
        );
        if ( $block_template && ! empty( $block_template->content ) ) {
            global $_wp_current_template_content, $_wp_current_template_id;
            $_wp_current_template_content = $block_template->content;
            $_wp_current_template_id      = $block_template->id;
            return ABSPATH . WPINC . '/template-canvas.php';
        }
    }

    return $template;

}, 99 );


