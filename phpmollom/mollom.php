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

define('MOLLOM_HAM', 1);
define('MOLLOM_SPAM', 2);
define('MOLLOM_UNSURE', 3);

class Mollom
{
	/**
	 * The allowed reverse proxy addresses
	 *
	 * @var	array
	 */
	private static $allowedReverseProxyAddresses = array();


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
	 * Reverse proxy allowed?
	 *
	 * @var	bool
	 */
	private static $reverseProxy = false;


	/**
	 * Does this setup runs on a cluster?
	 *
	 * @var bool
	 */
	private static $usesClusterSetup = false;


	/**
	 * The cache for the serverlist
	 *
	 * No need to change
	 *
	 * @var	array
	 */
	private static $serverList = array('http://xmlrpc.mollom.com');


	/**
	 * Default timeout
	 *
	 * @var	int
	 */
	private static $timeout = 10;


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
	 * Validates the answer for a CAPTCHA
	 *
	 * When the answer is false, you should request a new image- or audio-CAPTCHA, make sure your provide the current Mollom-sessionid.
	 * The sessionid will be used to track spambots that try to solve CAPTHCA's by brute force.
	 *
	 * @return	bool
	 * @param	string $sessionId
	 * @param	string $solution
	 */
	public static function checkCaptcha($sessionId, $solution)
	{
		// set autor ip
		$authorIp = self::getIpAddress();

		// set parameters
		$parameters['session_id'] = (string) $sessionId;
		$parameters['solution'] = (string) $solution;
		if($authorIp != null) $parameters['author_ip'] = (string) $authorIp;

		// do the call
		return self::doCall('checkCaptcha', $parameters);
	}


	/**
	 * Check if the data is spam or not, and gets an assessment of the datas quality
	 *
	 * This function will be used most. The more data you submit the more accurate the classification will be.
	 * If the spamstatus is 'unsure', you could send the user an extra check (eg. a captcha).
	 *
	 * REMARK: the Mollom-sessionid is NOT related to HTTP-session, so don't send 'session_id'.
	 *
	 * The function will return an array with 3 elements:
	 * - spam			the spam-status (ham/spam/unsure)
	 * - quality		an assessment of the content's quality (between 0 and 1)
	 * - session_id		Molloms session_id
	 *
	 * @return	array
	 * @param	string[optional] $sessionId
	 * @param	string[optional] $postTitle
	 * @param	string[optional] $postBody
	 * @param	string[optional] $authorName
	 * @param	string[optional] $authorUrl
	 * @param	string[optional] $authorEmail
	 * @param	string[optional] $authorOpenId
	 * @param	string[optional] $authorId
	 */
	public static function checkContent($sessionId = null, $postTitle = null, $postBody = null, $authorName = null, $authorUrl = null, $authorEmail = null, $authorOpenId = null, $authorId = null)
	{
		// validate
		if($sessionId === null && $postTitle === null && $postBody === null && $authorName === null && $authorUrl === null && $authorEmail === null && $authorOpenId === null && $authorId === null) throw new Exception('Specify at least on argument');

		// init var
		$parameters = array();

		// add parameters
		if($sessionId !== null) $parameters['session_id'] = (string) $sessionId;
		if($postTitle !== null) $parameters['post_title'] = (string) $postTitle;
		if($postBody !== null) $parameters['post_body'] = (string) $postBody;
		if($authorName !== null) $parameters['author_name'] = (string) $authorName;
		if($authorUrl !== null) $parameters['author_url'] = (string) $authorUrl;
		if($authorEmail !== null) $parameters['author_mail'] = (string) $authorEmail;
		if($authorOpenId != null) $parameters['author_openid'] = (string) $authorOpenId;
		if($authorId != null) $parameters['author_id'] = (string) $authorId;

		// set autor ip
		$authorIp = self::getIpAddress();
		if($authorIp != null) $parameters['author_ip'] = (string) $authorIp;

		// do the call
		return self::doCall('checkContent', $parameters);
	}


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
	private static function doCall($method, $parameters = array())
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
		if (empty(self::$serverList)) {
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
		while ($result == null && $i < count(self::$serverList)) {
			try {
				$result = $request->execute(self::$serverList[$i] . '/' . self::$version);
			} catch (XMLRPCException $e) {
				switch ($e->getCode()) {
					case 1000:	// internal mollom error, can't continue...
						throw new Exception('XMLRPC returned error message: ' . $e->getMessage());
						break;
					case 1100:	// refresh server list and start again
						self::getServerList();
						$i = 0;
						break;
					case 1200:	// server too busy, try next server
					default:	// unknown error, try next server
						$i++;
				}
			}
		}

		if ($i == count(self::$serverList)) {
			self::$serverList = array();
			throw new Exception("All Mollom servers are down.");
		}

		if ($result == null) {
			throw new Exception("The server didn't return a valid response.");
		}
		return $result;
	}


