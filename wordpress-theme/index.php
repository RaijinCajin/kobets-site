<?php
/**
 * Generic fallback template — blog index, archives, single posts, pages,
 * and the kb_* single/archive views. Wrapped in the site chrome.
 *
 * @package ko-bets
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="wrap page-content">
<?php
if ( have_posts() ) :
	if ( is_archive() || is_home() ) {
		echo '<h1>' . esc_html( wp_strip_all_tags( get_the_archive_title() ?: get_bloginfo( 'name' ) ) ) . '</h1>';
	}
	while ( have_posts() ) : the_post();
		?>
		<article <?php post_class(); ?> style="margin-bottom:2.5rem">
			<?php if ( ! is_singular() ) : ?>
				<h2 style="margin-bottom:.3rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p style="color:var(--muted);font-size:13px;margin:0 0 .6rem"><?php echo esc_html( get_the_date() ); ?></p>
				<div><?php the_excerpt(); ?></div>
			<?php else : ?>
				<h1><?php the_title(); ?></h1>
				<p style="color:var(--muted);font-size:13px"><?php echo esc_html( get_the_date() ); ?></p>
				<div><?php the_content(); ?></div>
			<?php endif; ?>
		</article>
		<?php
	endwhile;
	the_posts_pagination( array( 'mid_size' => 1 ) );
else :
	echo '<h1>Nothing here yet</h1><p>Check back soon.</p>';
endif;
?>
</div>
<?php get_footer(); ?>
