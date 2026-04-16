<?php
/**
 * SPOKE COURSE ARCHIVE — inc/functions-archive-addon.php
 *
 * Fixes:
 *  1. Forces /courses/ (Tutor LMS post-type archive) to render through
 *     the FSE block template system (archive-courses.html).
 *  2. Provides [spoke_course_archive] shortcode used inside that template.
 *  3. Queries BOTH Tutor LMS courses + linked WooCommerce products so
 *     "Add to Cart" works correctly.
 *  4. Registers the Hot Deals admin menu for curating up to 16 courses.
 *  5. CTA buttons have three states: Add to Cart / View Cart / Go to Dashboard.
 *  6. Grid hides itself when empty; no-results message shows instead.
 *  7. WooCommerce-appended "View cart" link is intercepted and replaced.
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. FORCE /courses/ TO USE THE FSE BLOCK TEMPLATE SYSTEM
// ─────────────────────────────────────────────────────────────────

add_filter( 'template_include', function ( string $template ): string {
    if ( ! is_post_type_archive( 'courses' ) ) {
        return $template;
    }
    $block_template = get_block_template(
        get_stylesheet() . '//archive-courses',
        'wp_template'
    );
    if ( $block_template && ! empty( $block_template->content ) ) {
        return ABSPATH . WPINC . '/template-canvas.php';
    }
    return $template;
}, 100 );

add_filter( 'block_template_hierarchy', function ( array $templates ): array {
    if ( is_post_type_archive( 'courses' ) ) {
        array_unshift( $templates, 'archive-courses' );
    }
    return $templates;
} );


// ─────────────────────────────────────────────────────────────────
// 2. SHORTCODE + BLOCK INTERCEPTOR
// ─────────────────────────────────────────────────────────────────

add_shortcode( 'spoke_course_archive', function (): string {
    ob_start();
    spoke_render_course_archive();
    $html = ob_get_clean();
    $html = preg_replace( '/<br\s*\/?>/i', '', $html );
    $html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
    return $html;
} );

add_filter( 'render_block', function ( string $block_content, array $block ): string {
    if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/html' ) {
        return $block_content;
    }
    $raw = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
    if ( false === strpos( $raw, 'spoke_course_archive' ) ) {
        return $block_content;
    }
    ob_start();
    spoke_render_course_archive();
    $html = ob_get_clean();
    $html = preg_replace( '/<br\s*\/?>/i', '', $html );
    $html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
    $html = preg_replace( '/(\s*\n){3,}/', "\n", $html );
    return $html;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 3. ENQUEUE ARCHIVE STYLES
// ─────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function (): void {
    if ( ! is_post_type_archive( 'courses' ) && ! is_page( 'courses' ) ) {
        return;
    }
    $css = '
        .spoke-cat-pill{background:transparent;border:1px solid rgba(0,0,0,0.12);color:#43474f;cursor:pointer;transition:all 0.15s ease;}
        .spoke-cat-pill:hover{background:#f3f4f5;border-color:#1A3C6E;color:#1A3C6E;}
        .spoke-cat-pill.active{background:#1A3C6E!important;border-color:#1A3C6E!important;color:#fff!important;}
        nav[aria-label="Course pages"] .page-numbers{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;font-size:13px;font-weight:600;color:#43474f;border:1px solid rgba(0,0,0,0.1);text-decoration:none;margin:0 2px;transition:background 200ms,color 200ms;}
        nav[aria-label="Course pages"] .page-numbers.current,nav[aria-label="Course pages"] .page-numbers:hover{background:#1A3C6E;border-color:#1A3C6E;color:#fff;}
        nav[aria-label="Course pages"] ul{display:flex;align-items:center;list-style:none;margin:0;padding:0;}
        nav[aria-label="Course pages"] ul li{display:inline-flex;}
        #spoke-course-grid.is-list-view{grid-template-columns:1fr!important;}
        #spoke-course-grid.is-list-view .spoke-course-card{flex-direction:row;}
        #spoke-course-grid.is-list-view .spoke-card-thumb{width:220px;height:auto!important;flex-shrink:0;}
        @media(max-width:640px){
            #spoke-course-grid.is-list-view .spoke-course-card{flex-direction:column;}
            #spoke-course-grid.is-list-view .spoke-card-thumb{width:100%;height:170px!important;}
        }
        .spoke-cta-wrap{display:flex;gap:8px;align-items:center;flex-wrap:nowrap;}
        .spoke-add-to-cart-btn:disabled{opacity:0.6;cursor:not-allowed;}
        .spoke-view-cart-btn{display:none;}
        .spoke-view-cart-btn.visible{display:inline-flex!important;}
        /* Kill WC appended link inside our cards */
        .spoke-course-card .added_to_cart.wc-forward{display:none!important;}
    ';
    wp_add_inline_style( 'spoke-global', $css );
} );


