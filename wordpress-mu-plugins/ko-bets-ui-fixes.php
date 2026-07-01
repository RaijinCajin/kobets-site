<?php
/**
 * Plugin Name: KO-Bets UI Fixes
 * Description: Nav pills, pick cards, sport colors, typography, archive fixes, REST home-picks endpoint.
 * Version: 2.0
 * Auto-loaded as mu-plugin -- no activation needed.
 */

defined('ABSPATH') || exit;

/* ── 1. Archive / Blog title ──────────────────────────────────────────────── */
add_filter('get_the_archive_title', function ($title) {
    if (is_home() || is_front_page()) return 'Blog';
    if (is_post_type_archive('kb_pick')) return 'Pick History';
    if (is_category()) return single_cat_title('', false);
    if (is_tag()) return single_tag_title('', false);
    return $title;
});
add_filter('pre_get_document_title', function ($title) {
    if (is_home()) return 'Blog - KO-Bets';
    return $title;
});

/* ── 2. CSS ───────────────────────────────────────────────────────────────── */
add_action('wp_head', function () { ?>
<style id="kobets-ui-css">
/* ── Base font override: readable medium-weight throughout ── */
body, .entry-content, .pick, .pick *, .archive *, .card * {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
    font-weight: 500 !important;
    -webkit-font-smoothing: antialiased;
}
h1,h2,h3,h4,h5,h6,
.pick .matchup,
.pick-meta-line strong,
.sport-label,
.channel-label,
.time-label,
.pick .ourpick b,
.stat .num,
th {
    font-weight: 700 !important;
}

/* ── Nav pills ── */
.navlinks .menu-item > a, nav .menu-item > a, .nav .menu-item > a {
    display: inline-block !important;
    padding: 5px 13px !important;
    border: 1px solid #2e2e2e !important;
    border-radius: 20px !important;
    background: transparent !important;
    color: #bbb !important;
    font-size: 0.82em !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
    text-decoration: none !important;
    transition: border-color 0.15s, color 0.15s, background 0.15s !important;
    letter-spacing: .01em !important;
    line-height: 1.5 !important;
}
.navlinks .menu-item > a:hover, nav .menu-item > a:hover, .nav .menu-item > a:hover {
    border-color: #c9ff00 !important; color: #c9ff00 !important;
    background: rgba(201,255,0,0.06) !important;
}
.navlinks .menu-item.current-menu-item > a, nav .menu-item.current-menu-item > a, .nav .menu-item.current-menu-item > a {
    border-color: #c9ff00 !important; color: #c9ff00 !important;
    background: rgba(201,255,0,0.09) !important;
}
.navlinks .menu-item, nav .menu-item, .nav .menu-item { margin: 0 2px !important; }
nav .navright .btn, .nav .navright .btn {
    border-radius: 8px !important; padding: 7px 16px !important;
    font-size: 0.85em !important; font-weight: 700 !important;
}

/* ── Pick cards ── */
a.pick-card-link { display:block !important; text-decoration:none !important; color:inherit !important; }
.pick {
    border: 1px solid #252525 !important;
    border-radius: 10px !important;
    background: #111 !important;
    transition: border-color 0.18s, transform 0.14s, box-shadow 0.18s !important;
    cursor: pointer !important;
    overflow: hidden !important;
}
a.pick-card-link:hover .pick {
    border-color: #c9ff00 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 18px rgba(201,255,0,0.07) !important;
}

/* Sport badge colors */
.pick .pick-top .league, .pick-top .league {
    background: #1c1c1c !important;
    color: #c9ff00 !important;
    border: 1px solid #2a2a2a !important;
    font-weight: 700 !important;
    padding: 3px 10px !important;
    border-radius: 20px !important;
    font-size: 0.78em !important;
    letter-spacing: .06em !important;
    display: inline-block !important;
    text-transform: uppercase !important;
}
.pick .league[data-sport="boxing"]     { color: #e53935 !important; border-color: #e5393544 !important; }
.pick .league[data-sport="muay-thai"]  { color: #ff6d00 !important; border-color: #ff6d0044 !important; }
.pick .league[data-sport="kickboxing"] { color: #1e88e5 !important; border-color: #1e88e544 !important; }
.pick .league[data-sport="mma"],
.pick .league[data-sport="ufc"]        { color: #c9ff00 !important; border-color: #c9ff0044 !important; }

/* Result badges */
.pick .res {
    font-size: 0.76em !important;
    font-weight: 700 !important;
    padding: 3px 9px !important;
    border-radius: 20px !important;
    text-transform: uppercase !important;
    letter-spacing: .06em !important;
}
.pick .res.live, .pick .res.upcoming {
    background: #1a1a0a !important; color: #c9ff00 !important; border: 1px solid #3a3a00 !important;
}
.pick .res.win   { background:#0d200d !important; color:#4caf50 !important; border:1px solid #1e4a1e !important; }
.pick .res.loss  { background:#200d0d !important; color:#f44336 !important; border:1px solid #4a1e1e !important; }
.pick .res.push  { background:#1a1a1a !important; color:#888 !important; border:1px solid #333 !important; }

/* Pick body content */
.pick .matchup {
    font-weight: 700 !important;
    font-size: 1.05em !important;
    color: #eee !important;
    margin-bottom: 6px !important;
    line-height: 1.3 !important;
}
.pick-meta-line {
    font-size: 0.82em !important;
    color: #aaa !important;
    margin-bottom: 4px !important;
    font-weight: 600 !important;
}
.pick-meta-line strong { color: #ddd !important; font-weight: 700 !important; }
.pick .ourpick { font-size: 0.95em !important; color: #ccc !important; margin-bottom: 8px !important; }
.pick .ourpick b { color: #c9ff00 !important; font-weight: 700 !important; }

/* Confidence bar */
.pick .barbg { background:#1a1a1a !important; border-radius:4px !important; overflow:hidden !important; }
.pick .bar   { background:#c9ff00 !important; height:6px !important; border-radius:4px !important; }
.pick .conf .lbl {
    display: flex !important;
    justify-content: space-between !important;
    font-size: 0.78em !important;
    color: #888 !important;
    font-weight: 600 !important;
    margin-bottom: 4px !important;
}

/* Watch button */
.pick-watch-btn {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 14px;
    background: #0064c8;
    color: #fff !important;
    border-radius: 6px;
    font-size: 0.82em;
    font-weight: 700;
    text-decoration: none;
    letter-spacing: .02em;
}

/* ── Pick History archive (replaces raw /picks/ list) ── */
.post-type-archive-kb_pick .entry-header,
.post-type-archive-kb_pick .entry-content p:first-child { display: none; }
.pick-history-card {
    background: #111;
    border: 1px solid #222;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 16px;
    transition: border-color 0.15s;
}
.pick-history-card:hover { border-color: #444; }
.pick-history-card .ph-sport {
    font-size: 0.72em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 6px;
    display: block;
}
.pick-history-card .ph-matchup { font-size: 1em; font-weight: 700; color: #eee; margin-bottom: 4px; }
.pick-history-card .ph-pick    { font-size: 0.88em; color: #aaa; margin-bottom: 8px; }
.pick-history-card .ph-pick b  { color: inherit; font-weight: 700; }
.pick-history-card .ph-meta    { font-size: 0.76em; color: #555; }
.ph-sport-mma, .ph-sport-ufc       { color: #c9ff00; }
.ph-sport-boxing                    { color: #e53935; }
.ph-sport-muay-thai, .ph-sport-muay_thai { color: #ff6d00; }
.ph-sport-kickboxing                { color: #1e88e5; }
.ph-status-win  { color: #4caf50 !important; }
.ph-status-loss { color: #f44336 !important; }
.ph-status-upcoming { color: #c9ff00 !important; }

/* ── Track record table ── */
.track-record-table { width:100%; border-collapse:collapse; margin-top:20px; font-size:0.9em; }
.track-record-table th {
    background:#1a1a1a; color:#aaa; font-weight:700;
    text-align:left; padding:10px 12px; font-size:0.78em;
    text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #2a2a2a;
}
.track-record-table td { padding:10px 12px; border-bottom:1px solid #1a1a1a; color:#ccc; font-weight:500; }
.track-record-wrap .stat-row { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px; }
.track-record-wrap .stat {
    background:#111; border:1px solid #222; border-radius:8px;
    padding:14px 20px; min-width:100px; text-align:center;
}
.track-record-wrap .stat .num { font-size:1.6em; font-weight:700; color:#eee; }
.track-record-wrap .stat .num.win { color:#4caf50; }
.track-record-wrap .stat .lbl { font-size:0.72em; color:#666; text-transform:uppercase; letter-spacing:.05em; margin-top:4px; }

/* ── Schedule ── */
.schedule-list { margin-top: 16px; }
.schedule-row {
    display: flex; gap: 16px; align-items: baseline;
    padding: 12px 0; border-bottom: 1px solid #1a1a1a; font-weight: 500;
}
.sched-date  { color: #888; font-size: 0.82em; min-width: 130px; font-weight: 600; }
.sched-event { color: #ddd; font-size: 0.95em; font-weight: 600; }

/* ── Misc ── */
.kobets-sport-pill {
    display:inline-block; font-size:0.72em; font-weight:700;
    padding:2px 7px; border-radius:4px; letter-spacing:.06em;
    text-transform:uppercase; margin-right:6px; vertical-align:middle;
}
.navright .btn { display: none !important; }
li#menu-item-23 { border-left:1px solid #2e2e2e !important; margin-left:10px !important; padding-left:10px !important; }
</style>
<?php }, 99);

/* ── 3. Gap 1: REST endpoint for homepage picks ───────────────────────────── */
add_action('rest_api_init', function () {
    register_rest_route('kobets/v1', '/home-picks', [
        'methods'             => 'GET',
        'callback'            => function () {
            $posts = get_posts([
                'post_type'      => 'kb_pick',
                'posts_per_page' => 3,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_status'    => 'publish',
            ]);
            return array_map(function ($p) {
                return [
                    'matchup'      => get_post_meta($p->ID, 'kb_matchup',      true),
                    'pick'         => get_post_meta($p->ID, 'kb_selection',    true),
                    'conf'         => get_post_meta($p->ID, 'kb_confidence',   true) ?: 60,
                    'status'       => get_post_meta($p->ID, 'kb_status',       true) ?: 'upcoming',
                    'league'       => get_post_meta($p->ID, 'kb_sport',        true) ?: 'MMA',
                    'date'         => get_post_meta($p->ID, 'kb_event_date',   true),
                    'channel'      => get_post_meta($p->ID, 'kb_stream_url',   true),
                    'channelLabel' => get_post_meta($p->ID, 'kb_stream_label', true) ?: 'Watch Live',
                ];
            }, $posts);
        },
        'permission_callback' => '__return_true',
    ]);
});

/* ── 4. JS via wp_footer ──────────────────────────────────────────────────── */
add_action('wp_footer', function () { ?>
<script id="kobets-fixes">
(function () {

var SPORT_COLORS = {
    'mma': '#c9ff00', 'ufc': '#c9ff00',
    'boxing': '#e53935',
    'muay thai': '#ff6d00', 'muay-thai': '#ff6d00',
    'kickboxing': '#1e88e5'
};

function sportColor(s) {
    var k = (s || '').toLowerCase();
    for (var n in SPORT_COLORS) {
        if (k.indexOf(n) !== -1) return SPORT_COLORS[n];
    }
    return '#c9ff00';
}

function sportSlug(s) {
    return (s || '').toLowerCase().replace(/\s+/g, '-');
}

function buildCard(p) {
    var color = sportColor(p.league);
    var slug = sportSlug(p.league);
    var statusClass = p.status === 'win' ? 'win' : (p.status === 'loss' ? 'loss' : 'upcoming');
    var statusLabel = {win:'WIN', loss:'LOSS', push:'PUSH'}[p.status] || 'UPCOMING';
    var conf = parseInt(p.conf) || 60;

    var a = document.createElement('a');
    a.className = 'pick-card-link';
    a.href = '/this-weeks-picks/';

    a.innerHTML =
        '<div class="pick" style="border-color:' + color + '22;">' +
        '<div class="pick-top">' +
        '<span class="league" data-sport="' + slug + '" style="color:' + color + ';border-color:' + color + '44;">' + (p.league || 'MMA').toUpperCase() + '</span>' +
        '<span class="res ' + statusClass + '">' + statusLabel + '</span>' +
        '</div>' +
        '<div class="pick-body">' +
        '<div class="pick-meta-line"><strong>' + (p.date || '') + '</strong></div>' +
        '<div class="matchup">' + (p.matchup || '') + '</div>' +
        '<div class="ourpick">Our read <b style="color:' + color + ';">' + (p.pick || '') + '</b></div>' +
        '<div class="conf">' +
        '<div class="lbl"><span>Confidence</span><span>' + conf + '%</span></div>' +
        '<div class="barbg"><div class="bar" style="width:' + conf + '%;background:' + color + ';"></div></div>' +
        '</div>' +
        (p.channel ? '<a class="pick-watch-btn" href="' + p.channel + '" target="_blank" rel="noopener" onclick="event.stopPropagation();">' + (p.channelLabel || 'Watch Live') + '</a>' : '') +
        '</div></div>';
    return a;
}

/* ── Homepage: fetch picks from REST endpoint ── */
function loadHomePicks() {
    if (window.location.pathname !== '/' && window.location.pathname !== '') return;
    var c = document.querySelector('.picks');
    if (!c) return;
    fetch('/wp-json/kobets/v1/home-picks')
        .then(function(r){ return r.json(); })
        .then(function(picks) {
            c.innerHTML = '';
            picks.forEach(function(p){ c.appendChild(buildCard(p)); });
        })
        .catch(function() {
            /* silently fail — static fallback remains */
        });
}

/* ── Pick History archive: style raw CPT posts as cards ── */
function styleArchive() {
    if (document.body.className.indexOf('post-type-archive-kb_pick') === -1 &&
        window.location.pathname.indexOf('/picks') === -1) return;

    /* Retitle the archive heading */
    document.querySelectorAll('.page-title, .archive-title, h1.entry-title').forEach(function(el){
        if (el.textContent.toLowerCase().indexOf('pick') !== -1 ||
            el.textContent.toLowerCase().indexOf('archive') !== -1) {
            el.textContent = 'Pick History';
        }
    });

    /* Style each post article as a pick-history-card */
    document.querySelectorAll('article').forEach(function(art) {
        var titleEl = art.querySelector('.entry-title a, h2 a, h3 a');
        var excerptEl = art.querySelector('.entry-summary, .entry-content');
        var dateEl = art.querySelector('.entry-date, time');
        if (!titleEl) return;

        var title = titleEl.textContent.trim();
        var excerpt = excerptEl ? excerptEl.textContent.trim().substring(0,180) : '';
        var date = dateEl ? dateEl.textContent.trim() : '';
        var href = titleEl.href;

        /* Detect sport from title/excerpt */
        var sportText = (title + ' ' + excerpt).toLowerCase();
        var sport = 'mma';
        if (sportText.indexOf('boxing') !== -1) sport = 'boxing';
        else if (sportText.indexOf('muay') !== -1) sport = 'muay-thai';
        else if (sportText.indexOf('kickbox') !== -1) sport = 'kickboxing';
        else if (sportText.indexOf('ufc') !== -1 || sportText.indexOf('mma') !== -1) sport = 'mma';

        var color = sportColor(sport);

        /* Detect result */
        var status = 'upcoming';
        if (/\bwin\b/i.test(excerpt) || /WIN/.test(excerpt)) status = 'win';
        else if (/\bloss\b/i.test(excerpt) || /LOSS/.test(excerpt)) status = 'loss';

        art.innerHTML =
            '<a href="' + href + '" style="text-decoration:none;color:inherit;display:block;">' +
            '<div class="pick-history-card">' +
            '<span class="ph-sport ph-sport-' + sport + '">' + sport.replace('-',' ').toUpperCase() + '</span>' +
            '<div class="ph-matchup">' + title + '</div>' +
            '<div class="ph-pick" style="color:' + (status==='win'?'#4caf50':status==='loss'?'#f44336':'#aaa') + ';">' +
            (status !== 'upcoming' ? '<b>' + status.toUpperCase() + '</b> &mdash; ' : '') + excerpt +
            '</div>' +
            '<div class="ph-meta">' + date + '</div>' +
            '</div></a>';
    });
}

/* ── Fix "See this week's analysis" button destination ── */
function fixAnalysisBtn() {
    document.querySelectorAll('a').forEach(function(a) {
        if (a.href && a.href.indexOf('/picks/') !== -1 &&
            (a.textContent.toLowerCase().indexOf('analysis') !== -1 ||
             a.textContent.toLowerCase().indexOf('week') !== -1)) {
            a.href = '/this-weeks-picks/';
        }
    });
}

/* ── Confidence % fix ── */
function fixConf() {
    document.querySelectorAll('.pick .conf').forEach(function(conf){
        var bar = conf.querySelector('.bar'), label = conf.querySelector('.lbl span:last-child');
        if (!bar || !label) return;
        var pct = parseInt(bar.style.width, 10);
        if (isNaN(pct) || pct === 0) return;
        var cur = label.textContent.trim();
        if (['Medium','High','Lean','Low'].indexOf(cur) !== -1 || /^[0-9]+$/.test(cur)) {
            label.textContent = pct + '%';
        }
    });
}

/* ── Wrap orphaned pick cards with links ── */
function wrapCards() {
    document.querySelectorAll('.pick').forEach(function(card) {
        if (card.closest('a')) return;
        var lnk = document.createElement('a');
        lnk.className = 'pick-card-link';
        lnk.href = '/this-weeks-picks/';
        card.parentNode.insertBefore(lnk, card);
        lnk.appendChild(card);
    });
}

function run() {
    loadHomePicks();
    styleArchive();
    fixAnalysisBtn();
    wrapCards();
    fixConf();
    setTimeout(fixConf, 800);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
} else {
    run();
}

})();
</script>
<?php }, 20);
