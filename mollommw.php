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

require_once(dirname(__FILE__) . '/phpmollom/mollom.client.php');

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

$wgExtensionMessagesFiles['MollomMW'] = dirname(__FILE__) . '/mollommw.i18n.php';
$wgExtensionFunctions[] = 'setupMollomMW';

global $wgMollomDebug;
global $wgMollomPublicKey;
global $wgMollomPrivateKey;
global $wgMollomServerList;
global $wgMollomReverseProxyAddresses;

global $wgMollomMWAcceptPolicy;
global $wgMollomMWAPIAcceptPolicy;

/* Setup the Mollom configuration */
Mollom::setUserAgent(MOLLOMMW_NAME . '/' . MOLLOMMW_VERSION);

if ($wgMollomDebug) {
	$wgDebugLogGroups['MollomMW'] = dirname(__FILE__) . '/debug.log';
}

if (isset($wgMollomReverseProxyAddresses) && is_array($wgMollomReverseProxyAddresses)) {
	MollomClient::setAllowedReverseProxyAddresses($wgMollomReverseProxyAddresses);
}

if (isset($wgMollomRunsOnClusterSetup)) {
	MollomClient::setUsesServerSetup ($wgMollomRunsOnClusterSetup);
}

if (!isset($wgMollomMWAcceptPolicy) && !is_bool($wgMollomMWAcceptPolicy)) {
	$wgMollomMWAPIAcceptPolicy = true;
}

if (!isset($wgMollomMWAPIAcceptPolicy) && !is_bool($wgMollomMWAPIAcceptPolicy)) {
	$wgMollomMWAPIAcceptPolicy = false;
}

Mollom::setPublicKey($wgMollomPublicKey);
Mollom::setPrivateKey($wgMollomPrivateKey);

$filter = new MollomSpamFilter();

/* Connect the hooks for the mollom filters */
global $wgHooks;
$wgHooks['EditFilter'][] = $filter;
$wgHooks['onAPIEditBeforeSave'][] = $filter;

/**
 * Extension initialisation function, used to set up special pages.
 */
function setupMollomMW () {
	/* setup autoloading of special page classes */
	global $wgAutoloadClasses;
	$wgAutoloadClasses['MollomMWStatPage'] = dirname(__FILE__) . '/pages/mollommw.stats.php';
	$wgAutoloadClasses['MollomMWBlacklistPage'] = dirname(__FILE__) . '/pages/mollommw.blacklist.php';

	/* setup the special statistics page */
	global $wgSpecialPages;
	$wgSpecialPages['mollommw-statistics'] = 'MollomMWStatPage';
	$wgSpecialPages['mollommw-blacklists'] = 'MollomMWBlacklistPage';

	/* define special rights for the mollommw module */
	global $wgAvailableRights;
	$wgAvailableRights[] = 'mollommw-admin';			// mollommw administrator rights
	$wgAvailableRights[] = 'mollommw-no-check';			// no need to check the posts from this user
	$wgAvailableRights[] = 'mollommw-no-captcha';		// no need to show this user a captcha

	/* setup special permissions for the mollommw administration page */
	global $wgGroupPermissions;
	$wgGroupPermissions['sysop']['mollommw-admin'] = true;
}

/**
 * MollomSpamFilter does all the heavy lifting. It talks to the Mollom class
 * to verify the legitimacy of the edit.
 */
class MollomSpamFilter {

	var $sessionid;

	function getCaptchaHTML ($sessionid, $image, $audio) {
		return '<div class="mollom-captcha" style="padding: 10px 0;">
					<strong>' . wfMsg('mollommw-word-verification') . '</strong><br>
		       		<p>' . wfMsg('mollommw-possibly-spam') . '<br>
		       			<a href="#" onclick="onMollomCaptchaToggle()">' . wfMsg('mollommw-captcha-toggle') . '</a>
		       		</p>
		       		<input type="hidden" id="mollom-captcha-type" name="mollom-captcha-type" value="text">
		       		<input type="hidden" name="mollom-text-sessionid" value="' . $sessionid . '">
		       		<input type="hidden" name="mollom-image-sessionid" value="' . $image['session_id'] . '">
		       		<input type="hidden" name="mollom-audio-sessionid" value="' . $audio['session_id'] . '">
		       		<span id="mollom-captcha-image">' . $image['html'] . '</span>
		       		<span id="mollom-captcha-audio" style="display: none">' . $audio['html'] . '</span><br>
		       		<label for="mollom-solution">Captcha:</label>
		       		<input type="text" class="captcha" name="mollom-solution"><br>
		       </div>';
	}

