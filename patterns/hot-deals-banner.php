<?php
/**
 * Title: Hot Deals Banner
 * Slug: spoke-theme/hot-deals-banner
 * Categories: spoke-theme
 * Description: Admin-curated hot deals course grid rendered via shortcode.
 *
 * @package SpokeTheme
 */
?>

<!-- wp:html -->
<section class="relative overflow-hidden px-6 lg:px-8 py-14 lg:py-16" style="background:linear-gradient(135deg,#1A3C6E 0%,#1A1A2E 100%);">

  <div class="absolute top-0 right-0 w-[480px] h-[480px] rounded-full pointer-events-none" style="background:rgba(255,255,255,0.04);transform:translate(33%,-33%);"></div>
  <div class="absolute bottom-0 left-0 w-[320px] h-[320px] rounded-full pointer-events-none" style="background:rgba(244,167,38,0.06);transform:translate(-33%,33%);"></div>

  <div class="relative max-w-[1280px] mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-10">

    <div class="flex flex-col gap-5 max-w-2xl">
      <span class="self-start inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.8px] px-4 py-1.5 rounded-full border" style="background:rgba(244,167,38,0.15);border-color:rgba(244,167,38,0.35);color:#F4A726;">
        🔥 Flash Sale — Limited Time Only
      </span>
      <div>
        <h2 class="font-bold leading-tight tracking-[-1.5px] mb-3" style="font-size:clamp(1.75rem,4vw,2.75rem);color:#ffffff;">
          Save Up to <span style="color:#F4A726;">70%</span> on Accredited UK Courses
        </h2>
        <p class="text-[16px] leading-relaxed m-0" style="color:rgba(255,255,255,0.65);">
          Hand-picked professional development programmes at prices that make career progression impossible to ignore. Sale ends soon.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-5 text-[13px] font-semibold" style="color:rgba(255,255,255,0.5);">
        <span>🔒 Stripe Secure</span>
        <span style="color:rgba(255,255,255,0.2);">·</span>
        <span>✓ CPD Accredited</span>
        <span style="color:rgba(255,255,255,0.2);">·</span>
        <span>↩ 30-Day Guarantee</span>
      </div>
    </div>

    <div class="flex flex-col items-start lg:items-end gap-4 shrink-0">
      <a href="/hot-deals/"
         class="inline-flex items-center gap-2 font-bold text-[17px] px-8 py-4 rounded-xl transition-all hover:brightness-110 hover:-translate-y-0.5 whitespace-nowrap"
         style="background:#F4A726;color:#6b4500;box-shadow:0 8px 24px rgba(244,167,38,0.3);">
        See All Hot Deals
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/>
        </svg>
      </a>
      <p class="text-[13px] m-0" style="color:rgba(255,255,255,0.4);">No code needed — discounts applied automatically</p>
    </div>

  </div>
</section>
<!-- /wp:html -->