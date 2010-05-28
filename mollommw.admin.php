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

class MollomMWAdminPage extends SpecialPage {
	function __construct() {
		parent::__construct('MollomMW', 'block');

		/* load the i18n messages */
		wfLoadExtensionMessages('MollomMW');
	}

	function execute( $par ) {
		global $wgOut;
		$wgOut->setPageTitle(wfMsg('mollommw'));

		$wgOut->addWikiText('== ' . wfMsg('mollommw-key-validation') . ' ==');

		try {
			$validKeys = Mollom::verifyKey();
			if ($validKeys) {
				$wgOut->addWikiText("'''" . wfMsg('mollommw-key-validation-success') . "'''");

				$wgOut->addWikiText('== ' . wfMsg('mollommw-stats') . ' ==');

				$wgOut->addHtml('<embed src="http://mollom.com/statistics.swf?key=' . Mollom::getPublicKey() . '"
					quality="high" width="500" height="480" name="Mollom" align="middle" play="true" loop="false" allowScriptAccess="sameDomain"
					type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer"></embed>');
			} else {
				$wgOut->addWikiText("'''" . wfMsg('mollommw-key-validation-failure') . "'''");
			}
		} catch (Exception $e) {
			wfDebugLog('MollomMW', 'Exception on statistics page: ' . $e->getMessage());
			$wgOut->addWikiText("'''" . wfMsg('mollommw-mollom-error') . "''''");
		}
	}
}
