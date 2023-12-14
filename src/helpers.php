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

namespace Drewlabs\Filesystem\Proxy;

    use Drewlabs\Filesystem\Adapters\LocalFileSystemAdapter;
    use Drewlabs\Filesystem\Adapters\Manager;
    use Drewlabs\Filesystem\Directory;
    use Drewlabs\Filesystem\File;
    use Drewlabs\Filesystem\Ownership;
    use Drewlabs\Filesystem\Path;

    /**
     * Creates an instance of the {File} Object.
     *
     * @return File
     */
    function File(string $path)
    {
        return new File($path);
    }

    /**
     * Directory instance creator object.
     *
     * @return Directory
     */
    function Directory(string $path)
    {
        return new Directory($path);
    }

    /**
     * Path instance creator function.
     *
     * @return Path
     */
    function Path(string $path)
    {
        return new Path($path);
    }

    /**
     * Creates an instance of the Owhnership helper class.
     *
     * @return Ownership
     */
    function Chmod()
    {
        return new Ownership();
    }

    /**
     * Creates an instance of the {@link LocalFileSystemAdapter} implementation.
     *
     * @return LocalFileSystemAdapter
     */
    function LocalFileSystem(
        string $location,
        int $writeFlags = \LOCK_EX,
        int $linkHandling = LocalFileSystemAdapter::DISALLOW_LINKS
    ) {
        return new LocalFileSystemAdapter($location, $writeFlags, $linkHandling);
    }

    /**
     * Proxy to the of the {@link \Drewlabs\Filesystem\Adapters\Manager} constructor.
     *
     * @return Manager
     */
    function FilesystemManager()
    {
        return new Manager();
    }