	/**
	 * Get a CAPTCHA-mp3 generated by Mollom
	 *
	 * If your already called getAudioCaptcha make sure you provide at least the $sessionId. It will be used
	 * to identify visitors that are trying to break a CAPTCHA with brute force.
	 *
	 * REMARK: the Mollom-sessionid is NOT related to HTTP-session, so don't send 'session_id'.
	 *
	 * The function will return an array with 3 elements:
	 * - session_id		the session_id from Mollom
	 * - url			the url to the mp3-file
	 * - html			html that can be used on your website to display the CAPTCHA
	 *
	 * @return	array
	 * @param	string[optional] $sessionId
	 */
	public static function getAudioCaptcha($sessionId = null)
	{
		// init vars
		$parameters = array();

		// set autor ip
		$authorIp = self::getIpAddress();

		// set parameters
		if($sessionId != null) $parameters['session_id'] = (string) $sessionId;
		if($authorIp != null) $parameters['author_ip'] = (string) $authorIp;

		// do the call
		$response = self::doCall('getAudioCaptcha', $parameters);

		// add audio html
		$response['html'] = '<object type="audio/mpeg" data="'. $response['url'] .'" width="50" height="16">'."\n"
								."\t".'<param name="autoplay" value="false" />'."\n"
								."\t".'<param name="controller" value="true" />'."\n"
							.'</object>';
		return $response;
	}


	/**
	 * Get a CAPTCHA-image generated by Mollom
	 *
	 * If your already called getImageCaptcha make sure you provide at least the $sessionId. It will be used
	 * to identify visitors that are trying to break a CAPTCHA with brute force.
	 *
	 * REMARK: the Mollom-sessionid is NOT related to HTTP-session, so don't send 'session_id'.
	 *
	 * The function will return an array with 3 elements:
	 * - session_id		the session_id from Mollom
	 * - url			the url to the image
	 * - html			html that can be used on your website to display the CAPTCHA
	 *
	 * @return	array
	 * @param	string[optional] $sessionId
	 */
	public static function getImageCaptcha($sessionId = null)
	{
		// init vars
		$parameters = array();

		// set autor ip
		$authorIp = self::getIpAddress();

		// set parameters
		if($sessionId !== null) $parameters['session_id'] = (string) $sessionId;
		if($authorIp !== null) $parameters['author_ip'] = (string) $authorIp;

		// do the call
		$response = self::doCall('getImageCaptcha', $parameters);

		// add image html
		$response['html'] = '<img src="'. $response['url'] .'" alt="Mollom CAPTCHA" />';
		return $response;
	}


	/**
	 * Get the real IP-address
	 *
	 * @return	string
	 */
	public static function getIpAddress()
	{
		// pre check
		if(!isset($_SERVER['REMOTE_ADDR'])) return null;

		// get ip
		$ipAddress = $_SERVER['REMOTE_ADDR'];

		if(self::$reverseProxy)
		{
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				// running behind a proxy
				if(!empty(self::$allowedReverseProxyAddresses) && in_array($ipAddress, self::$allowedProxyAddresses, true))
				{
					return array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
				}
   			}
		}

