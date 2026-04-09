<?php
/**
 * SPOKE COURSE ARCHIVE — inc/functions-archive-addon.php
 *
 * Fixes:
 *  1. Forces /courses/ (Tutor LMS post-type archive) to render through
 *     the FSE block template system (archive-courses.html) instead of
 *     falling back to the classic header.php / footer.php.
 *  2. Provides [spoke_course_archive] shortcode used inside that template.
 *  3. Queries BOTH Tutor LMS courses + linked WooCommerce products so
 *     "Add to Cart" works correctly.
 *  4. Registers the Hot Deals admin menu for curating up to 16 courses.
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. FORCE /courses/ TO USE THE FSE BLOCK TEMPLATE SYSTEM
//
// Tutor LMS registers the "courses" custom post type and WordPress
// normally handles its archive via template_include. Because this is
// a block (FSE) theme, WordPress *should* pick up
// templates/archive-courses.html automatically — but Tutor LMS hooks
// into template_include at priority 99 and overrides it with its own
// classic template, producing the deprecated header.php warning.
//
// We hook in at priority 100 (after Tutor) and, when on the courses
// archive, tell WordPress to use the FSE block template loader instead.
// ─────────────────────────────────────────────────────────────────

add_filter( 'template_include', function ( string $template ): string {

    // Only act on the Tutor LMS course post-type archive.
    if ( ! is_post_type_archive( 'courses' ) ) {
        return $template;
    }

    // Ask WordPress's block-template resolver for the correct FSE template.
    // _resolve_template_for_new_post() is internal; we use the public API instead.
    // get_block_template() looks up templates/archive-courses.html in the theme.
    $block_template = get_block_template(
        get_stylesheet() . '//archive-courses',
        'wp_template'
    );

    if ( $block_template && ! empty( $block_template->content ) ) {
        // Use WordPress's own block-template canvas file so all FSE
        // machinery (header/footer template parts, global styles, etc.) fires.
        return ABSPATH . WPINC . '/template-canvas.php';
    }

    // Fallback: if the template file doesn't exist yet, return what we had.
    return $template;
}, 100 );

// Tell WordPress which block template to render when the canvas loads.
// This mirrors what WordPress core does internally for FSE pages.
add_filter( 'block_template_hierarchy', function ( array $templates ): array {
    if ( is_post_type_archive( 'courses' ) ) {
        // Prepend our specific template so it wins over any generic fallback.
        array_unshift( $templates, 'archive-courses' );
    }
    return $templates;
} );


// ─────────────────────────────────────────────────────────────────
// 2. ARCHIVE-COURSES TEMPLATE CONTENT
//
// The template file (templates/archive-courses.html) contains just:
//   <!-- wp:template-part {"slug":"header"} /-->
//   <!-- wp:html --> <?php spoke_render_course_archive();  <!-- /wp:html -->
//   <!-- wp:template-part {"slug":"footer"} /-->
//
// But because FSE templates are HTML files (not PHP), we render the
// archive body via a [spoke_course_archive] shortcode block instead.
// ─────────────────────────────────────────────────────────────────

add_shortcode( 'spoke_course_archive', function (): string {
    ob_start();
    spoke_render_course_archive();
    return ob_get_clean();
} );

// Prevent wpautop from mangling the shortcode's block-level HTML.
// We do this cleanly by removing it only for the FSE shortcode block.
add_filter( 'render_block', function ( string $block_content, array $block ): string {
    if (
        isset( $block['blockName'] ) &&
        $block['blockName'] === 'core/shortcode' &&
        ! empty( $block['innerHTML'] ) &&
        false !== strpos( $block['innerHTML'], 'spoke_course_archive' )
    ) {
        // Strip the damage wpautop already did before we could stop it.
        $block_content = preg_replace( '/<p>\s*<\/p>/i', '', $block_content );
        $block_content = preg_replace( '/<br\s*\/?>/i', '', $block_content );
    }
    return $block_content;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 3. ENQUEUE ARCHIVE STYLES (only on the courses archive / courses page)
// ─────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function (): void {
    if ( ! is_post_type_archive( 'courses' ) && ! is_page( 'courses' ) ) {
        return;
    }

    $css = '
        /* Category filter pills */
        .spoke-cat-pill{background:transparent;border:1px solid rgba(0,0,0,0.12);color:#43474f;cursor:pointer;transition:all 0.15s ease;}
        .spoke-cat-pill:hover{background:#f3f4f5;border-color:#1A3C6E;color:#1A3C6E;}
        .spoke-cat-pill.active{background:#1A3C6E!important;border-color:#1A3C6E!important;color:#fff!important;}
        /* Pagination */
        nav[aria-label="Course pages"] .page-numbers{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;font-size:13px;font-weight:600;color:#43474f;border:1px solid rgba(0,0,0,0.1);text-decoration:none;margin:0 2px;transition:background 200ms,color 200ms;}
        nav[aria-label="Course pages"] .page-numbers.current,nav[aria-label="Course pages"] .page-numbers:hover{background:#1A3C6E;border-color:#1A3C6E;color:#fff;}
        nav[aria-label="Course pages"] ul{display:flex;align-items:center;list-style:none;margin:0;padding:0;}
        nav[aria-label="Course pages"] ul li{display:inline-flex;}
        /* List-view toggle */
        #spoke-course-grid.is-list-view{grid-template-columns:1fr!important;}
        #spoke-course-grid.is-list-view .spoke-course-card{flex-direction:row;}
        #spoke-course-grid.is-list-view .spoke-card-thumb{width:220px;height:auto!important;flex-shrink:0;}
        @media(max-width:640px){
            #spoke-course-grid.is-list-view .spoke-course-card{flex-direction:column;}
            #spoke-course-grid.is-list-view .spoke-card-thumb{width:100%;height:170px!important;}
        }
        /* Disabled add-to-cart */
        .spoke-add-to-cart-btn:disabled{opacity:0.6;cursor:not-allowed;}
    ';

    wp_add_inline_style( 'spoke-global', $css );
} );


// ─────────────────────────────────────────────────────────────────
// 4. HOT DEALS ADMIN MENU
//    Lets admins curate up to 16 courses shown on the Hot Deals page.
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
    add_menu_page(
        __( 'Hot Deals', 'spoke-theme' ),
        __( 'Hot Deals', 'spoke-theme' ),
        'manage_options',
        'spoke-hot-deals',
        'spoke_hot_deals_admin_page',
        'dashicons-tag',
        58
    );
} );

function spoke_hot_deals_admin_page(): void {

    // Handle save.
    if (
        isset( $_POST['spoke_hot_deals_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spoke_hot_deals_nonce'] ) ), 'spoke_save_hot_deals' )
    ) {
        $ids = [];
        if ( ! empty( $_POST['hot_deal_ids'] ) ) {
            foreach ( explode( ',', sanitize_text_field( wp_unslash( $_POST['hot_deal_ids'] ) ) ) as $id ) {
                $id = (int) trim( $id );
                if ( $id > 0 ) {
                    $ids[] = $id;
                }
            }
        }
        update_option( 'spoke_hot_deal_ids', array_unique( array_slice( $ids, 0, 16 ) ) );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Hot Deals saved!', 'spoke-theme' ) . '</p></div>';
    }

    $saved_ids   = (array) get_option( 'spoke_hot_deal_ids', [] );
    $all_courses = get_posts( [
        'post_type'      => [ 'courses', 'product' ],
        'posts_per_page' => 200,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Hot Deals — Course Curation', 'spoke-theme' ); ?></h1>
        <p class="description"><?php esc_html_e( 'Select up to 16 courses to feature on the Hot Deals page. Drag to reorder.', 'spoke-theme' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'spoke_save_hot_deals', 'spoke_hot_deals_nonce' ); ?>
            <input type="hidden" name="hot_deal_ids" id="hot-deal-ids-input"
                   value="<?php echo esc_attr( implode( ',', $saved_ids ) ); ?>">

            <div style="display:flex;gap:24px;margin-top:20px;flex-wrap:wrap;">

                <!-- All courses (left panel) -->
                <div style="flex:1;min-width:280px;">
                    <h3><?php esc_html_e( 'All Courses / Products', 'spoke-theme' ); ?></h3>
                    <input type="text" id="spoke-search-products"
                           placeholder="<?php esc_attr_e( 'Search…', 'spoke-theme' ); ?>"
                           style="width:100%;margin-bottom:8px;padding:6px 10px;border:1px solid #ddd;border-radius:4px;">
                    <div id="spoke-all-courses"
                         style="border:1px solid #ddd;border-radius:6px;height:400px;overflow-y:auto;background:#fff;padding:4px;">
                        <?php foreach ( $all_courses as $p ) :
                            $already = in_array( $p->ID, $saved_ids, true );
                        ?>
                        <div class="spoke-course-row"
                             data-id="<?php echo esc_attr( $p->ID ); ?>"
                             data-title="<?php echo esc_attr( strtolower( $p->post_title ) ); ?>"
                             style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:4px;margin-bottom:2px;background:<?php echo $already ? '#e8f4ec' : '#f9f9f9'; ?>;<?php echo $already ? 'opacity:0.5;' : ''; ?>">
                            <span style="font-size:13px;">
                                <?php echo esc_html( $p->post_title ); ?>
                                <span style="color:#999;font-size:11px;">(#<?php echo esc_html( $p->ID ); ?> — <?php echo esc_html( $p->post_type ); ?>)</span>
                            </span>
                            <button type="button" class="spoke-add-deal button button-small"
                                    data-id="<?php echo esc_attr( $p->ID ); ?>"
                                    data-title="<?php echo esc_attr( $p->post_title ); ?>"
                                    <?php echo $already ? 'disabled' : ''; ?>>
                                <?php echo $already ? esc_html__( 'Added', 'spoke-theme' ) : esc_html__( '+ Add', 'spoke-theme' ); ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Hot Deals list (right panel) -->
                <div style="flex:1;min-width:280px;">
                    <h3>
                        <?php esc_html_e( 'Hot Deals List', 'spoke-theme' ); ?>
                        <span id="deal-count" style="font-size:13px;color:#666;">(<?php echo count( $saved_ids ); ?>/16)</span>
                    </h3>
                    <ul id="spoke-deal-list"
                        style="border:1px solid #ddd;border-radius:6px;min-height:400px;background:#fff;padding:8px;list-style:none;margin:0;">
                        <?php foreach ( $saved_ids as $sid ) :
                            $p = get_post( $sid );
                            if ( ! $p ) { continue; }
                        ?>
                        <li class="spoke-deal-item" data-id="<?php echo esc_attr( $sid ); ?>"
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:4px;margin-bottom:4px;background:#f3f4f5;cursor:grab;border:1px solid #e0e0e0;">
                            <span style="font-size:13px;">≡ <?php echo esc_html( $p->post_title ); ?></span>
                            <button type="button" class="spoke-remove-deal button button-small button-link-delete"
                                    data-id="<?php echo esc_attr( $sid ); ?>">✕</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top:8px;font-size:12px;color:#666;"><?php esc_html_e( 'Drag items to reorder. Max 16.', 'spoke-theme' ); ?></p>
                </div>

            </div>

            <p class="submit">
                <input type="submit" class="button button-primary"
                       value="<?php esc_attr_e( 'Save Hot Deals', 'spoke-theme' ); ?>">
            </p>
        </form>
    </div>

    <script>
    (function () {
        var ids   = <?php echo wp_json_encode( $saved_ids ); ?>;
        var input = document.getElementById('hot-deal-ids-input');
        var list  = document.getElementById('spoke-deal-list');
        var count = document.getElementById('deal-count');

        function updateInput() {
            input.value = ids.join(',');
            count.textContent = '(' + ids.length + '/16)';
        }

        // Add button
        document.querySelectorAll('.spoke-add-deal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (ids.length >= 16) { alert('Maximum 16 courses.'); return; }
                var id    = parseInt(this.dataset.id);
                var title = this.dataset.title;
                if (ids.indexOf(id) !== -1) return;
                ids.push(id);
                updateInput();
                var li = document.createElement('li');
                li.className   = 'spoke-deal-item';
                li.dataset.id  = id;
                li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:4px;margin-bottom:4px;background:#f3f4f5;cursor:grab;border:1px solid #e0e0e0;';
                li.innerHTML   = '<span style="font-size:13px;">≡ ' + title + '</span>'
                               + '<button type="button" class="spoke-remove-deal button button-small button-link-delete" data-id="' + id + '">✕</button>';
                list.appendChild(li);
                bindRemove(li.querySelector('.spoke-remove-deal'));
                this.textContent = 'Added';
                this.disabled    = true;
                var row = this.closest('.spoke-course-row');
                if (row) { row.style.opacity = '0.5'; row.style.background = '#e8f4ec'; }
            });
        });

        function bindRemove(btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                ids = ids.filter(function (x) { return x !== id; });
                updateInput();
                this.closest('li').remove();
                var addBtn = document.querySelector('.spoke-add-deal[data-id="' + id + '"]');
                if (addBtn) {
                    addBtn.textContent = '+ Add';
                    addBtn.disabled    = false;
                    var row = addBtn.closest('.spoke-course-row');
                    if (row) { row.style.opacity = '1'; row.style.background = '#f9f9f9'; }
                }
            });
        }
        document.querySelectorAll('.spoke-remove-deal').forEach(bindRemove);

        // Search
        document.getElementById('spoke-search-products').addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.spoke-course-row').forEach(function (r) {
                r.style.display = r.dataset.title.includes(q) ? '' : 'none';
            });
        });

        // Drag-to-reorder
        var dragSrc = null;
        function makeDraggable(el) {
            el.draggable = true;
            el.addEventListener('dragstart', function () { dragSrc = this; this.style.opacity = '0.4'; });
            el.addEventListener('dragend',   function () { this.style.opacity = ''; });
            el.addEventListener('dragover',  function (e) { e.preventDefault(); });
            el.addEventListener('drop', function (e) {
                e.preventDefault();
                if (dragSrc !== this) {
                    var items  = [...list.querySelectorAll('.spoke-deal-item')];
                    var si = items.indexOf(dragSrc), di = items.indexOf(this);
                    si < di ? list.insertBefore(dragSrc, this.nextSibling) : list.insertBefore(dragSrc, this);
                    ids = [...list.querySelectorAll('.spoke-deal-item')].map(function (li) { return parseInt(li.dataset.id); });
                    updateInput();
                }
            });
        }
        document.querySelectorAll('.spoke-deal-item').forEach(makeDraggable);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.classList && n.classList.contains('spoke-deal-item')) makeDraggable(n);
                });
            });
        }).observe(list, { childList: true });
    })();
    </script>
    <?php
}