// ─────────────────────────────────────────────────────────────────
// 4. HOT DEALS ADMIN MENU
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
        <p class="description"><?php esc_html_e( 'Select up to 16 courses to feature on the Hot Deals page.', 'spoke-theme' ); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field( 'spoke_save_hot_deals', 'spoke_hot_deals_nonce' ); ?>
            <input type="hidden" name="hot_deal_ids" id="hot-deal-ids-input"
                   value="<?php echo esc_attr( implode( ',', $saved_ids ) ); ?>">
            <div style="display:flex;gap:24px;margin-top:20px;flex-wrap:wrap;">
                <div style="flex:1;min-width:280px;">
                    <h3><?php esc_html_e( 'All Courses / Products', 'spoke-theme' ); ?></h3>
                    <input type="text" id="spoke-search-products" placeholder="<?php esc_attr_e( 'Search…', 'spoke-theme' ); ?>"
                           style="width:100%;margin-bottom:8px;padding:6px 10px;border:1px solid #ddd;border-radius:4px;">
                    <div id="spoke-all-courses" style="border:1px solid #ddd;border-radius:6px;height:400px;overflow-y:auto;background:#fff;padding:4px;">
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
                <div style="flex:1;min-width:280px;">
                    <h3>
                        <?php esc_html_e( 'Hot Deals List', 'spoke-theme' ); ?>
                        <span id="deal-count" style="font-size:13px;color:#666;">(<?php echo count( $saved_ids ); ?>/16)</span>
                    </h3>
                    <ul id="spoke-deal-list" style="border:1px solid #ddd;border-radius:6px;min-height:400px;background:#fff;padding:8px;list-style:none;margin:0;">
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
                <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Hot Deals', 'spoke-theme' ); ?>">
            </p>
        </form>
    </div>
    <script>
    (function () {
        var ids   = <?php echo wp_json_encode( $saved_ids ); ?>;
        var input = document.getElementById('hot-deal-ids-input');
        var list  = document.getElementById('spoke-deal-list');
        var count = document.getElementById('deal-count');
        function updateInput() { input.value = ids.join(','); count.textContent = '(' + ids.length + '/16)'; }
        document.querySelectorAll('.spoke-add-deal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (ids.length >= 16) { alert('Maximum 16 courses.'); return; }
                var id = parseInt(this.dataset.id), title = this.dataset.title;
                if (ids.indexOf(id) !== -1) return;
                ids.push(id); updateInput();
                var li = document.createElement('li');
                li.className = 'spoke-deal-item'; li.dataset.id = id;
                li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:4px;margin-bottom:4px;background:#f3f4f5;cursor:grab;border:1px solid #e0e0e0;';
                li.innerHTML = '<span style="font-size:13px;">≡ ' + title + '</span><button type="button" class="spoke-remove-deal button button-small button-link-delete" data-id="' + id + '">✕</button>';
                list.appendChild(li); bindRemove(li.querySelector('.spoke-remove-deal'));
                this.textContent = 'Added'; this.disabled = true;
                var row = this.closest('.spoke-course-row');
                if (row) { row.style.opacity = '0.5'; row.style.background = '#e8f4ec'; }
            });
        });
        function bindRemove(btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.dataset.id);
                ids = ids.filter(function (x) { return x !== id; }); updateInput();
                this.closest('li').remove();
                var addBtn = document.querySelector('.spoke-add-deal[data-id="' + id + '"]');
                if (addBtn) { addBtn.textContent = '+ Add'; addBtn.disabled = false; var row = addBtn.closest('.spoke-course-row'); if (row) { row.style.opacity = '1'; row.style.background = '#f9f9f9'; } }
            });
        }
        document.querySelectorAll('.spoke-remove-deal').forEach(bindRemove);
        document.getElementById('spoke-search-products').addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.spoke-course-row').forEach(function (r) { r.style.display = r.dataset.title.includes(q) ? '' : 'none'; });
        });
        var dragSrc = null;
        function makeDraggable(el) {
            el.draggable = true;
            el.addEventListener('dragstart', function () { dragSrc = this; this.style.opacity = '0.4'; });
            el.addEventListener('dragend',   function () { this.style.opacity = ''; });
            el.addEventListener('dragover',  function (e) { e.preventDefault(); });
            el.addEventListener('drop', function (e) {
                e.preventDefault();
                if (dragSrc !== this) {
                    var items = [...list.querySelectorAll('.spoke-deal-item')];
                    var si = items.indexOf(dragSrc), di = items.indexOf(this);
                    si < di ? list.insertBefore(dragSrc, this.nextSibling) : list.insertBefore(dragSrc, this);
                    ids = [...list.querySelectorAll('.spoke-deal-item')].map(function (li) { return parseInt(li.dataset.id); });
                    updateInput();
                }
            });
        }
        document.querySelectorAll('.spoke-deal-item').forEach(makeDraggable);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.classList && n.classList.contains('spoke-deal-item')) makeDraggable(n); }); });
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
// 6. AJAX ENDPOINT — spoke_courses
// ─────────────────────────────────────────────────────────────────

