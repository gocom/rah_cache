<?php

/**
 * Rah_cache plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2012-
 * @license GNU GPLv2
 * @link https://github.com/gocom/rah_cache
 *
 * The plugin caches Textpattern's dynamic pages as
 * flat files.
 *
 * Requires Textpattern v4.4.1 or newer, and PHP5 or newer.
 * Also requires inc/fetch_cached.php and config/config.php.
 * 
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'public') {
		register_callback(array('rah_cache', 'store'), 'textpattern_end');
	}

/**
 * Cache handler
 */

class rah_cache {

	static public $data;

	/**
	 * Sets page data
	 * @param $name
	 */
	
	static public function data($data=NULL) {
		if($data !== NULL) {
			self::$data = $data;
		}
		
		return self::$data;
	}

	/**
	 * Writes the page to cache directory
	 */
	
	static public function store() {
		
		global $prefs, $rah_cache;
		
		if(empty($rah_cache['file']) || $prefs['production_status'] != 'live') {
			return;
		}

		if(!empty($rah_cache['skip'])) {
			foreach((array) $rah_cache['skip'] as $pattern) {
				if(strpos($rah_cache['request_uri'], $pattern) === 0) {
					return;
				}
			}
		}
		
		self::$data = ob_get_contents();
		
		/*
			Allow plugin to modify stored content
		*/
		
		callback_event('rah_cache.store');
				
		if(!self::$data) {
			return;
		}
		
		if(
			file_put_contents(
				$rah_cache['file'], self::$data
			) == false
		) {
			return;
		}
		
		if(function_exists('gzcompress')) {
			
			$size = strlen(self::$data);
			self::$data = gzcompress(self::$data, 9);
			self::$data = substr(self::$data, 0, $size);
			self::$data = "\x1f\x8b\x08\x00\x00\x00\x00\x00" . self::$data;
			
			if(
				file_put_contents(
					$rah_cache['file'].'.gz', self::$data
				) == false
			) {
				return;
			}
		}

		callback_event('rah_cache.created');
	}

	/**
	 * Flush cache
	 * @param string|array $file
	 * @return bool
	 */
	
	static public function flush($file) {
		
		global $rah_cache;
		
		if($file !== NULL) {
			
			foreach((array) $file as $f) {
		
				if(!preg_match('#[0-9a-f]{32}$#i', $f)) {
					return false;
				}
				
				unlink($f . '.rah');
				unlink($f . '.rah.gz');
			}
			
			return true;
		}
		
		foreach(glob( $rah_cache['path'] . '/' . '*', GLOB_NOSORT) as $file) {
			if(is_file($file) && strlen(basename($file)) == 32) {
				unlink($file);
			}
		}
		
	}
}
?>