<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Filesystem\Helpers;

class URLHelper
{
    /**
     * Concatenate a path to a URL.
     *
     * @param string $baseURL
     * @param string $path
     *
     * @return string
     */
    public static function addPathToBaseURL($baseURL, $path)
    {
        return sprintf('%s%s%s', rtrim($baseURL, '/'), '/', ltrim($path, '/'));
    }
}
