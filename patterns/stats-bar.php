<?php
/**
 * Title: Stats Bar
 * Slug: spoke-theme/stats-bar
 * Categories: spoke-theme
 * Description: Key platform statistics displayed in a white card with 4 columns.
 */
?>
<!-- wp:html -->
<section class="bg-white px-6 lg:px-8 py-10" style="border-bottom:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
  <div class="max-w-[1280px] mx-auto">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0">

      <!-- Stat 1 -->
      <div class="flex flex-col items-center text-center px-4 py-4">
        <span class="font-medium text-[28px] lg:text-[32px]" style="color:var(--wp--preset--color--primary);">150+</span>
        <span class="font-medium text-[14px] lg:text-[16px] mt-1" style="color:#43474f;">Total Courses</span>
      </div>

      <!-- Stat 2 -->
      <div class="flex flex-col items-center text-center px-4 py-4 lg:border-l" style="border-color:#f1f5f9;">
        <span class="font-medium text-[28px] lg:text-[32px]" style="color:var(--wp--preset--color--primary);">10,000+</span>
        <span class="font-medium text-[14px] lg:text-[16px] mt-1" style="color:#43474f;">Students Enrolled</span>
      </div>

      <!-- Stat 3 -->
      <div class="flex flex-col items-center text-center px-4 py-4 lg:border-l border-t lg:border-t-0" style="border-color:#f1f5f9;">
        <span class="font-medium text-[28px] lg:text-[32px]" style="color:var(--wp--preset--color--primary);">8,500+</span>
        <span class="font-medium text-[14px] lg:text-[16px] mt-1" style="color:#43474f;">Certified Graduates</span>
      </div>

      <!-- Stat 4 -->
      <div class="flex flex-col items-center text-center px-4 py-4 lg:border-l border-t lg:border-t-0" style="border-color:#f1f5f9;">
        <div class="flex items-center gap-1">
          <span class="font-medium text-[28px] lg:text-[32px]" style="color:var(--wp--preset--color--primary);">4.9/5</span>
          <svg class="w-5 h-5 mt-1 flex-shrink-0" fill="#fdaf2e" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
        </div>
        <span class="font-medium text-[14px] lg:text-[16px] mt-1" style="color:#43474f;">Average Rating</span>
      </div>

    </div>
  </div>
</section>
<!-- /wp:html -->