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

use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\PathNotFoundException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use function Drewlabs\Filesystem\Proxy\Path;

class Path
{
    /**
     * The number of buffer entries that triggers a cleanup operation.
     */
    private const CLEANUP_THRESHOLD = 1250;

    /**
     * The buffer size after the cleanup operation.
     */
    private const CLEANUP_SIZE = 1000;
    /**
     * @var array
     */
    private static $buffer = [];

    private static $bufferSize = 0;

    /**
     * @var string
     */
    private $path_;

    /**
     * Creates an instance of the Path class.
     *
     * @return void
     */
    public function __construct(string $path)
    {
        $this->path_ = './' === substr($path, 0, 2) ? substr($path, 2) : $path;
    }

    public function __toString()
    {
        return $this->path_;
    }

    /**
     * Check if the path is an absolute path or a relative path.
     */
    public function isAbsolute(): bool
    {
        $path = $this->path_;
        if (('' === $path) || (null === $path)) {
            return false;
        }

        // Remove scheme
        if (false !== ($schemeCharPos = mb_strpos($path, '://'))) {
            $path = mb_substr($path, $schemeCharPos + 3);
        }

        $first = $path[0];
        if ('/' === $first || '\\' === $first) {
            return true;
        }
        // Windows style root
        if (mb_strlen($path) > 1 && ctype_alpha($first) && ':' === $path[1]) {
            if (2 === mb_strlen($path)) {
                return true;
            }
            if ('/' === $path[2] || '\\' === $path[2]) {
                return true;
            }
        }

        return false;
    }

    public function makeAbsolute(string $base)
    {
        if ('' === $base) {
            throw new \InvalidArgumentException(sprintf('The base path must be empty. Got: "%s".', $base));
        }

        if (!(Path($base))->isAbsolute()) {
            throw new \InvalidArgumentException(sprintf('The base path "%s" is not an absolute path.', $base));
        }

        if ($this->isAbsolute()) {
            return $this->canonicalize();
        }

        if (false !== ($schemeCharPos = mb_strpos($base, '://'))) {
            $scheme = mb_substr($base, 0, $schemeCharPos + 3);
            $base = mb_substr($base, $schemeCharPos + 3);
        } else {
            $scheme = '';
        }

        return Path($scheme.(Path(rtrim($base, '/\\').'/'.$this->path_))->canonicalize()->__toString());
    }

    /**
     * Return boolean value indicating whether the path is a directory
     * or not.
     *
     * @return bool
     */
    public function isDirectory()
    {
        if (null === $this->path_) {
            return false;
        }

        return is_dir($this->path_);
    }

    /**
     * Tells if path exists or not.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->isDirectory() || ($this->isFile());
    }

    public function basename()
    {
        if (($result = @pathinfo($this->path_, \PATHINFO_BASENAME)) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'basename');
        }

        return $result;
    }

    /**
     * Extracts the directory name of the path object.
     *
     * @return string[]|string
     */
    public function dirname()
    {
        if (($result = @pathinfo($this->path_, \PATHINFO_DIRNAME)) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'dirname');
        }

