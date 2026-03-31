<?php
/**
 * Title: Hot Deals Banner
 * Slug: spoke-theme/hot-deals-banner
 * Categories: spoke-theme
 * Description: Promotional amber banner with live countdown timer for limited-time offers.
 */
?>
<!-- wp:html -->
<section class="px-6 lg:px-8 py-8" style="background:#f8f9fa;">
  <div class="max-w-[1280px] mx-auto">
    <div class="relative overflow-hidden rounded-2xl px-8 sm:px-12 py-12 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8" style="background:#fdaf2e;">

      <!-- Decorative orbs -->
      <div class="absolute top-0 right-0 w-64 h-64 rounded-full pointer-events-none" style="background:rgba(255,255,255,0.12);transform:translate(50%,-50%);"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 rounded-full pointer-events-none" style="background:rgba(107,69,0,0.05);transform:translate(-50%,50%);"></div>

      <!-- Content -->
      <div class="relative z-10 max-w-xl">
        <span class="inline-block text-white text-[12px] font-bold px-3 py-1.5 rounded-md uppercase tracking-wide mb-4" style="background:#002653;">Limited Time Offer</span>
        <h2 class="font-black tracking-tight mt-0 mb-2 leading-tight" style="color:#291800;font-size:clamp(1.5rem,3vw,2.25rem);">
          15% Discount for new UK enrollments
        </h2>
        <p class="font-medium text-[16px] lg:text-[18px] mb-6" style="color:#6b4500;">Accelerate your professional journey today. Discount expires in:</p>

        <!-- Countdown timer -->
        <div class="flex gap-3" id="hot-deals-countdown">
          <div class="rounded-lg p-3 min-w-[66px] text-center" style="background:rgba(255,255,255,0.3);backdrop-filter:blur(4px);">
            <div id="hd-hours" class="font-bold text-[22px] lg:text-[24px] leading-none" style="color:#291800;">08</div>
            <div class="font-bold text-[10px] uppercase tracking-[0.5px] mt-1" style="color:#191c1d;">Hours</div>
          </div>
          <div class="rounded-lg p-3 min-w-[66px] text-center" style="background:rgba(255,255,255,0.3);backdrop-filter:blur(4px);">
            <div id="hd-mins" class="font-bold text-[22px] lg:text-[24px] leading-none" style="color:#291800;">42</div>
            <div class="font-bold text-[10px] uppercase tracking-[0.5px] mt-1" style="color:#191c1d;">Mins</div>
          </div>
          <div class="rounded-lg p-3 min-w-[66px] text-center" style="background:rgba(255,255,255,0.3);backdrop-filter:blur(4px);">
            <div id="hd-secs" class="font-bold text-[22px] lg:text-[24px] leading-none" style="color:#291800;">15</div>
            <div class="font-bold text-[10px] uppercase tracking-[0.5px] mt-1" style="color:#191c1d;">Secs</div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="relative z-10 flex-shrink-0">
        <a href="/hot-deals/"
           class="inline-block text-white font-bold text-[18px] lg:text-[20px] px-10 py-5 rounded-lg transition-all hover:brightness-110 whitespace-nowrap"
           style="background:#002653;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
          Claim Offer
        </a>
      </div>

    </div>
  </div>
</section>

<script>
(function () {
  var end = new Date();
  end.setHours(end.getHours() + 8, end.getMinutes() + 42, end.getSeconds() + 15);
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function tick() {
    var diff = Math.max(0, Math.floor((end - Date.now()) / 1000));
    var h = Math.floor(diff / 3600);
    var m = Math.floor((diff % 3600) / 60);
    var s = diff % 60;
    var eh = document.getElementById('hd-hours');
    var em = document.getElementById('hd-mins');
    var es = document.getElementById('hd-secs');
    if (eh) eh.textContent = pad(h);
    if (em) em.textContent = pad(m);
    if (es) es.textContent = pad(s);
    if (diff > 0) setTimeout(tick, 1000);
  }
  tick();
})();
</script>
<!-- /wp:html -->