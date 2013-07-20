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
     * @var array
     */

    public $skipPaths = array('file_download/');

    /**
     * An array of cookies that disable caching.
     *
     * @var array
     */

    public $skipCookies = array('txp_login_public');

    /**
     * An array of skip query strings.
     *
     * @var array
     */

    public $skipParams = array('');

    /**
     * Whether cache requests with a HTTP query string
     *
     * @var bool
     */

    public $queryString = false;
}