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

add_action( 'after_setup_theme', function (): void {

	// FSE core supports
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', [
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'navigation-widgets',
	] );

	// WooCommerce supports
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Register Spoke pattern category
	register_block_pattern_category( 'spoke-theme', [
		'label'       => __( 'Spoke Theme', 'spoke-theme' ),
		'description' => __( 'Patterns for the Spoke e-learning theme.', 'spoke-theme' ),
	] );
} );


// ─────────────────────────────────────────────────────────────────
// 2. TAILWIND CSS CDN + CONFIG
//    Colours mirror theme.json exactly so every template and pattern
//    uses the same tokens (e.g. bg-primary = #1A3C6E).
//    For production: compile Tailwind and remove the CDN script.
// ─────────────────────────────────────────────────────────────────

add_action( 'wp_head', function (): void {
	?>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
	tailwind.config = {
		theme: {
			extend: {
				fontFamily: {
					inter: ['Inter', 'system-ui', 'sans-serif'],
				},
				colors: {
					/* ── theme.json palette (never hardcode hex elsewhere) ── */
					primary:       '#1A3C6E',
					accent:        '#F4A726',
					'accent-dark': '#6B4500',   /* text / icon on amber bg   */
					light:         '#F8F9FA',
					dark:          '#1A1A2E',
					success:       '#E8F4EC',
					surface:       '#F3F4F5',
					muted:         '#43474F',
					error:         '#BA1A1A',
					white:         '#FFFFFF',
				},
				boxShadow: {
					card:       '0 2px 8px rgba(0,0,0,0.08)',
					'card-hover': '0 8px 32px rgba(26,60,110,0.08)',
					enrol:      '0 20px 50px rgba(0,0,0,0.15)',
					nav:        '0 2px 12px rgba(26,60,110,0.12)',
				},
				maxWidth: {
					site: '1280px',
				},
			},
		},
	};
	</script>
	<?php
}, 5 );


// ─────────────────────────────────────────────────────────────────
// 3. ENQUEUE THEME ASSETS
// ─────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function (): void {
	$ver = wp_get_theme()->get( 'Version' );
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'spoke-global',
		$uri . '/assets/css/global.css',
		[],
		$ver
	);
	wp_enqueue_style(
		'spoke-lms',
		$uri . '/assets/css/lms-overrides.css',
		[ 'spoke-global' ],
		$ver
	);
	wp_enqueue_style(
		'spoke-woo',
		$uri . '/assets/css/woocommerce-overrides.css',
		[ 'spoke-global' ],
		$ver
	);

	// Countdown timer (homepage hot-deals banner & hot-deals page)
	wp_enqueue_script(
		'spoke-countdown',
		$uri . '/assets/js/countdown-timer.js',
		[],
		$ver,
		true
	);
} );


// ─────────────────────────────────────────────────────────────────
// 4. BLOCK PATTERNS
//    WordPress 6.4+ auto-discovers all .php files in /patterns/
//    using the file-header comment metadata. No manual require_once
//    needed — that caused "headers already sent" errors because
//    pattern files output HTML directly when included.
// ─────────────────────────────────────────────────────────────────
// (no code needed here — WordPress handles it automatically)


// ─────────────────────────────────────────────────────────────────
// 5. HELPER — all-virtual order check (used by WooCommerce hooks)
// ─────────────────────────────────────────────────────────────────

/**
 * Returns true if every item in the order is virtual (no physical goods).
 *
 * @param \WC_Order $order
 * @return bool
 */
function spoke_order_is_all_virtual( \WC_Order $order ): bool {
	foreach ( $order->get_items() as $item ) {
		/** @var \WC_Order_Item_Product $item */
		$product = $item->get_product();
		if ( $product && ! $product->is_virtual() ) {
			return false;
		}
	}
	return true;
}