	function showCaptcha(&$out) {
		global $wgScriptPath;
		$out->addScriptFile($wgScriptPath . '/extensions/mollommw/skins/mollommw.js');
		$image = MollomClient::getImageCaptcha($this->sessionid);
		$audio = MollomClient::getAudioCaptcha($this->sessionid);
		$out->addHtml($this->getCaptchaHtml($this->sessionid, $image, $audio));
	}

	/**
	 * Check edits from the webinterface for spam. Messages are rejected when they're
	 * 'spam'. Messages marked as 'unknown' or 'unsure' will trigger a captcha.
	 */
	function onEditFilter($editor, $text, $section, &$error) {
		global $wgUser, $wgMollomMWAcceptPolicy;

		/* load the i18n messages */
		wfLoadExtensionMessages('MollomMW');

		/* skip all users with mollommw-no-check or mollommw-admin rights */
		if ($wgUser->isAllowed('mollommw-no-check')) {
			return true;
		}

		// a captcha was solved, check it first.
		if (isset($_POST['mollom-captcha-type']) && isset($_POST['mollom-solution'])) {
			$imageSessionId = $_POST['mollom-image-sessionid'];
			$audioSessionId = $_POST['mollom-audio-sessionid'];
			try {
				$sessionid = ($_POST['mollom-captcha-type'] == "text") ? $imageSessionId : $audioSessionId;
				if (MollomClient::checkcaptcha($sessionid, $_POST['mollom-solution'])) {
					wfDebugLog('MollomMW', 'Correctly solved a captcha');
					return true;
				}
			} catch (Exception $e) {
				wfDebugLog('MollomMW', 'Exception while checking captcha: ' . $e->getMessage());
				return $wgMollomMWAcceptPolicy;
			}
		}

		// check the actual content
		try {
			$sessionid = null;
			if (isset($_POST['mollom-text-sessionid'])) {
				$sessionid = $_POST['mollom-text-sessionid'];
			}
			
			$id = null;
			$name = null;
			$email = null;
			if ($wgUser->getId() != 0) {
				$id = $wgUser->getId();
				$name = $wgUser->getName();
				$email = $wgUser->getEmail();
			}

			$response = MollomClient::checkContent($sessionid, $editor->mTitle, $text, $name, null, $email, null, $id);
			wfDebugLog('MollomMW', 'Mollom Response: ' . var_export($response, true));
			switch ($response['spam']) {
				case MOLLOM_HAM:
					return true;
				case MOLLOM_SPAM:
					$editor->spamPage();
					return false;
				default: /* 'unsure' or any other value */
					if ($wgUser->isAllowed('mollommw-no-captcha')) {
						return true;
					} else {
						$this->sessionid = $response['session_id'];
						$editor->showEditForm(array(&$this, 'showCaptcha'));
						return false;
					}
			}
		} catch (Exception $e) {
			wfDebugLog('MollomMW', 'Exception while checking content: ' . $e->getMessage());
			return $wgMollomMWAcceptPolicy;
		}
	}
	
	function onAPIEditBeforeSave (&$EditPage, $text, &$resultArr) {
		global $wgMollomwMWAPIAcceptPolicy;
		// check the actual content
		try {
			$response = MollomClient::checkContent(null, $EditPage->mTitle, $text);
			wfDebugLog('MollomMW', 'Mollom Response: ' . var_export($response, true));
			switch ($response['spam']) {
				case MOLLOM_SPAM:
					$resultArr[] = wfMsg('mollom-spam');
					return false;
				case MOLLOM_HAM:
				default: /* 'unsure' or unknown */
					return true;
			}
		} catch (Exception $e) {
			wfDebugLog('MollomMW', 'Exception while checking content: ' . $e->getMessage());
			return $wgMollomMWAPIAcceptPolicy;
		}
	}
}
