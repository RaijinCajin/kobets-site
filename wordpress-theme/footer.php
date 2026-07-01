<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<footer class="site-footer">
	<div class="wrap">
		<div class="foot-grid">
			<div>
				<div class="logo" style="font-size:24px;margin-bottom:12px">KO<span>-BETS</span></div>
				<p style="color:var(--muted);font-size:13.5px;max-width:280px">Educational odds analysis with a transparent, verifiable track record. We do not take bets, hold funds, or accept sportsbook referrals.</p>
				<div class="rg"><span>18+ ONLY</span><span>GAMBLE RESPONSIBLY</span><span>1-800-GAMBLER</span></div>
			</div>
			<div class="foot">
				<h5>Picks</h5>
				<a href="<?php echo esc_url( home_url( '/picks/' ) ); ?>">This week</a>
				<a href="<?php echo esc_url( home_url( '/track-record/' ) ); ?>">Track record</a>
				<a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>">Methodology</a>
			</div>
			<div class="foot">
				<h5>Learn</h5>
				<a href="<?php echo esc_url( home_url( '/learn-odds/' ) ); ?>">Odds basics</a>
				<a href="<?php echo esc_url( home_url( '/fighters/' ) ); ?>">Fighter profiles</a>
				<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
			</div>
			<div class="foot">
				<h5>Site</h5>
				<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a>
				<a href="<?php echo esc_url( home_url( '/merch/' ) ); ?>">Merch</a>
				<a href="<?php echo esc_url( home_url( '/responsible-gambling/' ) ); ?>">Responsible gambling</a>
				<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a>
			</div>
		</div>
		<div class="disclaimer">
			<b>Disclaimer:</b> KO-Bets is an independent educational and informational website. All content is provided for entertainment and informational purposes only and does not constitute betting, financial, or professional advice. Past performance does not guarantee future results &mdash; nothing on this site is a guaranteed pick. You are responsible for knowing and complying with the gambling laws in your jurisdiction. If you or someone you know has a gambling problem, call 1-800-GAMBLER. Must be 18+ (or the legal age in your jurisdiction). KO-Bets is not affiliated with, and does not accept referral commissions from, any sportsbook or betting operator.
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
