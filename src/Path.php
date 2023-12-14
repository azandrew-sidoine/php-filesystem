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

use Drewlabs\Filesystem\Exceptions\PathNotFoundException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;

class Path
{
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
        $this->path_ = $path;
    }

    public function __toString()
    {
        return $this->path_;
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
        return $this->isDirectory() || $this->isFile();
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
     */
    public function setPermissions(int $permissions)
    {
        if (!$this->exists()) {
            throw new PathNotFoundException($this->path_);
        }
        (new Ownership())->chmod($this->__toString(), $permissions);

        return $this;
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
}
