<?php
/**
 * SPOKE HOT DEALS — Admin Page + [spoke_hot_deals] Shortcode
 *
 * Place this file at: inc/functions-hot-deals.php
 *
 * Then in functions.php add ONE line alongside the other requires:
 *   require_once get_template_directory() . '/inc/functions-hot-deals.php';
 *
 * AND remove sections 4 & 5 from inc/functions-archive-addon.php:
 *   - "4. HOT DEALS ADMIN MENU"  (the add_action admin_menu + spoke_hot_deals_admin_page function)
 *   - "5. HELPER: GET HOT DEAL IDS"  (the spoke_get_hot_deal_ids function)
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. HELPER — read saved IDs from wp_options
// ─────────────────────────────────────────────────────────────────

if ( ! function_exists( 'spoke_get_hot_deal_ids' ) ) {
	function spoke_get_hot_deal_ids(): array {
		return array_map( 'intval', (array) get_option( 'spoke_hot_deal_ids', [] ) );
	}
}


// ─────────────────────────────────────────────────────────────────
// 2. SHORTCODE — [spoke_hot_deals]
//    Used in patterns/hot-deals-banner.php and anywhere on site.
// ─────────────────────────────────────────────────────────────────

add_shortcode( 'spoke_hot_deals', function (): string {
	ob_start();
	spoke_render_hot_deals_section();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	return $html;
} );

// Intercept the core/html block that contains [spoke_hot_deals] and render it directly.
// This is the same approach used for spoke_course_archive in functions-archive-addon.php.
// <!-- wp:shortcode --> is unreliable in FSE block theme patterns; <!-- wp:html --> + this
// render_block filter is the correct, consistent solution.
add_filter( 'render_block', function ( string $block_content, array $block ): string {
	if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/html' ) {
		return $block_content;
	}
	$raw = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
	if ( false === strpos( $raw, 'spoke_hot_deals' ) ) {
		return $block_content;
	}
	ob_start();
	spoke_render_hot_deals_section();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	$html = preg_replace( '/(\s*\n){3,}/', "\n", $html );
	return $html;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 3. ADMIN MENU — WP Admin > Hot Deals
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
	add_menu_page(
		'Hot Deals',          // page title
		'Hot Deals',          // menu label
		'manage_options',     // capability
		'spoke-hot-deals',    // slug
		'spoke_hot_deals_admin_page',
		'dashicons-tag',
		58
	);
} );


// ─────────────────────────────────────────────────────────────────
// 4. ADMIN PAGE CALLBACK
// ─────────────────────────────────────────────────────────────────

function spoke_hot_deals_admin_page(): void {

	// ── Process form save ────────────────────────────────────────
	$save_message = '';
	if (
		isset( $_POST['spoke_hot_deals_nonce'] ) &&
		wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['spoke_hot_deals_nonce'] ) ),
			'spoke_save_hot_deals'
		)
	) {
		$new_ids = [];
		$raw     = isset( $_POST['hot_deal_ids'] )
		         ? sanitize_text_field( wp_unslash( $_POST['hot_deal_ids'] ) )
		         : '';

		if ( $raw !== '' ) {
			foreach ( explode( ',', $raw ) as $piece ) {
				$n = (int) trim( $piece );
				if ( $n > 0 ) {
					$new_ids[] = $n;
				}
			}
		}

		$new_ids = array_unique( array_slice( $new_ids, 0, 16 ) );
		update_option( 'spoke_hot_deal_ids', $new_ids );
		$save_message = count( $new_ids ) . ' course(s) saved successfully.';
	}

	// ── Load current saved list ───────────────────────────────────
	$saved_ids = spoke_get_hot_deal_ids();

	// ── Fetch all courses (Tutor LMS post type) ───────────────────
	// We query ONLY 'courses' because that's what the frontend renders.
	// If you also want WooCommerce products here, add 'product'.
	$all_courses = get_posts( [
		'post_type'      => 'courses',
		'posts_per_page' => 500,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	// Fallback: if no 'courses' found, also try 'product' (useful during setup)
	if ( empty( $all_courses ) ) {
		$all_courses = get_posts( [
			'post_type'      => 'product',
			'posts_per_page' => 500,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
	}

	?>
	<div class="wrap" style="max-width:1200px;">

		<h1 style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
			<span style="font-size:24px;">🔥</span> Hot Deals — Course Curation
		</h1>
		<p style="color:#50575e;margin-top:4px;margin-bottom:20px;">
			Pick up to 16 courses to feature in the Hot Deals section on your site.
			Click <strong>+ Add</strong> to add a course, drag items in the right column to reorder, then click <strong>Save Hot Deals</strong>.
		</p>

		<?php if ( $save_message ) : ?>
			<div class="notice notice-success is-dismissible" style="margin-bottom:16px;">
				<p>✅ <?php echo esc_html( $save_message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $all_courses ) ) : ?>
			<div class="notice notice-warning">
				<p>
					<strong>No courses found.</strong>
					Make sure you have published courses (Tutor LMS post type: <code>courses</code>).
				</p>
				<p>
					Post counts right now:
					<?php foreach ( [ 'courses', 'product', 'post' ] as $pt ) :
						$c = wp_count_posts( $pt ); ?>
						<code><?php echo esc_html( $pt ); ?>: <?php echo intval( $c->publish ?? 0 ); ?> published</code>&nbsp;
					<?php endforeach; ?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'spoke_save_hot_deals', 'spoke_hot_deals_nonce' ); ?>
			<input type="hidden" name="hot_deal_ids" id="hd-ids-input"
			       value="<?php echo esc_attr( implode( ',', $saved_ids ) ); ?>">

			<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">

				<!-- ════════ LEFT COLUMN — All Courses ════════ -->
				<div style="flex:1;min-width:300px;">

					<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
						<h2 style="font-size:15px;margin:0;">
							All Courses
							<span style="font-size:12px;color:#666;font-weight:normal;">
								(<?php echo count( $all_courses ); ?> published)
							</span>
						</h2>
					</div>

					<input type="text"
					       id="hd-search"
					       placeholder="Filter courses by name…"
					       autocomplete="off"
					       style="width:100%;padding:8px 12px;margin-bottom:8px;border:1px solid #ccd0d4;border-radius:4px;font-size:13px;box-sizing:border-box;">

					<div id="hd-course-list"
					     style="border:1px solid #ccd0d4;border-radius:4px;height:500px;overflow-y:auto;background:#fff;">

						<?php if ( empty( $all_courses ) ) : ?>
							<p style="padding:24px;color:#666;font-style:italic;text-align:center;">
								No published courses found.
							</p>
						<?php else : ?>
							<?php foreach ( $all_courses as $course ) :
								$is_added = in_array( $course->ID, $saved_ids, true );
							?>
							<div class="hd-course-row"
							     data-id="<?php echo esc_attr( $course->ID ); ?>"
							     data-name="<?php echo esc_attr( strtolower( $course->post_title ) ); ?>"
							     style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f0f0f1;background:<?php echo $is_added ? '#f0faf4' : '#fff'; ?>;">

								<?php
								// Small thumbnail
								$thumb_url = get_the_post_thumbnail_url( $course->ID, 'thumbnail' );
								if ( $thumb_url ) :
								?>
								<img src="<?php echo esc_url( $thumb_url ); ?>"
								     alt=""
								     style="width:40px;height:30px;object-fit:cover;border-radius:3px;flex-shrink:0;">
								<?php else : ?>
								<div style="width:40px;height:30px;background:#e0e4ea;border-radius:3px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
									<span style="font-size:14px;">🎓</span>
								</div>
								<?php endif; ?>

								<div style="flex:1;min-width:0;">
									<div style="font-size:13px;font-weight:600;color:#1d2327;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
										<?php echo esc_html( $course->post_title ); ?>
									</div>
									<div style="font-size:11px;color:#666;">
										ID: <?php echo esc_html( $course->ID ); ?> &middot; <?php echo esc_html( $course->post_type ); ?>
									</div>
								</div>

								<button type="button"
								        class="hd-add-btn button<?php echo $is_added ? '' : ' button-primary'; ?>"
								        data-id="<?php echo esc_attr( $course->ID ); ?>"
								        data-title="<?php echo esc_attr( $course->post_title ); ?>"
								        <?php echo $is_added ? 'disabled' : ''; ?>
								        style="flex-shrink:0;white-space:nowrap;">
									<?php echo $is_added ? '✓ Added' : '+ Add'; ?>
								</button>

							</div>
							<?php endforeach; ?>
						<?php endif; ?>

					</div><!-- /hd-course-list -->

				</div><!-- /left -->


				<!-- ════════ RIGHT COLUMN — Hot Deals List ════════ -->
				<div style="flex:1;min-width:300px;">

					<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
						<h2 style="font-size:15px;margin:0;">
							Hot Deals List
							<span id="hd-count" style="font-size:12px;color:#666;font-weight:normal;">
								(<?php echo count( $saved_ids ); ?>/16)
							</span>
						</h2>
						<span style="font-size:11px;color:#666;">Drag to reorder</span>
					</div>

					<ul id="hd-deal-list"
					    style="border:1px solid #ccd0d4;border-radius:4px;min-height:500px;background:#f6f7f7;padding:8px;list-style:none;margin:0 0 12px;">

						<?php if ( empty( $saved_ids ) ) : ?>
							<li id="hd-empty-state"
							    style="padding:48px 20px;text-align:center;color:#666;font-style:italic;border:2px dashed #ccd0d4;border-radius:4px;margin:8px;">
								No courses added yet.<br>
								Click <strong>+ Add</strong> on the left to get started.
							</li>
						<?php else : ?>
							<?php foreach ( $saved_ids as $sid ) :
								$p = get_post( (int) $sid );
								if ( ! $p ) { continue; }
							?>
							<li class="hd-deal-item"
							    data-id="<?php echo esc_attr( $p->ID ); ?>"
							    style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:4px;margin-bottom:6px;background:#fff;cursor:grab;border:1px solid #ddd;user-select:none;">
								<span style="font-size:18px;color:#aaa;flex-shrink:0;cursor:grab;">⠿</span>
								<span style="flex:1;font-size:13px;font-weight:600;color:#1d2327;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
									<?php echo esc_html( $p->post_title ); ?>
									<small style="color:#888;font-weight:normal;">&nbsp;#<?php echo (int) $p->ID; ?></small>
								</span>
								<button type="button"
								        class="hd-remove-btn button button-small"
								        data-id="<?php echo esc_attr( $p->ID ); ?>"
								        style="color:#d63638;border-color:#d63638;flex-shrink:0;">
									✕
								</button>
							</li>
							<?php endforeach; ?>
						<?php endif; ?>

					</ul><!-- /hd-deal-list -->

					<input type="submit"
					       class="button button-primary button-large"
					       value="💾  Save Hot Deals"
					       style="width:100%;">

				</div><!-- /right -->

			</div><!-- /flex -->
		</form>

	</div><!-- /wrap -->

	<script>
	/* ================================================================
	   Hot Deals Admin JS
	   All DOM is ready when this runs (WordPress loads scripts in footer)
	   ================================================================ */
	(function () {
		'use strict';

		// ── State ─────────────────────────────────────────────────────
		var ids      = <?php echo wp_json_encode( array_values( $saved_ids ) ); ?>;
		var idsInput = document.getElementById('hd-ids-input');
		var dealList = document.getElementById('hd-deal-list');
		var countEl  = document.getElementById('hd-count');
		var searchEl = document.getElementById('hd-search');

		// ── Sync hidden input + counter ───────────────────────────────
		function sync() {
			idsInput.value      = ids.join(',');
			countEl.textContent = '(' + ids.length + '/16)';

			var emptyState = document.getElementById('hd-empty-state');
			if ( ids.length === 0 ) {
				if ( ! emptyState ) {
					var li       = document.createElement('li');
					li.id        = 'hd-empty-state';
					li.style.cssText = 'padding:48px 20px;text-align:center;color:#666;font-style:italic;border:2px dashed #ccd0d4;border-radius:4px;margin:8px;';
					li.innerHTML = 'No courses added yet.<br>Click <strong>+ Add</strong> on the left to get started.';
					dealList.appendChild(li);
				}
			} else {
				if ( emptyState ) { emptyState.remove(); }
			}
		}

		// ── Mark a left-column row as added/removed ───────────────────
		function markRow( id, added ) {
			var row = document.querySelector('.hd-course-row[data-id="' + id + '"]');
			if ( ! row ) { return; }
			row.style.background = added ? '#f0faf4' : '#fff';
			var btn = row.querySelector('.hd-add-btn');
			if ( ! btn ) { return; }
			btn.textContent = added ? '✓ Added' : '+ Add';
			btn.disabled    = added;
			if ( added ) {
				btn.classList.remove('button-primary');
			} else {
				btn.classList.add('button-primary');
			}
		}

		// ── Add a course to the right-column list ─────────────────────
		function addCourse( id, title ) {
			id = parseInt( id, 10 );
			if ( ids.length >= 16 ) { alert('Maximum 16 courses allowed.'); return; }
			if ( ids.indexOf(id) !== -1 ) { return; }

			ids.push(id);
			sync();
			markRow(id, true);

			var li          = document.createElement('li');
			li.className    = 'hd-deal-item';
			li.dataset.id   = id;
			li.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:4px;margin-bottom:6px;background:#fff;cursor:grab;border:1px solid #ddd;user-select:none;';
			li.innerHTML    = '<span style="font-size:18px;color:#aaa;flex-shrink:0;cursor:grab;">⠿</span>'
			                + '<span style="flex:1;font-size:13px;font-weight:600;color:#1d2327;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'
			                + esc(title)
			                + ' <small style="color:#888;font-weight:normal;">&nbsp;#' + id + '</small>'
			                + '</span>'
			                + '<button type="button" class="hd-remove-btn button button-small" data-id="' + id + '" style="color:#d63638;border-color:#d63638;flex-shrink:0;">✕</button>';

			dealList.appendChild(li);
			bindRemove( li.querySelector('.hd-remove-btn') );
			makeDraggable(li);
		}

		// ── Remove a course from the list ─────────────────────────────
		function removeCourse( id ) {
			id   = parseInt( id, 10 );
			ids  = ids.filter(function(x) { return x !== id; });
			sync();
			markRow(id, false);

			var li = dealList.querySelector('.hd-deal-item[data-id="' + id + '"]');
			if ( li ) { li.remove(); }
		}

		// ── Bind a remove button ──────────────────────────────────────
		function bindRemove(btn) {
			btn.addEventListener('click', function() {
				removeCourse( this.dataset.id );
			});
		}

		// ── Wire up all existing + Add buttons ────────────────────────
		document.querySelectorAll('.hd-add-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				addCourse( this.dataset.id, this.dataset.title );
			});
		});

		// ── Wire up all existing remove buttons ───────────────────────
		document.querySelectorAll('.hd-remove-btn').forEach(bindRemove);

		// ── Live search / filter ──────────────────────────────────────
		searchEl.addEventListener('input', function() {
			var q = this.value.toLowerCase().trim();
			document.querySelectorAll('.hd-course-row').forEach(function(row) {
				row.style.display = ( q === '' || row.dataset.name.includes(q) ) ? '' : 'none';
			});
		});

		// ── Drag-to-reorder ───────────────────────────────────────────
		var dragSrc = null;

		function makeDraggable(el) {
			el.setAttribute('draggable', 'true');

			el.addEventListener('dragstart', function(e) {
				dragSrc = this;
				this.style.opacity = '0.45';
				e.dataTransfer.effectAllowed = 'move';
			});

			el.addEventListener('dragend', function() {
				this.style.opacity = '';
				clearDropIndicators();
			});

			el.addEventListener('dragover', function(e) {
				e.preventDefault();
				clearDropIndicators();
				this.style.outline = '2px solid #2271b1';
				this.style.outlineOffset = '-2px';
			});

			el.addEventListener('dragleave', function() {
				this.style.outline = '';
				this.style.outlineOffset = '';
			});

			el.addEventListener('drop', function(e) {
				e.preventDefault();
				clearDropIndicators();
				if ( dragSrc && dragSrc !== this ) {
					dealList.insertBefore( dragSrc, this );
					rebuildIds();
				}
			});
		}

		function clearDropIndicators() {
			dealList.querySelectorAll('.hd-deal-item').forEach(function(li) {
				li.style.outline      = '';
				li.style.outlineOffset = '';
			});
		}

		function rebuildIds() {
			ids = Array.from( dealList.querySelectorAll('.hd-deal-item') )
			           .map(function(li) { return parseInt(li.dataset.id, 10); });
			sync();
		}

		// Make existing list items draggable
		document.querySelectorAll('.hd-deal-item').forEach(makeDraggable);

		// Watch for dynamically added items
		new MutationObserver(function(mutations) {
			mutations.forEach(function(m) {
				m.addedNodes.forEach(function(n) {
					if ( n.classList && n.classList.contains('hd-deal-item') ) {
						makeDraggable(n);
					}
				});
			});
		}).observe(dealList, { childList: true });

		// ── HTML escape helper ────────────────────────────────────────
		function esc(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		// Initial sync
		sync();

	})();
	</script>
	<?php
}


