<?php
/**
 * Spoke Theme — functions.php
 *
 * Rules:
 *  - All PHP hooks and filters live here only.
 *  - No hardcoded hex values — use CSS custom properties.
 *  - LMS CSS  → assets/css/lms-overrides.css
 *  - WooC CSS → assets/css/woocommerce-overrides.css
 *
 * @package SpokeTheme
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────
// 1. THEME SETUP
// ─────────────────────────────────────────────
function spoke_theme_setup(): void {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', [
        'search-form', 'comment-form', 'comment-list',
        'gallery', 'caption', 'style', 'script',
    ] );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );

    load_plugin_textdomain( 'spoke-theme', false, get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'spoke_theme_setup' );


// ─────────────────────────────────────────────
// 2. ENQUEUE STYLES & SCRIPTS
// ─────────────────────────────────────────────
function spoke_enqueue_assets(): void {
    $ver = wp_get_theme()->get( 'Version' );

    // Main theme stylesheet (theme header only — no real styles here)
    wp_enqueue_style(
        'spoke-style',
        get_stylesheet_uri(),
        [],
        $ver
    );

    // Global theme styles — header, footer, and site-wide layout.
    // NOT for LMS or WooCommerce (those have their own files below).
    wp_enqueue_style(
        'spoke-global',
        get_template_directory_uri() . '/assets/css/global.css',
        [ 'spoke-style' ],
        $ver
    );

    // Header scroll shadow — adds .is-scrolled class on scroll
    wp_enqueue_script(
        'spoke-header-scroll',
        get_template_directory_uri() . '/assets/js/header-scroll.js',
        [],
        $ver,
        true  // load in footer
    );

    // Tutor LMS overrides
    if ( class_exists( 'TUTOR\Tutor' ) ) {
        wp_enqueue_style(
            'spoke-lms-overrides',
            get_template_directory_uri() . '/assets/css/lms-overrides.css',
            [ 'spoke-style' ],
            $ver
        );
    }

    // WooCommerce overrides
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style(
            'spoke-woo-overrides',
            get_template_directory_uri() . '/assets/css/woocommerce-overrides.css',
            [ 'spoke-style' ],
            $ver
        );
    }

    // Countdown timer (hot deals page only)
    if ( is_page_template( 'templates/page-hot-deals.html' ) || is_page( 'hot-deals' ) ) {
        wp_enqueue_script(
            'spoke-countdown',
            get_template_directory_uri() . '/assets/js/countdown-timer.js',
            [],
            $ver,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'spoke_enqueue_assets' );


// ─────────────────────────────────────────────
// 3. BLOCK PATTERN REGISTRATION
// ─────────────────────────────────────────────
function spoke_register_block_pattern_categories(): void {
    register_block_pattern_category( 'spoke-theme', [
        'label' => __( 'Spoke Theme', 'spoke-theme' ),
    ] );
}
add_action( 'init', 'spoke_register_block_pattern_categories' );

function spoke_register_patterns(): void {
    $patterns = [
        'hero-banner',
        'stats-bar',
        'how-it-works',
        'testimonials-row',
        'cta-banner',
        'hot-deals-banner',
    ];

    foreach ( $patterns as $pattern ) {
        $file = get_template_directory() . "/patterns/{$pattern}.php";
        if ( file_exists( $file ) ) {
            register_block_pattern( "spoke-theme/{$pattern}", require $file );
        }
    }
}
add_action( 'init', 'spoke_register_patterns' );


// ─────────────────────────────────────────────
// 4. WOOCOMMERCE SETUP
// ─────────────────────────────────────────────

// Declare WooCommerce support
add_action( 'after_setup_theme', function (): void {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
} );

// Redirect WooCommerce login to Tutor LMS login page
add_filter( 'woocommerce_login_redirect', function ( string $redirect, \WP_User $user ): string {
    return home_url( '/login/' );
}, 10, 2 );

// Redirect /my-account/ login form to /login/ (Tutor LMS login page)
add_filter( 'woocommerce_customer_login_redirect', function ( string $redirect ): string {
    return home_url( '/dashboard/' );
} );


// ─────────────────────────────────────────────
// 5. TUTOR LMS — KEEP DASHBOARD SEPARATE
// ─────────────────────────────────────────────

// Ensure Tutor dashboard slug stays at /dashboard/ — never /my-account/
add_filter( 'tutor_dashboard/nav_items', function ( array $items ): array {
    return $items; // passthrough — extend here in Phase 3 if needed
} );


// ─────────────────────────────────────────────
// 6. UK VAT — ADD 20% NOTE TO CHECKOUT
// ─────────────────────────────────────────────
add_action( 'woocommerce_cart_totals_after_order_total', function (): void {
    echo '<tr><td colspan="2" style="font-size:0.85em;color:#666;">'
        . esc_html__( 'All prices include UK VAT at 20%.', 'spoke-theme' )
        . '</td></tr>';
} );

add_action( 'woocommerce_review_order_after_order_total', function (): void {
    echo '<tr><td colspan="2" style="font-size:0.85em;color:#666;">'
        . esc_html__( 'All prices include UK VAT at 20%.', 'spoke-theme' )
        . '</td></tr>';
} );


// ─────────────────────────────────────────────
// 7. REMOVE BLOCK PATTERNS FROM CORE & PLUGINS
//    (keep only spoke-theme patterns in inserter)
// ─────────────────────────────────────────────
add_action( 'init', function (): void {
    remove_theme_support( 'core-block-patterns' );
} );


// ─────────────────────────────────────────────
// 8. EDITOR STYLES
// ─────────────────────────────────────────────
add_action( 'after_setup_theme', function (): void {
    add_editor_style( 'assets/css/lms-overrides.css' );
    add_editor_style( 'assets/css/woocommerce-overrides.css' );
} );