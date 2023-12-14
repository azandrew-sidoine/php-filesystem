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

namespace Drewlabs\Filesystem;

use RuntimeException;

use function Drewlabs\Filesystem\Proxy\Path;

class PathHelper
{
    /**
     * 
     * @param string|Path $path 
     * @param string $mode 
     * @return resource 
     * @throws RuntimeException 
     */
    public static function tryFopen($path, string $mode)
    {
        $path = (string)$path;
        $exception = null;
        set_error_handler(static function (int $errno, string $errstr) use ($path, $mode, &$exception): bool {
            $exception = new \RuntimeException(sprintf(
                'Unable to open "%s" using mode "%s": %s',
                $path,
                $mode,
                $errstr
            ));
            return true;
        });

        try {
            /** @var resource $handle */
            $handle = fopen($path, $mode);
        } catch (\Throwable $e) {
            $exception = new \RuntimeException(sprintf(
                'Unable to open "%s" using mode "%s": %s',
                $path,
                $mode,
                $e->getMessage()
            ), 0, $e);
        }
        restore_error_handler();
        if ($exception) {
            throw $exception;
        }
        return $handle;
    }

    /**
     * 
     * @param string|Path $root 
     * @param string $pathWithoutRoot 
     * @return array 
     */
    public static function findCanonicalParts($root, string $pathWithoutRoot): array
    {
        $root = (string)$root;
        $parts = explode('/', $pathWithoutRoot);
        $canonicalParts = [];

        // Collapse "." and "..", if possible
        foreach ($parts as $part) {
            if ('.' === $part || '' === $part) {
                continue;
            }

            // Collapse ".." with the previous part, if one exists
            // Don't collapse ".." if the previous part is also ".."
            if ('..' === $part && \count($canonicalParts) > 0 && '..' !== $canonicalParts[\count($canonicalParts) - 1]) {
                array_pop($canonicalParts);

                continue;
            }

            // Only add ".." prefixes for relative paths
            if ('..' !== $part || '' === $root) {
                $canonicalParts[] = $part;
            }
        }

        return $canonicalParts;
    }

    /**
     * Returns canonical path of the user's home directory.
     *
     * Supported operating systems:
     *
     *  - UNIX
     *  - Windows
     *
     * If your operation system or environment isn't supported, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @throws \RuntimeException If your operation system or environment isn't supported
     */
    public static function getHomeDirectory(): string
    {
        // For UNIX support
        if (getenv('HOME')) {
            return (string)(Path(getenv('HOME')))->canonicalize();
        }

        // For >= Windows8 support
        if (getenv('HOMEDRIVE') && getenv('HOMEPATH')) {
            return (string)(Path(getenv('HOMEDRIVE') . getenv('HOMEPATH')))->canonicalize();
        }

        throw new \RuntimeException("Cannot find the home directory path: Your environment or operation system isn't supported.");
    }
}
