<?php

/*
 * rah_cache - Full page cache for Textpattern CMS
 * https://github.com/gocom/rah_cache
 *
 * Copyright (C) 2019 Jukka Svahn
 *
 * This file is part of rah_cache.
 *
 * rah_cache is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_cache is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_cache. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Cache handler.
 */
final class Rah_Cache
{
    /**
     * Stores sent HTTP response headers.
     *
     * @var array
     */
    private $headers = [];

    /**
     * The request.
     *
     * @var Rah_Cache_Request
     */
    private $request;

    /**
     * The config.
     *
     * @var Rah_Cache_Config
     */
    private $config;

    /**
     * Constructor.
     */

    public function __construct()
    {
        if (class_exists('Rah_Cache_Handler')) {
            global $event;
            $this->config = Rah_Cache_Handler::$config;
            $this->request = Rah_Cache_Handler::$request;
            register_callback(array($this, 'store'), 'textpattern_end');
            register_callback(array($this, 'updateLastmod'), $event ? $event : 'textpattern_end');
            Txp::get('\Textpattern\Tag\Registry')->register([$this, 'controller'], 'rah_memcached');
        }
    }

    /**
     * A tag to control caching on a page basis.
     *
     * @param  array $atts
     * @return string
     */
    public function controller($atts)
    {
        extract(lAtts(array(
            'ignore' => 0,
        ), $atts));

        if ($ignore) {
            $this->request->file = null;
        }
    }

    /**
     * Gets sent response headers.
     */

    protected function getHeaders()
    {
        if (function_exists('headers_list') && $headers = headers_list()) {
            foreach ((array) $headers as $header) {
                if (strpos($header, ':')) {
                    $header = explode(':', strtolower($header), 2);
                    $this->headers[trim($header[0])] = trim($header[1]);
                }
            }
        }
    }

    /**
     * Writes the page to the cache directory.
     */
    public function store()
    {
        if (empty($this->request->file) || get_pref('production_status') != 'live') {
            return;
        }

        foreach ($this->config->skipPaths as $path) {
            if (strpos($this->request->uri, $path) === 0) {
                return;
            }
        }

        $this->getHeaders();

        if (isset($this->headers['content-type']) &&
            strpos($this->headers['content-type'], 'text/html') === false
        ) {
            return;
        }

        $page = ob_get_contents();

        if (($r = callback_event('rah_cache.store', '', 0, array(
            'contents' => $page,
            'headers'  => $this->headers,
        ))) !== '') {
            $page = $r;
        }

        if (!$page) {
            return;
        }

        file_put_contents($this->request->file, $page);

        $size = strlen($page);
        $crc = crc32($page);
        $data = gzcompress($page, 6);
        $data = substr($data, 0, strlen($data)-4);
        $data = "\x1f\x8b\x08\x00\x00\x00\x00\x00".$data;
        $data .= pack('V', $crc);
        $data .= pack('V', $size);
        file_put_contents($this->request->file.'.gz', $data);

        callback_event('rah_cache.created');
    }

    /**
     * Update last modification timestamp.
     */
    public function updateLastmod()
    {
        if ($this->config->directory) {
            $lastmod = $this->config->directory . '/_lastmod.rah';

            if (!is_file($lastmod) || file_get_contents($lastmod) !== get_pref('lastmod', false, true)) {
                file_put_contents($lastmod, get_pref('lastmod', '', true));
            }
        }
    }
}
