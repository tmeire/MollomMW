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

	private function exceptionOccured($e) {
		global $wgOut;
		wfDebugLog('MollomMW', 'Exception on statistics page: ' . $e->getMessage());
		$wgOut->addWikiText("'''" . wfMsg('mollommw-mollom-error') . "'''");
	}

	public function execute () {
		global $wgOut, $wgUser, $wgScriptPath;

		/* check for user permissions */
		if (!$this->userCanExecute($wgUser)) {
			$this->displayRestrictionError();
			return;
		}
		
		try {
			if (!Mollom::verifyKey()) {
				$wgOut->addWikiText("'''" . wfMsg('mollommw-key-validation-failure') . "'''");
				return;
			}
		} catch (Exception $e) {
			$this->exceptionOccured($e);
			return;
		}

		if (isset($_POST['add']) && isset($_POST['url'])) {
			MollomClient::addBlacklistURL($_POST['url']);
		}

		if (isset($_POST['remove']) && isset($_POST['url'])) {
			MollomClient::removeBlacklistURL($_POST['url']);
		}

		if (isset($_POST['add']) && isset($_POST['text']) && isset($_POST['context']) && isset($_POST['reason'])) {
			MollomClient::addBlacklistText($_POST['text'], $_POST['context'], $_POST['reason']);
		}

		if (isset($_POST['remove']) && isset($_POST['text']) && isset($_POST['context']) && isset($_POST['reason'])) {
			MollomClient::removeBlacklistText($_POST['text'], $_POST['context'], $_POST['reason']);
		}

		$wgOut->addExtensionStyle($wgScriptPath . '/extensions/mollommw/skins/mollommw.css');
		$wgOut->setPageTitle(wfMsg('mollommw-blacklists'));

		$wgOut->addWikiText('== ' . wfMsg('mollommw-blacklist-url-title') . ' ==');
		try {
			$urls = MollomClient::listBlacklistURL();
			$wgOut->addHtml('<table class="blacklist">');
			foreach ($urls as $url) {
				$wgOut->addHtml('<tr>');
				$wgOut->addHtml('	<td style="padding-right: 100px;">' . wfMsg('mollommw-blacklist-addedon', $url['url'], date('d-m-Y H:i', strtotime($url['created']))) . '</td>');
				$wgOut->addHtml('
					<td><form method="post">
						<input type="hidden" name="url" value="' . $url['url'] . '">
						<input type="submit" name="remove" value="' . wfMsg('mollommw-blacklist-url-remove') . '">
					</form></td>');
				$wgOut->addHtml('</tr>');
			}
			$wgOut->addHtml('<tr>
				<form method="post">
					<td><input type="text" name="url"></td>
					<td><input type="submit" name="add" value="' . wfMsg('mollommw-blacklist-url-add') . '"></td>
				</form>');
			$wgOut->addHtml('</table><br>');
		} catch (Exception $e) {
			$this->exceptionOccured($e);
			return;
		}


		$wgOut->addWikiText('== ' . wfMsg('mollommw-blacklist-text-title') . ' ==');
		try {
			$entries = MollomClient::listBlacklistText();
			$wgOut->addHtml('<table>
				<thead>
					<tr>
						<td>' . wfMsg('mollommw-blacklist-text') . '</td>
						<td>' . wfMsg('mollommw-blacklist-context') . '</td>
						<td>' . wfMsg('mollommw-blacklist-reason') . '</td>
						<td></td>
					</tr>
				</thead>
			');
			foreach ($entries as $entry) {
				$wgOut->addHtml('<tr>');
				$wgOut->addHtml('	<td>' . wfMsg('mollommw-blacklist-addedon', $entry['text'], date('d-m-Y H:i', strtotime($entry['created']))) . '</td>');
				$wgOut->addHtml('	<td>' . wfMsg('mollommw-blacklist-context-' . $entry['context']) . '</td>');
				$wgOut->addHtml('	<td>' . wfMsg('mollommw-blacklist-reason-' . $entry['reason']) . '</td>');
					$wgOut->addHtml('
					<td><form method="post">
						<input type="hidden" name="text" value="' . $entry['text'] . '">
						<input type="hidden" name="context" value="' . $entry['context'] . '">
						<input type="hidden" name="reason" value="' . $entry['reason'] . '">
						<input type="submit" name="remove" value="' . wfMsg('mollommw-blacklist-text-remove') . '">
					</form></td>');
				$wgOut->addHtml('</tr>');
			}
			$wgOut->addHtml('<tr>');
			$wgOut->addHtml('	<form method="post">');
			$wgOut->addHtml('		<td><input type="text" name="text"></td>');
			$wgOut->addHtml('		<td>');
			$wgOut->addHtml('			<select name="context">');
			$wgOut->addHtml('				<option value="everything">' . wfMsg('mollommw-blacklist-context-everything') . '</option>');
			$wgOut->addHtml('				<option value="links">' . wfMsg('mollommw-blacklist-context-links') . '</option>');
			$wgOut->addHtml('				<option value="author">' . wfMsg('mollommw-blacklist-context-author') . '</option>');
			$wgOut->addHtml('			</select>');
			$wgOut->addHtml('		</td>');
			$wgOut->addHtml('		<td>');
			$wgOut->addHtml('			<select name="reason">');
			$wgOut->addHtml('				<option value="spam">' . wfMsg('mollommw-blacklist-reason-spam') . '</option>');
			$wgOut->addHtml('				<option value="profanity">' . wfMsg('mollommw-blacklist-reason-profanity') . '</option>');
			$wgOut->addHtml('				<option value="low-quality">' . wfMsg('mollommw-blacklist-reason-low-quality') . '</option>');
			$wgOut->addHtml('				<option value="unwanted">' . wfMsg('mollommw-blacklist-reason-unwanted') . '</option>');
			$wgOut->addHtml('			</select>');
			$wgOut->addHtml('		</td>');
			$wgOut->addHtml('		<td><input type="submit" name="add" value="' . wfMsg('mollommw-blacklist-text-add') . '"></td>');
			$wgOut->addHtml('	</form>');
			$wgOut->addHtml('</tr></table>');
		} catch (Exception $e) {
			$this->exceptionOccured($e);
			return;
		}
	}
}

?>
