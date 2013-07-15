<?php

/**
 * Rah_cache plugin for Textpattern CMS.
 *
 * @author  Jukka Svahn
 * @license GNU GPLv2
 * @link    https://github.com/gocom/rah_cache
 * 
 * Copyright (C) 2013 Jukka Svahn http://rahforum.biz
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * A tag to control caching on a page basis.
 *
 * @param  array $atts
 * @return string
 */

    function rah_cache($atts)
    {
        global $rah_cache;

        extract(lAtts(array(
            'ignore' => 0,
        ), $atts));

        if ($ignore)
        {
            $rah_cache['file'] = null;
        }
    }

/**
 * Cache handler.
 */

class rah_cache
{
    /**
     * The page data.
     *
     * @var string
     */

    static public $data;

    /**
     * Stores sent HTTP response headers.
     *
     * @var array 
     */

    protected $headers = array();

    /**
     * Constructor.
     */

    public function __construct()
    {
        global $event;
        register_callback(array($this, 'store'), 'textpattern_end');
        register_callback(array($this, 'update_lastmod'), $event ? $event : 'textpattern_end');
    }

    /**
     * Sets page data.
     *
     * @param $name
     */

    static public function data($data = null)
    {
        if ($data !== null)
        {
            self::$data = $data;
        }

        return self::$data;
    }

    /**
     * Gets sent response headers.
     */

    protected function get_headers()
    {
        if (!function_exists('header_list') || !header_list())
        {
            return;
        }

        foreach ((array) header_list() as $header)
        {
            if (strpos($header, ':'))
            {
                $header = explode(':', strtolower($header), 2);
                $this->headers[trim($header[0])] = trim($header[1]);
            }
        }
    }

    /**
     * Writes the page to the cache directory.
     */

    public function store()
    {   
        global $prefs, $rah_cache;

        if (empty($rah_cache['file']) || $prefs['production_status'] != 'live')
        {
            return;
        }

        if (!empty($rah_cache['skip']))
        {
            foreach ((array) $rah_cache['skip'] as $pattern)
            {
                if (strpos($rah_cache['request_uri'], $pattern) === 0)
                {
                    return;
                }
            }
        }

        $this->get_headers();

        if (
            isset($this->headers['content-type']) &&
            strpos($this->headers['content-type'], 'text/html') === false
        )
        {
            return;
        }

        self::$data = ob_get_contents();

        callback_event('rah_cache.store');

        if (!self::$data)
        {
            return;
        }

        file_put_contents($rah_cache['file'], self::$data);

        if (function_exists('gzcompress'))
        {
            $size = strlen(self::$data);
            $crc = crc32(self::$data);
            $data = gzcompress(self::$data, 6);
            $data = substr($data, 0, strlen($data)-4);
            $data = "\x1f\x8b\x08\x00\x00\x00\x00\x00".$data;
            $data .= pack('V', $crc);
            $data .= pack('V', $size);
            file_put_contents($rah_cache['file'].'.gz', $data);
        }

        callback_event('rah_cache.created');
    }

    /**
     * Update last modification timestamp.
     */

    public function update_lastmod()
    {
        global $rah_cache;

        if (!empty($rah_cache['path']))
        {
            file_put_contents(
                $rah_cache['path'] . '/_lastmod.rah', @strtotime(get_pref('lastmod', 'now', true))
            );
        }
    }

    /**
     * Flushes the cache.
     *
     * @param  string|array $file
     * @return bool
     */

    static public function flush($file)
    {
        global $rah_cache;

        if ($file !== null)
        {
            foreach ((array) $file as $f)
            {
                if (!preg_match('#[0-9a-f]{32}$#i', $f))
                {
                    return false;
                }

                $f = $rah_cache['path'] . '/' . $f;
                unlink($f . '.rah');
                unlink($f . '.rah.gz');
            }

            return true;
        }

        foreach ((array) glob($rah_cache['path'].'/*', GLOB_NOSORT) as $file)
        {
            if (is_file($file) && strlen(basename($file)) == 32)
            {
                unlink($file);
            }
        }
    }
}

new rah_cache();