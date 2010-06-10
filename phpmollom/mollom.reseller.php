<?php

class MollomReseller extends Mollom {

	private static function checkType ($type) {
		return in_array($status, array('personal', 'company', 'customer', 'non-profit'));
	}


	/**
	 * Get a list of sites
	 * 
	 * @return array of public keys for the sites
	 */
	public static function listSites () {
		return self::doCall('listSites', array());
	}


	/**
	 * Provide information to Mollom to create a new site.
	 *
	 * @param string $url
	 * @param string $mail
	 * @param bool $status
	 * @param bool $testing
	 * @param string $type
	 * @param string $language
	 * @return a public/private keypair for the new site
	 */
	public function createSite ($url, $mail, $status, $testing, $type=null, $language=null) {
		$params = array();

		$params['url'] = $url;
		$params['mail'] = $mail;
		$params['status'] = $status;
		$params['testing'] = $testing;

		if ($type != null && self::checkType($type)) {
			$params['type'] = $type;
		}
		if ($language != null) {
			$params['language'] = $language;
		}
		return self::doCall('createSite', $params);
	}


	/**
	 * Update information about the site to which the client key belongs.
	 * Only the $clientkey is required, all the other parameters are optional.
	 * However, there has to be at least one optional parameter given.
	 *
	 * @param string $clientkey
	 * @param string $url
	 * @param string $mail
	 * @param bool $status
	 * @param bool $testing
	 * @param string $type
	 * @param string $language
	 * @return bool true on success, false on failure
	 */
	public function updateSite ($clientkey, $url=null, $mail=null, $status=null, $testing=null, $type=null, $language=null) {
		$params = array();
		
		$params['client_key'] = $clientkey;
		if ($url != null) {
			$params['url'] = $url;
		}
		if ($mail != null) {
			$params['mail'] = $mail;
		}
		if ($type != null && self::checkType($type)) {
			$params['type'] = $type;
		}
		if ($language != null) {
			$params['language'] = $language;
		}
		if ($status != null) {
			$params['status'] = $status;
		}
		if ($testing != null) {
			$params['testing'] = $testing;
		}

		if (count($params) == 1) {
			throw new Exception('At least one optional parameter has to be provided for updateSite');
		}
		return self::doCall('updateSite', $params);
	}


	/**
	 * Get information about the website to which the public key belongs.
	 * 
	 * @param string $clientkey
	 * @return array with information about the site
	 */
	public static function getSite ($clientkey) {
		$params = array ('client_key' => $clientkey);

		return self::doCall('getSite', $params);
	}


	/**
	 * Delete the site to which the public key belongs from Mollom's database.
	 *
	 * @param  string   $clientkey
	 * @return boolean 	True if deleted successfully, false otherwise.
	 */
	public function deleteSite ($clientkey) {
		$params = array ('client_key' => $clientkey);

		return self::doCall('deleteSite', $params);
	}
}

?>
