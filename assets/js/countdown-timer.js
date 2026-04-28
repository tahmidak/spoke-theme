/**
 * countdown-timer.js — Spoke Theme
 *
 * Drives the countdown timer on the Hot Deals page.
 * Looks for elements with IDs: hd-days, hd-hours, hd-mins, hd-secs
 * and a data-end attribute on a parent [data-countdown] element,
 * OR falls back to a fixed offset from page load.
 *
 * @package SpokeTheme
 */
( function () {
    'use strict';

    var daysEl  = document.getElementById( 'hd-days' );
    var hoursEl = document.getElementById( 'hd-hours' );
    var minsEl  = document.getElementById( 'hd-mins' );
    var secsEl  = document.getElementById( 'hd-secs' );

    // Nothing to do if the timer elements aren't on this page
    if ( ! daysEl || ! hoursEl || ! minsEl || ! secsEl ) { return; }

    // Try to read end time from a data attribute, otherwise default to 2 days from now
    var wrap   = document.querySelector( '[data-countdown]' );
    var endVal = wrap ? wrap.getAttribute( 'data-end' ) : null;
    var end    = endVal ? new Date( endVal ) : ( function () {
        var d = new Date();
        d.setDate( d.getDate() + 2 );
        d.setHours( 14, 33, 7, 0 );
        return d;
    }() );

    function pad( n ) {
        return n < 10 ? '0' + n : String( n );
    }

    function tick() {
        var remaining = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );

        daysEl.textContent  = pad( Math.floor( remaining / 86400 ) );
        hoursEl.textContent = pad( Math.floor( ( remaining % 86400 ) / 3600 ) );
        minsEl.textContent  = pad( Math.floor( ( remaining % 3600 ) / 60 ) );
        secsEl.textContent  = pad( remaining % 60 );

        if ( remaining > 0 ) {
            setTimeout( tick, 1000 );
        }
    }

    tick();
}() );