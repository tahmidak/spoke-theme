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
