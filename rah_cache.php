<?php

/*
 * rah_cache - Full page cache plugin for Textpattern CMS
 * https://github.com/gocom/rah_cache
 *
 * Copyright (C) 2013 Jukka Svahn
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Cache handler.
 */

class Rah_Cache
{
    /**
     * Stores sent HTTP response headers.
     *
     * @var array 
     */

    protected $headers = array();

    /**
     * The request.
     *
     * @var Rah_Cache_Request
     */

    protected $request;

    /**
     * The config.
     *
     * @var Rah_Cache_Config
     */

    protected $config;

    /**
     * Constructor.
     */

    public function __construct()
    {
        if (class_exists('Rah_Cache_Handler'))
        {
            global $event;
            $this->config = Rah_Cache_Handler::$config;
            $this->request =  Rah_Cache_Handler::$request;
            register_callback(array($this, 'store'), 'textpattern_end');
            register_callback(array($this, 'update_lastmod'), $event ? $event : 'textpattern_end');
            Textpattern_Tag_Registry::register(array($this, 'controller'), 'rah_cache');
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

        if ($ignore)
        {
            $this->request->file = null;
        }
    }

    /**
     * Gets sent response headers.
     */

    protected function get_headers()
    {
        if (function_exists('headers_list') && $headers = headers_list())
        {
            foreach ((array) $headers as $header)
            {
                if (strpos($header, ':'))
                {
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
        if (empty($this->request->file) || get_pref('production_status') != 'live')
        {
            return;
        }

        foreach ($this->config->skipPaths as $path)
        {
            if (strpos($this->request->uri, $path) === 0)
            {
                return;
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

        $page = ob_get_contents();

        if (($r = callback_event('rah_cache.store', '', 0, array(
            'contents' => $page,
            'headers'  => $this->headers,
        ))) !== '')
        {
            $page = $r;
        }

        if (!$page)
        {
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

    public function update_lastmod()
    {
        if ($this->config->directory)
        {
            $lastmod = $this->config->directory . '/_lastmod.rah';

            if (!is_file($lastmod) || file_get_contents($lastmod) !== get_pref('lastmod', false, true))
            {
                file_put_contents($lastmod, get_pref('lastmod', '', true));
            }
        }
    }
}

new Rah_Cache();