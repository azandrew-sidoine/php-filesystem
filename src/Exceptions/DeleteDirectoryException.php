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

namespace Drewlabs\Filesystem\Exceptions;

use Drewlabs\Filesystem\Exceptions\Compact\BaseException as Exception;

class DeleteDirectoryException extends Exception
{
    public function __construct(string $dirname, string $message = null)
    {
        $message = "Error while deleting directory at {$dirname}. {$message}";
        parent::__construct(rtrim($message));
    }
}
