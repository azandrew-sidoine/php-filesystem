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

class PathPrefixer
{
    /**
     * @var string
     */
    private $prefix_ = '';

    /**
     * @var string
     */
    private $separator = '/';

    public function __construct(string $prefix, string $separator = \DIRECTORY_SEPARATOR)
    {
        $this->prefix_ = rtrim($prefix ?? '', '\\/');
        if ('' !== $this->prefix_ || $prefix === $separator) {
            $this->prefix_ .= $separator;
        }
        $this->separator = $separator;
    }

    /**
     * Add prefix to a file path.
     *
     * @param string|Path $path
     */
    public function prefix($path): string
    {
        if (!\is_string($path) && !($path instanceof Path)) {
            throw new \InvalidArgumentException('path parameter must be an instance os PHP string or '.Path::class);
        }
        $path = \is_string($path) ? new Path($path) : $path;
        /**
         * @var Closure<string, string, string>
         */
        $prefixFunc = static function (string $prefix, string $path_) {
            return $prefix.ltrim($path_, '\\/');
        };
        if ($path->isDirectory()) {
            $prefixedPath = $prefixFunc($this->prefix_, $path->__toString());
            if ((substr($prefixedPath, -1) === $this->separator) || '' === $prefixedPath) {
                return $prefixedPath;
            }

            return $prefixedPath.$this->separator;
        }

        return $prefixFunc($this->prefix_, $path->__toString());
    }

    /**
     * Checks if a given path is already prefixed.
     *
     * @param string|Path $path
     *
     * @return bool
     */
    public function hasPrefix($path)
    {
        if (!\is_string($path) && !($path instanceof Path)) {
            throw new \InvalidArgumentException('path parameter must be an instance os PHP string or '.Path::class);
        }
        $path = \is_string($path) ? $path : $path->__toString();

        return !empty($path) && substr($path, 0, \strlen($this->prefix_)) === $this->prefix_;
    }

    /**
     * Strip the provided strip from $path variable.
     *
     * @param Path|string $path
     *
     * @throws \InvalidArgumentException
     */
    public function stripPrefix($path): string
    {
        if (!\is_string($path) && !($path instanceof Path)) {
            throw new \InvalidArgumentException('path parameter must be an instance os PHP string or '.Path::class);
        }
        $path = \is_string($path) ? new Path($path) : $path;
        $stripPrefixFunc = static function (string $prefix, string $path_) {
            return substr($path_, \strlen($prefix));
        };
        if ($path->isDirectory()) {
            return rtrim($stripPrefixFunc($this->prefix_, $path->__toString()), '\\/');
        }

        return $stripPrefixFunc($this->prefix_, $path->__toString());
    }
}
