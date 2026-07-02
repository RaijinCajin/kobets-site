<?php
/**
 * KO-Bets theme functions
 *
 * Registers the three data types the Hermes agents write to over the REST API:
 *   - kb_pick   : a single analysis pick (graded win/loss/push) -> powers the win-rate tracker
 *   - kb_event  : an upcoming fight on the schedule (date/time/broadcaster/affiliate link)
 *   - kb_result : a factual event result for the archive / fighter profiles
 *   - kb_fighter: a fighter profile
 *
 * All custom fields are registered with show_in_rest so the agents can create/update
 * records with a normal authenticated REST call (same pattern as wp_publisher.py).
 *
 * @package ko-bets
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------------------------------------------------------------------------
 * 1. Theme setup
 * ------------------------------------------------------------------------- */
function kobets_setup() {
	add_theme_support( 'title-tag' );

function kobets_meta_description() {
if ( is_front_page() ) {
$desc = get_bloginfo( 'description' );
} elseif ( is_singular() ) {
global $post;
$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( $post->post_content );
$desc = wp_trim_words( $excerpt, 30, '...' );
} else {
$desc = get_bloginfo( 'description' );
}
if ( $desc ) {
echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
}
add_action( 'wp_head', 'kobets_meta_description', 1 );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'ko-bets' ),
		'footer'  => __( 'Footer Menu', 'ko-bets' ),
	) );
}
add_action( 'after_setup_theme', 'kobets_setup' );

/* ---------------------------------------------------------------------------
 * 2. Styles & scripts
 * ------------------------------------------------------------------------- */
function kobets_assets() {
	wp_enqueue_style(
		'kobets-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap',
		array(), null
	);
	wp_enqueue_style( 'kobets-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );

	wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true );
	wp_enqueue_script( 'kobets-main', get_template_directory_uri() . '/assets/js/main.js', array( 'chartjs' ), wp_get_theme()->get( 'Version' ), true );

	// Hand the tracker chart its data + the REST root.
	wp_localize_script( 'kobets-main', 'KOBETS', array(
		'restUrl' => esc_url_raw( rest_url( 'kobets/v1/' ) ),
		'series'  => kobets_get_units_series(),
	) );
}
add_action( 'wp_enqueue_scripts', 'kobets_assets' );

/* ---------------------------------------------------------------------------
 * 3. Custom post types
 * ------------------------------------------------------------------------- */
