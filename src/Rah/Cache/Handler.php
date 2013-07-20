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

include_once './Config.php';

/**
 * Cache handler.
 *
 * Gets the page from cache and sets options. This file should
 * be included and used via Textpattern's config.php file.
 *
 * @example
 * class My_App_Cache_Config extends Rah_Cache_Config
 * {
 *     public $directory = './../cache';
 *     public $skipPaths = array('file_download/', 'cart/');
 *     public $skipCookies = array('txp_login_public', 'shopping_cart');
 * }
 *
 * new Rah_Cache_Handler(new My_App_Cache_Config());
 */

class Rah_Cache_Handler
{
    /**
     * The config.
     *
     * @var Rah_Cache_Config
     */

    static public $config;

    /**
     * The request.
     *
     * @var Rah_Cache_Request
     */

    static public $request;

    /**
     * Constructor.
     *
     * @param array $opt Options
     */

    public function __construct(Rah_Cache_Config $config)
    {
        self::$config = $config;

        if (txpinterface !== 'public' || !empty($_POST))
        {
            return;
        }

        if ($config->queryString === false && empty($_GET) === false)
        {
            return;
        }

        foreach ($config->skipCookies as $name)
        {
            if (isset($_COOKIE[$name]))
            {
                return;
            }
        }

        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $md5 = md5($request_uri);
        $filename = $file = $config->directory . '/' . $md5 . '.rah';
        $encoding = $this->encoding();

        if ($encoding)
        {
            $filename = $file . '.gz';
        }

        if (file_exists($filename))
        {
            $modified = filemtime($filename);

            if (
                $modified > time()-2592000 && 
                $modified >= (int) @file_get_contents($config->directory . '/_lastmod.rah')
            )
            {
                header('Content-type: text/html; charset=utf-8');

                if ($encoding)
                {
                    header('Content-Encoding: '.$encoding);
                }

                die(file_get_contents($filename));
            }
        }

        if (!file_exists($config->directory) || !is_dir($config->directory) || !is_writeable($config->directory))
        {
            return;
        }

        include_once './Request.php';

        self::$request = new Rah_Cache_Request();
        self::$request->file = $file;
        self::$request->uri = $request_uri;
        self::$request->id = $md5;
    }

    /**
     * Check accepted encoding headers.
     *
     * @return bool
     */

    public function encoding()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || headers_sent())
        {
            return false;
        }

        $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];

        if (strpos($accept_encoding, 'x-gzip') !== false)
        {
            return 'x-gzip';
        }

        if (strpos($accept_encoding, 'gzip') !== false)
        {
            return 'gzip';
        }

        return false;
    }
}