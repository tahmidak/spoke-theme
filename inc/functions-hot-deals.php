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
	if ( empty( $deal_ids ) ) {
		return;
	}
 
	$user_id   = get_current_user_id();
	$is_logged = $user_id > 0;
 
	// ── Pre-fetch purchased product IDs (one query, not N) ────────
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
			if ( ! $order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof WC_Order_Item_Product ) {
					$pid = (int) $item->get_product_id();
					if ( $pid ) {
						$bought_pids[] = $pid;
					}
				}
			}
		}
		$bought_pids = array_unique( $bought_pids );
	}
 
	// ── Collect card data, preserving admin-curated order ─────────
	$cards = [];
	$loop  = new WP_Query( [
		'post_type'      => 'courses',
		'post__in'       => $deal_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => 12,
		'post_status'    => 'publish',
	] );
 
	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			$card = spoke_get_card_data( get_the_ID() );
			if ( ! $card ) {
				continue;
			}
 
			// Apply pre-fetched purchase status.
			if ( $is_logged && ! $card['purchased'] && $card['wc_product_id'] && in_array( $card['wc_product_id'], $bought_pids, true ) ) {
				$card['purchased'] = true;
			}
 
			$cards[] = $card;
		}
		wp_reset_postdata();
	}
 
	if ( empty( $cards ) ) {
		return;
	}
 
	// ── Print shared card CSS once ─────────────────────────────────
	spoke_enqueue_card_styles();
 
	// ── Section-level CSS (unique to hot-deals section) ───────────
	static $hd_css_printed = false;
	if ( ! $hd_css_printed ) {
		$hd_css_printed = true;
		echo '<style>
.hdb-wrap { background:#f3f4f5; padding:80px 24px; font-family:"Inter",sans-serif; }
.hdb-inner { max-width:1280px; margin:0 auto; }
.hdb-top { display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:40px; }
.hdb-eyebrow { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; padding:4px 12px; border-radius:100px; background:rgba(244,167,38,.15); border:1px solid rgba(244,167,38,.35); color:#F4A726; margin-bottom:10px; }
.hdb-h2 { font-size:clamp(1.75rem,3vw,2.25rem); font-weight:700; color:#1A3C6E; letter-spacing:-.04em; margin:0 0 8px; }
.hdb-sub { font-size:15px; color:#43474f; margin:0; }
.hdb-link { display:inline-flex; align-items:center; gap:6px; font-size:15px; font-weight:700; color:#1A3C6E; text-decoration:none; white-space:nowrap; }
.hdb-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
@media(max-width:1024px) { .hdb-grid { grid-template-columns:repeat(2,1fr); } }
@media(max-width:640px)  { .hdb-grid { grid-template-columns:1fr; } .hdb-wrap { padding:56px 16px; } }
</style>';
	}
 
	// ── Markup ────────────────────────────────────────────────────
	echo '<section class="hdb-wrap"><div class="hdb-inner">';
	echo '<div class="hdb-top">';
	echo '<div>';
	echo '<span class="hdb-eyebrow">🔥 Hot Deals</span>';
	echo '<h2 class="hdb-h2">Our Most Popular Courses</h2>';
	echo '<p class="hdb-sub">Hand-picked programmes trending among UK professionals — at exclusive prices.</p>';
	echo '</div>';
	echo '<a href="' . esc_url( home_url( '/courses/' ) ) . '" class="hdb-link">';
	echo 'View All Courses ';
	echo '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
	echo '</a>';
	echo '</div>';
 
	// Grid — use shared card with hot-deals context.
	echo '<div class="hdb-grid">';
	foreach ( $cards as $card ) {
		echo spoke_get_course_card_html( $card, [
			'context'    => 'hot-deals',
			'img_height' => 170,
			'lazy'       => true,
		] );
	}
	echo '</div>';
 
	echo '</div></section>';
 
	// Print the shared add-to-cart JS (once per page).
	spoke_print_card_atc_script();
}