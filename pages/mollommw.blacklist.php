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

class MollomMWBlacklistPage extends SpecialPage {
	function __construct() {
		parent::__construct('mollommw-blacklists', 'mollommw-admin');

		/* load the i18n messages */
		wfLoadExtensionMessages('MollomMW');
	}

	public function execute () {
		global $wgOut;
		
		if (isset($_POST['add']) && isset($_POST['url'])) {
			Mollom::addBlacklistURL($_POST['url']);
		}

		if (isset($_POST['remove']) && isset($_POST['url'])) {
			Mollom::removeBlacklistURL($_POST['url']);
		}

		$wgOut->setPageTitle(wfMsg('mollommw-blacklists'));

		$wgOut->addWikiText('== ' . wfMsg('mollommw-blacklist-url') . ' ==');
		
		$urls = Mollom::listBlacklistURL();
		$wgOut->addHtml('<table>');
		foreach ($urls as $url) {
			$wgOut->addHtml('<tr>');
			$wgOut->addHtml('	<td>' . $url['url'] . ' added on ' . date('d-m-Y H:i', strtotime($url['created'])) . '</td>');
			$wgOut->addHtml('
				<td><form method="post">
					<input type="hidden" name="url" value="' . $url['url'] . '">
					<input type="submit" name="remove" value="' . wfMsg('mollommw-blacklist-url-remove') . '">
				</form></td>');
			$wgOut->addHtml('</tr>');
		}
		$wgOut->addHtml('</table>');
		
		$wgOut->addHtml('<form method="post"><input type="text" name="url"><br><input type="submit" name="add" value="' . wfMsg('mollommw-blacklist-url-add') . '"></form>');

		//$wgOut->addWikiText('== ' . wfMsg('mollommw-blacklist-text') . ' ==');
	}
}

?>
