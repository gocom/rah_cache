<?php

/**
 * This is a configuration example for rah_cache.
 */

include './inc/fetch_cached.php';

rah_cache_init(
	array(
		'path' => './pagecache',
		'skip' => array('file_download/')
	)
);

?>