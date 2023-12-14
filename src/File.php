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

use Drewlabs\Filesystem\Exceptions\CopyFileException;
use Drewlabs\Filesystem\Exceptions\DeleteFileException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\MoveException;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use Drewlabs\Filesystem\Exceptions\WriteOperationFailedException;
use Drewlabs\Filesystem\Streams\Reader;

use function Drewlabs\Filesystem\Proxy\File;
use function Drewlabs\Filesystem\Proxy\Path;

use Drewlabs\Psr7Stream\Stream;

/**
 * @method bool   isDirectory()
 * @method bool   exists()
 * @method string basename()
 * @method string dirname()
 * @method string extension()
 * @method string filename()
 * @method bool   isFile()
 * @method bool   isWritable()
 * @method bool   isReadable()
 * @method array  glob($pattern, $flags = 0)
 * @method void   link($link)
 * @method string type()
 * @method string mimeType()
 * @method int    lastModified()
 * @method int    getPermissions()
 * @method self   setPermissions(int $permissions)
 */
class File
{
    /**
     * @var Path
     */
    private $path_;

    /**
     * Creates an instance of the file class.
     *
     * @param string|Path $path
     *
     * @return self
     */
    public function __construct($path)
    {
        $this->path_ = \is_string($path) ? new Path($path) : $path;
    }

    public function __call($name, $arguments)
    {
        $func_arguments = \func_get_args();
        $arguments = \array_slice($func_arguments, 1);

        return $this->path_->{$name}(...$arguments);
    }

    public function __toString()
    {
        return $this->path_->__toString();
    }

    public function read(int $offset = 0, ?int $length = null, $lock = false, ?\Closure $then = null)
    {
        if ($this->path_->isFile()) {
            $callable = $then ?? static function ($value) {
                return $value;
            };
            return $callable($lock ? $this->sharedRead($offset, $length) : $this->getContents($offset, $length));
        }
        throw new FileNotFoundException($this->__toString());
    }

    /**
     * Extracts the file size information from the file path.
     *
     * @return int|false
     */
    public function getSize()
    {
        if ($this->isDirectory()) {
            throw new UnableToRetrieveMetadataException($this->__toString(), '', 'filesize');
        }
        if (($result = @filesize($this->__toString())) === false) {
            throw new UnableToRetrieveMetadataException($this->__toString(), error_get_last()['message'] ?? '', 'filesize');
        }
        return $result;
    }

    /**
     * Get content from the file using a shared lock.
     *
     * @throws ReadFileException
     *
     * @return string
     */
    public function sharedRead(?int $offset = null, ?int $length = null)
    {
        // Open the file in read binary mode
        $handle = fopen($this->__toString(), 'r');
        $contents = '';
        if ($handle) {
            try {
                if (flock($handle, \LOCK_SH)) {
                    // Clear file status cache
                    clearstatcache(true, $this->__toString());
                    if ($offset) {
                        fseek($handle, $offset);
                    }
                    $contents = fread($handle, $length ?? $this->getSize());
                    // UNLOCK file handle
                    flock($handle, \LOCK_UN);
                }
            } catch (\Throwable $e) {
                throw new ReadFileException($e->getMessage(), 500, $e);
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Reads entire content into a string
     * 
     * @param int $offset 
     * @param null|int $length 
     * @return string|false 
     * @throws UnableToRetrieveMetadataException 
     */
    public function getContents(int $offset = 0, ?int $length = null)
    {
        // Clear cache so that next call does not have a 
        clearstatcache(true, $this->__toString());
        return file_get_contents(
            $this->__toString(),
            false,
            null,
            $offset ?? 0,
            $length ?? $this->getSize()
        );
    }

    /**
     * Write to the file.
     *
     * @param resource|string|array $contents
     * @param bool                  $lock
     *
     * @return self
     */
    public function write($contents, $lock = false)
    {
        $size = \is_resource($contents) ?
            @file_put_contents($this->__toString(), Reader::new($contents)->getContents(), $lock ? \LOCK_EX : 0) :
            @file_put_contents($this->__toString(), $contents, $lock ? \LOCK_EX : 0);
        if (false === $size) {
            throw new WriteOperationFailedException($this->__toString(), error_get_last()['message'] ?? '');
        }
        return File($this->path_->__toString());
    }

    /**
     * Append content to a file.
     *
     * @param resource|string|array $contents
     *
     * @return self
     */
    public function append($contents)
    {
        file_put_contents($this->__toString(), $contents, \FILE_APPEND);

        return File($this->path_->__toString());
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param string $content
     *
     * @return void
     */
    public function replace($content)
    {
        clearstatcache(true, $this->__toString());

        $path = realpath($this->__toString()) ?: $this->__toString();

        $tempPath = tempnam(\dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Calculate the hash of the file.
     *
     * @return string|false
     */
    public function hash()
    {
        return md5_file($this->__toString());
    }

    /**
     * Remove the file from the filesytem.
     *
     * @return false
     */
    public function delete()
    {
        $success = true;
        if (!@unlink($this->__toString())) {
            throw new DeleteFileException($this->__toString(), error_get_last()['message'] ?? '');
        }
        $this->path_ = null;

        return $success;
    }

    /**
     * Move the file pointer to a new location.
     *
     * @return bool
     */
    public function move(string $to)
    {
        if (Path($to)->isDirectory()) {
            $to = sprintf('%s%s%s', $to, \DIRECTORY_SEPARATOR, $this->basename());
        }

        if (!($result = true === @rename($this->__toString(), $to))) {
            throw new MoveException($this->path_->__toString(), $to);
        }
        // Change the string pointer of the file to th new destination
        $this->path_ = new Path($to);

        return $result;
    }

    /**
     * Create a copy of the file.
     *
     * @return bool
     */
    public function copy(string $to)
    {
        $path = Path($to);
        if ($path->isDirectory()) {
            $to = sprintf('%s%s%s', $to, \DIRECTORY_SEPARATOR, $this->basename());
        }
        if (!($result = @copy($this->__toString(), $to))) {
            throw new CopyFileException($this->__toString(), $to);
        }

        return $result;
    }

    /**
     * Get the name of this file.
     *
     * @return string
     */
    public function name()
    {
        return pathinfo($this->__toString(), \PATHINFO_FILENAME);
    }
}