// ─────────────────────────────────────────────────────────────────
// 5. FRONTEND RENDER — called by the [spoke_hot_deals] shortcode
// ─────────────────────────────────────────────────────────────────

function spoke_render_hot_deals_section(): void {

	$deal_ids = array_slice( spoke_get_hot_deal_ids(), 0, 12 );
	if ( empty( $deal_ids ) ) { return; }

	$user_id   = get_current_user_id();
	$is_logged = $user_id > 0;
	$cart_url  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );

	// Pre-fetch purchased product IDs (one query)
	$bought_pids = [];
	if ( $is_logged && function_exists( 'wc_get_orders' ) ) {
		$order_ids = wc_get_orders( [
			'customer_id' => $user_id,
			'status'      => [ 'completed', 'processing' ],
			'limit'       => -1,
			'return'      => 'ids',
		] );
		foreach ( $order_ids as $oid ) {
			$order = wc_get_order( $oid );
			if ( ! $order ) { continue; }
			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof WC_Order_Item_Product ) {
					$pid = (int) $item->get_product_id();
					if ( $pid ) { $bought_pids[] = $pid; }
				}
			}
		}
		$bought_pids = array_unique( $bought_pids );
	}

	// Build course data
	$courses = [];
	$loop = new WP_Query( [
		'post_type'      => 'courses',
		'post__in'       => $deal_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => 12,
		'post_status'    => 'publish',
	] );

	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			$id = get_the_ID();

			// Fake data takes priority over real Tutor LMS data
			$display    = spoke_get_course_display_data( $id );
			$rating_avg = $display['rating_avg'];
			$rating_cnt = $display['rating_cnt'];
			$students   = $display['students'];

			$wc_pid     = (int) get_post_meta( $id, '_tutor_course_product_id', true );
			$wc_product = ( $wc_pid && function_exists( 'wc_get_product' ) ) ? wc_get_product( $wc_pid ) : null;
			if ( $wc_product ) {
				$price           = (float) $wc_product->get_regular_price();
				$sale_price      = (float) $wc_product->get_sale_price();
				$add_to_cart_url = $wc_product->add_to_cart_url();
				$can_add         = $wc_product->is_purchasable() && $wc_product->is_in_stock();
			} else {
				$price = $sale_price = 0.0;
				$add_to_cart_url = get_permalink();
				$can_add = false; $wc_pid = 0;
			}
			$effective = $sale_price > 0 ? $sale_price : $price;
			$disc_pct  = ( $sale_price > 0 && $price > 0 ) ? round( ( 1 - $sale_price / $price ) * 100 ) : 0;

			$purchased = false;
			if ( $is_logged ) {
				if ( function_exists( 'tutor_utils' ) && tutor_utils()->is_enrolled( $id, $user_id ) ) {
					$purchased = true;
				} elseif ( $wc_pid && in_array( $wc_pid, $bought_pids, true ) ) {
					$purchased = true;
				}
			}

			$c_cats   = get_the_terms( $id, 'course-category' );
			$cat_name = ( $c_cats && ! is_wp_error( $c_cats ) ) ? $c_cats[0]->name : '';
			$thumb    = get_the_post_thumbnail_url( $id, 'medium_large' ) ?: '';

			$courses[] = [
				'id'              => $id,
				'title'           => get_the_title(),
				'url'             => get_permalink(),
				'thumb'           => $thumb,
				'cat_name'        => $cat_name,
				'price'           => $price,
				'sale_price'      => $sale_price,
				'effective'       => $effective,
				'disc_pct'        => $disc_pct,
				'rating_avg'      => $rating_avg,
				'rating_cnt'      => $rating_cnt,
				'students'        => $students,
				'wc_pid'          => $wc_pid,
				'add_to_cart_url' => $add_to_cart_url,
				'can_add'         => $can_add,
				'purchased'       => $purchased,
				'dashboard_url'   => home_url( '/dashboard/' ),
				'cart_url'        => $cart_url,
			];
		}
		wp_reset_postdata();
	}

	if ( empty( $courses ) ) { return; }

	// ── Inline CSS ────────────────────────────────────────────────
	static $css_printed = false;
	if ( ! $css_printed ) {
		$css_printed = true;
		echo '<style>.hdb-wrap{background:#f3f4f5;padding:80px 24px;font-family:"Inter",sans-serif;}.hdb-inner{max-width:1280px;margin:0 auto;}.hdb-top{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:40px;}.hdb-eyebrow{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:4px 12px;border-radius:100px;background:rgba(244,167,38,.15);border:1px solid rgba(244,167,38,.35);color:#F4A726;margin-bottom:10px;}.hdb-h2{font-size:clamp(1.75rem,3vw,2.25rem);font-weight:700;color:#1A3C6E;letter-spacing:-.04em;margin:0 0 8px;}.hdb-sub{font-size:15px;color:#43474f;margin:0;}.hdb-link{display:inline-flex;align-items:center;gap:6px;font-size:15px;font-weight:700;color:#1A3C6E;text-decoration:none;white-space:nowrap;}.hdb-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}.hdb-card{background:#fff;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,.08);transition:box-shadow 200ms,transform 200ms;}.hdb-card:hover{box-shadow:0 8px 32px rgba(26,60,110,.12);transform:translateY(-3px);}.hdb-thumb{position:relative;height:170px;overflow:hidden;display:block;flex-shrink:0;}.hdb-thumb img{width:100%;height:100%;object-fit:cover;transition:transform 400ms;}.hdb-card:hover .hdb-thumb img{transform:scale(1.05);}.hdb-ph{width:100%;height:100%;background:linear-gradient(135deg,#1A3C6E,#1A1A2E);display:flex;align-items:center;justify-content:center;}.hdb-cat{position:absolute;top:10px;left:10px;font-size:10px;font-weight:700;text-transform:uppercase;padding:3px 10px;border-radius:4px;background:#F4A726;color:#6b4500;}.hdb-disc{position:absolute;bottom:10px;right:10px;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;background:#BA1A1A;color:#fff;}.hdb-body{padding:20px;display:flex;flex-direction:column;gap:8px;flex:1;}.hdb-title{font-size:16px;font-weight:700;color:#1A3C6E;line-height:1.3;margin:0;}.hdb-title a{color:inherit;text-decoration:none;}.hdb-title a:hover{text-decoration:underline;}.hdb-stars{display:flex;align-items:center;gap:4px;flex-wrap:wrap;font-size:12px;color:#43474f;}.hdb-foot{display:flex;align-items:center;justify-content:space-between;padding-top:12px;margin-top:auto;border-top:1px solid rgba(0,0,0,.07);}.hdb-price{font-size:20px;font-weight:900;color:#1A3C6E;}.hdb-orig{font-size:13px;text-decoration:line-through;color:#43474f;margin-left:6px;}.hdb-cta{display:flex;gap:8px;align-items:center;}.hdb-btn-add,.hdb-btn-cart,.hdb-btn-nav{display:inline-flex;align-items:center;height:38px;padding:0 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;transition:filter 150ms;white-space:nowrap;}.hdb-btn-add{background:#F4A726;color:#6b4500;}.hdb-btn-add:hover{filter:brightness(1.07);}.hdb-btn-cart{background:#1A3C6E;color:#fff;display:none;}.hdb-btn-cart.hdb-show{display:inline-flex;}.hdb-btn-nav{background:#1A3C6E;color:#fff;}.hdb-cta .added_to_cart{display:none!important;}@media(max-width:1024px){.hdb-grid{grid-template-columns:repeat(2,1fr);}}@media(max-width:640px){.hdb-grid{grid-template-columns:1fr;}.hdb-wrap{padding:56px 16px;}}</style>';
	}

	// ── Markup ────────────────────────────────────────────────────
	echo '<section class="hdb-wrap"><div class="hdb-inner">';
	echo '<div class="hdb-top"><div><span class="hdb-eyebrow">🔥 Hot Deals</span><h2 class="hdb-h2">Our Most Popular Courses</h2><p class="hdb-sub">Hand-picked programmes trending among UK professionals — at exclusive prices.</p></div>';
	echo '<a href="' . esc_url( home_url( '/courses/' ) ) . '" class="hdb-link">View All Courses <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a></div>';
	echo '<div class="hdb-grid">';

	foreach ( $courses as $c ) {
		$stars = '';
		for ( $s = 1; $s <= 5; $s++ ) {
			$fill   = $s <= round( $c['rating_avg'] ) ? '#F4A726' : '#D1D5DB';
			$stars .= '<svg width="13" height="13" fill="' . $fill . '" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
		}

		echo '<article class="hdb-card">';
		echo '<a href="' . esc_url( $c['url'] ) . '" class="hdb-thumb" tabindex="-1" aria-hidden="true">';
		if ( $c['thumb'] ) {
			echo '<img src="' . esc_url( $c['thumb'] ) . '" alt="' . esc_attr( $c['title'] ) . '" width="400" height="170" loading="lazy">';
		} else {
			echo '<div class="hdb-ph"><svg width="48" height="48" fill="rgba(255,255,255,0.2)" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>';
		}
		if ( $c['cat_name'] )    { echo '<span class="hdb-cat">' . esc_html( $c['cat_name'] ) . '</span>'; }
		if ( $c['disc_pct'] > 0 ) { echo '<span class="hdb-disc">' . (int) $c['disc_pct'] . '% OFF</span>'; }
		echo '</a>';

		echo '<div class="hdb-body">';
		echo '<h3 class="hdb-title"><a href="' . esc_url( $c['url'] ) . '">' . esc_html( $c['title'] ) . '</a></h3>';
		echo '<div class="hdb-stars">' . $stars;
		echo '<strong style="color:#1A3C6E;">' . number_format( $c['rating_avg'], 1 ) . '</strong>';
		if ( $c['rating_cnt'] > 0 ) { echo '<span>(' . number_format( $c['rating_cnt'] ) . ')</span>'; }
		if ( $c['students']   > 0 ) { echo '<span style="margin-left:auto;">' . number_format( $c['students'] ) . ' students</span>'; }
		echo '</div>';

		echo '<div class="hdb-foot"><div>';
		if ( $c['effective'] > 0 ) {
			echo '<span class="hdb-price">£' . number_format( $c['effective'], 2 ) . '</span>';
			if ( $c['disc_pct'] > 0 ) { echo '<span class="hdb-orig">£' . number_format( $c['price'], 2 ) . '</span>'; }
		} else {
			echo '<span class="hdb-price">Free</span>';
		}
		echo '</div>';
		echo '<div class="hdb-cta" data-course="' . esc_attr( $c['id'] ) . '" data-pid="' . esc_attr( $c['wc_pid'] ) . '">';
		if ( $c['purchased'] ) {
			echo '<a href="' . esc_url( $c['dashboard_url'] ) . '" class="hdb-btn-nav">Go to Dashboard</a>';
		} elseif ( $c['can_add'] ) {
			echo '<a href="' . esc_url( $c['add_to_cart_url'] ) . '" class="ajax_add_to_cart add_to_cart_button hdb-btn-add" data-quantity="1" data-product_id="' . esc_attr( $c['wc_pid'] ) . '" rel="nofollow">Add to Cart</a>';
			echo '<a href="' . esc_url( $c['cart_url'] ) . '" class="hdb-btn-cart">View Cart →</a>';
		} else {
			echo '<a href="' . esc_url( $c['url'] ) . '" class="hdb-btn-nav">View Course</a>';
		}
		echo '</div></div></div></article>';
	}

	echo '</div></div></section>';

	// WC add-to-cart event handler
	echo '<script>(function(){if(typeof jQuery==="undefined"){return;}jQuery(document.body).on("added_to_cart.hdb",function(e,f,h,$btn){var pid=parseInt($btn.data("product_id"),10);if(!pid){return;}document.querySelectorAll(".hdb-cta[data-pid=\""+pid+"\"]").forEach(function(w){var a=w.querySelector(".hdb-btn-add"),v=w.querySelector(".hdb-btn-cart"),wc=w.querySelector(".added_to_cart");if(a)a.style.display="none";if(v)v.classList.add("hdb-show");if(wc)wc.remove();});});new MutationObserver(function(mm){mm.forEach(function(m){m.addedNodes.forEach(function(n){if(n.nodeType!==1)return;var w=n.closest?n.closest(".hdb-cta"):null;if(w&&n.classList&&n.classList.contains("added_to_cart")){n.remove();var v=w.querySelector(".hdb-btn-cart");if(v)v.classList.add("hdb-show");var a=w.querySelector(".hdb-btn-add");if(a)a.style.display="none";}});});}).observe(document.body,{childList:true,subtree:true});})();</script>';
}