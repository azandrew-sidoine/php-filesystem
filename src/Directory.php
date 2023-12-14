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

use Drewlabs\Filesystem\Exceptions\CreateDirectoryException;
use Drewlabs\Filesystem\Exceptions\DeleteDirectoryException;
use Drewlabs\Filesystem\Exceptions\MoveException;
use FilesystemIterator;
use Iterator;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @method bool         isWritable()
 * @method bool         isReadable()
 * @method basename     basename()
 * @method bool         isDirectory()
 * @method int          getPermissions()
 * @method self         setPermissions(int $permissions)
 * @method string|false dirname()
 */
class Directory
{
    private const PROXY_METHODS = [
        'isWritable',
        'isReadable',
        'basename',
        'isDirectory',
        'getPermissions',
        'setPermissions',
        'dirname',
    ];
    /**
     * @var Path
     */
    private $path_;

    /**
     * Create an instance of the directory class.
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
        if (!\in_array($name, self::PROXY_METHODS, true)) {
            throw new \BadMethodCallException("Method $name does not exists on ".__CLASS__);
        }
        $func_arguments = \func_get_args();
        $arguments = \array_slice($func_arguments, 1);

        return $this->path_->{$name}(...$arguments);
    }

    public function __toString()
    {
        return $this->path_->__toString();
    }

    /**
     * Return the path to the current directory object.
     */
    public function getPath(): ?string
    {
        return $this->__toString();
    }

    /**
     * Creates an iterator of files in the specified directory.
     *
     * If $depth is specified, the implementation loop up to the deepth specified by the user
     *
     * @throws DirectoryNotFoundException
     * @throws \LogicException
     *
     * @return \Iterator<mixed, SplFileInfo>
     */
    public function files(bool $hidden = false, int $depth = null)
    {
        $finder = null !== $depth ? Finder::create()->depth($depth) : Finder::create();

        return $finder
            ->files()
            ->ignoreDotFiles(!$hidden)
            ->in($this->__toString())
            ->sortByName()
            ->getIterator();
    }

    /**
     * Creates an iterable list of directories in the specified directory.
     *
     * @throws DirectoryNotFoundException
     * @throws \LogicException
     *
     * @return \Generator<int, string, mixed, void>
     */
    public function directories()
    {
        $finder = Finder::create()
            ->in($this->__toString())
            ->directories()
            ->sortByName()
            ->getIterator();

        foreach ($finder as $dir) {
            yield $dir->getPathname();
        }
    }

    /**
     * Ensure that a directory exists by creating it if it does not.
     *
     * @return self
     */
    public function createIfNotExists(int $mode = 0755, bool $recursive = false, bool $force = false)
    {
        if (!$this->isDirectory()) {
            return $this->create($mode, $recursive, $force);
        }

        return $this;
    }

    /**
     * Create a directory using the provided parameters.
     *
     * @param int  $mode
     * @param bool $recursive
     * @param bool $force
     *
     * @return bool
     */
    public function create(?int $mode = 0755, ?bool $recursive = false, ?bool $force = false)
    {
        if ($force) {
            if (!@mkdir($this->__toString(), $mode, true)) {
                $mkdirError = error_get_last();
            }
            clearstatcache(false, $this->__toString());
            if (!$this->isDirectory()) {
                $errorMessage = $mkdirError['message'] ?? '';

                throw new CreateDirectoryException($this->__toString(), $errorMessage);
            }
        } else {
            @mkdir($this->__toString(), $mode, $recursive);
        }

        return $this;
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param array $invalidPaths
     *
     * @return bool
     */
    public function copy(
        string $destination,
        int $options = null,
        array &$invalidPaths = null
    ) {
        if (!$this->isDirectory()) {
            if ($invalidPaths) {
                $invalidPaths[] = $this->__toString();
            }

            return false;
        }

        $options = $options ?? \FilesystemIterator::SKIP_DOTS;

        // TODO Create the destination directory if not exists
        // With read-write mode
        $directory = (new self($destination))->createIfNotExists(0777);

        // Create a FileSystemIterator for looping trough the directory
        $iterator = new \FilesystemIterator($this->__toString(), $options);

        $successful = true;
        foreach ($iterator as $item) {
            // Construct the path to the target element
            $target = rtrim($directory->getPath(), \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$item->getBasename();
            // If the $item is a directory, perform a recursive call
            $path = $item->getPathname();
            if ($item->isDir()) {
                // Even though we continue to copy files we need to notify user of failed $path
                $copied = (new self($path))->copy($target, $options);
            } else {
                // Call the file copy method
                $copied = (new File($path))->copy($target);
            }
            $successful = $successful && $copied;
            if ($invalidPaths && !$copied) {
                $invalidPaths[] = $path;
            }
        }

        return $successful;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param bool $preserve
     *
     * @return bool
     */
    public function delete($preserve = false)
    {
        if (!$this->isDirectory()) {
            return false;
        }

        $items = new \FilesystemIterator($this->__toString());

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                (new self($item->getPathname()))->delete();
                continue;
            }
            // If it's a file path, like a file delete the file
            (new File($item->getPathname()))->delete();
        }
        if (!$preserve) {
            error_clear_last();
            if (!@rmdir($this->__toString())) {
                throw new DeleteDirectoryException($this->__toString(), error_get_last()['message'] ?? '');
            }
            $this->path_ = null;
        }

        return true;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * @return bool
     */
    public function clean()
    {
        return $this->delete(true);
    }

    /**
     * Move a directory.
     *
     * @param bool $overwrite
     *
     * @return bool
     */
    public function move(string $to, $overwrite = false)
    {
        if ($overwrite && (new Path($to))->isDirectory() && !$this->delete()) {
            return false;
        }
        if (!($result = true === @rename($this->__toString(), $to))) {
            throw new MoveException($this->__toString(), $to);
        }
        $this->path_ = new Path($to);

        return $result;
    }

    /**
     * Tells if path exists or not.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->isDirectory();
    }

    public function recursiveIterator(
        int $mode = \RecursiveIteratorIterator::SELF_FIRST
    ): \Generator {
        yield from new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->__toString(), \FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    public function iterator(): \Generator
    {
        $iterator = new \DirectoryIterator($this->__toString());
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }
            yield $item;
        }
    }
}
