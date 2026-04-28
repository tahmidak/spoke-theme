/**
 * header-scroll.js — Spoke Theme
 *
 * Adds/removes the `.is-scrolled` class on `.site-header`
 * as the user scrolls. Used by global.css to apply the
 * subtle box-shadow on scroll.
 *
 * No dependencies. Enqueued in the footer via functions.php.
 *
 * @package SpokeTheme
 */
( function () {
	'use strict';

	var header = document.querySelector( '.site-header' );
	if ( ! header ) return;

	var THRESHOLD = 8; // px scrolled before class is added

	function onScroll() {
		if ( window.scrollY > THRESHOLD ) {
			header.classList.add( 'is-scrolled' );
		} else {
			header.classList.remove( 'is-scrolled' );
		}
	}

	// Run once on load in case page is already scrolled
	onScroll();

	window.addEventListener( 'scroll', onScroll, { passive: true } );
} )();

/* ── Bridge classic AJAX add-to-cart → WC Blocks mini-cart update ── */
(function () {
    'use strict';
    if (typeof jQuery === 'undefined') { return; }

    jQuery(document.body).on('added_to_cart', function () {
        document.dispatchEvent( new CustomEvent('wc-blocks_added_to_cart') );
    });

    jQuery(document.body).on('removed_from_cart', function () {
        document.dispatchEvent( new CustomEvent('wc-blocks_removed_from_cart') );
    });
})();