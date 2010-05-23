<?php

if (!defined('MEDIAWIKI')) { exit(1); }

require_once(dirname(__FILE__) . '/mollomphp/mollom.php');

define('MOLLOMMW_NAME', 'MollomWM');
define('MOLLOMMW_VERSION', '0.1');

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => MOLLOMMW_NAME,
	'version'     => MOLLOMMW_VERSION,
	'author'      => 'Thomas Meire',
	'url'         => 'http://github.com/blackskad/MollomMW',
	'description' => 'Mollom plugin for MediaWiki',
);

global $wgSpecialPages;
$wgSpecialPages['mollommw'] = array(
	'SpecialPage', /* class*/
	'MollomMW',    /* name */
	'editinterface',/* restriction*/
	true,          /* listed*/
	array('MollomSpamFilter', 'showAdminPage'), /* function*/
	false,         /* file*/
);

global $wgMollomDebug;
global $wgMollomPublicKey;
global $wgMollomPrivateKey;
global $wgMollomServerList;
global $wgMollomReverseProxyAddresses;

if ($wgMollomDebug) {
	$wgDebugLogGroups['MollomMW'] = dirname(__FILE__) . '/debug.log';
}

/* Setup the Mollom configuration */
Mollom::setUserAgent(MOLLOMMW_NAME . '/' . MOLLOMMW_VERSION);

if (isset($wgMollomServerList) && is_array($wgMollomServerList)) {
	Mollom::setServerList($wgMollomServerList);
}

if (isset($wgMollomReverseProxyAddresses) && is_array($wfMollomReverseProxyAddresses)) {
	Mollom::setAllowedReverseProxyAddresses($wfMollomReverseProxyAddresses);
}

Mollom::setPublicKey($wgMollomPublicKey);
Mollom::setPrivateKey($wgMollomPrivateKey);

$filter = new MollomSpamFilter();

/* Hook it up */
$wgHooks['EditFilter'][] = array($filter, 'wfMollomMWCheckEdit');

class MollomSpamFilter {

	var $sessionid;

	function getCaptchaHTML ($sessionid, $captchaHTML) {
		/* FIXME: i18n */
		return '<div class="mollom-captcha" style="padding: 10px 0;">' .
		       '    <strong>Word verification</strong><br>' .
		       '	<p>' .
		       '		Your edit looks like spam. To confirm your edit is not spam,' .
		       '        type the characters you see in the picture below. If you can\'t read them,'.
		       '        submit the form and a new image will be generated. Not case sensitive.' .
		       '	</p>' .
		       '	<input type="hidden" name="mollom-sessionid" value="' . $sessionid . '">' .
		       $captchaHTML . '<br>' .
		       '	<label for="mollom-solution">Captcha:</label>' .
		       '	<input type="text" class="captcha" name="mollom-solution"><br>' .
		       '</div>';
	}

	function showCaptcha (&$out) {
		$captcha = Mollom::getImageCaptcha($this->sessionid);
		$out->addHtml($this->getCaptchaHtml($captcha['session_id'], $captcha['html']));
	}

	/**
	 * Check edits from the webinterface for spam. Messages are rejected when they're
	 * 'spam'. Messages marked as 'unknown' or 'unsure' will trigger a captcha.
	 *
	 * @fixme: add error handling, it'll crash bigtime now if the servers are offline
	 */
	function wfMollomMWCheckEdit($editor, $text, $section, &$error) {

		// a captcha was solved, check it first.
		if (isset($_POST['mollom-sessionid']) && isset($_POST['mollom-solution'])) {
			if (Mollom::checkCaptcha($_POST['mollom-sessionid'], $_POST['mollom-solution'])) {
				wfDebugLog('MollomMW', 'Correctly solved a captcha');
				return true;
			}
		}

		// check the actual content
		$response = Mollom::checkContent(null, null, $text);
		wfDebugLog('MollomMW', 'Mollom Response: ' . var_export($response, true));
		switch ($response['spam']) {
			case 'ham':
				return true;
			case 'spam':
				$editor->spamPage();
				return false;
			default: /* 'unsure' or 'unknown' */
				$this->sessionid = $response['sessionid'];
				$editor->showEditForm(array(&$this, 'showCaptcha'));
				return false;
		}
	}
	
	static function showAdminPage () {
		global $wgOut;
		$wgOut->setPageTitle("MollomMW Administration");

		$wgOut->addWikiText("== Key validation ==");

		$validKeys = Mollom::verifyKey();
		if ($validKeys) {
			$wgOut->addWikiText("'''Your keys are valid.'''");

			$wgOut->addWikiText("== Statistics ==");

			$totaldays = Mollom::getStatistics('total_days');
			$totalRejected = Mollom::getStatistics('total_rejected');
			$totalAccepted = Mollom::getStatistics('total_accepted');
			$acceptedToday = Mollom::getStatistics('today_accepted');
			$rejectedToday = Mollom::getStatistics('today_rejected');
			$acceptedYesterday = Mollom::getStatistics('yesterday_accepted');
			$rejectedYesterday = Mollom::getStatistics('yesterday_rejected');
		
			$wgOut->addWikiText('Mollom has been protecting this site for ' . $totaldays . ' days.');
			$wgOut->addHtml(<<<HTML
<table>
	<tr>
		<td></td>
		<td>Accepted</td>
		<td>Rejected</td>
	</tr>
	<tr>
		<td>Today</td>
		<td>$acceptedToday</td>
		<td>$rejectedToday</td>
	</tr>
	<tr>
		<td>Yesterday</td>
		<td>$acceptedYesterday</td>
		<td>$rejectedYesterday</td>
	</tr>
	<tr>
		<td>Total</td>
		<td>$totalAccepted</td>
		<td>$totalRejected</td>
	</tr>
</table>
For more advanced statistics, visit your site's statistics at <a href="http://www.mollom.com/user">mollom.com</a>
HTML
			);
		} else {
			$wgOut->addWikiText("'''Your keys are invalid!''' Check [http://www.mollom.com/user mollom.com] for your correct keys. ");
		}
	}
}
