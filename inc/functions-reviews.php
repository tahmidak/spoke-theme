<?php
/**
 * SPOKE COURSE REVIEWS — inc/functions-reviews.php
 *
 * Tutor LMS FREE stores reviews in wp_comments:
 *   comment_type      = 'tutor_course_rating'
 *   comment_post_ID   = course post ID
 *   comment_approved  = '0' (pending) | '1' (approved) | 'trash' (rejected)
 *   commentmeta key   = '_tutor_rating'  →  value 1–5
 *
 * DO NOT query wp_tutor_ratings — that table is Tutor LMS Pro only.
 *
 * Add ONE line to functions.php:
 *   require_once get_template_directory() . '/inc/functions-reviews.php';
 *
 * @package SpokeTheme
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SPOKE_REVIEWS_PER_PAGE', 20 );

// ─────────────────────────────────────────────────────────────────
// 1. ADMIN MENU
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function (): void {
	add_submenu_page(
		'tutor',
		'Course Reviews',
		'⭐ Reviews',
		'manage_options',
		'spoke-reviews',
		'spoke_reviews_admin_page'
	);
} );


// ─────────────────────────────────────────────────────────────────
// 2. HANDLE ACTIONS (PRG — redirect after every write)
// ─────────────────────────────────────────────────────────────────

add_action( 'admin_init', function (): void {

	if ( empty( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'spoke-reviews' ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = sanitize_key( $_REQUEST['action'] ?? '' );
	$tab    = sanitize_key( $_REQUEST['tab']    ?? 'pending' );
	$paged  = max( 1, (int) ( $_REQUEST['paged'] ?? 1 ) );
	$base   = admin_url( 'admin.php?page=spoke-reviews' );

	// ── Save settings ─────────────────────────────────────────────
	if ( 'save_settings' === $action ) {
		check_admin_referer( 'spoke_reviews_settings' );
		update_option( 'spoke_reviews_auto_approve', isset( $_POST['auto_approve'] ) ? 1 : 0 );
		wp_safe_redirect( add_query_arg( [ 'tab' => 'settings', 'saved' => 1 ], $base ) );
		exit;
	}

	// ── Single row action ─────────────────────────────────────────
	if ( in_array( $action, [ 'approve', 'reject', 'delete' ], true ) ) {
		$id = (int) ( $_GET['comment_id'] ?? 0 );
		if ( ! $id ) { return; }
		check_admin_referer( 'sr_' . $action . '_' . $id );
		_spoke_reviews_apply( $action, [ $id ] );
		wp_safe_redirect( add_query_arg( [ 'tab' => $tab, 'paged' => $paged, 'done' => $action ], $base ) );
		exit;
	}

	// ── Inline edit save ──────────────────────────────────────────
	if ( 'edit_save' === $action ) {
		check_admin_referer( 'sr_edit_save' );
		$id     = (int) ( $_POST['comment_id'] ?? 0 );
		$rating = min( 5, max( 1, (int) ( $_POST['rating'] ?? 3 ) ) );
		$text   = sanitize_textarea_field( wp_unslash( $_POST['review'] ?? '' ) );
		if ( $id ) {
			wp_update_comment( [ 'comment_ID' => $id, 'comment_content' => $text ] );
			update_comment_meta( $id, '_tutor_rating', $rating );
		}
		wp_safe_redirect( add_query_arg( [ 'tab' => sanitize_key( $_POST['tab'] ?? 'all' ), 'paged' => (int) ( $_POST['paged'] ?? 1 ), 'done' => 'edited' ], $base ) );
		exit;
	}

	// ── Bulk action ───────────────────────────────────────────────
	if ( 'bulk' === $action ) {
		check_admin_referer( 'sr_bulk' );
		$bulk = sanitize_key( $_POST['bulk_action'] ?? '' );
		$ids  = array_map( 'intval', (array) ( $_POST['comment_ids'] ?? [] ) );
		if ( $ids && in_array( $bulk, [ 'approve', 'reject', 'delete' ], true ) ) {
			_spoke_reviews_apply( $bulk, $ids );
		}
		wp_safe_redirect( add_query_arg( [ 'tab' => $tab, 'paged' => $paged, 'done' => 'bulk_' . $bulk ], $base ) );
		exit;
	}
} );


// ─────────────────────────────────────────────────────────────────
// 3. APPLY ACTION HELPER
// ─────────────────────────────────────────────────────────────────

function _spoke_reviews_apply( string $action, array $ids ): void {
	global $wpdb;
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( ! $id ) { continue; }
		if ( 'approve' === $action ) {
			// Store as 'approved' — that's what Tutor LMS reads
			$wpdb->update( $wpdb->comments, [ 'comment_approved' => 'approved' ], [ 'comment_ID' => $id ] );
		} elseif ( 'reject' === $action ) {
			// Store as 'hold' — pending / held state
			$wpdb->update( $wpdb->comments, [ 'comment_approved' => 'hold' ], [ 'comment_ID' => $id ] );
		} elseif ( 'delete' === $action ) {
			wp_delete_comment( $id, true );
		}
	}
}


// ─────────────────────────────────────────────────────────────────
// 4. AUTO-APPROVE NEW REVIEWS
// ─────────────────────────────────────────────────────────────────

// Tutor LMS inserts reviews as comments with comment_approved = 0.
// We hook into comment insertion to flip it to 1 when auto-approve is on.
add_action( 'comment_post', function ( int $comment_id, $comment_approved, array $data ): void {
	if ( ( $data['comment_type'] ?? '' ) !== 'tutor_course_rating' ) {
		return;
	}
	if ( ! get_option( 'spoke_reviews_auto_approve', 0 ) ) {
		return;
	}
	wp_set_comment_status( $comment_id, 'approve' );
}, 10, 3 );


// ─────────────────────────────────────────────────────────────────
// 5. DATA HELPERS
// ─────────────────────────────────────────────────────────────────

/**
 * Map UI tab name to the actual comment_approved values stored in the DB.
 *
 * Tutor LMS Free stores comment_approved as:
 *   'approved' → approved reviews
 *   'hold'     → pending (submitted but not approved yet)
 *   'trash'    → trashed / rejected
 *   '0'        → sometimes used by older Tutor versions for pending
 *
 * We query directly via SQL so we match whatever Tutor actually stored.
 */
