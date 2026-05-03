<?php
/**
 * SPOKE SINGLE POST — inc/functions-blog-single.php
 *
 * Provides [spoke_single_post] shortcode used in single.html.
 * All PHP lives here — never inside .html block templates which
 * cannot execute PHP.
 *
 * Add ONE line to functions.php alongside the other requires:
 *   require_once get_template_directory() . '/inc/functions-blog-single.php';
 *
 * Then update single.html to:
 *   <!-- wp:template-part {"slug":"header","tagName":"header"} /-->
 *   <!-- wp:html -->
 *   [spoke_single_post]
 *   <!-- /wp:html -->
 *   <!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
 *
 * @package SpokeTheme
 */

// ─────────────────────────────────────────────────────────────────
// 1. SUPPRESS wpautop
// ─────────────────────────────────────────────────────────────────

add_filter( 'the_content', function ( string $content ): string {
	if ( has_shortcode( $content, 'spoke_single_post' ) ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'wptexturize' );
	}
	return $content;
}, 9 );

add_filter( 'the_content', 'shortcode_unautop', 10 );


// ─────────────────────────────────────────────────────────────────
// 2. SHORTCODE — [spoke_single_post]
// ─────────────────────────────────────────────────────────────────

add_shortcode( 'spoke_single_post', function (): string {
	ob_start();
	spoke_render_single_post();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	return $html;
} );


// ─────────────────────────────────────────────────────────────────
// 3. RENDER_BLOCK INTERCEPTOR
// ─────────────────────────────────────────────────────────────────

add_filter( 'render_block', function ( string $block_content, array $block ): string {
	if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/html' ) {
		return $block_content;
	}
	$raw = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
	if ( false === strpos( $raw, 'spoke_single_post' ) ) {
		return $block_content;
	}
	ob_start();
	spoke_render_single_post();
	$html = ob_get_clean();
	$html = preg_replace( '/<br\s*\/?>/i', '', $html );
	$html = preg_replace( '/<p>(\s|&nbsp;)*<\/p>/i', '', $html );
	$html = preg_replace( '/(\s*\n){3,}/', "\n", $html );
	return $html;
}, 10, 2 );


// ─────────────────────────────────────────────────────────────────
// 4. MAIN RENDER FUNCTION
// ─────────────────────────────────────────────────────────────────