// ─────────────────────────────────────────────────────────────────
// 5. HELPER: GET HOT DEAL IDS
// ─────────────────────────────────────────────────────────────────

function spoke_get_hot_deal_ids(): array {
    return array_map( 'intval', (array) get_option( 'spoke_hot_deal_ids', [] ) );
}


// ─────────────────────────────────────────────────────────────────
// 6. ARCHIVE RENDER FUNCTION
//
// Queries BOTH Tutor LMS 'courses' AND WooCommerce 'product' post types.
// For each course, it finds the linked WooCommerce product so that
// "Add to Cart" works correctly.
//
// Tutor LMS links a course to a WooCommerce product via the meta key
// _tutor_course_product_id stored on the course post.
// ─────────────────────────────────────────────────────────────────

function spoke_render_course_archive(): void {

    // Pagination: support both WP's 'paged' query var and our ?cpage= fallback.
    $paged = max( 1, (int) get_query_var( 'paged' ) ?: ( isset( $_GET['cpage'] ) ? (int) $_GET['cpage'] : 1 ) );

    // Build query — always use 'courses' post type so Tutor LMS data is available.
    // If a course has no linked WooCommerce product we fall back to the course URL.
    $loop = new WP_Query( [
        'post_type'      => 'courses',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    // Fetch category terms for the filter pills.
    $course_cats = get_terms( [ 'taxonomy' => 'course-category', 'hide_empty' => true, 'number' => 10 ] );
    if ( is_wp_error( $course_cats ) ) {
        $course_cats = [];
    }

    // Helper: build 5-star SVG row.
    $build_stars = function ( float $avg ): string {
        $out = '';
        for ( $i = 1; $i <= 5; $i++ ) {
            $color = $i <= round( $avg ) ? '#F4A726' : '#d1d5db';
            $out  .= '<svg class="w-3 h-3" fill="' . $color . '" viewBox="0 0 20 20">'
                   . '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>'
                   . '</svg>';
        }
        return $out;
    };

    ?>
    <div class="spoke-archive-wrap" style="background:#f8f9fa;min-height:100vh;font-family:'Inter',sans-serif;">

        <!-- ═══ PAGE HEADER ═══════════════════════════════════════════ -->
        <div style="background:#f8f9fa;" class="pt-12 pb-8 px-6 lg:px-8">
            <div class="max-w-[1280px] mx-auto">
                <nav class="flex items-center gap-2 text-[14px] mb-6" aria-label="Breadcrumb">
                    <a href="/" class="text-[#43474f] font-medium hover:text-[#1A3C6E] transition-colors no-underline">Home</a>
                    <span class="text-[#c4c6d0]">/</span>
                    <span class="font-semibold" style="color:#1A3C6E;">Courses</span>
                </nav>
                <h1 class="font-bold leading-tight tracking-[-2px] max-w-[660px] m-0"
                    style="color:#1A3C6E;font-size:clamp(1.75rem,4vw,3rem);">
                    Browse Accredited Courses for Professionals
                </h1>
                <p class="text-[17px] mt-3 mb-0" style="color:#43474f;">
                    150+ UK-accredited programmes for working professionals.
                </p>
            </div>
        </div>

        <!-- ═══ STICKY FILTER BAR ═════════════════════════════════════ -->
        <div class="sticky top-[80px] z-40 border-y"
             style="background:rgba(243,244,245,0.97);backdrop-filter:blur(8px);border-color:rgba(196,198,208,0.25);">
            <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">

                <!-- Search -->
                <div class="relative w-full sm:w-[360px]">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                         style="color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="spoke-course-search" placeholder="Search courses…"
                           class="w-full bg-white border border-[#d1d5db] rounded-lg pl-10 pr-4 py-2.5 text-[14px] text-[#191c1d] placeholder-[#6b7280] focus:outline-none focus:ring-2 focus:border-[#1A3C6E] transition"
                           style="focus-ring-color:rgba(26,60,110,0.3);">
                </div>

                <!-- Category pills -->
                <div class="flex gap-2 overflow-x-auto pb-0.5 shrink-0"
                     id="spoke-cat-pills" role="group" aria-label="Filter by category">
                    <button class="spoke-cat-pill active shrink-0 h-9 px-4 rounded-lg text-[13px] font-semibold"
                            data-cat="all">All Courses</button>
                    <?php foreach ( $course_cats as $cat ) : ?>
                        <button class="spoke-cat-pill shrink-0 h-9 px-4 rounded-lg text-[13px] font-medium"
                                data-cat="<?php echo esc_attr( $cat->slug ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Sort -->
                <div class="relative shrink-0">
                    <select id="spoke-sort"
                            class="appearance-none bg-white border rounded-lg pl-4 pr-9 py-2.5 text-[13px] font-medium text-[#43474f] min-w-[160px] cursor-pointer focus:outline-none"
                            style="border-color:rgba(196,198,208,0.5);">
                        <option value="newest">Newest</option>
                        <option value="rating">Highest Rated</option>
                        <option value="price-asc">Price: Low → High</option>
                        <option value="price-desc">Price: High → Low</option>
                    </select>
                    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                         style="color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

            </div>
        </div>

        <!-- ═══ MAIN: SIDEBAR + GRID ══════════════════════════════════ -->
        <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-10 flex gap-10 items-start">

            <!-- ── SIDEBAR ── -->
            <aside class="hidden lg:flex flex-col gap-8 w-[240px] shrink-0 sticky top-[148px]">

                <!-- Trust badges -->
                <div class="flex flex-col gap-2.5 pb-6 border-b" style="border-color:rgba(196,198,208,0.3);">
                    <?php
                    $badges = [
                        [ 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'STRIPE SECURE' ],
                        [ 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', '30-DAY REFUND' ],
                        [ 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'CPD ACCREDITED' ],
                    ];
                     foreach ( $badges as $badge ) {
                        $path  = $badge[0];
                        $label = $badge[1];
                    ?>
                    <div class="flex items-center gap-2 text-[12px] font-bold" style="color:#43474f;">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:#1A3C6E;" fill="none" stroke="currentColor"
                             stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr( $path ); ?>"/>
                        </svg>
                        <span><?php echo esc_html( $label ); ?></span>
                    </div>
                    <?php } ?>
                </div>

                <!-- Difficulty filter -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-[11px] font-bold uppercase tracking-[1.2px] m-0" style="color:#43474f;">Difficulty Level</h3>
                    <div class="flex flex-col gap-3">
                        <?php foreach ( [ 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced' ] as $val => $label ) : ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" value="<?php echo esc_attr( $val ); ?>"
                                   class="spoke-level-check w-4 h-4 rounded" style="accent-color:#1A3C6E;">
                            <span class="text-[14px] group-hover:text-[#1A3C6E] transition-colors"
                                  style="color:#191c1d;"><?php echo esc_html( $label ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price filter -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-[11px] font-bold uppercase tracking-[1.2px] m-0" style="color:#43474f;">Price Range</h3>
                    <div class="flex flex-col gap-3">
                        <?php foreach ( [ 'free' => 'Free', 'under100' => 'Under £100', '100-500' => '£100 – £500', '500plus' => '£500+' ] as $val => $label ) : ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" name="spoke-price" value="<?php echo esc_attr( $val ); ?>"
                                   class="spoke-price-check w-4 h-4" style="accent-color:#1A3C6E;">
                            <span class="text-[14px] group-hover:text-[#1A3C6E] transition-colors"
                                  style="color:#191c1d;"><?php echo esc_html( $label ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </aside><!-- /sidebar -->

            <!-- ── COURSE GRID COLUMN ── -->
            <div class="flex-1 min-w-0 flex flex-col gap-8">

                <!-- Toolbar -->
                <div class="flex items-center justify-between gap-4">
                    <p class="text-[14px] m-0" style="color:#43474f;" id="spoke-course-count">
                        <strong style="color:#1A3C6E;"><?php echo (int) $loop->found_posts; ?></strong> courses found
                    </p>
                    <div class="flex gap-1.5">
                        <button id="spoke-view-grid"
                                class="w-9 h-9 flex items-center justify-center rounded-lg border"
                                style="background:#1A3C6E;color:#fff;border-color:rgba(196,198,208,0.4);"
                                aria-label="Grid view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                        </button>
                        <button id="spoke-view-list"
                                class="w-9 h-9 flex items-center justify-center rounded-lg border"
                                style="background:#fff;color:#43474f;border-color:rgba(196,198,208,0.4);"
                                aria-label="List view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Cards grid -->
                <div id="spoke-course-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

                    <?php if ( $loop->have_posts() ) : while ( $loop->have_posts() ) : $loop->the_post();
                        $course_id = get_the_ID();

                        // Get Tutor LMS data.
                        $rating_obj = function_exists( 'tutor_utils' ) ? tutor_utils()->get_course_rating( $course_id ) : null;
                        $rating_avg = (float) ( $rating_obj->rating_avg   ?? 0 );
                        $rating_cnt = (int)   ( $rating_obj->rating_count ?? 0 );
                        $students   = function_exists( 'tutor_utils' ) ? (int) tutor_utils()->count_enrolled_users_by_course( $course_id ) : 0;

                        // Find the linked WooCommerce product.
                        $wc_product_id = (int) get_post_meta( $course_id, '_tutor_course_product_id', true );
                        $wc_product    = $wc_product_id && function_exists( 'wc_get_product' )
                                         ? wc_get_product( $wc_product_id )
                                         : null;

                        if ( $wc_product ) {
                            $price         = (float) $wc_product->get_regular_price();
                            $sale_price    = (float) $wc_product->get_sale_price();
                            $add_to_cart_url = $wc_product->add_to_cart_url();
                            $can_add       = $wc_product->is_purchasable() && $wc_product->is_in_stock();
                        } else {
                            // No linked product — use Tutor's own price meta as display-only.
                            $raw           = function_exists( 'tutor_utils' ) ? tutor_utils()->get_raw_course_price( $course_id ) : 0;
                            $price         = is_object( $raw ) ? (float) ( $raw->regular_price ?? 0 ) : (float) $raw;
                            $sale_price    = (float) get_post_meta( $course_id, '_sale_price', true );
                            $add_to_cart_url = get_permalink();
                            $can_add       = false; // No WC product to add to cart.
                        }

                        $effective_price = $sale_price > 0 ? $sale_price : $price;
                        $thumb_url       = get_the_post_thumbnail_url( $course_id, 'medium_large' );

                        // Category.
                        $c_cats   = get_the_terms( $course_id, 'course-category' );
                        $cat_name = ( $c_cats && ! is_wp_error( $c_cats ) ) ? $c_cats[0]->name : '';
                        $cat_slug = ( $c_cats && ! is_wp_error( $c_cats ) ) ? $c_cats[0]->slug : '';

                        // Discount badge.
                        $discount_pct = ( $sale_price > 0 && $price > 0 ) ? round( ( 1 - $sale_price / $price ) * 100 ) : 0;
                    ?>
                    <article class="spoke-course-card bg-white rounded-xl overflow-hidden flex flex-col"
                             style="box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:box-shadow 0.2s ease,transform 0.2s ease;"
                             data-cat="<?php echo esc_attr( $cat_slug ); ?>"
                             data-title="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>"
                             data-price="<?php echo esc_attr( $effective_price ); ?>"
                             onmouseenter="this.style.boxShadow='0 8px 32px rgba(26,60,110,0.12)';this.style.transform='translateY(-3px)';"
                             onmouseleave="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';this.style.transform='';">

                        <!-- Thumbnail -->
                        <div class="spoke-card-thumb relative overflow-hidden" style="height:170px;">
                            <a href="<?php the_permalink(); ?>" class="block w-full h-full" tabindex="-1" aria-hidden="true">
                                <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                                     class="w-full h-full object-cover"
                                     style="transition:transform 0.5s ease;"
                                     onmouseenter="this.style.transform='scale(1.05)';"
                                     onmouseleave="this.style.transform='';"
                                     loading="lazy" width="400" height="170">
                                <?php else : ?>
                                <div class="w-full h-full flex items-center justify-center"
                                     style="background:linear-gradient(135deg,#1A3C6E,#1A1A2E);">
                                    <svg class="w-14 h-14 opacity-20" fill="white" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </a>
                            <?php if ( $cat_name ) : ?>
                            <span class="absolute top-3 left-3 text-[10px] font-bold uppercase tracking-[0.5px] px-2.5 py-1 rounded"
                                  style="background:#F4A726;color:#6b4500;">
                                <?php echo esc_html( $cat_name ); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ( $discount_pct > 0 ) : ?>
                            <span class="absolute bottom-3 right-3 text-[10px] font-bold uppercase px-2 py-1 rounded"
                                  style="background:#BA1A1A;color:#fff;">
                                <?php echo (int) $discount_pct; ?>% OFF
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card body -->
                        <div class="p-5 flex flex-col gap-2 flex-1">
                            <h3 class="font-bold text-[16px] leading-snug m-0" style="color:#1A3C6E;">
                                <a href="<?php the_permalink(); ?>"
                                   class="no-underline hover:underline" style="color:inherit;">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <!-- Stars + count -->
                            <div class="flex items-center gap-1.5">
                                <div class="flex gap-0.5"><?php echo $build_stars( $rating_avg ); ?></div>
                                <span class="font-bold text-[12px]" style="color:#1A3C6E;">
                                    <?php echo number_format( $rating_avg, 1 ); ?>
                                </span>
                                <?php if ( $rating_cnt > 0 ) : ?>
                                <span class="text-[12px]" style="color:#43474f;">
                                    (<?php echo number_format( $rating_cnt ); ?>)
                                </span>
                                <?php endif; ?>
                                <?php if ( $students > 0 ) : ?>
                                <span class="text-[12px] ml-auto" style="color:#43474f;">
                                    <?php echo number_format( $students ); ?> students
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Price + CTA -->
                            <div class="flex items-center justify-between pt-3 mt-auto"
                                 style="border-top:1px solid rgba(0,0,0,0.07);">
                                <div class="flex items-baseline gap-2">
                                    <?php if ( $effective_price > 0 ) : ?>
                                    <span class="font-black text-[20px]" style="color:#1A3C6E;">
                                        £<?php echo number_format( $effective_price, 2 ); ?>
                                    </span>
                                    <?php if ( $discount_pct > 0 ) : ?>
                                    <span class="text-[14px] line-through" style="color:#43474f;">
                                        £<?php echo number_format( $price, 2 ); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php else : ?>
                                    <span class="font-black text-[20px]" style="color:#1A3C6E;">Free</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ( $can_add && $wc_product ) : ?>
                                <a href="<?php echo esc_url( $add_to_cart_url ); ?>"
                                   data-quantity="1"
                                   data-product_id="<?php echo esc_attr( $wc_product->get_id() ); ?>"
                                   data-product_sku="<?php echo esc_attr( $wc_product->get_sku() ); ?>"
                                   class="ajax_add_to_cart add_to_cart_button spoke-add-to-cart-btn inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline"
                                   style="background:#F4A726;color:#6b4500;transition:filter 0.15s ease;"
                                   onmouseenter="this.style.filter='brightness(1.07)';"
                                   onmouseleave="this.style.filter='';"
                                   rel="nofollow">
                                    Add to Cart
                                </a>
                                <?php else : ?>
                                <a href="<?php the_permalink(); ?>"
                                   class="inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline"
                                   style="background:#1A3C6E;color:#fff;transition:filter 0.15s ease;"
                                   onmouseenter="this.style.filter='brightness(1.15)';"
                                   onmouseleave="this.style.filter='';">
                                    View Course
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </article>
                    <?php endwhile; wp_reset_postdata();
                    else : ?>
                    <div class="col-span-full text-center py-20">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-20" fill="none" stroke="#1A3C6E"
                             stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0118 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                        <h2 class="font-bold text-[22px] mb-2" style="color:#1A3C6E;">No courses yet</h2>
                        <p class="text-[15px]" style="color:#43474f;">
                            Add your first course via Tutor LMS → Courses.
                        </p>
                    </div>
                    <?php endif; ?>

                </div><!-- /grid -->

                <!-- No-results message (JS-controlled) -->
                <div id="spoke-no-results" class="hidden text-center py-16" style="color:#43474f;">
                    <p class="text-[18px] font-medium">No courses match your search.</p>
                    <p class="text-[14px] mt-1">Try a different keyword or clear the filters.</p>
                </div>

                <!-- Pagination -->
                <?php if ( $loop->max_num_pages > 1 ) : ?>
                <nav class="flex justify-center mt-4" aria-label="Course pages">
                    <?php echo paginate_links( [
                        'total'     => $loop->max_num_pages,
                        'current'   => $paged,
                        'type'      => 'list',
                        'prev_text' => '← Prev',
                        'next_text' => 'Next →',
                        'add_args'  => [ 'cpage' => '%#%' ],
                        'base'      => is_post_type_archive( 'courses' )
                                       ? trailingslashit( get_post_type_archive_link( 'courses' ) ) . '%_%'
                                       : '',
                    ] ); ?>
                </nav>
                <?php endif; ?>

            </div><!-- /grid column -->

        </div><!-- /main flex wrapper -->

    </div><!-- /archive wrap -->

    <script>
    (function () {
        'use strict';
        var cards      = Array.from(document.querySelectorAll('.spoke-course-card'));
        var searchEl   = document.getElementById('spoke-course-search');
        var pillsEl    = document.getElementById('spoke-cat-pills');
        var noResultEl = document.getElementById('spoke-no-results');
        var countEl    = document.getElementById('spoke-course-count');
        var gridEl     = document.getElementById('spoke-course-grid');
        var btnGrid    = document.getElementById('spoke-view-grid');
        var btnList    = document.getElementById('spoke-view-list');
        var sortEl     = document.getElementById('spoke-sort');

        var activeCat   = 'all';
        var searchQuery = '';
        var activePrice = '';

        function applyFilters() {
            var visible = 0;
            cards.forEach(function (card) {
                var title = card.dataset.title || '';
                var cat   = card.dataset.cat   || '';
                var price = parseFloat(card.dataset.price) || 0;

                var matchQ = !searchQuery || title.includes(searchQuery);
                var matchC = activeCat === 'all' || cat === activeCat;
                var matchP = true;
                if (activePrice === 'free')     matchP = price === 0;
                if (activePrice === 'under100') matchP = price > 0 && price < 100;
                if (activePrice === '100-500')  matchP = price >= 100 && price <= 500;
                if (activePrice === '500plus')  matchP = price > 500;

                if (matchQ && matchC && matchP) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });
            if (noResultEl) noResultEl.classList.toggle('hidden', visible > 0);
            if (countEl)    countEl.innerHTML = '<strong style="color:#1A3C6E;">' + visible + '</strong> courses found';
        }

        // Search
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                searchQuery = this.value.toLowerCase().trim();
                applyFilters();
            });
        }

        // Category pills
        if (pillsEl) {
            pillsEl.addEventListener('click', function (e) {
                var btn = e.target.closest('.spoke-cat-pill');
                if (!btn) return;
                Array.from(pillsEl.querySelectorAll('.spoke-cat-pill')).forEach(function (p) {
                    p.classList.remove('active');
                });
                btn.classList.add('active');
                activeCat = btn.dataset.cat;
                applyFilters();
            });
        }

        // Price radio
        document.querySelectorAll('.spoke-price-check').forEach(function (r) {
            r.addEventListener('change', function () {
                activePrice = this.checked ? this.value : '';
                applyFilters();
            });
        });

        // Sort (client-side re-sort of visible cards)
        if (sortEl) {
            sortEl.addEventListener('change', function () {
                var mode  = this.value;
                var grid  = document.getElementById('spoke-course-grid');
                var items = Array.from(grid.querySelectorAll('.spoke-course-card'));
                items.sort(function (a, b) {
                    var pa = parseFloat(a.dataset.price) || 0;
                    var pb = parseFloat(b.dataset.price) || 0;
                    if (mode === 'price-asc')  return pa - pb;
                    if (mode === 'price-desc') return pb - pa;
                    return 0; // newest/rating: server-side order preserved
                });
                items.forEach(function (item) { grid.appendChild(item); });
            });
        }

        // Grid/List toggle
        function setView(mode) {
            if (mode === 'list') {
                gridEl.classList.add('is-list-view');
                btnList.style.background = '#1A3C6E'; btnList.style.color = '#fff';
                btnGrid.style.background = '#fff';    btnGrid.style.color = '#43474f';
            } else {
                gridEl.classList.remove('is-list-view');
                btnGrid.style.background = '#1A3C6E'; btnGrid.style.color = '#fff';
                btnList.style.background = '#fff';    btnList.style.color = '#43474f';
            }
        }
        if (btnGrid) btnGrid.addEventListener('click', function () { setView('grid'); });
        if (btnList) btnList.addEventListener('click', function () { setView('list'); });
    })();
    </script>
    <?php
}