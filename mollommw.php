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
 * @author          Thomas Meire <blackskad+mollom@gmail.com>
 * @version         1.0
 *
 * @copyright       Copyright (c) 2010, Thomas Meire. All rights reserved.
 * @license         http://mollom.crsolutions.be/license Modified BSD License
 */

if (!defined('MEDIAWIKI')) { exit(1); }

require_once(dirname(__FILE__) . '/phpmollom/mollom.client.php');

define('MOLLOMMW_NAME', 'MollomWM');
define('MOLLOMMW_VERSION', '1.0');

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

if (isset($wgMollomDebug) && $wgMollomDebug) {
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

/* Connect the hooks for the mollom filters */
global $wgHooks;
$wgHooks['EditFilter'][] = 'MollomSpamFilter::onEditFilter';
$wgHooks['APIEditBeforeSave'][] = 'MollomSpamFilter::onAPIEditBeforeSave';

/**
 * Extension initialisation function, used to set up special pages.
 */
function setupMollomMW () {
	/* setup autoloading of special page classes */
	global $wgAutoloadClasses;
	$wgAutoloadClasses['MollomSpamFilter'] = dirname(__FILE__) . '/mollommw.filter.php';
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

	/* setup up the default special rights */
	global $wgMollomMWSpecialRights;
	if (isset($wgMollomMWSpecialRights) && $wgMollomMWSpecialRights) {
		$wgGroupPermissions['sysop']['mollommw-no-check'] = true;
		$wgGroupPermissions['bureaucrat']['mollommw-no-check'] = true;

		$wgGroupPermissions['email']['mollommw-no-captcha'] = true;
	}
}

