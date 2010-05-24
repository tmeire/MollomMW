<?php
/**
 * Copyright (c) 2010, Thomas Meire. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * This software is provided by the author ``as is'' and any express or implied
 * warranties, including, but not limited to, the implied warranties of
 * merchantability and fitness for a particular purpose are disclaimed.
 * In no event shall the author be liable for any direct, indirect, incidental,
 * special, exemplary, or consequential damages (including, but not limited to,
 * procurement of substitute goods or services; loss of use, data, or profits;
 * or business interruption) however caused and on any theory of liability,
 * whether in contract, strict liability, or tort (including negligence or
 * otherwise) arising in any way out of the use of this software, even if
 * advised of the possibility of such damage.
 *
 * @author			Thomas Meire <blackskad+mollom@gmail.com>
 * @version			0.0.1
 *
 * @copyright		Copyright (c) 2010, Thomas Meire. All rights reserved.
 * @license			http://mollom.crsolutions.be/license Modified BSD License
 */

if (!defined('MEDIAWIKI')) { exit(1); }

require_once(dirname(__FILE__) . '/phpmollom/mollom.php');

define('MOLLOMMW_NAME', 'MollomWM');
define('MOLLOMMW_VERSION', '0.0.1');

$wgExtensionCredits['other'][] = array(
	'path'        => __FILE__,
	'name'        => MOLLOMMW_NAME,
	'version'     => MOLLOMMW_VERSION,
	'author'      => 'Thomas Meire',
	'url'         => 'http://github.com/blackskad/MollomMW',
	'description' => 'Mollom plugin for MediaWiki',
);

global $wgExtensionFunctions;
$wgExtensionFunctions[] = 'setupMollomMW';

global $wgMollomDebug;
global $wgMollomPublicKey;
global $wgMollomPrivateKey;
global $wgMollomServerList;
global $wgMollomReverseProxyAddresses;

/* Setup the Mollom configuration */
Mollom::setUserAgent(MOLLOMMW_NAME . '/' . MOLLOMMW_VERSION);

if ($wgMollomDebug) {
	$wgDebugLogGroups['MollomMW'] = dirname(__FILE__) . '/debug.log';
}

if (isset($wgMollomServerList) && is_array($wgMollomServerList)) {
	Mollom::setServerList($wgMollomServerList);
}

if (isset($wgMollomReverseProxyAddresses) && is_array($wfMollomReverseProxyAddresses)) {
	Mollom::setAllowedReverseProxyAddresses($wfMollomReverseProxyAddresses);
}

Mollom::setPublicKey($wgMollomPublicKey);
Mollom::setPrivateKey($wgMollomPrivateKey);

/**
 * Extension initialisation function, used to set up i18n, hooks and special pages.
 */
function setupMollomMW () {
	/* load the i18n messages */
	global $wgExtensionMessagesFiles;
	$wgExtensionMessagesFiles['MollomMW'] = dirname(__FILE__) . '/mollommw.i18n.php';
	wfLoadExtensionMessages('MollomMW');

	/* setup the special statistics page */
	global $wgSpecialPages;
	$wgSpecialPages['mollommw'] = array(
		'SpecialPage',  /* class*/
		'MollomMW',     /* name */
		'editinterface',/* restriction*/
		true,           /* listed*/
		array('MollomSpamFilter', 'showAdminPage'), /* function*/
		false,          /* file*/
	);

	/* Hook it up */
	global $wgHooks;
	$filter = new MollomSpamFilter();
	$wgHooks['EditFilter'][] = array($filter, 'wfMollomMWCheckEdit');
}

/**
 * MollomSpamFilter does all the heavy lifting. It talks to the Mollom class
 * to verify the legitimacy of the edit.
 */
class MollomSpamFilter {

	var $sessionid;

	function getCaptchaHTML ($sessionid, $captchaHTML) {
		/* FIXME: i18n */
		return '<div class="mollom-captcha" style="padding: 10px 0;">' .
		       '    <strong>' . wfMsg('mollommw-word-verification') . '</strong><br>' .
		       '	<p>' . wfMsg('mollommw-possibly-spam') . '</p>' .
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
	 */
	function wfMollomMWCheckEdit($editor, $text, $section, &$error) {

		// a captcha was solved, check it first.
		if (isset($_POST['mollom-sessionid']) && isset($_POST['mollom-solution'])) {
			try {
				if (Mollom::checkCaptcha($_POST['mollom-sessionid'], $_POST['mollom-solution'])) {
					wfDebugLog('MollomMW', 'Correctly solved a captcha');
					return true;
				}
			} catch (Exception $e) {
				wfDebugLog('MollomMW', 'Exception while checking captcha: ' . $e->getMessage());
				// What's the default action if this fails?
				// Accept it for now...
				return true;
			}
		}

		// check the actual content
		try {
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
		} catch (Exception $e) {
			wfDebugLog('MollomMW', 'Exception while checking content: ' . $e->getMessage());
			// What's the default action if this fails?
			// Accept it for now...
			return true;
		}
	}
	
	static function showAdminPage () {
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
