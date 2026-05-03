<?php
/**
 * SPOKE BLOG ARCHIVE — inc/functions-blog-archive.php
 *
 * Provides [spoke_blog_archive] shortcode used in archive.html.
 * All PHP lives here — never inside .html block templates which
 * cannot execute PHP.
 *
 * Add ONE line to functions.php alongside the other requires:
 *   require_once get_template_directory() . '/inc/functions-blog-archive.php';
 *
 * Then update archive.html to:
 *   <!-- wp:template-part {"slug":"header","tagName":"header"} /-->
 *   <!-- wp:html -->
 *   [spoke_blog_archive]
 *   <!-- /wp:html -->
 *   <!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. SUPPRESS wpautop SO IT DOESN'T INJECT <br> INTO OUR OUTPUT
// ─────────────────────────────────────────────────────────────────

add_filter( 'the_content', function ( string $content ): string {
	if ( has_shortcode( $content, 'spoke_blog_archive' ) ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'wptexturize' );
	}
	return $content;
}, 9 );

add_filter( 'the_content', 'shortcode_unautop', 10 );


// ─────────────────────────────────────────────────────────────────
// 2. SHORTCODE — [spoke_blog_archive]
// ─────────────────────────────────────────────────────────────────

add_shortcode( 'spoke_blog_archive', function (): string {
	ob_start();
	spoke_render_blog_archive();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	return $html;
} );


// ─────────────────────────────────────────────────────────────────
// 3. RENDER_BLOCK INTERCEPTOR
//    Intercepts the core/html block that contains [spoke_blog_archive]
//    and renders the PHP directly — same pattern as spoke_course_archive.
// ─────────────────────────────────────────────────────────────────

add_filter( 'render_block', function ( string $block_content, array $block ): string {
	if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/html' ) {
		return $block_content;
	}
	$raw = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
	if ( false === strpos( $raw, 'spoke_blog_archive' ) ) {
		return $block_content;
	}
	ob_start();
	spoke_render_blog_archive();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	$html = preg_replace( '/(\s*\n){3,}/', "\n", $html );
	return $html;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 4. MAIN RENDER FUNCTION
// ─────────────────────────────────────────────────────────────────

function spoke_render_blog_archive(): void {
	?>
	<style>
		.post-card { transition: transform 200ms ease, box-shadow 200ms ease; }
		.post-card:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(26,60,110,0.10); }
		.cat-pill { transition: background 150ms ease, color 150ms ease; }
		.cat-pill:hover { background: #1A3C6E !important; color: #fff !important; }
		.search-field { flex:1; height:42px; padding:0 14px; background:#f3f4f5; border:1px solid rgba(0,0,0,0.1); border-radius:8px; font-family:inherit; font-size:14px; color:#191c1d; }
		.search-field:focus { outline:2px solid #F4A726; outline-offset:1px; border-color:transparent; }
		.search-submit { height:42px; padding:0 14px; background:#1A3C6E; color:#fff; border:none; border-radius:8px; font-family:inherit; font-size:13px; font-weight:700; cursor:pointer; }
		.search-submit:hover { filter:brightness(1.15); }
		.page-numbers { display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:8px; font-size:13px; font-weight:600; color:#43474f; border:1px solid rgba(0,0,0,0.1); text-decoration:none; margin:0 2px; transition:background 150ms,color 150ms; }
		.page-numbers.current, .page-numbers:hover { background:#1A3C6E; border-color:#1A3C6E; color:#fff; }
		.page-numbers.dots { border:none; cursor:default; }
		ul.page-numbers { display:flex; align-items:center; list-style:none; margin:0; padding:0; }
		ul.page-numbers li { display:inline-flex; }
	</style>

	<?php
	// ── HERO HEADER ───────────────────────────────────────────────
	?>
	<div class="py-12 lg:py-16 px-4 sm:px-8" style="background:linear-gradient(135deg,#1A3C6E 0%,#1A1A2E 100%)">
		<div class="max-w-[1280px] mx-auto">
			<nav class="flex items-center gap-2 text-[13px] mb-4" aria-label="Breadcrumb">
				<a href="/" class="hover:text-white transition-colors" style="color:rgba(255,255,255,0.55)">Home</a>
				<span style="color:rgba(255,255,255,0.25)">/</span>
				<span class="font-medium" style="color:#F4A726">
					<?php
					if ( is_category() )        echo 'Category: ' . single_cat_title( '', false );
					elseif ( is_tag() )         echo 'Tag: ' . single_tag_title( '', false );
					elseif ( is_author() )      echo 'Author: ' . get_the_author();
					elseif ( is_date() )        echo get_the_date( 'F Y' );
					else                        echo 'Blog';
					?>
				</span>
			</nav>
			<h1 class="font-bold leading-tight tracking-[-1.2px] mb-3" style="font-size:clamp(1.75rem,4vw,2.75rem);color:#fff">
				<?php
				if ( is_category() )        single_cat_title();
				elseif ( is_tag() )         single_tag_title( 'Posts tagged: ' );
				elseif ( is_author() )      echo 'Posts by ' . get_the_author();
				elseif ( is_date() )        echo get_the_date( 'F Y' );
				else                        echo 'Blog &amp; Insights';
				?>
			</h1>
			<?php if ( ! is_category() && ! is_tag() && ! is_author() && ! is_date() ) : ?>
			<p class="text-[16px] max-w-lg m-0" style="color:rgba(255,255,255,0.65)">
				Career insights, study guides, and industry perspectives for UK professionals.
			</p>
			<?php endif; ?>
		</div>
	</div>

	<?php
	// ── CATEGORY PILLS ────────────────────────────────────────────
	$blog_page_url = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blogs/' );
	$categories    = get_categories( [ 'hide_empty' => true, 'number' => 8 ] );
	?>
	<div class="bg-white sticky top-[72px] z-20" style="border-bottom:1px solid rgba(196,198,208,0.2)">
		<div class="max-w-[1280px] mx-auto px-4 sm:px-8 py-3 flex gap-2 overflow-x-auto" role="navigation" aria-label="Filter by category">
			<a href="<?php echo esc_url( $blog_page_url ); ?>"
			   class="cat-pill shrink-0 h-9 px-4 rounded-lg text-[13px] font-semibold no-underline"
			   style="<?php echo ! is_category() ? 'background:#1A3C6E;color:#fff;' : 'background:#f3f4f5;color:#43474f;'; ?>">
				All Posts
			</a>
			<?php foreach ( $categories as $cat ) :
				$active = is_category( $cat->term_id );
			?>
			<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"
			   class="cat-pill shrink-0 h-9 px-4 rounded-lg text-[13px] font-medium no-underline"
			   style="<?php echo $active ? 'background:#1A3C6E;color:#fff;' : 'background:#f3f4f5;color:#43474f;'; ?>">
				<?php echo esc_html( $cat->name ); ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php
	// ── MAIN CONTENT ──────────────────────────────────────────────
	?>
	<main class="max-w-[1280px] mx-auto px-4 sm:px-8 py-12 lg:py-16">
		<div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-12">

			<!-- POST COLUMN -->
			<div>
				<?php if ( have_posts() ) : ?>

				<?php
				// ── Featured first post ───────────────────────────────────
				the_post();
				$f_cats  = get_the_category();
				$f_thumb = get_post_thumbnail_id() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' ) : '';
				$f_read  = max( 1, ceil( str_word_count( strip_tags( get_the_content() ) ) / 200 ) );
				?>
				<article class="post-card bg-white rounded-xl overflow-hidden mb-8" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<?php if ( $f_thumb ) : ?>
					<a href="<?php the_permalink(); ?>" class="block overflow-hidden" style="max-height:340px">
						<img src="<?php echo esc_url( $f_thumb ); ?>"
						     alt="<?php echo esc_attr( get_the_title() ); ?>"
						     class="w-full object-cover hover:scale-105 transition-transform duration-500"
						     style="height:340px" loading="eager" width="800" height="340" />
					</a>
					<?php else : ?>
					<div class="flex items-center justify-center" style="height:220px;background:linear-gradient(135deg,#1A3C6E,#1A1A2E)">
						<svg class="w-16 h-16 opacity-20" fill="white" viewBox="0 0 24 24"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/></svg>
					</div>
					<?php endif; ?>

					<div class="p-6 sm:p-8">
						<div class="flex flex-wrap items-center gap-2 mb-3">
							<?php if ( $f_cats ) : ?>
							<a href="<?php echo esc_url( get_category_link( $f_cats[0]->term_id ) ); ?>"
							   class="text-[11px] font-bold uppercase tracking-[0.8px] px-2.5 py-1 rounded no-underline"
							   style="background:rgba(26,60,110,0.08);color:#1A3C6E">
								<?php echo esc_html( $f_cats[0]->name ); ?>
							</a>
							<?php endif; ?>
							<span class="text-[12px]" style="color:#43474f"><?php echo get_the_date( 'j M Y' ); ?></span>
							<span style="color:rgba(0,0,0,0.2)">·</span>
							<span class="text-[12px]" style="color:#43474f"><?php echo $f_read; ?> min read</span>
						</div>

						<h2 class="font-bold leading-tight tracking-[-0.5px] mb-3" style="font-size:clamp(1.25rem,2.5vw,1.75rem);color:#1A3C6E">
							<a href="<?php the_permalink(); ?>" class="no-underline hover:underline" style="color:inherit"><?php the_title(); ?></a>
						</h2>

						<p class="text-[15px] leading-relaxed mb-5" style="color:#43474f">
							<?php echo wp_trim_words( get_the_excerpt(), 30 ); ?>
						</p>

						<div class="flex items-center justify-between gap-4">
							<div class="flex items-center gap-2.5">
								<img src="<?php echo esc_url( get_avatar_url( get_the_author_meta( 'ID' ), [ 'size' => 40 ] ) ); ?>"
								     alt="<?php the_author(); ?>"
								     class="w-9 h-9 rounded-full object-cover"
								     width="36" height="36" />
								<p class="text-[13px] font-semibold m-0" style="color:#1A3C6E"><?php the_author(); ?></p>
							</div>
							<a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1.5 font-bold text-[14px] no-underline" style="color:#1A3C6E">
								Read Article
								<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
							</a>
						</div>
					</div>
				</article>

				<?php
				// ── Remaining posts grid ──────────────────────────────────
				if ( have_posts() ) :
				?>
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
					<?php while ( have_posts() ) : the_post();
						$p_cats  = get_the_category();
						$p_thumb = get_post_thumbnail_id() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'medium_large' ) : '';
						$p_read  = max( 1, ceil( str_word_count( strip_tags( get_the_content() ) ) / 200 ) );
					?>
					<article class="post-card bg-white rounded-xl overflow-hidden flex flex-col" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<?php if ( $p_thumb ) : ?>
						<a href="<?php the_permalink(); ?>" class="block overflow-hidden" style="height:180px">
							<img src="<?php echo esc_url( $p_thumb ); ?>"
							     alt="<?php echo esc_attr( get_the_title() ); ?>"
							     class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
							     loading="lazy" width="400" height="180" />
						</a>
						<?php else : ?>
						<div class="flex items-center justify-center" style="height:180px;background:linear-gradient(135deg,#1A3C6E,#1A1A2E)">
							<svg class="w-10 h-10 opacity-20" fill="white" viewBox="0 0 24 24"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/></svg>
						</div>
						<?php endif; ?>

						<div class="p-5 flex flex-col flex-1 gap-2">
							<div class="flex items-center gap-2 flex-wrap">
								<?php if ( $p_cats ) : ?>
								<a href="<?php echo esc_url( get_category_link( $p_cats[0]->term_id ) ); ?>"
								   class="text-[10px] font-bold uppercase tracking-[0.8px] px-2 py-0.5 rounded no-underline"
								   style="background:rgba(26,60,110,0.08);color:#1A3C6E">
									<?php echo esc_html( $p_cats[0]->name ); ?>
								</a>
								<?php endif; ?>
								<span class="text-[11px]" style="color:#6b7280"><?php echo $p_read; ?> min read</span>
							</div>

							<h2 class="font-bold text-[16px] leading-snug m-0" style="color:#1A3C6E">
								<a href="<?php the_permalink(); ?>" class="no-underline hover:underline" style="color:inherit"><?php the_title(); ?></a>
							</h2>

							<p class="text-[13px] leading-relaxed flex-1 m-0" style="color:#43474f">
								<?php echo wp_trim_words( get_the_excerpt(), 18 ); ?>
							</p>

							<div class="flex items-center justify-between gap-2 pt-3 mt-auto" style="border-top:1px solid rgba(0,0,0,0.06)">
								<span class="text-[12px]" style="color:#6b7280"><?php echo get_the_date( 'j M Y' ); ?></span>
								<a href="<?php the_permalink(); ?>" class="text-[13px] font-semibold no-underline hover:underline" style="color:#1A3C6E">Read →</a>
							</div>
						</div>
					</article>
					<?php endwhile; ?>
				</div>
				<?php endif; ?>

				<!-- Pagination -->
				<nav class="mt-10 flex justify-center" aria-label="Posts pagination">
					<?php echo paginate_links( [ 'prev_text' => '← Prev', 'next_text' => 'Next →', 'type' => 'list' ] ); ?>
				</nav>

				<?php else : ?>
				<!-- No posts found -->
				<div class="text-center py-20 bg-white rounded-xl" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<svg class="w-14 h-14 mx-auto mb-4 opacity-20" fill="none" stroke="#1A3C6E" stroke-width="1.5" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
					</svg>
					<h2 class="font-bold text-[20px] mb-2" style="color:#1A3C6E">No posts found</h2>
					<p class="text-[15px] mb-5" style="color:#43474f">Check back soon — new insights are on the way.</p>
					<a href="/" class="inline-flex items-center h-11 px-6 rounded-lg font-bold text-[14px] no-underline" style="background:#F4A726;color:#6b4500">Back to Home</a>
				</div>
				<?php endif; ?>
			</div><!-- /post column -->

			<!-- SIDEBAR -->
			<aside class="flex flex-col gap-6">

				<!-- Search -->
				<div class="bg-white rounded-xl p-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<h3 class="font-bold text-[16px] mb-4 pb-3" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">Search</h3>
					<?php get_search_form(); ?>
				</div>

				<!-- Categories -->
				<div class="bg-white rounded-xl p-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<h3 class="font-bold text-[16px] mb-4 pb-3" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">Categories</h3>
					<ul class="flex flex-col gap-1 list-none m-0 p-0">
						<?php foreach ( get_categories( [ 'hide_empty' => true ] ) as $cat ) : ?>
						<li>
							<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"
							   class="flex items-center justify-between py-2 px-3 rounded-lg no-underline text-[14px] hover:bg-[#f3f4f5] transition-colors"
							   style="color:#43474f">
								<?php echo esc_html( $cat->name ); ?>
								<span class="text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(26,60,110,0.07);color:#1A3C6E">
									<?php echo (int) $cat->count; ?>
								</span>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<!-- Recent Posts -->
				<div class="bg-white rounded-xl p-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<h3 class="font-bold text-[16px] mb-4 pb-3" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">Recent Posts</h3>
					<ul class="flex flex-col gap-3 list-none m-0 p-0">
						<?php foreach ( get_posts( [ 'numberposts' => 5, 'post_status' => 'publish' ] ) as $rp ) :
							$rp_thumb = get_the_post_thumbnail_url( $rp->ID, 'thumbnail' );
						?>
						<li class="flex items-start gap-3">
							<?php if ( $rp_thumb ) : ?>
							<a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>" class="flex-shrink-0">
								<img src="<?php echo esc_url( $rp_thumb ); ?>"
								     alt="<?php echo esc_attr( $rp->post_title ); ?>"
								     class="w-14 h-14 object-cover rounded-lg"
								     width="56" height="56" loading="lazy" />
							</a>
							<?php endif; ?>
							<div class="min-w-0">
								<a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>"
								   class="text-[13px] font-semibold no-underline hover:underline"
								   style="color:#1A3C6E">
									<?php echo esc_html( $rp->post_title ); ?>
								</a>
								<span class="text-[11px] block mt-0.5" style="color:#6b7280">
									<?php echo get_the_date( 'j M Y', $rp->ID ); ?>
								</span>
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<!-- CTA -->
				<div class="rounded-xl p-6 text-center" style="background:linear-gradient(135deg,#1A3C6E 0%,#1A1A2E 100%)">
					<span class="text-[2rem]">🎓</span>
					<h3 class="font-bold text-[16px] my-2 text-white">Advance Your Career</h3>
					<p class="text-[13px] mb-4" style="color:rgba(255,255,255,0.65)">Browse 150+ accredited professional courses.</p>
					<a href="/courses/" class="inline-block w-full font-bold text-[14px] py-3 rounded-lg no-underline hover:brightness-105 transition-all" style="background:#F4A726;color:#6b4500">Browse Courses</a>
				</div>

				<!-- Popular Tags -->
				<div class="bg-white rounded-xl p-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
					<h3 class="font-bold text-[16px] mb-4 pb-3" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">Popular Tags</h3>
					<div class="flex flex-wrap gap-2">
						<?php foreach ( get_tags( [ 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ] ) as $tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"
						   class="cat-pill text-[12px] font-medium px-3 py-1.5 rounded-full no-underline"
						   style="background:#f3f4f5;color:#43474f">
							<?php echo esc_html( $tag->name ); ?>
						</a>
						<?php endforeach; ?>
					</div>
				</div>

			</aside>

		</div>
	</main>
	<?php
}
