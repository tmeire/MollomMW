<?php

if (!defined('MEDIAWIKI')) { exit(1); }

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

require_once(dirname(__FILE__) . '/mollomphp/mollom.php');

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
		$response = Mollom::checkContent(null, null, 'unsure');
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
}