function spoke_render_single_post(): void {

	if ( ! have_posts() ) {
		return;
	}

	// ── Styles ────────────────────────────────────────────────────
	?>
	<style>
		.prose-spoke h2 { font-size:1.5rem; font-weight:700; color:#1A3C6E; margin:2rem 0 0.75rem; letter-spacing:-0.02em; }
		.prose-spoke h3 { font-size:1.2rem; font-weight:700; color:#1A3C6E; margin:1.5rem 0 0.5rem; }
		.prose-spoke p  { font-size:1rem; line-height:1.8; color:#43474f; margin:0 0 1.25rem; }
		.prose-spoke ul, .prose-spoke ol { margin:0 0 1.25rem 1.5rem; }
		.prose-spoke li { font-size:1rem; line-height:1.7; color:#43474f; margin-bottom:0.4rem; }
		.prose-spoke a  { color:#1A3C6E; text-decoration:underline; text-underline-offset:2px; }
		.prose-spoke a:hover { color:#F4A726; }
		.prose-spoke blockquote { border-left:4px solid #F4A726; padding-left:1.25rem; margin:1.5rem 0; font-style:italic; color:#43474f; }
		.prose-spoke img { border-radius:10px; max-width:100%; height:auto; margin:1.5rem 0; }
		.prose-spoke strong { color:#1A3C6E; font-weight:700; }
		.prose-spoke code { background:#f3f4f5; padding:2px 6px; border-radius:4px; font-size:0.875rem; }
		.prose-spoke pre  { background:#1A1A2E; color:#e2e8f0; padding:1.25rem; border-radius:10px; overflow-x:auto; margin:1.5rem 0; }
		.prose-spoke hr   { border:none; border-top:2px solid rgba(0,0,0,0.07); margin:2rem 0; }
		.prose-spoke table { width:100%; border-collapse:collapse; margin:1.5rem 0; font-size:0.9rem; }
		.prose-spoke th,
		.prose-spoke td   { padding:10px 14px; border:1px solid rgba(0,0,0,0.1); }
		.prose-spoke th   { background:#1A3C6E; color:#fff; font-weight:700; text-align:left; }
		.prose-spoke tr:nth-child(even) td { background:#f8f9fa; }
		.share-btn { transition: transform 150ms ease, filter 150ms ease; }
		.share-btn:hover { transform:translateY(-2px); filter:brightness(1.1); }
		.related-card { transition: transform 200ms ease, box-shadow 200ms ease; }
		.related-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(26,60,110,0.10); }
	</style>
	<?php

	while ( have_posts() ) :
		the_post();

		$post_id     = get_the_ID();
		$cats        = get_the_category();
		$tags        = get_the_tags();
		$thumb_url   = get_post_thumbnail_id() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'full' ) : '';
		$word_count  = str_word_count( strip_tags( get_the_content() ) );
		$read_time   = max( 1, ceil( $word_count / 200 ) );
		$author_id   = (int) get_the_author_meta( 'ID' );
		$author_name = get_the_author();
		$author_bio  = get_the_author_meta( 'description' );
		$author_av   = get_avatar_url( $author_id, [ 'size' => 80 ] );
		$post_url    = urlencode( get_permalink() );
		$post_title  = urlencode( get_the_title() );
		$blog_url    = get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blogs/' );

		?>

		<!-- ══ BREADCRUMB + META HEADER ══════════════════════════ -->
		<div class="px-4 sm:px-8 pt-10 pb-0" style="background:#f8f9fa">
			<div class="max-w-[800px] mx-auto">

				<nav class="flex items-center gap-2 text-[13px] mb-6" aria-label="Breadcrumb">
					<a href="/" class="hover:underline" style="color:#43474f">Home</a>
					<span style="color:rgba(0,0,0,0.2)">/</span>
					<a href="<?php echo esc_url( $blog_url ); ?>" class="hover:underline" style="color:#43474f">Blog</a>
					<?php if ( $cats ) : ?>
					<span style="color:rgba(0,0,0,0.2)">/</span>
					<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>" class="hover:underline" style="color:#43474f">
						<?php echo esc_html( $cats[0]->name ); ?>
					</a>
					<?php endif; ?>
					<span style="color:rgba(0,0,0,0.2)">/</span>
					<span class="font-medium truncate max-w-[200px]" style="color:#1A3C6E"><?php the_title(); ?></span>
				</nav>

				<div class="flex flex-wrap items-center gap-3 mb-4">
					<?php if ( $cats ) : ?>
					<a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"
					   class="text-[11px] font-bold uppercase tracking-[0.8px] px-3 py-1 rounded-full no-underline"
					   style="background:rgba(26,60,110,0.09);color:#1A3C6E">
						<?php echo esc_html( $cats[0]->name ); ?>
					</a>
					<?php endif; ?>
					<span class="text-[13px]" style="color:#6b7280"><?php echo get_the_date( 'j F Y' ); ?></span>
					<span style="color:rgba(0,0,0,0.2)">·</span>
					<span class="text-[13px]" style="color:#6b7280"><?php echo $read_time; ?> min read</span>
					<span style="color:rgba(0,0,0,0.2)">·</span>
					<span class="text-[13px]" style="color:#6b7280"><?php echo number_format( $word_count ); ?> words</span>
				</div>

				<h1 class="font-bold leading-tight tracking-[-1.5px] mb-5" style="font-size:clamp(1.75rem,4vw,2.75rem);color:#1A3C6E">
					<?php the_title(); ?>
				</h1>

				<div class="flex items-center justify-between flex-wrap gap-4 pb-6" style="border-bottom:2px solid #F4A726">
					<div class="flex items-center gap-3">
						<img src="<?php echo esc_url( $author_av ); ?>"
						     alt="<?php echo esc_attr( $author_name ); ?>"
						     class="w-11 h-11 rounded-full object-cover flex-shrink-0"
						     width="44" height="44" />
						<div>
							<p class="font-semibold text-[14px] m-0" style="color:#1A3C6E"><?php echo esc_html( $author_name ); ?></p>
							<p class="text-[12px] m-0" style="color:#6b7280"><?php echo get_the_date( 'j M Y' ) . ' · ' . $read_time . ' min read'; ?></p>
						</div>
					</div>

					<!-- Share buttons -->
					<div class="flex items-center gap-2">
						<span class="text-[12px] font-semibold mr-1" style="color:#43474f">Share:</span>
						<a href="https://twitter.com/intent/tweet?url=<?php echo $post_url; ?>&text=<?php echo $post_title; ?>"
						   target="_blank" rel="noopener noreferrer"
						   class="share-btn w-9 h-9 rounded-full flex items-center justify-center no-underline"
						   style="background:#000;color:#fff" aria-label="Share on X">
							<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
						</a>
						<a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $post_url; ?>&title=<?php echo $post_title; ?>"
						   target="_blank" rel="noopener noreferrer"
						   class="share-btn w-9 h-9 rounded-full flex items-center justify-center no-underline"
						   style="background:#0A66C2;color:#fff" aria-label="Share on LinkedIn">
							<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
						</a>
						<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $post_url; ?>"
						   target="_blank" rel="noopener noreferrer"
						   class="share-btn w-9 h-9 rounded-full flex items-center justify-center no-underline"
						   style="background:#1877F2;color:#fff" aria-label="Share on Facebook">
							<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
						</a>
					</div>
				</div>
			</div>
		</div>

		<?php if ( $thumb_url ) : ?>
		<!-- ══ FEATURED IMAGE ════════════════════════════════════ -->
		<div class="px-4 sm:px-8 py-6" style="background:#f8f9fa">
			<div class="max-w-[1000px] mx-auto">
				<img src="<?php echo esc_url( $thumb_url ); ?>"
				     alt="<?php echo esc_attr( get_the_title() ); ?>"
				     class="w-full rounded-xl object-cover"
				     style="max-height:480px;object-position:center"
				     loading="eager" width="1000" height="480" />
			</div>
		</div>
		<?php endif; ?>

		<!-- ══ CONTENT AREA ══════════════════════════════════════ -->
		<div class="px-4 sm:px-8 py-10 lg:py-12" style="background:#f8f9fa">
			<div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-12 max-w-[1280px] mx-auto">

				<!-- Article body -->
				<article>
					<div class="prose-spoke bg-white rounded-xl p-6 sm:p-10" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<?php the_content(); ?>
					</div>

					<?php if ( $tags ) : ?>
					<div class="mt-6 flex flex-wrap items-center gap-2">
						<span class="text-[12px] font-bold uppercase tracking-wider mr-2" style="color:#43474f">Tags:</span>
						<?php foreach ( $tags as $tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"
						   class="text-[13px] px-3 py-1.5 rounded-full no-underline transition-colors hover:bg-[#1A3C6E] hover:text-white"
						   style="background:#f3f4f5;color:#43474f">
							#<?php echo esc_html( $tag->name ); ?>
						</a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<!-- Prev / Next -->
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
						<?php $prev = get_previous_post(); if ( $prev ) : ?>
						<a href="<?php echo esc_url( get_permalink( $prev->ID ) ); ?>"
						   class="bg-white rounded-xl p-5 no-underline flex flex-col gap-1 transition-all hover:shadow-md"
						   style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
							<span class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#F4A726">← Previous</span>
							<span class="text-[14px] font-semibold line-clamp-2" style="color:#1A3C6E"><?php echo esc_html( $prev->post_title ); ?></span>
						</a>
						<?php else : ?><div></div><?php endif; ?>
						<?php $next = get_next_post(); if ( $next ) : ?>
						<a href="<?php echo esc_url( get_permalink( $next->ID ) ); ?>"
						   class="bg-white rounded-xl p-5 no-underline flex flex-col gap-1 text-right transition-all hover:shadow-md"
						   style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
							<span class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#F4A726">Next →</span>
							<span class="text-[14px] font-semibold line-clamp-2" style="color:#1A3C6E"><?php echo esc_html( $next->post_title ); ?></span>
						</a>
						<?php endif; ?>
					</div>

					<!-- Author bio -->
					<div class="mt-8 bg-white rounded-xl p-6 sm:p-8 flex flex-col sm:flex-row gap-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<img src="<?php echo esc_url( $author_av ); ?>"
						     alt="<?php echo esc_attr( $author_name ); ?>"
						     class="w-20 h-20 rounded-xl object-cover flex-shrink-0"
						     width="80" height="80" />
						<div>
							<p class="text-[11px] font-bold uppercase tracking-[0.8px] mb-1" style="color:#F4A726">About the Author</p>
							<h3 class="font-bold text-[18px] mb-2" style="color:#1A3C6E"><?php echo esc_html( $author_name ); ?></h3>
							<?php if ( $author_bio ) : ?>
							<p class="text-[14px] leading-relaxed m-0" style="color:#43474f"><?php echo esc_html( $author_bio ); ?></p>
							<?php else : ?>
							<p class="text-[14px] leading-relaxed m-0" style="color:#43474f">Professional educator and content writer at StudyMate Central, helping UK professionals advance their careers.</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Comments -->
					<div class="mt-8">
						<?php
						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}
						?>
					</div>
				</article>

				<!-- Sticky Sidebar -->
				<aside class="flex flex-col gap-5">

					<!-- Table of Contents (JS-generated) -->
					<div class="bg-white rounded-xl p-5 sticky top-24" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<h3 class="font-bold text-[14px] mb-3 pb-2.5" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">Table of Contents</h3>
						<nav id="toc-nav" aria-label="Article sections">
							<ul class="flex flex-col gap-1 list-none m-0 p-0 text-[13px]" id="toc-list"></ul>
						</nav>
					</div>

					<!-- CTA box -->
					<div class="rounded-xl p-5 text-center" style="background:linear-gradient(135deg,#1A3C6E 0%,#1A1A2E 100%)">
						<span class="text-[2rem]">🎓</span>
						<h3 class="font-bold text-[15px] my-2 text-white">Ready to Upskill?</h3>
						<p class="text-[12px] mb-3" style="color:rgba(255,255,255,0.65)">150+ accredited courses for UK professionals.</p>
						<a href="/courses/" class="inline-block w-full font-bold text-[13px] py-2.5 rounded-lg no-underline hover:brightness-105" style="background:#F4A726;color:#6b4500">Browse Courses</a>
					</div>

					<!-- More Articles -->
					<div class="bg-white rounded-xl p-5" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<h3 class="font-bold text-[14px] mb-4 pb-2.5" style="color:#1A3C6E;border-bottom:1px solid rgba(0,0,0,0.07)">More Articles</h3>
						<ul class="flex flex-col gap-3 list-none m-0 p-0">
							<?php foreach ( get_posts( [ 'numberposts' => 4, 'post__not_in' => [ $post_id ], 'post_status' => 'publish' ] ) as $rp ) :
								$rp_thumb = get_the_post_thumbnail_url( $rp->ID, 'thumbnail' );
							?>
							<li class="flex items-start gap-3">
								<?php if ( $rp_thumb ) : ?>
								<a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>" class="flex-shrink-0">
									<img src="<?php echo esc_url( $rp_thumb ); ?>" alt=""
									     class="w-12 h-12 object-cover rounded-lg"
									     width="48" height="48" loading="lazy" />
								</a>
								<?php endif; ?>
								<div class="min-w-0">
									<a href="<?php echo esc_url( get_permalink( $rp->ID ) ); ?>"
									   class="text-[13px] font-semibold no-underline hover:underline line-clamp-2 block"
									   style="color:#1A3C6E">
										<?php echo esc_html( $rp->post_title ); ?>
									</a>
									<span class="text-[11px]" style="color:#6b7280">
										<?php echo get_the_date( 'j M Y', $rp->ID ); ?>
									</span>
								</div>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>

				</aside>

			</div>
		</div>

		<?php
		// ── Related posts ─────────────────────────────────────────
		$related_args = [
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'post__not_in'   => [ $post_id ],
			'orderby'        => 'rand',
		];
		if ( $cats ) {
			$related_args['category__in'] = array_map( fn( $c ) => $c->term_id, $cats );
		}
		$related = new WP_Query( $related_args );

		if ( $related->have_posts() ) :
		?>
		<section class="px-4 sm:px-8 py-14" style="background:#f3f4f5">
			<div class="max-w-[1280px] mx-auto">
				<h2 class="font-bold text-[24px] tracking-[-0.5px] mb-8" style="color:#1A3C6E">Related Articles</h2>
				<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
					<?php while ( $related->have_posts() ) : $related->the_post();
						$r_cats  = get_the_category();
						$r_thumb = get_post_thumbnail_id() ? wp_get_attachment_image_url( get_post_thumbnail_id(), 'medium_large' ) : '';
						$r_read  = max( 1, ceil( str_word_count( strip_tags( get_the_content() ) ) / 200 ) );
					?>
					<article class="related-card bg-white rounded-xl overflow-hidden flex flex-col" style="box-shadow:0 2px 8px rgba(0,0,0,0.06)">
						<?php if ( $r_thumb ) : ?>
						<a href="<?php the_permalink(); ?>" class="block overflow-hidden" style="height:170px">
							<img src="<?php echo esc_url( $r_thumb ); ?>"
							     alt="<?php echo esc_attr( get_the_title() ); ?>"
							     class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
							     loading="lazy" width="400" height="170" />
						</a>
						<?php else : ?>
						<div class="flex items-center justify-center" style="height:170px;background:linear-gradient(135deg,#1A3C6E,#1A1A2E)">
							<svg class="w-10 h-10 opacity-20" fill="white" viewBox="0 0 24 24"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/></svg>
						</div>
						<?php endif; ?>
						<div class="p-5 flex flex-col flex-1 gap-2">
							<?php if ( $r_cats ) : ?>
							<a href="<?php echo esc_url( get_category_link( $r_cats[0]->term_id ) ); ?>"
							   class="text-[10px] font-bold uppercase tracking-[0.8px] px-2 py-0.5 rounded self-start no-underline"
							   style="background:rgba(26,60,110,0.08);color:#1A3C6E">
								<?php echo esc_html( $r_cats[0]->name ); ?>
							</a>
							<?php endif; ?>
							<h3 class="font-bold text-[15px] leading-snug m-0" style="color:#1A3C6E">
								<a href="<?php the_permalink(); ?>" class="no-underline hover:underline" style="color:inherit"><?php the_title(); ?></a>
							</h3>
							<div class="flex items-center justify-between mt-auto pt-2.5" style="border-top:1px solid rgba(0,0,0,0.06)">
								<span class="text-[12px]" style="color:#6b7280"><?php echo get_the_date( 'j M Y' ); ?></span>
								<span class="text-[12px]" style="color:#6b7280"><?php echo $r_read; ?> min</span>
							</div>
						</div>
					</article>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- Table of Contents JS -->
		<script>
		(function(){
			var headings = document.querySelectorAll('.prose-spoke h2, .prose-spoke h3');
			var tocList  = document.getElementById('toc-list');
			if (!tocList || headings.length === 0) {
				var tocWrap = document.getElementById('toc-nav');
				if (tocWrap) tocWrap.closest('.bg-white').style.display = 'none';
				return;
			}
			headings.forEach(function(h, i) {
				if (!h.id) h.id = 'heading-' + i;
				var li = document.createElement('li');
				var a  = document.createElement('a');
				a.href = '#' + h.id;
				a.textContent = h.textContent;
				a.style.cssText = 'color:#43474f;text-decoration:none;display:block;padding:4px 8px;border-radius:6px;transition:background 150ms,color 150ms;';
				if (h.tagName === 'H3') a.style.paddingLeft = '20px';
				a.addEventListener('mouseenter', function(){ this.style.background='#f3f4f5'; this.style.color='#1A3C6E'; });
				a.addEventListener('mouseleave', function(){ this.style.background=''; this.style.color='#43474f'; });
				li.appendChild(a);
				tocList.appendChild(li);
			});
			document.querySelectorAll('#toc-list a').forEach(function(a){
				a.addEventListener('click', function(e){
					e.preventDefault();
					var target = document.querySelector(this.getAttribute('href'));
					if (target) target.scrollIntoView({ behavior:'smooth', block:'start' });
				});
			});
		})();
		</script>

	<?php endwhile; ?>
	<?php
}