        return $result;
    }

    /**
     * Extracts the extension from the path object.
     *
     * @return string[]|string
     */
    public function extension()
    {
        if (($result = @pathinfo($this->path_, \PATHINFO_EXTENSION)) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'extension');
        }

        return $result;
    }

    /**
     * Extracts the filename from the path object.
     *
     * @return string[]|string
     */
    public function filename()
    {
        if (($result = @pathinfo($this->path_, \PATHINFO_FILENAME)) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'filename');
        }

        return $result;
    }

    /**
     * Determine if the given path is a file.
     *
     * @return bool
     */
    public function isFile()
    {
        return is_file($this->path_);
    }

    /**
     * Determine if the given path is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return is_writable($this->path_);
    }

    /**
     * Determine if the given path is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return is_readable($this->path_);
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param mixed $pattern
     * @param int   $flags
     *
     * @return array|false
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Create a symlink to the path. On Windows, a hard link is created if this path is a file.
     *
     * @return void
     */
    public function link(string $link)
    {
        if (!$this->exists()) {
            throw new PathNotFoundException($this->path_);
        }
        if ('Windows' === !\PHP_OS_FAMILY) {
            return symlink($this->path_, $link);
        }

        $mode = $this->isDirectory() ? 'J' : 'H';

        exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($this->path_));
    }

    /**
     * Set the permission of the file or directory path.
     *
     * @throws PathNotFoundException
     * @throws \InvalidArgumentException
     * @throws FileNotFoundException
     *
     * @return self
     */
    public function setPermissions(int $permissions)
    {
        if (!$this->exists()) {
            throw new PathNotFoundException($this->path_);
        }
        (new Ownership())->chmod($this->__toString(), $permissions);

        return Path($this->path_);
    }

    /**
     * Extract file permission from the path object.
     *
     * @throws UnableToRetrieveMetadataException
     *
     * @return int
     */
    public function getPermissions()
    {
        clearstatcache(false, $this->path_);
        error_clear_last();
        $fileperms = @fileperms($this->path_);
        if (false === $fileperms) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'fileperm');
        }

        return $fileperms;
    }

    /**
     * Get the file type of this file.
     *
     * @return string
     */
    public function type()
    {
        if (($result = @filetype($this->__toString())) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'filetype');
        }

        return $result;
    }

    /**
     * Get the mime-type of this file.
     *
     * @return string|false
     */
    public function mimeType()
    {
        $result = @finfo_file(finfo_open(\FILEINFO_MIME_TYPE), $this->__toString());
        if ((false === $result) || ('application/x-empty' === $result)) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'filemimetype');
        }

        return $result;
    }

    /**
     * Get the file's last modification timestamp.
     *
     * @return int
     */
    public function lastModified()
    {
        if (($result = @filemtime($this->__toString())) === false) {
            throw new UnableToRetrieveMetadataException($this->path_, error_get_last()['message'] ?? '', 'filemtime');
        }

        return $result;
    }

    /**
     * Normalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     *
     * This method is able to deal with both UNIX and Windows paths.
     */
    public function normalize()
    {
        return Path(str_replace('\\', '/', $this->path_));
    }

    /**
     * Returns whether the given path is on the local filesystem.
     */
    public function isLocal(): bool
    {
        return '' !== $this->path_ && false === mb_strpos($this->path_, '://');
    }

    /**
     * Join list of paths to the current path.
     *
     * @param string[] $paths
     *
     * @throws \RuntimeException
     *
     * @return string|Path
     */
    public function join(string ...$paths)
    {
        $finalPath = null;
        $wasScheme = false;

        $paths = array_merge([$this->path_], $paths ?? []);

        foreach ($paths as $path) {
            if ('' === $path) {
                continue;
            }

            if (null === $finalPath) {
                // For first part we keep slashes, like '/top', 'C:\' or 'phar://'
                $finalPath = $path;
                $wasScheme = false !== mb_strpos($path, '://');
                continue;
            }

            // Only add slash if previous part didn't end with '/' or '\'
            if (!\in_array(mb_substr($finalPath, -1), ['/', '\\'], true)) {
                $finalPath .= '/';
            }

            // If first part included a scheme like 'phar://' we allow \current part to start with '/', otherwise trim
            $finalPath .= $wasScheme ? $path : ltrim($path, '/');
            $wasScheme = false;
        }

        if (null === $finalPath) {
            return '';
        }

        return (Path($finalPath))->canonicalize();
    }

    /**
     * Canonicalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Furthermore, all "." and ".." segments are removed as far as possible.
     * ".." segments at the beginning of relative paths are not removed.
     *
     * ```php
     * echo Path::canonicalize("\symfony\puli\..\css\style.css");
     * // => /symfony/css/style.css
     *
     * echo Path::canonicalize("../css/./style.css");
     * // => ../css/style.css
     * ```
     *
     * This method is able to deal with both UNIX and Windows paths.
     */
    public function canonicalize()
    {
        if (('' === $this->path_) || (null === $this->path_)) {
            return Path($this->path_);
        }

        if (isset(self::$buffer[$this->path_])) {
            $this->path_ = self::$buffer[$this->path_];

            return Path($this->path_);
        }

        // Replace "~" with user's home directory.
        if ('~' === $this->path_[0]) {
            $this->path_ = PathHelper::getHomeDirectory().mb_substr($this->path_, 1);
        }

        [$root, $pathWithoutRoot] = $this->normalize()->split();

        $parts = PathHelper::findCanonicalParts($root, $pathWithoutRoot);

        // Add the root directory again
        self::$buffer[$this->path_] = $path = $root.implode('/', $parts);
        ++self::$bufferSize;

        // Clean up regularly to prevent memory leaks
        if (self::$bufferSize > self::CLEANUP_THRESHOLD) {
            self::$buffer = \array_slice(self::$buffer, -self::CLEANUP_SIZE, null, true);
            self::$bufferSize = self::CLEANUP_SIZE;
        }

        return Path($path);
    }

    /**
     * Splits a canonical path into its root directory and the remainder.
     *
     * If the path has no root directory, an empty root directory will be
     * returned.
     *
     * If the root directory is a Windows style partition, the resulting root
     * will always contain a trailing slash.
     *
     * list ($root, $path) = Path::split("C:/symfony")
     * // => ["C:/", "symfony"]
     *
     * list ($root, $path) = Path::split("C:")
     * // => ["C:/", ""]
     *
     * @return array{string, string} an array with the root directory and the remaining relative path
     */
    public function split(): array
    {
        $path = $this->path_;

        if (('' === $path) || (null === $path)) {
            return ['', ''];
        }

        // Remember scheme as part of the root, if any
        if (false !== ($schemeSeparatorPosition = mb_strpos($path, '://'))) {
            [$root, $path] = [mb_substr($path, 0, $schemeSeparatorPosition + 3), mb_substr($path, $schemeSeparatorPosition + 3)];
        } else {
            $root = '';
        }

        $length = mb_strlen($path);
        if (0 === mb_strpos($path, '/')) {
            [$root, $path] = [$root.'/', $length > 1 ? mb_substr($path, 1) : ''];
        } elseif ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            [$root, $path] = (2 === $length) ? [$root."$path.'/'", ''] : (('/' === $path[2]) ? [$root.mb_substr($path, 0, 3), ($length > 3 ? mb_substr($path, 3) : '')] : [$root, $path]);
        }

        return [$root, $path];
    }
}
