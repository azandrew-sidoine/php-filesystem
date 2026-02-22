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

namespace Drewlabs\Filesystem\Exceptions;

use Drewlabs\Filesystem\Exceptions\Compact\BaseException as Exception;

class CopyFileException extends Exception
{
    /** @var string */
    private $destination;

    /** @var string */
    private $path;

    public function __construct(string $path, string $destination, ?string $message = null)
    {
        parent::__construct(sprintf('Cannot copy file at "%s": %s', $path, $message));
        $this->destination = $destination;
        $this->path = $path;
    }

    public function source(): string
    {
        return $this->path;
    }

    public function destination(): string
    {
        return $this->destination;
    }
}
