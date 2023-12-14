<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Filesystem;

use Drewlabs\Filesystem\Exceptions\FileNotFoundException;

class Ownership
{
    /**
     * Get or Set the file permissions on a given file.
     *
     * @param string|Path $path
     * @param int         $mode
     *
     * @return void|string
     */
    public function chmod($path, int $mode = null)
    {
        if (!\is_string($path) && !($path instanceof Path)) {
            throw new \InvalidArgumentException('path parameter must be an instance os PHP string or '.Path::class);
        }
        $path = \is_string($path) ? new Path($path) : $path;
        if (!$path->exists()) {
            throw new FileNotFoundException($path->__toString());
        }
        error_clear_last();
        if ($mode) {
            $result = @\chmod($path->__toString(), $mode);
            if (!$result) {
                throw new FileNotFoundException($path->__toString(), error_get_last()['message'] ?? '');
            }

            return $result;
        }

        return mb_substr(sprintf('%o', fileperms($path->__toString())), -4);
    }
}
