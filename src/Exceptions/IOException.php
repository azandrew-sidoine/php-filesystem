<?php

namespace Drewlabs\Filesystem\Exceptions;

use Exception;

class IOException extends Exception
{

    public static function open(string $path)
    {
        return new static("Can not open file located at path $path");
    }
}