function spoke_courses_ajax_handler(): void {
    $sort_map = [
        'popularity' => [ 'orderby' => 'date',           'order' => 'DESC' ],
        'rating'     => [ 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_spoke_rating_avg' ],
        'price-asc'  => [ 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => '_spoke_price' ],
        'price-desc' => [ 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_spoke_price' ],
    ];

    $cat       = sanitize_text_field( $_REQUEST['cat']    ?? '' );
    $sort      = sanitize_key(        $_REQUEST['sort']   ?? 'popularity' );
    $search    = sanitize_text_field( $_REQUEST['search'] ?? '' );
    $price     = sanitize_key(        $_REQUEST['price']  ?? '' );
    $level_raw = sanitize_text_field( $_REQUEST['level']  ?? '' );
    $levels    = array_filter( array_map( 'sanitize_key', explode( ',', $level_raw ) ) );
    $page      = max( 1, (int) ( $_REQUEST['page'] ?? 1 ) );

    if ( ! array_key_exists( $sort, $sort_map ) ) {
        $sort = 'popularity';
    }

    $args = [
        'post_type'      => 'courses',
        'posts_per_page' => 12,
        'paged'          => $page,
        'post_status'    => 'publish',
        'orderby'        => $sort_map[ $sort ]['orderby'],
        'order'          => $sort_map[ $sort ]['order'],
    ];

    if ( isset( $sort_map[ $sort ]['meta_key'] ) ) {
        $args['meta_key'] = $sort_map[ $sort ]['meta_key'];
    }
    if ( $cat ) {
        $args['tax_query'] = [ [ 'taxonomy' => 'course-category', 'field' => 'slug', 'terms' => $cat ] ];
    }
    if ( $search ) {
        $args['s'] = $search;
    }

    $meta_query = [];
    if ( $price ) {
        $pq = [ 'key' => '_spoken_effective_price', 'type' => 'DECIMAL(10,2)' ];
        if ( $price === 'free' )     { $pq['value'] = 0;               $pq['compare'] = '='; }
        if ( $price === 'under100' ) { $pq['value'] = [ 0.01, 99.99 ]; $pq['compare'] = 'BETWEEN'; }
        if ( $price === '100-500' )  { $pq['value'] = [ 100, 500 ];    $pq['compare'] = 'BETWEEN'; }
        if ( $price === '500plus' )  { $pq['value'] = 500;             $pq['compare'] = '>'; }
        $meta_query[] = $pq;
    }
    if ( ! empty( $levels ) ) {
        $allowed = [ 'beginner', 'intermediate', 'advanced' ];
        $safe    = array_values( array_intersect( $levels, $allowed ) );
        if ( ! empty( $safe ) ) {
            $meta_query[] = [ 'key' => '_spoke_level', 'value' => $safe, 'compare' => 'IN' ];
        }
    }
    if ( ! empty( $meta_query ) ) {
        $meta_query['relation']  = 'AND';
        $args['meta_query']      = $meta_query;
    }

    $loop   = new WP_Query( $args );
    $result = spoke_courses_to_json( $loop );
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_spoke_courses',        'spoke_courses_ajax_handler' );
add_action( 'wp_ajax_nopriv_spoke_courses', 'spoke_courses_ajax_handler' );


// ─────────────────────────────────────────────────────────────────
// 7. DATA HELPER — spoke_courses_to_json
//    Now includes 'purchased' boolean for logged-in users.
// ─────────────────────────────────────────────────────────────────

function spoke_courses_to_json( WP_Query $loop ): array {
    $courses    = [];
    $user_id    = get_current_user_id();
    $is_logged  = $user_id > 0;

    // Pre-fetch purchased product IDs for logged-in users (one query, not per card).
    $bought_product_ids = [];
    if ( $is_logged && function_exists( 'wc_get_orders' ) ) {
        $orders = wc_get_orders( [
            'customer_id' => $user_id,
            'status'      => [ 'completed', 'processing' ],
            'limit'       => -1,
            'return'      => 'ids',
        ] );
        foreach ( $orders as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }
            foreach ( $order->get_items() as $item ) {
                if ( $item instanceof WC_Order_Item_Product ) {
                    $pid = (int) $item->get_product_id();
                    if ( $pid ) {
                        $bought_product_ids[] = $pid;
                    }
                }
            }
        }
        $bought_product_ids = array_unique( $bought_product_ids );
    }

    if ( $loop->have_posts() ) {
        while ( $loop->have_posts() ) {
            $loop->the_post();
            $id = get_the_ID();

            // ── Rating & students ─────────────────────────────────────
            $rating_avg = (float) get_post_meta( $id, '_spoke_rating_avg', true );
            $rating_cnt = (int)   get_post_meta( $id, '_spoke_rating_count', true );
            $students   = (int)   get_post_meta( $id, '_spoke_students', true );

            if ( $rating_avg === 0.0 && function_exists( 'tutor_utils' ) ) {
                $rating_obj = tutor_utils()->get_course_rating( $id );
                $rating_avg = round( (float) ( $rating_obj->rating_avg   ?? 0 ), 1 );
                $rating_cnt = (int)          ( $rating_obj->rating_count ?? 0 );
            }
            if ( $students === 0 && function_exists( 'tutor_utils' ) ) {
                $students = (int) tutor_utils()->count_enrolled_users_by_course( $id );
            }

            // ── Price ─────────────────────────────────────────────────
            $wc_pid     = (int) get_post_meta( $id, '_tutor_course_product_id', true );
            $wc_product = $wc_pid && function_exists( 'wc_get_product' ) ? wc_get_product( $wc_pid ) : null;

            if ( $wc_product ) {
                $price           = (float) $wc_product->get_regular_price();
                $sale_price      = (float) $wc_product->get_sale_price();
                $add_to_cart_url = $wc_product->add_to_cart_url();
                $can_add         = $wc_product->is_purchasable() && $wc_product->is_in_stock();
            } else {
                $price           = (float) get_post_meta( $id, '_spoke_price', true );
                $sale_price      = 0.0;
                $add_to_cart_url = get_permalink();
                $can_add         = false;
                $wc_pid          = 0;
            }

            $effective = $sale_price > 0 ? $sale_price : $price;
            $disc_pct  = ( $sale_price > 0 && $price > 0 ) ? round( ( 1 - $sale_price / $price ) * 100 ) : 0;

            // ── Purchased check ───────────────────────────────────────
            // Priority: Tutor LMS enrollment (most reliable) → WC order history.
            $purchased = false;
            if ( $is_logged ) {
                if ( function_exists( 'tutor_utils' ) ) {
                    $purchased = (bool) tutor_utils()->is_enrolled( $id, $user_id );
                }
                if ( ! $purchased && $wc_pid && in_array( $wc_pid, $bought_product_ids, true ) ) {
                    $purchased = true;
                }
            }

            // ── Category ─────────────────────────────────────────────
            $c_cats   = get_the_terms( $id, 'course-category' );
            $cat_name = ( $c_cats && ! is_wp_error( $c_cats ) ) ? $c_cats[0]->name : '';
            $cat_slug = ( $c_cats && ! is_wp_error( $c_cats ) ) ? $c_cats[0]->slug : '';

            // ── Thumbnail ────────────────────────────────────────────
            $thumb = get_the_post_thumbnail_url( $id, 'medium_large' ) ?: '';

            // ── Keep _spoken_effective_price in sync ──────────────────
            $cached = (float) get_post_meta( $id, '_spoken_effective_price', true );
            if ( $cached !== $effective ) {
                update_post_meta( $id, '_spoken_effective_price', $effective );
            }

            $courses[] = [
                'id'              => $id,
                'title'           => get_the_title(),
                'url'             => get_permalink(),
                'thumb'           => $thumb,
                'cat_name'        => $cat_name,
                'cat_slug'        => $cat_slug,
                'price'           => $price,
                'sale_price'      => $sale_price,
                'effective_price' => $effective,
                'discount_pct'    => $disc_pct,
                'rating_avg'      => $rating_avg,
                'rating_cnt'      => $rating_cnt,
                'students'        => $students,
                'wc_product_id'   => $wc_pid,
                'add_to_cart_url' => $add_to_cart_url,
                'can_add'         => $can_add,
                'purchased'       => $purchased,
                'dashboard_url'   => home_url( '/dashboard/' ),
                'cart_url'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
            ];
        }
        wp_reset_postdata();
    }

    return [
        'courses'   => $courses,
        'total'     => (int) $loop->found_posts,
        'max_pages' => (int) $loop->max_num_pages,
    ];
}


// ─────────────────────────────────────────────────────────────────
// 8. ARCHIVE RENDER FUNCTION
// ─────────────────────────────────────────────────────────────────

function spoke_render_course_archive(): void {

    $initial_loop = new WP_Query( [
        'post_type'      => 'courses',
        'posts_per_page' => 12,
        'paged'          => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    $initial_data  = spoke_courses_to_json( $initial_loop );
    $initial_json  = wp_json_encode( $initial_data );
    $ajax_url      = admin_url( 'admin-ajax.php' );
    $ajax_nonce    = wp_create_nonce( 'spoke_courses_nonce' );
    $total_courses = (int) $initial_loop->found_posts;

    $course_cats = get_terms( [ 'taxonomy' => 'course-category', 'hide_empty' => true, 'number' => 20 ] );
    if ( is_wp_error( $course_cats ) ) {
        $course_cats = [];
    }

    ?>
    <div class="spoke-archive-wrap" style="background:#f8f9fa;min-height:100vh;font-family:'Inter',sans-serif;">

        <div style="background:#f8f9fa;" class="pt-12 pb-8 px-6 lg:px-8">
            <div class="max-w-[1280px] mx-auto">
                <nav class="flex items-center gap-2 text-[14px] mb-6" aria-label="Breadcrumb">
                    <a href="/" class="text-[#43474f] font-medium hover:text-[#1A3C6E] transition-colors no-underline">Home</a>
                    <span class="text-[#c4c6d0]">/</span>
                    <span class="font-semibold" style="color:#1A3C6E;">Courses</span>
                </nav>
                <h1 class="font-bold leading-tight tracking-[-2px] max-w-[660px] m-0" style="color:#1A3C6E;font-size:clamp(1.75rem,4vw,3rem);">
                    Browse Accredited Courses for Professionals
                </h1>
                <p class="text-[17px] mt-3 mb-0" style="color:#43474f;">
                    150+ UK-accredited programmes for working professionals.
                </p>
            </div>
        </div>

        <div class="sticky top-[80px] z-40 border-y" style="background:rgba(243,244,245,0.97);backdrop-filter:blur(8px);border-color:rgba(196,198,208,0.25);">
            <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="relative w-full sm:w-[360px]">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="spoke-course-search" placeholder="Search courses…"
                           class="w-full bg-white border border-[#d1d5db] rounded-lg pl-10 pr-4 py-2.5 text-[14px] text-[#191c1d] placeholder-[#6b7280] focus:outline-none transition">
                </div>
                <div class="flex gap-3 shrink-0">
                    <div class="relative">
                        <select id="categoryFilter" class="appearance-none bg-white border border-[rgba(196,198,208,0.3)] rounded-lg pl-4 pr-8 py-2.5 text-[14px] text-[#191c1d] font-medium min-w-[140px] cursor-pointer focus:outline-none">
                            <option value="">All Categories</option>
                            <?php foreach ( $course_cats as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="relative">
                        <select id="sortFilter" class="appearance-none bg-white border border-[rgba(196,198,208,0.3)] rounded-lg pl-4 pr-8 py-2.5 text-[14px] text-[#191c1d] font-medium min-w-[160px] cursor-pointer focus:outline-none">
                            <option value="popularity">Sort by: Popularity</option>
                            <option value="rating">Sort by: Rating</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                        </select>
                        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none" style="color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-10 flex gap-10 items-start">

            <aside class="hidden lg:flex flex-col gap-8 w-[240px] shrink-0 sticky top-[148px]">
                <div class="flex flex-col gap-2.5 pb-6 border-b" style="border-color:rgba(196,198,208,0.3);">
                    <?php
                    $badges = [
                        [ 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'STRIPE SECURE' ],
                        [ 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', '30-DAY REFUND' ],
                        [ 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'CPD ACCREDITED' ],
                    ];
                    foreach ( $badges as $badge ) { $path = $badge[0]; $label = $badge[1]; ?>
                    <div class="flex items-center gap-2 text-[12px] font-bold" style="color:#43474f;">
                        <svg class="w-4 h-4 flex-shrink-0" style="color:#1A3C6E;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr( $path ); ?>"/>
                        </svg>
                        <span><?php echo esc_html( $label ); ?></span>
                    </div>
                    <?php } ?>
                </div>

                <div class="flex flex-col gap-4">
                    <h3 class="text-[11px] font-bold uppercase tracking-[1.2px] m-0" style="color:#43474f;">Difficulty Level</h3>
                    <div class="flex flex-col gap-3">
                        <?php foreach ( [ 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced' ] as $val => $lbl ) : ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" value="<?php echo esc_attr( $val ); ?>" class="spoke-level-check w-4 h-4 rounded" style="accent-color:#1A3C6E;">
                            <span class="text-[14px] group-hover:text-[#1A3C6E] transition-colors" style="color:#191c1d;"><?php echo esc_html( $lbl ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <h3 class="text-[11px] font-bold uppercase tracking-[1.2px] m-0" style="color:#43474f;">Price Range</h3>
                    <div class="flex flex-col gap-3">
                        <?php foreach ( [ '' => 'Any Price', 'free' => 'Free', 'under100' => 'Under £100', '100-500' => '£100 – £500', '500plus' => '£500+' ] as $val => $lbl ) : ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" name="spoke-price" value="<?php echo esc_attr( $val ); ?>" class="spoke-price-check w-4 h-4" style="accent-color:#1A3C6E;" <?php checked( $val, '' ); ?>>
                            <span class="text-[14px] group-hover:text-[#1A3C6E] transition-colors" style="color:#191c1d;"><?php echo esc_html( $lbl ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <div class="flex-1 min-w-0 flex flex-col gap-8">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-[14px] m-0" style="color:#43474f;" id="spoke-course-count">
                        <strong style="color:#1A3C6E;"><?php echo $total_courses; ?></strong> courses found
                    </p>
                    <div class="flex gap-1.5">
                        <button id="spoke-view-grid" class="w-9 h-9 flex items-center justify-center rounded-lg border" style="background:#1A3C6E;color:#fff;border-color:rgba(196,198,208,0.4);" aria-label="Grid view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                        </button>
                        <button id="spoke-view-list" class="w-9 h-9 flex items-center justify-center rounded-lg border" style="background:#fff;color:#43474f;border-color:rgba(196,198,208,0.4);" aria-label="List view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="spoke-course-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6"></div>

                <div id="spoke-no-results" class="hidden text-center py-16" style="color:#43474f;">
                    <p class="text-[18px] font-medium">No courses match your search.</p>
                    <p class="text-[14px] mt-1">Try a different keyword or clear the filters.</p>
                </div>

                <nav id="spoke-pagination" class="flex justify-center mt-4 gap-1" aria-label="Course pages"></nav>
            </div>

        </div>

    </div>

    <script>
    (function () {
        'use strict';

        var AJAX_URL  = '<?php echo esc_js( $ajax_url ); ?>';
        var NONCE     = '<?php echo esc_js( $ajax_nonce ); ?>';
        var INITIAL   = <?php echo $initial_json; ?>;

        // ── SESSION CACHE ──────────────────────────────────────────────
        // Key: "cat|sort|search|price|level|page"
        // Stored in sessionStorage so cache clears when tab closes.
        var CACHE_PREFIX = 'spoke_courses_v2_';
        function cacheKey(p) { return CACHE_PREFIX + [p.cat,p.sort,p.search,p.price,p.level,p.page].join('|'); }
        function cacheGet(k) { try { var v = sessionStorage.getItem(k); return v ? JSON.parse(v) : null; } catch(e) { return null; } }
        function cacheSet(k, d) { try { sessionStorage.setItem(k, JSON.stringify(d)); } catch(e) {} }

        // ── STATE ──────────────────────────────────────────────────────
        var state = { cat:'', sort:'popularity', search:'', price:'', level:'', page:1 };
        var searchTimer = null, isListView = false;

        // Seed cache with server-rendered first page.
        cacheSet(cacheKey(state), INITIAL);

        // ── DOM REFS ───────────────────────────────────────────────────
        var gridEl     = document.getElementById('spoke-course-grid');
        var countEl    = document.getElementById('spoke-course-count');
        var noResultEl = document.getElementById('spoke-no-results');
        var paginEl    = document.getElementById('spoke-pagination');
        var searchEl   = document.getElementById('spoke-course-search');
        var catEl      = document.getElementById('categoryFilter');
        var sortEl     = document.getElementById('sortFilter');
        var btnGrid    = document.getElementById('spoke-view-grid');
        var btnList    = document.getElementById('spoke-view-list');

        // ── STAR BUILDER ───────────────────────────────────────────────
        var SP = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
        function buildStars(avg) {
            var h = '';
            for (var i = 1; i <= 5; i++) {
                h += '<svg class="w-3 h-3" fill="' + (i <= Math.round(avg) ? '#F4A726' : '#d1d5db') + '" viewBox="0 0 20 20"><path d="' + SP + '"/></svg>';
            }
            return h;
        }

        // ── ESCAPE HELPERS ─────────────────────────────────────────────
        function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

        // ── CTA BUILDER — three states ─────────────────────────────────
        // State 1 (purchased): Go to Dashboard — navy button.
        // State 2 (in cart / just added): View Cart — navy button (toggled via JS event).
        // State 3 (default): Add to Cart — amber button with WooCommerce AJAX class.
        function buildCta(c) {
            var wrap = '<div class="spoke-cta-wrap" data-course="' + c.id + '" data-pid="' + c.wc_product_id + '">';

            if (c.purchased) {
                // Already enrolled — go straight to the dashboard.
                wrap += '<a href="' + esc(c.dashboard_url) + '" class="inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline" style="background:#1A3C6E;color:#fff;">Go to Dashboard</a>';
            } else if (c.can_add) {
                // Add to Cart (default) — WooCommerce AJAX add-to-cart class attached.
                // View Cart link is hidden by default; JS shows it after add-to-cart fires.
                wrap += '<a href="' + esc(c.add_to_cart_url) + '" '
                    + 'data-quantity="1" data-product_id="' + c.wc_product_id + '" '
                    + 'class="ajax_add_to_cart add_to_cart_button spoke-add-to-cart-btn spoke-btn-add inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline" '
                    + 'style="background:#F4A726;color:#6b4500;" rel="nofollow">Add to Cart</a>';
                // View Cart — hidden until add-to-cart completes.
                wrap += '<a href="' + esc(c.cart_url) + '" '
                    + 'class="spoke-view-cart-btn inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline" '
                    + 'style="background:#1A3C6E;color:#fff;display:none;">View Cart →</a>';
            } else {
                // No WC product linked — link to single course page.
                wrap += '<a href="' + esc(c.url) + '" class="inline-flex items-center h-10 px-4 rounded-lg font-bold text-[13px] no-underline" style="background:#1A3C6E;color:#fff;">View Course</a>';
            }

            wrap += '</div>';
            return wrap;
        }

        // ── CARD BUILDER ───────────────────────────────────────────────
        function buildCard(c) {
            var thumbHtml = c.thumb
                ? '<img src="' + esc(c.thumb) + '" alt="' + esc(c.title) + '" class="w-full h-full object-cover" loading="lazy" width="400" height="170">'
                : '<div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,#1A3C6E,#1A1A2E);"><svg class="w-14 h-14 opacity-20" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>';

            var catBadge = c.cat_name
                ? '<span class="absolute top-3 left-3 text-[10px] font-bold uppercase tracking-[0.5px] px-2.5 py-1 rounded" style="background:#F4A726;color:#6b4500;">' + esc(c.cat_name) + '</span>'
                : '';

            var discBadge = c.discount_pct > 0
                ? '<span class="absolute bottom-3 right-3 text-[10px] font-bold uppercase px-2 py-1 rounded" style="background:#BA1A1A;color:#fff;">' + c.discount_pct + '% OFF</span>'
                : '';

            var priceHtml = '';
            if (c.effective_price > 0) {
                priceHtml = '<span class="font-black text-[20px]" style="color:#1A3C6E;">£' + c.effective_price.toFixed(2) + '</span>';
                if (c.discount_pct > 0) {
                    priceHtml += '<span class="text-[14px] line-through ml-1" style="color:#43474f;">£' + c.price.toFixed(2) + '</span>';
                }
            } else {
                priceHtml = '<span class="font-black text-[20px]" style="color:#1A3C6E;">Free</span>';
            }

            var studentsHtml = c.students > 0
                ? '<span class="text-[12px] ml-auto" style="color:#43474f;">' + c.students.toLocaleString() + ' students</span>'
                : '';
            var ratingCntHtml = c.rating_cnt > 0
                ? '<span class="text-[12px]" style="color:#43474f;">(' + c.rating_cnt.toLocaleString() + ')</span>'
                : '';

            return '<article class="spoke-course-card bg-white rounded-xl overflow-hidden flex flex-col"'
                + ' style="box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:box-shadow 0.2s ease,transform 0.2s ease;"'
                + ' onmouseenter="this.style.boxShadow=\'0 8px 32px rgba(26,60,110,0.12)\';this.style.transform=\'translateY(-3px)\';"'
                + ' onmouseleave="this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.08)\';this.style.transform=\'\';">'
                + '<div class="spoke-card-thumb relative overflow-hidden" style="height:170px;">'
                +   '<a href="' + esc(c.url) + '" class="block w-full h-full" tabindex="-1" aria-hidden="true">' + thumbHtml + '</a>'
                +   catBadge + discBadge
                + '</div>'
                + '<div class="p-5 flex flex-col gap-2 flex-1">'
                +   '<h3 class="font-bold text-[16px] leading-snug m-0" style="color:#1A3C6E;">'
                +     '<a href="' + esc(c.url) + '" class="no-underline hover:underline" style="color:inherit;">' + esc(c.title) + '</a>'
                +   '</h3>'
                +   '<div class="flex items-center gap-1.5">'
                +     '<div class="flex gap-0.5">' + buildStars(c.rating_avg) + '</div>'
                +     '<span class="font-bold text-[12px]" style="color:#1A3C6E;">' + c.rating_avg.toFixed(1) + '</span>'
                +     ratingCntHtml + studentsHtml
                +   '</div>'
                +   '<div class="flex items-center justify-between pt-3 mt-auto" style="border-top:1px solid rgba(0,0,0,0.07);">'
                +     '<div class="flex items-baseline gap-2">' + priceHtml + '</div>'
                +     buildCta(c)
                +   '</div>'
                + '</div>'
                + '</article>';
        }

        // ── WOOCOMMERCE: intercept added_to_cart event ─────────────────
        // WooCommerce appends a `.added_to_cart.wc-forward` link after the button.
        // We hide it via CSS AND switch our own button states here.
        function bindWooEvents() {
            if (typeof jQuery === 'undefined') return;
            jQuery(document.body).off('added_to_cart.spoke').on('added_to_cart.spoke', function(e, fragments, hash, $btn) {
                var pid = parseInt($btn.data('product_id'), 10);
                if (!pid) return;

                document.querySelectorAll('.spoke-cta-wrap[data-pid="' + pid + '"]').forEach(function(wrap) {
                    // Hide Add to Cart button.
                    var addBtn = wrap.querySelector('.spoke-btn-add');
                    if (addBtn) addBtn.style.display = 'none';
                    // Show View Cart button.
                    var vcBtn = wrap.querySelector('.spoke-view-cart-btn');
                    if (vcBtn) vcBtn.style.display = 'inline-flex';
                    // Remove WC's own appended View Cart link — prevent layout break.
                    var wcLink = wrap.querySelector('.added_to_cart');
                    if (wcLink) wcLink.remove();
                });
            });
        }

        // Also kill any WC-appended links via MutationObserver (belt-and-suspenders).
        var bodyObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                m.addedNodes.forEach(function(node) {
                    if (node.nodeType !== 1) return;
                    // If WC appended a .added_to_cart inside one of our CTA wrappers, remove it.
                    var parent = node.closest ? node.closest('.spoke-cta-wrap') : null;
                    if (parent && node.classList && node.classList.contains('added_to_cart')) {
                        node.remove();
                        // Also ensure View Cart is shown.
                        var vcBtn = parent.querySelector('.spoke-view-cart-btn');
                        if (vcBtn) vcBtn.style.display = 'inline-flex';
                        var addBtn = parent.querySelector('.spoke-btn-add');
                        if (addBtn) addBtn.style.display = 'none';
                    }
                });
            });
        });
        bodyObserver.observe(document.body, { childList: true, subtree: true });

        // ── SPINNER ────────────────────────────────────────────────────
        function showSpinner() {
            gridEl.style.display = '';
            noResultEl.classList.add('hidden');
            gridEl.innerHTML = '<div class="col-span-full flex items-center justify-center" style="min-height:300px;">'
                + '<div style="width:40px;height:40px;border:3px solid #e5e7eb;border-top-color:#1A3C6E;border-radius:50%;animation:spokeSpin 0.7s linear infinite;"></div>'
                + '</div>';
        }

        // ── RENDER COURSES ─────────────────────────────────────────────
        function renderData(data) {
            var courses = data.courses || [];
            var total   = data.total   || 0;

            if (courses.length === 0) {
                // Hide grid entirely (no wasted min-height), show message.
                gridEl.style.display = 'none';
                noResultEl.classList.remove('hidden');
                countEl.innerHTML = '<strong style="color:#1A3C6E;">0</strong> courses found';
                renderPagination(0, 0);
                return;
            }

            // Has results — show grid, hide message.
            gridEl.style.display = '';
            noResultEl.classList.add('hidden');
            countEl.innerHTML = '<strong style="color:#1A3C6E;">' + total + '</strong> course' + (total !== 1 ? 's' : '') + ' found';
            gridEl.innerHTML  = courses.map(buildCard).join('');

            // Re-bind WooCommerce AJAX on newly rendered buttons.
            bindWooEvents();
            if (typeof jQuery !== 'undefined') {
                jQuery(document.body).trigger('wc_fragment_refresh');
            }

            renderPagination(data.max_pages, state.page);
        }

        // ── PAGINATION ─────────────────────────────────────────────────
        function renderPagination(maxPages, current) {
            paginEl.innerHTML = '';
            if (maxPages <= 1) return;

            function makeBtn(label, page, disabled, active) {
                var el = document.createElement('button');
                el.textContent = label;
                el.disabled    = disabled;
                el.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;'
                    + 'min-width:40px;height:40px;padding:0 10px;border-radius:8px;font-size:13px;font-weight:600;'
                    + 'border:1px solid ' + (active ? '#1A3C6E' : 'rgba(0,0,0,0.1)') + ';'
                    + 'background:' + (active ? '#1A3C6E' : '#fff') + ';'
                    + 'color:' + (active ? '#fff' : '#43474f') + ';'
                    + 'cursor:' + (disabled ? 'default' : 'pointer') + ';margin:0 2px;';
                if (!disabled) el.addEventListener('click', function () { goToPage(page); });
                return el;
            }

            paginEl.appendChild(makeBtn('← Prev', current - 1, current <= 1, false));
            for (var p = 1; p <= maxPages; p++) {
                paginEl.appendChild(makeBtn(String(p), p, false, p === current));
            }
            paginEl.appendChild(makeBtn('Next →', current + 1, current >= maxPages, false));
        }

        function goToPage(page) {
            state.page = page;
            fetchAndRender();
            document.querySelector('.spoke-archive-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // ── FETCH + SESSIONCACHE ───────────────────────────────────────
        // Check sessionStorage first.
        // If found → render immediately (no network request).
        // If not found → fetch via AJAX, store result, then render.
        function fetchAndRender() {
            var key    = cacheKey(state);
            var cached = cacheGet(key);

            if (cached) {
                renderData(cached);
                return;
            }

            showSpinner();

            var params = new URLSearchParams({
                action:   'spoke_courses',
                _wpnonce: NONCE,
                cat:      state.cat,
                sort:     state.sort,
                search:   state.search,
                price:    state.price,
                level:    state.level,
                page:     state.page,
            });

            fetch(AJAX_URL + '?' + params.toString())
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success && res.data) {
                        cacheSet(key, res.data);
                        renderData(res.data);
                    } else {
                        gridEl.innerHTML = '<p class="col-span-full text-center py-10" style="color:#43474f;">Something went wrong. Please refresh.</p>';
                    }
                })
                .catch(function () {
                    gridEl.innerHTML = '<p class="col-span-full text-center py-10" style="color:#43474f;">Network error. Please refresh.</p>';
                });
        }

        // ── FILTER CHANGE HANDLER ──────────────────────────────────────
        function onFilterChange() {
            state.page = 1;
            fetchAndRender();
        }

        // ── EVENT LISTENERS ────────────────────────────────────────────
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var val = this.value.toLowerCase().trim();
                searchTimer = setTimeout(function () { state.search = val; onFilterChange(); }, 400);
            });
        }
        if (catEl)  { catEl.addEventListener('change', function () { state.cat  = this.value; onFilterChange(); }); }
        if (sortEl) { sortEl.addEventListener('change', function () { state.sort = this.value; onFilterChange(); }); }

        document.querySelectorAll('.spoke-price-check').forEach(function (r) {
            r.addEventListener('change', function () { state.price = this.value; onFilterChange(); });
        });

        document.querySelectorAll('.spoke-level-check').forEach(function (r) {
            r.addEventListener('change', function () {
                var checked = [];
                document.querySelectorAll('.spoke-level-check:checked').forEach(function (cb) { checked.push(cb.value); });
                state.level = checked.join(',');
                onFilterChange();
            });
        });

        function setView(mode) {
            isListView = mode === 'list';
            if (isListView) {
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

        // ── SPINNER CSS ────────────────────────────────────────────────
        var styleEl = document.createElement('style');
        styleEl.textContent = '@keyframes spokeSpin{to{transform:rotate(360deg)}}';
        document.head.appendChild(styleEl);

        // ── INITIAL RENDER (server-baked JSON, zero AJAX on first load) ─
        renderData(INITIAL);
        bindWooEvents();

    })();
    </script>
    <?php
}