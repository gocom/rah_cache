<?php

/**
 * This is an example plugin for rah_cache. It removes
 * comments and all newlines and tabs from cached pages.
 *
 * @package rah_cache
 * @author Jukka Svahn
 * @license GPLv2
 */

	if(@txpinterface == 'public') {
		register_callback('abc_minify', 'rah_cache.store');
	}

/**
 * Minify page's HTML markup
 * @see rah_cache::data()
 */

	function abc_minify() {
		$data = rah_cache::data();
		$data = preg_replace('/<!--(.*)-->/Uis', '', $data);
		$data = str_replace(array("\r","\n","\t",'  '), array('','','',' '), $data);
		rah_cache::data($data);
	}

?>