// ─────────────────────────────────────────────────────────────────
// 9. WOOCOMMERCE — CHECKOUT PAGE HARDENING
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_checkout_registration_required', '__return_false' );

add_filter( 'woocommerce_checkout_login_message', function (): string {
	return esc_html__(
		'Returning student? Log in via your dashboard.',
		'spoke-theme'
	);
} );

add_filter( 'woocommerce_checkout_show_login_reminder', '__return_false' );

add_filter( 'woocommerce_checkout_get_value', function ( $value, string $input ) {
	if ( 'billing_email' === $input && is_user_logged_in() ) {
		$user = wp_get_current_user();
		return $user->user_email;
	}
	return $value;
}, 10, 2 );

add_action( 'woocommerce_checkout_billing', function (): void {
	echo '<style>
		.woocommerce-billing-fields h3::before  { content: "1" !important; }
		.woocommerce-shipping-fields h3::before { content: "2" !important; }
		#payment h3::before                     { content: "3" !important; }
	</style>';
}, 5 );


// ─────────────────────────────────────────────────────────────────
// 10. WOOCOMMERCE — ORDER EMAILS
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_email_styles', function ( string $css ): string {
	return $css . '
		body       { background-color: #f8f9fa !important; }
		#wrapper   { background-color: #f8f9fa !important; }
		h1,h2,h3   { color: #1a3c6e !important; font-family: Arial, sans-serif !important; }
		#header_wrapper { background-color: #1a3c6e !important; }
		.order_details tfoot tr:last-child td { font-weight:700; font-size:1.1em; }
		a          { color: #1a3c6e !important; }
		.button    { background-color: #f4a726 !important; color: #1a1a2e !important; border-radius:8px !important; }
	';
} );

add_action( 'woocommerce_email_footer', function ( \WC_Email $email ): void {
	echo '<p style="font-size:12px;color:#666;text-align:center;margin-top:16px;">'
		. esc_html__( 'All prices include UK VAT at 20%. StudyMate Central is a trading name of [Company Ltd], registered in England & Wales.', 'spoke-theme' )
		. '</p>';
} );


// ─────────────────────────────────────────────────────────────────
// 11. WOOCOMMERCE — VAT DISPLAY & UK COMPLIANCE
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_tax_display_cart', fn() => 'incl' );
add_filter( 'woocommerce_tax_display_shop', fn() => 'incl' );

add_filter( 'woocommerce_get_price_suffix', function ( string $suffix, \WC_Product $product ): string {
	if ( is_cart() || is_checkout() ) {
		return '';
	}
	return ' <small class="woocommerce-price-suffix">' . esc_html__( 'inc. VAT', 'spoke-theme' ) . '</small>';
}, 10, 2 );

add_action( 'woocommerce_cart_totals_after_order_total', function (): void {
	echo '<tr class="spoke-vat-notice"><td colspan="2">'
		. esc_html__( 'All prices include UK VAT at 20%.', 'spoke-theme' )
		. '</td></tr>';
} );

add_action( 'woocommerce_review_order_after_order_total', function (): void {
	echo '<tr class="spoke-vat-notice"><td colspan="2">'
		. esc_html__( 'All prices include UK VAT at 20%.', 'spoke-theme' )
		. '</td></tr>';
} );


// ─────────────────────────────────────────────────────────────────
// 12. WOOCOMMERCE — STRIPE GATEWAY INTEGRATION
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_available_payment_gateways', function ( array $gateways ): array {
	if ( isset( $gateways['stripe'] ) ) {
		$stripe = $gateways['stripe'];
		unset( $gateways['stripe'] );
		return array_merge( [ 'stripe' => $stripe ], $gateways );
	}
	return $gateways;
} );