function _spoke_review_approved_values( string $tab ): array {
	return match( $tab ) {
		'pending'  => [ 'hold', '0' ],   // Tutor stores pending as 'hold' or '0'
		'approved' => [ 'approved', '1' ], // Tutor stores approved as 'approved' or '1'
		'rejected' => [ 'trash' ],
		default    => [],                  // empty = no WHERE clause = all
	};
}

/**
 * Fetch Tutor LMS reviews directly from wp_comments via raw SQL.
 * We bypass get_comments() because Tutor LMS stores comment_approved
 * as the string 'hold' / 'approved' instead of the integers 0/1 that
 * WordPress's own API expects.
 *
 * @param array $args  status|search|course_id|paged|per_page
 * @return array { comments: array, total: int }
 */
function spoke_reviews_get( array $args = [] ): array {
	global $wpdb;

	$tab      = sanitize_key( $args['status']    ?? 'all' );
	$search   = sanitize_text_field( $args['search']    ?? '' );
	$course   = (int) ( $args['course_id'] ?? 0 );
	$paged    = max( 1, (int) ( $args['paged']     ?? 1 ) );
	$per_page = (int) ( $args['per_page']  ?? SPOKE_REVIEWS_PER_PAGE );
	$offset   = ( $paged - 1 ) * $per_page;

	$where  = [ "c.comment_type = 'tutor_course_rating'" ];
	$params = [];

	// Status filter — match exactly what Tutor stored
	$approved_values = _spoke_review_approved_values( $tab );
	if ( ! empty( $approved_values ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $approved_values ), '%s' ) );
		$where[]      = "c.comment_approved IN ($placeholders)";
		$params       = array_merge( $params, $approved_values );
	}

	if ( $course ) {
		$where[]  = 'c.comment_post_ID = %d';
		$params[] = $course;
	}

	if ( $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where[]  = '(c.comment_author LIKE %s OR c.comment_author_email LIKE %s OR c.comment_content LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	$where_sql = implode( ' AND ', $where );

	$count_sql = "SELECT COUNT(*) FROM {$wpdb->comments} c WHERE {$where_sql}";
	$data_sql  = "SELECT c.*, p.post_title AS course_title
	              FROM {$wpdb->comments} c
	              LEFT JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
	              WHERE {$where_sql}
	              ORDER BY c.comment_date DESC
	              LIMIT %d OFFSET %d";

	if ( $params ) {
		$total    = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$comments = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ) );
	} else {
		$total    = (int) $wpdb->get_var( $count_sql );
		$comments = $wpdb->get_results( $wpdb->prepare( $data_sql, $per_page, $offset ) );
	}

	return [
		'comments' => $comments ?: [],
		'total'    => $total,
	];
}

