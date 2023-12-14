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

namespace Drewlabs\Filesystem\Contracts;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;

interface Filesystem extends FilesystemOperator
{
    /**
     * Return the filesystem adapter instance
     * 
     * @return FilesystemAdapter
     */
    public function adapter();

    /**
     * Returns the full path to the user specified resource
     * 
     * @param string $path 
     * @return string 
     */
    public function url(string $path);
}
