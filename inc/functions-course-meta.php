<?php
/**
 * SPOKE COURSE DISPLAY DATA — inc/functions-course-meta.php
 *
 * Why no meta box: Tutor LMS uses a custom React course builder
 * (?page=create-course). The add_meta_boxes hook only fires on the
 * classic WP post editor, so a meta box never appears in Tutor's UI.
 *
 * Solution: a dedicated WP Admin page (WP Admin → Tutor LMS → ⭐ Display Data)
 * that lists every published course with inline editable fields.
 *
 * Priority rule applied everywhere in the theme:
 *   fake field has a value  →  show fake value
 *   fake field is empty     →  show real Tutor LMS value
 *
 * Public API:
 *   spoke_get_course_display_data( int $post_id ) : array {
 *       rating_avg, rating_cnt, students, has_fake
 *   }
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. HELPER — called by archive, hot-deals, and single-course
// ─────────────────────────────────────────────────────────────────

if ( ! function_exists( 'spoke_get_course_display_data' ) ) {
	function spoke_get_course_display_data( int $post_id ): array {

		$fake_rating   = get_post_meta( $post_id, '_spoke_fake_rating',   true );
		$fake_reviews  = get_post_meta( $post_id, '_spoke_fake_reviews',  true );
		$fake_students = get_post_meta( $post_id, '_spoke_fake_students', true );

		$has_rating   = ( $fake_rating   !== '' && $fake_rating   !== false );
		$has_reviews  = ( $fake_reviews  !== '' && $fake_reviews  !== false );
		$has_students = ( $fake_students !== '' && $fake_students !== false );

		$real_avg      = 0.0;
		$real_reviews  = 0;
		$real_students = 0;

		if ( ( ! $has_rating || ! $has_reviews || ! $has_students ) && function_exists( 'tutor_utils' ) ) {
			$rd            = tutor_utils()->get_course_rating( $post_id );
			$real_avg      = round( (float) ( $rd->rating_avg   ?? 0 ), 1 );
			$real_reviews  = (int)          ( $rd->rating_count ?? 0 );
			$real_students = (int) tutor_utils()->count_enrolled_users_by_course( $post_id );
		}

		return [
			'rating_avg' => $has_rating   ? round( (float) $fake_rating,   1 ) : $real_avg,
			'rating_cnt' => $has_reviews  ? (int)          $fake_reviews       : $real_reviews,
			'students'   => $has_students ? (int)          $fake_students      : $real_students,
			'has_fake'   => $has_rating || $has_reviews || $has_students,
		];
	}
}


// ─────────────────────────────────────────────────────────────────
// 2. REGISTER ADMIN PAGE — appears under Tutor LMS in the sidebar
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
	add_submenu_page(
		'tutor',
		'Course Display Data',
		'⭐ Display Data',
		'manage_options',
		'spoke-course-display-data',
		'spoke_course_display_data_page'
	);
} );


// ─────────────────────────────────────────────────────────────────
// 3. HANDLE SAVE (PRG pattern — redirect after POST)
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_init', function (): void {

	if ( ! isset( $_POST['spoke_cdd_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce(
		sanitize_text_field( wp_unslash( $_POST['spoke_cdd_nonce'] ) ),
		'spoke_save_display_data'
	) ) {
		wp_die( 'Security check failed.' );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$ratings  = isset( $_POST['spoke_rating'] )   ? (array) $_POST['spoke_rating']   : [];
	$reviews  = isset( $_POST['spoke_reviews'] )  ? (array) $_POST['spoke_reviews']  : [];
	$students = isset( $_POST['spoke_students'] ) ? (array) $_POST['spoke_students'] : [];

	$all_ids = get_posts( [
		'post_type'      => 'courses',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
	] );

	foreach ( $all_ids as $post_id ) {
		$id = (int) $post_id;

		$raw_r = isset( $ratings[ $id ] )  ? trim( sanitize_text_field( $ratings[ $id ] ) )  : '';
		if ( $raw_r === '' ) { delete_post_meta( $id, '_spoke_fake_rating' ); }
		else { update_post_meta( $id, '_spoke_fake_rating', round( min( 5.0, max( 0.0, (float) $raw_r ) ), 1 ) ); }

		$raw_rv = isset( $reviews[ $id ] ) ? trim( sanitize_text_field( $reviews[ $id ] ) )  : '';
		if ( $raw_rv === '' ) { delete_post_meta( $id, '_spoke_fake_reviews' ); }
		else { update_post_meta( $id, '_spoke_fake_reviews', max( 0, (int) $raw_rv ) ); }

		$raw_s = isset( $students[ $id ] ) ? trim( sanitize_text_field( $students[ $id ] ) ) : '';
		if ( $raw_s === '' ) { delete_post_meta( $id, '_spoke_fake_students' ); }
		else { update_post_meta( $id, '_spoke_fake_students', max( 0, (int) $raw_s ) ); }
	}

	wp_safe_redirect( admin_url( 'admin.php?page=spoke-course-display-data&saved=1' ) );
	exit;
} );


// ─────────────────────────────────────────────────────────────────
// 4. RENDER THE PAGE
// ─────────────────────────────────────────────────────────────────

function spoke_course_display_data_page(): void {

	if ( ! current_user_can( 'manage_options' ) ) { return; }

	// ── Query params ──────────────────────────────────────────────
	$per_page   = 15;
	$search_raw = isset( $_GET['cdd_search'] ) ? sanitize_text_field( wp_unslash( $_GET['cdd_search'] ) ) : '';
	$filter     = isset( $_GET['cdd_filter'] ) ? sanitize_key( $_GET['cdd_filter'] ) : 'all';
	$cur_page   = max( 1, (int) ( $_GET['cdd_page'] ?? 1 ) );
	$saved      = isset( $_GET['saved'] ) && sanitize_key( $_GET['saved'] ) === '1';

	// Base URL for links (preserves search/filter, resets page)
	$base_url = admin_url( 'admin.php?page=spoke-course-display-data' );
	if ( $search_raw ) { $base_url = add_query_arg( 'cdd_search', urlencode( $search_raw ), $base_url ); }
	if ( $filter !== 'all' ) { $base_url = add_query_arg( 'cdd_filter', $filter, $base_url ); }

	// ── Query: get all matching course IDs first ──────────────────
	$query_args = [
		'post_type'      => 'courses',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	];
	if ( $search_raw ) {
		$query_args['s'] = $search_raw;
	}

	$all_ids = get_posts( $query_args );

	// ── Filter: "overrides only" or "real only" ───────────────────
	if ( $filter === 'overrides' ) {
		$all_ids = array_filter( $all_ids, function( $id ) {
			return get_post_meta( $id, '_spoke_fake_rating',   true ) !== '' ||
			       get_post_meta( $id, '_spoke_fake_reviews',  true ) !== '' ||
			       get_post_meta( $id, '_spoke_fake_students', true ) !== '';
		} );
		$all_ids = array_values( $all_ids );
	} elseif ( $filter === 'real' ) {
		$all_ids = array_filter( $all_ids, function( $id ) {
			return get_post_meta( $id, '_spoke_fake_rating',   true ) === '' &&
			       get_post_meta( $id, '_spoke_fake_reviews',  true ) === '' &&
			       get_post_meta( $id, '_spoke_fake_students', true ) === '';
		} );
		$all_ids = array_values( $all_ids );
	}

	$total_courses = count( $all_ids );
	$total_pages   = max( 1, (int) ceil( $total_courses / $per_page ) );
	$cur_page      = min( $cur_page, $total_pages );
	$offset        = ( $cur_page - 1 ) * $per_page;
	$page_ids      = array_slice( $all_ids, $offset, $per_page );

	// Fetch full post objects only for this page
	$courses = empty( $page_ids ) ? [] : get_posts( [
		'post_type'      => 'courses',
		'post__in'       => $page_ids,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
	] );

	// Count overrides across ALL courses (for the filter badge)
	$all_course_ids   = get_posts( [ 'post_type' => 'courses', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ] );
	$override_count   = count( array_filter( $all_course_ids, function( $id ) {
		return get_post_meta( $id, '_spoke_fake_rating', true )   !== '' ||
		       get_post_meta( $id, '_spoke_fake_reviews', true )  !== '' ||
		       get_post_meta( $id, '_spoke_fake_students', true ) !== '';
	} ) );

	// ── Pagination link builder ───────────────────────────────────
	$page_link = function( int $p ) use ( $base_url ): string {
		return esc_url( add_query_arg( 'cdd_page', $p, $base_url ) );
	};
	?>
	<div class="wrap">

	<h1 style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">⭐ Course Display Data</h1>
	<p style="color:#50575e;margin-top:0;max-width:680px;">
		Override the <strong>star rating</strong>, <strong>review count</strong>, and <strong>student count</strong> shown on course cards, the archive page, and single course pages.<br>
		Leave a field <strong>empty</strong> = real Tutor&nbsp;LMS data. Enter a number = override everywhere.
	</p>

	<?php if ( $saved ) : ?>
	<div class="notice notice-success is-dismissible" style="margin-bottom:16px;"><p>✅ Display data saved successfully.</p></div>
	<?php endif; ?>

	<style>
	/* ── Layout ── */
	#cdd-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin:16px 0 12px;max-width:1040px;}
	#cdd-search-box{display:flex;gap:6px;flex:1;min-width:220px;max-width:420px;}
	#cdd-search-input{flex:1;height:36px;padding:0 12px;border:1px solid #ccd0d4;border-radius:4px;font-size:14px;}
	#cdd-search-input:focus{border-color:#1A3C6E;box-shadow:0 0 0 1px #1A3C6E;outline:none;}
	#cdd-search-btn{height:36px;padding:0 14px;background:#1A3C6E;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;}
	#cdd-search-btn:hover{filter:brightness(1.15);}
	.cdd-clear-link{font-size:12px;color:#888;text-decoration:none;align-self:center;}
	.cdd-clear-link:hover{color:#1A3C6E;}
	/* Filter tabs */
	.cdd-filter-tabs{display:flex;gap:4px;flex-wrap:wrap;}
	.cdd-filter-tab{height:34px;padding:0 14px;border-radius:4px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;border:1px solid rgba(0,0,0,0.12);color:#43474f;background:#fff;}
	.cdd-filter-tab:hover{background:#f0f4ff;color:#1A3C6E;border-color:#1A3C6E;}
	.cdd-filter-tab.active{background:#1A3C6E;color:#fff;border-color:#1A3C6E;}
	.cdd-tab-count{font-size:11px;font-weight:700;padding:1px 5px;border-radius:10px;background:rgba(255,255,255,0.25);}
	.cdd-filter-tab:not(.active) .cdd-tab-count{background:rgba(0,0,0,0.08);color:#43474f;}
	/* Summary bar */
	#cdd-summary{font-size:13px;color:#555;margin-bottom:8px;max-width:1040px;display:flex;align-items:center;justify-content:space-between;}
	/* Table */
	#cdd-wrap table{border-collapse:collapse;width:100%;max-width:1040px;}
	#cdd-wrap th{background:#1A3C6E;color:#fff;padding:10px 14px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;}
	#cdd-wrap td{padding:10px 14px;border-bottom:1px solid #f0f0f1;vertical-align:middle;background:#fff;}
	#cdd-wrap tr:hover td{background:#f9fbfe;}
	#cdd-wrap input[type=number]{width:96px;height:34px;padding:0 8px;border:1px solid #ccd0d4;border-radius:4px;font-size:14px;}
	#cdd-wrap input[type=number]:focus{border-color:#1A3C6E;box-shadow:0 0 0 1px #1A3C6E;outline:none;}
	.cdd-badge{display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:3px;text-transform:uppercase;letter-spacing:.05em;vertical-align:middle;margin-left:4px;}
	.cdd-fake{background:#fef3c7;color:#92400e;}
	.cdd-real{background:#dcfce7;color:#166534;}
	.cdd-thumb{width:48px;height:36px;object-fit:cover;border-radius:4px;vertical-align:middle;}
	.cdd-ph{width:48px;height:36px;background:#e0e4ea;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;vertical-align:middle;}
	.cdd-name{font-weight:600;color:#1A3C6E;font-size:14px;}
	.cdd-sub{font-size:11px;color:#888;margin-top:2px;}
	/* No results */
	.cdd-no-results{text-align:center;padding:48px 0;color:#666;font-size:15px;}
	/* Save button */
	#cdd-save-row{display:flex;align-items:center;gap:16px;margin-top:16px;max-width:1040px;}
	#cdd-save{height:40px;padding:0 28px;background:#F4A726;color:#6b4500;border:none;border-radius:6px;font-size:15px;font-weight:700;cursor:pointer;}
	#cdd-save:hover{filter:brightness(1.07);}
	.cdd-save-note{font-size:12px;color:#888;}
	/* Pagination */
	#cdd-pagination{display:flex;align-items:center;gap:4px;margin-top:16px;flex-wrap:wrap;}
	.cdd-page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid rgba(0,0,0,0.1);color:#43474f;background:#fff;}
	.cdd-page-btn:hover{background:#1A3C6E;color:#fff;border-color:#1A3C6E;}
	.cdd-page-btn.current{background:#1A3C6E;color:#fff;border-color:#1A3C6E;cursor:default;}
	.cdd-page-btn.disabled{opacity:0.35;pointer-events:none;}
	.cdd-page-dots{padding:0 6px;color:#aaa;}
	.cdd-per-page-label{font-size:12px;color:#888;margin-left:12px;}
	/* Search highlight */
	mark.cdd-hl{background:#fef9c3;color:inherit;padding:0 1px;border-radius:2px;}
	</style>

	<!-- ── Toolbar: search + filter tabs ── -->
	<div id="cdd-toolbar">

		<form method="get" id="cdd-search-box" action="">
			<input type="hidden" name="page" value="spoke-course-display-data">
			<?php if ( $filter !== 'all' ) : ?><input type="hidden" name="cdd_filter" value="<?php echo esc_attr( $filter ); ?>"><?php endif; ?>
			<input type="text" id="cdd-search-input" name="cdd_search"
				   value="<?php echo esc_attr( $search_raw ); ?>"
				   placeholder="Search courses by name or ID…"
				   autocomplete="off">
			<button type="submit" id="cdd-search-btn">Search</button>
			<?php if ( $search_raw ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'cdd_filter' => $filter ], $base_url ) ); ?>" class="cdd-clear-link">✕ Clear</a>
			<?php endif; ?>
		</form>

		<div class="cdd-filter-tabs">
			<?php
			$tabs = [
				'all'       => [ 'All Courses', count( $all_course_ids ) ],
				'overrides' => [ '⚡ Overrides',  $override_count ],
				'real'      => [ '✓ Real Data',   count( $all_course_ids ) - $override_count ],
			];
			foreach ( $tabs as $slug => [ $label, $count ] ) :
				$tab_url = esc_url( add_query_arg( [ 'cdd_filter' => $slug, 'cdd_page' => 1 ], $search_raw ? add_query_arg( 'cdd_search', urlencode( $search_raw ), admin_url( 'admin.php?page=spoke-course-display-data' ) ) : admin_url( 'admin.php?page=spoke-course-display-data' ) ) );
			?>
			<a href="<?php echo $tab_url; ?>" class="cdd-filter-tab <?php echo $filter === $slug ? 'active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
				<span class="cdd-tab-count"><?php echo (int) $count; ?></span>
			</a>
			<?php endforeach; ?>
		</div>

	</div>

	<!-- ── Summary bar ── -->
	<div id="cdd-summary">
		<span>
			Showing <strong><?php echo count( $courses ); ?></strong> of
			<strong><?php echo $total_courses; ?></strong> course<?php echo $total_courses !== 1 ? 's' : ''; ?>
			<?php if ( $search_raw ) : ?> matching "<em><?php echo esc_html( $search_raw ); ?></em>"<?php endif; ?>
			<?php if ( $total_pages > 1 ) : ?> &mdash; page <?php echo $cur_page; ?> of <?php echo $total_pages; ?><?php endif; ?>
		</span>
		<?php if ( $override_count > 0 ) : ?>
		<span style="font-size:12px;color:#92400e;background:#fef3c7;padding:2px 8px;border-radius:4px;">
			<?php echo $override_count; ?> course<?php echo $override_count !== 1 ? 's' : ''; ?> with overrides active
		</span>
		<?php endif; ?>
	</div>

	<!-- ── Main form ── -->
	<form method="post" id="cdd-wrap" action="">
		<?php wp_nonce_field( 'spoke_save_display_data', 'spoke_cdd_nonce' ); ?>

		<?php if ( empty( $courses ) ) : ?>
		<div class="cdd-no-results">
			<p>🔍 No courses found<?php echo $search_raw ? ' for "<strong>' . esc_html( $search_raw ) . '</strong>"' : ''; ?>.</p>
			<?php if ( $search_raw || $filter !== 'all' ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=spoke-course-display-data' ) ); ?>" style="color:#1A3C6E;">Clear all filters</a>
			<?php endif; ?>
		</div>
		<?php else : ?>

		<table>
			<thead>
				<tr>
					<th style="width:54px;"></th>
					<th>Course</th>
					<th>⭐ Star Rating <span style="font-weight:400;text-transform:none;letter-spacing:0;">(0–5)</span></th>
					<th>💬 Review Count</th>
					<th>👥 Student Count</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $courses as $course ) :
				$id = (int) $course->ID;

				$fake_r  = get_post_meta( $id, '_spoke_fake_rating',   true );
				$fake_rv = get_post_meta( $id, '_spoke_fake_reviews',  true );
				$fake_s  = get_post_meta( $id, '_spoke_fake_students', true );

				$real_avg = $real_rv = $real_s = '—';
				if ( function_exists( 'tutor_utils' ) ) {
					$rd       = tutor_utils()->get_course_rating( $id );
					$real_avg = number_format( (float) ( $rd->rating_avg   ?? 0 ), 1 );
					$real_rv  = number_format( (int)   ( $rd->rating_count ?? 0 ) );
					$real_s   = number_format( (int) tutor_utils()->count_enrolled_users_by_course( $id ) );
				}

				$thumb  = get_the_post_thumbnail_url( $id, 'thumbnail' );
				$has_r  = ( $fake_r  !== '' && $fake_r  !== false );
				$has_rv = ( $fake_rv !== '' && $fake_rv !== false );
				$has_s  = ( $fake_s  !== '' && $fake_s  !== false );

				// Highlight search term in course name
				$display_title = esc_html( $course->post_title );
				if ( $search_raw ) {
					$display_title = preg_replace(
						'/(' . preg_quote( esc_html( $search_raw ), '/' ) . ')/i',
						'<mark class="cdd-hl">$1</mark>',
						$display_title
					);
				}
			?>
			<tr>
				<td>
					<?php if ( $thumb ) : ?>
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cdd-thumb">
					<?php else : ?>
						<span class="cdd-ph">🎓</span>
					<?php endif; ?>
				</td>
				<td>
					<div class="cdd-name"><?php echo $display_title; ?></div>
					<div class="cdd-sub">ID: <?php echo $id; ?></div>
				</td>
				<td>
					<input type="number" name="spoke_rating[<?php echo $id; ?>]"
						   value="<?php echo esc_attr( $fake_r ); ?>"
						   min="0" max="5" step="0.1" placeholder="e.g. 4.7">
					<span class="cdd-badge <?php echo $has_r ? 'cdd-fake' : 'cdd-real'; ?>"><?php echo $has_r ? 'Override' : 'Real'; ?></span>
					<div class="cdd-sub">Tutor: <?php echo esc_html( $real_avg ); ?></div>
				</td>
				<td>
					<input type="number" name="spoke_reviews[<?php echo $id; ?>]"
						   value="<?php echo esc_attr( $fake_rv ); ?>"
						   min="0" step="1" placeholder="e.g. 1243">
					<span class="cdd-badge <?php echo $has_rv ? 'cdd-fake' : 'cdd-real'; ?>"><?php echo $has_rv ? 'Override' : 'Real'; ?></span>
					<div class="cdd-sub">Tutor: <?php echo esc_html( $real_rv ); ?></div>
				</td>
				<td>
					<input type="number" name="spoke_students[<?php echo $id; ?>]"
						   value="<?php echo esc_attr( $fake_s ); ?>"
						   min="0" step="1" placeholder="e.g. 5800">
					<span class="cdd-badge <?php echo $has_s ? 'cdd-fake' : 'cdd-real'; ?>"><?php echo $has_s ? 'Override' : 'Real'; ?></span>
					<div class="cdd-sub">Tutor: <?php echo esc_html( $real_s ); ?></div>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<!-- ── Save row ── -->
		<div id="cdd-save-row">
			<button type="submit" id="cdd-save">💾 Save All Display Data</button>
			<span class="cdd-save-note">Saves changes on <em>all</em> pages, not just this one. Clear a field to revert to real Tutor LMS data.</span>
		</div>

		<!-- ── Pagination ── -->
		<?php if ( $total_pages > 1 ) : ?>
		<div id="cdd-pagination">

			<!-- Prev -->
			<a href="<?php echo $cur_page > 1 ? $page_link( $cur_page - 1 ) : '#'; ?>"
			   class="cdd-page-btn <?php echo $cur_page <= 1 ? 'disabled' : ''; ?>">← Prev</a>

			<?php
			// Smart pagination: always show first, last, and a window around current
			$window  = 2;
			$pages   = [];
			for ( $p = 1; $p <= $total_pages; $p++ ) {
				if ( $p === 1 || $p === $total_pages || abs( $p - $cur_page ) <= $window ) {
					$pages[] = $p;
				}
			}
			$prev_p = null;
			foreach ( $pages as $p ) :
				if ( $prev_p !== null && $p - $prev_p > 1 ) :
					echo '<span class="cdd-page-dots">…</span>';
				endif;
				$prev_p = $p;
			?>
			<a href="<?php echo $p === $cur_page ? '#' : $page_link( $p ); ?>"
			   class="cdd-page-btn <?php echo $p === $cur_page ? 'current' : ''; ?>"><?php echo $p; ?></a>
			<?php endforeach; ?>

			<!-- Next -->
			<a href="<?php echo $cur_page < $total_pages ? $page_link( $cur_page + 1 ) : '#'; ?>"
			   class="cdd-page-btn <?php echo $cur_page >= $total_pages ? 'disabled' : ''; ?>">Next →</a>

			<span class="cdd-per-page-label"><?php echo $per_page; ?> per page</span>
		</div>
		<?php endif; ?>

		<?php endif; // end !empty( $courses ) ?>

	</form>

	</div>
	<?php
}