/**
 * Count reviews per status tab — direct SQL to match actual stored values.
 */
function spoke_reviews_counts(): array {
	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT comment_approved, COUNT(*) AS cnt
		 FROM {$wpdb->comments}
		 WHERE comment_type = 'tutor_course_rating'
		 GROUP BY comment_approved"
	) ?: [];

	$counts = [ 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0 ];

	foreach ( $rows as $row ) {
		$cnt = (int) $row->cnt;
		$counts['all'] += $cnt;

		if ( in_array( $row->comment_approved, [ 'hold', '0' ], true ) ) {
			$counts['pending'] += $cnt;
		} elseif ( in_array( $row->comment_approved, [ 'approved', '1' ], true ) ) {
			$counts['approved'] += $cnt;
		} elseif ( $row->comment_approved === 'trash' ) {
			$counts['rejected'] += $cnt;
		}
	}

	return $counts;
}

/**
 * Get distinct courses that have at least one review comment.
 */
function spoke_reviews_get_courses(): array {
	global $wpdb;
	return $wpdb->get_results(
		"SELECT DISTINCT c.comment_post_ID AS course_id, p.post_title
		 FROM {$wpdb->comments} c
		 LEFT JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
		 WHERE c.comment_type = 'tutor_course_rating'
		 ORDER BY p.post_title ASC"
	) ?: [];
}

/**
 * Build SVG star row HTML.
 */
function spoke_reviews_stars( int $rating, int $size = 14 ): string {
	$path = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
	$html = '<span style="display:inline-flex;gap:1px;">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$fill = $i <= $rating ? '#F4A726' : '#D1D5DB';
		$html .= "<svg width='{$size}' height='{$size}' fill='{$fill}' viewBox='0 0 20 20'><path d='{$path}'/></svg>";
	}
	return $html . '</span>';
}


// ─────────────────────────────────────────────────────────────────
// 6. ADMIN PAGE RENDER
// ─────────────────────────────────────────────────────────────────

