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
 * Configuration options.
 */
class Rah_Cache_Config
{
    /**
     * Path to the cache directory.
     *
     * @var string
     */
    public $directory = './../cache';

    /**
     * An array of skipped paths.
     *
     * @var string[]
     */
    public $skipPaths = [
        'file_download/',
    ];

    /**
     * An array of cookies that disable caching.
     *
     * @var string[]
     */
    public $skipCookies = [
        'txp_login_public',
    ];

    /**
     * An array of skip query strings.
     *
     * @var string[]
     */
    public $skipParams = [
        '',
    ];

    /**
     * Whether cache requests with a HTTP query string
     *
     * @var bool
     */
    public $queryString = false;
}
