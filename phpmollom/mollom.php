<?php
/**
 * Mollom class
 *
 * This source file can be used to communicate with mollom (http://mollom.com)
 *
 * The class is documented in the file itself, but you can find more documentation and examples on the docs-page (http://mollom.crsolutions.be/docs).
 * If you find any bugs help me out and report them. Reporting can be done by sending an email to php-mollom-bugs[at]verkoyen[dot]eu. If you report a bug, make sure you give me enough information (include your code).
 * If you have questions, try the Mollom-forum, don't send them by mail, I won't read them.
 *
 * Changelog since 1.0.1
 * - Fixed a nasty bug. Possible infinite loop in doCall().
 * - Fixed getServerList. I misinterpreted the documentation, so now the defaultserver is xmlrpc.mollom.com instead of the first fallback-server.
 * - Fixed the timeout-issue. With fsockopen the timeout on connect wasn't respected. Rewrote the doCall function to use CURL over sockets.
 *
 * Changelog since 1.1.0
 * - Fixed a problem with the IP-addresses. see http://blog.verkoyen.eu/2008/07/12/important-php-mollom-update/
 *
 * Changelog since 1.1.1
 * - PHPMollom was using HTTP 1.1, now HTTP 1.0.
 * - Fallbackserver are hardcoded, when no servers could be retrieved the fallbacks are used
 *
 * Changelog since 1.1.2
 * - Typo
 * - New Licence: BSD Modified
 *
 * License
 * Copyright (c) 2008, Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author ``as is'' and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author			Tijs Verkoyen <mollom@verkoyen.eu>
 * @version			1.1.3
 *
 * @copyright		Copyright (c) 2008, Tijs Verkoyen. All rights reserved.
 * @license			http://mollom.crsolutions.be/license BSD License
 *
 * Additional changes by Thomas Meire.
 */

require_once (dirname(__FILE__) . '/xmlrpc.php');
require_once (dirname(__FILE__) . '/cache.php');

class Mollom
{
	/**
	 * Your private key
	 *
	 * Set it by calling Mollom::setPrivateKey('<your-key>');
	 *
	 * @var	string
	 */
	private static $privateKey;


	/**
	 * Your public key
	 *
	 * Set it by calling Mollom::setPublicKey('<your-key>');
	 *
	 * @var	string
	 */
	private static $publicKey;


	/**
	 * The cache for the serverlist
	 *
	 * No need to change
	 *
	 * @var	array
	 */
	public static $serverList = array();


	/**
	 * Default timeout
	 *
	 * @var	float
	 */
	private static $timeout = 1.5;


	/**
	 * The default user-agent
	 *
	 * Change it by calling Mollom::setUserAgent('<your-user-agent>');
	 *
	 * @var	string
	 */
	private static $userAgent = 'MollomPHP/1.1.3';


	/**
	 * The current Mollom-version
	 *
	 * No need to change
	 *
	 * @var	string
	 */
	private static $version = '1.0';


	/**
	 * The cache which is used to store the list of mollom servers
	 * 
	 * @var ServerListCache 
	 */
	private static $serverListCache = null;


