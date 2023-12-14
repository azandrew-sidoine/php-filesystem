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

namespace Drewlabs\Filesystem\Facades;

use Drewlabs\Filesystem\Directory as FilesystemDirectory;

/**
 * @method static \Iterator<\SplFileInfo> files(string $path, bool $hidden = false, ?int $depth = null)
 * @method static \Generator<string>      directories(string $path)
 */
class Directory
{
    public static function __callStatic($name, $arguments)
    {
        if (0 === \count($arguments)) {
            throw new \InvalidArgumentException('Total number of arguments required by the static call must be at least 1');
        }
        $arguments = array_values($arguments);
        $path = $arguments[0];
        $args = \array_slice($arguments, 1);

        return (new FilesystemDirectory($path))->{$name}(...$args);
    }
}
