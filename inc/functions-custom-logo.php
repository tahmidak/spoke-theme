<?php
/**
 * Shortcode: [site_logo_or_title]
 * Clean, consistent, controllable output
 */
function custom_site_logo_or_title_shortcode() {

    $home_url = esc_url( home_url( '/' ) );

    // START wrapper link (same for both cases)
    $output = '<a href="' . $home_url . '" class="site-branding-link">';

    if ( has_custom_logo() ) {

        $logo_id = get_theme_mod( 'custom_logo' );
        $logo    = wp_get_attachment_image(
            $logo_id,
            'full',
            false,
            [
                'class' => 'site-logo-img',
                'alt'   => get_bloginfo( 'name' ),
            ]
        );

        $output .= $logo;

    } else {

        $output .= '<span class="site-title-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
    }

    $output .= '</a>';

    return '<div class="site-branding-smart">' . $output . '</div>';
}
add_shortcode( 'site_logo_or_title', 'custom_site_logo_or_title_shortcode' );
?>