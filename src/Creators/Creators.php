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

namespace Drewlabs\Filesystem\Exceptions\Creators;

use Drewlabs\Filesystem\Directory;
use Drewlabs\Filesystem\File;
use Drewlabs\Filesystem\Ownership;
use Drewlabs\Filesystem\Path;

/**
 * New file object creator fn.
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
