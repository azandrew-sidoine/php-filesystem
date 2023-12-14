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

namespace Drewlabs\Filesystem\Adapters;

use Drewlabs\Filesystem\Directory;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\SymbolicLinkEncounteredException;
use Drewlabs\Filesystem\File;
use Drewlabs\Filesystem\Path;
use Drewlabs\Filesystem\PathPrefixer;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class LocalFileSystemAdapter implements FilesystemAdapter
{
    /**
     * @var int
     */
    public const SKIP_LINKS = 0001;

    /**
     * @var int
     */
    public const DISALLOW_LINKS = 0002;

    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var int
     */
    private $writeFlags;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * @var VisibilityConverter
     */
    private $visibility;

    public function __construct(
        string $location,
        int $writeFlags = \LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS
    ) {
        $this->prefixer = new PathPrefixer($location, \DIRECTORY_SEPARATOR);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibility = new PortableVisibilityConverter();
        $this->ensureDirectoryExists($location, $this->resolveDirectoryVisibility());
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->prefixer->prefix(new Path($path));
        $this->ensureDirectoryExists(
            \dirname($path),
            $this->resolveDirectoryVisibility($config->get(Config::OPTION_DIRECTORY_VISIBILITY))
        );
        (new File($path))->write($contents, $this->writeFlags);
        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($this->prefixer->stripPrefix($path), (string) $visibility);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $path = $this->prefixer->prefix(new Path($path));
        $this->ensureDirectoryExists(
            \dirname($path),
            $this->resolveDirectoryVisibility($config->get(Config::OPTION_DIRECTORY_VISIBILITY))
        );
        (new File($path))->write($contents, $this->writeFlags);
        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($this->prefixer->stripPrefix($path), (string) $visibility);
        }
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefix($path);
        $file = new File($location);
        if (!$file->isFile()) {
            throw new FileNotFoundException($path, 'Delete error');
        }
        $file->delete();
    }

    public function deleteDirectory(string $prefix): void
    {
        $location = $this->prefixer->prefix($prefix);
        /**
         * @var Path&Directory
         */
        $directory = new Directory($location);
        if (!$directory->isDirectory()) {
            return;
        }
        $directory->delete();
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->prefixer->prefix($path);

        /**
         * @var Path&Directory
         */
        $directory = new Directory($location);
        if (!$directory->isDirectory()) {
            return;
        }

        /** @var \SplFileInfo[] $iterator */
        $iterator = $deep ? $directory->recursiveIterator() : $directory->iterator();

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isLink()) {
                if ($this->linkHandling & self::SKIP_LINKS) {
                    continue;
                }
                throw new SymbolicLinkEncounteredException($fileInfo->getPathname());
            }

            $path = $this->prefixer->stripPrefix($fileInfo->getPathname());
            $lastModified = $fileInfo->getMTime();
            $isDirectory = $fileInfo->isDir();
            $permissions = $fileInfo->getPerms();
            $visibility = $isDirectory ? $this->visibility->inverseForDirectory($permissions) : $this->visibility->inverseForFile($permissions);

            yield $isDirectory ? new DirectoryAttributes($path, $visibility, $lastModified) : new FileAttributes(
                str_replace('\\', '/', $path),
                $fileInfo->getSize(),
                $visibility,
                $lastModified
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->prefixer->prefix($source);
        $destinationPath = $this->prefixer->prefix($destination);
        $this->ensureDirectoryExists(
            \dirname($destinationPath),
            $this->resolveDirectoryVisibility($config->get(Config::OPTION_DIRECTORY_VISIBILITY))
        );
        (new Path($sourcePath))->isDirectory() ? (new Directory($sourcePath))->move($destinationPath) : (new File($sourcePath))->move($destinationPath);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->prefixer->prefix($source);
        $destinationPath = $this->prefixer->prefix($destination);
        $this->ensureDirectoryExists(
            \dirname($destinationPath),
            $this->resolveDirectoryVisibility($config->get(Config::OPTION_DIRECTORY_VISIBILITY))
        );
        (new Path($sourcePath))->isDirectory() ? (new Directory($sourcePath))->copy($destinationPath) : (new File($sourcePath))->copy($destinationPath);
    }

    public function read(string $path): string
    {
        $location = $this->prefixer->prefix($path);
        $file = new File($location);
        if ($file->isFile()) {
            return $file->read();
        }
        throw new FileNotFoundException($path, 'Read error');
    }

    public function readStream(string $path)
    {
        $location = $this->prefixer->prefix($path);
        error_clear_last();
        $contents = @fopen($location, 'r');
        if (false === $contents) {
            throw new ReadFileException(sprintf('Unable to read from path %s, %s', $path, error_get_last()['message'] ?? ''));
        }

        return $contents;
    }

    public function fileExists(string $location): bool
    {
        return (new File($this->prefixer->prefix($location)))->isFile();
    }

    public function createDirectory(string $path, Config $config): void
    {
        $location = $this->prefixer->prefix($path);
        $visibility = $config->get(Config::OPTION_VISIBILITY, $config->get(Config::OPTION_DIRECTORY_VISIBILITY));
        $permissions = $this->resolveDirectoryVisibility($visibility);
        (new Directory($location))->create($permissions, true);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $path = $this->prefixer->prefix($path);
        $visibility = is_dir($path) ? $this->visibility->forDirectory($visibility) : $this->visibility->forFile(
            $visibility
        );
        (new Path($path))->setPermissions($visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $permissions = (new Path($this->prefixer->prefix($path)))->getPermissions() & 0777;
        $visibility = $this->visibility->inverseForFile($permissions);

        return new FileAttributes($path, null, $visibility);
    }

    public function mimeType(string $path): FileAttributes
    {
        $location = $this->prefixer->prefix($path);

        return new FileAttributes($path, null, null, null, (new File($location))->mimeType());
    }

    public function lastModified(string $path): FileAttributes
    {
        $location = $this->prefixer->prefix($path);

        return new FileAttributes($path, null, null, (new File($location))->lastModified());
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path, (new File($this->prefixer->prefix($path)))->getSize());
    }

    protected function ensureDirectoryExists(string $dirname, int $visibility)
    {
        return (new Directory($dirname))->createIfNotExists($visibility, true);
    }

    private function resolveDirectoryVisibility(string $visibility = null): int
    {
        return null === $visibility ? $this->visibility->defaultForDirectories() : $this->visibility->forDirectory(
            $visibility
        );
    }
}
