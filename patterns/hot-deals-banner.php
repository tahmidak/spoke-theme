<?php
/**
 * Title: Hot Deals Banner
 * Slug: spoke-theme/hot-deals-banner
 * Categories: spoke-theme
 * Description: Promotional banner with countdown timer for limited-time offers.
 */
?>
<!-- wp:group {"tagName":"section","className":"hot-deals-banner","style":{"spacing":{"padding":{"top":"0","bottom":"var(--wp--preset--spacing--8)","left":"var(--wp--preset--spacing--4)","right":"var(--wp--preset--spacing--4)"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<section class="wp-block-group hot-deals-banner">

	<!-- wp:html -->
	<div class="hot-deals-inner">
		<div class="hot-deals-content">
			<span class="hot-deals-badge">Limited Time Offer</span>
			<h2 class="hot-deals-title">15% Discount for new UK enrollments</h2>
			<p class="hot-deals-sub">Accelerate your professional journey today. Discount expires in:</p>
			<div class="hot-deals-timer" id="hot-deals-countdown">
				<div class="timer-unit">
					<span class="timer-num" id="timer-hours">08</span>
					<span class="timer-label">Hours</span>
				</div>
				<div class="timer-sep">:</div>
				<div class="timer-unit">
					<span class="timer-num" id="timer-mins">42</span>
					<span class="timer-label">Mins</span>
				</div>
				<div class="timer-sep">:</div>
				<div class="timer-unit">
					<span class="timer-num" id="timer-secs">15</span>
					<span class="timer-label">Secs</span>
				</div>
			</div>
		</div>
		<div class="hot-deals-action">
			<a href="/hot-deals/" class="hot-deals-cta">Claim Offer</a>
		</div>
		<div class="hot-deals-orb hot-deals-orb--1"></div>
		<div class="hot-deals-orb hot-deals-orb--2"></div>
	</div>
	<!-- /wp:html -->

</section>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.hot-deals-inner {
	background: var(--wp--preset--color--accent);
	border-radius: 16px;
	padding: var(--wp--preset--spacing--6) var(--wp--preset--spacing--6);
	position: relative;
	overflow: hidden;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: var(--wp--preset--spacing--4);
}
.hot-deals-badge {
	display: inline-block;
	background: var(--wp--preset--color--primary);
	color: var(--wp--preset--color--white);
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	padding: 4px 12px;
	border-radius: 4px;
	margin-bottom: var(--wp--preset--spacing--2);
}
.hot-deals-title {
	font-size: clamp(1.5rem, 3vw, 2rem);
	font-weight: 800;
	color: var(--wp--preset--color--primary);
	letter-spacing: -0.02em;
	margin: 0 0 var(--wp--preset--spacing--1) 0;
	line-height: 1.2;
}
.hot-deals-sub {
	font-size: 1rem;
	font-weight: 500;
	color: var(--wp--preset--color--on-secondary-container, #6b4500);
	margin: 0 0 var(--wp--preset--spacing--3) 0;
}
.hot-deals-timer {
	display: flex;
	align-items: center;
	gap: var(--wp--preset--spacing--2);
}
.timer-unit {
	background: rgba(255,255,255,0.35);
	backdrop-filter: blur(4px);
	border-radius: 8px;
	padding: 10px 14px;
	min-width: 68px;
	text-align: center;
}
.timer-num {
	display: block;
	font-size: 1.5rem;
	font-weight: 700;
	color: var(--wp--preset--color--primary);
	line-height: 1;
}
.timer-label {
	display: block;
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: var(--wp--preset--color--primary);
	opacity: 0.7;
	margin-top: 2px;
}
.timer-sep {
	font-size: 1.5rem;
	font-weight: 700;
	color: var(--wp--preset--color--primary);
}
.hot-deals-cta {
	display: inline-block;
	background: var(--wp--preset--color--primary);
	color: var(--wp--preset--color--white) !important;
	font-size: 1.0625rem;
	font-weight: 700;
	padding: var(--wp--preset--spacing--2) var(--wp--preset--spacing--6);
	border-radius: 8px;
	text-decoration: none !important;
	transition: filter 200ms ease, transform 200ms ease;
	white-space: nowrap;
}
.hot-deals-cta:hover {
	filter: brightness(1.15);
	transform: translateY(-1px);
}
.hot-deals-orb {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
}
.hot-deals-orb--1 {
	width: 200px;
	height: 200px;
	background: rgba(255,255,255,0.12);
	top: -60px;
	right: -60px;
}
.hot-deals-orb--2 {
	width: 100px;
	height: 100px;
	background: rgba(26,60,110,0.06);
	bottom: -30px;
	left: -30px;
}
.hot-deals-content { position: relative; z-index: 1; }
.hot-deals-action { position: relative; z-index: 1; }
@media (max-width: 768px) {
	.hot-deals-inner { flex-direction: column; text-align: center; }
	.hot-deals-timer { justify-content: center; }
}
</style>
<script>
(function() {
	var end = new Date();
	end.setHours(end.getHours() + 8, end.getMinutes() + 42, end.getSeconds() + 15);
	function pad(n) { return n < 10 ? '0' + n : n; }
	function tick() {
		var now = new Date();
		var diff = Math.max(0, Math.floor((end - now) / 1000));
		var h = Math.floor(diff / 3600);
		var m = Math.floor((diff % 3600) / 60);
		var s = diff % 60;
		var eh = document.getElementById('timer-hours');
		var em = document.getElementById('timer-mins');
		var es = document.getElementById('timer-secs');
		if (eh) eh.textContent = pad(h);
		if (em) em.textContent = pad(m);
		if (es) es.textContent = pad(s);
		if (diff > 0) setTimeout(tick, 1000);
	}
	tick();
})();
</script>
<!-- /wp:html -->