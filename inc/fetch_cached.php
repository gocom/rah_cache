<?php

/**
 * Gets the page from cache and sets options. This file should
 * be included and used via Textpattern's config.php file.
 * @param array $opt
 * @return nothing
 * <code>
 *		rah_cache_init(array $options);
 * </code>
 */

	function rah_cache_init($opt) {
		
		global $rah_cache;
		$rah_cache = $opt;

		if(txpinterface != 'public' || !empty($_POST) || !empty($_GET)) {
			return;
		}
		
		$request_uri = trim($_SERVER['REQUEST_URI'], '/');
		$md5 = md5($request_uri);
		$filename = $file = $rah_cache['path'] . '/' . $md5 . '.rah';
		$encoding = rah_cache_encoding();
		
		if($encoding) {
			$filename = $file . '.gz';
		}
		
		$modified = filemtime($filename);
		
		if(
			file_exists($filename) && 
			$modified > time()-2592000 && 
			$modified >= (int) @file_get_contents($rah_cache['path'] . '/_lastmod.rah')
		) {
			header('Content-type: text/html; charset=utf-8');
			
			if($encoding) {
				header('Content-Encoding: '.$encoding);
			}
			
			die(file_get_contents($filename));
		}
		
		if(
			!file_exists($rah_cache['path']) || 
			!is_dir($rah_cache['path']) || 
			!is_writeable($rah_cache['path'])
		) {
			return;
		}
		
		$rah_cache['file'] = $file;
		$rah_cache['request_uri'] = $request_uri;
		$rah_cache['cache_key'] = $md5;
	}

/**
 * Check accepted encoding headers
 * @return bool
 */

	function rah_cache_encoding() {
	
		if(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || headers_sent()) {
			return false;
		}
	
		$accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		
		if(strpos($accept_encoding, 'x-gzip') !== false) {
			return 'x-gzip';
		}
		
		if(strpos($accept_encoding, 'gzip') !== false) {
			return 'gzip';
		}
		
		return false;
	}

?>