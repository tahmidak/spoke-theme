<?php
/**
 * comments.php — Spoke Theme
 *
 * Required by WordPress since 3.0.0.
 * Renders the comments section on blog posts (single.html).
 * Styled to match the Spoke Design System.
 *
 * @package SpokeTheme
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area" style="margin-top:2rem;">

	<?php if ( have_comments() ) : ?>

		<h2 class="comments-title" style="font-size:1.375rem;font-weight:700;color:#1A3C6E;margin:0 0 1.5rem;letter-spacing:-0.02em;">
			<?php
			$comment_count = get_comments_number();
			printf(
				esc_html( _n( '%s Comment', '%s Comments', $comment_count, 'spoke-theme' ) ),
				'<span>' . number_format_i18n( $comment_count ) . '</span>'
			);
			?>
		</h2>

		<ol class="comment-list" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:1rem;">
			<?php
			wp_list_comments( [
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 48,
				'callback'    => 'spoke_comment_template',
			] );
			?>
		</ol>

		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
		<nav class="comment-navigation" style="margin-top:1.5rem;display:flex;justify-content:space-between;">
			<div><?php previous_comments_link( '← ' . esc_html__( 'Older Comments', 'spoke-theme' ) ); ?></div>
			<div><?php next_comments_link( esc_html__( 'Newer Comments', 'spoke-theme' ) . ' →' ); ?></div>
		</nav>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments" style="font-size:0.9375rem;color:#43474f;background:#f3f4f5;border-radius:8px;padding:1rem 1.25rem;margin:0;">
			<?php esc_html_e( 'Comments are closed.', 'spoke-theme' ); ?>
		</p>
	<?php endif; ?>

	<?php
	comment_form( [
		'title_reply'          => esc_html__( 'Leave a Comment', 'spoke-theme' ),
		'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title" style="font-size:1.25rem;font-weight:700;color:#1A3C6E;margin:2rem 0 1rem;letter-spacing:-0.02em;">',
		'title_reply_after'    => '</h3>',
		'cancel_reply_before'  => ' &mdash; ',
		'cancel_reply_after'   => '',
		'label_submit'         => esc_html__( 'Post Comment', 'spoke-theme' ),
		'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" style="height:48px;padding:0 28px;background:#1A3C6E;color:#fff;font-family:inherit;font-size:15px;font-weight:700;border:none;border-radius:8px;cursor:pointer;transition:filter 150ms ease;" onmouseenter="this.style.filter=\'brightness(1.12)\'" onmouseleave="this.style.filter=\'\'">',
		'fields'               => [
			'author' => '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">'
				. '<div><label for="author" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#43474f;margin-bottom:6px;">'
				. esc_html__( 'Name', 'spoke-theme' )
				. ( $req ? ' <span style="color:#F4A726;">*</span>' : '' )
				. '</label>'
				. '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"'
				. ( $req ? ' required' : '' )
				. ' style="width:100%;height:48px;padding:0 16px;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;" autocomplete="name">'
				. '</div>',

			'email'  => '<div><label for="email" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#43474f;margin-bottom:6px;">'
				. esc_html__( 'Email', 'spoke-theme' )
				. ( $req ? ' <span style="color:#F4A726;">*</span>' : '' )
				. '</label>'
				. '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"'
				. ( $req ? ' required' : '' )
				. ' style="width:100%;height:48px;padding:0 16px;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;" autocomplete="email">'
				. '</div></div>',

			'url'    => '',
			'cookies' => '<div style="margin-bottom:1rem;"><label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;color:#43474f;">'
				. '<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . ( isset( $_COOKIE['comment_author_' . COOKIEHASH] ) ? ' checked' : '' ) . ' style="width:16px;height:16px;accent-color:#1A3C6E;flex-shrink:0;">'
				. esc_html__( 'Save my name and email in this browser for the next time I comment.', 'spoke-theme' )
				. '</label></div>',
		],
		'comment_field' => '<div style="margin-bottom:1rem;"><label for="comment" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#43474f;margin-bottom:6px;">'
			. esc_html__( 'Comment', 'spoke-theme' )
			. ' <span style="color:#F4A726;">*</span></label>'
			. '<textarea id="comment" name="comment" rows="6" required style="width:100%;padding:14px 16px;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;resize:vertical;box-sizing:border-box;"></textarea></div>',
		'class_form'    => 'comment-form',
		'class_submit'  => 'submit',
	] );
	?>

</div>

<?php
/**
 * Custom comment template callback.
 * Renders each comment with the Spoke Design System styling.
 *
 * @param WP_Comment $comment
 * @param array      $args
 * @param int        $depth
 */
function spoke_comment_template( WP_Comment $comment, array $args, int $depth ): void {
	$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
	?>
	<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment-item', $comment ); ?>
		style="background:#fff;border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

		<div style="display:flex;align-items:flex-start;gap:14px;">

			<div style="flex-shrink:0;">
				<?php echo get_avatar( $comment, 48, '', '', [ 'class' => 'comment-avatar', 'style' => 'width:48px;height:48px;border-radius:8px;object-fit:cover;' ] ); ?>
			</div>

			<div style="flex:1;min-width:0;">
				<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:0.5rem;">
					<span style="font-size:14px;font-weight:700;color:#1A3C6E;">
						<?php comment_author(); ?>
					</span>
					<time datetime="<?php comment_date( DATE_W3C ); ?>"
						  style="font-size:12px;color:#6b7280;">
						<?php comment_date( 'j M Y' ); ?>
					</time>
					<?php if ( '0' === $comment->comment_approved ) : ?>
					<span style="font-size:11px;font-weight:700;text-transform:uppercase;padding:2px 8px;border-radius:4px;background:rgba(244,167,38,0.15);color:#92400e;">
						<?php esc_html_e( 'Awaiting moderation', 'spoke-theme' ); ?>
					</span>
					<?php endif; ?>
				</div>

				<div class="comment-content" style="font-size:14px;line-height:1.7;color:#43474f;">
					<?php comment_text(); ?>
				</div>

				<div style="margin-top:0.75rem;">
					<?php
					comment_reply_link( array_merge( $args, [
						'add_below' => 'comment',
						'depth'     => $depth,
						'max_depth' => $args['max_depth'],
						'before'    => '<span style="font-size:13px;font-weight:600;color:#1A3C6E;cursor:pointer;">',
						'after'     => '</span>',
					] ) );
					?>
				</div>
			</div>

		</div>

	</<?php echo $tag; ?>>
	<?php
}
