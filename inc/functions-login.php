<?php
/**
 * SPOKE LOGIN PAGE — inc/functions-login.php
 *
 * Provides [spoke_login_page] shortcode used in page-login.html.
 * All PHP (wp_login_url, is_user_logged_in, etc.) lives here —
 * never inside .html block templates which cannot execute PHP.
 *
 * Rules:
 *  - No HTML comments inside rendered output (triggers wpautop <br>)
 *  - No blank lines between echo statements
 *  - Use ob_start / ob_get_clean, scrub <br> and empty <p> before return
 *
 * @package SpokeTheme
 */

add_filter( 'the_content', function ( string $content ): string {
	if ( has_shortcode( $content, 'spoke_login_page' ) ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'wptexturize' );
	}
	return $content;
}, 9 );

add_filter( 'the_content', 'shortcode_unautop', 10 );

add_shortcode( 'spoke_login_page', function (): string {
	remove_filter( 'the_content', 'wpautop' );
	remove_filter( 'the_content', 'wptexturize' );

	if ( is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/dashboard/' ) );
		exit;
	}

	$login_url       = esc_url( wp_login_url() );
	$lost_pwd_url    = esc_url( wp_lostpassword_url() );
	$redirect_url    = esc_url( home_url( '/dashboard/' ) );
	$tutor_shortcode = function_exists( 'tutor' ) && shortcode_exists( 'tutor_login' );

	ob_start();
	echo '<div style="min-height:calc(100vh - 80px);display:flex;align-items:center;justify-content:center;padding:48px 16px;background:#f3f4f5;font-family:\'Inter\',sans-serif;">';
	echo '<div style="width:100%;max-width:1100px;display:grid;grid-template-columns:1fr;gap:32px;align-items:center;" class="lp-grid">';

	echo '<div style="background:#fff;border-radius:16px;padding:40px;display:flex;flex-direction:column;gap:24px;box-shadow:0 20px 60px rgba(26,60,110,0.12);">';

	echo '<div style="display:flex;justify-content:center;">';
	echo '<a href="/" style="display:flex;align-items:center;gap:8px;text-decoration:none;" aria-label="StudyMate Central Home">';
	echo '<div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#1A3C6E;flex-shrink:0;">';
	echo '<svg viewBox="0 0 24 24" style="width:20px;height:20px;color:#fff;" fill="currentColor" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
	echo '</div>';
	echo '<span style="font-weight:700;font-size:18px;letter-spacing:-0.05em;color:#1A3C6E;">StudyMate <span style="color:#F4A726;">Central</span></span>';
	echo '</a>';
	echo '</div>';

	echo '<div style="text-align:center;">';
	echo '<h1 style="font-weight:700;font-size:28px;letter-spacing:-0.03em;margin:0 0 4px;color:#1A3C6E;">Welcome Back</h1>';
	echo '<p style="font-size:14px;margin:0;color:#43474f;">Access your learning dashboard to continue your professional journey.</p>';
	echo '</div>';

	if ( $tutor_shortcode ) {
		echo '<div class="spoke-tutor-login">';
		echo do_shortcode( '[tutor_login]' );
		echo '</div>';
	} else {
		echo '<form id="lf-form" method="post" action="' . $login_url . '" novalidate style="display:flex;flex-direction:column;gap:16px;">';

		echo '<div style="display:flex;flex-direction:column;gap:6px;">';
		echo '<label for="lf-email" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#43474f;">Email Address <span style="color:#F4A726;">*</span></label>';
		echo '<div style="position:relative;">';
		echo '<svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#43474f;pointer-events:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
		echo '<input type="email" id="lf-email" name="log" placeholder="your@email.co.uk" required autocomplete="username email" style="width:100%;height:50px;padding:0 16px 0 44px;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;box-sizing:border-box;transition:border-color 200ms,box-shadow 200ms;" onfocus="this.style.borderColor=\'#F4A726\';this.style.boxShadow=\'0 0 0 3px rgba(244,167,38,0.15)\'" onblur="this.style.borderColor=\'rgba(0,0,0,0.12)\';this.style.boxShadow=\'none\'">';
		echo '</div>';
		echo '</div>';

		echo '<div style="display:flex;flex-direction:column;gap:6px;">';
		echo '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">';
		echo '<label for="lf-pwd" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#43474f;">Password <span style="color:#F4A726;">*</span></label>';
		echo '<a href="' . $lost_pwd_url . '" style="font-size:12px;font-weight:600;text-decoration:underline;color:#1A3C6E;">Forgot password?</a>';
		echo '</div>';
		echo '<div style="position:relative;">';
		echo '<svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#43474f;pointer-events:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>';
		echo '<input type="password" id="lf-pwd" name="pwd" placeholder="••••••••" required autocomplete="current-password" style="width:100%;height:50px;padding:0 48px 0 44px;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;box-sizing:border-box;transition:border-color 200ms,box-shadow 200ms;" onfocus="this.style.borderColor=\'#F4A726\';this.style.boxShadow=\'0 0 0 3px rgba(244,167,38,0.15)\'" onblur="this.style.borderColor=\'rgba(0,0,0,0.12)\';this.style.boxShadow=\'none\'">';
		echo '<button type="button" id="lf-pw-toggle" aria-label="Toggle password visibility" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#43474f;display:flex;align-items:center;">';
		echo '<svg id="lf-eye-show" style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
		echo '<svg id="lf-eye-hide" style="width:16px;height:16px;display:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
		echo '</button>';
		echo '</div>';
		echo '</div>';

		echo '<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">';
		echo '<input type="checkbox" name="rememberme" value="forever" style="width:16px;height:16px;accent-color:#1A3C6E;flex-shrink:0;">';
		echo '<span style="font-size:13px;color:#43474f;">Remember me for 30 days</span>';
		echo '</label>';

		echo '<input type="hidden" name="redirect_to" value="' . $redirect_url . '">';
		echo '<input type="hidden" name="testcookie" value="1">';

		echo '<div id="lf-error" role="alert" style="display:none;border-radius:8px;padding:12px 16px;font-size:13px;font-weight:600;background:rgba(186,26,26,0.08);color:#BA1A1A;">Incorrect email or password. Please try again.</div>';

		echo '<button type="submit" style="width:100%;height:52px;border-radius:8px;font-family:inherit;font-weight:700;font-size:17px;border:none;cursor:pointer;background:#1A3C6E;color:#fff;transition:filter 150ms,transform 150ms;" onmouseenter="this.style.filter=\'brightness(1.12)\';this.style.transform=\'translateY(-1px)\'" onmouseleave="this.style.filter=\'\';this.style.transform=\'\'">Sign In to Dashboard</button>';

		echo '<div style="display:flex;align-items:center;gap:12px;">';
		echo '<div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>';
		echo '<span style="font-size:12px;color:#43474f;white-space:nowrap;">or continue with</span>';
		echo '<div style="flex:1;height:1px;background:rgba(0,0,0,0.1);"></div>';
		echo '</div>';

		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">';
		echo '<button type="button" aria-label="Continue with Google" style="display:flex;align-items:center;justify-content:center;gap:8px;height:44px;border-radius:8px;border:1px solid rgba(0,0,0,0.12);background:#fff;font-family:inherit;font-size:13px;font-weight:600;color:#1A3C6E;cursor:pointer;transition:filter 150ms;" onmouseenter="this.style.filter=\'brightness(0.97)\'" onmouseleave="this.style.filter=\'\'">';
		echo '<svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
		echo 'Google';
		echo '</button>';
		echo '<button type="button" aria-label="Continue with LinkedIn" style="display:flex;align-items:center;justify-content:center;gap:8px;height:44px;border-radius:8px;border:1px solid rgba(0,0,0,0.12);background:#fff;font-family:inherit;font-size:13px;font-weight:600;color:#1A3C6E;cursor:pointer;transition:filter 150ms;" onmouseenter="this.style.filter=\'brightness(0.97)\'" onmouseleave="this.style.filter=\'\'">';
		echo '<svg width="18" height="18" fill="#0A66C2" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
		echo 'LinkedIn';
		echo '</button>';
		echo '</div>';

		echo '</form>';
	}

	echo '<p style="text-align:center;font-size:13px;margin:0;color:#43474f;">New to StudyMate Central? <a href="/courses/" style="font-weight:600;text-decoration:underline;color:#1A3C6E;">Browse our courses &#8594;</a></p>';

	echo '<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,0.07);font-size:12px;font-weight:600;color:#43474f;">';
	echo '<span>&#128274; Secure</span>';
	echo '<span>&#127891; 10,000+ learners</span>';
	echo '<span>&#10003; CPD Accredited</span>';
	echo '</div>';

	echo '</div>';

	echo '<div class="lp-promo" style="border-radius:16px;padding:40px;display:flex;flex-direction:column;gap:28px;background:linear-gradient(135deg,#1A3C6E 0%,#1A1A2E 100%);" aria-hidden="true">';

	echo '<h2 style="font-weight:700;font-size:clamp(1.5rem,2.5vw,2rem);line-height:1.25;letter-spacing:-0.03em;margin:0;color:#fff;">Continue Your Professional Journey</h2>';
	echo '<p style="font-size:16px;line-height:1.65;margin:0;color:rgba(255,255,255,0.7);">Access your accredited courses, track your progress, and download your certificates — all in one place.</p>';

	echo '<ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:16px;">';
	$perks = [
		[ 'Your Progress, Saved',      'Pick up exactly where you left off, on any device.' ],
		[ 'Certificates &amp; Records','Download and share your accredited certificates instantly.' ],
		[ 'Exclusive Member Deals',    'Exclusive pricing on new courses — available only when logged in.' ],
		[ 'Instructor Q&amp;A Access', 'Direct access to your course instructors for expert guidance.' ],
	];
	foreach ( $perks as $perk ) {
		echo '<li style="display:flex;align-items:flex-start;gap:12px;">';
		echo '<span style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;margin-top:2px;background:rgba(244,167,38,0.2);border:1px solid rgba(244,167,38,0.4);color:#F4A726;">&#10003;</span>';
		echo '<div><strong style="display:block;font-size:14px;font-weight:700;color:#fff;margin-bottom:2px;">' . $perk[0] . '</strong><p style="font-size:13px;margin:0;color:rgba(255,255,255,0.55);">' . $perk[1] . '</p></div>';
		echo '</li>';
	}
	echo '</ul>';

	echo '<div style="border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">';
	echo '<div style="color:#F4A726;font-size:1rem;letter-spacing:2px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>';
	echo '<blockquote style="font-size:15px;font-style:italic;line-height:1.65;margin:0;padding:0;color:rgba(255,255,255,0.85);border:none;">&#8220;Within three months of completing my Strategic Management course, I secured a promotion to Senior level.&#8221;</blockquote>';
	echo '<div style="display:flex;align-items:center;gap:10px;">';
	echo '<div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;background:#F4A726;color:#6b4500;">JW</div>';
	echo '<div><strong style="display:block;color:#fff;font-size:13px;font-weight:700;">James Whitaker</strong><span style="font-size:12px;color:rgba(255,255,255,0.55);">Senior Project Manager</span></div>';
	echo '</div>';
	echo '</div>';

	echo '</div>';

	echo '</div>';

	echo '<style>';
	echo '.lp-grid{grid-template-columns:1fr;}';
	echo '.lp-promo{display:none;}';
	echo '@media(min-width:1024px){.lp-grid{grid-template-columns:480px 1fr;}.lp-promo{display:flex;}}';
	echo '.spoke-tutor-login .tutor-login-wrap{background:transparent!important;box-shadow:none!important;padding:0!important;}';
	echo '.spoke-tutor-login input[type=email],.spoke-tutor-login input[type=password],.spoke-tutor-login input[type=text]{width:100%!important;height:50px!important;padding:0 16px!important;background:#f3f4f5!important;border:1px solid rgba(0,0,0,0.12)!important;border-radius:8px!important;font-family:inherit!important;font-size:16px!important;color:#191c1d!important;box-sizing:border-box!important;}';
	echo '.spoke-tutor-login button[type=submit],.spoke-tutor-login input[type=submit]{width:100%!important;height:52px!important;border-radius:8px!important;font-weight:700!important;font-size:17px!important;border:none!important;cursor:pointer!important;background:#1A3C6E!important;color:#fff!important;}';
	echo '</style>';

	echo '<script>(function(){';
	echo 'var tog=document.getElementById("lf-pw-toggle"),pwd=document.getElementById("lf-pwd"),es=document.getElementById("lf-eye-show"),eh=document.getElementById("lf-eye-hide");';
	echo 'if(tog&&pwd){tog.addEventListener("click",function(){var h=pwd.type==="password";pwd.type=h?"text":"password";if(es)es.style.display=h?"none":"";if(eh)eh.style.display=h?"":"none";});}';
	echo 'var form=document.getElementById("lf-form"),err=document.getElementById("lf-error");';
	echo 'if(form){form.addEventListener("submit",function(e){var em=document.getElementById("lf-email"),pw=document.getElementById("lf-pwd");if(em&&pw&&(!em.value.trim()||!pw.value)){e.preventDefault();if(err)err.style.display="block";}});}';
	echo '})();</script>';

	echo '</div>';

	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );

	add_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wptexturize' );

	return $html;
} );
