<?php
/**
 * Front page — KO-Bets homepage.
 * Pulls from the kb_* custom post types; falls back to sample rows so a fresh
 * install still looks like the approved mockup before the agents populate data.
 *
 * @package ko-bets
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$stats = kobets_get_stats();
?>

<div class="note">Educational odds analysis &middot; transparent track record &middot; boxing &middot; MMA &middot; kickboxing &middot; Muay Thai</div>

<!-- HERO + TRACKER -->
<div class="hero">
	<div class="wrap hero-grid">
		<div>
			<div class="eyebrow">Educational odds analysis</div>
			<h1>We break down the odds.<br><span class="accent">You make the call.</span></h1>
			<p class="sub">Data-driven fight breakdowns and odds analysis for boxing, MMA, kickboxing &amp; Muay Thai &mdash; with a fully transparent, public win-rate record. Not guaranteed picks. For educational purposes only.</p>
			<div class="hero-cta">
				<a class="btn" href="<?php echo esc_url( home_url( '/picks/' ) ); ?>">See this week's analysis</a>
				<a class="btn ghost" href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>">How it works</a>
			</div>
		</div>

		<div class="tracker">
			<h3>Overall performance &middot; all graded picks</h3>
			<div class="stat-row">
				<div class="stat"><div class="num win"><?php echo $stats['resolved'] ? esc_html( $stats['win_pct'] ) . '%' : '&mdash;'; ?></div><div class="lbl">Win rate</div></div>
				<div class="stat"><div class="num"><?php echo esc_html( $stats['wins'] ) . '&ndash;' . esc_html( $stats['losses'] ); ?></div><div class="lbl">Record</div></div>
				<div class="stat"><div class="num win"><?php echo ( $stats['units'] >= 0 ? '+' : '' ) . esc_html( $stats['units'] ); ?></div><div class="lbl">Units</div></div>
				<div class="stat"><div class="num"><?php echo ( $stats['roi'] >= 0 ? '+' : '' ) . esc_html( $stats['roi'] ); ?>%</div><div class="lbl">ROI</div></div>
			</div>
			<div class="chart-box"><canvas id="trackChart"></canvas></div>
			<div class="updated"><span class="dot"></span> Auto-updated weekly &middot; <?php echo esc_html( $stats['resolved'] ); ?> graded picks tracked</div>
		</div>
	</div>
</div>

<!-- LEADERBOARD AD -->
<div class="wrap" style="padding-top:28px">
	<div class="ad leader"><?php echo kobets_ad_slot( 'leaderboard' ); ?></div>
</div>

<!-- THIS WEEK'S PICKS -->
<section class="section">
	<div class="wrap">
		<div class="sec-head">
			<div>
				<h2>This week's analysis</h2>
				<div class="sec-sub">Our read on the card &mdash; odds, confidence, and the reasoning behind each.</div>
			</div>
			<a href="<?php echo esc_url( home_url( '/picks/' ) ); ?>">View all &rsaquo;</a>
		</div>
		<div class="picks">
		<?php
		$picks = new WP_Query( array( 'post_type' => 'kb_pick', 'posts_per_page' => 3 ) );
		if ( $picks->have_posts() ) :
			while ( $picks->have_posts() ) : $picks->the_post();
				$league = get_post_meta( get_the_ID(), 'kb_league', true );
				$sport  = get_post_meta( get_the_ID(), 'kb_sport', true );
				$status = get_post_meta( get_the_ID(), 'kb_status', true );
				$sel    = get_post_meta( get_the_ID(), 'kb_selection', true );
				$odds   = get_post_meta( get_the_ID(), 'kb_odds', true );
				$conf   = (float) get_post_meta( get_the_ID(), 'kb_confidence', true );
				$res    = in_array( $status, array( 'win', 'loss', 'push' ), true ) ? $status : 'live';
				$reslbl = array( 'win' => 'WON', 'loss' => 'LOST', 'push' => 'PUSH', 'live' => 'LIVE' )[ $res ];
				?>
				<div class="pick">
					<div class="pick-top"><span class="league"><?php echo esc_html( $league ?: $sport ); ?></span><span class="res <?php echo esc_attr( $res ); ?>"><?php echo esc_html( $reslbl ); ?></span></div>
					<div class="pick-body">
						<div class="matchup"><?php the_title(); ?></div>
						<div class="pick-row">
							<div class="ourpick">Our read<b><?php echo esc_html( $sel ); ?></b></div>
							<div class="odds"><?php echo esc_html( $odds ); ?></div>
						</div>
						<div class="conf">
							<div class="lbl"><span>Confidence</span><span><?php echo esc_html( $conf >= 75 ? 'High' : ( $conf >= 50 ? 'Medium' : 'Lean' ) ); ?></span></div>
							<div class="barbg"><div class="bar" style="width:<?php echo esc_attr( max( 5, min( 100, $conf ) ) ); ?>%"></div></div>
						</div>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata();
		else :
			kobets_sample_picks();
		endif; ?>
		</div>
	</div>
</section>

<!-- FIGHT SCHEDULE -->
<section class="section" style="padding-top:6px">
	<div class="wrap">
		<div class="sec-head">
			<div>
				<h2>Upcoming fight schedule</h2>
				<div class="sec-sub">When &amp; where to watch &mdash; boxing, MMA, kickboxing &amp; Muay Thai. Times in ET / PT.</div>
			</div>
			<a href="<?php echo esc_url( home_url( '/schedule/' ) ); ?>">Full calendar &rsaquo;</a>
		</div>
		<div class="sched">
		<?php
		$events = new WP_Query( array(
			'post_type'      => 'kb_event',
			'posts_per_page' => 6,
			'meta_key'       => 'kb_start_utc',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		) );
		if ( $events->have_posts() ) :
			while ( $events->have_posts() ) : $events->the_post();
				$sport  = get_post_meta( get_the_ID(), 'kb_sport', true );
				$promo  = get_post_meta( get_the_ID(), 'kb_promotion', true );
				$head   = get_post_meta( get_the_ID(), 'kb_headline', true );
				$venue  = get_post_meta( get_the_ID(), 'kb_venue', true );
				$chan   = get_post_meta( get_the_ID(), 'kb_stream_service', true );
				$aff    = get_post_meta( get_the_ID(), 'kb_affiliate_url', true );
				list( $day, $time ) = kobets_when( get_post_meta( get_the_ID(), 'kb_start_utc', true ) );
				?>
				<div class="sched-row">
					<div class="org <?php echo esc_attr( kobets_org_class( $sport ) ); ?>"><?php echo esc_html( strtoupper( substr( $promo ?: $sport, 0, 5 ) ) ); ?></div>
					<div class="sm-event"><?php the_title(); ?><small><?php echo esc_html( trim( ( $head ? $head . ' &middot; ' : '' ) . $venue ) ); ?></small></div>
					<div class="sm-when"><b><?php echo esc_html( $day ); ?></b><span><?php echo esc_html( $time ); ?></span></div>
					<div class="sm-chan"><span class="pill"><?php echo esc_html( $chan ); ?></span></div>
					<a class="watch" href="<?php echo esc_url( $aff ?: '#' ); ?>" rel="sponsored nofollow noopener" target="_blank">Sign up to watch &rsaquo;</a>
				</div>
			<?php endwhile; wp_reset_postdata();
		else :
			kobets_sample_schedule();
		endif; ?>
			<div class="sched-foot">Watch links are streaming-service sign-ups (affiliate). KO-Bets does not take bets or link to sportsbooks. Times shown in ET / PT.</div>
		</div>
	</div>
</section>

<!-- LEARN + FIGHTERS -->
<section class="section" style="padding-top:6px">
	<div class="wrap two">
		<div>
			<div class="sec-head"><div><h2>New to odds?</h2><div class="sec-sub">Plain-English guides &mdash; the ad-safe, educational core of the site.</div></div></div>
			<div class="learn-card">
				<?php
				$learn = array(
					array( 'How moneyline odds actually work', 'Reading +135 vs &minus;160, and what they imply about probability.' ),
					array( 'What "value" means in a betting line', 'Why the favorite isn\'t always the smart read.' ),
					array( 'Bankroll &amp; units, explained', 'How we measure performance honestly over time.' ),
					array( 'Reading a fight: the tape vs the line', 'Our framework for breaking down a matchup.' ),
				);
				$i = 1;
				foreach ( $learn as $l ) {
					echo '<div class="learn-item"><div class="learn-ico">' . $i++ . '</div><div><h4>' . wp_kses_post( $l[0] ) . '</h4><p>' . wp_kses_post( $l[1] ) . '</p></div></div>';
				}
				?>
			</div>
		</div>
		<div>
			<div class="sec-head"><div><h2>Fighter profiles</h2><div class="sec-sub">Records, styles, and how they shape the odds.</div></div></div>
			<?php
			$fighters = new WP_Query( array( 'post_type' => 'kb_fighter', 'posts_per_page' => 4 ) );
			if ( $fighters->have_posts() ) :
				while ( $fighters->have_posts() ) : $fighters->the_post();
					$rec   = get_post_meta( get_the_ID(), 'kb_record', true );
					$wc    = get_post_meta( get_the_ID(), 'kb_weight_class', true );
					$st    = get_post_meta( get_the_ID(), 'kb_stance', true );
					$title = get_the_title();
					$ini   = strtoupper( substr( $title, 0, 1 ) . ( strpos( $title, ' ' ) !== false ? substr( $title, strpos( $title, ' ' ) + 1, 1 ) : '' ) );
					?>
					<a class="fighter" href="<?php the_permalink(); ?>">
						<div class="av"><?php echo esc_html( $ini ); ?></div>
						<div class="meta"><b><?php echo esc_html( $title ); ?></b><span><?php echo esc_html( trim( $wc . ( $st ? ' &middot; ' . $st : '' ) ) ); ?></span></div>
						<div class="rec"><?php echo esc_html( $rec ); ?><small>Record</small></div>
					</a>
				<?php endwhile; wp_reset_postdata();
			else :
				kobets_sample_fighters();
			endif; ?>
		</div>
	</div>
</section>

<!-- INLINE AD -->
<div class="wrap"><div class="ad inline"><?php echo kobets_ad_slot( 'in_content' ); ?></div></div>

<!-- BLOG -->
<section class="section">
	<div class="wrap">
		<div class="sec-head">
			<div><h2>Latest breakdowns</h2><div class="sec-sub">Long-form analysis &mdash; written and graded by the pipeline, signed off before publish.</div></div>
			<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">All articles &rsaquo;</a>
		</div>
		<div class="blog">
		<?php
		$posts = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3 ) );
		if ( $posts->have_posts() ) :
			while ( $posts->have_posts() ) : $posts->the_post();
				$cat = get_the_category();
				$thumb = has_post_thumbnail() ? 'style="background-image:url(' . esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ) . ')"' : '';
				?>
				<a class="post" href="<?php the_permalink(); ?>">
					<div class="thumb" <?php echo $thumb; ?>><?php echo has_post_thumbnail() ? '' : 'KO-BETS'; ?></div>
					<div class="pbody">
						<span class="tag"><?php echo $cat ? esc_html( $cat[0]->name ) : 'Analysis'; ?></span>
						<h4><?php the_title(); ?></h4>
						<div class="pmeta"><?php echo esc_html( get_the_date() ); ?></div>
					</div>
				</a>
			<?php endwhile; wp_reset_postdata();
		else :
			kobets_sample_blog();
		endif; ?>
		</div>
	</div>
</section>

<!-- MERCH -->
<section class="section" style="padding-top:6px">
	<div class="wrap">
		<div class="merch">
			<div class="pitch">
				<h3>Rep the <span class="accent">KO-BETS</span> brand</h3>
				<p>Print-on-demand tees, hoodies &amp; gear. Plus our picks for the best fight equipment on Amazon.</p>
				<a class="btn" href="<?php echo esc_url( home_url( '/merch/' ) ); ?>">Shop the store</a>
			</div>
			<div class="product"><div class="ph">KO-BETS</div><b>"Trust the Tape" Tee</b><span>$28</span></div>
			<div class="product"><div class="ph">KO-BETS</div><b>Knockout Hoodie</b><span>$48</span></div>
			<div class="product"><div class="ph">&#9733;</div><b>Top training gloves</b><span>Amazon picks</span></div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