	/**
	 * Calculate the hash for the given timestamp and nonce.
	 * 
	 * @return	string
	 * @param	string $time
	 * @param	string $nonce
	 */
	private static function getHash ($time, $nonce)
	{
		return base64_encode(
			pack("H*", sha1((str_pad(self::$privateKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
			pack("H*", sha1((str_pad(self::$privateKey, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) .
			$time . ':' . $nonce . ':' . self::$privateKey))))
		);
	}


	/**
	 * Make the call.
	 *
	 * @return	array with the response from mollom
	 * @param	string $method
	 * @param	array[optional] $parameters
	 */
	protected static function doCall($method, $parameters = array())
	{
		// check if public key is set
		if(self::$publicKey === null) {
			throw new Exception('Public key wasn\'t set.');
		}

		// check if private key is set
		if(self::$privateKey === null) {
			throw new Exception('Private key wasn\'t set.');
		}

		// an empty serverlist, try to retrieve a list of mollom servers
		if (count(self::$serverList) == 0) {
			self::getServerList();
		}

		// redefine var
		$method = (string) $method;
		$parameters = (array) $parameters;

		// create timestamp & nonce
		$time = gmdate("Y-m-d\TH:i:s.\\0\\0\\0O", time());
		$nonce = md5($time);

		// create hash
		$hash = self::getHash ($time, $nonce);

		// add parameters
		$parameters['public_key'] = self::$publicKey;
		$parameters['time'] = $time;
		$parameters['hash'] = $hash;
		$parameters['nonce'] = $nonce;

		$request = new XMLRPCRequest('mollom.' . $method, $parameters);
		$request->setOption('user_agent', self::$userAgent);
		$request->setOption('timeout', self::$timeout);

		$i = 0;
		$result = null;
		while ($result === null && $i < count(self::$serverList)) {
			try {
				$result = $request->execute(self::$serverList[$i] . '/' . self::$version);
			} catch (XMLRPCException $e) {
				switch ($e->getCode()) {
					case 1000:	// internal mollom error, can't continue...
						throw new Exception('XMLRPC returned error message: ' . $e->getMessage());
						break;
					case 1100:	// refresh server list and start again
						self::getServerList(true);
						$i = 0;
						break;
					case 1200:	// server too busy, try next server
					default:	// unknown error, try next server
						$i++;
				}
			} catch (Exception $e) {
				// most likely a network error, just try the next server
				$i++;
			}
		}

		if ($i == count(self::$serverList)) {
			self::$serverList = array();
			throw new Exception("All Mollom servers are down.");
		}

		if ($result === null) {
			throw new Exception("The server didn't return a valid response.");
		}
		return $result;
	}


	/**
	 * Obtains a list of valid servers
	 *
	 * @return	array
	 */
	private static function getServerList($forcedRefresh = false)
	{
		// if the refresh isn't forced by mollom,
		// try to load the servers from the cache first
		if (!$forcedRefresh && self::$serverListCache != null) {
			self::$serverList = self::$serverListCache->load();
			if (count(self::$serverList) > 0) {
				return;
			}
		}

		// add generic server, so we can bootstrap
		self::$serverList[] = 'http://xmlrpc.mollom.com';

		try {
			// do the call
			$serverList = self::doCall('getServerList', array());
		} catch (Excepion $e) {
			$serverList = array();
		}

		// something went wrong, use the generic server
		if(count($serverList) == 0) {
			$serverList = array('http://xmlrpc.mollom.com');
		}

		// store the updated list in the cache
		if (self::$serverListCache != null) {
			self::$serverListCache->store($serverList);
		}

		// return
		self::$serverList = $serverList;
		return $serverList;
	}


	/**
	 * Verifies your key
	 *
	 * Returns information about the status of your key. Mollom will respond with a boolean value (true/false).
	 * False means that your keys is disabled or doesn't exists. True means the key is enabled and working properly.
	 *
	 * @return	bool
	 */
	public static function verifyKey()
	{
		return self::doCall('verifyKey');
	}


	/**
	 * Set the private key
	 *
	 * @return	void
	 * @param	string $key
	 */
	public static function setPrivateKey($key)
	{
		self::$privateKey = (string) $key;
	}


	/**
	 * Set the public key
	 *
	 * @return	void
	 * @param	string $key
	 */
	public static function setPublicKey($key)
	{
		self::$publicKey = (string) $key;
	}


	/**
	 * Get the public key
	 *
	 * @return	string
	 */
	public static function getPublicKey()
	{
		return self::$publicKey;
	}


	/**
	 * Set timeout
	 *
	 * @return	void
	 * @param	int $timeout
	 */
	public static function setTimeOut($timeout)
	{
		// redefine
		$timeout = (int) $timeout;

		// validate
		if($timeout == 0) throw new Exception('Invalid timeout. Timeout shouldn\'t be 0.');

		// set property
		self::$timeout = $timeout;
	}


	/**
	 * Set the user agent
	 *
	 * @return	void
	 * @param	string $newUserAgent
	 */
	public static function setUserAgent($newUserAgent)
	{
		self::$userAgent .=  ' '. (string) $newUserAgent;
	}


	/**
	 * Set a new object to use as ServerListCache
	 * @param ServerListCache $cache
	 */
	public static function setServerListCache ($cache) {
		self::$serverListCache = $cache;
	}
}

$cachefile = dirname(__file__) . '/servers.txt';
Mollom::setServerListCache(new ServerListFileCache($cachefile));

?>
