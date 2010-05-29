<?php

interface ServerListCache {
	/**
	 * Load a list of servers from the cache.
	 *
	 * @return	array
	 */
	public function load ();

	/**
	 * Store a list of servers in the cache.
	 * All servers that already exist in the cache should be removed.
	 *
	 * @return	void
	 * @param	array $serverList
	 */
	public function store($serverList);
}

/**
 * Basic simple of a cache, using a simple file. Each line contains a server.
 * The file should be readable and writeable by the server!
 */
class ServerListFileCache implements ServerListCache {

	private $filename;

	public function __construct ($filename) {
		$this->filename = $filename;
	}

	public function load () {
		$servers = array();
		$file = fopen($this->filename, "r");
		if ($file) {
			while(!feof($file)) {
				$server = trim(fgets($file));
				if ($server !== '') {
					$servers[] = $server;
				}
			}
			fclose($file);
		}
		return $servers;
	}

	public function store ($servers) {
		$file = fopen($this->filename, "w");
		if ($file) {
			foreach ($servers as $server) {
				fwrite($file, $server . "\r\n");
			}
			fclose($file);
		}
	}
}
?>
