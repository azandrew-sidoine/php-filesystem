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

class UnableToRetrieveMetadataException extends Exception
{
    public function __construct(string $path, string $errorMessage, string $attribute)
    {
        parent::__construct(sprintf('Cannot retrieve %s informations at "%s". %s', $attribute, $path, $errorMessage));
    }
}