function spoke_reviews_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$tab          = sanitize_key( $_GET['tab']    ?? 'pending' );
	$paged        = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
	$search       = sanitize_text_field( wp_unslash( $_GET['s']      ?? '' ) );
	$course       = (int) ( $_GET['course'] ?? 0 );
	$done         = sanitize_key( $_GET['done']   ?? '' );
	$saved        = ! empty( $_GET['saved'] );
	$base         = admin_url( 'admin.php?page=spoke-reviews' );
	$auto_approve = (int) get_option( 'spoke_reviews_auto_approve', 0 );
	$counts       = spoke_reviews_counts();
	$courses      = spoke_reviews_get_courses();

	$data         = ( $tab !== 'settings' )
		? spoke_reviews_get( [ 'status' => $tab, 'search' => $search, 'course_id' => $course, 'paged' => $paged ] )
		: [ 'comments' => [], 'total' => 0 ];

	$comments    = $data['comments'];
	$total       = $data['total'];
	$total_pages = max( 1, (int) ceil( $total / SPOKE_REVIEWS_PER_PAGE ) );

	$done_labels = [
		'approve'      => '✅ Review approved.',
		'reject'       => '🚫 Review moved to rejected.',
		'delete'       => '🗑️ Review permanently deleted.',
		'edited'       => '✏️ Review updated.',
		'bulk_approve' => '✅ Selected reviews approved.',
		'bulk_reject'  => '🚫 Selected reviews rejected.',
		'bulk_delete'  => '🗑️ Selected reviews deleted.',
	];

	?>
	<style>
	#sr-wrap{max-width:1200px;font-family:'Inter',sans-serif;}
	#sr-wrap h1{display:flex;align-items:center;gap:8px;margin-bottom:4px;}
	.sr-tabs{display:flex;flex-wrap:wrap;gap:4px;margin:16px 0 0;}
	.sr-tab{display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 14px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid rgba(0,0,0,0.1);color:#43474f;background:#fff;}
	.sr-tab:hover{background:#f0f4ff;color:#1A3C6E;border-color:#1A3C6E;}
	.sr-tab.active{background:#1A3C6E;color:#fff;border-color:#1A3C6E;}
	.sr-cnt{font-size:11px;font-weight:700;padding:1px 6px;border-radius:10px;background:rgba(255,255,255,0.25);}
	.sr-tab:not(.active) .sr-cnt{background:rgba(0,0,0,0.08);color:#43474f;}
	.sr-cnt-pending{background:#F4A726!important;color:#6b4500!important;}
	#sr-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:16px 0 10px;}
	#sr-search-box{display:flex;gap:6px;flex:1;min-width:240px;max-width:380px;}
	#sr-search-input{flex:1;height:36px;padding:0 12px;border:1px solid #ccd0d4;border-radius:4px;font-size:13px;}
	#sr-search-input:focus{border-color:#1A3C6E;box-shadow:0 0 0 1px #1A3C6E;outline:none;}
	#sr-search-btn{height:36px;padding:0 14px;background:#1A3C6E;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;}
	.sr-sel{height:36px;padding:0 10px;border:1px solid #ccd0d4;border-radius:4px;font-size:13px;background:#fff;}
	.sr-clear{font-size:12px;color:#888;text-decoration:none;align-self:center;}
	.sr-clear:hover{color:#1A3C6E;}
	.sr-bulk-bar{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
	#sr-wrap table{border-collapse:collapse;width:100%;}
	#sr-wrap th{background:#1A3C6E;color:#fff;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;}
	#sr-wrap td{padding:10px 14px;border-bottom:1px solid #f0f0f1;background:#fff;vertical-align:top;font-size:13px;}
	#sr-wrap tr:hover td{background:#f9fbfe;}
	.sr-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
	.sr-badge-pending{background:#fef3c7;color:#92400e;}
	.sr-badge-approved{background:#dcfce7;color:#166534;}
	.sr-badge-rejected{background:#fee2e2;color:#991b1b;}
	.sr-acts{display:flex;flex-wrap:wrap;gap:5px;}
	.sr-btn{display:inline-flex;align-items:center;height:28px;padding:0 10px;border-radius:4px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid;cursor:pointer;font-family:inherit;}
	.sr-btn:hover{filter:brightness(1.08);}
	.sr-btn-approve{background:#dcfce7;color:#166534;border-color:#bbf7d0;}
	.sr-btn-reject{background:#fef3c7;color:#92400e;border-color:#fde68a;}
	.sr-btn-delete{background:#fee2e2;color:#991b1b;border-color:#fecaca;}
	.sr-btn-edit{background:rgba(26,60,110,0.08);color:#1A3C6E;border-color:rgba(26,60,110,0.2);}
	.sr-edit-form{display:none;margin-top:10px;padding:12px;background:#f8f9fa;border-radius:6px;border:1px solid #e2e5ea;}
	.sr-edit-form.open{display:block;}
	.sr-edit-form label{font-size:11px;font-weight:700;text-transform:uppercase;color:#43474f;display:block;margin-bottom:4px;}
	.sr-edit-form textarea{width:100%;height:80px;padding:8px;border:1px solid #ccd0d4;border-radius:4px;font-size:13px;font-family:inherit;resize:vertical;}
	.sr-reviewer strong{font-size:13px;color:#1A3C6E;display:block;}
	.sr-reviewer span{font-size:11px;color:#888;}
	#sr-summary{font-size:13px;color:#555;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;}
	#sr-pagination{display:flex;align-items:center;gap:4px;margin-top:14px;flex-wrap:wrap;}
	.sr-pg{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid rgba(0,0,0,0.1);color:#43474f;background:#fff;}
	.sr-pg:hover{background:#1A3C6E;color:#fff;border-color:#1A3C6E;}
	.sr-pg.current{background:#1A3C6E;color:#fff;border-color:#1A3C6E;cursor:default;}
	.sr-pg.disabled{opacity:.35;pointer-events:none;}
	.sr-settings-card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:24px;max-width:580px;margin-top:14px;}
	.sr-toggle-row{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-top:1px solid #f0f0f1;}
	.sr-no-results{text-align:center;padding:48px;color:#666;}
	.sr-review-text{max-width:320px;}
	.sr-review-text p{margin:4px 0 0;line-height:1.5;color:#43474f;word-break:break-word;}
	</style>

	<div class="wrap" id="sr-wrap">
	<h1>⭐ Course Reviews</h1>
	<p style="color:#50575e;margin-top:0;max-width:640px;">Manage student reviews. Approve, reject, edit, or delete. Reviews stored in WordPress comments (Tutor LMS Free).</p>

	<?php if ( $done && isset( $done_labels[$done] ) ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $done_labels[$done] ); ?></p></div>
	<?php endif; ?>
	<?php if ( $saved ) : ?>
	<div class="notice notice-success is-dismissible"><p>✅ Settings saved.</p></div>
	<?php endif; ?>

	<!-- TABS -->
	<div class="sr-tabs" role="tablist">
	<?php
	$tab_defs = [
		'pending'  => [ 'Pending',    $counts['pending'],  true  ],
		'approved' => [ 'Approved',   $counts['approved'], false ],
		'rejected' => [ 'Rejected',   $counts['rejected'], false ],
		'all'      => [ 'All',        $counts['all'],      false ],
		'settings' => [ '⚙ Settings', null,                false ],
	];
	foreach ( $tab_defs as $slug => [ $label, $count, $highlight ] ) :
		$url = esc_url( add_query_arg( [ 'tab' => $slug, 'paged' => 1 ], $base ) );
	?>
	<a href="<?php echo $url; ?>" class="sr-tab <?php echo $tab === $slug ? 'active' : ''; ?>" role="tab">
		<?php echo esc_html( $label ); ?>
		<?php if ( $count !== null ) : ?>
		<span class="sr-cnt <?php echo ( $highlight && $count > 0 && $tab === $slug ) ? 'sr-cnt-pending' : ''; ?>">
			<?php echo (int) $count; ?>
		</span>
		<?php endif; ?>
	</a>
	<?php endforeach; ?>
	</div>

	<?php if ( $tab === 'settings' ) : ?>

	<!-- SETTINGS TAB -->
	<div class="sr-settings-card">
		<h2 style="font-size:15px;font-weight:700;color:#1A3C6E;margin:0 0 4px;">Review Settings</h2>
		<form method="post">
			<?php wp_nonce_field( 'spoke_reviews_settings' ); ?>
			<input type="hidden" name="action" value="save_settings">
			<input type="hidden" name="page"   value="spoke-reviews">
			<input type="hidden" name="tab"    value="settings">
			<div class="sr-toggle-row">
				<input type="checkbox" id="auto_approve" name="auto_approve" value="1"
				       style="width:18px;height:18px;accent-color:#1A3C6E;margin-top:2px;flex-shrink:0;"
				       <?php checked( $auto_approve, 1 ); ?>>
				<label for="auto_approve" style="font-size:14px;color:#43474f;cursor:pointer;">
					<strong style="color:#1A3C6E;">Auto-approve new reviews</strong><br>
					<span style="font-size:12px;color:#888;">When enabled, student reviews are immediately visible on the course page without manual approval.</span>
				</label>
			</div>
			<button type="submit" style="margin-top:14px;height:40px;padding:0 24px;background:#F4A726;color:#6b4500;border:none;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;">
				💾 Save Settings
			</button>
		</form>
	</div>

	<?php else : ?>

	<!-- TOOLBAR -->
	<div id="sr-toolbar">
		<form method="get" id="sr-search-box">
			<input type="hidden" name="page" value="spoke-reviews">
			<input type="hidden" name="tab"  value="<?php echo esc_attr( $tab ); ?>">
			<?php if ( $course ) : ?><input type="hidden" name="course" value="<?php echo $course; ?>"><?php endif; ?>
			<input type="text" id="sr-search-input" name="s"
			       value="<?php echo esc_attr( $search ); ?>"
			       placeholder="Search by name, email, or review text…"
			       autocomplete="off">
			<button type="submit" id="sr-search-btn">Search</button>
			<?php if ( $search ) : ?>
			<a href="<?php echo esc_url( add_query_arg( [ 'tab' => $tab ], $base ) ); ?>" class="sr-clear">✕ Clear</a>
			<?php endif; ?>
		</form>

		<?php if ( ! empty( $courses ) ) : ?>
		<form method="get">
			<input type="hidden" name="page" value="spoke-reviews">
			<input type="hidden" name="tab"  value="<?php echo esc_attr( $tab ); ?>">
			<?php if ( $search ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
			<select name="course" class="sr-sel" onchange="this.form.submit()">
				<option value="">All Courses</option>
				<?php foreach ( $courses as $c ) : ?>
				<option value="<?php echo esc_attr( $c->course_id ); ?>" <?php selected( $course, $c->course_id ); ?>>
					<?php echo esc_html( $c->post_title ?: 'Course #' . $c->course_id ); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</form>
		<?php endif; ?>
	</div>

	<!-- SUMMARY -->
	<div id="sr-summary">
		<span>
			Showing <strong><?php echo count( $comments ); ?></strong> of
			<strong><?php echo $total; ?></strong> review<?php echo $total !== 1 ? 's' : ''; ?>
			<?php if ( $search ) : ?> for "<em><?php echo esc_html( $search ); ?></em>"<?php endif; ?>
			<?php if ( $total_pages > 1 ) : ?> — page <?php echo $paged; ?> of <?php echo $total_pages; ?><?php endif; ?>
		</span>
		<?php if ( $counts['pending'] > 0 ) : ?>
		<span style="font-size:12px;color:#92400e;background:#fef3c7;padding:2px 10px;border-radius:4px;">
			<?php echo $counts['pending']; ?> awaiting approval
		</span>
		<?php endif; ?>
	</div>

	<?php if ( empty( $comments ) ) : ?>
	<div class="sr-no-results">
		<p style="font-size:15px;font-weight:500;">No reviews found<?php echo $search ? ' for "' . esc_html( $search ) . '"' : ''; ?>.</p>
		<?php if ( $search || $course ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', $tab, $base ) ); ?>" style="color:#1A3C6E;">Clear filters</a>
		<?php endif; ?>
	</div>
	<?php else : ?>

	<!-- BULK FORM -->
	<form method="post" id="sr-bulk-form">
		<?php wp_nonce_field( 'sr_bulk' ); ?>
		<input type="hidden" name="action" value="bulk">
		<input type="hidden" name="tab"    value="<?php echo esc_attr( $tab ); ?>">
		<input type="hidden" name="paged"  value="<?php echo $paged; ?>">
		<input type="hidden" name="page"   value="spoke-reviews">

		<div class="sr-bulk-bar">
			<label style="font-size:13px;font-weight:600;color:#43474f;">Bulk:</label>
			<select name="bulk_action" class="sr-sel">
				<option value="">— Select action —</option>
				<option value="approve">Approve</option>
				<option value="reject">Reject</option>
				<option value="delete">Delete</option>
			</select>
			<button type="submit" style="height:34px;padding:0 14px;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;background:#f0f0f0;"
			        onclick="return confirm('Apply to all selected reviews?')">Apply</button>
			<label style="font-size:12px;color:#888;cursor:pointer;margin-left:6px;">
				<input type="checkbox" id="sr-check-all" style="accent-color:#1A3C6E;margin-right:3px;">
				Select all
			</label>
		</div>

		<table>
			<thead>
				<tr>
					<th style="width:32px;"><input type="checkbox" id="sr-check-all-th" title="Select all"></th>
					<th>Reviewer</th>
					<th>Course</th>
					<th>Rating</th>
					<th>Review</th>
					<th>Date</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $comments as $comment ) :
				$cid     = (int) $comment->comment_ID;
				$rating  = max( 1, min( 5, (int) get_comment_meta( $cid, '_tutor_rating', true ) ) );
				$text    = $comment->comment_content ?? '';
				$name    = $comment->comment_author  ?? 'Unknown';
				$email   = $comment->comment_author_email ?? '';
				$date    = date( 'j M Y', strtotime( $comment->comment_date ) );
				$course_id = (int) $comment->comment_post_ID;
				$course_title = get_the_title( $course_id ) ?: 'Course #' . $course_id;

				// Map comment_approved to our UI status label.
				// Tutor LMS Free stores 'approved' or '1' for approved,
				// 'hold' or '0' for pending, 'trash' for rejected.
				$approved = $comment->comment_approved;
				if ( in_array( $approved, [ 'approved', '1' ], true ) ) {
					$status_label = 'Approved';
					$badge_cls    = 'sr-badge sr-badge-approved';
				} elseif ( $approved === 'trash' ) {
					$status_label = 'Rejected';
					$badge_cls    = 'sr-badge sr-badge-rejected';
				} else {
					// 'hold', '0', or anything else = pending
					$status_label = 'Pending';
					$badge_cls    = 'sr-badge sr-badge-pending';
				}

				$approve_url = wp_nonce_url( add_query_arg( [ 'action' => 'approve', 'comment_id' => $cid, 'tab' => $tab, 'paged' => $paged ], $base ), 'sr_approve_' . $cid );
				$reject_url  = wp_nonce_url( add_query_arg( [ 'action' => 'reject',  'comment_id' => $cid, 'tab' => $tab, 'paged' => $paged ], $base ), 'sr_reject_'  . $cid );
				$delete_url  = wp_nonce_url( add_query_arg( [ 'action' => 'delete',  'comment_id' => $cid, 'tab' => $tab, 'paged' => $paged ], $base ), 'sr_delete_'  . $cid );

				$truncated  = mb_strlen( $text ) > 120;
				$short_text = $truncated ? esc_html( mb_substr( $text, 0, 120 ) ) . '…' : esc_html( $text );
			?>
			<tr>
				<td><input type="checkbox" name="comment_ids[]" value="<?php echo $cid; ?>" style="accent-color:#1A3C6E;"></td>

				<td>
					<div class="sr-reviewer">
						<strong><?php echo esc_html( $name ); ?></strong>
						<span><?php echo esc_html( $email ); ?></span>
					</div>
				</td>

				<td style="max-width:160px;">
					<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" target="_blank"
					   style="color:#1A3C6E;font-size:13px;text-decoration:none;"
					   onmouseover="this.style.textDecoration='underline'"
					   onmouseout="this.style.textDecoration='none'">
						<?php echo esc_html( $course_title ); ?>
					</a>
				</td>

				<td style="white-space:nowrap;">
					<?php echo spoke_reviews_stars( $rating ); ?>
					<span style="font-size:12px;font-weight:700;color:#1A3C6E;margin-left:3px;"><?php echo $rating; ?>/5</span>
				</td>

				<td class="sr-review-text">
					<p><?php echo $short_text; ?></p>
					<!-- Inline edit -->
					<div class="sr-edit-form" id="sr-edit-<?php echo $cid; ?>">
						<form method="post">
							<?php wp_nonce_field( 'sr_edit_save' ); ?>
							<input type="hidden" name="action"     value="edit_save">
							<input type="hidden" name="page"       value="spoke-reviews">
							<input type="hidden" name="tab"        value="<?php echo esc_attr( $tab ); ?>">
							<input type="hidden" name="paged"      value="<?php echo $paged; ?>">
							<input type="hidden" name="comment_id" value="<?php echo $cid; ?>">
							<label>Rating (1–5)</label>
							<div style="display:flex;gap:6px;margin-bottom:8px;">
								<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
								<label style="cursor:pointer;">
									<input type="radio" name="rating" value="<?php echo $s; ?>" <?php checked( $rating, $s ); ?> style="display:none;">
									<svg width="22" height="22" fill="<?php echo $s <= $rating ? '#F4A726' : '#D1D5DB'; ?>" viewBox="0 0 20 20">
										<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
									</svg>
								</label>
								<?php endfor; ?>
							</div>
							<label>Review Text</label>
							<textarea name="review"><?php echo esc_textarea( $text ); ?></textarea>
							<div style="display:flex;gap:8px;margin-top:8px;">
								<button type="submit" style="height:32px;padding:0 14px;background:#F4A726;color:#6b4500;border:none;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">💾 Save</button>
								<button type="button" onclick="document.getElementById('sr-edit-<?php echo $cid; ?>').classList.remove('open')"
								        style="height:32px;padding:0 12px;background:#fff;color:#43474f;border:1px solid #ccd0d4;border-radius:4px;font-size:13px;cursor:pointer;">Cancel</button>
							</div>
						</form>
					</div>
				</td>

				<td style="white-space:nowrap;font-size:12px;color:#6b7280;"><?php echo esc_html( $date ); ?></td>

				<td><span class="<?php echo esc_attr( $badge_cls ); ?>"><?php echo esc_html( $status_label ); ?></span></td>

				<td>
					<div class="sr-acts">
						<?php if ( ! in_array( $approved, [ 'approved', '1' ], true ) ) : ?>
						<a href="<?php echo esc_url( $approve_url ); ?>" class="sr-btn sr-btn-approve">✓ Approve</a>
						<?php endif; ?>
						<?php if ( $approved !== 'trash' ) : ?>
						<a href="<?php echo esc_url( $reject_url ); ?>" class="sr-btn sr-btn-reject">✕ Reject</a>
						<?php endif; ?>
						<button type="button" class="sr-btn sr-btn-edit"
						        onclick="document.getElementById('sr-edit-<?php echo $cid; ?>').classList.toggle('open')">
							✏️ Edit
						</button>
						<a href="<?php echo esc_url( $delete_url ); ?>" class="sr-btn sr-btn-delete"
						   onclick="return confirm('Permanently delete this review?')">🗑️</a>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</form>

	<!-- PAGINATION -->
	<?php if ( $total_pages > 1 ) :
		$pl = function( int $p ) use ( $base, $tab, $paged, $search, $course ): string {
			return esc_url( add_query_arg( [ 'tab' => $tab, 'paged' => $p, 's' => $search ?: '', 'course' => $course ?: '' ], $base ) );
		};
		$pages = [];
		for ( $p = 1; $p <= $total_pages; $p++ ) {
			if ( $p === 1 || $p === $total_pages || abs( $p - $paged ) <= 2 ) { $pages[] = $p; }
		}
	?>
	<div id="sr-pagination">
		<a href="<?php echo $paged > 1 ? $pl( $paged - 1 ) : '#'; ?>"
		   class="sr-pg <?php echo $paged <= 1 ? 'disabled' : ''; ?>">← Prev</a>
		<?php $prev = null; foreach ( $pages as $p ) :
			if ( $prev !== null && $p - $prev > 1 ) echo '<span style="padding:0 4px;color:#aaa;">…</span>';
			$prev = $p; ?>
		<a href="<?php echo $p === $paged ? '#' : $pl( $p ); ?>"
		   class="sr-pg <?php echo $p === $paged ? 'current' : ''; ?>"><?php echo $p; ?></a>
		<?php endforeach; ?>
		<a href="<?php echo $paged < $total_pages ? $pl( $paged + 1 ) : '#'; ?>"
		   class="sr-pg <?php echo $paged >= $total_pages ? 'disabled' : ''; ?>">Next →</a>
	</div>
	<?php endif; ?>

	<?php endif; // empty/not empty ?>
	<?php endif; // settings tab ?>

	</div><!-- /wrap -->
	<script>
	(function(){
		['sr-check-all','sr-check-all-th'].forEach(function(id){
			var el = document.getElementById(id);
			if(!el) return;
			el.addEventListener('change', function(){
				document.querySelectorAll('#sr-bulk-form input[name="comment_ids[]"]').forEach(function(cb){ cb.checked = el.checked; });
				var other = document.getElementById(id==='sr-check-all'?'sr-check-all-th':'sr-check-all');
				if(other) other.checked = el.checked;
			});
		});
	})();
	</script>
	<?php
}