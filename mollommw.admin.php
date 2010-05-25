<?php

class MollomMWAdminPage extends SpecialPage {
	function __construct() {
		parent::__construct('MollomMW', 'block');
	}

	function execute( $par ) {
		global $wgOut;
		$wgOut->setPageTitle(wfMsg('mollommw-admin'));

		$wgOut->addWikiText('== ' . wfMsg('mollommw-key-validation') . ' ==');

		try {
			$validKeys = Mollom::verifyKey();
			if ($validKeys) {
				$wgOut->addWikiText("'''" . wfMsg('mollommw-key-validation-success') . "'''");

				$wgOut->addWikiText('== ' . wfMsg('mollommw-stats') . ' ==');

				$totaldays = Mollom::getStatistics('total_days');
				$totalRejected = Mollom::getStatistics('total_rejected');
				$totalAccepted = Mollom::getStatistics('total_accepted');
				$acceptedToday = Mollom::getStatistics('today_accepted');
				$rejectedToday = Mollom::getStatistics('today_rejected');
				$acceptedYesterday = Mollom::getStatistics('yesterday_accepted');
				$rejectedYesterday = Mollom::getStatistics('yesterday_rejected');
		
				$wgOut->addWikiText(wfMsg('mollommw-days-protected', $totaldays));
				$html = <<<HTML
<table>
	<tr>
		<td></td>
		<td>%s</td>
		<td>%s</td>
	</tr>
	<tr>
		<td>%s</td>
		<td style="text-align: center">$acceptedToday</td>
		<td style="text-align: center">$rejectedToday</td>
	</tr>
	<tr>
		<td>%s</td>
		<td style="text-align: center">$acceptedYesterday</td>
		<td style="text-align: center">$rejectedYesterday</td>
	</tr>
	<tr>
		<td>%s</td>
		<td style="text-align: center">$totalAccepted</td>
		<td style="text-align: center">$totalRejected</td>
	</tr>
</table>
HTML;
				$wgOut->addHtml(sprintf($html, wfMsg('mollommw-stats-accepted'), wfMsg('mollommw-stats-rejected'),
					wfMsg('mollommw-stats-today'), wfMsg('mollommw-stats-yesterday'), wfMsg('mollommw-stats-total')));
				$wgOut->addWikiText(wfMsg('mollommw-stats-advanced'));
			} else {
				$wgOut->addWikiText("'''" . wfMsg('mollommw-key-validation-failure') . "'''");
			}
		} catch (Exception $e) {
			wfDebugLog('MollomMW', 'Exception on statistics page: ' . $e->getMessage());
			$wgOut->addWikiText("'''" . wfMsg('mollommw-mollom-error') . "''''");
		}
	}
}
