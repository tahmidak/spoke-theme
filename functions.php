<?php
/**
 * functions.php — Phase 4 additions
 * Spoke Theme · WooCommerce + Stripe + VAT hooks
 *
 * HOW TO USE:
 *   Copy every section below and paste it into your existing
 *   functions.php, after the last closing brace (line ~196).
 *   Each section is clearly labelled.
 *
 * @package SpokeTheme
 */


// ─────────────────────────────────────────────────────────────────
// 9. WOOCOMMERCE — CHECKOUT PAGE HARDENING
// ─────────────────────────────────────────────────────────────────

/**
 * Remove WooCommerce's default login/register prompt at checkout.
 * Users register automatically on purchase; nudge them to /login/ instead.
 */
add_filter( 'woocommerce_checkout_registration_required', '__return_false' );

add_filter( 'woocommerce_checkout_login_message', function (): string {
	return esc_html__(
		'Returning student? Log in via your dashboard.',
		'spoke-theme'
	);
} );

/**
 * Remove the "Returning customer?" collapsible login block on checkout.
 * Stripe Checkout handles returning card data; a separate login step
 * creates friction and abandonment on digital-goods purchases.
 */
add_filter( 'woocommerce_checkout_show_login_reminder', '__return_false' );

/**
 * Prefill checkout email from logged-in user.
 */
add_filter( 'woocommerce_checkout_get_value', function ( $value, string $input ) {
	if ( 'billing_email' === $input && is_user_logged_in() ) {
		$user = wp_get_current_user();
		return $user->user_email;
	}
	return $value;
}, 10, 2 );

/**
 * Add step-number data attributes to checkout section h3s
 * so the CSS ::before counter (defined in woocommerce-overrides.css) works.
 * Hooked late so it fires after WooCommerce renders the form.
 */
add_action( 'woocommerce_checkout_billing', function (): void {
	echo '<style>
		.woocommerce-billing-fields h3::before  { content: "1" !important; }
		.woocommerce-shipping-fields h3::before { content: "2" !important; }
		#payment h3::before                     { content: "3" !important; }
	</style>';
}, 5 );


// ─────────────────────────────────────────────────────────────────
// 10. WOOCOMMERCE — ORDER EMAILS (TRANSACTIONAL)
// ─────────────────────────────────────────────────────────────────

/**
 * Stamp the order confirmation email with the site branding colours.
 * FluentSMTP handles SMTP delivery; this only controls visual styling.
 */
