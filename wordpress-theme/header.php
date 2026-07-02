<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="wrap">
		<nav class="nav">
			<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex;align-items:center;gap:10px;"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/logo/ko-bets-logo-transparent.png' ); ?>" alt="KO-Bets" style="height:72px;width:auto;display:block;">KO<span>-BETS</span></a>
			<button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span><span></span><span></span></button>
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'navlinks',
					'depth'          => 1,
				) );
			} else { ?>
				<ul class="navlinks">
					<li><a href="<?php echo esc_url( home_url( '/schedule/' ) ); ?>">Schedule</a></li>
					<li><a href="<?php echo esc_url( home_url( '/picks/' ) ); ?>">This Week's Picks</a></li>
					<li><a href="<?php echo esc_url( home_url( '/track-record/' ) ); ?>">Track Record</a></li>
					<li><a href="<?php echo esc_url( home_url( '/fighters/' ) ); ?>">Fighters</a></li>
					<li><a href="<?php echo esc_url( home_url( '/learn-odds/' ) ); ?>">Learn Odds</a></li>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a></li>
					<li><a href="<?php echo esc_url( home_url( '/merch/' ) ); ?>">Merch</a></li>
				</ul>
			<?php } ?>
			<div class="navright">
				<span class="badge18">18+</span>
				<a class="btn" href="<?php echo esc_url( home_url( '/picks/' ) ); ?>">Latest Picks</a>
			</div>
		</nav>
	</div>
</header>
<script>
(function(){var n=document.querySelector(".nav"),t=document.querySelector(".nav-toggle");if(t&&n){t.addEventListener("click",function(){var o=n.classList.toggle("open");t.setAttribute("aria-expanded",o?"true":"false");});}})();
</script>
