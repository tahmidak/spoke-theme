<?php
/**
 * Title: Hero Banner
 * Slug: spoke-theme/hero-banner
 * Categories: spoke-theme
 * Description: Homepage hero section with headline, subtext, CTA buttons and floating progress card.
 */
?>
<!-- wp:html -->
<section class="relative overflow-hidden py-20 lg:py-32 px-6 lg:px-8" style="background: linear-gradient(142deg, var(--wp--preset--color--primary) 0%, #252539 100%);">

  <!-- Decorative orbs -->
  <div class="absolute top-0 right-0 w-[400px] h-[400px] rounded-full pointer-events-none" style="background:rgba(255,255,255,0.05);transform:translate(33%,-33%);"></div>
  <div class="absolute bottom-0 left-0 w-[200px] h-[200px] rounded-full pointer-events-none" style="background:rgba(255,255,255,0.05);transform:translate(-33%,33%);"></div>

  <div class="max-w-[1280px] mx-auto relative z-10">
    <div class="grid lg:grid-cols-2 gap-12 items-center">

      <!-- Left: Content -->
      <div class="flex flex-col gap-7">

        <!-- Trust badge -->
        <div class="inline-flex items-center gap-2 self-start rounded-full px-4 py-2" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);">
          <svg class="w-3 h-3 flex-shrink-0" fill="#fdaf2e" viewBox="0 0 16 16"><circle cx="8" cy="8" r="8"/></svg>
          <span class="text-white text-[13px] font-medium">10,000+ UK professionals enrolled</span>
        </div>

        <!-- Heading -->
        <h1 class="text-white font-medium leading-[1.05] tracking-[-1.5px]" style="font-size:clamp(2.25rem,5vw,3.5rem);">
          Upskill for Career Progression with Accredited Online Courses
        </h1>

        <!-- Subtext -->
        <p class="text-[18px] lg:text-[20px] leading-relaxed max-w-[500px]" style="color:#abc7ff;">
          Specialised e-learning platform for UK professionals. Elevate your expertise with industry-recognised certifications.
        </p>

        <!-- CTAs -->
        <div class="flex flex-wrap gap-4 pt-2">
          <a href="/courses/"
             class="inline-block font-bold text-[17px] px-8 py-4 rounded-lg transition-all hover:brightness-105 shadow-lg"
             style="background:#fdaf2e;color:#6b4500;">
            Browse Courses
          </a>
          <a href="/accreditation/"
             class="inline-block font-bold text-[17px] px-8 py-4 rounded-lg transition-all"
             style="border:2px solid rgba(255,255,255,0.25);color:#ffffff;background:transparent;"
             onmouseover="this.style.background='rgba(255,255,255,0.1)'"
             onmouseout="this.style.background='transparent'">
            View Accreditation
          </a>
        </div>

      </div>

      <!-- Right: Hero card -->
      <div class="relative hidden lg:flex justify-end">
        <div class="w-[480px] rotate-3">
          <div class="rounded-xl overflow-hidden" style="aspect-ratio:4/5;border:1px solid rgba(255,255,255,0.1);box-shadow:0 40px 80px rgba(0,0,0,0.35);">
            <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,#1a4a8e 0%,#0d2347 100%);">
              <svg class="w-28 h-28 opacity-20" fill="white" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
            </div>
          </div>
        </div>
        <!-- Floating info card -->
        <div class="absolute -bottom-6 -left-8 bg-white rounded-xl p-5 z-10" style="box-shadow:0 20px 50px rgba(0,0,0,0.2);max-width:220px;">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0" style="background:#d7e3ff;">
              <svg class="w-5 h-5" fill="none" stroke="var(--wp--preset--color--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div>
              <p class="font-bold text-[14px] leading-tight" style="color:var(--wp--preset--color--primary);">New Course</p>
              <p class="text-[12px]" style="color:#43474f;">Project Leadership</p>
            </div>
          </div>
          <div class="rounded-full h-2 overflow-hidden" style="background:#e7e8e9;">
            <div class="h-full rounded-full" style="width:75%;background:#fdaf2e;"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<!-- /wp:html -->