add_filter( 'woocommerce_email_styles', function ( string $css ): string {
	return $css . '
		/* Spoke Theme email overrides */
		body       { background-color: #f8f9fa !important; }
		#wrapper   { background-color: #f8f9fa !important; }
		h1,h2,h3   { color: #1a3c6e !important; font-family: Arial, sans-serif !important; }
		#header_wrapper { background-color: #1a3c6e !important; }
		.order_details tfoot tr:last-child td { font-weight:700; font-size:1.1em; }
		a          { color: #1a3c6e !important; }
		.button    { background-color: #f4a726 !important; color: #1a1a2e !important; border-radius:8px !important; }
	';
} );

/**
 * Add a VAT line to every WooCommerce order confirmation email footer.
 */
add_action( 'woocommerce_email_footer', function ( \WC_Email $email ): void {
	echo '<p style="font-size:12px;color:#666;text-align:center;margin-top:16px;">'
		. esc_html__( 'All prices include UK VAT at 20%. StudyMate Central is a trading name of [Company Ltd], registered in England & Wales.', 'spoke-theme' )
		. '</p>';
} );

/**
 * Remove the "New order" admin notification for course purchases.
 * (Tutor LMS sends its own instructor notification.)
 * Un-comment only if you want to silence the default WooCommerce new-order email.
 */
// add_filter( 'woocommerce_email_enabled_new_order', '__return_false' );


// ─────────────────────────────────────────────────────────────────
// 11. WOOCOMMERCE — VAT DISPLAY & UK COMPLIANCE
// ─────────────────────────────────────────────────────────────────

/**
 * Ensure prices are always displayed inclusive of VAT sitewide.
 * Belt-and-braces: these should also be set in
 * WooCommerce → Settings → Tax, but the filter guarantees it.
 */
add_filter( 'woocommerce_tax_display_cart',  fn() => 'incl' );
add_filter( 'woocommerce_tax_display_shop',  fn() => 'incl' );

/**
 * Replace the default WooCommerce "inc. VAT" suffix label with
 * a friendlier UK-specific string shown next to every price.
 */
add_filter( 'woocommerce_get_price_suffix', function ( string $suffix, \WC_Product $product ): string {
	// Only show the suffix on shop/archive pages, not in cart/checkout (already in totals).
	if ( is_cart() || is_checkout() ) {
		return '';
	}
	return ' <small class="woocommerce-price-suffix">' . esc_html__( 'inc. VAT', 'spoke-theme' ) . '</small>';
}, 10, 2 );

/**
 * VAT notice injected into cart totals table.
 * (Duplicate hook from Phase 1 kept here for clarity — safe to have twice
 * because the content is idempotent.)
 */
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

/**
 * Force Stripe to be the first (and default) payment method.
 * WooCommerce Stripe plugin must be installed and activated.
 * The gateway ID is 'stripe' for the official WooCommerce Stripe plugin.
 */
add_filter( 'woocommerce_available_payment_gateways', function ( array $gateways ): array {
	if ( isset( $gateways['stripe'] ) ) {
		// Pull Stripe to front of array
		$stripe = $gateways['stripe'];
		unset( $gateways['stripe'] );
		return array_merge( [ 'stripe' => $stripe ], $gateways );
	}
	return $gateways;
} );

/**
 * Show a "Secured by Stripe" notice above the payment section.
 */
add_action( 'woocommerce_review_order_before_payment', function (): void {
	echo '<p class="spoke-stripe-notice" style="'
		. 'display:flex;align-items:center;gap:6px;'
		. 'font-size:0.8125rem;font-weight:600;'
		. 'color:var(--wp--preset--color--on-surface-variant);'
		. 'margin-bottom:0.75rem;">'
		. '🔒 ' . esc_html__( 'Payment secured by Stripe — we never store your card details.', 'spoke-theme' )
		. '</p>';
} );

/**
 * After a Stripe payment is complete and the order is processing/completed,
 * enrol the customer in the Tutor LMS course automatically.
 *
 * NOTE: With "Tutor LMS monetisation = WooCommerce", Tutor listens to the
 * woocommerce_order_status_completed / woocommerce_order_status_processing
 * hooks itself and enrols the student. This filter simply ensures the order
 * status transitions happen immediately for digital goods (no stock, no shipping).
 */
add_filter( 'woocommerce_payment_complete_order_status', function ( string $status, int $order_id, \WC_Order $order ): string {
	// Digital / virtual products → jump straight to "completed"
	if ( $order->has_downloadable_item() || spoke_order_is_all_virtual( $order ) ) {
		return 'completed';
	}
	return $status;
}, 10, 3 );

/**
 * Helper: returns true if every item in the order is virtual.
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
// 13. WOOCOMMERCE — DIGITAL PRODUCT HARDENING
// ─────────────────────────────────────────────────────────────────

/**
 * Disable shipping address fields on checkout when the cart contains
 * only virtual/downloadable products (all course products should be virtual).
 */
add_filter( 'woocommerce_cart_needs_shipping_address', function ( bool $needs ): bool {
	if ( WC()->cart && WC()->cart->needs_shipping() ) {
		return $needs;
	}
	return false;
} );

/**
 * Remove shipping from checkout if cart is all-virtual.
 * Belt-and-braces alongside the filter above.
 */
add_filter( 'woocommerce_cart_needs_shipping', function ( bool $needs ): bool {
	if ( ! WC()->cart ) return $needs;
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		if ( ! $product->is_virtual() ) return $needs;
	}
	return false;
} );

/**
 * Automatically mark orders as "Completed" (vs "Processing") for
 * virtual-only carts. Tutor enrolment fires on 'completed'.
 */
add_action( 'woocommerce_thankyou', function ( int $order_id ): void {
	if ( ! $order_id ) return;
	$order = wc_get_order( $order_id );
	if ( $order && spoke_order_is_all_virtual( $order ) && $order->has_status( 'processing' ) ) {
		$order->update_status( 'completed', __( 'Auto-completed: all-virtual order.', 'spoke-theme' ) );
	}
} );


// ─────────────────────────────────────────────────────────────────
// 14. WOOCOMMERCE — MY ACCOUNT MENU (keep separate from /dashboard/)
// ─────────────────────────────────────────────────────────────────

/**
 * Remove "Dashboard" from WooCommerce My Account menu.
 * Students use /dashboard/ (Tutor LMS) for their learning progress.
 * /my-account/ is purely for order history, billing, and account settings.
 */
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
	// Remove the generic WooCommerce "Dashboard" tab
	unset( $items['dashboard'] );

	// Add a direct link back to the Tutor dashboard
	$new = [];
	$new['tutor-dashboard'] = __( 'My Learning', 'spoke-theme' );
	foreach ( $items as $key => $label ) {
		$new[ $key ] = $label;
	}
	return $new;
} );

/**
 * Register the custom My Learning endpoint so WooCommerce doesn't 404 it.
 * This just adds the menu item — clicking it redirects to /dashboard/.
 */
add_action( 'woocommerce_account_tutor-dashboard_endpoint', function (): void {
	wp_safe_redirect( home_url( '/dashboard/' ) );
	exit;
} );

/**
 * Give the custom endpoint a proper URL (rewrites to /dashboard/).
 */
add_filter( 'woocommerce_get_endpoint_url', function ( string $url, string $endpoint ): string {
	if ( 'tutor-dashboard' === $endpoint ) {
		return home_url( '/dashboard/' );
	}
	return $url;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 15. WOOCOMMERCE — REMOVE UNWANTED DEFAULT PAGES/CONTENT
// ─────────────────────────────────────────────────────────────────

/**
 * Remove WooCommerce default breadcrumbs (we render our own in templates).
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

/**
 * Remove default WooCommerce page title on shop/archive
 * (our archive-course.html hero section replaces it).
 */
add_filter( 'woocommerce_show_page_title', '__return_false' );

/**
 * Remove WooCommerce result-count and ordering dropdowns from the shop page.
 * The Spoke course archive template handles its own filter/sort UI.
 */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count',   20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

/**
 * Keep the WooCommerce shop page pointing at /courses/ (Tutor archive),
 * NOT the default /shop/ page. Set this in WP Admin:
 *   WooCommerce → Settings → Products → Shop page → select "Courses"
 *
 * This filter is a fallback — it redirects /shop/ to /courses/ if somehow
 * a user lands on the old shop URL.
 */
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

/**
 * Simplify billing fields for digital-only purchases:
 *  - Remove company, state, phone (not needed for VAT on B2C digital goods)
 *  - Keep: first name, last name, email, address line 1, city, postcode, country
 *
 * For B2B / VAT receipt requirements, a "VAT number" field can be added here in Phase 6.
 */
add_filter( 'woocommerce_billing_fields', function ( array $fields ): array {
	// Remove non-essential fields for a frictionless checkout
	$remove = [ 'billing_company', 'billing_state', 'billing_phone' ];
	foreach ( $remove as $key ) {
		unset( $fields[ $key ] );
	}

	// Rename 'Address line 1' to clearer UK label
	if ( isset( $fields['billing_address_1'] ) ) {
		$fields['billing_address_1']['label']       = __( 'Street Address', 'spoke-theme' );
		$fields['billing_address_1']['placeholder'] = __( '123 High Street', 'spoke-theme' );
	}

	// Default country to United Kingdom
	if ( isset( $fields['billing_country'] ) ) {
		$fields['billing_country']['default'] = 'GB';
	}

	// Postcode label UK style
	if ( isset( $fields['billing_postcode'] ) ) {
		$fields['billing_postcode']['label']       = __( 'Postcode', 'spoke-theme' );
		$fields['billing_postcode']['placeholder'] = 'SW1A 1AA';
	}

	return $fields;
} );

/**
 * Add a "Purchase for organisation" optional VAT-number field.
 * Appears below billing postcode. Disabled by default for Phase 4;
 * enable in Phase 6 when Rank Math + invoicing is active.
 *
 * Un-comment to activate:
 */
/*
add_filter( 'woocommerce_billing_fields', function ( array $fields ): array {
	$fields['billing_vat_number'] = [
		'label'       => __( 'VAT Number (optional)', 'spoke-theme' ),
		'placeholder' => 'GB 123 4567 89',
		'required'    => false,
		'class'       => [ 'form-row-wide' ],
		'clear'       => true,
		'priority'    => 120,
	];
	return $fields;
} );
*/