		if(self::$usesClusterSetup)
		{
   			// running in a cluster environment
			if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		}

		// fallback
		return $ipAddress;
	}


	/**
	 * Obtains a list of valid servers
	 *
	 * @return	array
	 */
	private static function getServerList($counter = 0)
	{
		// TODO: try to load them from cache

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

		// TODO: write serverlist to cache

		// return
		self::$serverList = $serverList;
		return $serverList;
	}


	/**
	 * Retrieve statistics from Mollom
	 *
	 * Allowed types are listed below:
	 * - total_days				Number of days Mollom has been used
	 * - total_accepted			Total of blocked spam
	 * - total_rejected			Total accepted posts (not working?)
	 * - yesterday_accepted		Amount of spam blocked yesterday
	 * - yesterday_rejected		Number of posts accepted yesterday (not working?)
	 * - today_accepted			Amount of spam blocked today
	 * - today_rejected			Number of posts accepted today (not working?)
	 *
	 * @return	int
	 * @param	string $type
	 */
	public static function getStatistics($type)
	{
		// possible types
		$aPossibleTypes = array('total_days', 'total_accepted', 'total_rejected', 'yesterday_accepted', 'yesterday_rejected', 'today_accepted', 'today_rejected');

		// redefine
		$type = (string) $type;

		// validate
		if(!in_array($type, $aPossibleTypes)) throw new Exception('Invalid type. Only '. implode(', ', $aPossibleTypes) .' are possible types.');

		// do the call
		return self::doCall('getStatistics', array('type' => $type));
	}


	/**
	 * Send feedback to Mollom.
	 *
	 * With this method you can help train Mollom. Implement this method if possible. The more feedback is provided the more accurate
	 * Mollom will be.
	 *
	 * Allowed feedback-strings are listed below:
	 * - spam			Spam or unsolicited advertising
	 * - profanity		Obscene, violent or profane content
	 * - low-quality	Low-quality content or writing
	 * - unwanted		Unwanted, taunting or off-topic content
	 *
	 * @return	bool
	 * @param	string $sessionId
	 * @param	string $feedback
	 */
	public static function sendFeedback($sessionId, $feedback)
	{
		// possible feedback
		$aPossibleFeedback = array('spam', 'profanity', 'low-quality', 'unwanted');

		// redefine
		$sessionId = (string) $sessionId;
		$feedback = (string) $feedback;

		// validate
		if(!in_array($feedback, $aPossibleFeedback)) throw new Exception('Invalid feedback. Only '. implode(', ', $aPossibleFeedback) .' are possible feedback-strings.');

		// build parameters
		$parameters['session_id'] = $sessionId;
		$parameters['feedback'] = $feedback;

		// do the call
		return self::doCall('sendFeedback', $parameters);
	}


	/**
	 * Set the allowed reverse proxy Addresses
	 *
	 * @return	void
	 * @param	array $addresses
	 */
	public static function setAllowedReverseProxyAddresses($addresses)
	{
		// store allowed ip-addresses
		self::$allowedReverseProxyAddresses = (array) $addresses;

		// set reverse proxy
		self::$reverseProxy = (!empty($addresses)) ? true : false;
		if (self::$reverseProxy) {
			self::$usesClusterSetup = false;
		}
	}


	/**
	 * Set true if this setup runs on a cluster. If this setup runs on a cluster, the reverse proxy is disabled.
	 *
	 * @return	void
	 * @param	bool $usesClusterSetup;
	 */
	public static function setUsesClusterSetup ($usesClusterSetup)
	{
		server::$usesClusterSetup = $usesClusterSetup;
		if (server::$usesClusterSetup) {
			self::$reverseProxy = false;
		}
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
}

?>
