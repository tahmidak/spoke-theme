<?php

/**
 * SPOKE DYNAMIC FOOTER — inc/functions-footer.php
 *
 * Makes the footer template part fully dynamic:
 *  - Logo (custom logo or site title fallback)
 *  - Site description from Settings › General
 *  - Social media URLs from Appearance › Customizer › Social Links
 *  - "Quick Links" column from "Footer Quick Links" nav menu
 *  - "Support" column from "Footer Support" nav menu
 *  - Newsletter shortcode placeholder (swap in [fluentform id="2"])
 *  - Copyright year auto-updates
 *
 * Add ONE line to functions.php:
 *   require_once get_template_directory() . '/inc/functions-footer.php';
 *
 * Then in WP Admin:
 *   Appearance › Menus → assign menus to "Footer Quick Links" and "Footer Support"
 *   Appearance › Customize › Social Links → enter social profile URLs
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. THEME SUPPORT + MENU LOCATIONS
// ─────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {

	// Declare custom-logo support — this makes the logo upload field
	// appear under Appearance › Customize › Site Identity.
	// Without this, has_custom_logo() always returns false.
	add_theme_support('custom-logo', [
		'height'               => 80,
		'width'                => 300,
		'flex-height'          => true,
		'flex-width'           => true,
		'unlink-homepage-logo' => false,
	]);

	register_nav_menus([
		'footer-quick-links' => __('Footer Quick Links', 'spoke-theme'),
		'footer-support'     => __('Footer Support', 'spoke-theme'),
	]);
});


// ─────────────────────────────────────────────────────────────────
// 2. CUSTOMIZER — Social Media URLs
// ─────────────────────────────────────────────────────────────────

add_action('customize_register', function (\WP_Customize_Manager $wp_customize): void {

	$wp_customize->add_section('spoke_social_links', [
		'title'       => __('Social Media Links', 'spoke-theme'),
		'description' => __('Leave a field empty to hide that social icon in the footer.', 'spoke-theme'),
		'priority'    => 120,
	]);

	$socials = [
		'twitter'   => 'Twitter / X URL',
		'linkedin'  => 'LinkedIn URL',
		'facebook'  => 'Facebook URL',
		'instagram' => 'Instagram URL',
		'youtube'   => 'YouTube URL',
	];

	foreach ($socials as $key => $label) {
		$wp_customize->add_setting("spoke_social_{$key}", [
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		]);
		$wp_customize->add_control("spoke_social_{$key}", [
			'label'   => __($label, 'spoke-theme'),
			'section' => 'spoke_social_links',
			'type'    => 'url',
		]);
	}
});


// ─────────────────────────────────────────────────────────────────
// 3. SHORTCODE — [spoke_dynamic_footer]
// ─────────────────────────────────────────────────────────────────

add_shortcode('spoke_dynamic_footer', function (): string {
	ob_start();
	spoke_render_dynamic_footer();
	$html = ob_get_clean();
	$html = preg_replace('/<br\s*\/?>/i', '', $html);
	$html = preg_replace('/<p>(\s|&nbsp;)*<\/p>/i', '', $html);
	return $html;
});


// ─────────────────────────────────────────────────────────────────
// 4. RENDER BLOCK FILTER — intercepts <!-- wp:html --> in footer.html
// ─────────────────────────────────────────────────────────────────

add_filter('render_block', function (string $block_content, array $block): string {
	if (! isset($block['blockName']) || $block['blockName'] !== 'core/html') {
		return $block_content;
	}
	$raw = isset($block['innerHTML']) ? $block['innerHTML'] : '';
	if (false === strpos($raw, 'spoke_dynamic_footer')) {
		return $block_content;
	}
	ob_start();
	spoke_render_dynamic_footer();
	$html = ob_get_clean();
	$html = preg_replace('/<br\s*\/?>/i', '', $html);
	$html = preg_replace('/<p>(\s|&nbsp;)*<\/p>/i', '', $html);
	$html = preg_replace('/(\s*\n){3,}/', "\n", $html);
	return $html;
}, 10, 2);


// ─────────────────────────────────────────────────────────────────
// 5. WALKER — styles each nav menu <li><a> for the dark footer
// ─────────────────────────────────────────────────────────────────

if (! class_exists('Spoke_Footer_Nav_Walker')) {
	class Spoke_Footer_Nav_Walker extends Walker_Nav_Menu
	{

		public function start_lvl(&$output, $depth = 0, $args = null)
		{
			// Suppress sub-menus — footer nav is always flat.
		}

		public function end_lvl(&$output, $depth = 0, $args = null) {}

		public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0)
		{
			$item    = $data_object;
			$classes = in_array('current-menu-item', $item->classes, true) ? ' font-semibold' : '';
			$target  = ! empty($item->target) ? ' target="' . esc_attr($item->target) . '" rel="noopener noreferrer"' : '';
			$output .= '<li>';
			$output .= '<a href="' . esc_url($item->url) . '"' . $target
				. ' class="' . esc_attr('transition-colors duration-200' . $classes) . '"'
				. ' style="color:rgba(255,255,255,0.55);font-size:14px;text-decoration:none;"'
				. ' onmouseover="this.style.color=\'var(--wp--preset--color--accent)\'"'
				. ' onmouseout="this.style.color=\'rgba(255,255,255,0.55)\'">';
			$output .= esc_html($item->title);
			$output .= '</a></li>';
		}

		public function end_el(&$output, $data_object, $depth = 0, $args = null) {}
	}
}


// ─────────────────────────────────────────────────────────────────
// 6. MAIN RENDER FUNCTION
// ─────────────────────────────────────────────────────────────────

function spoke_render_dynamic_footer(): void
{

	// ── Logo / Site Title ────────────────────────────────────────
	// has_custom_logo() only works after add_theme_support('custom-logo')
	// is declared — which we do in the after_setup_theme hook above.
	$logo_id = (int) get_theme_mod('custom_logo', 0);

	if ($logo_id > 0) {
		// Logo image is uploaded: render it inside a home link.
		// height:40px keeps it compact; width:auto preserves aspect ratio.
		// We add filter:brightness(0) invert(1) so dark/coloured logos
		// still read on the dark footer — remove those two lines if your
		// logo is already white / transparent-background.
		$logo_img   = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			[
				'style'   => 'height:40px;width:auto;display:block;',
				'loading' => 'lazy',
				'alt'     => esc_attr(get_bloginfo('name')),
			]
		);
		$logo_html  = '<a href="' . esc_url(home_url('/')) . '"'
			. ' style="display:inline-flex;align-items:center;text-decoration:none;"'
			. ' aria-label="' . esc_attr(get_bloginfo('name')) . ' – Home">';
		$logo_html .= $logo_img;
		$logo_html .= '</a>';
	} else {
		// No logo uploaded yet — show the amber icon + site name as text.
		$logo_html  = '<a href="' . esc_url(home_url('/')) . '"'
			. ' style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;"'
			. ' aria-label="' . esc_attr(get_bloginfo('name')) . ' – Home">';
		$logo_html .= '<div style="width:32px;height:32px;background:var(--wp--preset--color--accent);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
		$logo_html .= '<svg viewBox="0 0 24 24" style="width:20px;height:20px;" fill="white" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
		$logo_html .= '</div>';
		$logo_html .= '<span style="color:#fff;font-weight:700;font-size:18px;letter-spacing:-0.05em;line-height:1;">' . esc_html(get_bloginfo('name')) . '</span>';
		$logo_html .= '</a>';
	}

	// ── Site Description ─────────────────────────────────────────
	$description = get_bloginfo('description');

	// ── Social Links ─────────────────────────────────────────────
	$socials = [
		'twitter'   => [
			'url'   => get_theme_mod('spoke_social_twitter', ''),
			'label' => 'Twitter / X',
			'svg'   => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
		],
		'linkedin'  => [
			'url'   => get_theme_mod('spoke_social_linkedin', ''),
			'label' => 'LinkedIn',
			'svg'   => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
		],
		'facebook'  => [
			'url'   => get_theme_mod('spoke_social_facebook', ''),
			'label' => 'Facebook',
			'svg'   => '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
		],
		'instagram' => [
			'url'   => get_theme_mod('spoke_social_instagram', ''),
			'label' => 'Instagram',
			'svg'   => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>',
		],
		'youtube'   => [
			'url'   => get_theme_mod('spoke_social_youtube', ''),
			'label' => 'YouTube',
			'svg'   => '<path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>',
		],
	];

	$social_html = '';
	foreach ($socials as $data) {
		if (empty($data['url'])) {
			continue;
		}
		$social_html .= '<a href="' . esc_url($data['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($data['label']) . '"';
		$social_html .= ' style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;flex-shrink:0;transition:background 200ms;"';
		$social_html .= ' onmouseover="this.style.background=\'var(--wp--preset--color--accent)\';this.style.color=\'#6b4500\';"';
		$social_html .= ' onmouseout="this.style.background=\'rgba(255,255,255,0.1)\';this.style.color=\'#fff\';">';
		$social_html .= '<svg style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">' . $data['svg'] . '</svg>';
		$social_html .= '</a>';
	}

	// ── Quick Links nav menu ─────────────────────────────────────
	$quick_links_html = '';
	if (has_nav_menu('footer-quick-links')) {
		$quick_links_html = wp_nav_menu([
			'theme_location' => 'footer-quick-links',
			'container'      => false,
			'items_wrap'     => '<ul class="flex flex-col gap-3">%3$s</ul>',
			'depth'          => 1,
			'walker'         => new Spoke_Footer_Nav_Walker(),
			'echo'           => false,
		]);
	} else {
		// Fallback — shown until a menu is assigned in the admin
		$fallback_links = [
			'/courses/'    => 'Browse All Courses',
			'/hot-deals/'  => 'Hot Deals',
			'/about/'      => 'About Us',
			'/blog/'       => 'Blog',
		];
		$quick_links_html = '<ul class="flex flex-col gap-3">';
		foreach ($fallback_links as $href => $text) {
			$quick_links_html .= '<li><a href="' . esc_url(home_url($href)) . '" style="color:rgba(255,255,255,0.55);font-size:14px;text-decoration:none;" onmouseover="this.style.color=\'var(--wp--preset--color--accent)\'" onmouseout="this.style.color=\'rgba(255,255,255,0.55)\'">' . esc_html($text) . '</a></li>';
		}
		$quick_links_html .= '</ul>';
	}

	// ── Support nav menu ─────────────────────────────────────────
	$support_html = '';
	if (has_nav_menu('footer-support')) {
		$support_html = wp_nav_menu([
			'theme_location' => 'footer-support',
			'container'      => false,
			'items_wrap'     => '<ul class="flex flex-col gap-3">%3$s</ul>',
			'depth'          => 1,
			'walker'         => new Spoke_Footer_Nav_Walker(),
			'echo'           => false,
		]);
	} else {
		// Fallback
		$fallback_support = [
			'/contact/'                    => 'Contact Us',
			'/privacy-policy/'             => 'Privacy Policy',
			'/terms-of-service/'           => 'Terms of Service',
			'/refund-and-returns-policy/'  => 'Refund Policy',
		];
		$support_html = '<ul class="flex flex-col gap-3">';
		foreach ($fallback_support as $href => $text) {
			$support_html .= '<li><a href="' . esc_url(home_url($href)) . '" style="color:rgba(255,255,255,0.55);font-size:14px;text-decoration:none;" onmouseover="this.style.color=\'var(--wp--preset--color--accent)\'" onmouseout="this.style.color=\'rgba(255,255,255,0.55)\'">' . esc_html($text) . '</a></li>';
		}
		$support_html .= '</ul>';
	}

	// ── Newsletter shortcode ─────────────────────────────────────
	// Swap [fluentform id="2"] below once your form ID is confirmed.
	$newsletter_form = '';
	if (shortcode_exists('fluentform')) {
		$newsletter_form = do_shortcode('[fluentform id="2"]');
	} else {
		$newsletter_form = '<form class="flex flex-col gap-2.5" action="#" method="post" aria-label="Newsletter signup">'
			. '<label for="footer-email" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0;">Email address</label>'
			. '<input id="footer-email" type="email" name="email" placeholder="your@email.co.uk" required autocomplete="email"'
			. ' style="width:100%;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:12px 16px;font-size:14px;color:#fff;font-family:inherit;box-sizing:border-box;"'
			. ' onfocus="this.style.borderColor=\'var(--wp--preset--color--accent)\'" onblur="this.style.borderColor=\'rgba(255,255,255,0.15)\'">'
			. '<button type="submit" style="width:100%;background:var(--wp--preset--color--accent);color:#6b4500;font-weight:700;font-size:14px;padding:12px;border-radius:8px;border:none;cursor:pointer;font-family:inherit;transition:filter 200ms;" onmouseover="this.style.filter=\'brightness(1.07)\'" onmouseout="this.style.filter=\'\'">Subscribe</button>'
			. '<p style="color:rgba(255,255,255,0.35);font-size:11px;margin:0;">No spam. Unsubscribe anytime.</p>'
			. '</form>';
	}

	// ── Copyright ─────────────────────────────────────────────────
	$site_name = esc_html(get_bloginfo('name'));

	// ══ OUTPUT ══════════════════════════════════════════════════════
	echo '<footer style="background:var(--wp--preset--color--dark);color:#fff;" aria-label="Site footer">';

	// Main columns
	echo '<div style="max-width:1280px;margin:0 auto;padding:56px 24px 40px;" class="px-6 lg:px-8">';
	echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">';

	// Col 1 — Brand
	echo '<div class="sm:col-span-2 lg:col-span-1 flex flex-col gap-5">';
	echo $logo_html;
	if ($description) {
		echo '<p style="color:rgba(255,255,255,0.55);font-size:14px;line-height:1.6;max-width:260px;margin:0;">' . esc_html($description) . '</p>';
	}
	if ($social_html) {
		echo '<div class="flex gap-2.5">' . $social_html . '</div>';
	}
	echo '</div>';

	// Col 2 — Quick Links
	echo '<div class="flex flex-col gap-5">';
	echo '<h3 style="color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;margin:0;">' . __('Quick Links', 'spoke-theme') . '</h3>';
	echo $quick_links_html;
	echo '</div>';

	// Col 3 — Support
	echo '<div class="flex flex-col gap-5">';
	echo '<h3 style="color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;margin:0;">' . __('Support', 'spoke-theme') . '</h3>';
	echo $support_html;
	echo '</div>';

	// Col 4 — Newsletter
	echo '<div class="flex flex-col gap-5">';
	echo '<h3 style="color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;margin:0;">' . __('Newsletter', 'spoke-theme') . '</h3>';
	echo '<p style="color:rgba(255,255,255,0.55);font-size:14px;line-height:1.6;margin:0;">Join 50,000+ professionals receiving weekly course deals and career insights.</p>';
	echo $newsletter_form;
	echo '</div>';

	echo '</div>'; // grid
	echo '</div>'; // max-width wrapper

	// Bottom bar
	echo '<div style="border-top:1px solid rgba(255,255,255,0.1);">';
	echo '<div style="max-width:1280px;margin:0 auto;padding:20px 24px;" class="flex flex-col sm:flex-row items-center justify-between gap-4">';
	echo '<p style="color:rgba(255,255,255,0.35);font-size:13px;margin:0;">';
	echo '&copy; <span id="spoke-footer-year">' . date('Y') . '</span> ' . $site_name . '. All Rights Reserved.';
	echo '</p>';
	echo '<div class="flex flex-wrap items-center justify-center sm:justify-end gap-4 sm:gap-5">';
	echo '<span style="color:rgba(255,255,255,0.35);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;">🔒 Stripe Secure</span>';
	echo '<span style="color:rgba(255,255,255,0.35);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;">✓ CPD Certified</span>';
	echo '<span style="color:rgba(255,255,255,0.35);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;">↩ 30-Day Guarantee</span>';
	echo '</div>';
	echo '</div>';
	echo '</div>';

	echo '</footer>';

	echo '<script>(function(){var el=document.getElementById("spoke-footer-year");if(el)el.textContent=new Date().getFullYear();})();</script>';
}