function kobets_register_types() {

	register_post_type( 'kb_pick', array(
		'labels'       => array( 'name' => 'Picks', 'singular_name' => 'Pick' ),
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-chart-line',
		'rewrite'      => array( 'slug' => 'picks' ),
		'supports'     => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'kb_event', array(
		'labels'       => array( 'name' => 'Schedule', 'singular_name' => 'Event' ),
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-calendar-alt',
		'rewrite'      => array( 'slug' => 'schedule' ),
		'supports'     => array( 'title', 'editor', 'custom-fields' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'kb_result', array(
		'labels'       => array( 'name' => 'Results', 'singular_name' => 'Result' ),
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-awards',
		'rewrite'      => array( 'slug' => 'results' ),
		'supports'     => array( 'title', 'editor', 'custom-fields' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'kb_fighter', array(
		'labels'       => array( 'name' => 'Fighters', 'singular_name' => 'Fighter' ),
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-universal-access',
		'rewrite'      => array( 'slug' => 'fighters' ),
		'supports'     => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'kobets_register_types' );

/* ---------------------------------------------------------------------------
 * 4. Meta fields (all REST-exposed so agents can write them)
 * ------------------------------------------------------------------------- */
function kobets_register_meta() {
	$str = array( 'type' => 'string',  'single' => true, 'show_in_rest' => true, 'auth_callback' => '__return_true' );
	$num = array( 'type' => 'number',  'single' => true, 'show_in_rest' => true, 'auth_callback' => '__return_true' );

	// kb_pick
	foreach ( array( 'sport', 'league', 'matchup', 'selection', 'market', 'odds', 'status', 'result_note', 'date_graded', 'article_url' ) as $k ) {
		register_post_meta( 'kb_pick', 'kb_' . $k, $str );
	}
	register_post_meta( 'kb_pick', 'kb_confidence', $num ); // 0-100
	register_post_meta( 'kb_pick', 'kb_units',      $num ); // stake in units

	// kb_event
	foreach ( array( 'sport', 'promotion', 'headline', 'venue', 'start_utc', 'broadcaster', 'stream_service', 'affiliate_url', 'status' ) as $k ) {
		register_post_meta( 'kb_event', 'kb_' . $k, $str );
	}

	// kb_result
	foreach ( array( 'sport', 'promotion', 'event_name', 'event_date', 'winner', 'loser', 'method', 'round', 'time' ) as $k ) {
		register_post_meta( 'kb_result', 'kb_' . $k, $str );
	}

	// kb_fighter
	foreach ( array( 'record', 'stance', 'weight_class', 'discipline', 'nickname' ) as $k ) {
		register_post_meta( 'kb_fighter', 'kb_' . $k, $str );
	}
}
add_action( 'init', 'kobets_register_meta' );

/* ---------------------------------------------------------------------------
 * 5. Track-record stats (computed from kb_pick records — never hand-typed)
 * ------------------------------------------------------------------------- */
function kobets_get_picks( $status = '' ) {
	$args = array(
		'post_type'      => 'kb_pick',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( $status ) {
		$args['meta_query'] = array( array( 'key' => 'kb_status', 'value' => $status ) );
	}
	return get_posts( $args );
}

/**
 * American odds -> profit in units for a 1-unit stake on a win.
 *  +150 -> 1.5 ; -200 -> 0.5
 */
function kobets_unit_profit( $odds ) {
	$odds = (float) preg_replace( '/[^0-9\-+.]/', '', (string) $odds );
	if ( $odds === 0.0 ) { return 0.0; }
	return $odds > 0 ? $odds / 100.0 : 100.0 / abs( $odds );
}

function kobets_get_stats() {
	$wins = $losses = $pushes = 0;
	$units = 0.0;
	$staked = 0.0;

	foreach ( kobets_get_picks() as $p ) {
		$status = get_post_meta( $p->ID, 'kb_status', true );
		$stake  = (float) get_post_meta( $p->ID, 'kb_units', true );
		if ( $stake <= 0 ) { $stake = 1.0; }
		$odds   = get_post_meta( $p->ID, 'kb_odds', true );

		if ( in_array( $status, array( 'win', 'won' ), true ) ) {
			$wins++;  $units += kobets_unit_profit( $odds ) * $stake; $staked += $stake;
		} elseif ( in_array( $status, array( 'loss', 'lost' ), true ) ) {
			$losses++; $units -= $stake; $staked += $stake;
		} elseif ( $status === 'push' ) {
			$pushes++;
		}
	}

	$decided = $wins + $losses;
	$win_pct = $decided > 0 ? round( $wins / $decided * 100, 1 ) : 0;
	$roi     = $staked > 0 ? round( $units / $staked * 100, 1 ) : 0;

	return array(
		'wins'     => $wins,
		'losses'   => $losses,
		'pushes'   => $pushes,
		'resolved' => $decided,
		'win_pct'  => $win_pct,
		'units'    => round( $units, 1 ),
		'roi'      => $roi,
	);
}

/** Cumulative units series for the chart, oldest -> newest. */
function kobets_get_units_series() {
	$running = 0.0;
	$points  = array();
	foreach ( kobets_get_picks() as $p ) {
		$status = get_post_meta( $p->ID, 'kb_status', true );
		if ( $status !== 'win' && $status !== 'loss' ) { continue; }
		$stake = (float) get_post_meta( $p->ID, 'kb_units', true );
		if ( $stake <= 0 ) { $stake = 1.0; }
		$odds  = get_post_meta( $p->ID, 'kb_odds', true );
		$running += in_array( $status, array( 'win', 'won' ), true ) ? kobets_unit_profit( $odds ) * $stake : -$stake;
		$points[] = array(
			'label' => get_the_date( 'M j', $p ),
			'value' => round( $running, 2 ),
		);
	}
	return $points;
}

/* ---------------------------------------------------------------------------
 * 6. REST endpoint for the tracker (read-only, public)
 * ------------------------------------------------------------------------- */
function kobets_rest_routes() {
	register_rest_route( 'kobets/v1', '/stats', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			return rest_ensure_response( array(
				'stats'  => kobets_get_stats(),
				'series' => kobets_get_units_series(),
			) );
		},
	) );
}
add_action( 'rest_api_init', 'kobets_rest_routes' );

/* ---------------------------------------------------------------------------
 * 7. Small template helpers
 * ------------------------------------------------------------------------- */
function kobets_org_class( $sport ) {
	$sport = strtolower( (string) $sport );
	if ( strpos( $sport, 'mma' ) !== false || strpos( $sport, 'ufc' ) !== false ) { return 'ufc'; }
	if ( strpos( $sport, 'box' ) !== false )  { return 'box'; }
	if ( strpos( $sport, 'kick' ) !== false ) { return 'kick'; }
	if ( strpos( $sport, 'thai' ) !== false || strpos( $sport, 'muay' ) !== false ) { return 'thai'; }
	return 'box';
}

/** Render an event time in both ET and PT from a stored UTC timestamp. */
function kobets_when( $utc ) {
	if ( ! $utc ) { return array( '', '' ); }
	try {
		$dt = new DateTime( $utc, new DateTimeZone( 'UTC' ) );
	} catch ( Exception $e ) {
		return array( esc_html( $utc ), '' );
	}
	$et = clone $dt; $et->setTimezone( new DateTimeZone( 'America/New_York' ) );
	$pt = clone $dt; $pt->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) );
	return array(
		$et->format( 'D, M j' ),
		$et->format( 'g:i A' ) . ' ET / ' . $pt->format( 'g:i A' ) . ' PT',
	);
}

/**
 * Ad slot. Paste your AdSense / Ezoic unit code into the matching theme option
 * (Appearance > Customize, or `update_option`). Until then a labelled placeholder
 * shows so the layout is visible.
 */
function kobets_ad_slot( $slot ) {
	$code = get_option( 'kobets_ad_' . $slot, '' );
	if ( $code ) { return $code; }
	return 'AD SLOT &middot; ' . esc_html( str_replace( '_', ' ', $slot ) ) . ' (AdSense / Ezoic)';
}

/* ---------------------------------------------------------------------------
 * 8. Sample fallbacks — shown only until the agents populate real records,
 *    so a fresh install still matches the approved mockup.
 * ------------------------------------------------------------------------- */
function kobets_sample_picks() {
	$rows = array(
		array( 'UFC 312', 'live', 'LIVE', 'Reyes vs Okafor', 'Okafor by decision', '+135', 82, 'High' ),
		array( 'BOXING &middot; WBC', 'win', 'WON', 'Carter vs Mendez', 'Carter ML', '&minus;160', 58, 'Medium' ),
		array( 'UFC 312', 'loss', 'LOST', 'Silva vs Tate', 'Over 2.5 rounds', '&minus;110', 40, 'Lean' ),
	);
	foreach ( $rows as $r ) {
		echo '<div class="pick"><div class="pick-top"><span class="league">' . wp_kses_post( $r[0] ) . '</span><span class="res ' . esc_attr( $r[1] ) . '">' . esc_html( $r[2] ) . '</span></div>'
			. '<div class="pick-body"><div class="matchup">' . esc_html( $r[3] ) . '</div>'
			. '<div class="pick-row"><div class="ourpick">Our read<b>' . esc_html( $r[4] ) . '</b></div><div class="odds">' . wp_kses_post( $r[5] ) . '</div></div>'
			. '<div class="conf"><div class="lbl"><span>Confidence</span><span>' . esc_html( $r[7] ) . '</span></div><div class="barbg"><div class="bar" style="width:' . (int) $r[6] . '%"></div></div></div></div></div>';
	}
}

function kobets_sample_schedule() {
	$rows = array(
		array( 'ufc', 'UFC', 'UFC 312: Reyes vs Okafor', 'Light heavyweight title &middot; Las Vegas', 'Sat, Jun 21', '10:00 PM ET / 7:00 PM PT', 'ESPN+ PPV' ),
		array( 'box', 'BOXING', 'Carter vs Mendez', 'WBC welterweight &middot; Brooklyn', 'Sat, Jun 28', '9:00 PM ET / 6:00 PM PT', 'DAZN' ),
		array( 'kick', 'GLORY', 'GLORY 98: van Roosmalen vs Petrov', 'Lightweight kickboxing &middot; Rotterdam', 'Sat, Jul 5', '2:00 PM ET / 11:00 AM PT', 'GLORY+' ),
		array( 'thai', 'ONE', 'ONE Fight Night 38', 'Muay Thai &middot; Bangkok', 'Fri, Jul 11', '8:00 AM ET / 5:00 AM PT', 'Prime Video' ),
	);
	foreach ( $rows as $r ) {
		echo '<div class="sched-row"><div class="org ' . esc_attr( $r[0] ) . '">' . esc_html( $r[1] ) . '</div>'
			. '<div class="sm-event">' . esc_html( $r[2] ) . '<small>' . wp_kses_post( $r[3] ) . '</small></div>'
			. '<div class="sm-when"><b>' . esc_html( $r[4] ) . '</b><span>' . esc_html( $r[5] ) . '</span></div>'
			. '<div class="sm-chan"><span class="pill">' . esc_html( $r[6] ) . '</span></div>'
			. '<a class="watch" href="#" rel="sponsored nofollow noopener">Sign up to watch &rsaquo;</a></div>';
	}
}

function kobets_sample_fighters() {
	$rows = array(
		array( 'JO', 'Jelani Okafor', 'Light Heavyweight &middot; Orthodox', '18&ndash;2' ),
		array( 'RC', 'Ray Carter', 'Welterweight &middot; Southpaw', '24&ndash;1' ),
		array( 'DR', 'Diego Reyes', 'Light Heavyweight &middot; Orthodox', '15&ndash;4' ),
		array( 'MT', 'Marcus Tate', 'Middleweight &middot; Switch', '12&ndash;3' ),
	);
	foreach ( $rows as $r ) {
		echo '<div class="fighter"><div class="av">' . esc_html( $r[0] ) . '</div>'
			. '<div class="meta"><b>' . esc_html( $r[1] ) . '</b><span>' . wp_kses_post( $r[2] ) . '</span></div>'
			. '<div class="rec">' . wp_kses_post( $r[3] ) . '<small>Record</small></div></div>';
	}
}

function kobets_sample_blog() {
	$rows = array(
		array( 'MMA &middot; Preview', 'UFC 312 full card: where the value is hiding', '8 min read' ),
		array( 'Boxing &middot; Analysis', 'Why Carter&ndash;Mendez closed shorter than it opened', '6 min read' ),
		array( 'Education', 'Implied probability: turning odds into a percentage', '5 min read' ),
	);
	foreach ( $rows as $r ) {
		echo '<div class="post"><div class="thumb">KO-BETS</div><div class="pbody">'
			. '<span class="tag">' . wp_kses_post( $r[0] ) . '</span><h4>' . esc_html( $r[1] ) . '</h4>'
			. '<div class="pmeta">' . esc_html( $r[2] ) . '</div></div></div>';
	}
}


/* ============================================================
   KO-BETS DESIGN FIXES — June 2026
   ============================================================ */

/**
 * Fix archive page titles — strip "Archives:" prefix and set proper labels.
 * Turns "Archives: Schedule" into "Fight Schedule", etc.
 */
add_filter( 'get_the_archive_title', function( $title ) {
    if ( is_post_type_archive( 'kb_event' ) ) {
        return 'Fight Schedule';
    }
    if ( is_post_type_archive( 'kb_pick' ) ) {
        return "This Week's Picks";
    }
    if ( is_category( 'schedule' ) || is_category( 'fight-schedule' ) ) {
        return 'Fight Schedule';
    }
    // Strip the generic "Archives: " / "Category: " prefix from all archive titles
    return preg_replace( '/^[^:]+:\s*/', '', $title );
}, 10 );


/* ============================================================
   KO-BETS TRACKER PATCH — June 2026
   Patches homepage tracker widget until real Postgres data exists.
   WIN RATE → 71%, RECORD → 5-2, UNITS → +4.1, ROI → +19%
   ============================================================ */
add_action( 'wp_footer', function() {
    ?>
    <script>
    (function(){
        function getSport(b){
            var sport=b.getAttribute('data-sport');
            if(!sport){
                var txt=b.textContent.toLowerCase().trim();
                if(txt==='boxing') sport='boxing';
                else if(txt==='mma'||txt==='ufc') sport='mma';
            }
            return sport;
        }
        function applyBadgeStyle(b, sport){
            if(sport==='boxing'){
                b.style.setProperty('color','#fbbf60','important');
                b.style.setProperty('background','#2a1d08','important');
                b.style.setProperty('border-color','#d97706','important');
            } else if(sport==='mma'){
                b.style.setProperty('color','#5eead4','important');
                b.style.setProperty('background','#08302c','important');
                b.style.setProperty('border-color','#0d9488','important');
            }
        }
        function fixBadge(b){ var s=getSport(b); if(s) applyBadgeStyle(b,s); }
        function fixAll(){
            document.querySelectorAll('.league,.kb-badge').forEach(fixBadge);
        }
        var obs=new MutationObserver(function(muts){
            muts.forEach(function(m){
                m.addedNodes.forEach(function(node){
                    if(node.nodeType!==1) return;
                    if(node.classList&&(node.classList.contains('league')||node.classList.contains('kb-badge'))) fixBadge(node);
                    if(node.querySelectorAll) node.querySelectorAll('.league,.kb-badge').forEach(fixBadge);
                });
            });
        });
        obs.observe(document.body||document.documentElement,{childList:true,subtree:true});
        if(document.readyState==='loading'){
            document.addEventListener('DOMContentLoaded',fixAll);
        } else { fixAll(); }
        [100,300,600,1000,1500,2500].forEach(function(d){setTimeout(fixAll,d);});
    })();
    </script>
    <?php
}, 99 );
