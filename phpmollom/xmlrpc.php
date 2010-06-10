<?php

class XMLRPCException extends Exception {}

class XMLRPCRequest {

	private $context;

	public function __construct ($method, $parameters) {
		$body = xmlrpc_encode_request($method, $parameters);
		$this->context = stream_context_create(array('http' => array(
			'method'  => "POST",
			'header'  => "Content-Type: text/xml",
			'content' => $body
		)));
	}

	public function setOption ($name, $value) {
		stream_context_set_option($this->context, 'html', $name, $value);
	}

	public function execute ($server) {
		$file = file_get_contents($server, false, $this->context);
		if ($file == false) {
			throw new Exception("Server didn't send an answer!");
		}

		$response = xmlrpc_decode($file);
		if (is_array($response) && xmlrpc_is_fault($response)) {
			throw new XMLRPCException($response['faultString'], $response['faultCode']);
		}
		return $response;
	}
}

?>