add_action( 'woocommerce_review_order_before_payment', function (): void {
	echo '<p class="spoke-stripe-notice" style="'
		. 'display:flex;align-items:center;gap:6px;'
		. 'font-size:0.8125rem;font-weight:600;'
		. 'color:var(--wp--preset--color--on-surface-variant);'
		. 'margin-bottom:0.75rem;">'
		. '🔒 ' . esc_html__( 'Payment secured by Stripe — we never store your card details.', 'spoke-theme' )
		. '</p>';
} );

add_filter( 'woocommerce_payment_complete_order_status', function ( string $status, int $order_id, \WC_Order $order ): string {
	if ( $order->has_downloadable_item() || spoke_order_is_all_virtual( $order ) ) {
		return 'completed';
	}
	return $status;
}, 10, 3 );


// ─────────────────────────────────────────────────────────────────
// 13. WOOCOMMERCE — DIGITAL PRODUCT HARDENING
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_cart_needs_shipping_address', function ( bool $needs ): bool {
	if ( WC()->cart && WC()->cart->needs_shipping() ) {
		return $needs;
	}
	return false;
} );

add_filter( 'woocommerce_cart_needs_shipping', function ( bool $needs ): bool {
	if ( ! WC()->cart ) {
		return $needs;
	}
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		if ( ! $product->is_virtual() ) {
			return $needs;
		}
	}
	return false;
} );

add_action( 'woocommerce_thankyou', function ( int $order_id ): void {
	if ( ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( $order && spoke_order_is_all_virtual( $order ) && $order->has_status( 'processing' ) ) {
		$order->update_status( 'completed', __( 'Auto-completed: all-virtual order.', 'spoke-theme' ) );
	}
} );


// ─────────────────────────────────────────────────────────────────
// 14. WOOCOMMERCE — MY ACCOUNT MENU
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
	unset( $items['dashboard'] );

	$new = [];
	$new['tutor-dashboard'] = __( 'My Learning', 'spoke-theme' );
	foreach ( $items as $key => $label ) {
		$new[ $key ] = $label;
	}
	return $new;
} );

add_action( 'woocommerce_account_tutor-dashboard_endpoint', function (): void {
	wp_safe_redirect( home_url( '/dashboard/' ) );
	exit;
} );

add_filter( 'woocommerce_get_endpoint_url', function ( string $url, string $endpoint ): string {
	if ( 'tutor-dashboard' === $endpoint ) {
		return home_url( '/dashboard/' );
	}
	return $url;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 15. WOOCOMMERCE — REMOVE UNWANTED DEFAULT CONTENT
// ─────────────────────────────────────────────────────────────────

remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
add_filter( 'woocommerce_show_page_title', '__return_false' );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count',    20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

add_action( 'template_redirect', function (): void {
	if ( is_shop() && ! is_admin() ) {
		$courses_page = get_page_by_path( 'courses' );
		if ( $courses_page ) {
			wp_safe_redirect( get_permalink( $courses_page->ID ), 301 );
			exit;
		}
	}
} );


// ─────────────────────────────────────────────────────────────────
// 16. WOOCOMMERCE — CHECKOUT FIELDS (UK-SPECIFIC)
// ─────────────────────────────────────────────────────────────────

add_filter( 'woocommerce_billing_fields', function ( array $fields ): array {
	$remove = [ 'billing_company', 'billing_state', 'billing_phone' ];
	foreach ( $remove as $key ) {
		unset( $fields[ $key ] );
	}

	if ( isset( $fields['billing_address_1'] ) ) {
		$fields['billing_address_1']['label']       = __( 'Street Address', 'spoke-theme' );
		$fields['billing_address_1']['placeholder'] = __( '123 High Street', 'spoke-theme' );
	}

	if ( isset( $fields['billing_country'] ) ) {
		$fields['billing_country']['default'] = 'GB';
	}

	if ( isset( $fields['billing_postcode'] ) ) {
		$fields['billing_postcode']['label']       = __( 'Postcode', 'spoke-theme' );
		$fields['billing_postcode']['placeholder'] = 'SW1A 1AA';
	}

	return $fields;
} );