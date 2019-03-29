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
final class Rah_Cache_Handler
{
    /**
     * The config.
     *
     * @var Rah_Cache_Config
     */
    public static $config;

    /**
     * The request.
     *
     * @var Rah_Cache_Request
     */
    public static $request;

    /**
     * Constructor.
     *
     * @param array $opt Options
     */
    public function __construct(Rah_Cache_Config $config)
    {
        self::$config = $config;

        if (txpinterface !== 'public' || !empty($_POST)) {
            return;
        }

        if ($config->queryString === false && empty($_GET) === false) {
            return;
        }

        foreach ($config->skipCookies as $name) {
            if (isset($_COOKIE[$name])) {
                return;
            }
        }

        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $md5 = md5($request_uri);
        $filename = $file = $config->directory . '/' . $md5 . '.rah';
        $encoding = $this->encoding();
        $lastmod = $config->directory . '/_lastmod.rah';

        if ($encoding) {
            $filename = $file . '.gz';
        }

        if (file_exists($filename) && file_exists($lastmod)) {
            $modified = filemtime($filename);
            $lastmod = filemtime($lastmod);

            if ($modified > time()-2592000 && $modified >= $lastmod) {
                header('Content-type: text/html; charset=utf-8');

                if ($encoding) {
                    header('Content-Encoding: '.$encoding);
                }

                die(file_get_contents($filename));
            }
        }

        if (!file_exists($config->directory) || !is_dir($config->directory) || !is_writeable($config->directory)) {
            return;
        }

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
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || headers_sent()) {
            return false;
        }

        $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];

        if (strpos($accept_encoding, 'x-gzip') !== false) {
            return 'x-gzip';
        }

        if (strpos($accept_encoding, 'gzip') !== false) {
            return 'gzip';
        }

        return false;
    }
}
